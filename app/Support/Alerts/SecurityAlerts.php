<?php

namespace App\Support\Alerts;

use App\Models\User;
use App\Support\AdminAlerts;
use App\Support\Telegram\BotActions;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Break-in attempts and the signs of one, reported to the admin chat — and counted per day, so
 * /security can show the shape of it.
 *
 * Nothing here blocks anyone: the existing limits (login throttle, device keys, signatures) already
 * do that, silently. What was missing is that nobody ever heard about it — a password being guessed,
 * a payment webhook being forged, an admin session from a network the admin has never used.
 *
 * Each signal has a threshold and a throttle, so one scanner is one card, not a thousand. Never
 * throws: a security check must not become the thing that breaks a login.
 */
final class SecurityAlerts
{
    public const STAT_LABELS = [
        'failed_login' => 'ล็อกอินผิด',
        'lockout' => 'ถูกล็อกชั่วคราว',
        'admin_login' => 'แอดมินล็อกอิน',
        'forged' => 'ลายเซ็น/webhook ปลอม',
        'probe' => 'ลองคีย์หรือสิทธิ์ผิด',
        'license' => 'License ผิดปกติ',
        'forbidden' => 'เข้าหน้าแอดมินไม่ได้รับอนุญาต',
    ];

    /** Failed logins from one IP inside the window before it is reported as guessing. */
    private const IP_THRESHOLD = 10;

    /** Failed logins on one ADMIN account (from anywhere) before it is reported. */
    private const ADMIN_THRESHOLD = 3;

    private const WINDOW_MINUTES = 15;

    // ================================================================================ logins

    public static function failedLogin(?string $email, ?string $ip, ?User $account): void
    {
        self::guard(function () use ($email, $ip, $account) {
            self::count('failed_login', $ip);

            $byIp = self::tick('sec:fail:ip:' . ($ip ?? '-'));
            $isAdmin = (bool) $account?->isAdmin();
            $byAccount = $isAdmin ? self::tick('sec:fail:acct:' . sha1(strtolower((string) $email))) : 0;

            if ($isAdmin && $byAccount >= self::ADMIN_THRESHOLD) {
                AdminAlerts::send(new Alert(
                    key: 'admin-brute:' . sha1(strtolower((string) $email)),
                    level: Alert::CRITICAL,
                    title: 'มีคนพยายามล็อกอินบัญชีแอดมิน',
                    body: 'บัญชี: ' . self::maskEmail($email) . "\nรหัสผ่านผิด {$byAccount} ครั้งใน " . self::WINDOW_MINUTES . ' นาที'
                        . "\nถ้าไม่ใช่คุณ ให้เปลี่ยนรหัสผ่านทันที — ระบบล็อกชั่วคราวให้เองหลังผิด 5 ครั้งต่อ IP",
                    facts: ['ครั้งที่ผิด' => $byAccount, 'IP ล่าสุด' => $ip ?? '—', 'ช่วงเวลา' => self::WINDOW_MINUTES . ' นาที'],
                    url: url('/admin/users'),
                    urlLabel: 'เปิดจัดการผู้ใช้',
                    category: 'security',
                    buttons: [[BotActions::ackButton('x')]],
                ), 60);

                return;
            }

            if ($byIp === self::IP_THRESHOLD || $byIp === self::IP_THRESHOLD * 5) {
                AdminAlerts::send(new Alert(
                    key: 'login-brute:' . ($ip ?? '-') . ':' . $byIp,
                    level: $byIp >= self::IP_THRESHOLD * 5 ? Alert::CRITICAL : Alert::WARNING,
                    title: 'มีคนเดารหัสผ่าน ' . $byIp . ' ครั้งใน ' . self::WINDOW_MINUTES . ' นาที',
                    body: 'จาก IP เดียวกัน · บัญชีล่าสุดที่ลอง: ' . self::maskEmail($email)
                        . "\nระบบจำกัดความถี่ให้แล้ว (5 ครั้งต่อ IP ต่ออีเมล) — ถ้าเกิดต่อเนื่อง ควรบล็อก IP นี้ที่ Cloudflare",
                    facts: ['IP' => $ip ?? '—', 'ครั้ง' => $byIp],
                    category: 'security',
                    buttons: [[BotActions::muteButton('login-brute-ip:' . ($ip ?? '-'))]],
                ), 60);
            }
        });
    }

    public static function lockout(?string $email, ?string $ip): void
    {
        self::guard(function () use ($ip) {
            self::count('lockout', $ip);
        });
    }

