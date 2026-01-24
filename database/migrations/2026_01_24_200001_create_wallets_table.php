<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User Wallets
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('total_deposited', 12, 2)->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->decimal('total_refunded', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('user_id');
            $table->index('balance');
        });

        // Wallet Transactions
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id', 50)->unique();

            // Transaction type
            $table->enum('type', [
                'deposit',      // เติมเงิน
                'withdrawal',   // ถอนเงิน
                'payment',      // ชำระเงิน
                'refund',       // คืนเงิน
                'bonus',        // โบนัส
                'adjustment',   // ปรับยอด (admin)
                'cashback',     // เงินคืน
            ]);

            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);

            // Reference
            $table->string('reference_type')->nullable(); // order, topup, etc.
            $table->unsignedBigInteger('reference_id')->nullable();

            // Payment details for deposits
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();

            $table->text('description')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('status');
        });

        // Wallet Top-up Requests
        Schema::create('wallet_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('topup_id', 50)->unique();

            $table->decimal('amount', 12, 2);
            $table->decimal('bonus_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2); // amount + bonus

            $table->string('payment_method'); // bank_transfer, promptpay, truemoney, etc.
            $table->string('payment_reference')->nullable();
            $table->text('payment_proof')->nullable(); // slip image path

            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->text('reject_reason')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('payment_method');
        });

        // Wallet Bonus Tiers (for top-up bonuses)
        Schema::create('wallet_bonus_tiers', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_amount', 12, 2);
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->enum('bonus_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('bonus_value', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['min_amount', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_bonus_tiers');
        Schema::dropIfExists('wallet_topups');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
