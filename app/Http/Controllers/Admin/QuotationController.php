<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProjectOrder;
use App\Models\Quotation;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
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

        $quotations = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => Quotation::count(),
            'pending' => Quotation::pending()->count(),
            'accepted' => Quotation::accepted()->count(),
            'paid' => Quotation::paid()->count(),
        ];

        return view('admin.quotations.list', compact('quotations', 'counts'));
    }

    /**
     * Display quotation details
     */
    public function show(Quotation $quotation)
    {
        $project = ProjectOrder::where('quotation_id', $quotation->id)->first();

        return view('admin.quotations.show', compact('quotation', 'project'));
    }

    /**
     * Update quotation status
     */
    public function updateStatus(Request $request, Quotation $quotation)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,viewed,accepted,paid,expired,rejected',
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

        // Auto-create project when quotation is accepted (if not already linked)
        $project = null;
        if ($request->status === 'accepted' && ! ProjectOrder::where('quotation_id', $quotation->id)->exists()) {
            $project = $this->createProjectFromQuotation($quotation);
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

        $message = 'อัปเดตสถานะ #' . $quotation->quote_number . ' เป็น "' . ($statusLabels[$request->status] ?? $request->status) . '" สำเร็จ';

        if ($project) {
            $message .= ' — สร้างโครงการ ' . $project->project_number . ' อัตโนมัติแล้ว';

            return redirect()
                ->route('admin.projects.show', $project)
                ->with('success', $message);
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    /**
     * Auto-create a project from an accepted quotation
     */
    protected function createProjectFromQuotation(Quotation $quotation): ProjectOrder
    {
        $project = ProjectOrder::create([
            'user_id' => $quotation->user_id,
            'quotation_id' => $quotation->id,
            'project_name' => $quotation->service_name ?? $quotation->service_type,
            'project_description' => $quotation->project_description,
            'project_type' => $quotation->service_type,
            'total_price' => $quotation->grand_total,
            'admin_notes' => 'สร้างอัตโนมัติจากใบเสนอราคา #' . $quotation->quote_number,
        ]);

        // Create features from service options
        if ($quotation->service_options) {
            foreach ($quotation->service_options as $index => $option) {
                $project->features()->create([
                    'name' => is_array($option) ? ($option['name'] ?? $option) : $option,
                    'description' => is_array($option) ? ($option['description'] ?? null) : null,
                    'order' => $index,
                ]);
            }
        }

        // Create features from additional options
        if ($quotation->additional_options) {
            $offset = count($quotation->service_options ?? []);
            foreach ($quotation->additional_options as $index => $option) {
                $project->features()->create([
                    'name' => is_array($option) ? ($option['name'] ?? $option) : $option,
                    'description' => is_array($option) ? ('ตัวเลือกเพิ่มเติม — ฿' . number_format($option['price'] ?? 0)) : null,
                    'order' => $offset + $index,
                ]);
            }
        }

        // Create initial timeline
        $project->timeline()->create([
            'title' => 'รับงาน — สร้างจากใบเสนอราคา',
            'description' => "ลูกค้ายอมรับใบเสนอราคา #{$quotation->quote_number}\nชื่อ: {$quotation->customer_name}\nยอดรวม: ฿" . number_format($quotation->grand_total, 2),
            'event_date' => now(),
            'type' => 'start',
            'is_completed' => true,
        ]);

        return $project;
    }
}
