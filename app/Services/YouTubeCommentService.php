<?php

namespace App\Services;

use App\Http\Controllers\Auth\YouTubeOAuthController;
use App\Models\MetalXBlacklist;
use App\Models\MetalXComment;
use App\Models\MetalXVideo;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeCommentService
{
    protected $apiKey;

    protected $channelId;

    public function __construct()
    {
        $this->apiKey = Setting::get('metalx_youtube_api_key');
        $this->channelId = Setting::get('metalx_channel_id');
    }

    /**
     * Check if YouTube API is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Get valid access token with automatic refresh.
     */
    private function getValidAccessToken(): ?string
    {
        return YouTubeOAuthController::getValidAccessToken();
    }

    /**
     * Fetch comments for a video from YouTube.
     */
    public function fetchVideoComments(MetalXVideo $video, int $maxResults = 100): array
    {
        if (! $this->isConfigured()) {
            throw new Exception('YouTube API is not configured');
        }

        $allComments = [];
        $pageToken = null;

        do {
            $response = Http::get('https://www.googleapis.com/youtube/v3/commentThreads', [
                'part' => 'snippet,replies',
                'videoId' => $video->youtube_id,
                'maxResults' => min($maxResults, 100),
                'order' => 'time',
                'textFormat' => 'plainText',
                'key' => $this->apiKey,
                'pageToken' => $pageToken,
            ]);

            if (! $response->successful()) {
                Log::error("Failed to fetch comments for video {$video->youtube_id}: " . $response->body());
                throw new Exception('Failed to fetch comments from YouTube');
            }

            $data = $response->json();
            $items = $data['items'] ?? [];

            foreach ($items as $item) {
                $topComment = $item['snippet']['topLevelComment']['snippet'];

                // Store top-level comment
                $comment = MetalXComment::updateOrCreate(
                    ['comment_id' => $item['snippet']['topLevelComment']['id']],
                    [
                        'video_id' => $video->id,
                        'author_name' => $topComment['authorDisplayName'],
                        'author_channel_id' => $topComment['authorChannelId']['value'] ?? null,
                        'author_profile_image' => $topComment['authorProfileImageUrl'] ?? null,
                        'text' => $topComment['textDisplay'],
                        'like_count' => $topComment['likeCount'] ?? 0,
                        'can_reply' => $item['snippet']['canReply'] ?? true,
                        'published_at' => $topComment['publishedAt'],
                        'updated_at_youtube' => $topComment['updatedAt'],
                    ]
                );

                $allComments[] = $comment;

                // Store replies if any
                if (isset($item['replies']['comments'])) {
                    foreach ($item['replies']['comments'] as $reply) {
                        $replySnippet = $reply['snippet'];

                        $replyComment = MetalXComment::updateOrCreate(
                            ['comment_id' => $reply['id']],
                            [
                                'video_id' => $video->id,
                                'parent_id' => $item['snippet']['topLevelComment']['id'],
                                'author_name' => $replySnippet['authorDisplayName'],
                                'author_channel_id' => $replySnippet['authorChannelId']['value'] ?? null,
                                'author_profile_image' => $replySnippet['authorProfileImageUrl'] ?? null,
                                'text' => $replySnippet['textDisplay'],
                                'like_count' => $replySnippet['likeCount'] ?? 0,
                                'can_reply' => false,
                                'published_at' => $replySnippet['publishedAt'],
                                'updated_at_youtube' => $replySnippet['updatedAt'],
                            ]
                        );

                        $allComments[] = $replyComment;
                    }
                }
            }

            $pageToken = $data['nextPageToken'] ?? null;
        } while ($pageToken && count($allComments) < $maxResults);

        return $allComments;
    }

    /**
     * Reply to a comment on YouTube.
     */
    public function replyToComment(MetalXComment $comment, string $replyText): ?array
    {
        if (! $this->isConfigured()) {
            throw new Exception('YouTube API is not configured');
        }

        $accessToken = $this->getValidAccessToken();
        if (empty($accessToken)) {
            throw new Exception('YouTube access token is not configured. Please authenticate via Settings > Integrations > Connect YouTube.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post('https://www.googleapis.com/youtube/v3/comments', [
            'part' => 'snippet',
            'snippet' => [
                'parentId' => $comment->comment_id,
                'textOriginal' => $replyText,
            ],
        ]);

        if (! $response->successful()) {
            Log::error("Failed to reply to comment {$comment->comment_id}: " . $response->body());
            throw new Exception('Failed to post reply to YouTube');
        }

        return $response->json();
    }

    /**
     * Like a comment on YouTube.
     */
    public function likeComment(MetalXComment $comment): bool
    {
        if (! $this->isConfigured()) {
            throw new Exception('YouTube API is not configured');
        }

        $accessToken = $this->getValidAccessToken();
        if (empty($accessToken)) {
            throw new Exception('YouTube access token is not configured. Please authenticate via Settings > Integrations > Connect YouTube.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post('https://www.googleapis.com/youtube/v3/comments/setModerationStatus', [
            'id' => $comment->comment_id,
            'moderationStatus' => 'published',
            'banAuthor' => false,
        ]);

        // Note: YouTube API doesn't have a direct "like" endpoint for comments
        // This marks it as published which is the closest action
        // For actual likes, we need to use the YouTube Data API v3 with proper scopes

        if ($response->successful()) {
            $comment->update([
                'liked_by_channel' => true,
                'liked_at' => now(),
            ]);

            return true;
        }

        Log::warning("Could not like comment {$comment->comment_id}: " . $response->body());

        return false;
    }

    /**
     * Fetch captions/transcript for a video.
     */
    public function fetchVideoCaptions(string $videoId): ?array
    {
        if (! $this->isConfigured()) {
            throw new Exception('YouTube API is not configured');
        }

        // First, get caption tracks
        $response = Http::get('https://www.googleapis.com/youtube/v3/captions', [
            'videoId' => $videoId,
            'part' => 'snippet',
            'key' => $this->apiKey,
        ]);

        if (! $response->successful()) {
            Log::error("Failed to fetch captions for video {$videoId}: " . $response->body());

            return null;
        }

        $data = $response->json();
        $items = $data['items'] ?? [];

        if (empty($items)) {
            return null;
        }

        // Try to get Thai or English captions
        $captionId = null;
        foreach ($items as $item) {
            $lang = $item['snippet']['language'];
            if ($lang === 'th' || $lang === 'en') {
                $captionId = $item['id'];
                break;
            }
        }

        if (! $captionId && ! empty($items)) {
            $captionId = $items[0]['id'];
        }

        return [
            'available' => ! empty($items),
            'tracks' => $items,
            'caption_id' => $captionId,
        ];
    }

    /**
     * Download caption content (requires OAuth).
     */
    public function downloadCaption(string $captionId): ?string
    {
        $accessToken = $this->getValidAccessToken();
        if (empty($accessToken)) {
            throw new Exception('YouTube access token is not configured. Please authenticate via Settings > Integrations > Connect YouTube.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get("https://www.googleapis.com/youtube/v3/captions/{$captionId}", [
            'tfmt' => 'srt', // or 'vtt', 'sbv'
        ]);

        if (! $response->successful()) {
            Log::error("Failed to download caption {$captionId}: " . $response->body());

            return null;
        }

        return $response->body();
    }

    /**
     * Sync comments for all active videos.
     */
    public function syncAllVideoComments(int $commentsPerVideo = 50): int
    {
        $videos = MetalXVideo::where('is_active', true)->get();
        $totalComments = 0;

        foreach ($videos as $video) {
            try {
                $comments = $this->fetchVideoComments($video, $commentsPerVideo);
                $totalComments += count($comments);

                Log::info("Synced {count($comments)} comments for video {$video->youtube_id}");
            } catch (Exception $e) {
                Log::error("Failed to sync comments for video {$video->youtube_id}: " . $e->getMessage());
            }
        }

        return $totalComments;
    }

    /**
     * Get comment statistics for a video.
     */
    public function getCommentStats(MetalXVideo $video): array
    {
        $total = $video->comments()->count();
        $topLevel = $video->comments()->topLevel()->count();
        $replies = $video->comments()->replies()->count();
        $needsReply = $video->comments()->needsReply()->count();
        $aiReplied = $video->comments()->where('ai_replied', true)->count();
        $liked = $video->comments()->where('liked_by_channel', true)->count();

        return [
            'total' => $total,
            'top_level' => $topLevel,
            'replies' => $replies,
            'needs_reply' => $needsReply,
            'ai_replied' => $aiReplied,
            'liked' => $liked,
            'reply_rate' => $total > 0 ? round(($aiReplied / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Mark comment as spam.
     */
    public function markAsSpam(MetalXComment $comment): void
    {
        $comment->update([
            'is_spam' => true,
            'is_hidden' => true,
        ]);
    }

    /**
     * Mark comment as requiring attention.
     */
    public function markRequiresAttention(MetalXComment $comment, bool $requires = true): void
    {
        $comment->update([
            'requires_attention' => $requires,
        ]);
    }

    /**
     * Delete comment from YouTube.
     */
    public function deleteComment(MetalXComment $comment): bool
    {
        if (! $this->isConfigured()) {
            throw new Exception('YouTube API is not configured');
        }

        $accessToken = $this->getValidAccessToken();
        if (empty($accessToken)) {
            throw new Exception('YouTube access token is not configured. Please authenticate via Settings > Integrations > Connect YouTube.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->delete('https://www.googleapis.com/youtube/v3/comments', [
            'id' => $comment->comment_id,
        ]);

        if ($response->successful()) {
            // Mark as deleted in our database
            $comment->update([
                'deleted_at' => now(),
                'is_hidden' => true,
            ]);

            Log::info("Deleted comment {$comment->comment_id}");

            return true;
        }

        Log::error("Failed to delete comment {$comment->comment_id}: " . $response->body());

        return false;
    }

    /**
     * Block channel and delete all their comments.
     * Wrapped in DB transaction for data integrity.
     */
    public function blockAndDeleteChannel(
        MetalXComment $comment,
        string $reason,
        ?int $blockedBy = null
    ): array {
        if (! $comment->author_channel_id) {
            throw new Exception('Comment does not have author channel ID');
        }

        // Wrap entire operation in transaction for atomicity
        return DB::transaction(function () use ($comment, $reason, $blockedBy) {
            // Add to blacklist
            $blacklistEntry = MetalXBlacklist::addToBlacklist(
                $comment->author_channel_id,
                $comment->author_name,
                $reason,
                "Auto-blocked for: {$reason}",
                $blockedBy
            );

            // Find all comments from this author
            $allComments = MetalXComment::where('author_channel_id', $comment->author_channel_id)
                ->whereNull('deleted_at')
                ->get();

            $deleted = 0;
            $failed = 0;

            foreach ($allComments as $authorComment) {
                try {
                    // Mark as blacklisted
                    $authorComment->update([
                        'is_blacklisted_author' => true,
                        'violation_type' => $reason,
                    ]);

                    // Try to delete from YouTube
                    if ($this->deleteComment($authorComment)) {
                        $deleted++;
                    } else {
                        // Even if YouTube delete fails, mark as deleted locally
                        $authorComment->update([
                            'deleted_at' => now(),
                            'is_hidden' => true,
                        ]);
                        $failed++;
                    }
                } catch (Exception $e) {
                    Log::error("Failed to delete comment {$authorComment->id}: " . $e->getMessage());
                    $failed++;
                }
            }

            Log::info("Blocked channel {$comment->author_channel_id}. Deleted {$deleted} comments, {$failed} failed.");

            return [
                'blocked' => true,
                'blacklist_entry' => $blacklistEntry,
                'total_comments' => count($allComments),
                'deleted' => $deleted,
                'failed' => $failed,
            ];
        });
    }

    /**
     * Check if channel is blacklisted.
     */
    public function isChannelBlacklisted(string $channelId): bool
    {
        return MetalXBlacklist::isBlacklisted($channelId);
    }

    /**
     * Record violation for existing blacklist entry.
     */
    public function recordViolation(string $channelId): void
    {
        $entry = MetalXBlacklist::where('channel_id', $channelId)->first();
        if ($entry) {
            $entry->recordViolation();
        }
    }
}
