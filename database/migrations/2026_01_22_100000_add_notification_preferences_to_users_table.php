<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // LINE Login OAuth fields
            $table->string('line_access_token')->nullable()->after('line_display_name');
            $table->string('line_refresh_token')->nullable()->after('line_access_token');
            $table->string('line_picture_url')->nullable()->after('line_refresh_token');

            // Notification preferences (JSON for flexibility)
            $table->json('notification_preferences')->nullable()->after('line_picture_url');

            // Marketing preferences
            $table->boolean('marketing_email_enabled')->default(true)->after('notification_preferences');
            $table->boolean('marketing_line_enabled')->default(true)->after('marketing_email_enabled');
            $table->timestamp('marketing_consent_at')->nullable()->after('marketing_line_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'line_access_token',
                'line_refresh_token',
                'line_picture_url',
                'notification_preferences',
                'marketing_email_enabled',
                'marketing_line_enabled',
                'marketing_consent_at',
            ]);
        });
    }
};
