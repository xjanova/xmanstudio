<?php

namespace App\Services;

use App\Exceptions\AIServiceException;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AiChatService
 *
 * General-purpose AI chat service supporting multi-turn conversations
 * across all configured providers (OpenAI, Claude, Gemini, Ollama).
 */
class AiChatService
{
    protected string $provider;

    protected string $model;

    protected ?string $apiKey;

    protected float $temperature;

    protected int $maxTokens;

    protected InputSanitizerService $sanitizer;

    public function __construct(InputSanitizerService $sanitizer)
    {
        $this->sanitizer = $sanitizer;
        $this->loadSettings();
    }

    /**
     * Load AI settings from database.
     */
    protected function loadSettings(): void
    {
        $this->provider = Setting::get('ai_provider', 'gemini');
        $this->temperature = (float) Setting::get('ai_temperature', 0.7);
        $this->maxTokens = (int) Setting::get('ai_max_tokens', 1000);

        switch ($this->provider) {
            case 'openai':
                $this->apiKey = Setting::get('openai_api_key') ?: Setting::get('ai_openai_key');
                $this->model = Setting::get('openai_model', 'gpt-4o-mini');
                break;
            case 'claude':
                $this->apiKey = Setting::get('claude_api_key') ?: Setting::get('ai_claude_key');
                $this->model = Setting::get('claude_model', 'claude-3-haiku-20240307');
                break;
            case 'gemini':
                $this->apiKey = Setting::get('gemini_api_key') ?: Setting::get('ai_gemini_key');
                $this->model = Setting::get('gemini_model', 'gemini-2.0-flash');
                break;
            case 'ollama':
                $this->apiKey = null;
                $this->model = Setting::get('ollama_model', 'llama3.2');
                break;
            default:
                $this->apiKey = null;
                $this->model = '';
        }
    }

    /**
     * Send a multi-turn conversation and get a response.
     *
     * @param  array  $messages  [{role: 'user'|'assistant'|'system', content: '...'}]
     * @param  ?string  $systemPrompt  Override system prompt (optional)
     * @return array {success, message, provider, model}
     */
    public function chat(array $messages, ?string $systemPrompt = null): array
    {
        $systemPrompt = $this->buildSystemPrompt($systemPrompt);

        // Sanitize user messages
        $messages = array_map(function ($msg) {
            if ($msg['role'] === 'user') {
                $msg['content'] = $this->sanitizer->sanitizeForPrompt($msg['content'], 10000);
            }

            return $msg;
        }, $messages);

        try {
            $response = match ($this->provider) {
                'openai' => $this->chatOpenAi($messages, $systemPrompt),
                'claude' => $this->chatClaude($messages, $systemPrompt),
                'gemini' => $this->chatGemini($messages, $systemPrompt),
                'ollama' => $this->chatOllama($messages, $systemPrompt),
                default => throw AIServiceException::unsupportedProvider($this->provider),
            };

            return [
                'success' => true,
                'message' => $response,
                'provider' => $this->provider,
                'model' => $this->model,
            ];
        } catch (AIServiceException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error("AiChatService error [{$this->provider}]: " . $e->getMessage());
            throw AIServiceException::serviceUnavailable($this->provider);
        }
    }

    /**
     * Send a single message (convenience wrapper).
     */
    public function sendMessage(string $message, ?string $systemPrompt = null): array
    {
        return $this->chat([
            ['role' => 'user', 'content' => $message],
        ], $systemPrompt);
    }

