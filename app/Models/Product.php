<?php

namespace App\Models;

use App\Support\CloudStorage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /**
     * สินค้าที่ขายแบบจ่ายครั้งเดียว ใช้ได้ตลอด (นอกจากแพ็กอวาตาร์) — ดู defaultLicenseType()
     * (WinXTools Pro ขายเป็นรายปี ฿199/ปี — จึงใช้ค่าตั้งต้นรายปี ไม่อยู่ในรายการนี้)
     */
    public const LIFETIME_LICENSE_SLUGS = [];

    /**
     * สินค้าที่ราคาในแถว (products.price) คือราคารายเดือน — ปุ่มซื้อที่ไม่ส่ง license_type มา
     * (เช่น POST ที่ทำขึ้นเอง) จ่ายราคานั้น จึงต้องได้คีย์รายเดือน ไม่ใช่รายปีที่เป็นค่าตั้งต้น
     * BrainX Cloud ขายรายเดือนอย่างเดียว ฿399 · Anti X, LocalVPN และ Reverse X ขายสามแบบแต่ราคาในแถวคือราคารายเดือน
     * (฿199 ทุกตัว — คีย์รายปีในราคารายเดือน = ส่วนลดที่ไม่มีใครตั้งใจให้)
     */
    public const MONTHLY_LICENSE_SLUGS = ['brainx', 'anti-x', 'localvpn', 'reverse-x'];

    /**
     * สินค้าที่ตั้งเป็น Coming Soon ห้ามโหลดได้จากทุกจุด (กฎเจ้าของ 2026-10-12): ปุ่มทุกหน้า, /download/{slug},
     * หน้าโหลดของแอปแต่ละตัว, API เวอร์ชัน/อัปเดต, ศูนย์ดาวน์โหลดของลูกค้า — ทุกที่ขึ้นป้าย Coming Soon แทน
     * ด่านสุดท้ายอยู่ที่ ReleaseDownloadStreamer::respond() ซึ่งไฟล์ทุกไฟล์ต้องผ่าน
     */
    public const COMING_SOON_DOWNLOAD_MESSAGE = 'ผลิตภัณฑ์นี้ยังเป็น Coming Soon — ยังไม่เปิดให้ดาวน์โหลด';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'short_description',
        'features',
        'price',
        // An optional fixed TPIX price the admin set; empty = THB converted at the live rate
        'price_tpix',
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
        'renew_while_coming_soon',
    ];

    protected $casts = [
        'features' => 'array',
        'images' => 'array',
        'is_custom' => 'boolean',
        'requires_license' => 'boolean',
        'is_active' => 'boolean',
        'is_coming_soon' => 'boolean',
        'coming_soon_until' => 'datetime',
        'renew_while_coming_soon' => 'boolean',
        'price' => 'decimal:2',
        'price_tpix' => 'decimal:8',
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
     * The version this site hands out now: the active one added most recently.
     *
     * Every GitHub sync and every version created in admin switches the product's other versions
     * off, so normally exactly one is active. Two are active only after an admin switches an old
     * one back on, and the one added later still wins. Never sort the version string as text:
     * "1.2.99" beats "1.2.102" that way.
     */
    public function latestVersion()
    {
        return $this->versions()->active()->latest()->first();
    }

    /**
     * Where the Download button in the customer's Download Center takes someone who owns this
     * product, or null when there is nothing to download (the card then shows no button).
     *
     * The app's own route first (config/downloads.php → app_routes), which streams the file from
     * this site. Anything else goes to the licence-checked /download/{slug} page, but only with an
     * active version to hand out: that page answers 404 without one. A product still "coming soon"
     * has nothing to download at all (owner, 2026-10-12) — the card shows the Coming Soon label.
     */
    /**
     * The free Anti X phone app as offered next to Anti X (download pages, licences): its latest version and this site's
     * link for it, a null link while it is coming soon, nothing at all before its first release or when it is switched off.
     *
     * @return array{product: Product, version: ?ProductVersion, url: ?string}|null
     */
    public static function antiXMobileOffer(): ?array
    {
        $app = static::where('slug', 'anti-x-mobile')->where('is_active', true)->first();
        if (! $app) {
            return null;
        }
        if ($app->isComingSoon()) {
            return ['product' => $app, 'version' => null, 'url' => null];
        }
        $latest = $app->latestVersion();

        return $latest ? ['product' => $app, 'version' => $latest, 'url' => route('anti-x-mobile.download', $latest->version)] : null;
    }

    public function downloadUrl(): ?string
    {
        if ($this->isComingSoon()) {
            return null;
        }

        // An app with installers for more than one platform goes to the page that offers each of them
        $route = config('downloads.app_pages', [])[$this->slug]
            ?? config('downloads.app_routes', [])[$this->slug]
            ?? null;

        // A route this site does not register must not take the whole page down with it
        if ($route !== null && Route::has($route)) {
            return route($route);
        }

        return $this->versions()->active()->exists()
            ? route('download.page', $this->slug)
            : null;
    }

    /**
     * What the download runs on ("Windows", "Android"), or null when this site doesn't know, in
     * which case the card in the customer's Download Center shows no Platform row.
     *
     * Listed per app in config/downloads.php → app_platforms.
     */
    public function downloadPlatform(): ?string
    {
        return config('downloads.app_platforms', [])[$this->slug] ?? null;
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
        if (in_array($this->licenseSlug(), self::LIFETIME_LICENSE_SLUGS, true) || $this->avatarPack()->exists()) {
            return LicenseKey::TYPE_LIFETIME;
        }

        if (in_array($this->licenseSlug(), self::MONTHLY_LICENSE_SLUGS, true)) {
            return LicenseKey::TYPE_MONTHLY;
        }

        return LicenseKey::TYPE_YEARLY;
    }

    /**
     * ซื้อซ้ำแล้วต่ออายุคีย์เดิมแทนการออกคีย์ใหม่ — รายการอยู่ที่ config/licenses.php
     */
    public function isRenewable(): bool
    {
        return in_array($this->licenseSlug(), (array) config('licenses.renewable_products', []), true);
    }

    /**
     * Shop slugs that are another name for a product the code knows by a different slug.
     *
     * Production renamed BrainX Cloud's row to `brainx-cloud`, while its prices, renewals, page
     * and the BrainX apps (purchase link, /api/v1/product/brainx/status/{key}) all say `brainx`.
     * Every lookup by the code's name found nothing: /products/brainx answered 404, the pricing
     * API sold no plan, and the cloud server's key check answered PRODUCT_NOT_FOUND.
     */
    public const SLUG_ALIASES = ['brainx-cloud' => 'brainx'];

    /** The slug the code (config/licenses.php, the license API, custom pages) knows this slug by. */
    public static function canonicalSlug(string $slug): string
    {
        return self::SLUG_ALIASES[$slug] ?? $slug;
    }

    /** Every shop slug that names the same product as $slug — itself first. */
    public static function slugsFor(string $slug): array
    {
        $canonical = self::canonicalSlug($slug);

        return array_values(array_unique([
            $slug,
            $canonical,
            ...array_keys(self::SLUG_ALIASES, $canonical, true),
        ]));
    }

    /** This product's slug as the code knows it — see SLUG_ALIASES. */
    public function licenseSlug(): string
    {
        return self::canonicalSlug((string) $this->slug);
    }

    /**
     * The row a slug names, trying its exact slug first and then the other names of the same
     * product, so a row renamed in the admin is still found by the name the code uses.
     */
    public static function bySlugOrAlias(string $slug): Builder
    {
        $slugs = self::slugsFor($slug);

        return static::query()
            ->whereIn('slug', $slugs)
            ->orderByRaw('CASE WHEN slug = ? THEN 0 ELSE 1 END', [$slug]);
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
     * BrainX's rows in the shop: the code sells it as `brainx`, while production lists it as
     * `brainx-cloud` — a check on `brainx` alone found no row and let the installer through.
     */
    public const BRAINX_SLUGS = ['brainx', 'brainx-cloud'];

    /** BrainX is Coming Soon while any of its rows is (owner, 2026-10-12: no download, free or not, until that changes). */
    public static function brainxComingSoonRow(): ?self
    {
        return static::whereIn('slug', self::BRAINX_SLUGS)->get()->first(fn (self $product) => $product->isComingSoon());
    }

    /**
     * Someone who already uses BrainX: an admin, or an account holding a key of any BrainX row (any
     * status — a lapsed key is still a user coming back). While BrainX is Coming Soon these people
     * still get the installer; everyone else waits (owner, 2026-10-19: "ปิด coming soon ก็ต้อง
     * download ได้สำหรับคนที่ใช้อยู่ก่อนแล้ว").
     */
    public static function isExistingBrainxUser(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return LicenseKey::where('user_id', $user->id)
            ->whereHas('product', fn ($q) => $q->whereIn('slug', self::BRAINX_SLUGS))
            ->exists();
    }

    /** The product with this slug exists and is still coming soon (for downloads tied to a slug, not a row). */
    public static function slugIsComingSoon(string $slug): bool
    {
        return (bool) static::where('slug', $slug)->first()?->isComingSoon();
    }

    /**
     * Check if product is available for purchase
     */
    public function isAvailable(): bool
    {
        return $this->is_active && ! $this->isComingSoon();
    }

    /**
     * Whether a customer may renew a key of this product they already hold: on sale, or Coming
     * Soon with the admin's "ต่ออายุได้ระหว่าง Coming Soon" switched on.
     */
    public function acceptsRenewals(): bool
    {
        return $this->is_active && (! $this->isComingSoon() || $this->renew_while_coming_soon);
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
        })
            // A cloud space pack is bought from the customer's own license page, for one key
            // (config/licenses.php → cloud_storage) — it means nothing on its own in a listing
            ->whereNotIn('slug', CloudStorage::addonProductSlugs());
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
     * The product's picture, everywhere it is shown: the home pages, /products, the product
     * pages, the cart, the menu, the customer area, the admin, and product.xman4289.com
     * (through /api/v1/catalog).
     *
     * The owner (2026-10-12): the picture is the real one set in the admin — a screenshot of
     * the program as it is. Until a product has one, it shows its picture from
     * product.xman4289.com (public_html/artwork/hub/<slug>.webp, copies of that site's
     * assets/art), else the older artwork set (public_html/artwork/product/<slug>.webp).
     * Returns null only when there is nothing at all.
     */
    public function getArtworkUrlAttribute(): ?string
    {
        if ($this->image) {
            return preg_match('#^https?://#i', $this->image) === 1 ? $this->image : Storage::url($this->image);
        }

        return static::defaultArtworkUrl((string) $this->slug);
    }

    /** A real picture has been set in the admin (artwork_url is not just the default art). */
    public function hasRealImage(): bool
    {
        return (bool) $this->image;
    }

    /**
     * The picture a product shows while it has no real one — see getArtworkUrlAttribute().
     * The per-request static cache keeps this to one scan of each folder even when a listing
     * renders dozens of products.
     */
    public static function defaultArtworkUrl(string $slug): ?string
    {
        static $sets = null;

        if ($sets === null) {
            $sets = [];
            foreach (['artwork/hub', 'artwork/product'] as $folder) {
                $files = glob(public_path($folder) . '/*.webp') ?: [];
                $sets[$folder] = array_flip(array_map(fn ($f) => pathinfo($f, PATHINFO_FILENAME), $files));
            }
        }

        foreach ($sets as $folder => $slugs) {
            if (isset($slugs[$slug])) {
                return asset($folder . '/' . $slug . '.webp');
            }
        }

        return null;
    }
}
