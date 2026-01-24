<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ตารางเก็บ device ที่ลงทะเบียนจาก AutoTradeX app
     * ใช้สำหรับ:
     * 1. รู้ว่า device ไหนรอ activate
     * 2. ป้องกัน trial abuse (hardware ID เดิม, IP เดิม)
     * 3. Lock license กับ device
     */
    public function up(): void
    {
        Schema::create('autotradex_devices', function (Blueprint $table) {
            $table->id();

            // Device identification
            $table->string('machine_id', 64)->unique(); // SHA256 hash
            $table->string('machine_name')->nullable();
            $table->string('os_version')->nullable();
            $table->string('app_version')->nullable();

            // Hardware fingerprint (for abuse detection)
            $table->string('hardware_hash', 64)->nullable(); // CPU + MB + BIOS hash
            $table->string('first_ip', 45)->nullable(); // First seen IP
            $table->string('last_ip', 45)->nullable(); // Last seen IP

            // Registration status
            // demo = trial expired, can view but not trade
            $table->enum('status', ['pending', 'trial', 'licensed', 'blocked', 'expired', 'demo'])
                ->default('pending');

            // Link to license (if activated)
            $table->foreignId('license_id')->nullable()->constrained('license_keys')->onDelete('set null');

            // Trial abuse detection
            $table->integer('trial_attempts')->default(0); // จำนวนครั้งที่พยายามขอ trial
            $table->timestamp('first_trial_at')->nullable(); // ครั้งแรกที่ขอ trial
            $table->timestamp('trial_expires_at')->nullable();
            $table->boolean('is_suspicious')->default(false); // Flag ว่าสงสัยว่า abuse
            $table->text('abuse_reason')->nullable(); // เหตุผลที่สงสัย

            // Related devices (same IP or similar hardware)
            $table->json('related_devices')->nullable(); // Array of related device IDs

            // Timestamps
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for fast lookup
            $table->index('hardware_hash');
            $table->index('first_ip');
            $table->index('last_ip');
            $table->index('status');
            $table->index(['machine_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autotradex_devices');
    }
};
