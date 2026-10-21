<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * แอดมินเรียกคืนเงินที่จ่ายไปแล้วได้ (R2-3) — รายได้ของเครื่องหนึ่งงาน หรือค่าแนะนำ GPUxMINE หนึ่งรายการ
 *
 * เรียกคืนได้ครั้งเดียว ต่อเมื่อยอดในกระเป๋ายังพอ (ยังไม่ถูกถอนเป็นเงินสด) — การเรียกคืนคือ
 * wallet_transactions หนึ่งแถว (adjustment ติดลบ) ที่ผูกกลับมาที่แถวเดิมด้วยคอลัมน์ข้างล่าง
 *
 * gpu_job_earnings: สถานะเป็น 'void' เหมือนการยกเลิกก่อนจ่าย (C2 มีห้าสถานะเท่านั้น และโปรแกรม
 *   ฝั่งเครื่องรู้จัก void อยู่แล้ว) ส่วน reversed_at บอกว่าเงินเคยเข้ากระเป๋าแล้วถูกเรียกคืน
 *   referral_reversed_satang: ส่วนแบ่งผู้แนะนำของงานนี้ที่ถูกเอาออกพร้อมกัน — หักจากกระเป๋าผู้แนะนำ
 *   (referral_reversal_wallet_transaction_id) ถ้าค่าแนะนำจ่ายไปแล้ว หรือตัดออกจากค่าแนะนำที่ยังไม่จ่าย
 *   ค่าแนะนำเป็นก้อนรวมหลายงาน การเรียกคืนทั้งก้อนภายหลังจึงหักเฉพาะส่วนที่ยังเหลือ
 * affiliate_commissions: ค่าใหม่ 'reversed' ต่อท้าย enum ของ status — 'rejected' แปลว่าไม่เคยจ่าย
 *   ใช้ปนกันไม่ได้ ผู้อ่านคอลัมน์นี้ที่ตรวจแล้ว (2026-09-26): xmanstudio เท่านั้น (หน้า affiliate
 *   ของลูกค้าและแอดมิน ป้าย/สีมี default ทุกที่ ยอดรายเดือนนับเฉพาะ 'paid') — aixman ไม่อ่านตารางนี้
 *
 * MySQL: อ่านรายการค่าจริงจาก information_schema แล้วต่อท้าย แบบเดียวกับ
 * 2026_09_25_200001 — ค่าที่ prod อาจเคยถูกเพิ่มด้วยมือต้องไม่หายไปกับ migration นี้
 */
return new class extends Migration
{
    private const STATUSES = ['pending', 'approved', 'paid', 'rejected'];

    private const REVERSED = 'reversed';

    public function up(): void
    {
        Schema::table('gpu_job_earnings', function (Blueprint $table) {
            $table->timestamp('reversed_at')->nullable()->after('void_reason');
            $table->foreignId('reversed_by')->nullable()->after('reversed_at')
                ->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('reversal_wallet_transaction_id')->nullable()->after('reversed_by');
            $table->bigInteger('referral_reversed_satang')->default(0)->after('reversal_wallet_transaction_id');
            $table->unsignedBigInteger('referral_reversal_wallet_transaction_id')->nullable()->after('referral_reversed_satang');
        });

        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->timestamp('reversed_at')->nullable()->after('paid_at');
            $table->foreignId('reversed_by')->nullable()->after('reversed_at')
                ->constrained('users')->nullOnDelete();
            $table->string('reversal_reason', 255)->nullable()->after('reversed_by');
            $table->unsignedBigInteger('reversal_wallet_transaction_id')->nullable()->after('reversal_reason');
        });

        if ($this->usesMySql()) {
            $values = $this->mysqlEnumValues();
            if (! in_array(self::REVERSED, $values, true)) {
                $this->mysqlSetEnum([...$values, self::REVERSED]);
            }

            return;
        }

        // SQLite: Laravel เขียน enum เป็น varchar check (...) ต้องนิยามคอลัมน์ใหม่
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->enum('status', [...self::STATUSES, self::REVERSED])->default('pending')->change();
        });
    }

    public function down(): void
    {
        // ค่าแนะนำที่ถูกเรียกคืนแล้วกลับเป็นสถานะอื่นเองไม่ได้ — ย้อนตรงนี้คือทิ้งร่องรอยว่าเงินถูกดึงกลับ
        if (DB::table('affiliate_commissions')->where('status', self::REVERSED)->exists()) {
            throw new RuntimeException("affiliate_commissions ยังมีรายการสถานะ 'reversed' — ย้อน migration นี้ไม่ได้จนกว่าจะจัดการแถวเหล่านั้น");
        }

        if ($this->usesMySql()) {
            $this->mysqlSetEnum(array_values(array_diff($this->mysqlEnumValues(), [self::REVERSED])));
        } else {
            Schema::table('affiliate_commissions', function (Blueprint $table) {
                $table->enum('status', self::STATUSES)->default('pending')->change();
            });
        }

        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['reversed_at', 'reversal_reason', 'reversal_wallet_transaction_id']);
        });

        Schema::table('gpu_job_earnings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['reversed_at', 'reversal_wallet_transaction_id', 'referral_reversed_satang', 'referral_reversal_wallet_transaction_id']);
        });
    }

    /** @return array<int, string> */
    private function mysqlEnumValues(): array
    {
        $row = DB::selectOne(
            'SELECT COLUMN_TYPE AS column_type FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['affiliate_commissions', 'status']
        );

        if ($row === null || ! preg_match('/^enum\((.*)\)$/i', (string) $row->column_type, $m)) {
            return self::STATUSES;
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.|'')*)'/", $m[1], $values);

        return array_map(fn ($v) => str_replace("''", "'", $v), $values[1]);
    }

    /** @param  array<int, string>  $values */
    private function mysqlSetEnum(array $values): void
    {
        $list = implode(', ', array_map(fn ($v) => DB::getPdo()->quote($v), $values));

        DB::statement("ALTER TABLE `affiliate_commissions` MODIFY COLUMN `status` ENUM({$list}) NOT NULL DEFAULT 'pending'");
    }

    private function usesMySql(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
