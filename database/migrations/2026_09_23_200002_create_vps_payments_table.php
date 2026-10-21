<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every time a VPS moved money: the first order and each renewal.
     *
     * A table of its own rather than more rows in vps_instances — the domain
     * shop keeps renewals in the registrations table, and every list of "the
     * customer's domains" has had to filter them back out ever since.
     *
     * idempotency_key is unique for the double-tap: two requests that both see
     * enough balance lose at the index, not at a check that went stale a
     * millisecond earlier. For an order it comes from a token printed into the
     * form, so two deliberate orders are two keys and a double submit is one.
     *
     * status: pending (debited, supplier not confirmed yet) → paid (the
     * supplier took the order) or refunded (the money went back).
     */
    public function up(): void
    {
        Schema::create('vps_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vps_instance_id')->constrained('vps_instances')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('kind', 16)->default('order');
            $table->string('status', 16)->default('pending');

            $table->decimal('amount_thb', 12, 2);
            $table->unsignedInteger('cost_cents')->default(0);
            $table->string('cost_currency', 3)->default('THB');
            $table->unsignedSmallInteger('months')->default(1);
            $table->string('item_id', 120)->nullable();
            // For a renewal: the paid-until date it was bought against. A
            // renewal whose answer never came is settled by whether upstream's
            // date moved past THIS — not past ours, which the sync may already
            // have moved to the new date.
            $table->timestamp('previous_expires_at')->nullable();

            $table->foreignId('wallet_transaction_id')->nullable()
                ->constrained('wallet_transactions')->nullOnDelete();
            $table->foreignId('refund_transaction_id')->nullable()
                ->constrained('wallet_transactions')->nullOnDelete();

            $table->string('idempotency_key', 80)->unique();
            $table->string('remote_order_id', 64)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['vps_instance_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vps_payments');
    }
};
