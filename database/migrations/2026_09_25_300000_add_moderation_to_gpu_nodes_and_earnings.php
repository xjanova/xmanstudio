<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สิ่งที่แอดมินต้องใช้คุมเครือข่าย GPUxMINE — และร่องรอยว่าใครตัดสินอะไร
 *
 * - gpu_nodes.banned_*: แบนเครื่อง ต่างจากระงับ (suspended_*) ตรงที่เครื่องถูก
 *   ถอนออกทั้งที่ aixman และ relay แล้ว และทั้ง machine_id นี้ (ทุกบัญชี) กับ
 *   บัญชีเจ้าของ จับคู่เครื่องใหม่ไม่ได้อีก จนกว่าแอดมินจะยกเลิกแบน
 * - gpu_job_earnings.reviewed_*: แอดมินคนไหนอนุมัติงานที่ติดรอตรวจ หรือยกเลิก
 *   รายได้ชิ้นไหน เมื่อไร — เงินของคนอื่นที่ถูกตัดสินด้วยมือต้องตามย้อนได้เสมอ
 *
 * ทุกคอลัมน์ nullable โค้ดชุดเก่าบน prod อ่านต่อได้โดยไม่รู้จักมัน และ aixman
 * ที่เขียน gpu_job_earnings ตรง (INSERT ระบุชื่อคอลัมน์) ไม่ถูกกระทบ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpu_nodes', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('suspended_by')->index();
            $table->string('banned_reason', 255)->nullable()->after('banned_at');
            $table->foreignId('banned_by')->nullable()->after('banned_reason')
                ->constrained('users')->nullOnDelete();
        });

        Schema::table('gpu_job_earnings', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->after('void_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('gpu_job_earnings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn('reviewed_at');
        });

        Schema::table('gpu_nodes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('banned_by');
            $table->dropIndex(['banned_at']);
            $table->dropColumn(['banned_at', 'banned_reason']);
        });
    }
};
