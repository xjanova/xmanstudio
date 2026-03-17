<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\AIServiceException;
use App\Exceptions\YouTubeAPIException;
use App\Http\Controllers\Controller;
use App\Jobs\AutoModerateCommentJob;
use App\Jobs\ProcessCommentEngagementJob;
use App\Jobs\SyncAllCommentsJob;
use App\Jobs\SyncVideoCommentsJob;
use App\Models\MetalXBlacklist;
use App\Models\MetalXChannel;
use App\Models\MetalXComment;
use App\Models\MetalXVideo;
use App\Services\YouTubeCommentService;
use App\Services\YouTubeEngagementAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetalXEngagementController extends Controller
{
    protected $commentService;

    protected $aiService;

    public function __construct(
        YouTubeCommentService $commentService,
        YouTubeEngagementAiService $aiService
    ) {
        $this->commentService = $commentService;
        $this->aiService = $aiService;
    }

    /**
     * Display engagement dashboard.
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $channelId = $request->get('channel');

        $query = MetalXComment::with('video')->topLevel();

        // Filter by channel
        if ($channelId) {
            $query->whereHas('video', fn ($q) => $q->where('metal_x_channel_id', $channelId));
        }

        switch ($filter) {
            case 'needs_reply':
                $query->needsReply();
                break;
            case 'questions':
                $query->questions();
                break;
            case 'negative':
                $query->where('sentiment', 'negative');
                break;
            case 'requires_attention':
                $query->where('requires_attention', true);
                break;
            case 'ai_replied':
                $query->where('ai_replied', true);
                break;
        }

        $comments = $query->latest()->paginate(20)->withQueryString();

        // Build stats query scoped to channel if filtered
        $statsBase = MetalXComment::topLevel();
        if ($channelId) {
            $statsBase = $statsBase->whereHas('video', fn ($q) => $q->where('metal_x_channel_id', $channelId));
        }

        $stats = [
            'total' => (clone $statsBase)->count(),
            'needs_reply' => (clone $statsBase)->needsReply()->count(),
            'ai_replied' => (clone $statsBase)->where('ai_replied', true)->count(),
            'requires_attention' => (clone $statsBase)->where('requires_attention', true)->count(),
            'positive' => (clone $statsBase)->positive()->count(),
            'questions' => (clone $statsBase)->questions()->count(),
            'liked' => (clone $statsBase)->where('liked_by_channel', true)->count(),
        ];

        $channels = MetalXChannel::active()->get();

        return view('admin.metal-x.engagement', compact('comments', 'stats', 'filter', 'channels', 'channelId'));
    }

    /**
     * Sync comments for a video.
     */
    public function syncComments(Request $request, MetalXVideo $video)
    {
        $maxComments = min(500, max(1, (int) $request->input('max_comments', 100)));
        $processEngagement = $request->boolean('process_engagement', true);

        SyncVideoCommentsJob::dispatch($video, $maxComments, $processEngagement);

        return response()->json([
            'success' => true,
            'message' => "Comment sync started for: {$video->title}",
        ]);
    }

    /**
     * Sync comments for all videos (dispatched to run after response).
     */
    public function syncAllComments(Request $request)
    {
        $maxComments = min(500, max(1, (int) $request->input('max_comments', 50)));
        $processEngagement = $request->boolean('process_engagement', true);
        $channelId = $request->get('channel') ? (int) $request->get('channel') : null;

        $progressKey = 'comment_sync_' . Str::random(16);

        // Count videos to show in response
        $query = MetalXVideo::where('is_active', true);
        if ($channelId) {
            $query->where('metal_x_channel_id', $channelId);
        }
        $videoCount = $query->count();

        // Initialize progress
        Cache::put($progressKey, [
            'status' => 'queued',
            'total_videos' => $videoCount,
            'videos_done' => 0,
            'total_comments' => 0,
            'current_video' => 'กำลังเริ่มต้น...',
            'errors' => 0,
        ], 900);

        // Dispatch after response — returns immediately
        SyncAllCommentsJob::dispatchAfterResponse($progressKey, $maxComments, $processEngagement, $channelId);

        return response()->json([
            'success' => true,
            'progress_key' => $progressKey,
            'message' => "เริ่ม sync คอมเมนต์จาก {$videoCount} วิดีโอแล้ว",
            'count' => $videoCount,
        ]);
    }

    /**
     * Get comment sync progress (AJAX polling).
     */
    public function commentSyncProgress(Request $request)
    {
        $key = $request->get('key');

        if (! $key) {
            return response()->json(['error' => 'No progress key'], 400);
        }

        $progress = Cache::get($key);

        if (! $progress) {
            return response()->json(['status' => 'not_found']);
        }

        return response()->json($progress);
    }

    /**
     * Process engagement for a comment.
     */
    public function processComment(MetalXComment $comment)
    {
        ProcessCommentEngagementJob::dispatch($comment);

        return response()->json([
            'success' => true,
            'message' => 'Processing engagement for comment',
        ]);
    }

    /**
     * Generate AI reply for a comment.
     */
    public function generateReply(MetalXComment $comment)
    {
        try {
            $result = $this->aiService->generateReply($comment);

            if ($result['success']) {
                $comment->update([
                    'ai_reply_text' => $result['reply_text'],
                    'ai_reply_confidence' => $result['confidence_score'],
                ]);

                return response()->json([
                    'success' => true,
                    'reply' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to generate reply',
            ], 500);
        } catch (AIServiceException $e) {
            Log::error("AI service error generating reply for comment {$comment->id}", [
                'provider' => $e->getProvider(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Exception $e) {
            Log::error("Unexpected error generating reply for comment {$comment->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    /**
     * Post AI-generated reply.
     */
    public function postReply(Request $request, MetalXComment $comment)
    {
        $request->validate([
            'reply_text' => 'required|string|max:10000',
        ]);

        try {
            $replyText = $request->input('reply_text');
            $response = $this->commentService->replyToComment($comment, $replyText);

            $comment->update([
                'ai_replied' => true,
                'ai_reply_text' => $replyText,
                'ai_replied_at' => now(),
                'ai_reply_comment_id' => $response['id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reply posted successfully',
            ]);
        } catch (YouTubeAPIException $e) {
            Log::error("YouTube API error posting reply for comment {$comment->id}", [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Exception $e) {
            Log::error("Unexpected error posting reply for comment {$comment->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    /**
     * Like a comment.
     */
    public function likeComment(MetalXComment $comment)
    {
        try {
            $success = $this->commentService->likeComment($comment);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Comment liked successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to like comment',
            ], 500);
        } catch (YouTubeAPIException $e) {
            Log::error("YouTube API error liking comment {$comment->id}", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Exception $e) {
            Log::error("Unexpected error liking comment {$comment->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    /**
     * Mark comment as spam.
     */
    public function markSpam(MetalXComment $comment)
    {
        $this->commentService->markAsSpam($comment);

        return response()->json([
            'success' => true,
            'message' => 'Comment marked as spam',
        ]);
    }

    /**
     * Toggle requires attention.
     */
    public function toggleAttention(MetalXComment $comment)
    {
        $newState = ! $comment->requires_attention;
        $this->commentService->markRequiresAttention($comment, $newState);

        return response()->json([
            'success' => true,
            'message' => $newState ? 'Marked as requiring attention' : 'Removed attention flag',
            'requires_attention' => $newState,
        ]);
    }

    /**
     * Improve video content (title, description).
     */
    public function improveContent(MetalXVideo $video)
    {
        try {
            $result = $this->aiService->improveVideoContent($video);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'improvements' => $result['improvements'],
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Failed to improve content',
            ], 500);
        } catch (AIServiceException $e) {
            Log::error("AI service error improving content for video {$video->id}", [
                'provider' => $e->getProvider(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Exception $e) {
            Log::error("Unexpected error improving content for video {$video->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    /**
     * Apply content improvements.
     */
    public function applyContentImprovements(Request $request, MetalXVideo $video)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $video->update([
            'title' => $request->input('title', $video->title),
            'description' => $request->input('description', $video->description),
            'tags' => $request->input('tags') ? implode(',', $request->input('tags')) : $video->tags,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Content improvements applied successfully',
        ]);
    }

    /**
     * Get comment statistics for a video.
     */
    public function videoStats(MetalXVideo $video)
    {
        $stats = $this->commentService->getCommentStats($video);

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Batch process comments.
     */
    public function batchProcess(Request $request)
    {
        $request->validate([
            'comment_ids' => 'required|array',
            'comment_ids.*' => 'exists:metal_x_comments,id',
            'action' => 'required|in:reply,like,spam,attention',
        ]);

        $commentIds = $request->input('comment_ids');
        $action = $request->input('action');
        $count = 0;

        foreach ($commentIds as $commentId) {
            $comment = MetalXComment::find($commentId);

            if ($comment) {
                switch ($action) {
                    case 'reply':
                        ProcessCommentEngagementJob::dispatch($comment, true, false);
                        $count++;
                        break;
                    case 'like':
                        ProcessCommentEngagementJob::dispatch($comment, false, true);
                        $count++;
                        break;
                    case 'spam':
                        $this->commentService->markAsSpam($comment);
                        $count++;
                        break;
                    case 'attention':
                        $this->commentService->markRequiresAttention($comment, true);
                        $count++;
                        break;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Processed {$count} comments",
            'count' => $count,
        ]);
    }

    /**
     * Delete comment.
     */
    public function deleteComment(MetalXComment $comment)
    {
        try {
            $success = $this->commentService->deleteComment($comment);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Comment deleted successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment',
            ], 500);
        } catch (YouTubeAPIException $e) {
            Log::error("YouTube API error deleting comment {$comment->id}", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Exception $e) {
            Log::error("Unexpected error deleting comment {$comment->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    /**
     * Block channel and delete all comments.
     */
    public function blockChannel(Request $request, MetalXComment $comment)
    {
        $request->validate([
            'reason' => 'required|in:gambling,scam,inappropriate,harassment,spam,impersonation',
        ]);

        try {
            $result = $this->commentService->blockAndDeleteChannel(
                $comment,
                $request->input('reason'),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => "Channel blocked and {$result['deleted']} comments deleted",
                'result' => $result,
            ]);
        } catch (YouTubeAPIException $e) {
            Log::error('YouTube API error blocking channel', [
                'comment_id' => $comment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error blocking channel: ' . $e->getMessage(), [
                'comment_id' => $comment->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    /**
     * Detect violation in comment.
     */
    public function detectViolation(MetalXComment $comment)
    {
        try {
            $result = $this->aiService->detectViolation($comment);

            return response()->json([
                'success' => true,
                'violation' => $result,
            ]);
        } catch (AIServiceException $e) {
            Log::error("AI service error detecting violation for comment {$comment->id}", [
                'provider' => $e->getProvider(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], $e->getCode() ?: 500);
        } catch (\Exception $e) {
            Log::error("Unexpected error detecting violation for comment {$comment->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        }
    }

    /**
     * Auto-moderate comment.
     */
    public function autoModerate(MetalXComment $comment)
    {
        AutoModerateCommentJob::dispatch($comment);

        return response()->json([
            'success' => true,
            'message' => 'Auto-moderation started',
        ]);
    }

    /**
     * Get blacklist.
     */
    public function blacklist()
    {
        $blacklist = MetalXBlacklist::with('blockedBy')
            ->orderByDesc('violation_count')
            ->paginate(50);

        return view('admin.metal-x.blacklist', compact('blacklist'));
    }

    /**
     * Unblock channel.
     */
    public function unblockChannel($id)
    {
        try {
            $entry = MetalXBlacklist::findOrFail($id);
            $entry->unblock();

            return response()->json([
                'success' => true,
                'message' => 'Channel unblocked successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error unblocking channel: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
