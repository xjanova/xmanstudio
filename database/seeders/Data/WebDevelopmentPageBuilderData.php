<?php

namespace Database\Seeders\Data;

class WebDevelopmentPageBuilderData
{
    /**
     * Get Landing Page content in Page Builder JSON format
     */
    public static function getLandingPageContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'Landing Page ที่ออกแบบเพื่อ Conversion สูงสุด',
                ],
                [
                    'type' => 'text',
                    'content' => 'บริการพัฒนา Landing Page มืออาชีพที่จะเปลี่ยนผู้เข้าชมเว็บไซต์ให้กลายเป็นลูกค้าของคุณ ด้วยการออกแบบที่เน้น User Experience และหลักจิตวิทยาการตลาด',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมต้องใช้ Landing Page?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'bgColor' => '#ecfdf5',
                    'title' => 'Conversion Rate สูงขึ้น 2-5 เท่า',
                    'description' => 'Landing Page ที่ออกแบบอย่างดีสามารถเพิ่ม Conversion จาก 2-3% เป็น 10-30% เทียบกับเว็บไซต์ทั่วไป',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'bgColor' => '#fffbeb',
                    'title' => 'ROI จาก Ads สูงขึ้น 3-5 เท่า',
                    'description' => 'ลด Cost per Lead และเพิ่มผลตอบแทนจาก Google Ads, Facebook Ads ได้อย่างมีประสิทธิภาพ',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'bgColor' => '#f5f3ff',
                    'title' => 'เปิดตัวสินค้าได้รวดเร็ว',
                    'description' => 'พัฒนาเสร็จภายใน 7-10 วัน พร้อมใช้งานทันทีสำหรับ Campaign ของคุณ',
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'องค์ประกอบสำคัญที่รวมอยู่',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#7c3aed',
                    'title' => 'Hero Section',
                    'description' => 'ส่วนบนสุดที่ดึงดูดความสนใจ พร้อม Value Proposition ชัดเจน, Headline ที่โดนใจ และ CTA Button ที่เด่น',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'title' => 'Features & Benefits',
                    'description' => 'นำเสนอจุดเด่นและประโยชน์ที่ลูกค้าจะได้รับ ด้วย Icons และ Copy ที่กระชับ อ่านง่าย',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#3b82f6',
                    'title' => 'Social Proof',
                    'description' => 'รีวิวจากลูกค้าจริง, Testimonials, Case Studies, ตัวเลขความสำเร็จ และ Trust Badges',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'title' => 'Call-to-Action',
                    'description' => 'ปุ่มกระตุ้นการตัดสินใจที่วางตำแหน่งอย่างมีกลยุทธ์ตลอดหน้า',
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
                        'SaaS Companies - รับ Free Trial Signups หรือ Demo Requests',
                        'E-commerce - โปรโมทสินค้าใหม่หรือ Flash Sales',
                        'B2B Lead Generation - Agency, Consulting, Real Estate',
                        'Product Launches - สร้าง Hype และรับ Pre-orders',
                        'Event Registration - Webinar, Conference, Workshop',
                        'App Downloads - กระตุ้นให้ดาวน์โหลด iOS/Android Apps',
                    ],
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ตัวอย่างความสำเร็จระดับโลก',
                ],
                [
                    'type' => 'text',
                    'content' => 'Shopify Landing Pages มี Conversion Rate 12-15% (เทียบกับค่าเฉลี่ย 2-3%), Slack เติบโตจาก 0 เป็น 10 ล้าน Users ด้วย Landing Page ที่เรียบง่ายแต่ทรงพลัง, Dropbox ได้ 4 ล้าน Users ใน 15 เดือนด้วย Referral Landing Page',
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
                    'bgColor' => '#eef2ff',
                    'title' => '1-5 หน้าเว็บ',
                    'description' => 'ครอบคลุม Hero Section, Features, Testimonials, Pricing, FAQ และ Contact Form ออกแบบเพื่อ Conversion สูงสุด',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#0ea5e9',
                    'bgColor' => '#f0f9ff',
                    'title' => 'Responsive Design',
                    'description' => 'แสดงผลสวยงามบนทุกอุปกรณ์ Desktop, Tablet, Mobile ด้วยเทคนิค Mobile-first Design',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'bgColor' => '#ecfdf5',
                    'title' => 'SEO Optimization',
                    'description' => 'ปรับแต่ง On-page SEO ครบถ้วน Title Tags, Meta Descriptions, Schema Markup, Open Graph',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'bgColor' => '#fffbeb',
                    'title' => 'Fast Loading',
                    'description' => 'PageSpeed Score 90+ ด้วย Image Optimization, Lazy Loading, Minification และ CDN',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'users',
                    'iconColor' => '#ec4899',
                    'bgColor' => '#fdf2f8',
                    'title' => 'Lead Capture Form',
                    'description' => 'ฟอร์มเก็บข้อมูล พร้อม Validation, Anti-spam, Email Notification และเชื่อมต่อ CRM',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#14b8a6',
                    'bgColor' => '#f0fdfa',
                    'title' => 'Analytics Setup',
                    'description' => 'ติดตั้ง Google Analytics 4, Search Console, Facebook Pixel พร้อม Conversion Tracking',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'clock',
                    'iconColor' => '#8b5cf6',
                    'bgColor' => '#f5f3ff',
                    'title' => '3 เดือน Support',
                    'description' => 'Bug Fixes, Minor Updates, Performance Monitoring และ Technical Support ฟรี 3 เดือน',
                ],
            ], JSON_UNESCAPED_UNICODE),

            'steps_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ขั้นตอนการทำงาน',
                ],
                [
                    'type' => 'text',
                    'content' => 'กระบวนการพัฒนาที่เป็นระบบ รับประกันคุณภาพและส่งมอบตรงเวลา',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'title' => 'ขั้นตอนที่ 1: Brief & Discovery',
                    'description' => 'ประชุมทำความเข้าใจเป้าหมายธุรกิจ, กลุ่มเป้าหมาย, Value Proposition และ Success Metrics วิเคราะห์คู่แข่งและวางแผน User Journey',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => 'ขั้นตอนที่ 2: Design & Mockup',
                    'description' => 'ออกแบบ Wireframe และ High-fidelity Mockup ด้วย Figma ตามหลัก Conversion-focused Design พร้อม Revisions จนพอใจ',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'title' => 'ขั้นตอนที่ 3: Development',
                    'description' => 'พัฒนา Frontend ด้วย HTML5, TailwindCSS, JavaScript หรือ React/Next.js พร้อม Responsive Design และ Animations',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'globe',
                    'iconColor' => '#10b981',
                    'title' => 'ขั้นตอนที่ 4: Content & Media',
                    'description' => 'เพิ่ม Content, รูปภาพ, Videos ที่มีคุณภาพสูง Optimize ทุกอย่างเพื่อ Performance พร้อม Social Proof และ FAQs',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'title' => 'ขั้นตอนที่ 5: Testing & QA',
                    'description' => 'ทดสอบ Cross-browser, Cross-device, Forms, Performance (PageSpeed 90+), SEO และ Accessibility อย่างครอบคลุม',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'title' => 'ขั้นตอนที่ 6: Launch',
                    'description' => 'Deploy บน Hosting, ตั้งค่า Domain & SSL, ติดตั้ง Analytics & Tracking, Submit Sitemap และเปิดให้ใช้งาน',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get Corporate Website content in Page Builder JSON format
     */
    public static function getCorporateWebsiteContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'เว็บไซต์องค์กรมืออาชีพ สร้างความน่าเชื่อถือให้ธุรกิจ',
                ],
                [
                    'type' => 'text',
                    'content' => 'เว็บไซต์องค์กรคือหน้าตาออนไลน์ของบริษัท ที่ลูกค้า พาร์ทเนอร์ และนักลงทุนจะเห็นเป็นอันดับแรก การมีเว็บไซต์ที่ดูเป็นมืออาชีพช่วยสร้างความน่าเชื่อถือและเพิ่มโอกาสทางธุรกิจได้มากกว่า 80%',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'trophy',
                    'iconColor' => '#f59e0b',
                    'bgColor' => '#fffbeb',
                    'title' => 'สร้าง Brand Credibility',
                    'description' => '80% ของลูกค้าจะค้นหาข้อมูลบริษัทจาก Google ก่อนตัดสินใจติดต่อ เว็บไซต์ที่ดีสร้างความประทับใจแรกที่ยอดเยี่ยม',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'bgColor' => '#ecfdf5',
                    'title' => 'เพิ่ม Leads & Sales',
                    'description' => 'ช่องทางการติดต่อที่ชัดเจน พร้อมระบบ Contact Form ที่ส่งตรงถึงทีมขายของคุณ',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#3b82f6',
                    'bgColor' => '#eff6ff',
                    'title' => 'เข้าถึงได้ 24/7',
                    'description' => 'ลูกค้าสามารถเข้าชมข้อมูลบริษัท บริการ และผลงานได้ตลอด 24 ชั่วโมงจากทุกที่ในโลก',
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'โครงสร้างเว็บไซต์มาตรฐาน',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#6366f1',
                    'title' => 'Homepage',
                    'description' => 'หน้าแรกที่ดึงดูดและสื่อสาร Value Proposition ชัดเจน พร้อม Hero Section, Services Overview และ CTA',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#ec4899',
                    'title' => 'About Us',
                    'description' => 'เกี่ยวกับบริษัท ประวัติ Vision/Mission ทีมผู้บริหาร และวัฒนธรรมองค์กร',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#0ea5e9',
                    'title' => 'Services/Products',
                    'description' => 'หน้าบริการหรือสินค้าแยกละเอียด พร้อมรายละเอียดและราคา',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#8b5cf6',
                    'title' => 'Portfolio/Case Studies',
                    'description' => 'ผลงานและกรณีศึกษาที่แสดงความสามารถของบริษัท',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'title' => 'Blog/News',
                    'description' => 'บทความและข่าวสารเพื่อ SEO และ Content Marketing',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'title' => 'Contact',
                    'description' => 'หน้าติดต่อพร้อมแผนที่ ข้อมูลติดต่อ และ Contact Form',
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
                        'บริษัทขนาดกลาง-ใหญ่ที่ต้องการเว็บไซต์มืออาชีพ',
                        'Startups ที่ต้องการสร้าง Credibility',
                        'B2B Companies - Agency, Consulting, Law Firm',
                        'Manufacturing/Trading Companies',
                        'Service Companies - Real Estate, Insurance, Finance',
                        'Non-profit Organizations และ Educational Institutions',
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
                    'icon' => 'code',
                    'iconColor' => '#6366f1',
                    'bgColor' => '#eef2ff',
                    'title' => 'สูงสุด 15 หน้า',
                    'description' => 'Homepage, About Us, Services, Portfolio, Blog, Contact และหน้าอื่นๆ ตามต้องการ ครอบคลุมทุกส่วนสำคัญ',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'bgColor' => '#fdf2f8',
                    'title' => 'Custom Design',
                    'description' => 'ออกแบบเฉพาะตาม Brand Identity ไม่ใช่ Template สำเร็จรูป พร้อม Mockup Revisions จนพอใจ',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#0ea5e9',
                    'bgColor' => '#f0f9ff',
                    'title' => 'CMS Integration',
                    'description' => 'จัดการเนื้อหาเองได้ง่ายๆ ผ่าน WordPress, Laravel Nova หรือ Strapi โดยไม่ต้องเขียนโค้ด',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'fire',
                    'iconColor' => '#ef4444',
                    'bgColor' => '#fef2f2',
                    'title' => 'Blog System',
                    'description' => 'ระบบบล็อกครบครัน รองรับ Categories, Tags, Search, Comments และ Social Sharing เพื่อ SEO',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#10b981',
                    'bgColor' => '#ecfdf5',
                    'title' => 'Contact System',
                    'description' => 'ระบบติดต่อครบ Contact Form, Google Maps, Email Notification, Auto-reply และ CRM Integration',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'star',
                    'iconColor' => '#f59e0b',
                    'bgColor' => '#fffbeb',
                    'title' => 'Gallery/Portfolio',
                    'description' => 'แสดงผลงาน โปรเจค หรือสินค้า ด้วย Grid, Masonry, Lightbox และ Filter by Category',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#8b5cf6',
                    'bgColor' => '#f5f3ff',
                    'title' => 'Admin Panel',
                    'description' => 'แผงควบคุมที่ใช้งานง่าย จัดการเนื้อหา ดู Statistics และตั้งค่าเว็บไซต์ได้สะดวก',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'clock',
                    'iconColor' => '#14b8a6',
                    'bgColor' => '#f0fdfa',
                    'title' => '6 เดือน Support',
                    'description' => 'Bug Fixes, Content Updates, Technical Support, Security Updates และ Training Support ฟรี 6 เดือน',
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
                    'title' => '1. Discovery & Planning',
                    'description' => 'ทำความเข้าใจธุรกิจ, Target Audience, Competitors, Brand Identity และ Goals สร้าง Sitemap และ Content Strategy',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => '2. UI/UX Design',
                    'description' => 'ออกแบบ Wireframes และ High-fidelity Mockups ด้วย Figma ครอบคลุมทุกหน้า Desktop และ Mobile',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'title' => '3. Development',
                    'description' => 'พัฒนา Frontend และ Backend พร้อม CMS, Blog System, Portfolio, Contact Forms และ Admin Panel',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'globe',
                    'iconColor' => '#10b981',
                    'title' => '4. Content Integration',
                    'description' => 'เพิ่มเนื้อหา รูปภาพ วิดีโอ ทั้งหมด Optimize รูปภาพและทำ SEO สำหรับทุกหน้า',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'title' => '5. Testing & QA',
                    'description' => 'ทดสอบ Cross-browser, Cross-device, Forms, CMS, Performance และ Security อย่างครอบคลุม',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'title' => '6. Launch & Training',
                    'description' => 'Deploy, ติดตั้ง Analytics และจัดอบรมการใช้งาน CMS พร้อม Support ต่อเนื่อง 6 เดือน',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get E-commerce Website content in Page Builder JSON format
     */
    public static function getEcommerceContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ร้านค้าออนไลน์ครบวงจร พร้อมขายได้ทันที',
                ],
                [
                    'type' => 'text',
                    'content' => 'ตลาด E-commerce ในไทยเติบโตกว่า 20-30% ต่อปี การมีร้านค้าออนไลน์ของตัวเองช่วยให้คุณควบคุม Brand Experience เก็บข้อมูลลูกค้า และไม่ต้องแบ่งกำไรให้ Marketplace',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมต้องมีเว็บไซต์ E-commerce เอง?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'trophy',
                    'iconColor' => '#f59e0b',
                    'bgColor' => '#fffbeb',
                    'title' => 'ควบคุม Brand 100%',
                    'description' => 'ออกแบบประสบการณ์ช้อปปิ้งตาม Brand Identity ไม่ต้องอยู่ท่ามกลางคู่แข่งเหมือนใน Marketplace',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'bgColor' => '#ecfdf5',
                    'title' => 'ไม่ต้องจ่าย Commission',
                    'description' => 'Shopee/Lazada หัก 3-5% + ค่าโฆษณา เว็บของคุณจ่ายครั้งเดียวและเป็นของคุณตลอดไป',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'users',
                    'iconColor' => '#3b82f6',
                    'bgColor' => '#eff6ff',
                    'title' => 'เป็นเจ้าของ Customer Data',
                    'description' => 'เก็บข้อมูลลูกค้า ทำ Remarketing และสร้าง Loyalty Program ได้เต็มที่',
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
                    'icon' => 'cog',
                    'iconColor' => '#6366f1',
                    'title' => 'Product Management',
                    'description' => 'จัดการสินค้า หมวดหมู่ Variants รูปภาพ SKU สต็อก ราคา โปรโมชั่น และ Reviews',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'star',
                    'iconColor' => '#ec4899',
                    'title' => 'Shopping Cart & Checkout',
                    'description' => 'ตะกร้าสินค้าทันสมัย Guest Checkout, Cart Recovery และ Smooth Checkout Flow',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => 'Payment Gateway',
                    'description' => 'รองรับ Credit Cards, PromptPay, Installment, TrueMoney, LINE Pay และ COD',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#0ea5e9',
                    'title' => 'Shipping Integration',
                    'description' => 'เชื่อมต่อ Kerry, Flash, Thailand Post คำนวณค่าส่งอัตโนมัติ และ Print Labels',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'title' => 'Order Management',
                    'description' => 'จัดการคำสั่งซื้อ Order Status พิมพ์ใบเสร็จ Tracking และ Email Notifications',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'chart',
                    'iconColor' => '#8b5cf6',
                    'title' => 'Admin Dashboard',
                    'description' => 'สถิติยอดขาย Real-time, Reports และจัดการร้านค้าทั้งหมดในที่เดียว',
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
                        'ธุรกิจ Retail ที่ขายของออนไลน์',
                        'แบรนด์สินค้า - Fashion, Beauty, Electronics',
                        'Manufacturers ที่ต้องการขาย Direct-to-Consumer',
                        'Dropshipping Businesses',
                        'Startups ที่ต้องการ Test Product-Market Fit',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'features_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ฟีเจอร์ E-commerce ครบครัน',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#6366f1',
                    'bgColor' => '#eef2ff',
                    'title' => 'Product Management',
                    'description' => 'จัดการสินค้าไม่จำกัด Categories, Variants, SKU, Inventory, Reviews พร้อม Bulk Import/Export',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'star',
                    'iconColor' => '#ec4899',
                    'bgColor' => '#fdf2f8',
                    'title' => 'Shopping Cart',
                    'description' => 'ตะกร้าสินค้าทันสมัย Mini Cart, Guest Checkout, Save for Later และ Cart Recovery',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'bgColor' => '#ecfdf5',
                    'title' => 'Payment Gateway',
                    'description' => 'เชื่อมต่อ Omise/2C2P รองรับ Credit Cards, PromptPay, Installment, TrueMoney, COD',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'check-circle',
                    'iconColor' => '#f59e0b',
                    'bgColor' => '#fffbeb',
                    'title' => 'Order Management',
                    'description' => 'จัดการคำสั่งซื้อ Order Status, พิมพ์ใบเสร็จ/ใบกำกับภาษี, Tracking, Refunds',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#0ea5e9',
                    'bgColor' => '#f0f9ff',
                    'title' => 'Inventory System',
                    'description' => 'ติดตาม Stock Real-time, Low Stock Alerts, Multiple Warehouses และ Batch Tracking',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'rocket',
                    'iconColor' => '#ef4444',
                    'bgColor' => '#fef2f2',
                    'title' => 'Shipping Integration',
                    'description' => 'เชื่อมต่อ Kerry, Flash, Thailand Post คำนวณค่าส่ง Print Labels และ Tracking',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'users',
                    'iconColor' => '#8b5cf6',
                    'bgColor' => '#f5f3ff',
                    'title' => 'Customer Accounts',
                    'description' => 'ระบบสมาชิก Order History, Wishlist, Address Book, Reorder และ Loyalty Points',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#14b8a6',
                    'bgColor' => '#f0fdfa',
                    'title' => 'Admin Dashboard',
                    'description' => 'สถิติยอดขาย Real-time, Revenue Reports, Top Products และจัดการร้านค้าทั้งหมด',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'clock',
                    'iconColor' => '#64748b',
                    'bgColor' => '#f8fafc',
                    'title' => '1 ปี Support',
                    'description' => 'Bug Fixes, Feature Updates, Technical Support, Security Updates และ Marketing Consulting',
                ],
            ], JSON_UNESCAPED_UNICODE),

            'steps_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'ขั้นตอนการพัฒนา E-commerce',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'title' => '1. Planning & Requirements',
                    'description' => 'ทำความเข้าใจธุรกิจ สินค้า Target Customers กำหนด Features และเลือก Platform (WooCommerce/Laravel)',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#ec4899',
                    'title' => '2. UI/UX Design',
                    'description' => 'ออกแบบ Homepage, Product Pages, Cart, Checkout ด้วย Mobile-first Approach เน้น Conversion',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'title' => '3. Development',
                    'description' => 'พัฒนา Frontend/Backend, Product Management, Cart, Checkout, Customer Accounts และ Admin Dashboard',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'title' => '4. Payment & Shipping',
                    'description' => 'เชื่อมต่อ Payment Gateways และ Shipping APIs ทดสอบ Flow ทั้งหมดบน Test Mode',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#f59e0b',
                    'title' => '5. Products & Content',
                    'description' => 'เพิ่มสินค้า หมวดหมู่ รูปภาพ และเนื้อหาทั้งหมด Optimize รูปสินค้าและทำ SEO',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#8b5cf6',
                    'title' => '6. Testing',
                    'description' => 'ทดสอบ Shopping Flow, Payment, Shipping, Admin Functions และ Performance อย่างครอบคลุม',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#ef4444',
                    'title' => '7. Launch & Training',
                    'description' => 'Deploy, ติดตั้ง Analytics/Tracking, อบรมการใช้งาน และ Support ต่อเนื่อง 1 ปี',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Get Custom Web Application content in Page Builder JSON format
     */
    public static function getCustomWebAppContent(): array
    {
        return [
            'long_description_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'เว็บแอปพลิเคชันที่สร้างเฉพาะสำหรับธุรกิจของคุณ',
                ],
                [
                    'type' => 'text',
                    'content' => 'เมื่อ Software สำเร็จรูปไม่สามารถตอบโจทย์ Business Workflow ที่ซับซ้อนหรือเฉพาะเจาะจง Custom Web Application คือคำตอบ ออกแบบและพัฒนาให้ Fit กับกระบวนการทำงานของคุณพอดี',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'md',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ทำไมต้อง Custom Development?',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'cog',
                    'iconColor' => '#6366f1',
                    'bgColor' => '#eef2ff',
                    'title' => 'Fit กับธุรกิจ 100%',
                    'description' => 'ไม่ต้องปรับ Workflow ให้เข้ากับ Software แต่ Software ปรับตาม Workflow ของคุณ',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'chart',
                    'iconColor' => '#10b981',
                    'bgColor' => '#ecfdf5',
                    'title' => 'Scalable & Future-proof',
                    'description' => 'ออกแบบให้รองรับการเติบโตในอนาคต เพิ่ม Features ได้ไม่จำกัด',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#ef4444',
                    'bgColor' => '#fef2f2',
                    'title' => 'เป็นเจ้าของ 100%',
                    'description' => 'ไม่มี License Fee รายเดือน/รายปี Source Code เป็นของคุณตลอดไป',
                ],
                [
                    'type' => 'divider',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'ประเภทระบบที่เราพัฒนา',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'title' => 'CRM Systems',
                    'description' => 'จัดการลูกค้า ติดตาม Leads, Sales Pipeline, Customer Support และ Analytics',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#ec4899',
                    'title' => 'ERP Systems',
                    'description' => 'จัดการทรัพยากรองค์กร Inventory, Accounting, HR, Procurement และ Production',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'clock',
                    'iconColor' => '#0ea5e9',
                    'title' => 'Booking Systems',
                    'description' => 'ระบบจอง ห้องพัก ห้องประชุม คิว บริการ พร้อม Calendar และ Payment',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#10b981',
                    'title' => 'Project Management',
                    'description' => 'จัดการโปรเจค Tasks, Timelines, Resources, Collaboration และ Reporting',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'chart',
                    'iconColor' => '#f59e0b',
                    'title' => 'Data Dashboards',
                    'description' => 'Visualize ข้อมูล Real-time Dashboards, Reports และ Business Intelligence',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'lightning',
                    'iconColor' => '#8b5cf6',
                    'title' => 'Workflow Automation',
                    'description' => 'Automate กระบวนการทำงานซ้ำๆ ลดเวลาและ Human Error',
                ],
                [
                    'type' => 'spacer',
                    'size' => 'lg',
                ],
                [
                    'type' => 'heading',
                    'level' => 'h3',
                    'content' => 'เทคโนโลยีที่ใช้',
                ],
                [
                    'type' => 'list',
                    'style' => 'bullet',
                    'items' => [
                        'Backend: Laravel, Node.js, Python Django/FastAPI',
                        'Frontend: React.js, Vue.js, Angular, Next.js',
                        'Database: PostgreSQL, MySQL, MongoDB, Redis',
                        'Cloud: AWS, Google Cloud, Azure, DigitalOcean',
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),

            'features_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'สิ่งที่รวมอยู่ในบริการ',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'sparkles',
                    'iconColor' => '#6366f1',
                    'bgColor' => '#eef2ff',
                    'title' => 'Custom Functionality',
                    'description' => 'ฟังก์ชันที่ออกแบบตามความต้องการเฉพาะของธุรกิจ ไม่ว่าจะซับซ้อนแค่ไหน',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'bgColor' => '#f0f9ff',
                    'title' => 'Database Design',
                    'description' => 'ออกแบบ Database Schema ที่ Normalized, Scalable และ Optimized สำหรับ Performance',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'shield',
                    'iconColor' => '#10b981',
                    'bgColor' => '#ecfdf5',
                    'title' => 'Authentication & Security',
                    'description' => 'ระบบ Login ที่ปลอดภัย รองรับ Social Login, 2FA, SSO พร้อม Security Best Practices',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'users',
                    'iconColor' => '#ec4899',
                    'bgColor' => '#fdf2f8',
                    'title' => 'Role-based Access',
                    'description' => 'กำหนดสิทธิ์การเข้าถึงตาม Roles (Admin, Manager, Staff, User) อย่างละเอียด',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'globe',
                    'iconColor' => '#f59e0b',
                    'bgColor' => '#fffbeb',
                    'title' => 'API Development',
                    'description' => 'พัฒนา RESTful/GraphQL APIs สำหรับเชื่อมต่อกับระบบอื่นหรือสร้าง Mobile App',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'lightning',
                    'iconColor' => '#ef4444',
                    'bgColor' => '#fef2f2',
                    'title' => 'Third-party Integration',
                    'description' => 'เชื่อมต่อ Payment, Email, SMS, Cloud Storage, Maps, CRM และระบบภายนอกอื่นๆ',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'rocket',
                    'iconColor' => '#8b5cf6',
                    'bgColor' => '#f5f3ff',
                    'title' => 'Scalable Architecture',
                    'description' => 'ออกแบบให้รองรับ Traffic สูงและ Concurrent Users จำนวนมาก พร้อมเติบโตในอนาคต',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'check-circle',
                    'iconColor' => '#14b8a6',
                    'bgColor' => '#f0fdfa',
                    'title' => 'Documentation',
                    'description' => 'เอกสารครบถ้วน System Architecture, API Docs, User Manual และ Technical Docs',
                ],
                [
                    'type' => 'feature-card',
                    'icon' => 'clock',
                    'iconColor' => '#64748b',
                    'bgColor' => '#f8fafc',
                    'title' => '1 ปี Support',
                    'description' => 'Bug Fixes, Feature Enhancements, Technical Support, Performance และ Security Updates',
                ],
            ], JSON_UNESCAPED_UNICODE),

            'steps_th' => json_encode([
                [
                    'type' => 'heading',
                    'level' => 'h2',
                    'content' => 'กระบวนการพัฒนา Custom Application',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'users',
                    'iconColor' => '#6366f1',
                    'title' => '1. Requirements Analysis',
                    'description' => 'วิเคราะห์ความต้องการอย่างละเอียด Business Requirements, User Stories, Use Cases และ Technical Constraints',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'cog',
                    'iconColor' => '#ec4899',
                    'title' => '2. System Architecture',
                    'description' => 'ออกแบบ Architecture, Technology Stack, Security และ Scalability Strategy',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'code',
                    'iconColor' => '#0ea5e9',
                    'title' => '3. Database Design',
                    'description' => 'ออกแบบ Database Schema, Relationships, Indexes และ Data Migration Strategy',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'sparkles',
                    'iconColor' => '#10b981',
                    'title' => '4. UI/UX Design',
                    'description' => 'ออกแบบ Wireframes และ Mockups ที่ User-friendly และตอบโจทย์ Business Goals',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'lightning',
                    'iconColor' => '#f59e0b',
                    'title' => '5. Development',
                    'description' => 'พัฒนา Backend APIs, Frontend, Admin Panel และ Integrations ด้วย Agile Methodology',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'check-circle',
                    'iconColor' => '#8b5cf6',
                    'title' => '6. Testing & QA',
                    'description' => 'ทดสอบ Unit Tests, Integration Tests, E2E Tests, Performance และ Security Testing',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'rocket',
                    'iconColor' => '#ef4444',
                    'title' => '7. Deployment',
                    'description' => 'Deploy บน Production, ตั้งค่า CI/CD, Monitoring และ Backup Strategy',
                ],
                [
                    'type' => 'icon-box',
                    'icon' => 'shield',
                    'iconColor' => '#14b8a6',
                    'title' => '8. Training & Support',
                    'description' => 'อบรมผู้ใช้งาน สร้าง Documentation และ Support ต่อเนื่อง 1 ปี',
                ],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
