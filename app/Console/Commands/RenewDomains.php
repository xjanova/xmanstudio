<?php

namespace App\Console\Commands;

use App\Mail\DomainExpiryReminderMail;
use App\Mail\DomainRenewalNoticeMail;
use App\Models\DomainRegistration;
use App\Models\Wallet;
use App\Services\DomainPurchaseException;
use App\Services\DomainRegistrarService;
use App\Services\HostingerApiService;
use App\Support\Alerts\BusinessAlerts;
use App\Support\DomainReminders;
use App\Support\UpstreamBilling;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Keep the promise printed on the domain page.
 *
 * It says, in both languages: charged from your wallet 30 days before expiry,
 * and we always warn you first. Neither half existed — auto-renew was a flag
 * nothing read, so a customer who switched it on and topped up their wallet
 * would still have lost the domain.
 *
 * Two passes, in this order, every day:
 *
 *   notice  — 37 days out, one e-mail per period saying what will be taken and
 *             when, and whether the wallet can cover it.
 *   charge  — 30 days out, but only for a domain whose notice has been sitting
 *             in the inbox for a few days. A warning that arrives with the
 *             receipt is not a warning.
 *
 * A wallet that cannot cover the renewal is not an error: it is skipped, said
 * out loud, and tried again tomorrow — there are thirty more days of tomorrows
 * before the domain is actually at risk.
 */
class RenewDomains extends Command
{
    protected $signature = 'domains:renew
                            {--notice-days= : เตือนล่วงหน้าเมื่อเหลืออายุไม่เกินกี่วัน (ไม่ใส่ = ใช้ค่าจากหลังบ้าน)}
                            {--charge-days= : ตัดเงินเมื่อเหลืออายุไม่เกินกี่วัน (ไม่ใส่ = ใช้ค่าจากหลังบ้าน)}
                            {--notice-lead= : ต้องเตือนไปแล้วกี่วันก่อนถึงจะตัดเงินได้}
                            {--reminder-days= : วันที่เตือนโดเมนที่ไม่ได้ต่ออัตโนมัติ เช่น 60,30,7}
                            {--skip-reminders : ข้ามการเตือนโดเมนที่ไม่ได้ต่ออัตโนมัติ}
                            {--id= : ทำเฉพาะโดเมนนี้ (id)}
                            {--dry : บอกว่าจะทำอะไร แต่ไม่ทำจริง}';

    protected $description = 'Warn about, and then take, automatic domain renewals from the wallet';

    /** Resolved once in handle() — settings unless a flag overrode them. */
    protected int $noticeDays = DomainReminders::DEFAULT_NOTICE_DAYS;

    protected int $chargeDays = DomainReminders::DEFAULT_CHARGE_DAYS;

    protected int $noticeLead = DomainReminders::DEFAULT_LEAD_DAYS;

    public function handle(HostingerApiService $api, DomainRegistrarService $registrar): int
    {
        $dry = (bool) $this->option('dry');

        // Renewing needs the registrar; warning a customer does not.
        //
        // This used to return here, before anything ran, which meant a token
        // that had expired or been rotated out silently switched off the
        // e-mails as well — the one thing that still works fine without it,
        // and the one thing a customer notices the absence of. The renewal
        // passes are skipped; the reminders still go out.
        $canRenew = $api->isConfigured();

        if (! $canRenew) {
            // Scheduled daily. "Not set up yet" is not a failure to shout about.
            $this->warn('No registrar API token configured — sending reminders only.');
        }

        // The schedule is an operator setting now, not a command-line default.
        // It used to be the latter, and since the scheduler calls this with no
        // options at all, changing when a customer hears from us meant a deploy.
        // A flag still wins, so a one-off run can use different numbers.
        $this->noticeDays = (int) ($this->option('notice-days') ?: DomainReminders::noticeDays());
        $this->chargeDays = (int) ($this->option('charge-days') ?: DomainReminders::chargeDays());
        $this->noticeLead = $this->option('notice-lead') !== null
            ? (int) $this->option('notice-lead')
            : DomainReminders::leadDays();

        if ($this->noticeDays < $this->chargeDays + $this->noticeLead) {
            // Not fatal — the passes still work — but the customer would be
            // told about a charge that had already happened, which is the one
            // thing the domain page promises will never occur.
            $this->warn(sprintf(
                'ตั้งค่าไม่สมเหตุผล: เตือนที่ %d วัน แต่ตัดเงินที่ %d วัน + รอ %d วัน — ลูกค้าจะได้รับแจ้งหลังถูกตัดเงิน',
                $this->noticeDays,
                $this->chargeDays,
                $this->noticeLead,
            ));
        }

        $notified = 0;
        $renewed = $short = $failed = 0;

        if ($canRenew) {
            $notified = $this->sendNotices($dry);
            [$renewed, $short, $failed] = $this->chargeDue($registrar, $dry);
        }

        $reminded = $this->option('skip-reminders') ? 0 : $this->sendExpiryReminders($dry);

        $this->newLine();
        $this->info(sprintf(
            '%s แจ้งเตือน %d · ต่ออายุสำเร็จ %d · เงินไม่พอ %d · ล้มเหลว %d · เตือนโดเมนที่ต่อเอง %d',
            $dry ? '[dry run]' : 'เสร็จ:',
            $notified,
            $renewed,
            $short,
            $failed,
            $reminded,
        ));

        return self::SUCCESS;
    }

