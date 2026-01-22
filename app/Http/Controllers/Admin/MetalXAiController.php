<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetalXVideo;
use App\Services\YouTubeMetadataAiService;
use App\Jobs\GenerateVideoMetadataJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MetalXAiController extends Controller
{
    protected $aiService;

    public function __construct(YouTubeMetadataAiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Display AI tools dashboard
     */
    public function index()
    {
        // Check if AI is configured
        if (!$this->aiService->isConfigured()) {
            return redirect()
                ->route('admin.metal-x.videos.index')
                ->with('error', 'AI is not properly configured. Please configure AI settings first.');
        }

        // Get statistics
        $stats = [
            'total_videos' => MetalXVideo::count(),
            'videos_with_ai' => MetalXVideo::where('ai_generated', true)->count(),
            'approved_ai' => MetalXVideo::where('ai_approved', true)->count(),
            'pending_review' => MetalXVideo::where('ai_generated', true)
                ->where('ai_approved', false)
                ->count(),
        ];

        // Get videos pending review
        $pendingVideos = MetalXVideo::where('ai_generated', true)
            ->where('ai_approved', false)
            ->orderBy('ai_generated_at', 'desc')
            ->paginate(20);

        return view('admin.metal-x.ai-tools', compact('stats', 'pendingVideos'));
    }

    /**
     * Generate AI metadata for a single video
     */
    public function generateSingle(Request $request, MetalXVideo $video)
    {
        try {
            // Check if AI is configured
            if (!$this->aiService->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI is not properly configured.',
                ], 400);
            }

            $autoApprove = $request->boolean('auto_approve', false);
            $minConfidence = $request->input('min_confidence', 80.0);

            // Dispatch job
            GenerateVideoMetadataJob::dispatch($video, $autoApprove, $minConfidence);

            return response()->json([
                'success' => true,
                'message' => 'AI metadata generation started for: ' . $video->title_en,
            ]);
        } catch (\Exception $e) {
            Log::error("Error generating AI metadata for video {$video->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate AI metadata for multiple videos
     */
    public function generateBatch(Request $request)
    {
        $request->validate([
            'video_ids' => 'required|array',
            'video_ids.*' => 'exists:metal_x_videos,id',
        ]);

        try {
            // Check if AI is configured
            if (!$this->aiService->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI is not properly configured.',
                ], 400);
            }

            $autoApprove = $request->boolean('auto_approve', false);
            $minConfidence = $request->input('min_confidence', 80.0);

            $videoIds = $request->input('video_ids');
            $count = 0;

            foreach ($videoIds as $videoId) {
                $video = MetalXVideo::find($videoId);
                if ($video) {
                    GenerateVideoMetadataJob::dispatch($video, $autoApprove, $minConfidence);
                    $count++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "AI metadata generation started for {$count} videos",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error("Error in batch AI generation: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate AI metadata for all videos without Thai metadata
     */
    public function generateAll(Request $request)
    {
        try {
            // Check if AI is configured
            if (!$this->aiService->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI is not properly configured.',
                ], 400);
            }

            $autoApprove = $request->boolean('auto_approve', false);
            $minConfidence = $request->input('min_confidence', 80.0);

            // Get videos that don't have Thai metadata or AI metadata
            $videos = MetalXVideo::where(function ($query) {
                $query->whereNull('title_th')
                    ->orWhereNull('description_th')
                    ->orWhere('ai_generated', false);
            })->get();

            $count = 0;
            foreach ($videos as $video) {
                GenerateVideoMetadataJob::dispatch($video, $autoApprove, $minConfidence);
                $count++;
            }

            return response()->json([
                'success' => true,
                'message' => "AI metadata generation started for {$count} videos",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error("Error in generate all: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve AI-generated metadata
     */
    public function approve(MetalXVideo $video)
    {
        try {
            if (!$video->ai_generated) {
                return response()->json([
                    'success' => false,
                    'message' => 'No AI metadata to approve',
                ], 400);
            }

            $this->aiService->approveMetadata($video, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'AI metadata approved and applied successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Error approving AI metadata for video {$video->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject AI-generated metadata
     */
    public function reject(MetalXVideo $video)
    {
        try {
            if (!$video->ai_generated) {
                return response()->json([
                    'success' => false,
                    'message' => 'No AI metadata to reject',
                ], 400);
            }

            $this->aiService->rejectMetadata($video);

            return response()->json([
                'success' => true,
                'message' => 'AI metadata rejected successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Error rejecting AI metadata for video {$video->id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve multiple videos
     */
    public function approveBatch(Request $request)
    {
        $request->validate([
            'video_ids' => 'required|array',
            'video_ids.*' => 'exists:metal_x_videos,id',
        ]);

        try {
            $videoIds = $request->input('video_ids');
            $count = 0;

            foreach ($videoIds as $videoId) {
                $video = MetalXVideo::find($videoId);
                if ($video && $video->ai_generated) {
                    $this->aiService->approveMetadata($video, auth()->id());
                    $count++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Approved AI metadata for {$count} videos",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error("Error in batch approve: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get AI metadata preview
     */
    public function preview(MetalXVideo $video)
    {
        if (!$video->ai_generated) {
            return response()->json([
                'success' => false,
                'message' => 'No AI metadata available',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'metadata' => [
                'title_th' => $video->ai_title_th,
                'description_th' => $video->ai_description_th,
                'tags' => $video->ai_tags,
                'category' => $video->ai_category,
                'confidence_score' => $video->ai_confidence_score,
                'generated_at' => $video->ai_generated_at?->format('Y-m-d H:i:s'),
            ],
            'current' => [
                'title_th' => $video->title_th,
                'description_th' => $video->description_th,
                'tags' => $video->tags,
            ],
        ]);
    }

    /**
     * Get AI generation status
     */
    public function status()
    {
        $stats = [
            'total_videos' => MetalXVideo::count(),
            'videos_with_ai' => MetalXVideo::where('ai_generated', true)->count(),
            'approved_ai' => MetalXVideo::where('ai_approved', true)->count(),
            'pending_review' => MetalXVideo::where('ai_generated', true)
                ->where('ai_approved', false)
                ->count(),
            'without_thai' => MetalXVideo::whereNull('title_th')
                ->orWhereNull('description_th')
                ->count(),
            'average_confidence' => MetalXVideo::where('ai_generated', true)
                ->avg('ai_confidence_score'),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'ai_configured' => $this->aiService->isConfigured(),
        ]);
    }
}
