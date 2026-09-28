<?php

namespace App\Http\Controllers;

use App\Models\ProjectInvoice;
use App\Support\Letterhead;
use App\Support\Quotation\VatMode;
use App\Support\ThaiPdf;
use Illuminate\Http\Request;

/**
 * The instalment invoice, as a page and as a PDF.
 *
 * Reached by its own token, like the quotation: the customer may have no
 * account, and the finance person who actually pays it almost certainly does
 * not. Everything printed is copied from the row — nothing is recalculated, so
 * a document already in somebody's inbox cannot change under them.
 */
class InvoiceController extends Controller
{
    public function show(string $token)
    {
        $invoice = $this->byToken($token);

        return view('invoice.document', [
            'invoice' => $invoice,
            'doc' => $this->documentData($invoice),
            'companyInfo' => $this->companyInfo(),
        ]);
    }

    public function download(string $token)
    {
        $invoice = $this->byToken($token);

        $pdf = ThaiPdf::view('invoice.pdf', [
            'invoice' => $invoice,
            'doc' => $this->documentData($invoice),
            'companyInfo' => $this->companyInfo(),
        ]);

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    /**
     * The admin's copy, by id — same document, reached from the project page
     * without needing the customer's token.
     */
    public function adminDownload(Request $request, ProjectInvoice $invoice)
    {
        $pdf = ThaiPdf::view('invoice.pdf', [
            'invoice' => $invoice,
            'doc' => $this->documentData($invoice),
            'companyInfo' => $this->companyInfo(),
        ]);

        return $pdf->download($invoice->invoice_number . '.pdf');
    }

    protected function byToken(string $token): ProjectInvoice
    {
        // 64 hex characters and nothing else, checked before the query so a scan
        // cannot read the shape of a token out of the response time.
        abort_unless(preg_match('/^[0-9a-f]{64}$/', $token) === 1, 404);

        return ProjectInvoice::with(['project', 'quotation'])
            ->where('public_token', $token)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    protected function documentData(ProjectInvoice $invoice): array
    {
        $quotation = $invoice->quotation;
        $mode = VatMode::normalise($quotation?->vat_mode);
        $amount = (float) $invoice->amount;

        // The instalment is a share of a total that already had its tax settled,
        // so the split is taken on the instalment itself rather than re-derived
        // from the quotation: the three invoices must add up to the quotation's
        // grand total exactly, tax included.
        $tax = VatMode::split($amount, $mode === VatMode::NONE ? VatMode::NONE : VatMode::INCLUSIVE,
            (float) ($quotation?->vat_rate ?: VatMode::DEFAULT_RATE));

        return [
            'number' => $invoice->invoice_number,
            'issued' => ($invoice->issued_at ?? $invoice->created_at)->format('d/m/Y'),
            'due' => $invoice->due_date?->format('d/m/Y'),
            'title' => $invoice->title,
            'installment' => $invoice->installment_no . '/' . $invoice->total_installments,
            'percent' => rtrim(rtrim(number_format((float) $invoice->percent, 2), '0'), '.'),
            'amount' => $amount,
            'base' => $tax['base'],
            'vat' => $tax['vat'],
            'vat_rate' => rtrim(rtrim(number_format($tax['rate'], 2), '0'), '.'),
            'vat_mode' => $mode,
            'amount_words' => VatMode::bahtText($amount),
            'status' => $invoice->status,
            'status_label' => $invoice->statusLabel(),
            'overdue' => $invoice->isOverdue(),
            'paid_at' => $invoice->paid_at?->format('d/m/Y'),
            'project' => [
                'number' => $invoice->project?->project_number,
                'name' => $invoice->project?->project_name,
            ],
            'quote_number' => $quotation?->displayNumber(),
            'customer' => [
                'name' => $quotation?->customer_name ?? $invoice->project?->user?->name ?? '-',
                'company' => $quotation?->customer_company ?? '',
                'email' => $quotation?->customer_email ?? '',
                'phone' => $quotation?->customer_phone ?? '',
                'address' => $quotation?->customer_address ?? '',
            ],
        ];
    }

    /**
     * Who is issuing the document — the same letterhead as the quotation.
     *
     * @return array<string, mixed>
     */
    protected function companyInfo(): array
    {
        return Letterhead::info();
    }
}
