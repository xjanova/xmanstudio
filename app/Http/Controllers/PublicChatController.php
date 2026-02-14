<?php

namespace App\Http\Controllers;

use App\Exceptions\AIServiceException;
use App\Models\Setting;
use App\Services\AiChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicChatController extends Controller
{
    protected AiChatService $chatService;

    public function __construct(AiChatService $chatService)
    {
        $this->chatService = $chatService;
    }

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
        ]);

        try {
            $systemPrompt = $this->buildPublicSystemPrompt();

            $result = $this->chatService->chat(
                $request->input('messages'),
                $systemPrompt
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'bot_name' => Setting::getValue('ai_bot_name', 'AI Assistant'),
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

    /**
     * Build an enhanced system prompt for public chat.
     *
     * Forces the AI to only answer questions about the website/project.
     */
    protected function buildPublicSystemPrompt(): string
    {
        $botName = Setting::getValue('ai_bot_name', 'AI Assistant');
        $language = Setting::getValue('ai_response_language', 'th');
        $style = Setting::getValue('ai_response_style', 'professional');
        $length = Setting::getValue('ai_response_length', 'medium');

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

        $parts = [];

        // Core identity and restriction
        $parts[] = "คุณชื่อ {$botName} เป็นผู้ช่วย AI ของเว็บไซต์ XMAN Studio";
        $parts[] = 'คุณมีหน้าที่ตอบคำถามเกี่ยวกับเว็บไซต์ XMAN Studio, บริการ, ผลิตภัณฑ์, โปรเจค และข้อมูลบริษัทเท่านั้น';
        $parts[] = 'ห้ามตอบคำถามที่ไม่เกี่ยวข้องกับเว็บไซต์หรือบริษัท XMAN Studio โดยเด็ดขาด หากถูกถามเรื่องอื่น ให้ปฏิเสธอย่างสุภาพและแนะนำให้ถามเรื่องที่เกี่ยวกับ XMAN Studio แทน';

        // Style and language
        $parts[] = 'สไตล์การตอบ: ' . ($styleMap[$style] ?? 'มืออาชีพ');
        $parts[] = $langMap[$language] ?? 'ตอบเป็นภาษาไทยเสมอ';
        $parts[] = $lengthMap[$length] ?? 'ตอบปานกลาง';

        // Company info context
        $parts[] = "ข้อมูลพื้นฐานของ XMAN Studio:\n" .
            "- XMAN Studio เป็นผู้เชี่ยวชาญด้าน IT Solutions ครบวงจร\n" .
            "- บริการ: พัฒนาเว็บไซต์, แอพพลิเคชัน, Blockchain, IoT, Network Security, AI Services\n" .
            "- ผลิตภัณฑ์: ซอฟต์แวร์สำเร็จรูป, เครื่องมือสำหรับนักพัฒนา\n" .
            "- ติดต่อ: 080-6038278 (คุณกรณิภา), Email: xjanovax@gmail.com\n" .
            "- Facebook: XMAN Enterprise, Line OA: @xmanstudio\n" .
            '- เว็บไซต์: xman4289.com';

        // Base system prompt from settings
        $basePrompt = Setting::getValue('ai_system_prompt', '');
        if (! empty($basePrompt)) {
            $parts[] = "คำสั่งเพิ่มเติม:\n{$basePrompt}";
        }

        // Custom knowledge base
        $knowledge = Setting::getValue('ai_custom_knowledge', '');
        if (! empty($knowledge)) {
            $parts[] = "ข้อมูลเพิ่มเติมที่ต้องรู้:\n{$knowledge}";
        }

        // Topic restrictions from settings
        $allowed = Setting::getValue('ai_allowed_topics', '');
        if (! empty($allowed)) {
            $parts[] = "หัวข้อที่อนุญาตให้ตอบ: {$allowed}";
        }

        $forbidden = Setting::getValue('ai_forbidden_topics', '');
        if (! empty($forbidden)) {
            $parts[] = "หัวข้อที่ห้ามตอบ: {$forbidden}";
        }

        // Fallback message
        $fallback = Setting::getValue('ai_fallback_message', '');
        if (! empty($fallback)) {
            $parts[] = "ถ้าตอบไม่ได้หรือเป็นคำถามนอกเหนือจากเรื่อง XMAN Studio ให้ตอบว่า: {$fallback}";
        } else {
            $parts[] = 'ถ้าตอบไม่ได้หรือเป็นคำถามนอกเหนือจากเรื่อง XMAN Studio ให้ตอบว่า: ขออภัยค่ะ ฉันตอบได้เฉพาะคำถามเกี่ยวกับ XMAN Studio เท่านั้น หากต้องการสอบถามข้อมูลเพิ่มเติม สามารถติดต่อทีมงานได้โดยตรงค่ะ';
        }

        return implode("\n\n", array_filter($parts));
    }
}
