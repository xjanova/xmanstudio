<?php

namespace App\Http\Controllers;

use App\Models\DownloadLog;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Services\GithubReleaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WinXTools (Windows) — หน้าโหลดสาธารณะ
 *
 * ไฟล์เดียวใช้ทั้งรุ่น Free และ Pro (Pro ปลดล็อกในแอปด้วย license key) จึงไม่ต้องล็อกอิน
 * และตัวแอปก็อัปเดตตัวเองผ่าน URL นี้ (ได้มาจาก /api/v1/product/winx-tools/update/check)
 *
 * ⚠️ ผูกกับ winx-tools ตายตัว ห้ามทำเป็น /{slug}/download — จะกลายเป็นประตูหลังให้โหลด
 *    สินค้าตัวอื่นที่ต้องซื้อก่อนได้ฟรี
 * ⚠️ ส่งลูกค้าไปโหลดจาก GitHub ด้วย redirect ไม่ stream ผ่าน PHP แบบ /smschecker/download/apk
 *    ไฟล์ ~70 MB จะกิน PHP worker ไว้ตลอดเวลาที่ลูกค้าโหลด
 */
class WinXToolsController extends Controller
{
    private const PRODUCT_SLUG = 'winx-tools';

    public function download(Request $request, GithubReleaseService $github, ?string $version = null)
    {
        $product = Product::where('slug', self::PRODUCT_SLUG)
            ->where('is_active', true)
            ->first();

        if (! $product) {
            // หน้าสินค้าก็ 404 อยู่แล้ว ไม่มีที่ให้ส่งเบราว์เซอร์กลับไป
            abort_if($this->isBrowser($request), 404);

            return response()->json(['success' => false, 'error' => 'Product not found'], 404);
        }

        // ระบุเวอร์ชัน = ตัวนั้นเป๊ะ ๆ (update/check ส่ง sha256 ของเวอร์ชันนี้ให้แอปไปแล้ว ต่อให้ระหว่างนั้น
        // มีตัวใหม่ออกมา ไฟล์ก็ต้องตรงกับ hash) · ไม่ระบุ = ตัวล่าสุด ตัวเดียวกับที่ update/check โฆษณา
        $productVersion = $version !== null
            ? ProductVersion::where('product_id', $product->id)->where('version', $version)->first()
            : $this->latestVersion($github, $product);

        if (! $productVersion) {
            return $this->unavailable($request, 404, 'Version not found', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        $setting = $product->githubSetting;
        $fileUrl = null;

        // 1) ไม่มี token (repo public) + มีลิงก์ตรง → ส่งไปเลย ไม่ต้องถาม API ไม่เปลืองโควตา
        if (empty($setting?->github_token_decrypted) && $productVersion->download_url) {
            $fileUrl = $productVersion->download_url;
        }

        // 2) ขอลิงก์ชั่วคราวที่ GitHub เซ็นให้ — ใช้ได้กับ repo private (มี token) และเวอร์ชันที่ sync
        //    ไว้ก่อนมีคอลัมน์ download_url · ในลิงก์ไม่มี token ของเรา ส่งต่อให้ลูกค้าได้
        if (! $fileUrl && $setting) {
            try {
                $fileUrl = $github->signedDownloadUrl($setting, $productVersion);
            } catch (\Throwable $e) {
                Log::warning('winx-tools download: ขอลิงก์จาก GitHub ไม่สำเร็จ', [
                    'version' => $productVersion->version,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 3) GitHub ไม่ให้ลิงก์ → ถอยไปใช้ลิงก์ตรง
        $fileUrl = $fileUrl ?: $productVersion->download_url;

        if (! $fileUrl) {
            // ไฟล์อยู่บน GitHub แต่ขอลิงก์ไม่ได้ = GitHub สะดุด ลองใหม่ได้ · ไม่มีไฟล์เลย = 404
            return $setting && $github->releaseAssetId($productVersion)
                ? $this->unavailable($request, 502, 'Could not get the file from GitHub, please try again later', 'ดาวน์โหลดไม่สำเร็จชั่วคราว กรุณาลองใหม่อีกครั้ง')
                : $this->unavailable($request, 404, 'No file available for this version', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        $this->logDownload($request, $productVersion);

        return redirect()->away($fileUrl);
    }

    /**
     * ตัวล่าสุดที่ตามทัน GitHub (read-through) — ต่อ GitHub ไม่ติด (timeout/DNS) HTTP client โยน
     * ConnectionException ออกมา ปุ่ม "เริ่มใช้ฟรี" ต้องไม่กลายเป็นหน้า 500 จึงถอยไปใช้ตัวที่ DB รู้
     */
    private function latestVersion(GithubReleaseService $github, Product $product): ?ProductVersion
    {
        try {
            return $github->latestVersionFresh($product);
        } catch (\Throwable $e) {
            Log::warning('winx-tools download: ถาม GitHub ไม่สำเร็จ ใช้เวอร์ชันใน DB แทน', ['error' => $e->getMessage()]);

            return $product->latestVersion();
        }
    }

    /**
     * เบราว์เซอร์ขอ text/html มาเสมอเวลาเปิดลิงก์ ส่วนแอป (HttpClient) ไม่ส่ง Accept หรือส่ง * / *
     * — แอปต้องได้ JSON ที่อ่านออก ไม่ใช่หน้าเว็บ
     */
    private function isBrowser(Request $request): bool
    {
        return ! $request->wantsJson()
            && str_contains((string) $request->header('Accept'), 'text/html');
    }

    private function unavailable(Request $request, int $status, string $error, string $message)
    {
        if (! $this->isBrowser($request)) {
            return response()->json(['success' => false, 'error' => $error], $status);
        }

        return redirect()->route('products.show', self::PRODUCT_SLUG)->with('error', $message);
    }

    /**
     * บันทึกไว้ให้เห็นในหน้า admin → เวอร์ชัน → ประวัติดาวน์โหลด
     * บันทึกไม่ได้ต้องไม่ทำให้ลูกค้าโหลดไม่ได้
     */
    private function logDownload(Request $request, ProductVersion $version): void
    {
        try {
            DownloadLog::create([
                'user_id' => $request->user()?->id,
                'product_version_id' => $version->id,
                'ip_address' => $request->ip(),
                // คอลัมน์ยาว 255 — MySQL strict ไม่ตัดให้เอง แต่ error ทั้งแถว
                'user_agent' => $request->userAgent() ? mb_substr($request->userAgent(), 0, 255) : null,
                'downloaded_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('winx-tools download: บันทึก download log ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }
}
