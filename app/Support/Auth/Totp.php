<?php

namespace App\Support\Auth;

/**
 * Time-based one-time passwords (RFC 6238) — the six digits an authenticator
 * app shows: Google Authenticator, Microsoft Authenticator, 1Password, Authy.
 *
 * SHA-1, 6 digits, 30-second steps: the defaults every one of those apps
 * assumes when it scans an otpauth:// QR code. Written out here rather than
 * pulled in as a package because the whole algorithm is one HMAC and a
 * truncation, and the tests pin it to the RFC's own vectors.
 */
final class Totp
{
    public const DIGITS = 6;

    public const PERIOD = 30;

    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /** A new shared secret: 160 random bits, base32 as the apps expect. */
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    /** The code for a given 30-second step. */
    public static function at(string $secret, int $step, int $digits = self::DIGITS): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);

        // Dynamic truncation (RFC 4226 §5.3).
        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | (ord($hash[$offset + 1]) << 16)
            | (ord($hash[$offset + 2]) << 8)
            | ord($hash[$offset + 3]);

        return str_pad((string) ($binary % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    public static function stepAt(int $timestamp): int
    {
        return intdiv($timestamp, self::PERIOD);
    }

    /**
     * The step a code belongs to, or null when it matches none.
     *
     * One step either side is accepted, for a phone whose clock has drifted or
     * a code typed as it rolled over. The caller keeps the returned step and
     * refuses it next time, so a code seen over a shoulder cannot be replayed.
     */
    public static function verify(string $secret, string $code, ?int $timestamp = null, int $window = 1): ?int
    {
        $code = preg_replace('/\s+/', '', $code);

        if (! preg_match('/^\d{' . self::DIGITS . '}$/', (string) $code)) {
            return null;
        }

        $current = self::stepAt($timestamp ?? time());

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals(self::at($secret, $current + $offset), $code)) {
                return $current + $offset;
            }
        }

        return null;
    }

    /** What the QR code carries; the app reads issuer and account from it. */
    public static function provisioningUri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($account)
            . '?' . http_build_query([
                'secret' => $secret,
                'issuer' => $issuer,
                'algorithm' => 'SHA1',
                'digits' => self::DIGITS,
                'period' => self::PERIOD,
            ], '', '&', PHP_QUERY_RFC3986);
    }

    public static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 5) as $chunk) {
            $out .= self::BASE32[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $out;
    }

    public static function base32Decode(string $text): string
    {
        $text = strtoupper(preg_replace('/[\s=-]/', '', $text));

        $bits = '';
        foreach (str_split($text) as $char) {
            $value = strpos(self::BASE32, $char);

            if ($value === false) {
                continue;
            }

            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }
}
