<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiSettingsController extends Controller
{
    /**
     * Display AI settings page.
     */
    public function index()
    {
        $settings = [
            // OpenAI Settings
            'openai_api_key' => Setting::getValue('openai_api_key', ''),
            'openai_model' => Setting::getValue('openai_model', 'gpt-4o-mini'),
            'openai_enabled' => Setting::getValue('openai_enabled', false),

            // Claude Settings
            'claude_api_key' => Setting::getValue('claude_api_key', ''),
            'claude_model' => Setting::getValue('claude_model', 'claude-3-haiku-20240307'),
            'claude_enabled' => Setting::getValue('claude_enabled', false),

            // Gemini Settings
            'gemini_api_key' => Setting::getValue('gemini_api_key', ''),
            'gemini_model' => Setting::getValue('gemini_model', 'gemini-2.0-flash'),
            'gemini_enabled' => Setting::getValue('gemini_enabled', false),

            // Ollama Settings (Local AI)
            'ollama_enabled' => Setting::getValue('ollama_enabled', false),
            'ollama_host' => Setting::getValue('ollama_host', 'http://localhost:11434'),
            'ollama_model' => Setting::getValue('ollama_model', 'llama3.2'),
            'ollama_keep_alive' => Setting::getValue('ollama_keep_alive', '5m'),

            // General AI Settings
            'ai_provider' => Setting::getValue('ai_provider', 'openai'),
            'ai_max_tokens' => Setting::getValue('ai_max_tokens', 1000),
            'ai_temperature' => Setting::getValue('ai_temperature', 0.7),
            'ai_top_p' => Setting::getValue('ai_top_p', 1.0),
            'ai_frequency_penalty' => Setting::getValue('ai_frequency_penalty', 0),
            'ai_presence_penalty' => Setting::getValue('ai_presence_penalty', 0),

            // AI Bot Behavior Settings
            'ai_bot_name' => Setting::getValue('ai_bot_name', 'AI Assistant'),
            'ai_system_prompt' => Setting::getValue('ai_system_prompt', ''),
            'ai_response_language' => Setting::getValue('ai_response_language', 'th'),
            'ai_response_style' => Setting::getValue('ai_response_style', 'professional'),
            'ai_response_length' => Setting::getValue('ai_response_length', 'medium'),

            // Knowledge & Context Settings
            'ai_use_product_data' => Setting::getValue('ai_use_product_data', true),
            'ai_use_service_data' => Setting::getValue('ai_use_service_data', true),
            'ai_use_faq_data' => Setting::getValue('ai_use_faq_data', true),
            'ai_use_company_info' => Setting::getValue('ai_use_company_info', true),
            'ai_use_order_history' => Setting::getValue('ai_use_order_history', false),
            'ai_custom_knowledge' => Setting::getValue('ai_custom_knowledge', ''),

            // Response Restrictions
            'ai_allowed_topics' => Setting::getValue('ai_allowed_topics', ''),
            'ai_forbidden_topics' => Setting::getValue('ai_forbidden_topics', ''),
            'ai_fallback_message' => Setting::getValue('ai_fallback_message', 'ขออภัย ฉันไม่สามารถตอบคำถามนี้ได้ กรุณาติดต่อทีมงานโดยตรง'),
            'ai_require_human_handoff' => Setting::getValue('ai_require_human_handoff', true),
            'ai_handoff_keywords' => Setting::getValue('ai_handoff_keywords', 'ติดต่อพนักงาน,คุยกับคน,ต้องการความช่วยเหลือ'),

            // Feature Toggles
            'ai_chat_enabled' => Setting::getValue('ai_chat_enabled', false),
            'ai_content_generation' => Setting::getValue('ai_content_generation', false),
            'ai_code_assistant' => Setting::getValue('ai_code_assistant', false),
            'ai_auto_reply_line' => Setting::getValue('ai_auto_reply_line', false),
            'ai_auto_translate' => Setting::getValue('ai_auto_translate', false),
            'ai_sentiment_analysis' => Setting::getValue('ai_sentiment_analysis', false),
        ];

        return view('admin.ai-settings.index', compact('settings'));
    }

    /**
     * Update AI settings.
     */
    public function update(Request $request)
    {
        $request->validate([
            'openai_api_key' => 'nullable|string|max:255',
            'openai_model' => 'required|string|max:100',
            'claude_api_key' => 'nullable|string|max:255',
            'claude_model' => 'required|string|max:100',
            'gemini_api_key' => 'nullable|string|max:255',
            'gemini_model' => 'required|string|max:100',
            'ollama_host' => 'nullable|url|max:255',
            'ollama_model' => 'nullable|string|max:100',
            'ai_provider' => 'required|in:openai,claude,gemini,ollama',
            'ai_max_tokens' => 'required|integer|min:100|max:32000',
            'ai_temperature' => 'required|numeric|min:0|max:2',
            'ai_top_p' => 'nullable|numeric|min:0|max:1',
            'ai_frequency_penalty' => 'nullable|numeric|min:-2|max:2',
            'ai_presence_penalty' => 'nullable|numeric|min:-2|max:2',
            'ai_bot_name' => 'nullable|string|max:100',
            'ai_system_prompt' => 'nullable|string|max:10000',
            'ai_response_language' => 'required|in:th,en,auto',
            'ai_response_style' => 'required|in:professional,friendly,casual,formal',
            'ai_response_length' => 'required|in:short,medium,long',
            'ai_custom_knowledge' => 'nullable|string|max:50000',
            'ai_allowed_topics' => 'nullable|string|max:2000',
            'ai_forbidden_topics' => 'nullable|string|max:2000',
            'ai_fallback_message' => 'nullable|string|max:500',
            'ai_handoff_keywords' => 'nullable|string|max:1000',
        ]);

        // OpenAI Settings
        if ($request->filled('openai_api_key')) {
            Setting::setValue('openai_api_key', $request->openai_api_key, 'string', 'ai');
        }
        Setting::setValue('openai_model', $request->openai_model, 'string', 'ai');
        Setting::setValue('openai_enabled', $request->boolean('openai_enabled'), 'boolean', 'ai');

        // Claude Settings
        if ($request->filled('claude_api_key')) {
            Setting::setValue('claude_api_key', $request->claude_api_key, 'string', 'ai');
        }
        Setting::setValue('claude_model', $request->claude_model, 'string', 'ai');
        Setting::setValue('claude_enabled', $request->boolean('claude_enabled'), 'boolean', 'ai');

        // Gemini Settings
        if ($request->filled('gemini_api_key')) {
            Setting::setValue('gemini_api_key', $request->gemini_api_key, 'string', 'ai');
        }
        Setting::setValue('gemini_model', $request->gemini_model, 'string', 'ai');
        Setting::setValue('gemini_enabled', $request->boolean('gemini_enabled'), 'boolean', 'ai');

        // Ollama Settings
        Setting::setValue('ollama_enabled', $request->boolean('ollama_enabled'), 'boolean', 'ai');
        Setting::setValue('ollama_host', $request->ollama_host ?? 'http://localhost:11434', 'string', 'ai');
        Setting::setValue('ollama_model', $request->ollama_model ?? 'llama3.2', 'string', 'ai');
        Setting::setValue('ollama_keep_alive', $request->ollama_keep_alive ?? '5m', 'string', 'ai');

        // General AI Settings
        Setting::setValue('ai_provider', $request->ai_provider, 'string', 'ai');
        Setting::setValue('ai_max_tokens', $request->ai_max_tokens, 'integer', 'ai');
        Setting::setValue('ai_temperature', $request->ai_temperature, 'string', 'ai');
        Setting::setValue('ai_top_p', $request->ai_top_p ?? 1.0, 'string', 'ai');
        Setting::setValue('ai_frequency_penalty', $request->ai_frequency_penalty ?? 0, 'string', 'ai');
        Setting::setValue('ai_presence_penalty', $request->ai_presence_penalty ?? 0, 'string', 'ai');

        // AI Bot Behavior Settings
        Setting::setValue('ai_bot_name', $request->ai_bot_name ?? 'AI Assistant', 'string', 'ai');
        Setting::setValue('ai_system_prompt', $request->ai_system_prompt ?? '', 'text', 'ai');
        Setting::setValue('ai_response_language', $request->ai_response_language, 'string', 'ai');
        Setting::setValue('ai_response_style', $request->ai_response_style, 'string', 'ai');
        Setting::setValue('ai_response_length', $request->ai_response_length, 'string', 'ai');

        // Knowledge & Context Settings
        Setting::setValue('ai_use_product_data', $request->boolean('ai_use_product_data'), 'boolean', 'ai');
        Setting::setValue('ai_use_service_data', $request->boolean('ai_use_service_data'), 'boolean', 'ai');
        Setting::setValue('ai_use_faq_data', $request->boolean('ai_use_faq_data'), 'boolean', 'ai');
        Setting::setValue('ai_use_company_info', $request->boolean('ai_use_company_info'), 'boolean', 'ai');
        Setting::setValue('ai_use_order_history', $request->boolean('ai_use_order_history'), 'boolean', 'ai');
        Setting::setValue('ai_custom_knowledge', $request->ai_custom_knowledge ?? '', 'text', 'ai');

        // Response Restrictions
        Setting::setValue('ai_allowed_topics', $request->ai_allowed_topics ?? '', 'text', 'ai');
        Setting::setValue('ai_forbidden_topics', $request->ai_forbidden_topics ?? '', 'text', 'ai');
        Setting::setValue('ai_fallback_message', $request->ai_fallback_message ?? '', 'string', 'ai');
        Setting::setValue('ai_require_human_handoff', $request->boolean('ai_require_human_handoff'), 'boolean', 'ai');
        Setting::setValue('ai_handoff_keywords', $request->ai_handoff_keywords ?? '', 'string', 'ai');

        // Feature Toggles
        Setting::setValue('ai_chat_enabled', $request->boolean('ai_chat_enabled'), 'boolean', 'ai');
        Setting::setValue('ai_content_generation', $request->boolean('ai_content_generation'), 'boolean', 'ai');
        Setting::setValue('ai_code_assistant', $request->boolean('ai_code_assistant'), 'boolean', 'ai');
        Setting::setValue('ai_auto_reply_line', $request->boolean('ai_auto_reply_line'), 'boolean', 'ai');
        Setting::setValue('ai_auto_translate', $request->boolean('ai_auto_translate'), 'boolean', 'ai');
        Setting::setValue('ai_sentiment_analysis', $request->boolean('ai_sentiment_analysis'), 'boolean', 'ai');

        return redirect()->route('admin.ai-settings.index')
            ->with('success', 'บันทึกการตั้งค่า AI เรียบร้อยแล้ว');
    }

    /**
     * Test AI connection.
     */
    public function test(Request $request)
    {
        $provider = $request->input('provider', Setting::getValue('ai_provider', 'openai'));

        try {
            if ($provider === 'openai') {
                return $this->testOpenAI();
            } elseif ($provider === 'claude') {
                return $this->testClaude();
            } elseif ($provider === 'gemini') {
                return $this->testGemini();
            } elseif ($provider === 'ollama') {
                return $this->testOllama();
            }

            return response()->json(['success' => false, 'message' => 'Provider ไม่ถูกต้อง']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        }
    }

    /**
     * Test OpenAI connection.
     */
    private function testOpenAI()
    {
        $apiKey = Setting::getValue('openai_api_key');
        if (empty($apiKey)) {
            return response()->json(['success' => false, 'message' => 'ยังไม่ได้ตั้งค่า OpenAI API Key']);
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->get('https://api.openai.com/v1/models');

        if ($response->successful()) {
            return response()->json(['success' => true, 'message' => 'เชื่อมต่อ OpenAI สำเร็จ']);
        }

        return response()->json(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อ OpenAI ได้: ' . $response->status()]);
    }

    /**
     * Test Claude connection.
     */
    private function testClaude()
    {
        $apiKey = Setting::getValue('claude_api_key');
        if (empty($apiKey)) {
            return response()->json(['success' => false, 'message' => 'ยังไม่ได้ตั้งค่า Claude API Key']);
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => Setting::getValue('claude_model', 'claude-3-haiku-20240307'),
            'max_tokens' => 10,
            'messages' => [['role' => 'user', 'content' => 'Hi']],
        ]);

        if ($response->successful()) {
            return response()->json(['success' => true, 'message' => 'เชื่อมต่อ Claude สำเร็จ']);
        }

        return response()->json(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อ Claude ได้: ' . $response->status()]);
    }

    /**
     * Test Gemini connection.
     */
    private function testGemini()
    {
        $apiKey = Setting::getValue('gemini_api_key');
        if (empty($apiKey)) {
            return response()->json(['success' => false, 'message' => 'ยังไม่ได้ตั้งค่า Gemini API Key']);
        }

        $model = Setting::getValue('gemini_model', 'gemini-2.0-flash');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [
                ['parts' => [['text' => 'Hello, respond with "OK" only.']]],
            ],
            'generationConfig' => [
                'maxOutputTokens' => 10,
            ],
        ]);

        if ($response->successful()) {
            return response()->json(['success' => true, 'message' => 'เชื่อมต่อ Google Gemini สำเร็จ']);
        }

        $error = $response->json('error.message', $response->status());

        return response()->json(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อ Gemini ได้: ' . $error]);
    }

    /**
     * Test Ollama connection.
     */
    private function testOllama()
    {
        $host = Setting::getValue('ollama_host', 'http://localhost:11434');

        try {
            $response = Http::timeout(10)->get($host . '/api/tags');

            if ($response->successful()) {
                $models = $response->json('models', []);
                $modelCount = count($models);

                return response()->json([
                    'success' => true,
                    'message' => "เชื่อมต่อ Ollama สำเร็จ พบ {$modelCount} โมเดล",
                    'models' => collect($models)->pluck('name')->toArray(),
                ]);
            }

            return response()->json(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อ Ollama ได้: ' . $response->status()]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเชื่อมต่อ Ollama ได้ กรุณาตรวจสอบว่า Ollama กำลังทำงานอยู่ที่ ' . $host,
            ]);
        }
    }

    /**
     * Get available Ollama models.
     */
    public function getOllamaModels()
    {
        $host = Setting::getValue('ollama_host', 'http://localhost:11434');

        try {
            $response = Http::timeout(10)->get($host . '/api/tags');

            if ($response->successful()) {
                $models = $response->json('models', []);

                return response()->json([
                    'success' => true,
                    'models' => collect($models)->map(function ($model) {
                        return [
                            'name' => $model['name'],
                            'size' => $this->formatBytes($model['size'] ?? 0),
                            'modified' => $model['modified_at'] ?? null,
                        ];
                    })->toArray(),
                ]);
            }

            return response()->json(['success' => false, 'models' => []]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'models' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Format bytes to human readable.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
