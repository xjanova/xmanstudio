@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย (กฎเจ้าของ 2026-10-12) --}}
@php $csDownload = \App\Models\Product::slugIsComingSoon('gpuxmine'); @endphp

@section('title', 'GPUxMINE — ให้การ์ดจอทำงาน AI ตอนคุณไม่ได้ใช้ รับค่าตอบแทนเข้ากระเป๋า XMAN · ใช้ฟรี | XMAN Studio')
@section('meta_description', 'GPUxMINE แชร์การ์ดจอที่บ้านให้รับงาน AI ผ่าน ComfyUI ในเครื่อง ตั้งตารางเวลาแชร์ เพดานอุณหภูมิ และหยุดรับงานเองเมื่อคุณใช้เครื่องหรือเปิดเกม ค่าตอบแทนพักไว้ตรวจก่อนแล้วเข้ากระเป๋า XMAN อัตโนมัติ โปรแกรมใช้ฟรี สำหรับ Windows · Share your idle GPU for AI jobs and get paid into your XMAN wallet.')
@section('og_image', asset('images/products/gpuxmine/v2/og.jpg'))

@section('content')
@php
    // โปรแกรมแจกฟรี (products.price = 0) และยังไม่มีแผนขาย — LicensePlans::for('gpuxmine') ว่าง (LicensePlansTest)
    // Pro Miner มีแค่จุดต่อในโค้ด (GpuxMineDispatchService::isProMiner คืน false เสมอ) จึงไม่มีตะกร้า ไม่มีราคาบนหน้านี้
    // อัตราค่าตอบแทนต่องานเป็นของ pool (AIXMAN) — หน้านี้ไม่บอกตัวเลขเงินใด ๆ ที่โค้ดไม่ได้กำหนด

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว — เปิดหน้านี้ต้องไม่ถามแหล่งไฟล์ภายนอก)
    $latest = $product->latestVersion();

    // ตัวเลขที่หน้านี้พูดถึง อ่านจากที่เดียวกับระบบจ่ายเงิน ไม่เขียนตายตัว
    $holdHours = app(\App\Services\GpuxMineEarningSettlementService::class)->holdHours();
    $nodeCap = (int) config('services.gpuxmine.max_nodes_per_user', 10);
    $pairTtl = \App\Models\GpuNode::PAIRING_TTL_MINUTES;

    // ภาพหน้าจอเป็น mockup ทั้งหมด (ยังไม่มีภาพจริงจากโปรแกรม) วาดตามธีมของแอป WPF (Theme.xaml) ข้อมูลในภาพเป็นตัวอย่าง
    // สร้างใหม่ได้ด้วย scripts/product-mockups/build.py gpuxmine
    $shot = fn (string $file) => asset('images/products/gpuxmine/v2/' . $file);

    // ข้อเท็จจริงทุกข้อเช็กกับโค้ดโปรแกรม (GpuXmine: src/GpuxMine.Node/NodeSettings.cs, Views/*.xaml, README) และฝั่งเว็บ (GpuNodeController, GpuxMine*Service)
    $controlRows = [
        [
            'img' => $shot('power.webp'), 'mock' => true, 'name' => 'คุณใช้เครื่องเมื่อไร มันถอยให้', 'eyebrow' => 'Power & Limits', 'tier' => 'free',
            'th' => 'GPUxMINE ตัดสินใหม่ก่อนรับทุกงาน: ถ้าคุณกำลังใช้เครื่อง เปิดเกมหรือโปรแกรมเต็มจอ อยู่นอกตารางเวลา หรือการ์ดร้อนถึงเพดาน มันหยุดรับงานใหม่ทันที งานที่รับไว้แล้วทำต่อจนเสร็จ',
            'en' => 'Before every job it checks again: you at the PC, a full-screen game, outside your schedule or at the temperature ceiling means no new work.',
            'points' => ['เพดานอุณหภูมิตั้งเองได้ (ค่าเริ่มต้น 78 °C) และมีผลจริง', 'แบ่งงานบางส่วนให้ pool ฟรีได้ 0–100% (ค่าเริ่มต้น 0%) — ส่วนนั้นไม่ได้ค่าตอบแทน', 'หน้า Power บอกค่าไฟต่อวันจากวัตต์ที่วัดจากการ์ดจริง ส่วนรายได้ขึ้นเมื่อ pool ตัดยอดแล้วเท่านั้น'],
            'alt' => 'ภาพตัวอย่างหน้า Power & Limits ของ GPUxMINE: โปรไฟล์กำลังไฟ สวิตช์หยุดเมื่อใช้เครื่อง ตารางเวลา แชร์ฟรี และเพดานอุณหภูมิ',
        ],
        [
            'img' => $shot('settings.webp'), 'mock' => true, 'name' => 'ตารางเวลาแชร์รายชั่วโมง', 'eyebrow' => 'Weekly schedule', 'tier' => 'free',
            'th' => 'ตาราง 7 วัน × 24 ชั่วโมง คลิกเลือกได้ว่าชั่วโมงไหนแชร์ ชั่วโมงไหนเป็นเวลาของคุณ ค่าเริ่มต้นเว้นช่วง 18–22 น. ทุกวัน และกลางวันเสาร์–อาทิตย์ไว้ให้คุณ ใส่ค่าไฟและช่วงไฟถูกได้ เพื่อให้คำนวณค่าไฟตรงกับบ้านคุณ',
            'en' => 'A 7 × 24 hour grid: pick the hours you share. Evenings and weekend daytime are yours by default.',
            'points' => ['เปิด "Run on schedule only" เพื่อรับงานเฉพาะชั่วโมงที่เลือก', 'จับคู่เครื่องกับบัญชีด้วยรหัส 8 ตัวจากหน้า เครื่องของฉัน — ใช้ได้ครั้งเดียว อายุ ' . $pairTtl . ' นาที', 'เปิดพร้อม Windows ได้ แล้วแชร์ต่อจากที่ค้างไว้ (ปิดไว้เป็นค่าเริ่มต้น)'],
            'alt' => 'ภาพตัวอย่างหน้า Settings ของ GPUxMINE: ช่องรหัสจับคู่ ตารางเวลาแชร์รายสัปดาห์ และค่าไฟ',
        ],
        [
            'img' => $shot('benchmark.webp'), 'mock' => true, 'name' => 'วัดจริง ไม่ใช่การประกาศเอง', 'eyebrow' => 'Benchmark', 'tier' => 'free',
            'th' => 'ก่อนรับงาน โปรแกรมวัดความเร็วการ์ดด้วยงานมาตรฐานผ่าน ComfyUI ในเครื่อง ได้คะแนนเครื่อง (1000 = เครื่องอ้างอิง) และผลต่อประเภทงาน: รับได้เต็มความเร็ว งานไม่เร่ง หรือรับไม่ได้ แล้วปรับตามเวลาจริงของงานที่ทำ',
            'en' => 'A real measurement through your own ComfyUI: a node score and, per job type, full speed, relaxed, or not possible.',
            'points' => ['ประเมินใหม่เองเมื่อเปลี่ยนการ์ด อัปเดตเวอร์ชัน หรือผลเก่าเกิน 30 วัน', '"งานไม่เร่ง" ยังได้งานและได้ค่าตอบแทน — เป็นงานที่ไม่มีคนนั่งรอ'],
            'alt' => 'ภาพตัวอย่างหน้า Benchmark ของ GPUxMINE: คะแนนเครื่อง ผลการประเมิน และงานที่เครื่องรับได้',
        ],
    ];

    // งานหนึ่งชิ้นเดินทางอย่างไร — ตามลำดับใน README ของโปรแกรม และการจ่ายเงินฝั่งเว็บ (gpuxmine:settle-earnings ทุกชั่วโมง)
    $journey = [
        [
            'key' => 'pair', 'name' => 'จับคู่เครื่อง', 'short' => 'รหัสจากหน้า เครื่องของฉัน',
            'th' => 'ขอรหัสจับคู่จากหน้า เครื่องของฉัน บน XMAN Studio แล้วพิมพ์ในหน้า Settings ของโปรแกรม เครื่องได้ตัวตนของตัวเองและผูกกับบัญชีคุณ — โปรแกรมสร้างตัวตนเองไม่ได้ ต้องมาจากรหัสบนเว็บเท่านั้น',
            'en' => 'A one-time code from My GPUs on XMAN Studio ties the machine to your account.',
            'points' => ['รหัสใช้ได้ครั้งเดียว อายุ ' . $pairTtl . ' นาที หมดแล้วขอใหม่ได้', 'บัญชีหนึ่งลงทะเบียนได้สูงสุด ' . $nodeCap . ' เครื่อง'],
        ],
        [
            'key' => 'assess', 'name' => 'ประเมินเครื่อง', 'short' => 'วัดความเร็วผ่าน ComfyUI',
            'th' => 'โปรแกรมวัดความเร็วการ์ดเองตอนเปิดครั้งแรก เครื่องจะรับงานได้ก็ต่อเมื่อผ่านการประเมินแล้วเท่านั้น',
            'en' => 'The client benchmarks the card first; only an assessed machine gets work.',
            'points' => [],
        ],
        [
            'key' => 'pool', 'name' => 'เข้า pool', 'short' => 'pool เลือกงานที่เครื่องรับไหว',
            'th' => 'XMAN Studio ส่งข้อมูลเครื่องให้ pool ของ AIXMAN ซึ่งตัดสินว่าเครื่องนี้รับงานโมเดลไหนได้ เครื่องที่บ้านได้เฉพาะโมเดลที่ทำไว้สำหรับการ์ดบ้าน และเฉพาะงานเนื้อหาทั่วไปที่ไม่มีไฟล์ของลูกค้าติดมา',
            'en' => 'The AIXMAN pool decides which models a home card may run: general content only, never a customer\'s own files.',
            'points' => ['ตอนนี้งานสำหรับการ์ดบ้านคือการสร้างภาพด้วย SDXL', 'หน้า Dashboard บอกสถานะจาก pool ตรง ๆ เช่น "ยังไม่มีงานที่ตรงกับเครื่องนี้" พร้อมเหตุผล'],
        ],
        [
            'key' => 'job', 'name' => 'รับงานทีละงาน', 'short' => 'ส่งผลแล้วลบทิ้ง',
            'th' => 'งานวิ่งเข้า ComfyUI ในเครื่องคุณ พอ pool ดึงผลงานไปแล้วก็สั่งลบไฟล์ผลงานและประวัติของงานนั้น ถ้าไม่มีใครสั่ง เครื่องลบเองหลัง 6 ชั่วโมง งานของคุณเองใน ComfyUI ไม่ถูกแตะ',
            'en' => 'Each job runs in your ComfyUI; its output and history are deleted once delivered, or after 6 hours.',
            'points' => [],
        ],
        [
            'key' => 'hold', 'name' => 'พักเงินแล้วเข้ากระเป๋า', 'short' => 'พัก ' . $holdHours . ' ชม. · โอนทุกชั่วโมง',
            'th' => 'ค่าตอบแทนของแต่ละงานพักไว้ ' . $holdHours . ' ชั่วโมงเพื่อตรวจสอบ แล้วเข้ากระเป๋าเงิน XMAN ของคุณอัตโนมัติในรอบโอนทุกชั่วโมง ไม่ต้องรอใครกดจ่าย งานที่ผลดูผิดปกติขึ้นว่า "รอตรวจสอบ" จนกว่าผู้ดูแลจะตัดสิน',
            'en' => 'Each job\'s pay is held for ' . $holdHours . ' hours for checks, then lands in your XMAN wallet in the hourly run.',
            'points' => ['ดูได้ทุกรายการที่หน้า เครื่องของฉัน: อยู่ในระยะพัก · รอตรวจสอบ · รอโอนรอบถัดไป · เข้ากระเป๋าแล้ว', 'งานที่แชร์ฟรีตามสัดส่วนที่คุณตั้งไม่ได้ค่าตอบแทน แต่เห็นมูลค่าของงานนั้น'],
        ],
        [
            'key' => 'cash', 'name' => 'ใช้จ่ายหรือถอนเป็นเงินสด', 'short' => 'ยืนยันตัวตน · แอดมินอนุมัติ',
            'th' => 'เงินในกระเป๋า XMAN ใช้ซื้อสินค้าและบริการบน XMAN Studio ได้ หรือขอถอนเป็นเงินสดที่หน้ากระเป๋าเงิน (ต้องยืนยันตัวตนก่อน) แล้วแอดมินอนุมัติและโอนเข้าบัญชีของคุณ',
            'en' => 'Spend it on XMAN Studio, or request a cash withdrawal (identity check first); our team approves and transfers it.',
            'points' => [],
        ],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'GPUxMINE',
        'operatingSystem' => 'Windows (64-bit)',
        'applicationCategory' => 'UtilitiesApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'โปรแกรมแชร์การ์ดจอที่บ้านให้รับงาน AI ผ่าน ComfyUI ในเครื่อง ค่าตอบแทนเข้ากระเป๋า XMAN Studio ตั้งตารางเวลาและเพดานอุณหภูมิได้ หยุดรับงานเองเมื่อเจ้าของใช้เครื่อง',
        'image' => asset('images/products/gpuxmine/v2/og.jpg'),
        'url' => route('products.show', 'gpuxmine'),
        'isAccessibleForFree' => true,
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
    ];
    if ($latest) {
        $ld['softwareVersion'] = $latest->version;
    }
