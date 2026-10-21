<?php

namespace App\Mail;

use App\Models\VpsInstance;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Your server is up." Carries what is needed to log in — address, user,
 * operating system — and deliberately NOT the password: an inbox is not a
 * safe, and the customer chose that password a few minutes ago.
 */
class VpsReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public VpsInstance $server) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'เซิร์ฟเวอร์ ' . $this->server->hostname . ' พร้อมใช้งานแล้ว');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.vps-ready');
    }
}
