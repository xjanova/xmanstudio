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
use Illuminate\Support\Facades\Cache;

/**
 * Searches the website's internal content (products, services, etc.)
 * and returns formatted knowledge for the AI chatbot.
 *
 * Respects admin toggle settings:
 * - ai_use_product_data
 * - ai_use_service_data
 */
class WebsiteKnowledgeService
{
    /**
     * Search website content by user query and return formatted context.
     * Only searches models that are enabled via admin toggles.
     */
    public function search(string $query): string
    {
        $keywords = $this->extractKeywords($query);

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

        return "=== ข้อมูลจากเว็บไซต์ที่เกี่ยวข้องกับคำถาม (ใช้ข้อมูลนี้ในการตอบ) ===\n" . $combined;
    }

    /**
     * Build a full knowledge snapshot of active website content.
     * Only includes data that is enabled via admin toggles.
     * Cached for 10 minutes to avoid repeated queries.
     */
    public function buildFullKnowledge(): string
    {
        $useProducts = Setting::getValue('ai_use_product_data', true);
        $useServices = Setting::getValue('ai_use_service_data', true);

        // If nothing is enabled, return empty
        if (! $useProducts && ! $useServices) {
            return '';
        }

        $cacheKey = 'chatbot_full_knowledge_' . ($useProducts ? '1' : '0') . '_' . ($useServices ? '1' : '0');

        return Cache::remember($cacheKey, 600, function () use ($useProducts, $useServices) {
            $parts = [];

            if ($useServices) {
                $parts[] = $this->getAllServices();
                $parts[] = $this->getAllQuotationCategories();
            }

            if ($useProducts) {
                $parts[] = $this->getAllProducts();
                $parts[] = $this->getAllRentalPackages();
            }

            $combined = implode("\n", array_filter($parts));

            return empty($combined) ? '' : "=== ข้อมูลบริการและสินค้าของเว็บไซต์ ===\n" . $combined;
        });
    }

    /**
     * Extract meaningful Thai/English keywords from user query.
     */
    protected function extractKeywords(string $query): array
    {
        $stopWords = [
            'ไหม', 'มั้ย', 'ครับ', 'ค่ะ', 'คะ', 'นะ', 'จ้า', 'จ๊ะ', 'หน่อย',
            'ได้', 'ไหม', 'บ้าง', 'อะไร', 'ยังไง', 'อย่างไร', 'เท่าไหร่', 'กี่',
            'มี', 'เป็น', 'คือ', 'ที่', 'ของ', 'ให้', 'กับ', 'และ', 'หรือ',
            'จะ', 'ก็', 'แล้ว', 'ดี', 'สิ', 'เลย', 'ด้วย', 'กัน', 'ไป', 'มา',
            'ถาม', 'อยาก', 'ต้องการ', 'the', 'is', 'a', 'an', 'and', 'or',
            'what', 'how', 'do', 'you', 'have', 'can', 'about', 'this',
        ];

        $words = preg_split('/[\s,.\/?!;:()]+/u', mb_strtolower($query));
        $words = array_filter($words, function ($w) use ($stopWords) {
            return mb_strlen($w) >= 2 && ! in_array($w, $stopWords);
        });

        return array_values(array_unique($words));
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
            ['name', 'name_th', 'description', 'description_th'],
            $keywords,
            fn ($q) => $q->where('is_active', true)
        );

        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[บริการ]'];
        foreach ($items as $item) {
            $name = $item->name_th ?: $item->name;
            $desc = $item->description_th ?: $item->description;
            $price = $item->starting_price ? 'เริ่มต้น ' . number_format($item->starting_price) . ' บาท' : '';
            $features = is_array($item->features_th) ? implode(', ', $item->features_th) : (is_array($item->features) ? implode(', ', $item->features) : '');
            $lines[] = "- {$name}: {$desc}" . ($price ? " ({$price})" : '') . ($features ? " | ฟีเจอร์: {$features}" : '');
        }

