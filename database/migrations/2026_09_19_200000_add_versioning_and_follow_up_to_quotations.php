<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Two things a quotation could not do before.
     *
     * 1. Be revised. A customer who asks to renegotiate got a brand new
     *    quote number, so the same job came back with a second identity and
     *    nothing tied the two together in the admin list or in accounting.
     *    A revision now keeps the number and bumps the version — which means
     *    quote_number can no longer be unique on its own.
     *
     * 2. Be chased. Nothing recorded whether we had already nudged a customer,
     *    so a scheduled reminder would either never run or run every day.
     */
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            // The identity of a document is now number + version.
            $table->dropUnique('quotations_quote_number_unique');
            $table->unique(['quote_number', 'version']);

            // Superseded, not expired: the old version stays readable (someone
            // has the PDF) but can no longer be accepted.
            $table->timestamp('superseded_at')->nullable()->after('declined_at');
            $table->unsignedBigInteger('revision_of')->nullable()->after('version');
            $table->string('revision_note', 500)->nullable()->after('revision_of');

            // One nudge per quotation, ever. The column is the lock.
            $table->timestamp('follow_up_sent_at')->nullable()->after('sent_at');

            $table->index('revision_of');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropIndex(['revision_of']);
            $table->dropUnique(['quote_number', 'version']);
            $table->dropColumn(['superseded_at', 'revision_of', 'revision_note', 'follow_up_sent_at']);
            $table->unique('quote_number');
        });
    }
};
