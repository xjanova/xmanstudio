<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_key_id',
        'product_version_id',
        'ip_address',
        'user_agent',
        'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function licenseKey(): BelongsTo
    {
        return $this->belongsTo(LicenseKey::class);
    }

    public function productVersion(): BelongsTo
    {
        return $this->belongsTo(ProductVersion::class);
    }

    /**
     * Get the product through the product version
     */
    public function getProductAttribute()
    {
        return $this->productVersion?->product;
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('downloaded_at', 'desc');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->whereHas('productVersion', function ($q) use ($productId) {
            $q->where('product_id', $productId);
        });
    }
}
