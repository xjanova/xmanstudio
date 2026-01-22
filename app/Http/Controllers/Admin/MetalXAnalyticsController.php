<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetalXPlaylist;
use App\Models\MetalXTeamMember;
use App\Models\MetalXVideo;
use App\Models\Setting;
use App\Services\YouTubeService;
use Illuminate\Http\Request;

class MetalXAnalyticsController extends Controller
{
    protected YouTubeService $youtubeService;

    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }

    /**
     * Display the analytics dashboard.
     */
    public function index()
    {
        // Channel Info
        $channelInfo = [
            'name' => Setting::getValue('metalx_channel_name', 'Metal-X Project'),
            'url' => Setting::getValue('metalx_channel_url'),
            'logo' => Setting::getValue('metalx_channel_logo'),
            'banner' => Setting::getValue('metalx_channel_banner'),
            'id' => Setting::getValue('metalx_channel_id'),
        ];

        // Video Statistics
        $videoStats = [
            'total' => MetalXVideo::count(),
            'active' => MetalXVideo::where('is_active', true)->count(),
            'featured' => MetalXVideo::where('is_featured', true)->count(),
            'total_views' => MetalXVideo::sum('view_count'),
            'total_likes' => MetalXVideo::sum('like_count'),
            'total_comments' => MetalXVideo::sum('comment_count'),
            'avg_views' => MetalXVideo::count() > 0 ? round(MetalXVideo::avg('view_count')) : 0,
        ];

        // Playlist Statistics
        $playlistStats = [
            'total' => MetalXPlaylist::count(),
            'active' => MetalXPlaylist::where('is_active', true)->count(),
            'synced' => MetalXPlaylist::where('is_synced', true)->count(),
            'total_videos' => MetalXPlaylist::sum('video_count'),
        ];

        // Team Statistics
        $teamStats = [
            'total' => MetalXTeamMember::count(),
            'active' => MetalXTeamMember::where('is_active', true)->count(),
        ];

        // Top Videos by Views
        $topVideosByViews = MetalXVideo::active()
            ->orderByDesc('view_count')
            ->limit(10)
            ->get();

        // Top Videos by Likes
        $topVideosByLikes = MetalXVideo::active()
            ->orderByDesc('like_count')
            ->limit(10)
            ->get();

        // Recent Videos
        $recentVideos = MetalXVideo::active()
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        // Videos by Month (last 12 months)
        $videosByMonth = MetalXVideo::selectRaw('DATE_FORMAT(published_at, "%Y-%m") as month, COUNT(*) as count, SUM(view_count) as views')
            ->whereNotNull('published_at')
            ->where('published_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // API Status
        $isApiConfigured = $this->youtubeService->isConfigured();

        // Last sync time
        $lastSync = MetalXVideo::max('synced_at');

        return view('admin.metal-x.analytics', compact(
            'channelInfo',
            'videoStats',
            'playlistStats',
            'teamStats',
            'topVideosByViews',
            'topVideosByLikes',
            'recentVideos',
            'videosByMonth',
            'isApiConfigured',
            'lastSync'
        ));
    }

    /**
     * Refresh channel statistics.
     */
    public function refresh(Request $request)
    {
        if (! $this->youtubeService->isConfigured()) {
            return back()->with('error', 'YouTube API is not configured');
        }

        $channelId = Setting::getValue('metalx_channel_id');

        if ($channelId) {
            $channelInfo = $this->youtubeService->getChannelInfo($channelId);

            if ($channelInfo) {
                // Update channel statistics in settings if needed
                $stats = $channelInfo['statistics'] ?? [];
                Setting::setValue('metalx_subscriber_count', $stats['subscriberCount'] ?? 0, 'integer', 'metalx');
                Setting::setValue('metalx_video_count', $stats['videoCount'] ?? 0, 'integer', 'metalx');
                Setting::setValue('metalx_view_count', $stats['viewCount'] ?? 0, 'integer', 'metalx');
            }
        }

        // Update video statistics
        $updated = $this->youtubeService->updateVideoStatistics();

        return back()->with('success', "Statistics refreshed! {$updated} videos updated.");
    }

    /**
     * Export analytics data.
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');

        $videos = MetalXVideo::orderByDesc('view_count')->get();

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="metal-x-analytics-'.date('Y-m-d').'.csv"',
            ];

            $callback = function () use ($videos) {
                $file = fopen('php://output', 'w');

                // Header row
                fputcsv($file, [
                    'YouTube ID',
                    'Title',
                    'Views',
                    'Likes',
                    'Comments',
                    'Duration',
                    'Published At',
                    'Status',
                    'Featured',
                ]);

                foreach ($videos as $video) {
                    fputcsv($file, [
                        $video->youtube_id,
                        $video->title,
                        $video->view_count,
                        $video->like_count,
                        $video->comment_count,
                        $video->formatted_duration,
                        $video->published_at?->format('Y-m-d H:i:s'),
                        $video->is_active ? 'Active' : 'Inactive',
                        $video->is_featured ? 'Yes' : 'No',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        // JSON format
        return response()->json([
            'exported_at' => now()->toISOString(),
            'total_videos' => $videos->count(),
            'total_views' => $videos->sum('view_count'),
            'total_likes' => $videos->sum('like_count'),
            'videos' => $videos->map(fn ($v) => [
                'youtube_id' => $v->youtube_id,
                'title' => $v->title,
                'views' => $v->view_count,
                'likes' => $v->like_count,
                'comments' => $v->comment_count,
                'published_at' => $v->published_at?->toISOString(),
            ]),
        ]);
    }
}
