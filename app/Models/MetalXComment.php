<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetalXComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'video_id',
        'comment_id',
        'parent_id',
        'author_name',
        'author_channel_id',
        'author_profile_image',
        'text',
        'like_count',
        'can_reply',
        'published_at',
        'updated_at_youtube',
        'ai_replied',
        'ai_reply_text',
        'ai_reply_confidence',
        'ai_replied_at',
        'ai_reply_comment_id',
        'liked_by_channel',
        'liked_at',
        'sentiment',
        'sentiment_score',
        'requires_attention',
        'is_spam',
        'is_hidden',
        'is_blacklisted_author',
        'violation_type',
    ];

    protected $casts = [
        'like_count' => 'integer',
        'can_reply' => 'boolean',
        'published_at' => 'datetime',
        'updated_at_youtube' => 'datetime',
        'ai_replied' => 'boolean',
        'ai_reply_confidence' => 'decimal:2',
        'ai_replied_at' => 'datetime',
        'liked_by_channel' => 'boolean',
        'liked_at' => 'datetime',
        'sentiment_score' => 'decimal:2',
        'requires_attention' => 'boolean',
        'is_spam' => 'boolean',
        'is_hidden' => 'boolean',
        'is_blacklisted_author' => 'boolean',
    ];

    /**
     * Get the video that owns the comment.
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(MetalXVideo::class, 'video_id');
    }

    /**
     * Scope a query to only include top-level comments (not replies).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include replies.
     */
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope a query to only include comments that need AI reply.
     */
    public function scopeNeedsReply($query)
    {
        return $query->where('ai_replied', false)
            ->where('is_spam', false)
            ->where('is_hidden', false)
            ->where('can_reply', true);
    }

    /**
     * Scope a query to only include comments with positive sentiment.
     */
    public function scopePositive($query)
    {
        return $query->where('sentiment', 'positive');
    }

    /**
     * Scope a query to only include questions.
     */
    public function scopeQuestions($query)
    {
        return $query->where('sentiment', 'question');
    }

    /**
     * Scope a query to order by latest.
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('published_at');
    }

    /**
     * Scope a query to order by most liked.
     */
    public function scopePopular($query)
    {
        return $query->orderByDesc('like_count');
    }

    /**
     * Check if comment is a reply.
     */
    public function isReply(): bool
    {
        return ! empty($this->parent_id);
    }

    /**
     * Check if comment needs attention.
     */
    public function needsAttention(): bool
    {
        return $this->requires_attention ||
               $this->sentiment === 'negative' ||
               $this->sentiment === 'question';
    }

    /**
     * Check if author is blacklisted.
     */
    public function isAuthorBlacklisted(): bool
    {
        if (! $this->author_channel_id) {
            return false;
        }

        return MetalXBlacklist::isBlacklisted($this->author_channel_id);
    }

    /**
     * Scope to only include blacklisted authors.
     */
    public function scopeBlacklistedAuthors($query)
    {
        return $query->where('is_blacklisted_author', true);
    }

    /**
     * Scope to filter by violation type.
     */
    public function scopeByViolationType($query, string $type)
    {
        return $query->where('violation_type', $type);
    }
}
