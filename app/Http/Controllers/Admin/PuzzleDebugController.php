<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PuzzleDebugImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

        // Default: hide labeled records (show only unlabeled/pending)
        // Use show_all=1 to see everything including labeled
        if ($request->get('show_all') !== '1') {
            $query->unlabeled();
        }

        $records = $query->paginate(20)->withQueryString();

        // Stats
        $total = PuzzleDebugImage::count();
        $labeled = PuzzleDebugImage::labeled()->count();
        $humanLabeled = PuzzleDebugImage::labeled()->where('labeled_by', 'human')->count();
        $unlabeled = PuzzleDebugImage::unlabeled()->count();

        // Accuracy for HUMAN-labeled records only (reliable ground truth)
        $accuracy = PuzzleDebugImage::labeled()
            ->where('labeled_by', 'human')
            ->selectRaw('AVG(ABS(gap_x - actual_gap_x)) as avg_error')
            ->selectRaw('SUM(CASE WHEN ABS(gap_x - actual_gap_x) <= 20 THEN 1 ELSE 0 END) as within_20px')
            ->selectRaw('COUNT(*) as total')
            ->first();

        // Success rate from auto-feedback
        $successCount = PuzzleDebugImage::where('success', true)->count();
        $failCount = PuzzleDebugImage::where('success', false)->count();
        $totalAttempts = $successCount + $failCount;

        // Current AI model (cached correction)
        $aiModel = Cache::get('puzzle_ai_model', [
            'correction' => 0,
            'samples' => 0,
            'trained_at' => null,
            'by_method' => [],
        ]);

        // With-images count (records that have debug images vs feedback-only)
        $withImages = PuzzleDebugImage::whereNotNull('image_paths')
            ->whereRaw("JSON_LENGTH(image_paths) > 0")
            ->count();

        // Error distribution for human-labeled data
        $errorBuckets = [
            'perfect' => 0,   // 0-5px
            'good' => 0,      // 6-15px
            'ok' => 0,        // 16-30px
            'bad' => 0,       // 31-50px
            'miss' => 0,      // 51+px
        ];
        if ($humanLabeled > 0) {
            $errorBuckets['perfect'] = PuzzleDebugImage::labeled()->where('labeled_by', 'human')
                ->whereNotNull('gap_x')->whereRaw('ABS(gap_x - actual_gap_x) <= 5')->count();
            $errorBuckets['good'] = PuzzleDebugImage::labeled()->where('labeled_by', 'human')
                ->whereNotNull('gap_x')->whereRaw('ABS(gap_x - actual_gap_x) BETWEEN 6 AND 15')->count();
            $errorBuckets['ok'] = PuzzleDebugImage::labeled()->where('labeled_by', 'human')
                ->whereNotNull('gap_x')->whereRaw('ABS(gap_x - actual_gap_x) BETWEEN 16 AND 30')->count();
            $errorBuckets['bad'] = PuzzleDebugImage::labeled()->where('labeled_by', 'human')
                ->whereNotNull('gap_x')->whereRaw('ABS(gap_x - actual_gap_x) BETWEEN 31 AND 50')->count();
            $errorBuckets['miss'] = PuzzleDebugImage::labeled()->where('labeled_by', 'human')
                ->whereNotNull('gap_x')->whereRaw('ABS(gap_x - actual_gap_x) > 50')->count();
        }

        // Recent trend: last 24h vs older
        $recent24h = PuzzleDebugImage::where('created_at', '>=', now()->subDay());
        $recentTotal = (clone $recent24h)->count();
        $recentSuccess = (clone $recent24h)->where('success', true)->count();

        $stats = [
            'total' => $total,
            'labeled' => $labeled,
            'human_labeled' => $humanLabeled,
            'unlabeled' => $unlabeled,
            'with_images' => $withImages,
            'avg_error' => round($accuracy->avg_error ?? 0, 1),
            'accuracy_pct' => $humanLabeled > 0
                ? round(($accuracy->within_20px / $humanLabeled) * 100, 1)
                : 0,
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'success_rate' => $totalAttempts > 0
                ? round(($successCount / $totalAttempts) * 100, 1)
                : 0,
            'error_buckets' => $errorBuckets,
            'recent_24h' => $recentTotal,
            'recent_24h_success' => $recentSuccess,
            'recent_24h_rate' => $recentTotal > 0 ? round(($recentSuccess / $recentTotal) * 100, 1) : 0,
        ];

        return view('admin.puzzle-debug.index', compact('records', 'stats', 'aiModel'));
    }

    public function show(PuzzleDebugImage $record)
    {
        $prevRecord = PuzzleDebugImage::unlabeled()
            ->where('id', '<', $record->id)
            ->orderByDesc('id')
            ->first();

        $nextRecord = PuzzleDebugImage::unlabeled()
            ->where('id', '>', $record->id)
            ->orderBy('id')
            ->first();

        return view('admin.puzzle-debug.show', compact('record', 'prevRecord', 'nextRecord'));
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
            'labeled_by' => 'human',
        ]);

        return redirect()->back()->with('success', 'Label อัปเดตแล้ว (by human)');
    }

    /**
     * AI Learning: compute correction model from labeled data.
     * Calculates average detection offset and stores as cached model.
     */
    public function train(Request $request)
    {
        // ONLY train from HUMAN-labeled data — auto-labeled data is unreliable
        $global = PuzzleDebugImage::labeled()
            ->where('labeled_by', 'human')
            ->whereNotNull('gap_x')
            ->selectRaw('AVG(actual_gap_x - gap_x) as avg_correction')
            ->selectRaw('STDDEV(actual_gap_x - gap_x) as std_correction')
            ->selectRaw('COUNT(*) as samples')
            ->selectRaw('AVG(ABS(actual_gap_x - gap_x)) as avg_error')
            ->selectRaw('SUM(CASE WHEN ABS(actual_gap_x - gap_x) <= 20 THEN 1 ELSE 0 END) as within_20px')
            ->first();

        // Per-method breakdown (human-labeled only)
        $byMethod = PuzzleDebugImage::labeled()
            ->where('labeled_by', 'human')
            ->whereNotNull('gap_x')
            ->selectRaw('detection_method')
            ->selectRaw('AVG(actual_gap_x - gap_x) as avg_correction')
            ->selectRaw('AVG(ABS(actual_gap_x - gap_x)) as avg_error')
            ->selectRaw('COUNT(*) as samples')
            ->groupBy('detection_method')
            ->get()
            ->keyBy('detection_method')
            ->toArray();

        // Per-machine breakdown (top 5, human-labeled only)
        $byMachine = PuzzleDebugImage::labeled()
            ->where('labeled_by', 'human')
            ->whereNotNull('gap_x')
            ->selectRaw('SUBSTRING(machine_id, 1, 12) as machine_short')
            ->selectRaw('AVG(actual_gap_x - gap_x) as avg_correction')
            ->selectRaw('COUNT(*) as samples')
            ->groupBy('machine_short')
            ->orderByDesc('samples')
            ->limit(5)
            ->get()
            ->toArray();

        $model = [
            'correction' => round($global->avg_correction ?? 0, 1),
            'std_dev' => round($global->std_correction ?? 0, 1),
            'samples' => $global->samples ?? 0,
            'avg_error' => round($global->avg_error ?? 0, 1),
            'accuracy_pct' => ($global->samples ?? 0) > 0
                ? round(($global->within_20px / $global->samples) * 100, 1)
                : 0,
            'by_method' => $byMethod,
            'by_machine' => $byMachine,
            'trained_at' => now()->toISOString(),
        ];

        // Cache the model (used by API inference endpoint)
        Cache::put('puzzle_ai_model', $model, now()->addDays(30));

        Log::info('PuzzleAI: Model trained', $model);

        return redirect()->back()->with('success',
            "AI Model อัปเดตแล้ว! Correction: {$model['correction']}px | " .
            "Accuracy: {$model['accuracy_pct']}% | Samples: {$model['samples']}");
    }

    /**
     * Auto-label: records with success=true get actual_gap_x = gap_x
     * (if puzzle was solved successfully, the detected gap_x was correct)
     */
    public function autoLabel(Request $request)
    {
        $count = PuzzleDebugImage::where('success', true)
            ->whereNull('actual_gap_x')
            ->whereNotNull('gap_x')
            ->update([
                'actual_gap_x' => \DB::raw('gap_x'),
                'labeled_by' => 'auto_success',
            ]);

        return redirect()->back()->with('success',
            "Auto-labeled {$count} records (success=true, gap_x -> actual_gap_x)");
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
