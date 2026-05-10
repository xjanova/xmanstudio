<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\AIServiceException;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AiChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiPlaygroundController extends Controller
{
    protected AiChatService $chatService;

    public function __construct(AiChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Show the AI Playground page.
     */
    public function index(Request $request)
    {
        $providerInfo = $this->chatService->getProviderInfo();
        $isConfigured = $this->chatService->isConfigured();
        // Verify against the live provider so the badge reflects reality, not just
        // "the key field is non-empty". Cached for 5 minutes; ?refresh=1 forces a fresh probe.
        $connection = $this->chatService->verifyConnection($request->boolean('refresh'));
        $settings = [
            'ai_bot_name' => Setting::getValue('ai_bot_name', 'AI Assistant'),
            'ai_system_prompt' => Setting::getValue('ai_system_prompt', ''),
            'ai_provider' => Setting::getValue('ai_provider', 'gemini'),
            'ai_response_language' => Setting::getValue('ai_response_language', 'th'),
            'ai_response_style' => Setting::getValue('ai_response_style', 'professional'),
        ];

        return view('admin.ai-playground.index', compact('providerInfo', 'isConfigured', 'connection', 'settings'));
    }

    /**
     * Handle chat message (AJAX endpoint).
     */
    public function chat(Request $request)
    {
        $request->validate([
            'messages' => 'required|array|min:1|max:50',
            'messages.*.role' => 'required|in:user,assistant,system',
            'messages.*.content' => 'required|string|max:10000',
            'system_prompt_override' => 'nullable|string|max:10000',
        ]);

        if (! $this->chatService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'AI ยังไม่ได้ตั้งค่า กรุณาไปตั้งค่า AI Provider ก่อน',
            ], 400);
        }

        try {
            $result = $this->chatService->chat(
                $request->input('messages'),
                $request->input('system_prompt_override')
            );

            return response()->json($result);
        } catch (AIServiceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('AI Playground error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            ], 500);
        }
    }
}
