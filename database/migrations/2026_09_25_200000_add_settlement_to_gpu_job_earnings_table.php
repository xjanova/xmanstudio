<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * gpu_job_earnings ต้องเก็บการคิดเงินของงานหนึ่งชิ้นได้ครบ แล้วพาไปถึงกระเป๋าได้
 *
 * aixman เป็นคนเขียนแถว (ตรงเข้าตารางนี้ แบบเดียวกับที่มันเขียน wallets อยู่แล้ว)
 * ส่วนเราเป็นคนพักเงิน ปล่อยเงิน และจ่าย (gpuxmine:settle-earnings) ทุกคอลัมน์ใหม่
 * nullable หรือมีค่าเริ่มต้น โค้ดชุดเก่าบน prod เขียนและอ่านตารางนี้ต่อได้
 *
 * - job_id คือ 'aix-gpu-job-' + ai_gpu_jobs.id ไม่ใช่ prompt_id ของ ComfyUI อีกต่อไป
 *   prompt_id มาจากเครื่อง (เครื่องเลือกเองได้ และถูกล้างทุกครั้งที่งาน retry)
 *   จึงเอามาเป็นกุญแจกันจ่ายซ้ำไม่ได้ — มันย้ายไปอยู่คอลัมน์ของตัวเอง ให้โปรแกรม
 *   บนเครื่องใช้จับคู่กับบัญชีงานของมัน
 * - credits_charged, thb_per_credit, revenue_satang, platform_fee_*: ตอบข้อโต้แย้ง
 *   "ทำไมงานนี้ได้เท่านี้" ได้จากแถวเดียว โดยไม่ต้องคำนวณย้อนจากเรตวันนี้
 * - referral_*: ส่วนแบ่งผู้แนะนำ จ่ายเป็น AffiliateCommission ตอนเงินพ้นระยะพัก
 * - donated_value_satang / free_share: งานที่เจ้าของแชร์ฟรี — ได้ 0 แต่ต้องเห็นว่า
 *   ให้อะไรไปเท่าไร
 * - review_reason / cleared_at / paid_at / void_reason: เส้นทางของเงิน
 *   pending → cleared → paid, หรือ review (รอแอดมิน), หรือ void
 *
 * user_id เลิก cascade: ลบบัญชีแล้วประวัติการจ่ายเงินต้องยังอยู่ แต่ถ้าเป็น restrict
 * ทุกทางที่ลบผู้ใช้อยู่วันนี้ (แอดมิน, ลบบัญชีตัวเอง) จะกลายเป็น 500 สำหรับคนที่เคย
 * แชร์การ์ด — จึงเลือก nullOnDelete และให้ user_id เป็น null ได้ แถวยังอยู่พร้อม
 * worker_id และยอดเงิน ตัวจ่ายเงินข้ามแถวที่ไม่มีเจ้าของ
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpu_job_earnings', function (Blueprint $table) {
            $table->unsignedBigInteger('ai_gpu_job_id')->nullable()->unique()->after('job_id');
            $table->unsignedBigInteger('generation_id')->nullable()->after('ai_gpu_job_id');
            $table->string('prompt_id', 255)->nullable()->index()->after('generation_id');

            $table->integer('credits_charged')->default(0)->after('amount_satang');
            $table->decimal('thb_per_credit', 12, 6)->default(0)->after('credits_charged');
            $table->bigInteger('revenue_satang')->default(0)->after('thb_per_credit');
            $table->decimal('platform_fee_rate', 5, 4)->default(0)->after('revenue_satang');
            $table->bigInteger('platform_fee_satang')->default(0)->after('platform_fee_rate');
            $table->bigInteger('referral_satang')->default(0)->after('platform_fee_satang');
            $table->foreignId('referral_user_id')->nullable()->after('referral_satang')
                ->constrained('users')->nullOnDelete();
            $table->bigInteger('donated_value_satang')->default(0)->after('referral_user_id');
            $table->boolean('free_share')->default(false)->after('donated_value_satang');
            $table->boolean('pro')->default(false)->after('free_share');

            $table->string('review_reason', 255)->nullable()->after('status');
            $table->timestamp('cleared_at')->nullable()->after('completed_at');
            $table->timestamp('paid_at')->nullable()->after('cleared_at');
            $table->string('void_reason', 255)->nullable()->after('paid_at');
            $table->unsignedBigInteger('affiliate_commission_id')->nullable()->after('wallet_transaction_id');

            // ตัวปล่อยเงินอ่านด้วยคู่แรก ตัวจ่ายและหน้า/API ของเจ้าของเครื่องอ่านด้วยคู่ที่สอง
            $table->index(['status', 'completed_at']);
            $table->index(['user_id', 'status']);
        });

        // แยกเป็นสองขั้น: MySQL ต้องปลด constraint เดิมก่อนจึงแก้คอลัมน์ได้
        Schema::table('gpu_job_earnings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('gpu_job_earnings', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // แถวที่เจ้าของถูกลบไปแล้วกลับไปเป็น NOT NULL ไม่ได้ — ต้องจัดการเองก่อน
        // ย้อน migration นี้ ไม่ลบประวัติเงินทิ้งให้โดยอัตโนมัติ
        Schema::table('gpu_job_earnings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('gpu_job_earnings', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('gpu_job_earnings', function (Blueprint $table) {
            $table->dropIndex(['status', 'completed_at']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropUnique(['ai_gpu_job_id']);
            $table->dropIndex(['prompt_id']);
            $table->dropConstrainedForeignId('referral_user_id');
            $table->dropColumn([
                'ai_gpu_job_id',
                'generation_id',
                'prompt_id',
                'credits_charged',
                'thb_per_credit',
                'revenue_satang',
                'platform_fee_rate',
                'platform_fee_satang',
                'referral_satang',
                'donated_value_satang',
                'free_share',
                'pro',
                'review_reason',
                'cleared_at',
                'paid_at',
                'void_reason',
                'affiliate_commission_id',
            ]);
        });
    }
};