    /**
     * Nudge the owners of domains that will NOT renew themselves.
     *
     * This pass is the one that was missing. dueForRenewalNotice only ever
     * looked at auto_renew = true, so a customer who left it off was never
     * told anything: the domain expired, the site went down, and the first
     * they knew was a phone call.
     *
     * Milestones come from the operator's settings and each fires once per
     * period. The record is cleared when the domain is renewed, which is what
     * arms them again for next year.
     */
    protected function sendExpiryReminders(bool $dry): int
    {
        if (! DomainReminders::enabled()) {
            return 0;
        }

        $milestones = $this->option('reminder-days')
            ? DomainReminders::parseDays((string) $this->option('reminder-days'))
            : DomainReminders::reminderDays();

        if ($milestones === []) {
            return 0;
        }

        // Only look as far out as the furthest milestone.
        $query = DomainRegistration::dueForExpiryReminder(max($milestones))
            ->with(['user', 'tldRecord']);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $sent = 0;

        foreach ($query->get() as $domain) {
            $milestone = $domain->dueReminderMilestone($milestones);

            if ($milestone === null || ! $domain->user?->email) {
                continue;
            }

            $price = $domain->tldRecord?->renewPriceThb() ?? 0.0;
            $balance = (float) (Wallet::getOrCreateForUser($domain->user_id)->balance ?? 0);
            $daysLeft = (int) $domain->daysUntilExpiry();

            $this->line(sprintf('  เตือน %s — เหลือ %d วัน (จุดเตือน %d วัน)', $domain->domain, $daysLeft, $milestone));

            if ($dry) {
                $sent++;

                continue;
            }

            try {
                Mail::to($domain->user->email)
                    ->send(new DomainExpiryReminderMail($domain, $price, $balance, $daysLeft));

                // Only after it leaves — a send that threw must be retried
                // tomorrow, not silently counted as done.
                $domain->markReminderSent($milestone);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('[Domains] expiry reminder failed', [
                    'domain' => $domain->domain,
                    'error' => $e->getMessage(),
                ]);
                $this->warn('  ส่งเมลเตือนไม่สำเร็จ: ' . $domain->domain);
            }
        }

        return $sent;
    }

    /**
     * Tell the owner before the money moves.
     */
    protected function sendNotices(bool $dry): int
    {
        $query = DomainRegistration::dueForRenewalNotice($this->noticeDays)
            ->with(['user', 'tldRecord']);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $sent = 0;

        foreach ($query->get() as $domain) {
            $price = $domain->tldRecord?->renewPriceThb() ?? 0.0;

            if ($price <= 0 || ! $domain->user?->email) {
                // No price or nobody to write to. The charge pass will skip it
                // too, because the notice never goes out.
                $this->warn(sprintf('  ข้าม %s — %s', $domain->domain, $price <= 0 ? 'ยังไม่มีราคาต่ออายุ' : 'ไม่มีอีเมลผู้ใช้'));

                continue;
            }

            $balance = (float) (Wallet::getOrCreateForUser($domain->user_id)->balance ?? 0);
            $days = (int) now()->startOfDay()->diffInDays($domain->expires_at->startOfDay(), false);

            $this->line(sprintf('  แจ้ง %s — %s บาท เหลือ %d วัน', $domain->domain, number_format($price), $days));

            if ($dry) {
                $sent++;

                continue;
            }

            try {
                Mail::to($domain->user->email)
                    ->send(new DomainRenewalNoticeMail($domain, $price, $balance, $days));

                // Only after it leaves: a notice that failed to send must still
                // be owed, and must still block the charge.
                $domain->update(['renewal_notice_sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('[Domains] renewal notice failed', [
                    'domain' => $domain->domain,
                    'error' => $e->getMessage(),
                ]);
                $this->warn('  ส่งเมลไม่สำเร็จ: ' . $domain->domain);
            }
        }

        return $sent;
    }

