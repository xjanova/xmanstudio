<?php

namespace App\Support;

/**
 * What the home page says, in one place.
 *
 * Two home pages carry the same content: the Nova theme's (layouts/nova + partials/nova-*) and
 * the 3D XMAN Universe (home-universe + partials/universe/*), which is served instead of it to
 * browsers that can run WebGL — see UniverseHome. Every list either page loops over lives here,
 * so an edit to a service, a stat or a menu entry reaches both.
 *
 * The platform cards are the exception on the Nova side: nova-ecosystem.blade.php is written
 * out by hand, one <article> per platform, and only the universe reads platforms() below.
 * UniverseHomeTest checks that every link on the Nova home also exists on the universe home,
 * so a link added to one and not the other fails the build.
 */
class HomeContent
{
    /**
     * The primary menu: Nova's orbital star menu and the universe's ring menu.
     *
     * `accent` drives the item's glow, `art` names artwork/menu/labelled/{art}.webp and `icon`
     * a partials/nova-icon glyph. The rest is shown only by the universe's ring menu, under
     * the card in front: `about_*` a one-line pitch, `desc_*` the plain-words explanation
     * (the owner: this panel is where the selling happens) and `points` three highlights.
     * Every claim here is one the site already makes on the destination page.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function menu(): array
    {
        return [
            ['th' => 'หน้าหลัก', 'en' => 'Home', 'href' => url('/'), 'accent' => '#22d3ee', 'icon' => 'home', 'art' => 'home',
                'about_th' => 'กลับสู่แกนกลางจักรวาล XMAN', 'about_en' => 'Back to the XMAN core',
                'desc_th' => 'จุดเริ่มต้นของจักรวาล XMAN Studio ที่รวมทุกอย่างที่เราทำไว้ในที่เดียว ตั้งแต่รับทำระบบตามสั่ง ซอฟต์แวร์พร้อมใช้ ไปจนถึงโดเมนและเซิร์ฟเวอร์ เลื่อนดูไปเรื่อย ๆ แล้วเลือกสิ่งที่ใช่สำหรับคุณได้เลย',
                'desc_en' => 'Where the XMAN universe begins: everything we make, in one place.',
                'points' => ['บริการครบวงจร', 'ทำงานมาตั้งแต่ปี 2018', 'ปรึกษาฟรี']],
            ['th' => 'บริการ', 'en' => 'Services', 'href' => route('services.index'), 'accent' => '#8b5cf6', 'icon' => 'grid', 'art' => 'services',
                'about_th' => 'โซลูชัน IT ครบวงจร ตั้งแต่เว็บจนถึง Blockchain', 'about_en' => 'End-to-end IT, from web to blockchain',
                'desc_th' => 'มีระบบในใจแต่ไม่รู้จะเริ่มตรงไหน? เรารับทำให้ครบ ทั้งเว็บไซต์ แอปมือถือ ระบบ AI บล็อกเชน IoT และความปลอดภัยเครือข่าย ทีมเราฟังโจทย์ วางแผนให้ชัด ทำจนใช้งานได้จริง และดูแลต่อให้หลังส่งมอบ',
                'desc_en' => 'Websites, apps, AI, blockchain, IoT and security, built to your brief and looked after.',
                'points' => ['เว็บ · แอป · AI', 'ส่งมอบตรงเวลา', 'ดูแลหลังส่งมอบ']],
            ['th' => 'ผลิตภัณฑ์', 'en' => 'Products', 'href' => config('app.product_site_url'), 'accent' => '#e879f9', 'icon' => 'cube', 'art' => 'products',
                'about_th' => 'ซอฟต์แวร์พร้อมใช้ ไลเซนส์แท้ อัปเดตต่อเนื่อง', 'about_en' => 'Ready-made software, licensed and updated',
                'desc_th' => 'ไม่อยากรอพัฒนาใหม่? เลือกซอฟต์แวร์สำเร็จรูปที่เราสร้างและดูแลเองได้เลย ซื้อแล้วติดตั้งใช้งานได้ทันที มีไลเซนส์ถูกต้อง ได้อัปเดตฟีเจอร์ใหม่ต่อเนื่อง และมีทีมคอยช่วยเมื่อติดปัญหา',
                'desc_en' => 'Software we build and maintain ourselves: install and go, with updates and support.',
                'points' => ['ใช้งานได้ทันที', 'อัปเดตต่อเนื่อง', 'มีทีมช่วยเหลือ']],
            ['th' => 'เช่าใช้งาน', 'en' => 'Rental', 'href' => route('rental.index'), 'accent' => '#34d399', 'icon' => 'clock', 'art' => 'rental',
                'about_th' => 'เช่าระบบใช้งาน ไม่ต้องลงทุนก้อนใหญ่', 'about_en' => 'Rent the system instead of buying it',
                'desc_th' => 'อยากใช้ระบบดี ๆ แต่ยังไม่อยากลงทุนก้อนใหญ่? เลือกเช่าได้ตามขนาดงาน มีแพ็กเกจเริ่มต้น มืออาชีพ และองค์กร อัปเกรดได้ตลอดเวลา จ่ายง่ายผ่านพร้อมเพย์ โอนเงิน หรือบัตร และระบบจะเตือนก่อนหมดอายุ 7 วัน',
                'desc_en' => 'Starter, pro and enterprise plans; upgrade any time; pay by PromptPay, transfer or card.',
                'points' => ['3 แพ็กเกจให้เลือก', 'อัปเกรดได้ทุกเมื่อ', 'จ่ายผ่านพร้อมเพย์ได้']],
            ['th' => 'จดโดเมน', 'en' => 'Domains', 'href' => route('domains.index'), 'accent' => '#60a5fa', 'icon' => 'globe', 'art' => 'domains',
                'about_th' => 'จดโดเมนกว่า 400 นามสกุล ราคาเป็นบาท', 'about_en' => '400+ extensions, priced in baht',
                'desc_th' => 'จองชื่อเว็บไซต์ของคุณก่อนใครจะเอาไป ค้นหาได้กว่า 400 นามสกุล ราคาแสดงเป็นเงินบาทชัดเจนไม่มีค่าแอบแฝง จดเป็นชื่อของคุณเอง ย้ายออกได้ทุกเมื่อ และตั้งค่า DNS เองได้ง่าย ๆ จากหลังบ้าน',
                'desc_en' => 'Over 400 extensions, clear prices in baht, registered in your name, DNS you control.',
                'points' => ['400+ นามสกุล', 'ราคาเป็นบาท ไม่มีแอบแฝง', 'จัดการ DNS เองได้']],
            ['th' => 'เช่า VPS', 'en' => 'VPS', 'href' => route('vps.index'), 'accent' => '#2dd4bf', 'icon' => 'server', 'art' => 'vps',
                'about_th' => 'เซิร์ฟเวอร์ส่วนตัว root เต็มสิทธิ์', 'about_en' => 'Your own server with full root',
                'desc_th' => 'เซิร์ฟเวอร์ส่วนตัวที่คุณคุมได้ทั้งหมดด้วยสิทธิ์ root เต็ม ดิสก์ NVMe SSD เร็วแรง สำรองข้อมูลให้ทุกสัปดาห์ เลือกระบบปฏิบัติการหรือแอปพร้อมใช้ได้กว่า 90 แบบ เปิด-ปิด ติดตั้งใหม่ และชี้โดเมนได้เองจากหน้าเว็บ',
                'desc_en' => 'Full root, NVMe SSD, weekly backups and 90+ ready-made systems, managed from the web.',
                'points' => ['root เต็มสิทธิ์', 'NVMe SSD', 'สำรองข้อมูลทุกสัปดาห์']],
            ['th' => 'สร้างภาพ AI', 'en' => 'XDreamer', 'href' => config('services.aixman.site_url'), 'accent' => '#f472b6', 'icon' => 'spark', 'art' => 'xdreamer',
                'about_th' => 'สตูดิโอสร้างภาพและวิดีโอด้วย AI', 'about_en' => 'AI image and video studio',
                'desc_th' => 'แค่พิมพ์สิ่งที่อยากเห็น AI ก็วาดภาพหรือทำวิดีโอให้ภายในไม่กี่วินาที เลือกสไตล์ได้ตามใจ ปรับแต่งต่อได้ แล้วดาวน์โหลดไปใช้ได้ทันที มีแกลเลอรีผลงานจากผู้ใช้จริงไว้เป็นแรงบันดาลใจ',
                'desc_en' => 'Type what you imagine and get images or videos in seconds, in the style you choose.',
                'points' => ['ภาพและวิดีโอ AI', 'เลือกสไตล์ได้', 'ดาวน์โหลดได้ทันที']],
            ['th' => 'เรียนโค้ด', 'en' => 'Academy', 'href' => route('code-academy'), 'accent' => '#38bdf8', 'icon' => 'book', 'art' => 'academy',
                'about_th' => 'ตัวอย่างโค้ดคุณภาพ เรียนฟรี 100%', 'about_en' => 'Quality code examples, free',
                'desc_th' => 'อยากเขียนโปรแกรมเป็น เริ่มที่นี่ได้เลยฟรี ๆ รวมตัวอย่างโค้ดคุณภาพกว่า 50 ตัวอย่าง ทั้ง Laravel, PHP, JavaScript, Python, Flutter, SQL, Git และ Docker อธิบายเข้าใจง่าย เปิดอ่านได้เลยไม่ต้องสมัครสมาชิก',
                'desc_en' => '50+ quality code examples across eight technologies, free and without sign-up.',
                'points' => ['ฟรี 100%', '50+ ตัวอย่าง', 'ไม่ต้องสมัคร']],
            ['th' => 'เพลง', 'en' => 'Metal-X', 'href' => route('metal-x.index'), 'accent' => '#fb7185', 'icon' => 'play', 'art' => 'metalx',
                'about_th' => 'ช่องเพลงและ MV ที่เราผลิตเอง', 'about_en' => 'Our own music channel and videos',
                'desc_th' => 'ช่องเพลงและมิวสิกวิดีโอที่ทีมเราทำเองทั้งหมด ตั้งแต่แต่งเพลงด้วย AI ทำภาพ จนถึงตัดต่อและปล่อยขึ้น YouTube มาฟังเพลงใหม่ ดูเบื้องหลัง และรู้จักทีมงานได้ที่นี่',
                'desc_en' => 'Songs and music videos we make ourselves, from AI songwriting to YouTube.',
                'points' => ['เพลงใหม่เสมอ', 'ผลิตด้วย AI', 'ดูได้บน YouTube']],
            ['th' => 'ติดต่อเรา', 'en' => 'Contact', 'href' => route('quote.index'), 'accent' => '#ffd479', 'icon' => 'chat', 'art' => 'contact',
                'about_th' => 'ปรึกษาฟรี ขอใบเสนอราคาได้ทันที', 'about_en' => 'Free consultation and quotes',
                'desc_th' => 'เล่าไอเดียหรือปัญหาของคุณให้เราฟัง ทีมผู้เชี่ยวชาญจะช่วยคิด วางแผน และประเมินราคาให้ฟรี ก่อนเริ่มงานจริงคุณจะเห็นขอบเขตงานและค่าใช้จ่ายชัดเจนทุกบาท',
                'desc_en' => 'Tell us your idea: free planning and a clear quote before any work begins.',
                'points' => ['ปรึกษาฟรี', 'ใบเสนอราคาชัดเจน', 'ทีมพร้อมช่วย 24/7']],
        ];
    }

    /**
     * The headline numbers. `value` is what the counters count up to; the number shown is
     * value . suffix ("24" . "/7").
     *
     * @return array<int, array{value: int, suffix: string, th: string, en: string}>
     */
    public static function stats(): array
    {
        return [
            ['value' => 150, 'suffix' => '+', 'th' => 'โปรเจคสำเร็จ', 'en' => 'Projects'],
            ['value' => 50, 'suffix' => '+', 'th' => 'ลูกค้าพึงพอใจ', 'en' => 'Clients'],
            ['value' => 8, 'suffix' => '+', 'th' => 'ปีประสบการณ์', 'en' => 'Years'],
            ['value' => 24, 'suffix' => '/7', 'th' => 'บริการตลอดเวลา', 'en' => 'Support'],
        ];
    }

