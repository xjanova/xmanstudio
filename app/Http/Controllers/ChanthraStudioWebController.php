<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVersion;

/**
 * Chanthra Studio Web Controller
 *
 * Landing page (detail), manual/docs, and download redirect for the
 * Chanthra Studio desktop app. License/version checks are handled by
 * the existing generic ProductLicenseController + VersionController
 * under /api/v1/product/{slug}.
 */
class ChanthraStudioWebController extends Controller
{
    private const PRODUCT_SLUG = 'chanthra-studio';

    private const GITHUB_REPO = 'https://github.com/xjanova/chanthra-studio';

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
                'Auto-update จาก GitHub Releases',
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
            'githubRepo' => self::GITHUB_REPO,
        ]);
    }

    public function manual()
    {
        return view('chanthrastudio.manual', [
            'githubRepo' => self::GITHUB_REPO,
        ]);
    }

    public function pricing()
    {
        return view('chanthrastudio.pricing', [
            'pricing' => self::PRICING,
        ]);
    }

    public function downloadPage()
    {
        return redirect(self::GITHUB_REPO . '/releases/latest');
    }
}
