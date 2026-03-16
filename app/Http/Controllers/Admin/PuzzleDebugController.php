<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PuzzleDebugImage;
use Illuminate\Http\Request;

class PuzzleDebugController extends Controller
{
    public function index(Request $request)
    {
        $query = PuzzleDebugImage::query()->latest();

        if ($machine = $request->get('machine_id')) {
            $query->where('machine_id', 'like', "%{$machine}%");
        }

        if ($method = $request->get('method')) {
            $query->where('detection_method', $method);
        }

        if ($request->get('unlabeled') === '1') {
            $query->unlabeled();
        }

        $records = $query->paginate(20)->withQueryString();

        // Stats
        $total = PuzzleDebugImage::count();
        $labeled = PuzzleDebugImage::labeled()->count();
        $unlabeled = PuzzleDebugImage::unlabeled()->count();

        // Accuracy for labeled records
        $accuracy = PuzzleDebugImage::labeled()
            ->selectRaw('AVG(ABS(gap_x - actual_gap_x)) as avg_error')
            ->selectRaw('SUM(CASE WHEN ABS(gap_x - actual_gap_x) <= 20 THEN 1 ELSE 0 END) as within_20px')
            ->selectRaw('COUNT(*) as total')
            ->first();

        $stats = [
            'total' => $total,
            'labeled' => $labeled,
            'unlabeled' => $unlabeled,
            'avg_error' => round($accuracy->avg_error ?? 0, 1),
            'accuracy_pct' => $labeled > 0
                ? round(($accuracy->within_20px / $labeled) * 100, 1)
                : 0,
        ];

        return view('admin.puzzle-debug.index', compact('records', 'stats'));
    }

    public function show(PuzzleDebugImage $record)
    {
        return view('admin.puzzle-debug.show', compact('record'));
    }

    public function updateLabel(Request $request, PuzzleDebugImage $record)
    {
        $request->validate([
            'actual_gap_x' => 'required|integer|min:0|max:3000',
            'success' => 'nullable|boolean',
        ]);

        $record->update([
            'actual_gap_x' => $request->input('actual_gap_x'),
            'success' => $request->boolean('success'),
        ]);

        return redirect()->back()->with('success', 'Label อัปเดตแล้ว');
    }

    public function destroy(PuzzleDebugImage $record)
    {
        // Delete stored images
        if ($record->image_paths) {
            foreach ($record->image_paths as $path) {
                \Storage::disk('public')->delete($path);
            }
        }

        $record->delete();
        return redirect()->route('admin.puzzle-debug.index')->with('success', 'ลบแล้ว');
    }

    public function bulkDelete(Request $request)
    {
        $days = $request->input('older_than_days', 30);
        $cutoff = now()->subDays($days);

        $records = PuzzleDebugImage::where('created_at', '<', $cutoff)->get();
        $count = 0;

        foreach ($records as $record) {
            if ($record->image_paths) {
                foreach ($record->image_paths as $path) {
                    \Storage::disk('public')->delete($path);
                }
            }
            $record->delete();
            $count++;
        }

        return redirect()->back()->with('success', "ลบ {$count} รายการที่เก่ากว่า {$days} วัน");
    }
}
