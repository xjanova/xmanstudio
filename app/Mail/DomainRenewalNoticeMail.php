<?php

namespace App\Mail;

use App\Models\DomainRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "We are about to take money from your wallet."
 *
 * The domain page has always told the customer we charge thirty days before
 * expiry and warn first. This is the warning, and it goes out days ahead of the
 * charge so there is time to top up, to turn auto-renew off, or to decide the
 * domain is not wanted any more.
 *
 * It carries the exact figure and the exact date, because a warning that says
 * "soon" is one the customer has to come and check.
 */
class DomainRenewalNoticeMail extends Mailable
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
        return new Envelope(
            subject: 'โดเมน ' . $this->domain->domain . ' จะต่ออายุอัตโนมัติในอีก ' . max(0, $this->daysLeft - 30) . ' วัน',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.domain-renewal-notice');
    }

    /** True when the wallet cannot cover the renewal as things stand. */
    public function short(): bool
    {
        return $this->walletBalance < $this->price;
    }
}
