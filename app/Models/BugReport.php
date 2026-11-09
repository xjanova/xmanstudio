<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BugReport extends Model
{
    use HasFactory, SoftDeletes;

    /** Reports sent from a desktop app's "แจ้งปัญหา" window (POST /api/v1/product/{slug}/bug-reports). */
    public const CHANNEL_APP = 'app';

    /** The statuses an admin can set; the enum in the 2026_02_14 migration. */
    public const STATUSES = ['new', 'analyzing', 'confirmed', 'fixed', 'wont_fix', 'duplicate', 'closed'];

    /** Ticket letters: no 0/O, 1/I/L, so a ticket read out over the phone is never ambiguous. */
    private const TICKET_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'ticket',
        'channel',
        'client_report_id',
        'product_id',
        'license_key_id',
        'log_excerpt',
        'product_name',
        'product_version',
        'report_type',
        'title',
        'description',
        'metadata',
        'user_id',
        'user_email',
        'device_id',
        'status',
        'priority',
        'severity',
        'github_issue_url',
        'github_issue_number',
        'posted_to_github_at',
        'is_analyzed',
        'analysis_notes',
        'analyzed_at',
        'analyzed_by',
        'is_fixed',
        'fix_notes',
        'fixed_in_version',
        'fixed_at',
        'os_version',
        'app_version',
        'stack_trace',
        'additional_info',
    ];

    protected $casts = [
        'metadata' => 'array',
        'additional_info' => 'array',
        'is_analyzed' => 'boolean',
        'is_fixed' => 'boolean',
        'posted_to_github_at' => 'datetime',
        'analyzed_at' => 'datetime',
        'fixed_at' => 'datetime',
    ];

    protected $attributes = [
        'report_type' => 'bug',
        'status' => 'new',
        'priority' => 'medium',
        'severity' => 'moderate',
        'is_analyzed' => false,
        'is_fixed' => false,
    ];

    /**
     * Get the user who created this report
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class);
    }

    /**
     * Get the user who analyzed this report
     */
    public function analyzer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyzed_by');
    }

    /**
     * Get all comments for this report
     */
    public function comments(): HasMany
    {
        return $this->hasMany(BugReportComment::class);
    }

    /**
     * Get all attachments for this report
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(BugReportAttachment::class);
    }

    /**
     * Scope: what the old public GET /api/v1/bug-reports endpoints may show. A report sent from an
     * app's "แจ้งปัญหา" window carries the user's logs and system details: admins only.
     */
    public function scopeVisibleToPublicApi($query)
    {
        return $query->whereNull('channel');
    }

    /**
     * A fresh ticket like "BR-261109-K7Q4M" (date + 5 letters). Unique column; the caller retries
     * on the rare collision.
     */
    public static function newTicket(): string
    {
        $letters = '';
        for ($i = 0; $i < 5; $i++) {
            $letters .= self::TICKET_ALPHABET[random_int(0, strlen(self::TICKET_ALPHABET) - 1)];
        }

        return 'BR-' . now()->format('ymd') . '-' . $letters;
    }

    /** Thai label for a status, for the admin pages. */
    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'new' => 'ใหม่',
            'analyzing' => 'กำลังตรวจสอบ',
            'confirmed' => 'ยืนยันว่าเป็นปัญหา',
            'fixed' => 'แก้แล้ว',
            'wont_fix' => 'จะไม่แก้',
            'duplicate' => 'ซ้ำกับรายงานอื่น',
            'closed' => 'ปิดแล้ว',
            default => (string) $status,
        };
    }

    /**
     * Scope: Get unanalyzed reports
     */
    public function scopeUnanalyzed($query)
    {
        return $query->where('is_analyzed', false);
    }

    /**
     * Scope: Get unfixed reports
     */
    public function scopeUnfixed($query)
    {
        return $query->where('is_fixed', false);
    }

    /**
     * Scope: Filter by product
     */
    public function scopeForProduct($query, string $productName)
    {
        return $query->where('product_name', $productName);
    }

    /**
     * Scope: Filter by report type
     */
    public function scopeOfType($query, string $reportType)
    {
        return $query->where('report_type', $reportType);
    }

    /**
     * Scope: Get reports not yet posted to GitHub
     */
    public function scopeNotPostedToGitHub($query)
    {
        return $query->whereNull('github_issue_number');
    }

    /**
     * Scope: Get reports by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if this report has been posted to GitHub
     */
    public function isPostedToGitHub(): bool
    {
        return ! is_null($this->github_issue_number);
    }

    /**
     * Mark as posted to GitHub
     */
    public function markAsPostedToGitHub(int $issueNumber, string $issueUrl): void
    {
        $this->update([
            'github_issue_number' => $issueNumber,
            'github_issue_url' => $issueUrl,
            'posted_to_github_at' => now(),
        ]);
    }

    /**
     * Mark as analyzed
     */
    public function markAsAnalyzed(?int $analyzedBy = null, ?string $notes = null): void
    {
        $this->update([
            'is_analyzed' => true,
            'analyzed_at' => now(),
            'analyzed_by' => $analyzedBy,
            'analysis_notes' => $notes,
        ]);
    }

    /**
     * Mark as fixed
     */
    public function markAsFixed(string $version, ?string $notes = null): void
    {
        $this->update([
            'is_fixed' => true,
            'fixed_at' => now(),
            'fixed_in_version' => $version,
            'fix_notes' => $notes,
            'status' => 'fixed',
        ]);
    }

    /**
     * Get formatted metadata for SMS misclassification
     */
    public function getSmsMetadata(): ?array
    {
        if ($this->report_type !== 'misclassification') {
            return null;
        }

        return $this->metadata;
    }

    /**
     * Generate GitHub issue title
     */
    public function getGitHubIssueTitle(): string
    {
        return sprintf('[%s] %s', strtoupper($this->product_name), $this->title);
    }

    /**
     * Generate GitHub issue body
     */
    public function getGitHubIssueBody(): string
    {
        $body = "## Description\n\n{$this->description}\n\n";

        $body .= "## Product Information\n\n";
        $body .= "- **Product:** {$this->product_name}\n";
        $body .= '- **Version:** ' . ($this->product_version ?? 'N/A') . "\n";
        $body .= '- **App Version:** ' . ($this->app_version ?? 'N/A') . "\n";
        $body .= '- **OS:** ' . ($this->os_version ?? 'N/A') . "\n";
        $body .= "- **Report Type:** {$this->report_type}\n";
        $body .= '- **Device ID:** ' . ($this->device_id ?? 'N/A') . "\n\n";

        if ($this->report_type === 'misclassification' && $this->metadata) {
            $meta = $this->metadata;
            $body .= "## SMS Misclassification Details\n\n";
            $body .= '- **Bank:** ' . ($meta['bank'] ?? 'N/A') . "\n";
            $body .= '- **Amount:** ' . ($meta['amount'] ?? 'N/A') . " THB\n";
            $body .= '- **Detected Type:** ' . ($meta['detected_type'] ?? 'N/A') . "\n";
            $body .= '- **Correct Type:** ' . ($meta['correct_type'] ?? 'N/A') . "\n";
            $body .= '- **Issue Type:** ' . ($meta['issue_type'] ?? 'N/A') . "\n\n";

            if (! empty($meta['original_message'])) {
                $body .= "### Original SMS Message\n\n```\n{$meta['original_message']}\n```\n\n";
            }
        }

        if ($this->stack_trace) {
            $body .= "## Stack Trace\n\n```\n{$this->stack_trace}\n```\n\n";
        }

        if ($this->additional_info) {
            $body .= "## Additional Information\n\n```json\n" . json_encode($this->additional_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n```\n\n";
        }

        $body .= "---\n\n";
        $body .= "_Reported on: {$this->created_at->format('Y-m-d H:i:s')} UTC_\n";
        $body .= "_Report ID: #{$this->id}_\n";

        return $body;
    }

    /**
     * Get GitHub labels for this report
     */
    public function getGitHubLabels(): array
    {
        $labels = [
            $this->product_name,
            $this->report_type,
            "priority:{$this->priority}",
            "severity:{$this->severity}",
        ];

        if ($this->report_type === 'misclassification') {
            $labels[] = 'sms-parser';
            if (isset($this->metadata['bank'])) {
                $labels[] = "bank:{$this->metadata['bank']}";
            }
        }

        return $labels;
    }
}
