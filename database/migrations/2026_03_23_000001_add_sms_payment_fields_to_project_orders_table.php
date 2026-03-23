<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_orders', function (Blueprint $table) {
            $table->foreignId('unique_payment_amount_id')->nullable()->after('payment_status');
            $table->foreignId('sms_notification_id')->nullable()->after('unique_payment_amount_id');
            $table->string('sms_verification_status')->nullable()->after('sms_notification_id');
            $table->timestamp('sms_verified_at')->nullable()->after('sms_verification_status');
            $table->decimal('payment_display_amount', 15, 2)->nullable()->after('sms_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('project_orders', function (Blueprint $table) {
            $table->dropColumn([
                'unique_payment_amount_id',
                'sms_notification_id',
                'sms_verification_status',
                'sms_verified_at',
                'payment_display_amount',
            ]);
        });
    }
};
