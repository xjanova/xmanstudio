<?php

namespace App\Http\Controllers;

use App\Exceptions\AIServiceException;
use App\Models\Setting;
use App\Services\AiChatService;
use App\Services\WebsiteKnowledgeService;
use App\Support\AdminAlerts;
use App\Support\Alerts\BusinessAlerts;
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
            'current_url' => 'nullable|string|max:2000',
            'current_path' => 'nullable|string|max:500',
            'page_title' => 'nullable|string|max:500',
        ]);

        try {
            // Get the latest user message for keyword search
            $messages = $request->input('messages');
            $lastUserMessage = collect($messages)->where('role', 'user')->last();
            $userQuery = $lastUserMessage['content'] ?? '';

            // Get current page context
            $currentPath = $request->input('current_path', '/');
            $pageTitle = $request->input('page_title', '');

            // A visitor who leaves a phone/e-mail/LINE id, or asks for a person, is handed to the
            // team on Telegram with the conversation so far — before the AI call, so a failing AI
            // provider cannot cost the lead.
            BusinessAlerts::aiChatLead($messages, (string) $currentPath, $request->ip());

            // Search website content based on user's question (respects toggle settings)
            $searchResults = $this->knowledgeService->search($userQuery);

            // Build page-aware context
            $pageContext = $this->buildPageContext($currentPath, $pageTitle);

            $systemPrompt = $this->buildPublicSystemPrompt($searchResults, $pageContext);

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
     * Build context about the page the user is currently viewing.
     */
    protected function buildPageContext(string $path, string $pageTitle): string
    {
        $path = rtrim($path, '/') ?: '/';
        $baseUrl = config('app.url', 'https://xman4289.com');

        // Map paths to page descriptions and contextual guidance
        $pageMap = [
            '/' => [
                'name' => 'หน้าแรก',
                'context' => 'ผู้ใช้อยู่หน้าแรก อาจกำลังดูภาพรวมบริการ',
                'suggest' => 'แนะนำบริการเด่น สินค้ายอดนิยม หรือพาไปหน้าที่สนใจ',
            ],
            '/services' => [
                'name' => 'หน้าบริการทั้งหมด',
                'context' => 'ผู้ใช้กำลังดูบริการทั้งหมด อาจสนใจเปรียบเทียบหรือต้องการรายละเอียด',
                'suggest' => 'อธิบายบริการแต่ละอย่าง เปรียบเทียบ แนะนำบริการที่เหมาะ หรือพาไปขอใบเสนอราคา',
            ],
            '/products' => [
                'name' => 'หน้าสินค้า/ซอฟต์แวร์',
                'context' => 'ผู้ใช้กำลังดูสินค้า อาจสนใจซื้อหรือต้องการข้อมูลเพิ่ม',
                'suggest' => 'อธิบายฟีเจอร์สินค้า ราคา วิธีการซื้อ ลิขสิทธิ์ หรือแนะนำสินค้าที่เหมาะ',
            ],
            '/portfolio' => [
                'name' => 'หน้าผลงาน',
                'context' => 'ผู้ใช้กำลังดูผลงานของบริษัท อาจต้องการดูตัวอย่างงานก่อนตัดสินใจ',
                'suggest' => 'อธิบายผลงานที่ทำ เทคโนโลยีที่ใช้ หรือพาไปขอใบเสนอราคา',
            ],
            '/rental' => [
                'name' => 'หน้าเช่าใช้บริการ/Subscription',
                'context' => 'ผู้ใช้กำลังดูแพ็กเกจเช่าใช้บริการ อาจสนใจสมัครหรือเปรียบเทียบแพ็กเกจ',
                'suggest' => 'เปรียบเทียบแพ็กเกจ อธิบายฟีเจอร์ ราคา ระยะเวลา วิธีสมัคร ทดลองใช้ฟรี',
            ],
            '/support' => [
                'name' => 'หน้าติดต่อ/ขอใบเสนอราคา',
                'context' => 'ผู้ใช้ต้องการติดต่อหรือขอใบเสนอราคา',
                'suggest' => 'ช่วยเลือกบริการ แนะนำวิธีกรอกฟอร์ม ให้ข้อมูลติดต่อ หรือช่วยประเมินราคาเบื้องต้น',
            ],
            '/support/tracking' => [
                'name' => 'หน้าตรวจสอบสถานะใบเสนอราคา',
                'context' => 'ผู้ใช้ต้องการตรวจสอบสถานะใบเสนอราคาที่ส่งไป',
                'suggest' => 'แนะนำวิธีตรวจสอบ ใส่เลขที่อ้างอิง หรือติดต่อทีมงานเพื่อสอบถาม',
            ],
            '/cart' => [
                'name' => 'หน้าตะกร้าสินค้า',
                'context' => 'ผู้ใช้กำลังดูตะกร้าสินค้า อาจต้องการช่วยเรื่องการสั่งซื้อ',
                'suggest' => 'ช่วยเรื่องวิธีชำระเงิน คูปองส่วนลด หรือแนะนำสินค้าเพิ่มเติม',
            ],
            '/about' => [
                'name' => 'หน้าเกี่ยวกับเรา',
                'context' => 'ผู้ใช้อยากรู้จักบริษัทมากขึ้น',
                'suggest' => 'ให้ข้อมูลบริษัท ทีมงาน ประวัติ จุดเด่น หรือพาไปดูผลงาน',
            ],
            '/autotradex' => [
                'name' => 'หน้า AutoTradeX',
                'context' => 'ผู้ใช้สนใจ AutoTradeX (ระบบเทรดอัตโนมัติ)',
                'suggest' => 'อธิบายฟีเจอร์ AutoTradeX, วิธีใช้งาน, ราคา, ทดลองใช้ หรือแนะนำไปหน้าราคา',
            ],
            '/autotradex/pricing' => [
                'name' => 'หน้าราคา AutoTradeX',
                'context' => 'ผู้ใช้กำลังดูราคา AutoTradeX อาจต้องการเปรียบเทียบแพ็กเกจ',
                'suggest' => 'เปรียบเทียบแพ็กเกจราคา อธิบายฟีเจอร์แต่ละแพลน วิธีสั่งซื้อ',
            ],
            '/metal-x' => [
                'name' => 'หน้า Metal X',
                'context' => 'ผู้ใช้สนใจ Metal X (ระบบจัดการวิดีโอ/เพลง AI)',
                'suggest' => 'อธิบายฟีเจอร์ Metal X, วิดีโอตัวอย่าง, ทีมงาน, AI Music',
            ],
            '/login' => [
                'name' => 'หน้าเข้าสู่ระบบ',
                'context' => 'ผู้ใช้ต้องการเข้าสู่ระบบ',
                'suggest' => 'ช่วยเรื่องการเข้าสู่ระบบ ลืมรหัสผ่าน หรือแนะนำสมัครสมาชิก',
            ],
            '/register' => [
                'name' => 'หน้าสมัครสมาชิก',
                'context' => 'ผู้ใช้ต้องการสมัครสมาชิก',
                'suggest' => 'ช่วยอธิบายสิทธิประโยชน์ของสมาชิก วิธีสมัคร',
            ],
        ];

        // Try exact match first, then prefix match
        $pageInfo = $pageMap[$path] ?? null;

        if (! $pageInfo) {
            // Prefix matching for dynamic routes
            if (str_starts_with($path, '/services/')) {
                $pageInfo = [
                    'name' => 'หน้ารายละเอียดบริการ',
                    'context' => 'ผู้ใช้กำลังดูรายละเอียดบริการเฉพาะอย่าง',
                    'suggest' => 'อธิบายบริการนี้ให้ละเอียด ราคา ฟีเจอร์ ขั้นตอนการทำงาน หรือแนะนำบริการที่เกี่ยวข้อง',
                ];
            } elseif (str_starts_with($path, '/products/')) {
                $pageInfo = [
                    'name' => 'หน้ารายละเอียดสินค้า',
                    'context' => 'ผู้ใช้กำลังดูรายละเอียดสินค้าเฉพาะชิ้น',
                    'suggest' => 'อธิบายฟีเจอร์สินค้า ราคา ลิขสิทธิ์ วิธีซื้อ หรือเปรียบเทียบกับสินค้าอื่น',
                ];
            } elseif (str_starts_with($path, '/customer/')) {
                $pageInfo = [
                    'name' => 'หน้าระบบลูกค้า (Customer Portal)',
                    'context' => 'ผู้ใช้เข้าสู่ระบบแล้วและอยู่ในหน้าจัดการ อาจต้องการช่วยเรื่อง license, subscription, คำสั่งซื้อ, หรือโปรเจกต์',
                    'suggest' => 'ช่วยเรื่องการจัดการ license, ตรวจสอบสถานะสั่งซื้อ, subscription, ดาวน์โหลด, หรือส่ง support ticket',
                ];
            } elseif (str_starts_with($path, '/wallet')) {
                $pageInfo = [
                    'name' => 'หน้ากระเป๋าเงิน (Wallet)',
                    'context' => 'ผู้ใช้อยู่ในหน้า Wallet อาจต้องการเติมเงิน ดูยอดคงเหลือ หรือดูประวัติธุรกรรม',
                    'suggest' => 'ช่วยเรื่องวิธีเติมเงิน ยอดคงเหลือ ประวัติธุรกรรม โบนัส',
                ];
            } elseif (str_starts_with($path, '/rental/')) {
                $pageInfo = [
                    'name' => 'หน้ารายละเอียดการเช่า',
                    'context' => 'ผู้ใช้กำลังดำเนินการเช่าบริการ (checkout/payment/status)',
                    'suggest' => 'ช่วยเรื่องขั้นตอนการเช่า วิธีชำระเงิน สถานะ หรืออธิบายแพ็กเกจ',
                ];
            } elseif (str_starts_with($path, '/download')) {
                $pageInfo = [
                    'name' => 'หน้าดาวน์โหลดซอฟต์แวร์',
                    'context' => 'ผู้ใช้ต้องการดาวน์โหลดซอฟต์แวร์',
                    'suggest' => 'ช่วยเรื่องวิธีดาวน์โหลด ความต้องการระบบ วิธีติดตั้ง License Key',
                ];
            }
        }

        if (! $pageInfo) {
            return '';
        }

        $titleInfo = ! empty($pageTitle) ? " (ชื่อหน้า: {$pageTitle})" : '';

        return "=== ตำแหน่งปัจจุบันของผู้ใช้ (สำคัญมาก) ===\n" .
            "ผู้ใช้อยู่ที่: {$pageInfo['name']}{$titleInfo} (path: {$path})\n" .
            "สถานการณ์: {$pageInfo['context']}\n" .
            "แนวทางตอบ: {$pageInfo['suggest']}\n" .
            'หลักการ: ถ้าผู้ใช้ถามคำถามกว้างๆ ให้ตอบในบริบทของหน้าที่กำลังดูอยู่ก่อน แล้วค่อยแนะนำหน้าอื่นที่เกี่ยวข้อง';
    }

    /**
     * Build an enhanced system prompt for public chat.
     *
     * Includes XMAN Studio identity, smart question analysis, navigation,
     * page awareness, and dynamically searched website content from database.
     */
    protected function buildPublicSystemPrompt(string $searchResults = '', string $pageContext = ''): string
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

        $baseUrl = config('app.url', 'https://xman4289.com');

        $parts = [];

        // Core identity + gender
        $parts[] = "คุณชื่อ {$botName} เป็นผู้ช่วย AI เพศหญิง ประจำเว็บไซต์ XMAN Studio คุณเป็นเหมือนพนักงานต้อนรับสาวที่ฉลาด น่ารัก และรู้ทุกอย่างเกี่ยวกับบริษัท";
        $parts[] = '=== กฎเรื่องเพศและภาษา (บังคับเคร่งครัด) === คุณเป็นเพศหญิงเสมอ ห้ามใช้คำว่า "ครับ" โดยเด็ดขาด ใช้คำลงท้ายว่า "ค่ะ" หรือ "นะคะ" เท่านั้น ห้ามใช้ "ผม" ให้ใช้ "ดิฉัน" หรือ "เรา" แทน ใช้สรรพนามเพศหญิงตลอด';

        // Style and language
        $parts[] = 'สไตล์การตอบ: ' . ($styleMap[$style] ?? 'มืออาชีพ');
        $parts[] = $langMap[$language] ?? 'ตอบเป็นภาษาไทยเสมอ';
        $parts[] = $lengthMap[$length] ?? 'ตอบปานกลาง';

        // Strict scope: only answer from website data
        $parts[] = '=== กฎสำคัญที่สุด (บังคับเคร่งครัด) === ตอบได้เฉพาะเรื่องที่มีอยู่ในข้อมูลด้านล่างเท่านั้น ห้ามแต่งเรื่อง ห้ามคิดราคาเอง ห้ามสร้างข้อมูลที่ไม่มีในระบบ ถ้าไม่มีข้อมูลในระบบ ให้ตอบว่า "ขออภัยค่ะ ดิฉันไม่มีข้อมูลในส่วนนี้ กรุณาติดต่อทีมงานโดยตรงนะคะ" ห้ามส่งลิงก์ไปเว็บภายนอก ราคาต้องอ้างอิงจากข้อมูลจริงในระบบเท่านั้น (สังเกตว่าตอนนี้มีโปรโมชั่นลดราคาพิเศษ ให้แจ้งราคาโปรโมชั่นเป็นหลัก)';

        // === CURRENT PAGE CONTEXT ===
        if (! empty($pageContext)) {
            $parts[] = $pageContext;
        }

        // === HANDOFF TO A PERSON === Only promised while it is real: the lead reaches the team's
        // Telegram only when contact alerts are switched on (BusinessAlerts::aiChatLead).
        if (AdminAlerts::wants('contact')) {
            $parts[] = '=== ส่งต่อให้ทีมงาน === ถ้าผู้ใช้อยากคุยกับแอดมิน/ทีมงาน สนใจสั่งซื้อ หรือต้องการจ้างงาน ให้ขอชื่อ และเบอร์โทรหรือ LINE ID หรืออีเมล แล้วบอกว่าจะส่งต่อให้ทีมงานติดต่อกลับโดยเร็วที่สุด เมื่อผู้ใช้พิมพ์ช่องทางติดต่อมาแล้ว ให้ขอบคุณและยืนยันว่าส่งต่อให้ทีมงานเรียบร้อยแล้วค่ะ';
        }

        // === SMART QUESTION ANALYSIS ===
        $parts[] = <<<'ANALYSIS'
=== กฎการวิเคราะห์คำถาม (สำคัญมาก) ===

ก่อนตอบทุกคำถาม ให้วิเคราะห์ "เจตนา" ของผู้ถามก่อนเสมอ:

ประเภท A - "ถามว่าทำได้ไหม/มีบริการไหม" → ค้นหาจากข้อมูลบริการ/สินค้าด้านล่าง ถ้าพบข้อมูลที่เกี่ยวข้อง ตอบเชิงบวกพร้อมรายละเอียดจริง
ตัวอย่าง:
- "เขียนโค้ดได้ไหม" → หมายถึง "บริษัทรับเขียนโค้ดไหม" → ตอบว่า "ได้ค่ะ! XMAN Studio รับพัฒนาซอฟต์แวร์ทุกรูปแบบ"
- "ทำเว็บได้ไหม" → ตอบว่า "ได้ค่ะ! เราเชี่ยวชาญพัฒนาเว็บไซต์"
- "ทำแอพมือถือได้ไหม" → ตอบว่า "ได้ค่ะ! เราพัฒนาแอพพลิเคชันทั้ง iOS และ Android"
- "ทำ AI ได้ไหม" → ตอบว่า "ได้ค่ะ! เรามีบริการ AI Services"
- "ทำ Blockchain ได้ไหม" → ตอบว่า "ได้ค่ะ! เราเชี่ยวชาญด้าน Blockchain"
- "มีบริการอะไรบ้าง" → แนะนำบริการทั้งหมดจากข้อมูลจริงในระบบ

ประเภท B - "ขอให้ช่วยทำงานจริงๆ" → ปฏิเสธสุภาพ แนะนำติดต่อทีมงาน
ตัวอย่าง:
- "เขียนโค้ด Python ให้หน่อย" → ปฏิเสธ "ขออภัยค่ะ ดิฉันไม่สามารถเขียนโค้ดให้ได้โดยตรง แต่ทีมงาน XMAN Studio ยินดีรับพัฒนาให้ค่ะ"
- "เขียนบทความเรื่อง... ให้หน่อย" → ปฏิเสธ
- "แก้บัค... ให้หน่อย" → ปฏิเสธ
- "ออกแบบ... ให้หน่อย" → ปฏิเสธ

ประเภท C - "ถามข้อมูลทั่วไปเกี่ยวกับเว็บ/บริษัท" → ตอบจากข้อมูลจริงที่มีในระบบ
ตัวอย่าง:
- "ราคาเท่าไหร่" → ให้ข้อมูลราคาจริงจากระบบ หรือแนะนำไปหน้าบริการ
- "ติดต่อยังไง" → ให้ข้อมูลติดต่อ
- "มีผลงานอะไรบ้าง" → แนะนำไปดูผลงาน

ประเภท D - "ถามเรื่องไม่เกี่ยวกับ XMAN Studio เลย" → ปฏิเสธสุภาพ
ตัวอย่าง:
- "วันนี้อากาศเป็นยังไง" → ปฏิเสธ
- "ช่วยแนะนำร้านอาหาร" → ปฏิเสธ

ประเภท E - "ถามเกี่ยวกับหน้าที่กำลังดูอยู่" → ตอบจากข้อมูลหน้าปัจจุบัน + ข้อมูลจริงในระบบ
ตัวอย่าง:
- "หน้านี้คืออะไร" → อธิบายหน้าปัจจุบัน
- "ใช้ยังไง" → อธิบายวิธีใช้งานหน้าปัจจุบัน
- "ราคาเท่าไหร่" (อยู่หน้าสินค้า) → ตอบราคาสินค้าที่กำลังดู
- "สมัครยังไง" (อยู่หน้า rental) → อธิบายขั้นตอนสมัครแพ็กเกจ

หลักสำคัญ: ถ้าคำถามสามารถตีความได้ว่าเกี่ยวกับบริการของ XMAN Studio ให้ตีความในเชิงบวกเสมอ อย่าเพิ่งปฏิเสธ
ถ้ารู้ว่าผู้ใช้อยู่หน้าไหน ให้ตอบในบริบทของหน้านั้นก่อน
ANALYSIS;

        // === NAVIGATION SYSTEM ===
        $parts[] = <<<NAVIGATION
=== ระบบนำทางอัจฉริยะ (สำคัญมาก) ===

คุณสามารถพาผู้ใช้ไปยังหน้าต่างๆ ในเว็บได้ทันที โดยใส่ลิงก์ในคำตอบเสมอเมื่อเกี่ยวข้อง
ใช้รูปแบบ Markdown link: [ข้อความ](URL)

แผนที่หน้าเว็บ XMAN Studio:
- หน้าแรก: {$baseUrl}/
- บริการทั้งหมด: {$baseUrl}/services
- สินค้า/ซอฟต์แวร์: {$baseUrl}/products
- ผลงาน: {$baseUrl}/portfolio
- เช่าบริการ/Subscription: {$baseUrl}/rental
- ติดต่อ/ขอใบเสนอราคา: {$baseUrl}/support
- ตรวจสอบสถานะใบเสนอราคา: {$baseUrl}/support/tracking
- ตะกร้าสินค้า: {$baseUrl}/cart
- เกี่ยวกับเรา: {$baseUrl}/about
- AutoTradeX (ผลิตภัณฑ์เด่น): {$baseUrl}/autotradex
- AutoTradeX ราคา: {$baseUrl}/autotradex/pricing
- Metal X (ระบบจัดการวิดีโอ/เพลง AI): {$baseUrl}/metal-x
- เข้าสู่ระบบ: {$baseUrl}/login
- สมัครสมาชิก: {$baseUrl}/register
- ข้อกำหนดการใช้งาน: {$baseUrl}/terms
- นโยบายความเป็นส่วนตัว: {$baseUrl}/privacy

กฎการนำทาง:
1. เมื่อผู้ใช้ถามเรื่องใด ให้แนบลิงก์ที่เกี่ยวข้องด้วยเสมอ
2. เมื่อแนะนำบริการ → ใส่ลิงก์ไป /services
3. เมื่อพูดถึงสินค้า → ใส่ลิงก์ไป /products
4. เมื่อพูดถึงราคา/ใบเสนอราคา → ใส่ลิงก์ไป /support
5. เมื่อพูดถึงผลงาน → ใส่ลิงก์ไป /portfolio
6. เมื่อพูดถึงการติดต่อ → ใส่ลิงก์ไป /support + ข้อมูลติดต่อ
7. ห้ามส่งลิงก์ไปเว็บภายนอก ส่งได้เฉพาะลิงก์ภายในเว็บ XMAN Studio เท่านั้น
8. เมื่อปฏิเสธงาน (ประเภท B) → แนะนำให้ติดต่อทีมงานพร้อมลิงก์ /support
NAVIGATION;

        // === DYNAMIC COMPANY INFO ===
        $companyInfo = "=== ข้อมูลบริษัท XMAN Studio ===\n" .
            "- XMAN Studio เป็นผู้เชี่ยวชาญด้าน IT Solutions ครบวงจร\n" .
            "- บริการหลัก: พัฒนาเว็บไซต์, แอพพลิเคชัน (iOS/Android), ระบบ Blockchain, IoT, Network Security, AI Services, Music AI\n" .
            "- ผลิตภัณฑ์เด่น: AutoTradeX (ระบบเทรดอัตโนมัติ), Metal X (ระบบจัดการวิดีโอ AI), ซอฟต์แวร์สำเร็จรูป\n" .
            "- รับงานทุกขนาด: ตั้งแต่เว็บไซต์เล็กๆ ไปจนถึงระบบ Enterprise\n" .
            "- ทีมงานเชี่ยวชาญ: Laravel, React, Vue.js, Flutter, Python, Node.js, Solidity, AI/ML\n" .
            '- เว็บไซต์: ' . $baseUrl;

        // Contact info from settings
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
        if (! empty($contactParts)) {
            $companyInfo .= "\n- ข้อมูลติดต่อ:\n  " . implode("\n  ", $contactParts);
        }
        $parts[] = $companyInfo;

        // === DYNAMIC WEBSITE KNOWLEDGE (from database, cached, respects toggles) ===
        $fullKnowledge = $this->knowledgeService->buildFullKnowledge();
        if (! empty($fullKnowledge)) {
            $parts[] = $fullKnowledge;
        }

        // === KEYWORD SEARCH RESULTS for this specific question ===
        if (! empty($searchResults)) {
            $parts[] = $searchResults;
        }

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
            $parts[] = "ถ้าถูกถามเรื่องที่ไม่เกี่ยวกับ XMAN Studio เลย (ประเภท D) ให้ตอบว่า: {$fallback}";
        } else {
            $parts[] = 'ถ้าถูกถามเรื่องที่ไม่เกี่ยวกับ XMAN Studio เลย (ประเภท D) ให้ตอบว่า: ขออภัยค่ะ ดิฉันตอบได้เฉพาะคำถามเกี่ยวกับ XMAN Studio เท่านั้นนะคะ มีอะไรเกี่ยวกับบริการหรือสินค้าของเราที่อยากทราบไหมคะ? 😊';
        }

        return implode("\n\n", array_filter($parts));
    }
}