    /**
     * "Why us" points.
     *
     * @return array<int, array<string, string>>
     */
    public static function why(): array
    {
        return [
            ['icon' => 'shield', 'accent' => '#34d399', 'th' => 'คุณภาพระดับสากล', 'en' => 'International standards',
                'body' => 'พัฒนาตามมาตรฐาน International Best Practice'],
            ['icon' => 'clock', 'accent' => '#22d3ee', 'th' => 'ส่งมอบตรงเวลา', 'en' => 'On-time delivery',
                'body' => 'บริหารโปรเจคด้วยระบบ Agile ส่งมอบงานตามกำหนด'],
            ['icon' => 'chat', 'accent' => '#8b5cf6', 'th' => 'ซัพพอร์ตตลอด 24/7', 'en' => 'Round-the-clock support',
                'body' => 'ทีมซัพพอร์ตพร้อมช่วยเหลือทุกเวลา'],
            ['icon' => 'wrench', 'accent' => '#ffd479', 'th' => 'ดูแลต่อเนื่องหลังส่งมอบ', 'en' => 'Ongoing maintenance',
                'body' => 'อัปเดต แก้บั๊ก และปรับปรุงระบบให้ทันสมัยอยู่เสมอ'],
        ];
    }

    /**
     * The eight services. Every card links to /services, the detail hub.
     *
     * @return array<int, array<string, string|null>>
     */
    public static function services(): array
    {
        return [
            [
                'th' => 'Blockchain Development', 'en' => 'Smart contracts · DeFi · NFT',
                'body' => 'พัฒนาโซลูชั่น Blockchain, Smart Contracts, DeFi และ NFT Marketplace',
                'icon' => 'chain', 'accent' => '#22d3ee', 'badge' => 'ยอดนิยม',
                'art' => 'card-blockchain',
            ],
            [
                'th' => 'Web Development', 'en' => 'Responsive · Modern stack',
                'body' => 'ออกแบบและพัฒนาเว็บไซต์สมัยใหม่ Responsive รองรับทุกอุปกรณ์',
                'icon' => 'globe', 'accent' => '#38bdf8', 'badge' => null,
                'art' => 'card-web',
            ],
            [
                'th' => 'Mobile Application', 'en' => 'iOS · Android',
                'body' => 'พัฒนาแอพ iOS และ Android ด้วย Flutter และ React Native',
                'icon' => 'mobile', 'accent' => '#34d399', 'badge' => null,
                'art' => 'card-mobile',
            ],
            [
                'th' => 'AI Solutions', 'en' => 'Generative AI · Chatbot',
                'body' => 'วีดีโอ AI, เพลง AI, Chatbot และบริการ Generative AI',
                'icon' => 'spark', 'accent' => '#e879f9', 'badge' => 'ใหม่',
                'art' => 'card-ai',
            ],
            [
                'th' => 'IoT Solutions', 'en' => 'Internet of Things',
                'body' => 'ออกแบบและพัฒนาระบบ Internet of Things ครบวงจร',
                'icon' => 'bolt', 'accent' => '#fb923c', 'badge' => null,
                'art' => 'card-iot',
            ],
            [
                'th' => 'Network & IT Security', 'en' => 'Firewall · Pentest',
                'body' => 'ออกแบบ ติดตั้งระบบ Network, Firewall และทดสอบเจาะระบบ',
                'icon' => 'shield', 'accent' => '#fb7185', 'badge' => null,
                'art' => 'card-security',
            ],
            [
                'th' => 'Custom Software', 'en' => 'ERP · CRM · Inventory',
                'body' => 'พัฒนาซอฟต์แวร์เฉพาะ ERP, CRM และระบบจัดการสินค้าคงคลัง',
                'icon' => 'layers', 'accent' => '#8b5cf6', 'badge' => null,
                'art' => 'card-software',
            ],
            [
                'th' => 'Flutter & Android Studio', 'en' => 'Cross-platform · Training',
                'body' => 'พัฒนาแอพ Cross-platform ด้วย Flutter และอบรมการใช้งาน',
                'icon' => 'code', 'accent' => '#0ea5e9', 'badge' => 'Flutter',
                'art' => 'card-flutter',
            ],
        ];
    }

