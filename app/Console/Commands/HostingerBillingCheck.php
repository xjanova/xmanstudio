<?php

namespace App\Console\Commands;

use App\Models\DomainRegistration;
use App\Models\VpsInstance;
use App\Services\HostingerApiService;
use App\Support\Alerts\BusinessAlerts;
use App\Support\UpstreamBilling;
use Illuminate\Console\Command;

/**
 * Can we still pay our supplier — and is it charging us for anything it should not?
 *
 * Two checks, both about our own money at Hostinger:
 *
 *   payment methods — the card behind every domain and VPS we sell. Expired,
 *                     suspended, missing, or about to expire is found HERE,
 *                     days ahead, instead of from the first customer's refused
 *                     order. A critical finding pauses sales (nothing would go
 *                     through anyway) and the next healthy check lifts it.
 *
 *   auto-renewal    — every subscription we resell renews from the customer's
 *                     wallet, never from our card. One left on upstream is a
 *                     charge to us at expiry: double for a customer who renews
 *                     with us, and a free year for one who let it lapse. Any
 *                     found on is switched off and counted.
 *
 * The API has no balance figure, so "insufficient funds" on the card itself
 * can still only show up as a refused charge — that path pauses sales and
 * pages the admin on its own (see UpstreamBilling).
 */
class HostingerBillingCheck extends Command
{
    protected $signature = 'hostinger:billing-check
                            {--quiet-ok : Do not print anything when all is well}';

    protected $description = 'Check the supplier payment method and switch off stray auto-renewals';

    public function handle(HostingerApiService $api): int
    {
        if (! $api->isConfigured()) {
            // Not set up yet is not a failure worth an ERROR line every run.
            $this->warn('No supplier API token configured — nothing to check.');

            return self::SUCCESS;
        }

        $health = UpstreamBilling::check($api);

        foreach ($health['methods'] as $m) {
            $this->line(sprintf(
                '  %s%s · %s%s%s',
                UpstreamBilling::label($m['type']),
                $m['is_default'] ? ' (ค่าเริ่มต้น)' : '',
                $m['expires_at'] ? 'หมดอายุ ' . substr($m['expires_at'], 0, 10) : 'ไม่มีวันหมดอายุ',
                $m['is_expired'] ? ' · หมดอายุแล้ว' : '',
                $m['is_suspended'] ? ' · ถูกระงับ' : '',
            ));
        }

        $pause = UpstreamBilling::paused();

        if ($health['level'] === 'critical') {
            $this->error('Payment method: ' . implode(' / ', $health['problems']));
            UpstreamBilling::pause(implode(' / ', $health['problems']), 'health');
            BusinessAlerts::upstreamBillingProblem($health, true);
        } elseif ($health['level'] === 'warning') {
            $this->warn('Payment method: ' . implode(' / ', $health['problems']));
            BusinessAlerts::upstreamBillingProblem($health, false);
        } elseif ($health['level'] === 'ok') {
            // A pause this check put in place is lifted by this check. One set
            // by a refused charge is not: a declined card looks perfectly
            // healthy from the payment-method list.
            if ($pause && ($pause['source'] ?? '') === 'health') {
                UpstreamBilling::resume();
                $this->info('Payment method healthy again — sales resumed.');
            }

            if (! $this->option('quiet-ok')) {
                $this->info('Payment method OK.');
            }
        } else {
            // 'unknown': the API did not answer. Say so, change nothing.
            $this->warn('Could not read payment methods: ' . implode(' / ', $health['problems']));
        }

        $this->switchOffStrayAutoRenewals($api);
        $this->reportOrphanMachines($api);

        return self::SUCCESS;
    }

    /**
     * Machines bought and never installed that no order claims.
     *
     * The one way we end up paying for a server nobody rents: a purchase
     * whose answer arrives after the order was given up on and refunded. It
     * is never touched from here — the owner's own servers share the account,
     * and one they are still setting up looks exactly like this — only
     * reported, once a day, with what hPanel needs to find it.
     */
    protected function reportOrphanMachines(HostingerApiService $api): void
    {
        if (! VpsInstance::query()->exists()) {
            // Nothing was ever sold: every machine in the account is the owner's.
            return;
        }

        $vms = $api->listVirtualMachines();

        if (! is_array($vms)) {
            return;
        }

        $claimedVms = VpsInstance::whereNotNull('remote_vm_id')->pluck('remote_vm_id')->map(fn ($id) => (int) $id)->all();
        $claimedSubscriptions = VpsInstance::whereNotNull('remote_subscription_id')->pluck('remote_subscription_id')->map(fn ($id) => (string) $id)->all();
        $firstSale = VpsInstance::min('created_at');

        $orphans = [];

        foreach ($vms as $vm) {
            if (! is_array($vm) || ($vm['state'] ?? null) !== 'initial' || ! isset($vm['id'])) {
                continue;
            }

            if (in_array((int) $vm['id'], $claimedVms, true)
                || (! empty($vm['subscription_id']) && in_array((string) $vm['subscription_id'], $claimedSubscriptions, true))) {
                continue;
            }

            // Older than our first sale: the owner's, whatever state it is in.
            if ($firstSale && isset($vm['created_at']) && strtotime((string) $vm['created_at']) < strtotime((string) $firstSale)) {
                continue;
            }

            // Still inside the window a live order may be about to claim it.
            if (isset($vm['created_at']) && strtotime((string) $vm['created_at']) > now()->subHours(3)->getTimestamp()) {
                continue;
            }

            $orphans[] = '#' . $vm['id'] . (isset($vm['plan']) ? ' (' . $vm['plan'] . ')' : '');
        }

        if ($orphans !== []) {
            $this->warn('Bought, never installed, claimed by no order: ' . implode(', ', $orphans));
            BusinessAlerts::orphanMachines($orphans);
        }
    }

    /**
     * Turn off upstream auto-renewal on every subscription we resell.
     *
     * Only subscriptions linked to a domain or VPS a customer bought from us
     * are touched: the owner's own services live in the same account, and
     * switching THEIR auto-renewal off would let the company's own domain lapse.
     */
    protected function switchOffStrayAutoRenewals(HostingerApiService $api): void
    {
        // Every status, refunded included: an order refunded AFTER upstream
        // took it (an admin refund, a machine that never built) is still a
        // subscription on our card — the one nobody is paying us for.
        $ours = DomainRegistration::whereNotNull('remote_subscription_id')
            ->pluck('remote_subscription_id')
            ->merge(
                VpsInstance::whereNotNull('remote_subscription_id')
                    ->pluck('remote_subscription_id')
            )
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->all();

        if ($ours === []) {
            return;
        }

        $subscriptions = $api->getSubscriptions();

        if (! is_array($subscriptions)) {
            $this->warn('Could not read subscriptions — auto-renewal not checked.');

            return;
        }

        $switched = [];

        foreach ($subscriptions as $row) {
            if (! is_array($row) || ! in_array((string) ($row['id'] ?? ''), $ours, true)) {
                continue;
            }

            if (! ($row['is_auto_renewed'] ?? false)) {
                continue;
            }

            if ($api->setAutoRenewal((string) $row['id'], false)) {
                $switched[] = (string) ($row['name'] ?? $row['id']);
            }
        }

        if ($switched !== []) {
            $this->warn(sprintf('Switched off upstream auto-renewal on %d resold subscription(s): %s', count($switched), implode(', ', $switched)));
            BusinessAlerts::strayAutoRenewalsSwitchedOff($switched);
        }
    }
}
