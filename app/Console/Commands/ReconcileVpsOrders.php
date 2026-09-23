<?php

namespace App\Console\Commands;

use App\Models\VpsInstance;
use App\Models\VpsPayment;
use App\Services\HostingerApiService;
use App\Services\VpsProvisioningService;
use App\Support\Alerts\BusinessAlerts;
use Illuminate\Console\Command;

/**
 * Finish every VPS order that did not settle in the request that took the
 * money: install machines bought with a 202, adopt ones whose purchase timed
 * out, refund orders that never reached the supplier, and settle renewals
 * whose answer never came back.
 *
 * A machine the supplier took OUR money for is never refunded on a timer —
 * see VpsProvisioningService. It is flagged for a person instead.
 */
class ReconcileVpsOrders extends Command
{
    protected $signature = 'vps:reconcile
                            {--id= : Reconcile one instance by id}
                            {--dry : Report without changing anything}';

    protected $description = 'Install, adopt or refund VPS orders left in progress';

    protected const POLL_BACKOFF_MINUTES = 2;

    public function handle(HostingerApiService $api, VpsProvisioningService $provisioning): int
    {
        if (! $api->isConfigured()) {
            $this->warn('No supplier API token configured — nothing to reconcile.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry');

        // Least recently looked at first, so a handful of stuck orders cannot
        // take every run while a new one waits behind them.
        $query = VpsInstance::unsettled()
            ->orderByRaw('last_polled_at IS NOT NULL')
            ->orderBy('last_polled_at')
            ->orderBy('created_at');

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } else {
            $query->where(fn ($q) => $q->whereNull('last_polled_at')
                ->orWhere('last_polled_at', '<=', now()->subMinutes(self::POLL_BACKOFF_MINUTES)));
        }

        $instances = $query->limit(20)->get();

        foreach ($instances as $instance) {
            $this->line(sprintf('#%d %s · %s · %d min old', $instance->id, str_pad($instance->hostname, 32), $instance->status, (int) $instance->created_at->diffInMinutes(now())));

            if ($dry) {
                continue;
            }

            try {
                $this->line('  → ' . $provisioning->reconcile($instance));
            } catch (\Throwable $e) {
                report($e);
                $this->error('  → error: ' . $e->getMessage());
            }
        }

        // Renewals whose outcome we never heard: did the paid-until date move?
        $renewals = VpsPayment::with('instance')
            ->where('kind', VpsPayment::KIND_RENEW)
            ->where('status', VpsPayment::STATUS_PENDING)
            ->where('created_at', '<=', now()->subMinutes(10))
            ->limit(20)
            ->get();

        foreach ($renewals as $payment) {
            $this->line(sprintf('renewal #%d for instance #%d', $payment->id, $payment->vps_instance_id));

            if ($dry) {
                continue;
            }

            $outcome = $provisioning->settleRenewal($payment);
            $this->line('  → ' . $outcome);

            // Nothing is decided while upstream will not answer — but the
            // customer has paid, and a whole window of silence needs a person.
            if ($outcome === 'unknown' && $payment->instance
                && $payment->created_at->diffInMinutes(now()) >= VpsProvisioningService::UNKNOWN_OUTCOME_MINUTES) {
                BusinessAlerts::vpsNeedsAttention($payment->instance, 'ต่ออายุแล้วไม่ทราบผล และอ่านข้อมูลการต่ออายุจากผู้ให้บริการไม่ได้ — ลูกค้าจ่ายแล้ว ยังไม่คืนเงิน', false);
            }
        }

        if ($instances->isEmpty() && $renewals->isEmpty()) {
            $this->info('Nothing to reconcile.');
        }

        return self::SUCCESS;
    }
}
