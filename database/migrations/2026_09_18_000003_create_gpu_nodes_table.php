<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เครื่องที่เจ้าของนำมาแชร์ใน GPUxMINE — ผูกบัญชี XMAN เข้ากับ worker บน relay
 *
 * ตารางนี้คือจุดเดียวที่รู้ว่า "เครื่องนี้เป็นของใคร" relay รู้แค่ worker id
 * กับ token (เก็บเป็น hash) ส่วน aixman รู้แค่ปลายทางที่จะส่งงานไป ใครเป็น
 * เจ้าของและจะจ่ายเงินเข้ากระเป๋าใบไหน อยู่ที่นี่ที่เดียว
 *
 * `relay_token` เข้ารหัสด้วย APP_KEY เพราะมันคือกุญแจที่เปิดอุโมงค์ไปยัง
 * เครื่องในบ้านคน ไม่ใช่คอนเทนเนอร์ที่ลบทิ้งได้ — เก็บไว้เพราะต้องส่งให้
 * aixman ตอนขึ้นทะเบียน และต้องส่งซ้ำได้เมื่อเจ้าของลงโปรแกรมใหม่
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gpu_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_device_id')->nullable()
                ->constrained('product_devices')->nullOnDelete();

            // --- การจับคู่เครื่องกับบัญชี ---
            // เว็บเป็นฝ่ายออกรหัส เจ้าของพิมพ์ลงในโปรแกรม โปรแกรมยิงมาแลก
            // credential ครั้งเดียว รหัสหมดอายุเร็วเพราะมันคือสิทธิ์ในการ
            // สร้าง worker ใหม่ในนามของบัญชีนี้
            $table->string('pairing_code', 16)->nullable()->unique();
            $table->timestamp('pairing_expires_at')->nullable();
            $table->timestamp('paired_at')->nullable();

            // --- ตัวตนบน relay ---
            $table->string('worker_id', 64)->nullable()->unique();
            $table->text('relay_token')->nullable();
            $table->string('relay_url')->nullable();
            $table->string('tunnel_endpoint')->nullable();

            $table->string('label')->nullable();
            $table->string('machine_id', 64)->nullable()->index();

            // --- สิ่งที่ relay รายงานล่าสุด (คนละเรื่องกับที่เจ้าของกรอก) ---
            $table->boolean('online')->default(false)->index();
            $table->string('agent_version', 32)->nullable();
            $table->string('gpu_name')->nullable();
            $table->unsignedInteger('vram_total_mb')->default(0);
            $table->unsignedInteger('score')->default(0);
            $table->string('tier', 16)->default('unrated');
            $table->boolean('assessed')->default(false)->index();
            $table->json('can_run')->nullable();
            $table->unsignedTinyInteger('free_share_pct')->default(0);
            $table->timestamp('last_seen_at')->nullable();

            // ผลการขึ้นทะเบียนฝั่ง aixman — ว่าเครื่องนี้รับงานจริงได้หรือยัง
            // และถ้าไม่ได้ เพราะอะไร เจ้าของจะได้ไม่ต้องเดา
            $table->timestamp('dispatch_synced_at')->nullable();
            $table->string('dispatch_status', 32)->nullable();
            $table->string('dispatch_note', 255)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gpu_nodes');
    }
};
