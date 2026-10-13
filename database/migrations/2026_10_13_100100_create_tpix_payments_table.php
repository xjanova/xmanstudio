<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One TPIX quote and what became of it: an exact amount of TPIX to send to the shop's address
     * for an order or a wallet top-up, at the price of the moment it was made.
     *
     * Plain string columns, no ENUM: status and purpose are checked in code (TpixPayment), and an
     * enum here would be one more column for the MySQL strict-mode trap EnumColumnsMatchCodeTest
     * guards.
     *
     * Two unique indexes carry the money safety:
     * - tx_hash: one on-chain transfer pays one quote, ever.
     * - open_key (= receive address + exact wei) while the quote can still be paid: no two open
     *   quotes ask for the same amount, so a transfer found on the chain belongs to exactly one.
     *   NULL (released) once paid, cancelled, or past expiry + grace — both engines allow many NULLs.
     */
    public function up(): void
    {
        if (Schema::hasTable('tpix_payments')) {
            return;
        }

        Schema::create('tpix_payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->nullableMorphs('payable');
            $table->string('purpose', 20);

            $table->decimal('amount_thb', 14, 2);
            $table->decimal('rate_thb', 24, 12);
            $table->decimal('price_usd', 24, 12)->nullable();
            $table->decimal('usd_thb', 14, 6)->nullable();
            $table->string('amount_wei', 80);
            $table->decimal('amount_tpix', 38, 18);
            $table->string('receive_address', 42);

            $table->string('status', 20)->default('pending');
            $table->string('open_key', 130)->nullable()->unique();

            $table->string('tx_hash', 66)->nullable()->unique();
            $table->string('from_address', 42)->nullable();
            $table->unsignedBigInteger('block_number')->nullable();
            $table->unsignedInteger('confirmations')->default(0);

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpix_payments');
    }
};
