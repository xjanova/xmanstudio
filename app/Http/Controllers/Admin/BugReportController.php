<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\BugReportAttachment;
use App\Models\BugReportComment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BugReportController extends Controller
{
    public function index(Request $request)
    {
        $query = BugReport::query()->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('device_id', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('report_type')) {
            $query->ofType($type);
        }

        if ($status = $request->get('status')) {
            $query->byStatus($status);
        }

        if ($product = $request->get('product_name')) {
            $query->forProduct($product);
        }

        if ($request->get('channel') === BugReport::CHANNEL_APP) {
            $query->where('channel', BugReport::CHANNEL_APP);
        }

        $reports = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => BugReport::count(),
            'new' => BugReport::byStatus('new')->count(),
            'misclassification' => BugReport::ofType('misclassification')->count(),
            'fixed' => BugReport::where('is_fixed', true)->count(),
            'app' => BugReport::where('channel', BugReport::CHANNEL_APP)->count(),
            'app_new' => BugReport::where('channel', BugReport::CHANNEL_APP)->byStatus('new')->count(),
        ];

        $products = BugReport::distinct()->pluck('product_name')->filter()->sort()->values();

        $autoDeleteDays = Setting::getValue('bug_report_auto_delete_days', 0);
        $autoDeleteEnabled = (int) $autoDeleteDays > 0;

        return view('admin.bug-reports.index', compact('reports', 'counts', 'products', 'autoDeleteDays', 'autoDeleteEnabled'));
    }

    public function show(BugReport $report)
    {
        $report->load(['comments.user', 'attachments', 'user', 'licenseKey', 'product']);

        return view('admin.bug-reports.show', compact('report'));
    }

    public function destroy(BugReport $report)
    {
        $report->attachments->each(fn ($a) => $a->deleteFile());
        $report->forceDelete();

        return redirect()->route('admin.bug-reports.index')->with('success', 'ลบ Bug Report #' . $report->id . ' สำเร็จ');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:bug_reports,id',
        ]);

        $reports = BugReport::whereIn('id', $request->input('ids'))->get();

        foreach ($reports as $report) {
            $report->attachments->each(fn ($a) => $a->deleteFile());
            $report->forceDelete();
        }

        return back()->with('success', 'ลบ Bug Reports ' . count($request->input('ids')) . ' รายการสำเร็จ');
    }

    public function updateAutoDelete(Request $request)
    {
        $request->validate([
            'auto_delete_days' => 'required|integer|min:0|max:365',
        ]);

        Setting::setValue('bug_report_auto_delete_days', $request->input('auto_delete_days'));

        $days = (int) $request->input('auto_delete_days');
        $msg = $days > 0
            ? "ตั้งค่าลบอัตโนมัติ: ลบ Bug Reports ที่เก่ากว่า {$days} วัน"
            : 'ปิดการลบอัตโนมัติแล้ว';

        return back()->with('success', $msg);
    }

    public function markAnalyzed(Request $request, BugReport $report)
    {
        $report->markAsAnalyzed(
            auth()->id(),
            $request->input('analysis_notes')
        );

        return back()->with('success', 'Report marked as analyzed');
    }

    public function markFixed(Request $request, BugReport $report)
    {
        $request->validate([
            'fixed_in_version' => 'required|string|max:20',
        ]);

        $report->markAsFixed(
            $request->input('fixed_in_version'),
            $request->input('fix_notes')
        );

        return back()->with('success', 'Report marked as fixed');
    }

    /**
     * Move a report along (new → analyzing → confirmed → fixed / wont_fix / duplicate / closed), with an
     * optional note kept as an internal comment on the report.
     */
    public function updateStatus(Request $request, BugReport $report)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(BugReport::STATUSES)],
            'note' => 'nullable|string|max:2000',
        ]);

        $old = $report->status;
        $report->status = $data['status'];
        if (in_array($data['status'], ['analyzing', 'confirmed'], true) && ! $report->is_analyzed) {
            $report->is_analyzed = true;
            $report->analyzed_at = now();
            $report->analyzed_by = auth()->id();
        }
        if ($data['status'] === 'fixed' && ! $report->is_fixed) {
            $report->is_fixed = true;
            $report->fixed_at = now();
        }
        $report->save();

        $note = trim((string) ($data['note'] ?? ''));
        if ($note !== '' || $old !== $data['status']) {
            BugReportComment::create([
                'bug_report_id' => $report->id,
                'user_id' => auth()->id(),
                'comment' => trim(sprintf(
                    '%s%s',
                    $old !== $data['status'] ? 'สถานะ: ' . BugReport::statusLabel($old) . ' → ' . BugReport::statusLabel($data['status']) . "\n" : '',
                    $note
                )),
                'is_internal' => true,
            ]);
        }

        return back()->with('success', 'อัปเดตสถานะเป็น "' . BugReport::statusLabel($data['status']) . '" แล้ว');
    }

    /**
     * An attachment of a report (the app's screenshot), from the private disk, to admins only.
     */
    public function attachment(BugReport $report, BugReportAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->bug_report_id === $report->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        $mime = match (strtolower((string) $attachment->file_type)) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return Storage::disk('local')->response($attachment->file_path, $attachment->filename, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
