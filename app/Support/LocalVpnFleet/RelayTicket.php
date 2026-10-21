<?php

namespace App\Support\LocalVpnFleet;

/**
 * LVR1 relay tickets (docs/RELAY_FLEET.md, "Ticket").
 *
 *   v1.<node_id>.<network_id>.<vip>.<exp>.<kbps>.<daily_mb>.<sig>
 *   sig = lowercase hex HMAC-SHA256(node_secret, everything before ".<sig>")
 *
 * The vip is a dotted IPv4 address, so a parser takes the first three fields,
 * the next four as the address, then exp, kbps, daily_mb and the signature.
 * kbps = 0 / daily_mb = 0 mean no cap.
 */
final class RelayTicket
{
    public const VERSION = 'v1';

    /** exp moves in 10-minute steps so a 15 s heartbeat keeps getting the same token. */
    public const STEP_SECONDS = 600;

    /** Next 10-minute boundary at or after $now, plus ten minutes: valid 10–20 min. */
    public static function expiry(int $now): int
    {
        return intdiv($now + self::STEP_SECONDS - 1, self::STEP_SECONDS) * self::STEP_SECONDS + self::STEP_SECONDS;
    }

    public static function payload(int $nodeId, int $networkId, string $vip, int $exp, int $kbps, int $dailyMb): string
    {
        return implode('.', [self::VERSION, $nodeId, $networkId, $vip, $exp, $kbps, $dailyMb]);
    }

    public static function token(string $secret, int $nodeId, int $networkId, string $vip, int $exp, int $kbps, int $dailyMb): string
    {
        $payload = self::payload($nodeId, $networkId, $vip, $exp, $kbps, $dailyMb);

        return $payload . '.' . hash_hmac('sha256', $payload, $secret);
    }

    /** What a node does with a token: true when the signature is ours. */
    public static function verify(string $secret, string $token): bool
    {
        $cut = strrpos($token, '.');
        if ($cut === false) {
            return false;
        }

        $expected = hash_hmac('sha256', substr($token, 0, $cut), $secret);

        return hash_equals($expected, substr($token, $cut + 1));
    }
}
