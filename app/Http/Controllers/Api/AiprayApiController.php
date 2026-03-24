<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiprayAudioSample;
use App\Models\AiprayChant;
use App\Models\AiprayAiModel;
use App\Models\AiprayPrayerSession;
use App\Models\AiprayTrainingJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiprayApiController extends Controller
{
    /**
     * POST /api/aipray/sessions
     * Store a prayer session from the Flutter app.
     */
    public function storeSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'nullable|string|max:100',
            'chantId' => 'required|string|max:100',
            'chantTitle' => 'required|string|max:255',
            'startTime' => 'required|date',
            'endTime' => 'nullable|date',
            'roundsCompleted' => 'nullable|integer|min:0',
            'totalLines' => 'nullable|integer|min:0',
            'linesReached' => 'nullable|integer|min:0',
            'usedVoiceTracking' => 'nullable|boolean',
        ]);

        $session = AiprayPrayerSession::create([
            'session_uuid' => $validated['id'] ?? Str::uuid()->toString(),
            'device_id' => $request->header('X-Device-Id'),
            'chant_id' => $validated['chantId'],
            'chant_title' => $validated['chantTitle'],
            'start_time' => $validated['startTime'],
            'end_time' => $validated['endTime'] ?? null,
            'rounds_completed' => $validated['roundsCompleted'] ?? 0,
            'total_lines' => $validated['totalLines'] ?? 0,
            'lines_reached' => $validated['linesReached'] ?? 0,
            'used_voice_tracking' => $validated['usedVoiceTracking'] ?? false,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['success' => true, 'id' => $session->id], 201);
    }

    /**
     * POST /api/aipray/audio/upload
     * Receive base64-encoded audio from the Flutter app for AI training.
     */
    public function uploadAudio(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'chant_id' => 'required|string|max:100',
            'line_index' => 'required|integer|min:0',
            'audio_data' => 'required|string',
            'duration_ms' => 'required|integer|min:0',
            'format' => 'nullable|string|max:10',
            'device_info' => 'nullable|string|max:255',
        ]);

        try {
            $audioBytes = base64_decode($validated['audio_data'], true);
            if ($audioBytes === false) {
                return response()->json(['error' => 'Invalid base64 audio data'], 422);
            }

            // Limit to 10MB
            if (strlen($audioBytes) > 10 * 1024 * 1024) {
                return response()->json(['error' => 'Audio file too large'], 422);
            }

            $format = $validated['format'] ?? 'wav';
            $filename = sprintf(
                '%s_line%d_%s.%s',
                $validated['chant_id'],
                $validated['line_index'],
                now()->format('YmdHis') . '_' . Str::random(4),
                $format
            );

            $path = "aipray_audio/{$filename}";
            Storage::disk('local')->put($path, $audioBytes);

            $sample = AiprayAudioSample::create([
                'filename' => $filename,
                'original_name' => $filename,
                'file_path' => $path,
                'chant_id' => $validated['chant_id'],
                'line_index' => $validated['line_index'],
                'duration' => $validated['duration_ms'] / 1000.0,
                'sample_rate' => 16000,
                'format' => $format,
                'file_size' => strlen($audioBytes),
                'status' => 'unlabeled',
                'device_info' => $validated['device_info'] ?? 'unknown',
                'metadata' => [
                    'uploaded_at' => now()->toIso8601String(),
                    'ip' => $request->ip(),
                    'app_version' => $request->header('X-App-Version', 'unknown'),
                ],
            ]);

            return response()->json(['success' => true, 'id' => $sample->id], 201);
        } catch (\Exception $e) {
            Log::error('Aipray audio upload failed: ' . $e->getMessage());
            return response()->json(['error' => 'Upload failed'], 500);
        }
    }

    /**
     * POST /api/aipray/chants/sync
     * Sync chants with delta token.
     */
    public function syncChants(Request $request): JsonResponse
    {
        $syncToken = $request->input('sync_token');

        $query = AiprayChant::active()->orderBy('sort_order');

        if ($syncToken) {
            $query->where('updated_at_token', '>', $syncToken);
        }

        $chants = $query->limit(500)->get()->map(fn($c) => [
            'id' => $c->chant_id,
            'title' => $c->title_th,
            'title_en' => $c->title_en,
            'category' => $c->category,
            'lines' => $c->lines,
            'is_community' => $c->is_community,
            'author' => $c->author,
        ]);

        $latestToken = AiprayChant::active()
            ->orderByDesc('updated_at_token')
            ->value('updated_at_token') ?? '';

        return response()->json([
            'chants' => $chants,
            'sync_token' => $latestToken,
            'total' => $chants->count(),
        ]);
    }

    /**
     * GET /api/aipray/models/latest
     * Check for the latest deployed AI model.
     */
    public function latestModel(Request $request): JsonResponse
    {
        $currentVersion = $request->query('current_version');

        $model = AiprayAiModel::where('status', 'deployed')
            ->latest()
            ->first();

        if (!$model) {
            return response()->json(['update_available' => false]);
        }

        $isNewer = $currentVersion ? version_compare($model->version ?? '0', $currentVersion, '>') : true;

        return response()->json([
            'update_available' => $isNewer,
            'model_id' => $model->id,
            'version' => $model->version,
            'size_mb' => round(($model->file_size ?? 0) / 1048576, 1),
            'accuracy' => $model->accuracy,
            'url' => $model->onnx_file_path
                ? \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'aipray.model.download',
                    now()->addHours(6),
                    ['model' => $model->id]
                )
                : null,
        ]);
    }

    /**
     * GET /api/aipray/chants/community
     * Fetch community-contributed chants.
     */
    public function communityChants(): JsonResponse
    {
        $chants = AiprayChant::community()
            ->orderBy('sort_order')
            ->get()
            ->map(fn($c) => [
                'id' => $c->chant_id,
                'title' => $c->title_th,
                'category' => $c->category,
                'lines' => $c->lines,
                'author' => $c->author,
            ]);

        return response()->json(['chants' => $chants]);
    }

    /**
     * GET /api/aipray/stats
     * Global statistics.
     */
    public function stats(): JsonResponse
    {
        // Calculate total hours in a database-agnostic way
        $totalSeconds = 0;
        AiprayPrayerSession::whereNotNull('end_time')
            ->whereNotNull('start_time')
            ->select(['start_time', 'end_time'])
            ->chunk(500, function ($sessions) use (&$totalSeconds) {
                foreach ($sessions as $s) {
                    $start = \Carbon\Carbon::parse($s->start_time);
                    $end = \Carbon\Carbon::parse($s->end_time);
                    $totalSeconds += max(0, $end->diffInSeconds($start));
                }
            });

        return response()->json([
            'total_sessions' => AiprayPrayerSession::count(),
            'total_audio_samples' => AiprayAudioSample::count(),
            'total_chanting_hours' => round($totalSeconds / 3600, 1),
            'total_users' => AiprayPrayerSession::distinct('device_id')->count('device_id'),
            'active_models' => AiprayAiModel::whereIn('status', ['active', 'deployed'])->count(),
            'contributors' => AiprayAudioSample::distinct('device_info')->count('device_info'),
        ]);
    }

    /**
     * GET /api/aipray/models/{model}/download (signed URL)
     * Securely download the ONNX model file.
     */
    public function downloadModel(int $model): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        $aiModel = AiprayAiModel::findOrFail($model);

        if (!$aiModel->onnx_file_path || !Storage::disk('local')->exists($aiModel->onnx_file_path)) {
            return response()->json(['error' => 'Model file not found'], 404);
        }

        return response()->download(
            Storage::disk('local')->path($aiModel->onnx_file_path),
            "aipray-model-{$aiModel->version}.onnx"
        );
    }

    /**
     * POST /api/aipray/ml/training-callback
     * Internal callback from ML service when training updates occur.
     */
    public function mlCallback(Request $request): JsonResponse
    {
        $secret = $request->header('Authorization');
        $expectedSecret = 'Bearer ' . config('services.aipray_ml.secret');

        if (!$secret || !$expectedSecret || !hash_equals($expectedSecret, (string) $secret)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $jobId = $request->input('job_id');
        $status = $request->input('status');

        $job = AiprayTrainingJob::find($jobId);
        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $updates = array_filter([
            'status' => $status,
            'current_epoch' => $request->input('current_epoch'),
            'training_loss' => $request->input('training_loss'),
            'validation_loss' => $request->input('validation_loss'),
            'wer' => $request->input('wer'),
            'cer' => $request->input('cer'),
            'accuracy' => $request->input('accuracy'),
            'log' => $request->input('log'),
        ], fn($v) => $v !== null);

        if ($status === 'running' && !$job->started_at) {
            $updates['started_at'] = now();
        }
        if (in_array($status, ['completed', 'failed', 'cancelled'])) {
            $updates['completed_at'] = now();
        }

        // Append to loss/metrics history
        if ($request->has('training_loss') && $request->has('current_epoch')) {
            $lossHistory = $job->loss_history ?? [];
            $lossHistory[] = [
                'epoch' => $request->input('current_epoch'),
                'train_loss' => $request->input('training_loss'),
                'val_loss' => $request->input('validation_loss'),
            ];
            $updates['loss_history'] = $lossHistory;
        }

        $job->update($updates);

        // Auto-create model record on completion
        if ($status === 'completed' && $request->has('model_path')) {
            AiprayAiModel::create([
                'name' => "Aipray Model (Job #{$jobId})",
                'version' => now()->format('Y.m.d'),
                'base_model' => $job->base_model,
                'training_job_id' => $jobId,
                'file_path' => $request->input('model_path'),
                'accuracy' => $request->input('accuracy'),
                'wer' => $request->input('wer'),
                'cer' => $request->input('cer'),
                'total_samples_trained' => AiprayAudioSample::count(),
                'status' => 'active',
            ]);
        }

        return response()->json(['success' => true]);
    }
}
