<?php

namespace App\Services\AiChat;

use App\Models\Product;
use App\Models\QuotationCategory;
use App\Models\QuotationOption;
use App\Models\RentalPackage;
use App\Models\Service;
use App\Support\LicensePlans;
use App\Support\Quotation\Pricing;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

/**
 * One line (or a short block) of facts per thing the site sells, written the
 * way the site's own pages state them.
 *
 * Every price comes from the table the pages read: licence terms from
 * config/licenses.php (LicensePlans), the service promotion from
 * Pricing::saleDiscount(), so the assistant cannot quote a number the page
 * does not show. Used for the catalogue in every prompt (WebsiteKnowledgeService)
 * and, with $detailed, for the product or service the visitor has open
 * (CurrentPage).
 */
class CatalogFacts
{
    private const TERMS = ['monthly' => 'รายเดือน', 'yearly' => 'รายปี', 'lifetime' => 'ตลอดชีพ'];

    public static function money(float|int|string|null $amount): string
    {
        $amount = (float) $amount;

        return number_format($amount, floor($amount) == $amount ? 0 : 2) . ' บาท';
    }

    public static function product(Product $product, bool $detailed = false): string
    {
        $parts = ['- ' . $product->name];

        $about = self::plain($product->short_description ?: $product->description, $detailed ? 900 : 160);
        if ($about !== '') {
            $parts[0] .= ': ' . $about;
        }

        if ($product->isComingSoon()) {
            $parts[] = 'สถานะ: เร็วๆ นี้ — ยังไม่เปิดขาย'
                . ($product->coming_soon_until ? ' (กำหนดเปิด ' . $product->coming_soon_until->format('Y-m-d') . ')' : '')
                . ' ห้ามรับปากว่าซื้อได้แล้ว';
        } elseif (! $product->is_active) {
            $parts[] = 'สถานะ: ปิดการขายอยู่ ยังไม่รับคำสั่งซื้อใหม่';
        } else {
            $parts[] = self::productPrice($product);
        }

        $pages = self::appPages($product->slug);
        $parts[] = 'หน้าสินค้า: ' . ($pages['detail'] ?? '/products/' . $product->slug);

        if ($product->isAvailable()) {
            // How it is bought: the cart, or the app's own checkout that ties the key to a machine.
            if (LicensePlans::soldInCart($product->slug)) {
                $parts[] = 'วิธีซื้อ: กดซื้อ/ใส่ตะกร้าที่หน้าสินค้า';
            } elseif (isset($pages['pricing'])) {
                $parts[] = 'วิธีซื้อ: เลือกแพ็กเกจที่ ' . $pages['pricing'];
            }
        }

        if ($detailed) {
            if ($product->category) {
                $parts[] = 'หมวด: ' . $product->category->name;
            }

            $features = self::featureList($product->features, 10);
            if ($features !== '') {
                $parts[] = 'ฟีเจอร์: ' . $features;
            }

            $platform = $product->downloadPlatform();
            if ($platform) {
                $parts[] = 'ใช้บน: ' . $platform;
            }

            try {
                $version = $product->latestVersion();
                if ($version) {
                    $parts[] = 'เวอร์ชันล่าสุด: ' . $version->version;
                }

                $reviews = $product->approved_reviews_count;
                if ($reviews > 0) {
                    $parts[] = 'รีวิว: ' . $product->average_rating . '/5 จาก ' . $reviews . ' รีวิว';
                }
            } catch (Throwable) {
                // Versions and reviews are extras; the product line stands without them.
            }
        }

        return implode(' | ', $parts);
    }

    /** What the product costs, as its pages say it. */
    public static function productPrice(Product $product): string
    {
        $plans = LicensePlans::for($product->slug);

        if ($plans !== []) {
            $terms = [];
            foreach ($plans as $term => $price) {
                $terms[] = (self::TERMS[$term] ?? $term) . ' ' . self::money($price);
            }

            $saving = LicensePlans::yearlySaving($product->slug);

            return 'ราคา License: ' . implode(', ', $terms) . ($saving ? " (รายปีประหยัดกว่ารายเดือน {$saving}%)" : '');
        }

        return (float) $product->price > 0 ? 'ราคา: ' . self::money($product->price) : 'ราคา: ใช้ฟรี';
    }

