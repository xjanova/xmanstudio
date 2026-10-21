<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The expiry date a renewal was paid against, kept on the renewal row.
 *
 * A renewal whose answer never came back is settled later by asking the
 * registrar whether the date moved. "Moved" used to be measured against the
 * domain's CURRENT date — which the daily sync overwrites with the registrar's
 * date as soon as the renewal lands. After that, the new date no longer looks
 * newer than itself, and a renewal that went through was refunded at the
 * timeout. The date at the moment of paying does not move.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('domain_registrations', 'previous_expires_at')) {
                $table->timestamp('previous_expires_at')->nullable()->after('expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('domain_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('domain_registrations', 'previous_expires_at')) {
                $table->dropColumn('previous_expires_at');
            }
        });
    }
};
