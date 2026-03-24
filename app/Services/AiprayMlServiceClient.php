<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiprayMlServiceClient
{
    private string $baseUrl;

    private string $secret;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.aipray_ml.url', 'http://localhost:8100'), '/');
        $this->secret = config('services.aipray_ml.secret', '');
        $this->timeout = (int) config('services.aipray_ml.timeout', 300);
    }

    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");

            return $response->ok() && ($response->json('status') === 'ok');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function health(): ?array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");

            return $response->ok() ? $response->json() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function startTraining(array $params): array
    {
        return $this->post('/train/start', $params, $this->timeout);
    }

    public function pauseTraining(int $jobId): array
    {
        return $this->post("/train/{$jobId}/pause");
    }

    public function resumeTraining(int $jobId): array
    {
        return $this->post("/train/{$jobId}/resume");
    }

    public function cancelTraining(int $jobId): array
    {
        return $this->post("/train/{$jobId}/cancel");
    }

    public function transcribeFile(string $filePath, string $modelId = 'default'): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->secret)
                ->attach('audio', file_get_contents($filePath), basename($filePath))
                ->post("{$this->baseUrl}/transcribe/file", [
                    'model_id' => $modelId,
                    'language' => 'th',
                ]);

            return $response->ok()
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => 'ML service returned ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('ML transcribe failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function transcribeUpload($file, string $modelId = 'default'): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->secret)
                ->attach('audio', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post("{$this->baseUrl}/transcribe/file", [
                    'model_id' => $modelId,
                    'language' => 'th',
                ]);

            return $response->ok()
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => 'ML service returned ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('ML transcribe upload failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function evaluate($audioFile, string $referenceText, string $modelId = 'default'): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->secret)
                ->attach('audio', file_get_contents($audioFile->getRealPath()), $audioFile->getClientOriginalName())
                ->post("{$this->baseUrl}/evaluate", [
                    'reference_text' => $referenceText,
                    'model_id' => $modelId,
                ]);

            return $response->ok()
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => 'ML service returned ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('ML evaluate failed: ' . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function loadModel(string $modelPath, string $modelId = 'default'): array
    {
        return $this->post('/models/load', [
            'model_path' => $modelPath,
            'model_id' => $modelId,
        ]);
    }

    public function getModels(): array
    {
        return $this->get('/models');
    }

    public function exportOnnx(string $modelPath, ?string $outputPath = null): array
    {
        $params = ['model_path' => $modelPath];
        if ($outputPath) {
            $params['output_path'] = $outputPath;
        }

        return $this->post('/models/export-onnx', $params, $this->timeout);
    }

    private function get(string $path): array
    {
        try {
            $response = Http::timeout(30)
                ->withToken($this->secret)
                ->get("{$this->baseUrl}{$path}");

            return $response->ok()
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => 'Status ' . $response->status()];
        } catch (\Exception $e) {
            Log::error("ML GET {$path} failed: " . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function post(string $path, array $data = [], ?int $timeout = null): array
    {
        try {
            $response = Http::timeout($timeout ?? 30)
                ->withToken($this->secret)
                ->post("{$this->baseUrl}{$path}", $data);

            return $response->ok()
                ? ['success' => true, 'data' => $response->json()]
                : ['success' => false, 'error' => 'Status ' . $response->status()];
        } catch (\Exception $e) {
            Log::error("ML POST {$path} failed: " . $e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
