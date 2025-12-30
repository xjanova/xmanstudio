<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * รหัสส่วนลด - Promo Codes
     */
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();

            // Discount
            $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('discount_value', 10, 2);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->decimal('min_purchase', 10, 2)->default(0);

            // Restrictions
            $table->json('applicable_packages')->nullable();
            $table->boolean('first_purchase_only')->default(false);
            $table->integer('max_uses')->nullable();
            $table->integer('max_uses_per_user')->default(1);
            $table->integer('times_used')->default(0);

            // Validity
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['code', 'is_active']);
        });

        // Promo Code Usages
        Schema::create('promo_code_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promo_code_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('rental_payment_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();

            $table->index(['promo_code_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_code_usages');
        Schema::dropIfExists('promo_codes');
    }
};
