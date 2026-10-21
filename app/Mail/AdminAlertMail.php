<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * An admin alert that could not go to Telegram, sent as a plain e-mail.
 *
 * Only for the few alerts the business cannot afford to miss — our card at
 * the supplier refused, a server bought with our money that will not build.
 * Plain text on purpose: it has to be readable on a phone lock screen, and
 * it must not depend on the alert-card renderer that may be what is broken.
 */
class AdminAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $headline,
        public string $text,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[XMAN แจ้งเตือนด่วน] ' . $this->headline);
    }

    public function content(): Content
    {
        return new Content(htmlString: '<div style="font-family:sans-serif;font-size:14px;line-height:1.7;white-space:normal">'
            . nl2br(e($this->text)) . '</div>');
    }
}
