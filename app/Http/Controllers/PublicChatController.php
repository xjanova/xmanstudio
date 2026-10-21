<?php

namespace App\Http\Controllers;

use App\Exceptions\AIServiceException;
use App\Models\Setting;
use App\Services\AiChat\ChatPrompt;
use App\Services\AiChatService;
use App\Support\Alerts\BusinessAlerts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The site's AI assistant (น้อง Nova): the chat button on every page and the
 * guide's "ask me" field on the 3D home page both post here.
 *
 * What she knows for each answer — who is asking, the page they have open,
 * the site's pages and what it sells — is put together by ChatPrompt from the
 * live site, never written down here.
 */
class PublicChatController extends Controller
{
    public function __construct(
        protected AiChatService $chatService,
        protected ChatPrompt $prompt,
    ) {}

    /**
     * Handle public AI chat message (AJAX endpoint).
     */
    public function chat(Request $request)
    {
        // Check if AI chat is enabled
        if (! Setting::getValue('ai_chat_enabled', false)) {
            return response()->json([
                'success' => false,
                'message' => 'AI Chat is currently disabled.',
            ], 403);
        }

        // Check if AI is configured
        if (! $this->chatService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'ขออภัย ระบบ AI ยังไม่พร้อมใช้งาน กรุณาลองใหม่ภายหลัง',
            ], 503);
        }

        $request->validate([
            'messages' => 'required|array|min:1|max:20',
            'messages.*.role' => 'required|in:user,assistant',
            'messages.*.content' => 'required|string|max:2000',
            'current_url' => 'nullable|string|max:2000',
            'current_path' => 'nullable|string|max:500',
            'page_title' => 'nullable|string|max:500',
            // The screen snapshot (partials/ai-chat-page) is capped again in CurrentPage;
            // these limits only stop an oversized body, the widget sends far less.
            'page' => 'nullable|array',
            'page.text' => 'nullable|string|max:20000',
            'page.headings' => 'nullable|array|max:60',
            'page.visible' => 'nullable|array|max:30',
        ]);

        try {
            $messages = $request->input('messages');
            $currentPath = (string) $request->input('current_path', '/');

            // A visitor who leaves a phone/e-mail/LINE id, or asks for a person, is handed to the
            // team on Telegram with the conversation so far — before the AI call, so a failing AI
            // provider cannot cost the lead.
            BusinessAlerts::aiChatLead($messages, $currentPath, $request->ip());

            $systemPrompt = $this->prompt->build($messages, [
                'path' => $request->input('current_path'),
                'url' => $request->input('current_url'),
                'title' => $request->input('page_title'),
                'page' => $request->input('page'),
                'session' => $request->hasSession() ? $request->session()->getId() : null,
            ], $request->user());

            $result = $this->chatService->chat($messages, $systemPrompt);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'bot_name' => ChatPrompt::botName(),
            ]);
        } catch (AIServiceException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getUserMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Public AI Chat error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'ขออภัย เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง',
            ], 500);
        }
    }
}