        return implode("\n", $lines);
    }

    protected function searchProducts(array $keywords): string
    {
        $items = $this->searchModels(
            Product::class,
            ['name', 'slug', 'description', 'short_description'],
            $keywords,
            fn ($q) => $q->where('is_active', true)
        );

        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[สินค้า/ซอฟต์แวร์]'];
        foreach ($items as $item) {
            $price = $item->price ? number_format($item->price) . ' บาท' : 'สอบถามราคา';
            $features = is_array($item->features) ? implode(', ', array_slice($item->features, 0, 5)) : '';
            $lines[] = "- {$item->name}: " . ($item->short_description ?: $item->description) . " (ราคา: {$price})" . ($features ? " | ฟีเจอร์: {$features}" : '');
        }

        return implode("\n", $lines);
    }

    protected function searchRentalPackages(array $keywords): string
    {
        $items = $this->searchModels(
            RentalPackage::class,
            ['name', 'name_th', 'description', 'description_th'],
            $keywords,
            fn ($q) => $q->where('is_active', true)
        );

        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[แพ็กเกจเช่าใช้บริการ]'];
        foreach ($items as $item) {
            $name = $item->name_th ?: $item->name;
            $desc = $item->description_th ?: $item->description;
            $price = number_format($item->price) . ' บาท';
            $features = is_array($item->features) ? implode(', ', array_slice($item->features, 0, 5)) : '';
            $lines[] = "- {$name}: {$desc} (ราคา: {$price})" . ($features ? " | รวม: {$features}" : '');
        }

        return implode("\n", $lines);
    }

    protected function searchQuotationOptions(array $keywords): string
    {
        $items = $this->searchModels(
            QuotationOption::class,
            ['name', 'name_th', 'description', 'description_th', 'long_description', 'long_description_th'],
            $keywords,
            fn ($q) => $q->where('is_active', true)->with('category')
        );

        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[บริการและตัวเลือกงาน]'];
        foreach ($items as $item) {
            $name = $item->name_th ?: $item->name;
            $desc = $item->description_th ?: $item->description;
            $category = $item->category ? ($item->category->name_th ?: $item->category->name) : '';
            $price = $item->price ? number_format($item->price) . ' บาท' : '';
            $features = is_array($item->features_th) ? implode(', ', array_slice($item->features_th, 0, 5)) : (is_array($item->features) ? implode(', ', array_slice($item->features, 0, 5)) : '');
            $lines[] = "- {$name}" . ($category ? " (หมวด: {$category})" : '') . ": {$desc}" . ($price ? " (ราคา: {$price})" : '') . ($features ? " | ฟีเจอร์: {$features}" : '');
        }

        return implode("\n", $lines);
    }

    protected function searchCoupons(array $keywords): string
    {
        $items = $this->searchModels(
            Coupon::class,
            ['code', 'name', 'description'],
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
            ['title', 'description'],
            $keywords,
            fn ($q) => $q->where('enabled', true)
        );

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
        $items = Service::where('is_active', true)->orderBy('order')->get();
        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[บริการทั้งหมด]'];
        foreach ($items as $item) {
            $name = $item->name_th ?: $item->name;
            $desc = $item->description_th ?: $item->description;
            $price = $item->starting_price ? 'เริ่มต้น ' . number_format($item->starting_price) . ' บาท' : '';
            $lines[] = "- {$name}: {$desc}" . ($price ? " ({$price})" : '');
        }

        return implode("\n", $lines);
    }

    protected function getAllProducts(): string
    {
        $items = Product::where('is_active', true)->get();
        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[สินค้า/ซอฟต์แวร์ทั้งหมด]'];
        foreach ($items as $item) {
            $price = $item->price ? number_format($item->price) . ' บาท' : 'สอบถามราคา';
            $lines[] = "- {$item->name}: " . ($item->short_description ?: $item->description) . " (ราคา: {$price})";
        }

        return implode("\n", $lines);
    }

    protected function getAllRentalPackages(): string
    {
        $items = RentalPackage::where('is_active', true)->orderBy('sort_order')->get();
        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[แพ็กเกจเช่าใช้บริการทั้งหมด]'];
        foreach ($items as $item) {
            $name = $item->name_th ?: $item->name;
            $price = number_format($item->price) . ' บาท';
            $features = is_array($item->features) ? implode(', ', array_slice($item->features, 0, 5)) : '';
            $lines[] = "- {$name}: {$price}" . ($features ? " | รวม: {$features}" : '');
        }

        return implode("\n", $lines);
    }

    protected function getAllQuotationCategories(): string
    {
        $items = QuotationCategory::where('is_active', true)->with(['options' => fn ($q) => $q->where('is_active', true)])->get();
        if ($items->isEmpty()) {
            return '';
        }

        $lines = ['[หมวดบริการและตัวเลือกงาน]'];
        foreach ($items as $cat) {
            $catName = $cat->name_th ?: $cat->name;
            $lines[] = "หมวด: {$catName}";
            foreach ($cat->options as $opt) {
                $optName = $opt->name_th ?: $opt->name;
                $optDesc = $opt->description_th ?: $opt->description;
                $price = $opt->price ? number_format($opt->price) . ' บาท' : '';
                $lines[] = "  - {$optName}: {$optDesc}" . ($price ? " ({$price})" : '');
            }
        }

        return implode("\n", $lines);
    }
}
