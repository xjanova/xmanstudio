<?php

namespace App\Services;

use App\Models\MetalXMusicGeneration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SunoMusicService
{
    protected string $apiKey;

    protected string $baseUrl;

    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('metalx.suno.api_key') ?: (string) \App\Models\Setting::getValue('suno_api_key', '');
        $this->baseUrl = (string) \App\Models\Setting::getValue('suno_base_url') ?: config('metalx.suno.base_url', 'https://api.sunoapi.org');
        $this->timeout = config('metalx.suno.timeout', 120);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Generate music via Suno API.
     */
    public function generateMusic(string $prompt, string $style = '', int $durationSeconds = 60): MetalXMusicGeneration
    {
        $generation = MetalXMusicGeneration::create([
            'prompt' => $prompt,
            'style' => $style,
            'duration_seconds' => $durationSeconds,
            'status' => 'pending',
        ]);

        try {
            $callbackUrl = config('app.url', 'http://localhost') . '/api/suno/callback';

            $payload = [
                'prompt' => $prompt,
                'customMode' => true,
                'instrumental' => true,
                'style' => $style ?: 'metal',
                'title' => 'Generated Track',
                'model' => 'V4',
                'callBackUrl' => $callbackUrl,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($this->timeout)->post("{$this->baseUrl}/api/v1/generate", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $taskId = $data['data']['taskId'] ?? $data['taskId'] ?? null;

                $generation->update([
                    'suno_task_id' => $taskId,
                    'status' => 'processing',
                    'metadata' => $data,
                ]);

                Log::info("[Suno] Music generation started, task: {$taskId}");
            } else {
                $generation->update([
                    'status' => 'failed',
                    'error_message' => 'API error: ' . \Illuminate\Support\Str::limit($response->body(), 500),
                ]);

                Log::error('[Suno] Generation request failed', ['status' => $response->status()]);
            }
        } catch (\Exception $e) {
            $generation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error("[Suno] Generation error: {$e->getMessage()}");
        }

        return $generation;
    }

    /**
     * Check the status of a Suno generation task.
     */
    public function checkStatus(MetalXMusicGeneration $generation): MetalXMusicGeneration
    {
        if (! $generation->suno_task_id) {
            return $generation;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(30)->get("{$this->baseUrl}/api/v1/generate/record-info", [
                'taskId' => $generation->suno_task_id,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['data']['status'] ?? '';
                $sunoData = $data['data']['response']['sunoData'] ?? $data['data']['sunoData'] ?? [];

                if (in_array($status, ['SUCCESS', 'FIRST_SUCCESS'])) {
                    $track = is_array($sunoData) ? ($sunoData[0] ?? null) : null;
                    $audioUrl = $track['audioUrl'] ?? $track['audio_url'] ?? $track['streamAudioUrl'] ?? null;

                    $generation->update([
                        'status' => 'completed',
                        'audio_url' => $audioUrl,
                        'title' => $track['title'] ?? $generation->title,
                        'metadata' => $data,
                    ]);

                    Log::info("[Suno] Generation completed: {$generation->suno_task_id}");
                } elseif (str_contains($status, 'FAILED') || $status === 'SENSITIVE_WORD_ERROR') {
                    $generation->update([
                        'status' => 'failed',
                        'error_message' => "Suno generation failed: {$status}",
                        'metadata' => $data,
                    ]);
                } else {
                    $generation->update(['metadata' => $data]);
                }
            }
        } catch (\Exception $e) {
            Log::error("[Suno] Status check error: {$e->getMessage()}");
        }

        return $generation->fresh();
    }

    /**
     * Download completed audio to local storage.
     */
    public function downloadAudio(MetalXMusicGeneration $generation): ?string
    {
        if (empty($generation->audio_url)) {
            return null;
        }

        try {
            $response = Http::timeout(60)->get($generation->audio_url);

            if ($response->successful()) {
                $path = "metal-x/music/{$generation->id}.mp3";
                Storage::disk('local')->put($path, $response->body());

                $generation->update(['audio_path' => $path]);

                Log::info("[Suno] Audio downloaded: {$path}");

                return $path;
            }
        } catch (\Exception $e) {
            Log::error("[Suno] Download error: {$e->getMessage()}");
        }

        return null;
    }
}
