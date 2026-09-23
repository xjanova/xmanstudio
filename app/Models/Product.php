<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /**
     * สินค้าที่ขายแบบจ่ายครั้งเดียว ใช้ได้ตลอด (นอกจากแพ็กอวาตาร์) — ดู defaultLicenseType()
     * (WinXTools Pro ขายเป็นรายปี ฿199/ปี — จึงใช้ค่าตั้งต้นรายปี ไม่อยู่ในรายการนี้)
     */
    public const LIFETIME_LICENSE_SLUGS = [];

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'features',
        'price',
        'image',
        'images',
        'sku',
        'is_custom',
        'requires_license',
        'stock',
        'low_stock_threshold',
        'is_active',
        'is_coming_soon',
        'coming_soon_until',
    ];

    protected $casts = [
        'features' => 'array',
        'images' => 'array',
        'is_custom' => 'boolean',
        'requires_license' => 'boolean',
        'is_active' => 'boolean',
        'is_coming_soon' => 'boolean',
        'coming_soon_until' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function licenseKeys(): HasMany
    {
        return $this->hasMany(LicenseKey::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProductVersion::class);
    }

    public function githubSetting(): HasOne
    {
        return $this->hasOne(GithubSetting::class);
    }

    public function avatarPack(): HasOne
    {
        return $this->hasOne(AvatarPack::class);
    }

    /**
     * Get the latest active version
     */
    public function latestVersion()
    {
        return $this->versions()->active()->latest()->first();
    }

    /**
     * ประเภท license ที่ออกให้เมื่อรายการในออเดอร์ไม่ได้ระบุ license_type มาเอง
     *
     * ค่าตั้งต้นคือรายปี แต่ของที่ซื้อขาดต้องได้ lifetime ไม่งั้นของที่ลูกค้าจ่ายไปแล้วจะหมดอายุเอง
     * ในอีกปี — แพ็กอวาตาร์จะหลุดออกจากรายการของที่มีในแอปโดยไม่มีอะไรบอกเหตุผล
     * license_type ที่ระบุมากับรายการในออเดอร์ยังชนะค่านี้เสมอ
     *
     * ที่เดียวที่ตัดสินเรื่องนี้ — LicenseService และ Api\V1\SmsPaymentController ใช้ร่วมกัน
     */
    public function defaultLicenseType(): string
    {
        if (in_array($this->slug, self::LIFETIME_LICENSE_SLUGS, true) || $this->avatarPack()->exists()) {
            return LicenseKey::TYPE_LIFETIME;
        }

        return LicenseKey::TYPE_YEARLY;
    }

    /**
     * Check if product has GitHub settings configured
     */
    public function hasGithubSettings(): bool
    {
        return $this->githubSetting()->exists();
    }

    /**
     * Check if product is currently coming soon
     */
    public function isComingSoon(): bool
    {
        if (! $this->is_coming_soon) {
            return false;
        }

        // If coming_soon_until is set, check if it's still in the future
        if ($this->coming_soon_until) {
            return $this->coming_soon_until->isFuture();
        }

        return true;
    }

    /**
     * Check if product is available for purchase
     */
    public function isAvailable(): bool
    {
        return $this->is_active && ! $this->isComingSoon();
    }

    /**
     * Scope for coming soon products
     */
    public function scopeComingSoon($query)
    {
        return $query->where('is_coming_soon', true);
    }

    /**
     * Products a website visitor may see listed.
     *
     * Excludes anything in an app-only category (see Category::APP_ONLY_SLUGS).
     * Apply this to every public LISTING - catalogue, homepage, related
     * products, category menus - not to a direct product page, which the app
     * links to when someone buys a pack.
     *
     * Written with the query builder rather than a join so it composes with
     * whatever the caller already built, and so it behaves the same on the
     * sqlite used in dev and the MySQL used in production.
     */
    public function scopeOnWebsite($query)
    {
        return $query->whereNotIn('category_id', function ($sub) {
            $sub->select('id')
                ->from('categories')
                ->whereIn('slug', Category::APP_ONLY_SLUGS);
        });
    }

    /**
     * Scope for available products (active and not coming soon)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_coming_soon', false)
                    ->orWhere(function ($q2) {
                        $q2->where('is_coming_soon', true)
                            ->whereNotNull('coming_soon_until')
                            ->where('coming_soon_until', '<=', now());
                    });
            });
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->reviews()->approved()->avg('rating');

        return $avg ? round($avg, 1) : null;
    }

    public function getApprovedReviewsCountAttribute(): int
    {
        return $this->reviews()->approved()->count();
    }

    /**
     * Image to show on product cards and hero sections.
     *
     * Falls back to the in-house artwork set (public_html/artwork/product/<slug>.webp)
     * when the product has no uploaded image, so a card never renders as the bare
     * generic placeholder icon. Returns null only when neither exists.
     *
     * The per-request static cache keeps this to one directory scan even when a
     * listing renders dozens of products.
     */
    public function getArtworkUrlAttribute(): ?string
    {
        if ($this->image) {
            return Storage::url($this->image);
        }

        static $available = null;

        if ($available === null) {
            $dir = public_path('artwork/product');
            $available = is_dir($dir)
                ? array_flip(array_map(
                    fn ($f) => pathinfo($f, PATHINFO_FILENAME),
                    glob($dir . '/*.webp') ?: []
                ))
                : [];
        }

        return isset($available[$this->slug])
            ? asset('artwork/product/' . $this->slug . '.webp')
            : null;
    }
}
