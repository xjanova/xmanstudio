<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateVideoMetadataJob;
use App\Models\MetalXChannel;
use App\Models\MetalXVideo;
use App\Models\Setting;
use App\Services\YouTubeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MetalXVideoController extends Controller
{
    protected YouTubeService $youtubeService;

    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }

    /**
     * Display a listing of videos.
     */
    public function index(Request $request)
    {
        $query = MetalXVideo::query();

        // Filter by channel
        if ($channelId = $request->get('channel')) {
            $query->where('metal_x_channel_id', $channelId);
        }

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('title_th', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        // Filter by featured
        if ($request->has('featured')) {
            $query->where('is_featured', $request->get('featured') === 'yes');
        }

        // Filter by video type (whitelist to prevent SQL injection)
        if ($request->filled('video_type') && in_array($request->get('video_type'), ['standard', 'short', 'live'])) {
            $query->where('video_type', $request->get('video_type'));
        }

        // Sort (whitelist to prevent SQL injection)
        $allowedSorts = ['published_at', 'title', 'view_count', 'like_count', 'comment_count', 'created_at'];
        $sortBy = in_array($request->get('sort'), $allowedSorts) ? $request->get('sort') : 'published_at';
        $sortDir = $request->get('dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $videos = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => MetalXVideo::count(),
            'active' => MetalXVideo::where('is_active', true)->count(),
            'featured' => MetalXVideo::where('is_featured', true)->count(),
            'total_views' => MetalXVideo::sum('view_count'),
            'shorts' => MetalXVideo::where('video_type', 'short')->count(),
            'live' => MetalXVideo::where('video_type', 'live')->count(),
            'standard' => MetalXVideo::where('video_type', 'standard')->count(),
        ];

        $isApiConfigured = $this->youtubeService->isConfigured();
        $channels = MetalXChannel::active()->get();

        return view('admin.metal-x.videos.index', compact('videos', 'stats', 'isApiConfigured', 'channels'));
    }

    /**
     * Show the form for creating a new video.
     */
    public function create()
    {
        $isApiConfigured = $this->youtubeService->isConfigured();

        return view('admin.metal-x.videos.create', compact('isApiConfigured'));
    }

    /**
     * Store a newly created video.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'youtube_url' => 'required_without:youtube_id|nullable|string',
            'youtube_id' => 'required_without:youtube_url|nullable|string|max:20',
            'title' => 'nullable|string|max:255',
            'title_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        // Extract video ID from URL
        $videoId = $validated['youtube_id'] ?? YouTubeService::extractVideoId($validated['youtube_url'] ?? '');

        if (! $videoId) {
            return back()->withErrors(['youtube_url' => 'Invalid YouTube URL or Video ID'])->withInput();
        }

        // Check if video already exists
        if (MetalXVideo::where('youtube_id', $videoId)->exists()) {
            return back()->withErrors(['youtube_url' => 'This video already exists'])->withInput();
        }

        // Import from YouTube API if configured
        if ($this->youtubeService->isConfigured()) {
            $video = $this->youtubeService->importVideo($videoId);

            if ($video) {
                // Update with user-provided data
                $video->update([
                    'title_th' => $validated['title_th'] ?? $video->title_th,
                    'description_th' => $validated['description_th'] ?? $video->description_th,
                    'is_featured' => $validated['is_featured'] ?? false,
                    'is_active' => $validated['is_active'] ?? true,
                    'order' => $validated['order'] ?? 0,
                ]);

                // Dispatch AI metadata generation if enabled
                if (Setting::get('ai_content_generation', false) && ! $validated['title_th']) {
                    GenerateVideoMetadataJob::dispatch($video, false, 80.0);
                }

                return redirect()->route('admin.metal-x.videos.index')
                    ->with('success', 'Video imported successfully from YouTube!');
            }
        }

        // Manual creation if API not available
        $video = MetalXVideo::create([
            'youtube_id' => $videoId,
            'title' => $validated['title'] ?? 'Untitled Video',
            'title_th' => $validated['title_th'] ?? null,
            'description' => $validated['description'] ?? null,
            'description_th' => $validated['description_th'] ?? null,
            'thumbnail_url' => "https://img.youtube.com/vi/{$videoId}/default.jpg",
            'thumbnail_medium_url' => "https://img.youtube.com/vi/{$videoId}/mqdefault.jpg",
            'thumbnail_high_url' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
            'is_featured' => $validated['is_featured'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $validated['order'] ?? 0,
        ]);

        return redirect()->route('admin.metal-x.videos.index')
            ->with('success', 'Video added successfully!');
    }

    /**
     * Show the form for editing a video.
     */
    public function edit(MetalXVideo $video)
    {
        return view('admin.metal-x.videos.edit', compact('video'));
    }

    /**
     * Update the specified video.
     */
    public function update(Request $request, MetalXVideo $video)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $video->update([
            'title' => $validated['title'],
            'title_th' => $validated['title_th'],
            'description' => $validated['description'],
            'description_th' => $validated['description_th'],
            'category' => $validated['category'],
            'is_featured' => $validated['is_featured'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $validated['order'] ?? 0,
        ]);

        return redirect()->route('admin.metal-x.videos.index')
            ->with('success', 'Video updated successfully!');
    }

    /**
     * Remove the specified video.
     */
    public function destroy(MetalXVideo $video)
    {
        $video->playlists()->detach();
        $video->delete();

        return redirect()->route('admin.metal-x.videos.index')
            ->with('success', 'Video deleted successfully!');
    }

    /**
     * Toggle video active status.
     */
    public function toggle(MetalXVideo $video)
    {
        $video->update(['is_active' => ! $video->is_active]);

        return back()->with('success', 'Video status updated!');
    }

    /**
     * Toggle video featured status.
     */
    public function toggleFeatured(MetalXVideo $video)
    {
        $video->update(['is_featured' => ! $video->is_featured]);

        return back()->with('success', 'Video featured status updated!');
    }

    /**
     * Sync video data from YouTube.
     */
    public function sync(MetalXVideo $video)
    {
        if (! $this->youtubeService->isConfigured()) {
            return back()->with('error', 'YouTube API is not configured');
        }

        $updated = $this->youtubeService->importVideo($video->youtube_id);

        if ($updated) {
            return back()->with('success', 'Video synced from YouTube!');
        }

        return back()->with('error', 'Failed to sync video from YouTube');
    }

    /**
     * Start async sync of all videos from all active channels (AJAX).
     */
    public function syncAll(Request $request)
    {
        if (! $this->youtubeService->isConfigured()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'YouTube API is not configured'], 400);
            }

            return back()->with('error', 'YouTube API is not configured');
        }

        $channels = MetalXChannel::active()->get();

        if ($channels->isEmpty()) {
            $channelId = Setting::getValue('metalx_channel_id');

            if (! $channelId) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'No channels configured'], 400);
                }

                return back()->with('error', 'No channels configured. Please add channels in Metal-X Settings.');
            }

            $channels = collect([(object) ['youtube_channel_id' => $channelId, 'id' => null, 'name' => 'Default']]);
        }

        // limit=0 means import ALL videos from channel
        $limit = (int) $request->get('limit', 0);
        $progressKey = 'video_sync_' . Str::random(16);

        // Initialize master progress
        Cache::put($progressKey, [
            'status' => 'running',
            'channels_total' => $channels->count(),
            'channels_done' => 0,
            'total_imported' => 0,
            'total_skipped' => 0,
            'current_channel' => '',
            'channel_progress' => [],
        ], 600);

        // Process synchronously but with progress updates
        $totalVideos = [];

        foreach ($channels as $index => $channel) {
            $channelModel = $channel instanceof MetalXChannel ? $channel : null;
            $channelProgressKey = $progressKey . '_ch_' . $index;

            // Update master progress with current channel
            $masterProgress = Cache::get($progressKey, []);
            $masterProgress['current_channel'] = $channel->name ?? $channel->youtube_channel_id;
            Cache::put($progressKey, $masterProgress, 600);

            $videos = $this->youtubeService->syncChannelVideos(
                $channel->youtube_channel_id,
                $limit,
                $channelModel,
                $channelProgressKey
            );
            $totalVideos = array_merge($totalVideos, $videos);

            // Update master progress
            $channelResult = Cache::get($channelProgressKey, []);
            $masterProgress = Cache::get($progressKey, []);
            $masterProgress['channels_done'] = $index + 1;
            $masterProgress['total_imported'] += count($videos);
            $masterProgress['total_skipped'] += $channelResult['skipped'] ?? 0;
            $masterProgress['channel_progress'][$channel->name ?? $channel->youtube_channel_id] = $channelResult;
            Cache::put($progressKey, $masterProgress, 600);

            // Clean up channel progress key
            Cache::forget($channelProgressKey);
        }

        // Dispatch AI metadata generation for videos without Thai metadata
        if (Setting::get('ai_content_generation', false)) {
            foreach ($totalVideos as $video) {
                if (empty($video->title_th)) {
                    GenerateVideoMetadataJob::dispatch($video, false, 80.0);
                }
            }
        }

        // Mark complete
        $masterProgress = Cache::get($progressKey, []);
        $masterProgress['status'] = 'completed';
        Cache::put($progressKey, $masterProgress, 600);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'progress_key' => $progressKey,
                'imported' => count($totalVideos),
                'message' => count($totalVideos) . " วิดีโอซิงค์จาก {$channels->count()} ช่อง",
            ]);
        }

        return back()->with('success', count($totalVideos) . " videos synced from {$channels->count()} channels!");
    }

    /**
     * Get sync progress (AJAX polling endpoint).
     */
    public function syncProgress(Request $request)
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
     * Update statistics for all videos.
     */
    public function updateStats()
    {
        if (! $this->youtubeService->isConfigured()) {
            return back()->with('error', 'YouTube API is not configured');
        }

        $updated = $this->youtubeService->updateVideoStatistics();

        return back()->with('success', "{$updated} videos statistics updated!");
    }

    /**
     * Import video from URL (AJAX).
     */
    public function import(Request $request)
    {
        $request->validate([
            'url' => 'required|string',
        ]);

        $videoId = YouTubeService::extractVideoId($request->url);

        if (! $videoId) {
            return response()->json(['error' => 'Invalid YouTube URL'], 400);
        }

        if (MetalXVideo::where('youtube_id', $videoId)->exists()) {
            return response()->json(['error' => 'Video already exists'], 400);
        }

        if ($this->youtubeService->isConfigured()) {
            $video = $this->youtubeService->importVideo($videoId);

            if ($video) {
                // Dispatch AI metadata generation if enabled
                if (Setting::get('ai_content_generation', false)) {
                    GenerateVideoMetadataJob::dispatch($video, false, 80.0);
                }

                return response()->json([
                    'success' => true,
                    'video' => $video,
                ]);
            }
        }

        // Create with basic thumbnail
        $video = MetalXVideo::create([
            'youtube_id' => $videoId,
            'title' => 'Imported Video',
            'thumbnail_url' => "https://img.youtube.com/vi/{$videoId}/default.jpg",
            'thumbnail_medium_url' => "https://img.youtube.com/vi/{$videoId}/mqdefault.jpg",
            'thumbnail_high_url' => "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
        ]);

        return response()->json([
            'success' => true,
            'video' => $video,
            'message' => 'Video added with basic info. Configure YouTube API for full details.',
        ]);
    }
}
