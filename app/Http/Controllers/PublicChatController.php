<?php

namespace App\Http\Controllers;

use App\Exceptions\AIServiceException;
use App\Models\Setting;
use App\Services\AiChatService;
use App\Services\WebsiteKnowledgeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PublicChatController extends Controller
{
    protected AiChatService $chatService;

    protected WebsiteKnowledgeService $knowledgeService;

    public function __construct(AiChatService $chatService, WebsiteKnowledgeService $knowledgeService)
    {
        $this->chatService = $chatService;
        $this->knowledgeService = $knowledgeService;
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
            // Get the latest user message for keyword search
            $messages = $request->input('messages');
            $lastUserMessage = collect($messages)->where('role', 'user')->last();
            $userQuery = $lastUserMessage['content'] ?? '';

            // Search website content based on user's question (respects toggle settings)
            $searchResults = $this->knowledgeService->search($userQuery);

            $systemPrompt = $this->buildPublicSystemPrompt($searchResults);

            $result = $this->chatService->chat(
                $messages,
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
     * Build the system prompt for public chat.
     *
     * Bot identity, persona, and knowledge all come from admin settings.
     * Website data is only included when the respective toggles are enabled.
     */
    protected function buildPublicSystemPrompt(string $searchResults = ''): string
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

        // Bot identity (from settings only - no hardcoded persona)
        $parts[] = "คุณชื่อ {$botName}";

        // Admin-defined system prompt (persona, rules, personality all configured here)
        $basePrompt = Setting::getValue('ai_system_prompt', '');
        if (! empty($basePrompt)) {
            $parts[] = $basePrompt;
        }

        // Style and language
        $parts[] = 'สไตล์การตอบ: ' . ($styleMap[$style] ?? 'มืออาชีพ');
        $parts[] = $langMap[$language] ?? 'ตอบเป็นภาษาไทยเสมอ';
        $parts[] = $lengthMap[$length] ?? 'ตอบปานกลาง';

        // Strict scope: only answer from provided data
        $parts[] = '=== กฎสำคัญ === ตอบได้เฉพาะเรื่องที่มีอยู่ในข้อมูลด้านล่างเท่านั้น ห้ามแต่งเรื่องหรือตอบจากความรู้ภายนอก ถ้าไม่มีข้อมูล ให้แนะนำติดต่อทีมงานแทน ห้ามส่งลิงก์ไปเว็บภายนอก';

        // Custom knowledge base (admin-configured: fortune telling, commission rates, etc.)
        $knowledge = Setting::getValue('ai_custom_knowledge', '');
        if (! empty($knowledge)) {
            $parts[] = "=== ข้อมูลที่ต้องรู้ ===\n{$knowledge}";
        }

        // Company info (only if toggle is on)
        $useCompanyInfo = Setting::getValue('ai_use_company_info', true);
        if ($useCompanyInfo) {
            $companyInfo = $this->buildCompanyInfo();
            if (! empty($companyInfo)) {
                $parts[] = $companyInfo;
            }
        }

        // Website data (only if toggles are on - respects ai_use_product_data, ai_use_service_data)
        $fullKnowledge = $this->knowledgeService->buildFullKnowledge();
        if (! empty($fullKnowledge)) {
            $parts[] = $fullKnowledge;
        }

        // Keyword search results for this specific question
        if (! empty($searchResults)) {
            $parts[] = $searchResults;
        }

        // Navigation links (only if company info or website data is used)
        if ($useCompanyInfo) {
            $parts[] = $this->buildNavigation();
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
            $parts[] = "ถ้าถูกถามเรื่องที่ไม่เกี่ยวข้อง ให้ตอบว่า: {$fallback}";
        }

        return implode("\n\n", array_filter($parts));
    }

    /**
     * Build company/contact info section from settings.
     */
    protected function buildCompanyInfo(): string
    {
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
        if ($fbName) {
            $contactParts[] = 'Facebook: ' . $fbName;
        }
        $lineId = Setting::get('contact_line_id', '');
        if ($lineId) {
            $contactParts[] = 'Line OA: ' . $lineId;
        }
        $ytName = Setting::get('contact_youtube_name', '');
        if ($ytName) {
            $contactParts[] = 'YouTube: ' . $ytName;
        }
        $address = Setting::get('contact_address', '');
        if ($address) {
            $contactParts[] = 'ที่อยู่: ' . $address;
        }

        if (empty($contactParts)) {
            return '';
        }

        return "=== ข้อมูลติดต่อ ===\n" . implode("\n", $contactParts);
    }

    /**
     * Build navigation links section.
     */
    protected function buildNavigation(): string
    {
        $baseUrl = config('app.url', 'https://xman4289.com');

        return <<<NAVIGATION
=== ลิงก์ในเว็บไซต์ (ใส่ในคำตอบเมื่อเกี่ยวข้อง ใช้ Markdown link) ===
- หน้าแรก: {$baseUrl}/
- บริการ: {$baseUrl}/services
- สินค้า: {$baseUrl}/products
- ผลงาน: {$baseUrl}/portfolio
- เช่าบริการ: {$baseUrl}/rental
- ติดต่อ: {$baseUrl}/support
- ตรวจสอบสถานะ: {$baseUrl}/support/tracking
- เกี่ยวกับเรา: {$baseUrl}/about
- AutoTradeX: {$baseUrl}/autotradex
- Metal X: {$baseUrl}/metal-x
ห้ามส่งลิงก์ไปเว็บภายนอก
NAVIGATION;
    }
}
