<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which expiry reminders have already gone out for the current period.
 *
 * renewal_notice_sent_at, which is already here, is a single flag for the
 * auto-renew warning: one per period, sent once. Reminders are different —
 * an operator sets several milestones (60, 30, 7, 1 days) and each must go out
 * exactly once, so a flag is not enough and the milestone itself has to be
 * remembered.
 *
 * Stored as a JSON list of day-counts rather than one column per milestone,
 * because the milestones are configuration and adding one must not need a
 * migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domain_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('domain_registrations', 'expiry_reminders_sent')) {
                $table->json('expiry_reminders_sent')->nullable()->after('renewal_notice_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('domain_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('domain_registrations', 'expiry_reminders_sent')) {
                $table->dropColumn('expiry_reminders_sent');
            }
        });
    }
};
