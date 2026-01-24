<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_devices', function (Blueprint $table) {
            $table->id();

            // Product reference
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            // Device identification
            $table->string('machine_id', 64)->index();
            $table->string('machine_name')->nullable();
            $table->string('os_version')->nullable();
            $table->string('app_version', 50)->nullable();

            // Hardware fingerprint for abuse detection
            $table->string('hardware_hash', 64)->nullable()->index();
            $table->string('first_ip', 45)->nullable()->index();
            $table->string('last_ip', 45)->nullable()->index();

            // Status
            $table->enum('status', ['pending', 'trial', 'licensed', 'blocked', 'expired', 'demo'])
                  ->default('pending')
                  ->index();

            // License reference
            $table->foreignId('license_id')->nullable()->constrained('license_keys')->onDelete('set null');

            // Trial tracking
            $table->unsignedInteger('trial_attempts')->default(0);
            $table->timestamp('first_trial_at')->nullable();
            $table->timestamp('trial_expires_at')->nullable()->index();

            // Abuse detection
            $table->boolean('is_suspicious')->default(false)->index();
            $table->text('abuse_reason')->nullable();
            $table->json('related_devices')->nullable();

            // Timestamps
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            // Early bird discount tracking
            $table->boolean('early_bird_used')->default(false);
            $table->timestamp('early_bird_used_at')->nullable();
            $table->string('early_bird_order_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: one device per product
            $table->unique(['product_id', 'machine_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_devices');
    }
};
