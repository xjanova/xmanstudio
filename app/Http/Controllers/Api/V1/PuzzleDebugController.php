<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PuzzleDebugImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PuzzleDebugController extends Controller
{
    /**
     * Upload puzzle debug images from the Android app.
     *
     * POST /api/v1/product/{productSlug}/debug-images
     *
     * Accepts multipart form data:
     * - machine_id (required)
     * - app_version
     * - timestamp
     * - detection_method (diff, static, none)
     * - gap_x, slider_x, drag_dist, track_width
     * - images[] (multiple PNG files)
     */
    public function store(Request $request, string $productSlug): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'machine_id'       => 'required|string|max:100',
            'app_version'      => 'nullable|string|max:20',
            'timestamp'        => 'nullable|string|max:50',
            'detection_method' => 'nullable|string|max:30',
            'gap_x'            => 'nullable|integer',
            'slider_x'         => 'nullable|integer',
            'drag_dist'        => 'nullable|integer',
            'track_width'      => 'nullable|integer',
            'images'           => 'required|array|min:1|max:10',
            'images.*'         => 'file|image|max:2048', // max 2MB per image
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $machineId = $request->input('machine_id');
            $shortId = substr($machineId, 0, 8);
            $date = now()->format('Y-m-d');
            $batch = now()->format('His'); // HH:mm:ss as batch ID

            // Store images in: puzzle-debug/{product}/{date}/{machine_short}/{batch}/
            $storagePath = "puzzle-debug/{$productSlug}/{$date}/{$shortId}/{$batch}";
            $savedPaths = [];

            foreach ($request->file('images') as $image) {
                $originalName = $image->getClientOriginalName();
                // Sanitize filename
                $safeName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $originalName);
                $path = $image->storeAs($storagePath, $safeName, 'public');
                $savedPaths[] = $path;
            }

            // Save record to DB
            $record = PuzzleDebugImage::create([
                'machine_id'       => $machineId,
                'app_version'      => $request->input('app_version'),
                'detection_method' => $request->input('detection_method'),
                'gap_x'            => $request->input('gap_x'),
                'slider_x'         => $request->input('slider_x'),
                'drag_dist'        => $request->input('drag_dist'),
                'track_width'      => $request->input('track_width'),
                'image_paths'      => $savedPaths,
                'metadata'         => [
                    'product'   => $productSlug,
                    'timestamp' => $request->input('timestamp'),
                    'ip'        => $request->ip(),
                ],
                'captured_at'      => now(),
            ]);

            Log::info("PuzzleDebug: stored {$record->id} with " . count($savedPaths) . " images from {$shortId}");

            return response()->json([
                'success' => true,
                'id'      => $record->id,
                'count'   => count($savedPaths),
            ], 201);

        } catch (\Exception $e) {
            Log::error("PuzzleDebug upload failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Upload failed',
            ], 500);
        }
    }

    /**
     * List debug images (admin/API access for reviewing).
     *
     * GET /api/v1/product/{productSlug}/debug-images
     */
    public function index(Request $request, string $productSlug): JsonResponse
    {
        $query = PuzzleDebugImage::query()
            ->where('metadata->product', $productSlug)
            ->orderByDesc('created_at');

        // Filters
        if ($machineId = $request->query('machine_id')) {
            $query->forMachine($machineId);
        }
        if ($request->query('unlabeled') === 'true') {
            $query->unlabeled();
        }
        if ($method = $request->query('method')) {
            $query->where('detection_method', $method);
        }

        $records = $query->paginate(20);

        // Add image URLs to each record
        $records->getCollection()->transform(function ($record) {
            $record->image_urls = $record->image_urls;
            return $record;
        });

        return response()->json([
            'success' => true,
            'data'    => $records,
        ]);
    }

    /**
     * Update label (actual_gap_x) for training data.
     *
     * PUT /api/v1/product/{productSlug}/debug-images/{id}/label
     */
    public function updateLabel(Request $request, string $productSlug, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'actual_gap_x' => 'required|integer|min:0|max:3000',
            'success'      => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $record = PuzzleDebugImage::findOrFail($id);
        $record->update([
            'actual_gap_x' => $request->input('actual_gap_x'),
            'success'      => $request->input('success', $record->success),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $record,
        ]);
    }

    /**
     * Get stats summary for debug data.
     *
     * GET /api/v1/product/{productSlug}/debug-images/stats
     */
    public function stats(Request $request, string $productSlug): JsonResponse
    {
        $base = PuzzleDebugImage::where('metadata->product', $productSlug);

        $total = (clone $base)->count();
        $labeled = (clone $base)->labeled()->count();
        $unlabeled = (clone $base)->unlabeled()->count();

        // Accuracy: where actual_gap_x is set, how close was gap_x?
        $accuracyData = (clone $base)->labeled()
            ->selectRaw('AVG(ABS(gap_x - actual_gap_x)) as avg_error')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(CASE WHEN ABS(gap_x - actual_gap_x) <= 20 THEN 1 ELSE 0 END) as within_20px')
            ->first();

        $byMethod = (clone $base)->labeled()
            ->selectRaw('detection_method, COUNT(*) as count, AVG(ABS(gap_x - actual_gap_x)) as avg_error')
            ->groupBy('detection_method')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'total'       => $total,
                'labeled'     => $labeled,
                'unlabeled'   => $unlabeled,
                'avg_error'   => round($accuracyData->avg_error ?? 0, 1),
                'within_20px' => $accuracyData->within_20px ?? 0,
                'accuracy_pct' => $labeled > 0
                    ? round(($accuracyData->within_20px / $labeled) * 100, 1)
                    : 0,
                'by_method'   => $byMethod,
            ],
        ]);
    }

    /**
     * Export labeled data as JSON for ML training.
     *
     * GET /api/v1/product/{productSlug}/debug-images/export
     */
    public function export(Request $request, string $productSlug): JsonResponse
    {
        $records = PuzzleDebugImage::where('metadata->product', $productSlug)
            ->labeled()
            ->orderBy('created_at')
            ->get()
            ->map(function ($record) {
                return [
                    'id'               => $record->id,
                    'machine_id'       => $record->machine_id,
                    'detection_method' => $record->detection_method,
                    'gap_x'            => $record->gap_x,
                    'actual_gap_x'     => $record->actual_gap_x,
                    'slider_x'         => $record->slider_x,
                    'drag_dist'        => $record->drag_dist,
                    'track_width'      => $record->track_width,
                    'error_px'         => abs($record->gap_x - $record->actual_gap_x),
                    'success'          => $record->success,
                    'image_urls'       => $record->image_urls,
                    'captured_at'      => $record->captured_at?->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'count'   => $records->count(),
            'data'    => $records,
        ]);
    }

    /**
     * Receive auto-feedback from app (success/fail + actual gap position).
     * Auto-labels the most recent debug image record for this machine.
     *
     * POST /api/v1/product/{productSlug}/debug-images/feedback
     */
    public function feedback(Request $request, string $productSlug): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'machine_id'       => 'required|string|max:100',
            'success'          => 'required|boolean',
            'detected_gap_x'   => 'required|integer',
            'actual_gap_x'     => 'nullable|integer',
            'attempt'          => 'nullable|integer',
            'detection_method' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            // Find the most recent debug image record for this machine (within last 5 min)
            $record = PuzzleDebugImage::where('machine_id', $request->input('machine_id'))
                ->where('created_at', '>=', now()->subMinutes(5))
                ->orderByDesc('created_at')
                ->first();

            $detectedGapX = $request->input('detected_gap_x');
            $actualGapX = $request->input('actual_gap_x', $request->boolean('success') ? $detectedGapX : null);

            if ($record) {
                // Update existing record with feedback
                $record->update([
                    'success'      => $request->boolean('success'),
                    'actual_gap_x' => $actualGapX,
                    'metadata'     => array_merge($record->metadata ?? [], [
                        'feedback_attempt' => $request->input('attempt'),
                        'feedback_at'      => now()->toISOString(),
                    ]),
                ]);

                Log::info("PuzzleDebug feedback: record #{$record->id} " .
                    "success={$request->input('success')} actual_gap_x={$actualGapX}");
            } else {
                // No recent debug image — create a feedback-only record
                $record = PuzzleDebugImage::create([
                    'machine_id'       => $request->input('machine_id'),
                    'app_version'      => $request->input('app_version'),
                    'detection_method' => $request->input('detection_method'),
                    'gap_x'            => $detectedGapX,
                    'actual_gap_x'     => $actualGapX,
                    'success'          => $request->boolean('success'),
                    'metadata'         => [
                        'product'          => $productSlug,
                        'feedback_only'    => true,
                        'feedback_attempt' => $request->input('attempt'),
                        'timestamp'        => $request->input('timestamp'),
                    ],
                    'captured_at'      => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'id'      => $record->id,
                'labeled' => $actualGapX !== null,
            ]);

        } catch (\Exception $e) {
            Log::error("PuzzleDebug feedback failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Feedback failed'], 500);
        }
    }

    /**
     * Server-side inference: receive screenshots, return predicted gap_x.
     *
     * Currently uses the labeled data to compute average offset corrections.
     * When enough data is collected, this can be upgraded to a real ML model.
     *
     * POST /api/v1/product/{productSlug}/debug-images/infer
     */
    public function infer(Request $request, string $productSlug): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'machine_id'    => 'required|string|max:100',
            'slider_x'      => 'required|integer',
            'slider_y'      => 'required|integer',
            'move_distance'  => 'required|integer',
            'track_width'    => 'required|integer',
            'before'         => 'required|file|image|max:5120',
            'after'          => 'required|file|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $machineId = $request->input('machine_id');
            $sliderX = (int) $request->input('slider_x');
            $trackWidth = (int) $request->input('track_width');

            // Store images for future training
            $shortId = substr($machineId, 0, 8);
            $date = now()->format('Y-m-d');
            $batch = now()->format('His');
            $storagePath = "puzzle-debug/{$productSlug}/{$date}/{$shortId}/infer_{$batch}";

            $beforePath = $request->file('before')->storeAs($storagePath, 'before.png', 'public');
            $afterPath = $request->file('after')->storeAs($storagePath, 'after.png', 'public');

            // Save record
            $record = PuzzleDebugImage::create([
                'machine_id'       => $machineId,
                'app_version'      => $request->input('app_version'),
                'detection_method' => 'server_infer',
                'slider_x'         => $sliderX,
                'track_width'      => $trackWidth,
                'image_paths'      => [$beforePath, $afterPath],
                'metadata'         => [
                    'product'       => $productSlug,
                    'move_distance' => (int) $request->input('move_distance'),
                    'slider_y'      => (int) $request->input('slider_y'),
                    'inference'     => true,
                ],
                'captured_at'      => now(),
            ]);

            // === INFERENCE LOGIC ===
            // Phase 1: Statistical correction based on labeled data
            // Calculate average detection error from successful solves
            $correction = $this->computeCorrection($machineId);

            // Phase 2: Check if Python ML service is available
            $mlResult = $this->callMlService($beforePath, $afterPath, $request->all());

            if ($mlResult !== null) {
                // ML model available — use its prediction
                $record->update([
                    'gap_x' => $mlResult['gap_x'],
                    'metadata' => array_merge($record->metadata ?? [], [
                        'source' => 'ml_model',
                        'ml_confidence' => $mlResult['confidence'],
                    ]),
                ]);

                return response()->json([
                    'success'    => true,
                    'gap_x'      => $mlResult['gap_x'],
                    'confidence' => $mlResult['confidence'],
                    'source'     => 'ml_model',
                    'record_id'  => $record->id,
                ]);
            }

            // Phase 1 fallback: return correction hint (app applies to its own detection)
            return response()->json([
                'success'    => true,
                'gap_x'      => null, // no prediction yet — not enough data or no ML
                'confidence' => 0.0,
                'correction' => $correction, // average px offset to add to detected gap_x
                'source'     => 'statistical',
                'record_id'  => $record->id,
                'message'    => 'ML model not ready. Collecting data for training.',
            ]);

        } catch (\Exception $e) {
            Log::error("PuzzleDebug infer failed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Inference failed'], 500);
        }
    }

    /**
     * Compute average detection error correction from labeled data.
     */
    private function computeCorrection(string $machineId): float
    {
        // Use labeled data where we know both detected and actual gap_x
        $data = PuzzleDebugImage::labeled()
            ->whereNotNull('gap_x')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('AVG(actual_gap_x - gap_x) as avg_correction')
            ->first();

        return round($data->avg_correction ?? 0, 1);
    }

    /**
     * Call Python ML microservice if available.
     * Returns ['gap_x' => int, 'confidence' => float] or null if service unavailable.
     */
    private function callMlService(string $beforePath, string $afterPath, array $params): ?array
    {
        $mlUrl = config('services.puzzle_ml.url', 'http://127.0.0.1:5050/predict');

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 10, 'connect_timeout' => 3]);
            $response = $client->post($mlUrl, [
                'multipart' => [
                    ['name' => 'before', 'contents' => Storage::disk('public')->readStream($beforePath), 'filename' => 'before.png'],
                    ['name' => 'after', 'contents' => Storage::disk('public')->readStream($afterPath), 'filename' => 'after.png'],
                    ['name' => 'slider_x', 'contents' => (string) ($params['slider_x'] ?? 0)],
                    ['name' => 'slider_y', 'contents' => (string) ($params['slider_y'] ?? 0)],
                    ['name' => 'move_distance', 'contents' => (string) ($params['move_distance'] ?? 0)],
                    ['name' => 'track_width', 'contents' => (string) ($params['track_width'] ?? 0)],
                ],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            if (isset($result['gap_x']) && isset($result['confidence'])) {
                return $result;
            }
        } catch (\Exception $e) {
            // ML service not running — that's OK, use statistical fallback
            Log::debug("PuzzleML service unavailable: " . $e->getMessage());
        }

        return null;
    }
}
