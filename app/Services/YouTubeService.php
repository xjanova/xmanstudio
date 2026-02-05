<?php

namespace App\Services;

use App\Models\MetalXPlaylist;
use App\Models\MetalXVideo;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    protected ?string $apiKey;

    protected string $baseUrl = 'https://www.googleapis.com/youtube/v3';

    public function __construct()
    {
        $this->apiKey = Setting::getValue('youtube_api_key');
    }

    /**
     * Check if API key is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Get channel information.
     */
    public function getChannelInfo(string $channelId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::get("{$this->baseUrl}/channels", [
                'key' => $this->apiKey,
                'id' => $channelId,
                'part' => 'snippet,statistics,contentDetails,brandingSettings',
            ]);

            if ($response->successful() && ! empty($response->json('items'))) {
                return $response->json('items.0');
            }
        } catch (\Exception $e) {
            Log::error('YouTube API Error (getChannelInfo): ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get channel by username or handle.
     */
    public function getChannelByHandle(string $handle): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        // Remove @ if present
        $handle = ltrim($handle, '@');

        try {
            $response = Http::get("{$this->baseUrl}/channels", [
                'key' => $this->apiKey,
                'forHandle' => $handle,
                'part' => 'snippet,statistics,contentDetails',
            ]);

            if ($response->successful() && ! empty($response->json('items'))) {
                return $response->json('items.0');
            }
        } catch (\Exception $e) {
            Log::error('YouTube API Error (getChannelByHandle): ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get video details by ID.
     */
    public function getVideoDetails(string $videoId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::get("{$this->baseUrl}/videos", [
                'key' => $this->apiKey,
                'id' => $videoId,
                'part' => 'snippet,statistics,contentDetails',
            ]);

            if ($response->successful() && ! empty($response->json('items'))) {
                return $response->json('items.0');
            }
        } catch (\Exception $e) {
            Log::error('YouTube API Error (getVideoDetails): ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get multiple videos by IDs.
     */
    public function getVideosDetails(array $videoIds): array
    {
        if (! $this->isConfigured() || empty($videoIds)) {
            return [];
        }

        try {
            $response = Http::get("{$this->baseUrl}/videos", [
                'key' => $this->apiKey,
                'id' => implode(',', $videoIds),
                'part' => 'snippet,statistics,contentDetails',
            ]);

            if ($response->successful()) {
                return $response->json('items', []);
            }
        } catch (\Exception $e) {
            Log::error('YouTube API Error (getVideosDetails): ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Search videos from channel.
     */
    public function searchChannelVideos(string $channelId, int $maxResults = 50, ?string $pageToken = null): array
    {
        if (! $this->isConfigured()) {
            return ['items' => [], 'nextPageToken' => null];
        }

        try {
            $params = [
                'key' => $this->apiKey,
                'channelId' => $channelId,
                'part' => 'snippet',
                'type' => 'video',
                'order' => 'date',
                'maxResults' => min($maxResults, 50),
            ];

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = Http::get("{$this->baseUrl}/search", $params);

            if ($response->successful()) {
                return [
                    'items' => $response->json('items', []),
                    'nextPageToken' => $response->json('nextPageToken'),
                    'totalResults' => $response->json('pageInfo.totalResults', 0),
                ];
            }
        } catch (\Exception $e) {
            Log::error('YouTube API Error (searchChannelVideos): ' . $e->getMessage());
        }

        return ['items' => [], 'nextPageToken' => null];
    }

    /**
     * Get playlist details.
     */
    public function getPlaylistDetails(string $playlistId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::get("{$this->baseUrl}/playlists", [
                'key' => $this->apiKey,
                'id' => $playlistId,
                'part' => 'snippet,contentDetails',
            ]);

            if ($response->successful() && ! empty($response->json('items'))) {
                return $response->json('items.0');
            }
        } catch (\Exception $e) {
            Log::error('YouTube API Error (getPlaylistDetails): ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get playlist items (videos in playlist).
     */
    public function getPlaylistItems(string $playlistId, int $maxResults = 50, ?string $pageToken = null): array
    {
        if (! $this->isConfigured()) {
            return ['items' => [], 'nextPageToken' => null];
        }

        try {
            $params = [
                'key' => $this->apiKey,
                'playlistId' => $playlistId,
                'part' => 'snippet,contentDetails',
                'maxResults' => min($maxResults, 50),
            ];

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $response = Http::get("{$this->baseUrl}/playlistItems", $params);

            if ($response->successful()) {
                return [
                    'items' => $response->json('items', []),
                    'nextPageToken' => $response->json('nextPageToken'),
                    'totalResults' => $response->json('pageInfo.totalResults', 0),
                ];
            }
        } catch (\Exception $e) {
            Log::error('YouTube API Error (getPlaylistItems): ' . $e->getMessage());
        }

        return ['items' => [], 'nextPageToken' => null];
    }

    /**
     * Get channel playlists.
     */
    public function getChannelPlaylists(string $channelId, int $maxResults = 50): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::get("{$this->baseUrl}/playlists", [
                'key' => $this->apiKey,
                'channelId' => $channelId,
                'part' => 'snippet,contentDetails',
                'maxResults' => min($maxResults, 50),
            ]);

            if ($response->successful()) {
                return $response->json('items', []);
            }
        } catch (\Exception $e) {
            Log::error('YouTube API Error (getChannelPlaylists): ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Import a video from YouTube.
     */
    public function importVideo(string $videoId): ?MetalXVideo
    {
        $details = $this->getVideoDetails($videoId);

        if (! $details) {
            return null;
        }

        return MetalXVideo::updateOrCreate(
            ['youtube_id' => $videoId],
            $this->mapVideoData($details)
        );
    }

    /**
     * Import multiple videos from YouTube.
     */
    public function importVideos(array $videoIds): array
    {
        $details = $this->getVideosDetails($videoIds);
        $imported = [];

        foreach ($details as $detail) {
            $video = MetalXVideo::updateOrCreate(
                ['youtube_id' => $detail['id']],
                $this->mapVideoData($detail)
            );
            $imported[] = $video;
        }

        return $imported;
    }

    /**
     * Sync all videos from channel.
     */
    public function syncChannelVideos(string $channelId, int $limit = 100): array
    {
        $imported = [];
        $pageToken = null;
        $count = 0;

        do {
            $result = $this->searchChannelVideos($channelId, 50, $pageToken);
            $videoIds = array_map(fn ($item) => $item['id']['videoId'], $result['items']);

            if (! empty($videoIds)) {
                $videos = $this->importVideos($videoIds);
                $imported = array_merge($imported, $videos);
                $count += count($videos);
            }

            $pageToken = $result['nextPageToken'];
        } while ($pageToken && $count < $limit);

        return $imported;
    }

    /**
     * Import a playlist from YouTube.
     */
    public function importPlaylist(string $playlistId): ?MetalXPlaylist
    {
        $details = $this->getPlaylistDetails($playlistId);

        if (! $details) {
            return null;
        }

        $playlist = MetalXPlaylist::updateOrCreate(
            ['youtube_id' => $playlistId],
            [
                'title' => $details['snippet']['title'] ?? 'Untitled',
                'description' => $details['snippet']['description'] ?? null,
                'thumbnail_url' => $details['snippet']['thumbnails']['high']['url'] ?? $details['snippet']['thumbnails']['default']['url'] ?? null,
                'video_count' => $details['contentDetails']['itemCount'] ?? 0,
                'is_synced' => true,
                'synced_at' => now(),
            ]
        );

        // Sync playlist videos
        $this->syncPlaylistVideos($playlist);

        return $playlist;
    }

    /**
     * Sync videos in a playlist.
     */
    public function syncPlaylistVideos(MetalXPlaylist $playlist): void
    {
        if (! $playlist->youtube_id) {
            return;
        }

        $pageToken = null;
        $position = 0;
        $videoIds = [];

        do {
            $result = $this->getPlaylistItems($playlist->youtube_id, 50, $pageToken);

            foreach ($result['items'] as $item) {
                $videoId = $item['contentDetails']['videoId'] ?? $item['snippet']['resourceId']['videoId'] ?? null;
                if ($videoId) {
                    $videoIds[$videoId] = $position++;
                }
            }

            $pageToken = $result['nextPageToken'];
        } while ($pageToken);

        // Import all videos
        if (! empty($videoIds)) {
            $this->importVideos(array_keys($videoIds));

            // Attach videos to playlist
            $videos = MetalXVideo::whereIn('youtube_id', array_keys($videoIds))->get();
            $syncData = [];

            foreach ($videos as $video) {
                $syncData[$video->id] = ['position' => $videoIds[$video->youtube_id]];
            }

            $playlist->videos()->sync($syncData);
            $playlist->updateVideoCount();
        }
    }

    /**
     * Update statistics for all videos.
     */
    public function updateVideoStatistics(): int
    {
        $videos = MetalXVideo::pluck('youtube_id')->toArray();
        $updated = 0;

        // Process in batches of 50
        foreach (array_chunk($videos, 50) as $batch) {
            $details = $this->getVideosDetails($batch);

            foreach ($details as $detail) {
                MetalXVideo::where('youtube_id', $detail['id'])->update([
                    'view_count' => $detail['statistics']['viewCount'] ?? 0,
                    'like_count' => $detail['statistics']['likeCount'] ?? 0,
                    'comment_count' => $detail['statistics']['commentCount'] ?? 0,
                    'synced_at' => now(),
                ]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Map YouTube API video data to model fields.
     */
    protected function mapVideoData(array $data): array
    {
        $snippet = $data['snippet'] ?? [];
        $statistics = $data['statistics'] ?? [];
        $contentDetails = $data['contentDetails'] ?? [];

        return [
            'title' => $snippet['title'] ?? 'Untitled',
            'description' => $snippet['description'] ?? null,
            'thumbnail_url' => $snippet['thumbnails']['default']['url'] ?? null,
            'thumbnail_medium_url' => $snippet['thumbnails']['medium']['url'] ?? null,
            'thumbnail_high_url' => $snippet['thumbnails']['high']['url'] ?? $snippet['thumbnails']['maxres']['url'] ?? null,
            'channel_id' => $snippet['channelId'] ?? null,
            'channel_title' => $snippet['channelTitle'] ?? null,
            'view_count' => $statistics['viewCount'] ?? 0,
            'like_count' => $statistics['likeCount'] ?? 0,
            'comment_count' => $statistics['commentCount'] ?? 0,
            'duration' => $contentDetails['duration'] ?? null,
            'duration_seconds' => $this->parseDuration($contentDetails['duration'] ?? 'PT0S'),
            'tags' => $snippet['tags'] ?? [],
            'category' => $snippet['categoryId'] ?? null,
            'privacy_status' => $contentDetails['privacyStatus'] ?? $snippet['privacyStatus'] ?? 'public',
            'published_at' => isset($snippet['publishedAt']) ? Carbon::parse($snippet['publishedAt']) : null,
            'synced_at' => now(),
        ];
    }

    /**
     * Parse ISO 8601 duration to seconds.
     */
    protected function parseDuration(string $duration): int
    {
        try {
            $interval = new \DateInterval($duration);

            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Extract video ID from YouTube URL.
     */
    public static function extractVideoId(string $url): ?string
    {
        $patterns = [
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            '/youtu\.be\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        // Check if it's already a video ID
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }

        return null;
    }

    /**
     * Extract playlist ID from YouTube URL.
     */
    public static function extractPlaylistId(string $url): ?string
    {
        if (preg_match('/[?&]list=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Check if it's already a playlist ID
        if (preg_match('/^[a-zA-Z0-9_-]+$/', $url) && strlen($url) > 11) {
            return $url;
        }

        return null;
    }
}
