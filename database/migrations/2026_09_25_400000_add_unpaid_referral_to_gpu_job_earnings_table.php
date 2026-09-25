<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ส่วนแบ่งผู้แนะนำที่ไม่มีใครรับได้ต้องไปอยู่ที่ไหนสักที่ที่ตามรอยได้ และโปรแกรมต้องถามได้ว่าอะไรเปลี่ยน
 *
 * - referral_unpaid_satang / referral_unpaid_to: aixman หักส่วนแบ่งผู้แนะนำออกจากเงินของเจ้าของเครื่อง
 *   ตอนงานเสร็จ (ผู้แนะนำยังเป็น affiliate ที่ active ตอนนั้น) แต่ถ้าถึงวันโอนผู้แนะนำถูกระงับ ถูกลบ
 *   หรือเป็นเจ้าของเครื่องเอง ส่วนนั้นเคยหายเงียบ ๆ — ไม่มีใครได้ ไม่มีรายการไหนบอก ตอนนี้ตัวโอน
 *   บันทึกว่าไม่มีใครรับได้เท่าไร และไปที่ไหน: 'owner' = คืนเข้ายอดของเจ้าของเครื่อง (ค่าเริ่มต้น)
 *   'platform' = แพลตฟอร์มเก็บไว้ (ตั้ง GPUXMINE_UNPAID_REFERRAL=platform) ทั้งสองแบบเห็นในหน้าแอดมิน
 * - index (user_id, updated_at): /status ของโปรแกรมถามได้ว่า "มีอะไรเปลี่ยนตั้งแต่ครั้งก่อน"
 *   (updated_after) แทนการเห็นแค่ห้าสิบงานล่าสุด
 *
 * ทุกคอลัมน์มีค่าเริ่มต้นหรือ nullable — aixman ที่ INSERT ระบุชื่อคอลัมน์ และโค้ดชุดเก่าบน prod
 * เขียนและอ่านตารางนี้ต่อได้โดยไม่รู้จักมัน
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpu_job_earnings', function (Blueprint $table) {
            $table->bigInteger('referral_unpaid_satang')->default(0)->after('referral_user_id');
            $table->string('referral_unpaid_to', 16)->nullable()->after('referral_unpaid_satang');

            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('gpu_job_earnings', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'updated_at']);
            $table->dropColumn(['referral_unpaid_satang', 'referral_unpaid_to']);
        });
    }
};
