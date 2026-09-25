<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * คำขอถอนเงินสดออกจากกระเป๋า XMAN (R2-4)
 *
 * ผู้ใช้กดถอน → ยอดถูกพักทันที (หักจาก wallets.balance ด้วยรายการ withdrawal ที่ยัง pending)
 * → รอแอดมินโอนแล้วกดอนุมัติพร้อมเลขอ้างอิง/สลิป หรือปฏิเสธพร้อมเหตุผล (ยอดที่พักคืนกระเป๋า)
 * ผู้ใช้ยกเลิกเองได้ระหว่างรอ
 *
 * ข้อออกแบบที่ตั้งใจ:
 *   - status เป็น string ไม่ใช่ enum — enum ของ MySQL เพิ่มค่าทีหลังแล้วเจ็บ (บทเรียน 2026-09-19:
 *     ค่าที่คอลัมน์ไม่รู้จักผ่าน sqlite แต่ 500 บน MySQL strict)
 *   - open_user_id = user_id ระหว่างที่คำขอยังเปิดอยู่ และ null เมื่อจบ — unique ที่ฐานข้อมูลจึงกัน
 *     "หนึ่งคำขอที่เปิดอยู่ต่อคน" ได้แม้สองคำขอมาพร้อมกัน (NULL ซ้ำกันได้ทั้ง MySQL และ SQLite)
 *   - (user_id, idempotency_key) unique — กดส่งซ้ำ/รีเฟรชหน้าได้คำขอเดิม ไม่ใช่คำขอใหม่
 *   - เลขบัญชีและพร้อมเพย์เก็บแบบเข้ารหัส (cast encrypted) จึงเป็น text; ชื่อบัญชีเก็บตรง
 *     เพราะแอดมินต้องค้นได้ และเป็นข้อมูลที่อยู่บนสลิปอยู่แล้ว
 *   - user_id/wallet_id เป็น nullOnDelete แบบเดียวกับ gpu_job_earnings: restrict จะทำให้ทุกทาง
 *     ที่ลบผู้ใช้วันนี้กลายเป็น 500 ส่วนประวัติการจ่ายเงินยังอยู่พร้อมบัญชีปลายทางที่ถ่ายไว้
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('withdrawal_id', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('amount', 12, 2);

            // pending | approved | rejected | cancelled
            $table->string('status', 16)->default('pending');
            $table->unsignedBigInteger('open_user_id')->nullable()->unique();
            $table->string('idempotency_key', 64);

            // บัญชีปลายทาง — ถ่ายไว้ ณ ตอนขอ (KYC ที่ยืนยันแล้ว หรือกรอกเองเมื่อ KYC ไม่มี)
            $table->string('method', 16);                     // bank | promptpay
            $table->string('account_source', 16);             // kyc | form
            $table->string('bank_code', 20)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->text('account_number')->nullable();       // encrypted
            $table->string('account_name', 150)->nullable();
            $table->text('promptpay_id')->nullable();         // encrypted

            // รายการในกระเป๋า: พักยอด และคืนยอด (เมื่อปฏิเสธ/ยกเลิก)
            $table->unsignedBigInteger('hold_transaction_id')->nullable();
            $table->unsignedBigInteger('return_transaction_id')->nullable();

            // การตัดสินของแอดมิน
            $table->string('payout_reference', 100)->nullable();
            $table->string('slip_path', 500)->nullable();
            $table->string('reject_reason', 255)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->string('request_ip', 45)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_withdrawals');
    }
};
