<?php

namespace App\Console\Commands;

use App\Models\DomainTld;
use App\Services\HostingerApiService;
use App\Support\DomainPricing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Pull real domain prices from the registrar into our catalogue.
 *
 * Until this has run at least once, the TLD table holds the placeholder
 * costs from its seeding migration and every row has a null item id, which
 * the order path treats as "not for sale". So this command is what actually
 * opens the shop.
 *
 * It is also what keeps it honest: the registrar reprices whenever it likes,
 * and a catalogue that is six months stale is a catalogue selling .io below
 * cost. Run it on a schedule.
 */
class SyncDomainCatalogue extends Command
{
    protected $signature = 'domains:sync-catalogue
                            {--dry : Show what would change without writing}
                            {--prune : Deactivate TLDs the registrar no longer sells}';

    protected $description = 'Sync domain TLD prices and item ids from the registrar catalogue';

    /**
     * Currencies the catalogue offered on domain rows, for the message shown
     * when none of them could be used.
     *
     * @var array<string,true>
     */
    protected array $seenCurrencies = [];

    public function handle(HostingerApiService $api): int
    {
        if (! $api->isConfigured()) {
            // Not a failure — the shop simply is not set up yet. Returning
            // FAILURE here made the scheduler write an ERROR line on every run
            // until somebody pasted a token, which is noise in a log people are
            // supposed to trust.
            $this->warn('No registrar API token configured. Set it at /admin/domains first.');

            return self::SUCCESS;
        }

        $this->info('Fetching catalogue…');
        $items = $api->getCatalog();

        if ($items === null) {
            $this->error('Catalogue request failed. Check the log for details.');

            return self::FAILURE;
        }

        $parsed = $this->parseDomainItems($items);

        if ($parsed === []) {
            $this->warn('The catalogue came back with no domain items. Nothing to do.');
            $this->line('If this is unexpected, the item id format may have changed — see parseDomainItems().');
            // Say what WAS there. The first time this fired, the catalogue held
            // 889 domain items priced in THB and the parser wanted USD; without
            // this line the only clue was "nothing to do".
            if ($this->seenCurrencies !== []) {
                $this->line('Currencies seen on domain rows: ' . implode(', ', array_keys($this->seenCurrencies)));
            }

            return self::FAILURE;
        }

        $this->line(sprintf('Found %d sellable TLDs.', count($parsed)));

        $dry = (bool) $this->option('dry');
        $created = $updated = $unchanged = 0;
        $rows = [];

        foreach ($parsed as $tld => $data) {
            $record = DomainTld::firstOrNew(['tld' => $tld]);
            $isNew = ! $record->exists;

            $before = [
                'cost' => $record->cost_usd_cents,
                'renew' => $record->renew_cost_usd_cents,
                'item' => $record->item_id_register,
                'currency' => $record->cost_currency ?: 'USD',
            ];

            $record->fill([
                'item_id_register' => $data['item_id_register'] ?? $record->item_id_register,
                'item_id_renew' => $data['item_id_renew'] ?? $record->item_id_renew,
                'cost_usd_cents' => $data['cost'] ?? $record->cost_usd_cents,
                'renew_cost_usd_cents' => $data['renew'] ?? $data['cost'] ?? $record->renew_cost_usd_cents,
                // The money the registrar billed, not ours. A Thai account
                // quotes the whole catalogue in THB and must not be converted.
                'cost_currency' => $data['currency'] ?? $record->cost_currency ?? 'USD',
                'synced_at' => now(),
            ]);

            if ($isNew) {
                // A TLD we have never sold arrives switched off. Someone
                // decides it is worth selling, not the sync.
                $record->is_active = false;
                $record->sort_order = 500;
            }

            $changed = $isNew || $record->isDirty(['cost_usd_cents', 'renew_cost_usd_cents', 'item_id_register', 'cost_currency']);

            if ($changed) {
                $rows[] = [
                    '.' . $tld,
                    $isNew ? 'NEW' : 'updated',
                    // A TLD we have never seen has no old price to show.
                    $isNew
                        ? $record->costLabel()
                        : sprintf('%s → %s', $this->money((int) $before['cost'], (string) $before['currency']), $record->costLabel()),
                    DomainPricing::format(DomainPricing::sell(
                        $record->cost_usd_cents,
                        $record->margin_percent !== null ? (float) $record->margin_percent : null,
                        $record->costCurrency(),
                    )),
                ];

                $isNew ? $created++ : $updated++;
            } else {
                $unchanged++;
            }

            if (! $dry) {
                $record->save();
            }
        }

        if ($rows !== []) {
            $this->table(['TLD', 'Change', 'Cost', 'New price'], $rows);
        }

        if ($this->option('prune') && ! $dry) {
            $gone = DomainTld::whereNotIn('tld', array_keys($parsed))
                ->where('is_active', true)
                ->update(['is_active' => false]);

            if ($gone) {
                $this->warn(sprintf('Deactivated %d TLD(s) the registrar no longer lists.', $gone));
            }
        }

        if (! $dry) {
            Cache::forget('domain.catalogue');
            Cache::forget('domain.search_tlds');
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d new, %d updated, %d unchanged.',
            $dry ? '[dry run]' : 'Done:',
            $created,
            $updated,
            $unchanged,
        ));

