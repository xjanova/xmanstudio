<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Reverse X (Windows) — ไฟล์ติดตั้ง/อัปเดตสาธารณะ
 *
 * zip เดียวใช้ทั้งรุ่นฟรีและ Pro (Pro ปลดในแอปด้วย license key) จึงไม่ต้องล็อกอิน
 * ตัวอัปเดตในแอปโหลดจาก URL นี้แบบระบุเวอร์ชัน (ได้มาจาก /api/v1/product/reverse-x/update/check)
 * และอัปเดตได้ทุก tier (ฟรีด้วย) เหมือน Anti X
 *
 * ⚠️ ผูกกับ reverse-x ตายตัว ห้ามทำเป็น /{slug}/download — จะกลายเป็นประตูหลังให้โหลดสินค้าตัวอื่นที่ต้องซื้อก่อนได้ฟรี
 * ⚠️ release อยู่ใน repo public (xjanova/reverse-x) แต่ไฟล์ก็ยังต้องผ่านเซิร์ฟเวอร์นี้
 *    ห้าม redirect ไป GitHub (กฎเจ้าของ: ลูกค้าต้องไม่รู้ repo) และห้ามมีคำว่า github ในคำตอบ
 * ⚠️ ตัวอัปเดตของแอปปิดการตาม redirect, รับเฉพาะ https://xman4289.com, ตรวจ sha256 ของ update/check
 *    แล้วตรวจ manifest ที่เซ็นด้วย ECDSA ในตัว zip อีกชั้น — ที่นี่ส่งไฟล์ตรงทุก byte เท่านั้น
 */
class ReverseXController extends Controller
{
    use ServesReleaseDownloads;

    private const PRODUCT_SLUG = 'reverse-x';

    public function download(Request $request, ?string $version = null)
    {
        $product = Product::where('slug', self::PRODUCT_SLUG)
            ->where('is_active', true)
            ->first();

        if (! $product) {
            // หน้าสินค้าก็ 404 อยู่แล้ว ไม่มีที่ให้ส่งเบราว์เซอร์กลับไป
            abort_if($this->isBrowser($request), 404);

            return response()->json(['success' => false, 'error' => 'Product not found'], 404);
        }

        // ระบุเวอร์ชัน = ตัวนั้นเป๊ะ ๆ ตาม sha256 ที่ update/check ส่งให้แอปไปแล้ว · ไม่ระบุ = ตัวล่าสุด
        $release = $this->releaseFor($product, $version);

        return $this->serveRelease(
            $request,
            $product,
            $release,
            route('products.show', self::PRODUCT_SLUG),
            // เวอร์ชันที่สร้างมือในหน้า admin อาจไม่รู้ชื่อไฟล์ — ตั้งชื่อแบบเดียวกับไฟล์ใน release (.zip → octet-stream)
            $release ? "ReverseX-{$release->version}-win-x64.zip" : null,
        );
    }
}
