<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

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

            // General AI Settings
            'ai_provider' => Setting::getValue('ai_provider', 'openai'),
            'ai_max_tokens' => Setting::getValue('ai_max_tokens', 1000),
            'ai_temperature' => Setting::getValue('ai_temperature', 0.7),

            // Feature Toggles
            'ai_chat_enabled' => Setting::getValue('ai_chat_enabled', false),
            'ai_content_generation' => Setting::getValue('ai_content_generation', false),
            'ai_code_assistant' => Setting::getValue('ai_code_assistant', false),
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
            'ai_provider' => 'required|in:openai,claude',
            'ai_max_tokens' => 'required|integer|min:100|max:8000',
            'ai_temperature' => 'required|numeric|min:0|max:2',
        ]);

        // OpenAI Settings
        if ($request->filled('openai_api_key')) {
            Setting::setValue('openai_api_key', $request->openai_api_key, 'ai');
        }
        Setting::setValue('openai_model', $request->openai_model, 'ai');
        Setting::setValue('openai_enabled', $request->boolean('openai_enabled'), 'ai', 'boolean');

        // Claude Settings
        if ($request->filled('claude_api_key')) {
            Setting::setValue('claude_api_key', $request->claude_api_key, 'ai');
        }
        Setting::setValue('claude_model', $request->claude_model, 'ai');
        Setting::setValue('claude_enabled', $request->boolean('claude_enabled'), 'ai', 'boolean');

        // General AI Settings
        Setting::setValue('ai_provider', $request->ai_provider, 'ai');
        Setting::setValue('ai_max_tokens', $request->ai_max_tokens, 'ai', 'integer');
        Setting::setValue('ai_temperature', $request->ai_temperature, 'ai');

        // Feature Toggles
        Setting::setValue('ai_chat_enabled', $request->boolean('ai_chat_enabled'), 'ai', 'boolean');
        Setting::setValue('ai_content_generation', $request->boolean('ai_content_generation'), 'ai', 'boolean');
        Setting::setValue('ai_code_assistant', $request->boolean('ai_code_assistant'), 'ai', 'boolean');

        return redirect()->route('admin.ai-settings.index')
            ->with('success', 'บันทึกการตั้งค่า AI เรียบร้อยแล้ว');
    }

    /**
     * Test AI connection.
     */
    public function test(Request $request)
    {
        $provider = Setting::getValue('ai_provider', 'openai');

        try {
            if ($provider === 'openai') {
                $apiKey = Setting::getValue('openai_api_key');
                if (empty($apiKey)) {
                    return response()->json(['success' => false, 'message' => 'ยังไม่ได้ตั้งค่า OpenAI API Key']);
                }

                // Test OpenAI connection
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                ])->get('https://api.openai.com/v1/models');

                if ($response->successful()) {
                    return response()->json(['success' => true, 'message' => 'เชื่อมต่อ OpenAI สำเร็จ']);
                }

                return response()->json(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อ OpenAI ได้: '.$response->status()]);
            } else {
                $apiKey = Setting::getValue('claude_api_key');
                if (empty($apiKey)) {
                    return response()->json(['success' => false, 'message' => 'ยังไม่ได้ตั้งค่า Claude API Key']);
                }

                // Test Claude connection
                $response = \Illuminate\Support\Facades\Http::withHeaders([
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

                return response()->json(['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อ Claude ได้: '.$response->status()]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage()]);
        }
    }
}
