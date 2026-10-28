<?php

namespace App\Console\Commands;

use App\Mail\LicenseRenewalMail;
use App\Models\LicenseKey;
use App\Models\Wallet;
use App\Services\LicenseRenewalException;
use App\Services\LicenseRenewalService;
use App\Support\Alerts\BusinessAlerts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Keep licenses running — or make sure their owners know they are about to stop.
 *
 * Three passes, daily:
 *
 *   notice  — auto-renew on: say what will be charged, when, and whether the wallet covers it.
 *             Once per period.
 *   charge  — auto-renew on and the key within CHARGE_DAYS of its expiry (or run out less than
 *             GRACE_DAYS ago): renew from the wallet. A short wallet is not a failure; it is said
 *             once, and tried again tomorrow.
 *   remind  — auto-renew off: two e-mails (a week and a day out) with the renew button.
 *
 * Only keys that can be renewed online are touched (LicenseRenewalService::offerFor) — a lifetime
 * or trial key, a product off sale, a term the store does not sell gets nothing.
 */
class RenewLicenses extends Command
{
    protected $signature = 'licenses:renew
                            {--id= : Only this license}
                            {--dry : Say what would happen without doing it}';

    protected $description = 'Warn about, auto-renew from the wallet, and remind licenses about to expire';

    public function handle(LicenseRenewalService $renewals): int
    {
        $dry = (bool) $this->option('dry');

        $base = LicenseKey::query()
            ->with(['user', 'product'])
            ->whereNotNull('user_id')
            ->whereNotNull('expires_at')
            ->when($this->option('id'), fn ($q, $id) => $q->whereKey($id));

        $noticed = $renewed = $short = $failed = $reminded = 0;
        $shortList = [];

        // ---------------------------------------------------------- notice
        (clone $base)->where('auto_renew', true)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where('expires_at', '>', now()->addDays(LicenseRenewalService::CHARGE_DAYS))
            ->where('expires_at', '<=', now()->addDays(LicenseRenewalService::NOTICE_DAYS))
            ->chunkById(200, function ($keys) use ($renewals, $dry, &$noticed) {
                foreach ($keys as $license) {
                    $offer = $renewals->offerFor($license, $license->user_id);

                    if (! $offer || LicenseRenewalService::mailSent($license, 'notice')) {
                        continue;
                    }

                    $this->line(sprintf('  แจ้ง #%d %s — %s บาท', $license->id, $license->product->name, number_format($offer['total'], 2)));
                    $noticed++;

                    if (! $dry) {
                        $this->mail($license, 'notice', $offer);
                    }
                }
            });

        // ---------------------------------------------------------- charge
        (clone $base)->where('auto_renew', true)
            ->whereIn('status', [LicenseKey::STATUS_ACTIVE, LicenseKey::STATUS_EXPIRED])
            ->where('expires_at', '<=', now()->addDays(LicenseRenewalService::CHARGE_DAYS))
            ->where('expires_at', '>', now()->subDays(LicenseRenewalService::GRACE_DAYS))
            ->chunkById(200, function ($keys) use ($renewals, $dry, &$renewed, &$short, &$failed, &$shortList) {
                foreach ($keys as $license) {
                    $offer = $renewals->offerFor($license, $license->user_id);

                    if (! $offer || ! $license->user) {
                        continue;
                    }

                    $balance = $this->balance($license);

                    if ($balance < $offer['total']) {
                        $short++;
                        $this->line(sprintf('  เงินไม่พอ #%d — ต้อง %s มี %s', $license->id, number_format($offer['total'], 2), number_format($balance, 2)));

                        $shortList[] = [
                            'what' => 'license',
                            'name' => $license->product->name . ' #' . $license->id,
                            'owner' => (string) ($license->user->name ?? $license->user->email ?? '#' . $license->user_id),
                            'need' => $offer['total'],
                            'have' => $balance,
                            'expires' => $license->expires_at?->format('d/m/Y'),
                        ];

                        // Said once per period, not every morning
                        if (! $dry && ! LicenseRenewalService::mailSent($license, 'short')) {
                            $this->mail($license, 'short', $offer);
                        }

                        continue;
                    }

                    $this->line(sprintf('  ต่ออายุ #%d %s — %s บาท', $license->id, $license->product->name, number_format($offer['total'], 2)));

                    if ($dry) {
                        $renewed++;

                        continue;
                    }

                    try {
                        // The receipt e-mail (payment confirmed) goes out from the order itself
                        $renewals->renewFromWallet($license, $license->user, true);
                        $renewed++;
                    } catch (LicenseRenewalException $e) {
                        // Spent between the balance check and the charge: the same as short
                        $e->reason === LicenseRenewalException::SHORT ? $short++ : $failed++;
                        $this->warn('  #' . $license->id . ' — ' . $e->getMessage());
                    } catch (\Throwable $e) {
                        $failed++;
                        report($e);
                    }
                }
            });

        // The team hears about short wallets too — once a day, as one card
        if (! $dry) {
            BusinessAlerts::renewalsShortOfFunds('license', $shortList);
        }

        // ---------------------------------------------------------- remind
        (clone $base)->where('auto_renew', false)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(max(LicenseRenewalService::REMINDER_DAYS)))
            ->chunkById(200, function ($keys) use ($renewals, $dry, &$reminded) {
                foreach ($keys as $license) {
                    $offer = $renewals->offerFor($license, $license->user_id);

                    if (! $offer) {
                        continue;
                    }

                    $days = (int) ceil(now()->diffInHours($license->expires_at) / 24);

                    foreach (LicenseRenewalService::REMINDER_DAYS as $milestone) {
                        if ($days > $milestone || LicenseRenewalService::mailSent($license, 'lapse-' . $milestone)) {
                            continue;
                        }

                        $this->line(sprintf('  เตือน #%d %s — เหลือ %d วัน', $license->id, $license->product->name, $days));
                        $reminded++;

                        if (! $dry) {
                            // Retire the wider milestones too, so a key first seen at 1 day does
                            // not get the 7-day e-mail tomorrow
                            $this->mail($license, 'lapse-' . $milestone, $offer, array_map(
                                fn ($other) => 'lapse-' . $other,
                                array_filter(LicenseRenewalService::REMINDER_DAYS, fn ($other) => $other >= $milestone)
                            ));
                        }

                        break;
                    }
                }
            });

        $this->newLine();
        $this->info(sprintf(
            '%s แจ้ง %d · ต่ออายุ %d · เงินไม่พอ %d · ล้มเหลว %d · เตือนคีย์ที่ไม่ต่ออัตโนมัติ %d',
            $dry ? '[dry run]' : 'เสร็จ:',
            $noticed,
            $renewed,
            $short,
            $failed,
            $reminded,
        ));

        return self::SUCCESS;
    }

    protected function balance(LicenseKey $license): float
    {
        return (float) (Wallet::where('user_id', $license->user_id)->value('balance') ?? 0);
    }

    /**
     * Send one renewal e-mail, and record it only once it has left.
     *
     * @param  array<int,string>  $alsoMark  other kinds to record as sent with it
     */
    protected function mail(LicenseKey $license, string $kind, array $offer, array $alsoMark = []): void
    {
        if (! $license->user?->email) {
            return;
        }

        try {
            Mail::to($license->user->email)->send(new LicenseRenewalMail($license, $kind, $offer['total'], $this->balance($license)));
            LicenseRenewalService::markMailSent($license, $kind, ...$alsoMark);
        } catch (\Throwable $e) {
            Log::error('[License] renewal mail failed', ['license' => $license->id, 'kind' => $kind, 'error' => $e->getMessage()]);
        }
    }
}
