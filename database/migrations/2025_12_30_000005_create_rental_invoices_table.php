<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ใบแจ้งหนี้/ใบเสร็จ - Invoices
     */
    public function up(): void
    {
        Schema::create('rental_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('rental_payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_rental_id')->nullable()->constrained()->onDelete('set null');

            // Invoice Details
            $table->enum('type', ['invoice', 'receipt', 'tax_invoice'])->default('receipt');
            $table->enum('status', ['draft', 'sent', 'paid', 'void'])->default('draft');

            // Amounts
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('vat', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('THB');

            // Tax Invoice Info
            $table->string('tax_id', 20)->nullable();
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('branch_name', 100)->nullable();

            // Line Items
            $table->json('line_items');

            // Dates
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();

            // PDF
            $table->string('pdf_url')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_invoices');
    }
};
