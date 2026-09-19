<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The instalments of an accepted quotation, as documents.
     *
     * The payment schedule existed only as three lines printed on the
     * quotation. Once the customer accepted, nobody could say which instalment
     * had been billed, when the next one was due, or how much of the job was
     * paid for — the project carried a single paid_amount with no history
     * behind it. Each instalment is now a row that can be issued, paid and
     * printed on its own.
     *
     * status is a plain string, not an enum, on purpose. The quotations table
     * learned this the hard way: a new status written by code that the enum did
     * not know about is a 500 on MySQL and passes silently on SQLite, so the
     * whitelist lives in ProjectInvoice::STATUSES where the application can see
     * it and a new value costs no migration.
     */
    public function up(): void
    {
        Schema::create('project_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();

            $table->foreignId('project_order_id')->constrained('project_orders')->cascadeOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete();

            $table->unsignedTinyInteger('installment_no')->default(1);
            $table->unsignedTinyInteger('total_installments')->default(1);
            $table->string('title');
            $table->decimal('percent', 5, 2)->default(0);
            $table->decimal('amount', 12, 2);

            $table->string('status', 20)->default('scheduled');
            $table->date('due_date')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('paid_note', 255)->nullable();
            $table->text('notes')->nullable();

            // Same reasoning as the quotation's token: the document carries
            // prices and the customer's details, and the customer may not have
            // an account, so the link gets its own secret.
            $table->string('public_token', 64)->nullable()->unique();

            $table->timestamps();

            $table->index(['project_order_id', 'installment_no']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_invoices');
    }
};
