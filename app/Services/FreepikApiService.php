<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FreepikApiService
{
    protected string $apiKey;

    protected string $baseUrl = 'https://api.freepik.com/v1/ai';

    public function __construct()
    {
        $this->apiKey = (string) Setting::getValue('freepik_api_key', '');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Generate an image from text prompt using Mystic API.
     *
     * @return array{task_id: string, status: string}|null
     */
    public function generateImage(
        string $prompt,
        string $aspectRatio = 'widescreen_16_9',
        string $model = 'realism',
        string $resolution = '2k',
    ): ?array {
        if (! $this->isConfigured()) {
            Log::error('[FreepikApi] API key not configured');

            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-freepik-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("{$this->baseUrl}/mystic", [
                'prompt' => $prompt,
                'aspect_ratio' => $aspectRatio,
                'model' => $model,
                'resolution' => $resolution,
                'filter_nsfw' => true,
            ]);

            if ($response->successful()) {
                $data = $response->json('data');
                Log::info('[FreepikApi] Image generation started', [
                    'task_id' => $data['task_id'] ?? null,
                ]);

                return $data;
            }

            Log::error('[FreepikApi] Image generation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("[FreepikApi] Image generation error: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Check image generation status and get result.
     *
     * @return array{status: string, generated?: array}|null
     */
    public function getImageStatus(string $taskId): ?array
    {
        try {
            $response = Http::withHeaders([
                'x-freepik-api-key' => $this->apiKey,
            ])->timeout(15)->get("{$this->baseUrl}/mystic/{$taskId}");

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error("[FreepikApi] Image status check error: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Start image-to-video conversion using Kling v2.
     *
     * @param  string  $imageUrl  URL or base64 of the source image
     * @return array{task_id: string, status: string}|null
     */
    public function imageToVideo(
        string $imageUrl,
        string $duration = '10',
        string $prompt = '',
        ?string $webhookUrl = null,
    ): ?array {
        if (! $this->isConfigured()) {
            Log::error('[FreepikApi] API key not configured');

            return null;
        }

        try {
            $body = [
                'image' => $imageUrl,
                'duration' => $duration,
            ];

            if (! empty($prompt)) {
                $body['prompt'] = $prompt;
            }

            if ($webhookUrl) {
                $body['webhook_url'] = $webhookUrl;
            }

            $response = Http::withHeaders([
                'x-freepik-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("{$this->baseUrl}/image-to-video/kling-v2", $body);

            if ($response->successful()) {
                $data = $response->json('data');
                Log::info('[FreepikApi] Video generation started', [
                    'task_id' => $data['task_id'] ?? null,
                ]);

                return $data;
            }

            Log::error('[FreepikApi] Video generation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("[FreepikApi] Video generation error: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Check video generation status and get result.
     */
    public function getVideoStatus(string $taskId): ?array
    {
        try {
            $response = Http::withHeaders([
                'x-freepik-api-key' => $this->apiKey,
            ])->timeout(15)->get("{$this->baseUrl}/image-to-video/kling-v2/{$taskId}");

            if ($response->successful()) {
                return $response->json('data');
            }

            // Try listing endpoint as fallback
            $response = Http::withHeaders([
                'x-freepik-api-key' => $this->apiKey,
            ])->timeout(15)->get("{$this->baseUrl}/image-to-video/kling-v2");

            if ($response->successful()) {
                $tasks = $response->json('data', []);
                foreach ($tasks as $task) {
                    if (($task['task_id'] ?? '') === $taskId) {
                        return $task;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("[FreepikApi] Video status check error: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Wait for a task to complete (poll with backoff).
     *
     * @param  callable  $statusChecker  Function that takes task_id and returns status array
     * @return array|null Final task data when COMPLETED
     */
    public function pollUntilComplete(string $taskId, callable $statusChecker, int $maxWaitSeconds = 300, int $intervalSeconds = 10): ?array
    {
        $elapsed = 0;

        while ($elapsed < $maxWaitSeconds) {
            sleep($intervalSeconds);
            $elapsed += $intervalSeconds;

            $result = $statusChecker($taskId);
            if (! $result) {
                continue;
            }

            $status = $result['status'] ?? '';
            Log::info("[FreepikApi] Poll: task {$taskId} status={$status} ({$elapsed}s)");

            if ($status === 'COMPLETED') {
                return $result;
            }

            if ($status === 'FAILED') {
                Log::error("[FreepikApi] Task {$taskId} failed");

                return null;
            }
        }

        Log::warning("[FreepikApi] Task {$taskId} timed out after {$maxWaitSeconds}s");

        return null;
    }
}
