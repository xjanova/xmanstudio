<?php

namespace Database\Seeders\Data;

class AppDevelopmentPageBuilderData
{
    /**
     * Get iOS App content
     */
    public static function getIOSAppContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'พัฒนาแอป iOS คุณภาพสูงระดับ App Store',
                ],
                [
                    'type' => 'text',
                    'content' => 'สร้างแอป iPhone และ iPad ที่ดูดี ใช้งานง่าย และผ่าน App Store Review ได้อย่างราบรื่น ด้วยทีมนักพัฒนาที่มีประสบการณ์พัฒนาแอปบน iOS มากกว่า 50 โปรเจค',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมต้องมีแอป iOS?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'trophy',
                    'iconColor' => '#f59e0b',
                    'title' => 'ลูกค้ากำลังซื้อสูง',
                    'content' => 'ผู้ใช้ iOS มีแนวโน้มใช้จ่ายในแอปสูงกว่า Android 2-3 เท่า เหมาะสำหรับแอปที่ต้องการ Revenue',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'ความน่าเชื่อถือสูง',
                    'content' => 'แอปบน App Store ผ่านการตรวจสอบคุณภาพ สร้างความเชื่อมั่นให้ลูกค้า',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#6366f1',
                    'title' => 'UX/UI ระดับพรีเมียม',
                    'content' => 'ออกแบบตาม Apple Human Interface Guidelines สวยงามและใช้งานง่าย',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ฟีเจอร์ที่รองรับ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#ec4899',
                    'content' => 'User Authentication',
                    'description' => 'Login/Register ด้วย Email, Phone, Apple ID, Google, Facebook และ Biometrics (Face ID/Touch ID)',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'content' => 'In-App Purchase',
                    'description' => 'ระบบชำระเงินในแอป Subscriptions, Consumables, Non-consumables ตาม Apple Guidelines',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'content' => 'Push Notifications',
                    'description' => 'แจ้งเตือนผู้ใช้แบบ Real-time รองรับ Rich Notifications, Silent Push และ Scheduled Push',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#8b5cf6',
                    'content' => 'Offline Support',
                    'description' => 'ใช้งานได้แม้ไม่มี Internet ด้วย Local Storage และ Sync เมื่อออนไลน์',
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
                        'Startups ที่ต้องการเข้าถึงลูกค้า Premium',
                        'ธุรกิจ B2C ที่ต้องการ Subscription Revenue',
                        'แบรนด์ที่ต้องการสร้าง Customer Loyalty',
                        'E-commerce ที่ต้องการ Mobile Commerce',
                        'Content Platforms และ Social Apps',
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
                    'title' => 'Native iOS App',
                    'content' => 'พัฒนาด้วย Swift/SwiftUI เพื่อ Performance สูงสุดบน iPhone และ iPad',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'UI/UX Design',
                    'content' => 'ออกแบบตาม Apple Human Interface Guidelines พร้อม Figma Files',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'title' => 'Backend API',
                    'content' => 'พัฒนา RESTful API สำหรับแอป พร้อม Database และ Cloud Hosting',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'App Store Submission',
                    'content' => 'ดูแลการ Submit แอปขึ้น App Store จนผ่าน Review สำเร็จ',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#f59e0b',
                    'title' => 'Analytics Integration',
                    'content' => 'ติดตั้ง Firebase Analytics, Crashlytics เพื่อติดตาม User Behavior',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'clock',
                    'iconColor' => '#8b5cf6',
                    'title' => '3 เดือน Support',
                    'content' => 'Bug Fixes, Minor Updates และ Technical Support หลัง Launch',
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
                    'content' => '1. Discovery & Planning',
                    'description' => 'วิเคราะห์ความต้องการ User Research กำหนด Features และวาง Technical Architecture',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => '2. UI/UX Design',
                    'description' => 'ออกแบบ Wireframes และ High-fidelity Mockups ตาม Apple Guidelines',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => '3. Development',
                    'description' => 'พัฒนา iOS App ด้วย Swift/SwiftUI และ Backend API พร้อมกัน',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'content' => '4. Testing',
                    'description' => 'ทดสอบบน TestFlight, QA Testing และ User Acceptance Testing',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '5. App Store Launch',
                    'description' => 'Submit App Store, ผ่าน Review และ Launch พร้อม Marketing Support',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get Android App content
     */
    public static function getAndroidAppContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'พัฒนาแอป Android เข้าถึงผู้ใช้หลายพันล้านคน',
                ],
                [
                    'type' => 'text',
                    'content' => 'สร้างแอป Android ที่ทำงานได้ดีบนมือถือทุกรุ่น ทุกยี่ห้อ เข้าถึงตลาดผู้ใช้ Android ที่ใหญ่ที่สุดในโลก โดยเฉพาะในเอเชียตะวันออกเฉียงใต้',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมต้องมีแอป Android?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#10b981',
                    'title' => 'Market Share สูงสุด',
                    'content' => 'Android ครองตลาด 70%+ ทั่วโลก และ 80%+ ในไทย เข้าถึงผู้ใช้ได้มากที่สุด',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'title' => 'เผยแพร่ได้เร็ว',
                    'content' => 'Google Play Review เร็วกว่า App Store อัปเดตแอปได้ภายในไม่กี่ชั่วโมง',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#6366f1',
                    'title' => 'ยืดหยุ่นกว่า',
                    'content' => 'เข้าถึง Device Features ได้มากกว่า รองรับ Sideloading และ Custom Distribution',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ฟีเจอร์ที่รองรับ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#ec4899',
                    'content' => 'User Authentication',
                    'description' => 'Login/Register ด้วย Email, Phone, Google, Facebook และ Biometrics (Fingerprint/Face)',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'content' => 'Google Play Billing',
                    'description' => 'ระบบชำระเงินผ่าน Google Play Subscriptions และ One-time Purchases',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'content' => 'Firebase Integration',
                    'description' => 'Push Notifications, Analytics, Crashlytics, Remote Config และ A/B Testing',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#8b5cf6',
                    'content' => 'Device Features',
                    'description' => 'Camera, GPS, NFC, Bluetooth, Sensors และ Background Services',
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
                        'ธุรกิจที่ต้องการเข้าถึงตลาด Mass Market',
                        'แอปที่ต้องการ User Base ขนาดใหญ่',
                        'Enterprise Apps สำหรับพนักงาน',
                        'IoT และ Hardware Integration Apps',
                        'แอปสำหรับตลาดเอเชียตะวันออกเฉียงใต้',
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
                    'title' => 'Native Android App',
                    'content' => 'พัฒนาด้วย Kotlin/Jetpack Compose เพื่อ Performance และ Modern UI',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'Material Design',
                    'content' => 'ออกแบบตาม Material Design 3 Guidelines สวยงามและ Consistent',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'title' => 'Backend API',
                    'content' => 'พัฒนา RESTful API สำหรับแอป พร้อม Database และ Cloud Hosting',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'Play Store Submission',
                    'content' => 'ดูแลการ Submit แอปขึ้น Google Play Store จนเผยแพร่สำเร็จ',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#f59e0b',
                    'title' => 'Firebase Suite',
                    'content' => 'Analytics, Crashlytics, Performance Monitoring, Remote Config',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'clock',
                    'iconColor' => '#8b5cf6',
                    'title' => '3 เดือน Support',
                    'content' => 'Bug Fixes, Minor Updates และ Technical Support หลัง Launch',
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
                    'content' => '1. Discovery & Planning',
                    'description' => 'วิเคราะห์ความต้องการ กำหนด Target Devices และวาง Technical Architecture',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => '2. UI/UX Design',
                    'description' => 'ออกแบบ Wireframes และ Mockups ตาม Material Design Guidelines',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => '3. Development',
                    'description' => 'พัฒนา Android App ด้วย Kotlin และ Backend API พร้อมกัน',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'content' => '4. Testing',
                    'description' => 'ทดสอบบน Devices หลากหลายรุ่น QA Testing และ Beta Testing',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '5. Play Store Launch',
                    'description' => 'Submit Google Play, ผ่าน Review และ Launch พร้อม ASO Optimization',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get Cross-Platform App content
     */
    public static function getCrossPlatformContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'แอป Cross-Platform: iOS + Android ในโค้ดเดียว',
                ],
                [
                    'type' => 'text',
                    'content' => 'พัฒนาแอปครั้งเดียว ใช้ได้ทั้ง iOS และ Android ประหยัดเวลาและค่าใช้จ่าย 40-60% เทียบกับการพัฒนาแยก ด้วย Flutter หรือ React Native',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมต้อง Cross-Platform?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'title' => 'ประหยัด 40-60%',
                    'content' => 'พัฒนาโค้ดเดียวใช้ได้สองแพลตฟอร์ม ลดเวลาและค่าใช้จ่ายอย่างมาก',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'title' => 'Time-to-Market เร็ว',
                    'content' => 'Launch iOS และ Android พร้อมกัน ไม่ต้องรอพัฒนาแยก',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#6366f1',
                    'title' => 'บำรุงรักษาง่าย',
                    'content' => 'แก้ไข Bug หรือเพิ่ม Feature ครั้งเดียว อัปเดตทั้งสองแพลตฟอร์ม',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'เทคโนโลยีที่ใช้',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => 'Flutter (Dart)',
                    'description' => 'Framework จาก Google ที่ให้ Performance ใกล้เคียง Native พร้อม Beautiful UI',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#61dafb',
                    'content' => 'React Native (JavaScript)',
                    'description' => 'Framework จาก Meta ที่มี Community ใหญ่ เหมาะกับทีมที่มี Web Background',
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
                        'Startups ที่มี Budget จำกัดแต่ต้องการทั้ง iOS และ Android',
                        'MVP ที่ต้องการทดสอบตลาดอย่างรวดเร็ว',
                        'Enterprise Apps ที่ต้องการ Consistent Experience',
                        'แอปที่ต้องการ Update บ่อย Feature เดียวกันทั้งสองแพลตฟอร์ม',
                        'ธุรกิจที่ต้องการเข้าถึงผู้ใช้ทั้ง iOS และ Android พร้อมกัน',
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
                    'title' => 'iOS + Android App',
                    'content' => 'แอปสำหรับทั้งสองแพลตฟอร์มจาก Codebase เดียวกัน',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'Platform-Specific UI',
                    'content' => 'UI ที่ปรับตาม Platform Guidelines ทั้ง Apple HIG และ Material Design',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'title' => 'Shared Backend',
                    'content' => 'Backend API เดียวที่รองรับทั้งสองแพลตฟอร์ม',
                    'style' => ['bgColor' => '#f0f9ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'Store Submission',
                    'content' => 'ดูแล Submit ทั้ง App Store และ Play Store จนเผยแพร่สำเร็จ',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#f59e0b',
                    'title' => 'Cross-Platform Analytics',
                    'content' => 'ติดตาม User Behavior บนทั้งสองแพลตฟอร์มใน Dashboard เดียว',
                    'style' => ['bgColor' => '#fffbeb'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'clock',
                    'iconColor' => '#8b5cf6',
                    'title' => '6 เดือน Support',
                    'content' => 'Bug Fixes, Updates และ Technical Support สำหรับทั้งสองแพลตฟอร์ม',
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
                    'content' => '1. Discovery & Tech Selection',
                    'description' => 'วิเคราะห์ความต้องการ เลือก Framework (Flutter/React Native) ที่เหมาะสม',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'content' => '2. UI/UX Design',
                    'description' => 'ออกแบบ UI ที่ Adaptive ตาม Platform พร้อม Design System',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'content' => '3. Development',
                    'description' => 'พัฒนา Cross-Platform App และ Shared Backend',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'content' => '4. Multi-Platform Testing',
                    'description' => 'ทดสอบบนทั้ง iOS และ Android หลากหลาย Devices',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'content' => '5. Dual Store Launch',
                    'description' => 'Submit App Store และ Play Store พร้อมกัน Launch ทั้งสองแพลตฟอร์ม',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get Default App Development content
     */
    public static function getDefaultAppContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'บริการพัฒนาแอปพลิเคชันมือถือครบวงจร',
                ],
                [
                    'type' => 'text',
                    'content' => 'สร้างแอป iOS, Android หรือทั้งสองแพลตฟอร์มด้วยทีมนักพัฒนามืออาชีพ ครอบคลุมตั้งแต่การออกแบบ พัฒนา ทดสอบ จนถึง Submit ขึ้น Store',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'code',
                    'iconColor' => '#6366f1',
                    'title' => 'Native & Cross-Platform',
                    'content' => 'เลือกได้ทั้ง Native iOS/Android หรือ Cross-Platform (Flutter/React Native)',
                    'style' => ['bgColor' => '#eef2ff'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'UI/UX Design',
                    'content' => 'ออกแบบตาม Platform Guidelines สวยงามและใช้งานง่าย',
                    'style' => ['bgColor' => '#fdf2f8'],
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'Store Submission',
                    'content' => 'ดูแลการ Submit ขึ้น App Store และ Play Store จนสำเร็จ',
                    'style' => ['bgColor' => '#ecfdf5'],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'บริการครอบคลุม',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'content' => 'iOS Development',
                    'description' => 'พัฒนาแอป iPhone และ iPad ด้วย Swift/SwiftUI',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#10b981',
                    'content' => 'Android Development',
                    'description' => 'พัฒนาแอป Android ด้วย Kotlin/Jetpack Compose',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'lightning',
                    'iconColor' => '#0ea5e9',
                    'content' => 'Cross-Platform',
                    'description' => 'พัฒนาแอปสองแพลตฟอร์มด้วย Flutter หรือ React Native',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'globe',
                    'iconColor' => '#f59e0b',
                    'content' => 'Backend Development',
                    'description' => 'พัฒนา API และ Cloud Infrastructure สำหรับแอป',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
