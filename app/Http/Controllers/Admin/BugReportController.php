<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\Setting;
use Illuminate\Http\Request;

class BugReportController extends Controller
{
    public function index(Request $request)
    {
        $query = BugReport::query()->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
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

        $reports = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => BugReport::count(),
            'new' => BugReport::byStatus('new')->count(),
            'misclassification' => BugReport::ofType('misclassification')->count(),
            'fixed' => BugReport::where('is_fixed', true)->count(),
        ];

        $products = BugReport::distinct()->pluck('product_name')->filter()->sort()->values();

        $autoDeleteDays = Setting::getValue('bug_report_auto_delete_days', 0);
        $autoDeleteEnabled = (int) $autoDeleteDays > 0;

        return view('admin.bug-reports.index', compact('reports', 'counts', 'products', 'autoDeleteDays', 'autoDeleteEnabled'));
    }

    public function show(BugReport $report)
    {
        $report->load(['comments', 'attachments']);

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
}
