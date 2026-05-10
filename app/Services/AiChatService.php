<?php

namespace App\Services;

use App\Exceptions\AIServiceException;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
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
            case 'groq':
                $this->apiKey = Setting::get('groq_api_key');
                $this->model = Setting::get('groq_model', 'llama-3.3-70b-versatile');
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
                'groq' => $this->chatGroq($messages, $systemPrompt),
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
     * Chat via Groq API (OpenAI-compatible).
     */
    protected function chatGroq(array $messages, ?string $systemPrompt): string
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
        ])->timeout(60)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => $this->model,
            'messages' => $apiMessages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ]);

        if (! $response->successful()) {
            $error = $response->json('error.message', $response->body());
            throw new \Exception("Groq: {$error}");
        }

        return $response->json('choices.0.message.content', '');
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
            case 'groq':
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
            'groq' => 'Groq',
            'ollama' => 'Ollama (Local)',
        ];

        return [
            'provider' => $this->provider,
            'provider_name' => $providerNames[$this->provider] ?? $this->provider,
            'model' => $this->model,
            'configured' => $this->isConfigured(),
        ];
    }

    /**
     * Live-check that the configured provider/key/model can actually serve a request.
     * Returns ['ok' => bool, 'error' => ?string, 'tested_at' => iso8601, 'cached' => bool].
     *
     * Result is cached for 5 minutes per (provider, key, model) tuple so the AI
     * Playground page does not pay a 1-3s API round trip on every render. Callers
     * that need a fresh check (e.g. right after the admin saves a new key) can
     * call self::clearStatusCache() or pass $forceFresh = true.
     */
    public function verifyConnection(bool $forceFresh = false): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'error' => 'ยังไม่ได้ตั้งค่า API Key หรือ Model',
                'tested_at' => now()->toIso8601String(),
                'cached' => false,
            ];
        }

        $cacheKey = self::statusCacheKey($this->provider, $this->apiKey, $this->model);

        if (! $forceFresh) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                $cached['cached'] = true;

                return $cached;
            }
        }

        $result = $this->probeProvider();
        $result['tested_at'] = now()->toIso8601String();
        $result['cached'] = false;

        Cache::put($cacheKey, $result, now()->addMinutes(5));

        return $result;
    }

    /**
     * Invalidate every cached connection-status entry. Call this from the
     * settings update controllers so the next page render reflects reality
     * instead of a stale "พร้อมใช้งาน" badge from before the new key was saved.
     */
    public static function clearStatusCache(): void
    {
        // We cannot enumerate cache entries by prefix (driver-dependent), so we
        // bump a monotonically-increasing version counter that is part of every
        // key. Old (cached) entries become orphans and expire on their own TTL.
        // Cache::increment is atomic on file/redis/memcached drivers.
        if (Cache::get('ai_chat_status_version') === null) {
            Cache::forever('ai_chat_status_version', 2);

            return;
        }
        Cache::increment('ai_chat_status_version');
    }

    protected static function statusCacheKey(string $provider, ?string $apiKey, string $model): string
    {
        // Bumping ai_chat_status_version (via clearStatusCache()) atomically
        // invalidates the whole namespace by changing the prefix every key uses.
        $version = Cache::get('ai_chat_status_version');
        if ($version === null) {
            Cache::forever('ai_chat_status_version', 1);
            $version = 1;
        }
        $fingerprint = md5(($apiKey ?? '') . '|' . $model);

        return "ai_chat_status:v{$version}:{$provider}:{$fingerprint}";
    }

    /**
     * Hit a cheap, no-quota endpoint for the configured provider.
     * Returns ['ok' => bool, 'error' => ?string].
     */
    protected function probeProvider(): array
    {
        try {
            return match ($this->provider) {
                'openai' => $this->probeBearerEndpoint('https://api.openai.com/v1/models', 'OpenAI'),
                'claude' => $this->probeClaude(),
                'gemini' => $this->probeGemini(),
                'groq' => $this->probeBearerEndpoint('https://api.groq.com/openai/v1/models', 'Groq'),
                'ollama' => $this->probeOllama(),
                default => ['ok' => false, 'error' => 'Provider ไม่รองรับ: ' . $this->provider],
            };
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'เชื่อมต่อไม่สำเร็จ: ' . $e->getMessage()];
        }
    }

    protected function probeBearerEndpoint(string $url, string $providerLabel): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->timeout(10)->get($url);

        if ($response->successful()) {
            return $this->validateModelAvailable($response->json('data', []), $providerLabel);
        }

        return ['ok' => false, 'error' => $providerLabel . ': ' . $this->describeHttpError($response)];
    }

    protected function probeClaude(): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(10)->get('https://api.anthropic.com/v1/models');

        if ($response->successful()) {
            return $this->validateModelAvailable($response->json('data', []), 'Claude');
        }

        return ['ok' => false, 'error' => 'Claude: ' . $this->describeHttpError($response)];
    }

    protected function probeGemini(): array
    {
        $response = Http::timeout(10)
            ->get('https://generativelanguage.googleapis.com/v1beta/models?key=' . urlencode($this->apiKey));

        if ($response->successful()) {
            $models = $response->json('models', []);
            $names = array_map(fn ($m) => str_replace('models/', '', $m['name'] ?? ''), $models);

            if (! empty($this->model) && ! in_array($this->model, $names, true)) {
                return [
                    'ok' => false,
                    'error' => "Gemini: Model '{$this->model}' ไม่อยู่ในรายการที่ใช้ได้สำหรับ key นี้ — เลือก model อื่นในหน้า AI Settings",
                ];
            }

            return ['ok' => true, 'error' => null];
        }

        return ['ok' => false, 'error' => 'Gemini: ' . $this->describeHttpError($response)];
    }

    protected function probeOllama(): array
    {
        $host = Setting::get('ollama_host', 'http://localhost:11434');
        $response = Http::timeout(5)->get(rtrim($host, '/') . '/api/tags');

        if (! $response->successful()) {
            return ['ok' => false, 'error' => 'Ollama: ไม่ตอบสนองที่ ' . $host];
        }

        $names = array_map(fn ($m) => $m['name'] ?? '', $response->json('models', []));
        if (! empty($this->model) && ! in_array($this->model, $names, true)) {
            return ['ok' => false, 'error' => "Ollama: ยังไม่มีโมเดล '{$this->model}' บน host นี้ (รัน `ollama pull {$this->model}`)"];
        }

        return ['ok' => true, 'error' => null];
    }

    protected function validateModelAvailable(array $models, string $providerLabel): array
    {
        if (empty($this->model)) {
            return ['ok' => true, 'error' => null];
        }

        $names = array_map(fn ($m) => $m['id'] ?? ($m['name'] ?? ''), $models);
        if (! in_array($this->model, $names, true)) {
            return [
                'ok' => false,
                'error' => "{$providerLabel}: Model '{$this->model}' ไม่อยู่ในรายการที่ใช้ได้สำหรับ key นี้",
            ];
        }

        return ['ok' => true, 'error' => null];
    }

    protected function describeHttpError($response): string
    {
        $status = $response->status();
        $msg = $response->json('error.message') ?? $response->json('message') ?? null;

        if ($status === 401 || $status === 403) {
            return "API key ไม่ผ่านการตรวจสอบ (HTTP {$status})" . ($msg ? " — {$msg}" : '');
        }
        if ($status === 429) {
            return 'เกิน quota / rate limit (HTTP 429)' . ($msg ? " — {$msg}" : '');
        }
        if ($status >= 500) {
            return "ฝั่งผู้ให้บริการขัดข้อง (HTTP {$status})";
        }

        return "HTTP {$status}" . ($msg ? " — {$msg}" : '');
    }
}