    /**
     * Take the money and buy the year.
     *
     * @return array{0:int,1:int,2:int} renewed, short of funds, failed
     */
    protected function chargeDue(DomainRegistrarService $registrar, bool $dry): array
    {
        $query = DomainRegistration::dueForRenewalCharge(
            $this->chargeDays,
            $this->noticeLead,
        )->with(['user', 'tldRecord']);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $renewed = $short = $failed = 0;
        $shortList = [];

        // Our card at the registrar was just refused (or found expired): every
        // renewal would be debited and refunded. They are due in weeks, not
        // hours — skip today, and the admin already has the alert.
        if (UpstreamBilling::isPaused()) {
            $this->warn('Supplier payments are paused — renewals skipped today.');

            return [0, 0, 0];
        }

        foreach ($query->get() as $domain) {
            if (! $domain->canRenew()) {
                // Already has a renewal in flight, or lost its upstream handle.
                continue;
            }

            $price = $domain->tldRecord?->renewPriceThb() ?? 0.0;
            $balance = (float) (Wallet::getOrCreateForUser($domain->user_id)->balance ?? 0);

            if ($price <= 0) {
                $this->warn('  ข้าม ' . $domain->domain . ' — ยังไม่มีราคาต่ออายุ');

                continue;
            }

            if ($balance < $price) {
                // Not a failure. There are still weeks left, and the customer
                // has been told. Try again tomorrow.
                $this->line(sprintf('  เงินไม่พอ %s — ต้อง %s มี %s', $domain->domain, number_format($price), number_format($balance)));
                $short++;
                $shortList[] = [
                    'what' => 'domain',
                    'name' => $domain->domain,
                    'owner' => (string) ($domain->user?->name ?? $domain->user?->email ?? '#' . $domain->user_id),
                    'need' => $price,
                    'have' => $balance,
                    'expires' => $domain->expires_at?->format('d/m/Y'),
                ];

                continue;
            }

            $this->line(sprintf('  ต่ออายุ %s — %s บาท', $domain->domain, number_format($price)));

            if ($dry) {
                $renewed++;

                continue;
            }

            try {
                $renewal = $registrar->renew($domain);

                if ($renewal->status === DomainRegistration::STATUS_REFUNDED) {
                    $failed++;
                    BusinessAlerts::domainRenewalFailed($domain, 'ต่ออายุไม่สำเร็จ เงินคืนเข้ากระเป๋าแล้ว');

                    continue;
                }

                $renewed++;

                if ($renewal->status === DomainRegistration::STATUS_PENDING) {
                    // Sent, not confirmed (payment still clearing, or the
                    // answer was lost): "renewed" would be premature. The
                    // reconciliation job settles it either way.
                    $this->line('    → รอผู้ให้บริการยืนยัน');

                    continue;
                }
                BusinessAlerts::domainRenewed($domain->fresh() ?? $domain, (float) $renewal->price_thb);
            } catch (DomainPurchaseException $e) {
                // Everything this throws is already customer-readable.
                $failed++;
                $this->warn('  ' . $domain->domain . ' — ' . $e->getMessage());
                BusinessAlerts::domainRenewalFailed($domain, $e->getMessage());
            } catch (\Throwable $e) {
                $failed++;
                Log::error('[Domains] renewal threw', [
                    'domain' => $domain->domain,
                    'error' => $e->getMessage(),
                ]);
                $this->warn('  ' . $domain->domain . ' — ' . $e->getMessage());
                BusinessAlerts::domainRenewalFailed($domain, 'ข้อผิดพลาดระบบ — ดู log');
            }
        }

        // The team hears about wallets that could not cover a renewal, once a
        // day as one card — the customer has been e-mailed, but a phone call
        // while there are weeks left is what actually keeps the domain.
        if (! $dry) {
            BusinessAlerts::renewalsShortOfFunds('domains', $shortList);
        }

        return [$renewed, $short, $failed];
    }
}
