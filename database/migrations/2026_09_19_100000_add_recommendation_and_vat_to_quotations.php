<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two things the quotation builder could not express before.
 *
 * 1. Which options belong together. The page showed every option at once and
 *    let the visitor tick them, so nothing knew that a shopping cart needs a
 *    member system, or that SEO is worth suggesting to someone who said they
 *    want to be found on Google. Putting that on the option rows means an
 *    admin changes the sales logic without a deploy.
 *
 * 2. How the totals were meant to be read. `vat` held an amount but nothing
 *    recorded whether it had been added on top, extracted from a tax-inclusive
 *    price, or waived — so an old quotation could not be re-read once the rate
 *    or the policy changed. The accounting work planned on top of this needs
 *    that, and it has to be stored per document, not looked up at print time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_options', function (Blueprint $table) {
            // Ticked and locked: the job cannot be delivered without it.
            $table->boolean('is_core')->default(false);
            // Option keys this one drags in. Ticking the child ticks these too.
            $table->json('requires')->nullable();
            // Outcome keys (App\Support\Quotation\Outcomes) where this is offered
            // up front rather than folded away.
            $table->json('suggested_for')->nullable();
            // Shown beside the price so a line reads as a decision, not a SKU.
            $table->string('reason_th', 255)->nullable();
            $table->string('reason', 255)->nullable();
            // Working days this line adds, for the delivery estimate.
            $table->unsignedSmallInteger('duration_days')->default(0);
        });

        Schema::table('quotations', function (Blueprint $table) {
            // exclusive = add on top · inclusive = price already contains it
            // · none = not registered or exempt.
            $table->string('vat_mode', 12)->default('exclusive');
            // Kept with the document: the rate in force when it was issued.
            $table->decimal('vat_rate', 5, 2)->default(7.00);
            // The taxable base after any extraction, so the accounting side
            // never has to re-derive it from a rounded total.
            $table->decimal('amount_before_vat', 12, 2)->nullable();
            // Withholding is the customer's deduction, not our discount: it
            // changes what lands in the bank, never the amount invoiced.
            $table->decimal('withholding_pct', 5, 2)->default(0);
            $table->decimal('withholding_amount', 12, 2)->default(0);

            // The public link must not be guessable. quote_number is
            // QT-<date>-<4 chars>, which is a short walk for a script, and the
            // document carries prices and the customer's details.
            $table->string('public_token', 64)->nullable()->unique();
            // Which outcome the visitor picked, for reporting and for
            // re-opening the builder with their answers intact.
            $table->string('outcome', 40)->nullable();
            // A renegotiated quotation is a new version of the same number.
            $table->unsignedSmallInteger('version')->default(1);
            $table->timestamp('declined_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quotation_options', function (Blueprint $table) {
            $table->dropColumn(['is_core', 'requires', 'suggested_for', 'reason_th', 'reason', 'duration_days']);
        });

        Schema::table('quotations', function (Blueprint $table) {
            // The unique index has to go before its column on MySQL.
            $table->dropUnique(['public_token']);
            $table->dropColumn([
                'vat_mode', 'vat_rate', 'amount_before_vat', 'withholding_pct',
                'withholding_amount', 'public_token', 'outcome', 'version', 'declined_at',
            ]);
        });
    }
};
