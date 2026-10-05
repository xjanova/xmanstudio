<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Models\Product;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Thai Prompt POS — ไฟล์ของแอปขายหน้าร้าน (Android APK / Windows zip) ส่งจาก xman4289.com เอง
 *
 * ตัวอัปเดตในแอปถาม /api/v1/product/{thaiprompt-pos|thaiprompt-pos-windows}/update/check แล้วโหลด download_url
 * ที่ได้มา — แอปรับเฉพาะ https://xman4289.com ไม่ยอมตาม redirect และไฟล์ต้องได้ขนาด + sha256 ตรงกับที่ประกาศ
 * ห้ามพาไป GitHub ทั้งลิงก์และ redirect (กฎเจ้าของ 2026-09-24) · แอปไม่ส่ง session หรือ license มา
 *
 * {version} = ตัวนั้นเป๊ะ ๆ ที่ update/check ประกาศ sha256 ไว้ (แม้มีตัวใหม่ออกมาระหว่างนั้น) · ไม่ใส่ = ตัวล่าสุด
 *
 * ต่างจาก Aipray ตรงที่ไม่ดู is_active: สินค้าสองแถวนี้ปิดไว้โดยตั้งใจให้ไม่ขึ้นหน้าร้านและซื้อไม่ได้
 * (migration 2026_10_05_100000) · หยุดแจกเวอร์ชันไหน ให้ปิดเวอร์ชันนั้นในหน้า admin → เวอร์ชัน
 */
class ThaipromptPosController extends Controller
{
    use ServesReleaseDownloads;

    /** GET /apps/thaiprompt-pos/download/{version?} — APK (application/vnd.android.package-archive) */
    public function android(Request $request, ?string $version = null): Response
    {
        return $this->serve($request, 'thaiprompt-pos', $version, fn (string $v) => "thaiprompt-pos-v{$v}.apk");
    }

    /** GET /apps/thaiprompt-pos-windows/download/{version?} — zip */
    public function windows(Request $request, ?string $version = null): Response
    {
        return $this->serve($request, 'thaiprompt-pos-windows', $version, fn (string $v) => "thaiprompt-pos-windows-v{$v}.zip");
    }

    /**
     * @param  \Closure(string): string  $fallbackName  ชื่อไฟล์เมื่อเวอร์ชันไม่รู้ชื่อจริง (สร้างมือในหน้า admin) — นามสกุลกำหนด Content-Type
     */
    private function serve(Request $request, string $slug, ?string $version, \Closure $fallbackName): Response
    {
        $product = Product::where('slug', $slug)->with('githubSetting')->first();

        if (! $product) {
            abort_if($this->isBrowser($request), 404);

            return response()->json(['success' => false, 'error' => 'Product not found'], 404);
        }

        $release = $this->releaseFor($product, $version);

        // แอปนี้ไม่มีหน้าของตัวเองบนเว็บเรา — ส่งไม่ได้ เบราว์เซอร์กลับหน้าแรกพร้อมข้อความ แอปได้ JSON
        return $this->serveRelease($request, $product, $release, url('/'), $release ? $fallbackName($release->version) : null);
    }
}