@endphp
<x-xp.page a="#ffcf3a" b="#f59e0b" c="#60a5fa" glow="96 165 250" g1="#ffd77a" g2="#f59e0b" g3="#60a5fa">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="ให้การ์ดจอทำงาน AI ตอนคุณไม่ได้ใช้"
               tagline-en="Your idle graphics card takes AI jobs, and you get paid."
               lead="เปิดโปรแกรมทิ้งไว้ ระบบส่งงานสร้างภาพ AI มาให้การ์ดจอของคุณทำเท่าที่เครื่องไหว ตามตารางเวลาที่คุณตั้ง และถอยให้ทันทีเมื่อคุณกลับมาใช้เครื่อง — ค่าตอบแทนของแต่ละงานเข้ากระเป๋าเงิน XMAN ของคุณ"
               lead-en="Leave it running: AI jobs your card can handle arrive on your schedule, it steps aside when you are back, and each job pays into your XMAN wallet.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'GPUxMINE ' . $latest->version : 'GPUxMINE' }}</span>
            <span class="xp-chip">Windows 64-bit · ComfyUI</span>
        </x-slot:chips>
        <x-slot:title>GPU<span class="xp-text-glow">xMINE</span></x-slot:title>
        <x-slot:actions>
            <x-xp.download :href="route('gpuxmine.download')" :coming-soon="$csDownload" />
            <a href="{{ route('gpuxmine.index') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">
                <x-bi th="เครื่องของฉัน · ขอรหัสจับคู่" en="My GPUs · pairing code" />
            </a>
        </x-slot:actions>
        <x-slot:notes>
            <p class="mt-4 text-sm text-gray-400">
                @if($latest)เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · @endif
                ตัวติดตั้ง .exe จาก xman4289.com โดยตรง · ต้องเข้าสู่ระบบก่อนดาวน์โหลด
            </p>
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="free" /> โปรแกรมใช้ฟรี</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ถอยให้เมื่อคุณใช้เครื่อง</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ไม่ต้องเปิดพอร์ต</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('dashboard.webp')" :eager="true" mock
                             alt="ภาพตัวอย่างหน้า Dashboard ของ GPUxMINE: สถานะการแชร์ เกจโหลดและอุณหภูมิการ์ด สถานะจาก pool และงานที่กำลังทำ" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">ค่าตอบแทน · Earnings</p>
                <p class="text-white font-extrabold text-lg leading-tight">พัก {{ $holdHours }} ชม. <span class="text-amber-300">แล้วเข้ากระเป๋า XMAN</span></p>
            </div>
            <div class="xp-float-2 absolute -right-2 sm:-right-6 -top-5 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">เปิดเกม · Full-screen</p>
                <p class="text-white font-bold leading-tight">หยุดรับงานใหม่ทันที<br><span class="text-gray-400 text-sm font-normal">งานที่ค้างทำต่อจนเสร็จ</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================= YOUR MACHINE, YOUR RULES ========================= --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Your PC · your rules" title="เครื่องของคุณ" title2="กติกาของคุณ"
                       sub="คุณเลือกเองว่าแชร์ช่วงไหน ร้อนได้แค่ไหน และให้ถอยเมื่อไร ตัวเลขทุกตัวบนหน้าจอวัดจากเครื่องนี้หรือมาจากระบบจริง ไม่มีการประมาณตัวเลขเงินให้ดูสวย"
                       en="You decide when it shares, how hot it may run and when it steps aside. Every number on screen is measured or reported, never estimated money." />
            <x-xp.rows :items="$controlRows" />
        </div>
    </section>

    {{-- ========================= HOW A JOB PAYS ========================= --}}
    <section class="relative py-20 isolate scroll-mt-20" id="how-it-works">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="From pairing to wallet" title="งานหนึ่งชิ้น" title2="เดินทางอย่างไร"
                       sub="ตั้งแต่จับคู่เครื่องจนเงินเข้ากระเป๋า — ทุกขั้นเห็นได้ในโปรแกรมและที่หน้า เครื่องของฉัน"
                       en="From pairing to your wallet, every step is visible in the client and on My GPUs." />
            <x-xp.explorer id="journey" label="ขั้นตอนตั้งแต่จับคู่เครื่องจนเงินเข้ากระเป๋า" :tools="$journey" />
        </div>
    </section>

    {{-- ============================ SAFETY ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Safe to leave running" title="แชร์การ์ด" title2="ไม่ใช่แชร์เครื่อง"
                       sub="คนอื่นใช้ได้แค่ช่องทางรับงาน AI ที่กำหนดไว้ ไม่ได้เข้าถึงไฟล์หรือโปรแกรมอื่นในเครื่องคุณ"
                       en="Others reach only the AI job routes, not your files or anything else on the PC." />
            <x-xp.cards :items="[
                ['icon' => 'globe', 'title' => 'ไม่ต้องเปิดพอร์ต', 'body' => 'โปรแกรมต่อออกไปหาตัวกลาง (relay) แล้วถือสายไว้ งานวิ่งลงมาตามสายนั้น ใช้ได้หลัง NAT/เราเตอร์บ้านโดยไม่ต้องตั้งค่าอะไร', 'en' => 'Outbound only — nothing to open on your router'],
                ['icon' => 'lock', 'title' => 'เฉพาะเส้นทางที่ใช้จริง', 'body' => 'ส่งต่อเฉพาะคำสั่งที่งานต้องใช้ (ส่งงาน อ่านผล ดาวน์โหลดผลงาน ลบงาน) คำสั่งอื่นของ ComfyUI เช่นติดตั้ง custom node ถูกปฏิเสธ ทั้งที่ตัวกลางและที่เครื่องคุณ', 'en' => 'An allow-list, checked twice'],
                ['icon' => 'doc', 'title' => 'ส่งผลแล้วลบ', 'body' => 'ไฟล์ผลงานและประวัติของงานถูกลบหลังส่ง หรือหลัง 6 ชั่วโมงถ้าไม่มีใครสั่ง งานของคุณเองใน ComfyUI ไม่ถูกแตะ', 'en' => 'Outputs deleted after delivery'],
                ['icon' => 'user', 'title' => 'ถอยให้เจ้าของ', 'body' => 'ตรวจจับการใช้งานและเกม/โปรแกรมเต็มจอ แล้วหยุดรับงานใหม่ทันที เปิดอยู่เป็นค่าเริ่มต้น', 'en' => 'Steps aside when you use the PC'],
                ['icon' => 'power', 'title' => 'กด STOP ไม่ทิ้งงาน', 'body' => 'STOP = ไม่รับงานใหม่ แต่ส่งงานที่รับไว้ให้ครบก่อน และอัปเดตตัวเองโดยไม่ทิ้งงานกลางทาง ถ้าเลือก “หยุดทันที” โปรแกรมบอกก่อนว่างานที่ยังไม่ส่งจะไม่ได้ค่าตอบแทน', 'en' => 'Stop finishes the job in hand first'],
                ['icon' => 'eye', 'title' => 'ไม่มีตัวเลขเงินประมาณเอา', 'body' => 'ยอดเงินในโปรแกรมและบนเว็บมาจากที่ระบบบันทึกจริงเป็นสตางค์ ตรงไหนยังไม่มีข้อมูลจะขึ้น “—” พร้อมเหตุผล ไม่ใช่ตัวเลขสวย ๆ', 'en' => 'Real figures or a dash, never a guess'],
            ]" />
        </div>
    </section>

    {{-- =========================== COST & EARNINGS =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="earnings">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Free to use" title="โปรแกรมฟรี" title2="ได้ค่าตอบแทนตามงาน"
                       sub="ไม่มีค่าสมัคร ไม่มีแพ็กเกจให้ซื้อ — อัตราค่าตอบแทนของแต่ละงานกำหนดโดย pool และแต่ละรายการเห็นได้ที่หน้า เครื่องของฉัน"
                       en="No fee and nothing to buy. The pool sets what each job pays, and every payment is listed on My GPUs." />

            <div class="grid md:grid-cols-3 gap-5 max-w-6xl mx-auto">
                <div class="xp-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">โปรแกรม</h3>
                        <x-xp.badge tier="free" />
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="ดาวน์โหลดและใช้ได้ฟรี" en="Free download, free to run" /></p>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ทุกหน้าในโปรแกรม ไม่มีส่วนที่ล็อกไว้', 'อัปเดตตัวเองอัตโนมัติจาก xman4289.com', 'บัญชีหนึ่งลงทะเบียนได้สูงสุด ' . $nodeCap . ' เครื่อง']" />
                    <x-xp.download block :href="route('gpuxmine.download')" :coming-soon="$csDownload" />
                </div>
                <div class="xp-card p-7 flex flex-col">
                    <h3 class="text-2xl font-black text-white mb-1">ค่าตอบแทน</h3>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="ต่องานที่ทำเสร็จ" en="Per finished job" /></p>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-6 flex-1" :items="['พักไว้ ' . $holdHours . ' ชั่วโมงเพื่อตรวจสอบ', 'เข้ากระเป๋า XMAN อัตโนมัติ รอบโอนทุกชั่วโมง', 'งานที่ผลผิดปกติรอผู้ดูแลตรวจก่อน', 'ส่วนที่คุณตั้งให้แชร์ฟรีไม่ได้ค่าตอบแทน']" />
                    <a href="{{ route('gpuxmine.index') }}" class="xp-btn-ghost block w-full py-3.5 text-center rounded-xl font-extrabold transition">ดูรายได้ที่หน้า เครื่องของฉัน</a>
                </div>
                <div class="xp-card p-7 flex flex-col">
                    <h3 class="text-2xl font-black text-white mb-1">ต้นทุนของคุณ</h3>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="ค่าไฟของเครื่องเอง" en="Your own electricity" /></p>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-6 flex-1" :items="['หน้า Power คำนวณค่าไฟต่อวันจากวัตต์ที่อ่านจากการ์ดจริง × ค่าไฟที่คุณใส่', 'ตั้งช่วงไฟถูกในตารางเวลาได้', 'ค่าไฟจริงดูจากบิลของคุณเอง']" />
                    <a href="{{ route('user.wallet.index') }}" class="xp-btn-ghost block w-full py-3.5 text-center rounded-xl font-extrabold transition">กระเป๋าเงิน XMAN</a>
                </div>
            </div>
            <p class="text-center text-xs text-gray-500 mt-6 max-w-3xl mx-auto">เงินในกระเป๋า XMAN ใช้ซื้อสินค้าและบริการบน XMAN Studio ได้ หรือถอนเป็นเงินสด (ต้องยืนยันตัวตน แอดมินอนุมัติแล้วโอนเข้าบัญชี) · งานที่เข้ากระเป๋าแล้วแต่ภายหลังพบว่าผิดปกติ ผู้ดูแลเรียกคืนได้ถ้ายอดยังไม่ถูกถอนออก · เครื่องที่ส่งผลงานผิดปกติอาจถูกระงับ และเงินที่ยังไม่เข้ากระเป๋าของเครื่องนั้นจะพักไว้จนกว่าจะยกเลิกการระงับ</p>
        </div>
    </section>

    <x-xp.setup id="download" class="scroll-mt-20"
        :specs="[
            ['ระบบปฏิบัติการ', 'Windows แบบ 64-bit · ไม่ต้องติดตั้ง .NET เพิ่ม', 'Windows, 64-bit, self-contained'],
            ['การ์ดจอ', 'การ์ดที่รัน ComfyUI กับ SDXL ได้ — VRAM 6 GB ขึ้นไป', 'A card that runs SDXL in ComfyUI, 6 GB VRAM or more'],
            ['ComfyUI + SDXL', 'ติดตั้ง ComfyUI ให้รันในเครื่อง และวาง checkpoint ของ SDXL ในโฟลเดอร์ checkpoints — โปรแกรมไม่ลงให้', 'Install ComfyUI and an SDXL checkpoint yourself'],
            ['บัญชี XMAN Studio', 'ใช้รับรหัสจับคู่และรับค่าตอบแทน · ต่อเน็ตตลอดที่แชร์', 'An XMAN account, online while sharing'],
        ]"
        specs-note="ตัวเลือก Power profile ในโปรแกรมยังไม่ได้จำกัดกำลังการ์ดจริง — ถ้าต้องการจำกัด ให้ตั้ง power limit ในโปรแกรมของการ์ดจอ (เช่น MSI Afterburner) แล้วกดประเมินเครื่องใหม่"
        :steps="[
            ['ติดตั้ง ComfyUI + SDXL แล้วลง GPUxMINE', 'ดาวน์โหลดตัวติดตั้งจากหน้านี้ (ต้องเข้าสู่ระบบ) ติดตั้งครั้งเดียว จากนั้นอัปเดตตัวเอง', 'Install ComfyUI, then GPUxMINE'],
            ['ขอรหัสจับคู่ที่หน้า เครื่องของฉัน', 'ได้รหัส 8 ตัว อายุ ' . $pairTtl . ' นาที พิมพ์ในหน้า Settings ของโปรแกรมแล้วกดลงทะเบียน', 'Get a pairing code on My GPUs'],
            ['กด START แล้วปล่อยไว้', 'โปรแกรมประเมินเครื่องเอง แล้วรับงานตามที่การ์ดไหวและตามตารางเวลาของคุณ ดูสถานะ pool ได้ที่หน้า Dashboard', 'Press START and leave it running'],
        ]" />

    <x-xp.faq :items="[
        ['ต้องจ่ายอะไรไหม?', 'ไม่ต้อง โปรแกรมดาวน์โหลดและใช้ได้ฟรี ไม่มีค่าสมัครและไม่มีแพ็กเกจให้ซื้อ ต้นทุนของคุณคือค่าไฟของเครื่องเอง ซึ่งหน้า Power ในโปรแกรมคำนวณให้จากวัตต์ที่วัดจากการ์ดจริง'],
        ['ได้เงินเท่าไร?', 'ขึ้นกับงานที่ pool ส่งมาและที่การ์ดของคุณรับไหว อัตราต่องานกำหนดโดย pool เราจึงไม่ประมาณตัวเลขให้ — ค่าตอบแทนจริงของทุกงานแสดงที่หน้า เครื่องของฉัน และในโปรแกรมหน้า Wallet / History'],
        ['เงินเข้าเมื่อไร ถอนได้ไหม?', 'ค่าตอบแทนแต่ละงานพักไว้ ' . $holdHours . ' ชั่วโมงเพื่อตรวจสอบ แล้วเข้ากระเป๋าเงิน XMAN อัตโนมัติในรอบโอนทุกชั่วโมง ใช้ซื้อของบน XMAN Studio ได้ หรือขอถอนเป็นเงินสดที่หน้ากระเป๋าเงิน (ต้องยืนยันตัวตน) แล้วแอดมินอนุมัติและโอนเข้าบัญชีของคุณ'],
        ['เล่นเกมอยู่จะแย่งการ์ดไหม?', 'ไม่ เมื่อเปิดเกมหรือโปรแกรมเต็มจอ หรือคุณกำลังใช้เครื่อง GPUxMINE หยุดรับงานใหม่ทันที (เปิดอยู่เป็นค่าเริ่มต้น) งานที่รับไว้แล้วทำต่อจนเสร็จ และตั้งตารางเวลาให้แชร์เฉพาะชั่วโมงที่คุณไม่ใช้เครื่องได้'],
        ['ทำไมเครื่องยังไม่ได้งาน?', 'เครื่องต้องผ่านการประเมินก่อน และต้องมีโมเดลในระบบที่การ์ดของคุณรับไหว ตอนนี้งานสำหรับการ์ดบ้านคือการสร้างภาพด้วย SDXL หน้า Dashboard ในโปรแกรมและหน้า เครื่องของฉัน บอกเหตุผลตรง ๆ เช่น “ยังไม่มีงานที่ตรงกับเครื่องนี้” หรือถูกระงับพร้อมเหตุผล'],
        ['คนอื่นเข้าถึงไฟล์ในเครื่องได้ไหม?', 'ไม่ได้ โปรแกรมส่งต่อเฉพาะคำสั่งที่งาน AI ต้องใช้ไปที่ ComfyUI ในเครื่อง คำสั่งอื่นถูกปฏิเสธ ผลงานถูกลบหลังส่ง และเครื่องที่บ้านได้เฉพาะงานเนื้อหาทั่วไปที่ไม่มีไฟล์ของลูกค้าติดมา'],
        ['แชร์ฟรีคืออะไร?', 'คุณตั้งสัดส่วนงานที่ให้ pool ฟรีได้ 0–100% (ค่าเริ่มต้น 0%) งานส่วนนั้นไม่ได้ค่าตอบแทน แต่บันทึกมูลค่าไว้ให้เห็น และเลือกได้ว่าจะแสดงชื่อในทำเนียบผู้บริจาคหรือไม่ประสงค์ออกนาม'],
        ['มีผู้แนะนำแล้วมีผลอะไร?', 'ถ้าตอนจับคู่เครื่องแรกคุณมีผู้แนะนำ (เข้ามาจากลิงก์แนะนำ หรืออยู่ในผังของผู้แนะนำ) ส่วนแบ่งของผู้แนะนำจะหักจากค่าตอบแทนของแต่ละงาน ผู้แนะนำถูกจับครั้งเดียวต่อบัญชี และหน้า เครื่องของฉัน แสดงคอลัมน์ “หักผู้แนะนำ” แยกให้เห็นทุกรายการ ถ้าผู้แนะนำไม่ได้ใช้งานแล้วตอนถึงรอบโอน ตามค่าเริ่มต้นส่วนนั้นคืนให้คุณ'],
        ['ใช้ได้กี่เครื่อง?', 'บัญชีหนึ่งลงทะเบียนได้สูงสุด ' . $nodeCap . ' เครื่อง แต่ละเครื่องจับคู่ด้วยรหัสของตัวเอง ถอนเครื่องที่ไม่ใช้แล้วออกได้ที่หน้า เครื่องของฉัน — ยอดที่ค้างจ่ายยังอยู่ตามเดิม'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="การ์ดจอว่าง" glow="ให้มันทำงาน"
              sub="ดาวน์โหลดฟรี จับคู่เครื่องกับบัญชีในไม่ถึงนาที แล้วรับค่าตอบแทนเข้ากระเป๋า XMAN"
              en="Download free, pair in under a minute, get paid into your XMAN wallet.">
        <x-xp.download :href="route('gpuxmine.download')" :coming-soon="$csDownload" class="!px-8" />
        <a href="{{ route('gpuxmine.index') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">เครื่องของฉัน</a>
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows 64-bit · ต้องมี ComfyUI + SDXL (VRAM 6 GB ขึ้นไป) · ต้องเข้าสู่ระบบก่อนดาวน์โหลด · ใช้ฟรี</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
