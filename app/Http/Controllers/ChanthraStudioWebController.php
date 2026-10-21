<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Support\LicensePlans;
use Illuminate\Http\Request;

/**
 * Chanthra Studio Web Controller
 *
 * Landing page (detail), manual/docs, and the free download of the
 * Chanthra Studio desktop app. License/version checks are handled by
 * the existing generic ProductLicenseController + VersionController
 * under /api/v1/product/{slug}.
 *
 * ⚠️ ห้ามลิงก์หรือ redirect ไป GitHub (กฎเจ้าของ 2026-09-24: แอปโหลดจาก xman4289.com เท่านั้น ลูกค้าต้องไม่รู้ repo)
 */
class ChanthraStudioWebController extends Controller
{
    use ServesReleaseDownloads;

    private const PRODUCT_SLUG = 'chanthra-studio';

    /**
     * The plans the pages offer: names, durations, features. What each one costs is in
     * config/licenses.php, the shop's one price table (product.xman4289.com prints it too);
     * pricedPlans() puts the two together.
     */
    private const PLANS = [
        'monthly' => [
            'name' => 'Monthly',
            'name_th' => 'รายเดือน',
            'duration_days' => 30,
            'license_type' => 'monthly',
            'features' => [
                'ครบทุกฟีเจอร์ใน Generate / Voice / Library / Queue',
                'ComfyUI WebSocket integration',
                'TTS (OpenAI / ElevenLabs)',
                'LLM script writing (4 providers)',
                'อัปเดตอัตโนมัติในแอป',
                'ซัพพอร์ตมาตรฐาน',
            ],
        ],
        'yearly' => [
            'name' => 'Yearly',
            'name_th' => 'รายปี',
            'duration_days' => 365,
            'license_type' => 'yearly',
            'features' => [
                'ทุกอย่างใน Monthly +',
                'Priority support',
                'อัพเดทก่อนใคร',
            ],
        ],
        'lifetime' => [
            'name' => 'Lifetime',
            'name_th' => 'ตลอดชีพ',
            'duration_days' => null,
            'license_type' => 'lifetime',
            'features' => [
                'ทุกอย่างใน Yearly +',
                'อัพเดทตลอดชีพ ไม่ต่ออายุ',
                'VIP support',
                'Early access ฟีเจอร์ใหม่',
            ],
        ],
    ];

    /** PLANS with each price from config/licenses.php — and the yearly saving worked out from them. */
    private static function pricedPlans(): array
    {
        $plans = LicensePlans::priced(self::PRODUCT_SLUG, self::PLANS);
        $saving = LicensePlans::yearlySaving(self::PRODUCT_SLUG);

        if ($saving !== null && isset($plans['yearly'])) {
            $plans['yearly']['features'][] = 'ประหยัด ' . $saving . '% (≈฿' . number_format(intdiv($plans['yearly']['price'], 12)) . '/เดือน)';
        }

        return $plans;
    }

    public function detail()
    {
        $product = Product::where('slug', self::PRODUCT_SLUG)->first();
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

        return view('chanthrastudio.detail', [
            'pricing' => self::pricedPlans(),
            'product' => $product,
            'version' => $version,
            'hasPurchased' => $hasPurchased,
            'comingSoon' => (bool) $product?->isComingSoon(),
        ]);
    }

    public function manual()
    {
        return view('chanthrastudio.manual', [
            'comingSoon' => Product::slugIsComingSoon(self::PRODUCT_SLUG),
        ]);
    }

    public function pricing()
    {
        return view('chanthrastudio.pricing', [
            'pricing' => self::pricedPlans(),
            'comingSoon' => Product::slugIsComingSoon(self::PRODUCT_SLUG),
        ]);
    }

    /**
     * ดาวน์โหลดฟรี — zip ส่งจาก xman4289.com เอง
     *
     * GET /chanthra-studio/download/{version?} — ไม่ระบุ = ตัวล่าสุด (ปุ่มในหน้าเว็บ/คู่มือ/ปุ่มโหลดเองในแอป)
     * · ระบุ = ตัวนั้นเป๊ะ ๆ (ลิงก์ที่ update/check ส่งให้ตัวอัปเดตในแอป คู่กับ sha256 ของเวอร์ชันนั้น)
     *
     * เดิม 302 ไปหน้า releases บน GitHub = ลูกค้าเห็น repo · กฎเจ้าของ (2026-09-24) ห้ามเด็ดขาด
     * สินค้ายังเป็น Coming Soon = โหลดไม่ได้ (กฎเจ้าของ 2026-10-12 แทนกฎเดิม "เร็ว ๆ นี้ก็โหลดได้") — serveRelease กันให้
     * ไฟล์ไหนเป็นตัวดาวน์โหลดกำหนดที่ asset_pattern ของ GitHub setting ในหน้า admin ไม่ใช่ในโค้ด
     */
    public function downloadPage(Request $request, ?string $version = null)
    {
        $product = Product::where('slug', self::PRODUCT_SLUG)->where('is_active', true)->first();

        if (! $product) {
            return $this->downloadUnavailable($request, route('chanthra-studio.detail'), 404, 'Product not found', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        return $this->serveRelease($request, $product, $this->releaseFor($product, $version), route('chanthra-studio.detail'));
    }
}