    /**
     * The tech stack: [label, logo URL, invert-on-dark]. Devicons come from the jsDelivr CDN,
     * as they always have; the AI models' logos are kept in public_html/artwork/tech
     * (from the MIT-licensed @lobehub/icons-static-svg set, one-colour ones made white).
     *
     * @return array<int, array{0: string, 1: string, 2: bool}>
     */
    public static function tech(): array
    {
        $dev = fn (string $label, string $icon, bool $invert = false) => [$label, 'https://cdn.jsdelivr.net/gh/devicons/devicon/icons/' . $icon . '.svg', $invert];

        return [
            $dev('React', 'react/react-original'),
            $dev('Flutter', 'flutter/flutter-original'),
            $dev('Laravel', 'laravel/laravel-original'),
            $dev('Node.js', 'nodejs/nodejs-original'),
            $dev('Python', 'python/python-original'),
            $dev('AWS', 'amazonwebservices/amazonwebservices-plain-wordmark', true),
            $dev('PHP', 'php/php-original'),
            $dev('TypeScript', 'typescript/typescript-original'),
            $dev('Docker', 'docker/docker-original'),
            $dev('PostgreSQL', 'postgresql/postgresql-original'),
            $dev('Tailwind', 'tailwindcss/tailwindcss-original'),
            $dev('Solidity', 'solidity/solidity-original'),
        ];
    }

