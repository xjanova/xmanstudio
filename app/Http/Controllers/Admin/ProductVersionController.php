<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DownloadLog;
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
            // 2026-08-31: เดิมเป็น required → **ลบ token ที่หมดอายุทิ้งไม่ได้เลย**
            // cluadex เก็บ PAT ที่ถูก revoke ไว้ GitHub ตอบ 401 ทุกครั้งทั้งที่ repo เป็น public
            // เจ้าของแก้เองผ่านหน้า admin ไม่ได้ ต้องไปแก้ที่ฐานข้อมูล ⇒ เปิดให้เว้นว่างได้
            'github_token' => 'nullable|string',
            'asset_pattern' => 'required|string|max:100',
        ]);

        // ⚠️ ห้ามใส่ default true — checkbox ที่ไม่ติ๊กจะ "ไม่ถูกส่งมา" เลย
        //    `boolean('is_active', true)` จึงอ่านได้ true เสมอ = ปิด "เปิดใช้งาน" ไม่ได้จริง
        //    (auto_sync ด้านล่างเขียนถูกอยู่แล้ว ไม่มี default)
        $validated['is_active'] = $request->boolean('is_active');
        $validated['auto_sync'] = $request->boolean('auto_sync');

        $githubSetting = $product->githubSetting;

        if ($githubSetting) {
            $submitted = $request->input('github_token');

            // '********' = ค่าที่ฟอร์มใส่มาแทนของเดิม แปลว่า "ไม่แตะ token"
            // มีค่าอื่น = เปลี่ยนเป็นค่าใหม่ · เว้นว่าง = ตั้งใจลบทิ้ง
            if ($submitted !== '********') {
                $githubSetting->github_token = $submitted;
            }
            unset($validated['github_token']);
            $githubSetting->update($validated);
        } else {
            // คอลัมน์เป็น NOT NULL — ตั้งค่าใหม่โดยไม่กรอก token ต้องได้สตริงว่าง ไม่ใช่ค่าหาย
            $validated['github_token'] ??= '';
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

        if (! $githubSetting) {
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

            $totalVersions = $product->versions()->count();
            $maxKeep = GithubReleaseService::MAX_VERSIONS_KEEP;

            $msg = "Sync สำเร็จ! เวอร์ชันล่าสุด: {$version->version} (เก็บ {$totalVersions} เวอร์ชัน, สูงสุด {$maxKeep})";

            return redirect()
                ->route('admin.products.versions.index', $product)
                ->with('success', $msg);
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
        $version->update(['is_active' => ! $version->is_active]);

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
