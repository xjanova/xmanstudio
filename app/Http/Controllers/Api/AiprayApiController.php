<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiprayAiModel;
use App\Models\AiprayAudioSample;
use App\Models\AiprayChant;
use App\Models\AiprayContributor;
use App\Models\AiprayPrayerSession;
use App\Models\AiprayTrainingJob;
use App\Models\Product;
use App\Services\AiprayAudioStorage;
use App\Services\AiprayAutoLabeler;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
     *
     * Apps from 1.2.5 also send client_sample_id (same on every retry),
     * contributor_id + contributor_key_hash (lets that phone erase its voice
     * later) and consent_version. Older apps send none of these.
     * Contract: docs/audio-upload-contract.md in the Aipray repo.
     */
    public function uploadAudio(
        Request $request,
        AiprayAudioStorage $storage,
        AiprayAutoLabeler $labeler,
    ): JsonResponse {
        $validated = $request->validate([
            // Both end up in the stored file name.
            'chant_id' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9_-]+$/'],
            'format' => 'nullable|in:m4a,wav',
            'line_index' => 'required|integer|min:0',
            'audio_data' => 'required|string',
            'duration_ms' => 'required|integer|min:0',
            'device_info' => 'nullable|string|max:255',
            'client_sample_id' => 'nullable|uuid',
            'contributor_id' => 'nullable|uuid|required_with:contributor_key_hash',
            'contributor_key_hash' => ['nullable', 'string', 'regex:/^[0-9a-f]{64}$/', 'required_with:contributor_id'],
            'consent_version' => 'nullable|string|max:32',
        ]);

        $sampleId = $validated['client_sample_id'] ?? null;
        if ($sampleId && ($existing = AiprayAudioSample::where('client_sample_id', $sampleId)->first())) {
            // A retry after a lost response: the phone already has what it needs.
            return response()->json(['success' => true, 'id' => $existing->id, 'duplicate' => true]);
        }

        $contributorId = $validated['contributor_id'] ?? null;
        if ($contributorId && ! $this->bindContributor($contributorId, $validated['contributor_key_hash'])) {
            return response()->json(['error' => 'Contributor key mismatch'], 409);
        }

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
                now()->format('YmdHis') . '_' . Str::random(8),
                $format
            );

            $path = "aipray_audio/{$filename}";
            $storage->put($path, $audioBytes);

            $transcript = $labeler->transcriptFor($validated['chant_id'], $validated['line_index']);

            try {
                $sample = AiprayAudioSample::create([
                    'filename' => $filename,
                    'original_name' => $filename,
                    'file_path' => $path,
                    'chant_id' => $validated['chant_id'],
                    'line_index' => $validated['line_index'],
                    'transcript' => $transcript,
                    'duration' => $validated['duration_ms'] / 1000.0,
                    'sample_rate' => 16000,
                    'format' => $format,
                    'file_size' => strlen($audioBytes),
                    'status' => $transcript ? AiprayAutoLabeler::STATUS : 'unlabeled',
                    'device_info' => $validated['device_info'] ?? 'unknown',
                    'client_sample_id' => $sampleId,
                    'contributor_id' => $contributorId,
                    'consent_version' => $validated['consent_version'] ?? null,
                    'metadata' => [
                        'uploaded_at' => now()->toIso8601String(),
                        'ip' => $request->ip(),
                        'app_version' => $request->header('X-App-Version', 'unknown'),
                    ],
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // The same retry raced us between the lookup and the insert.
                $storage->delete($path);
                $existing = AiprayAudioSample::where('client_sample_id', $sampleId)->firstOrFail();

                return response()->json(['success' => true, 'id' => $existing->id, 'duplicate' => true]);
            }

            return response()->json(['success' => true, 'id' => $sample->id], 201);
        } catch (\Exception $e) {
            Log::error('Aipray audio upload failed: ' . $e->getMessage());

            return response()->json(['error' => 'Upload failed'], 500);
        }
    }

    /**
     * The first upload with a contributor_id binds its key hash; later ones
     * must match it, or they could erase or impersonate that phone.
     */
    private function bindContributor(string $contributorId, string $keyHash): bool
    {
        try {
            $contributor = AiprayContributor::firstOrCreate(
                ['contributor_id' => $contributorId],
                ['key_hash' => $keyHash],
            );
        } catch (UniqueConstraintViolationException $e) {
            $contributor = AiprayContributor::where('contributor_id', $contributorId)->firstOrFail();
        }

        return hash_equals($contributor->key_hash, $keyHash);
    }

    /**
     * POST /api/aipray/contributors/erase
     * Delete every voice sample one phone uploaded ("ลบข้อมูลเสียงของฉัน").
     *
     * The phone proves ownership with the secret whose SHA-256 its uploads
     * carried. Unknown contributors get deleted:0 rather than 404, because
     * the app retries 404s forever. Repeating the request is harmless.
     */
    public function eraseContributor(Request $request, AiprayAudioStorage $storage): JsonResponse
    {
        $validated = $request->validate([
            'contributor_id' => 'required|uuid',
            'secret' => ['required', 'string', 'regex:/^[A-Za-z0-9_-]{32,128}$/'],
        ]);

        $contributor = AiprayContributor::where('contributor_id', $validated['contributor_id'])->first();
        if (! $contributor) {
            return response()->json(['deleted' => 0]);
        }

        if (! $contributor->ownsSecret($validated['secret'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $deleted = 0;
        AiprayAudioSample::where('contributor_id', $contributor->contributor_id)
            ->chunkById(200, function ($samples) use ($storage, &$deleted) {
                foreach ($samples as $sample) {
                    $storage->delete($sample->file_path);
                    $sample->delete();
                    $deleted++;
                }
            });
        $contributor->delete();

        return response()->json(['deleted' => $deleted]);
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

        $chants = $query->limit(500)->get()->map(fn ($c) => [
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

        if (! $model) {
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
                ? URL::temporarySignedRoute(
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
            ->map(fn ($c) => [
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
                    $start = Carbon::parse($s->start_time);
                    $end = Carbon::parse($s->end_time);
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
    public function downloadModel(int $model): BinaryFileResponse|JsonResponse
    {
        $aiModel = AiprayAiModel::findOrFail($model);

        // Aipray set to Coming Soon: no file of it for anyone, like its APK (owner, 2026-10-12)
        if (Product::slugIsComingSoon('aipray')) {
            return response()->json(['success' => false, 'error' => 'Coming soon: this product is not available for download yet', 'coming_soon' => true], 403);
        }

        if (! $aiModel->onnx_file_path || ! Storage::disk('local')->exists($aiModel->onnx_file_path)) {
            return response()->json(['error' => 'Model file not found'], 404);
        }

        return response()->download(
            Storage::disk('local')->path($aiModel->onnx_file_path),
            "aipray-model-{$aiModel->version}.onnx"
        );
    }

    /**
     * GET /api/aipray/ml/datasets/{job} (signed URL)
     * The training archive for a rented GPU, only while that run is live.
     */
    public function downloadDataset(AiprayTrainingJob $job): BinaryFileResponse|JsonResponse
    {
        $disk = Storage::disk(AiprayAudioStorage::DISK);
        if (! in_array($job->gpu_state, ['booting', 'training'], true)
            || ! $job->dataset_path || ! $disk->exists($job->dataset_path)) {
            return response()->json(['error' => 'Dataset no longer available'], 410);
        }

        return response()->download($disk->path($job->dataset_path), "aipray-dataset-{$job->id}.zip");
    }

    /**
     * POST /api/aipray/ml/training-callback
     * Internal callback from ML service when training updates occur.
     */
    public function mlCallback(Request $request): JsonResponse
    {
        $configured = (string) config('services.aipray_ml.secret');

        // Closed until a secret is set. 'Bearer ' alone was never empty, so the old
        // "no secret configured" check could not fire.
        if ($configured === '') {
            return response()->json(['error' => 'ML callback not configured'], 503);
        }

        if (! hash_equals('Bearer ' . $configured, (string) $request->header('Authorization'))) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $jobId = $request->input('job_id');
        $status = $request->input('status');

        $job = AiprayTrainingJob::find($jobId);
        if (! $job) {
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
        ], fn ($v) => $v !== null);

        if ($status === 'running' && ! $job->started_at) {
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
