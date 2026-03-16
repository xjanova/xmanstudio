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
    ) {
        $this->attempt = $attempt;
    }

    public function handle(SunoMusicService $suno): void
    {
        $generation = $suno->checkStatus($this->generation);

        if ($generation->status === 'completed') {
            // Download the audio file
            $path = $suno->downloadAudio($generation);

            if ($path) {
                $this->project->update(['status' => 'music_ready']);
                Log::info("[CheckMusic] Music ready for project {$this->project->id}");

                // Auto-render if project has images
                if (! empty($this->project->images)) {
                    RenderVideoJob::dispatch($this->project);
                }
            } else {
                $this->project->update([
                    'status' => 'failed',
                    'error_message' => 'Failed to download audio file',
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

        // Still processing - retry with delay
        if ($this->attempt < 20) {
            self::dispatch($this->project, $generation, $this->attempt + 1)
                ->delay(now()->addSeconds(30));
        } else {
            $this->project->update([
                'status' => 'failed',
                'error_message' => 'Music generation timed out after 10 minutes',
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[CheckMusic] Failed for project {$this->project->id}: {$exception->getMessage()}");
    }
}
