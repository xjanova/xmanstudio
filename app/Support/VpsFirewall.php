<?php

namespace App\Support;

/**
 * The customer's firewall rules, in and out of the shape upstream wants.
 *
 * Upstream's model: a firewall drops everything that no rule accepts. So a
 * rule list is a list of doors to open, and forgetting SSH locks the owner
 * out of their own server — which is why lockout is checked before saving.
 */
class VpsFirewall
{
    public const MAX_RULES = 30;

    /**
     * Protocols a customer can pick. The named ones imply their port.
     *
     * @var array<string,array{th:string,en:string,port:?string}>
     */
    public const PROTOCOLS = [
        'SSH' => ['th' => 'SSH', 'en' => 'SSH', 'port' => '22'],
        'HTTP' => ['th' => 'เว็บ HTTP', 'en' => 'Web HTTP', 'port' => '80'],
        'HTTPS' => ['th' => 'เว็บ HTTPS', 'en' => 'Web HTTPS', 'port' => '443'],
        'MySQL' => ['th' => 'MySQL', 'en' => 'MySQL', 'port' => '3306'],
        'PostgreSQL' => ['th' => 'PostgreSQL', 'en' => 'PostgreSQL', 'port' => '5432'],
        'TCP' => ['th' => 'TCP (ระบุพอร์ต)', 'en' => 'TCP (port)', 'port' => null],
        'UDP' => ['th' => 'UDP (ระบุพอร์ต)', 'en' => 'UDP (port)', 'port' => null],
        'ICMP' => ['th' => 'Ping (ICMP)', 'en' => 'Ping (ICMP)', 'port' => 'any'],
    ];

    /**
     * Ready-made rule sets. "web" is what a new firewall starts with: without
     * SSH in it, switching the firewall on would lock the customer out.
     *
     * @var array<string,array{th:string,en:string,rules:array<int,array{0:string,1:?string}>}>
     */
    public const PRESETS = [
        'web' => ['th' => 'เว็บไซต์ (SSH + HTTP + HTTPS)', 'en' => 'Website (SSH + HTTP + HTTPS)', 'rules' => [['SSH', null], ['HTTP', null], ['HTTPS', null]]],
        'ssh' => ['th' => 'SSH อย่างเดียว', 'en' => 'SSH only', 'rules' => [['SSH', null]]],
        'mail' => ['th' => 'เว็บ + เมลเซิร์ฟเวอร์', 'en' => 'Web + mail server', 'rules' => [
            ['SSH', null], ['HTTP', null], ['HTTPS', null],
            ['TCP', '25'], ['TCP', '465'], ['TCP', '587'], ['TCP', '993'], ['TCP', '995'],
        ]],
    ];

    /**
     * Doors that templates open for themselves: a firewall switched on with
     * only SSH and the web would cut a CloudPanel, an n8n or a game server
     * off from its owner on the first click.
     *
     * @var array<string,array<int,array{0:string,1:string}>>
     */
    protected const TEMPLATE_PORTS = [
        'cloudpanel' => [['TCP', '8443']],
        'plesk' => [['TCP', '8443']],
        'cyberpanel' => [['TCP', '8090']],
        'hestiacp' => [['TCP', '8083']],
        'webmin' => [['TCP', '10000']],
        'virtualmin' => [['TCP', '10000']],
        'cpanel' => [['TCP', '2083'], ['TCP', '2087']],
        'directadmin' => [['TCP', '2222']],
        'coolify' => [['TCP', '8000']],
        'dokploy' => [['TCP', '3000']],
        'easypanel' => [['TCP', '3000']],
        'webuzo' => [['TCP', '2005']],
        'fastpanel' => [['TCP', '8888']],
        'n8n' => [['TCP', '5678']],
        'minecraft' => [['TCP', '25565']],
    ];

    /**
     * What a brand-new firewall starts with on this server: SSH and the web,
     * plus whatever the installed template listens on.
     *
     * @return array<int,array{protocol:string,port:string,source:string,source_detail:string}>
     */
    public static function seedFor(?string $templateName): array
    {
        $rules = self::preset('web');
        $lower = strtolower((string) $templateName);

        foreach (self::TEMPLATE_PORTS as $needle => $ports) {
            if (! str_contains($lower, $needle)) {
                continue;
            }

            foreach ($ports as [$protocol, $port]) {
                $rule = self::rule($protocol, $port, 'any');

                if (! in_array($rule, $rules, true)) {
                    $rules[] = $rule;
                }
            }
        }

        return $rules;
    }

    /**
     * The open doors in words, for the confirmation before switching on.
     *
     * @param  array<int,array<string,mixed>>  $rules
     */
    public static function describe(array $rules): string
    {
        $parts = [];

        foreach ($rules as $rule) {
            $protocol = (string) ($rule['protocol'] ?? '');
            $port = (string) ($rule['port'] ?? '');
            $parts[] = in_array($protocol, ['TCP', 'UDP'], true) ? $protocol . ' ' . $port : $protocol . ($port !== '' && $port !== 'any' ? ' (' . $port . ')' : '');
        }

        return $parts === [] ? '—' : implode(', ', array_unique($parts));
    }

    /**
     * Rules for a preset, in upstream's shape.
     *
     * @return array<int,array{protocol:string,port:string,source:string,source_detail:string}>
     */
    public static function preset(string $key): array
    {
        return array_map(
            fn (array $r) => self::rule($r[0], $r[1] ?? self::PROTOCOLS[$r[0]]['port'], 'any'),
            (self::PRESETS[$key] ?? self::PRESETS['web'])['rules'],
        );
    }

