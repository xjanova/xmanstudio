<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiprayAudioSample;
use App\Models\AiprayTrainingJob;
use App\Services\AiprayAudioStorage;
use App\Services\AiprayDatasetReadiness;
use App\Services\AiprayGpuTrainingRunner;
use App\Services\AiprayMlServiceClient;
use Illuminate\Http\Request;

class AiprayTrainingController extends Controller
{
    public function index()
    {
        try {
            $jobs = AiprayTrainingJob::latest()->paginate(20);
        } catch (\Exception $e) {
            $jobs = collect();
        }

        try {
            $ml = new AiprayMlServiceClient;
            $healthData = $ml->health();
            $mlHealth = ($healthData['status'] ?? null) === 'ok' ? 'healthy' : 'offline';
        } catch (\Exception $e) {
            $mlHealth = 'offline';
        }

        try {
            $stats = [
                'verified_samples' => AiprayAudioSample::verified()->count(),
                'total_jobs' => AiprayTrainingJob::count(),
                'active_jobs' => AiprayTrainingJob::whereIn('status', ['running', 'queued', 'pending'])->count(),
            ];
        } catch (\Exception $e) {
            $stats = [
                'verified_samples' => 0,
                'total_jobs' => 0,
                'active_jobs' => 0,
            ];
        }

        $gpu = $this->gpuPanel(app(AiprayGpuTrainingRunner::class));

        return view('admin.aipray.training.index', compact('jobs', 'mlHealth', 'stats', 'gpu'));
    }

    private function gpuPanel(AiprayGpuTrainingRunner $runner): array
    {
        try {
            $estimate = $runner->estimate('whisper-small', 5);
        } catch (\Throwable $e) {
            $estimate = null;
        }

        try {
            $readiness = app(AiprayDatasetReadiness::class)->check();
            $active = AiprayTrainingJob::gpuActive()->latest()->first();
        } catch (\Throwable $e) {
            // Usually the 2026_09_12 migrations haven't run yet. Keep the page
            // up, with renting switched off, instead of a 500.
            report($e);

            return ['enabled' => false, 'missing' => [], 'estimate' => null, 'readiness' => null, 'active' => null, 'error' => true];
        }

        return [
            'enabled' => $runner->enabled(),
            'missing' => $runner->missingConfig(),
            'estimate' => $estimate,
            'readiness' => $readiness,
            'active' => $active,
            'error' => false,
        ];
    }

    /** Queues a rented-GPU run; the scheduler rents on its next tick. */
    public function gpuStart(Request $request, AiprayGpuTrainingRunner $runner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_model' => 'required|in:whisper-tiny,whisper-base,whisper-small,whisper-medium',
            'epochs' => 'required|integer|min:1|max:30',
            'batch_size' => 'required|integer|min:1|max:64',
            'learning_rate' => 'required|numeric|min:0.0000001|max:0.01',
            'max_cost_usd' => 'required|numeric|min:1|max:200',
            'confirm_cost' => 'accepted',
            'trial' => 'nullable|boolean',
        ]);

        try {
            $job = $runner->queue([
                'name' => $validated['name'],
                'base_model' => $validated['base_model'],
                'epochs' => $validated['epochs'],
                'batch_size' => $validated['batch_size'],
                'learning_rate' => $validated['learning_rate'],
                'optimizer' => 'adamw',
                'train_split' => 90,
            ], (float) $validated['max_cost_usd'], $request->boolean('trial'));
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', 'ยังเริ่มไม่ได้: ' . $e->getMessage());
        }

        return back()->with('success', "เข้าคิวงาน #{$job->id} แล้ว ระบบจะเช่าเครื่องภายใน 1 นาที");
    }

    public function gpuCancel(AiprayTrainingJob $job, AiprayGpuTrainingRunner $runner)
    {
        $runner->cancel($job);

        return back()->with('success', 'สั่งยกเลิกแล้ว ระบบจะคืนเครื่องภายใน 1 นาที');
    }

    public function start(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'base_model' => 'required|in:whisper-tiny,whisper-base,whisper-small,whisper-medium',
            'epochs' => 'required|integer|min:1|max:100',
            'batch_size' => 'required|integer|min:1|max:64',
            'learning_rate' => 'required|numeric|min:0.0000001|max:0.01',
        ]);

        $job = AiprayTrainingJob::create([
            'name' => $request->name,
            'base_model' => $request->base_model,
            'epochs' => $request->epochs,
            'batch_size' => $request->batch_size,
            'learning_rate' => $request->learning_rate,
            'optimizer' => $request->input('optimizer', 'adamw'),
            'train_split' => $request->input('train_split', 80),
            'augmentation' => [
                'noise' => $request->boolean('augment_noise', true),
                'pitch_shift' => $request->boolean('augment_pitch', true),
                'time_stretch' => $request->boolean('augment_time', true),
            ],
            'status' => 'pending',
        ]);

        // Prepare samples for an ML service that shares this server's disk.
        $storage = app(AiprayAudioStorage::class);
        $samples = AiprayAudioSample::trainable()
            ->get()
            // Keys are the ones ml-service's prepare_dataset_from_db() reads.
            ->map(fn ($s) => [
                'chant_id' => $s->chant_id,
                'line_index' => $s->line_index,
                'file_path' => $storage->absolutePath($s->file_path),
                'transcript' => $s->transcript ?? '',
            ])
            ->filter(fn ($s) => $s['file_path'] !== null)
            ->values()
            ->toArray();

        $ml = new AiprayMlServiceClient;
        $result = $ml->startTraining([
            'job_id' => $job->id,
            'base_model' => $job->base_model,
            'learning_rate' => $job->learning_rate,
            'batch_size' => $job->batch_size,
            'epochs' => $job->epochs,
            'optimizer' => $job->optimizer,
            'train_split' => $job->train_split / 100,
            'augmentation' => $job->augmentation,
            'samples' => $samples,
        ]);

        if ($result['success'] ?? false) {
            $job->update(['status' => 'running', 'started_at' => now()]);

            return back()->with('success', "เริ่ม Training Job #{$job->id}");
        }

        $job->update(['status' => 'failed', 'log' => $result['error'] ?? 'ML service error']);

        return back()->with('error', 'ไม่สามารถเริ่ม training ได้: ' . ($result['error'] ?? ''));
    }

    public function progress(AiprayTrainingJob $job)
    {
        return response()->json([
            'id' => $job->id,
            'status' => $job->status,
            'progress' => $job->progress,
            'current_epoch' => $job->current_epoch,
            'epochs' => $job->epochs,
            'training_loss' => $job->training_loss,
            'validation_loss' => $job->validation_loss,
            'wer' => $job->wer,
            'cer' => $job->cer,
            'accuracy' => $job->accuracy,
            'elapsed' => $job->elapsed,
        ]);
    }

    public function stop(AiprayTrainingJob $job)
    {
        $ml = new AiprayMlServiceClient;
        $ml->pauseTraining($job->id);
        $job->update(['status' => 'paused']);

        return back()->with('success', 'หยุด Training ชั่วคราว');
    }

    public function resume(AiprayTrainingJob $job)
    {
        $ml = new AiprayMlServiceClient;
        $ml->resumeTraining($job->id);
        $job->update(['status' => 'running']);

        return back()->with('success', 'เริ่ม Training ต่อ');
    }

    public function cancel(AiprayTrainingJob $job)
    {
        $ml = new AiprayMlServiceClient;
        $ml->cancelTraining($job->id);
        $job->update(['status' => 'cancelled', 'completed_at' => now()]);

        return back()->with('success', 'ยกเลิก Training แล้ว');
    }
}
