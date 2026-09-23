<?php

namespace App\Console\Commands;

use App\Mail\VpsExpiryMail;
use App\Mail\VpsRenewalNoticeMail;
use App\Models\VpsInstance;
use App\Models\VpsPayment;
use App\Models\Wallet;
use App\Services\HostingerApiService;
use App\Services\VpsOrderException;
use App\Services\VpsProvisioningService;
use App\Support\Alerts\BusinessAlerts;
use App\Support\UpstreamBilling;
use App\Support\VpsSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Keep every rented server paid for — or make sure its owner knows it is not.
 *
 * Four passes, daily:
 *
 *   notice   — auto-renew on: say what will be charged, when, and whether the
 *              wallet covers it. Once per period.
 *   charge   — auto-renew on, notice out long enough to be read: renew from
 *              the wallet. A short wallet is not a failure; it is skipped,
 *              said out loud once, and tried again tomorrow.
 *   remind   — auto-renew off: a server that is about to stop, and whose data
 *              goes with it, gets two e-mails (a week and a day out).
 *   expire   — past its date: ask upstream what it is now and mark it, so the
 *              page offers "renew" instead of power buttons that would fail.
 */
class RenewVps extends Command
{
    protected $signature = 'vps:renew
                            {--id= : Only this instance}
                            {--dry : Say what would happen without doing it}';

    protected $description = 'Warn about, charge, remind and expire VPS rentals';

    /** Days before expiry when a server that will NOT renew gets a reminder. */
    protected const REMINDER_DAYS = [7, 1];

    public function handle(HostingerApiService $api, VpsProvisioningService $provisioning): int
    {
        $dry = (bool) $this->option('dry');

        $notice = VpsSettings::noticeDays();
        $charge = VpsSettings::chargeDays();
        $lead = VpsSettings::leadDays();

        $base = VpsInstance::query()
            ->with(['user', 'plan'])
            ->when($this->option('id'), fn ($q, $id) => $q->where('id', $id));

        $noticed = $renewed = $short = $failed = $reminded = $expired = 0;
        $shortList = [];

        // ---------------------------------------------------------- notice
        $due = (clone $base)->where('status', VpsInstance::STATUS_ACTIVE)
            ->where('auto_renew', true)
            ->whereNull('renewal_notice_sent_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($notice))
            ->get();

        foreach ($due as $server) {
            $price = $server->plan?->renewPriceThb((string) $server->period) ?? 0.0;

            if ($price <= 0 || ! $server->user?->email) {
                continue;
            }

            $this->line(sprintf('  แจ้ง %s — %s บาท', $server->hostname, number_format($price)));

            if ($dry) {
                $noticed++;

                continue;
            }

            try {
                Mail::to($server->user->email)->send(new VpsRenewalNoticeMail($server, $price, $this->balance($server)));
                $server->update(['renewal_notice_sent_at' => now()]);
                $noticed++;
            } catch (\Throwable $e) {
                Log::error('[VPS] renewal notice failed', ['instance' => $server->id, 'error' => $e->getMessage()]);
            }
        }

        // ---------------------------------------------------------- charge
        // Paused = our card was just refused: every renewal would be debited
        // and refunded. The charge day is days before expiry — skip today.
        if (UpstreamBilling::isPaused()) {
            $this->warn('Supplier payments are paused — renewals skipped today.');
        } elseif ($api->isConfigured()) {
            $chargeable = (clone $base)->whereIn('status', [VpsInstance::STATUS_ACTIVE, VpsInstance::STATUS_EXPIRED])
                ->where('auto_renew', true)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()->addDays($charge))
                // Charge an expired one too — it is still savable, and the
                // customer asked for automatic renewal.
                ->where('expires_at', '>', now()->subDays(7))
                ->whereNotNull('renewal_notice_sent_at')
                ->where('renewal_notice_sent_at', '<=', now()->subDays($lead))
                ->get();

            foreach ($chargeable as $server) {
                if (! $server->canRenew()) {
                    continue;
                }

                $price = $server->plan?->renewPriceThb((string) $server->period) ?? 0.0;

                if ($price <= 0) {
                    continue;
                }

                if ($this->balance($server) < $price) {
                    $short++;
                    $this->line(sprintf('  เงินไม่พอ %s — ต้อง %s', $server->hostname, number_format($price)));

                    $shortList[] = [
                        'what' => 'vps',
                        'name' => $server->hostname,
                        'owner' => (string) ($server->user?->name ?? $server->user?->email ?? '#' . $server->user_id),
                        'need' => $price,
                        'have' => $this->balance($server),
                        'expires' => $server->expires_at?->format('d/m/Y'),
                    ];

                    // Said once per period, not every morning.
                    if (! $dry && ! $this->reminded($server, 'short')) {
                        $this->mail($server, 'short', $price);
                    }

                    continue;
                }

                $this->line(sprintf('  ต่ออายุ %s — %s บาท', $server->hostname, number_format($price)));

                if ($dry) {
                    $renewed++;

                    continue;
                }

                try {
                    $payment = $provisioning->renew($server);
                    $payment->status === VpsPayment::STATUS_REFUNDED ? $failed++ : $renewed++;
                } catch (VpsOrderException $e) {
                    $failed++;
                    $this->warn('  ' . $server->hostname . ' — ' . $e->getMessage());
                } catch (\Throwable $e) {
                    $failed++;
                    report($e);
                }
            }

            // The team hears about short wallets too — once a day, as one card.
            if (! $dry) {
                BusinessAlerts::renewalsShortOfFunds('vps', $shortList);
            }
        }

        // ---------------------------------------------------------- remind
        $lapsing = (clone $base)->where('status', VpsInstance::STATUS_ACTIVE)
            ->where('auto_renew', false)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(max(self::REMINDER_DAYS)))
            ->get();