    /**
     * The AI models and tools we build with — shown beside the stack. Same shape as tech().
     *
     * @return array<int, array{0: string, 1: string, 2: bool}>
     */
    public static function aiTech(): array
    {
        $ai = fn (string $label, string $file) => [$label, asset('artwork/tech/' . $file . '.svg'), false];

        return [
            $ai('Claude', 'claude-color'),
            $ai('OpenAI', 'openai'),
            $ai('Gemini', 'gemini-color'),
            $ai('Llama', 'meta-color'),
            $ai('DeepSeek', 'deepseek-color'),
            $ai('Qwen', 'qwen-color'),
            $ai('Mistral', 'mistral-color'),
            $ai('Grok', 'grok'),
            $ai('Hugging Face', 'huggingface-color'),
            $ai('ComfyUI', 'comfyui-color'),
            $ai('Midjourney', 'midjourney'),
            $ai('Suno', 'suno'),
        ];
    }

    /**
     * A product card's picture on the universe home: the key art drawn for it
     * (public_html/artwork/universe/products/{slug}.webp) when there is one, else the product's own.
     */
    public static function productArt(object $product): ?string
    {
        $slug = (string) ($product->slug ?? '');
        $file = 'artwork/universe/products/' . $slug . '.webp';

        if (preg_match('/^[a-z0-9-]+$/', $slug) === 1 && is_file(public_path($file))) {
            return asset($file);
        }

        return $product->artwork_url ?: null;
    }

