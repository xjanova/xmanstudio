<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/**
 * Ready setups a customer lays on their Cloudflare zone in one click.
 *
 * Each template is a set of DNS records plus zone settings. Applying one
 * replaces only what would clash with it — the A/AAAA/CNAME on the same host,
 * the MX of a mail template, the SPF/DMARC line — and leaves every other
 * record alone. The values are the providers' published ones (checked
 * 2026-10-10); anything per-customer (an IP, a site name, a verification
 * code) is a field on the form.
 */
class CloudflareTemplates
{
    /** Replace every A, AAAA and CNAME on the same name. */
    public const REPLACE_HOST = 'host';

    /** Replace every record of the same type on the same name. */
    public const REPLACE_TYPE = 'type';

    /** Replace TXT records on the same name that start with the same prefix. */
    public const REPLACE_PREFIX = 'prefix';

    /** Add unless an identical record is already there. */
    public const ADD = 'add';

    /**
     * Catalogue for the page: label, what it is for, and its fields.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function all(): array
    {
        $sslField = [
            'name' => 'ssl',
            'type' => 'select',
            'label_th' => 'เซิร์ฟเวอร์มี HTTPS แล้วหรือยัง',
            'label_en' => 'Does the server already serve HTTPS?',
            'options' => [
                'full' => 'มีแล้ว (แนะนำ) — Full / Yes (recommended)',
                'flexible' => 'ยังไม่มี — Flexible / Not yet',
            ],
        ];

        return [
            'vps' => [
                'group' => 'web',
                'label_th' => 'เว็บบน VPS ที่เช่ากับเรา',
                'label_en' => 'Website on your VPS from us',
                'desc_th' => 'ชี้โดเมนและ www ไปที่ VPS ผ่าน Cloudflare พร้อมบังคับ HTTPS',
                'desc_en' => 'Points the domain and www at your VPS through Cloudflare, HTTPS enforced.',
                'preview' => ['A @ → IP ของ VPS (ผ่าน Cloudflare)', 'AAAA @ → IPv6 ของ VPS ถ้ามี', 'CNAME www → @', 'SSL ตามที่เลือก + บังคับ HTTPS'],
                'fields' => [
                    ['name' => 'vps_id', 'type' => 'vps', 'label_th' => 'เลือก VPS', 'label_en' => 'Pick a VPS'],
                    $sslField,
                ],
            ],
            'server_ip' => [
                'group' => 'web',
                'label_th' => 'เว็บบนเซิร์ฟเวอร์ / โฮสต์อื่น (IP)',
                'label_en' => 'Website on another server (IP)',
                'desc_th' => 'มี IP ของเซิร์ฟเวอร์อยู่แล้ว ชี้โดเมนและ www ไปที่นั่น ผ่าน Cloudflare',
                'desc_en' => 'You have the server IP — point the domain and www at it through Cloudflare.',
                'preview' => ['A @ → IP ที่ใส่ (ผ่าน Cloudflare)', 'AAAA @ → IPv6 ถ้าใส่', 'CNAME www → @', 'SSL ตามที่เลือก + บังคับ HTTPS'],
                'fields' => [
                    ['name' => 'ipv4', 'type' => 'text', 'label_th' => 'IPv4 ของเซิร์ฟเวอร์', 'label_en' => 'Server IPv4', 'placeholder' => '203.0.113.10'],
                    ['name' => 'ipv6', 'type' => 'text', 'label_th' => 'IPv6 (ถ้ามี)', 'label_en' => 'IPv6 (optional)', 'placeholder' => '2001:db8::10', 'optional' => true],
                    $sslField,
                ],
            ],
            'vercel' => [
                'group' => 'web',
                'label_th' => 'Vercel',
                'label_en' => 'Vercel',
                'desc_th' => 'เว็บที่ deploy บน Vercel — เพิ่มโดเมนในโปรเจกต์ Vercel ด้วย',
                'desc_en' => 'A site deployed on Vercel — add the domain to the Vercel project too.',
                'preview' => ['A @ → 76.76.21.21', 'CNAME www → cname.vercel-dns.com', 'ไม่ผ่าน proxy ตามที่ Vercel แนะนำ'],
                'fields' => [],
            ],
            'netlify' => [
                'group' => 'web',
                'label_th' => 'Netlify',
                'label_en' => 'Netlify',
                'desc_th' => 'เว็บบน Netlify — เพิ่มโดเมนใน Site settings ของ Netlify ด้วย',
                'desc_en' => 'A site on Netlify — add the domain in Netlify\'s site settings too.',
                'preview' => ['A @ → 75.2.60.5', 'CNAME www → ชื่อไซต์.netlify.app'],
                'fields' => [
                    ['name' => 'site', 'type' => 'text', 'label_th' => 'ชื่อไซต์ Netlify (xxx.netlify.app)', 'label_en' => 'Netlify site name (xxx.netlify.app)', 'placeholder' => 'my-site', 'suffix' => '.netlify.app'],
                ],
            ],
            'github_pages' => [
                'group' => 'web',
                'label_th' => 'GitHub Pages',
                'label_en' => 'GitHub Pages',
                'desc_th' => 'เว็บบน GitHub Pages — ใส่โดเมนใน Settings → Pages ของ repo ด้วย',
                'desc_en' => 'A site on GitHub Pages — set the domain in the repo\'s Settings → Pages too.',
                'preview' => ['A @ → 185.199.108–111.153 (4 รายการ)', 'CNAME www → ชื่อผู้ใช้.github.io'],
                'fields' => [
                    ['name' => 'user', 'type' => 'text', 'label_th' => 'ชื่อผู้ใช้หรือองค์กร GitHub', 'label_en' => 'GitHub user or organisation', 'placeholder' => 'octocat', 'suffix' => '.github.io'],
                ],
            ],
            'shopify' => [
                'group' => 'web',
                'label_th' => 'Shopify',
                'label_en' => 'Shopify',
                'desc_th' => 'ร้านบน Shopify — เชื่อมโดเมนใน Shopify Admin → Domains ด้วย',
                'desc_en' => 'A Shopify store — connect the domain in Shopify Admin → Domains too.',
                'preview' => ['A @ → 23.227.38.65', 'CNAME www → shops.myshopify.com'],
                'fields' => [],
            ],
            'google_workspace' => [
                'group' => 'mail',
                'label_th' => 'อีเมล Google Workspace',
                'label_en' => 'Google Workspace mail',
                'desc_th' => 'รับ-ส่งอีเมล @โดเมนนี้ด้วย Gmail ของ Google Workspace (MX + SPF + DMARC)',
                'desc_en' => 'Mail @this domain through Google Workspace Gmail (MX + SPF + DMARC).',
                'preview' => ['MX @ → smtp.google.com (1)', 'TXT @ → v=spf1 include:_spf.google.com ~all', 'TXT _dmarc → v=DMARC1; p=none', 'TXT @ → รหัสยืนยัน ถ้าใส่'],
                'fields' => [
                    ['name' => 'verification', 'type' => 'text', 'label_th' => 'รหัสยืนยันจาก Google (ถ้ามี)', 'label_en' => 'Google verification code (optional)', 'placeholder' => 'google-site-verification=…', 'optional' => true],
                ],
            ],
            'zoho_mail' => [
                'group' => 'mail',
                'label_th' => 'อีเมล Zoho Mail',
                'label_en' => 'Zoho Mail',
                'desc_th' => 'อีเมล @โดเมนนี้ด้วย Zoho Mail (บัญชีภูมิภาค zoho.com)',
                'desc_en' => 'Mail @this domain through Zoho Mail (zoho.com region accounts).',
                'preview' => ['MX @ → mx.zoho.com (10), mx2 (20), mx3 (50)', 'TXT @ → v=spf1 include:zohomail.com ~all', 'TXT _dmarc → v=DMARC1; p=none', 'TXT @ → รหัสยืนยัน ถ้าใส่'],
                'fields' => [
                    ['name' => 'verification', 'type' => 'text', 'label_th' => 'รหัสยืนยันจาก Zoho (ถ้ามี)', 'label_en' => 'Zoho verification code (optional)', 'placeholder' => 'zoho-verification=zb…', 'optional' => true],
                ],
            ],
            'security' => [
                'group' => 'security',
                'label_th' => 'ชุดความปลอดภัยพื้นฐาน',
                'label_en' => 'Baseline security',
                'desc_th' => 'บังคับ HTTPS, แก้ลิงก์ http อัตโนมัติ, TLS 1.2 ขึ้นไป, เปิด TLS 1.3 — ไม่แตะเรคคอร์ด DNS',
                'desc_en' => 'Forces HTTPS, rewrites http links, TLS 1.2 minimum, TLS 1.3 on — no DNS records touched.',
                'preview' => ['Always Use HTTPS: เปิด', 'Automatic HTTPS Rewrites: เปิด', 'TLS ขั้นต่ำ 1.2', 'TLS 1.3: เปิด'],
                'fields' => [],
            ],
        ];
    }

    /**
     * Records and settings for a template, from validated input.
     *
     * $vpsIps maps the customer's own VPS ids to their addresses, so the
     * form can never name somebody else's machine.
     *
     * @param  array<string,mixed>  $input
     * @param  array<int,array{ipv4:?string,ipv6:?string}>  $vpsIps
     * @return array{records:array<int,array<string,mixed>>, settings:array<string,string>}
     *
     * @throws ValidationException
     */
    public static function build(string $key, array $input, string $domain, array $vpsIps = []): array
    {
        $fail = fn (string $field, string $message) => throw ValidationException::withMessages([$field => $message]);
        $www = 'www.' . $domain;
        $ssl = in_array($input['ssl'] ?? 'full', ['full', 'flexible'], true) ? ($input['ssl'] ?? 'full') : 'full';

        switch ($key) {
            case 'vps':
            case 'server_ip':
                if ($key === 'vps') {
                    $vps = $vpsIps[(int) ($input['vps_id'] ?? 0)] ?? null;

                    if (! $vps || empty($vps['ipv4'])) {
                        $fail('vps_id', 'เลือก VPS ของคุณที่มี IP แล้ว');
                    }

                    $v4 = $vps['ipv4'];
                    $v6 = $vps['ipv6'] ?? null;
                } else {
                    $v4 = trim((string) ($input['ipv4'] ?? ''));
                    $v6 = trim((string) ($input['ipv6'] ?? '')) ?: null;

                    if (! filter_var($v4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        $fail('ipv4', 'IPv4 ไม่ถูกต้อง หรือเป็น IP ภายในที่ใช้บนอินเทอร์เน็ตไม่ได้');
                    }

                    if ($v6 !== null && ! filter_var($v6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        $fail('ipv6', 'IPv6 ไม่ถูกต้อง');
                    }
                }

                $records = [self::record('A', $domain, $v4, self::REPLACE_HOST, proxied: true)];

                if ($v6) {
                    $records[] = self::record('AAAA', $domain, $v6, self::REPLACE_TYPE, proxied: true);
                }

                $records[] = self::record('CNAME', $www, $domain, self::REPLACE_HOST, proxied: true);

                return ['records' => $records, 'settings' => ['ssl' => $ssl, 'always_use_https' => 'on']];

            case 'vercel':
                return ['records' => [
                    self::record('A', $domain, '76.76.21.21', self::REPLACE_HOST),
                    self::record('CNAME', $www, 'cname.vercel-dns.com', self::REPLACE_HOST),
                ], 'settings' => []];

            case 'netlify':
                $site = strtolower(trim((string) ($input['site'] ?? '')));
                $site = preg_replace('/\.netlify\.app$/', '', $site);

                if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', (string) $site)) {
                    $fail('site', 'ชื่อไซต์ Netlify ใช้ได้แค่ a-z 0-9 และขีด (-)');
                }

                return ['records' => [
                    self::record('A', $domain, '75.2.60.5', self::REPLACE_HOST),
                    self::record('CNAME', $www, $site . '.netlify.app', self::REPLACE_HOST),
                ], 'settings' => []];

            case 'github_pages':
                $user = strtolower(trim((string) ($input['user'] ?? '')));
                $user = preg_replace('/\.github\.io$/', '', $user);

                if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,37}[a-z0-9])?$/', (string) $user)) {
                    $fail('user', 'ชื่อผู้ใช้ GitHub ใช้ได้แค่ a-z 0-9 และขีด (-)');
                }

