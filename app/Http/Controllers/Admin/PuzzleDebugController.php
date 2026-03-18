<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PuzzleDebugImage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

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
            ->whereRaw('JSON_LENGTH(image_paths) > 0')
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

    /**
     * Train real ML model from human-labeled data.
     * Auto-starts ML service if not running.
     */
    public function trainMl(Request $request)
    {
        $epochs = $request->input('epochs', 100);
        $mlUrl = config('services.puzzle_ml.url', 'http://127.0.0.1:5050');
        $baseUrl = str_replace('/predict', '', $mlUrl);
        $trainUrl = $baseUrl . '/train';

        // Auto-start ML service if not running
        if (! $this->isMlServiceRunning($baseUrl)) {
            $started = $this->startMlService();
            if (! $started) {
                return redirect()->back()->with('error',
                    'ML Service ไม่สามารถเริ่มได้อัตโนมัติ — ตรวจสอบ Python + venv บน server');
            }
            // Wait for service to be ready
            sleep(3);
            if (! $this->isMlServiceRunning($baseUrl)) {
                return redirect()->back()->with('error',
                    'ML Service เริ่มแล้วแต่ยังไม่พร้อม — ลองกดอีกครั้ง');
            }
        }

        try {
            $client = new Client(['timeout' => 600, 'connect_timeout' => 10]);
            $response = $client->post($trainUrl, [
                'form_params' => [
                    'api_url' => config('app.url') . '/api/v1/product/tping',
                    'epochs' => $epochs,
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if ($result['success'] ?? false) {
                $stats = $result['stats'] ?? [];
                $msg = 'ML Model trained! ';
                $msg .= 'Samples: ' . ($stats['samples'] ?? '?');
                $msg .= ' | Avg Error: ' . ($stats['avg_error_px'] ?? '?') . 'px';
                $msg .= ' | Accuracy: ' . ($stats['accuracy_within_20px'] ?? '?') . '%';

                return redirect()->back()->with('success', $msg);
            }

            $error = $result['stderr'] ?? $result['error'] ?? 'Unknown error';

            return redirect()->back()->with('error', 'ML Training failed: ' . substr($error, 0, 200));
        } catch (ConnectException $e) {
            return redirect()->back()->with('error',
                'ML Service ไม่ตอบสนอง — ตรวจสอบ log: storage/logs/ml-service.log');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'ML Training error: ' . $e->getMessage());
        }
    }

    /**
     * Check if ML service is running.
     */
    private function isMlServiceRunning(string $baseUrl): bool
    {
        try {
            $client = new Client(['timeout' => 3, 'connect_timeout' => 2]);
            $response = $client->get($baseUrl . '/health');

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Auto-start ML service via Laravel Process.
     */
    private function startMlService(): bool
    {
        $mlDir = base_path('ml-services/puzzle-solver');
        $logFile = storage_path('logs/ml-service.log');

        if (! is_dir($mlDir)) {
            Log::warning('ML service directory not found: ' . $mlDir);

            return false;
        }

        // Build a bash script that sets up venv + starts gunicorn
        $script = <<<BASH
            cd {$mlDir}

            # Create venv if not exists
            if [ ! -d "venv" ]; then
                python3 -m venv venv 2>&1 || python -m venv venv 2>&1 || exit 1
            fi

            # Install inference deps
            source venv/bin/activate
            pip install -q -r requirements-inference.txt 2>&1 | tail -3

            # Stop old service
            if [ -f ml-service.pid ]; then
                kill \$(cat ml-service.pid) 2>/dev/null || true
                rm -f ml-service.pid
                sleep 1
            fi

            # Start gunicorn in background
            nohup gunicorn -w 2 -b 127.0.0.1:5050 --timeout 600 --pid ml-service.pid app:app > {$logFile} 2>&1 &
            sleep 2
            echo "started"
        BASH;

        try {
            $result = Process::timeout(120)->run(['bash', '-c', $script]);
            Log::info('ML service start: ' . $result->output());

            if (! $result->successful()) {
                Log::error('ML service start failed: ' . $result->errorOutput());
            }

            return str_contains($result->output(), 'started');
        } catch (\Exception $e) {
            Log::error('ML service start exception: ' . $e->getMessage());

            return false;
        }
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
