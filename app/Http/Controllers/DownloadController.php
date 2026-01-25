<?php

namespace App\Http\Controllers;

use App\Models\DownloadLog;
use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Services\GithubReleaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    protected GithubReleaseService $githubService;

    public function __construct(GithubReleaseService $githubService)
    {
        $this->githubService = $githubService;
    }

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

            return redirect()->route('packages.index')
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
            'user_agent' => $request->userAgent(),
            'downloaded_at' => now(),
        ]);

        // Get GitHub settings
        $githubSetting = $product->githubSetting;

        if (! $githubSetting || ! $productVersion->github_release_url) {
            // If no GitHub settings, redirect to external URL if available
            if ($productVersion->github_release_url) {
                return redirect($productVersion->github_release_url);
            }

            abort(404, 'Download not available');
        }

        // Proxy the download from GitHub
        return $this->proxyGithubDownload($githubSetting, $productVersion);
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
            return redirect()->route('packages.index')
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
            'user_agent' => $request->userAgent(),
            'downloaded_at' => now(),
        ]);

        // Get GitHub settings
        $githubSetting = $product->githubSetting;

        if (! $githubSetting || ! $productVersion->github_release_url) {
            if ($productVersion->github_release_url) {
                return response()->json([
                    'success' => true,
                    'download_url' => $productVersion->github_release_url,
                    'redirect' => true,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Download not available',
            ], 404);
        }

        // Proxy the download from GitHub
        return $this->proxyGithubDownload($githubSetting, $productVersion);
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

    /**
     * Proxy download from GitHub private repo
     */
    protected function proxyGithubDownload($githubSetting, ProductVersion $productVersion): StreamedResponse
    {
        $token = $githubSetting->github_token_decrypted;
        $assetUrl = $productVersion->github_release_url;

        // Get the actual download URL (GitHub redirects to S3)
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/octet-stream',
            'User-Agent' => 'XMAN-Studio-Download-Proxy',
        ])->withOptions([
            'allow_redirects' => false,
        ])->get($assetUrl);

        if ($response->status() === 302) {
            $downloadUrl = $response->header('Location');
        } else {
            $downloadUrl = $assetUrl;
        }

        // Stream the file
        $filename = $productVersion->download_filename ?? 'download';
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

            // For S3 URLs, we don't need auth, but for GitHub we do
            if (strpos($downloadUrl, 'github.com') !== false) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer '.$token,
                    'Accept: application/octet-stream',
                    'User-Agent: XMAN-Studio-Download-Proxy',
                ]);
            }

            curl_exec($ch);
            curl_close($ch);
        }, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => $fileSize,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
