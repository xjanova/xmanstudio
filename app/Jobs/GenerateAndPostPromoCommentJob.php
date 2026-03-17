<?php

namespace App\Jobs;

use App\Http\Controllers\Auth\YouTubeOAuthController;
use App\Models\MetalXAutomationLog;
use App\Models\MetalXChannel;
use App\Models\MetalXPromoComment;
use App\Models\MetalXVideo;
use App\Models\Setting;
use App\Services\InputSanitizerService;
use App\Services\YouTubeEngagementAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateAndPostPromoCommentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    public $timeout = 120;

    protected MetalXVideo $video;

    protected bool $requireApproval;

    protected bool $shouldPin;

    public function __construct(MetalXVideo $video, bool $requireApproval = true, bool $shouldPin = false)
    {
        $this->video = $video;
        $this->requireApproval = $requireApproval;
        $this->shouldPin = $shouldPin;
    }

    public function handle(YouTubeEngagementAiService $aiService): void
    {
        Log::info("[Metal-X Promo] Generating promo comment for video {$this->video->youtube_id}");

        try {
            // Check daily limit
            $todayCount = MetalXPromoComment::where('video_id', $this->video->id)
                ->where('created_at', '>=', now()->startOfDay())
                ->count();

            $dailyLimit = (int) Setting::get('metalx_promo_max_per_video_per_day', 2);

            if ($todayCount >= $dailyLimit) {
                Log::info("[Metal-X Promo] Daily limit reached for video {$this->video->youtube_id} ({$todayCount}/{$dailyLimit})");
                MetalXAutomationLog::log('promo_comment', 'skipped', [
                    'video_id' => $this->video->id,
                    'details' => ['reason' => 'daily_limit', 'count' => $todayCount, 'limit' => $dailyLimit],
                ]);

                return;
            }

            // Generate promo text via AI
            $promoText = $this->generatePromoText($aiService);

            if (empty($promoText)) {
                Log::warning("[Metal-X Promo] Failed to generate promo text for video {$this->video->youtube_id}");

                return;
            }

            // Create promo comment record
            $promo = MetalXPromoComment::create([
                'video_id' => $this->video->id,
                'comment_text' => $promoText,
                'generated_by_ai' => true,
                'status' => $this->requireApproval ? 'draft' : 'scheduled',
                'should_pin' => $this->shouldPin,
                'scheduled_at' => $this->requireApproval ? null : now(),
            ]);

            // Auto-post if no approval needed
            if (! $this->requireApproval) {
                $this->postToYouTube($promo);
            }

            MetalXAutomationLog::log('promo_comment', 'success', [
                'video_id' => $this->video->id,
                'details' => [
                    'promo_id' => $promo->id,
                    'status' => $promo->status,
                    'require_approval' => $this->requireApproval,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("[Metal-X Promo] Error for video {$this->video->youtube_id}: " . $e->getMessage());
            MetalXAutomationLog::log('promo_comment', 'failed', [
                'video_id' => $this->video->id,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    protected function generatePromoText(YouTubeEngagementAiService $aiService): ?string
    {
        $sanitizer = app(InputSanitizerService::class);

        $channelName = $sanitizer->sanitizeForPrompt(Setting::get('metalx_channel_name', 'Metal-X'), 100);
        $videoTitle = $sanitizer->sanitizeForPrompt($this->video->title ?? '', 200);
        $videoDescription = $sanitizer->sanitizeForPrompt(mb_substr($this->video->description ?? '', 0, 300), 300);
        $viewCount = (int) ($this->video->view_count ?? 0);
        $likeCount = (int) ($this->video->like_count ?? 0);

        $prompt = <<<PROMPT
You are the social media manager for {$channelName} YouTube channel.

Generate an engaging promotional comment to post on this video to boost engagement:

Video Title: "{$videoTitle}"
Video Description: "{$videoDescription}"
Current Views: {$viewCount}
Current Likes: {$likeCount}

Guidelines:
1. Write as the channel owner/creator
2. Encourage viewers to like, subscribe, and comment
3. Ask an engaging question related to the video content
4. Keep it natural and conversational (not spammy)
5. Use Thai language primarily, with some English tech terms if relevant
6. Include 1-2 relevant emoji
7. Keep it concise (2-4 sentences max)
8. Vary the style - sometimes ask questions, sometimes share insights, sometimes call to action
9. Do NOT use hashtags excessively
10. Do NOT mention "algorithm" or "YouTube algorithm"

Respond with JSON only:
{
  "comment_text": "your promotional comment in Thai",
  "engagement_type": "question|insight|cta|discussion"
}
PROMPT;

        try {
            $result = $aiService->generateFromPrompt($prompt);

            return $result['comment_text'] ?? null;
        } catch (\Exception $e) {
            Log::error('[Metal-X Promo] AI generation failed: ' . $e->getMessage());

            return null;
        }
    }

    public function postToYouTube(MetalXPromoComment $promo): bool
    {
        // Use per-channel token if available, fallback to global
        $video = $promo->video;
        $channel = $video->metal_x_channel_id
            ? MetalXChannel::find($video->metal_x_channel_id)
            : MetalXChannel::getDefault();

        $accessToken = $channel
            ? $channel->getValidAccessToken()
            : YouTubeOAuthController::getValidAccessToken();

        if (empty($accessToken)) {
            $promo->update([
                'status' => 'failed',
                'error_message' => 'No valid YouTube access token',
            ]);

            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post('https://www.googleapis.com/youtube/v3/commentThreads?part=snippet', [
                'snippet' => [
                    'videoId' => $video->youtube_id,
                    'topLevelComment' => [
                        'snippet' => [
                            'textOriginal' => $promo->comment_text,
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $commentId = $data['id'] ?? null;

                $promo->update([
                    'status' => 'posted',
                    'posted_at' => now(),
                    'youtube_comment_id' => $commentId,
                ]);

                Log::info("[Metal-X Promo] Posted promo comment for video {$video->youtube_id}");

                // If should_pin is set, mark for pinning (admin pins via YouTube Studio)
                if ($promo->should_pin && $commentId) {
                    Log::info("[Metal-X Promo] Comment {$commentId} marked for pinning — pin via YouTube Studio");
                }

                return true;
            }

            $errorBody = Str::limit($response->body(), 500);
            $promo->update([
                'status' => 'failed',
                'error_message' => 'YouTube API error: ' . $errorBody,
            ]);

            Log::error('[Metal-X Promo] YouTube API error: ' . $errorBody);

            return false;
        } catch (\Exception $e) {
            $promo->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[Metal-X Promo] Job failed for video {$this->video->youtube_id}: " . $exception->getMessage());
    }
}
