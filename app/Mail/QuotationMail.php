<?php

namespace App\Mail;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The quotation, sent to the customer.
 *
 * Until now the builder produced a PDF only while the visitor sat on the page:
 * close the tab and the document was gone, and the team had no copy either.
 * This carries the same PDF into their inbox and links back to a page where
 * they can accept it or ask to renegotiate.
 *
 * The PDF is built by the caller and handed over as bytes rather than
 * re-rendered here, so the attachment is byte-identical to the one the
 * document page serves — a customer comparing the two must not find them
 * different.
 */
class QuotationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $pdfBytes  the rendered document
     * @param  array<string, mixed>  $doc  the same shape the document views read
     */
    public function __construct(
        public Quotation $quotation,
        public array $doc,
        public string $pdfBytes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ใบเสนอราคา ' . $this->quotation->quote_number . ' จาก XMAN Studio',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quotation');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfBytes, $this->quotation->quote_number . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
