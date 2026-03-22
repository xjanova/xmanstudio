<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Services\SmsPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * ทำความสะอาดข้อมูล SMS Payment ที่หมดอายุ
 *
 * ทำงานอัตโนมัติทุก 5 นาที:
 * - ยกเลิก Orders ที่หมดเวลาชำระ (30 นาที)
 * - ล้าง unique amounts ที่หมดอายุ
 * - ล้าง nonces เก่า
 * - ล้าง notifications ที่ pending นานเกินไป
 */
class SmsCheckerCleanupCommand extends Command
{
    protected $signature = 'smschecker:cleanup
                            {--dry-run : Show what would be cleaned without actually doing it}';

    protected $description = 'Clean up expired SMS payment data and auto-cancel unpaid orders (30 min timeout)';

    public function handle(SmsPaymentService $service): int
    {
        $this->info('');
        $this->info('🧹 SMS Checker Cleanup');
        $this->info('======================');
        $this->info('');

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No data will be modified');
            $this->info('');

            // Show what would be cleaned
            $expiredAmounts = UniquePaymentAmount::where('status', 'reserved')
                ->where('expires_at', '<=', now())
                ->count();

            // Count orders that would be cancelled
            $expiredOrders = Order::whereHas('uniquePaymentAmount', function ($q) {
                $q->where('status', 'reserved')
                    ->where('expires_at', '<=', now());
            })
                ->where('status', 'pending')
                ->where('payment_status', '!=', 'paid')
                ->count();

            $oldNonces = DB::table('sms_payment_nonces')
                ->where('used_at', '<', now()->subHours(config('smschecker.nonce_expiry_hours', 24)))
                ->count();

            $notifDays = (int) config('smschecker.retention.notification_days', 30);
            $oldNotifications = SmsPaymentNotification::where('status', 'pending')
                ->where('created_at', '<', now()->subDays($notifDays))
                ->count();

            $bugDays = (int) config('smschecker.retention.bug_report_days', 90);
            $oldBugReports = DB::table('bug_reports')
                ->where('created_at', '<', now()->subDays($bugDays))
                ->count();

            $amountDays = (int) config('smschecker.retention.completed_amount_days', 60);
            $oldAmounts = UniquePaymentAmount::whereIn('status', ['used', 'expired'])
                ->where('created_at', '<', now()->subDays($amountDays))
                ->count();

            $this->info("📊 Would cancel {$expiredOrders} orders (payment timeout)");
            $this->info("📊 Would expire {$expiredAmounts} unique payment amounts");
            $this->info("📊 Would delete {$oldNonces} old nonces");
            $this->info("📊 Would expire {$oldNotifications} old pending notifications (>{$notifDays} days)");
            $this->info("📊 Would delete {$oldBugReports} old bug reports (>{$bugDays} days)");
            $this->info("📊 Would delete {$oldAmounts} old completed amounts (>{$amountDays} days)");

            return self::SUCCESS;
        }

        $stats = $service->cleanup();

        $this->table(['Item', 'Count'], [
            ['Cancelled Orders (timeout)', $stats['cancelled_orders']],
            ['Cancelled Topups (timeout)', $stats['cancelled_topups'] ?? 0],
            ['Expired Amounts', $stats['expired_amounts']],
            ['Deleted Nonces', $stats['deleted_nonces']],
            ['Expired Notifications', $stats['expired_notifications']],
            ['Deleted Bug Reports', $stats['deleted_bug_reports'] ?? 0],
            ['Deleted Old Amounts', $stats['deleted_old_amounts'] ?? 0],
        ]);

        $this->info('');
        $this->info('✅ Cleanup completed successfully!');

        return self::SUCCESS;
    }
}
