<?php

namespace Database\Seeders\Data;

class AIImagePageBuilderData
{
    /**
     * Get AI Product Photo content
     */
    public static function getProductPhotoContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ภาพสินค้าระดับมืออาชีพด้วย AI',
                ],
                [
                    'type' => 'text',
                    'content' => 'เปลี่ยนภาพสินค้าธรรมดาให้กลายเป็นภาพคุณภาพระดับ Studio ด้วยเทคโนโลยี AI ขั้นสูง เหมาะสำหรับ E-commerce, Social Media และการตลาดออนไลน์',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมภาพสินค้าคุณภาพสูงจึงสำคัญ?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'title' => 'เพิ่มยอดขาย 30-40%',
                    'content' => 'ภาพสินค้าที่สวยงามช่วยเพิ่ม Conversion Rate และลด Return Rate อย่างมีนัยสำคัญ',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'title' => 'ประหยัดค่าถ่ายภาพ 80%',
                    'content' => 'ไม่ต้องจ้าง Studio ถ่ายภาพ ไม่ต้องเช่าสถานที่ ไม่ต้องซื้อ Props AI สร้างได้หมด',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'clock',
                    'iconColor' => '#6366f1',
                    'title' => 'เสร็จใน 24-48 ชั่วโมง',
                    'content' => 'รับภาพสินค้าคุณภาพสูงได้เร็วกว่าการถ่ายภาพแบบดั้งเดิมหลายเท่า',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'บริการที่รวมอยู่',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => 'Background Removal & Replacement',
                    'description' => 'ลบพื้นหลังเดิมและใส่พื้นหลังใหม่ที่สวยงาม Studio-like หรือ Lifestyle Setting',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'content' => 'Color Correction & Enhancement',
                    'description' => 'ปรับสีให้ตรงกับสินค้าจริง เพิ่มความคมชัดและดึงดูด',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'content' => 'Shadow & Reflection',
                    'description' => 'เพิ่มเงาและ Reflection ที่ดูเป็นธรรมชาติ สร้างความลึกให้ภาพ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'content' => 'Multiple Angles',
                    'description' => 'สร้างภาพจากหลายมุมมองจากภาพเดียว 360 องศา',
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
                        'ร้านค้าออนไลน์ (Shopee, Lazada, Website)',
                        'แบรนด์สินค้าที่ต้องการภาพคุณภาพสูง',
                        'Dropshipping Business',
                        'Social Commerce (Facebook, Instagram)',
                        'Catalog และ Product Brochure',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'features_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'สิ่งที่คุณจะได้รับ',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'code',
                    'iconColor' => '#6366f1',
                    'title' => '10 ภาพต่อชุด',
                    'content' => 'ภาพสินค้าคุณภาพสูง 10 ภาพ ครอบคลุมหลายมุมมองและหลาย Setting',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'หลายพื้นหลัง',
                    'content' => 'เลือกพื้นหลังได้หลายแบบ White Background, Lifestyle, หรือ Custom Scene',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'title' => 'หลายขนาด',
                    'content' => 'รับไฟล์หลายขนาดสำหรับทุกแพลตฟอร์ม Square 1:1, Portrait 4:5, Landscape 16:9',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'ความละเอียดสูง',
                    'content' => 'ไฟล์ PNG/JPG ความละเอียด 4K+ พร้อมใช้งานทุก Platform',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'title' => 'แก้ไขฟรี 2 ครั้ง',
                    'content' => 'ปรับแต่งภาพได้ 2 รอบ ปรับสี พื้นหลัง หรือ Composition',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'title' => 'ส่งมอบไว 24-48 ชม.',
                    'content' => 'รับไฟล์ภาพภายใน 24-48 ชั่วโมง พร้อมใช้งานทันที',
                    'style' => ['bgColor' => '#f5f3ff'],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'steps_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ขั้นตอนการทำงาน',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'content' => '1. ส่งภาพสินค้าต้นฉบับ',
                    'description' => 'ส่งภาพสินค้าของคุณ ไม่ต้องกังวลเรื่องคุณภาพ AI จะจัดการให้',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => '2. เลือก Style ที่ต้องการ',
                    'description' => 'กำหนด Style พื้นหลัง Setting และ Mood ที่ต้องการ พร้อมตัวอย่าง Reference',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => '3. AI สร้างภาพ',
                    'description' => 'ระบบ AI ประมวลผลและสร้างภาพสินค้าคุณภาพสูงตาม Brief',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'content' => '4. ตรวจสอบและแก้ไข',
                    'description' => 'ตรวจสอบผลลัพธ์และแจ้งแก้ไขได้ 2 รอบจนพอใจ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '5. รับไฟล์',
                    'description' => 'ดาวน์โหลดไฟล์ภาพคุณภาพสูงทุกขนาดที่ต้องการ',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get AI Art Generation content
     */
    public static function getArtGenerationContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'สร้างงาน Art ไม่ซ้ำใครด้วย AI',
                ],
                [
                    'type' => 'text',
                    'content' => 'เปลี่ยนไอเดียของคุณให้เป็นผลงาน Art ที่สวยงามด้วย AI รองรับทุก Style ตั้งแต่ Realistic, Anime, Digital Art ไปจนถึง Fine Art เหมาะสำหรับ Marketing, Branding และ Content Creation',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ความสามารถของ AI Art',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'สร้างภาพจากข้อความ',
                    'content' => 'เพียงอธิบายสิ่งที่คุณต้องการ AI จะสร้างภาพตามจินตนาการ',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#6366f1',
                    'title' => 'ควบคุม Style ได้',
                    'content' => 'เลือก Art Style ได้หลากหลาย Oil Painting, Watercolor, 3D Render, Anime และอื่นๆ',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'ลิขสิทธิ์เต็มรูปแบบ',
                    'content' => 'ภาพที่สร้างเป็นของคุณ 100% ใช้เชิงพาณิชย์ได้ไม่จำกัด',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'Art Styles ที่รองรับ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'content' => 'Realistic & Photorealistic',
                    'description' => 'ภาพที่ดูเหมือนจริง สมจริงทุกรายละเอียด เหมาะสำหรับ Marketing',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'heart',
                    'iconColor' => '#ef4444',
                    'content' => 'Anime & Manga',
                    'description' => 'สไตล์ญี่ปุ่น Anime, Manga, Chibi และ Character Design',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#f59e0b',
                    'content' => 'Digital Art & Concept Art',
                    'description' => 'งาน Digital Art ทันสมัย Concept Art สำหรับเกมและภาพยนตร์',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => 'Fine Art & Classical',
                    'description' => 'Oil Painting, Watercolor, Impressionist และ Renaissance Style',
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
                        'Social Media Content & Marketing Materials',
                        'Book Covers & Album Artwork',
                        'Game Assets & Character Design',
                        'Website & App Illustrations',
                        'NFT Art & Digital Collectibles',
                        'Merchandise & Print-on-Demand',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'features_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'สิ่งที่คุณจะได้รับ',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'code',
                    'iconColor' => '#6366f1',
                    'title' => '5 Artworks',
                    'content' => 'ผลงาน Art คุณภาพสูง 5 ชิ้น ตาม Brief ที่กำหนด',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'หลาย Style',
                    'content' => 'เลือก Art Style ได้ตามต้องการ Realistic, Anime, Digital Art, Fine Art',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'title' => 'Resolution สูง',
                    'content' => 'ไฟล์ความละเอียดสูงถึง 4K พร้อมสำหรับ Print และ Digital',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'Commercial License',
                    'content' => 'ใช้งานเชิงพาณิชย์ได้ไม่จำกัด ไม่มี Royalty',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'title' => 'แก้ไขได้',
                    'content' => 'ปรับแต่งภาพได้จนกว่าจะพอใจ ทั้งสี Composition และ Style',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'title' => 'ส่งมอบ 3-5 วัน',
                    'content' => 'รับผลงาน Art ภายใน 3-5 วันทำการ',
                    'style' => ['bgColor' => '#f5f3ff'],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'steps_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ขั้นตอนการสร้าง Art',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'content' => '1. อธิบายไอเดีย',
                    'description' => 'บอกเล่าแนวคิดที่ต้องการ รวมถึงอารมณ์ สี และ Style ที่ชอบ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => '2. เลือก Art Style',
                    'description' => 'กำหนด Style ที่ต้องการ พร้อมส่ง Reference ภาพตัวอย่างที่ชอบ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => '3. AI สร้างผลงาน',
                    'description' => 'ระบบ AI สร้างภาพหลายเวอร์ชันตาม Brief ให้เลือก',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#10b981',
                    'content' => '4. ปรับแต่ง',
                    'description' => 'เลือกเวอร์ชันที่ชอบและปรับแต่งรายละเอียดจนสมบูรณ์',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '5. รับผลงาน',
                    'description' => 'ดาวน์โหลดผลงาน Art คุณภาพสูงพร้อมใช้งาน',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get Default AI Image content
     */
    public static function getDefaultImageContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'บริการสร้างและแก้ไขภาพด้วย AI',
                ],
                [
                    'type' => 'text',
                    'content' => 'เทคโนโลยี AI ล้ำสมัยที่จะช่วยสร้าง แก้ไข และปรับแต่งภาพได้อย่างมืออาชีพ ไม่ว่าจะเป็นภาพสินค้า งาน Art หรือภาพสำหรับ Marketing',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'AI สร้างภาพ',
                    'content' => 'สร้างภาพใหม่จากข้อความหรือแนวคิด ทุก Style ทุกรูปแบบ',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#6366f1',
                    'title' => 'AI แก้ไขภาพ',
                    'content' => 'ปรับแต่ง ลบ เปลี่ยนพื้นหลัง และ Enhance ภาพที่มีอยู่',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'ลิขสิทธิ์เต็มรูปแบบ',
                    'content' => 'ภาพทั้งหมดเป็นของคุณ 100% ใช้งานเชิงพาณิชย์ได้ไม่จำกัด',
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
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'content' => 'Text-to-Image',
                    'description' => 'สร้างภาพจากคำอธิบาย ตั้งแต่ไอเดียง่ายๆ จนถึงแนวคิดซับซ้อน',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'content' => 'Image Enhancement',
                    'description' => 'เพิ่มความละเอียด ปรับแสง สี และความคมชัดของภาพ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'content' => 'Background Edit',
                    'description' => 'ลบ เปลี่ยน หรือสร้างพื้นหลังใหม่ให้ภาพ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#0ea5e9',
                    'content' => 'Object Removal',
                    'description' => 'ลบวัตถุที่ไม่ต้องการออกจากภาพอย่างแนบเนียน',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
