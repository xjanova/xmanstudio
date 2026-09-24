<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * รายได้จากการแชร์การ์ดจอ GPUxMINE เข้ากระเป๋าเป็นรายการประเภทของตัวเอง
 *
 * ไม่ใช่ 'deposit' และไม่ใช่ 'bonus': สองประเภทนั้นเดินผ่าน Wallet::deposit()/
 * addBonus() ซึ่งบวก total_deposited ด้วย — ยอด "เติมเงินสะสม" จะรวมเงินที่
 * เขาไม่ได้เติม และรายงานรายรับของเว็บจะนับเงินที่เราจ่ายออกไปเป็นเงินที่รับเข้ามา
 *
 * ผู้อ่านคอลัมน์นี้ที่ตรวจแล้ว (2026-09-25): xmanstudio (WalletTransaction ป้าย/ไอคอน/สี
 * มี default ทุกตัว, หน้าแอดมินกรองตามประเภท) และ aixman (prisma map เป็น String
 * VarChar(20) และเขียนเฉพาะ 'payment') — เพิ่มค่าต่อท้าย enum ไม่ทำให้ใครพัง
 *
 * MySQL: อ่านรายการค่าจริงจาก information_schema แล้วต่อท้าย แทนที่จะเขียนรายการ
 * ทับ — ถ้า prod เคยถูกเพิ่มค่าอื่นด้วยมือ ค่าพวกนั้นต้องไม่หายไปกับ migration นี้
 * การต่อท้าย enum เป็นการแก้ metadata อย่างเดียวบน MySQL 8 ไม่ต้องเขียนตารางใหม่
 */
return new class extends Migration
{
    private const TYPES = ['deposit', 'withdrawal', 'payment', 'refund', 'bonus', 'adjustment', 'cashback'];

    private const EARNING = 'earning';

    public function up(): void
    {
        if ($this->usesMySql()) {
            $values = $this->mysqlEnumValues();
            if (in_array(self::EARNING, $values, true)) {
                return;
            }

            $this->mysqlSetEnum([...$values, self::EARNING]);

            return;
        }

        // SQLite: Laravel เขียน enum เป็น varchar check (...) ต้องนิยามคอลัมน์ใหม่
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->enum('type', [...self::TYPES, self::EARNING])->change();
        });
    }

    public function down(): void
    {
        // เอาค่าออกทั้งที่ยังมีแถวใช้อยู่ = MySQL strict ปฏิเสธ หรือแย่กว่านั้นคือ
        // ตัดประเภทของรายการเงินทิ้งเงียบ ๆ — ให้คนย้อนตัดสินใจเองว่าจะทำอะไรกับแถวพวกนั้น
        if (DB::table('wallet_transactions')->where('type', self::EARNING)->exists()) {
            throw new RuntimeException("wallet_transactions ยังมีรายการประเภท 'earning' — ย้อน migration นี้ไม่ได้จนกว่าจะจัดการแถวเหล่านั้น");
        }

        if ($this->usesMySql()) {
            $this->mysqlSetEnum(array_values(array_diff($this->mysqlEnumValues(), [self::EARNING])));

            return;
        }

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->enum('type', self::TYPES)->change();
        });
    }

    /** @return array<int, string> */
    private function mysqlEnumValues(): array
    {
        $row = DB::selectOne(
            'SELECT COLUMN_TYPE AS column_type FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['wallet_transactions', 'type']
        );

        if ($row === null || ! preg_match('/^enum\((.*)\)$/i', (string) $row->column_type, $m)) {
            return self::TYPES;
        }

        preg_match_all("/'((?:[^'\\\\]|\\\\.|'')*)'/", $m[1], $values);

        return array_map(fn ($v) => str_replace("''", "'", $v), $values[1]);
    }

    /** @param  array<int, string>  $values */
    private function mysqlSetEnum(array $values): void
    {
        $list = implode(', ', array_map(fn ($v) => DB::getPdo()->quote($v), $values));

        DB::statement("ALTER TABLE `wallet_transactions` MODIFY COLUMN `type` ENUM({$list}) NOT NULL");
    }

    private function usesMySql(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
};
