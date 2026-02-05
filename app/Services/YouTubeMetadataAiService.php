<?php

namespace App\Services;

use App\Models\MetalXVideo;
use App\Models\Setting;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeMetadataAiService
{
    protected $provider;

    protected $model;

    protected $apiKey;

    protected $temperature;

    protected $maxTokens;

    protected $sanitizer;

    public function __construct(InputSanitizerService $sanitizer)
    {
        $this->sanitizer = $sanitizer;
        $this->loadAiSettings();
    }

    /**
     * Load AI settings from database
     */
    protected function loadAiSettings(): void
    {
        $this->provider = Setting::get('ai_provider', 'openai');
        $this->temperature = (float) Setting::get('ai_temperature', 0.7);
        $this->maxTokens = (int) Setting::get('ai_max_tokens', 2000);

        switch ($this->provider) {
            case 'openai':
                $this->apiKey = Setting::get('ai_openai_key');
                $this->model = Setting::get('ai_openai_model', 'gpt-4o-mini');
                break;
            case 'claude':
                $this->apiKey = Setting::get('ai_claude_key');
                $this->model = Setting::get('ai_claude_model', 'claude-3-haiku-20240307');
                break;
            case 'ollama':
                $this->model = Setting::get('ai_ollama_model', 'llama2');
                break;
            default:
                throw new Exception("Unsupported AI provider: {$this->provider}");
        }
    }

    /**
     * Generate metadata for a video
     */
    public function generateMetadata(MetalXVideo $video): array
    {
        $prompt = $this->buildMetadataPrompt($video);

        try {
            $response = $this->callAi($prompt);
            $metadata = $this->parseMetadataResponse($response);

            return [
                'success' => true,
                'metadata' => $metadata,
                'confidence' => $this->calculateConfidence($metadata),
            ];
        } catch (Exception $e) {
            Log::error("AI metadata generation failed for video {$video->id}: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate metadata for multiple videos
     * Fixed: Use findMany() to avoid N+1 query problem
     */
    public function generateBatchMetadata(array $videoIds): array
    {
        $results = [];

        // Load all videos in a single query to avoid N+1 problem
        $videos = MetalXVideo::findMany($videoIds)->keyBy('id');

        foreach ($videoIds as $videoId) {
            $video = $videos->get($videoId);

            if (! $video) {
                $results[$videoId] = [
                    'success' => false,
                    'error' => 'Video not found',
                ];

                continue;
            }

            $results[$videoId] = $this->generateMetadata($video);

            if ($results[$videoId]['success']) {
                $this->saveMetadata($video, $results[$videoId]['metadata'], $results[$videoId]['confidence']);
            }
        }

        return $results;
    }

    /**
     * Build prompt for metadata generation
     * Sanitized to prevent prompt injection attacks
     */
    protected function buildMetadataPrompt(MetalXVideo $video): string
    {
        // Sanitize all user-provided content to prevent prompt injection
        $channelName = $this->sanitizer->sanitizeForPrompt(Setting::get('metalx_channel_name', 'Metal-X'), 100);
        $titleEn = $this->sanitizer->sanitizeForPrompt($video->title_en ?? '', 200);
        $descriptionEn = $this->sanitizer->sanitizeForPrompt($video->description_en ?? '', 2000);
        $tags = $this->sanitizer->sanitizeForPrompt($video->tags ?? '', 500);
        $channelTitle = $this->sanitizer->sanitizeForPrompt($video->channel_title ?? '', 100);
        $categoryId = (int) $video->category_id; // Ensure integer
        $duration = (int) $video->duration; // Ensure integer

        return <<<PROMPT
You are a professional YouTube content specialist for {$channelName}, a metal fabrication and engineering company in Thailand.

Your task is to generate Thai metadata for the following YouTube video:

**English Title:** {$titleEn}
**English Description:** {$descriptionEn}
**Existing Tags:** {$tags}
**Category:** {$categoryId}
**Duration:** {$duration} seconds
**Channel:** {$channelTitle}

Please generate the following in JSON format:

1. **title_th**: A natural, engaging Thai translation of the title (keep it concise, under 100 characters)
2. **description_th**: A comprehensive Thai translation of the description, maintaining professional tone
3. **tags**: An array of 10-15 relevant Thai and English tags for SEO (mix of both languages)
4. **category**: Suggested category (one of: metal-fabrication, welding, cnc-machining, engineering, tutorial, showcase, company-update)

Guidelines:
- Keep the Metal-X brand voice: professional, innovative, trustworthy
- Use proper Thai technical terminology for metalworking and engineering
- Tags should include: Thai keywords, English keywords, trending search terms
- Make content SEO-friendly for both Thai and international audiences
- Keep translations natural and engaging, not literal
- Include relevant hashtags in tags

Respond ONLY with valid JSON in this exact format:
{
  "title_th": "Thai title here",
  "description_th": "Thai description here",
  "tags": ["tag1", "tag2", "tag3"],
  "category": "category-name"
}
PROMPT;
    }

    /**
     * Call AI API based on provider
     */
    protected function callAi(string $prompt): string
    {
        switch ($this->provider) {
            case 'openai':
                return $this->callOpenAi($prompt);
            case 'claude':
                return $this->callClaude($prompt);
            case 'ollama':
                return $this->callOllama($prompt);
            default:
                throw new Exception("Unsupported AI provider: {$this->provider}");
        }
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAi(string $prompt): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional YouTube content specialist. Always respond with valid JSON only.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ]);

        if (! $response->successful()) {
            throw new Exception('OpenAI API error: ' . $response->body());
        }

        return $response->json('choices.0.message.content');
    }

    /**
     * Call Claude API
     */
    protected function callClaude(string $prompt): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new Exception('Claude API error: ' . $response->body());
        }

        return $response->json('content.0.text');
    }

    /**
     * Call Ollama API
     */
    protected function callOllama(string $prompt): string
    {
        $ollamaUrl = Setting::get('ai_ollama_url', 'http://localhost:11434');

        $response = Http::timeout(120)->post("{$ollamaUrl}/api/generate", [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => $this->temperature,
            ],
        ]);

        if (! $response->successful()) {
            throw new Exception('Ollama API error: ' . $response->body());
        }

        return $response->json('response');
    }

    /**
     * Parse AI response and extract metadata
     */
    protected function parseMetadataResponse(string $response): array
    {
        // Remove markdown code blocks if present
        $response = preg_replace('/```json\s*|\s*```/', '', $response);
        $response = trim($response);

        $metadata = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from AI: ' . json_last_error_msg());
        }

        // Validate required fields
        $required = ['title_th', 'description_th', 'tags', 'category'];
        foreach ($required as $field) {
            if (! isset($metadata[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Validate tags is array
        if (! is_array($metadata['tags'])) {
            throw new Exception('Tags must be an array');
        }

        return $metadata;
    }

    /**
     * Calculate confidence score based on metadata quality
     */
    protected function calculateConfidence(array $metadata): float
    {
        $score = 0;

        // Title quality (30 points)
        if (! empty($metadata['title_th']) && mb_strlen($metadata['title_th']) > 10) {
            $score += 30;
        } elseif (! empty($metadata['title_th'])) {
            $score += 15;
        }

        // Description quality (30 points)
        if (! empty($metadata['description_th']) && mb_strlen($metadata['description_th']) > 50) {
            $score += 30;
        } elseif (! empty($metadata['description_th'])) {
            $score += 15;
        }

        // Tags quality (20 points)
        $tagCount = count($metadata['tags']);
        if ($tagCount >= 10) {
            $score += 20;
        } elseif ($tagCount >= 5) {
            $score += 10;
        } elseif ($tagCount > 0) {
            $score += 5;
        }

        // Category (20 points)
        if (! empty($metadata['category'])) {
            $score += 20;
        }

        return round($score, 2);
    }

    /**
     * Save generated metadata to video
     */
    public function saveMetadata(MetalXVideo $video, array $metadata, float $confidence): void
    {
        $video->update([
            'ai_title_th' => $metadata['title_th'],
            'ai_description_th' => $metadata['description_th'],
            'ai_tags' => $metadata['tags'],
            'ai_category' => $metadata['category'],
            'ai_confidence_score' => $confidence,
            'ai_generated' => true,
            'ai_generated_at' => now(),
        ]);
    }

    /**
     * Approve AI-generated metadata and apply to main fields
     */
    public function approveMetadata(MetalXVideo $video, int $userId): void
    {
        if (! $video->ai_generated) {
            throw new Exception('No AI metadata to approve');
        }

        $video->update([
            'title_th' => $video->ai_title_th,
            'description_th' => $video->ai_description_th,
            'tags' => implode(',', $video->ai_tags ?? []),
            'ai_approved' => true,
            'ai_approved_at' => now(),
            'ai_approved_by' => $userId,
        ]);
    }

    /**
     * Reject AI-generated metadata
     */
    public function rejectMetadata(MetalXVideo $video): void
    {
        $video->update([
            'ai_title_th' => null,
            'ai_description_th' => null,
            'ai_tags' => null,
            'ai_category' => null,
            'ai_confidence_score' => null,
            'ai_generated' => false,
            'ai_generated_at' => null,
        ]);
    }

    /**
     * Check if AI is properly configured
     */
    public function isConfigured(): bool
    {
        if (! Setting::get('ai_content_generation', false)) {
            return false;
        }

        switch ($this->provider) {
            case 'openai':
                return ! empty($this->apiKey) && ! empty($this->model);
            case 'claude':
                return ! empty($this->apiKey) && ! empty($this->model);
            case 'ollama':
                return ! empty($this->model);
            default:
                return false;
        }
    }
}
