<?php

namespace App\Mail;

use App\Models\VpsInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The e-mails about a server running out.
 *
 *   short     — auto-renew is on, but the wallet cannot cover it.
 *   lapse-N   — auto-renew is off and N days are left.
 *   expired   — it ran out; it can still be renewed before the data goes.
 *
 * One class, one template, because the three say the same thing at rising
 * volume: this server stops, and what is on it goes with it, unless you act.
 */
class VpsExpiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public VpsInstance $server,
        public string $kind,
        public float $price,
        public float $walletBalance,
    ) {}

    public function envelope(): Envelope
    {
        $host = $this->server->hostname;

        return new Envelope(subject: match (true) {
            $this->kind === 'short' => 'ยอดเงินไม่พอต่ออายุเซิร์ฟเวอร์ ' . $host,
            $this->kind === 'expired' => 'เซิร์ฟเวอร์ ' . $host . ' หมดอายุแล้ว',
            default => 'เซิร์ฟเวอร์ ' . $host . ' จะหมดอายุใน ' . max(0, (int) $this->server->daysUntilExpiry()) . ' วัน',
        });
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vps-expiry');
    }
}
