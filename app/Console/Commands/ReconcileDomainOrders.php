<?php

namespace App\Console\Commands;

use App\Models\DomainRegistration;
use App\Services\DomainRegistrarService;
use App\Services\HostingerApiService;
use App\Support\Alerts\BusinessAlerts;
use Illuminate\Console\Command;

/**
 * Finish, or refund, every domain order that did not settle in one request.
 *
 * What leaves an order hanging, and what happens to it here:
 *
 *   `pending`      — we took the money and the process died, or the purchase
 *                    call timed out. It MAY have reached the registrar, so the
 *                    portfolio is checked first; only a domain that is not
 *                    there after the timeout is refunded. (Refunding without
 *                    looking hands out the domain free when it did go through.)
 *   `registering`  — the registrar accepted, often with a 202. When its
 *                    payment clears the domain appears as `pending_setup`:
 *                    paid for, registered to nobody, until setup is called.
 *                    That call is made here, with the customer's own contacts.
 *   renewals       — a renewal whose answer never came back is settled by
 *                    comparing the registrar's expiry date with ours.
 *
 * Without this command, a customer whose purchase hit a network blip is left
 * with a charge and no domain, and nobody finds out until they complain.
 *
 * Safe to run every few minutes: it only touches unsettled rows, it backs
 * off per row via last_polled_at, and every refund goes through the same
 * guarded path that will not pay twice.
 */
class ReconcileDomainOrders extends Command
{
    protected $signature = 'domains:reconcile
                            {--id= : Reconcile one registration by id}
                            {--dry : Report without changing anything}';

    protected $description = 'Settle or refund domain registrations left in progress';

    /** Wait this long between polls of the same row. */
    protected const POLL_BACKOFF_MINUTES = 3;

    /** Setup calls per order before a person is asked to look. */
    protected const MAX_SETUP_ATTEMPTS = 3;

    /** A pending row younger than this may still be mid-purchase in its own request. */
    protected const PENDING_GRACE_MINUTES = 5;

    public function handle(HostingerApiService $api, DomainRegistrarService $registrar): int
    {
        if (! $api->isConfigured()) {
            // Runs every five minutes. Before a token is pasted in, FAILURE here
            // meant an ERROR line in the production log 288 times a day saying
            // nothing more than "not set up yet".
            $this->warn('No registrar API token configured — nothing to reconcile.');

            return self::SUCCESS;
        }

        $query = DomainRegistration::unsettled();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } else {
            // Leave rows alone that were polled a moment ago — the upstream
            // budget is 90 calls a minute for the whole site.
            $query->where(function ($q) {
                $q->whereNull('last_polled_at')
                    ->orWhere('last_polled_at', '<=', now()->subMinutes(self::POLL_BACKOFF_MINUTES));
            });
        }

        // Least recently looked at first. Oldest-first let the same thirty
        // stuck rows take every run while a new order behind them waited.
        $rows = $query->orderByRaw('last_polled_at IS NOT NULL')
            ->orderBy('last_polled_at')
            ->orderBy('created_at')
            ->limit(30)
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Nothing to reconcile.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry');
        $settled = $refunded = $stillWaiting = 0;

