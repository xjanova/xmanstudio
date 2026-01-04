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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quote_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Customer info
            $table->string('customer_name');
            $table->string('customer_company')->nullable();
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('customer_address')->nullable();

            // Service info
            $table->string('service_type');
            $table->string('service_name');
            $table->json('service_options');
            $table->json('additional_options')->nullable();
            $table->text('project_description')->nullable();
            $table->string('timeline')->default('normal');

            // Pricing
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->integer('discount_percent')->default(0);
            $table->decimal('rush_fee', 12, 2)->default(0);
            $table->decimal('vat', 12, 2);
            $table->decimal('grand_total', 12, 2);

            // Status
            $table->enum('status', ['draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired', 'paid'])->default('draft');
            $table->enum('action_type', ['quotation', 'order'])->default('quotation');
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->nullable();

            // Dates
            $table->date('valid_until');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Notes
            $table->text('admin_notes')->nullable();
            $table->text('customer_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('customer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
