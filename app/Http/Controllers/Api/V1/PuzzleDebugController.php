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
}
