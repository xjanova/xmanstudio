<?php

namespace App\Console\Commands;

use App\Mail\QuotationReminderMail;
use App\Models\Quotation;
use App\Support\Alerts\BusinessAlerts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Chase the quotations that went quiet.
 *
 * A quotation used to be sent and then forgotten by the system entirely: no
 * reminder, and no list of what was still unanswered. Most silence is not a no
 * — it is an e-mail that slid down an inbox while the price quietly expired.
 *
 * One reminder per quotation, a few days before it expires. follow_up_sent_at is
 * the lock: it is written only after the mail actually goes out, so a mailer
 * outage means the next run tries again rather than skipping the customer, and
 * a second run on the same day sends nothing.
 */
class QuotationFollowUpCommand extends Command
{
    protected $signature = 'quotations:follow-up
                            {--days=3 : ส่งเตือนเมื่อเหลืออายุไม่เกินกี่วัน}
                            {--dry-run : แสดงว่าจะส่งถึงใคร แต่ไม่ส่งจริง}';

    protected $description = 'ส่งอีเมลเตือนลูกค้าหนึ่งครั้งก่อนใบเสนอราคาหมดอายุ';

    public function handle(): int
    {
        $days = max(0, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');

        $quotations = Quotation::needsFollowUp($days)->orderBy('valid_until')->get();

        if ($quotations->isEmpty()) {
            $this->info('No quotations need a follow-up right now.');

            return self::SUCCESS;
        }

        $reminded = collect();
        $sent = 0;
        $failed = 0;

        foreach ($quotations as $quotation) {
            $daysLeft = $quotation->daysLeft();

            if ($dryRun) {
                $this->line(sprintf(
                    '  would remind %s — %s (%s) · %d วัน',
                    $quotation->displayNumber(),
                    $quotation->customer_name,
                    $quotation->customer_email,
                    $daysLeft
                ));
                $sent++;

                continue;
            }

            try {
                // The oldest quotations predate public tokens and so have no link
                // to send. Minting one now costs nothing and is the difference
                // between a useful reminder and a dead end.
                if (! $quotation->public_token) {
                    $quotation->update(['public_token' => Quotation::newPublicToken()]);
                }

                Mail::to($quotation->customer_email)
                    ->send(new QuotationReminderMail($quotation, $quotation->publicUrl(), $daysLeft));

                // Only now: a reminder that failed to send must still be owed.
                $quotation->update(['follow_up_sent_at' => now()]);
                $reminded->push($quotation);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('Quotation follow-up e-mail failed: ' . $e->getMessage(), [
                    'quote_number' => $quotation->quote_number,
                    'to' => $quotation->customer_email,
                    'exception' => get_class($e),
                ]);
                $this->warn('  failed: ' . $quotation->quote_number . ' — ' . $e->getMessage());
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Reminded {$sent} customer(s)" . ($failed ? ", {$failed} failed" : '') . '.');

        if (! $dryRun && $reminded->isNotEmpty()) {
            // The team should know which jobs are on the clock, not discover it
            // from a customer who says "I thought you'd forgotten".
            BusinessAlerts::quotationsChased($reminded, $failed);
        }

        return self::SUCCESS;
    }
}
