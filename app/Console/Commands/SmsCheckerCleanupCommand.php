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

            $oldNotifications = SmsPaymentNotification::where('status', 'pending')
                ->where('created_at', '<', now()->subDays(7))
                ->count();

            $this->info("📊 Would cancel {$expiredOrders} orders (payment timeout)");
            $this->info("📊 Would expire {$expiredAmounts} unique payment amounts");
            $this->info("📊 Would delete {$oldNonces} old nonces");
            $this->info("📊 Would expire {$oldNotifications} old pending notifications");

            return self::SUCCESS;
        }

        $stats = $service->cleanup();

        $this->table(['Item', 'Count'], [
            ['Cancelled Orders (timeout)', $stats['cancelled_orders']],
            ['Expired Amounts', $stats['expired_amounts']],
            ['Deleted Nonces', $stats['deleted_nonces']],
            ['Expired Notifications', $stats['expired_notifications']],
        ]);

        $this->info('');
        $this->info('✅ Cleanup completed successfully!');

        return self::SUCCESS;
    }
}
