<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GigGok AI is now paid per message from the user's wallet (THB) - no free
 * quota. Each usage row records what it cost and which wallet transaction paid
 * for it, so a refund can find its payment and the admin page can sum revenue
 * without re-reading the wallet ledger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_ai_usages', function (Blueprint $table) {
            // null = the call never got as far as being charged (refused early)
            $table->decimal('price', 10, 2)->nullable()->after('ok');
            $table->unsignedBigInteger('wallet_transaction_id')->nullable()->after('price');
            // The answer failed after we charged, and the charge went back
            $table->boolean('refunded')->default(false)->after('wallet_transaction_id');

            $table->index('wallet_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('app_ai_usages', function (Blueprint $table) {
            $table->dropIndex(['wallet_transaction_id']);
            $table->dropColumn(['price', 'wallet_transaction_id', 'refunded']);
        });
    }
};
