<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ประวัติการชำระเงิน - Rental Payments
     */
    public function up(): void
    {
        Schema::create('rental_payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_rental_id')->nullable()->constrained()->onDelete('set null');

            // Payment Details
            $table->decimal('amount', 10, 2);
            $table->decimal('fee', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            $table->string('currency', 3)->default('THB');

            // Payment Method
            $table->enum('payment_method', [
                'promptpay',
                'bank_transfer',
                'credit_card',
                'truemoney',
                'linepay',
                'manual',
            ])->default('promptpay');

            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'refunded',
                'cancelled',
            ])->default('pending');

            // Gateway Info
            $table->string('gateway')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->json('gateway_response')->nullable();

            // Thai Payment Specific
            $table->string('promptpay_qr_url')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('transfer_slip_url')->nullable();

            // Verification
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');

            // Metadata
            $table->string('description')->nullable();
            $table->json('metadata')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_payments');
    }
};
