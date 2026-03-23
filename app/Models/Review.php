<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reviewable_id',
        'reviewable_type',
        'rating',
        'title',
        'comment',
        'status',
        'admin_note',
        'is_featured',
        'approved_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_featured' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public const STATUS_LABELS = [
        'pending' => 'รอตรวจสอบ',
        'approved' => 'อนุมัติ',
        'rejected' => 'ปฏิเสธ',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Accessors

    public function getReviewableNameAttribute(): string
    {
        return $this->reviewable?->name ?? '-';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
