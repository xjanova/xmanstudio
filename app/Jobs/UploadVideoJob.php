<?php

namespace App\Jobs;

use App\Models\MetalXVideoProject;
use App\Services\YouTubeUploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UploadVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 600;

    public function __construct(
        protected MetalXVideoProject $project,
    ) {}

    public function handle(YouTubeUploadService $uploader): void
    {
        $this->project->update(['status' => 'uploading']);

        try {
            $result = $uploader->upload($this->project);

            $this->project->update([
                'status' => 'uploaded',
                'published_at' => now(),
            ]);

            Log::info("[UploadVideo] Uploaded project {$this->project->id} → YouTube {$result['youtube_id']}");
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
        Log::error("[UploadVideo] Failed for project {$this->project->id}: {$exception->getMessage()}");

        $this->project->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
