@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย (กฎเจ้าของ 2026-10-12) --}}
@php $csDownload = \App\Models\Product::slugIsComingSoon('anti-x'); @endphp

@section('title', 'Anti X — กันการเจาะเซิร์ฟเวอร์ Windows แบบเรียลไทม์ · ฟรีพื้นฐาน · Pro เริ่ม ฿' . number_format(\App\Support\LicensePlans::price('anti-x', 'monthly')) . '/เดือน | XMAN Studio')
@section('meta_description', 'Anti X บล็อกการเดารหัส RDP สแกนพอร์ต และพอร์ตอันตรายฟรี · Pro เพิ่มเดารหัสฐานข้อมูล/SSH ใน VM ยิงหาช่องโหว่ IP อันตราย Honeypot บล็อกที่ Cloudflare และจับเครื่องที่ถูกเจาะ ไม่ปิดพอร์ต RDP ไม่ล็อกตัวเอง ทดลอง Pro ฟรี 14 วัน · Real-time intrusion blocking for Windows servers.')
@section('og_image', asset('images/products/anti-x/v2/og.jpg'))

@section('content')
@php
    // ราคาทุกแผนมาจาก config/licenses.php 'plans' ที่เดียว (ตะกร้าคิดตามนี้) — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $prices = \App\Support\LicensePlans::for('anti-x');
    $monthly = number_format($prices['monthly'] ?? 0);
    $yearlySaving = \App\Support\LicensePlans::yearlySaving('anti-x');

    // ปุ่มซื้อต้องมีเสมอ — แอปส่งลูกค้ามาหน้านี้เพื่อซื้อ และ 1 คีย์ใช้ได้ 1 เครื่อง คนที่ซื้อแล้วก็ซื้อให้เครื่องอื่นได้
    $ownsAntiX = auth()->check() && $hasPurchased;

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว — เปิดหน้านี้ต้องไม่ถามแหล่งไฟล์ภายนอก)
    $latest = $product->latestVersion();

    // แอปมือถือคู่หู: แจกฟรีทุกคน แม้ไม่ได้ซื้อ Anti X (เจ้าของ 2026-10-21) — อ่านเวอร์ชันจาก DB อย่างเดียว
    $mobileApp = \App\Models\Product::where('slug', 'anti-x-mobile')->where('is_active', true)->first();
    $mobileLatest = $mobileApp?->latestVersion();
    $csMobile = \App\Models\Product::slugIsComingSoon('anti-x-mobile');

    // ภาพหน้าจอ: ภาพจริงจากแอป (เบลอ IP/ชื่อเครื่องของเราแล้ว) + mockup ของหน้าที่ยังไม่มีภาพจริง
    // สร้างใหม่ได้ด้วย scripts/product-mockups/build.py anti-x
    $shot = fn (string $file) => asset('images/products/anti-x/v2/' . $file);

    $plans = [
        'monthly' => ['name' => 'รายเดือน', 'en' => 'Monthly', 'unit' => '/ เดือน', 'note' => 'ซื้อทีละ 30 วัน ไม่ตัดเงินอัตโนมัติ', 'days' => '30 วัน'],
        'yearly' => ['name' => 'รายปี', 'en' => 'Yearly', 'unit' => '/ ปี', 'note' => $yearlySaving ? 'ถูกกว่ารายเดือน ' . $yearlySaving . '%' : 'จ่ายครั้งเดียวใช้ทั้งปี', 'days' => '365 วัน'],
        'lifetime' => ['name' => 'ตลอดชีพ', 'en' => 'Lifetime', 'unit' => 'ครั้งเดียว', 'note' => 'จ่ายครั้งเดียว ใช้ได้ตลอด', 'days' => 'ไม่หมดอายุ'],
    ];

    // หน้าจอที่ใช้ได้ในรุ่นฟรี — ข้อเท็จจริงจากโค้ดแอป (DetectionRules.WithoutPro, GuardService)
    $consoleRows = [
        [
            'img' => $shot('live.webp'), 'name' => 'เชื่อมต่อสด', 'eyebrow' => 'Live connections', 'tier' => 'free',
            'th' => 'เห็นทุก IP ที่กำลังใช้พอร์ตอินเทอร์เน็ตของเครื่องอยู่ตอนนี้ ทั้งเครื่องแม่และ VM พร้อมธงประเทศ บริการที่ถูกแตะ ปริมาณรับ/ส่ง และแยกสีให้เห็นทันทีว่าตัวไหนบล็อกแล้ว น่าสงสัย ทั่วไป หรือไว้ใจ',
            'en' => 'Every address using your internet-facing ports right now, coloured blocked, suspicious, normal or trusted.',
            'points' => ['บล็อกหรือปลดบล็อกจากแถวนั้นได้ในคลิกเดียว', 'กรองตามเครื่อง และค้นหาด้วย IP ประเทศ หรือพอร์ต'],
            'alt' => 'หน้าเชื่อมต่อสดของ Anti X แสดง IP ที่กำลังเชื่อมต่อ แยกสถานะบล็อก น่าสงสัย ทั่วไป ไว้ใจ',
        ],
        [
            'img' => $shot('blocks.webp'), 'name' => 'แบล็คลิสต์ บล็อกยิ่งทำผิดยิ่งนาน', 'eyebrow' => 'Escalating blocks', 'tier' => 'free',
            'th' => 'IP ที่ทำผิดครั้งแรกถูกบล็อก 1 วัน ครั้งที่สอง 7 วัน ครั้งที่สามถาวร ส่วนพวกที่ตั้งใจโจมตีชัด ๆ (ยิงหาช่องโหว่ สเปรย์รหัส แตะกับดัก) ถูกบล็อกถาวรตั้งแต่ครั้งแรก',
            'en' => 'One day, then seven, then for good. Deliberate attacks are blocked for good the first time.',
            'points' => ['บล็อก IP เองได้ พร้อมเหตุผลและระยะเวลา', 'IP ที่โจมตีเครื่องหนึ่ง ถูกบล็อกทุกเครื่องที่ดูแล'],
            'alt' => 'หน้าแบล็คลิสต์ของ Anti X รายการ IP ที่ถูกบล็อก เหตุผล และเวลาที่เหลือ',
        ],
        [
            'img' => $shot('events.webp'), 'name' => 'บันทึกเหตุการณ์', 'eyebrow' => 'Every event, explained', 'tier' => 'free',
            'th' => 'ทุกการโจมตีถูกบันทึกเป็นภาษาคน ว่าใคร ทำอะไร กับเครื่องไหน พอร์ตอะไร พร้อมรายละเอียดจาก log ต้นทาง ดูสดหรือย้อนหลัง แล้วส่งออก CSV ได้',
            'en' => 'Every attempt in plain words, with the original log line. Live or historic, exportable to CSV.',
            'points' => [],
            'alt' => 'หน้าบันทึกเหตุการณ์ของ Anti X รายการการโจมตีพร้อมประเภท IP เครื่อง และพอร์ต',
        ],
    ];

    // Pro — ทุกข้อเช็กกับด่าน IsPro ในโค้ดแอป
    $proTools = [
        [
            'key' => 'suspects', 'name' => 'ผู้ต้องสงสัย', 'short' => 'รวมหลักฐาน ให้คุณตัดสิน', 'tier' => 'pro',
            'img' => $shot('suspects.webp'), 'mock' => true,
            'th' => 'วิเคราะห์รวมจากทุกเครื่องที่ดูแล จับคนร้ายที่ฉลาดพอจะยิงต่ำกว่าเกณฑ์ เช่น เดารหัสช้า ๆ ทั้งวัน หรือหลาย IP ผลัดกันรุมเดาบัญชีเดียว แล้วสรุปเป็นคะแนนพร้อมหลักฐาน ให้คุณกดตัดสินเอง',
            'en' => 'A combined analysis across every machine that catches slow and distributed attacks, scored with its evidence for you to decide.',
            'points' => ['สเปรย์รหัส: IP เดียวลองหลายชื่อผู้ใช้', 'เดารหัสแบบค่อย ๆ ยิง และหลาย IP รุมเดาบัญชีเดียว', 'แจ้งเมื่อมีคนล็อกอินสำเร็จจาก IP ที่ไม่เคยเห็น'],
        ],
        [
            'key' => 'honeypot', 'name' => 'Honeypot กับดักคนร้าย', 'short' => 'แตะเมื่อไร บล็อกถาวร', 'tier' => 'pro',
            'img' => $shot('honeypot.webp'), 'mock' => true,
            'th' => 'เปิดพอร์ตล่อที่ผู้ใช้จริงไม่มีทางแตะ เช่น Telnet, Redis, MongoDB, ADB ใครแตะคือคนร้ายแน่นอน ถูกบล็อกถาวรทันที และ Anti X เก็บสิ่งที่เขาพิมพ์ใส่กับดักไว้เป็นหลักฐาน',
            'en' => 'Decoy ports no real user ever touches. Whoever does is blocked for good, and what they type is kept as evidence.',
            'points' => ['Tarpit ถ่วงคนร้ายให้ค้างนาน ๆ', 'ไฟล์ล่อ (Canary) ในเครื่อง ใครเปิด = มีคนอยู่ข้างในแล้ว', 'ติดตั้งตัวดักใน VM ที่ดูแลผ่าน SSH ได้ด้วย', 'ปิดไว้เป็นค่าเริ่มต้น เปิดเองในหน้าตั้งค่า'],
        ],
        [
            'key' => 'compromise', 'name' => 'จับเครื่องที่ถูกเจาะ', 'short' => 'ถ้ามีคนเข้ามาได้แล้ว', 'tier' => 'pro',
            'th' => 'ถ้ามีคนหลุดเข้ามาได้แล้ว Anti X มองหาร่องรอยในเครื่อง: Defender ถูกปิด log ถูกล้าง บริการหรืองานตั้งเวลาแปลกใหม่ ตัวขุดเหรียญ คำสั่งแรนซัมแวร์ และการติดต่อออกที่ผิดปกติ',
            'en' => 'Looks for the traces of a break-in: Defender switched off, logs wiped, new services or scheduled tasks, miners, ransomware commands, odd outbound traffic.',
            'points' => ['ปุ่ม "จัดการ" ฆ่าโปรเซส กักกันไฟล์ ปิดทางกลับ', 'ต้องกดยืนยันก่อนทุกครั้ง และคืนของที่กักกันได้'],
        ],
        [
            'key' => 'web', 'name' => 'เว็บและฐานข้อมูล', 'short' => 'ยิงหาช่องโหว่ · เดารหัส DB · flood', 'tier' => 'pro',
            'th' => 'อ่าน log เว็บและฐานข้อมูล จับคนที่ยิงหาช่องโหว่ (/.env, /.git/, ${jndi:, ../../, SQL injection) เดารหัส MSSQL / MySQL / PostgreSQL / MongoDB และกันการยิงท่วม ทั้ง HTTP flood และ Slowloris',
            'en' => 'Web exploit probes, database brute force (MSSQL, MySQL, PostgreSQL, MongoDB), HTTP floods and Slowloris.',
            'points' => ['ค่าเริ่มต้น: HTTP เกิน 600 คำขอใน 60 วินาที · ค้างเชื่อมต่อเกิน 150 จาก IP เดียว', 'สแกนแบบซ่อนตัว (NULL / FIN / XMAS) และไล่สแกนทุก IP ของเรา', 'แพ็กเก็ตผิดรูป และการติดต่อออกที่น่าสงสัย'],
        ],
        [
            'key' => 'feeds', 'name' => 'IP ในรายชื่ออันตราย', 'short' => 'บล็อกตั้งแต่ครั้งแรกที่ติดต่อ', 'tier' => 'pro',
            'th' => 'ดึงรายชื่อเครือข่ายอันตรายสาธารณะ (Spamhaus DROP และ abuse.ch Feodo) ทุก 12 ชั่วโมง IP ในรายชื่อถูกบล็อกตั้งแต่ติดต่อครั้งแรก ไม่ต้องรอให้โจมตีก่อน',
            'en' => 'Public blocklists refreshed every 12 hours: listed networks are blocked on first contact.',
            'points' => [],
        ],
        [
            'key' => 'cloudflare', 'name' => 'บล็อกที่ Cloudflare', 'short' => 'กันตั้งแต่หน้าบ้าน', 'tier' => 'pro',
            'th' => 'เว็บอยู่หลัง Cloudflare? Anti X ส่ง IP ที่เครื่องเราบล็อกแล้วไปบล็อกที่ Cloudflare ด้วย คนร้ายจึงไม่ถึงเซิร์ฟเวอร์เลย และเปิดโหมด Under Attack ให้อัตโนมัติเมื่อโดนยิงหนัก',
            'en' => 'Blocks reach Cloudflare too, and Under Attack mode switches on by itself when a flood hits.',
            'points' => ['ส่งไปเฉพาะ IP ที่เครื่องเราบล็อกได้จริงแล้ว', 'ระหว่าง Under Attack แอปมือถือและ API ยังใช้งานได้'],
        ],
        [
            'key' => 'vm', 'name' => 'ดูแล VM ผ่าน SSH', 'short' => 'Linux · csf · iptables · Docker', 'tier' => 'pro',
            'img' => $shot('machines.webp'),
            'th' => 'มี VM Linux บนเครื่องเดียวกัน? Anti X อ่าน log SSH ใน VM แบบสด และสั่งไฟร์วอลล์ในตัว VM (csf หรือ iptables) ผ่าน SSH ด้วยคีย์ที่คุณสร้างเอง รู้จัก Docker ด้วย จึงบล็อกใน chain DOCKER-USER ให้',
            'en' => 'Reads SSH logs inside your Linux VMs and drives their own firewall (csf or iptables) over SSH, Docker-aware.',
            'points' => ['ตัวช่วยเพิ่ม VM ทีละขั้น ตรวจการเชื่อมต่อให้ก่อน', 'ไฟร์วอลล์และข้อมูลซ่อมตัวเองได้ถ้ามีอะไรหาย'],
            'alt' => 'หน้าเครื่องที่ดูแลของ Anti X แสดงเครื่องแม่และ VM ที่เชื่อมต่อผ่าน SSH',
        ],
    ];

    // ตารางเทียบ — ตรงกับ DetectionRules.WithoutPro() และด่าน IsPro ของแอป
    $compare = [
        ['ตัวจับการโจมตี · Detectors', [
            ['เดารหัส RDP และล็อกอิน Windows (event 4625 รวม SMB)', false],
            ['สแกนพอร์ต · แตะพอร์ตอันตราย 36 พอร์ต · เชื่อมต่อถี่', false],
            ['แจ้งเตือนเมื่อบัญชี/สิทธิ์ผู้ดูแลถูกเปลี่ยน', false],
            ['เดารหัสฐานข้อมูล (MSSQL / MySQL / PostgreSQL / MongoDB)', true],
            ['เดารหัส SSH ใน VM Linux', true],
            ['สเปรย์รหัส · เดาช้า ๆ · หลาย IP รุมเดา', true],
            ['สแกนแบบซ่อนตัว · ไล่สแกนทุก IP', true],
            ['ยิงหาช่องโหว่เว็บ · HTTP flood · Slowloris · แพ็กเก็ตผิดรูป', true],
            ['IP ในรายชื่ออันตราย (อัปเดตทุก 12 ชม.)', true],
            ['จับเครื่องที่ถูกเจาะ + ปุ่ม "จัดการ"', true],
        ]],
        ['เครื่องมือ · Tools', [
            ['เชื่อมต่อสด · แบล็คลิสต์ · ไวท์ลิสต์ · บันทึกเหตุการณ์', false],
            ['รายงาน / ผลตรวจ ส่งออก Excel · CSV · PDF', false],
            ['แอปมือถือคู่หู', false],
            ['สวิตช์ปิดฉุกเฉินทางบรรทัดคำสั่ง · อัปเดตอัตโนมัติ', false],
            ['ผู้ต้องสงสัย (วิเคราะห์รวมพร้อมหลักฐาน)', true],
            ['Honeypot · Tarpit · ไฟล์ล่อ', true],
            ['บล็อกที่ Cloudflare + Under Attack อัตโนมัติ', true],
            ['ดูแล VM ผ่าน SSH (csf / iptables / Docker)', true],
        ]],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Anti X',
        'operatingSystem' => 'Windows (64-bit)',
        'applicationCategory' => 'SecurityApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'ระบบกันการเจาะเซิร์ฟเวอร์ Windows แบบเรียลไทม์ บล็อกการเดารหัส สแกนพอร์ต ยิงหาช่องโหว่ และ IP อันตราย พร้อม Honeypot และจับเครื่องที่ถูกเจาะ',
        'image' => asset('images/products/anti-x/v2/og.jpg'),
        'url' => route('products.show', 'anti-x'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => array_values(array_merge(
            [['@type' => 'Offer', 'name' => 'Free', 'price' => '0', 'priceCurrency' => 'THB']],
            collect($prices)->map(fn ($price, $term) => ['@type' => 'Offer', 'name' => 'Pro (' . $plans[$term]['en'] . ')', 'price' => (string) $price, 'priceCurrency' => 'THB'])->values()->all(),
        )),
    ];
@endphp
<x-xp.page a="#34e3a1" b="#7cf5c4" c="#38d6ff" glow="56 214 255" g1="#ff4d5e" g2="#ffb547" g3="#34e3a1">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="กันการเจาะเซิร์ฟเวอร์ Windows แบบเรียลไทม์"
               tagline-en="Real-time intrusion blocking for Windows servers."
               lead="บล็อกการเดารหัส สแกนพอร์ต ยิงหาช่องโหว่ และ IP อันตราย วางกับดักล่อคนร้าย และจับเครื่องที่ถูกเจาะ — ทำงานเป็นบริการ Windows ตลอดเวลา แม้ปิดหน้าจอหรือตัด RDP ไปแล้ว"
               lead-en="Blocks brute force, port scans, exploit probes and known-bad networks, sets traps for attackers and spots a machine that has already been broken into.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'Anti X ' . $latest->version : 'Anti X' }}</span>
            <span class="xp-chip">Windows Server · Windows 10/11</span>
        </x-slot:chips>
        <x-slot:title>Anti <span class="xp-text-glow">X</span></x-slot:title>
        <x-slot:actions>
            <x-xp.download :href="route('anti-x.download')" :coming-soon="$csDownload" />
            <a href="#pricing" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">
                {{ $ownsAntiX ? 'ซื้อ License เพิ่ม' : 'ซื้อ Pro' }} — เริ่ม ฿{{ $monthly }}/เดือน
            </a>
        </x-slot:actions>
        <x-slot:notes>
            @if($latest)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · ไฟล์ .zip จาก xman4289.com โดยตรง</p>
            @endif
            @if($ownsAntiX)
                <p class="mt-3 text-sm text-emerald-200/90">
                    คุณมี License แล้ว · 1 คีย์ใช้ได้ 1 เครื่อง — ซื้อเพิ่มได้สำหรับเครื่องอื่น ·
                    <a href="{{ route('customer.licenses') }}" class="font-semibold underline decoration-emerald-300/50 hover:text-white">ดูคีย์ License ของฉัน</a>
                </p>
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="free" /> ตัวจับพื้นฐาน ใช้ได้ตลอด</li>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="pro" /> ทดลองฟรี 14 วัน</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ไม่ปิดพอร์ต RDP</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('dashboard.webp')" :eager="true"
                             alt="หน้าภาพรวมของ Anti X: การโจมตี 24 ชั่วโมง IP ที่บล็อกอยู่ ประเทศที่โจมตีมากที่สุด และเหตุการณ์ล่าสุด" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">บล็อกทีละ IP · One IP at a time</p>
                <p class="text-white font-extrabold text-lg leading-tight">ไม่ล็อก<span class="text-emerald-300">ผู้ดูแลที่ต่ออยู่</span></p>
            </div>
            <div class="xp-float-2 absolute -right-2 sm:-right-6 -top-5 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">RDP · ค่าเริ่มต้น</p>
                <p class="text-white font-bold leading-tight">ผิด 5 ครั้งใน 10 นาที<br><span class="text-gray-400 text-sm font-normal">→ บล็อก IP นั้นทันที</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================== FREE CONSOLE ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Free · works forever" title="เห็นทุกการโจมตี" title2="บล็อกให้เองตลอด 24 ชม."
                       sub="รุ่นฟรีจับการเดารหัส RDP และล็อกอิน Windows สแกนพอร์ต และการแตะพอร์ตอันตราย 36 พอร์ต (ฐานข้อมูล SMB/RPC WinRM VNC Telnet Docker API ฯลฯ) แล้วบล็อกให้ทันที"
                       en="The free edition blocks RDP and Windows logon brute force, port scans and touches on 36 dangerous ports, for good." />
            <x-xp.rows :items="$consoleRows" />
        </div>
    </section>

    {{-- =========================== PRO TOOLS =========================== --}}
    <section class="relative py-20 isolate">
        <x-xp.art :src="$shot('art-honeypot.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="Pro · every advanced detector" title="คนร้ายที่ฉลาดกว่า" title2="ก็ยังหนีไม่พ้น"
                       sub="Pro ปลดตัวจับขั้นสูงทั้งหมด: การโจมตีแบบแอบยิงช้า ๆ ยิงหาช่องโหว่เว็บ เดารหัสฐานข้อมูล กับดัก Honeypot บล็อกที่ Cloudflare และดูแล VM"
                       en="Pro unlocks every advanced detector: slow and distributed attacks, exploit probes, database brute force, honeypots, Cloudflare and VMs." />
            <x-xp.explorer id="pro" label="ฟีเจอร์ Pro ของ Anti X" :tools="$proTools" />
        </div>
    </section>

    {{-- ============================ REPORTS ============================ --}}
    <section class="relative py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.rows :items="[[
                'img' => $shot('reports.webp'), 'mock' => true, 'name' => 'รายงาน / ผลตรวจ', 'eyebrow' => 'Evidence you can hand over',
                'th' => 'กรองทุกเหตุการณ์ บล็อก และหลักฐานตามช่วงวัน IP หรือช่วง CIDR ประเทศ ASN ประเภท ความรุนแรง หรือพอร์ต แล้วส่งออกทั้งชุดเป็น Excel, CSV หรือ PDF สรุป ใช้แนบรายงานให้หัวหน้า ลูกค้า หรือผู้ให้บริการเครือข่ายได้ทันที',
                'en' => 'Filter every event, block and piece of evidence, then export the whole set as Excel, CSV or a PDF summary.',
                'points' => ['บันทึกผลตรวจของแอดมินได้ว่าบล็อกถูกหรือผิด แล้ววัดความแม่นยำ (precision) ให้เห็นเป็นตัวเลข', 'หลักฐานเต็มรูปแบบ (Evidence) เก็บได้ในรุ่น Pro'],
                'alt' => 'ภาพตัวอย่างหน้ารายงานของ Anti X ตัวกรอง ตารางผล และปุ่มส่งออก Excel CSV PDF',
            ]]" />
        </div>
    </section>

    {{-- ============================ SAFETY ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Never locks you out" title="กันคนร้าย" title2="โดยไม่ล็อกตัวเอง"
                       sub="ตัวกันเจาะที่ดีต้องไม่ทำให้ผู้ดูแลเข้าเซิร์ฟเวอร์ของตัวเองไม่ได้"
                       en="A guard must never become the reason you cannot reach your own server." />
            <x-xp.cards :items="[
                ['icon' => 'shield', 'title' => 'บล็อกทีละ IP', 'body' => 'ไม่เคยปิดพอร์ต RDP 3389 ทั้งพอร์ต — คนอื่นที่ไม่ได้ทำผิดยังเข้าได้ตามปกติ', 'en' => 'One IP at a time, never the whole port'],
                ['icon' => 'user', 'title' => 'ล็อกอินสำเร็จ = ไว้ใจ 24 ชม.', 'body' => 'IP ที่เข้า RDP สำเร็จถูกไว้ใจอัตโนมัติ 24 ชั่วโมง และไวท์ลิสต์ใส่ได้ทั้ง IP เดี่ยวและช่วง CIDR', 'en' => 'Successful RDP logins are trusted for a day'],
                ['icon' => 'eye', 'title' => 'ด่านสุดท้ายก่อนเขียนไฟร์วอลล์', 'body' => 'คัด IP ที่ไว้ใจ IP ของเราเอง และ IP ของ Cloudflare ออกจากกฎบล็อกทุกครั้ง', 'en' => 'A last check before every firewall rule'],
                ['icon' => 'globe', 'title' => 'ประเทศผ่อนปรน', 'body' => 'IP จากประเทศที่เลือก (ค่าเริ่มต้นไทย) ต้องทำผิดมากกว่าปกติ 4 เท่าจึงถูกบล็อก', 'en' => 'Lenient countries need 4× the evidence'],
                ['icon' => 'power', 'title' => 'สวิตช์ปิดฉุกเฉิน', 'body' => 'สั่ง off / on ได้จากบรรทัดคำสั่ง ไม่ต้องใช้ RDP และ emergency-clear ล้างกฎทั้งหมดรวมที่ Cloudflare ในคำสั่งเดียว', 'en' => 'An off switch that needs no RDP'],
                ['icon' => 'lock', 'title' => 'อัปเดตที่ตรวจลายเซ็น', 'body' => 'อัปเดตมาจาก xman4289.com เท่านั้น ทุกแพ็กเกจมีลายเซ็นดิจิทัลและถูกตรวจก่อนติดตั้ง', 'en' => 'Every update is signed and checked'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="เริ่มฟรี" title2="อยากได้ครบค่อยอัป Pro"
                       sub="ไฟล์เดียวกันทั้งรุ่นฟรีและ Pro · 1 คีย์ใช้ได้ 1 เครื่อง · ทดลอง Pro ฟรี 14 วันก่อนตัดสินใจ (ครั้งเดียวต่อเครื่อง)"
                       en="One download. One key per machine. Try every Pro feature free for 14 days first." />

            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-5 max-w-6xl mx-auto">
                <div class="xp-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">Free</h3>
                        <x-xp.badge tier="free" />
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="ตัวจับพื้นฐาน ไม่จำกัดเวลา" en="The core, no time limit" /></p>
                    <div class="mb-6"><span class="text-5xl font-black text-white">฿0</span></div>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['เดารหัส RDP + ล็อกอิน Windows', 'สแกนพอร์ต + พอร์ตอันตราย 36 พอร์ต', 'เชื่อมต่อสด · แบล็คลิสต์ · ไวท์ลิสต์', 'รายงาน + แอปมือถือ', 'อัปเดตอัตโนมัติ']" />
                    {{-- ไฟล์เดียวกับ Pro — Pro ปลดล็อกในแอปด้วย license key --}}
                    <x-xp.download block :href="route('anti-x.download')" :coming-soon="$csDownload" />
                </div>

                @foreach($plans as $term => $plan)
                    @continue(! isset($prices[$term]))
                    @php($featured = $term === 'yearly')
                    <div class="{{ $featured ? 'xp-featured' : 'xp-card' }}">
                        <div class="p-7 flex flex-col h-full">
                            @if($featured)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2"><x-xp.badge tier="pro" label="คุ้มที่สุด · BEST VALUE" class="shadow-lg" /></div>
                            @endif
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-2xl font-black text-white">Pro {{ $plan['name'] }}</h3>
                                <x-xp.badge tier="pro" />
                            </div>
                            <p class="text-amber-100/80 text-sm mb-6">{{ $plan['note'] }}</p>
                            <div class="mb-6 flex items-baseline gap-2">
                                <span class="text-5xl font-black text-white">฿{{ number_format($prices[$term]) }}</span>
                                <span class="text-gray-300">{{ $plan['unit'] }}</span>
                            </div>
                            <x-tpix-price variant="detail" :thb="$prices[$term]" class="-mt-4 mb-6" />
                            <x-xp.ticks pro class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ทุกอย่างในรุ่นฟรี', 'ตัวจับขั้นสูงทั้งหมด + จับเครื่องที่ถูกเจาะ', 'Honeypot · Cloudflare · ดูแล VM', 'อายุ License: ' . $plan['days']]" />
                            {{-- ราคามาจาก config/licenses.php ไม่ใช่จากฟอร์ม — ตะกร้าคิดตามแผนที่ส่งไป --}}
                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="license_type" value="{{ $term }}">
                                <input type="hidden" name="buy_now" value="1">
                                <button type="submit" class="{{ $featured ? 'xp-btn-pro' : 'xp-btn-ghost' }} block w-full py-3.5 text-center rounded-xl font-extrabold transition cursor-pointer">
                                    {{ $ownsAntiX ? 'ซื้อ License เพิ่ม' : 'ซื้อ Pro' }} {{ $plan['name'] }} — ฿{{ number_format($prices[$term]) }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($ownsAntiX)
                <p class="text-center mt-6">
                    <a href="{{ route('customer.licenses') }}" class="xp-link text-sm font-semibold">มี License แล้ว? ดูคีย์ของคุณ →</a>
                </p>
            @endif
            <p class="text-center text-xs text-gray-500 mt-6">ซื้อแล้วได้ License key ทางอีเมลและในหน้า บัญชีของฉัน · ใส่คีย์ในโปรแกรมได้เลย ไม่ต้องลงใหม่ · ย้ายเครื่องได้ด้วยการยกเลิกบนเครื่องเดิม · คีย์ที่จ่ายแล้วใช้ต่อได้ 30 วันแม้ติดต่อเซิร์ฟเวอร์ไม่ได้</p>

            <x-xp.compare class="mt-14" :groups="$compare" note="ทุกเกณฑ์ (จำนวนครั้ง ช่วงเวลา พอร์ต) แก้ได้ในหน้า ตั้งค่า ของโปรแกรม · ระหว่างทดลอง 14 วันใช้ได้ทุกฟีเจอร์" />
        </div>
    </section>

    {{-- ===================== MOBILE APP (ฟรีทุกคน) ===================== --}}
    <section class="relative py-20 scroll-mt-20" id="mobile">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-5">
                    <p class="xp-eyebrow mb-3">Mobile app · Free</p>
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-4 flex flex-wrap items-center gap-3">แอปมือถือ Anti X <x-xp.badge tier="free" label="ฟรีทุกคน" /></h2>
                    <p class="text-lg text-gray-300 leading-relaxed mb-2">
                        ดูสถานะการป้องกัน การโจมตี 24 ชั่วโมง การเชื่อมต่อสด รายการบล็อก และตัดสินผู้ต้องสงสัยได้จากมือถือ พร้อมรับแจ้งเตือนทันทีที่มีเหตุ
                        จับคู่ด้วยการสแกน QR จากหน้า "อุปกรณ์มือถือ" ในโปรแกรม แล้วยืนยันรหัส 6 หลักให้ตรงกันทั้งสองฝั่ง
                    </p>
                    <p class="text-sm text-gray-500 leading-relaxed mb-6">Watch and act on your Anti X server from your phone, with push alerts — paired by QR code. Free for everyone.</p>
                    <ul class="space-y-2.5 text-gray-300 mb-8">
                        <li class="flex gap-2"><span style="color: var(--xp-a)">✓</span> ดาวน์โหลดได้เลย ไม่ต้องล็อกอิน ไม่ต้องซื้อ Anti X</li>
                        <li class="flex gap-2"><span style="color: var(--xp-a)">✓</span> เปิดแอปครั้งแรกได้ไลเซนส์ฟรีอัตโนมัติ</li>
                        <li class="flex gap-2"><span style="color: var(--xp-a)">✓</span> กุญแจอยู่ในชิปของมือถือ ไม่มีรหัสผ่านให้ขโมย · สั่งงานสำคัญต้องสแกนนิ้วหรือใบหน้า</li>
                        <li class="flex gap-2"><span style="color: var(--xp-a)">✓</span> อัปเดตตัวเองจาก xman4289.com และตรวจไฟล์ก่อนติดตั้งทุกครั้ง</li>
                    </ul>
                    <div class="flex flex-wrap items-center gap-3">
                        @if($csMobile)
                            <span class="xp-btn-main xp-btn-off inline-flex items-center gap-2 px-7 py-4 rounded-xl font-extrabold text-lg transition" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดให้ดาวน์โหลด</span>
                        @elseif($mobileLatest)
                            <a href="{{ route('anti-x-mobile.download') }}" class="xp-btn-main inline-flex items-center gap-2 px-7 py-4 rounded-xl font-extrabold text-lg transition" data-mobile-download>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <x-bi th="ดาวน์โหลดแอป Android ฟรี" en="Free Android download" />
                            </a>
                        @else
                            <span class="xp-btn-ghost inline-flex items-center px-7 py-4 rounded-xl font-bold" role="status" data-mobile-pending>กำลังจะเปิดให้ดาวน์โหลดเร็ว ๆ นี้</span>
                        @endif
                    </div>
                    <p class="mt-4 text-sm text-gray-400">
                        @if($mobileLatest)เวอร์ชันล่าสุด {{ $mobileLatest->version }}@if($mobileLatest->file_size) · {{ $mobileLatest->file_size_formatted }}@endif · @endif
                        ไฟล์ .apk จาก xman4289.com โดยตรง · Android 8 ขึ้นไป · ใช้คู่กับ Anti X บนคอม (ฟรีหรือ Pro ก็ได้)
                    </p>
                </div>
                <div class="lg:col-span-7">
                    <div class="grid grid-cols-3 gap-3 sm:gap-5">
                        @foreach([['dashboard', 'ภาพรวมการป้องกัน'], ['blocks', 'รายการบล็อก'], ['pair', 'จับคู่ QR + รหัส 6 หลัก']] as [$phoneShot, $caption])
                            <figure class="text-center">
                                <x-xp.screen phone :src="asset('images/products/anti-x-mobile/' . $phoneShot . '.webp')" alt="หน้าจอแอป Anti X บนมือถือ — {{ $caption }}" :width="390" :height="865" />
                                <figcaption class="mt-3 text-xs text-gray-400">{{ $caption }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                    <p class="mt-3 text-center text-xs text-gray-500">หน้าจอจริงของแอป · ข้อมูลในภาพเป็นข้อมูลตัวอย่าง</p>
                </div>
            </div>
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Windows Server 2016 ขึ้นไป หรือ Windows 10/11 แบบ 64-bit', 'Windows, 64-bit'],
            ['ไม่ต้องติดตั้ง .NET', 'ทุกอย่างอยู่ใน zip เดียว', 'Self-contained'],
            ['สิทธิ์ Administrator', 'ติดตั้งเป็นบริการ Windows และเขียน Windows Firewall จึงต้องใช้สิทธิ์ผู้ดูแล', 'Installs as a Windows service'],
            ['อินเทอร์เน็ต', 'ต่อเน็ตตอนเปิดครั้งแรกเพื่อลงทะเบียนเครื่องและเริ่มทดลอง และตอนใส่คีย์', 'Online to register, trial and activate'],
        ]"
        specs-note="ติดตั้ง Npcap เพิ่มได้ถ้าอยากจับสแกนพอร์ต flood และแพ็กเก็ตผิดรูปได้ครบ หรือเห็นทราฟฟิกของ VM แบบ bridged — ไม่ติดตั้งก็ใช้ตัวดักในตัวของ Windows แทน และมีปุ่มติดตั้งในหน้า เครื่องที่ดูแล"
        :steps="[
            ['ดาวน์โหลดไฟล์ .zip', 'ไฟล์มาจาก xman4289.com โดยตรง ใช้ได้ทั้งรุ่นฟรีและ Pro', 'Download the .zip'],
            ['แตกไฟล์แล้วรันตัวติดตั้งแบบ Administrator', 'ติดตั้งบริการ Anti X ให้เอง เลือกโหมดเฝ้าดูอย่างเดียวก่อนได้ถ้ายังไม่อยากให้บล็อกจริง', 'Run the installer as Administrator'],
            ['เปิด Anti X แล้วทดลอง Pro 14 วัน', 'ถูกใจแล้วซื้อคีย์ใส่ในโปรแกรมได้เลย หมดทดลองแล้วตัวจับพื้นฐานยังทำงานต่อฟรี', 'Open Anti X and try Pro for 14 days'],
        ]" />

    <x-xp.faq :items="[
        ['ฟรีจริงไหม?', 'ฟรีจริง ตัวจับพื้นฐาน (เดารหัส RDP และล็อกอิน Windows สแกนพอร์ต พอร์ตอันตราย) ใช้ได้ไม่จำกัดเวลา ดาวน์โหลดได้เลยโดยไม่ต้องล็อกอิน'],
        ['ทดลอง Pro ทำอย่างไร?', 'เปิดโปรแกรมครั้งแรกตอนต่อเน็ต จะได้ทดลองทุกฟีเจอร์ Pro 14 วัน ครั้งเดียวต่อเครื่อง (ลง Windows ใหม่ก็ไม่ได้สิทธิ์ซ้ำ) หมดเวลาแล้วตัวจับพื้นฐานยังทำงานต่อ'],
        ['Pro ราคาเท่าไร ใช้ได้กี่เครื่อง?', 'รายเดือน ฿' . number_format($prices['monthly'] ?? 0) . ' · รายปี ฿' . number_format($prices['yearly'] ?? 0) . ' · ตลอดชีพ ฿' . number_format($prices['lifetime'] ?? 0) . ' — 1 คีย์ใช้ได้ 1 เครื่อง มีหลายเครื่องซื้อคีย์เพิ่มได้ และย้ายคีย์ไปเครื่องใหม่ได้'],
        ['จะล็อกตัวเองออกจากเซิร์ฟเวอร์ไหม?', 'ไม่ Anti X บล็อกทีละ IP ไม่ปิดพอร์ต RDP ทั้งพอร์ต IP ที่ล็อกอิน RDP สำเร็จถูกไว้ใจ 24 ชั่วโมง และมีคำสั่งปิดฉุกเฉินที่ไม่ต้องใช้ RDP'],
        ['เดารหัสฐานข้อมูลกับ SSH ต้องใช้ Pro ไหม?', 'ใช่ การจับเดารหัสฐานข้อมูล (MSSQL, MySQL, PostgreSQL, MongoDB) และ SSH ใน VM Linux เป็นของ Pro ส่วนรุ่นฟรีจับการเดารหัส RDP และล็อกอิน Windows ทุกแบบ'],
        ['ใช้กับ VM บนเครื่องเดียวกันได้ไหม?', 'ได้ (Pro) Anti X อ่าน log SSH ใน VM และสั่งไฟร์วอลล์ในตัว VM (csf หรือ iptables) ผ่าน SSH ด้วยคีย์ที่คุณสร้างเอง รู้จัก Docker ด้วย เพิ่ม VM ไว้ก่อนได้ในรุ่นฟรี แล้วเริ่มทำงานเมื่อเปิด Pro'],
        ['Honeypot จะเปิดพอร์ตให้คนร้ายเข้าเครื่องไหม?', 'ไม่ พอร์ตล่อเป็นของปลอมที่ไม่ได้ต่อกับโปรแกรมจริง ใครแตะถูกบล็อกถาวรทันที และ Honeypot ปิดไว้เป็นค่าเริ่มต้น เปิดเมื่อคุณต้องการเท่านั้น'],
        ['ข้อมูลเก็บที่ไหน?', 'เหตุการณ์ ประวัติบล็อก หลักฐาน และการตั้งค่าเก็บในเครื่องของคุณเอง โปรแกรมติดต่อ xman4289.com เพื่อตรวจ License เช็กอัปเดต และส่งแจ้งเตือนไปมือถือที่จับคู่ไว้เท่านั้น'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="ปิดประตูก่อน" glow="คนร้ายจะเข้า"
              :sub="'ดาวน์โหลดรุ่นฟรีได้ทันที หรือปลดตัวจับขั้นสูงทั้งหมดด้วย Pro เริ่มเพียง ฿' . $monthly . ' ต่อเดือน'"
              en="Shut the door before anyone walks in.">
        <x-xp.download :href="route('anti-x.download')" :coming-soon="$csDownload" class="!px-8" />
        <a href="#pricing" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ดูแผน Pro</a>
        @if($ownsAntiX)
            <a href="{{ route('customer.licenses') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ดูคีย์ License ของฉัน</a>
        @endif
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows Server · Windows 10/11 (64-bit) · ไม่ต้องลง .NET · ทดลอง Pro ฟรี 14 วัน · 1 คีย์ = 1 เครื่อง</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
