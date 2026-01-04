<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    // Status constants
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_REPLY = 'waiting_reply';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    // Priority constants
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    // Category constants
    public const CATEGORY_GENERAL = 'general';
    public const CATEGORY_TECHNICAL = 'technical';
    public const CATEGORY_BILLING = 'billing';
    public const CATEGORY_LICENSE = 'license';
    public const CATEGORY_FEATURE_REQUEST = 'feature_request';

    protected $fillable = [
        'ticket_number',
        'user_id',
        'order_id',
        'name',
        'email',
        'subject',
        'message',
        'category',
        'priority',
        'status',
        'attachments',
        'assigned_to',
        'admin_response',
        'responded_at',
        'last_reply_at',
        'last_reply_by',
        'closed_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'responded_at' => 'datetime',
        'last_reply_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }
        });
    }

    /**
     * Generate unique ticket number
     */
    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -4));

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Get all available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN => 'เปิด',
            self::STATUS_IN_PROGRESS => 'กำลังดำเนินการ',
            self::STATUS_WAITING_REPLY => 'รอการตอบกลับ',
            self::STATUS_RESOLVED => 'แก้ไขแล้ว',
            self::STATUS_CLOSED => 'ปิด',
        ];
    }

    /**
     * Get all available priorities
     */
    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'ต่ำ',
            self::PRIORITY_MEDIUM => 'ปานกลาง',
            self::PRIORITY_HIGH => 'สูง',
            self::PRIORITY_URGENT => 'เร่งด่วน',
        ];
    }

    /**
     * Get all available categories
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_GENERAL => 'ทั่วไป',
            self::CATEGORY_TECHNICAL => 'ปัญหาทางเทคนิค',
            self::CATEGORY_BILLING => 'การเงิน/ใบแจ้งหนี้',
            self::CATEGORY_LICENSE => 'License',
            self::CATEGORY_FEATURE_REQUEST => 'ขอฟีเจอร์ใหม่',
        ];
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function lastReplyBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_reply_by');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class, 'ticket_id');
    }

    public function publicReplies(): HasMany
    {
        return $this->hasMany(TicketReply::class, 'ticket_id')
            ->where('is_internal', false)
            ->orderBy('created_at', 'asc');
    }

    // Scopes

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_WAITING_REPLY]);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    // Helper methods

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_WAITING_REPLY]);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function isUrgent(): bool
    {
        return $this->priority === self::PRIORITY_URGENT;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::getPriorities()[$this->priority] ?? $this->priority;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::getCategories()[$this->category] ?? $this->category;
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'blue',
            self::STATUS_IN_PROGRESS => 'yellow',
            self::STATUS_WAITING_REPLY => 'orange',
            self::STATUS_RESOLVED => 'green',
            self::STATUS_CLOSED => 'gray',
            default => 'gray',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_MEDIUM => 'blue',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_URGENT => 'red',
            default => 'gray',
        };
    }

    /**
     * Add a reply to the ticket
     */
    public function addReply(User $user, string $message, array $attachments = [], bool $isInternal = false): TicketReply
    {
        $reply = $this->replies()->create([
            'user_id' => $user->id,
            'message' => $message,
            'attachments' => $attachments,
            'is_internal' => $isInternal,
        ]);

        // Update last reply info
        $this->update([
            'last_reply_at' => now(),
            'last_reply_by' => $user->id,
        ]);

        return $reply;
    }

    /**
     * Assign ticket to a staff member
     */
    public function assignTo(User $user): void
    {
        $this->update([
            'assigned_to' => $user->id,
            'status' => self::STATUS_IN_PROGRESS,
        ]);
    }

    /**
     * Close the ticket
     */
    public function close(): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
    }

    /**
     * Resolve the ticket
     */
    public function resolve(): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'closed_at' => now(),
        ]);
    }

    /**
     * Reopen the ticket
     */
    public function reopen(): void
    {
        $this->update([
            'status' => self::STATUS_OPEN,
            'closed_at' => null,
        ]);
    }
}
