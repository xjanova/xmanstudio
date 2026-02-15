<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
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

        return view('admin.bug-reports.index', compact('reports', 'counts', 'products'));
    }

    public function show(BugReport $report)
    {
        $report->load(['comments', 'attachments']);

        return view('admin.bug-reports.show', compact('report'));
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
