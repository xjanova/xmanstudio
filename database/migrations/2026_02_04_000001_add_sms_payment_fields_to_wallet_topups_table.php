<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_topups', function (Blueprint $table) {
            // SMS Payment unique amount reference
            $table->foreignId('unique_payment_amount_id')
                ->nullable()
                ->after('payment_method')
                ->constrained('unique_payment_amounts')
                ->nullOnDelete();

            // Display amount (unique amount for SMS matching)
            $table->decimal('payment_display_amount', 12, 2)
                ->nullable()
                ->after('unique_payment_amount_id');

            // SMS notification that matched this topup
            $table->foreignId('sms_notification_id')
                ->nullable()
                ->after('payment_display_amount')
                ->constrained('sms_payment_notifications')
                ->nullOnDelete();

            // SMS verification status
            $table->enum('sms_verification_status', ['pending', 'matched', 'confirmed', 'timeout'])
                ->default('pending')
                ->after('sms_notification_id');

            // Timestamp when SMS was verified
            $table->timestamp('sms_verified_at')
                ->nullable()
                ->after('sms_verification_status');

            // Remove unused columns (payment_proof, payment_reference)
            // These are no longer needed since we use SMS auto-verification
            $table->dropColumn(['payment_proof', 'payment_reference']);

            // Index for SMS matching
            $table->index('sms_verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_topups', function (Blueprint $table) {
            // Re-add removed columns
            $table->text('payment_proof')->nullable()->after('payment_method');
            $table->string('payment_reference')->nullable()->after('payment_method');

            // Drop new columns
            $table->dropForeign(['unique_payment_amount_id']);
            $table->dropForeign(['sms_notification_id']);
            $table->dropIndex(['sms_verification_status']);

            $table->dropColumn([
                'unique_payment_amount_id',
                'payment_display_amount',
                'sms_notification_id',
                'sms_verification_status',
                'sms_verified_at',
            ]);
        });
    }
};
