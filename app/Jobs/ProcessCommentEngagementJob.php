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

class ProcessCommentEngagementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 120;

    protected $comment;

    protected $autoReply;

    protected $autoLike;

    /**
     * Create a new job instance.
     */
    public function __construct(MetalXComment $comment, bool $autoReply = true, bool $autoLike = true)
    {
        $this->comment = $comment;
        $this->autoReply = $autoReply;
        $this->autoLike = $autoLike;
    }

    /**
     * Execute the job.
     */
    public function handle(
        YouTubeCommentService $commentService,
        YouTubeEngagementAiService $aiService
    ): void {
        Log::info("Processing engagement for comment {$this->comment->id}");

        try {
            // Analyze sentiment if not already done
            if (empty($this->comment->sentiment)) {
                $aiService->analyzeSentiment($this->comment);
                $this->comment->refresh();
            }

            // Skip spam comments
            if ($this->comment->is_spam) {
                Log::info("Skipping spam comment {$this->comment->id}");

                return;
            }

            // Auto-like if enabled and appropriate
            if ($this->autoLike && ! $this->comment->liked_by_channel) {
                if ($aiService->shouldLikeComment($this->comment)) {
                    try {
                        $commentService->likeComment($this->comment);
                        Log::info("Liked comment {$this->comment->id}");
                    } catch (\Exception $e) {
                        Log::warning("Could not like comment {$this->comment->id}: " . $e->getMessage());
                    }
                }
            }

            // Auto-reply if enabled and appropriate
            if ($this->autoReply && ! $this->comment->ai_replied && $this->comment->can_reply) {
                $replyResult = $aiService->generateReply($this->comment);

                if ($replyResult['success'] && $replyResult['should_reply']) {
                    $replyText = $replyResult['reply_text'];
                    $confidence = $replyResult['confidence_score'];

                    // Only auto-post if confidence is high enough
                    $minConfidence = config('metalx.auto_reply_min_confidence', 75);

                    if ($confidence >= $minConfidence) {
                        try {
                            $response = $commentService->replyToComment($this->comment, $replyText);

                            $this->comment->update([
                                'ai_replied' => true,
                                'ai_reply_text' => $replyText,
                                'ai_reply_confidence' => $confidence,
                                'ai_replied_at' => now(),
                                'ai_reply_comment_id' => $response['id'] ?? null,
                            ]);

                            Log::info("Replied to comment {$this->comment->id} with confidence {$confidence}");
                        } catch (\Exception $e) {
                            Log::error("Failed to post reply for comment {$this->comment->id}: " . $e->getMessage());

                            // Save draft reply for manual review
                            $this->comment->update([
                                'ai_reply_text' => $replyText,
                                'ai_reply_confidence' => $confidence,
                                'requires_attention' => true,
                            ]);
                        }
                    } else {
                        // Save draft for manual approval if confidence is low
                        $this->comment->update([
                            'ai_reply_text' => $replyText,
                            'ai_reply_confidence' => $confidence,
                            'requires_attention' => true,
                        ]);

                        Log::info("Draft reply saved for comment {$this->comment->id} (confidence too low: {$confidence})");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error processing engagement for comment {$this->comment->id}: " . $e->getMessage());
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessCommentEngagementJob failed for comment {$this->comment->id}: " . $exception->getMessage());
    }
}