    /**
     * An admin signed in. From a network this admin has signed in from before: nothing to say. From
     * a new one: worth a look — a stolen password looks exactly like this.
     */
    public static function adminLogin(User $user, ?string $ip, ?string $agent): void
    {
        self::guard(function () use ($user, $ip, $agent) {
            if (! $user->isAdmin()) {
                return;
            }
            self::count('admin_login', null);
            $network = self::network($ip);
            $key = 'sec:admin-nets:' . $user->id;
            $known = Cache::get($key, []);
            $known = is_array($known) ? $known : [];
            $first = $known === [];
            $isNew = ! isset($known[$network]);
            $known[$network] = time();
            arsort($known);
            Cache::put($key, array_slice($known, 0, 20, true), now()->addDays(90));

            if (! $isNew) {
                return;
            }

            AdminAlerts::send(new Alert(
                key: 'admin-new-net:' . $user->id . ':' . $network,
                // The very first sighting after this feature ships is learning, not news.
                level: $first ? Alert::INFO : Alert::WARNING,
                title: 'แอดมิน ' . Str::limit((string) $user->name, 30) . ' ล็อกอินจากเครือข่ายใหม่',
                body: 'อุปกรณ์: ' . self::device($agent)
                    . "\nถ้าไม่ใช่คุณ ให้เปลี่ยนรหัสผ่านบัญชีนี้ทันที และออกจากระบบทุกอุปกรณ์",
                facts: ['IP' => $ip ?? '—', 'บัญชี' => self::maskEmail($user->email)],
                url: url('/admin/users/' . $user->id),
                urlLabel: 'เปิดบัญชีนี้',
                category: 'security',
                buttons: [[BotActions::ackButton('l', '✅ นี่คือฉันเอง')]],
            ), 60);
        });
    }

    /** A logged-in non-admin opening an admin page. */
    public static function forbidden(User $user, string $path, ?string $ip): void
    {
        self::guard(function () use ($user, $path, $ip) {
            self::count('forbidden', $ip);
            AdminAlerts::send(new Alert(
                key: 'admin-forbidden:' . $user->id,
                level: Alert::INFO,
                title: 'สมาชิกพยายามเปิดหน้าแอดมิน',
                body: 'ผู้ใช้: ' . Str::limit((string) $user->name, 40) . ' (' . self::maskEmail($user->email) . ')' . "\nหน้า: /" . ltrim(Str::limit($path, 80), '/')
                    . "\nระบบปฏิเสธแล้ว (403) — ถ้าเกิดซ้ำหลายหน้า อาจเป็นการสำรวจช่องโหว่",
                facts: ['IP' => $ip ?? '—', 'ผู้ใช้ #' => (string) $user->id],
                category: 'security',
            ), 360);
        });
    }

    /** Someone POSTed the first-run setup form on an installed site. */
    public static function setupRerun(?string $ip): void
    {
        self::guard(function () use ($ip) {
            self::count('probe', $ip);
            AdminAlerts::send(new Alert(
                key: 'setup-rerun:' . ($ip ?? '-'),
                level: Alert::CRITICAL,
                title: 'มีคนพยายามรันหน้าติดตั้งระบบซ้ำ',
                body: 'ฟอร์ม /setup ถูกส่งเข้ามาทั้งที่ระบบติดตั้งแล้ว — ระบบปฏิเสธแล้ว แต่นี่คือการพยายามสร้างแอดมินใหม่',
                facts: ['IP' => $ip ?? '—'],
                category: 'security',
            ), 60);
        });
    }

    // ================================================================================ webhooks & keys

    /**
     * A request that carried a credential and got it wrong where it matters — a payment webhook
     * signed with the wrong key, a replayed nonce. The key itself may have leaked.
     *
     * @param  array<string,string|int>  $facts
     */
    public static function forged(string $source, string $what, ?string $ip, array $facts = [], string $level = Alert::CRITICAL): void
    {
        self::guard(function () use ($source, $what, $ip, $facts, $level) {
            self::count('forged', $ip);
            AdminAlerts::send(new Alert(
                key: 'forged:' . $source . ':' . ($ip ?? '-'),
                level: $level,
                title: $source . ': ' . $what,
                body: 'คำขอนี้ถูกปฏิเสธแล้ว — แต่ถ้ามาจากคนนอก แปลว่าเขามีคีย์บางส่วนของเรา ควรหมุนคีย์ (rotate) ของ ' . $source,
                facts: ['IP' => $ip ?? '—'] + $facts,
                category: 'security',
                buttons: [[BotActions::muteButton('forged:' . $source . ':' . ($ip ?? '-'))]],
            ), 60);
        });
    }

    /** Someone knocking with a key we never issued — a scanner, most of the time. */
    public static function probe(string $source, string $what, ?string $ip): void
    {
        self::guard(function () use ($source, $what, $ip) {
            self::count('probe', $ip);
            AdminAlerts::send(new Alert(
                key: 'probe:' . $source . ':' . ($ip ?? '-'),
                level: Alert::INFO,
                title: $source . ': ' . $what,
                body: 'ปฏิเสธแล้ว ไม่มีผลกับระบบ — แจ้งไว้เพื่อให้เห็นว่ามีคนสำรวจ endpoint นี้ (IP เดิมจะไม่แจ้งซ้ำ 6 ชม.)',
                facts: ['IP' => $ip ?? '—'],
                category: 'security',
            ), 360);
        });
    }

