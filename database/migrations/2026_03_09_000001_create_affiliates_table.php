<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('referral_code', 32)->unique();
            $table->decimal('commission_rate', 5, 2)->default(10.00); // % ค่าคอมมิชชั่น
            $table->enum('status', ['pending', 'active', 'suspended'])->default('active');
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('total_pending', 12, 2)->default(0);
            $table->integer('total_clicks')->default(0);
            $table->integer('total_referrals')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('referral_code');
            $table->index('status');
        });

        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('referred_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('order_amount', 12, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['affiliate_id', 'status']);
        });

        // Add affiliate tracking to orders
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('affiliate_id')->nullable()->after('coupon_code')->constrained()->onDelete('set null');
            $table->string('referral_code', 32)->nullable()->after('affiliate_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['affiliate_id']);
            $table->dropColumn(['affiliate_id', 'referral_code']);
        });

        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('affiliates');
    }
};
