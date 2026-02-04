<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\SmsCheckerDevice;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SmsCheckerStatusCommand extends Command
{
    protected $signature = 'smschecker:status';

    protected $description = 'Show SMS Checker system status and statistics';

    public function handle(): int
    {
        $this->info('');
        $this->info('📱 SMS Checker System Status');
        $this->info('============================');
        $this->info('');

        // Device Statistics
        $this->info('🔌 Devices:');
        $devices = SmsCheckerDevice::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->table(['Status', 'Count'], [
            ['Active', $devices['active'] ?? 0],
            ['Inactive', $devices['inactive'] ?? 0],
            ['Blocked', $devices['blocked'] ?? 0],
            ['Total', array_sum($devices)],
        ]);
        $this->info('');

        // Notification Statistics
        $this->info('📬 Notifications (Last 7 days):');
        $notifications = SmsPaymentNotification::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->table(['Status', 'Count'], [
            ['Pending', $notifications['pending'] ?? 0],
            ['Matched', $notifications['matched'] ?? 0],
            ['Confirmed', $notifications['confirmed'] ?? 0],
            ['Rejected', $notifications['rejected'] ?? 0],
            ['Expired', $notifications['expired'] ?? 0],
            ['Total', array_sum($notifications)],
        ]);
        $this->info('');

        // Unique Amount Statistics
        $this->info('💰 Unique Payment Amounts:');
        $amounts = UniquePaymentAmount::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $this->table(['Status', 'Count'], [
            ['Reserved (Active)', UniquePaymentAmount::active()->count()],
            ['Used', $amounts['used'] ?? 0],
            ['Expired', $amounts['expired'] ?? 0],
            ['Cancelled', $amounts['cancelled'] ?? 0],
        ]);
        $this->info('');

        // Order Statistics (SMS-based)
        $this->info('📋 SMS Payment Orders (Last 7 days):');
        $orders = Order::whereNotNull('unique_payment_amount_id')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('sms_verification_status, count(*) as count, sum(total) as total_amount')
            ->groupBy('sms_verification_status')
            ->get();

        $orderTable = [];
        foreach ($orders as $order) {
            $orderTable[] = [
                $order->sms_verification_status ?? 'pending',
                $order->count,
                '฿' . number_format($order->total_amount, 2),
            ];
        }
        $this->table(['Status', 'Count', 'Total Amount'], $orderTable);
        $this->info('');

        // Recent Activity
        $this->info('🕐 Recent Notifications:');
        $recent = SmsPaymentNotification::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['bank', 'amount', 'status', 'created_at']);

        $recentTable = [];
        foreach ($recent as $n) {
            $recentTable[] = [
                $n->bank,
                '฿' . number_format($n->amount, 2),
                $n->status,
                $n->created_at->diffForHumans(),
            ];
        }
        if (count($recentTable) > 0) {
            $this->table(['Bank', 'Amount', 'Status', 'Time'], $recentTable);
        } else {
            $this->line('  No recent notifications');
        }
        $this->info('');

        // Cleanup Recommendation
        $expiredNonces = DB::table('sms_payment_nonces')
            ->where('used_at', '<', now()->subHours(config('smschecker.nonce_expiry_hours', 24)))
            ->count();

        if ($expiredNonces > 0) {
            $this->warn("⚠️  {$expiredNonces} expired nonces can be cleaned up. Run: php artisan smschecker:cleanup");
        }

        return self::SUCCESS;
    }
}
