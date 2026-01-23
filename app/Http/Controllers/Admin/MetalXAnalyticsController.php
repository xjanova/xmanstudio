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
        // Channel Info with subscriber count
        $channelInfo = [
            'name' => Setting::getValue('metalx_channel_name', 'Metal-X Project'),
            'url' => Setting::getValue('metalx_channel_url'),
            'logo' => Setting::getValue('metalx_channel_logo'),
            'banner' => Setting::getValue('metalx_channel_banner'),
            'id' => Setting::getValue('metalx_channel_id'),
            'subscriber_count' => Setting::getValue('metalx_subscriber_count', 0),
            'channel_view_count' => Setting::getValue('metalx_view_count', 0),
        ];

        // Video Statistics with more details
        $totalVideos = MetalXVideo::count();
        $totalViews = MetalXVideo::sum('view_count');
        $totalLikes = MetalXVideo::sum('like_count');
        $totalComments = MetalXVideo::sum('comment_count');
        $totalDuration = MetalXVideo::sum('duration_seconds');

        $videoStats = [
            'total' => $totalVideos,
            'active' => MetalXVideo::where('is_active', true)->count(),
            'featured' => MetalXVideo::where('is_featured', true)->count(),
            'total_views' => $totalViews,
            'total_likes' => $totalLikes,
            'total_comments' => $totalComments,
            'avg_views' => $totalVideos > 0 ? round($totalViews / $totalVideos) : 0,
            'avg_likes' => $totalVideos > 0 ? round($totalLikes / $totalVideos) : 0,
            'avg_comments' => $totalVideos > 0 ? round($totalComments / $totalVideos) : 0,
            'engagement_rate' => $totalViews > 0 ? round(($totalLikes + $totalComments) / $totalViews * 100, 2) : 0,
            'total_duration_seconds' => $totalDuration,
            'total_duration_formatted' => $this->formatDuration($totalDuration),
            'avg_duration_seconds' => $totalVideos > 0 ? round($totalDuration / $totalVideos) : 0,
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

        // Top Videos by Engagement (likes + comments per view)
        $topVideosByEngagement = MetalXVideo::active()
            ->where('view_count', '>', 0)
            ->selectRaw('*, ((like_count + comment_count) / view_count * 100) as engagement_rate')
            ->orderByDesc('engagement_rate')
            ->limit(10)
            ->get();

        // Recent Videos
        $recentVideos = MetalXVideo::active()
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        // Videos by Month (last 12 months)
        $videosByMonth = MetalXVideo::selectRaw('DATE_FORMAT(published_at, "%Y-%m") as month, COUNT(*) as count, SUM(view_count) as views, SUM(like_count) as likes')
            ->whereNotNull('published_at')
            ->where('published_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Performance Comparison (This month vs Last month)
        $thisMonthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $thisMonthStats = MetalXVideo::where('published_at', '>=', $thisMonthStart)
            ->selectRaw('COUNT(*) as videos, COALESCE(SUM(view_count), 0) as views, COALESCE(SUM(like_count), 0) as likes')
            ->first();

        $lastMonthStats = MetalXVideo::whereBetween('published_at', [$lastMonthStart, $lastMonthEnd])
            ->selectRaw('COUNT(*) as videos, COALESCE(SUM(view_count), 0) as views, COALESCE(SUM(like_count), 0) as likes')
            ->first();

        $performanceComparison = [
            'this_month' => [
                'videos' => $thisMonthStats->videos ?? 0,
                'views' => $thisMonthStats->views ?? 0,
                'likes' => $thisMonthStats->likes ?? 0,
            ],
            'last_month' => [
                'videos' => $lastMonthStats->videos ?? 0,
                'views' => $lastMonthStats->views ?? 0,
                'likes' => $lastMonthStats->likes ?? 0,
            ],
            'growth' => [
                'videos' => $this->calculateGrowth($lastMonthStats->videos ?? 0, $thisMonthStats->videos ?? 0),
                'views' => $this->calculateGrowth($lastMonthStats->views ?? 0, $thisMonthStats->views ?? 0),
                'likes' => $this->calculateGrowth($lastMonthStats->likes ?? 0, $thisMonthStats->likes ?? 0),
            ],
        ];

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
            'topVideosByEngagement',
            'recentVideos',
            'videosByMonth',
            'performanceComparison',
            'isApiConfigured',
            'lastSync'
        ));
    }

    /**
     * Calculate growth percentage between two values.
     */
    private function calculateGrowth($oldValue, $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return round(($newValue - $oldValue) / $oldValue * 100, 1);
    }

    /**
     * Format duration in seconds to human readable format.
     */
    private function formatDuration($seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' วินาที';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return $hours . ' ชม. ' . $minutes . ' นาที';
        }

        return $minutes . ' นาที';
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
