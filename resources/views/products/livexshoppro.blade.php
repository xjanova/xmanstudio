@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ไม่มีปุ่มดาวน์โหลดและไม่มีทางซื้อ (กฎเจ้าของ 2026-10-12) --}}
@php $csLive = $product->isComingSoon(); @endphp

@section('title', 'Live x Shop Pro — ระบบหลังบ้านสำหรับไลฟ์ขายของ' . ($csLive ? ' · Coming Soon' : '') . ' | XMAN Studio')
@section('meta_description', 'Live x Shop Pro โปรแกรมหลังบ้านบน Windows สำหรับแม่ค้าไลฟ์สด (กำลังพัฒนา): ดึงแชท Facebook Live, TikTok Live และ LINE OA มาที่เดียว เปลี่ยน CF เป็นออเดอร์ ตรวจสลิป เชื่อมขนส่ง และแอปมือถือ · A Windows back office for live-stream sellers, in development.')
@section('og_image', asset('images/products/livexshop-pro/v2/og.jpg'))

@section('content')
@php
    // ราคาอ่านจากแถวสินค้าในร้าน (products.price) ที่เดียว — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $price = (float) $product->price;
    $original = $product->original_price && $product->original_price > $product->price ? (float) $product->original_price : null;

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว) — ยังไม่มีจนกว่าจะเปิดดาวน์โหลด
    $latest = $product->latestVersion();

    // ไม่มีลิงก์ดาวน์โหลดสาธารณะ: คนที่ซื้อแล้วโหลดที่ศูนย์ดาวน์โหลดของบัญชี (กลไกเดิมของหน้า products/show)
    $downloadHref = route('customer.downloads');
    $ownsLive = auth()->check() && $hasPurchased;

    // แถบสถานะ License ของคนที่มีคีย์อยู่แล้ว (ยกมาจาก products/show)
    $licenseNote = null;
    if (isset($userLicense) && $userLicense) {
        if ($userLicense->isValid()) {
            $daysLeft = $userLicense->daysRemaining();
            $licenseNote = ($daysLeft <= 7 && $userLicense->license_type !== 'lifetime')
                ? ['text-amber-200', 'License ใกล้หมดอายุ — เหลืออีก ' . $daysLeft . ' วัน' . ($userLicense->expires_at ? ' (' . $userLicense->expires_at->format('d/m/Y') . ')' : ''), 'ต่ออายุตอนนี้']
                : ['text-emerald-200', 'คุณมี License แล้ว · ' . ($userLicense->license_type === 'lifetime' ? 'ใบอนุญาตตลอดชีพ' : 'หมดอายุ ' . $userLicense->expires_at?->format('d/m/Y')), 'ดู License ของฉัน'];
        } else {
            $licenseNote = ['text-red-300', 'License ของคุณหมดอายุแล้ว' . ($userLicense->expires_at ? ' เมื่อ ' . $userLicense->expires_at->format('d/m/Y') : ''), 'ต่ออายุตอนนี้'];
        }
    }

    // ภาพหน้าจอ: แอปยังไม่มีข้อมูลจริงให้จับภาพ ทุกภาพเป็น mockup ที่วาดจาก XAML ของแอป (ข้อมูลในภาพแต่งขึ้นทั้งหมด)
    // สร้างใหม่ได้ด้วย scripts/product-mockups/build.py livexshop-pro
    $shot = fn (string $file) => asset('images/products/livexshop-pro/v2/' . $file);

    // หน้าจอที่ออกแบบไว้ในโค้ดแล้ว (D:\Code\LiveShop pro\src\Desktop\Views) — งานเบื้องหลังยังไม่ทำ จึงติดป้าย "เร็ว ๆ นี้" ทุกแถว
    $screenRows = [
        [
            'img' => $shot('live.webp'), 'mock' => true, 'name' => 'ดึงแชทจากไลฟ์มาไว้ที่เดียว', 'eyebrow' => 'Live chat capture', 'tier' => 'soon',
            'th' => 'เลือก Facebook, TikTok หรือ LINE ตั้งชื่อรอบไลฟ์ วาง URL ของไลฟ์ แล้วกด เริ่มดึงแชท — คอมเมนต์ไหลเข้ามาในหน้าเดียว ข้อความ CF ถูกไฮไลต์ และสลับดูเฉพาะ CF ได้',
            'en' => 'Pick the platform, paste the live URL and start: comments land in one list, CF lines highlighted.',
            'points' => ['สถิติระหว่างไลฟ์: แชททั้งหมด CF ยอดขาย ออเดอร์ CF ต่อนาที และ CF ซ้ำ', 'Facebook และ TikTok ดึงผ่านบอตเบราว์เซอร์ · LINE OA ผ่าน LINE Messaging API'],
            'alt' => 'ภาพตัวอย่างหน้าไลฟ์สดของ Live x Shop Pro แผงควบคุมการดึงแชท สถิติไลฟ์ และรายการแชทที่ไฮไลต์ CF',
        ],
        [
            'img' => $shot('cf.webp'), 'mock' => true, 'name' => 'จาก CF เป็นออเดอร์', 'eyebrow' => 'CF to order', 'tier' => 'soon',
            'th' => 'ทุก CF เข้าคิวรอดำเนินการ เลือกแล้วโปรแกรมจับรหัส CF กับสินค้าให้ ปรับจำนวน เพิ่มสินค้า ใส่หมายเหตุ แล้วกด สร้างออเดอร์ — ข้างกันเห็นข้อมูลลูกค้า ยอดซื้อรวม และออเดอร์ล่าสุดของเขา',
            'en' => 'Every CF waits in a queue; pick one, check the matched product and create the order, with the buyer’s history beside it.',
            'points' => ['รูปแบบ CF ที่วางแผนรองรับ: cf1 · CF 2 · ซีเอฟ1 · สั่ง1 · เอา1 · 2ชิ้น · #1', 'หลายตัวในข้อความเดียวได้ เช่น "cf1 2ชิ้น cf3"', 'CF ซ้ำถูกแยกไว้ในแท็บ Duplicate'],
            'alt' => 'ภาพตัวอย่างหน้าแชท CF ของ Live x Shop Pro คิว CF ฟอร์มสร้างออเดอร์ และข้อมูลลูกค้า',
        ],
        [
            'img' => $shot('orders.webp'), 'mock' => true, 'name' => 'ออเดอร์ทั้งร้านในตารางเดียว', 'eyebrow' => 'Orders', 'tier' => 'soon',
            'th' => 'นับออเดอร์ตามสถานะ รอยืนยัน ยืนยันแล้ว รอแพ็ค จัดส่งแล้ว สำเร็จ และยกเลิก ค้นด้วยเลขออเดอร์ ชื่อ หรือเบอร์โทร กรองและส่งออกได้ เปิดดูหรือพิมพ์จากแถวนั้นได้เลย',
            'en' => 'Orders counted by status, searchable by number, name or phone, exportable, viewable and printable from the row.',
            'points' => ['ออเดอร์มาได้ทั้งจากไลฟ์ ขายหน้าร้าน (POS) และสร้างเอง'],
            'alt' => 'ภาพตัวอย่างหน้าออเดอร์ของ Live x Shop Pro ตัวนับตามสถานะและตารางออเดอร์',
        ],
    ];

    // ส่วนที่อยู่ในแผนพัฒนา (docs/ROADMAP.md, docs/FEATURES.md ของโปรเจกต์) — ยังไม่มีโค้ดที่ทำงานจริงสักข้อ
    $roadmap = [
        [
            'key' => 'slip', 'name' => 'อ่านสลิปและจับสลิปปลอม', 'short' => 'OCR · สลิปซ้ำ · SMS ธนาคาร', 'tier' => 'soon',
            'th' => 'วางแผนให้อ่านสลิปโอนเงินด้วย OCR ดึงธนาคาร ยอด วันเวลา และเลขอ้างอิง แล้วตรวจว่าสลิปน่าสงสัย ปลอม ซ้ำ หรือหมดอายุ จับคู่กับ SMS แจ้งเงินเข้าของธนาคาร และเลือกได้ว่าจะให้ตรวจอัตโนมัติ ตรวจเอง หรือผสม',
            'en' => 'Planned: slip OCR, fake / duplicate / expired slip checks, bank SMS matching, with auto, manual or hybrid review.',
            'points' => ['ธนาคารที่วางแผนไว้: กสิกรไทย ไทยพาณิชย์ กรุงเทพ กรุงไทย กรุงศรี ออมสิน ทีทีบี และพร้อมเพย์'],
        ],
        [
            'key' => 'shipping', 'name' => 'เชื่อมขนส่ง', 'short' => 'Kerry · Flash · J&T', 'tier' => 'soon',
            'th' => 'วางแผนให้ส่งออเดอร์ไปยัง Kerry Express, Flash Express และ J&T Express พิมพ์ใบปะหน้าจากโปรแกรม รองรับเก็บเงินปลายทาง และดึงสถานะพัสดุกลับมาอัตโนมัติ',
            'en' => 'Planned: Kerry, Flash and J&T bookings, label printing, COD and tracking sync.',
            'points' => [],
        ],
        [
            'key' => 'pos', 'name' => 'POS และสต็อก', 'short' => 'ขายหน้าร้าน · บาร์โค้ด', 'tier' => 'soon',
            'th' => 'วางแผนให้ขายหน้าร้านจากสต็อกเดียวกับไลฟ์ สแกนและพิมพ์บาร์โค้ด รับเงินสด พิมพ์ใบเสร็จ และเตือนเมื่อสินค้าใกล้หมด',
            'en' => 'Planned: point of sale on the same stock as the live, barcodes, cash and receipts, low-stock alerts.',
            'points' => [],
        ],
        [
            'key' => 'packing', 'name' => 'สถานีแพ็คของ', 'short' => 'จอแยก · LAN / VPN', 'tier' => 'soon',
            'th' => 'วางแผนให้เปิดอีกจอหนึ่งเป็นคิวแพ็คของ เห็นออเดอร์ใหม่แบบเรียลไทม์ ยืนยันสินค้าด้วยบาร์โค้ด และพิมพ์ใบปะหน้า ต่อกับเครื่องหลักผ่านวงแลน หรือผ่าน VPN เมื่ออยู่คนละที่',
            'en' => 'Planned: a separate packing screen fed in real time over LAN or VPN.',
            'points' => [],
        ],
        [
            'key' => 'obs', 'name' => 'OBS Overlay', 'short' => 'ยอดขาย · ออเดอร์ล่าสุดบนไลฟ์', 'tier' => 'soon',
            'th' => 'วางแผนหน้า overlay ให้ใส่ใน OBS ระหว่างไลฟ์ แสดงยอดขาย ออเดอร์ล่าสุด และสินค้าที่กำลังขาย อัปเดตเองตามออเดอร์จริง',
            'en' => 'Planned: OBS overlays for the sales counter, latest orders and the product on show.',
            'points' => [],
        ],
        [
            'key' => 'portal', 'name' => 'หน้าเช็คสถานะของลูกค้า', 'short' => 'เว็บในตัวโปรแกรม', 'tier' => 'soon',
            'th' => 'วางแผนเว็บเล็ก ๆ ในตัวโปรแกรมให้ลูกค้าเปิดเช็คสถานะออเดอร์และเลขพัสดุของตัวเอง',
            'en' => 'Planned: a small built-in web page where buyers check their order and tracking number.',
            'points' => [],
        ],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Live x Shop Pro',
        'operatingSystem' => 'Windows 10, Windows 11',
        'applicationCategory' => 'BusinessApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'โปรแกรมหลังบ้านบน Windows สำหรับไลฟ์ขายของ (กำลังพัฒนา): ดึงแชท Facebook Live, TikTok Live และ LINE OA เปลี่ยน CF เป็นออเดอร์ ตรวจสลิป และเชื่อมขนส่ง',
        'image' => asset('images/products/livexshop-pro/v2/og.jpg'),
        'url' => route('products.show', 'livexshop-pro'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
    ];
    // ระหว่าง Coming Soon ยังขายไม่ได้ — ไม่ประกาศ Offer ให้เครื่องมือค้นหาเอาไปแสดงเป็นของที่ซื้อได้
    if (! $csLive && ! $product->is_custom && $price > 0) {
        $ld['offers'] = ['@type' => 'Offer', 'price' => (string) $price, 'priceCurrency' => 'THB'];
    }
@endphp
<x-xp.page a="#ff6b9d" b="#ff9ec0" c="#8b83ff" glow="108 99 255" g1="#6c63ff" g2="#ff6b9d" g3="#fbbf24">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="ระบบหลังบ้านสำหรับไลฟ์ขายของ"
               tagline-en="The back office for selling on live streams."
               lead="Live x Shop Pro กำลังพัฒนาเป็นโปรแกรมบน Windows ที่ดึงคอมเมนต์จาก Facebook Live, TikTok Live และ LINE OA มารวมที่เดียว เปลี่ยน CF เป็นออเดอร์ แล้วต่อไปถึงการเก็บเงิน แพ็คของ และส่งของ — ฐานข้อมูลร้านเก็บในเครื่องของคุณ"
               lead-en="A Windows program, in development, that gathers live comments from Facebook, TikTok and LINE, turns CFs into orders and carries them through payment, packing and shipping.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'Live x Shop Pro ' . $latest->version : 'Live x Shop Pro' }}</span>
            <span class="xp-chip">Windows 10 / 11</span>
            @if($csLive)<x-xp.badge tier="soon" label="Coming Soon" />@endif
        </x-slot:chips>
        <x-slot:title>Live x Shop <span class="xp-text-glow">Pro</span></x-slot:title>
        <x-slot:actions>
            @if($csLive || $ownsLive)
                <x-xp.download :href="$downloadHref" :coming-soon="$csLive" th="ดาวน์โหลด" en="Download" />
            @endif
            @if($product->is_custom)
                <a href="{{ route('quote.index') }}" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">ติดต่อสอบถาม</a>
            @elseif(! $csLive)
                <a href="#pricing" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">
                    {{ $ownsLive ? 'ซื้อ License เพิ่ม' : 'ซื้อ License' }}@if($price > 0) — ฿{{ number_format($price) }}@endif
                </a>
            @else
                <a href="#pricing" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition"><x-bi th="ราคา" en="Pricing" /></a>
            @endif
        </x-slot:actions>
        <x-slot:notes>
            @if($csLive)
                <p class="mt-4 text-sm text-gray-400">
                    กำลังพัฒนา · ยังไม่เปิดขายและยังไม่มีไฟล์ให้ดาวน์โหลด@if($product->coming_soon_until) · กำหนดเปิด {{ $product->coming_soon_until->format('d/m/Y') }}@endif
                    · <a href="{{ route('quote.index') }}" class="xp-link font-semibold">แจ้งเตือนเมื่อเปิดขาย</a>
                </p>
            @elseif($latest)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · ดาวน์โหลดได้ในหน้า บัญชีของฉัน หลังซื้อ</p>
            @endif
            @if($licenseNote)
                <p class="mt-3 text-sm {{ $licenseNote[0] }}" data-license-status>
                    {{ $licenseNote[1] }} ·
                    <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline hover:text-white">{{ $licenseNote[2] }}</a>
                </p>
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> Facebook · TikTok · LINE</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> หน้าจอภาษาไทยทั้งโปรแกรม</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ข้อมูลร้านอยู่ในเครื่องคุณ</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('dashboard.webp')" :eager="true" :mock="true"
                             alt="ภาพตัวอย่างหน้าแดชบอร์ดของ Live x Shop Pro: ยอดขายวันนี้ ออเดอร์ งานที่รอ CF สลิป แพ็ค ส่ง ออเดอร์ล่าสุด และสินค้าขายดี" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">แชท → ออเดอร์ · CF to order</p>
                <p class="text-white font-extrabold text-lg leading-tight">"cf1 2ชิ้น" <span class="text-pink-300">→ ออเดอร์</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ======================== SCREENS (IN DEVELOPMENT) ======================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="In development" title="จากคอมเมนต์" title2="ถึงออเดอร์"
                       sub="Live x Shop Pro ยังอยู่ระหว่างพัฒนา ภาพด้านล่างคือหน้าจอที่ออกแบบไว้ในโค้ดของโปรแกรม (ข้อมูลในภาพแต่งขึ้น) ส่วนการทำงานเบื้องหลังกำลังสร้าง"
                       en="Still in development: these are the screens laid out in the program’s code, with made-up data. The work behind them is being built." />
            <x-xp.rows :items="$screenRows" />
        </div>
    </section>

    {{-- =========================== ROADMAP =========================== --}}
    <section class="relative py-20 isolate" id="roadmap">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="On the roadmap" title="หลังไลฟ์จบ" title2="งานยังไม่จบ"
                       sub="เก็บเงิน แพ็ค ส่ง และขายหน้าร้าน — ส่วนเหล่านี้อยู่ในแผนพัฒนา ยังไม่นับเป็นฟีเจอร์ที่ใช้ได้"
                       en="Payment, packing, shipping and the shop counter are on the roadmap and are not counted as working features yet." />
            <x-xp.explorer id="roadmap" label="ส่วนที่อยู่ในแผนพัฒนาของ Live x Shop Pro" :tools="$roadmap" />
        </div>
    </section>

    {{-- ============================ STATUS ============================ --}}
    <section class="relative py-20" id="status">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Where it stands" title="บอกตรง ๆ" title2="ว่าถึงไหนแล้ว"
                       sub="แผนพัฒนามี 5 ขั้น ตอนนี้อยู่ขั้นแรก"
                       en="Five phases. The first is under way." />
            <x-xp.cards :items="[
                ['icon' => 'chip', 'title' => '1 · โครงสร้างโปรแกรม', 'body' => 'กำลังทำ: ฐานข้อมูลในเครื่อง ข้อมูลสินค้า ลูกค้า ออเดอร์ การชำระเงิน การจัดส่ง และหน้าจอหลักวางแล้ว กำลังต่อหน้าจอเข้ากับข้อมูลจริง', 'en' => 'Foundation — in progress'],
                ['icon' => 'sparkles', 'title' => '2 · แชท CF และออเดอร์', 'tier' => 'soon', 'body' => 'ดึงแชท Facebook / TikTok / LINE จับ CF สร้างออเดอร์ จัดการสินค้าและลูกค้า', 'en' => 'Chat capture, CF detection, orders'],
                ['icon' => 'doc', 'title' => '3 · การเงินและขนส่ง', 'tier' => 'soon', 'body' => 'อ่านสลิป จับสลิปปลอม SMS ธนาคาร เชื่อมขนส่ง พิมพ์ใบปะหน้า และ POS', 'en' => 'Slips, shipping, point of sale'],
                ['icon' => 'phone', 'title' => '4 · มือถือ สถานีแพ็ค OBS', 'tier' => 'soon', 'body' => 'แอปมือถือ จอแพ็คของ OBS overlay หน้าเช็คสถานะของลูกค้า และระบบ License', 'en' => 'Phone app, packing station, overlays, licensing'],
                ['icon' => 'shield', 'title' => '5 · ทดสอบและเปิดขาย', 'tier' => 'soon', 'body' => 'ทดสอบกับผู้ขายจริง ตัวติดตั้ง อัปเดตอัตโนมัติ และคู่มือ ก่อนเปิดขายในหน้านี้', 'en' => 'Testing, installer, updates, release'],
                ['icon' => 'server', 'title' => 'ข้อมูลร้านอยู่ในเครื่องคุณ', 'body' => 'ฐานข้อมูลร้าน (SQLite) เก็บในเครื่อง Windows ของคุณ ไม่ได้ฝากไว้บนเซิร์ฟเวอร์ของ XMAN', 'en' => 'Your shop’s database stays on your PC'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="ราคา" :title2="$csLive ? 'เมื่อเปิดขาย' : null"
                       :sub="$csLive ? 'Live x Shop Pro ยังไม่เปิดขาย ราคาด้านล่างคือราคาที่ตั้งไว้ในร้านตอนนี้' : 'ราคาจากร้านโดยตรง'"
                       :en="$csLive ? 'Not on sale yet: this is the price currently set in the shop.' : 'The price straight from the shop.'" />

            <div class="max-w-md mx-auto">
                <div class="xp-featured">
                    <div class="p-7 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-2xl font-black text-white">Live x Shop Pro</h3>
                            @if($csLive)<x-xp.badge tier="soon" label="Coming Soon" />@endif
                        </div>
                        <p class="text-gray-400 text-sm mb-6">Live commerce · Windows</p>
                        @if($product->is_custom)
                            <p class="mb-6 text-3xl font-black text-white">สอบถามราคา</p>
                        @elseif($price > 0)
                            <div class="mb-6 flex flex-wrap items-baseline gap-3">
                                <span class="text-5xl font-black text-white">฿{{ number_format($price) }}</span>
                                @if($original)
                                    <span class="text-xl text-gray-500 line-through">฿{{ number_format($original) }}</span>
                                    <x-xp.badge tier="pro" :label="'-' . round((1 - $price / $original) * 100) . '%'" />
                                @endif
                            </div>
                            <x-tpix-price variant="detail" :thb="$price" :tpix="$product->price_tpix" class="-mt-4 mb-6" />
                        @else
                            <p class="mb-6 text-lg font-bold text-white">ยังไม่ประกาศราคา</p>
                        @endif
                        <p class="text-xs text-gray-500 mb-3">ขอบเขตที่วางแผนไว้ · Planned scope</p>
                        <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['โปรแกรมหลักบน Windows', 'ดึงแชท Facebook · TikTok · LINE และเปลี่ยน CF เป็นออเดอร์', 'สินค้า สต็อก ลูกค้า ตรวจสลิป และขนส่ง', 'แอปมือถือและสถานีแพ็คของ']" />
                        @if($csLive)
                            {{-- ComingSoonPurchase ปฏิเสธทุกทางซื้ออยู่แล้ว — หน้านี้ก็ไม่มีปุ่มซื้อหรือลิงก์ไฟล์ --}}
                            <span class="xp-btn-ghost xp-btn-off block w-full py-3.5 text-center rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
                            <a href="{{ route('quote.index') }}" class="xp-link block text-center text-sm font-semibold mt-4">แจ้งเตือนเมื่อเปิดขาย →</a>
                        @elseif($product->is_custom)
                            <a href="{{ route('quote.index') }}" class="xp-btn-pro block w-full py-3.5 text-center rounded-xl font-extrabold transition">ติดต่อสอบถามราคา</a>
                        @else
                            {{-- ราคามาจากแถวสินค้า ไม่ใช่จากฟอร์ม — ตะกร้าคิดราคาเอง --}}
                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="xp-btn-pro block w-full py-3.5 text-center rounded-xl font-extrabold transition cursor-pointer">
                                    {{ $ownsLive ? 'ซื้อ License เพิ่ม' : 'ซื้อ License' }}@if($price > 0) — ฿{{ number_format($price) }}@endif
                                </button>
                            </form>
                            @if($ownsLive)
                                <a href="{{ $downloadHref }}" class="xp-btn-main block w-full py-3.5 mt-3 text-center rounded-xl font-extrabold transition">ดาวน์โหลด — บัญชีของฉัน</a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            <p class="text-center text-xs text-gray-500 mt-6">เงื่อนไข License (อายุการใช้งาน จำนวนเครื่องต่อคีย์ และการทดลองใช้) ยังไม่ประกาศ จะแจ้งในหน้านี้ก่อนเปิดขาย</p>
        </div>
    </section>

    {{-- ===================== MOBILE APP (กำลังพัฒนา) ===================== --}}
    <section class="relative py-20 scroll-mt-20" id="mobile">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-7">
                    <p class="xp-eyebrow mb-3">Mobile chat capture</p>
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-4 flex flex-wrap items-center gap-3">แอปมือถือคู่หู <x-xp.badge tier="soon" /></h2>
                    <p class="text-lg text-gray-300 leading-relaxed mb-2">
                        สำหรับวันที่ไลฟ์จากโทรศัพท์: แอปมือถือออกแบบให้ดึงแชทจากไลฟ์บนมือถือส่งเข้าโปรแกรมบนคอมแบบเรียลไทม์
                        จับคู่ด้วยการสแกน QR จากโปรแกรมบนคอม (ตั้งค่า › Mobile App) หรือใส่ IP ของคอมเอง
                    </p>
                    <p class="text-sm text-gray-500 leading-relaxed mb-6">For lives run from a phone: the app is designed to pass the chat to the desktop program, paired by QR code over the same network.</p>
                    <x-xp.ticks class="mb-6" :items="['หน้าเชื่อมต่อมีในโค้ดแล้ว ส่วนการจับคู่และดึงแชทยังพัฒนาอยู่', 'มือถือกับคอมต้องอยู่ในเครือข่ายเดียวกัน', 'โปรเจกต์ตั้งเป้า Android 7.0 ขึ้นไป และ iOS 14.2 ขึ้นไป']" />
                    <p class="text-sm text-gray-400">ยังไม่มีไฟล์ให้ดาวน์โหลด</p>
                </div>
                <div class="lg:col-span-5">
                    <figure class="max-w-xs mx-auto text-center">
                        <x-xp.screen phone :mock="true" :src="$shot('connect.webp')" alt="ภาพตัวอย่างหน้าเชื่อมต่อของแอปมือถือ Live x Shop Pro: สแกน QR Code หรือใส่ IP ของโปรแกรมบนคอม" :width="390" :height="845" />
                        <figcaption class="mt-3 text-xs text-gray-500">หน้าเชื่อมต่อ วาดจากโค้ดของแอป</figcaption>
                    </figure>
                </div>
            </div>
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Windows 10 (1903 ขึ้นไป) หรือ Windows 11 — โปรแกรมหลักเป็นแอป Windows', 'Windows 10 (1903+) or 11'],
            ['สเปกเครื่อง', 'RAM 4 GB (แนะนำ 8 GB) · พื้นที่ 500 MB (แนะนำ 2 GB) · จอ 1366×768 ขึ้นไป (แนะนำ 1920×1080)', 'RAM 4 GB, 500 MB disk, 1366×768'],
            ['อินเทอร์เน็ต', 'ต้องต่อเน็ตระหว่างไลฟ์เพื่อดึงแชทจากแพลตฟอร์ม', 'Online while you are live'],
            ['ระบบอื่น', 'ยังไม่มีโปรแกรมหลักสำหรับ macOS หรือ Linux', 'Windows only'],
        ]"
        specs-note="สเปกเหล่านี้เป็นเป้าหมายในเอกสารของโปรเจกต์ อาจปรับก่อนเปิดขาย"
        steps-title="เมื่อเปิดขาย: 3 ขั้น"
        :steps="[
            ['ซื้อ License ในหน้านี้', 'ได้ License key ทางอีเมลและในหน้า บัญชีของฉัน', 'Buy a licence here'],
            ['ดาวน์โหลดและติดตั้งบน Windows', 'ไฟล์อยู่ที่ บัญชีของฉัน › ดาวน์โหลด แล้วใส่ License key ในโปรแกรม', 'Download from your account and activate'],
            ['เริ่มดึงแชท', 'เลือกแพลตฟอร์ม ตั้งชื่อรอบไลฟ์ วาง URL ของไลฟ์ แล้วกด เริ่มดึงแชท', 'Pick the platform, paste the live URL, start'],
        ]" />

    <x-xp.faq :items="[
        ['ตอนนี้ซื้อหรือดาวน์โหลดได้ไหม?', $csLive ? 'ยังไม่ได้ Live x Shop Pro อยู่ในสถานะ Coming Soon ยังไม่เปิดขายและยังไม่มีไฟล์ให้ดาวน์โหลด กด แจ้งเตือนเมื่อเปิดขาย แล้วเราจะติดต่อกลับเมื่อพร้อม' : 'ได้ ซื้อ License ได้ในหน้านี้ คนที่ซื้อแล้วดาวน์โหลดได้ที่หน้า บัญชีของฉัน › ดาวน์โหลด'],
        ['ราคาเท่าไร?', $product->is_custom ? 'ติดต่อสอบถามราคาได้ที่หน้าขอใบเสนอราคา' : ($price > 0 ? 'ราคาในร้านตอนนี้ ฿' . number_format($price) . ' ตัวเลขในหน้านี้อ่านจากร้านโดยตรง ส่วนเงื่อนไข License (อายุการใช้งาน จำนวนเครื่อง การทดลองใช้) ยังไม่ประกาศ' : 'ยังไม่ประกาศราคา')],
        ['เป็นโปรแกรมถ่ายทอดสดไหม?', 'ไม่ใช่ Live x Shop Pro ไม่ได้สตรีมไลฟ์แทนคุณ คุณยังไลฟ์ด้วยแอปของแพลตฟอร์มหรือ OBS ตามเดิม ส่วนโปรแกรมทำงานหลังบ้าน: ดึงแชท จับ CF ทำออเดอร์ เก็บเงิน และส่งของ'],
        ['ดึงแชทจากแพลตฟอร์มไหนได้บ้าง?', 'แผนแรกคือ Facebook Live และ TikTok Live (ผ่านบอตเบราว์เซอร์) และ LINE OA (ผ่าน LINE Messaging API) ทั้งหมดยังอยู่ระหว่างพัฒนา'],
        ['ตรวจสลิปได้จริงไหม?', 'ยังไม่ได้ การอ่านสลิปด้วย OCR การจับสลิปปลอมหรือสลิปซ้ำ และการจับคู่กับ SMS ธนาคาร อยู่ในขั้นที่ 3 ของแผนพัฒนา ยังไม่มีในโปรแกรม'],
        ['ข้อมูลร้านเก็บที่ไหน?', 'ฐานข้อมูลร้าน (สินค้า ลูกค้า ออเดอร์) เป็น SQLite เก็บในเครื่อง Windows ของคุณ ไม่ได้ฝากไว้บนเซิร์ฟเวอร์ของ XMAN'],
        ['ใช้บน Mac ได้ไหม?', 'ไม่ได้ โปรแกรมหลักสร้างสำหรับ Windows 10 (1903 ขึ้นไป) และ Windows 11 เท่านั้น ส่วนแอปมือถือ (Android / iOS) เป็นตัวช่วยดึงแชทที่ต้องใช้คู่กับโปรแกรมบนคอม'],
        ['จะเปิดขายเมื่อไร?', $product->coming_soon_until ? 'กำหนดเปิด ' . $product->coming_soon_until->format('d/m/Y') . ' หน้านี้จะเปิดปุ่มซื้อและดาวน์โหลดเองเมื่อถึงวัน' : 'ยังไม่ประกาศวัน ระหว่างนี้ฝากข้อมูลไว้ที่ แจ้งเตือนเมื่อเปิดขาย ได้'],
    ]" />

    {{-- ====================== RELATED (จาก products/show) ====================== --}}
    @if($relatedProducts && $relatedProducts->count() > 0)
        <section class="relative py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <x-xp.head class="mb-10" size="md" eyebrow="More from XMAN Studio" title="ผลิตภัณฑ์ที่เกี่ยวข้อง" />
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach($relatedProducts as $related)
                        <a href="{{ route('products.show', $related->slug) }}" class="xp-card overflow-hidden block transition group">
                            <div class="aspect-video bg-white/[.03] overflow-hidden">
                                @if($related->artwork_url)
                                    <img src="{{ $related->artwork_url }}" alt="{{ $related->name }}" loading="lazy" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-white mb-1">{{ $related->name }}</h3>
                                @if($related->short_description)
                                    <p class="text-gray-400 text-sm mb-2 line-clamp-2">{{ $related->short_description }}</p>
                                @endif
                                @if(! $related->is_custom)
                                    <p class="font-bold text-lg" style="color: var(--xp-a)">฿{{ number_format($related->price, 0) }}</p>
                                    <x-tpix-price :thb="$related->price" :tpix="$related->price_tpix" />
                                @else
                                    <p class="text-gray-400">สอบถามราคา</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="ไลฟ์ต่อไป" glow="หลังบ้านไม่ต้องวุ่น"
              :sub="$csLive ? 'Live x Shop Pro กำลังพัฒนา ฝากชื่อไว้แล้วเราจะแจ้งเมื่อเปิดขาย' : 'ดึงแชท เปลี่ยน CF เป็นออเดอร์ และส่งของจากโปรแกรมเดียว'"
              en="A calmer back office for your next live.">
        @if($csLive)
            <x-xp.download :href="$downloadHref" :coming-soon="true" th="ดาวน์โหลด" en="Download" class="!px-8" />
            <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">แจ้งเตือนเมื่อเปิดขาย</a>
        @elseif($product->is_custom)
            <a href="{{ route('quote.index') }}" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ติดต่อสอบถาม</a>
        @else
            @if($ownsLive)
                <x-xp.download :href="$downloadHref" th="ดาวน์โหลด" en="Download" class="!px-8" />
            @endif
            <a href="#pricing" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">{{ $ownsLive ? 'ซื้อ License เพิ่ม' : 'ซื้อ License' }}</a>
            <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ติดต่อสอบถาม</a>
        @endif
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows 10 (1903+) / 11 · Facebook · TikTok · LINE · ข้อมูลร้านอยู่ในเครื่องคุณ</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
