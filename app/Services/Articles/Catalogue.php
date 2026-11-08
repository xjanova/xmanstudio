<?php

namespace App\Services\Articles;

use App\Models\Product;
use App\Models\Service;
use App\Services\AiChat\CatalogFacts;
use Illuminate\Support\Facades\Route;

/**
 * What an article may sell: every product on sale, every active service, and the sales pages
 * that are not products (the DGX Spark campaign, VPS, domains, rental, quotation). Read from
 * the live database each run — the writer never invents a product, a price or a link.
 */
class Catalogue
{
    /**
     * @return array<string, array{key:string, name:string, url:string, blurb:string, facts:string}>
     */
    public function items(): array
    {
        $items = [];

        $products = Product::query()->onWebsite()->where('is_active', true)->orderBy('name')->get()
            ->filter(fn (Product $p) => $p->isAvailable());

        foreach ($products as $p) {
            $key = 'p:' . $p->slug;
            $items[$key] = [
                'key' => $key,
                'name' => $p->name,
                'url' => route('products.show', $p->slug),
                'blurb' => CatalogFacts::plain($p->short_description ?: $p->description, 90),
                'facts' => CatalogFacts::product($p),
            ];
        }

        foreach (Service::query()->where('is_active', true)->orderBy('order')->get() as $s) {
            $key = 's:' . $s->slug;
            $items[$key] = [
                'key' => $key,
                'name' => $s->name_th ?: $s->name,
                'url' => route('services.show', $s->slug),
                'blurb' => CatalogFacts::plain($s->description_th ?: $s->description, 90),
                'facts' => CatalogFacts::service($s),
            ];
        }

        $pages = [
            'campaign.dgx-spark' => ['NVIDIA DGX Spark + CluadeX + BrainX ตลอดชีพ', 'ซูเปอร์คอมพิวเตอร์ AI บนโต๊ะ รันโมเดลในเครื่องเอง โค้ดไม่ออกนอกออฟฟิศ'],
            'vps.index' => ['เช่า VPS', 'เซิร์ฟเวอร์เสมือนพร้อมใช้ จ่ายรายเดือนจากกระเป๋าเงิน'],
            'domains.index' => ['จดโดเมน', 'ค้นหาและจดชื่อโดเมนสำหรับเว็บไซต์ธุรกิจ'],
            'rental.index' => ['แพ็กเกจเช่าใช้งานซอฟต์แวร์', 'เช่าใช้งานรายเดือน ไม่ต้องลงทุนก้อนใหญ่'],
            'quote.index' => ['ขอใบเสนอราคาพัฒนาระบบ', 'บอกความต้องการ แล้วรับใบเสนอราคาทำเว็บ แอป หรือระบบเฉพาะธุรกิจ'],
        ];

        foreach ($pages as $route => [$name, $blurb]) {
            if (! Route::has($route)) {
                continue;
            }
            $key = 'page:' . $route;
            $items[$key] = ['key' => $key, 'name' => $name, 'url' => route($route), 'blurb' => $blurb, 'facts' => '- ' . $name . ': ' . $blurb . ' | หน้า: ' . route($route)];
        }

        return $items;
    }

    /** One line per item, with its key, for the prompt. */
    public function promptList(array $items): string
    {
        return collect($items)->map(fn ($i) => '[' . $i['key'] . '] ' . ltrim($i['facts'], '- '))->implode("\n");
    }
}
