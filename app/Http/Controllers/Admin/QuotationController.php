<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectInvoice;
use App\Models\ProjectOrder;
use App\Models\Quotation;
use App\Services\QuotationAcceptance;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuotationController extends Controller
{
    public function __construct(protected QuotationAcceptance $acceptance) {}

    /**
     * Display listing of quotations/orders from website
     */
    public function index(Request $request)
    {
        $query = Quotation::with('project')->orderByDesc('created_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_company', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->get('action_type')) {
            $query->where('action_type', $type);
        }

        // "ต้องตาม": sent, unanswered, longest silence first. A quotation nobody
        // chases is the most expensive kind — the work was done to price it and
        // then nothing happened.
        if ($request->boolean('follow_up')) {
            $query->awaitingAnswer()->reorder()->orderBy('sent_at');
        }

        $quotations = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => Quotation::count(),
            'pending' => Quotation::pending()->count(),
            'accepted' => Quotation::accepted()->count(),
            'paid' => Quotation::paid()->count(),
            'follow_up' => Quotation::awaitingAnswer()->count(),
        ];

        return view('admin.quotations.list', compact('quotations', 'counts'));
    }

    /**
     * Display quotation details
     */
    public function show(Quotation $quotation)
    {
        $project = ProjectOrder::where('quotation_id', $quotation->id)->first();
        $invoices = $project ? $project->invoices()->get() : collect();
        $versions = $quotation->versions()->get();

        return view('admin.quotations.show', compact('quotation', 'project', 'invoices', 'versions'));
    }

    /**
     * Update quotation status
     */
    public function updateStatus(Request $request, Quotation $quotation)
    {
        // Rule::in over the model's list, not a hand-typed string: the two drifted
        // apart once already and a status the enum does not know is a 500 on MySQL.
        $request->validate([
            'status' => ['required', Rule::in(Quotation::STATUSES)],
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $data = ['status' => $request->status];

        if ($request->status === 'sent' && ! $quotation->sent_at) {
            $data['sent_at'] = now();
        }
        if ($request->status === 'accepted' && ! $quotation->accepted_at) {
            $data['accepted_at'] = now();
        }
        if ($request->status === 'paid' && ! $quotation->paid_at) {
            $data['paid_at'] = now();
        }

        if ($request->admin_notes) {
            $data['admin_notes'] = ($quotation->admin_notes ? $quotation->admin_notes . "\n" : '')
                . $request->admin_notes . ' — ' . now()->format('d/m/Y H:i');
        }

        $quotation->update($data);

        // Accepting creates the project and its instalments — the same code path
        // the customer's own "ตอบรับ" runs, so both produce identical paperwork.
        $project = null;
        if ($request->status === 'accepted') {
            $existing = ProjectOrder::where('quotation_id', $quotation->id)->exists();
            $project = $this->acceptance->projectFor($quotation);

            if ($existing) {
                $project = null;  // already known to the admin, no need to jump them there
            }
        }

        $statusLabels = [
            'draft' => 'ร่าง',
            'sent' => 'ส่งแล้ว',
            'viewed' => 'เปิดดูแล้ว',
            'accepted' => 'ยอมรับ',
            'paid' => 'ชำระแล้ว',
            'expired' => 'หมดอายุ',
            'rejected' => 'ปฏิเสธ',
        ];

        $message = 'อัปเดตสถานะ #' . $quotation->displayNumber() . ' เป็น "' . ($statusLabels[$request->status] ?? $request->status) . '" สำเร็จ';

        if ($project) {
            $message .= ' — สร้างโครงการ ' . $project->project_number . ' และออกใบแจ้งหนี้งวดแรกอัตโนมัติแล้ว';

            return redirect()
                ->route('admin.projects.show', $project)
                ->with('success', $message);
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Re-quote the same job after a negotiation.
     *
     * The customer asked to change something, so they need a new document — but
     * not a new identity. The revision keeps the quote number and bumps the
     * version, and the old one is marked superseded so it can no longer be
     * accepted behind our backs.
     */
    public function revise(Request $request, Quotation $quotation)
    {
        $request->validate([
            'revision_note' => 'nullable|string|max:500',
        ]);

        if ($quotation->status === 'accepted' || $quotation->status === 'paid') {
            return back()->with('error', 'ใบเสนอราคาที่ตอบรับหรือชำระแล้ว ออกฉบับแก้ไขไม่ได้ — ให้ออกใบใหม่แทน');
        }

        if ($quotation->isSuperseded()) {
            return back()->with('error', 'ฉบับนี้ถูกแทนด้วยเวอร์ชันใหม่ไปแล้ว ให้แก้ที่เวอร์ชันล่าสุด');
        }

        $revision = $quotation->createRevision($request->input('revision_note'));

        return redirect()
            ->route('admin.quotations.detail', $revision)
            ->with('success', 'สร้างฉบับแก้ไข ' . $revision->displayNumber() . ' แล้ว — แก้ยอดแล้วกดส่งอีเมลให้ลูกค้าอีกครั้ง');
    }

    /**
     * Move an instalment along: issue it, mark it paid, or cancel it.
     */
    public function updateInvoice(Request $request, ProjectInvoice $invoice)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(ProjectInvoice::STATUSES)],
            'paid_note' => 'nullable|string|max:255',
            'due_date' => 'nullable|date',
        ]);

        match ($validated['status']) {
            ProjectInvoice::ISSUED => $invoice->issue(),
            ProjectInvoice::PAID => $invoice->markAsPaid($validated['paid_note'] ?? null),
            ProjectInvoice::VOID => $invoice->update(['status' => ProjectInvoice::VOID]),
            default => $invoice->update([
                'status' => ProjectInvoice::SCHEDULED,
                'issued_at' => null,
                'due_date' => null,
            ]),
        };

        if (! empty($validated['due_date']) && $invoice->status === ProjectInvoice::ISSUED) {
            $invoice->update(['due_date' => $validated['due_date']]);
        }

        $this->acceptance->syncPayment($invoice->project);

        return back()->with('success', 'อัปเดต ' . $invoice->invoice_number . ' เป็น "' . $invoice->statusLabel() . '" แล้ว');
    }
}
