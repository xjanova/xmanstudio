<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // Discount type
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 10, 2);
            $table->decimal('max_discount', 10, 2)->nullable(); // Max discount for percentage

            // Minimum requirements
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->integer('min_items')->nullable();

            // Usage limits
            $table->integer('usage_limit')->nullable(); // Total usage limit
            $table->integer('usage_limit_per_user')->default(1);
            $table->integer('used_count')->default(0);

            // Validity
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Restrictions
            $table->json('applicable_products')->nullable(); // Product IDs
            $table->json('applicable_categories')->nullable(); // Category IDs
            $table->json('excluded_products')->nullable();
            $table->json('applicable_license_types')->nullable(); // monthly, yearly, lifetime

            // User restrictions
            $table->boolean('first_order_only')->default(false);
            $table->json('allowed_user_ids')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['code', 'is_active']);
            $table->index(['starts_at', 'expires_at']);
        });

        // Coupon usage tracking
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('order_amount', 10, 2);
            $table->timestamps();

            $table->index(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('coupons');
    }
};
