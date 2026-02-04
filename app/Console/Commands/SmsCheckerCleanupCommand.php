<?php

namespace App\Console\Commands;

use App\Services\SmsPaymentService;
use Illuminate\Console\Command;

class SmsCheckerCleanupCommand extends Command
{
    protected $signature = 'smschecker:cleanup
                            {--dry-run : Show what would be cleaned without actually doing it}';

    protected $description = 'Clean up expired SMS payment data (nonces, amounts, notifications)';

    public function handle(SmsPaymentService $service): int
    {
        $this->info('');
        $this->info('🧹 SMS Checker Cleanup');
        $this->info('======================');
        $this->info('');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No data will be modified');
            $this->info('');
        }

        if ($this->option('dry-run')) {
            // Just show what would be cleaned
            $expiredAmounts = \App\Models\UniquePaymentAmount::where('status', 'reserved')
                ->where('expires_at', '<=', now())
                ->count();

            $oldNonces = \Illuminate\Support\Facades\DB::table('sms_payment_nonces')
                ->where('used_at', '<', now()->subHours(config('smschecker.nonce_expiry_hours', 24)))
                ->count();

            $oldNotifications = \App\Models\SmsPaymentNotification::where('status', 'pending')
                ->where('created_at', '<', now()->subDays(7))
                ->count();

            $this->info("Would expire {$expiredAmounts} unique payment amounts");
            $this->info("Would delete {$oldNonces} old nonces");
            $this->info("Would expire {$oldNotifications} old pending notifications");

            return self::SUCCESS;
        }

        $stats = $service->cleanup();

        $this->table(['Item', 'Count'], [
            ['Expired Amounts', $stats['expired_amounts']],
            ['Deleted Nonces', $stats['deleted_nonces']],
            ['Expired Notifications', $stats['expired_notifications']],
        ]);

        $this->info('');
        $this->info('✅ Cleanup completed successfully!');

        return self::SUCCESS;
    }
}
