@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย (กฎเจ้าของ 2026-10-12) --}}
@php $csDownload = \App\Models\Product::slugIsComingSoon('reverse-x'); @endphp

@section('title', 'Reverse X — สตูดิโอแกะโปรแกรมด้วย AI บน Windows · ฟรีทุกหน้าวิเคราะห์ · Pro เริ่ม ฿' . number_format(\App\Support\LicensePlans::price('reverse-x', 'monthly')) . '/เดือน | XMAN Studio')
@section('meta_description', 'Reverse X ลากไฟล์ EXE/DLL, .NET, แอป Electron หรือ APK เข้ามา แล้วเห็นว่าโปรแกรมทำงานอย่างไรพร้อมหลักฐาน วิเคราะห์ในเครื่องของคุณเอง ฟรีทุกหน้าวิเคราะห์ ทดลองผู้ช่วย AI (Pro) ฟรี 14 วัน · An AI-assisted reverse engineering studio for Windows.')

@push('styles')
<style>
    /* Reverse X product page — scoped under .rvx (the app's own night-sky look: deep blue, cyan glass, gold accents) */
    .rvx { --cyan: #38d6ff; --sky: #5aa8ff; --gold: #ffcf5a; --mint: #34e3a1; --ink: #030817; background: var(--ink); color: #d6dbe6; }
    .rvx h1, .rvx h2, .rvx h3 { text-wrap: balance; }
    .rvx-sky { background-image: radial-gradient(ellipse 70% 60% at 70% 0%, rgba(56, 214, 255, .16), transparent 70%), radial-gradient(ellipse 60% 50% at 0% 30%, rgba(90, 168, 255, .12), transparent 70%); }
    .rvx-glow { position: absolute; border-radius: 9999px; filter: blur(100px); pointer-events: none; }
    .rvx-text-glow { background: linear-gradient(95deg, var(--cyan) 0%, var(--sky) 45%, var(--gold) 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .rvx-eyebrow { font-size: .72rem; font-weight: 800; letter-spacing: .28em; text-transform: uppercase; color: rgba(255, 207, 90, .9); }
    .rvx-chip { display: inline-flex; align-items: center; gap: .45rem; padding: .42rem .85rem; border-radius: 9999px; font-size: .84rem; color: #e6ebf3;
        background: rgba(255, 255, 255, .04); border: 1px solid rgba(255, 255, 255, .1); }
    .rvx-chip i { width: .45rem; height: .45rem; border-radius: 9999px; background: var(--cyan); box-shadow: 0 0 10px var(--cyan); }
    .rvx-badge { display: inline-flex; align-items: center; font-weight: 800; font-size: .7rem; letter-spacing: .06em; line-height: 1; padding: .34rem .6rem .38rem; border-radius: .45rem; white-space: nowrap; }
    .rvx-free { background: linear-gradient(105deg, var(--cyan), #8be6ff); color: #021624; }
    .rvx-pro { background: linear-gradient(105deg, var(--gold), #ff9f43); color: #1a1003; }
    .rvx-card { background: linear-gradient(180deg, rgba(255, 255, 255, .05), rgba(255, 255, 255, .015)); border: 1px solid rgba(140, 200, 255, .12); border-radius: 1.1rem; }
    .rvx-frame { border-radius: 1.1rem; padding: 1px; background: linear-gradient(140deg, rgba(56, 214, 255, .7), rgba(90, 168, 255, .25) 45%, rgba(255, 207, 90, .6));
        box-shadow: 0 40px 90px -30px rgba(56, 214, 255, .35), 0 20px 50px -20px rgba(0, 0, 0, .8); }
    .rvx-frame > img { display: block; width: 100%; height: auto; border-radius: calc(1.1rem - 1px); }
    .rvx-tick { flex: none; width: 1.3rem; height: 1.3rem; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(140deg, var(--cyan), #8be6ff); color: #021624; font-size: .72rem; font-weight: 900; margin-top: .15rem; }
    .rvx-tick-pro { background: linear-gradient(140deg, var(--gold), #ff9f43); color: #1a1003; }
    .rvx-btn-main { background: linear-gradient(105deg, var(--cyan), #8be6ff); color: #021624; box-shadow: 0 10px 40px -10px rgba(56, 214, 255, .55); }
    .rvx-btn-main:hover { filter: brightness(1.06); transform: translateY(-1px); }
    .rvx-btn-pro { background: linear-gradient(105deg, var(--gold), #ff9f43); color: #1a1003; box-shadow: 0 10px 40px -12px rgba(255, 180, 60, .5); }
    .rvx-btn-pro:hover { filter: brightness(1.05); transform: translateY(-1px); }
    .rvx-btn-ghost { background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .14); color: #fff; }
    .rvx-btn-ghost:hover { background: rgba(255, 255, 255, .09); }
    .rvx-ev { font-size: .68rem; font-weight: 800; padding: .25rem .5rem; border-radius: .4rem; white-space: nowrap; }
    .rvx-ev-seen { background: rgba(52, 227, 161, .1); color: #6ff0be; border: 1px solid rgba(52, 227, 161, .3); }
    .rvx-ev-infer { background: rgba(255, 207, 90, .1); color: #ffd97a; border: 1px solid rgba(255, 207, 90, .32); }
    .rvx-ev-unknown { background: rgba(255, 99, 132, .1); color: #ff9ab0; border: 1px solid rgba(255, 99, 132, .3); }
    .rvx-faq summary { list-style: none; cursor: pointer; }
    .rvx-faq summary::-webkit-details-marker { display: none; }
    .rvx-faq[open] summary .pm { transform: rotate(45deg); }
    .rvx-faq summary .pm { transition: transform .2s ease; }
    .rvx code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .8rem; color: #e6ebf3; background: rgba(255, 255, 255, .06); padding: .1rem .35rem; border-radius: .3rem; }
    /* written here, not as new Tailwind classes: the page must look right with the bundle that is already built */
    .rvx-glow-sky { background: rgba(2, 132, 199, .2); }
    .rvx-glow-sky-soft { background: rgba(14, 165, 233, .1); }
    .rvx-glow-sky-mid { background: rgba(2, 132, 199, .15); }
    .rvx-logo { filter: drop-shadow(0 10px 30px rgba(56, 214, 255, .35)); }
    .rvx-wordmark { letter-spacing: .35em; }
    .rvx-dot { width: .5rem; height: .5rem; border-radius: 9999px; background: var(--mint); box-shadow: 0 0 10px var(--mint); }
    .rvx-owned { color: rgba(165, 243, 252, .9); }
    .rvx-owned a { text-decoration-color: rgba(103, 232, 249, .5); }
    .rvx-featured { position: relative; border-radius: 1.1rem; padding: 1px; background: linear-gradient(135deg, rgba(252, 211, 77, .8), rgba(14, 165, 233, .4), rgba(103, 232, 249, .6));
        box-shadow: 0 30px 90px -30px rgba(56, 214, 255, .45); }
    .rvx-featured-inner { height: 100%; border-radius: 1.05rem; background: #060c1d; }
    .rvx-shot-first { order: 1; }
    .rvx-text-second { order: 2; }
    @media (min-width: 1024px) { .rvx-shot-first { order: 2; } .rvx-text-second { order: 1; } }
    @keyframes rvx-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }
    .rvx-live { animation: rvx-pulse 1.6s ease-in-out infinite; }
    @media (prefers-reduced-motion: reduce) { .rvx-live { animation: none; } }
</style>
@endpush

@section('content')
@php
    // ราคาทุกแผนมาจาก config/licenses.php 'plans' ที่เดียว (ตะกร้าคิดตามนี้) — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $prices = \App\Support\LicensePlans::for('reverse-x');
    $monthly = number_format($prices['monthly'] ?? 0);
    $yearlySaving = \App\Support\LicensePlans::yearlySaving('reverse-x');

    // ปุ่มซื้อต้องมีเสมอ — แอปส่งลูกค้ามาหน้านี้เพื่อซื้อ และ 1 คีย์ใช้ได้ 1 เครื่อง คนที่ซื้อแล้วก็ซื้อให้เครื่องอื่นได้
    $ownsReverseX = auth()->check() && $hasPurchased;

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว — เปิดหน้านี้ต้องไม่ถามแหล่ง release ภายนอก)
    $latest = $product->latestVersion();

    $img = fn (string $name) => asset('images/products/reverse-x/' . $name . '.webp');

    $plans = [
        'monthly' => ['name' => 'รายเดือน', 'en' => 'Monthly', 'unit' => '/ เดือน', 'note' => 'ซื้อทีละ 30 วัน ไม่ตัดเงินอัตโนมัติ', 'days' => '30 วัน'],
        'yearly' => ['name' => 'รายปี', 'en' => 'Yearly', 'unit' => '/ ปี', 'note' => $yearlySaving ? 'ถูกกว่ารายเดือน ' . $yearlySaving . '%' : 'จ่ายครั้งเดียวใช้ทั้งปี', 'days' => '365 วัน'],
        'lifetime' => ['name' => 'ตลอดชีพ', 'en' => 'Lifetime', 'unit' => 'ครั้งเดียว', 'note' => 'จ่ายครั้งเดียว ใช้ได้ตลอด', 'days' => 'ไม่หมดอายุ'],
    ];

    // หน้าวิเคราะห์ในแอป (ฟรีทุกหน้า) — ไอคอนคือไอคอนจริงของแอป
    $pages = [
        ['binary', 'ไบนารี · EXE / DLL', 'PE, ELF, Mach-O — รายชื่อฟังก์ชัน โค้ด C จำลอง แอสเซมบลี และใครเรียกใคร (เจาะลึกด้วย Ghidra / IDA / Hopper ที่คุณมี)', 'Native binaries'],
        ['dotnet', '.NET Assembly', 'type และ method ทั้งหมด คำสั่ง CIL การอ้างอิงของแต่ละ method, P/Invoke และ API ที่เสี่ยง — ไม่ต้องมีเครื่องมืออื่นเพิ่ม', '.NET assemblies'],
        ['javascript', 'แอป JS / Electron', 'แกะ app.asar ดูโมดูล ช่อง IPC สะพาน preload และ main process ว่าข้อมูลวิ่งผ่านไหน', 'JS & Electron apps'],
        ['android', 'Android · APK', 'สิทธิ์ที่ขอ คอมโพเนนต์ SDK ที่ฝังมา ใบรับรอง และ URL ในแอป — บอกด้วยเมื่อโค้ดถูก obfuscate', 'Android APKs'],
        ['web', 'เว็บ · เน็ตเวิร์ก', 'ไฟล์บันทึกเครือข่าย HAR — request, endpoint และ host ที่แอปหรือเว็บคุยด้วย', 'Web & network captures'],
        ['database', 'ฐานข้อมูล · ไฟล์', 'SQLite — ตาราง คอลัมน์ trigger บอกคอลัมน์ที่ดูอ่อนไหว และพื้นที่ที่อาจยังเหลือข้อมูลที่ถูกลบ', 'Databases & data files'],
    ];

    $proItems = [
        ['ถามเป็นภาษาคน', '"ปุ่มนี้ทำงานยังไง" "โปรแกรมส่งข้อมูลอะไรออกไป" — ผู้ช่วย AI เรียกเครื่องมือวิเคราะห์เองทีละขั้น ให้คุณเห็นทุกขั้น', 'Ask in plain words'],
        ['ทุกคำตอบมีหลักฐาน', 'อ้างไฟล์และบรรทัดที่พบจริง แยก เห็นจริง / อนุมาน / ยังไม่รู้ ไม่เดาแล้วพูดเหมือนรู้', 'Every answer cites evidence'],
        ['สร้างแบบเดียวกันให้โปรเจกต์ของคุณ', 'AI เสนอไฟล์เป็น diff ในโฟลเดอร์โปรเจกต์ที่คุณเลือก — ไม่มีอะไรถูกเขียนลงเครื่องจนกว่าคุณกดยืนยัน', 'Build the same in your own project'],
        ['เลือกโมเดลเองได้', 'Claude, GPT, Gemini, DeepSeek, OpenRouter ฯลฯ ด้วย API key ของคุณ หรือรันในเครื่องด้วย Ollama / LM Studio', 'Bring your own model'],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Reverse X',
        'operatingSystem' => 'Windows 10/11 (64-bit)',
        'applicationCategory' => 'DeveloperApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'สตูดิโอแกะโปรแกรมด้วย AI บน Windows — ลากไฟล์ EXE/DLL, .NET, แอป Electron หรือ APK เข้ามา แล้วเห็นว่าโปรแกรมทำงานอย่างไรพร้อมหลักฐาน',
        'url' => route('products.show', 'reverse-x'),
        'image' => $img('binary'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => array_values(array_merge(
            [['@type' => 'Offer', 'name' => 'Free', 'price' => '0', 'priceCurrency' => 'THB']],
            collect($prices)->map(fn ($price, $term) => ['@type' => 'Offer', 'name' => 'Pro (' . $plans[$term]['en'] . ')', 'price' => (string) $price, 'priceCurrency' => 'THB'])->values()->all(),
        )),
    ];
@endphp
<div class="rvx relative overflow-hidden">

    {{-- ============================ HERO ============================ --}}
    <section class="relative isolate rvx-sky">
        <div class="rvx-glow rvx-glow-sky -z-10 w-[34rem] h-[34rem] -top-40 -left-40"></div>
        <div class="rvx-glow -z-10 w-[30rem] h-[30rem] top-32 right-0 bg-amber-400/10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-20 lg:pt-12 lg:pb-24">
            <nav class="mb-10">
                <a href="{{ route('products.index') }}" class="inline-flex items-center text-sm text-cyan-300/90 hover:text-cyan-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <x-bi th="กลับไปรายการผลิตภัณฑ์" en="All products" />
                </a>
            </nav>

            <div class="grid lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-5">
                    <div class="flex items-center gap-4 mb-6">
                        <img src="{{ $img('logo') }}" alt="โลโก้ Reverse X" width="384" height="384" class="rvx-logo w-20 h-20 sm:w-24 sm:h-24">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rvx-chip"><i class="rvx-live"></i>{{ $latest ? 'Reverse X ' . $latest->version : 'Reverse X' }}</span>
                            <span class="rvx-chip">Windows 10/11 · 64-bit</span>
                        </div>
                    </div>

                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black tracking-tight text-white leading-[1.02] mb-3">
                        Reverse <span class="rvx-text-glow">X</span>
                    </h1>
                    <p class="rvx-wordmark text-sm font-extrabold text-amber-200/80 mb-5">INSIGHT STUDIO</p>
                    <p class="text-2xl sm:text-3xl font-bold text-white mb-2">เห็นฟีเจอร์ที่ชอบ? แกะดูได้ถึงระดับไบนารี</p>
                    <p class="text-base sm:text-lg text-gray-400 mb-6">An AI-assisted reverse engineering studio for Windows.</p>

                    <p class="text-lg text-gray-300 leading-relaxed mb-2">
                        ลากโปรแกรม แอป หรือไฟล์เข้ามา — EXE/DLL, .NET, แอป Electron, APK — แล้วเห็นว่ามันทำงานอย่างไร
                        ทุกข้อสรุปบอกหลักฐานและความมั่นใจ วิเคราะห์ในเครื่องของคุณเอง ไม่อัปโหลดไฟล์ไปที่ไหน
                    </p>
                    <p class="text-sm text-gray-500 leading-relaxed mb-8">
                        Drop in a program and see how it works — with evidence for every finding, analysed on your own PC.
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        @if($csDownload)
                            <span class="rvx-btn-main inline-flex items-center gap-2 px-7 py-4 rounded-xl font-extrabold text-lg transition" style="cursor: not-allowed; opacity: .8;" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดให้ดาวน์โหลด</span>
                        @else
                        <a href="{{ route('reverse-x.download') }}"
                           class="rvx-btn-main inline-flex items-center gap-2 px-7 py-4 rounded-xl font-extrabold text-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <x-bi th="ดาวน์โหลดฟรี" en="Free download" />
                        </a>
                        @endif
                        <a href="#pricing" class="rvx-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">
                            {{ $ownsReverseX ? 'ซื้อ License เพิ่ม' : 'ซื้อ Pro' }} — เริ่ม ฿{{ $monthly }}/เดือน
                        </a>
                    </div>

                    @if($latest)
                        <p class="mt-4 text-sm text-gray-400">
                            เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · ไฟล์ .zip จาก xman4289.com โดยตรง
                        </p>
                    @endif

                    @if($ownsReverseX)
                        <p class="rvx-owned mt-3 text-sm">
                            คุณมี License แล้ว · 1 คีย์ใช้ได้ 1 เครื่อง — ซื้อเพิ่มได้สำหรับเครื่องอื่น ·
                            <a href="{{ route('customer.licenses') }}" class="font-semibold underline hover:text-white">ดูคีย์ License ของฉัน</a>
                        </p>
                    @endif

                    <ul class="mt-8 grid sm:grid-cols-3 gap-3 text-sm">
                        <li class="flex items-center gap-2 text-gray-300"><span class="rvx-badge rvx-free">ฟรี</span> ทุกหน้าวิเคราะห์</li>
                        <li class="flex items-center gap-2 text-gray-300"><span class="rvx-badge rvx-pro">PRO</span> ทดลองฟรี 14 วัน</li>
                        <li class="flex items-center gap-2 text-gray-300"><span class="rvx-dot"></span> วิเคราะห์ในเครื่อง</li>
                    </ul>
                </div>

                {{-- หน้าจอจริงของโปรแกรม (ธีมกลางคืน) --}}
                <div class="lg:col-span-7">
                    <figure>
                        <div class="rvx-frame">
                            <img src="{{ $img('binary') }}" alt="หน้าจอ Reverse X — วิเคราะห์ .NET assembly: รายการ method คำสั่ง CIL การอ้างอิง และสรุปจากหลักฐาน"
                                 width="1280" height="800" fetchpriority="high">
                        </div>
                        <figcaption class="mt-4 text-center text-xs text-gray-500">หน้าจอจริงของโปรแกรม — แกะไฟล์ .NET: method, คำสั่ง CIL, ใครเรียกใคร และสรุปจากหลักฐาน</figcaption>
                    </figure>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================== WHAT IT READS ========================== --}}
    <section class="relative py-20" id="features">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <p class="rvx-eyebrow mb-3">Free · every analysis page</p>
                <h2 class="text-3xl md:text-5xl font-black text-white mb-4">ฟรีทุกหน้าวิเคราะห์ ไม่จำกัดเวลา</h2>
                <p class="text-gray-300">โปรแกรมดูชนิดไฟล์ให้เอง แล้วพาไปหน้าที่ใช่ — ทุกหน้าใช้ได้เต็มที่ในรุ่นฟรี</p>
                <p class="text-sm text-gray-500 mt-1">Every analysis page is free, for as long as you like.</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($pages as [$icon, $title, $body, $en])
                    <div class="rvx-card p-6">
                        <img src="{{ $img('icon-' . $icon) }}" alt="" width="128" height="128" loading="lazy" class="w-14 h-14 mb-4">
                        <h3 class="text-lg font-bold text-white mb-1.5">{{ $title }}</h3>
                        <p class="text-sm text-gray-300 leading-relaxed">{{ $body }}</p>
                        <p class="text-xs text-gray-500 mt-2">{{ $en }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-12 grid lg:grid-cols-12 gap-8 items-center">
                <figure class="lg:col-span-7">
                    <div class="rvx-frame">
                        <img src="{{ $img('electron') }}" alt="หน้าจอ Reverse X — แกะแอป Electron: รายการโมดูล ช่อง IPC เส้นทางจากหน้าจอถึง main process และหลักฐาน"
                             width="1280" height="800" loading="lazy">
                    </div>
                    <figcaption class="mt-3 text-center text-xs text-gray-500">แกะแอป Electron — โมดูล ช่อง IPC และเส้นทางที่ข้อมูลวิ่งผ่าน</figcaption>
                </figure>
                <div class="lg:col-span-5">
                    <h3 class="text-2xl md:text-3xl font-black text-white mb-4">ทุกข้อสรุปบอกว่ารู้มาจากไหน</h3>
                    <p class="text-gray-300 leading-relaxed mb-6">
                        Reverse X ไม่พูดเกินหลักฐาน ทุกบรรทัดในสรุปติดป้ายระดับความมั่นใจ และเก็บลงสมุดหลักฐานให้ย้อนดูได้ทุกครั้ง
                    </p>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start gap-3"><span class="rvx-ev rvx-ev-seen">เห็นจริง</span><span class="text-gray-300">พบในไฟล์จริง พร้อมตำแหน่งที่พบ</span></li>
                        <li class="flex items-start gap-3"><span class="rvx-ev rvx-ev-infer">อนุมาน</span><span class="text-gray-300">สรุปจากหลายจุดที่เห็น — บอกว่าอนุมานจากอะไร</span></li>
                        <li class="flex items-start gap-3"><span class="rvx-ev rvx-ev-unknown">ยังไม่รู้</span><span class="text-gray-300">สิ่งที่การวิเคราะห์แบบนี้ยังยืนยันไม่ได้ — บอกตรง ๆ</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================ PRO: AI ============================ --}}
    <section class="relative py-20">
        <div class="rvx-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24 bg-amber-400/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <p class="rvx-eyebrow mb-3">Pro · AI assistant</p>
                <h2 class="text-3xl md:text-5xl font-black text-white mb-4">Pro ปลดผู้ช่วย AI <span class="rvx-badge rvx-pro align-middle">PRO</span></h2>
                <p class="text-gray-300">ถามว่าฟีเจอร์ที่ชอบทำงานอย่างไร แล้วให้ AI สืบให้พร้อมหลักฐาน — จนถึงเสนอโค้ดแบบเดียวกันลงโปรเจกต์ของคุณ</p>
                <p class="text-sm text-gray-500 mt-1">Ask how a feature works; the assistant investigates with evidence and can propose the same for your own project.</p>
            </div>

            <div class="grid lg:grid-cols-12 gap-8 items-center">
                <div class="rvx-text-second lg:col-span-5">
                    <ul class="space-y-5">
                        @foreach($proItems as [$name, $body, $en])
                            <li class="flex gap-3">
                                <span class="rvx-tick rvx-tick-pro">✓</span>
                                <span>
                                    <span class="block font-bold text-gray-100">{{ $name }} <span class="text-xs font-normal text-gray-500">· {{ $en }}</span></span>
                                    <span class="block text-sm text-gray-400">{{ $body }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-6 text-xs text-gray-500">ค่าใช้งานโมเดล AI จ่ายกับผู้ให้บริการที่คุณเลือกเองผ่าน API key ของคุณ (ไม่รวมในราคา Pro) · ใช้โมเดลในเครื่องได้โดยไม่มีค่าใช้จ่ายเพิ่ม</p>
                </div>
                <figure class="rvx-shot-first lg:col-span-7">
                    <div class="rvx-frame">
                        <img src="{{ $img('ai') }}" alt="หน้าจอ Reverse X — ผู้ช่วย AI สืบเส้นทางการทำงานพร้อมหลักฐาน แล้วเสนอไฟล์ใหม่ให้โปรเจกต์เป็น diff"
                             width="1280" height="800" loading="lazy">
                    </div>
                    <figcaption class="mt-3 text-center text-xs text-gray-500">ผู้ช่วย AI — เส้นทางที่พบ หลักฐาน และไฟล์ที่เสนอเป็น diff ก่อนเขียนจริง</figcaption>
                </figure>
            </div>
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="rvx-glow rvx-glow-sky-soft w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <p class="rvx-eyebrow mb-3">Pricing</p>
                <h2 class="text-3xl md:text-5xl font-black text-white mb-4">เริ่มฟรี อยากได้ผู้ช่วย AI ค่อยอัป Pro</h2>
                <p class="text-gray-300">1 คีย์ใช้ได้ 1 เครื่อง · ทดลอง Pro ฟรี 14 วันก่อนตัดสินใจ (ครั้งเดียวต่อเครื่อง)</p>
                <p class="text-sm text-gray-500 mt-1">One key per PC. Try Pro free for 14 days first.</p>
            </div>

            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-5 max-w-6xl mx-auto">
                <div class="rvx-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">Free</h3>
                        <span class="rvx-badge rvx-free">ฟรี</span>
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="ทุกหน้าวิเคราะห์ ไม่จำกัดเวลา" en="Every analysis page, no time limit" /></p>
                    <div class="mb-6"><span class="text-5xl font-black text-white">฿0</span></div>
                    <ul class="space-y-2.5 text-gray-200 text-sm mb-8 flex-1">
                        @foreach(['ไบนารี EXE/DLL · .NET', 'แอป JS/Electron · APK', 'เว็บ·เน็ตเวิร์ก · ฐานข้อมูล', 'สมุดหลักฐาน', 'อัปเดตอัตโนมัติ'] as $item)
                            <li class="flex gap-3"><span class="rvx-tick">✓</span><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                    {{-- ไฟล์เดียวกับ Pro — Pro ปลดล็อกในแอปด้วย license key --}}
                    @if($csDownload)
                        <span class="rvx-btn-main block w-full py-3.5 text-center rounded-xl font-extrabold transition" style="cursor: not-allowed; opacity: .8;" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดให้ดาวน์โหลด</span>
                    @else
                    <a href="{{ route('reverse-x.download') }}" class="rvx-btn-main block w-full py-3.5 text-center rounded-xl font-extrabold transition">
                        <x-bi th="ดาวน์โหลดฟรี" en="Free download" />
                    </a>
                    @endif
                </div>

                @foreach($plans as $term => $plan)
                    @continue(! isset($prices[$term]))
                    @php($featured = $term === 'yearly')
                    <div class="{{ $featured ? 'rvx-featured' : '' }}">
                        <div class="{{ $featured ? 'rvx-featured-inner' : 'rvx-card h-full' }} p-7 flex flex-col">
                            @if($featured)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2"><span class="rvx-badge rvx-pro shadow-lg">คุ้มที่สุด · BEST VALUE</span></div>
                            @endif
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-2xl font-black text-white">Pro {{ $plan['name'] }}</h3>
                                <span class="rvx-badge rvx-pro">PRO</span>
                            </div>
                            <p class="text-amber-100/80 text-sm mb-6">{{ $plan['note'] }}</p>
                            <div class="mb-6 flex items-baseline gap-2">
                                <span class="text-5xl font-black text-white">฿{{ number_format($prices[$term]) }}</span>
                                <span class="text-gray-300">{{ $plan['unit'] }}</span>
                            </div>
                            <x-tpix-price variant="detail" :thb="$prices[$term]" class="-mt-4 mb-6" />
                            <ul class="space-y-2.5 text-gray-100 text-sm mb-8 flex-1">
                                <li class="flex gap-3"><span class="rvx-tick rvx-tick-pro">✓</span><span>ทุกอย่างในรุ่นฟรี</span></li>
                                <li class="flex gap-3"><span class="rvx-tick rvx-tick-pro">✓</span><span>ผู้ช่วย AI สืบฟีเจอร์พร้อมหลักฐาน</span></li>
                                <li class="flex gap-3"><span class="rvx-tick rvx-tick-pro">✓</span><span>เสนอโค้ดลงโปรเจกต์ของคุณ (ดู diff ก่อนเขียน)</span></li>
                                <li class="flex gap-3"><span class="rvx-tick rvx-tick-pro">✓</span><span>อายุ License: {{ $plan['days'] }}</span></li>
                            </ul>
                            {{-- ราคามาจาก config/licenses.php ไม่ใช่จากฟอร์ม — ตะกร้าคิดตามแผนที่ส่งไป --}}
                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="license_type" value="{{ $term }}">
                                <input type="hidden" name="buy_now" value="1">
                                <button type="submit" class="{{ $featured ? 'rvx-btn-pro' : 'rvx-btn-ghost' }} block w-full py-3.5 text-center rounded-xl font-extrabold transition cursor-pointer">
                                    {{ $ownsReverseX ? 'ซื้อ License เพิ่ม' : 'ซื้อ Pro' }} {{ $plan['name'] }} — ฿{{ number_format($prices[$term]) }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($ownsReverseX)
                <p class="text-center mt-6">
                    <a href="{{ route('customer.licenses') }}" class="text-cyan-300 hover:text-cyan-200 text-sm font-semibold">มี License แล้ว? ดูคีย์ของคุณ →</a>
                </p>
            @endif
            <p class="text-center text-xs text-gray-500 mt-6">ซื้อแล้วได้ License key ทางอีเมลและในหน้า บัญชีของฉัน · ใส่คีย์ในหน้า ตั้งค่า ของโปรแกรมได้เลย ไม่ต้องลงใหม่ · ย้ายเครื่องได้ด้วยการยกเลิกบนเครื่องเดิม</p>
        </div>
    </section>

    {{-- ===================== REQUIREMENTS + INSTALL ===================== --}}
    <section class="relative py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-8">
                <div>
                    <p class="rvx-eyebrow mb-3">Requirements</p>
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-8">ใช้กับเครื่องไหนได้บ้าง</h2>
                    <dl class="grid sm:grid-cols-2 gap-4">
                        @foreach([
                            ['ระบบปฏิบัติการ', 'Windows 10/11 แบบ 64-bit', 'Windows, 64-bit'],
                            ['ไม่ต้องติดตั้ง .NET', 'ทุกอย่างอยู่ใน zip เดียว ไม่ต้องใช้สิทธิ์ Administrator', 'Self-contained, no admin rights'],
                            ['Node.js 22.19+ / 24.11+ / 26+', 'ตัววิเคราะห์ใช้ Node.js — ถ้ายังไม่มี โปรแกรมบอกวิธีติดตั้งให้', 'Node.js for the analysis engine'],
                            ['อินเทอร์เน็ต', 'ต่อเน็ตตอนเปิดครั้งแรกเพื่อลงทะเบียนเครื่องและเริ่มทดลอง และตอนใส่คีย์', 'Online to register, trial and activate'],
                        ] as [$k, $v, $en])
                            <div class="rvx-card p-5">
                                <dt class="text-white font-bold mb-1">{{ $k }}</dt>
                                <dd class="text-sm text-gray-300">{{ $v }}</dd>
                                <dd class="text-xs text-gray-500 mt-1">{{ $en }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <p class="mt-4 text-xs text-gray-500">ทางเลือก: ไบนารีเนทีฟแบบเจาะลึก (โค้ด C จำลอง แอสเซมบลี) ใช้ Ghidra 12.1.x หรือ IDA / Hopper ที่มีอยู่แล้ว · ถอดโค้ด Java ของ APK ใช้ JADX — .NET และแอป JS/Electron ไม่ต้องมีเพิ่ม</p>
                </div>
                <div>
                    <p class="rvx-eyebrow mb-3">Get started</p>
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-8">เริ่มใช้ใน 3 ขั้น</h2>
                    <ol class="space-y-4">
                        @foreach([
                            ['ดาวน์โหลดไฟล์ .zip', 'ไฟล์มาจาก xman4289.com โดยตรง ใช้ได้ทั้งรุ่นฟรีและ Pro', 'Download the .zip'],
                            ['แตกไฟล์ทั้งโฟลเดอร์แล้วเปิด ReverseX.exe', 'แตกไว้ในที่ที่เขียนได้ เช่น %LOCALAPPDATA%\Programs\ReverseX — โปรแกรมอัปเดตตัวเองในโฟลเดอร์นั้น', 'Unzip the folder and run ReverseX.exe'],
                            ['เปิดครั้งแรกตอนต่อเน็ต แล้วทดลอง Pro 14 วัน', 'ถูกใจแล้วซื้อคีย์ใส่ในโปรแกรมได้เลย หมดทดลองแล้วหน้าวิเคราะห์ทุกหน้ายังใช้ต่อได้ฟรี', 'Open it online and try Pro for 14 days'],
                        ] as $n => [$title, $body, $en])
                            <li class="rvx-card p-5 flex gap-4">
                                <span class="flex-none w-10 h-10 rounded-xl rvx-btn-main flex items-center justify-center font-black text-lg">{{ $n + 1 }}</span>
                                <span>
                                    <span class="block text-white font-bold">{{ $title }}</span>
                                    <span class="block text-sm text-gray-300 mt-0.5">{{ $body }}</span>
                                    <span class="block text-xs text-gray-500 mt-1">{{ $en }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>

            <figure class="mt-14 max-w-5xl mx-auto">
                <div class="rvx-frame">
                    <img src="{{ $img('home') }}" alt="หน้าจอ Reverse X — ภาพรวม: ลากไฟล์เข้ามา โปรเจกต์ล่าสุด และสถานะเครื่องมือในเครื่องนี้"
                         width="1280" height="960" loading="lazy">
                </div>
                <figcaption class="mt-3 text-center text-xs text-gray-500">หน้าแรกของโปรแกรม — ลากไฟล์เข้ามา และดูว่าเครื่องมือไหนพร้อมใช้ในเครื่องนี้</figcaption>
            </figure>
        </div>
    </section>

    {{-- ============================== FAQ ============================== --}}
    <section class="relative py-20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <p class="rvx-eyebrow mb-3">FAQ</p>
                <h2 class="text-3xl md:text-4xl font-black text-white">คำถามที่พบบ่อย</h2>
            </div>
            <div class="space-y-3">
                @foreach([
                    ['ฟรีจริงไหม?', 'ฟรีจริง หน้าวิเคราะห์ทุกหน้า (ไบนารี .NET แอป JS/Electron APK เว็บ ฐานข้อมูล และสมุดหลักฐาน) ใช้ได้ไม่จำกัดเวลา ดาวน์โหลดได้เลยโดยไม่ต้องล็อกอิน'],
                    ['ทดลอง Pro ทำอย่างไร?', 'เปิดโปรแกรมครั้งแรกตอนต่อเน็ต จะได้ทดลองผู้ช่วย AI 14 วัน ครั้งเดียวต่อเครื่อง (ลง Windows ใหม่ก็ไม่ได้สิทธิ์ซ้ำ) หมดเวลาแล้วหน้าวิเคราะห์ทุกหน้ายังใช้ต่อได้'],
                    ['Pro ราคาเท่าไร ใช้ได้กี่เครื่อง?', 'รายเดือน ฿' . number_format($prices['monthly'] ?? 0) . ' · รายปี ฿' . number_format($prices['yearly'] ?? 0) . ' · ตลอดชีพ ฿' . number_format($prices['lifetime'] ?? 0) . ' — 1 คีย์ใช้ได้ 1 เครื่อง มีหลายเครื่องซื้อคีย์เพิ่มได้ และย้ายคีย์ไปเครื่องใหม่ได้'],
                    ['ผู้ช่วย AI ต้องจ่ายค่า AI เพิ่มไหม?', 'ผู้ช่วย AI ใช้ API key ของคุณเอง (Claude, GPT, Gemini, DeepSeek, OpenRouter ฯลฯ) ค่าใช้งานโมเดลจ่ายกับผู้ให้บริการนั้นโดยตรง หรือใช้โมเดลในเครื่องด้วย Ollama / LM Studio ได้ฟรี · key เก็บเข้ารหัสในเครื่องนี้เท่านั้น'],
                    ['ไฟล์ที่วิเคราะห์ถูกส่งไปไหนไหม?', 'ไม่ การวิเคราะห์ทำในเครื่องของคุณทั้งหมด ไม่อัปโหลดไฟล์ · ตอนใช้ผู้ช่วย AI ข้อความและหลักฐานที่เกี่ยวข้องจะส่งไปยังผู้ให้บริการ AI ที่คุณเลือกเท่านั้น (ใช้โมเดลในเครื่องก็ไม่ออกจากเครื่องเลย) · โปรแกรมติดต่อ xman4289.com เพื่อตรวจ License และเช็กอัปเดตเท่านั้น'],
                    ['ใช้กับโปรแกรมอะไรก็ได้ไหม?', 'ใช้กับซอฟต์แวร์ที่คุณมีสิทธิ์วิเคราะห์เท่านั้น เช่น โปรแกรมของคุณเอง ซอฟต์แวร์โอเพนซอร์ส หรืองานที่ได้รับอนุญาต — โปรดตรวจเงื่อนไขการใช้งานของซอฟต์แวร์นั้นก่อนเสมอ'],
                    ['อัปเดตอย่างไร ปลอดภัยไหม?', 'โปรแกรมเช็กและอัปเดตตัวเองจาก xman4289.com ทั้งรุ่นฟรีและ Pro ทุกแพ็กเกจมีลายเซ็นดิจิทัลและถูกตรวจก่อนติดตั้งทุกครั้ง ไฟล์ที่ถูกแก้ไขจะไม่ถูกติดตั้ง และย้อนกลับเวอร์ชันเดิมได้ถ้าติดตั้งไม่สำเร็จ'],
                ] as [$q, $a])
                    <details class="rvx-faq rvx-card px-5 py-4">
                        <summary class="flex items-center justify-between gap-4 text-white font-bold">
                            <span>{{ $q }}</span>
                            <span class="pm flex-none w-7 h-7 rounded-full border border-white/15 flex items-center justify-center text-cyan-300 text-lg leading-none">+</span>
                        </summary>
                        <p class="mt-3 text-gray-300 leading-relaxed">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =========================== FINAL CTA =========================== --}}
    <section class="relative py-24 isolate">
        <div class="rvx-glow rvx-glow-sky-mid -z-10 w-[40rem] h-[22rem] left-1/2 -translate-x-1/2 top-10"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-6xl font-black text-white mb-5">เห็นแล้วเข้าใจ <span class="rvx-text-glow">ว่ามันทำงานยังไง</span></h2>
            <p class="text-lg text-gray-300 mb-1">ดาวน์โหลดรุ่นฟรีได้ทันที หรือปลดผู้ช่วย AI ด้วย Pro เริ่มเพียง ฿{{ $monthly }} ต่อเดือน</p>
            <p class="text-sm text-gray-500 mb-9">See how it works — with evidence.</p>
            <div class="flex flex-wrap justify-center gap-3">
                @if($csDownload)
                    <span class="rvx-btn-main inline-flex items-center gap-2 px-8 py-4 rounded-xl font-extrabold text-lg transition" style="cursor: not-allowed; opacity: .8;" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดให้ดาวน์โหลด</span>
                @else
                <a href="{{ route('reverse-x.download') }}" class="rvx-btn-main inline-flex items-center gap-2 px-8 py-4 rounded-xl font-extrabold text-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <x-bi th="ดาวน์โหลดฟรี" en="Free download" />
                </a>
                @endif
                <a href="#pricing" class="rvx-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ดูแผน Pro</a>
                @if($ownsReverseX)
                    <a href="{{ route('customer.licenses') }}" class="rvx-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ดูคีย์ License ของฉัน</a>
                @endif
            </div>
            <p class="text-gray-500 text-sm mt-7">Windows 10/11 64-bit · ไม่ต้องลง .NET · ทดลอง Pro ฟรี 14 วัน · 1 คีย์ = 1 เครื่อง</p>
            <p class="text-gray-600 text-xs mt-2">ใช้กับซอฟต์แวร์ที่คุณมีสิทธิ์วิเคราะห์เท่านั้น</p>
            <a href="{{ route('products.index') }}" class="inline-block mt-4 text-sm text-cyan-300/90 hover:text-cyan-200"><x-bi th="ดูผลิตภัณฑ์อื่นของ XMAN Studio" en="More from XMAN Studio" /> →</a>
        </div>
    </section>

</div>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
