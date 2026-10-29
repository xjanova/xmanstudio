@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ไม่มีปุ่มซื้อและไม่มีลิงก์ไฟล์ (กฎเจ้าของ 2026-10-12) --}}
@php $csSkidrow = $product->isComingSoon(); @endphp

@section('title', 'Skidrow Killer — สแกนและกำจัดมัลแวร์ แครก โทรจัน บน Windows | XMAN Studio')
@section('meta_description', 'Skidrow Killer สแกนถึงเนื้อไฟล์ ไม่ใช่แค่ชื่อ: hash, ลายเซ็น, heuristics และ entropy ป้องกันแบบเรียลไทม์ บล็อกเว็บอันตราย เฝ้า USB และ ransomware ลบมัลแวร์พร้อมจุดที่ทำให้มันกลับมา สำรองก่อนลบ กักกันแล้วคืนได้ ไม่ปิด Windows Defender · Malware scanner and remover for Windows 10/11.')
@section('og_image', asset('images/products/skidrow-killer/v2/og.jpg'))

@section('content')
@php
    // ราคาอ่านจากแถวสินค้าในร้าน (products.price) ที่เดียว ตะกร้าคิดราคานี้ — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $price = (float) $product->price;
    $hasDiscount = $product->original_price && $product->original_price > $product->price;
    $ownsSkidrow = auth()->check() && $hasPurchased;

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว) — หน้าดาวน์โหลดตรวจว่าซื้อแล้วก่อนให้ไฟล์
    $latest = $product->latestVersion();
    $downloadHref = $latest ? route('download.page', ['slug' => $product->slug, 'version' => $latest->version]) : null;

    // ภาพหน้าจอ: ยังไม่มีภาพจริงจากโปรแกรม ทุกภาพเป็น mockup วาดตามธีมของแอป (scripts/product-mockups/skidrow-killer)
    $shot = fn (string $file) => asset('images/products/skidrow-killer/v2/' . $file);

    // ข้อเท็จจริงจากโค้ดแอป D:\Code\skidrowkill (SafeScanner, ThreatAnalyzer.AnalyzeFileAsync, ThreatEradicator)
    $coreRows = [
        [
            'img' => $shot('scan.webp'), 'mock' => true, 'name' => 'Smart Scan ดูถึงเนื้อไฟล์', 'eyebrow' => 'Quick · Deep · Custom',
            'th' => 'Quick Scan ตรวจจุดเริ่มอัตโนมัติ โปรเซสที่รันอยู่ และที่ที่มัลแวร์ชอบซ่อน · Deep Scan ตรวจทุกไดรฟ์ที่เลือก รวม registry และโปรเซส · Custom เลือกเองว่าจะตรวจไฟล์ registry หรือหน่วยความจำ และเฉพาะโฟลเดอร์ไหน',
            'en' => 'Every file is judged by its content, not just its name: hashes, content signatures and fuzzy hashes, with deeper analysis for programs and scripts.',
            'points' => [
                'ไฟล์ที่ตั้งชื่อหลอกก็เจอ: เทียบ hash กับฐานข้อมูลมัลแวร์ ลายเซ็นในเนื้อไฟล์ และ fuzzy hash (ssdeep) จับไฟล์ที่ดัดแปลงจากตัวเดิม',
                'ไฟล์โปรแกรมและสคริปต์วิเคราะห์ละเอียด: โครงสร้าง PE, imphash, heuristics, API ที่น่าสงสัย และ entropy (ถูกแพ็กหรือเข้ารหัส)',
                'ทุกไฟล์ได้คะแนน 0–100 พร้อมเหตุผล ไฟล์ในโฟลเดอร์ที่ปลอดภัยถูกลดคะแนน เพื่อลดการเตือนผิด',
                'บันทึกรายงานผลสแกนเป็นไฟล์ HTML ได้',
            ],
            'alt' => 'ภาพตัวอย่างหน้า Smart Scan ของ Skidrow Killer ระหว่าง Deep Scan: แถบความคืบหน้า จำนวนไฟล์ที่ตรวจ และ Scan Log',
        ],
        [
            'img' => $shot('threats.webp'), 'mock' => true, 'name' => 'ลบให้หมด ไม่ใช่แค่ไฟล์เดียว', 'eyebrow' => 'Remove the whole threat',
            'th' => 'หน้า Threats บอกคะแนน ระดับความรุนแรง ประเภท (แครกเกม keygen activator โทรจัน ransomware ตัวขุดเหรียญ ตัวขโมยรหัส ฯลฯ) เหตุผลที่ตรวจเจอ และคำแนะนำ แล้วให้คุณเลือก กักกัน ลบ หรือข้าม',
            'en' => 'Removal ends every process running the file, strips the Run keys, scheduled tasks and services that would bring it back, then deletes or quarantines it.',
            'points' => [
                'ปิดทุกโปรเซสที่รันไฟล์นั้นพร้อมโปรเซสลูก',
                'ลบค่า Run / RunOnce งานตั้งเวลา และหยุดบริการที่ชี้มาที่ไฟล์ ไม่ให้กลับมาตอนเปิดเครื่องใหม่',
                'ไฟล์ยังถูกล็อกอยู่? ตั้งให้ลบตอนรีสตาร์ตและบอกตรง ๆ ไม่รายงานว่าลบแล้วทั้งที่ยังอยู่',
            ],
            'alt' => 'ภาพตัวอย่างหน้า Threats Found ของ Skidrow Killer รายการไฟล์อันตรายพร้อมคะแนน ประเภท คำแนะนำ และปุ่มกักกัน ลบ ข้าม',
        ],
    ];

    // ชั้นป้องกันที่ทำงานตลอดเวลา — ทุกข้อเช็กกับ Services/ ของแอป
    $guards = [
        [
            'key' => 'realtime', 'name' => 'ป้องกันแบบเรียลไทม์', 'short' => 'เห็นทุกโปรเซสตั้งแต่เริ่มรัน',
            'th' => 'Skidrow Killer เห็นทุกโปรเซสทันทีที่เริ่มรันผ่านเหตุการณ์ของ Windows จึงจับตัวปล่อยมัลแวร์ที่รันแล้วปิดตัวในเสี้ยววินาทีได้ ตรวจคำสั่งที่ใช้เรียก และตรวจไฟล์ใหม่ด้วยเนื้อไฟล์เหมือนตอนสแกน',
            'en' => 'Sees every process the moment it starts, checks its command line, and content-checks new files.',
            'points' => ['ตั้งได้ว่าเจอแล้วให้ทำอะไร: ถามทุกครั้ง · กักกันอัตโนมัติ · ลบอัตโนมัติ · บันทึกอย่างเดียว', 'โหมด "ถาม" จัดการเองเฉพาะระดับ Critical และใช้วิธีกักกันซึ่งคืนได้เสมอ'],
        ],
        [
            'key' => 'web', 'name' => 'Web Protection', 'short' => 'บล็อกเว็บแครก มัลแวร์ และ C2',
            'th' => 'บล็อกโดเมนเว็บแจกของเถื่อน เว็บปล่อยมัลแวร์ และเซิร์ฟเวอร์สั่งการ (C2) ทั้งระบบผ่านไฟล์ hosts และตัดการเชื่อมต่อ TCP ที่ต่ออยู่แล้วกับปลายทางนั้นทันที ไม่ใช่แค่บันทึกไว้',
            'en' => 'Blocks warez, malware and C2 domains system-wide and tears down live connections to them.',
            'points' => ['ปิด Web Protection เมื่อไร รายการที่เพิ่มใน hosts ถูกลบออกให้ด้วย'],
        ],
        [
            'key' => 'ransomware', 'name' => 'กัน Ransomware', 'short' => 'เฝ้าโฟลเดอร์สำคัญ + ไฟล์ล่อ',
            'img' => $shot('ransomware.webp'), 'mock' => true,
            'th' => 'เฝ้าโฟลเดอร์ Documents, Pictures, Desktop (เพิ่มเองได้) และวางไฟล์ล่อที่ซ่อนไว้ เตือนทันทีเมื่อไฟล์ถูกเปลี่ยนชื่อเป็นนามสกุลของ ransomware มีไฟล์ชื่อแบบจดหมายเรียกค่าไถ่โผล่มา เนื้อไฟล์กลายเป็นข้อมูลเข้ารหัส หรือโปรเซสเดียวแก้ไฟล์จำนวนมากในเวลาสั้น ๆ',
            'en' => 'Watches your folders with hidden decoys and alerts on ransomware extensions, ransom notes, files turning encrypted and mass edits.',
            'points' => ['ปิดไฟล์ล่อได้ในหน้าตั้งค่า ระบบลบเฉพาะไฟล์ล่อที่ตัวเองวางไว้'],
            'alt' => 'ภาพตัวอย่างหน้า Ransomware Protection ของ Skidrow Killer: โฟลเดอร์ที่เฝ้า ไฟล์ล่อ และ Activity Log',
        ],
        [
            'key' => 'usb', 'name' => 'USB Protection', 'short' => 'สแกนแฟลชไดรฟ์ทันทีที่เสียบ',
            'th' => 'ตรวจ USB และไดรฟ์ภายนอกทันทีที่เสียบ ด้วยการตรวจเนื้อไฟล์แบบเดียวกับ Smart Scan และกันไฟล์ autorun.inf ที่มัลแวร์ใช้ติดต่อข้ามเครื่อง',
            'en' => 'Scans removable drives the moment they connect and blocks autorun.inf.',
            'points' => [],
        ],
        [
            'key' => 'startup', 'name' => 'จุดที่มัลแวร์ฝังตัว', 'short' => 'ตัวที่กลับมาหลังรีสตาร์ต',
            'th' => 'ตรวจจุดที่ทำให้โปรแกรมรันเองตอนเปิดเครื่อง เกินกว่าแค่ Run key: โฟลเดอร์ Startup, Winlogon Shell/Userinit, AppInit_DLLs และการแฮ็ก Image File Execution Options',
            'en' => 'Checks the auto-start points malware abuses to survive a reboot, well beyond the Run keys.',
            'points' => ['Run / RunOnce ทั้ง HKCU, HKLM และฝั่ง 32-bit', 'การตั้งค่าที่อันตรายในตัว (IFEO debugger, AppInit DLL) ถูกเตือนแม้ไฟล์ปลายทางจะดูปกติ'],
        ],
        [
            'key' => 'browser', 'name' => 'Browser Protection', 'short' => 'ส่วนขยายเบราว์เซอร์อันตราย',
            'th' => 'ตรวจส่วนขยาย (extension) ของ Chrome, Edge, Firefox, Brave และ Opera หาตัวที่น่าสงสัย แล้วลบออกได้จากในโปรแกรม',
            'en' => 'Checks Chrome, Edge, Firefox, Brave and Opera extensions and removes the bad ones.',
            'points' => [],
        ],
        [
            'key' => 'more', 'name' => 'สแกนตามเวลา · Gaming · Cleanup', 'short' => 'ตั้งแล้วลืมได้',
            'th' => 'ตั้งสแกนอัตโนมัติรายวัน รายสัปดาห์ หรือรายเดือน · Gaming Mode ตรวจจับเกมหรือแอปเต็มจอแล้วพักการแจ้งเตือนให้ · System Cleanup ล้างไฟล์ชั่วคราว แคชเบราว์เซอร์ ถังขยะ แคชรูปย่อ และ log ของ Windows',
            'en' => 'Scheduled scans, a Gaming Mode that holds pop-ups while you play, and a junk-file cleanup.',
            'points' => [],
        ],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Skidrow Killer',
        'operatingSystem' => 'Windows 10, Windows 11',
        'applicationCategory' => 'SecurityApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'โปรแกรมสแกนและกำจัดมัลแวร์บน Windows ตรวจถึงเนื้อไฟล์ ป้องกันแบบเรียลไทม์ บล็อกเว็บอันตราย เฝ้า USB และ ransomware สำรองก่อนลบ กักกันแล้วคืนได้',
        'image' => asset('images/products/skidrow-killer/v2/og.jpg'),
        'url' => route('products.show', $product->slug),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
    ];
    // ระหว่าง Coming Soon ยังขายไม่ได้ — ไม่ประกาศ Offer ให้เครื่องมือค้นหาเอาไปแสดงเป็นของที่ซื้อได้
    if (! $csSkidrow && ! $product->is_custom && $price > 0) {
        $ld['offers'] = ['@type' => 'Offer', 'price' => (string) $price, 'priceCurrency' => 'THB'];
    }