    /**
     * An app's own pages (/tping and /tping/pricing, /autotradex/pricing …),
     * found from its download route in config/downloads.php. Empty for a
     * product that only has its /products page.
     *
     * @return array{detail?: string, pricing?: string}
     */
    public static function appPages(string $slug): array
    {
        $download = config('downloads.app_routes', [])[$slug] ?? null;
        if (! is_string($download) || ! str_contains($download, '.')) {
            return [];
        }

        $prefix = Str::before($download, '.');
        $pages = [];

        $siteMap = app(SiteMap::class);

        foreach (['detail' => ['detail', 'show', 'index'], 'pricing' => ['pricing']] as $kind => $names) {
            foreach ($names as $page) {
                $route = Route::getRoutes()->getByName("{$prefix}.{$page}");

                // A product page is for visitors: one behind a login (/gpuxmine) is not it.
                if ($route !== null && ! $siteMap->needsLogin($route)) {
                    try {
                        $pages[$kind] = '/' . ltrim((string) parse_url(route("{$prefix}.{$page}"), PHP_URL_PATH), '/');
                    } catch (Throwable) {
                        // A route that needs parameters is not a page to send anyone to.
                    }

                    break;
                }
            }
        }

        return $pages;
    }

    /** The product whose app pages use this route-name prefix ("tping" of tping.pricing), or null. */
    public static function productSlugForRoute(string $routeName): ?string
    {
        $prefix = Str::before($routeName, '.');
        if ($prefix === '' || $prefix === $routeName) {
            return null;
        }

        foreach (config('downloads.app_routes', []) as $slug => $download) {
            if (is_string($download) && Str::before($download, '.') === $prefix) {
                return (string) $slug;
            }
        }

        return null;
    }

    public static function service(Service $service, bool $detailed = false): string
    {
        $line = '- ' . ($service->name_th ?: $service->name) . ': ' . self::plain($service->description_th ?: $service->description, $detailed ? 700 : 160);

        if ((float) $service->starting_price > 0) {
            $line .= ' (' . self::salePrice((float) $service->starting_price, $service->slug, 'เริ่มต้น') . ')';
        }

        $line .= ' | หน้า: /services/' . $service->slug;

        $features = self::featureList($service->features_th, $detailed ? 10 : 5) ?: self::featureList($service->features, $detailed ? 10 : 5);
        if ($detailed && $features !== '') {
            $line .= ' | ฟีเจอร์: ' . $features;
        }

        return $line;
    }

    public static function quotationOption(QuotationOption $option, ?QuotationCategory $category = null, bool $detailed = false): string
    {
        $category ??= $option->category;
        $isAddon = $category && $category->type === QuotationCategory::TYPE_ADDON;
        $desc = self::plain($option->description_th ?: $option->description, 200);

        $line = '  - ' . ($option->name_th ?: $option->name) . ($desc !== '' ? ': ' . $desc : '');

        if ((float) $option->price > 0) {
            // Add-ons are billed at full price: only the service packages carry the promotion.
            $line .= $isAddon
                ? ' (บริการเสริม ราคา ' . self::money($option->price) . ' ไม่เข้าร่วมโปรโมชั่น)'
                : ' (' . self::salePrice((float) $option->price, $category?->key) . ')';
        }

        if (! $isAddon && $category && Route::has('service.detail')) {
            $line .= ' | /services/' . $category->key . '/' . $option->key;
        }

        if ($detailed) {
            $long = self::plain($option->long_description_th ?: $option->long_description, 700);
            if ($long !== '') {
                $line .= "\n    รายละเอียด: " . $long;
            }

            $features = self::featureList($option->features_th, 10) ?: self::featureList($option->features, 10);
            if ($features !== '') {
                $line .= "\n    ฟีเจอร์: " . $features;
            }

            if ($option->duration_days) {
                $line .= "\n    ระยะเวลาทำงานโดยประมาณ: " . $option->duration_days . ' วัน';
            }
        }

        return $line;
    }

