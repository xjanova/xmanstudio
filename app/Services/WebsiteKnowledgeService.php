<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\QuotationCategory;
use App\Models\QuotationOption;
use App\Models\RentalPackage;
use App\Models\Service;
use App\Models\Setting;
use App\Services\AiChat\CatalogFacts;
use App\Services\AiChat\Keywords;
use App\Services\AiChat\KnowledgeVersion;
use App\Support\Quotation\Pricing;
use Illuminate\Support\Facades\Cache;

/**
 * What the site sells, from the database, for the AI chatbot: products and
 * their licence plans, services and their promotion prices, rental packages,
 * and the announcements on the site right now.
 *
 * Respects admin toggle settings:
 * - ai_use_product_data
 * - ai_use_service_data
 *
 * The full snapshot is cached under KnowledgeVersion, which moves on every
 * save of these models (AppServiceProvider), so an admin's edit reaches the
 * next answer instead of the one ten minutes later.
 */
class WebsiteKnowledgeService
{
    /** Longest the snapshot lives even when no save moved the version (a bulk update fires no events). */
    private const TTL_SECONDS = 3600;

    /**
     * Search website content by user query and return formatted context.
     * Only searches models that are enabled via admin toggles.
     */
    public function search(string $query): string
    {
        return $this->searchKeywords(Keywords::extract($query));
    }

    /**
     * The catalogue entries a question names, in detail.
     *
     * Matched on names only (name, name_th, slug, and a product's one-line
     * summary), never long descriptions: "แพ็กเกจไหนคุ้มสุด" once matched a
     * dozen AI-music services on "สุด" in their marketing copy and the assistant
     * answered about those instead of the page the visitor had open.
     *
     * @param  array<int, string>  $keywords  already cut down to specific words (SiteIndex::specific)
     */
    public function searchKeywords(array $keywords): string
    {
        if (empty($keywords)) {
            return '';
        }

        $useProducts = Setting::getValue('ai_use_product_data', true);
        $useServices = Setting::getValue('ai_use_service_data', true);

        $results = [];

        if ($useServices) {
            $results[] = $this->searchServices($keywords);
            $results[] = $this->searchQuotationOptions($keywords);
        }

        if ($useProducts) {
            $results[] = $this->searchProducts($keywords);
            $results[] = $this->searchRentalPackages($keywords);
            $results[] = $this->searchCoupons($keywords);
            $results[] = $this->searchBanners($keywords);
        }

        $combined = implode("\n", array_filter($results));

        if (empty($combined)) {
            return '';
        }

        return "=== รายการที่ชื่อตรงกับคำในคำถาม (รายละเอียดเพิ่มเติม — ใช้เมื่อผู้ใช้ถามถึงรายการเหล่านี้ ถ้าถามถึงสิ่งบนหน้าที่เปิดอยู่ ให้ยึดหน้านั้น) ===\n" . $combined;
    }

    /**
     * Build a full knowledge snapshot of active website content.
     * Only includes data that is enabled via admin toggles.
     */
    public function buildFullKnowledge(): string
    {
        $useProducts = Setting::getValue('ai_use_product_data', true);
        $useServices = Setting::getValue('ai_use_service_data', true);

        // If nothing is enabled, return empty
        if (! $useProducts && ! $useServices) {
            return '';
        }

        $cacheKey = 'chatbot_full_knowledge:v' . KnowledgeVersion::current() . ':' . ($useProducts ? '1' : '0') . ($useServices ? '1' : '0');

        return Cache::remember($cacheKey, self::TTL_SECONDS, function () use ($useProducts, $useServices) {
            $parts = [];

            if ($useServices) {
                $parts[] = $this->getAllServices();
                $parts[] = $this->getAllQuotationCategories();
                $parts[] = $this->getAllQuotationAddons();
            }

            if ($useProducts) {
                $parts[] = $this->getAllProducts();
                $parts[] = $this->getAllRentalPackages();
                $parts[] = $this->getActiveAnnouncements();
            }

            $combined = implode("\n", array_filter($parts));

            return empty($combined) ? '' : "=== ข้อมูลสินค้าและบริการของเว็บไซต์ (ข้อมูลจริงจากระบบ ณ ตอนนี้) ===\n" . $combined;
        });
    }

