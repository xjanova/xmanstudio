<?php

namespace App\Services\AppAuth;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

/**
 * Device-code sign-in (the QR on the app's sign-in page, installer contract 1.3).
 *
 * The app asks for a pair of codes: a secret device_code it keeps, and a short user_code the
 * person types (or scans) on /device while signed in on the website. The app polls with the
 * device_code until the person approves or cancels there.
 *
 * Everything lives in the cache for ten minutes. The raw device_code is never stored — only
 * its sha256 — so a cache dump cannot be replayed. The user_code key holds only a pointer to
 * that hash. An approval is handed out once: the first poll that sees it consumes it.
 */
class DeviceAuthorizations
{
    public const TTL_SECONDS = 600;

    /** Seconds the app waits between polls. */
    public const INTERVAL_SECONDS = 3;

    /** A poll this much earlier than the interval still counts as on time (network jitter). */
    private const JITTER_MS = 500;

    /** No 0/O, 1/I — read off a phone screen without mistakes. */
    public const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DENIED = 'denied';

    private const PREFIX = 'app-auth:device:';

    /**
     * @return array{device_code: string, user_code: string, expires_at: int}
     */
    public function start(Product $product, string $machineId, ?string $machineName, ?string $appVersion): array
    {
        $deviceCode = bin2hex(random_bytes(32));
        $hash = self::hash($deviceCode);
        $now = now()->getTimestamp();
        $expiresAt = $now + self::TTL_SECONDS;

        // Reserve a user_code nobody else holds right now (add() is atomic)
        do {
            $userCode = self::randomUserCode();
        } while (! Cache::add(self::PREFIX . 'user:' . $userCode, $hash, self::TTL_SECONDS));

        Cache::put(self::PREFIX . 'code:' . $hash, [
            'product_id' => $product->id,
            'machine_id' => $machineId,
            'machine_name' => $machineName,
            'app_version' => $appVersion,
            'status' => self::STATUS_PENDING,
            'user_id' => null,
            'user_code' => $userCode,
            'created_at' => $now,
            'expires_at' => $expiresAt,
        ], self::TTL_SECONDS);

        return [
            'device_code' => $deviceCode,
            'user_code' => self::formatUserCode($userCode),
            'expires_at' => $expiresAt,
        ];
    }

    /** The pending/decided request behind a device_code, or null when unknown or expired. */
    public function findByDeviceCode(string $deviceCode): ?array
    {
        return $this->record(self::hash($deviceCode));
    }

    /** The request behind a typed user_code (any case, dash optional), with its `hash`, or null. */
    public function findByUserCode(?string $input): ?array
    {
        $code = self::normalizeUserCode($input);
        if ($code === null) {
            return null;
        }

        $hash = Cache::get(self::PREFIX . 'user:' . $code);
        if (! is_string($hash)) {
            return null;
        }

        $record = $this->record($hash);

        return $record && $record['user_code'] === $code ? $record + ['hash' => $hash] : null;
    }

    /**
     * The person on /device answers. Returns what the page should say:
     *   'approved' / 'denied' — this answer was recorded (or this person already gave it)
     *   'invalid'             — unknown, expired, or already answered by someone else
     */
    public function decide(?string $userCode, User $user, bool $approve): string
    {
        $found = $this->findByUserCode($userCode);
        if (! $found) {
            return 'invalid';
        }

        $hash = $found['hash'];
        $wanted = $approve ? self::STATUS_APPROVED : self::STATUS_DENIED;

        try {
            return Cache::lock(self::PREFIX . 'lock:' . $hash, 5)->block(3, function () use ($hash, $user, $wanted) {
                $record = $this->record($hash);
                if (! $record) {
                    return 'invalid';
                }

                if ($record['status'] !== self::STATUS_PENDING) {
                    // Pressing the same button twice is fine; anyone else answering an answered code is not
                    return (int) $record['user_id'] === (int) $user->id && $record['status'] === $wanted
                        ? $wanted
                        : 'invalid';
                }

                $record['status'] = $wanted;
                $record['user_id'] = (int) $user->id;
                $this->save($hash, $record);

                return $wanted;
            });
        } catch (LockTimeoutException) {
            return 'invalid';
        }
    }

    /**
     * Records this poll and says whether it came sooner than the interval allows.
     */
    public function pollTooSoon(string $deviceCode): bool
    {
        $key = self::PREFIX . 'poll:' . self::hash($deviceCode);
        $now = now()->getTimestampMs();
        $last = Cache::get($key);

        Cache::put($key, $now, self::TTL_SECONDS);

        return is_int($last) && ($now - $last) < (self::INTERVAL_SECONDS * 1000 - self::JITTER_MS);
    }

    /**
     * Take an approval out, once. True for exactly one caller; the codes are gone afterwards.
     */
    public function consume(string $deviceCode): bool
    {
        $hash = self::hash($deviceCode);

        if (! Cache::add(self::PREFIX . 'used:' . $hash, true, self::TTL_SECONDS)) {
            return false;
        }

        $record = $this->record($hash);
        if ($record) {
            Cache::forget(self::PREFIX . 'user:' . $record['user_code']);
        }
        Cache::forget(self::PREFIX . 'code:' . $hash);

        return $record !== null;
    }

    /** Seconds left before the code expires (0 when it has). */
    public static function secondsLeft(array $record): int
    {
        return max(0, (int) $record['expires_at'] - now()->getTimestamp());
    }

    /** "kq7m-4tzp", "KQ7M4TZP", " kq7m 4tzp " → "KQ7M4TZP"; anything else → null. */
    public static function normalizeUserCode(?string $input): ?string
    {
        if (! is_string($input)) {
            return null;
        }

        $code = strtoupper((string) preg_replace('/[\s\-]+/', '', $input));

        return preg_match('/^[' . self::ALPHABET . ']{8}$/', $code) === 1 ? $code : null;
    }

    public static function formatUserCode(string $code): string
    {
        return substr($code, 0, 4) . '-' . substr($code, 4, 4);
    }

    private static function randomUserCode(): string
    {
        $code = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < 8; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        return $code;
    }

    private static function hash(string $deviceCode): string
    {
        return hash('sha256', $deviceCode);
    }

    private function record(string $hash): ?array
    {
        $record = Cache::get(self::PREFIX . 'code:' . $hash);

        if (! is_array($record) || (int) $record['expires_at'] <= now()->getTimestamp()) {
            return null;
        }

        return $record;
    }

    private function save(string $hash, array $record): void
    {
        $ttl = self::secondsLeft($record);
        if ($ttl > 0) {
            Cache::put(self::PREFIX . 'code:' . $hash, $record, $ttl);
        }
    }
}
