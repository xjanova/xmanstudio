<?php

namespace App\Http\Controllers\Concerns;

use App\Exceptions\DownloadUnavailableException;
use App\Models\DownloadLog;
use App\Models\Product;
use App\Models\ProductVersion;
use App\Services\GithubReleaseService;
use App\Services\ReleaseDownloadStreamer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ปุ่มดาวน์โหลดของแอป (ตัวฟรีบน Windows และ APK ของแอป Android) — ไฟล์ส่งจาก xman4289.com เองทุกตัว
 *
 * ห้ามพาลูกค้าไป GitHub ไม่ว่าทางลิงก์หรือ redirect (กฎเจ้าของ 2026-09-24) — ReleaseDownloadStreamer
 * ดึงไฟล์ผ่านเซิร์ฟเวอร์ให้ ที่นี่แค่เลือกเวอร์ชัน แปลงความล้มเหลวเป็นคำตอบที่อ่านออก และนับยอดโหลด
 */
trait ServesReleaseDownloads
{
    /**
     * ไฟล์ของเวอร์ชันนี้ หรือคำตอบที่บอกว่าทำไมยังส่งไม่ได้
     *
     * @param  string  $backTo  หน้าที่พาเบราว์เซอร์กลับไปพร้อมข้อความเมื่อส่งไม่ได้
     * @param  ?string  $fallbackName  ชื่อไฟล์เมื่อเวอร์ชันไม่รู้ชื่อจริง
     */
    protected function serveRelease(Request $request, Product $product, ?ProductVersion $version, string $backTo, ?string $fallbackName = null): Response
    {
        if (! $version) {
            return $this->downloadUnavailable($request, $backTo, 404, 'Version not found', 'ยังไม่มีไฟล์สำหรับดาวน์โหลด กรุณาลองใหม่ภายหลัง');
        }

        try {
            $response = app(ReleaseDownloadStreamer::class)->respond($request, $version, $product->githubSetting, $fallbackName);
        } catch (DownloadUnavailableException $e) {
            return $this->downloadUnavailable($request, $backTo, $e->status, $e->getMessage(), $e->customerMessage, $e->retryAfter);
        }

        // HEAD (curl -I, link preview) ไม่ได้โหลดไฟล์ไปจริง ไม่นับเป็นการดาวน์โหลด
        if (! $request->isMethod('HEAD')) {
            $this->logReleaseDownload($request, $product, $version);
        }

        return $response;
    }

    /**
     * เวอร์ชันที่ขอ — ระบุเวอร์ชัน = ตัวนั้นเป๊ะ ๆ แม้ไม่ใช่ตัวล่าสุดแล้ว (update/check ส่ง sha256 ของเวอร์ชันนี้ให้แอป
     * ไปแล้ว ต่อให้ระหว่างนั้นมีตัวใหม่ออกมา ไฟล์ที่แอปโหลดก็ต้องตรงกับ hash) · ไม่ระบุ = ตัวล่าสุด
     */
    protected function releaseFor(Product $product, ?string $version): ?ProductVersion
    {
        return $version !== null
            ? ProductVersion::where('product_id', $product->id)->where('version', $version)->first()
            : $this->latestRelease($product);
    }

    /**
     * ตัวล่าสุดที่ตามทัน GitHub (read-through 5 นาที) — ต่อ GitHub ไม่ติด (timeout/DNS) HTTP client โยน
     * ConnectionException ออกมา ปุ่ม "ดาวน์โหลดฟรี" ต้องไม่กลายเป็นหน้า 500 จึงถอยไปใช้ตัวที่ DB รู้
     */
    protected function latestRelease(Product $product): ?ProductVersion
    {
        try {
            return app(GithubReleaseService::class)->latestVersionFresh($product);
        } catch (\Throwable $e) {
            Log::warning('download: ถาม GitHub ไม่สำเร็จ ใช้เวอร์ชันใน DB แทน', [
                'product' => $product->slug,
                'error' => $e->getMessage(),
            ]);

            return $product->latestVersion();
        }
    }

    /**
     * APK ของแอป Android — Content-Type ต้องเป็น application/vnd.android.package-archive มือถือถึงจะเสนอติดตั้ง
     *
     * streamer เลือกชนิดจากนามสกุลของชื่อไฟล์ เวอร์ชันที่ไม่รู้ชื่อไฟล์ (สร้างมือ มีแค่ลิงก์ API) จึงได้ชื่อ
     * "{แอป}-v{เวอร์ชัน}.apk" แบบเดียวกับไฟล์ใน release ของแอปเหล่านี้ ไม่ใช่ "download-{เวอร์ชัน}" ที่ไม่มีนามสกุล
     */
    protected function serveApk(Request $request, Product $product, ?ProductVersion $version, string $backTo, string $appName): Response
    {
        return $this->serveRelease($request, $product, $version, $backTo, $version ? "{$appName}-v{$version->version}.apk" : null);
    }

    /**
     * เบราว์เซอร์ขอ text/html มาเสมอเวลาเปิดลิงก์ ส่วนแอป (HttpClient) ไม่ส่ง Accept หรือส่ง * / *
     * — แอปต้องได้ JSON ที่อ่านออก ไม่ใช่หน้าเว็บ
     */
    protected function isBrowser(Request $request): bool
    {
        return ! $request->wantsJson()
            && str_contains((string) $request->header('Accept'), 'text/html');
    }

    protected function downloadUnavailable(
        Request $request,
        string $backTo,
        int $status,
        string $error,
        string $message,
        ?int $retryAfter = null,
    ): Response {
        if (! $this->isBrowser($request)) {
            $response = response()->json(['success' => false, 'error' => $error], $status);

            // ช่องส่งไฟล์เต็ม — บอกแอปว่าอีกนานแค่ไหนค่อยลองใหม่
            return $retryAfter ? $response->header('Retry-After', (string) $retryAfter) : $response;
        }

        return redirect()->to($backTo)->with('error', $message);
    }

    /**
     * บันทึกไว้ให้เห็นในหน้า admin → เวอร์ชัน → ประวัติดาวน์โหลด
     * บันทึกไม่ได้ต้องไม่ทำให้ลูกค้าโหลดไม่ได้
     */
    private function logReleaseDownload(Request $request, Product $product, ProductVersion $version): void
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
            Log::warning('download: บันทึก download log ไม่สำเร็จ', [
                'product' => $product->slug,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
