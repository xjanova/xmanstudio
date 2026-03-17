<?php

namespace App\Jobs;

use App\Models\MetalXChannel;
use App\Models\Setting;
use App\Services\YouTubeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncAllVideosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 600; // 10 minutes max

    protected string $progressKey;

    protected int $limit;

    public function __construct(string $progressKey, int $limit = 0)
    {
        $this->progressKey = $progressKey;
        $this->limit = $limit;
    }

    public function handle(YouTubeService $youtubeService): void
    {
        // Allow unlimited execution time since this runs after response
        set_time_limit(0);

        $channels = MetalXChannel::active()->get();

        if ($channels->isEmpty()) {
            $channelId = Setting::getValue('metalx_channel_id');

            if (! $channelId) {
                Cache::put($this->progressKey, [
                    'status' => 'failed',
                    'error' => 'No channels configured',
                ], 600);

                return;
            }

            $channels = collect([(object) ['youtube_channel_id' => $channelId, 'id' => null, 'name' => 'Default']]);
        }

        // Initialize master progress
        Cache::put($this->progressKey, [
            'status' => 'running',
            'channels_total' => $channels->count(),
            'channels_done' => 0,
            'total_imported' => 0,
            'total_updated' => 0,
            'total_deleted' => 0,
            'current_channel' => '',
            'channel_progress' => [],
        ], 600);

        $totalImported = 0;
        $totalUpdated = 0;
        $totalDeleted = 0;
        $newVideos = [];

        foreach ($channels as $index => $channel) {
            $channelModel = $channel instanceof MetalXChannel ? $channel : null;
            $channelProgressKey = $this->progressKey . '_ch_' . $index;

            // Update master progress with current channel
            $masterProgress = Cache::get($this->progressKey, []);
            $masterProgress['current_channel'] = $channel->name ?? $channel->youtube_channel_id;
            Cache::put($this->progressKey, $masterProgress, 600);

            try {
                $result = $youtubeService->syncChannelVideos(
                    $channel->youtube_channel_id,
                    $this->limit,
                    $channelModel,
                    $channelProgressKey
                );

                $totalImported += $result['imported'];
                $totalUpdated += $result['updated'];
                $totalDeleted += $result['deleted'];
                $newVideos = array_merge($newVideos, $result['videos']);
            } catch (\Exception $e) {
                Log::error("[SyncAllVideos] Error syncing channel {$channel->name}: " . $e->getMessage());
            }

            // Update master progress
            $channelResult = Cache::get($channelProgressKey, []);
            $masterProgress = Cache::get($this->progressKey, []);
            $masterProgress['channels_done'] = $index + 1;
            $masterProgress['total_imported'] = $totalImported;
            $masterProgress['total_updated'] = $totalUpdated;
            $masterProgress['total_deleted'] = $totalDeleted;
            $masterProgress['channel_progress'][$channel->name ?? $channel->youtube_channel_id] = $channelResult;
            Cache::put($this->progressKey, $masterProgress, 600);

            Cache::forget($channelProgressKey);
        }

        // Dispatch AI metadata generation for new videos without Thai metadata
        if (Setting::get('ai_content_generation', false)) {
            foreach ($newVideos as $video) {
                if (empty($video->title_th)) {
                    GenerateVideoMetadataJob::dispatch($video, false, 80.0);
                }
            }
        }

        // Mark complete
        Cache::put($this->progressKey, [
            'status' => 'completed',
            'channels_total' => $channels->count(),
            'channels_done' => $channels->count(),
            'total_imported' => $totalImported,
            'total_updated' => $totalUpdated,
            'total_deleted' => $totalDeleted,
            'current_channel' => '',
            'channel_progress' => Cache::get($this->progressKey, [])['channel_progress'] ?? [],
        ], 600);

        Log::info("[SyncAllVideos] Complete: imported {$totalImported}, updated {$totalUpdated}, deleted {$totalDeleted} from {$channels->count()} channels");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[SyncAllVideos] Job failed: ' . $exception->getMessage());

        Cache::put($this->progressKey, [
            'status' => 'failed',
            'error' => 'Sync job failed: ' . $exception->getMessage(),
        ], 600);
    }
}
