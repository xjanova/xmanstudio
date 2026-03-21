<?php

namespace App\Mail;

use App\Models\AffiliateCommission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AffiliateCommissionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AffiliateCommission $commission,
        public string $action = 'paid' // 'paid' or 'rejected'
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->action === 'paid'
            ? 'ค่าคอมมิชชัน Affiliate ได้รับการอนุมัติ - ' . config('app.name')
            : 'ค่าคอมมิชชัน Affiliate ถูกปฏิเสธ - ' . config('app.name');

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.affiliate-commission',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
