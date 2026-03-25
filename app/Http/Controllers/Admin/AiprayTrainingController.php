<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiprayAudioSample;
use App\Models\AiprayTrainingJob;
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

        return view('admin.aipray.training.index', compact('jobs', 'mlHealth', 'stats'));
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

        // Prepare samples for ML service
        $samples = AiprayAudioSample::whereIn('status', ['labeled', 'verified'])
            ->get()
            ->map(fn ($s) => [
                'chant_id' => $s->chant_id,
                'line_index' => $s->line_index,
                'audio_path' => storage_path("app/public/{$s->file_path}"),
                'transcription' => $s->transcript ?? '',
            ])
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
