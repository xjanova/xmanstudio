<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadLog;
use App\Models\GithubSetting;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Services\GithubReleaseService;
use Illuminate\Http\Request;

class ProductVersionController extends Controller
{
    protected GithubReleaseService $githubService;

    public function __construct(GithubReleaseService $githubService)
    {
        $this->githubService = $githubService;
    }

    /**
     * Display versions and GitHub settings for a product
     */
    public function index(Product $product)
    {
        $product->load(['githubSetting', 'versions' => function ($query) {
            $query->latest()->limit(20);
        }]);

        $downloadStats = DownloadLog::whereHas('productVersion', function ($q) use ($product) {
            $q->where('product_id', $product->id);
        })->count();

        return view('admin.products.versions.index', compact('product', 'downloadStats'));
    }

    /**
     * Store or update GitHub settings for a product
     */
    public function saveGithubSettings(Request $request, Product $product)
    {
        $validated = $request->validate([
            'github_owner' => 'required|string|max:100',
            'github_repo' => 'required|string|max:100',
            'github_token' => 'required|string',
            'asset_pattern' => 'required|string|max:100',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $githubSetting = $product->githubSetting;

        if ($githubSetting) {
            // Only update token if a new one is provided
            if ($request->filled('github_token') && $request->github_token !== '********') {
                $githubSetting->github_token = $validated['github_token'];
            }
            unset($validated['github_token']);
            $githubSetting->update($validated);
        } else {
            $product->githubSetting()->create($validated);
        }

        return redirect()
            ->route('admin.products.versions.index', $product)
            ->with('success', 'บันทึก GitHub Settings สำเร็จ');
    }

    /**
     * Test GitHub connection
     */
    public function testConnection(Product $product)
    {
        $githubSetting = $product->githubSetting;

        if (!$githubSetting) {
            return response()->json([
                'success' => false,
                'message' => 'GitHub settings not configured',
            ]);
        }

        $result = $this->githubService->testConnection($githubSetting);

        return response()->json($result);
    }

    /**
     * Sync latest release from GitHub
     */
    public function syncRelease(Product $product)
    {
        try {
            $version = $this->githubService->syncLatestRelease($product);

            return redirect()
                ->route('admin.products.versions.index', $product)
                ->with('success', "Sync สำเร็จ! เวอร์ชันล่าสุด: {$version->version}");
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.products.versions.index', $product)
                ->with('error', 'Sync ล้มเหลว: ' . $e->getMessage());
        }
    }

    /**
     * Toggle version active status
     */
    public function toggleVersion(Product $product, ProductVersion $version)
    {
        $version->update(['is_active' => !$version->is_active]);

        $status = $version->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return redirect()
            ->back()
            ->with('success', "{$status} เวอร์ชัน {$version->version} แล้ว");
    }

    /**
     * Delete a version
     */
    public function destroyVersion(Product $product, ProductVersion $version)
    {
        $versionName = $version->version;
        $version->delete();

        return redirect()
            ->route('admin.products.versions.index', $product)
            ->with('success', "ลบเวอร์ชัน {$versionName} แล้ว");
    }

    /**
     * View download logs
     */
    public function downloadLogs(Product $product)
    {
        $logs = DownloadLog::with(['user', 'licenseKey', 'productVersion'])
            ->whereHas('productVersion', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->recent()
            ->paginate(50);

        return view('admin.products.versions.download-logs', compact('product', 'logs'));
    }

    /**
     * Manual create version (without GitHub sync)
     */
    public function createVersion(Request $request, Product $product)
    {
        $validated = $request->validate([
            'version' => 'required|string|max:50',
            'changelog' => 'nullable|string',
            'download_url' => 'nullable|url',
            'download_filename' => 'nullable|string|max:255',
        ]);

        // Deactivate previous versions
        ProductVersion::where('product_id', $product->id)
            ->update(['is_active' => false]);

        ProductVersion::create([
            'product_id' => $product->id,
            'version' => $validated['version'],
            'changelog' => $validated['changelog'] ?? null,
            'github_release_url' => $validated['download_url'] ?? null,
            'download_filename' => $validated['download_filename'] ?? null,
            'is_active' => true,
            'synced_at' => now(),
        ]);

        return redirect()
            ->route('admin.products.versions.index', $product)
            ->with('success', "สร้างเวอร์ชัน {$validated['version']} สำเร็จ");
    }
}
