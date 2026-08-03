<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove the per-user theme override.
 *
 * The theme is now site-wide and admin-controlled (/admin/theme).
 * ThemeService::getCurrentTheme() used to prefer users.preferred_theme over
 * the site default, so anyone who had once picked a personal theme kept
 * seeing it and the site-wide switch appeared to do nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'preferred_theme')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_theme');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'preferred_theme')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_theme')->nullable()->after('notification_preferences');
        });
    }
};