        foreach ($rows as $row) {
            $age = (int) $row->created_at->diffInMinutes(now());
            $expired = $age >= DomainRegistrarService::settleWindowMinutes($row);

            $this->line(sprintf(
                '#%d %s · %s%s · %d min old%s',
                $row->id,
                str_pad($row->domain, 28),
                $row->status,
                $row->kind === DomainRegistration::KIND_RENEW ? ' (renewal)' : '',
                $age,
                $expired ? ' · PAST TIMEOUT' : '',
            ));

            if ($dry) {
                continue;
            }

            $row->update(['last_polled_at' => now(), 'poll_attempts' => $row->poll_attempts + 1]);

            // A renewal left pending: did the registrar's expiry date move?
            if ($row->kind === DomainRegistration::KIND_RENEW) {
                $outcome = $registrar->settleRenewal($row);
                $this->line('  → renewal ' . $outcome);
                $outcome === 'confirmed' ? $settled++ : ($outcome === 'refunded' ? $refunded++ : $stillWaiting++);

                if ($outcome === 'unknown' && $expired) {
                    // The registrar has not answered for the whole window. The
                    // money stays where it is until somebody looks.
                    BusinessAlerts::domainNeedsAttention($row, 'ต่ออายุแล้วไม่ทราบผล และอ่านวันหมดอายุจากผู้ให้บริการไม่ได้มา ' . $age . ' นาที — ลูกค้าจ่ายแล้ว ยังไม่คืนเงิน');
                }

                continue;
            }

            $outcome = $this->settleRegistration($row, $registrar, $expired);
            $this->line('  → ' . $outcome);

            match ($outcome) {
                'active' => $settled++,
                'refunded' => $refunded++,
                default => $stillWaiting++,
            };
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d settled, %d refunded, %d still waiting.',
            $dry ? '[dry run]' : 'Done:',
            $settled,
            $refunded,
            $stillWaiting,
        ));

        return self::SUCCESS;
    }

    /**
     * One registration: look it up in our portfolio and move it on.
     */
    protected function settleRegistration(DomainRegistration $row, DomainRegistrarService $registrar, bool $expired): string
    {
        // Both pending and registering rows are looked up. A pending row can
        // be a purchase whose answer we never heard — if the domain is in our
        // portfolio, it went through, and refunding it would give it away.
        if ($row->poll_attempts > self::MAX_SETUP_ATTEMPTS * 4 && $row->last_error && str_starts_with($row->last_error, 'setup rejected')) {
            // The registrar keeps refusing the setup (usually registrant data
            // it will not accept). Money has left us; a person has to decide —
            // and has to hear about it, or the order sits here for ever.
            BusinessAlerts::domainNeedsAttention($row, 'ผู้ให้บริการปฏิเสธการจดให้เสร็จซ้ำหลายครั้ง: ' . mb_substr($row->last_error, 0, 200));

            return 'needs attention: ' . mb_substr($row->last_error, 0, 120);
        }

        // A fresh pending row is usually a purchase still in flight in the
        // request that took the money; settling it from here too would run
        // activation (and upstream calls) twice. Give it a few minutes.
        if ($row->status === DomainRegistration::STATUS_PENDING
            && $row->created_at->diffInMinutes(now()) < self::PENDING_GRACE_MINUTES) {
            return 'waiting';
        }

        $outcome = $registrar->settle($row);

        if ($outcome === 'active') {
            return 'active';
        }

        if ($outcome === 'ambiguous') {
            $rival = $registrar->rivalOrderFor($row);

            $row->update(['last_error' => 'ambiguous: another order for this name (#' . ($rival?->id ?? '?') . ') is live or settling — decide by hand']);

            BusinessAlerts::domainNeedsAttention($row, sprintf(
                'มีอีกรายการของชื่อเดียวกัน (#%d, %s) — ระบบบอกไม่ได้ว่าโดเมนในบัญชีเป็นของรายการไหน จึงไม่เปิดใช้และไม่คืนเงินให้อัตโนมัติ',
                $rival?->id ?? 0,
                $rival?->status ?? '?',
            ));

            return 'ambiguous';
        }

        if ($outcome === 'unknown') {
            // Could not ask. Nothing is decided on silence — but a whole
            // window of it is somebody's problem.
            if ($expired) {
                BusinessAlerts::domainNeedsAttention($row, 'อ่านสถานะโดเมนจากผู้ให้บริการไม่ได้มา ' . $row->created_at->diffInMinutes(now()) . ' นาที — ลูกค้าจ่ายแล้ว ยังไม่คืนเงิน');
            }

            return 'unknown';
        }

        if (in_array($outcome, ['setup-sent', 'setup-failed', 'waiting'], true)) {
            // It exists upstream, so it was paid for: never refund on a timer
            // from here. The setup is retried on the next run.
            if ($row->status === DomainRegistration::STATUS_PENDING) {
                $row->update(['status' => DomainRegistration::STATUS_REGISTERING]);
            }

            return $outcome;
        }

        // 'missing' (the portfolio was read and the name is not in it) or
        // 'gone' (deleted/expired upstream).
        if ($expired) {
            $registrar->failAndRefund(
                $row,
                $outcome === 'gone'
                    ? 'the domain shows as deleted/expired upstream before it was ever registered'
                    : 'no domain appeared in the portfolio within the settle window',
            );

            return 'refunded';
        }

        return 'waiting';
    }
}