        foreach ($lapsing as $server) {
            $days = (int) $server->daysUntilExpiry();

            foreach (self::REMINDER_DAYS as $milestone) {
                if ($days <= $milestone && ! $this->reminded($server, 'lapse-' . $milestone)) {
                    $this->line(sprintf('  เตือน %s — เหลือ %d วัน', $server->hostname, $days));

                    if (! $dry) {
                        $price = $server->plan?->renewPriceThb((string) $server->period) ?? 0.0;
                        $this->mail($server, 'lapse-' . $milestone, $price);

                        // Retire the wider milestones too, so a server first
                        // seen at 1 day does not get the 7-day mail tomorrow.
                        foreach (self::REMINDER_DAYS as $other) {
                            if ($other >= $milestone) {
                                $this->markReminded($server, 'lapse-' . $other);
                            }
                        }
                    }

                    $reminded++;

                    break;
                }
            }
        }

        // ---------------------------------------------------------- expire
        $past = (clone $base)->where('status', VpsInstance::STATUS_ACTIVE)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($past as $server) {
            // Ask upstream before believing our own date: a renewal we did not
            // see (made in hPanel) would otherwise suspend a paid-up server on
            // the customer's page.
            if (! $dry && $api->isConfigured()) {
                $provisioning->refreshSubscription($server);
                $server->refresh();
            }

            if ($server->expires_at && $server->expires_at->isFuture()) {
                continue;
            }

            $this->warn(sprintf('  หมดอายุ %s', $server->hostname));
            $expired++;

            if (! $dry) {
                $server->update(['status' => VpsInstance::STATUS_EXPIRED]);
                $this->mail($server, 'expired', $server->plan?->renewPriceThb((string) $server->period) ?? 0.0);
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s แจ้ง %d · ต่ออายุ %d · เงินไม่พอ %d · ล้มเหลว %d · เตือนเครื่องที่ไม่ต่อ %d · หมดอายุ %d',
            $dry ? '[dry run]' : 'เสร็จ:',
            $noticed,
            $renewed,
            $short,
            $failed,
            $reminded,
            $expired,
        ));

        return self::SUCCESS;
    }

    protected function balance(VpsInstance $server): float
    {
        return (float) (Wallet::getOrCreateForUser($server->user_id)->balance ?? 0);
    }

    protected function reminded(VpsInstance $server, string $key): bool
    {
        return in_array($key, (array) ($server->reminders_sent ?? []), true);
    }

    protected function markReminded(VpsInstance $server, string $key): void
    {
        $sent = array_values(array_unique([...((array) ($server->reminders_sent ?? [])), $key]));
        $server->update(['reminders_sent' => $sent]);
    }

    /** Send one of the expiry e-mails, and record it only once it has left. */
    protected function mail(VpsInstance $server, string $kind, float $price): void
    {
        if (! $server->user?->email) {
            return;
        }

        try {
            Mail::to($server->user->email)->send(new VpsExpiryMail($server, $kind, $price, $this->balance($server)));
            $this->markReminded($server, $kind);
        } catch (\Throwable $e) {
            Log::error('[VPS] expiry mail failed', ['instance' => $server->id, 'kind' => $kind, 'error' => $e->getMessage()]);
        }
    }
}
