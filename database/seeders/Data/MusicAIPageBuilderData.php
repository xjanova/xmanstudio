<?php

namespace Database\Seeders\Data;

class MusicAIPageBuilderData
{
    /**
     * Get AI Background Music content
     */
    public static function getBackgroundMusicContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'เพลงประกอบ AI คุณภาพระดับมืออาชีพ',
                ],
                [
                    'type' => 'text',
                    'content' => 'บริการสร้างเพลงประกอบด้วยเทคโนโลยี AI ขั้นสูง เหมาะสำหรับ YouTuber ครีเอเตอร์ และธุรกิจที่ต้องการเพลงประกอบไม่ซ้ำใคร ไม่ต้องกังวลเรื่องลิขสิทธิ์',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมต้องใช้ AI สร้างเพลง?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'title' => 'รวดเร็วทันใจ',
                    'content' => 'สร้างเพลงคุณภาพสูงได้ภายใน 3-5 วันทำการ เร็วกว่าจ้างนักแต่งเพลง 10 เท่า',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'ลิขสิทธิ์เต็มรูปแบบ',
                    'content' => 'เป็นเจ้าของลิขสิทธิ์ 100% ใช้งานเชิงพาณิชย์ได้ไม่จำกัด ไม่มีค่า Royalty',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#6366f1',
                    'title' => 'คุ้มค่าคุ้มราคา',
                    'content' => 'ราคาเริ่มต้นเพียง 50,000 บาท ประหยัดกว่าจ้างนักดนตรีมืออาชีพหลายเท่า',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'แนวเพลงที่รองรับ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#ec4899',
                    'content' => 'Pop & Electronic',
                    'description' => 'เพลงป๊อปสดใส Electronic Dance Music (EDM) และ Synth Wave ทันสมัย',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'heart',
                    'iconColor' => '#ef4444',
                    'content' => 'Cinematic & Orchestral',
                    'description' => 'เพลงประกอบภาพยนตร์ Trailer Epic Score และวงออร์เคสตราครบวง',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#8b5cf6',
                    'content' => 'Jazz & Acoustic',
                    'description' => 'Jazz ผ่อนคลาย Acoustic Guitar และเพลงบรรยากาศร้านกาแฟ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#f59e0b',
                    'content' => 'Rock & Hip-Hop',
                    'description' => 'Rock เร้าใจ Hip-Hop Beat และ Trap Music สำหรับคอนเทนต์ทันสมัย',
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
                        'YouTuber และ Content Creator ที่ต้องการเพลงประกอบวิดีโอ',
                        'ธุรกิจที่ต้องการเพลงสำหรับโฆษณาและ Marketing',
                        'Podcast และ Audio Content',
                        'วิดีโอ Corporate และ Presentation',
                        'เกมและแอปพลิเคชัน',
                        'Social Media Content (TikTok, Instagram Reels)',
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
                    'icon' => 'clock',
                    'iconColor' => '#6366f1',
                    'title' => 'ความยาว 3-5 นาที',
                    'content' => 'เพลงความยาว 3-5 นาทีต่อแทร็ก เหมาะกับวิดีโอสั้นถึงปานกลาง',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'หลากหลายแนวเพลง',
                    'content' => 'เลือกแนวเพลงได้ไม่จำกัด Pop, Rock, Jazz, Classical, Electronic, Cinematic และอื่นๆ',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'ลิขสิทธิ์เชิงพาณิชย์',
                    'content' => 'ใช้งานได้ไม่จำกัดตลอดชีพ เหมาะสำหรับโฆษณา YouTube และทุกแพลตฟอร์ม',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'title' => 'ไฟล์คุณภาพสูง',
                    'content' => 'MP3 320kbps สำหรับใช้งานทั่วไป และ WAV สำหรับงานมาสเตอร์คุณภาพสูง',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'title' => 'แก้ไขฟรี 2 ครั้ง',
                    'content' => 'ปรับแต่งเพลงได้ 2 รอบจนกว่าจะพอใจ 100%',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'title' => 'ส่งมอบไว 3-5 วัน',
                    'content' => 'เหมาะสำหรับโปรเจคที่รีบด่วน ส่งมอบไวทันใจ',
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
                    'content' => '1. ปรึกษาและวางแผน',
                    'description' => 'พูดคุยเพื่อทำความเข้าใจโปรเจค กำหนดแนวเพลง อารมณ์ที่ต้องการ จังหวะ (BPM) และวัตถุประสงค์การใช้งาน',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => '2. สร้างเพลงด้วย AI',
                    'description' => 'ใช้เทคโนโลยี AI ขั้นสูงสร้างเพลง 3-5 เวอร์ชัน แต่ละเวอร์ชันมีความแตกต่างกันเพื่อให้มีทางเลือก',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'content' => '3. เลือกเวอร์ชัน',
                    'description' => 'ส่งเพลงตัวอย่างให้ฟังและเลือก สามารถเลือก 1 เวอร์ชันหรือผสมผสานไอเดียจากหลายเวอร์ชัน',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#f59e0b',
                    'content' => '4. ปรับแต่งให้สมบูรณ์',
                    'description' => 'ปรับแต่งตามฟีดแบค เช่น ปรับจังหวะ เปลี่ยนเครื่องดนตรี หรือปรับอารมณ์เพลง แก้ไขได้ 2 รอบ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '5. ส่งมอบไฟล์',
                    'description' => 'ส่งมอบไฟล์เพลงใน MP3 และ WAV พร้อมเอกสารลิขสิทธิ์ ดาวน์โหลดได้ทันที',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get Custom AI Music content
     */
    public static function getCustomMusicContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'สร้างเพลง AI แบบกำหนดเองทุกรายละเอียด',
                ],
                [
                    'type' => 'text',
                    'content' => 'บริการสร้างเพลงที่ปรับแต่งได้ทุกรายละเอียด ควบคุมทุกองค์ประกอบของเพลงได้เอง ตั้งแต่เครื่องดนตรี แนวเพลง จังหวะ จนถึงความยาว เหมาะสำหรับแบรนด์และธุรกิจที่ต้องการเอกลักษณ์ทางดนตรีเฉพาะตัว',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ความสามารถพิเศษ',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#6366f1',
                    'title' => 'ควบคุมได้ทุกรายละเอียด',
                    'content' => 'เลือกเครื่องดนตรีแต่ละชิ้น ผสมผสานแนวเพลง กำหนด BPM และ Key ได้อย่างอิสระ',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'Cross-Genre Fusion',
                    'content' => 'ผสมผสานแนวเพลงได้อย่างไร้ขีดจำกัด เช่น Jazz + Electronic หรือ Rock + Orchestra',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'title' => 'แก้ไขไม่จำกัด',
                    'content' => 'ปรับแต่งได้ไม่จำกัดจำนวนครั้งจนกว่าจะได้เพลงที่สมบูรณ์แบบ 100%',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'เครื่องดนตรีที่รองรับ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'content' => 'เครื่องสาย',
                    'description' => 'เปียโน กีตาร์อะคูสติก กีตาร์ไฟฟ้า ไวโอลิน เชลโล่ ฮาร์ป และเบส',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'content' => 'เครื่องเป่า',
                    'description' => 'แซ็กโซโฟน ทรัมเป็ต ฟลุต คลาริเน็ต และเครื่องเป่าทองเหลืองครบวง',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'content' => 'เครื่องกระทบ',
                    'description' => 'กลองชุด เพอร์คัชชัน ทิมปานี ไซโลโฟน และเครื่องกระทบอิเล็กทรอนิกส์',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => 'อิเล็กทรอนิกส์',
                    'description' => 'ซินธ์ไซเซอร์ ดรัมแมชชีน แพด และเอฟเฟกต์เสียงทันสมัย',
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
                        'แบรนด์ที่ต้องการเพลงประจำแบรนด์ (Brand Anthem)',
                        'นักพัฒนาเกมที่ต้องการ Soundtrack เฉพาะตัว',
                        'ภาพยนตร์และซีรีส์ที่ต้องการเพลงประกอบ Original',
                        'ธุรกิจที่ต้องการเอกลักษณ์ทางดนตรีเป็นของตัวเอง',
                        'โปรเจคที่ต้องการคุณภาพระดับสากล',
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
                    'icon' => 'clock',
                    'iconColor' => '#6366f1',
                    'title' => 'ความยาวสูงสุด 10 นาที',
                    'content' => 'กำหนดความยาวเพลงได้เองตามต้องการ เหมาะกับทุกโปรเจค',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'เลือกเครื่องดนตรี 100+ ชนิด',
                    'content' => 'ตั้งแต่เปียโน ไวโอลิน กีตาร์ ไปจนถึงซินธ์ไซเซอร์ทันสมัย',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#0ea5e9',
                    'title' => 'ควบคุม Tempo & Key',
                    'content' => 'กำหนดจังหวะ (BPM) และคีย์เพลงได้อย่างละเอียด',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'code',
                    'iconColor' => '#10b981',
                    'title' => 'ไฟล์หลายรูปแบบ',
                    'content' => 'MP3, WAV, FLAC และ MIDI เหมาะสำหรับทุกการใช้งาน',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'title' => 'แก้ไขไม่จำกัด',
                    'content' => 'ปรับแต่งได้ไม่จำกัดจำนวนครั้งจนกว่าจะพอใจ',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'title' => 'Source Files',
                    'content' => 'ไฟล์ต้นฉบับพร้อมแทร็กแยกเครื่องดนตรีแต่ละชิ้น',
                    'style' => ['bgColor' => '#f5f3ff'],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'steps_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'กระบวนการพัฒนา',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'content' => '1. รับฟังความต้องการ',
                    'description' => 'ประชุมเพื่อพูดคุยแนวคิดโปรเจค ศึกษา Reference เพลงที่ชอบ และวิเคราะห์ความต้องการ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => '2. พัฒนาแนวคิดเพลง',
                    'description' => 'สร้าง Music Blueprint พร้อม Mood Board กำหนดโครงสร้างเพลง เลือกเครื่องดนตรี',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => '3. สร้างเพลง AI',
                    'description' => 'ใช้ระบบ AI สร้างเพลง 5-8 เวอร์ชัน แต่ละเวอร์ชันมีความแตกต่างในด้านการเรียบเรียง',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#10b981',
                    'content' => '4. ปรับแต่งซ้ำ',
                    'description' => 'รับฟีดแบคและปรับแต่ง เช่น เปลี่ยนเครื่องดนตรี ปรับจังหวะ ทำซ้ำได้ไม่จำกัด',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'content' => '5. มาสเตอร์',
                    'description' => 'ปรับสมดุลเสียง EQ Compression และ Limiting ให้คุณภาพระดับสากล',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '6. ส่งมอบ',
                    'description' => 'ส่งมอบไฟล์ทุกรูปแบบ พร้อมไฟล์ต้นฉบับแยกแทร็ก เอกสารลิขสิทธิ์ และคู่มือการใช้งาน',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get default content for other music options
     */
    public static function getDefaultMusicContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'บริการสร้างเพลงด้วย AI คุณภาพสูง',
                ],
                [
                    'type' => 'text',
                    'content' => 'เทคโนโลยี AI ล้ำสมัยที่จะเปลี่ยนไอเดียของคุณให้เป็นเพลงคุณภาพระดับมืออาชีพ ไม่ว่าจะเป็นเพลงประกอบ เพลงโฆษณา หรือเพลงสำหรับโปรเจคพิเศษ',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'title' => 'เทคโนโลยี AI ขั้นสูง',
                    'content' => 'ระบบ AI ที่ผ่านการเทรนจากเพลงนับล้านเพลงทั่วโลก สร้างเพลงคุณภาพเทียบเท่านักแต่งเพลงมืออาชีพ',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'ลิขสิทธิ์เต็มรูปแบบ',
                    'content' => 'เป็นเจ้าของลิขสิทธิ์ 100% ใช้งานเชิงพาณิชย์ได้ไม่จำกัด ไม่มีค่า Royalty',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'rocket',
                    'iconColor' => '#6366f1',
                    'title' => 'ส่งมอบรวดเร็ว',
                    'content' => 'ส่งมอบเพลงคุณภาพสูงได้ภายในไม่กี่วัน พร้อมไฟล์หลายรูปแบบ',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมต้องเลือกเรา',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'content' => 'คุณภาพระดับสากล',
                    'description' => 'เพลงทุกเพลงผ่านการตรวจสอบคุณภาพโดยมืออาชีพ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'content' => 'แก้ไขจนพอใจ',
                    'description' => 'ปรับแต่งเพลงได้จนกว่าจะพอใจ 100%',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#0ea5e9',
                    'content' => 'ทีมงานมืออาชีพ',
                    'description' => 'ดูแลโดยทีมที่มีประสบการณ์ด้านดนตรีและเทคโนโลยี',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
