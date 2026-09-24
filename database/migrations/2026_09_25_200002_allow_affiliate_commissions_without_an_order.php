<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ค่าแนะนำที่ไม่ได้มาจากคำสั่งซื้อ (เช่น ส่วนแบ่งจากงาน GPUxMINE) ต้องบันทึกได้ทุกฐานข้อมูล
 *
 * 2026_03_10_000001 ทำ order_id ให้เป็น null ได้เฉพาะบน MySQL แล้วข้าม SQLite ไป
 * เพราะตอนนั้น SQLite แก้คอลัมน์ไม่ได้ — ผลคือเครื่อง dev และชุดเทสต์บันทึกค่าแนะนำ
 * ของ rental, Tping หรือ GPUxMINE ไม่ได้เลย ส่วน prod (MySQL) เป็น null ได้อยู่แล้ว
 * migration นี้จึงไม่แตะ prod: ทำเฉพาะเมื่อคอลัมน์ยังเป็น NOT NULL อยู่จริง
 */
return new class extends Migration
{
    public function up(): void
    {
        if ($this->orderIdNullable()) {
            return;
        }

        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // ไม่ย้อน: ค่าแนะนำที่ไม่มีคำสั่งซื้อที่บันทึกไปแล้วจะทำให้การย้อนล้ม
        // และ MySQL บน prod เป็น null ได้มาตั้งแต่ก่อน migration นี้
    }

    private function orderIdNullable(): bool
    {
        foreach (Schema::getColumns('affiliate_commissions') as $column) {
            if ($column['name'] === 'order_id') {
                return (bool) $column['nullable'];
            }
        }

        return true;
    }
};