        if ($created > 0 && ! $dry) {
            $this->line('New TLDs arrive inactive — enable the ones you want to sell at /admin/domains.');
        }

        return self::SUCCESS;
    }

    /**
     * Pick the domain rows out of the catalogue and reduce them to one entry
     * per TLD.
     *
     * Item ids look like `hostingercom-domain-com-usd-1y`: vendor, product,
     * tld, currency, period. We want the one-year USD price for each TLD, and
     * the cheapest one when several exist (promotional tiers).
     *
     * This is written defensively — an unrecognised row is skipped, not
     * guessed at. A wrong item id would order the wrong product with real
     * money.
     *
     * @param  array<int,array<string,mixed>>  $items
     * @return array<string,array<string,mixed>>
     */
    protected function parseDomainItems(array $items): array
    {
        $out = [];
        $this->seenCurrencies = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $category = strtolower((string) ($item['category'] ?? ''));

            if (! str_contains($category, 'domain')) {
                continue;
            }

            foreach (($item['prices'] ?? []) as $price) {
                if (! is_array($price)) {
                    continue;
                }

                $id = (string) ($price['id'] ?? '');
                $currency = strtoupper((string) ($price['currency'] ?? ''));
                $period = (int) ($price['period'] ?? 0);
                $unit = strtolower((string) ($price['period_unit'] ?? 'year'));
                $amount = $price['first_period_price'] ?? $price['price'] ?? null;
                $renew = $price['price'] ?? $amount;

                if ($id === '' || $currency === '' || ! is_numeric($amount)) {
                    continue;
                }

                // One year only. Multi-year rows price the same TLD at a
                // multiple and would poison the per-year maths.
                if (! ($period === 1 && str_starts_with($unit, 'year'))) {
                    continue;
                }

                $this->seenCurrencies[$currency] = true;

                $tld = $this->tldFromItem($item, $id);

                if ($tld === null) {
                    continue;
                }

                $cost = (int) $amount;
                $existing = $out[$tld] ?? null;

                // A reseller account is billed in one currency, but if the
                // catalogue ever offers a choice, prefer the one we already
                // convert from. Otherwise take what we are given: a Thai
                // account lists THB and nothing else, and refusing it is how
                // the catalogue ended up with no sellable TLD at all.
                if ($existing !== null) {
                    $preferred = $existing['currency'] === 'USD' && $currency !== 'USD';
                    $cheaperSame = $existing['currency'] === $currency && $existing['cost'] <= $cost;

                    if ($preferred || $cheaperSame) {
                        continue;
                    }
                }

                $out[$tld] = [
                    'item_id_register' => $id,
                    'item_id_renew' => $id,
                    'cost' => $cost,
                    'renew' => is_numeric($renew) ? (int) $renew : $cost,
                    'currency' => $currency,
                ];
            }
        }

        return $out;
    }

    /**
     * The TLD this catalogue item sells.
     *
     * The item's NAME is authoritative — `.CO.UK Domain` — because the id glues
     * multi-label suffixes together: `hostingerinth-domain-couk-thb-1y` reads
     * as `couk`, and a catalogue row for a TLD that does not exist is a row
     * nobody can ever buy. The id is the fallback for an item whose name the
     * registrar writes differently.
     *
     * @param  array<string,mixed>  $item
     */
    protected function tldFromItem(array $item, string $priceId): ?string
    {
        $name = trim((string) ($item['name'] ?? ''));

        if (preg_match('/^\.([a-z0-9.\-]+)/i', $name, $m)) {
            $tld = strtolower(rtrim($m[1], '.-'));

            if (preg_match('/^[a-z0-9]+(\.[a-z0-9]+)*$/', $tld)) {
                return $tld;
            }
        }

        return $this->tldFromItemId($priceId);
    }

    /**
     * A cost written the way the operator reads it, for the summary table.
     */
    protected function money(int $cents, string $currency): string
    {
        return strtoupper($currency) === 'THB'
            ? number_format($cents / 100, 2) . ' ฿'
            : '$' . number_format($cents / 100, 2);
    }

    /**
     * `hostingercom-domain-co-uk-usd-1y` → `co.uk`
     *
     * Everything between the `domain` marker and the currency is the TLD,
     * with hyphens standing in for dots. Returns null when the id does not
     * look like a domain product at all.
     */
    protected function tldFromItemId(string $id): ?string
    {
        // Any ISO currency, not a hardcoded three: our own account bills THB,
        // which the original list did not contain.
        if (! preg_match('/-domain-(.+)-([a-z]{3})-\d+[a-z]/i', $id, $m)) {
            return null;
        }

        $tld = strtolower(str_replace('-', '.', $m[1]));

        return preg_match('/^[a-z0-9.]+$/', $tld) ? $tld : null;
    }
}
