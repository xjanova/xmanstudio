<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Http\Request;

/**
 * CluadeX Web Controller
 *
 * Handles landing page, pricing, and download for CluadeX product.
 * Pattern follows LocalVpnWebController.
 */
class CluadeXWebController extends Controller
{
    private const PRODUCT_SLUG = 'cluadex-ai-coding-assistant';

    private const PRICING = [
        'monthly' => [
            'name' => 'Monthly',
            'name_th' => 'รายเดือน',
            'price' => 199,
            'duration_days' => 30,
            'license_type' => 'monthly',
            'features' => [
                'OpenAI, Anthropic, Gemini providers',
                'Git/GitHub Integration (12+ commands)',
                'Plugin System (20+ plugins)',
                'Web Fetch & Context Memory',
                'Smart Code Editing & Review',
                'Auto-Update',
                'ซัพพอร์ตมาตรฐาน',
            ],
        ],
        'yearly' => [
            'name' => 'Yearly',
            'name_th' => 'รายปี',
            'price' => 899,
            'duration_days' => 365,
            'license_type' => 'yearly',
            'features' => [
                'ทุกอย่างใน Monthly +',
                'Priority Support',
                'อัพเดทก่อนใคร',
                'ประหยัด 63% (≈฿75/เดือน)',
            ],
        ],
        'lifetime' => [
            'name' => 'Lifetime',
            'name_th' => 'ตลอดชีพ',
            'price' => 4999,
            'duration_days' => null,
            'license_type' => 'lifetime',
            'features' => [
                'ทุกอย่างใน Yearly +',
                'VIP Support ตลอดชีพ',
                'Early Access ฟีเจอร์ใหม่',
                'อัพเดทตลอดชีพ',
                'ใช้ได้หลายเครื่อง',
                'จ่ายครั้งเดียว ไม่มีค่าใช้จ่ายเพิ่ม',
            ],
        ],
    ];

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
            'pricing' => self::PRICING,
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
            'pricing' => self::PRICING,
            'machineId' => $machineId,
        ]);
    }

    /**
     * Download page — redirect to GitHub releases
     */
    public function downloadPage()
    {
        return redirect('https://github.com/xjanova/cluadeX/releases/latest');
    }
}
