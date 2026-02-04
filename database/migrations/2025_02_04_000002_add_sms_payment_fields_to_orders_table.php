<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Unique payment amount reference
            $table->foreignId('unique_payment_amount_id')
                ->nullable()
                ->after('notes')
                ->constrained('unique_payment_amounts')
                ->nullOnDelete();

            // SMS notification reference
            $table->foreignId('sms_notification_id')
                ->nullable()
                ->after('unique_payment_amount_id')
                ->constrained('sms_payment_notifications')
                ->nullOnDelete();

            // SMS verification status
            $table->enum('sms_verification_status', ['pending', 'matched', 'confirmed', 'rejected', 'timeout'])
                ->default('pending')
                ->after('sms_notification_id');

            // Timestamp when SMS was verified
            $table->timestamp('sms_verified_at')
                ->nullable()
                ->after('sms_verification_status');

            // Display amount (unique amount shown to customer)
            $table->decimal('payment_display_amount', 15, 2)
                ->nullable()
                ->after('sms_verified_at');

            // Add indexes
            $table->index('sms_verification_status');
            $table->index('unique_payment_amount_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['sms_verification_status']);
            $table->dropIndex(['unique_payment_amount_id']);
            $table->dropForeign(['unique_payment_amount_id']);
            $table->dropForeign(['sms_notification_id']);
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
