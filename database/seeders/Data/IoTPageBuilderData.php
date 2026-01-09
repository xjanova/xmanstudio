<?php

namespace Database\Seeders\Data;

class IoTPageBuilderData
{
    /**
     * Get Smart Home content
     */
    public static function getSmartHomeContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ระบบบ้านอัจฉริยะ Smart Home ครบวงจร',
                ],
                [
                    'type' => 'text',
                    'content' => 'เปลี่ยนบ้านของคุณให้เป็นบ้านอัจฉริยะที่ควบคุมได้จากทุกที่ผ่านสมาร์ทโฟน ประหยัดพลังงาน เพิ่มความปลอดภัย และยกระดับคุณภาพชีวิต',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมต้อง Smart Home?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'title' => 'ประหยัดพลังงาน 20-40%',
                    'content' => 'ระบบจัดการพลังงานอัจฉริยะ ปิดไฟ-แอร์อัตโนมัติ ลดค่าไฟได้จริง',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'ปลอดภัยมากขึ้น',
                    'content' => 'กล้องวงจรปิด เซ็นเซอร์เปิด-ปิดประตู แจ้งเตือนเมื่อมีความผิดปกติ',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#6366f1',
                    'title' => 'ควบคุมง่ายจากทุกที่',
                    'content' => 'ควบคุมบ้านผ่าน App, สั่งเสียงด้วย Google Home หรือ Alexa',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ระบบที่รวมอยู่',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'content' => 'Lighting Control',
                    'description' => 'ควบคุมไฟทั้งบ้าน หรี่แสง เปลี่ยนสี ตั้งเวลา และ Scene ตามกิจกรรม',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#0ea5e9',
                    'content' => 'Climate Control',
                    'description' => 'ควบคุมแอร์ พัดลม และระบบระบายอากาศ ปรับอุณหภูมิอัตโนมัติ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'content' => 'Security System',
                    'description' => 'กล้องวงจรปิด ประตูล็อคอัจฉริยะ เซ็นเซอร์ความเคลื่อนไหว และ Alarm',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'content' => 'Entertainment',
                    'description' => 'ระบบเสียง Multi-room, Smart TV และ Home Theater Control',
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
                        'บ้านพักอาศัย คอนโด และ Townhouse',
                        'โรงแรมและ Airbnb',
                        'สำนักงานและพื้นที่ Co-working',
                        'โครงการอสังหาริมทรัพย์',
                        'รีสอร์ทและ Hospitality',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'features_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ฟีเจอร์ Smart Home',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#6366f1',
                    'title' => 'Mobile App Control',
                    'content' => 'ควบคุมทุกอุปกรณ์ผ่าน App บน iOS และ Android ได้จากทุกที่ในโลก',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'users',
                    'iconColor' => '#ec4899',
                    'title' => 'Voice Control',
                    'content' => 'สั่งงานด้วยเสียงผ่าน Google Home, Amazon Alexa หรือ Siri',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'clock',
                    'iconColor' => '#0ea5e9',
                    'title' => 'Automation',
                    'content' => 'ตั้งเวลาและ Triggers อัตโนมัติ เช่น ปิดไฟเมื่อออกจากบ้าน',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'title' => 'Energy Monitoring',
                    'content' => 'ดูการใช้พลังงาน Real-time และรายงานประจำเดือน',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#f59e0b',
                    'title' => 'Security Alerts',
                    'content' => 'แจ้งเตือนทันทีเมื่อมีการเคลื่อนไหวผิดปกติหรือเปิดประตู',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'fire',
                    'iconColor' => '#8b5cf6',
                    'title' => 'Scenes & Moods',
                    'content' => 'สร้าง Scene เช่น "Movie Night" "Wake Up" หรือ "Leaving Home"',
                    'style' => ['bgColor' => '#f5f3ff'],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'steps_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ขั้นตอนการติดตั้ง',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'content' => '1. Survey & Design',
                    'description' => 'สำรวจบ้านและวิเคราะห์ความต้องการ ออกแบบระบบที่เหมาะสม',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#ec4899',
                    'content' => '2. อุปกรณ์และ Hardware',
                    'description' => 'เลือกอุปกรณ์ที่เหมาะสม Hub, Sensors, Smart Switches และอื่นๆ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => '3. Installation',
                    'description' => 'ติดตั้งอุปกรณ์โดยทีมช่างผู้ชำนาญ ไม่กระทบระบบไฟเดิม',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#10b981',
                    'content' => '4. Programming',
                    'description' => 'ตั้งค่าระบบ Scenes Automation และเชื่อมต่อกับ Voice Assistants',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'content' => '5. Training',
                    'description' => 'สอนการใช้งาน App และระบบต่างๆ ให้ทุกคนในบ้าน',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '6. After-Sales Support',
                    'description' => 'รับประกัน 1 ปี Support ตลอดอายุการใช้งาน',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get Industrial IoT content
     */
    public static function getIndustrialIoTContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ระบบ Industrial IoT (IIoT) สำหรับโรงงานอัจฉริยะ',
                ],
                [
                    'type' => 'text',
                    'content' => 'ยกระดับโรงงานสู่ Industry 4.0 ด้วยระบบ IoT ที่เชื่อมต่อเครื่องจักร ติดตาม Production Real-time ทำ Predictive Maintenance และลดต้นทุนการผลิต',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ประโยชน์ของ IIoT',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'title' => 'เพิ่ม OEE 15-25%',
                    'content' => 'Overall Equipment Effectiveness สูงขึ้นจากการ Monitor และ Optimize เครื่องจักร',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#6366f1',
                    'title' => 'ลด Downtime 30-50%',
                    'content' => 'Predictive Maintenance แจ้งเตือนก่อนเครื่องจักรเสียหาย',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'title' => 'ประหยัดพลังงาน 20%',
                    'content' => 'วิเคราะห์และ Optimize การใช้พลังงานทั้งโรงงาน',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ความสามารถของระบบ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => 'Machine Connectivity',
                    'description' => 'เชื่อมต่อเครื่องจักรทุกประเภท PLC, SCADA, OPC-UA และ Modbus',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'content' => 'Real-time Monitoring',
                    'description' => 'Dashboard แสดง Production, Quality และ Machine Status แบบ Real-time',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => 'Predictive Maintenance',
                    'description' => 'AI วิเคราะห์ Pattern และทำนายการเสียหายของเครื่องจักร',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'shield',
                    'iconColor' => '#ef4444',
                    'content' => 'Quality Control',
                    'description' => 'ตรวจสอบคุณภาพอัตโนมัติ AI Visual Inspection และ SPC',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'lg',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'อุตสาหกรรมที่เราดูแล',
                ],
                [
                    'type' => 'list',
                    'style' => 'check',
                    'items' => [
                        'อุตสาหกรรมยานยนต์และชิ้นส่วน',
                        'อิเล็กทรอนิกส์และเซมิคอนดักเตอร์',
                        'อาหารและเครื่องดื่ม',
                        'Pharmaceutical และ Medical Devices',
                        'พลาสติก ยาง และปิโตรเคมี',
                        'Logistics และ Warehouse',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'features_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ฟีเจอร์ระบบ IIoT',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'code',
                    'iconColor' => '#6366f1',
                    'title' => 'Data Collection',
                    'content' => 'เก็บข้อมูลจากเครื่องจักร Sensors และ ERP แบบ Real-time',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'title' => 'Analytics Dashboard',
                    'content' => 'OEE, Downtime Analysis, Production Trends และ KPI Tracking',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'AI & Machine Learning',
                    'content' => 'Anomaly Detection, Predictive Models และ Process Optimization',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#0ea5e9',
                    'title' => 'ERP Integration',
                    'content' => 'เชื่อมต่อ SAP, Oracle, Microsoft Dynamics และ MES',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#f59e0b',
                    'title' => 'Alerting System',
                    'content' => 'แจ้งเตือน LINE, Email, SMS เมื่อมี Anomaly หรือ Threshold Breach',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#8b5cf6',
                    'title' => 'Remote Monitoring',
                    'content' => 'ดู Dashboard ได้จากทุกที่ผ่าน Web และ Mobile App',
                    'style' => ['bgColor' => '#f5f3ff'],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'steps_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'กระบวนการ Implementation',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'content' => '1. Assessment',
                    'description' => 'สำรวจโรงงาน วิเคราะห์ Pain Points และกำหนด KPIs',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => '2. Architecture Design',
                    'description' => 'ออกแบบ System Architecture, Connectivity และ Data Flow',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#0ea5e9',
                    'content' => '3. Hardware Setup',
                    'description' => 'ติดตั้ง Edge Devices, Gateways และ Sensors',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#10b981',
                    'content' => '4. Software Development',
                    'description' => 'พัฒนา Platform, Dashboard, Analytics และ AI Models',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'content' => '5. Integration & Testing',
                    'description' => 'เชื่อมต่อระบบ ทดสอบ และ Calibrate จนทำงานเสถียร',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '6. Go-Live & Support',
                    'description' => 'เปิดใช้งาน อบรมพนักงาน และ Support ต่อเนื่อง',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get Default IoT content
     */
    public static function getDefaultIoTContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'บริการพัฒนาระบบ IoT ครบวงจร',
                ],
                [
                    'type' => 'text',
                    'content' => 'เปลี่ยนทุกอย่างให้ Smart ด้วยเทคโนโลยี IoT ตั้งแต่ Smart Home, Smart Building ไปจนถึง Industrial IoT (IIoT) ด้วยทีมผู้เชี่ยวชาญ',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#6366f1',
                    'title' => 'Connected Devices',
                    'content' => 'เชื่อมต่ออุปกรณ์ทุกประเภทเข้าสู่ระบบ Cloud ควบคุมได้จากทุกที่',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'title' => 'Data Analytics',
                    'content' => 'วิเคราะห์ข้อมูลจาก Sensors เพื่อ Insights และ Optimization',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'Automation',
                    'content' => 'ระบบทำงานอัตโนมัติตาม Rules และ AI Predictions',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'บริการของเรา',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'content' => 'Smart Home & Building',
                    'description' => 'ระบบบ้านและอาคารอัจฉริยะ ควบคุมไฟ แอร์ ความปลอดภัย',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#0ea5e9',
                    'content' => 'Industrial IoT',
                    'description' => 'ระบบโรงงานอัจฉริยะ Machine Monitoring และ Predictive Maintenance',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'content' => 'Asset Tracking',
                    'description' => 'ติดตามตำแหน่งและสถานะของ Assets ด้วย GPS และ BLE',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'content' => 'Energy Management',
                    'description' => 'Monitor และ Optimize การใช้พลังงานแบบ Real-time',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
