<?php

namespace App\Jobs;

use App\Models\MetalXComment;
use App\Services\YouTubeCommentService;
use App\Services\YouTubeEngagementAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoModerateCommentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 120;

    protected $comment;
    protected $autoBlock;

    /**
     * Create a new job instance.
     */
    public function __construct(MetalXComment $comment, bool $autoBlock = true)
    {
        $this->comment = $comment;
        $this->autoBlock = $autoBlock;
    }

    /**
     * Execute the job.
     */
    public function handle(
        YouTubeCommentService $commentService,
        YouTubeEngagementAiService $aiService
    ): void {
        Log::info("Auto-moderating comment {$this->comment->id}");

        try {
            // Skip if already deleted or from blacklisted author
            if ($this->comment->deleted_at || $this->comment->is_blacklisted_author) {
                return;
            }

            // Check if author is blacklisted
            if ($this->comment->author_channel_id &&
                $commentService->isChannelBlacklisted($this->comment->author_channel_id)) {

                Log::info("Comment {$this->comment->id} from blacklisted author, deleting...");

                $this->comment->update([
                    'is_blacklisted_author' => true,
                    'is_hidden' => true,
                ]);

                // Try to delete from YouTube
                try {
                    $commentService->deleteComment($this->comment);
                } catch (\Exception $e) {
                    Log::warning("Could not delete comment from YouTube: " . $e->getMessage());
                }

                // Record violation
                $commentService->recordViolation($this->comment->author_channel_id);
                return;
            }

            // Quick pattern-based detection for gambling
            if ($aiService->quickDetectGambling($this->comment->text)) {
                Log::warning("Gambling content detected in comment {$this->comment->id} by quick detection");

                // Run full AI analysis to confirm
                $violation = $aiService->detectViolation($this->comment);

                if ($violation['is_violation'] &&
                    in_array($violation['violation_type'], ['gambling', 'scam'])) {

                    $this->handleViolation($violation, $commentService);
                    return;
                }
            }

            // Full AI-based violation detection
            $violation = $aiService->detectViolation($this->comment);

            if ($violation['is_violation']) {
                Log::warning("Violation detected in comment {$this->comment->id}: {$violation['violation_type']} (confidence: {$violation['confidence']})");

                // Auto-delete and block for high severity
                if ($violation['should_delete'] || in_array($violation['severity'], ['high', 'critical'])) {
                    $this->handleViolation($violation, $commentService);
                } else {
                    // Just mark for review for lower severity
                    $this->comment->update([
                        'requires_attention' => true,
                        'violation_type' => $violation['violation_type'],
                        'is_hidden' => true,
                    ]);

                    Log::info("Comment {$this->comment->id} marked for review ({$violation['severity']} severity)");
                }
            }

        } catch (\Exception $e) {
            Log::error("Error auto-moderating comment {$this->comment->id}: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Handle violation by deleting and optionally blocking.
     */
    protected function handleViolation(array $violation, YouTubeCommentService $commentService): void
    {
        try {
            // Delete the comment
            $commentService->deleteComment($this->comment);

            // Block if auto-block is enabled and violation warrants it
            if ($this->autoBlock && $violation['should_block']) {
                $result = $commentService->blockAndDeleteChannel(
                    $this->comment,
                    $violation['violation_type'],
                    0 // System auto-blocked
                );

                Log::warning("Channel {$this->comment->author_channel_id} blocked and {$result['deleted']} comments deleted for: {$violation['violation_type']}");
            }

        } catch (\Exception $e) {
            Log::error("Failed to handle violation for comment {$this->comment->id}: " . $e->getMessage());

            // Even if YouTube delete fails, mark locally
            $this->comment->update([
                'is_hidden' => true,
                'deleted_at' => now(),
                'violation_type' => $violation['violation_type'],
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("AutoModerateCommentJob failed for comment {$this->comment->id}: " . $exception->getMessage());
    }
}
