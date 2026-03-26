<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * LocalVPN Web Controller
 *
 * Handles web pages for LocalVPN product (pricing, checkout, download).
 * Follows the same pattern as TpingController.
 */
class LocalVpnWebController extends Controller
{
    private const WALLET_DISCOUNT_PERCENT = 10;

    private const PRICING = [
        'monthly' => [
            'name' => 'Monthly',
            'name_th' => 'รายเดือน',
            'price' => 399,
            'duration_days' => 30,
            'license_type' => 'monthly',
            'features' => [
                'สร้างวง LAN เสมือนไม่จำกัด',
                'เชื่อมต่ออุปกรณ์สูงสุด 10 เครื่อง',
                'เข้ารหัส WireGuard',
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
                'สร้างวง LAN เสมือนไม่จำกัด',
                'เชื่อมต่ออุปกรณ์สูงสุด 25 เครื่อง',
                'เข้ารหัส WireGuard',
                'ซัพพอร์ตพรีเมียม',
                'อัพเดทก่อนใคร',
            ],
        ],
        'lifetime' => [
            'name' => 'Lifetime',
            'name_th' => 'ตลอดชีพ',
            'price' => 5000,
            'duration_days' => null,
            'license_type' => 'lifetime',
            'features' => [
                'สร้างวง LAN เสมือนไม่จำกัด',
                'เชื่อมต่ออุปกรณ์ไม่จำกัด',
                'เข้ารหัส WireGuard',
                'ซัพพอร์ตพรีเมียม',
                'อัพเดทตลอดชีพ',
                'ใช้ได้หลายเครื่อง',
            ],
        ],
    ];

    /**
     * Show LocalVPN detail / landing page
     */
    public function detail()
    {
        $product = Product::where('slug', 'localvpn')->first();
        $version = null;

        if ($product) {
            $version = ProductVersion::where('product_id', $product->id)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();
        }

        return view('localvpn.detail', [
            'pricing' => self::PRICING,
            'product' => $product,
            'version' => $version,
        ]);
    }

    /**
     * Show pricing page
     */
    public function pricing(Request $request)
    {
        $machineId = $request->query('machine_id') ?? session('localvpn_machine_id');

        if ($machineId) {
            session(['localvpn_machine_id' => $machineId]);
        }

        return view('localvpn.pricing', [
            'pricing' => self::PRICING,
            'machineId' => $machineId,
            'walletDiscount' => self::WALLET_DISCOUNT_PERCENT,
        ]);
    }

    /**
     * Redirect to pricing or checkout based on plan
     */
    public function buyRedirect(Request $request)
    {
        $plan = $request->query('plan');
        $machineId = $request->query('machine_id');

        if ($machineId) {
            session(['localvpn_machine_id' => $machineId]);
        }

        if ($plan && in_array($plan, ['monthly', 'yearly', 'lifetime'])) {
            $url = route('localvpn.checkout', $plan);
            if ($machineId) {
                $url .= '?machine_id=' . $machineId;
            }

            return redirect($url);
        }

        $url = route('localvpn.pricing');
        if ($machineId) {
            $url .= '?machine_id=' . $machineId;
        }

        return redirect($url);
    }

    /**
     * Show download page
     */
    public function downloadPage()
    {
        $product = Product::where('slug', 'localvpn')->first();
        $version = null;

        if ($product) {
            $version = ProductVersion::where('product_id', $product->id)
                ->where('is_active', true)
                ->orderByDesc('version')
                ->first();
        }

        return view('localvpn.download', [
            'product' => $product,
            'version' => $version,
        ]);
    }

    /**
     * Download APK (proxy from GitHub release)
     */
    public function downloadApk()
    {
        $product = Product::where('slug', 'localvpn')->firstOrFail();
        $version = ProductVersion::where('product_id', $product->id)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();

        if (! $version || ! $version->github_release_url) {
            return redirect()->route('localvpn.download')
                ->with('error', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        $githubSetting = $product->githubSetting;

        if (! $githubSetting) {
            return redirect()->route('localvpn.download')
                ->with('error', 'ระบบดาวน์โหลดยังไม่พร้อม');
        }

        return $this->proxyGithubDownload($githubSetting, $version);
    }

    /**
     * Proxy download from GitHub
     */
    protected function proxyGithubDownload($githubSetting, ProductVersion $productVersion): StreamedResponse
    {
        $token = $githubSetting->github_token_decrypted;
        $assetUrl = $productVersion->github_release_url;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/octet-stream',
            'User-Agent' => 'XMAN-LocalVPN-Download-Proxy',
        ])->withOptions([
            'allow_redirects' => false,
        ])->get($assetUrl);

        if ($response->status() === 302) {
            $downloadUrl = $response->header('Location');
        } else {
            $downloadUrl = $assetUrl;
        }

        $filename = $productVersion->download_filename ?? 'LocalVPN-v' . $productVersion->version . '.apk';
        $fileSize = $productVersion->file_size;

        return new StreamedResponse(function () use ($downloadUrl, $token) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $downloadUrl);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) {
                echo $data;
                flush();

                return strlen($data);
            });

            if (strpos($downloadUrl, 'github.com') !== false) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/octet-stream',
                    'User-Agent: XMAN-LocalVPN-Download-Proxy',
                ]);
            }

            curl_exec($ch);
            curl_close($ch);
        }, 200, [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => $fileSize,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Checkout page
     */
    public function checkout(string $plan, Request $request)
    {
        if (! isset(self::PRICING[$plan])) {
            return redirect()->route('localvpn.pricing');
        }

        $planData = self::PRICING[$plan];
        $machineId = $request->query('machine_id') ?? session('localvpn_machine_id');

        if ($machineId) {
            session(['localvpn_machine_id' => $machineId]);
        }

        $walletBalance = 0;
        $walletDiscount = self::WALLET_DISCOUNT_PERCENT;

        if (auth()->check()) {
            $wallet = Wallet::where('user_id', auth()->id())->first();
            $walletBalance = $wallet ? $wallet->balance : 0;
        }

        return view('localvpn.checkout', [
            'plan' => $plan,
            'planData' => $planData,
            'machineId' => $machineId,
            'walletBalance' => $walletBalance,
            'walletDiscount' => $walletDiscount,
        ]);
    }
}
