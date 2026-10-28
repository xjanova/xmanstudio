<?php

namespace App\Mail;

use App\Models\LicenseKey;
use App\Services\LicenseRenewalService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The e-mails about a license running out (licenses:renew).
 *
 *   notice   — auto-renew is on: this is what will be charged, and when.
 *   short    — auto-renew is on, but the wallet cannot cover it.
 *   lapse-N  — auto-renew is off and N days are left: here is the renew button.
 *
 * A successful automatic renewal sends no mail of its own — its order sends the usual
 * payment-confirmed e-mail with the receipt.
 */
class LicenseRenewalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LicenseKey $license,
        public string $kind,
        public float $price,
        public float $walletBalance,
    ) {}

    public function envelope(): Envelope
    {
        $name = $this->license->product?->name ?? 'License';

        return new Envelope(subject: match ($this->kind) {
            'notice' => 'แจ้งล่วงหน้า: จะต่ออายุ ' . $name . ' อัตโนมัติวันที่ ' . $this->chargeDate(),
            'short' => 'ยอดเงินไม่พอต่ออายุ ' . $name,
            default => $name . ' จะหมดอายุใน ' . $this->daysLeft() . ' วัน',
        });
    }

    public function content(): Content
    {
        return new Content(view: 'emails.license-renewal', with: [
            'daysLeft' => $this->daysLeft(),
            'chargeDate' => $this->chargeDate(),
        ]);
    }

    public function daysLeft(): int
    {
        $expires = $this->license->expires_at;

        return $expires ? max(0, (int) ceil(now()->diffInHours($expires, false) / 24)) : 0;
    }

    /** The day the auto-renew run charges the wallet — CHARGE_DAYS before expiry, today at the earliest. */
    public function chargeDate(): string
    {
        $expires = $this->license->expires_at;

        if (! $expires) {
            return '-';
        }

        $charge = $expires->copy()->subDays(LicenseRenewalService::CHARGE_DAYS);

        return ($charge->isPast() ? now() : $charge)->format('d/m/Y');
    }
}
