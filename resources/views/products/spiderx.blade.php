@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ไม่มีปุ่มดาวน์โหลดและไม่มีทางซื้อ (กฎเจ้าของ 2026-10-12) --}}
@php $csSpiderX = $product->isComingSoon(); @endphp

@section('title', 'SpiderX — คุยและแชร์ไฟล์ตรงถึงกันแบบ P2P ไม่ผ่านเซิร์ฟเวอร์กลาง' . ($csSpiderX ? ' · Coming Soon' : '') . ' | XMAN Studio')
@section('meta_description', 'SpiderX เครือข่าย P2P mesh สำหรับ Windows: ค้นหาเครื่องในวงแลนอัตโนมัติ แชทตรงถึงเครื่องเพื่อน แชร์ไฟล์ดึงจากหลายเครื่องพร้อมกันพร้อมตรวจ SHA-256 ทุกชิ้น และ Virtual LAN สำหรับเกม ตัวตนเป็นกุญแจในเครื่อง ไม่ต้องสมัคร · Direct peer-to-peer chat, file sharing and LAN games for Windows.')
@section('og_image', asset('images/products/spiderx/v2/og.jpg'))

@section('content')
@php
    // ราคาอ่านจากแถวสินค้าในร้าน (products.price) ที่เดียว — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $price = (float) $product->price;

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว) — ยังไม่มีจนกว่าจะเปิดดาวน์โหลด
    $latest = $product->latestVersion();

    // คนที่ซื้อแล้วโหลดได้ที่ศูนย์ดาวน์โหลดของบัญชี (กลไกเดิมของหน้านี้) — ระหว่าง Coming Soon เป็นป้ายแทน
    $downloadHref = route('customer.downloads');
    $ownsSpiderX = auth()->check() && $hasPurchased;

    // ภาพหน้าจอ: ยังไม่มีภาพจริงของแอป ทุกภาพเป็น mockup ที่วาดจาก XAML ของแอป (ข้อมูลในภาพแต่งขึ้นทั้งหมด)
    // สร้างใหม่ได้ด้วย scripts/product-mockups/build.py spiderx
    $shot = fn (string $file) => asset('images/products/spiderx/v2/' . $file);

    // สิ่งที่แอปทำได้ — เช็กกับโค้ดใน D:\Code\SpiderX (SpiderX.App/Views, SpiderX.Services, SpiderX.Transport)
    $featureRows = [
        [
            'img' => $shot('chat.webp'), 'mock' => true, 'name' => 'แชทตรงถึงเครื่องเพื่อน', 'eyebrow' => 'Direct chat',
            'th' => 'เพิ่มเพื่อนด้วย SpiderX ID (รหัสขึ้นต้น spx1) หรือที่อยู่ที่เพื่อนแชร์จากแอป ส่งคำขอเป็นเพื่อนให้อีกฝ่ายกดยอมรับ แล้วข้อความวิ่งจากเครื่องคุณถึงเครื่องเขาโดยตรง',
            'en' => 'Add a friend by their SpiderX ID, they accept, and messages travel machine to machine.',
            'points' => ['เห็นว่าเพื่อนคนไหนออนไลน์อยู่', 'คำขอที่ไม่รู้จักกดยอมรับหรือบล็อกได้'],
            'alt' => 'ภาพตัวอย่างหน้าแชทของ SpiderX รายการบทสนทนาและข้อความกับเพื่อน',
        ],
        [
            'img' => $shot('nearby.webp'), 'mock' => true, 'name' => 'เจอเครื่องในวงแลนเอง', 'eyebrow' => 'Nearby peers',
            'th' => 'กด Scan แล้ว SpiderX หาเครื่องที่เปิดแอปอยู่ในวงแลนเดียวกันให้ ผ่าน UDP broadcast และ multicast เห็นชื่อเครื่องกับที่อยู่ แล้วกด Connect ได้ทันที ไม่ต้องถามหา IP กัน',
            'en' => 'Scan finds every SpiderX on your local network; one click connects.',
            'points' => ['เครื่องในวงแลนเดียวกันคุยกันได้แม้อินเทอร์เน็ตล่ม'],
            'alt' => 'ภาพตัวอย่างหน้า Nearby ของ SpiderX รายการเครื่องที่เจอในวงแลนพร้อมปุ่ม Connect',
        ],
        [
            'img' => $shot('files.webp'), 'mock' => true, 'name' => 'แชร์ไฟล์ดึงจากหลายเครื่อง', 'eyebrow' => 'Swarm downloads',
            'th' => 'แชร์ไฟล์ให้เพื่อนเปิดดูรายการได้ ฝั่งที่ดาวน์โหลดดึงชิ้นส่วนจากทุกเครื่องที่มีไฟล์เดียวกันพร้อมกันแบบ BitTorrent ทุกชิ้นถูกตรวจค่า SHA-256 ก่อนเขียนลงดิสก์ และหยุดแล้วโหลดต่อได้',
            'en' => 'BitTorrent-style downloads from every peer that has the file, each chunk checked with SHA-256. Pause and resume.',
            'points' => ['ค้นหาไฟล์ในแคตตาล็อกของเพื่อน แยกตามหมวด', 'เห็นความเร็ว ความคืบหน้า และจำนวนเครื่องที่ส่งให้'],
            'alt' => 'ภาพตัวอย่างหน้า Files ของ SpiderX ดาวน์โหลดที่กำลังทำงาน ไฟล์จากเพื่อน และไฟล์ที่แชร์',
        ],
        [
            'img' => $shot('lan.webp'), 'mock' => true, 'name' => 'Virtual LAN สำหรับเกม', 'eyebrow' => 'LAN games',
            'th' => 'เปิด Virtual LAN แล้วแต่ละเครื่องได้ IP เสมือนในวง 10.147.x.x และ SpiderX ส่งต่อสัญญาณหาห้องของเกม LAN ไปให้เพื่อนที่ต่ออยู่ เกมที่เล่นได้แค่ในแลนจึงมองเห็นกัน',
            'en' => 'A virtual IP for every machine, and LAN-game discovery broadcasts relayed to your connected friends.',
            'points' => ['ฟังพอร์ตหาห้องของเกมที่นิยม เช่น Minecraft, Counter-Strike, StarCraft, Terraria', 'เห็นปริมาณรับ/ส่งของแต่ละเครื่อง'],
            'alt' => 'ภาพตัวอย่างหน้า Virtual LAN ของ SpiderX IP เสมือน เครื่องที่ต่ออยู่ และรายชื่อเกม',
        ],
    ];

    // ยังไม่เสร็จ — ทุกข้อเช็กแล้วว่าโค้ดยังไม่ทำงานจริง จึงติดป้าย "เร็ว ๆ นี้" ไม่ขายเป็นฟีเจอร์
    $roadmap = [
        [
            'key' => 'e2e', 'name' => 'เข้ารหัสปลายทางถึงปลายทาง', 'short' => 'X25519 · Ed25519 · AES-256-GCM', 'tier' => 'soon',
            'th' => 'กำลังพัฒนาการแลกกุญแจ X25519 และลายเซ็น Ed25519 เพื่อให้ทุกข้อความและไฟล์เข้ารหัส AES-256-GCM ด้วยกุญแจที่มีแค่สองเครื่องปลายทาง ส่วนนี้ยังไม่นับเป็นฟีเจอร์ที่ใช้ได้จนกว่าจะเสร็จและผ่านการตรวจ',
            'en' => 'End-to-end encryption is still being built and is not counted as a working feature yet.',
            'points' => [],
        ],
        [
            'key' => 'internet', 'name' => 'หากันข้ามอินเทอร์เน็ต', 'short' => 'DHT · ทะลุ NAT', 'tier' => 'soon',
            'th' => 'ตอนนี้ SpiderX หาเครื่องได้เองในวงแลนเดียวกัน ส่วนการค้นหาเพื่อนจากทั่วโลกด้วย DHT (Kademlia) และการทะลุ NAT / ไฟร์วอลล์ให้อัตโนมัติยังอยู่ระหว่างพัฒนา',
            'en' => 'Finding peers worldwide (Kademlia DHT) and automatic NAT traversal are in development.',
            'points' => [],
        ],
        [
            'key' => 'voice', 'name' => 'โทรเสียง', 'short' => 'Voice calls', 'tier' => 'soon',
            'th' => 'ปุ่ม Call มีในหน้าแชทแล้ว แต่การส่งเสียงระหว่างเครื่องยังไม่เสร็จ',
            'en' => 'Peer-to-peer voice calls are not finished yet.',
            'points' => [],
        ],
        [
            'key' => 'qr', 'name' => 'เพิ่มเพื่อนด้วย QR', 'short' => 'Scan · Show QR Code', 'tier' => 'soon',
            'th' => 'ปุ่ม Scan และ Show QR Code อยู่ในแอปแล้ว แต่ยังสแกนและสร้าง QR ไม่ได้ ระหว่างนี้เพิ่มเพื่อนด้วยการวาง SpiderX ID',
            'en' => 'QR scanning and QR codes are not ready; paste a SpiderX ID meanwhile.',
            'points' => [],
        ],
        [
            'key' => 'groups', 'name' => 'แชทกลุ่ม', 'short' => 'Group chat', 'tier' => 'soon',
            'th' => 'ยังไม่มีในแอป อยู่ในแผนของรุ่นถัดไป ตอนนี้แชทได้ทีละคน',
            'en' => 'Not in the app yet; chats are one to one for now.',
            'points' => [],
        ],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'SpiderX',
        'operatingSystem' => 'Windows 10, Windows 11',
        'applicationCategory' => 'CommunicationApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'เครือข่าย P2P mesh สำหรับ Windows: แชทตรงถึงเครื่องเพื่อน แชร์ไฟล์ดึงจากหลายเครื่องพร้อมกัน และ Virtual LAN สำหรับเกม ไม่มีเซิร์ฟเวอร์กลาง ไม่ต้องสมัคร',
        'image' => asset('images/products/spiderx/v2/og.jpg'),
        'url' => route('products.show', 'spiderx'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
    ];
    // ระหว่าง Coming Soon ยังขายไม่ได้ — ไม่ประกาศ Offer ให้เครื่องมือค้นหาเอาไปแสดงเป็นของที่ซื้อได้
    if (! $csSpiderX && $price > 0) {
        $ld['offers'] = ['@type' => 'Offer', 'price' => (string) $price, 'priceCurrency' => 'THB'];
    }
@endphp
<x-xp.page a="#22d3ee" b="#a78bfa" c="#f472b6" glow="124 58 237" g1="#a78bfa" g2="#ec4899" g3="#22d3ee">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="คุย แชร์ไฟล์ ตรงถึงกัน ไม่มีตัวกลาง"
               tagline-en="Chat and share files machine to machine, with no server in between."
               lead="SpiderX ต่อเครื่องเข้าหากันโดยตรงแบบ P2P: หาเครื่องในวงแลนเดียวกันให้เอง แชทกับเพื่อน แชร์ไฟล์ดึงจากหลายเครื่องพร้อมกัน และเปิด Virtual LAN ให้เกมที่เล่นได้แค่ในแลน — ตัวตนของคุณคือกุญแจที่สร้างในเครื่องคุณเอง ไม่ต้องสมัคร ไม่ใช้เบอร์โทรหรืออีเมล"
               lead-en="Peer-to-peer chat, swarm file sharing and LAN games for Windows. Your identity is a key made on your own machine: no sign-up, no phone number, no e-mail.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'SpiderX ' . $latest->version : 'SpiderX' }}</span>
            <span class="xp-chip">Windows 10 / 11</span>
            @if($csSpiderX)<x-xp.badge tier="soon" label="Coming Soon" />@endif
        </x-slot:chips>
        <x-slot:title>Spider<span class="xp-text-glow">X</span></x-slot:title>
        <x-slot:actions>
            @if($csSpiderX || $ownsSpiderX)
                <x-xp.download :href="$downloadHref" :coming-soon="$csSpiderX" th="ดาวน์โหลด" en="Download" />
            @else
                <a href="{{ route('products.index') }}" class="xp-btn-main inline-flex items-center px-7 py-4 rounded-xl font-extrabold text-lg transition">ดูแพคเกจ</a>
            @endif
            <a href="#pricing" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition">
                <x-bi th="ราคา" en="Pricing" />
            </a>
        </x-slot:actions>
        <x-slot:notes>
            @if($csSpiderX)
                <p class="mt-4 text-sm text-gray-400">
                    กำลังพัฒนา · ยังไม่เปิดขายและยังไม่มีไฟล์ให้ดาวน์โหลด@if($product->coming_soon_until) · กำหนดเปิด {{ $product->coming_soon_until->format('d/m/Y') }}@endif
                </p>
            @elseif($latest)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · ดาวน์โหลดได้ในหน้า บัญชีของฉัน หลังซื้อ</p>
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ไม่มีเซิร์ฟเวอร์กลาง</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ไม่ต้องสมัครสมาชิก</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> หาเครื่องในวงแลนเอง</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('dashboard.webp')" :eager="true" :mock="true"
                             alt="ภาพตัวอย่างหน้า Home ของ SpiderX: สถานะออนไลน์ จำนวนเครื่องที่ต่อ ข้อความ ไฟล์ และ SpiderX ID" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">ตัวตน · Identity</p>
                <p class="text-white font-extrabold text-lg leading-tight">กุญแจ<span class="text-cyan-300">ในเครื่องคุณ</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================== WHAT IT DOES ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Peer to peer" title="ต่อกันตรง ๆ" title2="ไม่มีตัวกลาง"
                       sub="ข้อความ ไฟล์ และทราฟฟิกเกมวิ่งระหว่างเครื่องคุณกับเครื่องเพื่อนโดยตรง ไม่ผ่านเซิร์ฟเวอร์ของเรา"
                       en="Messages, files and game traffic go straight between your machines, never through our servers." />
            <x-xp.rows :items="$featureRows" />
        </div>
    </section>

    {{-- =========================== IN DEVELOPMENT =========================== --}}
    <section class="relative py-20 isolate" id="roadmap">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="In development" title="ที่ยังทำอยู่" title2="บอกกันตรง ๆ"
                       sub="SpiderX ยังเป็น Coming Soon ส่วนเหล่านี้กำลังพัฒนาและยังไม่นับเป็นฟีเจอร์ที่ใช้ได้"
                       en="SpiderX is still coming soon. These parts are in development and are not counted as working features." />
            <x-xp.explorer id="roadmap" label="ส่วนที่กำลังพัฒนาของ SpiderX" :tools="$roadmap" />
        </div>
    </section>

    {{-- ============================ TRUST ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Yours, on your machine" title="ของคุณ" title2="อยู่ในเครื่องคุณ"
                       sub="ไม่มีบัญชีให้สมัคร ไม่มีเซิร์ฟเวอร์เก็บข้อความ"
                       en="No account to create, no server holding your messages." />
            <x-xp.cards :items="[
                ['icon' => 'key', 'title' => 'ตัวตนคือกุญแจ', 'body' => 'เปิดแอปครั้งแรก SpiderX สร้างกุญแจในเครื่องและได้ SpiderX ID (spx1…) ทันที ไม่ต้องสมัคร ไม่ใช้เบอร์โทรหรืออีเมล', 'en' => 'An identity made on your machine'],
                ['icon' => 'lock', 'title' => 'กุญแจอยู่ในที่เก็บของระบบ', 'body' => 'กุญแจส่วนตัวเก็บใน SecureStorage ของ Windows สำรองออกมาหรือนำเข้าบนเครื่องใหม่ได้จากหน้า Settings', 'en' => 'Back up and restore from Settings'],
                ['icon' => 'server', 'title' => 'ไม่มีเซิร์ฟเวอร์กลาง', 'body' => 'เครื่องต่อกันตรงด้วย UDP และ TCP แอปไม่ส่งข้อความหรือไฟล์ผ่านเซิร์ฟเวอร์ของ XMAN', 'en' => 'No server in the middle'],
                ['icon' => 'doc', 'title' => 'ตรวจทุกชิ้นของไฟล์', 'body' => 'ไฟล์ถูกแบ่งเป็นชิ้น แต่ละชิ้นมีค่า SHA-256 ชิ้นที่ค่าไม่ตรงจะไม่ถูกเขียนลงไฟล์', 'en' => 'Every chunk checked with SHA-256'],
                ['icon' => 'user', 'title' => 'เพื่อนต้องตอบรับ', 'body' => 'เพิ่มเพื่อนคือการส่งคำขอ อีกฝ่ายกดยอมรับก่อน และคำขอที่ไม่รู้จักบล็อกได้', 'en' => 'Contacts are requested and accepted'],
                ['icon' => 'globe', 'title' => 'ใช้ในวงแลนได้แม้เน็ตล่ม', 'body' => 'เครื่องในวงแลนเดียวกันหากันและคุยกันได้โดยไม่ต้องออกอินเทอร์เน็ต', 'en' => 'Works on a LAN without the internet'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="ราคา" :title2="$csSpiderX ? 'เมื่อเปิดขาย' : null"
                       :sub="$csSpiderX ? 'SpiderX ยังไม่เปิดขาย ราคาด้านล่างคือราคาที่ตั้งไว้ในร้านตอนนี้' : 'ราคาจากร้านโดยตรง'"
                       :en="$csSpiderX ? 'Not on sale yet: this is the price currently set in the shop.' : 'The price straight from the shop.'" />

            <div class="max-w-md mx-auto">
                <div class="xp-featured">
                    <div class="p-7 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-2xl font-black text-white">SpiderX</h3>
                            @if($csSpiderX)<x-xp.badge tier="soon" label="Coming Soon" />@endif
                        </div>
                        <p class="text-gray-400 text-sm mb-6">P2P mesh network · Windows</p>
                        @if($price > 0)
                            <div class="mb-6"><span class="text-5xl font-black text-white">฿{{ number_format($price) }}</span></div>
                            <x-tpix-price variant="detail" :thb="$price" :tpix="$product->price_tpix" class="-mt-4 mb-6" />
                        @else
                            <p class="mb-6 text-lg font-bold text-white">ยังไม่ประกาศราคา</p>
                        @endif
                        <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['แชทตรงถึงเครื่องเพื่อน', 'หาเครื่องในวงแลนอัตโนมัติ', 'แชร์ไฟล์ดึงจากหลายเครื่อง ตรวจ SHA-256', 'Virtual LAN สำหรับเกม']" />
                        @if($csSpiderX)
                            {{-- ComingSoonPurchase ปฏิเสธทุกทางซื้ออยู่แล้ว — หน้านี้ก็ไม่มีปุ่มซื้อหรือลิงก์ไฟล์ --}}
                            <span class="xp-btn-ghost xp-btn-off block w-full py-3.5 text-center rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
                        @elseif($ownsSpiderX)
                            <a href="{{ $downloadHref }}" class="xp-btn-main block w-full py-3.5 text-center rounded-xl font-extrabold transition">ดาวน์โหลด — บัญชีของฉัน</a>
                        @else
                            <a href="{{ route('products.index') }}" class="xp-btn-pro block w-full py-3.5 text-center rounded-xl font-extrabold transition">ดูแพคเกจ</a>
                        @endif
                    </div>
                </div>
            </div>
            <p class="text-center text-xs text-gray-500 mt-6">เงื่อนไข License (อายุการใช้งานและจำนวนเครื่องต่อคีย์) ยังไม่ประกาศ</p>
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Windows 10 เวอร์ชัน 1809 ขึ้นไป หรือ Windows 11', 'Windows 10 (1809+) or 11'],
            ['ไม่ต้องสมัครบัญชี', 'ตัวตนสร้างในเครื่องตอนเปิดแอปครั้งแรก', 'No account'],
            ['พอร์ตเครือข่าย', 'UDP 45678 และ TCP 45679 · Virtual LAN ใช้ UDP 45680–45681', 'Ports SpiderX listens on'],
            ['ระบบอื่น', 'ยังไม่มีตัวติดตั้งสำหรับ macOS, Linux, Android หรือ iOS', 'Windows only for now'],
        ]"
        specs-note="ถ้าไฟร์วอลล์ถามตอนเปิดแอปครั้งแรก ให้อนุญาต SpiderX บนเครือข่ายส่วนตัว เครื่องในวงแลนจึงหากันเจอ"
        steps-title="เมื่อเปิดใช้ได้: 3 ขั้น"
        :steps="[
            ['เปิด SpiderX', 'แอปสร้างกุญแจและ SpiderX ID ให้ทันที ไม่ต้องสมัคร', 'Open SpiderX: your ID is made on the spot'],
            ['เพิ่มเพื่อน', 'กด Scan ในแท็บ Nearby เพื่อหาเครื่องในวงแลน หรือวาง SpiderX ID ของเพื่อนในหน้า Contacts', 'Scan nearby or paste a friend’s ID'],
            ['คุย แชร์ไฟล์ หรือเปิด Virtual LAN', 'เพื่อนยอมรับคำขอแล้วเริ่มแชท แชร์ไฟล์ หรือเล่นเกม LAN ด้วยกันได้', 'Chat, share files or play LAN games'],
        ]" />

    <x-xp.faq :items="[
        ['ตอนนี้ซื้อหรือดาวน์โหลดได้ไหม?', $csSpiderX ? 'ยังไม่ได้ SpiderX อยู่ในสถานะ Coming Soon ยังไม่เปิดขายและยังไม่มีไฟล์ให้ดาวน์โหลด เมื่อเปิดแล้วปุ่มดาวน์โหลดและการซื้อจะขึ้นในหน้านี้' : 'ได้ ผู้ที่ซื้อแล้วดาวน์โหลดได้ที่หน้า บัญชีของฉัน › ดาวน์โหลด'],
        ['ราคาเท่าไร?', $price > 0 ? 'ราคาในร้านตอนนี้ ฿' . number_format($price) . ' ตัวเลขในหน้านี้อ่านจากร้านโดยตรง ส่วนเงื่อนไข License ยังไม่ประกาศ' : 'ยังไม่ประกาศราคา'],
        ['ต้องสมัครหรือใช้เบอร์โทรไหม?', 'ไม่ต้อง SpiderX สร้างกุญแจในเครื่องคุณตอนเปิดแอปครั้งแรก ได้ SpiderX ID ขึ้นต้นด้วย spx1 ให้แชร์กับเพื่อน ไม่ใช้เบอร์โทร อีเมล หรือบัญชีใด ๆ'],
        ['ข้อความผ่านเซิร์ฟเวอร์ของ XMAN ไหม?', 'ไม่ เครื่องต่อกันตรงด้วย UDP และ TCP ข้อความและไฟล์วิ่งจากเครื่องถึงเครื่อง ไม่มีเซิร์ฟเวอร์กลางเก็บไว้'],
        ['เข้ารหัสแล้วหรือยัง?', 'ระบบเข้ารหัสปลายทางถึงปลายทางยังอยู่ระหว่างพัฒนา จึงยังไม่นับเป็นฟีเจอร์ที่ใช้ได้ในตอนนี้'],
        ['ใช้บน Mac หรือมือถือได้ไหม?', 'ตอนนี้แอปสร้างสำหรับ Windows 10 (1809 ขึ้นไป) และ Windows 11 เท่านั้น ยังไม่มีตัวติดตั้งสำหรับ macOS, Linux, Android หรือ iOS'],
        ['คุยกับเพื่อนที่อยู่คนละที่ได้ไหม?', 'วันนี้ SpiderX หาเครื่องให้เองเฉพาะในวงแลนเดียวกัน การหาเพื่อนข้ามอินเทอร์เน็ตอัตโนมัติ (DHT และการทะลุ NAT) ยังอยู่ระหว่างพัฒนา'],
        ['Virtual LAN ใช้กับเกมอะไรได้?', 'เกมที่หาห้องกันในแลนด้วย broadcast เช่น Minecraft, Counter-Strike, StarCraft, Terraria SpiderX ให้ IP เสมือน 10.147.x.x และส่งต่อสัญญาณหาห้องไปให้เพื่อนที่ต่ออยู่'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="เครือข่ายของคุณ" glow="ไม่มีตัวกลาง"
              :sub="$csSpiderX ? 'SpiderX กำลังพัฒนา กลับมาที่หน้านี้เมื่อเปิดดาวน์โหลด' : 'คุย แชร์ไฟล์ และเล่นเกม LAN กับเพื่อน แบบเครื่องถึงเครื่อง'"
              en="Your network, with nobody in the middle.">
        @if($csSpiderX || $ownsSpiderX)
            <x-xp.download :href="$downloadHref" :coming-soon="$csSpiderX" th="ดาวน์โหลด" en="Download" class="!px-8" />
        @else
            <a href="{{ route('products.index') }}" class="xp-btn-main inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ดูแพคเกจ</a>
        @endif
        <a href="#pricing" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition"><x-bi th="ดูราคา" en="Pricing" /></a>
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows 10 (1809+) / 11 · ไม่ต้องสมัคร · ไม่มีเซิร์ฟเวอร์กลาง</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