    /** A licensed app's device flagged or blocked for abuse (trial farming, key sharing). */
    public static function licenseAbuse(string $product, ?string $machineId, string $reason, ?string $ip, bool $blocked): void
    {
        self::guard(function () use ($product, $machineId, $reason, $ip, $blocked) {
            self::count('license', $ip);
            AdminAlerts::send(new Alert(
                key: 'license-abuse:' . $product . ':' . ($machineId ?? '-') . ':' . ($blocked ? 'b' : 's'),
                level: $blocked ? Alert::WARNING : Alert::INFO,
                title: ($blocked ? 'บล็อกเครื่องที่ใช้ ' : 'พบการใช้ผิดปกติใน ') . $product,
                body: 'เหตุผล: ' . Str::limit($reason, 200),
                facts: array_filter(['เครื่อง' => $machineId ? Str::limit($machineId, 18) : null, 'IP' => $ip]),
                category: 'security',
            ), 1440);
        });
    }

    // ================================================================================ stats

    /** @return array{counts:array<string,int>,ips:array<string,int>} a Thai calendar day's tallies */
    public static function stats(CarbonImmutable $day): array
    {
        $d = $day->setTimezone('Asia/Bangkok')->format('Ymd');
        $counts = [];
        try {
            foreach (array_keys(self::STAT_LABELS) as $kind) {
                $counts[$kind] = (int) Cache::get('sec:stats:' . $d . ':' . $kind, 0);
            }
            $ips = Cache::get('sec:ips:' . $d, []);
        } catch (Throwable) {
            $ips = [];
        }
        $counts += array_fill_keys(array_keys(self::STAT_LABELS), 0);
        $ips = is_array($ips) ? $ips : [];
        arsort($ips);

        return ['counts' => $counts, 'ips' => $ips];
    }

    /**
     * Tally one event for /security. Never blocks: this runs inside logins and webhooks, and during
     * credential stuffing a lock here would hold a PHP worker per request. The per-kind count is an
     * atomic increment; the per-IP breakdown is read-modify-write and may lose a hit under load,
     * which is fine for "who is knocking the most".
     */
    private static function count(string $kind, ?string $ip): void
    {
        $d = now('Asia/Bangkok')->format('Ymd');
        try {
            $key = 'sec:stats:' . $d . ':' . $kind;
            Cache::add($key, 0, now()->addDays(3));
            Cache::increment($key);
            if ($ip) {
                $ips = Cache::get('sec:ips:' . $d, []);
                $ips = is_array($ips) ? $ips : [];
                $ips[$ip] = ($ips[$ip] ?? 0) + 1;
                arsort($ips);
                Cache::put('sec:ips:' . $d, array_slice($ips, 0, 200, true), now()->addDays(3));
            }
        } catch (Throwable) {
            // stats are a nicety; the alert decision does not depend on them
        }
    }

    /** Increment a counter that lives for the detection window. */
    private static function tick(string $key): int
    {
        Cache::add($key, 0, now()->addMinutes(self::WINDOW_MINUTES));

        return (int) Cache::increment($key);
    }

    /** /24 for IPv4, /48 for IPv6 — close enough to "the same network" without alerting on every DHCP lease. */
    private static function network(?string $ip): string
    {
        if (! $ip) {
            return '-';
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return implode('.', array_slice(explode('.', $ip), 0, 3)) . '.0/24';
        }
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return $ip;
        }

        return implode(':', array_slice(str_split(bin2hex(substr($packed, 0, 6)), 4), 0, 3)) . '::/48';
    }

    private static function device(?string $agent): string
    {
        $a = (string) $agent;
        $os = match (true) {
            str_contains($a, 'Windows') => 'Windows',
            str_contains($a, 'iPhone') || str_contains($a, 'iPad') => 'iOS',
            str_contains($a, 'Android') => 'Android',
            str_contains($a, 'Mac OS') => 'macOS',
            str_contains($a, 'Linux') => 'Linux',
            default => 'ไม่ทราบระบบ',
        };
        $browser = match (true) {
            str_contains($a, 'Edg/') => 'Edge',
            str_contains($a, 'Chrome/') => 'Chrome',
            str_contains($a, 'Firefox/') => 'Firefox',
            str_contains($a, 'Safari/') => 'Safari',
            default => 'เบราว์เซอร์อื่น',
        };

        return $os . ' · ' . $browser;
    }

    private static function maskEmail(?string $email): string
    {
        $email = (string) $email;
        if (! str_contains($email, '@')) {
            return $email !== '' ? Str::limit($email, 3, '***') : '—';
        }
        [$user, $domain] = explode('@', $email, 2);

        return mb_substr($user, 0, 2) . '***@' . $domain;
    }

    private static function guard(callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable) {
            // never break a login or a webhook over an alert
        }
    }
}