    /**
     * The platforms we run ourselves, one planet each in the universe. Same copy and links as
     * the cards in nova-ecosystem.blade.php.
     *
     * Link `kind`: primary / ghost are buttons, sub is a small text link, note is plain text.
     * `auth` links are only shown to a signed-in visitor; `external` ones open in a new tab.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function platforms(): array
    {
        // Taken as-is, the way nova-ecosystem prints them, so both pages carry identical hrefs.
        $ai = (string) config('services.aixman.site_url');
        $productSite = (string) config('app.product_site_url');

        return [
            [
                'key' => 'domains', 'accent' => '#60a5fa', 'badge' => 'ใหม่', 'icon' => 'globe',
                'title' => 'จดโดเมน / Domains', 'en' => 'Register & manage your own domain',
                'body' => 'จดโดเมนกว่า 400 นามสกุล ราคาชัดเจนเป็นบาท ไม่มีค่าซ่อน จดในชื่อคุณเอง ย้ายออกได้ทุกเมื่อ และแก้ DNS เองได้จากหลังบ้าน',
                'links' => [
                    ['kind' => 'primary', 'label' => 'ค้นหาชื่อโดเมน', 'href' => route('domains.index')],
                    ['kind' => 'ghost', 'label' => 'ดูราคาทุกนามสกุล', 'href' => route('domains.pricing')],
                ],
            ],
            [
                'key' => 'vps', 'accent' => '#2dd4bf', 'badge' => 'ใหม่', 'icon' => 'server',
                'title' => 'เช่า VPS / Cloud servers', 'en' => 'Your own server, ready in minutes',
                'body' => 'เซิร์ฟเวอร์ส่วนตัว root เต็มสิทธิ์ NVMe SSD สำรองข้อมูลทุกสัปดาห์ เลือก OS หรือแอปพร้อมใช้ได้กว่า 90 แบบ เปิด-ปิด ติดตั้งใหม่ และชี้โดเมนได้เองจากหน้าเว็บ',
                'links' => [
                    ['kind' => 'primary', 'label' => 'ดูแพ็กเกจ VPS', 'href' => route('vps.index')],
                    ['kind' => 'ghost', 'label' => 'VPS ของฉัน', 'href' => route('customer.vps.index'), 'auth' => true],
                ],
            ],
            [
                'key' => 'xdreamer', 'accent' => '#f472b6', 'badge' => 'AI', 'icon' => 'spark',
                'title' => 'XDreamer AI Studio', 'en' => 'Generative image & video studio',
                'body' => 'สร้างภาพและวิดีโอด้วย AI ผ่านสตูดิโอออนไลน์ของเรา เลือกสไตล์ ปรับแต่ง และดาวน์โหลดได้ทันที พร้อมแกลเลอรีผลงานจากผู้ใช้จริง',
                // ai.xman4289.com is AIXMAN's own site (Next.js), not a page of this one.
                'links' => [
                    ['kind' => 'primary', 'label' => 'เข้าใช้งาน / Launch', 'href' => $ai, 'external' => true],
                    ['kind' => 'sub', 'label' => 'สตูดิโอ / Studio', 'href' => $ai . '/generate', 'external' => true],
                    ['kind' => 'sub', 'label' => 'แกลเลอรี / Gallery', 'href' => $ai . '/gallery', 'external' => true],
                    ['kind' => 'sub', 'label' => 'ราคา / Pricing', 'href' => $ai . '/pricing', 'external' => true],
                ],
            ],
            [
                'key' => 'brainx', 'accent' => '#8b5cf6', 'badge' => 'ใหม่', 'icon' => 'brain',
                'title' => 'BrainX', 'en' => 'Personal knowledge engine',
                'body' => 'ระบบความจำถาวรสำหรับ AI agent — เก็บโน้ต เชื่อมโยงความรู้เป็นกราฟ และค้นหาแบบ semantic ให้ผู้ช่วย AI ของคุณจำบริบทได้ข้ามเซสชัน',
                'links' => [
                    ['kind' => 'primary', 'label' => 'ดูรายละเอียด / Learn more', 'href' => $productSite, 'external' => true],
                    ['kind' => 'note', 'label' => (string) parse_url($productSite, PHP_URL_HOST)],
                ],
            ],
            [
                'key' => 'metalx', 'accent' => '#fb7185', 'badge' => null, 'icon' => 'play',
                'title' => 'Metal-X Project', 'en' => 'Music channel & AI production',
                'body' => 'ช่องเพลงและ Music Video ที่เราผลิตเอง ตั้งแต่แต่งเพลงด้วย AI ไปจนถึงเรนเดอร์ภาพและปล่อยขึ้น YouTube — สำรวจผลงานและทีมงานทั้งหมดได้',
                'links' => [
                    ['kind' => 'primary', 'label' => 'ทีมงาน & ผลงาน / Explore', 'href' => route('metal-x.index')],
                    ['kind' => 'sub', 'label' => 'ช่อง YouTube / Channel ↗', 'href' => 'https://www.youtube.com/@Metal-XProject', 'external' => true],
                ],
            ],
            [
                'key' => 'academy', 'accent' => '#38bdf8', 'badge' => 'ฟรี', 'icon' => 'book',
                'title' => 'XMAN Code Academy', 'en' => 'Free resource · 50+ examples',
                'body' => 'ศูนย์เรียนรู้โค้ดมืออาชีพ รวมตัวอย่างคุณภาพสูงกว่า 50 ตัวอย่าง ครอบคลุม Laravel, PHP, JavaScript, Python, Flutter, SQL, Git และ Docker — ฟรี ไม่ต้องสมัครสมาชิก',
                'links' => [
                    ['kind' => 'primary', 'label' => 'เข้าสู่ Code Academy', 'href' => route('code-academy')],
                    ['kind' => 'note', 'label' => 'ฟรี 100% / No signup'],
                ],
            ],
        ];
    }
}
