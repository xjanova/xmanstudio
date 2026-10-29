@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย และซื้อไม่ได้ (กฎเจ้าของ 2026-10-12) --}}
@php $csDownload = \App\Models\Product::slugIsComingSoon('tping'); @endphp

@section('title', 'Tping — ช่วยพิมพ์และกดแทนบน Android · ทดลองฟรี 24 ชม.' . (\App\Support\LicensePlans::price('tping', 'monthly') ? ' · เริ่ม ฿' . number_format(\App\Support\LicensePlans::price('tping', 'monthly')) . '/เดือน' : '') . ' | XMAN Studio')
@section('meta_description', 'Tping แอปช่วยพิมพ์บน Android สำหรับผู้ที่ใช้นิ้วไม่สะดวกและงานกรอกซ้ำ ๆ บันทึกขั้นตอนครั้งเดียวแล้วเล่นซ้ำ 1–999 รอบ สลับชุดข้อมูลทุกรอบ โหมดเกมวางพิกัดด้วยกากบาท กดรัว และแก้ captcha แบบเลื่อน ไม่ต้อง Root ทดลองฟรี 24 ชั่วโมง · An Android auto-typer: record once, play it back.')
@section('og_image', asset('images/products/tping/v2/og.jpg'))

@section('content')
@php
    // ราคาทุกแผนมาจาก config/licenses.php 'plans' ที่เดียว (หน้า checkout ของ Tping คิดตามนี้) — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $prices = \App\Support\LicensePlans::for('tping');
    $monthly = isset($prices['monthly']) ? number_format($prices['monthly']) : null;
    $yearlySaving = \App\Support\LicensePlans::yearlySaving('tping');

    // ส่วนลดเมื่อจ่ายด้วย Wallet — อ่านจากตัวที่หน้า checkout ใช้คิดเงินจริง (TpingController::WALLET_DISCOUNT_PERCENT)
    $walletDiscount = (new \ReflectionClassConstant(\App\Http\Controllers\TpingController::class, 'WALLET_DISCOUNT_PERCENT'))->getValue();
    $tpixPay = \App\Services\TpixPay\TpixCheckout::available();

    // เวอร์ชันที่ /tping/download/apk ส่งจริง (ตัวที่ active — TpingController ส่ง $version มาให้)
    $latest = $version ?? null;

    // ภาพหน้าจอ: mockup วาดตามโค้ด Compose ของแอป (ยังไม่มีภาพจริง) — สร้างใหม่ได้ด้วย scripts/product-mockups/build.py tping
    $shot = fn (string $file) => asset('images/products/tping/v2/' . $file);

    // จำนวนเครื่องต่อคีย์ = max_activations ที่ TpingController::generateLicenseForOrder ออกให้ (ตลอดชีพ 3 · นอกนั้น 1)
    $plans = [
        'monthly' => ['name' => 'รายเดือน', 'en' => 'Monthly', 'unit' => '/ เดือน', 'note' => 'ซื้อทีละ 30 วัน ไม่ตัดเงินอัตโนมัติ', 'days' => '30 วัน', 'devices' => '1 เครื่อง'],
        'yearly' => ['name' => 'รายปี', 'en' => 'Yearly', 'unit' => '/ ปี', 'note' => $yearlySaving ? 'ถูกกว่ารายเดือน ' . $yearlySaving . '%' : 'จ่ายครั้งเดียวใช้ทั้งปี', 'days' => '365 วัน', 'devices' => '1 เครื่อง'],
        'lifetime' => ['name' => 'ตลอดชีพ', 'en' => 'Lifetime', 'unit' => 'ครั้งเดียว', 'note' => 'จ่ายครั้งเดียว ใช้ได้ตลอด', 'days' => 'ไม่หมดอายุ', 'devices' => 'สูงสุด 3 เครื่อง'],
    ];

    // บันทึก → เล่นซ้ำ → ชุดข้อมูล — ข้อเท็จจริงจากโค้ดแอป (HomeScreen guide, FloatingOverlayContent, PlaybackEngine, DataProfileScreen)
    $coreRows = [
        [
            'img' => $shot('record.webp'), 'mock' => true, 'phone' => true, 'name' => 'บันทึกขั้นตอน', 'eyebrow' => 'Record once',
            'th' => 'กดปุ่มลอย "บันทึก" แล้วไปใช้แอปที่ต้องการตามปกติ — แตะ กดค้าง พิมพ์ เลื่อน ย้อนกลับ Tping จำทุกขั้นตอนไว้ แล้วกด "Tag" เพื่อผูกช่องที่พิมพ์กับข้อมูล เช่น อีเมล หรือรหัสผ่าน',
            'en' => 'Use any app as you normally would; Tping remembers every tap, long-press, text, scroll and back. Tag a field to fill it from your data.',
            'points' => ['ระบบแนะนำชื่อช่อง (อีเมล รหัสผ่าน) และชื่อ Workflow จากแอปเป้าหมายให้', 'หาปุ่มด้วย ID → ข้อความ → พิกัดจอ ไล่ตามลำดับ', 'ตั้งชื่อ Workflow แล้วบันทึก ใช้ซ้ำได้ตลอด'],
            'alt' => 'ภาพตัวอย่าง Tping กำลังบันทึกขั้นตอนในแอปอื่น พร้อมหน้าต่างผูกข้อมูลกับช่องอีเมล',
        ],
        [
            'img' => $shot('play.webp'), 'mock' => true, 'phone' => true, 'name' => 'เล่นอัตโนมัติ', 'eyebrow' => 'Play it back',
            'th' => 'เลือก Workflow เลือกหมวดข้อมูล ตั้งจำนวนรอบ 1–999 แล้วกดเริ่มเล่น Tping เปิดแอปเป้าหมายให้เอง และใช้ข้อมูลชุดถัดไปในแต่ละรอบ ข้อมูลหมดก่อนรอบจะวนกลับไปชุดแรก',
            'en' => 'Pick a workflow and a data category, set 1–999 loops and press play. Each loop takes the next data set.',
            'points' => ['สุ่มลำดับข้อมูลได้ โดยไม่ซ้ำในหมวดเดียวกัน', 'Turbo Mode: ไม่มีดีเลย์ระหว่างขั้นตอน (ปกติรอ 3 วินาที)', 'พัก ต่อ หยุด ได้ทั้งจากหน้าแอปและปุ่มลอย'],
            'alt' => 'ภาพตัวอย่างหน้าต่างเลือก Workflow ของ Tping: ข้อมูลที่ต้องใช้ หมวดข้อมูล จำนวนรอบ และ Turbo Mode',
        ],
        [
            'img' => $shot('data.webp'), 'mock' => true, 'phone' => true, 'name' => 'ชุดข้อมูล', 'eyebrow' => 'Data sets',
            'th' => 'เก็บข้อมูลที่ต้องกรอกบ่อยเป็นชุด ๆ เช่น "บัญชี A" "บัญชี B" แต่ละชุดมีกี่ช่องก็ได้ และจัดเข้าหมวดเพื่อเลือกใช้ตอนเล่น',
            'en' => 'Keep what you type often as named sets of fields, grouped into categories you pick at play time.',
            'points' => ['Gen ชุดข้อมูล: สร้างหลายชุดในครั้งเดียวจากรูปแบบเดียว ใช้ * แทนเลขลำดับ สูงสุด 500 ชุดต่อครั้ง (ต้องมี License)', 'ทำสำเนา แก้ไข ลบ ได้ทีละชุด'],
            'alt' => 'ภาพตัวอย่างหน้าจัดการข้อมูลของ Tping พร้อมหน้าต่าง Gen ชุดข้อมูล',
        ],
    ];

    // โหมดเกม (ภาพมือถือ จึงเป็นแถว ไม่ใช่แท็บ) + เครื่องมือเสริม — ใช้ได้ทั้งตอนทดลองและเมื่อมี License
    $gameRow = [
        'img' => $shot('game.webp'), 'mock' => true, 'phone' => true, 'name' => 'โหมดเกม (พิกัด)', 'eyebrow' => 'Game mode',
        'th' => 'เกมและแอปบางตัวไม่บอกตำแหน่งปุ่มให้ระบบอ่าน เลือกโหมด "เกม" ตอนบันทึก แล้ววางจุดกดเองด้วยกากบาท ลากไปตำแหน่งที่ต้องการแล้วกดตกลง',
        'en' => 'For games and apps that expose no buttons: place every tap yourself with a crosshair.',
        'points' => ['กด · กดค้าง · กรอกข้อมูล · กดรัว · รอ 1 หรือ 3 วินาที · ย้อนกลับ · หน้าหลัก', 'บล็อกพิกัดลากย้ายได้ ไม่บังจุดที่จะวาง', 'ตอนเล่นต้องใช้ความละเอียดจอเท่าเดิม และห้ามหมุนจอ'],
        'alt' => 'ภาพตัวอย่างโหมดเกมของ Tping: กากบาทวางพิกัด และแผงปุ่มคำสั่งของโหมดเกม',
    ];
    $gameTools = [
        [
            'key' => 'rapid', 'name' => 'กดรัว', 'short' => 'กดซ้ำจุดเดิมเร็ว ๆ',
            'th' => 'วางจุดด้วยกากบาท แล้วตั้งให้กดซ้ำที่จุดนั้นตามจำนวนครั้งและความเร็วที่ต้องการ หรือกดไปเรื่อย ๆ จนกว่าจะสั่งหยุด',
            'en' => 'Tap one spot over and over, a set number of times or until you stop it.',
            'points' => ['เลือกจำนวน 5 / 10 / 20 / 50 / 100 ครั้ง หรือ ∞ ไม่จำกัด', 'ความเร็ว 30–2,000 ms ต่อครั้ง (เร็วสุด เร็ว ปกติ ช้า ช้ามาก)'],
        ],
        [
            'key' => 'captcha', 'name' => 'Captcha แบบเลื่อน', 'short' => 'เลื่อนชิ้นส่วนให้เอง',
            'th' => 'เจอ captcha แบบเลื่อนชิ้นส่วนเข้าช่อง กดปุ่ม Captcha ในโหมดเกม แล้วกำหนดตำแหน่งตัวเลื่อนกับปุ่มรีเฟรช ตอนเล่น Tping หาช่องจากภาพหน้าจอและเลื่อนให้เอง',
            'en' => 'Slider-puzzle captchas: mark the slider and refresh button once, and Tping finds the gap and slides it during playback.',
            'points' => ['วิเคราะห์ภาพด้วย OpenCV บนมือถือเอง', 'ไม่ผ่านจะกดรีเฟรชแล้วลองใหม่ให้'],
        ],
        [
            'key' => 'float', 'name' => 'ปุ่มลอย + ปุ่มลัด', 'short' => 'สั่งงานได้จากทุกแอป',
            'th' => 'แผงควบคุมลอยอยู่บนทุกแอป บันทึก เล่น พัก หยุด ได้โดยไม่ต้องกลับมาที่ Tping และตั้งเป็นปุ่มลัดการช่วยเหลือพิเศษของ Android ได้ เปิดแผงจากในเกมได้ทันที',
            'en' => 'A floating panel over every app, also reachable from Android’s accessibility shortcut.',
            'points' => ['ปุ่มลัดเปิดได้ทั้งปุ่มลอยที่ขอบจอ กดปุ่มเสียงค้าง หรือปัดสองนิ้ว (แล้วแต่รุ่นเครื่อง)'],
        ],
    ];

    // License ปลดสิ่งนี้เพิ่ม — CloudScreen/CloudSyncManager/DataProfileScreen เช็ก LicenseStatus.ACTIVE (ช่วงทดลองไม่ใช่)
    $compare = [
        ['ในเครื่อง · On the phone', [
            ['บันทึกขั้นตอน โหมดปกติ + โหมดเกม', false],
            ['เล่นอัตโนมัติ 1–999 รอบ · สลับ/สุ่มชุดข้อมูล', false],
            ['Turbo Mode · กดรัว · Captcha แบบเลื่อน', false],
            ['ชุดข้อมูลหลายชุด แยกหมวด', false],
            ['ปุ่มลอย · ปุ่มลัดการช่วยเหลือพิเศษ · อัปเดตในแอป', false],
            ['Gen ชุดข้อมูลอัตโนมัติ (สูงสุด 500 ชุดต่อครั้ง)', true],
        ]],
        ['คลาวด์ · Cloud', [
            ['Cloud Sync — ซิงค์ Workflow และชุดข้อมูลข้ามเครื่อง', true],
            ['ดู/แก้ Workflow และชุดข้อมูลบนเว็บ (บัญชีของฉัน)', true],
            ['ส่งออก/นำเข้าเป็นไฟล์ JSON', true],
        ]],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Tping',
        'operatingSystem' => 'Android 8.0+',
        'applicationCategory' => 'UtilitiesApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'แอปช่วยพิมพ์และกดแทนบน Android บันทึกขั้นตอนครั้งเดียวแล้วเล่นซ้ำพร้อมสลับชุดข้อมูล มีโหมดเกม กดรัว และแก้ captcha แบบเลื่อน ไม่ต้อง Root',
        'image' => asset('images/products/tping/v2/og.jpg'),
        'url' => route('tping.detail'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => collect($prices)->map(fn ($price, $term) => ['@type' => 'Offer', 'name' => 'Tping (' . ($plans[$term]['en'] ?? $term) . ')', 'price' => (string) $price, 'priceCurrency' => 'THB'])->values()->all(),
    ];
@endphp
<x-xp.page a="#b9a3ff" b="#5eead4" c="#22d3ee" glow="139 92 246" g1="#8b5cf6" g2="#e040fb" g3="#22d3ee">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="ช่วยพิมพ์และกดแทนคุณ"
               tagline-en="An Android helper that taps and types for you."
               lead="สำหรับผู้ที่ใช้นิ้วไม่สะดวกและงานกรอกซ้ำ ๆ — บันทึกขั้นตอนการกดและพิมพ์ในแอปไหนก็ได้ครั้งเดียว แล้วให้ Tping ทำซ้ำแทนคุณ 1–999 รอบ พร้อมสลับชุดข้อมูลทุกรอบ ทำงานผ่านบริการการช่วยเหลือพิเศษของ Android ไม่ต้อง Root เครื่อง"
               lead-en="Record a routine once in any app and let Tping repeat it up to 999 times with a different data set each loop. No root needed.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'Tping ' . $latest->version : 'Tping' }}</span>
            <span class="xp-chip">Android 8.0 ขึ้นไป</span>
        </x-slot:chips>
        <x-slot:title>T<span class="xp-text-glow">ping</span></x-slot:title>
        <x-slot:actions>
            <x-xp.download :href="route('tping.download')" :coming-soon="$csDownload" th="ดาวน์โหลด ทดลองฟรี 24 ชม." en="Free 24-hour trial" />
            <a href="#pricing" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">
                ซื้อ License{{ $monthly ? ' — เริ่ม ฿' . $monthly . '/เดือน' : '' }}
            </a>
        </x-slot:actions>
        <x-slot:notes>
            @if($latest)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · ไฟล์ .apk จาก xman4289.com โดยตรง</p>
            @endif
            <p class="mt-2 text-sm"><a href="{{ route('tping.install-guide') }}" class="xp-link font-semibold">วิธีติดตั้งและเปิดสิทธิ์ทีละขั้น →</a></p>
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="free" label="24 ชม." /> ทดลองฟรี ไม่ต้องสมัคร</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ไม่ต้อง Root</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ใช้ได้ทุกแอปและเกม</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="max-w-xs mx-auto w-full">
                <x-xp.screen phone :src="$shot('home.webp')" :eager="true" :mock="true" :width="390" :height="845"
                             alt="ภาพตัวอย่างหน้าหลักของ Tping: สถานะทดลองใช้ สถานะสิทธิ์ ปุ่มลอย และเมนูหลัก" />
            </div>
            <div class="xp-float absolute -left-1 sm:left-0 bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">เล่นซ้ำ · Loops</p>
                <p class="text-white font-extrabold text-lg leading-tight">1–999 รอบ<br><span class="text-gray-400 text-sm font-normal">สลับชุดข้อมูลทุกรอบ</span></p>
            </div>
            <div class="xp-float-2 absolute right-0 top-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">Turbo Mode</p>
                <p class="text-white font-bold leading-tight">ไม่มีดีเลย์<br><span class="text-gray-400 text-sm font-normal">ระหว่างขั้นตอน</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================== RECORD · PLAY · DATA ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Record once · play it back" title="ทำครั้งเดียว" title2="ที่เหลือให้ Tping ทำ"
                       sub="งานกรอกซ้ำ ๆ สมัครสมาชิก ล็อกอิน หรือกดเดิมทุกวัน — บันทึกครั้งเดียว แล้วให้ Tping กดและพิมพ์แทนนิ้วคุณ"
                       en="Repetitive forms, sign-ups, logins, daily taps: record them once and let Tping do the tapping and typing." />
            <x-xp.rows :items="$coreRows" />
        </div>
    </section>

    {{-- ========================== GAME MODE + TOOLS ========================== --}}
    <section class="relative py-20 isolate">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="Games & extra tools" title="ใช้กับเกม" title2="และแอปที่กดยาก"
                       sub="โหมดเกม กดรัว และ Captcha แบบเลื่อน ใช้ได้ตั้งแต่ช่วงทดลอง ไม่ต้องรอซื้อ"
                       en="Game mode, rapid taps and slider captchas are all there from the trial on." />
            <x-xp.rows :items="[$gameRow]" class="mb-16" />
            <x-xp.explorer id="tools" label="เครื่องมือเสริมของ Tping" :tools="$gameTools" />
        </div>
    </section>

    {{-- ============================ CLOUD (License) ============================ --}}
    <section class="relative py-20 scroll-mt-20" id="cloud">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.rows :items="[[
                'img' => $shot('cloud.webp'), 'mock' => true, 'phone' => true, 'name' => 'Cloud Sync', 'eyebrow' => 'With a licence · ต้องมี License',
                'th' => 'มี License แล้ว Tping เชื่อม Xman Studio Cloud ให้เอง Workflow และชุดข้อมูลซิงค์ขึ้นคลาวด์ทุกครั้งที่แก้ ดึงลงเครื่องใหม่ได้ และดูหรือแก้บนเว็บในหน้า บัญชีของฉัน ได้ด้วย',
                'en' => 'With a licence, workflows and data sets sync to Xman Studio Cloud after every change, and show up in your account on the web.',
                'points' => ['อัปโหลด ดาวน์โหลด หรือซิงค์ทั้งหมดในปุ่มเดียว', 'ส่งออก/นำเข้า Workflow และชุดข้อมูลเป็นไฟล์ JSON ชื่อซ้ำเลือกได้ว่าจะข้าม แทนที่ หรือเปลี่ยนชื่อ', 'ช่วงทดลองยังไม่เปิด Cloud Sync — ข้อมูลอยู่ในเครื่องเท่านั้น'],
                'alt' => 'ภาพตัวอย่างหน้า Cloud Sync ของ Tping: อัปโหลด ดาวน์โหลด ซิงค์ทั้งหมด แดชบอร์ดบนเว็บ และ Import / Export',
            ]]" />
        </div>
    </section>

    {{-- ============================ TRUST ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Built for real phones" title="ใช้ง่าย" title2="ไม่ต้องแก้เครื่อง"
                       sub="Tping ใช้ความสามารถมาตรฐานของ Android เท่านั้น"
                       en="Tping only uses what Android itself offers." />
            <x-xp.cards :items="[
                ['icon' => 'phone', 'title' => 'ไม่ต้อง Root', 'body' => 'ทำงานผ่านบริการการช่วยเหลือพิเศษ (Accessibility) กับสิทธิ์แสดงทับแอปอื่น เปิดในหน้าตั้งค่าของ Android เท่านั้น', 'en' => 'Accessibility service + overlay, nothing more'],
                ['icon' => 'chip', 'title' => 'ข้อมูลอยู่ในเครื่อง', 'body' => 'Workflow และชุดข้อมูลเก็บในมือถือของคุณ ขึ้นคลาวด์เฉพาะเมื่อมี License และเชื่อม Cloud Sync แล้ว', 'en' => 'Your workflows stay on your phone'],
                ['icon' => 'key', 'title' => 'ลงใหม่ เครื่องเดิม คีย์กลับมาเอง', 'body' => 'ลบแอปแล้วลงใหม่บนเครื่องเดิม Tping ถามร้านว่าเครื่องนี้มีคีย์อยู่แล้วไหม แล้วเปิดใช้ให้เอง ไม่ต้องพิมพ์คีย์ใหม่', 'en' => 'Reinstall on the same phone, the key comes back'],
                ['icon' => 'cloud', 'title' => 'เน็ตหลุดก็ยังใช้ได้', 'body' => 'คีย์ที่ซื้อแล้วใช้ต่อได้แม้ติดต่อเซิร์ฟเวอร์ไม่ได้ จนถึงวันหมดอายุของคีย์', 'en' => 'A paid key keeps working offline until it expires'],
                ['icon' => 'download', 'title' => 'อัปเดตในแอป', 'body' => 'Tping เช็กเวอร์ชันใหม่จาก xman4289.com และโหลด APK ให้ในแอป เลือกเปิดอัปเดตอัตโนมัติได้', 'en' => 'Updates from xman4289.com, inside the app'],
                ['icon' => 'shield', 'title' => 'ตรวจว่าแอปไม่ถูกแก้', 'body' => 'แอปตรวจตัวเองตอนเปิด ถ้าไฟล์ถูกดัดแปลงจะไม่ใช้งาน License — ดาวน์โหลดจาก xman4289.com เท่านั้น', 'en' => 'A modified APK is refused'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="ลองฟรี 24 ชั่วโมง" title2="ถูกใจค่อยซื้อคีย์"
                       sub="เปิดแอปครั้งแรกตอนต่อเน็ต ได้ทดลอง 24 ชั่วโมงทันที ไม่ต้องสมัคร ไม่ต้องผูกบัตร · หมดเวลาแล้วใส่ License เพื่อใช้ต่อ"
                       en="Open the app once online for a 24-hour trial, no sign-up. After that, a licence key keeps it running." />

            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-5 max-w-6xl mx-auto">
                <div class="xp-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">ทดลอง</h3>
                        <x-xp.badge tier="free" label="24 ชม." />
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="ทุกฟีเจอร์ในเครื่อง" en="Everything on the phone" /></p>
                    <div class="mb-6"><span class="text-5xl font-black text-white">฿0</span></div>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['บันทึก + เล่นซ้ำ 1–999 รอบ', 'โหมดเกม · กดรัว · Captcha', 'ชุดข้อมูลหลายชุด', 'ไม่ต้องสมัคร ไม่ต้องผูกบัตร']" />
                    {{-- แอปไฟล์เดียวกับตัวที่ซื้อ — ใส่คีย์ในแอปเมื่อซื้อแล้ว --}}
                    <x-xp.download block :href="route('tping.download')" :coming-soon="$csDownload" th="ดาวน์โหลด" en="Try it free" />
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
                                <h3 class="text-2xl font-black text-white">{{ $plan['name'] }}</h3>
                                <x-xp.badge tier="pro" label="LICENSE" />
                            </div>
                            <p class="text-amber-100/80 text-sm mb-6">{{ $plan['note'] }}</p>
                            <div class="mb-6 flex items-baseline gap-2">
                                <span class="text-5xl font-black text-white">฿{{ number_format($prices[$term]) }}</span>
                                <span class="text-gray-300">{{ $plan['unit'] }}</span>
                            </div>
                            <x-tpix-price variant="detail" :thb="$prices[$term]" class="-mt-4 mb-6" />
                            <x-xp.ticks pro class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ทุกอย่างในช่วงทดลอง', 'Cloud Sync + แดชบอร์ดบนเว็บ', 'Gen ชุดข้อมูล · ส่งออก/นำเข้า', 'อายุ License: ' . $plan['days'], 'ใช้ได้ ' . $plan['devices']]" />
                            @if($csDownload)
                                {{-- Coming Soon: ยังไม่เปิดขาย — หน้า checkout เองก็ปฏิเสธ (ComingSoonPurchase) --}}
                                <span class="xp-btn-ghost xp-btn-off block w-full py-3.5 text-center rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
                            @else
                                {{-- ราคาคิดที่ TpingController::checkout จาก config/licenses.php — ลิงก์ส่งแค่ชื่อแผน --}}
                                <a href="{{ route('tping.checkout', $term) }}" class="{{ $featured ? 'xp-btn-pro' : 'xp-btn-ghost' }} block w-full py-3.5 text-center rounded-xl font-extrabold transition" data-plan="{{ $term }}">
                                    ซื้อ{{ $plan['name'] }} — ฿{{ number_format($prices[$term]) }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-center text-sm text-gray-400 mt-8">
                จ่ายได้ทั้ง PromptPay โอนผ่านธนาคาร{{ $tpixPay ? ' เหรียญ TPIX' : '' }} หรือ Wallet ของเว็บ (จ่ายด้วย Wallet ลดเพิ่ม {{ $walletDiscount }}%) ·
                <a href="{{ route('tping.pricing') }}" class="xp-link font-semibold">ดูรายละเอียดแพ็กเกจ →</a>
            </p>
            <p class="text-center text-xs text-gray-500 mt-3">ซื้อแล้วได้ License key ทางอีเมลและในหน้าสรุปคำสั่งซื้อ · ใส่คีย์ในหน้าแรกของแอป หรือสแกน QR · รายเดือนและรายปีใช้ได้ 1 เครื่อง ตลอดชีพสูงสุด 3 เครื่อง · ต้องการย้ายเครื่องติดต่อแอดมิน</p>

            <x-xp.compare class="mt-14" :groups="$compare" free-label="ทดลอง" pro-label="LICENSE" note="หมดเวลาทดลองหรือคีย์หมดอายุ แอปจะพาไปหน้าใส่คีย์ Workflow และชุดข้อมูลที่บันทึกไว้ยังอยู่ในเครื่อง ใส่คีย์แล้วใช้ต่อได้ทันที" />
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Android 8.0 ขึ้นไป', 'Android 8.0+ (API 26)'],
            ['สิทธิ์ที่ต้องเปิด', 'การช่วยเหลือพิเศษ (Accessibility) และแสดงทับแอปอื่น (Overlay) · แจ้งเตือน (แนะนำ)', 'Accessibility + overlay'],
            ['ติดตั้งจากไฟล์ .apk', 'ดาวน์โหลดจาก xman4289.com แล้วอนุญาตให้ติดตั้งจากเบราว์เซอร์ที่ใช้โหลด', 'Sideloaded APK'],
            ['อินเทอร์เน็ต', 'ต่อเน็ตตอนเปิดครั้งแรกเพื่อเริ่มทดลอง ตอนใส่คีย์ และตอนใช้ Cloud Sync', 'Online to start the trial and activate'],
        ]"
        specs-note="บางยี่ห้อปิดแอปเบื้องหลังเพื่อประหยัดแบต ถ้า Tping หยุดกลางทาง ให้ตั้งแบตเตอรี่ของ Tping เป็น ไม่จำกัด (หน้าแรกของแอปมีวิธีของแต่ละยี่ห้อ)"
        :steps="[
            ['ดาวน์โหลดและติดตั้ง Tping', 'ไฟล์ .apk จาก xman4289.com โดยตรง ใช้ไฟล์เดียวกันทั้งช่วงทดลองและเมื่อซื้อแล้ว', 'Download and install the APK'],
            ['เปิดสิทธิ์ Accessibility + Overlay', 'กดปุ่ม เปิด ในหน้าแรกของแอปเพื่อไปหน้าตั้งค่าโดยตรง ทำตามคู่มือติดตั้งได้ทีละขั้น', 'Turn on accessibility and overlay'],
            ['ใช้ฟรี 24 ชม. แล้วใส่คีย์', 'ถูกใจแล้วซื้อ License ใส่คีย์ในหน้าแรกของแอป หรือสแกน QR ไม่ต้องลงแอปใหม่', 'Try it for a day, then enter a key'],
        ]" />

    <x-xp.faq :items="[
        ['Tping คืออะไร?', 'แอป Android ที่ช่วยพิมพ์และกดแทนคุณ บันทึกขั้นตอนในแอปไหนก็ได้ครั้งเดียว แล้วเล่นซ้ำพร้อมกรอกข้อมูลจากชุดข้อมูลที่ตั้งไว้ เหมาะกับผู้ที่ใช้นิ้วไม่สะดวก และงานกรอกข้อมูลซ้ำ ๆ'],
        ['ต้อง Root เครื่องไหม?', 'ไม่ต้อง Tping ใช้บริการการช่วยเหลือพิเศษ (Accessibility) และสิทธิ์แสดงทับแอปอื่น ซึ่งเป็นความสามารถมาตรฐานของ Android แค่เปิดในหน้าตั้งค่า'],
        ['ทดลองฟรีอย่างไร?', 'ติดตั้งแล้วเปิดแอปตอนต่อเน็ต จะได้ทดลอง 24 ชั่วโมงทันที ไม่ต้องสมัครหรือผูกบัตร ใช้ได้ทุกฟีเจอร์ในเครื่อง ส่วน Cloud Sync, Gen ชุดข้อมูล และส่งออก/นำเข้า เปิดเมื่อมี License'],
        ['หมดเวลาทดลองแล้วข้อมูลหายไหม?', 'ไม่หาย แอปจะพาไปหน้าใส่คีย์ Workflow และชุดข้อมูลยังอยู่ในเครื่อง ใส่ License แล้วใช้ต่อได้ทันที'],
        ['ราคาเท่าไร ใช้ได้กี่เครื่อง?', collect($plans)->filter(fn ($p, $t) => isset($prices[$t]))->map(fn ($p, $t) => $p['name'] . ' ฿' . number_format($prices[$t]) . ' (' . $p['devices'] . ')')->implode(' · ') . ' — ต้องการย้ายคีย์ไปเครื่องใหม่ ติดต่อแอดมิน'],
        ['ชำระเงินอย่างไร?', 'PromptPay (สแกน QR) โอนผ่านธนาคาร' . ($tpixPay ? ' เหรียญ TPIX' : '') . ' หรือ Wallet ของเว็บ จ่ายด้วย Wallet ลดเพิ่ม ' . $walletDiscount . '% และได้คีย์ทันที'],
        ['ใช้กับเกมได้ไหม?', 'ได้ เลือกโหมด เกม ตอนบันทึกแล้ววางจุดกดด้วยกากบาท มีกดค้าง กรอก กดรัว และรอให้ด้วย ตอนเล่นต้องใช้ความละเอียดจอเท่าเดิมและห้ามหมุนจอ'],
        ['ไม่มีเน็ตใช้ได้ไหม?', 'คีย์ที่ซื้อแล้วใช้ต่อได้แม้ออฟไลน์จนถึงวันหมดอายุของคีย์ แต่ต้องต่อเน็ตตอนเปิดครั้งแรก ตอนใส่คีย์ และตอนใช้ Cloud Sync'],
        ['ข้อมูลของฉันไปอยู่ที่ไหน?', 'Workflow และชุดข้อมูลเก็บในมือถือของคุณ เมื่อมี License และเชื่อม Cloud Sync จะซิงค์ขึ้น Xman Studio Cloud ภายใต้บัญชีของคุณ และดูได้ในหน้า บัญชีของฉัน บนเว็บ'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="ให้ Tping " glow="พิมพ์แทนคุณ"
              :sub="'ทดลองฟรี 24 ชั่วโมง ไม่ต้องสมัคร ไม่ต้อง Root' . ($monthly ? ' · License เริ่ม ฿' . $monthly . ' ต่อเดือน' : '')"
              en="Let Tping do the typing.">
        <x-xp.download :href="route('tping.download')" :coming-soon="$csDownload" th="ดาวน์โหลดแอป" en="Download the app" class="!px-8" />
        <a href="#pricing" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ดูแพ็กเกจ</a>
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Android 8.0 ขึ้นไป · ไม่ต้อง Root · ทดลองฟรี 24 ชม. · ไฟล์ .apk จาก xman4289.com</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
