<?php

namespace App\Services;

use App\Models\DomainTld;
use App\Support\DomainPricing;
use Illuminate\Support\Facades\Cache;

/**
 * Turns whatever the customer typed into a list of domains they can buy.
 *
 * The search box takes three different kinds of input and the customer should
 * not have to know which one they are giving us:
 *
 *   "myshop"                  → a name; check it across the popular TLDs
 *   "myshop.com"              → a specific domain; check that first, then others
 *   "ร้านขายเสื้อผ้าออนไลน์"      → a description; ask for names that fit it
 *
 * Thai input is always a description. The registry alphabet is ASCII, so a
 * Thai phrase cannot be a domain label — but it is an excellent prompt, and
 * the customer who types their shop's name in Thai gets suggestions rather
 * than an error telling them their own language is invalid.
 *
 * Results are cached for a minute. That is not a performance tweak: upstream
 * allows 90 calls a minute for the entire account, and a search page without
 * a cache lets one impatient visitor lock every other customer out of
 * checkout. The window is short enough that a name someone else registers
 * mid-search is caught again at the confirm step, which re-checks before
 * touching money.
 */
class DomainSearchService
{
    public const CACHE_SECONDS = 60;

    /** How many suggestions to ask for beyond the exact match. */
    public const MAX_ALTERNATIVES = 12;

    public function __construct(
        protected HostingerApiService $api,
    ) {}

    /**
     * @return array{
     *     query: string,
     *     mode: string,
     *     exact: array<string,mixed>|null,
     *     results: array<int,array<string,mixed>>,
     *     unavailable: array<int,array<string,mixed>>,
     *     error: string|null,
     * }
     */
    public function search(string $input): array
    {
        $input = trim($input);

        if ($input === '') {
            return $this->empty($input, 'empty');
        }

        $mode = $this->detectMode($input);

        return match ($mode) {
            'description' => $this->searchByDescription($input),
            'domain' => $this->searchByName($input),
            default => $this->empty($input, $mode),
        };
    }

    /**
     * Is this a name to check, or a phrase to interpret?
     *
     * Anything with whitespace, non-ASCII, or more words than a label can
     * hold is a description. A bare label is a name.
     */
    public function detectMode(string $input): string
    {
        if (preg_match('/[^\x20-\x7E]/', $input)) {
            return 'description';
        }

        if (str_contains(trim($input), ' ')) {
            return 'description';
        }

        return 'domain';
    }

