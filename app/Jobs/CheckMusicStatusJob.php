<?php

namespace App\Jobs;

use App\Models\MetalXMusicGeneration;
use App\Models\MetalXVideoProject;
use App\Services\SunoMusicService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckMusicStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 20;

    public $timeout = 60;

    protected int $attempt;

    public function __construct(
        protected MetalXVideoProject $project,
        protected MetalXMusicGeneration $generation,
        int $attempt = 1,
        protected bool $autoUpload = false,
    ) {
        $this->attempt = $attempt;
    }

    public function handle(SunoMusicService $suno): void
    {
        Log::info("[CheckMusic] Attempt {$this->attempt}/40 for project {$this->project->id}, task: {$this->generation->suno_task_id}");

        $generation = $suno->checkStatus($this->generation);

        Log::info("[CheckMusic] Status after check: {$generation->status}", [
            'project_id' => $this->project->id,
            'attempt' => $this->attempt,
            'audio_url' => $generation->audio_url ? 'present' : 'null',
        ]);

        if ($generation->status === 'completed') {
            // Download the audio file
            $path = $suno->downloadAudio($generation);

            if ($path) {
                $this->project->update(['status' => 'music_ready']);
                Log::info("[CheckMusic] Music ready for project {$this->project->id}, path: {$path}");

                // Auto-render if project has images
                if (! empty($this->project->images)) {
                    RenderVideoJob::dispatch($this->project, $this->autoUpload);
                }
            } else {
                $this->project->update([
                    'status' => 'failed',
                    'error_message' => 'Failed to download audio file from: ' . ($generation->audio_url ?? 'no URL'),
                ]);
            }

            return;
        }

        if ($generation->status === 'failed') {
            $this->project->update([
                'status' => 'failed',
                'error_message' => $generation->error_message ?? 'Music generation failed',
            ]);

            return;
        }

        // Still processing - retry with delay (40 attempts × 30s = 20 minutes max)
        if ($this->attempt < 40) {
            self::dispatch($this->project, $generation, $this->attempt + 1, $this->autoUpload)
                ->delay(now()->addSeconds(30));
        } else {
            $this->project->update([
                'status' => 'failed',
                'error_message' => 'Music generation timed out after 20 minutes',
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[CheckMusic] Failed for project {$this->project->id}: {$exception->getMessage()}");
    }
}
