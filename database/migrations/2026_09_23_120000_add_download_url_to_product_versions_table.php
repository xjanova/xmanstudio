<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ลิงก์ตรงของไฟล์ที่ใครก็โหลดได้ (browser_download_url ของ release asset)
     *
     * github_release_url เก็บ URL ของ asset API ซึ่งต้องถาม api.github.com ก่อนทุกครั้ง
     * (repo private ต้องมี token ด้วย) กว่าจะได้ไฟล์จริง ส่วน repo public ใช้ลิงก์นี้ได้เลย
     * หน้าโหลดจึง redirect ไปตรง ๆ ได้ ไม่เปลืองโควตา API และไม่ต้อง stream ไฟล์หลายสิบ MB
     * ผ่าน PHP worker (ดู WinXToolsController)
     *
     * ตั้งชื่อ download_url ไม่ใช่ browser_download_url โดยตั้งใจ — TpingController และ
     * LocalVpnWebController อ่าน $version->browser_download_url (ยังไม่มีคอลัมน์ จึงได้ null
     * แล้วประกอบ URL เอง) ถ้าใช้ชื่อนั้นลิงก์ดาวน์โหลดของสองแอปนั้นจะเปลี่ยนไปด้วย
     */
    public function up(): void
    {
        Schema::table('product_versions', function (Blueprint $table) {
            $table->string('download_url', 1024)->nullable()->after('github_release_url');
        });
    }

    public function down(): void
    {
        Schema::table('product_versions', function (Blueprint $table) {
            $table->dropColumn('download_url');
        });
    }
};
