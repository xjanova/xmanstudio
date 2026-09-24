<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Support\LicensePlans;
use Illuminate\Http\Request;

/**
 * CluadeX Web Controller
 *
 * Handles landing page, pricing, and download for CluadeX product.
 * Pattern follows LocalVpnWebController.
 */
class CluadeXWebController extends Controller
{
    use ServesReleaseDownloads;

    private const PRODUCT_SLUG = 'cluadex-ai-coding-assistant';

    /**
     * The plans the store sells, as the product page (/products/cluadex-ai-coding-assistant)
     * describes them. What each one costs is in config/licenses.php; pricedPlans()
     * puts the two together. There is no monthly plan: the store sells a year or a lifetime.
     */
    private const PLANS = [
        'yearly' => [
            'name' => 'Yearly',
            'name_th' => 'รายปี',
            'duration_days' => 365,
            'license_type' => 'yearly',
            'features' => [
                'ฟีเจอร์ทั้งหมด',
                '5 AI Providers',
                '28 Agent Tools',
                'ซัพพอร์ตพรีเมียม',
                'อัปเดตฟรี 1 ปี',
            ],
        ],
        'lifetime' => [
            'name' => 'Lifetime',
            'name_th' => 'ตลอดชีพ',
            'duration_days' => null,
            'license_type' => 'lifetime',
            'features' => [
                'ฟีเจอร์ทั้งหมด',
                '5 AI Providers',
                '28 Agent Tools',
                'ซัพพอร์ตพรีเมียม',
                'อัปเดตฟรีตลอดชีพ',
            ],
        ],
    ];

    /** PLANS with each price from config/licenses.php — only the terms the store sells. */
    private static function pricedPlans(): array
    {
        return LicensePlans::priced(self::PRODUCT_SLUG, self::PLANS);
    }

    /**
     * Show CluadeX landing page
     */
    public function detail()
    {
        $product = Product::where('slug', 'like', '%cluadex%')->first();
        $version = null;
        $hasPurchased = false;

        if ($product) {
            $version = ProductVersion::where('product_id', $product->id)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();

            if (auth()->check()) {
                $hasPurchased = $product->orderItems()
                    ->whereHas('order', fn ($q) => $q->where('user_id', auth()->id())->where('status', 'completed'))
                    ->exists();
            }
        }

        return view('cluadex.detail', [
            'pricing' => self::pricedPlans(),
            'product' => $product,
            'version' => $version,
            'hasPurchased' => $hasPurchased,
        ]);
    }

    /**
     * Show pricing page
     */
    public function pricing(Request $request)
    {
        $machineId = $request->query('machine_id') ?? session('cluadex_machine_id');

        if ($machineId) {
            session(['cluadex_machine_id' => $machineId]);
        }

        return view('cluadex.pricing', [
            'pricing' => self::pricedPlans(),
            'machineId' => $machineId,
            // the cart sells the licence — each plan's button posts its term there,
            // exactly as the product page does
            'product' => Product::where('slug', self::PRODUCT_SLUG)->where('is_active', true)->first(),
        ]);
    }

    /**
     * ดาวน์โหลดฟรี — ไฟล์ของเวอร์ชันล่าสุดส่งจาก xman4289.com เอง
     *
     * เดิม redirect ไปหน้า releases บน GitHub = ลูกค้าเห็น repo · กฎเจ้าของ (2026-09-24) ห้ามเด็ดขาด
     * ไฟล์ไหนเป็น "ตัวดาวน์โหลด" กำหนดที่ asset_pattern ของ GitHub setting ในหน้า admin
     * (ตัวเดียวกับที่ update/check ประกาศ) ไม่ใช่ในโค้ด
     */
    public function downloadPage(Request $request)
    {
        $product = Product::where('slug', self::PRODUCT_SLUG)->where('is_active', true)->first();

        if (! $product) {
            return $this->downloadUnavailable($request, route('cluadex.detail'), 404, 'Product not found', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        return $this->serveRelease($request, $product, $this->latestRelease($product), route('cluadex.detail'));
    }
}
