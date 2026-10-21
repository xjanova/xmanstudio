<?php

use App\Support\ReleaseNotes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ล้างลิงก์ GitHub ออกจาก changelog ที่ sync เก็บไว้ก่อนหน้านี้
 *
 * กฎเจ้าของ (2026-09-24): ลูกค้าต้องไม่รู้ repo ต้นทาง — แต่ product_versions.changelog เก็บ body ของ
 * release จาก GitHub มาทั้งดุ้น ("**Full Changelog**: https://github.com/…/compare/…" ของ gpuxmine,
 * "Commit: <sha เต็ม>" + trailer ของ smschecker) แล้วมันออกหน้าสินค้า หน้าโหลด และ update/check
 * ตั้งแต่นี้ไป sync ล้างให้เองทุกรอบ แต่รอบ sync แตะแค่เวอร์ชันล่าสุด — แถวเก่าต้องล้างครั้งเดียวที่นี่
 *
 * แก้เฉพาะแถวที่ค่าเปลี่ยนจริง แถวที่ไม่มีอะไรต้องตัดคงเดิมทุก byte (ReleaseNotes คืนค่าเดิม)
 * ใช้ query builder ตรง ๆ ไม่ผ่าน model — ตัวกรองตอนอ่านของ ProductVersion จะทำให้เห็นแต่ค่าที่ล้างแล้ว
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_versions') || ! Schema::hasColumn('product_versions', 'changelog')) {
            return;
        }

        $owners = Schema::hasTable('github_settings')
            ? DB::table('github_settings')->pluck('github_owner', 'product_id')->all()
            : [];
        $accounts = array_values(array_filter($owners));

        DB::table('product_versions')
            ->whereNotNull('changelog')
            ->select(['id', 'product_id', 'changelog'])
            ->chunkById(200, function ($rows) use ($owners, $accounts) {
                foreach ($rows as $row) {
                    if (trim((string) $row->changelog) === '') {
                        continue;
                    }

                    $clean = ReleaseNotes::forCustomers($row->changelog, [$owners[$row->product_id] ?? null, ...$accounts]);

                    if ($clean !== $row->changelog) {
                        DB::table('product_versions')->where('id', $row->id)->update(['changelog' => $clean]);
                    }
                }
            });
    }

    public function down(): void
    {
        // ลิงก์ที่ตัดทิ้งไปแล้วไม่มีที่ให้เอาคืน — และไม่ควรเอาคืน
    }
};
