<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCommentEngagementJob;
use App\Jobs\SyncVideoCommentsJob;
use App\Models\MetalXComment;
use App\Models\MetalXVideo;
use App\Services\YouTubeCommentService;
use App\Services\YouTubeEngagementAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $query = MetalXComment::with('video')->topLevel();

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

        $comments = $query->latest()->paginate(20);

        // Statistics
        $stats = [
            'total' => MetalXComment::topLevel()->count(),
            'needs_reply' => MetalXComment::needsReply()->count(),
            'ai_replied' => MetalXComment::where('ai_replied', true)->count(),
            'requires_attention' => MetalXComment::where('requires_attention', true)->count(),
            'positive' => MetalXComment::positive()->count(),
            'questions' => MetalXComment::questions()->count(),
            'liked' => MetalXComment::where('liked_by_channel', true)->count(),
        ];

        return view('admin.metal-x.engagement', compact('comments', 'stats', 'filter'));
    }

    /**
     * Sync comments for a video.
     */
    public function syncComments(Request $request, MetalXVideo $video)
    {
        $maxComments = $request->input('max_comments', 100);
        $processEngagement = $request->boolean('process_engagement', true);

        SyncVideoCommentsJob::dispatch($video, $maxComments, $processEngagement);

        return response()->json([
            'success' => true,
            'message' => "Comment sync started for: {$video->title_en}",
        ]);
    }

    /**
     * Sync comments for all videos.
     */
    public function syncAllComments(Request $request)
    {
        $videos = MetalXVideo::where('is_active', true)->get();
        $maxComments = $request->input('max_comments', 50);
        $processEngagement = $request->boolean('process_engagement', true);

        foreach ($videos as $video) {
            SyncVideoCommentsJob::dispatch($video, $maxComments, $processEngagement);
        }

        return response()->json([
            'success' => true,
            'message' => "Comment sync started for {$videos->count()} videos",
            'count' => $videos->count(),
        ]);
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
        } catch (\Exception $e) {
            Log::error("Error generating reply for comment {$comment->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
        } catch (\Exception $e) {
            Log::error("Error posting reply for comment {$comment->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
        } catch (\Exception $e) {
            Log::error("Error liking comment {$comment->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
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
        $newState = !$comment->requires_attention;
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
        } catch (\Exception $e) {
            Log::error("Error improving content for video {$video->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Apply content improvements.
     */
    public function applyContentImprovements(Request $request, MetalXVideo $video)
    {
        $request->validate([
            'title_en' => 'nullable|string|max:255',
            'description_en' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $video->update([
            'title_en' => $request->input('title_en', $video->title_en),
            'description_en' => $request->input('description_en', $video->description_en),
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
}
