@extends($publicLayout ?? 'layouts.app')

@section('title', 'Anti X — กันการเจาะเซิร์ฟเวอร์ Windows แบบเรียลไทม์ · ฟรีพื้นฐาน · Pro เริ่ม ฿' . number_format(\App\Support\LicensePlans::price('anti-x', 'monthly')) . '/เดือน | XMAN Studio')
@section('meta_description', 'Anti X บล็อกการเดารหัส RDP/SSH/ฐานข้อมูล สแกนพอร์ต ยิงหาช่องโหว่ และ IP อันตรายแบบเรียลไทม์ พร้อมจับเครื่องที่ถูกเจาะ ไม่ปิดพอร์ต RDP ไม่ล็อกตัวเอง ฟรีพื้นฐาน ทดลอง Pro ฟรี 14 วัน · Real-time intrusion blocking for Windows servers.')

@push('styles')
<style>
    /* Anti X product page — scoped under .atx (dark "security console" look, no images: every visual is HTML) */
    .atx { --red: #ff4d5e; --amber: #ffb547; --mint: #34e3a1; --cyan: #38d6ff; --ink: #05070d; background: var(--ink); color: #d6dbe6; }
    .atx h1, .atx h2, .atx h3 { text-wrap: balance; }
    .atx-grid { background-image: linear-gradient(rgba(148, 163, 184, .06) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, .06) 1px, transparent 1px);
        background-size: 48px 48px; -webkit-mask-image: radial-gradient(ellipse 80% 70% at 50% 30%, #000 20%, transparent 75%); mask-image: radial-gradient(ellipse 80% 70% at 50% 30%, #000 20%, transparent 75%); }
    .atx-glow { position: absolute; border-radius: 9999px; filter: blur(100px); pointer-events: none; }
    .atx-text-glow { background: linear-gradient(95deg, var(--red) 0%, var(--amber) 45%, var(--mint) 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .atx-eyebrow { font-size: .72rem; font-weight: 800; letter-spacing: .28em; text-transform: uppercase; color: rgba(56, 214, 255, .85); }
    .atx-chip { display: inline-flex; align-items: center; gap: .45rem; padding: .42rem .85rem; border-radius: 9999px; font-size: .84rem; color: #e6ebf3;
        background: rgba(255, 255, 255, .04); border: 1px solid rgba(255, 255, 255, .1); }
    .atx-chip i { width: .45rem; height: .45rem; border-radius: 9999px; background: var(--mint); box-shadow: 0 0 10px var(--mint); }
    .atx-badge { display: inline-flex; align-items: center; font-weight: 800; font-size: .7rem; letter-spacing: .06em; line-height: 1; padding: .34rem .6rem .38rem; border-radius: .45rem; white-space: nowrap; }
    .atx-free { background: linear-gradient(105deg, var(--mint), #7cf5c4); color: #04140d; }
    .atx-pro { background: linear-gradient(105deg, var(--amber), #ff7a45); color: #1a0d03; }
    .atx-card { background: linear-gradient(180deg, rgba(255, 255, 255, .045), rgba(255, 255, 255, .014)); border: 1px solid rgba(255, 255, 255, .08); border-radius: 1.1rem; }
    .atx-console { border-radius: 1.1rem; padding: 1px; background: linear-gradient(140deg, rgba(255, 77, 94, .65), rgba(56, 214, 255, .25) 45%, rgba(52, 227, 161, .55));
        box-shadow: 0 40px 90px -30px rgba(255, 77, 94, .3), 0 20px 50px -20px rgba(0, 0, 0, .8); }
    .atx-console > div { border-radius: calc(1.1rem - 1px); background: #070b14; overflow: hidden; }
    .atx-row { display: grid; grid-template-columns: 1fr auto; gap: .75rem; align-items: center; padding: .62rem 1rem; border-top: 1px solid rgba(255, 255, 255, .05); font-size: .82rem; }
    .atx-ip { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; color: #f1f4fa; }
    .atx-tag { font-size: .68rem; font-weight: 800; padding: .25rem .5rem; border-radius: .4rem; white-space: nowrap; }
    .atx-tag-block { background: rgba(255, 77, 94, .14); color: #ff8a95; border: 1px solid rgba(255, 77, 94, .35); }
    .atx-tag-warn { background: rgba(255, 181, 71, .12); color: #ffc670; border: 1px solid rgba(255, 181, 71, .32); }
    .atx-tag-ok { background: rgba(52, 227, 161, .1); color: #6ff0be; border: 1px solid rgba(52, 227, 161, .3); }
    .atx-tag-trust { background: rgba(56, 214, 255, .1); color: #7fe3ff; border: 1px solid rgba(56, 214, 255, .3); }
    .atx-tick { flex: none; width: 1.3rem; height: 1.3rem; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(140deg, var(--mint), #7cf5c4); color: #04140d; font-size: .72rem; font-weight: 900; margin-top: .15rem; }
    .atx-tick-pro { background: linear-gradient(140deg, var(--amber), #ff7a45); color: #1a0d03; }
    .atx-btn-main { background: linear-gradient(105deg, var(--mint), #7cf5c4); color: #04140d; box-shadow: 0 10px 40px -10px rgba(52, 227, 161, .5); }
    .atx-btn-main:hover { filter: brightness(1.06); transform: translateY(-1px); }
    .atx-btn-pro { background: linear-gradient(105deg, var(--amber), #ff7a45); color: #1a0d03; box-shadow: 0 10px 40px -12px rgba(255, 140, 60, .5); }
    .atx-btn-pro:hover { filter: brightness(1.05); transform: translateY(-1px); }
    .atx-btn-ghost { background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .14); color: #fff; }
    .atx-btn-ghost:hover { background: rgba(255, 255, 255, .09); }
    .atx-faq summary { list-style: none; cursor: pointer; }
    .atx-faq summary::-webkit-details-marker { display: none; }
    .atx-faq[open] summary .pm { transform: rotate(45deg); }
    .atx-faq summary .pm { transition: transform .2s ease; }
    .atx code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .8rem; color: #e6ebf3; background: rgba(255, 255, 255, .06); padding: .1rem .35rem; border-radius: .3rem; }
    @keyframes atx-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .35; } }
    .atx-live { animation: atx-pulse 1.6s ease-in-out infinite; }
    @media (prefers-reduced-motion: reduce) { .atx-live { animation: none; } }
</style>
@endpush

@section('content')
@php
    // ราคาทุกแผนมาจาก config/licenses.php 'plans' ที่เดียว (ตะกร้าคิดตามนี้) — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $prices = \App\Support\LicensePlans::for('anti-x');
    $monthly = number_format($prices['monthly'] ?? 0);
    $yearlySaving = \App\Support\LicensePlans::yearlySaving('anti-x');

    // ปุ่มซื้อต้องมีเสมอ — แอปส่งลูกค้ามาหน้านี้เพื่อซื้อ และ 1 คีย์ใช้ได้ 1 เครื่อง คนที่ซื้อแล้วก็ซื้อให้เครื่องอื่นได้
    $ownsAntiX = auth()->check() && $hasPurchased;

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว — เปิดหน้านี้ต้องไม่ถาม GitHub)
    $latest = $product->latestVersion();

    $plans = [
        'monthly' => ['name' => 'รายเดือน', 'en' => 'Monthly', 'unit' => '/ เดือน', 'note' => 'ซื้อทีละ 30 วัน ไม่ตัดเงินอัตโนมัติ', 'days' => '30 วัน'],
        'yearly' => ['name' => 'รายปี', 'en' => 'Yearly', 'unit' => '/ ปี', 'note' => $yearlySaving ? 'ถูกกว่ารายเดือน ' . $yearlySaving . '%' : 'จ่ายครั้งเดียวใช้ทั้งปี', 'days' => '365 วัน'],
        'lifetime' => ['name' => 'ตลอดชีพ', 'en' => 'Lifetime', 'unit' => 'ครั้งเดียว', 'note' => 'จ่ายครั้งเดียว ใช้ได้ตลอด', 'days' => 'ไม่หมดอายุ'],
    ];

    $freeDetectors = [
        ['เดารหัส RDP', 'ผิด 5 ครั้งใน 10 นาที → บล็อก IP นั้น', 'RDP brute force'],
        ['เดารหัสฐานข้อมูล', 'MSSQL อ่านจาก event log ให้เอง · MySQL/MariaDB, PostgreSQL, MongoDB เพิ่ม log ได้', 'Database brute force'],
        ['สแกนพอร์ต', 'ไล่เคาะ 10 พอร์ตต่างกันใน 60 วินาที → บล็อก', 'Port scans'],
        ['แตะพอร์ตอันตราย', 'ฐานข้อมูล, SMB/RPC/WinRM, VNC, Telnet, Docker API ฯลฯ → บล็อกทันที', 'Dangerous ports'],
        ['การเชื่อมต่อสด', 'ใครใช้พอร์ตอินเทอร์เน็ตของเราอยู่ตอนนี้ ธงประเทศ แยกสีบล็อก/น่าสงสัย/ไว้ใจ', 'Live connections'],
        ['แบล็คลิสต์ · ไวท์ลิสต์', 'ปลด/บล็อกเองได้ทุก IP บล็อกนาน 1 วัน → 7 วัน → ถาวรตามครั้งที่ทำผิด', 'Block & trust lists'],
    ];

    $proDetectors = [
        ['สเปรย์รหัส', 'IP เดียวลองหลายชื่อผู้ใช้ · เดารหัสแบบค่อย ๆ ยิงทั้งวัน · หลาย IP รุมเดาบัญชีเดียว', 'Spraying & slow attacks'],
        ['สแกนแบบซ่อนตัว', 'แพ็กเก็ต NULL / FIN / XMAS และการไล่สแกนทุก IP ของเรา', 'Stealth & sweep scans'],
        ['ยิงหาช่องโหว่เว็บ', '/.env, /.git/, ${jndi:, ../../, SQL injection ฯลฯ จาก log เว็บ', 'Web exploit probes'],
        ['IP ในรายชื่ออันตราย', 'รายชื่อสาธารณะที่อัปเดตทุก 12 ชั่วโมง → บล็อกตั้งแต่ติดต่อครั้งแรก', 'Threat feeds'],
        ['จับเครื่องที่ถูกเจาะ', 'Defender ถูกปิด, ล้าง event log, บริการ/งานตั้งเวลาแปลกใหม่, ตัวขุดเหรียญ, คำสั่งแรนซัมแวร์, ติดต่อออกผิดปกติ', 'Compromise detection'],
        ['ดูแล VM บนเครื่องเดียวกัน', 'อ่าน log SSH ใน VM สด ๆ และสั่งไฟร์วอลล์ในตัว VM (csf / iptables) ผ่าน SSH', 'VMs over SSH'],
        ['ปุ่ม "จัดการ"', 'ฆ่าโปรเซส กักกันไฟล์ ปิดทางกลับ — ต้องกดยืนยันก่อนทุกครั้ง และกดคืนได้ทุกอย่าง', 'Kill · quarantine · undo'],
    ];

    // ตัวอย่างหน้าจอเชื่อมต่อสด — IP ชุดเอกสาร (RFC 5737) ไม่ใช่ของใครจริง
    $demoRows = [
        ['203.0.113.47', 'RU', 'เดารหัส RDP ผิด 5 ครั้ง', 'บล็อก 1 วัน', 'atx-tag-block'],
        ['198.51.100.12', 'CN', 'สแกนพอร์ต 14 พอร์ต / 60 วิ', 'บล็อก 1 วัน', 'atx-tag-block'],
        ['192.0.2.199', 'NL', 'แตะพอร์ต 3306 (MySQL)', 'บล็อก 7 วัน', 'atx-tag-block'],
        ['203.0.113.8', 'US', 'ลอง 3 ชื่อผู้ใช้ใน 30 นาที', 'น่าสงสัย', 'atx-tag-warn'],
        ['198.51.100.230', 'TH', 'RDP · ผู้ดูแลที่กำลังต่ออยู่', 'ไว้ใจ', 'atx-tag-trust'],
        ['192.0.2.31', 'SG', 'HTTPS 443', 'ปกติ', 'atx-tag-ok'],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Anti X',
        'operatingSystem' => 'Windows (64-bit)',
        'applicationCategory' => 'SecurityApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'ระบบกันการเจาะเซิร์ฟเวอร์ Windows แบบเรียลไทม์ บล็อกการเดารหัส RDP/SSH/ฐานข้อมูล สแกนพอร์ต ยิงหาช่องโหว่ และ IP อันตราย พร้อมจับเครื่องที่ถูกเจาะ',
        'url' => route('products.show', 'anti-x'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => array_values(array_merge(
            [['@type' => 'Offer', 'name' => 'Free', 'price' => '0', 'priceCurrency' => 'THB']],
            collect($prices)->map(fn ($price, $term) => ['@type' => 'Offer', 'name' => 'Pro (' . $plans[$term]['en'] . ')', 'price' => (string) $price, 'priceCurrency' => 'THB'])->values()->all(),
        )),
    ];
@endphp
<div class="atx relative overflow-hidden">

    {{-- ============================ HERO ============================ --}}
    <section class="relative isolate">
        <div class="atx-grid absolute inset-0 -z-10"></div>
        <div class="atx-glow -z-10 w-[34rem] h-[34rem] -top-40 -left-40 bg-rose-600/20"></div>
        <div class="atx-glow -z-10 w-[30rem] h-[30rem] top-32 right-0 bg-emerald-500/10"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-20 lg:pt-12 lg:pb-24">
            <nav class="mb-10">
                <a href="{{ route('products.index') }}" class="inline-flex items-center text-sm text-cyan-300/90 hover:text-cyan-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <x-bi th="กลับไปรายการผลิตภัณฑ์" en="All products" />
                </a>
            </nav>

            <div class="grid lg:grid-cols-12 gap-12 items-center">
                <div class="lg:col-span-6">
                    <div class="flex flex-wrap items-center gap-2 mb-6">
                        <span class="atx-chip"><i class="atx-live"></i>{{ $latest ? 'Anti X ' . $latest->version : 'Anti X' }}</span>
                        <span class="atx-chip">Windows Server · Windows 10/11</span>
                    </div>

                    <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black tracking-tight text-white leading-[1.02] mb-5">
                        Anti <span class="atx-text-glow">X</span>
                    </h1>
                    <p class="text-2xl sm:text-3xl font-bold text-white mb-2">กันการเจาะเซิร์ฟเวอร์ Windows แบบเรียลไทม์</p>
                    <p class="text-base sm:text-lg text-gray-400 mb-6">Real-time intrusion blocking for Windows servers.</p>

                    <p class="text-lg text-gray-300 leading-relaxed mb-2">
                        บล็อกการเดารหัส RDP / SSH / ฐานข้อมูล สแกนพอร์ต ยิงหาช่องโหว่ และ IP อันตราย พร้อมจับเครื่องที่ถูกเจาะ —
                        ทำงานเป็นบริการ Windows ตลอดเวลา แม้ปิดหน้าจอหรือตัด RDP ไปแล้ว
                    </p>
                    <p class="text-sm text-gray-500 leading-relaxed mb-8">
                        Blocks RDP, SSH and database brute force, port scans, exploit probes and known-bad IPs, and spots a machine that has already been broken into.
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('anti-x.download') }}"
                           class="atx-btn-main inline-flex items-center gap-2 px-7 py-4 rounded-xl font-extrabold text-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <x-bi th="ดาวน์โหลดฟรี" en="Free download" />
                        </a>
                        <a href="#pricing" class="atx-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">
                            {{ $ownsAntiX ? 'ซื้อ License เพิ่ม' : 'ซื้อ Pro' }} — เริ่ม ฿{{ $monthly }}/เดือน
                        </a>
                    </div>

                    @if($latest)
                        <p class="mt-4 text-sm text-gray-400">
                            เวอร์ชันล่าสุด {{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · ไฟล์ .zip จาก xman4289.com โดยตรง
                        </p>
                    @endif

                    @if($ownsAntiX)
                        <p class="mt-3 text-sm text-emerald-200/90">
                            คุณมี License แล้ว · 1 คีย์ใช้ได้ 1 เครื่อง — ซื้อเพิ่มได้สำหรับเครื่องอื่น ·
                            <a href="{{ route('customer.licenses') }}" class="font-semibold underline decoration-emerald-300/50 hover:text-white">ดูคีย์ License ของฉัน</a>
                        </p>
                    @endif

                    <ul class="mt-8 grid sm:grid-cols-3 gap-3 text-sm">
                        <li class="flex items-center gap-2 text-gray-300"><span class="atx-badge atx-free">ฟรี</span> ตัวจับพื้นฐาน ใช้ได้ตลอด</li>
                        <li class="flex items-center gap-2 text-gray-300"><span class="atx-badge atx-pro">PRO</span> ทดลองฟรี 14 วัน</li>
                        <li class="flex items-center gap-2 text-gray-300"><span class="w-2 h-2 rounded-full bg-cyan-400 shadow-[0_0_10px_#38d6ff]"></span> ไม่ปิดพอร์ต RDP</li>
                    </ul>
                </div>

                {{-- ตัวอย่างหน้าจอ "เชื่อมต่อสด" (HTML ล้วน ข้อมูลสาธิต) --}}
                <div class="lg:col-span-6">
                    <div class="atx-console">
                        <div>
                            <div class="flex items-center justify-between px-4 py-3 bg-white/[.03]">
                                <span class="flex items-center gap-2 text-sm font-bold text-white">
                                    <span class="w-2 h-2 rounded-full bg-rose-500 atx-live"></span>
                                    <x-bi th="เชื่อมต่อสด" en="Live connections" />
                                </span>
                                <span class="text-[11px] tracking-widest uppercase text-gray-500">ตัวอย่าง · demo data</span>
                            </div>
                            @foreach($demoRows as [$ip, $country, $what, $verdict, $tag])
                                <div class="atx-row">
                                    <span class="min-w-0">
                                        <span class="atx-ip">{{ $ip }}</span>
                                        <span class="ml-2 text-[11px] font-bold text-gray-500">{{ $country }}</span>
                                        <span class="block text-xs text-gray-400 truncate">{{ $what }}</span>
                                    </span>
                                    <span class="atx-tag {{ $tag }}">{{ $verdict }}</span>
                                </div>
                            @endforeach
                            <div class="grid grid-cols-3 border-t border-white/5 text-center">
                                <div class="px-3 py-4"><p class="text-2xl font-black text-rose-300">3</p><p class="text-[11px] text-gray-500">บล็อกอยู่ · blocked</p></div>
                                <div class="px-3 py-4 border-x border-white/5"><p class="text-2xl font-black text-amber-300">1</p><p class="text-[11px] text-gray-500">น่าสงสัย · watch</p></div>
                                <div class="px-3 py-4"><p class="text-2xl font-black text-cyan-300">1</p><p class="text-[11px] text-gray-500">ไว้ใจ · trusted</p></div>
                            </div>
                        </div>
                    </div>
                    <p class="mt-4 text-center text-xs text-gray-500">IP ในตัวอย่างเป็นชุดสำหรับเอกสาร ไม่ใช่ของใครจริง · illustrative data</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================== DETECTORS ========================== --}}
    <section class="relative py-20" id="features">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <p class="atx-eyebrow mb-3">What it catches</p>
                <h2 class="text-3xl md:text-5xl font-black text-white mb-4">ฟรีพื้นฐาน · Pro ปลดตัวจับขั้นสูงทั้งหมด</h2>
                <p class="text-gray-300">ไฟล์เดียวกันทั้งรุ่นฟรีและ Pro — Pro ปลดล็อกในโปรแกรมด้วย License key ระหว่างทดลอง 14 วันใช้ได้ทุกตัว</p>
                <p class="text-sm text-gray-500 mt-1">One download. Pro unlocks inside the app with a licence key; the 14-day trial unlocks everything.</p>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                @foreach([
                    ['ฟรี — ตัวจับพื้นฐานบนเครื่องนี้', 'Free — the core, on this machine', $freeDetectors, false],
                    ['Pro — ตัวจับขั้นสูงทั้งหมด', 'Pro — every advanced detector', $proDetectors, true],
                ] as [$title, $titleEn, $items, $isPro])
                    <div class="atx-card p-6 sm:p-8">
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <h3 class="text-2xl font-black text-white">{{ $title }}</h3>
                            @if($isPro)<span class="atx-badge atx-pro">PRO</span>@else<span class="atx-badge atx-free">ฟรี</span>@endif
                        </div>
                        <p class="text-sm text-gray-500 mb-6">{{ $titleEn }}</p>
                        <ul class="space-y-4">
                            @foreach($items as [$name, $body, $en])
                                <li class="flex gap-3">
                                    <span class="atx-tick {{ $isPro ? 'atx-tick-pro' : '' }}">✓</span>
                                    <span>
                                        <span class="block font-bold text-gray-100">{{ $name }} <span class="text-xs font-normal text-gray-500">· {{ $en }}</span></span>
                                        <span class="block text-sm text-gray-400">{{ $body }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <p class="mt-6 text-center text-xs text-gray-500">ทุกเกณฑ์ (จำนวนครั้ง ช่วงเวลา พอร์ต) แก้ได้ในหน้า ตั้งค่า ของโปรแกรม · IP ที่โจมตีเครื่องหนึ่งถูกบล็อกทุกเครื่องที่ดูแล</p>
        </div>
    </section>

    {{-- ============================ SAFETY ============================ --}}
    <section class="relative py-20">
        <div class="atx-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24 bg-cyan-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <p class="atx-eyebrow mb-3">Never locks you out</p>
                <h2 class="text-3xl md:text-5xl font-black text-white mb-4">กันคนร้าย โดยไม่ล็อกตัวเอง</h2>
                <p class="text-gray-300">ตัวกันเจาะที่ดีต้องไม่ทำให้ผู้ดูแลเข้าเซิร์ฟเวอร์ของตัวเองไม่ได้</p>
                <p class="text-sm text-gray-500 mt-1">A guard must never become the reason you cannot reach your own server.</p>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach([
                    ['บล็อกทีละ IP', 'ไม่เคยปิดพอร์ต RDP 3389 ทั้งพอร์ต — คนอื่นที่ไม่ได้ทำผิดยังเข้าได้ตามปกติ', 'One IP at a time, never the whole port'],
                    ['ผู้ดูแลที่ต่ออยู่ไม่โดนบล็อก', 'IP ที่กำลังต่อ RDP อยู่ถูกไว้ใจตลอดที่ยังต่อ ถ้าเคยโดนบล็อกไว้จะถูกปลดทันทีที่เข้าได้', 'Connected RDP sessions are always trusted'],
                    ['ด่านสุดท้ายก่อนเขียนไฟร์วอลล์', 'คัด IP ที่ไว้ใจ IP ของเราเอง และ IP ของ Cloudflare ออกจากกฎบล็อกทุกครั้ง', 'A last check before every firewall rule'],
                    ['ล็อกอินสำเร็จ = ไว้ใจ 24 ชม.', 'และแจ้งเตือนเมื่อเป็น IP ที่ไม่เคยเห็นมาก่อน', 'Successful logins are trusted for a day'],
                    ['ประเทศผ่อนปรน', 'IP จากประเทศที่เลือก (ค่าเริ่มต้นไทย) ต้องผิดมากกว่าปกติ 4 เท่าจึงถูกบล็อก', 'Lenient countries need 4× the evidence'],
                    ['สวิตช์ปิดฉุกเฉิน', 'สั่ง off / on / unblock ได้จากบรรทัดคำสั่ง ไม่ต้องใช้ RDP และล้างกฎทั้งหมดได้ในคำสั่งเดียว', 'An off switch that needs no RDP'],
                ] as [$title, $body, $en])
                    <div class="atx-card p-6">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4 bg-emerald-400/10 border border-emerald-400/25">
                            <svg class="w-6 h-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-white mb-1.5">{{ $title }}</h3>
                        <p class="text-sm text-gray-300 leading-relaxed">{{ $body }}</p>
                        <p class="text-xs text-gray-500 mt-2">{{ $en }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="atx-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <p class="atx-eyebrow mb-3">Pricing</p>
                <h2 class="text-3xl md:text-5xl font-black text-white mb-4">เริ่มฟรี อยากได้ครบค่อยอัป Pro</h2>
                <p class="text-gray-300">1 คีย์ใช้ได้ 1 เครื่อง · ทดลอง Pro ฟรี 14 วันก่อนตัดสินใจ (ครั้งเดียวต่อเครื่อง)</p>
                <p class="text-sm text-gray-500 mt-1">One key per machine. Try every Pro feature free for 14 days first.</p>
            </div>

            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-5 max-w-6xl mx-auto">
                <div class="atx-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">Free</h3>
                        <span class="atx-badge atx-free">ฟรี</span>
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="ตัวจับพื้นฐาน ไม่จำกัดเวลา" en="The core, no time limit" /></p>
                    <div class="mb-6"><span class="text-5xl font-black text-white">฿0</span></div>
                    <ul class="space-y-2.5 text-gray-200 text-sm mb-8 flex-1">
                        @foreach(['เดารหัส RDP / ฐานข้อมูล', 'สแกนพอร์ต + พอร์ตอันตราย', 'การเชื่อมต่อสด', 'แบล็คลิสต์ · ไวท์ลิสต์', 'อัปเดตอัตโนมัติ'] as $item)
                            <li class="flex gap-3"><span class="atx-tick">✓</span><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                    {{-- ไฟล์เดียวกับ Pro — Pro ปลดล็อกในแอปด้วย license key --}}
                    <a href="{{ route('anti-x.download') }}" class="atx-btn-main block w-full py-3.5 text-center rounded-xl font-extrabold transition">
                        <x-bi th="ดาวน์โหลดฟรี" en="Free download" />
                    </a>
                </div>

                @foreach($plans as $term => $plan)
                    @continue(! isset($prices[$term]))
                    @php($featured = $term === 'yearly')
                    <div class="{{ $featured ? 'relative rounded-[1.1rem] p-px bg-gradient-to-br from-amber-300/80 via-orange-500/40 to-emerald-300/60 shadow-[0_30px_90px_-30px_rgba(255,160,40,.45)]' : '' }}">
                        <div class="{{ $featured ? 'h-full rounded-[1.05rem] bg-[#0b0d14]' : 'atx-card h-full' }} p-7 flex flex-col">
                            @if($featured)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2"><span class="atx-badge atx-pro shadow-lg">คุ้มที่สุด · BEST VALUE</span></div>
                            @endif
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-2xl font-black text-white">Pro {{ $plan['name'] }}</h3>
                                <span class="atx-badge atx-pro">PRO</span>
                            </div>
                            <p class="text-amber-100/80 text-sm mb-6">{{ $plan['note'] }}</p>
                            <div class="mb-6 flex items-baseline gap-2">
                                <span class="text-5xl font-black text-white">฿{{ number_format($prices[$term]) }}</span>
                                <span class="text-gray-300">{{ $plan['unit'] }}</span>
                            </div>
                            <ul class="space-y-2.5 text-gray-100 text-sm mb-8 flex-1">
                                <li class="flex gap-3"><span class="atx-tick atx-tick-pro">✓</span><span>ทุกอย่างในรุ่นฟรี</span></li>
                                <li class="flex gap-3"><span class="atx-tick atx-tick-pro">✓</span><span>ตัวจับขั้นสูงทั้งหมด + จับเครื่องที่ถูกเจาะ</span></li>
                                <li class="flex gap-3"><span class="atx-tick atx-tick-pro">✓</span><span>ดูแล VM ผ่าน SSH · ปุ่ม "จัดการ"</span></li>
                                <li class="flex gap-3"><span class="atx-tick atx-tick-pro">✓</span><span>อายุ License: {{ $plan['days'] }}</span></li>
                            </ul>
                            {{-- ราคามาจาก config/licenses.php ไม่ใช่จากฟอร์ม — ตะกร้าคิดตามแผนที่ส่งไป --}}
                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="license_type" value="{{ $term }}">
                                <input type="hidden" name="buy_now" value="1">
                                <button type="submit" class="{{ $featured ? 'atx-btn-pro' : 'atx-btn-ghost' }} block w-full py-3.5 text-center rounded-xl font-extrabold transition cursor-pointer">
                                    {{ $ownsAntiX ? 'ซื้อ License เพิ่ม' : 'ซื้อ Pro' }} {{ $plan['name'] }} — ฿{{ number_format($prices[$term]) }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($ownsAntiX)
                <p class="text-center mt-6">
                    <a href="{{ route('customer.licenses') }}" class="text-emerald-300 hover:text-emerald-200 text-sm font-semibold">มี License แล้ว? ดูคีย์ของคุณ →</a>
                </p>
            @endif
            <p class="text-center text-xs text-gray-500 mt-6">ซื้อแล้วได้ License key ทางอีเมลและในหน้า บัญชีของฉัน · ใส่คีย์ในโปรแกรมได้เลย ไม่ต้องลงใหม่ · ย้ายเครื่องได้ด้วยการยกเลิกบนเครื่องเดิม</p>
        </div>
    </section>

    {{-- ===================== REQUIREMENTS + INSTALL ===================== --}}
    <section class="relative py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-8">
                <div>
                    <p class="atx-eyebrow mb-3">Requirements</p>
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-8">ใช้กับเครื่องไหนได้บ้าง</h2>
                    <dl class="grid sm:grid-cols-2 gap-4">
                        @foreach([
                            ['ระบบปฏิบัติการ', 'Windows Server หรือ Windows 10/11 แบบ 64-bit', 'Windows, 64-bit'],
                            ['ไม่ต้องติดตั้ง .NET', 'ทุกอย่างอยู่ใน zip เดียว', 'Self-contained'],
                            ['สิทธิ์ Administrator', 'ติดตั้งเป็นบริการ Windows และเขียน Windows Firewall จึงต้องใช้สิทธิ์ผู้ดูแล', 'Installs as a Windows service'],
                            ['อินเทอร์เน็ต', 'ต่อเน็ตตอนเปิดครั้งแรกเพื่อลงทะเบียนเครื่องและเริ่มทดลอง และตอนใส่คีย์', 'Online to register, trial and activate'],
                        ] as [$k, $v, $en])
                            <div class="atx-card p-5">
                                <dt class="text-white font-bold mb-1">{{ $k }}</dt>
                                <dd class="text-sm text-gray-300">{{ $v }}</dd>
                                <dd class="text-xs text-gray-500 mt-1">{{ $en }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    <p class="mt-4 text-xs text-gray-500">อยากเห็นทราฟฟิกของ VM ที่อยู่บนเครื่องเดียวกันด้วย ติดตั้ง Npcap เพิ่ม (ไม่ติดตั้งก็ยังจับ RDP ของเครื่องหลักได้)</p>
                </div>
                <div>
                    <p class="atx-eyebrow mb-3">Get started</p>
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-8">เริ่มใช้ใน 3 ขั้น</h2>
                    <ol class="space-y-4">
                        @foreach([
                            ['ดาวน์โหลดไฟล์ .zip', 'ไฟล์มาจาก xman4289.com โดยตรง ใช้ได้ทั้งรุ่นฟรีและ Pro', 'Download the .zip'],
                            ['แตกไฟล์แล้วรันตัวติดตั้งแบบ Administrator', 'ติดตั้งบริการ Anti X ให้เอง เลือกโหมดเฝ้าดูอย่างเดียวก่อนได้ถ้ายังไม่อยากให้บล็อกจริง', 'Run the installer as Administrator'],
                            ['เปิด Anti X แล้วทดลอง Pro 14 วัน', 'ถูกใจแล้วซื้อคีย์ใส่ในโปรแกรมได้เลย หมดทดลองแล้วตัวจับพื้นฐานยังทำงานต่อฟรี', 'Open Anti X and try Pro for 14 days'],
                        ] as $n => [$title, $body, $en])
                            <li class="atx-card p-5 flex gap-4">
                                <span class="flex-none w-10 h-10 rounded-xl atx-btn-main flex items-center justify-center font-black text-lg">{{ $n + 1 }}</span>
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
        </div>
    </section>

    {{-- ============================== FAQ ============================== --}}
    <section class="relative py-20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <p class="atx-eyebrow mb-3">FAQ</p>
                <h2 class="text-3xl md:text-4xl font-black text-white">คำถามที่พบบ่อย</h2>
            </div>
            <div class="space-y-3">
                @foreach([
                    ['ฟรีจริงไหม?', 'ฟรีจริง ตัวจับพื้นฐาน (เดารหัส สแกนพอร์ต พอร์ตอันตราย) ใช้ได้ไม่จำกัดเวลา ดาวน์โหลดได้เลยโดยไม่ต้องล็อกอิน'],
                    ['ทดลอง Pro ทำอย่างไร?', 'เปิดโปรแกรมครั้งแรกตอนต่อเน็ต จะได้ทดลองทุกฟีเจอร์ Pro 14 วัน ครั้งเดียวต่อเครื่อง (ลง Windows ใหม่ก็ไม่ได้สิทธิ์ซ้ำ) หมดเวลาแล้วตัวจับพื้นฐานยังทำงานต่อ'],
                    ['Pro ราคาเท่าไร ใช้ได้กี่เครื่อง?', 'รายเดือน ฿' . number_format($prices['monthly'] ?? 0) . ' · รายปี ฿' . number_format($prices['yearly'] ?? 0) . ' · ตลอดชีพ ฿' . number_format($prices['lifetime'] ?? 0) . ' — 1 คีย์ใช้ได้ 1 เครื่อง มีหลายเครื่องซื้อคีย์เพิ่มได้ และย้ายคีย์ไปเครื่องใหม่ได้'],
                    ['จะล็อกตัวเองออกจากเซิร์ฟเวอร์ไหม?', 'ไม่ Anti X บล็อกทีละ IP ไม่ปิดพอร์ต RDP ทั้งพอร์ต IP ที่กำลังต่อ RDP อยู่ถูกไว้ใจตลอด และมีคำสั่งปิดฉุกเฉินที่ไม่ต้องใช้ RDP'],
                    ['ใช้กับ VM บนเครื่องเดียวกันได้ไหม?', 'ได้ (Pro) Anti X อ่าน log SSH ใน VM และสั่งไฟร์วอลล์ในตัว VM (csf หรือ iptables) ผ่าน SSH ด้วยคีย์ที่คุณสร้างเอง'],
                    ['ข้อมูลเก็บที่ไหน?', 'เหตุการณ์ ประวัติบล็อก และการตั้งค่าเก็บในเครื่องของคุณเอง โปรแกรมติดต่อ xman4289.com เพื่อตรวจ License และเช็กอัปเดตเท่านั้น'],
                    ['อัปเดตอย่างไร ปลอดภัยไหม?', 'โปรแกรมเช็กและอัปเดตตัวเองจาก xman4289.com ทุกแพ็กเกจมีลายเซ็นดิจิทัลและถูกตรวจก่อนติดตั้งทุกครั้ง ไฟล์ที่ถูกแก้ไขจะไม่ถูกติดตั้ง'],
                ] as [$q, $a])
                    <details class="atx-faq atx-card px-5 py-4">
                        <summary class="flex items-center justify-between gap-4 text-white font-bold">
                            <span>{{ $q }}</span>
                            <span class="pm flex-none w-7 h-7 rounded-full border border-white/15 flex items-center justify-center text-emerald-300 text-lg leading-none">+</span>
                        </summary>
                        <p class="mt-3 text-gray-300 leading-relaxed">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =========================== FINAL CTA =========================== --}}
    <section class="relative py-24 isolate">
        <div class="atx-glow -z-10 w-[40rem] h-[22rem] left-1/2 -translate-x-1/2 top-10 bg-rose-600/15"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-6xl font-black text-white mb-5">ปิดประตูก่อน<span class="atx-text-glow">คนร้ายจะเข้า</span></h2>
            <p class="text-lg text-gray-300 mb-1">ดาวน์โหลดรุ่นฟรีได้ทันที หรือปลดตัวจับขั้นสูงทั้งหมดด้วย Pro เริ่มเพียง ฿{{ $monthly }} ต่อเดือน</p>
            <p class="text-sm text-gray-500 mb-9">Shut the door before anyone walks in.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('anti-x.download') }}" class="atx-btn-main inline-flex items-center gap-2 px-8 py-4 rounded-xl font-extrabold text-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <x-bi th="ดาวน์โหลดฟรี" en="Free download" />
                </a>
                <a href="#pricing" class="atx-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ดูแผน Pro</a>
                @if($ownsAntiX)
                    <a href="{{ route('customer.licenses') }}" class="atx-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ดูคีย์ License ของฉัน</a>
                @endif
            </div>
            <p class="text-gray-500 text-sm mt-7">Windows 64-bit · ไม่ต้องลง .NET · ทดลอง Pro ฟรี 14 วัน · 1 คีย์ = 1 เครื่อง</p>
            <a href="{{ route('products.index') }}" class="inline-block mt-4 text-sm text-cyan-300/90 hover:text-cyan-200"><x-bi th="ดูผลิตภัณฑ์อื่นของ XMAN Studio" en="More from XMAN Studio" /> →</a>
        </div>
    </section>

</div>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
