<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ทุกไฟล์ของ release บน GitHub ที่เวอร์ชันนี้มาจาก — ชื่อ ขนาด sha256 และที่ดึงไฟล์ (GithubReleaseService)
     *
     * คอลัมน์ download_filename / file_size / sha256 เก็บได้ไฟล์เดียว (ตัวที่ตรง asset_pattern เช่น APK)
     * แต่ release ของ LocalVPN มีตัวติดตั้ง Windows (LocalVPN-Setup-v{เวอร์ชัน}.exe) มาคู่กันด้วย
     * แอปบน Windows ต้องได้ขนาดและ sha256 ของตัวติดตั้งไปตรวจไฟล์ก่อนรัน ไม่ใช่ของ APK
     *
     * null = sync ไว้ก่อนมีคอลัมน์นี้ — read-through ครั้งถัดไปเห็นว่ารายการไฟล์ไม่ตรงแล้ว sync ซ้ำให้เอง
     * JSON ไม่มีค่า default (MySQL strict ไม่ยอมให้ JSON มี default) · nullable = แถวเดิมทุกแถวได้ null
     */
    public function up(): void
    {
        if (Schema::hasColumn('product_versions', 'assets')) {
            return;
        }

        Schema::table('product_versions', function (Blueprint $table) {
            $table->json('assets')->nullable()->after('sha256');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('product_versions', 'assets')) {
            return;
        }

        Schema::table('product_versions', function (Blueprint $table) {
            $table->dropColumn('assets');
        });
    }
};
