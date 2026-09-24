<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สิ่งที่ gpu_nodes ต้องรู้เพิ่ม เพื่อให้เครื่องที่แชร์อยู่ได้งานจริงและถอนออกได้จริง
 *
 * ทุกคอลัมน์ nullable หรือมีค่าเริ่มต้น โค้ดชุดเก่าที่ยังรันอยู่บน prod อ่าน
 * ตารางนี้ต่อได้โดยไม่รู้ว่ามีคอลัมน์ใหม่ และ aixman อ่านตารางเดียวกันนี้
 * (รวมแถวที่ soft delete แล้ว) เพื่อหาเจ้าของและผู้แนะนำจาก worker_id
 *
 * - referrer_user_id: ผู้แนะนำของเจ้าของเครื่อง จับไว้ครั้งเดียวตอนจับคู่
 *   aixman ใช้คิดส่วนแบ่งแนะนำเพื่อน (D8)
 * - tunnel_token: กุญแจที่ aixman ใช้เปิดอุโมงค์ /w/ แยกจาก relay_token ที่
 *   เครื่องใช้ต่อ /agent — relay รุ่นใหม่ออกให้สองใบ รุ่นเก่าออกใบเดียว (C4)
 * - dispatch_fingerprint: sha1 ของข้อมูลชุดล่าสุดที่ aixman ตอบรับด้วย 2xx
 *   การส่งซ้ำตัดสินจากค่านี้ ไม่ใช่จาก "เห็นอะไรเปลี่ยนเอง" ซึ่งหน้าเว็บ
 *   ของเจ้าของเคยกลืนไปหมด
 * - dispatch_worker_status / dispatch_last_error: สถานะที่ aixman บอกกลับมา
 *   ว่า worker ของเครื่องนี้อยู่ในสภาพไหน
 * - accepting / busy: สิ่งที่เครื่องรายงานผ่าน relay — null คือไม่รู้
 * - suspended_*: แอดมินระงับเครื่อง (ย้อนกลับได้ ต่างจากการถอนออก)
 * - retire_status: null | pending | done — การถอน worker ที่ aixman และ relay
 *   ต้องสำเร็จจริง ไม่งั้นรอบถัดไปลองใหม่ แม้แถวจะถูก soft delete ไปแล้ว
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpu_nodes', function (Blueprint $table) {
            $table->foreignId('referrer_user_id')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();

            $table->text('tunnel_token')->nullable()->after('relay_token');

            $table->string('dispatch_fingerprint', 64)->nullable()->after('dispatch_note');
            $table->string('dispatch_worker_status', 32)->nullable()->after('dispatch_fingerprint');
            $table->string('dispatch_last_error', 255)->nullable()->after('dispatch_worker_status');

            $table->boolean('accepting')->nullable()->after('assessed');
            $table->boolean('busy')->nullable()->after('accepting');

            $table->timestamp('suspended_at')->nullable()->after('dispatch_last_error');
            $table->string('suspended_reason', 255)->nullable()->after('suspended_at');
            $table->foreignId('suspended_by')->nullable()->after('suspended_reason')
                ->constrained('users')->nullOnDelete();

            $table->string('retire_status', 16)->nullable()->after('suspended_by')->index();
        });
    }

    public function down(): void
    {
        Schema::table('gpu_nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referrer_user_id');
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropIndex(['retire_status']);
            $table->dropColumn([
                'tunnel_token',
                'dispatch_fingerprint',
                'dispatch_worker_status',
                'dispatch_last_error',
                'accepting',
                'busy',
                'suspended_at',
                'suspended_reason',
                'retire_status',
            ]);
        });
    }
};
