<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProjectOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_number',
        'user_id',
        'quotation_id',
        'project_name',
        'project_description',
        'project_type',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'status',
        'progress_percent',
        'total_price',
        'paid_amount',
        'payment_status',
        'repository_url',
        'staging_url',
        'production_url',
        'admin_notes',
        'customer_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'actual_end_date' => 'date',
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    /**
     * Status labels in Thai
     */
    public const STATUS_LABELS = [
        'pending' => 'รอเริ่มงาน',
        'in_progress' => 'กำลังดำเนินการ',
        'on_hold' => 'พักงานชั่วคราว',
        'review' => 'รอตรวจสอบ',
        'revision' => 'แก้ไข',
        'completed' => 'เสร็จสิ้น',
        'cancelled' => 'ยกเลิก',
    ];

    /**
     * Status colors for UI
     */
    public const STATUS_COLORS = [
        'pending' => 'gray',
        'in_progress' => 'blue',
        'on_hold' => 'yellow',
        'review' => 'purple',
        'revision' => 'orange',
        'completed' => 'green',
        'cancelled' => 'red',
    ];

    /**
     * Project type labels
     */
    public const TYPE_LABELS = [
        'web' => 'พัฒนาเว็บไซต์',
        'mobile' => 'พัฒนาแอปมือถือ',
        'blockchain' => 'บล็อกเชน',
        'ai' => 'ปัญญาประดิษฐ์',
        'iot' => 'IoT',
        'software' => 'ซอฟต์แวร์',
        'security' => 'ความปลอดภัย',
        'other' => 'อื่นๆ',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->project_number)) {
                $project->project_number = static::generateProjectNumber();
            }
        });
    }

    /**
     * Generate unique project number
     */
    public static function generateProjectNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "PRJ-{$date}-{$random}";
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(ProjectFeature::class)->orderBy('order');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ProjectProgress::class)->orderByDesc('created_at');
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(ProjectTimeline::class)->orderBy('event_date');
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->project_type] ?? $this->project_type;
    }

    public function getProjectLeadAttribute(): ?ProjectMember
    {
        return $this->members()->where('is_lead', true)->first();
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->total_price - $this->paid_amount;
    }

    public function getCompletedFeaturesCountAttribute(): int
    {
        return $this->features()->where('status', 'completed')->count();
    }

    public function getTotalFeaturesCountAttribute(): int
    {
        return $this->features()->count();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Methods
     */
    public function updateProgress(): void
    {
        $features = $this->features;

        if ($features->count() === 0) {
            return;
        }

        $totalProgress = $features->sum('progress_percent');
        $this->progress_percent = (int) round($totalProgress / $features->count());
        $this->save();
    }

    public function markAsInProgress(): void
    {
        $this->update([
            'status' => 'in_progress',
            'start_date' => $this->start_date ?? now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'progress_percent' => 100,
            'actual_end_date' => now(),
        ]);
    }

    public function isOverdue(): bool
    {
        return $this->expected_end_date
            && $this->expected_end_date->isPast()
            && ! in_array($this->status, ['completed', 'cancelled']);
    }
}
