<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ระบบยืนยันตัวตน (KYC) ของ XMAN Studio
 *
 * ใช้เป็นด่านเดียวกันสำหรับสองเรื่องที่ต้องรู้ว่า "คนนี้คือใครจริง ๆ":
 *   1. การรับเงิน/ถอนเงิน — ต้องรู้ว่าบัญชีธนาคารปลายทางเป็นของเจ้าของบัญชีจริง
 *   2. การสร้างเนื้อหาสำหรับผู้ใหญ่บน ai.xman4289.com — ความรับผิดของผู้สร้าง
 *      จะบังคับได้ก็ต่อเมื่อระบุตัวผู้สร้างได้ตามกฎหมาย
 *
 * aixman (Next.js) ใช้ฐานข้อมูลเดียวกัน จึงอ่าน `users.kyc_status` ได้ตรง ๆ
 * ไม่ต้องมี API กลาง — รูปแบบเดียวกับที่ genlotto อ่าน wallets
 *
 * ข้อออกแบบที่ตั้งใจ:
 *   - เก็บ **แฮชของเลขบัตร** ไม่เก็บเลขเต็ม → ตรวจซ้ำได้ว่า "บัตรใบนี้เคยใช้แล้ว"
 *     (หนึ่งบัตร = หนึ่งบัญชี) โดยที่ฐานข้อมูลรั่วแล้วไม่ได้เลขบัตรของใครไป
 *     ผู้ตรวจดูเลขเต็มจากรูปได้อยู่แล้ว จึงไม่มีเหตุต้องเก็บเป็นข้อความ
 *   - หนึ่งแถวต่อหนึ่งผู้ใช้ ส่งใหม่ได้ ประวัติการถูกปฏิเสธเก็บใน `history`
 *   - ไฟล์อยู่บนดิสก์ส่วนตัว เสิร์ฟผ่าน controller ที่ตรวจสิทธิ์เสมอ
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasTable('kyc_verifications')) {
            Schema::create('kyc_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

                // draft | pending | approved | rejected
                // string ไม่ใช่ enum — enum ของ MySQL เพิ่มค่าใหม่ทีหลังแล้วเจ็บ
                $table->string('status', 20)->default('draft');

                // เอกสาร (path บนดิสก์ส่วนตัว)
                $table->string('id_card_front_path', 500)->nullable();
                $table->string('id_card_back_path', 500)->nullable();
                $table->string('selfie_path', 500)->nullable();
                $table->string('bank_book_path', 500)->nullable();

                // ตัวตน — เลขบัตรเก็บเป็นแฮช ไม่เก็บเลขเต็ม
                $table->string('id_card_number_hash', 64)->nullable()->unique();
                $table->string('id_card_number_last4', 4)->nullable();
                $table->string('full_name_th')->nullable();
                $table->string('full_name_en')->nullable();
                $table->date('birth_date')->nullable();

                // บัญชีรับเงิน
                $table->string('bank_code', 20)->nullable();
                $table->string('bank_account_number', 30)->nullable();
                $table->string('bank_account_name')->nullable();

                // เผื่อ OCR ในอนาคต — ตอนนี้ผู้ตรวจอ่านจากรูปเอง
                $table->json('extracted_data')->nullable();

                // การตรวจ
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->json('history')->nullable();

                $table->timestamps();

                $table->index('status');
                $table->index(['status', 'submitted_at']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            // ซ้ำกับ kyc_verifications.status โดยตั้งใจ — ด่านตรวจสิทธิ์ถูกเรียก
            // ทุก request ทั้งฝั่ง Laravel และฝั่ง aixman การ join ทุกครั้งไม่คุ้ม
            if (! Schema::hasColumn('users', 'kyc_status')) {
                $table->string('kyc_status', 20)->default('not_submitted')->index();
            }
            if (! Schema::hasColumn('users', 'kyc_verified_at')) {
                $table->timestamp('kyc_verified_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                foreach (['kyc_status', 'kyc_verified_at'] as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