    /**
     * @return array{protocol:string,port:string,source:string,source_detail:string}
     */
    public static function rule(string $protocol, ?string $port, string $sourceDetail): array
    {
        $sourceDetail = trim($sourceDetail) === '' ? 'any' : trim($sourceDetail);

        return [
            'protocol' => $protocol,
            'port' => (string) (self::PROTOCOLS[$protocol]['port'] ?? $port ?? 'any'),
            'source' => strtolower($sourceDetail) === 'any' ? 'any' : 'custom',
            'source_detail' => strtolower($sourceDetail) === 'any' ? 'any' : $sourceDetail,
        ];
    }

    /**
     * Turn the submitted rows into upstream rules, or explain what is wrong.
     * Blank rows are dropped; duplicates are dropped once.
     *
     * @param  array<int,mixed>  $rows
     * @return array{0:array<int,array{protocol:string,port:string,source:string,source_detail:string}>,1:array<int,string>}
     */
    public static function normalise(array $rows): array
    {
        $rules = [];
        $errors = [];
        $seen = [];

        foreach (array_values($rows) as $i => $row) {
            if (! is_array($row)) {
                continue;
            }

            // Anything but plain text (an array smuggled in as rules[0][port][])
            // is treated as empty rather than allowed to throw.
            $text = fn ($v) => is_scalar($v) ? trim((string) $v) : '';
            $protocol = $text($row['protocol'] ?? '');
            $port = $text($row['port'] ?? '');
            $source = $text($row['source_detail'] ?? '');
            $n = $i + 1;

            if ($protocol === '' && $port === '' && $source === '') {
                continue;
            }

            if (! array_key_exists($protocol, self::PROTOCOLS)) {
                $errors[] = "กฎข้อ {$n}: เลือกชนิดการเชื่อมต่อ";

                continue;
            }

            if (self::PROTOCOLS[$protocol]['port'] === null && ! self::validPort($port)) {
                $errors[] = "กฎข้อ {$n}: พอร์ตต้องเป็นเลข 1–65535 หรือช่วง เช่น 8000:8100";

                continue;
            }

            if ($source !== '' && strtolower($source) !== 'any' && ! self::validSource($source)) {
                $errors[] = "กฎข้อ {$n}: ต้นทางต้องเป็น IP, CIDR (เช่น 203.0.113.0/24) หรือเว้นว่างเพื่อเปิดให้ทุกที่";

                continue;
            }

            $rule = self::rule($protocol, $port, $source);
            $key = implode('|', $rule);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $rules[] = $rule;
        }

        if (count($rules) > self::MAX_RULES) {
            $errors[] = 'กฎได้สูงสุด ' . self::MAX_RULES . ' ข้อ';
        }

        return [$rules, $errors];
    }

    /**
     * Does any rule let SSH in from somewhere? Without one, a firewall that is
     * switched on leaves the web console as the only way back in.
     *
     * @param  array<int,array<string,mixed>>  $rules
     */
    public static function allowsSsh(array $rules): bool
    {
        foreach ($rules as $rule) {
            $protocol = strtoupper((string) ($rule['protocol'] ?? ''));
            $port = (string) ($rule['port'] ?? '');

            if ($protocol === 'SSH' || ($protocol === 'ANY')) {
                return true;
            }

            if ($protocol === 'TCP' && self::portCovers($port, 22)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Upstream rules → rows for the page, in the order they were saved.
     *
     * @param  array<int,mixed>  $rules
     * @return array<int,array{protocol:string,port:string,source_detail:string}>
     */
    public static function rows(array $rules): array
    {
        $rows = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $protocol = (string) ($rule['protocol'] ?? 'TCP');
            $rows[] = [
                'protocol' => array_key_exists($protocol, self::PROTOCOLS) ? $protocol : 'TCP',
                'port' => (string) ($rule['port'] ?? ''),
                'source_detail' => (string) ($rule['source_detail'] ?? 'any'),
            ];
        }

        return $rows;
    }

    public static function validPort(string $port): bool
    {
        if (preg_match('/^(\d{1,5})$/', $port, $m)) {
            return (int) $m[1] >= 1 && (int) $m[1] <= 65535;
        }

        if (preg_match('/^(\d{1,5}):(\d{1,5})$/', $port, $m)) {
            return (int) $m[1] >= 1 && (int) $m[2] <= 65535 && (int) $m[1] < (int) $m[2];
        }

        return false;
    }

    public static function validSource(string $source): bool
    {
        if (filter_var($source, FILTER_VALIDATE_IP)) {
            return true;
        }

        // CIDR: v4 /0-32, v6 /0-128.
        if (preg_match('#^([^/]+)/(\d{1,3})$#', $source, $m) && filter_var($m[1], FILTER_VALIDATE_IP)) {
            $max = filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 32 : 128;

            return (int) $m[2] <= $max;
        }

        // A range: 203.0.113.10-203.0.113.20.
        if (preg_match('#^([^-]+)-([^-]+)$#', $source, $m)) {
            return (bool) filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                && (bool) filter_var($m[2], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                && ip2long($m[1]) <= ip2long($m[2]);
        }

        return false;
    }

    protected static function portCovers(string $port, int $wanted): bool
    {
        if ($port === '' || strtolower($port) === 'any') {
            return true;
        }

        if (preg_match('/^(\d+):(\d+)$/', $port, $m)) {
            return (int) $m[1] <= $wanted && $wanted <= (int) $m[2];
        }

        return (int) $port === $wanted;
    }
}
