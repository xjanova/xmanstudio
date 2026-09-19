<?php

namespace App\Console\Commands;

use App\Mail\DomainRenewalNoticeMail;
use App\Models\DomainRegistration;
use App\Models\Wallet;
use App\Services\DomainPurchaseException;
use App\Services\DomainRegistrarService;
use App\Services\HostingerApiService;
use App\Support\Alerts\BusinessAlerts;
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
                            {--notice-days=37 : เตือนล่วงหน้าเมื่อเหลืออายุไม่เกินกี่วัน}
                            {--charge-days=30 : ตัดเงินเมื่อเหลืออายุไม่เกินกี่วัน}
                            {--notice-lead=3 : ต้องเตือนไปแล้วกี่วันก่อนถึงจะตัดเงินได้}
                            {--id= : ทำเฉพาะโดเมนนี้ (id)}
                            {--dry : บอกว่าจะทำอะไร แต่ไม่ทำจริง}';

    protected $description = 'Warn about, and then take, automatic domain renewals from the wallet';

    public function handle(HostingerApiService $api, DomainRegistrarService $registrar): int
    {
        if (! $api->isConfigured()) {
            // Scheduled daily. "Not set up yet" is not a failure to shout about.
            $this->warn('No registrar API token configured — nothing to renew.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry');

        $notified = $this->sendNotices($dry);
        [$renewed, $short, $failed] = $this->chargeDue($registrar, $dry);

        $this->newLine();
        $this->info(sprintf(
            '%s แจ้งเตือน %d · ต่ออายุสำเร็จ %d · เงินไม่พอ %d · ล้มเหลว %d',
            $dry ? '[dry run]' : 'เสร็จ:',
            $notified,
            $renewed,
            $short,
            $failed,
        ));

        return self::SUCCESS;
    }

    /**
     * Tell the owner before the money moves.
     */
    protected function sendNotices(bool $dry): int
    {
        $query = DomainRegistration::dueForRenewalNotice((int) $this->option('notice-days'))
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
            (int) $this->option('charge-days'),
            (int) $this->option('notice-lead'),
        )->with(['user', 'tldRecord']);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $renewed = $short = $failed = 0;

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

        return [$renewed, $short, $failed];
    }
}
