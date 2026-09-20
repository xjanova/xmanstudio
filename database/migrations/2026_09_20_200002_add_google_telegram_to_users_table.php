<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Google and Telegram sign-in, alongside the LINE columns already here.
 *
 * Also adds password_set_at, which LineLoginController::unlink() has been
 * reading since the day it shipped. The column never existed, so the check
 * read null for everyone and refused every unlink from a LINE-created account
 * — including accounts that had since set a real password.
 */
return new class extends Migration
{
    public function up(): void
    {
        $backfill = false;

        Schema::table('users', function (Blueprint $table) use (&$backfill) {
            if (! Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('line_picture_url');
                $table->string('google_avatar')->nullable()->after('google_id');
            }

            if (! Schema::hasColumn('users', 'telegram_id')) {
                // Telegram ids are 64-bit integers, but they only ever arrive
                // as strings here and are only ever compared, never summed.
                $table->string('telegram_id', 32)->nullable()->unique()->after('google_avatar');
                $table->string('telegram_username')->nullable()->after('telegram_id');
                $table->string('telegram_avatar')->nullable()->after('telegram_username');
            }

            if (! Schema::hasColumn('users', 'password_set_at')) {
                // Null means the account has only ever been reachable through a
                // social provider. Unlinking the last provider would then lock
                // the owner out of their own orders and wallet.
                $table->timestamp('password_set_at')->nullable()->after('password');

                $backfill = true;
            }
        });

        // Everyone already here would otherwise read as "has no password",
        // and the unlink guard would refuse them all. Accounts LINE created
        // are the exception: their password is the random string the old
        // controller generated, which nobody has ever seen.
        //
        // Matched on the domain rather than the "line_" prefix: escaping the
        // underscore would need a backslash on MySQL and an explicit ESCAPE
        // clause on SQLite, and the domain alone identifies them exactly.
        if ($backfill) {
            DB::table('users')
                ->whereNull('password_set_at')
                ->where('email', 'not like', '%@line.local')
                ->update(['password_set_at' => DB::raw('created_at')]);
        }
    }

    public function down(): void
    {
        // The unique indexes have to go first and in their own statement.
        // SQLite rebuilds the table on a DROP COLUMN and then validates the
        // old indexes against the new shape, so dropping a uniquely-indexed
        // column in one go fails with "no such column" — on the column it was
        // just asked to remove. MySQL does not care either way.
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'google_id')) {
                $table->dropUnique('users_google_id_unique');
            }

            if (Schema::hasColumn('users', 'telegram_id')) {
                $table->dropUnique('users_telegram_id_unique');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            foreach (['google_id', 'google_avatar', 'telegram_id', 'telegram_username', 'telegram_avatar', 'password_set_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
