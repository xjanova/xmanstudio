<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เก็บ "เลน" ของแต่ละงานที่โหนดทำได้ แยกจาก can_run
 *
 * can_run ตอบว่าเครื่องทำงานนั้น "ได้ไหม" ซึ่งยังตอบว่าได้อยู่แม้เครื่องจะใช้
 * เวลาสี่นาทีต่อภาพ ส่วน lanes ตอบว่า "เร็วพอให้คนนั่งรอไหม" — เป็นคนละคำถาม
 * และเป็นคำถามที่ตัวจ่ายงานต้องใช้ ไม่งั้นลูกค้าที่กดแล้วนั่งดูแถบความคืบหน้า
 * จะถูกส่งไปเครื่องที่ช้าที่สุดในระบบได้
 *
 * provisional คือรายการงานที่โหนดได้เลนมาแบบ "ให้ไว้ก่อน" ยังไม่มีเวลาจริง
 * มายืนยัน — โหนดใหม่ทุกเครื่องเริ่มแบบนี้ แล้วถูกปรับตามงานที่ทำจริง
 *
 * ทั้งสองคอลัมน์ nullable: โหนดที่ยังไม่ได้อัปเดตไคลเอนต์จะไม่ส่งมา และ
 * "ไม่ส่งมา" ต้องแปลว่าเร็วเต็มที่ ซึ่งคือสิ่งที่ระบบสมมติอยู่แล้วก่อนมีฟิลด์นี้
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpu_nodes', function (Blueprint $table) {
            $table->json('lanes')->nullable()->after('can_run');
            $table->json('provisional')->nullable()->after('lanes');
        });
    }

    public function down(): void
    {
        Schema::table('gpu_nodes', function (Blueprint $table) {
            $table->dropColumn(['lanes', 'provisional']);
        });
    }
};
