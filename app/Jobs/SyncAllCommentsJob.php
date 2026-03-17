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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncAllCommentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 900; // 15 minutes max

    protected string $progressKey;

    protected int $maxComments;

    protected bool $processEngagement;

    protected ?int $channelId;

    public function __construct(string $progressKey, int $maxComments = 50, bool $processEngagement = true, ?int $channelId = null)
    {
        $this->progressKey = $progressKey;
        $this->maxComments = $maxComments;
        $this->processEngagement = $processEngagement;
        $this->channelId = $channelId;
    }

    public function handle(YouTubeCommentService $commentService): void
    {
        set_time_limit(0);

        $query = MetalXVideo::where('is_active', true);

        if ($this->channelId) {
            $query->where('metal_x_channel_id', $this->channelId);
        }

        $videos = $query->get();

        Cache::put($this->progressKey, [
            'status' => 'running',
            'total_videos' => $videos->count(),
            'videos_done' => 0,
            'total_comments' => 0,
            'current_video' => '',
            'errors' => 0,
        ], 900);

        $totalComments = 0;
        $videosDone = 0;
        $errors = 0;

        foreach ($videos as $video) {
            try {
                Cache::put($this->progressKey, array_merge(
                    Cache::get($this->progressKey, []),
                    ['current_video' => $video->title ?? $video->youtube_id]
                ), 900);

                $comments = $commentService->fetchVideoComments($video, $this->maxComments);
                $totalComments += count($comments);

                // Dispatch engagement processing if enabled
                if ($this->processEngagement && Setting::get('metalx_auto_engagement', false)) {
                    foreach ($comments as $comment) {
                        if (! $comment->isReply()) {
                            ProcessCommentEngagementJob::dispatchAfterResponse($comment);
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                Log::warning("[SyncAllComments] Error syncing comments for video {$video->youtube_id}: " . $e->getMessage());
            }

            $videosDone++;

            Cache::put($this->progressKey, [
                'status' => 'running',
                'total_videos' => $videos->count(),
                'videos_done' => $videosDone,
                'total_comments' => $totalComments,
                'current_video' => $video->title ?? $video->youtube_id,
                'errors' => $errors,
            ], 900);
        }

        Cache::put($this->progressKey, [
            'status' => 'completed',
            'total_videos' => $videos->count(),
            'videos_done' => $videosDone,
            'total_comments' => $totalComments,
            'current_video' => '',
            'errors' => $errors,
        ], 900);

        Log::info("[SyncAllComments] Complete: synced {$totalComments} comments from {$videosDone} videos ({$errors} errors)");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[SyncAllComments] Job failed: ' . $exception->getMessage());

        Cache::put($this->progressKey, [
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ], 900);
    }
}
