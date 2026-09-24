<?php

namespace App\Http\Controllers;

use App\Exceptions\DownloadUnavailableException;
use App\Models\DownloadLog;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Services\ReleaseDownloadStreamer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    /**
     * ไฟล์ส่งจาก xman4289.com เองเสมอ — ห้าม redirect หรือยื่นลิงก์ GitHub ให้ลูกค้า (กฎเจ้าของ 2026-09-24)
     */
    public function __construct(protected ReleaseDownloadStreamer $streamer) {}

    /**
     * Download a product (requires authentication)
     */
    public function download(Request $request, string $slug, ?string $version = null)
    {
        $product = Product::where('slug', $slug)->first();

        if (! $product) {
            abort(404, 'Product not found');
        }

        // Get the requested version or latest
        if ($version) {
            $productVersion = ProductVersion::where('product_id', $product->id)
                ->where('version', $version)
                ->first();
        } else {
            $productVersion = $product->latestVersion();
        }

        if (! $productVersion) {
            abort(404, 'Version not found');
        }

        // Check if user is authenticated
        $user = auth()->user();

        // Check if user is authenticated
        if (! $user) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Authentication required',
                ], 401);
            }

            return redirect()->route('login')
                ->with('error', 'กรุณาเข้าสู่ระบบเพื่อดาวน์โหลด');
        }

        // Check if user has purchased this product
        $hasPurchased = Order::where('user_id', $user->id)
            ->whereHas('items', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->where('status', 'completed')
            ->exists();

        if (! $hasPurchased) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Product purchase required for download',
                ], 403);
            }

            return redirect()->route('products.index')
                ->with('error', 'คุณต้องซื้อผลิตภัณฑ์นี้ก่อนจึงจะดาวน์โหลดได้');
        }

        // Check license if product requires it
        if ($product->requires_license) {
            $license = $this->validateUserLicense($request, $product, $user);

            if (! $license) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Valid license required for download',
                    ], 403);
                }

                return redirect()->route('products.show', $product->slug)
                    ->with('error', 'คุณต้องมี License ที่ใช้งานได้เพื่อดาวน์โหลด');
            }
        } else {
            $license = null;
        }

        // Log the download
        DownloadLog::create([
            'user_id' => $user?->id,
            'license_key_id' => $license?->id,
            'product_version_id' => $productVersion->id,
            'ip_address' => $request->ip(),
            // คอลัมน์ยาว 255 — MySQL strict ไม่ตัดให้เอง แต่ error ทั้งแถว
            'user_agent' => $request->userAgent() ? mb_substr($request->userAgent(), 0, 255) : null,
            'downloaded_at' => now(),
        ]);

        try {
            return $this->streamer->respond($request, $productVersion, $product->githubSetting);
        } catch (DownloadUnavailableException $e) {
            if ($request->wantsJson()) {
                return response()
                    ->json(['success' => false, 'error' => $e->getMessage()], $e->status)
                    ->withHeaders($e->retryAfter ? ['Retry-After' => (string) $e->retryAfter] : []);
            }

            abort_if($e->status === 404, 404, 'Download not available');

            // ดึงไฟล์ไม่ได้ชั่วคราว / ช่องส่งเต็ม — กลับหน้าดาวน์โหลดพร้อมบอกให้ลองใหม่ ไม่ใช่หน้า error เปล่า ๆ
            return redirect()->route('download.page', $product->slug)->with('error', $e->customerMessage);
        }
    }

    /**
     * Download page (with license check form)
     */
    public function downloadPage(Request $request, string $slug, ?string $version = null)
    {
        $product = Product::where('slug', $slug)->first();

        if (! $product) {
            abort(404, 'Product not found');
        }

        // Get the requested version or latest
        if ($version) {
            $productVersion = ProductVersion::where('product_id', $product->id)
                ->where('version', $version)
                ->first();
        } else {
            $productVersion = $product->latestVersion();
        }

        if (! $productVersion) {
            abort(404, 'Version not found');
        }

        $user = auth()->user();

        // Check if user is authenticated
        if (! $user) {
            return redirect()->route('login')
                ->with('error', 'กรุณาเข้าสู่ระบบเพื่อดาวน์โหลด');
        }

        // Check if user has purchased this product
        $hasPurchased = Order::where('user_id', $user->id)
            ->whereHas('items', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })
            ->where('status', 'completed')
            ->exists();

        if (! $hasPurchased) {
            return redirect()->route('products.index')
                ->with('error', 'คุณต้องซื้อผลิตภัณฑ์นี้ก่อนจึงจะดาวน์โหลดได้');
        }

        $hasValidLicense = false;

        if ($user && $product->requires_license) {
            $license = LicenseKey::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();

            $hasValidLicense = (bool) $license;
        }

        return view('downloads.show', compact('product', 'productVersion', 'hasValidLicense'));
    }

    /**
     * API Download endpoint (requires license key)
     */
    public function apiDownload(Request $request, string $slug, ?string $version = null)
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);

        $product = Product::where('slug', $slug)->first();

        if (! $product) {
            return response()->json([
                'success' => false,
                'error' => 'Product not found',
            ], 404);
        }

        // Get the requested version or latest
        if ($version) {
            $productVersion = ProductVersion::where('product_id', $product->id)
                ->where('version', $version)
                ->first();
        } else {
            $productVersion = $product->latestVersion();
        }

        if (! $productVersion) {
            return response()->json([
                'success' => false,
                'error' => 'Version not found',
            ], 404);
        }

        // Validate license
        $license = LicenseKey::where('license_key', $request->input('license_key'))
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $license) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or expired license key',
            ], 403);
        }

        // Log the download
        DownloadLog::create([
            'user_id' => $license->user_id,
            'license_key_id' => $license->id,
            'product_version_id' => $productVersion->id,
            'ip_address' => $request->ip(),
            // คอลัมน์ยาว 255 — MySQL strict ไม่ตัดให้เอง แต่ error ทั้งแถว
            'user_agent' => $request->userAgent() ? mb_substr($request->userAgent(), 0, 255) : null,
            'downloaded_at' => now(),
        ]);

        try {
            $response = $this->streamer->respond($request, $productVersion, $product->githubSetting);
        } catch (DownloadUnavailableException $e) {
            return response()
                ->json(['success' => false, 'error' => $e->getMessage()], $e->status)
                ->withHeaders($e->retryAfter ? ['Retry-After' => (string) $e->retryAfter] : []);
        }

        // ลิงก์ภายนอกที่ admin ใส่เอง (ไม่ใช่ GitHub — ไฟล์บน GitHub ถูก stream ไปแล้ว) ตอบเป็น JSON ตามเดิม
        if ($response instanceof RedirectResponse) {
            return response()->json([
                'success' => true,
                'download_url' => $response->getTargetUrl(),
                'redirect' => true,
            ]);
        }

        return $response;
    }

    /**
     * Validate user's license for the product
     */
    protected function validateUserLicense(Request $request, Product $product, $user): ?LicenseKey
    {
        // First check if user has a license
        if ($user) {
            $license = LicenseKey::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();

            if ($license) {
                return $license;
            }
        }

        // Check if license key is provided in request
        if ($request->filled('license_key')) {
            $license = LicenseKey::where('license_key', $request->input('license_key'))
                ->where('product_id', $product->id)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();

            return $license;
        }

        return null;
    }
}
