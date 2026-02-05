<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public ?string $invoicePath = null;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order, ?string $invoicePath = null)
    {
        $this->order = $order;
        $this->invoicePath = $invoicePath;
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
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if ($this->invoicePath && file_exists($this->invoicePath)) {
            return [
                Attachment::fromPath($this->invoicePath)
                    ->as('invoice-' . $this->order->order_number . '.pdf')
                    ->withMime('application/pdf'),
            ];
        }

        return [];
    }
}
