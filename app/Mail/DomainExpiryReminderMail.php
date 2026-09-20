<?php

namespace App\Mail;

use App\Models\DomainRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Your domain runs out in X days and nothing will renew it for you."
 *
 * The other renewal e-mail, DomainRenewalNoticeMail, only goes to domains with
 * auto-renew switched on — it is a warning that money is about to move. A
 * domain without auto-renew got nothing at all: it simply stopped working one
 * morning, and the first the customer heard of it was the site being down.
 *
 * Sent at each milestone the operator configured (default 60, 30, 14, 7 and 1
 * days), once each, and the wording sharpens as the date closes in.
 */
class DomainExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DomainRegistration $domain,
        public float $price,
        public float $walletBalance,
        public int $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match (true) {
            $this->daysLeft <= 1 => 'ด่วน! โดเมน ' . $this->domain->domain . ' หมดอายุพรุ่งนี้',
            $this->daysLeft <= 7 => 'โดเมน ' . $this->domain->domain . ' เหลืออีก ' . $this->daysLeft . ' วันจะหมดอายุ',
            default => 'แจ้งเตือน: โดเมน ' . $this->domain->domain . ' จะหมดอายุในอีก ' . $this->daysLeft . ' วัน',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.domain-expiry-reminder');
    }

    /** How loud the e-mail should be. */
    public function urgency(): string
    {
        return match (true) {
            $this->daysLeft <= 7 => 'critical',
            $this->daysLeft <= 30 => 'warning',
            default => 'info',
        };
    }

    /** True when renewing straight from the wallet would work right now. */
    public function walletCovers(): bool
    {
        return $this->price > 0 && $this->walletBalance >= $this->price;
    }
}
