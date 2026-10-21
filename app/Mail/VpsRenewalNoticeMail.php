<?php

namespace App\Mail;

use App\Models\VpsInstance;
use App\Support\VpsSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "We are about to take money from your wallet for your server."
 *
 * The exact figure and the exact day, and whether the wallet covers it —
 * a warning that says "soon" is one the customer has to come and check.
 */
class VpsRenewalNoticeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public VpsInstance $server,
        public float $price,
        public float $walletBalance,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'เซิร์ฟเวอร์ ' . $this->server->hostname . ' จะต่ออายุอัตโนมัติเร็ว ๆ นี้');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vps-renewal-notice');
    }

    /** The day the money moves: expiry minus the configured charge days. */
    public function chargeDate(): ?string
    {
        return $this->server->expires_at?->copy()->subDays(VpsSettings::chargeDays())->format('d/m/Y');
    }

    public function short(): bool
    {
        return $this->walletBalance < $this->price;
    }
}
