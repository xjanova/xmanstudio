<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetalXBlacklist extends Model
{
    protected $table = 'metal_x_blacklist';

    protected $fillable = [
        'channel_id',
        'channel_name',
        'reason',
        'notes',
        'violation_count',
        'first_violation_at',
        'last_violation_at',
        'is_blocked',
        'blocked_by',
    ];

    protected $casts = [
        'violation_count' => 'integer',
        'first_violation_at' => 'datetime',
        'last_violation_at' => 'datetime',
        'is_blocked' => 'boolean',
    ];

    /**
     * Get the user who blocked this channel.
     */
    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * Scope to only include blocked channels.
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /**
     * Scope to filter by reason.
     */
    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    /**
     * Scope to order by violation count.
     */
    public function scopeMostViolations($query)
    {
        return $query->orderByDesc('violation_count');
    }

    /**
     * Increment violation count.
     */
    public function recordViolation(): void
    {
        $this->increment('violation_count');
        $this->update(['last_violation_at' => now()]);
    }

    /**
     * Check if channel is blacklisted.
     */
    public static function isBlacklisted(string $channelId): bool
    {
        return self::where('channel_id', $channelId)
            ->where('is_blocked', true)
            ->exists();
    }

    /**
     * Add channel to blacklist.
     */
    public static function addToBlacklist(
        string $channelId,
        string $channelName,
        string $reason,
        ?string $notes = null,
        ?int $blockedBy = null
    ): self {
        return self::updateOrCreate(
            ['channel_id' => $channelId],
            [
                'channel_name' => $channelName,
                'reason' => $reason,
                'notes' => $notes,
                'first_violation_at' => now(),
                'last_violation_at' => now(),
                'is_blocked' => true,
                'blocked_by' => $blockedBy,
            ]
        );
    }

    /**
     * Unblock channel.
     */
    public function unblock(): void
    {
        $this->update(['is_blocked' => false]);
    }
}