                $records = [];
                foreach (['185.199.108.153', '185.199.109.153', '185.199.110.153', '185.199.111.153'] as $i => $ip) {
                    // The first one clears the host; the rest join it.
                    $records[] = self::record('A', $domain, $ip, $i === 0 ? self::REPLACE_HOST : self::ADD);
                }
                $records[] = self::record('CNAME', $www, $user . '.github.io', self::REPLACE_HOST);

                return ['records' => $records, 'settings' => []];

            case 'shopify':
                return ['records' => [
                    self::record('A', $domain, '23.227.38.65', self::REPLACE_HOST),
                    self::record('CNAME', $www, 'shops.myshopify.com', self::REPLACE_HOST),
                ], 'settings' => []];

            case 'google_workspace':
                $records = [
                    self::record('MX', $domain, 'smtp.google.com', self::REPLACE_TYPE, priority: 1),
                    self::record('TXT', $domain, 'v=spf1 include:_spf.google.com ~all', self::REPLACE_PREFIX, prefix: 'v=spf1'),
                    self::record('TXT', '_dmarc.' . $domain, 'v=DMARC1; p=none', self::REPLACE_PREFIX, prefix: 'v=DMARC1'),
                ];

                if ($code = self::verification($input['verification'] ?? null, 'google-site-verification=', $fail)) {
                    $records[] = self::record('TXT', $domain, $code, self::ADD);
                }

