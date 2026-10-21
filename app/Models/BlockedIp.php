<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class BlockedIp extends Model
{
    use HasFactory;

    public const SOURCE_AUTO = 'auto';

    public const SOURCE_MANUAL = 'manual';

    /**
     * How long the "is this address blocked" answer is cached.
     *
     * Short on purpose. The middleware asks on every single request, so it must
     * not be a query — but an admin who unblocks someone should not have to
     * explain why the block is still there a minute later.
     */
    private const CACHE_SECONDS = 30;

    protected $fillable = [
        'ip',
        'reason',
        'source',
        'expires_at',
        'hits',
        'last_hit_at',
        'blocked_by',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_hit_at' => 'datetime',
        ];
    }

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /** A null expiry is permanent, not expired. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function isPermanent(): bool
    {
        return $this->expires_at === null;
    }

    /**
     * The hot path: called for every request that reaches the app.
     *
     * Returns the row so the caller can record the hit and show the reason,
     * or null when the address is free to pass.
     */
    public static function findActive(?string $ip): ?self
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        $id = Cache::remember(self::cacheKey($ip), self::CACHE_SECONDS, function () use ($ip) {
            // 0 rather than null: Cache::remember treats a null return as a
            // miss and would re-query on every request for every visitor who
            // is not blocked, which is all of them.
            return static::query()->active()->where('ip', $ip)->value('id') ?? 0;
        });

        if (! $id) {
            return null;
        }

        $block = static::find($id);

        // The cache can outlive the row (unblocked, or expired between the two
        // lines above). Re-checking here keeps a stale id from blocking anyone.
        if (! $block || ! $block->isActive()) {
            static::forget($ip);

            return null;
        }

        return $block;
    }

    /**
     * Block an address, or extend a block that is already there.
     *
     * An automatic block never overrules a manual one: an operator who blocked
     * an address for good should not find it quietly expiring in an hour
     * because the counter tripped again.
     */
    public static function block(string $ip, string $reason, ?\DateTimeInterface $until, string $source = self::SOURCE_AUTO, ?int $byUserId = null): self
    {
        $block = static::firstOrNew(['ip' => $ip]);

        $keepManual = $block->exists
            && $block->source === self::SOURCE_MANUAL
            && $source === self::SOURCE_AUTO;

        if (! $keepManual) {
            $block->reason = $reason;
            $block->source = $source;
            $block->blocked_by = $byUserId;

            // Never shorten a block that already runs longer, and never turn a
            // permanent one back into a temporary one.
            if (! ($block->exists && $block->expires_at === null)) {
                $block->expires_at = $until;
            }
        }

        $block->save();

        static::forget($ip);

        return $block;
    }

    public static function unblock(string $ip): void
    {
        static::query()->where('ip', $ip)->delete();

        static::forget($ip);
    }

    public function recordHit(): void
    {
        // Not a model save: this runs on every refused request, and a blocked
        // bot refreshes a lot.
        static::query()->whereKey($this->getKey())->update([
            'hits' => $this->hits + 1,
            'last_hit_at' => now(),
        ]);
    }

    public static function forget(string $ip): void
    {
        Cache::forget(self::cacheKey($ip));
    }

    private static function cacheKey(string $ip): string
    {
        return 'blocked-ip:' . sha1($ip);
    }
}
