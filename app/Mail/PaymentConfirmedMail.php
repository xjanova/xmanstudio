<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\OrderReceipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PaymentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ชำระเงินเรียบร้อย - คำสั่งซื้อ #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-confirmed',
            with: [
                'order' => $this->order,
                'hasReceipt' => $this->order->exists && OrderReceipt::available($this->order),
            ],
        );
    }

    /**
     * The receipt PDF, rendered now rather than lazily: a failure to print must cost the
     * customer the attachment, never the e-mail itself — it carries their license keys.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->order->exists || ! OrderReceipt::available($this->order)) {
            return [];
        }

        try {
            $pdf = OrderReceipt::pdf($this->order)->output();
        } catch (\Throwable $e) {
            Log::error('Receipt PDF could not be attached to the payment e-mail', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        return [
            Attachment::fromData(fn () => $pdf, OrderReceipt::filename($this->order))
                ->withMime('application/pdf'),
        ];
    }
}
