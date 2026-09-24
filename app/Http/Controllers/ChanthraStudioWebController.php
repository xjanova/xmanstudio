<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Models\Product;
use App\Models\ProductVersion;
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

    private const PRICING = [
        'monthly' => [
            'name' => 'Monthly',
            'name_th' => 'รายเดือน',
            'price' => 399,
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
            'price' => 2500,
            'duration_days' => 365,
            'license_type' => 'yearly',
            'features' => [
                'ทุกอย่างใน Monthly +',
                'Priority support',
                'อัพเดทก่อนใคร',
                'ประหยัด ~48% (≈฿208/เดือน)',
            ],
        ],
        'lifetime' => [
            'name' => 'Lifetime',
            'name_th' => 'ตลอดชีพ',
            'price' => 7500,
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
            'pricing' => self::PRICING,
            'product' => $product,
            'version' => $version,
            'hasPurchased' => $hasPurchased,
        ]);
    }

    public function manual()
    {
        return view('chanthrastudio.manual');
    }

    public function pricing()
    {
        return view('chanthrastudio.pricing', [
            'pricing' => self::PRICING,
        ]);
    }

    /**
     * ดาวน์โหลดฟรี — zip ของเวอร์ชันล่าสุดส่งจาก xman4289.com เอง
     *
     * เดิม 302 ไปหน้า releases บน GitHub = ลูกค้าเห็น repo · กฎเจ้าของ (2026-09-24) ห้ามเด็ดขาด
     * สินค้ายังเป็น "เร็ว ๆ นี้" (ซื้อไม่ได้) ก็โหลดได้ — ตัวแอปฟรี ที่ขายคือ License key
     * ไฟล์ไหนเป็นตัวดาวน์โหลดกำหนดที่ asset_pattern ของ GitHub setting ในหน้า admin ไม่ใช่ในโค้ด
     */
    public function downloadPage(Request $request)
    {
        $product = Product::where('slug', self::PRODUCT_SLUG)->where('is_active', true)->first();

        if (! $product) {
            return $this->downloadUnavailable($request, route('chanthra-studio.detail'), 404, 'Product not found', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        return $this->serveRelease($request, $product, $this->latestRelease($product), route('chanthra-studio.detail'));
    }
}
