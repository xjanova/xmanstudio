<?php

namespace App\Jobs;

use App\Models\MetalXVideoProject;
use App\Services\SunoMusicService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMusicJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 300;

    public function __construct(
        protected MetalXVideoProject $project,
        protected bool $autoUpload = false,
    ) {}

    public function handle(SunoMusicService $suno): void
    {
        // Onsite mode — skip API generation, user will upload audio manually
        if ($suno->isOnsiteMode()) {
            $this->project->update([
                'status' => 'draft',
                'error_message' => null,
            ]);

            Log::info("[GenerateMusic] Suno is in onsite mode — skipping API generation for project {$this->project->id}. User must upload audio manually.");

            return;
        }

        if (! $suno->isConfigured()) {
            $this->project->update([
                'status' => 'failed',
                'error_message' => 'Suno API is not configured',
            ]);

            return;
        }

        $this->project->update(['status' => 'generating_music']);

        $style = $this->project->getTemplateSetting('music_style', '');
        $duration = $this->project->getTemplateSetting('music_duration', 60);
        $model = $this->project->getTemplateSetting('music_model', 'V4');

        $prompt = $this->project->getTemplateSetting('music_prompt', $this->project->title ?? 'background music');

        $generation = $suno->generateMusic($prompt, $style, $duration, $model);

        $this->project->update(['music_generation_id' => $generation->id]);

        if ($generation->status === 'processing') {
            CheckMusicStatusJob::dispatch($this->project, $generation, 1, $this->autoUpload)->delay(now()->addSeconds(30));
        } elseif ($generation->status === 'failed') {
            $this->project->update([
                'status' => 'failed',
                'error_message' => $generation->error_message,
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[GenerateMusic] Failed for project {$this->project->id}: {$exception->getMessage()}");

        $this->project->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