    public static function rentalPackage(RentalPackage $package): string
    {
        $line = '- ' . ($package->name_th ?: $package->name) . ': ' . self::money($package->price);

        $duration = trim($package->duration_text);
        if ($duration !== '') {
            $line .= ' / ' . $duration;
        }

        if ((float) $package->original_price > (float) $package->price) {
            $line .= ' (ปกติ ' . self::money($package->original_price) . ')';
        }

        if ($package->has_trial && $package->trial_days) {
            $line .= ' | ทดลองใช้ฟรี ' . $package->trial_days . ' วัน';
        }

        $desc = self::plain($package->description_th ?: $package->description, 160);
        if ($desc !== '') {
            $line .= ' | ' . $desc;
        }

        $features = self::featureList($package->features);
        if ($features !== '') {
            $line .= ' | รวม: ' . $features;
        }

        return $line;
    }

    /** "ราคาปกติ 30,000 บาท → SALE ลด 50% เหลือ 15,000 บาท", the way the services pages print it. */
    public static function salePrice(float $price, ?string $key, string $prefix = ''): string
    {
        $discount = Pricing::saleDiscount($key);
        $percent = (int) round($discount * 100);

        return trim($prefix . ' ราคาปกติ ' . self::money($price) . " → SALE ลด {$percent}% เหลือ " . self::money(round($price * (1 - $discount))));
    }

    /**
     * Flatten a features array into a short comma-separated list.
     *
     * `features` is JSON and arrives in two shapes: a plain list of strings, or
     * a list of {icon, title, description} objects. imploding the second shape
     * raises "Array to string conversion", which 500s the whole chat endpoint —
     * so pull the label out of each entry instead of imploding blindly.
     */
    public static function featureList(mixed $features, int $limit = 5): string
    {
        if (! is_array($features)) {
            return '';
        }

        $labels = [];

        foreach (array_slice($features, 0, $limit) as $feature) {
            if (is_scalar($feature)) {
                $labels[] = (string) $feature;

                continue;
            }

            if (is_array($feature)) {
                $label = $feature['title'] ?? $feature['name'] ?? $feature['label'] ?? null;

                if (is_scalar($label) && (string) $label !== '') {
                    $labels[] = (string) $label;
                }
            }
        }

        return implode(', ', $labels);
    }

    /**
     * Text out of stored content, on one line, capped. The content is HTML, or
     * the page builder's JSON blocks ([{"type":"heading","content":…}, …]),
     * which reached the assistant raw — braces, icon names and colour codes.
     */
    public static function plain(?string $html, int $max): string
    {
        $html = (string) $html;
        $start = ltrim($html)[0] ?? '';

        if ($start === '[' || $start === '{') {
            $blocks = json_decode($html, true);
            if (is_array($blocks)) {
                $html = implode(' · ', self::blockText($blocks));
            }
        }

        $text = html_entity_decode(strip_tags(str_replace(['<br', '</p>', '</li>'], [' <br', ' </p>', ' </li>'], $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return PageText::clean($text, $max);
    }

    /**
     * The words of page-builder blocks: their headings, text, card titles and
     * list items — not their icons, colours or sizes.
     *
     * @return array<int, string>
     */
    private static function blockText(mixed $node, string $key = ''): array
    {
        if (is_string($node)) {
            $wanted = in_array($key, ['content', 'title', 'text', 'label', 'description', 'caption', 'question', 'answer', 'item'], true);

            return $wanted && trim($node) !== '' ? [trim($node)] : [];
        }

        if (! is_array($node)) {
            return [];
        }

        $out = [];
        foreach ($node as $childKey => $child) {
            $childKey = is_int($childKey) ? ($key === 'items' ? 'item' : $key) : (string) $childKey;
            array_push($out, ...self::blockText($child, $childKey));
        }

        return $out;
    }
}
