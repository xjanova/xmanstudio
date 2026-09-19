<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The one nudge.
 *
 * A quotation went out, nobody answered, and it is about to expire. Most of
 * those are not a no — they are an e-mail that slid down an inbox. One reminder
 * before the price stops standing is worth sending; a second one is pestering,
 * so follow_up_sent_at makes sure there is never one.
 *
 * No PDF attached: they already have it. This is a link and a deadline.
 */
class QuotationReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Quotation $quotation,
        public ?string $publicUrl,
        public int $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->daysLeft <= 0
            ? 'ใบเสนอราคา ' . $this->quotation->displayNumber() . ' หมดอายุวันนี้'
            : 'ใบเสนอราคา ' . $this->quotation->displayNumber() . ' ยืนราคาอีก ' . $this->daysLeft . ' วัน';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quotation-reminder');
    }
}