    /**
     * @return array<string,mixed>
     */
    protected function searchByName(string $input): array
    {
        [$label, $typedTld] = $this->splitDomain($input);

        if ($label === '') {
            return $this->empty($input, 'domain', __('ชื่อโดเมนไม่ถูกต้อง'));
        }

        // The TLD the customer typed always goes first, then the defaults.
        $tlds = $this->tldsToCheck($typedTld);

        $rows = $this->cached("name:{$label}:" . implode(',', $tlds), function () use ($label, $tlds) {
            return $this->api->checkAvailability($label, $tlds, withAlternatives: false);
        });

        if ($rows === null) {
            return $this->empty($input, 'domain', $this->unavailableMessage());
        }

        $priced = $this->priceRows($rows);

        // Alternatives are only worth the extra call when the thing they
        // actually asked for is gone.
        $exactKey = $typedTld ? $label . '.' . $typedTld : null;
        $exact = $exactKey ? ($priced['available'][$exactKey] ?? $priced['taken'][$exactKey] ?? null) : null;

        $available = array_values($priced['available']);
        $taken = array_values($priced['taken']);

        if ($available === [] || ($exact !== null && ! ($exact['available'] ?? false))) {
            $available = array_merge($available, $this->alternativesFor($label, $typedTld));
        }

        return [
            'query' => $input,
            'mode' => 'domain',
            'exact' => $exact,
            'results' => $this->dedupe($available),
            'unavailable' => $taken,
            'error' => null,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    protected function searchByDescription(string $input): array
    {
        $tlds = $this->tldsToCheck(null);

        $rows = $this->cached('desc:' . md5($input) . ':' . implode(',', $tlds), function () use ($input, $tlds) {
            return $this->api->suggestFromDescription($input, $tlds);
        });

        if ($rows === null) {
            return $this->empty($input, 'description', $this->unavailableMessage());
        }

        $priced = $this->priceRows($rows);

        return [
            'query' => $input,
            'mode' => 'description',
            'exact' => null,
            'results' => $this->dedupe(array_values($priced['available'])),
            'unavailable' => array_values($priced['taken']),
            'error' => null,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    protected function alternativesFor(string $label, ?string $tld): array
    {
        $base = $label . '.' . ($tld ?: 'com');

        $rows = $this->cached("alt:{$base}", function () use ($base, $tld) {
            return $this->api->suggestFromDomain($base, $this->tldsToCheck($tld));
        });

        if ($rows === null) {
            return [];
        }

        $priced = $this->priceRows($rows);

        return array_slice(array_values($priced['available']), 0, self::MAX_ALTERNATIVES);
    }

    /**
     * Attach our price to each upstream row, and drop TLDs we do not sell.
     *
     * A row we have no catalogue entry for is skipped rather than shown
     * priceless — an "available!" with no price is a promise we cannot keep,
     * because we would have no item id to order it with.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array{available: array<string,array<string,mixed>>, taken: array<string,array<string,mixed>>}
     */
    protected function priceRows(array $rows): array
    {
        $catalogue = $this->catalogue();

        $available = [];
        $taken = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $domain = strtolower((string) ($row['domain'] ?? ''));
            if ($domain === '' || ! str_contains($domain, '.')) {
                continue;
            }

            $tld = $this->tldOf($domain);
            $record = $catalogue[$tld] ?? null;

            if (! $record || ! $record->item_id_register) {
                continue;
            }

            $isAvailable = (bool) ($row['is_available'] ?? $row['available'] ?? false);

            $entry = [
                'domain' => $domain,
                'label' => explode('.', $domain, 2)[0],
                'tld' => $tld,
                'available' => $isAvailable,
                'is_premium' => (bool) ($row['is_premium'] ?? false),
                'price' => $record->registerPriceThb(),
                'price_display' => DomainPricing::format($record->registerPriceThb()),
                'renew_price' => $record->renewPriceThb(),
                'renew_price_display' => DomainPricing::format($record->renewPriceThb()),
                'renewal_is_dearer' => $record->renewalIsDearer(),
                'featured' => $record->is_featured,
            ];

            if ($isAvailable) {
                $available[$domain] = $entry;
            } else {
                $taken[$domain] = $entry;
            }
        }

        return ['available' => $available, 'taken' => $taken];
    }

    /**
     * A premium domain is priced by the registry per name, not per TLD, so
     * our catalogue price would be wrong — and wrong downwards, which means
     * selling at a loss. They are shown but not orderable until the order
     * path can read a per-name price.
     *
     * @param  array<int,array<string,mixed>>  $rows
     * @return array<int,array<string,mixed>>
     */
    protected function dedupe(array $rows): array
    {
        $seen = [];
        $out = [];

        foreach ($rows as $row) {
            $key = $row['domain'] ?? null;
            if (! $key || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $row;
        }

        // Exact-length labels first, then cheapest — the customer is usually
        // after the shortest thing they can get.
        usort($out, function ($a, $b) {
            return [$a['is_premium'], strlen($a['label']), $a['price']]
                <=> [$b['is_premium'], strlen($b['label']), $b['price']];
        });

        return $out;
    }

    /**
     * The TLDs we sell, keyed by TLD, cached for a few minutes because the
     * search page reads it on every keystroke-driven request.
     *
     * @return array<string,DomainTld>
     */
    public function catalogue(): array
    {
        return Cache::remember('domain.catalogue', 300, function () {
            return DomainTld::active()->orderBy('sort_order')->get()->keyBy('tld')->all();
        });
    }

    /**
     * @return array<int,string>
     */
    protected function tldsToCheck(?string $typedTld): array
    {
        $defaults = Cache::remember('domain.search_tlds', 300, function () {
            $rows = DomainTld::searchDefault()->orderBy('sort_order')->pluck('tld')->all();

            return $rows !== [] ? $rows : ['com'];
        });

        if ($typedTld && ! in_array($typedTld, $defaults, true)) {
            // Only worth asking about a TLD we can actually sell.
            if (isset($this->catalogue()[$typedTld])) {
                array_unshift($defaults, $typedTld);
            }
        }

        // The availability endpoint is metered separately and generously, but
        // a 30-TLD sweep on every keystroke is still rude. Cap it.
        return array_slice(array_values(array_unique($defaults)), 0, 10);
    }

    /**
     * @return array{0:string,1:string|null} [label, tld]
     */
    public function splitDomain(string $input): array
    {
        $input = strtolower(trim($input));
        $input = preg_replace('#^https?://#', '', $input);
        $input = explode('/', $input, 2)[0];
        $input = trim($input, '.');

        if (! str_contains($input, '.')) {
            return [$this->sanitizeLabel($input), null];
        }

        $parts = explode('.', $input, 2);

        return [$this->sanitizeLabel($parts[0]), $this->sanitizeTld($parts[1] ?? '')];
    }

    protected function sanitizeLabel(string $label): string
    {
        // Registry rules: letters, digits and hyphens, not starting or ending
        // with one. Anything else the customer typed is dropped rather than
        // sent upstream to be rejected.
        $label = preg_replace('/[^a-z0-9-]/', '', $label) ?? '';

        return trim(substr($label, 0, 63), '-');
    }

    protected function sanitizeTld(string $tld): ?string
    {
        $tld = preg_replace('/[^a-z0-9.-]/', '', $tld) ?? '';

        return $tld !== '' ? $tld : null;
    }

    protected function tldOf(string $domain): string
    {
        $parts = explode('.', $domain, 2);

        return $parts[1] ?? '';
    }

    /**
     * @return array<mixed>|null
     */
    protected function cached(string $key, \Closure $fetch): ?array
    {
        $cacheKey = 'domain.search.' . md5($key);

        $hit = Cache::get($cacheKey);
        if (is_array($hit)) {
            return $hit;
        }

        $fresh = $fetch();

        // A failure is not cached — the next visitor should get a real try
        // rather than inherit a minute-old outage.
        if (is_array($fresh)) {
            Cache::put($cacheKey, $fresh, self::CACHE_SECONDS);
        }

        return $fresh;
    }

    protected function unavailableMessage(): string
    {
        return 'ระบบค้นหาโดเมนไม่พร้อมใช้งานชั่วคราว กรุณาลองใหม่อีกครั้ง';
    }

    /**
     * @return array<string,mixed>
     */
    protected function empty(string $query, string $mode, ?string $error = null): array
    {
        return [
            'query' => $query,
            'mode' => $mode,
            'exact' => null,
            'results' => [],
            'unavailable' => [],
            'error' => $error,
        ];
    }
}
