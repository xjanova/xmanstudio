<?php

namespace App\Services;

use App\Models\MetalXChannel;
use App\Models\MetalXVideo;
use App\Models\MetalXVideoProject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class YouTubeUploadService
{
    /**
     * Upload a video project to YouTube.
     */
    public function upload(MetalXVideoProject $project): array
    {
        $channel = $project->channel;

        if (! $channel) {
            throw new \RuntimeException('No channel assigned to project');
        }

        $accessToken = $channel->getValidAccessToken();

        if (empty($accessToken)) {
            throw new \RuntimeException('No valid access token for channel: ' . $channel->name);
        }

        $videoPath = $project->video_file_path;

        if (empty($videoPath) || ! Storage::disk('local')->exists($videoPath)) {
            throw new \RuntimeException('Video file not found');
        }

        $fullPath = Storage::disk('local')->path($videoPath);
        $fileSize = filesize($fullPath);

        // Step 1: Initiate resumable upload
        $uploadUrl = $this->initiateUpload($accessToken, $project, $fileSize);

        if (! $uploadUrl) {
            throw new \RuntimeException('Failed to initiate upload session');
        }

        // Step 2: Upload in chunks
        $youtubeData = $this->uploadFile($uploadUrl, $fullPath, $fileSize);

        if (! $youtubeData) {
            throw new \RuntimeException('Failed to upload video file');
        }

        $youtubeId = $youtubeData['id'] ?? null;

        // Step 3: Create local MetalXVideo record
        if ($youtubeId) {
            $video = MetalXVideo::updateOrCreate(
                ['youtube_id' => $youtubeId],
                [
                    'title' => $project->title ?? 'Untitled',
                    'description' => $project->description,
                    'tags' => $project->tags,
                    'channel_id' => $channel->youtube_channel_id,
                    'channel_title' => $channel->name,
                    'metal_x_channel_id' => $channel->id,
                    'source' => 'created',
                    'privacy_status' => $project->privacy_status ?? 'private',
                    'is_active' => true,
                    'published_at' => ($project->scheduled_at && $project->scheduled_at->isFuture()) ? $project->scheduled_at : now(),
                ],
            );

            $project->update(['video_id' => $video->id]);

            Log::info("[YouTube Upload] Video uploaded: {$youtubeId} to channel {$channel->name}");

            return [
                'youtube_id' => $youtubeId,
                'youtube_url' => "https://www.youtube.com/watch?v={$youtubeId}",
                'video' => $video,
            ];
        }

        throw new \RuntimeException('Upload completed but no YouTube ID returned');
    }

    /**
     * Initiate a resumable upload session.
     */
    protected function initiateUpload(string $accessToken, MetalXVideoProject $project, int $fileSize): ?string
    {
        $categoryId = config('metalx.upload.default_category_id', '10');

        // Determine privacy and scheduling
        $privacyStatus = $project->privacy_status ?? 'private';
        $statusData = [
            'privacyStatus' => $privacyStatus,
            'selfDeclaredMadeForKids' => false,
        ];

        // YouTube scheduled publishing: upload as private with publishAt
        // YouTube will auto-publish at the specified time
        if ($project->scheduled_at && $project->scheduled_at->isFuture()) {
            $statusData['privacyStatus'] = 'private';
            $statusData['publishAt'] = $project->scheduled_at->toIso8601String();
            Log::info("[YouTube Upload] Scheduled publish at: {$project->scheduled_at->toDateTimeString()}");
        }

        $metadata = [
            'snippet' => [
                'title' => Str::limit($project->title ?? 'Untitled', 100),
                'description' => Str::limit($project->description ?? '', 5000),
                'tags' => array_slice($project->tags ?? [], 0, 500),
                'categoryId' => $categoryId,
            ],
            'status' => $statusData,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json; charset=UTF-8',
                'X-Upload-Content-Length' => $fileSize,
                'X-Upload-Content-Type' => 'video/mp4',
            ])->post('https://www.googleapis.com/upload/youtube/v3/videos?uploadType=resumable&part=snippet,status', $metadata);

            if ($response->status() === 200) {
                return $response->header('Location');
            }

            Log::error('[YouTube Upload] Initiate failed', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("[YouTube Upload] Initiate error: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Upload file to the resumable upload URL.
     */
    protected function uploadFile(string $uploadUrl, string $filePath, int $fileSize): ?array
    {
        $chunkSize = config('metalx.upload.chunk_size', 10 * 1024 * 1024);

        try {
            $handle = fopen($filePath, 'rb');

            if (! $handle) {
                throw new \RuntimeException('Cannot open video file');
            }

            $offset = 0;

            while ($offset < $fileSize) {
                $chunk = fread($handle, $chunkSize);
                $chunkLength = strlen($chunk);
                $endByte = $offset + $chunkLength - 1;

                $response = Http::withHeaders([
                    'Content-Length' => $chunkLength,
                    'Content-Range' => "bytes {$offset}-{$endByte}/{$fileSize}",
                    'Content-Type' => 'video/mp4',
                ])->timeout(300)->withBody($chunk, 'video/mp4')->put($uploadUrl);

                if ($response->status() === 200 || $response->status() === 201) {
                    // Upload complete
                    fclose($handle);

                    return $response->json();
                }

                if ($response->status() === 308) {
                    // Resume incomplete - continue uploading
                    $offset += $chunkLength;

                    continue;
                }

                // Error
                fclose($handle);
                Log::error('[YouTube Upload] Chunk upload failed', [
                    'status' => $response->status(),
                    'offset' => $offset,
                ]);

                return null;
            }

            fclose($handle);
        } catch (\Exception $e) {
            Log::error("[YouTube Upload] Upload error: {$e->getMessage()}");

            return null;
        }

        return null;
    }

    /**
     * Set a custom thumbnail for a video.
     */
    public function setThumbnail(MetalXChannel $channel, string $youtubeId, string $thumbnailPath): bool
    {
        $accessToken = $channel->getValidAccessToken();

        if (empty($accessToken)) {
            return false;
        }

        try {
            $fullPath = Storage::disk('local')->exists($thumbnailPath)
                ? Storage::disk('local')->path($thumbnailPath)
                : $thumbnailPath;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->attach('media', file_get_contents($fullPath), 'thumbnail.jpg')
                ->post("https://www.googleapis.com/upload/youtube/v3/thumbnails/set?videoId={$youtubeId}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("[YouTube Upload] Thumbnail error: {$e->getMessage()}");

            return false;
        }
    }
}