    /**
     * Chat via OpenAI API.
     */
    protected function chatOpenAi(array $messages, ?string $systemPrompt): string
    {
        $apiMessages = [];

        if (! empty($systemPrompt)) {
            $apiMessages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        foreach ($messages as $msg) {
            if ($msg['role'] !== 'system') {
                $apiMessages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => $apiMessages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ]);

        if (! $response->successful()) {
            $error = $response->json('error.message', $response->body());
            throw new \Exception("OpenAI: {$error}");
        }

        return $response->json('choices.0.message.content', '');
    }

    /**
     * Chat via Claude API.
     */
    protected function chatClaude(array $messages, ?string $systemPrompt): string
    {
        // Claude uses separate 'system' param, not in messages array
        $apiMessages = [];
        foreach ($messages as $msg) {
            if ($msg['role'] !== 'system') {
                $apiMessages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }

        $payload = [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'messages' => $apiMessages,
        ];

        if (! empty($systemPrompt)) {
            $payload['system'] = $systemPrompt;
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'anthropic-version' => '2023-06-01',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', $payload);

        if (! $response->successful()) {
            $error = $response->json('error.message', $response->body());
            throw new \Exception("Claude: {$error}");
        }

        return $response->json('content.0.text', '');
    }

    /**
     * Chat via Gemini API.
     */
    protected function chatGemini(array $messages, ?string $systemPrompt): string
    {
        // Gemini uses 'user' and 'model' roles (not 'assistant')
        $contents = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                continue;
            }
            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $this->temperature,
                'maxOutputTokens' => $this->maxTokens,
            ],
        ];

        if (! empty($systemPrompt)) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemPrompt]],
            ];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(60)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
            $payload
        );

        if (! $response->successful()) {
            $error = $response->json('error.message', $response->body());
            throw new \Exception("Gemini: {$error}");
        }

        return $response->json('candidates.0.content.parts.0.text', '');
    }

    /**
     * Chat via Ollama API (local).
     */
    protected function chatOllama(array $messages, ?string $systemPrompt): string
    {
        $ollamaUrl = Setting::get('ollama_host', 'http://localhost:11434');

        // Ollama /api/chat uses same format as OpenAI
        $apiMessages = [];

        if (! empty($systemPrompt)) {
            $apiMessages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        foreach ($messages as $msg) {
            if ($msg['role'] !== 'system') {
                $apiMessages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }

        $response = Http::timeout(120)->post("{$ollamaUrl}/api/chat", [
            'model' => $this->model,
            'messages' => $apiMessages,
            'stream' => false,
            'options' => [
                'temperature' => $this->temperature,
            ],
        ]);

        if (! $response->successful()) {
            throw new \Exception('Ollama: ' . $response->body());
        }

        return $response->json('message.content', '');
    }

    /**
     * Build the effective system prompt from settings.
     */
    protected function buildSystemPrompt(?string $override = null): string
    {
        if (! empty($override)) {
            return $override;
        }

        $parts = [];

        // Base system prompt from settings
        $basePrompt = Setting::get('ai_system_prompt', '');
        if (! empty($basePrompt)) {
            $parts[] = $basePrompt;
        }

        // Bot personality
        $botName = Setting::get('ai_bot_name', 'AI Assistant');
        $style = Setting::get('ai_response_style', 'professional');
        $language = Setting::get('ai_response_language', 'th');
        $length = Setting::get('ai_response_length', 'medium');

        $styleMap = [
            'professional' => 'มืออาชีพ สุภาพ',
            'friendly' => 'เป็นมิตร อบอุ่น',
            'casual' => 'ผ่อนคลาย เป็นกันเอง',
            'formal' => 'ทางการ สุภาพ',
        ];

        $langMap = [
            'th' => 'ตอบเป็นภาษาไทยเสมอ',
            'en' => 'Always reply in English',
            'auto' => 'ตอบตามภาษาที่ผู้ใช้ถาม',
        ];

        $lengthMap = [
            'short' => 'ตอบสั้น กระชับ ไม่เกิน 2-3 ประโยค',
            'medium' => 'ตอบปานกลาง ครบถ้วนแต่กระชับ',
            'long' => 'ตอบละเอียด ครบถ้วน อธิบายเพิ่มเติม',
        ];

        $parts[] = "คุณชื่อ {$botName}";
        $parts[] = 'สไตล์การตอบ: ' . ($styleMap[$style] ?? 'มืออาชีพ');
        $parts[] = $langMap[$language] ?? 'ตอบเป็นภาษาไทยเสมอ';
        $parts[] = $lengthMap[$length] ?? 'ตอบปานกลาง';

        // Custom knowledge base
        $knowledge = Setting::get('ai_custom_knowledge', '');
        if (! empty($knowledge)) {
            $parts[] = "ข้อมูลที่ต้องรู้:\n{$knowledge}";
        }

        // Topic restrictions
        $allowed = Setting::get('ai_allowed_topics', '');
        if (! empty($allowed)) {
            $parts[] = "หัวข้อที่อนุญาตให้ตอบ: {$allowed}";
        }

        $forbidden = Setting::get('ai_forbidden_topics', '');
        if (! empty($forbidden)) {
            $parts[] = "หัวข้อที่ห้ามตอบ: {$forbidden}";
        }

        $fallback = Setting::get('ai_fallback_message', '');
        if (! empty($fallback)) {
            $parts[] = "ถ้าตอบไม่ได้ ให้ตอบว่า: {$fallback}";
        }

        // Inject contact info from settings
        $contactParts = [];
        $phone = Setting::get('contact_phone', '');
        $phoneName = Setting::get('contact_phone_name', '');
        if ($phone) {
            $contactParts[] = 'โทรศัพท์: ' . $phone . ($phoneName ? " ({$phoneName})" : '');
        }
        $email = Setting::get('contact_email', '');
        if ($email) {
            $contactParts[] = 'อีเมล: ' . $email;
        }
        $fbName = Setting::get('contact_facebook_name', '');
        $fbUrl = Setting::get('contact_facebook_url', '');
        if ($fbName) {
            $contactParts[] = 'Facebook: ' . $fbName . ($fbUrl ? " ({$fbUrl})" : '');
        }
        $lineId = Setting::get('contact_line_id', '');
        $lineUrl = Setting::get('contact_line_url', '');
        if ($lineId) {
            $contactParts[] = 'Line OA: ' . $lineId . ($lineUrl ? " ({$lineUrl})" : '');
        }
        $ytName = Setting::get('contact_youtube_name', '');
        $ytUrl = Setting::get('contact_youtube_url', '');
        if ($ytName) {
            $contactParts[] = 'YouTube: ' . $ytName . ($ytUrl ? " ({$ytUrl})" : '');
        }
        $address = Setting::get('contact_address', '');
        if ($address) {
            $contactParts[] = 'ที่อยู่: ' . $address;
        }
        if (! empty($contactParts)) {
            $parts[] = "ข้อมูลติดต่อของเรา (ใช้ข้อมูลนี้เมื่อลูกค้าถามเรื่องการติดต่อ):\n" . implode("\n", $contactParts);
        }

        return implode("\n\n", array_filter($parts));
    }

    /**
     * Check if AI is properly configured.
     */
    public function isConfigured(): bool
    {
        switch ($this->provider) {
            case 'openai':
            case 'claude':
            case 'gemini':
                return ! empty($this->apiKey) && ! empty($this->model);
            case 'ollama':
                return ! empty($this->model);
            default:
                return false;
        }
    }

    /**
     * Get current provider info.
     */
    public function getProviderInfo(): array
    {
        $providerNames = [
            'openai' => 'OpenAI (GPT)',
            'claude' => 'Anthropic (Claude)',
            'gemini' => 'Google Gemini',
            'ollama' => 'Ollama (Local)',
        ];

        return [
            'provider' => $this->provider,
            'provider_name' => $providerNames[$this->provider] ?? $this->provider,
            'model' => $this->model,
            'configured' => $this->isConfigured(),
        ];
    }
}