@endphp
<x-xp.page a="#4dd0e1" b="#66bb6a" c="#81d4fa" glow="41 182 246" g1="#f43f5e" g2="#c084fc" g3="#4dd0e1">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="สแกนและกำจัดมัลแวร์บน Windows"
               tagline-en="Finds and removes malware, cracks and trojans on Windows."
               lead="ตรวจถึงเนื้อไฟล์ ไม่ใช่แค่ชื่อ ป้องกันแบบเรียลไทม์ บล็อกเว็บอันตราย เฝ้า USB และ ransomware — เจอแล้วลบให้หมดทั้งโปรเซสและจุดที่ทำให้มันกลับมา โดยสำรองไฟล์ไว้ก่อนเสมอ"
               lead-en="Content-based scanning, real-time protection, web, USB and ransomware guards, and removal that takes the whole threat out, with a backup first.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'Skidrow Killer ' . $latest->version : 'Skidrow Killer' }}</span>
            <span class="xp-chip">Windows 10 / 11 (64-bit)</span>
            @if($csSkidrow)<x-xp.badge tier="soon" label="Coming Soon" />@endif
        </x-slot:chips>
        <x-slot:title>Skidrow <span class="xp-text-glow">Killer</span></x-slot:title>
        <x-slot:actions>
            @if($csSkidrow || $downloadHref)
                <x-xp.download :href="$downloadHref" :coming-soon="$csSkidrow" th="ดาวน์โหลด" en="Download" />
            @endif
            @if(! $csSkidrow)
                @if($product->is_custom)
                    <a href="{{ route('quote.index') }}" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">สอบถามราคา</a>
                @else
                    <a href="#pricing" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">
                        {{ $ownsSkidrow ? 'ซื้อ License เพิ่ม' : 'ซื้อ License' }} — ฿{{ number_format($price) }}
                    </a>
                @endif
            @else
                <a href="#pricing" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition"><x-bi th="ราคา" en="Pricing" /></a>
            @endif
        </x-slot:actions>
        <x-slot:notes>
            @if(session('success'))
                <p class="mt-4 text-sm text-emerald-200/90">{{ session('success') }}</p>
            @endif
            @if($csSkidrow)
                <p class="mt-4 text-sm text-gray-400">
                    ยังไม่เปิดขายและยังไม่มีไฟล์ให้ดาวน์โหลด@if($product->coming_soon_until) · กำหนดเปิด {{ $product->coming_soon_until->format('d/m/Y H:i') }}@endif
                </p>
            @elseif($latest)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · ดาวน์โหลดได้หลังซื้อ License</p>
            @endif
            {{-- สถานะ License ของคนที่ล็อกอิน: ใช้ได้ / ใกล้หมด / หมดแล้ว --}}
            @if(isset($userLicense) && $userLicense)
                @php($daysLeft = $userLicense->daysRemaining())
                @if($userLicense->isValid() && ($daysLeft > 7 || ! $userLicense->expires_at))
                    <p class="mt-3 text-sm text-emerald-200/90" data-license-state="active">
                        คุณมี License แล้ว{{ $userLicense->expires_at ? ' · ใช้ได้ถึง ' . $userLicense->expires_at->format('d/m/Y') : ' · ไม่มีวันหมดอายุ' }} ·
                        <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline hover:text-white">ดู License ของฉัน</a>
                    </p>
                @elseif($userLicense->isValid())
                    <p class="mt-3 text-sm text-amber-200" data-license-state="expiring">
                        License ใกล้หมดอายุ · เหลืออีก {{ $daysLeft }} วัน@if($userLicense->expires_at) ({{ $userLicense->expires_at->format('d/m/Y') }})@endif ·
                        <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline hover:text-white">ต่ออายุตอนนี้</a>
                    </p>
                @else
                    <p class="mt-3 text-sm text-red-300" data-license-state="expired">
                        License ของคุณหมดอายุแล้ว@if($userLicense->expires_at) เมื่อ {{ $userLicense->expires_at->format('d/m/Y') }}@endif ·
                        <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline hover:text-white">ต่ออายุตอนนี้</a>
                    </p>
                @endif
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ดูเนื้อไฟล์ ไม่ใช่แค่ชื่อ</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> สำรองก่อนลบ คืนได้</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ไม่ปิด Windows Defender</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('home.webp')" :eager="true" :mock="true"
                             alt="ภาพตัวอย่างหน้า Home ของ Skidrow Killer: สถานะการป้องกัน จำนวนภัยที่พบ ไฟล์ที่กักกัน และผล Engine Self-Test" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">ลบให้หมด · Remove it all</p>
                <p class="text-white font-extrabold text-lg leading-tight">โปรเซส · Run key · <span class="text-cyan-300">ไฟล์</span></p>
            </div>
            <div class="xp-float-2 absolute -right-2 sm:-right-6 -top-5 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">ชื่อหลอกก็เจอ</p>
                <p class="text-white font-bold leading-tight">เทียบ hash + ลายเซ็น<br><span class="text-gray-400 text-sm font-normal">ในเนื้อไฟล์ทุกไฟล์</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ============================ SCAN + REMOVE ============================ --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Scan · Remove" title="หาให้เจอ" title2="แล้วเอาออกให้หมด"
                       sub="สแกนที่ตัดสินจากเนื้อไฟล์ แล้วลบทั้งตัวไฟล์ โปรเซส และจุดที่ทำให้มันกลับมา"
                       en="A scan that reads the file, then a removal that takes out the file, its processes and its way back in." />
            <x-xp.rows :items="$coreRows" />
        </div>
    </section>

    {{-- =========================== ALWAYS ON =========================== --}}
    <section class="relative py-20 isolate">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="Always on" title="เฝ้าเครื่อง" title2="ตลอดเวลา"
                       sub="นอกจากสแกน Skidrow Killer มีชั้นป้องกันที่ทำงานอยู่เบื้องหลัง เปิดปิดแต่ละชั้นได้ในหน้าตั้งค่า"
                       en="Background guards beside the scanner, each one switched on or off in Settings." />
            <x-xp.explorer id="guards" label="ชั้นป้องกันของ Skidrow Killer" :tools="$guards" />
        </div>
    </section>

    {{-- ========================= THREAT INTEL ========================= --}}
    <section class="relative py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.rows :items="[[
                'img' => $shot('intel.webp'), 'mock' => true, 'name' => 'ฐานข้อมูลภัยคุกคามที่อัปเดตเอง', 'eyebrow' => 'Threat intelligence',
                'th' => 'ดึง hash มัลแวร์ URL อันตราย และ IP ของเซิร์ฟเวอร์สั่งการ จากแหล่งเปิดอย่าง abuse.ch (MalwareBazaar, ThreatFox, URLhaus, Feodo Tracker), DigitalSide OSINT และรายชื่อ IP อันตรายอื่น ๆ ผ่าน HTTPS อัปเดตตอนเปิดโปรแกรมและตามรอบที่ตั้งไว้ แล้วป้อนเข้าเครื่องสแกนทันที',
                'en' => 'Malware hashes, bad URLs and C2 addresses from public feeds, refreshed on start-up and on a schedule, fed straight into the scanner.',
                'points' => ['หน้า Detection Engine บอกตรง ๆ ว่าชั้นตรวจไหนทำงานอยู่ ชั้นที่ปิดหรือยังขาดข้อมูลแสดงเป็นสีเทา', 'ใส่ VirusTotal API key ของคุณเองได้ (ไม่บังคับ) เพื่อเปิดการเช็กบนคลาวด์ ซึ่งส่งไปแค่ค่า hash ไม่ส่งตัวไฟล์'],
                'alt' => 'ภาพตัวอย่างหน้า Threat Intelligence ของ Skidrow Killer: จำนวน hash URL IP ชั้นตรวจที่ทำงาน และรายการแหล่งข้อมูล',
            ]]" />
        </div>
    </section>

    {{-- ============================ SAFETY ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Careful by design" title="กำจัดมัลแวร์" title2="โดยไม่ทำเครื่องพัง"
                       sub="โปรแกรมลบไฟล์ได้ต้องรู้ด้วยว่าอะไรห้ามแตะ และต้องย้อนกลับได้"
                       en="A tool that deletes files must know what never to touch, and must be able to undo." />
            <x-xp.cards :items="[
                ['icon' => 'shield', 'title' => 'อยู่ร่วมกับ Windows Defender', 'body' => 'ไม่ปิดการป้องกันของ Defender เด็ดขาด เพิ่มแค่โฟลเดอร์ของ Skidrow Killer เองเป็นข้อยกเว้น เพื่อไม่ให้ Defender กักตัวโปรแกรมเราผิด', 'en' => 'Never switches Defender off'],
                ['icon' => 'undo', 'title' => 'สำรองก่อนลบ · กักกันแล้วคืนได้', 'body' => 'สำรองไฟล์ก่อนลบเป็นค่าเริ่มต้น (เก็บ 7 วัน ปรับได้) ไฟล์ที่กักกันถูกเข้ารหัสเก็บไว้และคืนกลับที่เดิมได้ครบทุกไบต์', 'en' => 'Backup first, restorable quarantine'],
                ['icon' => 'lock', 'title' => 'ไม่แตะไฟล์ของ Windows', 'body' => 'ปฏิเสธการลบไฟล์ใน System32, SysWOW64, WinSxS, Boot และโฟลเดอร์หลักของ Windows รวมถึงไฟล์ที่คุณใส่ไวท์ลิสต์ไว้', 'en' => 'Windows’ own files are off limits'],
                ['icon' => 'sparkles', 'title' => 'เรียนรู้จากการตัดสินใจของคุณ', 'body' => 'จำว่าไฟล์ไหนคุณยืนยันว่าอันตรายหรือปลอดภัย ผูกกับ hash ของไฟล์ ไม่ใช่ที่อยู่ไฟล์ และต้องได้หลายเสียงก่อนจะเชื่อ ทุกการปรับคะแนนตรวจย้อนได้', 'en' => 'Local learning you can audit'],
                ['icon' => 'bolt', 'title' => 'ทดสอบตัวเองได้', 'body' => 'ปุ่ม Run Self-Test ใช้ไฟล์ตัวอย่างที่ไม่อันตราย พิสูจน์ว่าการตรวจ การลบ การสำรอง และการกักกันทำงานจริงบนเครื่องคุณ', 'en' => 'A self-test on harmless samples'],
                ['icon' => 'eye', 'title' => 'บอกผลตามจริง', 'body' => 'ลบไม่สำเร็จก็บอกว่าไม่สำเร็จ ไฟล์ที่ต้องรอรีสตาร์ตก็บอกว่ารอรีสตาร์ต ไม่นับเป็นภัยที่บล็อกแล้วถ้ายังไม่ได้บล็อกจริง', 'en' => 'Results that say what really happened'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="ราคา" :title2="$csSkidrow ? 'เมื่อเปิดขาย' : '1 คีย์ ใช้ได้ 1 ปี'"
                       :sub="$csSkidrow ? 'Skidrow Killer ยังไม่เปิดขาย ราคาด้านล่างคือราคาที่ตั้งไว้ในร้านตอนนี้' : 'ราคาจากร้านโดยตรง · 1 คีย์ใช้ได้ 1 เครื่อง'"
                       :en="$csSkidrow ? 'Not on sale yet: this is the price currently set in the shop.' : 'The price straight from the shop. One key per machine.'" />

            <div class="max-w-md mx-auto">
                <div class="xp-featured">
                    <div class="p-7 flex flex-col h-full">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-2xl font-black text-white">Skidrow Killer</h3>
                            @if($csSkidrow)<x-xp.badge tier="soon" label="Coming Soon" />@endif
                        </div>
                        <p class="text-gray-400 text-sm mb-6">License · Windows 10 / 11</p>
                        @if($product->is_custom)
                            <p class="mb-6 text-3xl font-black text-white">สอบถามราคา</p>
                        @elseif($price > 0)
                            <div class="mb-6 flex flex-wrap items-baseline gap-3">
                                <span class="text-5xl font-black text-white">฿{{ number_format($price) }}</span>
                                @if($hasDiscount)
                                    <span class="text-xl text-gray-500 line-through">฿{{ number_format($product->original_price) }}</span>
                                    <x-xp.badge tier="pro" :label="'ลด ' . round((1 - $product->price / $product->original_price) * 100) . '%'" />
                                @endif
                            </div>
                            <x-tpix-price variant="detail" :thb="$product->price" :tpix="$product->price_tpix" class="-mt-4 mb-6" />
                        @else
                            <p class="mb-6 text-lg font-bold text-white">ยังไม่ประกาศราคา</p>
                        @endif
                        <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ทุกฟีเจอร์ในหน้านี้', '1 คีย์ ใช้ได้ 1 เครื่อง', 'อายุ License 1 ปีนับจากวันที่ชำระเงิน', 'ดาวน์โหลดไฟล์ได้ในหน้า ดาวน์โหลด หลังซื้อ']" />
                        @if($csSkidrow)
                            {{-- ComingSoonPurchase ปฏิเสธทุกทางซื้ออยู่แล้ว — หน้านี้ก็ไม่มีฟอร์มตะกร้าหรือลิงก์ไฟล์ --}}
                            <span class="xp-btn-ghost xp-btn-off block w-full py-3.5 text-center rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
                        @elseif($product->is_custom)
                            <a href="{{ route('quote.index') }}" class="xp-btn-pro block w-full py-3.5 text-center rounded-xl font-extrabold transition">ติดต่อสอบถามราคา</a>
                        @else
                            {{-- ตะกร้าคิดราคาจากแถวสินค้า (products.price) ไม่ใช่จากฟอร์ม · หนึ่งคีย์ต่อหนึ่งเครื่อง ซื้อหลายเครื่องได้ --}}
                            <form action="{{ route('cart.add', $product) }}" method="POST" class="flex items-stretch gap-3">
                                @csrf
                                <label class="sr-only" for="quantity">จำนวนคีย์ (เครื่อง)</label>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="99"
                                       class="xp-btn-ghost w-20 rounded-xl text-center font-bold" title="จำนวนคีย์ · 1 คีย์ต่อ 1 เครื่อง">
                                <button type="submit" class="xp-btn-pro flex-1 py-3.5 text-center rounded-xl font-extrabold transition cursor-pointer">
                                    {{ $ownsSkidrow ? 'ซื้อ License เพิ่ม' : 'เพิ่มลงตะกร้า' }} — ฿{{ number_format($price) }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            @if($ownsSkidrow)
                <p class="text-center mt-6">
                    <a href="{{ route('customer.licenses') }}" class="xp-link text-sm font-semibold">มี License แล้ว? ดูคีย์ของคุณ →</a>
                </p>
            @endif
            <p class="text-center text-xs text-gray-500 mt-6 max-w-2xl mx-auto">ซื้อแล้วได้ License key ในหน้า บัญชีของฉัน › License · ใส่คีย์ในหน้า License ของโปรแกรม · ใช้ได้ 1 เครื่องต่อ 1 คีย์ มีหลายเครื่องซื้อหลายคีย์ได้</p>
        </div>
    </section>

    {{-- =========================== DOWNLOAD =========================== --}}
    @if($latest)
        <section class="relative py-12 scroll-mt-20" id="download">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="xp-card p-6 sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                        <div>
                            <p class="xp-eyebrow mb-1">Download</p>
                            <h2 class="text-2xl md:text-3xl font-black text-white">Skidrow Killer {{ $latest->version }}</h2>
                            <p class="text-sm text-gray-400 mt-1">
                                @if($latest->file_size){{ $latest->file_size_formatted }} · @endif
                                อัปเดตเมื่อ {{ $latest->synced_at?->format('d/m/Y') ?? $latest->created_at?->format('d/m/Y') ?? '—' }} · Windows 10 / 11 (64-bit)
                            </p>
                        </div>
                        <x-xp.download :href="$downloadHref" :coming-soon="$csSkidrow" th="ดาวน์โหลด" en="Download" :big="false" />
                    </div>
                    @if($latest->changelog)
                        <div class="rounded-xl bg-white/[.03] border border-white/5 p-4">
                            <p class="text-xs text-gray-400 mb-2 font-semibold">มีอะไรใหม่ · Changelog</p>
                            <p class="text-sm text-gray-300 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($latest->changelog, 300) }}</p>
                        </div>
                    @endif
                    <p class="text-xs text-gray-500 mt-4">{{ $csSkidrow ? 'เปิดให้ดาวน์โหลดเมื่อ Skidrow Killer เปิดตัว' : 'หน้าดาวน์โหลดให้ไฟล์เฉพาะบัญชีที่ซื้อ License แล้ว (ต้องเข้าสู่ระบบ)' }}</p>
                </div>
            </div>
        </section>
    @endif

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Windows 10 หรือ Windows 11 แบบ 64-bit', 'Windows 10 / 11, 64-bit'],
            ['สิทธิ์ Administrator', 'โปรแกรมขอสิทธิ์ผู้ดูแลทุกครั้งที่เปิด เพราะต้องตรวจโปรเซส registry และลบไฟล์ที่ถูกล็อก', 'Runs as administrator'],
            ['อินเทอร์เน็ต', 'ใช้ดาวน์โหลดฐานข้อมูลภัยคุกคามและเปิดใช้ License · สแกนได้แม้ออฟไลน์ด้วยฐานข้อมูลที่โหลดไว้แล้ว', 'For threat feeds and activation'],
            ['1 คีย์ = 1 เครื่อง', 'คีย์ผูกกับเครื่องที่เปิดใช้ มีหลายเครื่องซื้อคีย์เพิ่มได้', 'One key per machine'],
        ]"
        :steps-title="$csSkidrow ? 'เมื่อเปิดขาย: 3 ขั้น' : null"
        :steps="[
            ['ซื้อ License', 'เข้าสู่ระบบแล้วซื้อในหน้านี้ คีย์จะอยู่ในหน้า บัญชีของฉัน › License', 'Buy a license'],
            ['ดาวน์โหลดแล้วเปิดโปรแกรม', 'ดาวน์โหลดไฟล์ .zip จากหน้า ดาวน์โหลด แตกไฟล์ แล้วเปิด SkidrowKiller.exe (ยืนยันสิทธิ์ผู้ดูแล)', 'Download, unzip and run as administrator'],
            ['ใส่คีย์ แล้วกด Quick Scan', 'ใส่ License key ในหน้า License ของโปรแกรม จากนั้นสแกนครั้งแรกจากหน้า Home', 'Enter your key and run a Quick Scan'],
        ]" />

    <x-xp.faq :items="[
        ['ตอนนี้ซื้อหรือดาวน์โหลดได้ไหม?', $csSkidrow ? 'ยังไม่ได้ Skidrow Killer อยู่ในสถานะ Coming Soon ยังไม่เปิดขายและยังไม่มีไฟล์ให้ดาวน์โหลด เมื่อเปิดแล้วการซื้อและปุ่มดาวน์โหลดจะขึ้นในหน้านี้' : 'ได้ ซื้อ License ในหน้านี้ แล้วดาวน์โหลดได้ที่หน้า ดาวน์โหลด ด้วยบัญชีที่ซื้อ'],
        ['มีรุ่นฟรีไหม?', 'ไม่มี ไฟล์โปรแกรมดาวน์โหลดได้เฉพาะบัญชีที่ซื้อ License แล้ว'],
        ['ราคาเท่าไร ใช้ได้กี่เครื่อง นานแค่ไหน?', ($product->is_custom ? 'สอบถามราคาได้ที่หน้าติดต่อ' : ($price > 0 ? 'ราคาในร้านตอนนี้ ฿' . number_format($price) . ' ต่อคีย์' : 'ยังไม่ประกาศราคา')) . ' · 1 คีย์ใช้ได้ 1 เครื่อง · อายุ License 1 ปีนับจากวันที่ชำระเงิน มีหลายเครื่องซื้อหลายคีย์ได้'],
        ['ใช้คู่กับ Windows Defender ได้ไหม?', 'ได้ Skidrow Killer ไม่ปิดการป้องกันของ Defender เพิ่มแค่โฟลเดอร์ของตัวเองเป็นข้อยกเว้น เพื่อไม่ให้ Defender เข้าใจผิดว่าโปรแกรมเราเป็นมัลแวร์ (เพราะในตัวมีลายเซ็นมัลแวร์ไว้เทียบ)'],
        ['จะลบไฟล์ของ Windows ผิดไหม?', 'โปรแกรมปฏิเสธการลบไฟล์ใน System32, SysWOW64, WinSxS, Boot และโฟลเดอร์หลักของ Windows และไฟล์ในไวท์ลิสต์ ส่วนไฟล์อื่นสำรองไว้ก่อนลบเป็นค่าเริ่มต้น'],
        ['ลบผิดแล้วเอาคืนได้ไหม?', 'ได้ ไฟล์ที่ลบถูกสำรองไว้ในหน้า Backups (เก็บ 7 วันเป็นค่าเริ่มต้น ปรับได้) และไฟล์ที่กักกันคืนกลับที่เดิมได้จากหน้า Quarantine กดคืนหรือใส่ไวท์ลิสต์แล้วโปรแกรมจะจำไว้'],
        ['ไฟล์ของฉันถูกส่งขึ้นอินเทอร์เน็ตไหม?', 'ไม่ การสแกนทำในเครื่อง ประวัติ ไฟล์ที่กักกัน และการตั้งค่าเก็บในเครื่องของคุณ ถ้าคุณใส่ VirusTotal API key เอง โปรแกรมจะส่งไปเช็กแค่ค่า hash ของไฟล์ ไม่ส่งตัวไฟล์'],
        ['ทำไมชื่อ Skidrow Killer?', 'เริ่มจากการกำจัดไฟล์แครกเกมและตัวโหลดของกลุ่มแครก (เช่น Skidrow) ที่มักแอบพ่วงโทรจันมา ปัจจุบันตรวจมัลแวร์ทั่วไปด้วย: โทรจัน ransomware ตัวขุดเหรียญ ตัวขโมยรหัส RAT และ adware'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="เครื่องสะอาด" glow="ไม่มีของแถม"
              :sub="$csSkidrow ? 'Skidrow Killer กำลังจะเปิดตัว กลับมาที่หน้านี้เมื่อเปิดขาย' : 'สแกนถึงเนื้อไฟล์ ลบให้หมด และสำรองไว้ก่อนเสมอ'"
              en="A clean machine, with nothing riding along.">
        @if($csSkidrow || $downloadHref)
            <x-xp.download :href="$downloadHref" :coming-soon="$csSkidrow" th="ดาวน์โหลด" en="Download" class="!px-8" />
        @endif
        @unless($csSkidrow)
            <a href="#pricing" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">{{ $product->is_custom ? 'สอบถามราคา' : 'ซื้อ License' }}</a>
        @endunless
        <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ติดต่อสอบถาม</a>
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows 10 / 11 (64-bit) · สิทธิ์ Administrator · 1 คีย์ = 1 เครื่อง</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
