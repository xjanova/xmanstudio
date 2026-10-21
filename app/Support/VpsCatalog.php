<?php

namespace App\Support;

use App\Services\HostingerApiService;
use Illuminate\Support\Facades\Cache;

/**
 * The operating systems and data centers a VPS can be ordered with.
 *
 * Both lists come from the supplier and change rarely, so they are cached for
 * an hour: the order page would otherwise spend two calls of a 90-a-minute
 * budget on every view. A failed fetch is NOT cached — the next page view
 * tries again rather than showing an empty dropdown for an hour.
 *
 * Everything that leaves this class is safe to render: entries that name the
 * supplier are dropped, HTML entities are decoded, and each template carries
 * a group so the dropdown is readable (a flat list of ninety names is not).
 */
class VpsCatalog
{
    protected const TTL_SECONDS = 3600;

    /** Panels that need a licence bought separately — said on the form, before the money moves. */
    protected const LICENSED = ['cpanel', 'plesk', 'directadmin', 'cloudlinux', 'icewarp'];

    protected const PANELS = [
        'panel', 'cpanel', 'plesk', 'directadmin', 'webmin', 'webuzo', 'hestiacp', 'aapanel', 'tinycp',
        'fastpanel', 'easypanel', 'adminbolt', 'cloudpanel', 'cyberpanel', 'kusanagi', 'cloudron', 'cosmos',
    ];

    protected const AI = ['claude code', 'gemini cli', 'codex', 'grok', 'deepseek', 'herdr', 'nemoclaw', 'mcp server', 'anaconda'];

    /** @var array<string,array{th:string,en:string}> */
    public const GROUPS = [
        'os' => ['th' => 'ระบบปฏิบัติการ', 'en' => 'Operating systems'],
        'panel' => ['th' => 'พร้อมแผงควบคุม', 'en' => 'With a control panel'],
        'app' => ['th' => 'พร้อมแอปติดตั้งให้', 'en' => 'One-click apps'],
        'ai' => ['th' => 'เครื่องมือ AI / นักพัฒนา', 'en' => 'AI & developer tools'],
    ];

    /** @var array<string,string> */
    protected const COUNTRIES = [
        'my' => 'มาเลเซีย',
        'sg' => 'สิงคโปร์',
        'in' => 'อินเดีย',
        'id' => 'อินโดนีเซีย',
        'jp' => 'ญี่ปุ่น',
        'us' => 'สหรัฐอเมริกา',
        'gb' => 'สหราชอาณาจักร',
        'fr' => 'ฝรั่งเศส',
        'de' => 'เยอรมนี',
        'nl' => 'เนเธอร์แลนด์',
        'lt' => 'ลิทัวเนีย',
        'br' => 'บราซิล',
        'au' => 'ออสเตรเลีย',
    ];

    /** Closest to Thailand first: that is where most of our customers' visitors are. */
    protected const COUNTRY_ORDER = ['my', 'sg', 'in', 'id', 'jp', 'au', 'de', 'fr', 'nl', 'gb', 'lt', 'us', 'br'];

    public function __construct(protected HostingerApiService $api) {}

