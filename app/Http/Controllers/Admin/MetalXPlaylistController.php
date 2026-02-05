<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetalXPlaylist;
use App\Models\MetalXVideo;
use App\Services\YouTubeService;
use Illuminate\Http\Request;

class MetalXPlaylistController extends Controller
{
    protected YouTubeService $youtubeService;

    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }

    /**
     * Display a listing of playlists.
     */
    public function index(Request $request)
    {
        $query = MetalXPlaylist::withCount('videos');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('title_th', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        $playlists = $query->orderBy('order')->paginate(20)->withQueryString();

        $stats = [
            'total' => MetalXPlaylist::count(),
            'active' => MetalXPlaylist::where('is_active', true)->count(),
            'synced' => MetalXPlaylist::where('is_synced', true)->count(),
        ];

        $isApiConfigured = $this->youtubeService->isConfigured();

        return view('admin.metal-x.playlists.index', compact('playlists', 'stats', 'isApiConfigured'));
    }

    /**
     * Show the form for creating a new playlist.
     */
    public function create()
    {
        $videos = MetalXVideo::active()->ordered()->get();
        $isApiConfigured = $this->youtubeService->isConfigured();

        return view('admin.metal-x.playlists.create', compact('videos', 'isApiConfigured'));
    }

    /**
     * Store a newly created playlist.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'youtube_url' => 'nullable|string',
            'title' => 'required_without:youtube_url|nullable|string|max:255',
            'title_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
            'videos' => 'nullable|array',
            'videos.*' => 'exists:metal_x_videos,id',
        ]);

        // If YouTube URL provided, import from YouTube
        if (! empty($validated['youtube_url'])) {
            $playlistId = YouTubeService::extractPlaylistId($validated['youtube_url']);

            if (! $playlistId) {
                return back()->withErrors(['youtube_url' => 'Invalid YouTube Playlist URL'])->withInput();
            }

            if (MetalXPlaylist::where('youtube_id', $playlistId)->exists()) {
                return back()->withErrors(['youtube_url' => 'This playlist already exists'])->withInput();
            }

            if ($this->youtubeService->isConfigured()) {
                $playlist = $this->youtubeService->importPlaylist($playlistId);

                if ($playlist) {
                    $playlist->update([
                        'title_th' => $validated['title_th'] ?? null,
                        'description_th' => $validated['description_th'] ?? null,
                        'is_featured' => $validated['is_featured'] ?? false,
                        'is_active' => $validated['is_active'] ?? true,
                        'order' => $validated['order'] ?? 0,
                    ]);

                    return redirect()->route('admin.metal-x.playlists.index')
                        ->with('success', 'Playlist imported from YouTube with ' . ($playlist->video_count) . ' videos!');
                }

                return back()->with('error', 'Failed to import playlist from YouTube');
            }

            return back()->with('error', 'YouTube API is not configured');
        }

        // Create manual playlist
        $playlist = MetalXPlaylist::create([
            'title' => $validated['title'],
            'title_th' => $validated['title_th'] ?? null,
            'description' => $validated['description'] ?? null,
            'description_th' => $validated['description_th'] ?? null,
            'is_featured' => $validated['is_featured'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $validated['order'] ?? 0,
        ]);

        // Attach videos
        if (! empty($validated['videos'])) {
            $position = 0;
            $syncData = [];
            foreach ($validated['videos'] as $videoId) {
                $syncData[$videoId] = ['position' => $position++];
            }
            $playlist->videos()->sync($syncData);
            $playlist->updateVideoCount();
        }

        return redirect()->route('admin.metal-x.playlists.index')
            ->with('success', 'Playlist created successfully!');
    }

    /**
     * Display the specified playlist.
     */
    public function show(MetalXPlaylist $playlist)
    {
        $playlist->load('videos');

        return view('admin.metal-x.playlists.show', compact('playlist'));
    }

    /**
     * Show the form for editing a playlist.
     */
    public function edit(MetalXPlaylist $playlist)
    {
        $playlist->load('videos');
        $allVideos = MetalXVideo::active()->ordered()->get();
        $selectedVideoIds = $playlist->videos->pluck('id')->toArray();

        return view('admin.metal-x.playlists.edit', compact('playlist', 'allVideos', 'selectedVideoIds'));
    }

    /**
     * Update the specified playlist.
     */
    public function update(Request $request, MetalXPlaylist $playlist)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'title_th' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_th' => 'nullable|string',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
            'videos' => 'nullable|array',
            'videos.*' => 'exists:metal_x_videos,id',
        ]);

        $playlist->update([
            'title' => $validated['title'],
            'title_th' => $validated['title_th'],
            'description' => $validated['description'],
            'description_th' => $validated['description_th'],
            'is_featured' => $validated['is_featured'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'order' => $validated['order'] ?? 0,
        ]);

        // Update videos
        if (isset($validated['videos'])) {
            $position = 0;
            $syncData = [];
            foreach ($validated['videos'] as $videoId) {
                $syncData[$videoId] = ['position' => $position++];
            }
            $playlist->videos()->sync($syncData);
        } else {
            $playlist->videos()->detach();
        }

        $playlist->updateVideoCount();

        return redirect()->route('admin.metal-x.playlists.index')
            ->with('success', 'Playlist updated successfully!');
    }

    /**
     * Remove the specified playlist.
     */
    public function destroy(MetalXPlaylist $playlist)
    {
        $playlist->videos()->detach();
        $playlist->delete();

        return redirect()->route('admin.metal-x.playlists.index')
            ->with('success', 'Playlist deleted successfully!');
    }

    /**
     * Toggle playlist active status.
     */
    public function toggle(MetalXPlaylist $playlist)
    {
        $playlist->update(['is_active' => ! $playlist->is_active]);

        return back()->with('success', 'Playlist status updated!');
    }

    /**
     * Sync playlist from YouTube.
     */
    public function sync(MetalXPlaylist $playlist)
    {
        if (! $playlist->youtube_id) {
            return back()->with('error', 'This playlist is not linked to YouTube');
        }

        if (! $this->youtubeService->isConfigured()) {
            return back()->with('error', 'YouTube API is not configured');
        }

        $this->youtubeService->syncPlaylistVideos($playlist);

        return back()->with('success', 'Playlist synced from YouTube! ' . $playlist->video_count . ' videos.');
    }

    /**
     * Reorder videos in playlist (AJAX).
     */
    public function reorder(Request $request, MetalXPlaylist $playlist)
    {
        $request->validate([
            'videos' => 'required|array',
            'videos.*' => 'exists:metal_x_videos,id',
        ]);

        $position = 0;
        $syncData = [];
        foreach ($request->videos as $videoId) {
            $syncData[$videoId] = ['position' => $position++];
        }

        $playlist->videos()->sync($syncData);

        return response()->json(['success' => true]);
    }

    /**
     * Add video to playlist (AJAX).
     */
    public function addVideo(Request $request, MetalXPlaylist $playlist)
    {
        $request->validate([
            'video_id' => 'required|exists:metal_x_videos,id',
        ]);

        $maxPosition = $playlist->videos()->max('metal_x_playlist_video.position') ?? -1;

        $playlist->videos()->attach($request->video_id, ['position' => $maxPosition + 1]);
        $playlist->updateVideoCount();

        return response()->json(['success' => true]);
    }

    /**
     * Remove video from playlist (AJAX).
     */
    public function removeVideo(Request $request, MetalXPlaylist $playlist)
    {
        $request->validate([
            'video_id' => 'required|exists:metal_x_videos,id',
        ]);

        $playlist->videos()->detach($request->video_id);
        $playlist->updateVideoCount();

        return response()->json(['success' => true]);
    }
}
