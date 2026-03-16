<?php

namespace App\Jobs;

use App\Models\MetalXVideoProject;
use App\Services\VideoRenderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RenderVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 600;

    public function __construct(
        protected MetalXVideoProject $project,
    ) {}

    public function handle(VideoRenderService $renderer): void
    {
        $this->project->update(['status' => 'rendering']);

        try {
            $renderer->render($this->project);
            $this->project->update(['status' => 'rendered']);

            Log::info("[RenderVideo] Render complete for project {$this->project->id}");

            // Auto-upload if scheduled
            if ($this->project->scheduled_at && $this->project->scheduled_at->isPast()) {
                UploadVideoJob::dispatch($this->project);
            }
        } catch (\Exception $e) {
            $this->project->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[RenderVideo] Failed for project {$this->project->id}: {$exception->getMessage()}");

        $this->project->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
