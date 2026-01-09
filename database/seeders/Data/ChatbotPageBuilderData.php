<?php

namespace Database\Seeders\Data;

class ChatbotPageBuilderData
{
    /**
     * Get Customer Service Chatbot content
     */
    public static function getCustomerServiceContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'Chatbot บริการลูกค้าอัจฉริยะ 24/7',
                ],
                [
                    'type' => 'text',
                    'content' => 'ยกระดับการบริการลูกค้าด้วย AI Chatbot ที่เข้าใจภาษาธรรมชาติ ตอบคำถามได้ทันที และเรียนรู้จากทุกการสนทนา ลดภาระทีม CS และเพิ่มความพึงพอใจลูกค้า',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมต้องใช้ AI Chatbot?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'clock',
                    'iconColor' => '#6366f1',
                    'title' => 'พร้อมให้บริการ 24/7',
                    'content' => 'ตอบคำถามลูกค้าได้ตลอด 24 ชั่วโมง ไม่มีวันหยุด ไม่ต้องรอคิว',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'title' => 'ตอบทันทีภายใน 3 วินาที',
                    'content' => 'ลูกค้าได้รับคำตอบทันที ไม่ต้องรอ ลด Bounce Rate และเพิ่ม Conversion',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'title' => 'ลดต้นทุน CS 60%',
                    'content' => 'รับมือ 80% ของคำถามซ้ำๆ ได้เอง ทีม CS โฟกัสกับเคสที่ซับซ้อน',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ความสามารถหลัก',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => 'Natural Language Understanding',
                    'description' => 'เข้าใจภาษาธรรมชาติ ทั้งภาษาไทยและอังกฤษ รวมถึงภาษาพูด คำสแลง และ Typo',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#6366f1',
                    'content' => 'Multi-turn Conversations',
                    'description' => 'จำบริบทการสนทนา ถามตอบต่อเนื่องได้เหมือนคุยกับคนจริง',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#0ea5e9',
                    'content' => 'Human Handoff',
                    'description' => 'ส่งต่อให้เจ้าหน้าที่อัตโนมัติเมื่อเจอเคสที่ซับซ้อนหรือลูกค้าต้องการ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'content' => 'Analytics & Insights',
                    'description' => 'รายงานสถิติการใช้งาน คำถามที่พบบ่อย และ Customer Sentiment',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'lg',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'เหมาะสำหรับ',
                ],
                [
                    'type' => 'list',
                    'style' => 'check',
                    'items' => [
                        'E-commerce & Online Retail',
                        'ธนาคาร ประกัน และ Financial Services',
                        'Telco & Internet Service Providers',
                        'Healthcare & Clinics',
                        'Travel & Hospitality',
                        'SaaS & Technology Companies',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'features_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ฟีเจอร์ครบครัน',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#6366f1',
                    'title' => 'AI เข้าใจภาษาไทย',
                    'content' => 'รองรับภาษาไทยอย่างสมบูรณ์ เข้าใจ Context ภาษาพูด คำสแลง และ Typo',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'title' => 'Multi-Channel',
                    'content' => 'รองรับ Website, LINE, Facebook Messenger, WhatsApp และ Mobile App',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#ec4899',
                    'title' => 'CRM Integration',
                    'content' => 'เชื่อมต่อ Salesforce, HubSpot, Zendesk และระบบ CRM อื่นๆ',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'Knowledge Base',
                    'content' => 'ฐานความรู้ที่ Chatbot ใช้ตอบคำถาม อัปเดตได้ง่ายผ่าน Admin Panel',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'users',
                    'iconColor' => '#f59e0b',
                    'title' => 'Human Handoff',
                    'content' => 'ส่งต่อให้เจ้าหน้าที่เมื่อจำเป็น พร้อมประวัติการสนทนาครบถ้วน',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#8b5cf6',
                    'title' => 'Analytics Dashboard',
                    'content' => 'รายงานสถิติ Popular Questions, Resolution Rate และ Customer Satisfaction',
                    'style' => ['bgColor' => '#f5f3ff'],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'steps_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ขั้นตอนการพัฒนา',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'content' => '1. วิเคราะห์ความต้องการ',
                    'description' => 'ศึกษา Business Workflow, FAQ ที่พบบ่อย และ Use Cases ที่ต้องการ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => '2. ออกแบบ Conversation Flow',
                    'description' => 'ออกแบบ Dialog Flow, User Intents และ Responses ที่เหมาะสม',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => '3. Train AI Model',
                    'description' => 'สอน AI ด้วยข้อมูลจริงของธุรกิจ FAQ, Product Info และ Policies',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'globe',
                    'iconColor' => '#10b981',
                    'content' => '4. Integration',
                    'description' => 'เชื่อมต่อกับ Channels ที่ต้องการ Website, LINE, Facebook และ CRM',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'content' => '5. Testing & Fine-tuning',
                    'description' => 'ทดสอบกับ Scenarios จริง ปรับปรุงจนตอบได้แม่นยำ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '6. Launch & Monitor',
                    'description' => 'เปิดใช้งานและ Monitor Performance พร้อมปรับปรุงต่อเนื่อง',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get Sales Chatbot content
     */
    public static function getSalesContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'Chatbot ขายของอัจฉริยะ เพิ่มยอดขาย 24/7',
                ],
                [
                    'type' => 'text',
                    'content' => 'เปลี่ยน Website Visitors เป็นลูกค้าด้วย AI Sales Chatbot ที่แนะนำสินค้า ตอบคำถาม และปิดการขายได้เหมือนพนักงานขายมืออาชีพ',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'เพิ่มยอดขายอย่างไร?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'title' => 'Lead Capture 24/7',
                    'content' => 'จับ Leads ได้ตลอดเวลา แม้นอกเวลาทำการ ไม่พลาด Potential Customers',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'Personalized Recommendations',
                    'content' => 'แนะนำสินค้าที่ตรงใจลูกค้าแต่ละคน ตาม Behavior และ Preferences',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'title' => 'Qualify Leads อัตโนมัติ',
                    'content' => 'คัดกรอง Leads คุณภาพสูงส่งต่อให้ทีมขาย ประหยัดเวลาทีม',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ความสามารถการขาย',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'content' => 'Product Discovery',
                    'description' => 'ช่วยลูกค้าค้นหาสินค้าที่ต้องการ ถามตอบ Specs และ Compare Products',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'content' => 'Cart Recovery',
                    'description' => 'ติดตาม Abandoned Carts และ Re-engage ลูกค้าให้กลับมาซื้อ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'content' => 'Upsell & Cross-sell',
                    'description' => 'แนะนำสินค้าเพิ่มเติมที่เข้ากับสิ่งที่ลูกค้าสนใจ เพิ่ม Average Order Value',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'clock',
                    'iconColor' => '#0ea5e9',
                    'content' => 'Order Tracking',
                    'description' => 'ให้ลูกค้าตรวจสอบสถานะคำสั่งซื้อได้ทันที ลดคำถามซ้ำๆ',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'lg',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'เหมาะสำหรับ',
                ],
                [
                    'type' => 'list',
                    'style' => 'check',
                    'items' => [
                        'E-commerce Stores',
                        'B2B Companies ที่ต้องการ Lead Generation',
                        'Real Estate & Property Developers',
                        'Automotive Dealers',
                        'Insurance & Financial Products',
                        'Education & Course Providers',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'features_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ฟีเจอร์สำหรับการขาย',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#6366f1',
                    'title' => 'Product Catalog Integration',
                    'content' => 'เชื่อมต่อกับ Catalog สินค้า ค้นหาและแนะนำสินค้าได้ Real-time',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'users',
                    'iconColor' => '#ec4899',
                    'title' => 'Lead Scoring',
                    'content' => 'ให้คะแนน Leads ตามความพร้อมซื้อ ส่งต่อเฉพาะ Hot Leads ให้ทีมขาย',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#0ea5e9',
                    'title' => 'E-commerce Integration',
                    'content' => 'เชื่อมต่อ WooCommerce, Shopify, Magento สำหรับ Orders และ Inventory',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'title' => 'Conversion Tracking',
                    'content' => 'ติดตาม Conversion Rate, Revenue Generated และ ROI ของ Chatbot',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'fire',
                    'iconColor' => '#f59e0b',
                    'title' => 'Promotional Campaigns',
                    'content' => 'โปรโมท Deals และ Offers ผ่าน Chatbot พร้อม Coupon Distribution',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#8b5cf6',
                    'title' => 'Payment Integration',
                    'content' => 'รับชำระเงินผ่าน Chatbot ได้เลย Credit Cards, PromptPay, LINE Pay',
                    'style' => ['bgColor' => '#f5f3ff'],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'steps_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ขั้นตอนการพัฒนา Sales Bot',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'content' => '1. เข้าใจ Sales Process',
                    'description' => 'วิเคราะห์ Customer Journey, Pain Points และ Sales Funnel ของธุรกิจ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => '2. ออกแบบ Sales Flow',
                    'description' => 'วาง Conversation Flow ที่นำลูกค้าผ่าน Awareness, Consideration, Decision',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => '3. Connect Product Data',
                    'description' => 'เชื่อมต่อ Product Catalog, Pricing และ Inventory ให้ Bot เข้าถึงได้',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#10b981',
                    'content' => '4. Train Sales Scenarios',
                    'description' => 'สอน AI ด้วย Sales Scripts, Objection Handling และ Closing Techniques',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'content' => '5. A/B Testing',
                    'description' => 'ทดสอบ Messages และ Flows ต่างๆ เพื่อหา Version ที่ Convert สูงสุด',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '6. Launch & Optimize',
                    'description' => 'เปิดใช้งานและ Continuously Optimize ตาม Data และ Performance',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get Default Chatbot content
     */
    public static function getDefaultChatbotContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'AI Chatbot สำหรับธุรกิจยุคใหม่',
                ],
                [
                    'type' => 'text',
                    'content' => 'เปลี่ยนการสื่อสารกับลูกค้าด้วย AI Chatbot ที่เข้าใจภาษาธรรมชาติ ตอบได้ทันที และเรียนรู้จากทุกการสนทนา',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'clock',
                    'iconColor' => '#6366f1',
                    'title' => 'พร้อมบริการ 24/7',
                    'content' => 'ไม่พลาดทุกโอกาสทางธุรกิจ ตอบลูกค้าได้ตลอด 24 ชั่วโมง',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'AI เข้าใจภาษาไทย',
                    'content' => 'เทคโนโลยี NLU ที่เข้าใจภาษาไทยอย่างลึกซึ้ง รวมถึงภาษาพูดและคำสแลง',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'title' => 'รองรับทุก Channel',
                    'content' => 'Website, LINE, Facebook Messenger, WhatsApp และ Mobile App',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ประโยชน์ที่ได้รับ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'content' => 'ตอบทันที',
                    'description' => 'ลูกค้าได้รับคำตอบภายใน 3 วินาที เพิ่มความพึงพอใจ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'content' => 'ลดต้นทุน',
                    'description' => 'ลดภาระทีม Customer Service และค่าใช้จ่ายด้านบุคลากร',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#8b5cf6',
                    'content' => 'Scale ได้ไม่จำกัด',
                    'description' => 'รับมือลูกค้าหลายพันคนพร้อมกันได้โดยไม่ต้องเพิ่มคน',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
