<?php

namespace App\Jobs;

use App\Models\MetalXVideo;
use App\Models\Setting;
use App\Services\YouTubeCommentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncVideoCommentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 180;

    protected $video;

    protected $maxComments;

    protected $processEngagement;

    /**
     * Create a new job instance.
     */
    public function __construct(MetalXVideo $video, int $maxComments = 100, bool $processEngagement = true)
    {
        $this->video = $video;
        $this->maxComments = $maxComments;
        $this->processEngagement = $processEngagement;
    }

    /**
     * Execute the job.
     */
    public function handle(YouTubeCommentService $commentService): void
    {
        Log::info("Syncing comments for video {$this->video->youtube_id}");

        try {
            $comments = $commentService->fetchVideoComments($this->video, $this->maxComments);

            Log::info('Synced ' . count($comments) . " comments for video {$this->video->youtube_id}");

            // Dispatch engagement processing if enabled
            if ($this->processEngagement && Setting::get('metalx_auto_engagement', false)) {
                foreach ($comments as $comment) {
                    // Only process top-level comments
                    if (! $comment->isReply()) {
                        ProcessCommentEngagementJob::dispatch($comment);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync comments for video {$this->video->youtube_id}: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SyncVideoCommentsJob failed for video {$this->video->youtube_id}: " . $exception->getMessage());
    }
}
