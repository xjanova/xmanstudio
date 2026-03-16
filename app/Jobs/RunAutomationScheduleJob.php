<?php

namespace App\Jobs;

use App\Models\MetalXAutomationLog;
use App\Models\MetalXAutomationSchedule;
use App\Models\MetalXVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RunAutomationScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 300;

    public function handle(): void
    {
        if (! config('metalx.automation.enabled', true)) {
            Log::info('[Metal-X Automation] Automation is disabled via config');

            return;
        }

        $schedules = MetalXAutomationSchedule::enabled()->due()->with('video')->get();

        if ($schedules->isEmpty()) {
            return;
        }

        Log::info("[Metal-X Automation] Processing {$schedules->count()} due schedules");

        foreach ($schedules as $schedule) {
            try {
                $this->processSchedule($schedule);
            } catch (\Exception $e) {
                Log::error("[Metal-X Automation] Schedule {$schedule->id} failed: " . $e->getMessage());
                MetalXAutomationLog::log($schedule->action_type, 'failed', [
                    'video_id' => $schedule->video_id,
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function processSchedule(MetalXAutomationSchedule $schedule): void
    {
        $dispatched = 0;

        switch ($schedule->action_type) {
            case 'sync_comments':
                $dispatched = $this->handleSyncComments($schedule);
                break;

            case 'auto_reply':
                $dispatched = $this->handleAutoReply($schedule);
                break;

            case 'auto_like':
                $dispatched = $this->handleAutoLike($schedule);
                break;

            case 'auto_moderate':
                $dispatched = $this->handleAutoModerate($schedule);
                break;

            case 'promo_comment':
                $dispatched = $this->handlePromoComment($schedule);
                break;

            default:
                Log::warning("[Metal-X Automation] Unknown action type: {$schedule->action_type}");

                return;
        }

        $schedule->markRun();

        MetalXAutomationLog::log($schedule->action_type, 'success', [
            'video_id' => $schedule->video_id,
            'details' => ['dispatched' => $dispatched],
        ]);

        Log::info("[Metal-X Automation] {$schedule->action_type}: dispatched {$dispatched} jobs");
    }

    protected function handleSyncComments(MetalXAutomationSchedule $schedule): int
    {
        $videos = $this->getTargetVideos($schedule);
        $maxComments = $schedule->getSetting('max_comments', 50);

        foreach ($videos as $video) {
            SyncVideoCommentsJob::dispatch($video, $maxComments, true);
        }

        return $videos->count();
    }

    protected function handleAutoReply(MetalXAutomationSchedule $schedule): int
    {
        $videos = $this->getTargetVideos($schedule);
        $max = $schedule->max_actions_per_run;
        $dispatched = 0;

        foreach ($videos as $video) {
            $comments = $video->comments()
                ->topLevel()
                ->where('ai_replied', false)
                ->where('is_spam', false)
                ->whereNotNull('sentiment')
                ->orderByDesc('published_at')
                ->limit($max - $dispatched)
                ->get();

            foreach ($comments as $comment) {
                ProcessCommentEngagementJob::dispatch($comment, true, false);
                $dispatched++;

                if ($dispatched >= $max) {
                    break 2;
                }
            }
        }

        return $dispatched;
    }

    protected function handleAutoLike(MetalXAutomationSchedule $schedule): int
    {
        $videos = $this->getTargetVideos($schedule);
        $max = $schedule->max_actions_per_run;
        $dispatched = 0;

        foreach ($videos as $video) {
            $comments = $video->comments()
                ->topLevel()
                ->where('liked_by_channel', false)
                ->where('is_spam', false)
                ->whereIn('sentiment', ['positive', 'question'])
                ->orderByDesc('published_at')
                ->limit($max - $dispatched)
                ->get();

            foreach ($comments as $comment) {
                ProcessCommentEngagementJob::dispatch($comment, false, true);
                $dispatched++;

                if ($dispatched >= $max) {
                    break 2;
                }
            }
        }

        return $dispatched;
    }

    protected function handleAutoModerate(MetalXAutomationSchedule $schedule): int
    {
        $videos = $this->getTargetVideos($schedule);
        $max = $schedule->max_actions_per_run;
        $dispatched = 0;

        foreach ($videos as $video) {
            $comments = $video->comments()
                ->topLevel()
                ->whereNull('violation_type')
                ->where('is_spam', false)
                ->orderByDesc('published_at')
                ->limit($max - $dispatched)
                ->get();

            foreach ($comments as $comment) {
                AutoModerateCommentJob::dispatch($comment);
                $dispatched++;

                if ($dispatched >= $max) {
                    break 2;
                }
            }
        }

        return $dispatched;
    }

    protected function handlePromoComment(MetalXAutomationSchedule $schedule): int
    {
        $videos = $this->getTargetVideos($schedule);
        $dispatched = 0;

        foreach ($videos as $video) {
            GenerateAndPostPromoCommentJob::dispatch($video, $schedule->getSetting('require_approval', true));
            $dispatched++;
        }

        return $dispatched;
    }

    /**
     * Get target videos: specific video if set, or all active videos.
     */
    protected function getTargetVideos(MetalXAutomationSchedule $schedule)
    {
        if ($schedule->video_id) {
            $video = $schedule->video;

            return $video ? collect([$video]) : collect();
        }

        return MetalXVideo::where('is_active', true)->get();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[Metal-X Automation] RunAutomationScheduleJob failed: ' . $exception->getMessage());
    }
}
