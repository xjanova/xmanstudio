<?php

namespace App\Console\Commands;

use App\Models\DomainRegistration;
use App\Services\DomainRegistrarService;
use App\Services\HostingerApiService;
use Illuminate\Console\Command;

/**
 * Finish, or refund, every domain order that did not settle in one request.
 *
 * Three things leave an order hanging:
 *
 *   `pending`      — we took the money and the process died before the
 *                    registrar was called. Nothing was bought; refund.
 *   `registering`  — the registrar accepted but answered 202, meaning the
 *                    payment was still clearing on their side. Poll until
 *                    the domain shows up in our portfolio.
 *   either, old    — past the timeout with no resolution. The customer has
 *                    waited long enough; give the money back and let them
 *                    try again.
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

    public function handle(HostingerApiService $api, DomainRegistrarService $registrar): int
    {
        if (! $api->isConfigured()) {
            $this->error('No registrar API token configured.');

            return self::FAILURE;
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

        $rows = $query->orderBy('created_at')->limit(30)->get();

        if ($rows->isEmpty()) {
            $this->info('Nothing to reconcile.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry');
        $settled = $refunded = $stillWaiting = 0;

        foreach ($rows as $row) {
            $age = $row->created_at->diffInMinutes(now());
            $expired = $age >= DomainRegistrarService::SETTLE_TIMEOUT_MINUTES;

            $this->line(sprintf(
                '#%d %s · %s · %d min old%s',
                $row->id,
                str_pad($row->domain, 28),
                $row->status,
                $age,
                $expired ? ' · PAST TIMEOUT' : '',
            ));

            if ($dry) {
                continue;
            }

            // Nothing was ever sent upstream for a pending row, so there is
            // nothing to look up — it is money taken for an order that never
            // left the building.
            if ($row->status === DomainRegistration::STATUS_PENDING) {
                if ($expired) {
                    $registrar->failAndRefund($row, 'pending past timeout; upstream was never called');
                    $this->warn('  → refunded (never reached the registrar)');
                    $refunded++;
                } else {
                    $row->update(['last_polled_at' => now(), 'poll_attempts' => $row->poll_attempts + 1]);
                    $stillWaiting++;
                }

                continue;
            }

            // registering: ask whether the domain exists in our portfolio yet.
            $details = $api->getDomain($row->domain);

            $row->update(['last_polled_at' => now(), 'poll_attempts' => $row->poll_attempts + 1]);

            if (is_array($details) && $details !== [] && $this->looksRegistered($details)) {
                $registrar->markActive($row);
                $this->info('  → now active');
                $settled++;

                continue;
            }

            if ($expired) {
                $registrar->failAndRefund($row, 'registration did not complete within the settle window');
                $this->warn('  → refunded (timed out)');
                $refunded++;

                continue;
            }

            $stillWaiting++;
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
     * Does this portfolio entry represent a domain that actually exists?
     *
     * Deliberately strict. Treating a half-populated response as success
     * marks a domain active that the customer does not own, and they will
     * find out when they try to use it.
     *
     * @param  array<string,mixed>  $details
     */
    protected function looksRegistered(array $details): bool
    {
        $status = strtolower((string) ($details['status'] ?? ''));

        if (in_array($status, ['active', 'registered', 'ok'], true)) {
            return true;
        }

        // Some responses carry no status but do carry an expiry, which only
        // a registered domain has.
        return ! empty($details['expires_at']) || ! empty($details['expire_date']);
    }
}
