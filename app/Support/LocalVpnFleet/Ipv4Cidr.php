<?php

namespace App\Support\LocalVpnFleet;

/**
 * IPv4 CIDRs for game exits.
 *
 * A game node forwards only to these ranges, so a sloppy entry is a hole:
 * 0.0.0.0/1 would turn the exit into a free full VPN, and 10.0.0.0/8 or
 * 169.254.0.0/16 would let users reach the VPS provider's internal network and
 * metadata service. Hence: aligned network address, prefix /8 or longer, and
 * nothing that touches private, loopback, link-local, CGNAT, documentation,
 * multicast or reserved space.
 */
final class Ipv4Cidr
{
    public const MIN_PREFIX = 8;

    /** Ranges a game exit must never forward to. */
    private const FORBIDDEN = [
        '0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16',
        '172.16.0.0/12', '192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24', '192.168.0.0/16',
        '198.18.0.0/15', '198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4',
    ];

    /**
     * Normalised "a.b.c.d/n" for a valid entry (a bare address becomes /32),
     * or a Thai reason it was refused.
     *
     * @return array{0: ?string, 1: ?string} [cidr, error]
     */
    public static function check(string $entry): array
    {
        $entry = trim($entry);

        if (str_contains($entry, ':')) {
            return [null, "{$entry}: รองรับเฉพาะ IPv4 ตอนนี้"];
        }

        $parts = explode('/', $entry);
        if (count($parts) > 2) {
            return [null, "{$entry}: รูปแบบไม่ถูกต้อง (ตัวอย่าง 43.229.64.0/18)"];
        }

        $ip = $parts[0];
        $prefixText = $parts[1] ?? '32';

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false || ! preg_match('/^\d{1,2}$/', $prefixText)) {
            return [null, "{$entry}: รูปแบบไม่ถูกต้อง (ตัวอย่าง 43.229.64.0/18)"];
        }

        $prefix = (int) $prefixText;
        if ($prefix > 32) {
            return [null, "{$entry}: prefix ต้องอยู่ระหว่าง /" . self::MIN_PREFIX . ' ถึง /32'];
        }
        if ($prefix < self::MIN_PREFIX) {
            return [null, "{$entry}: กว้างเกินไป — ต้องเป็น /" . self::MIN_PREFIX . ' หรือแคบกว่า'];
        }

        [$start, $end] = self::range($ip, $prefix);
        $network = long2ip($start) . '/' . $prefix;

        if (ip2long($ip) !== $start) {
            return [null, "{$entry}: ไม่ตรงขอบเขตเครือข่าย — ควรเป็น {$network}"];
        }

        foreach (self::FORBIDDEN as $forbidden) {
            [$fIp, $fPrefix] = explode('/', $forbidden);
            [$fStart, $fEnd] = self::range($fIp, (int) $fPrefix);
            if ($start <= $fEnd && $fStart <= $end) {
                return [null, "{$entry}: ทับช่วงที่สงวนไว้ ({$forbidden}) — ใช้กับ game exit ไม่ได้"];
            }
        }

        return [$network, null];
    }

    /**
     * Validate a textarea of CIDRs: one per line, blank lines and "# comments"
     * ignored.
     *
     * @return array{0: list<string>, 1: list<string>} [normalised cidrs, errors]
     */
    public static function parseList(string $text): array
    {
        $cidrs = [];
        $errors = [];

        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim(explode('#', $line, 2)[0]);
            if ($line === '') {
                continue;
            }

            [$cidr, $error] = self::check($line);
            if ($error !== null) {
                $errors[] = $error;
            } else {
                $cidrs[$cidr] = true;
            }
        }

        return [array_keys($cidrs), $errors];
    }

    /** @return array{0: int, 1: int} first and last address as integers */
    private static function range(string $ip, int $prefix): array
    {
        $long = ip2long($ip);
        $mask = $prefix === 0 ? 0 : (~((1 << (32 - $prefix)) - 1) & 0xFFFFFFFF);
        $start = $long & $mask;

        return [$start, $start | (~$mask & 0xFFFFFFFF)];
    }
}
