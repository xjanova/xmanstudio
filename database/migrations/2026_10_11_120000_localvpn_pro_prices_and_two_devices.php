<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * LocalVPN Pro — owner's decisions of 2026-10-11, the data half (the code half is config/licenses.php,
 * Product::MONTHLY_LICENSE_SLUGS and ProductLicenseController):
 *
 *   — the product row's price is the monthly ฿199. The listing card shows it, and a purchase that names
 *     no term pays it and gets a monthly key (Product::defaultLicenseType()). Set outright: the owner chose
 *     the number, and running this again sets the same number.
 *   — one purchased key runs on two devices (a PC + a phone): every paid LocalVPN key that is still active
 *     and allows fewer gets max_activations = 2. Free and trial keys stay one device; a key the admin
 *     already gave more keeps its number.
 *   — the listing text and feature list said phones only (and "Android and iOS" — there is no iOS app);
 *     now that there is a Windows app they say both. Each is replaced only while it is still exactly what
 *     the first migration wrote, so an edit made in the admin stays.
 *
 * down() changes nothing back: it would put a price the owner retired, a description that is wrong, and
 * take a device away from keys customers already use on two.
 */
return new class extends Migration
{
    private const SLUG = 'localvpn';

    private const MONTHLY_PRICE = 199;

    private const DEVICES_PER_KEY = 2;

    private const OLD_SHORT_DESCRIPTION = 'แอพสร้างวง LAN เสมือนผ่านอินเทอร์เน็ต ให้มือถือทุกเครื่องเชื่อมต่อกันเหมือนอยู่วงแลนเดียวกัน';

    private const NEW_SHORT_DESCRIPTION = 'LAN เสมือนผ่านอินเทอร์เน็ต + ลดปิงเกม สำหรับ Windows และ Android — เล่นเกม LAN และส่งไฟล์กับเพื่อนเหมือนอยู่วงเดียวกัน ทดลอง Pro ฟรี 30 วัน';

    /** what 2026_03_26_000001_insert_localvpn_product wrote — "Android และ iOS" was never true (no iOS app) */
    private const OLD_FEATURES = [
        'สร้างวง LAN เสมือนผ่านอินเทอร์เน็ต',
        'สแกนหาเครือข่ายที่เปิดให้เข้าร่วม',
        'ตั้งรหัสเครือข่ายเพื่อความส่วนตัว',
        'เห็นอุปกรณ์ทั้งหมดในวง LAN เสมือน',
        'ส่งข้อมูลระหว่างอุปกรณ์ได้โดยตรง',
        'รองรับ Android และ iOS',
        'เข้ารหัสข้อมูลด้วย WireGuard Protocol',
        'NAT Traversal — ใช้ได้ทุกเครือข่าย',
    ];

    private const NEW_FEATURES = [
        'วง LAN เสมือนผ่านอินเทอร์เน็ต',
        'ลดปิงเกม — วัดเส้นทางจริงก่อนแนะนำ',
        'VPN เลือกประเทศ (WireGuard)',
        'ส่งไฟล์ P2P + Torrent ชุมชน',
        'Windows 10/11 และ Android 7.0+',
        'ทดลอง Pro ฟรี 30 วัน · 1 คีย์ใช้ได้ 2 เครื่อง',
    ];

    private const PAID_TYPES = ['daily', 'weekly', 'monthly', 'yearly', 'lifetime', 'product'];

    public function up(): void
    {
        $productId = DB::table('products')->where('slug', self::SLUG)->value('id');

        if ($productId === null) {
            return;
        }

        DB::table('products')->where('id', $productId)->update([
            'price' => self::MONTHLY_PRICE,
            'updated_at' => now(),
        ]);

        DB::table('products')
            ->where('id', $productId)
            ->where('short_description', self::OLD_SHORT_DESCRIPTION)
            ->update(['short_description' => self::NEW_SHORT_DESCRIPTION]);

        // compared after decoding — MySQL stores the column as JSON and re-formats the text
        $features = json_decode((string) DB::table('products')->where('id', $productId)->value('features'), true);
        if ($features === self::OLD_FEATURES) {
            DB::table('products')->where('id', $productId)->update(['features' => json_encode(self::NEW_FEATURES)]);
        }

        DB::table('license_keys')
            ->where('product_id', $productId)
            ->where('status', 'active')
            ->whereIn('license_type', self::PAID_TYPES)
            ->where('max_activations', '<', self::DEVICES_PER_KEY)
            ->whereNull('deleted_at')
            ->update(['max_activations' => self::DEVICES_PER_KEY, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Deliberately nothing — see the class comment.
    }
};
