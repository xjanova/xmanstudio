<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Http\Request;

/**
 * WinXTools (Windows) — หน้าโหลดสาธารณะ
 *
 * ไฟล์เดียวใช้ทั้งรุ่น Free และ Pro (Pro ปลดล็อกในแอปด้วย license key) จึงไม่ต้องล็อกอิน
 * และตัวแอปก็อัปเดตตัวเองผ่าน URL นี้ (ได้มาจาก /api/v1/product/winx-tools/update/check)
 *
 * ⚠️ ผูกกับ winx-tools ตายตัว ห้ามทำเป็น /{slug}/download — จะกลายเป็นประตูหลังให้โหลด
 *    สินค้าตัวอื่นที่ต้องซื้อก่อนได้ฟรี
 * ⚠️ ไฟล์ส่งจาก xman4289.com เอง ห้าม redirect ไป GitHub (กฎเจ้าของ 2026-09-24 ลูกค้าต้องไม่รู้ repo)
 *    ตัวอัปเดตในแอป (AutoUpdateService) ติดตั้งเมื่อได้ 200 + ชนิดไฟล์ที่ไม่ใช่ text/json
 *    + Content-Length เท่ากับ file_size ที่ update/check ประกาศ + ไฟล์ตรง sha256 ทุก byte เท่านั้น
 */
class WinXToolsController extends Controller
{
    use ServesReleaseDownloads;

    private const PRODUCT_SLUG = 'winx-tools';

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

        // ระบุเวอร์ชัน = ตัวนั้นเป๊ะ ๆ (update/check ส่ง sha256 ของเวอร์ชันนี้ให้แอปไปแล้ว ต่อให้ระหว่างนั้น
        // มีตัวใหม่ออกมา ไฟล์ก็ต้องตรงกับ hash) · ไม่ระบุ = ตัวล่าสุด ตัวเดียวกับที่ update/check โฆษณา
        $productVersion = $version !== null
            ? ProductVersion::where('product_id', $product->id)->where('version', $version)->first()
            : $this->latestRelease($product);

        return $this->serveRelease($request, $product, $productVersion, route('products.show', self::PRODUCT_SLUG));
    }
}