                return ['records' => $records, 'settings' => []];

            case 'zoho_mail':
                $records = [
                    self::record('MX', $domain, 'mx.zoho.com', self::REPLACE_TYPE, priority: 10),
                    self::record('MX', $domain, 'mx2.zoho.com', self::ADD, priority: 20),
                    self::record('MX', $domain, 'mx3.zoho.com', self::ADD, priority: 50),
                    self::record('TXT', $domain, 'v=spf1 include:zohomail.com ~all', self::REPLACE_PREFIX, prefix: 'v=spf1'),
                    self::record('TXT', '_dmarc.' . $domain, 'v=DMARC1; p=none', self::REPLACE_PREFIX, prefix: 'v=DMARC1'),
                ];

                if ($code = self::verification($input['verification'] ?? null, 'zoho-verification=', $fail)) {
                    $records[] = self::record('TXT', $domain, $code, self::ADD);
                }

                return ['records' => $records, 'settings' => []];

            case 'security':
                return ['records' => [], 'settings' => [
                    'always_use_https' => 'on',
                    'automatic_https_rewrites' => 'on',
                    'min_tls_version' => '1.2',
                    'tls_1_3' => 'on',
                ]];
        }

        $fail('template', 'ไม่รู้จักเทมเพลตนี้');
    }

    /**
     * What to delete and what to create, against the zone as it is now.
     *
     * @param  array<int,array<string,mixed>>  $records  from build()
     * @param  array<int,array<string,mixed>>  $existing  Cloudflare's dns_records
     * @return array{delete:array<int,array<string,mixed>>, create:array<int,array<string,mixed>>}
     */
    public static function plan(array $records, array $existing): array
    {
        $delete = [];
        $create = [];
        $norm = fn ($s) => rtrim(strtolower(trim((string) $s, ' "')), '.');

        foreach ($records as $record) {
            $name = $norm($record['name']);
            $type = $record['type'];

            foreach ($existing as $row) {
                if ($norm($row['name'] ?? '') !== $name || isset($delete[$row['id'] ?? ''])) {
                    continue;
                }

                $rowType = strtoupper((string) ($row['type'] ?? ''));
                $content = trim((string) ($row['content'] ?? ''), '"');

                $clashes = match ($record['replace']) {
                    self::REPLACE_HOST => in_array($rowType, ['A', 'AAAA', 'CNAME'], true),
                    self::REPLACE_TYPE => $rowType === $type,
                    self::REPLACE_PREFIX => $rowType === 'TXT' && str_starts_with($content, (string) $record['prefix']),
                    default => false,
                };

                if ($clashes) {
                    $delete[$row['id']] = $row;
                }
            }

            if ($record['replace'] === self::ADD) {
                $already = collect($existing)->contains(fn ($row) => ! isset($delete[$row['id'] ?? ''])
                    && $norm($row['name'] ?? '') === $name
                    && strtoupper((string) ($row['type'] ?? '')) === $type
                    && $norm($row['content'] ?? '') === $norm($record['content']));

                if ($already) {
                    continue;
                }
            }

            $create[] = $record;
        }

        return ['delete' => array_values($delete), 'create' => $create];
    }

    /**
     * The body Cloudflare's create-record call takes.
     *
     * @param  array<string,mixed>  $record
     * @return array<string,mixed>
     */
    public static function payload(array $record): array
    {
        $body = [
            'type' => $record['type'],
            'name' => $record['name'],
            'content' => $record['content'],
            'ttl' => 1,
            'comment' => 'XMAN Studio quick setup',
        ];

        if (in_array($record['type'], ['A', 'AAAA', 'CNAME'], true)) {
            $body['proxied'] = (bool) $record['proxied'];
        }

        if ($record['type'] === 'MX') {
            $body['priority'] = (int) $record['priority'];
        }

        return $body;
    }

    /**
     * @return array<string,mixed>
     */
    private static function record(
        string $type,
        string $name,
        string $content,
        string $replace,
        bool $proxied = false,
        ?int $priority = null,
        ?string $prefix = null,
    ): array {
        return compact('type', 'name', 'content', 'replace', 'proxied', 'priority', 'prefix');
    }

    private static function verification(?string $code, string $prefix, callable $fail): ?string
    {
        $code = trim((string) $code, " \t\n\r\0\x0B\"");

        if ($code === '') {
            return null;
        }

        if (! str_starts_with($code, $prefix)) {
            $code = $prefix . $code;
        }

        if (strlen($code) > 255 || ! preg_match('/^[\x21-\x7E]+$/', $code)) {
            $fail('verification', 'รหัสยืนยันไม่ถูกต้อง — คัดลอกมาทั้งบรรทัดโดยไม่มีช่องว่าง');
        }

        return $code;
    }
}