    protected function searchModels($modelClass, array $columns, array $keywords, ?callable $scope = null)
    {
        $query = $modelClass::query();

        if ($scope) {
            $scope($query);
        }

        $query->where(function ($q) use ($columns, $keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere(function ($inner) use ($columns, $keyword) {
                    foreach ($columns as $col) {
                        // Keywords::extract() keeps letters, digits and . + # - only: nothing here needs escaping.
                        $inner->orWhere($col, 'LIKE', "%{$keyword}%");
                    }
                });
            }
        });

        return $query->limit(5)->get();
    }

    protected function searchServices(array $keywords): string
    {
        $items = $this->searchModels(
            Service::class,
            ['name', 'name_th', 'slug'],
            $keywords,
            fn ($q) => $q->where('is_active', true)
        );

        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[บริการ] (โปรโมชั่นลดราคาพิเศษ!)'];
        foreach ($items as $item) {
            $lines[] = CatalogFacts::service($item, detailed: true);
        }

        return implode("\n", $lines);
    }

    protected function searchProducts(array $keywords): string
    {
        $items = $this->searchModels(
            Product::class,
            ['name', 'slug', 'short_description'],
            $keywords,
            fn ($q) => $q->onWebsite()->where('is_active', true)->with('category')
        );

        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[สินค้า/ซอฟต์แวร์]'];
        foreach ($items as $item) {
            $lines[] = CatalogFacts::product($item, detailed: true);
        }

        return implode("\n", $lines);
    }

    protected function searchRentalPackages(array $keywords): string
    {
        $items = $this->searchModels(
            RentalPackage::class,
            ['name', 'name_th'],
            $keywords,
            fn ($q) => $q->where('is_active', true)
        );

        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[แพ็กเกจเช่าใช้บริการ] สมัครที่ /rental'];
        foreach ($items as $item) {
            $lines[] = CatalogFacts::rentalPackage($item);
        }

        return implode("\n", $lines);
    }

    protected function searchQuotationOptions(array $keywords): string
    {
        $items = $this->searchModels(
            QuotationOption::class,
            ['name', 'name_th', 'key'],
            $keywords,
            fn ($q) => $q->where('is_active', true)->with('category')
        );

        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[บริการและตัวเลือกงาน] (โปรโมชั่นลดราคาพิเศษ! ยกเว้นบริการเสริม)'];
        foreach ($items as $item) {
            $category = $item->category ? ' (หมวด: ' . ($item->category->name_th ?: $item->category->name) . ')' : '';
            $lines[] = CatalogFacts::quotationOption($item, detailed: true) . $category;
        }

        return implode("\n", $lines);
    }

    protected function searchCoupons(array $keywords): string
    {
        $items = $this->searchModels(
            Coupon::class,
            ['code', 'name'],
            $keywords,
            fn ($q) => $q->where('is_active', true)
        );

        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[โปรโมชั่น/คูปอง]'];
        foreach ($items as $item) {
            $discount = $item->discount_type === 'percentage'
                ? "ลด {$item->discount_value}%"
                : 'ลด ' . number_format($item->discount_value) . ' บาท';
            $lines[] = "- {$item->name}: {$discount}" . ($item->description ? " - {$item->description}" : '');
        }

        return implode("\n", $lines);
    }

    protected function searchBanners(array $keywords): string
    {
        $items = $this->searchModels(
            Banner::class,
            ['title'],
            $keywords,
            fn ($q) => $q->where('enabled', true)
        )->filter(fn (Banner $banner) => $banner->isActive());

        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[ประกาศ/โปรโมชั่น]'];
        foreach ($items as $item) {
            $lines[] = "- {$item->title}" . ($item->description ? ": {$item->description}" : '');
        }

        return implode("\n", $lines);
    }

    // === Full knowledge builders ===

    protected function getAllServices(): string
    {
        $items = Service::where('is_active', true)->ordered()->get();
        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[บริการทั้งหมด] (โปรโมชั่นลดราคาพิเศษ!) ดูทั้งหมดที่ /services'];
        foreach ($items as $item) {
            $lines[] = CatalogFacts::service($item);
        }

        return implode("\n", $lines);
    }

    protected function getAllProducts(): string
    {
        // onWebsite(): the app-only packs are sold inside the app, never listed here.
        $items = Product::onWebsite()->where('is_active', true)->orderBy('is_coming_soon')->orderBy('name')->get();
        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[สินค้า/ซอฟต์แวร์ทั้งหมด] (สินค้าที่ไม่อยู่ในรายการนี้ = ไม่ได้ขายบนเว็บตอนนี้) ดูทั้งหมดที่ /products'];
        foreach ($items as $item) {
            $lines[] = CatalogFacts::product($item);
        }

        return implode("\n", $lines);
    }

    protected function getAllRentalPackages(): string
    {
        $items = RentalPackage::where('is_active', true)->orderBy('sort_order')->get();
        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[แพ็กเกจเช่าใช้บริการทั้งหมด] สมัครที่ /rental'];
        foreach ($items as $item) {
            $lines[] = CatalogFacts::rentalPackage($item);
        }

        return implode("\n", $lines);
    }

    protected function getAllQuotationCategories(): string
    {
        // Services only — add-on groups carry no discount and are listed
        // separately by getAllQuotationAddons() at their full price.
        $items = QuotationCategory::where('is_active', true)
            ->services()
            ->ordered()
            ->with(['options' => fn ($q) => $q->where('is_active', true)->orderBy('order')])
            ->get();
        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[หมวดบริการและตัวเลือกงาน] (โปรโมชั่นลดราคาพิเศษ!) ขอใบเสนอราคาได้ที่ /quote'];
        foreach ($items as $cat) {
            $percent = (int) round(Pricing::saleDiscount($cat->key) * 100);
            $lines[] = 'หมวด: ' . ($cat->name_th ?: $cat->name) . " (ลด {$percent}%!)";
            foreach ($cat->options as $opt) {
                $lines[] = CatalogFacts::quotationOption($opt, $cat);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Optional extras (support, delivery, hosting, design, SEO).
     *
     * Quoted at full price: the 50/70% promotion applies to the main service
     * packages only, so these must never be run through that discount.
     */
    protected function getAllQuotationAddons(): string
    {
        $items = QuotationCategory::where('is_active', true)
            ->addons()
            ->ordered()
            ->with(['options' => fn ($q) => $q->where('is_active', true)->orderBy('order')])
            ->get();
        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[บริการเสริม] (ราคาเต็ม ไม่เข้าร่วมโปรโมชั่นลดราคา)'];
        foreach ($items as $cat) {
            $lines[] = 'หมวดเสริม: ' . ($cat->name_th ?: $cat->name);
            foreach ($cat->options as $opt) {
                $optName = $opt->name_th ?: $opt->name;
                $price = $opt->price ? ' (' . CatalogFacts::money($opt->price) . ')' : '';
                $lines[] = "  - {$optName}{$price}";
            }
        }

        return implode("\n", $lines);
    }

    /** The announcements and promotions the site is showing right now. */
    protected function getActiveAnnouncements(): string
    {
        $items = Banner::where('enabled', true)->orderByDesc('priority')->limit(10)->get()
            ->filter(fn (Banner $banner) => $banner->isActive() && trim((string) $banner->title . $banner->description) !== '');

        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[ประกาศ/โปรโมชั่นที่แสดงบนเว็บตอนนี้]'];
        foreach ($items as $item) {
            $lines[] = '- ' . trim((string) $item->title) . ($item->description ? ': ' . CatalogFacts::plain($item->description, 200) : '');
        }

        return implode("\n", $lines);
    }

    /** Kept for WebsiteKnowledgeFeatureListTest; the logic lives in CatalogFacts. */
    protected function featureList(mixed $features, int $limit = 5): string
    {
        return CatalogFacts::featureList($features, $limit);
    }
}
