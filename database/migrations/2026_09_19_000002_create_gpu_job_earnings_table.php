<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ประวัติงานและค่าตอบแทนของเครื่องที่คนแชร์การ์ดจอ
 *
 * เจ้าของเครื่องยอมให้เราใช้การ์ดของเขา แล้วจนถึงตอนนี้ระบบไม่มีที่ไหนเลย
 * ที่บอกได้ว่าเครื่องเขาทำงานไปกี่ชิ้นและได้เงินเท่าไร — มีแต่ตัวเลข
 * "วันนี้ทำไปกี่งาน" ที่อยู่ในโปรแกรมและหายไปเมื่อปิดเครื่อง
 * ตารางนี้คือหลักฐานของเขา ไม่ใช่ของเรา
 *
 * `job_id` unique คือหัวใจ: relay หรือ aixman ยิงผลงานเดิมซ้ำได้จากการ retry
 * และจ่ายซ้ำสองรอบคือเงินที่หายไปจริง ๆ ฐานข้อมูลต้องเป็นคนกันเอง
 * ไม่ใช่ปล่อยให้โค้ดฝั่งแอปจำเอง
 *
 * เก็บเป็นสตางค์จำนวนเต็ม ไม่ใช่ float — เงินที่ปัดเศษเพี้ยนทีละนิด
 * สะสมเป็นยอดที่อธิบายกับเจ้าของเครื่องไม่ได้
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gpu_job_earnings', function (Blueprint $table) {
            $table->id();

            // ถอนเครื่องออกแล้วประวัติต้องยังอยู่ — เป็นหลักฐานการจ่ายเงิน
            // จึงเป็น nullOnDelete ไม่ใช่ cascade
            $table->foreignId('gpu_node_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('worker_id', 64)->index();

            // prompt_id ที่ ComfyUI ออกให้ — กันจ่ายซ้ำในระดับฐานข้อมูล
            $table->string('job_id', 96)->unique();

            $table->string('kind', 24);                       // image · audio · video · upscale · embed
            $table->string('model_key', 64)->nullable();
            $table->string('lane', 8)->default('full');       // full = งานด่วน · slow = งานที่ไม่มีคนรอ
            $table->unsignedInteger('seconds')->default(0);   // เวลาเรนเดอร์จริง
            $table->integer('amount_satang')->default(0);

            // pending = ทำงานเสร็จแล้วแต่ยังไม่เข้ากระเป๋า
            // paid    = เข้ากระเป๋าแล้ว ผูกกับ wallet_transactions
            // void    = ยกเลิก เช่น งานล้มหลังจากบันทึกไปแล้ว
            $table->string('status', 16)->default('pending')->index();
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // หน้า "ประวัติการรับเงิน" อ่านด้วยคู่นี้เสมอ
            $table->index(['user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gpu_job_earnings');
    }
};
