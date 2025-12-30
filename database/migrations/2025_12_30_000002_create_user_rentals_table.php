<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ประวัติการเช่าของผู้ใช้ - User Rentals
     */
    public function up(): void
    {
        Schema::create('user_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('rental_package_id')->constrained()->onDelete('cascade');

            // Rental Period
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Status
            $table->enum('status', [
                'pending',      // รอชำระเงิน
                'active',       // ใช้งานอยู่
                'expired',      // หมดอายุ
                'cancelled',    // ยกเลิก
                'suspended'     // ระงับชั่วคราว
            ])->default('pending');

            // Payment Info
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('currency', 3)->default('THB');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();

            // Usage Tracking
            $table->json('usage_stats')->nullable();

            // Auto-renewal
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('next_renewal_at')->nullable();

            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['expires_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_rentals');
    }
};