    /**
     * @return array<int,array{id:int,name:string,description:string,group:string,licensed:bool}>
     */
    public function templates(): array
    {
        $rows = $this->remember('vps.catalog.templates', fn () => $this->api->getVpsTemplates());
        $out = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['id'], $row['name'])) {
                continue;
            }

            $name = trim(html_entity_decode((string) $row['name'], ENT_QUOTES | ENT_HTML5));
            $description = trim(html_entity_decode((string) ($row['description'] ?? ''), ENT_QUOTES | ENT_HTML5));

            // A template that names the supplier would tell the customer who
            // we buy from. There is always a plain equivalent.
            if (stripos($name . ' ' . $description, 'hostinger') !== false) {
                continue;
            }

            $lower = strtolower($name);

            $out[] = [
                'id' => (int) $row['id'],
                'name' => $name,
                'description' => mb_substr($description, 0, 240),
                'group' => $this->groupFor($lower),
                'licensed' => $this->mentionsAny($lower, self::LICENSED),
            ];
        }

        usort($out, function (array $a, array $b) {
            $order = array_flip(array_keys(self::GROUPS));

            return [$order[$a['group']] ?? 9, $this->osRank($a['name']), $a['name']]
                <=> [$order[$b['group']] ?? 9, $this->osRank($b['name']), $b['name']];
        });

        return $out;
    }

    /**
     * @return array{id:int,name:string,description:string,group:string,licensed:bool}|null
     */
    public function template(int $id): ?array
    {
        foreach ($this->templates() as $template) {
            if ($template['id'] === $id) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Grouped for the dropdown's <optgroup>s.
     *
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function groupedTemplates(): array
    {
        $grouped = [];

        foreach ($this->templates() as $template) {
            $grouped[$template['group']][] = $template;
        }

        return array_intersect_key(array_replace(array_fill_keys(array_keys(self::GROUPS), []), $grouped), $grouped);
    }

    /**
     * Ubuntu 24.04 LTS when offered — what most tutorials, panels and install
     * scripts are written for today — otherwise the newest plain Ubuntu LTS.
     * A brand-new LTS is a worse default than it looks: half the one-line
     * installers a customer will paste in have not caught up with it yet.
     */
    public function defaultTemplateId(): ?int
    {
        $plain = array_values(array_filter($this->templates(), fn ($t) => $t['group'] === 'os'));

        foreach ($plain as $template) {
            if (strcasecmp($template['name'], 'Ubuntu 24.04 LTS') === 0) {
                return $template['id'];
            }
        }

        foreach ($plain as $template) {
            if (preg_match('/^ubuntu \d+\.04 lts$/i', $template['name'])) {
                return $template['id'];
            }
        }

        return $plain[0]['id'] ?? ($this->templates()[0]['id'] ?? null);
    }

    /**
     * @return array<int,array{id:int,name:string,city:string,country:string,country_th:string,flag:string,continent:string,recommended:bool}>
     */
    public function dataCenters(): array
    {
        $rows = $this->remember('vps.catalog.datacenters', fn () => $this->api->getVpsDataCenters());
        $out = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! isset($row['id'])) {
                continue;
            }

            $country = strtolower((string) ($row['location'] ?? ''));

            $out[] = [
                'id' => (int) $row['id'],
                'name' => (string) ($row['name'] ?? ''),
                'city' => (string) ($row['city'] ?? $row['name'] ?? ''),
                'country' => $country,
                'country_th' => self::COUNTRIES[$country] ?? strtoupper($country),
                'flag' => self::flag($country),
                'continent' => (string) ($row['continent'] ?? ''),
                'recommended' => $country === 'my' || $country === 'sg',
            ];
        }

        $rank = array_flip(self::COUNTRY_ORDER);

        usort($out, fn ($a, $b) => [$rank[$a['country']] ?? 99, $a['city']] <=> [$rank[$b['country']] ?? 99, $b['city']]);

        return $out;
    }

    /**
     * @return array{id:int,name:string,city:string,country:string,country_th:string,flag:string,continent:string,recommended:bool}|null
     */
    public function dataCenter(int $id): ?array
    {
        foreach ($this->dataCenters() as $dc) {
            if ($dc['id'] === $id) {
                return $dc;
            }
        }

        return null;
    }

    public function defaultDataCenterId(): ?int
    {
        $centers = $this->dataCenters();

        foreach ($centers as $dc) {
            if ($dc['recommended']) {
                return $dc['id'];
            }
        }

        return $centers[0]['id'] ?? null;
    }

    /** "Kuala Lumpur, มาเลเซีย" — what goes on the receipt and the dashboard. */
    public static function describeDataCenter(array $dc): string
    {
        return trim($dc['city'] . ', ' . $dc['country_th'], ', ');
    }

    public function forget(): void
    {
        Cache::forget('vps.catalog.templates');
        Cache::forget('vps.catalog.datacenters');
    }

    /** "my" → 🇲🇾, from the two regional-indicator letters. */
    public static function flag(string $country): string
    {
        if (! preg_match('/^[a-z]{2}$/', $country)) {
            return '';
        }

        return mb_chr(0x1F1E6 + ord($country[0]) - 97) . mb_chr(0x1F1E6 + ord($country[1]) - 97);
    }

    /**
     * @return array<int,mixed>
     */
    protected function remember(string $key, callable $fetch): array
    {
        try {
            $cached = Cache::get($key);
        } catch (\Throwable) {
            $cached = null;
        }

        if (is_array($cached)) {
            return $cached;
        }

        $fresh = $fetch();

        if (! is_array($fresh) || $fresh === []) {
            return [];
        }

        try {
            Cache::put($key, $fresh, self::TTL_SECONDS);
        } catch (\Throwable) {
        }

        return $fresh;
    }

    protected function groupFor(string $lowerName): string
    {
        if (! str_contains($lowerName, ' with ')) {
            return 'os';
        }

        $extra = substr($lowerName, strpos($lowerName, ' with ') + 6);

        if ($this->mentionsAny($extra, self::AI)) {
            return 'ai';
        }

        if ($this->mentionsAny($extra, self::PANELS)) {
            return 'panel';
        }

        return 'app';
    }

    /** @param array<int,string> $needles */
    protected function mentionsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /** Ubuntu first, then the other mainstream families, newest version first within each. */
    protected function osRank(string $name): string
    {
        $families = ['ubuntu', 'debian', 'almalinux', 'rocky', 'centos', 'fedora', 'opensuse', 'arch', 'alpine', 'kali', 'nixos', 'cloudlinux'];
        $lower = strtolower($name);

        foreach ($families as $i => $family) {
            if (str_starts_with($lower, $family)) {
                preg_match('/(\d+(?:\.\d+)?)/', $lower, $m);
                $version = isset($m[1]) ? (float) $m[1] : 0.0;

                // Descending version inside the family: invert it into the sort key.
                return sprintf('%02d-%08.2f', $i, 99999 - $version);
            }
        }

        return '99-' . $lower;
    }
}
