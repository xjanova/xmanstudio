@extends($publicLayout ?? 'layouts.app')

@section('title', 'LocalVPN — LAN เสมือนผ่านอินเทอร์เน็ต + ลดปิงเกม · Windows และ Android · ทดลอง Pro ฟรี 30 วัน · Pro เริ่ม ฿' . number_format(\App\Support\LicensePlans::price('localvpn', 'monthly') ?? 0) . '/เดือน | XMAN Studio')
@section('meta_description', 'LocalVPN ต่อคอมกับมือถือของเพื่อนเป็นวง LAN เดียวกันผ่านอินเทอร์เน็ต เล่นเกมโหมด LAN และส่งไฟล์เหมือนนั่งข้างกัน พร้อมตัวลดปิงเกมที่วัดเส้นทางจริงก่อนแนะนำ และ VPN เลือกประเทศ สำหรับ Windows และ Android ทดลอง Pro ฟรี 30 วัน แล้วใช้รุ่นฟรีต่อได้')
@section('og_image', asset('images/products/localvpn/og.jpg'))

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600&family=Kanit:wght@500;600;700&display=swap">
<style>
    /* LocalVPN product page — the app's "Millennium 3D" look, scoped under .lvp */
    .lvp {
        --navy: #050A1C; --navy-2: #0A1430; --aqua: #38D9F5; --gold: #F2C75C;
        --text: #EAF4FF; --muted: #A9C3E6; --edge: rgba(198, 230, 255, .7);
        background: var(--navy); color: var(--text);
        font-family: 'Anuphan', 'Noto Sans Thai', system-ui, sans-serif;
    }
    .lvp h1, .lvp h2, .lvp h3, .lvp .lvp-display { font-family: 'Kanit', 'Noto Sans Thai', sans-serif; text-wrap: balance; letter-spacing: -.01em; }
    .lvp p { text-wrap: pretty; }
    .lvp-muted { color: var(--muted); }
    .lvp-nw { white-space: nowrap; }
    .lvp-aqua { color: var(--aqua); }
    .lvp-goldtext { color: var(--gold); }
    .lvp-eyebrow { font-family: 'Kanit', sans-serif; font-size: .78rem; font-weight: 600; letter-spacing: .24em; text-transform: uppercase; color: var(--aqua); }

    /* glass panel: navy-blue vertical glass with a light top edge */
    .lvp-glass {
        border: 1px solid transparent; border-radius: 18px;
        background:
            linear-gradient(180deg, rgba(106, 156, 230, .29) 0%, rgba(23, 47, 106, .54) 42%, rgba(4, 10, 32, .87) 100%) padding-box,
            linear-gradient(180deg, var(--edge), rgba(198, 230, 255, .14) 55%, rgba(198, 230, 255, .08)) border-box;
        box-shadow: 0 24px 60px -30px rgba(0, 0, 0, .85), inset 0 1px 0 rgba(255, 255, 255, .08);
    }
    .lvp-glass-soft { border-radius: 14px; background: rgba(10, 20, 48, .62); border: 1px solid rgba(198, 230, 255, .16); }

    /* glossy aqua button with a thin gold ring — rounded rectangle, never a pill */
    /* gold ring = the border (border-box layer), #0A7FA6 edge = an inset line just inside it, gloss split at 50% */
    .lvp-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: .6rem;
        padding: .85rem 1.35rem; border-radius: 12px; font-family: 'Kanit', sans-serif; font-weight: 600; line-height: 1.2;
        color: #03263B; border: 2px solid transparent;
        background:
            linear-gradient(180deg, #D4FCFF 0%, #6EE6F8 50%, #18AEDB 50%, #3CCFF0 100%) padding-box,
            linear-gradient(90deg, #C9932E, #FFF1C4 50%, #C9932E) border-box;
        box-shadow: inset 0 0 0 1px #0A7FA6, inset 0 2px 0 rgba(255, 255, 255, .55), 0 10px 26px -10px rgba(56, 217, 245, .6);
        transition: transform .15s ease, filter .15s ease;
    }
    .lvp-btn:hover { filter: brightness(1.06); transform: translateY(-1px); }
    .lvp-btn:focus-visible, .lvp-btn-glass:focus-visible { outline: 2px solid #FFF1C4; outline-offset: 4px; }
    .lvp-btn-glass {
        display: inline-flex; align-items: center; justify-content: center; gap: .6rem; padding: .9rem 1.4rem; border-radius: 11px;
        font-family: 'Kanit', sans-serif; font-weight: 600; color: var(--text);
        background: linear-gradient(180deg, rgba(106, 156, 230, .32), rgba(10, 20, 48, .8));
        border: 1px solid rgba(198, 230, 255, .45); transition: background .15s ease, transform .15s ease;
    }
    .lvp-btn-glass:hover { background: linear-gradient(180deg, rgba(106, 156, 230, .45), rgba(10, 20, 48, .85)); transform: translateY(-1px); }
    .lvp-btn-gold {
        position: relative; isolation: isolate; display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
        padding: .9rem 1.4rem; border-radius: 11px; font-family: 'Kanit', sans-serif; font-weight: 600; color: #2A1A00;
        border: 1px solid #A8741C; background: linear-gradient(180deg, #FFF1C4 0%, #F2C75C 50%, #D9A43A 50%, #F2C75C 100%);
        box-shadow: 0 10px 26px -12px rgba(242, 199, 92, .55), inset 0 1px 0 rgba(255, 255, 255, .7); transition: filter .15s ease, transform .15s ease;
    }
    .lvp-btn-gold:hover { filter: brightness(1.05); transform: translateY(-1px); }
    .lvp-btn small, .lvp-btn-glass small { display: block; font-family: 'Anuphan', sans-serif; font-weight: 500; font-size: .74rem; opacity: .8; }

    .lvp-badge { display: inline-flex; align-items: center; gap: .3rem; font-family: 'Kanit', sans-serif; font-weight: 600; font-size: .72rem; letter-spacing: .06em; padding: .28rem .55rem; border-radius: 7px; line-height: 1; white-space: nowrap; }
    .lvp-badge-pro { color: #2A1A00; background: linear-gradient(180deg, #FFF1C4, #F2C75C 55%, #D9A43A); border: 1px solid #A8741C; }
    .lvp-badge-free { color: #03263B; background: linear-gradient(180deg, #D4FCFF, #6EE6F8); border: 1px solid #0A7FA6; }
    .lvp-chip { display: inline-flex; align-items: center; gap: .45rem; padding: .4rem .7rem; border-radius: 9px; font-size: .84rem; color: var(--text); background: rgba(10, 20, 48, .6); border: 1px solid rgba(198, 230, 255, .22); }
    .lvp-dot { width: .5rem; height: .5rem; border-radius: 9999px; background: var(--aqua); box-shadow: 0 0 10px var(--aqua); }

    .lvp-num { flex: none; width: 2.5rem; height: 2.5rem; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center;
        font-family: 'Kanit', sans-serif; font-weight: 700; font-size: 1.1rem; color: #03263B; border: 1px solid #0A7FA6;
        background: linear-gradient(180deg, #D4FCFF 0%, #6EE6F8 50%, #18AEDB 50%, #3CCFF0 100%); box-shadow: 0 0 0 2px rgba(242, 199, 92, .65); }
    .lvp-icon { width: 4.25rem; height: 4.25rem; flex: none; filter: drop-shadow(0 8px 16px rgba(56, 217, 245, .25)); }
    .lvp-tick { flex: none; width: 1.35rem; height: 1.35rem; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center; font-size: .78rem; font-weight: 800; color: #03263B; background: linear-gradient(180deg, #D4FCFF, #6EE6F8); margin-top: .15rem; }
    .lvp-tick-pro { color: #2A1A00; background: linear-gradient(180deg, #FFF1C4, #F2C75C); }
    .lvp-cross { flex: none; width: 1.35rem; height: 1.35rem; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center; font-size: .78rem; color: rgba(169, 195, 230, .7); background: rgba(169, 195, 230, .1); margin-top: .15rem; }

    .lvp-hero { position: relative; isolation: isolate; overflow: hidden; }
    /* the art's light waves sit on its left — mirrored, they glow behind the screenshot and the text side stays dark */
    .lvp-hero-bg { position: absolute; inset: 0; z-index: -2; background: url('{{ asset('images/products/localvpn/bg-night.webp') }}') left center / cover no-repeat; transform: scaleX(-1); }
    /* drawn inside the mirrored box, so 0% here is the right-hand side of the page */
    .lvp-hero-bg::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(5, 10, 28, .25) 0%, rgba(5, 10, 28, .7) 45%, rgba(5, 10, 28, .94) 70%), linear-gradient(180deg, rgba(5, 10, 28, 0) 60%, var(--navy) 100%); }
    @media (max-width: 1023px) { .lvp-hero-bg::after { background: linear-gradient(180deg, rgba(5, 10, 28, .82), rgba(5, 10, 28, .9) 60%, var(--navy)); } }
    .lvp-shot { border-radius: 16px; overflow: hidden; border: 1px solid rgba(198, 230, 255, .45); box-shadow: 0 40px 90px -35px rgba(56, 217, 245, .45), 0 18px 40px -20px rgba(0, 0, 0, .9); background: var(--navy-2); }
    .lvp-shot img { display: block; width: 100%; height: auto; }
    .lvp-section { position: relative; padding: 5rem 0; }
    .lvp-section-alt { background: linear-gradient(180deg, var(--navy) 0%, var(--navy-2) 50%, var(--navy) 100%); }
    .lvp-wrap { max-width: 80rem; margin: 0 auto; padding: 0 1rem; }
    @media (min-width: 640px) { .lvp-wrap { padding: 0 1.5rem; } }
    @media (min-width: 1024px) { .lvp-wrap { padding: 0 2rem; } }

    .lvp-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .95rem; }
    .lvp-table th, .lvp-table td { padding: .85rem 1rem; text-align: left; vertical-align: top; border-top: 1px solid rgba(198, 230, 255, .12); }
    .lvp-table thead th { border-top: 0; font-family: 'Kanit', sans-serif; font-weight: 600; color: var(--text); }
    .lvp-table td:not(:first-child), .lvp-table th:not(:first-child) { text-align: center; white-space: nowrap; }
    .lvp-table tbody th { font-weight: 500; color: var(--text); }
    @media (max-width: 639px) {
        .lvp-table { font-size: .86rem; }
        .lvp-table th, .lvp-table td { padding: .7rem .5rem; }
        .lvp-table td:not(:first-child), .lvp-table th:not(:first-child) { white-space: normal; }
    }

    .lvp-faq summary { list-style: none; cursor: pointer; }
    .lvp-faq summary::-webkit-details-marker { display: none; }
    .lvp-faq summary .pm { transition: transform .2s ease; }
    .lvp-faq[open] summary .pm { transform: rotate(45deg); }
    .lvp-gallery a { display: block; }
    .lvp-gallery figcaption { font-size: .86rem; color: var(--muted); padding: .7rem .9rem .85rem; }

    @media (prefers-reduced-motion: reduce) {
        .lvp-btn, .lvp-btn-glass, .lvp-btn-gold { transition: none; }
        .lvp-btn:hover, .lvp-btn-glass:hover, .lvp-btn-gold:hover { transform: none; }
    }
</style>
@endpush

@section('content')
@php
    // ราคาทุกแผนมาจาก config/licenses.php 'plans' ที่เดียว (ตะกร้าคิดตามนี้) — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $prices = \App\Support\LicensePlans::for('localvpn');
    $monthly = number_format($prices['monthly'] ?? 0);
    $yearlySaving = \App\Support\LicensePlans::yearlySaving('localvpn');
    $devices = \App\Support\LicensePlans::devicesPerKey('localvpn');

    // ดาวน์โหลดจาก xman4289.com เท่านั้น — ตัวติดตั้ง Windows ใช้ route ของมันเมื่อเว็บมีแล้ว ไม่งั้นหน้าโหลดของ LocalVPN
    $windowsUrl = \Illuminate\Support\Facades\Route::has('localvpn.download.windows')
        ? route('localvpn.download.windows')
        : route('localvpn.download');
    $androidUrl = route('localvpn.download.apk');

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว — เปิดหน้านี้ต้องไม่ถาม GitHub)
    $latest = $product->latestVersion();

    $ownsLocalVpn = auth()->check() && $hasPurchased;

    $img = fn (string $path) => asset('images/products/localvpn/' . $path);

    $plans = [
        'monthly' => ['name' => 'รายเดือน', 'en' => 'Monthly', 'unit' => '/ เดือน', 'note' => 'ซื้อทีละ 30 วัน ไม่ตัดเงินอัตโนมัติ', 'days' => '30 วัน'],
        'yearly' => ['name' => 'รายปี', 'en' => 'Yearly', 'unit' => '/ ปี', 'note' => $yearlySaving ? 'ถูกกว่ารายเดือน ' . $yearlySaving . '%' : 'จ่ายครั้งเดียวใช้ทั้งปี', 'days' => '365 วัน'],
        'lifetime' => ['name' => 'ตลอดชีพ', 'en' => 'Lifetime', 'unit' => 'จ่ายครั้งเดียว', 'note' => 'ใช้ได้ตลอด ไม่มีวันหมดอายุ', 'days' => 'ไม่หมดอายุ'],
    ];

    $features = [
        [
            'icon' => 'networks', 'title' => 'วง LAN เสมือน ผ่านอินเทอร์เน็ต', 'en' => 'Virtual LAN over the internet',
            'body' => 'สร้างวงหรือเข้าวงเพื่อนด้วยรหัส ทุกเครื่องในวงได้ IP ชุดเดียวกัน (เช่น 10.10.7.x) และมองเห็นกันเหมือนเสียบสาย LAN เดียวกัน — เล่นเกมโหมด LAN หรือต่อด้วย IP ได้เลย',
        ],
        [
            'icon' => 'adapter', 'title' => 'การ์ดเครือข่ายจริงบน Windows', 'en' => 'A real network adapter (Wintun)',
            'body' => 'บน Windows แอปสร้างการ์ดเครือข่ายเสมือน (Wintun) ให้เกมและโปรแกรมทั่วไปเห็นวง LAN เหมือนการ์ดแลนจริง ไม่ต้องตั้งค่าเราเตอร์หรือเปิดพอร์ต',
        ],
        [
            'icon' => 'p2p-direct', 'title' => 'ต่อตรง P2P ก่อน รีเลย์สำรอง', 'en' => 'Direct P2P first, relay as backup',
            'body' => 'พยายามต่อตรงถึงกันทะลุ NAT ก่อนเสมอ เครือข่ายไหนต่อตรงไม่ได้จึงส่งผ่านรีเลย์ หน้าสมาชิกบอกชัดว่าแต่ละเครื่องต่อแบบไหน',
        ],
        [
            'icon' => 'game-boost', 'title' => 'ลดปิงเกม แบบวัดจริง', 'en' => 'Game ping booster that measures first',
            'body' => 'วัดเส้นทางจริงไปภูมิภาคเซิร์ฟเวอร์เกม (สิงคโปร์ ฮ่องกง ญี่ปุ่น เกาหลี อินเดีย ออสเตรเลีย สหรัฐฯ ยุโรป ฯลฯ) เทียบเส้นทางตรงของเน็ตคุณกับรีเลย์ใกล้เคียง แล้วแนะนำรีเลย์เฉพาะเมื่อมันเร็วกว่าจริง',
            'pro' => true,
        ],
        [
            'icon' => 'settings', 'title' => 'ปรับแต่ง Windows ระหว่างเล่น', 'en' => 'Windows tune-ups while you play',
            'body' => 'หยุดการสแกน Wi-Fi เบื้องหลังที่ทำให้ปิงกระตุก 100–300 ms ตั้งแผนพลังงานประสิทธิภาพสูง และปิด Nagle สำหรับเกม TCP — ทุกอย่างคืนค่าเดิมเมื่อปิดโหมดลดปิง',
            'pro' => true,
        ],
        [
            'icon' => 'vpn-proxy', 'title' => 'VPN เลือกประเทศ (WireGuard)', 'en' => 'Country VPN over WireGuard',
            'body' => 'เปลี่ยนประเทศของอินเทอร์เน็ตเพื่อเข้าเกมหรือเว็บต่างประเทศ ด้วยโปรโตคอล WireGuard โดยวง LAN และเครือข่ายในบ้านยังใช้ได้ตามปกติ',
        ],
        [
            'icon' => 'file-transfer', 'title' => 'ส่งไฟล์ถึงเพื่อนในวง', 'en' => 'P2P file transfer',
            'body' => 'เลือกไฟล์แล้วทุกเครื่องในวงกดรับได้ทันที ส่งตรงแบบ P2P ถ้าต่อตรงไม่ได้จะส่งผ่านรีเลย์ให้อัตโนมัติ',
        ],
        [
            'icon' => 'torrent', 'title' => 'Torrent ชุมชน', 'en' => 'Community torrent',
            'body' => 'แชร์และดาวน์โหลดไฟล์กับผู้ใช้ LocalVPN ทั่วโลกแบบแยกหมวด ยิ่งมีคนแชร์ต่อยิ่งเร็ว พร้อมอันดับนักแชร์และถ้วยรางวัล',
        ],
    ];

    $compare = [
        ['สร้างวง / เข้าวง LAN เสมือน', true, true],
        ['สมาชิกต่อวง', '5 คน', '50 คน'],
        ['การ์ดเครือข่ายเสมือนบน Windows', true, true],
        ['ส่งไฟล์ในวง (P2P)', true, true],
        ['Torrent ชุมชน', true, true],
        ['VPN เลือกประเทศ', 'ประเทศฟรี', 'ทุกประเทศ'],
        ['ลดปิงเกม + ปรับแต่ง Windows', false, true],
        ['อัปเดตอัตโนมัติ', true, true],
        ['ใช้ได้กี่เครื่อง', 'ทุกเครื่องที่ติดตั้ง', '1 คีย์ = ' . $devices . ' เครื่อง'],
    ];

    $gallery = [
        ['night-boost', 'ลดปิงเกม — ธีมกลางคืน', 'วัดปิง ความกระชาก (jitter) และแพ็กเก็ตหาย ระหว่างเล่นจริง'],
        ['night-network', 'รายละเอียดวง', 'สมาชิกแต่ละเครื่อง ต่อตรง P2P หรือผ่านรีเลย์'],
        ['sky-networks', 'เครือข่าย LAN', 'ล็อบบี้วงสาธารณะ ค้นหา หรือเข้าวงส่วนตัวด้วยรหัส'],
        ['sky-home', 'ภาพรวม — ธีมท้องฟ้า', 'สถานะวง สมาชิกออนไลน์ ปิงเกม และ VPN ในหน้าเดียว'],
        ['sky-vpn', 'VPN ประเทศ', 'เลือกประเทศ ดูปิงและโหลดของแต่ละเซิร์ฟเวอร์'],
        ['sky-files', 'ส่งไฟล์', 'ส่งถึงทุกเครื่องในวงพร้อมกัน'],
        ['phone', 'บนมือถือ Android', 'หน้าจอเดียวกัน จัดวางสำหรับมือถือ'],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'LocalVPN',
        'operatingSystem' => 'Windows 10, Windows 11, Android 7.0+',
        'applicationCategory' => 'NetworkingApplication',
        'inLanguage' => 'th',
        'description' => 'LAN เสมือนผ่านอินเทอร์เน็ต พร้อมตัวลดปิงเกม VPN เลือกประเทศ และส่งไฟล์ P2P สำหรับ Windows และ Android',
        'url' => route('products.show', 'localvpn'),
        'image' => $img('og.jpg'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => array_values(array_merge(
            [['@type' => 'Offer', 'name' => 'Free', 'price' => '0', 'priceCurrency' => 'THB']],
            collect($prices)->map(fn ($price, $term) => ['@type' => 'Offer', 'name' => 'Pro (' . ($plans[$term]['en'] ?? $term) . ')', 'price' => (string) $price, 'priceCurrency' => 'THB'])->values()->all(),
        )),
    ];
@endphp
<div class="lvp relative overflow-hidden">

    {{-- ============================ HERO ============================ --}}
    <section class="lvp-hero">
        <div class="lvp-hero-bg" aria-hidden="true"></div>

        <div class="lvp-wrap pt-8 pb-16 lg:pt-12 lg:pb-24">
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="inline-flex items-center text-sm lvp-aqua hover:text-white">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <x-bi th="กลับไปรายการผลิตภัณฑ์" en="All products" />
                </a>
            </nav>

            <div class="grid lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-6">
                    <div class="flex items-center gap-4 mb-6">
                        <img src="{{ $img('logo.webp') }}" alt="โลโก้ LocalVPN" width="96" height="96" class="w-20 h-20 sm:w-24 sm:h-24" style="filter: drop-shadow(0 10px 24px rgba(56,217,245,.35))">
                        <div class="flex flex-col gap-2">
                            <span class="lvp-chip"><span class="lvp-dot"></span>{{ $latest ? 'LocalVPN ' . $latest->version : 'LocalVPN' }}</span>
                            <span class="lvp-chip">Windows 10/11 · Android</span>
                        </div>
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-4">
                        <span class="block">LocalVPN</span>
                        <span class="block text-2xl sm:text-3xl lg:text-4xl mt-2 lvp-aqua"><span class="lvp-nw">LAN เสมือนผ่านอินเทอร์เน็ต</span> <span class="lvp-nw">+ ลดปิงเกม</span></span>
                    </h1>
                    <p class="text-lg lvp-muted leading-relaxed mb-8">
                        ต่อคอมกับมือถือของเพื่อนให้อยู่วง LAN เดียวกันจากคนละบ้าน เล่นเกมโหมด LAN ส่งไฟล์ และลดปิงเกมด้วยเส้นทางที่วัดแล้วว่าเร็วกว่าจริง
                    </p>

                    <div class="flex flex-wrap gap-4">
                        <a href="{{ $windowsUrl }}" class="lvp-btn text-lg">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 5.1 10.4 4v7.1H3V5.1Zm0 13.8 7.4 1.1v-7H3v5.9ZM11.3 3.9 21 2.5v8.6h-9.7V3.9Zm0 16.2 9.7 1.4v-8.5h-9.7v7.1Z"/></svg>
                            <span class="text-left">ดาวน์โหลดสำหรับ Windows<small>Windows 10/11 · 64-bit</small></span>
                        </a>
                        <a href="{{ $androidUrl }}" class="lvp-btn-glass text-lg">
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.6 9.48 19.44 6.3a.38.38 0 0 0-.66-.38l-1.86 3.22a11.4 11.4 0 0 0-9.84 0L5.22 5.92a.38.38 0 0 0-.66.38L6.4 9.48A10.8 10.8 0 0 0 1 18h22a10.8 10.8 0 0 0-5.4-8.52ZM7 15.25a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5Zm10 0a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5Z"/></svg>
                            <span class="text-left">ดาวน์โหลดสำหรับ Android<small>ไฟล์ APK · Android 7.0+</small></span>
                        </a>
                    </div>

                    <p class="mt-6 text-base">
                        <span class="lvp-badge lvp-badge-pro mr-2">PRO</span>
                        <strong>ทดลอง Pro ครบทุกฟีเจอร์ฟรี 30 วัน</strong>
                        <span class="lvp-muted">— ไม่ต้องผูกบัตร ครบแล้วใช้รุ่นฟรีต่อได้ · Pro เริ่ม ฿{{ $monthly }}/เดือน</span>
                    </p>
                    @if($latest)
                        <p class="mt-2 text-sm lvp-muted">เวอร์ชันล่าสุด {{ $latest->version }} · ดาวน์โหลดจาก xman4289.com โดยตรง</p>
                    @endif
                    @if($ownsLocalVpn)
                        <p class="mt-3 text-sm">
                            คุณมี License แล้ว · 1 คีย์ใช้ได้ {{ $devices }} เครื่อง ·
                            <a href="{{ route('customer.licenses') }}" class="font-semibold underline lvp-aqua hover:text-white">ดูคีย์ License ของฉัน</a>
                        </p>
                    @endif
                </div>

                <div class="lg:col-span-6">
                    <div class="lvp-shot">
                        <img src="{{ $img('shots/night-home.webp') }}" alt="หน้าภาพรวมของ LocalVPN บน Windows: เชื่อมต่อ LAN แล้ว สมาชิกออนไลน์ 4 จาก 6 เครื่อง ปิงเกม 31 ms ผ่านสิงคโปร์" width="1424" height="891" fetchpriority="high">
                    </div>
                    <p class="mt-3 text-center text-xs lvp-muted">หน้าจอจริงของแอป (ข้อมูลตัวอย่าง)</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================== FEATURES ========================== --}}
    <section class="lvp-section" id="features">
        <div class="lvp-wrap">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <p class="lvp-eyebrow mb-3">Features</p>
                <h2 class="text-3xl md:text-5xl font-bold mb-4">ทุกอย่างที่ทำให้เพื่อนต่างบ้านเล่นด้วยกันได้</h2>
                <p class="lvp-muted">แอปเดียวทั้ง Windows และ Android ใช้วง LAN เดียวกันได้ข้ามอุปกรณ์</p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach($features as $f)
                    <div class="lvp-glass p-6 flex flex-col">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <img src="{{ $img('icons/' . $f['icon'] . '.webp') }}" alt="" width="68" height="68" class="lvp-icon" loading="lazy">
                            @if(! empty($f['pro']))<span class="lvp-badge lvp-badge-pro">PRO</span>@endif
                        </div>
                        <h3 class="text-xl font-semibold mb-1">{{ $f['title'] }}</h3>
                        <p class="text-xs lvp-muted mb-3">{{ $f['en'] }}</p>
                        <p class="text-sm lvp-muted leading-relaxed">{{ $f['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ===================== PING BOOSTER (honest) ===================== --}}
    <section class="lvp-section lvp-section-alt" id="ping">
        <div class="lvp-wrap">
            <div class="grid lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-5">
                    <p class="lvp-eyebrow mb-3">Game ping booster</p>
                    <h2 class="text-3xl md:text-4xl font-bold mb-5">ลดปิงเมื่อลดได้จริง และบอกตรง ๆ เมื่อลดไม่ได้</h2>
                    <p class="lvp-muted leading-relaxed mb-6">
                        ปิงที่สูงเกินจำเป็นมักมาจาก <strong class="text-white">เส้นทาง</strong> — บางครั้งเน็ตบ้านส่งข้อมูลไปเกมอ้อมไกลผ่านประเทศอื่น
                        รีเลย์ที่อยู่บนเส้นทางที่ดีกว่าช่วยตัดทางอ้อมนั้นได้ แต่ไม่มีโปรแกรมไหนทำให้ข้อมูลวิ่งเร็วกว่าระยะทางจริง
                    </p>
                    <ol class="space-y-4">
                        @foreach([
                            ['วัดก่อน', 'วัดปิงจริงไปภูมิภาคของเซิร์ฟเวอร์เกม ทั้งเส้นทางตรงของคุณและผ่านรีเลย์ใกล้เคียง'],
                            ['เทียบ', 'ดูทั้งปิงเฉลี่ย ความกระชาก (jitter) และแพ็กเก็ตหาย ไม่ใช่ตัวเลขครั้งเดียว'],
                            ['แนะนำเมื่อชนะ', 'เปิดรีเลย์เฉพาะเมื่อเร็วกว่าจริง ถ้าเส้นทางตรงดีที่สุดอยู่แล้ว แอปบอกให้ใช้ทางตรง'],
                        ] as $n => [$title, $body])
                            <li class="flex gap-4">
                                <span class="lvp-num" aria-hidden="true">{{ $n + 1 }}</span>
                                <span>
                                    <span class="block font-semibold text-white">{{ $title }}</span>
                                    <span class="block text-sm lvp-muted">{{ $body }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ol>
                    <p class="mt-6 text-sm lvp-muted">บน Windows ยังช่วยลดอาการปิงกระตุกจากเครื่องเอง: หยุดสแกน Wi-Fi ระหว่างเล่น แผนพลังงานสูง และปิด Nagle — คืนค่าทั้งหมดเมื่อปิดโหมดลดปิง (ต้องใช้สิทธิ์ผู้ดูแลระบบ)</p>
                </div>
                <div class="lg:col-span-7">
                    <div class="lvp-shot">
                        <img src="{{ $img('shots/sky-boost.webp') }}" alt="หน้าลดปิงเกมของ LocalVPN: กำลังลดปิงผ่านสิงคโปร์ ปิง 31 ms กระชาก 3 ms แพ็กเก็ตหาย 0% พร้อมรายการเกม ภูมิภาค และสวิตช์ปรับแต่ง Windows" width="1424" height="891" loading="lazy">
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================= SCREENSHOTS ========================= --}}
    <section class="lvp-section" id="screenshots">
        <div class="lvp-wrap">
            <div class="text-center max-w-3xl mx-auto mb-10">
                <p class="lvp-eyebrow mb-3">Screenshots</p>
                <h2 class="text-3xl md:text-5xl font-bold mb-4">หน้าตาของแอปจริง</h2>
                <p class="lvp-muted">มีสองธีม — <strong class="text-white">กลางคืน</strong> น้ำเงินเข้มเรืองแสงฟ้า และ <strong class="text-white">ท้องฟ้า</strong> ขาวฟ้าใส เปลี่ยนได้ทันทีในหน้าตั้งค่า</p>
            </div>
            <div class="lvp-gallery grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($gallery as [$file, $title, $caption])
                    <figure class="lvp-glass overflow-hidden {{ $file === 'phone' ? 'sm:col-span-2 lg:col-span-3' : '' }}" @if($file === 'phone') style="justify-self: center; width: 100%; max-width: 56rem" @endif>
                        <a href="{{ $img('shots/' . $file . '.webp') }}" target="_blank" rel="noopener">
                            <img src="{{ $img('shots/' . $file . '.webp') }}" alt="{{ $title }} — {{ $caption }}" loading="lazy" class="w-full h-auto block" @if($file === 'phone') width="1614" height="851" @else width="1424" height="891" @endif>
                        </a>
                        <figcaption><strong class="block text-white font-semibold">{{ $title }}</strong>{{ $caption }}</figcaption>
                    </figure>
                @endforeach
            </div>
            <p class="mt-4 text-center text-xs lvp-muted">ข้อมูลในภาพเป็นตัวอย่าง</p>
        </div>
    </section>

    {{-- ========================= FREE vs PRO ========================= --}}
    <section class="lvp-section lvp-section-alt" id="free-vs-pro">
        <div class="lvp-wrap">
            <div class="text-center max-w-3xl mx-auto mb-10">
                <p class="lvp-eyebrow mb-3">Free vs Pro</p>
                <h2 class="text-3xl md:text-5xl font-bold mb-4">ฟรีใช้ได้จริง Pro ปลดเต็ม</h2>
                <p class="lvp-muted">เครื่องใหม่ได้ทดลอง Pro ครบทุกอย่าง 30 วัน ครบแล้วยังใช้รุ่นฟรีต่อได้ตลอด ไม่ต้องลงแอปใหม่</p>
            </div>
            <div class="lvp-glass max-w-4xl mx-auto overflow-x-auto">
                <table class="lvp-table">
                    <thead>
                        <tr>
                            <th scope="col">ฟีเจอร์</th>
                            <th scope="col"><span class="lvp-badge lvp-badge-free">ฟรี</span></th>
                            <th scope="col"><span class="lvp-badge lvp-badge-pro">PRO</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($compare as [$label, $free, $pro])
                            <tr>
                                <th scope="row">{{ $label }}</th>
                                @foreach([$free, $pro] as $i => $cell)
                                    <td>
                                        @if($cell === true)
                                            <span class="{{ $i ? 'lvp-tick lvp-tick-pro' : 'lvp-tick' }}" role="img" aria-label="มี">✓</span>
                                        @elseif($cell === false)
                                            <span class="lvp-cross" role="img" aria-label="ไม่มี">—</span>
                                        @else
                                            <span class="{{ $i ? 'lvp-goldtext font-semibold' : 'lvp-muted' }}">{{ $cell }}</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="lvp-section scroll-mt-20" id="pricing">
        {{-- the app's old "buy" links point at /localvpn#monthly, #yearly, #lifetime — /localvpn redirects here, each card carries that id --}}
        <div class="lvp-wrap">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <p class="lvp-eyebrow mb-3">Pricing</p>
                <h2 class="text-3xl md:text-5xl font-bold mb-4">เลือกแผน Pro</h2>
                <p class="lvp-muted">
                    <span class="lvp-goldtext font-semibold">1 คีย์ใช้ได้ {{ $devices }} เครื่องพร้อมกัน</span> — เช่น คอม 1 เครื่อง + มือถือ 1 เครื่อง ·
                    ทดลอง Pro ฟรี 30 วันก่อนตัดสินใจ (ครั้งเดียวต่อเครื่อง)
                </p>
            </div>

            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-6 max-w-6xl mx-auto">
                <div class="lvp-glass p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-bold">ฟรี</h3>
                        <span class="lvp-badge lvp-badge-free">FREE</span>
                    </div>
                    <p class="lvp-muted text-sm mb-6">ใช้ได้ตลอด ไม่มีวันหมดอายุ</p>
                    <div class="mb-6"><span class="lvp-display text-5xl font-bold">฿0</span></div>
                    <ul class="space-y-2.5 text-sm mb-8 flex-1">
                        @foreach(['วง LAN เสมือน สูงสุด 5 คน/วง', 'ส่งไฟล์ในวง + Torrent ชุมชน', 'VPN เฉพาะประเทศฟรี', 'อัปเดตอัตโนมัติ'] as $item)
                            <li class="flex gap-3"><span class="lvp-tick">✓</span><span>{{ $item }}</span></li>
                        @endforeach
                    </ul>
                    {{-- ไฟล์เดียวกับ Pro — Pro ปลดในแอปด้วย License key --}}
                    <div class="grid gap-3">
                        <a href="{{ $windowsUrl }}" class="lvp-btn w-full">ดาวน์โหลด Windows</a>
                        <a href="{{ $androidUrl }}" class="lvp-btn-glass w-full">ดาวน์โหลด Android</a>
                    </div>
                </div>

                @foreach($plans as $term => $plan)
                    @continue(! isset($prices[$term]))
                    @php($featured = $term === 'yearly')
                    <div id="{{ $term }}" class="lvp-glass p-7 flex flex-col relative scroll-mt-24 {{ $featured ? 'ring-2 ring-amber-300/70' : '' }}" @if($featured) style="box-shadow: 0 30px 80px -30px rgba(242,199,92,.45)" @endif>
                        @if($featured)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2"><span class="lvp-badge lvp-badge-pro">คุ้มที่สุด · BEST VALUE</span></div>
                        @endif
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-2xl font-bold">Pro {{ $plan['name'] }}</h3>
                            <img src="{{ $img('icons/crown.webp') }}" alt="" width="40" height="40" class="w-10 h-10" loading="lazy">
                        </div>
                        <p class="text-sm mb-6 lvp-goldtext">{{ $plan['note'] }}</p>
                        <div class="mb-6 flex items-baseline gap-2 flex-wrap">
                            <span class="lvp-display text-5xl font-bold">฿{{ number_format($prices[$term]) }}</span>
                            <span class="lvp-muted">{{ $plan['unit'] }}</span>
                        </div>
                        <ul class="space-y-2.5 text-sm mb-8 flex-1">
                            <li class="flex gap-3"><span class="lvp-tick lvp-tick-pro">✓</span><span>ทุกอย่างในรุ่นฟรี</span></li>
                            <li class="flex gap-3"><span class="lvp-tick lvp-tick-pro">✓</span><span>ลดปิงเกม + ปรับแต่ง Windows</span></li>
                            <li class="flex gap-3"><span class="lvp-tick lvp-tick-pro">✓</span><span>สมาชิกสูงสุด 50 คน/วง · VPN ทุกประเทศ</span></li>
                            <li class="flex gap-3"><span class="lvp-tick lvp-tick-pro">✓</span><span>1 คีย์ใช้ได้ {{ $devices }} เครื่อง · อายุ {{ $plan['days'] }}</span></li>
                        </ul>
                        {{-- ราคามาจาก config/licenses.php ไม่ใช่จากฟอร์ม — ตะกร้าคิดตามแผนที่ส่งไป --}}
                        <form action="{{ route('cart.add', $product) }}" method="POST">
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="license_type" value="{{ $term }}">
                            <input type="hidden" name="buy_now" value="1">
                            <button type="submit" class="{{ $featured ? 'lvp-btn-gold' : 'lvp-btn-glass' }} w-full cursor-pointer">
                                {{ $ownsLocalVpn ? 'ซื้อ License เพิ่ม' : 'ซื้อ Pro' }} {{ $plan['name'] }} — ฿{{ number_format($prices[$term]) }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>

            <div class="lvp-glass-soft max-w-4xl mx-auto mt-8 p-5 flex flex-col sm:flex-row items-center gap-5">
                <div class="flex items-center gap-2 flex-none">
                    <img src="{{ $img('icons/device-pc.webp') }}" alt="" width="56" height="56" class="w-14 h-14" loading="lazy">
                    <span class="lvp-display text-2xl lvp-goldtext">+</span>
                    <img src="{{ $img('icons/device-phone.webp') }}" alt="" width="56" height="56" class="w-14 h-14" loading="lazy">
                </div>
                <p class="text-sm lvp-muted">
                    <strong class="text-white">1 คีย์ = คอม + มือถือ</strong> ใส่คีย์เดียวกันได้ {{ $devices }} เครื่องพร้อมกัน
                    อยากย้ายเครื่องก็กดยกเลิก License บนเครื่องเดิมก่อน หรือย้ายจากเครื่องใหม่ได้เลย (เครื่องที่ไม่ได้ใช้นานที่สุดจะถูกปลดออก)
                </p>
            </div>

            @if($ownsLocalVpn)
                <p class="text-center mt-6">
                    <a href="{{ route('customer.licenses') }}" class="lvp-aqua hover:text-white text-sm font-semibold">มี License แล้ว? ดูคีย์ของคุณ →</a>
                </p>
            @endif
            <p class="text-center text-xs lvp-muted mt-6">ซื้อแล้วได้ License key ทางอีเมลและในหน้า บัญชีของฉัน · ใส่คีย์ในแอปได้เลย ไม่ต้องลงใหม่ · ชำระผ่าน PromptPay หรือโอนธนาคาร</p>
        </div>
    </section>

    {{-- =========================== DOWNLOAD =========================== --}}
    <section class="lvp-section lvp-section-alt" id="download">
        <div class="lvp-wrap">
            <div class="text-center max-w-3xl mx-auto mb-10">
                <p class="lvp-eyebrow mb-3">Download</p>
                <h2 class="text-3xl md:text-5xl font-bold mb-4">ดาวน์โหลด LocalVPN</h2>
                <p class="lvp-muted">ไฟล์จาก xman4289.com โดยตรง ใช้ไฟล์เดียวกันทั้งรุ่นฟรีและ Pro</p>
            </div>
            <div class="grid md:grid-cols-2 gap-6 max-w-5xl mx-auto">
                <div class="lvp-glass p-7 flex flex-col">
                    <div class="flex items-center gap-4 mb-5">
                        <img src="{{ $img('icons/device-pc.webp') }}" alt="" width="68" height="68" class="lvp-icon" loading="lazy">
                        <div>
                            <h3 class="text-2xl font-bold">Windows</h3>
                            <p class="text-sm lvp-muted">ตัวติดตั้ง .exe</p>
                        </div>
                    </div>
                    <ul class="space-y-2.5 text-sm mb-7 flex-1">
                        <li class="flex gap-3"><span class="lvp-tick">✓</span><span>Windows 10 หรือ 11 แบบ 64-bit (x64)</span></li>
                        <li class="flex gap-3"><span class="lvp-tick">✓</span><span>ขอสิทธิ์ผู้ดูแลระบบครั้งเดียวตอนติดตั้ง เพื่อลงบริการเครือข่ายและการ์ดเครือข่ายเสมือน (Wintun)</span></li>
                        <li class="flex gap-3"><span class="lvp-tick">✓</span><span>ต่ออินเทอร์เน็ตตอนเปิดครั้งแรก เพื่อลงทะเบียนเครื่องและเริ่มทดลอง Pro</span></li>
                    </ul>
                    <a href="{{ $windowsUrl }}" class="lvp-btn w-full text-lg">ดาวน์โหลดสำหรับ Windows</a>
                </div>
                <div class="lvp-glass p-7 flex flex-col">
                    <div class="flex items-center gap-4 mb-5">
                        <img src="{{ $img('icons/device-phone.webp') }}" alt="" width="68" height="68" class="lvp-icon" loading="lazy">
                        <div>
                            <h3 class="text-2xl font-bold">Android</h3>
                            <p class="text-sm lvp-muted">ไฟล์ APK</p>
                        </div>
                    </div>
                    <ul class="space-y-2.5 text-sm mb-7 flex-1">
                        <li class="flex gap-3"><span class="lvp-tick">✓</span><span>Android 7.0 ขึ้นไป</span></li>
                        <li class="flex gap-3"><span class="lvp-tick">✓</span><span>อนุญาตการติดตั้งแอปจากไฟล์ (APK) ในเครื่องครั้งแรก</span></li>
                        <li class="flex gap-3"><span class="lvp-tick">✓</span><span>อนุญาตการเชื่อมต่อ VPN เมื่อแอปถาม</span></li>
                    </ul>
                    <a href="{{ $androidUrl }}" class="lvp-btn-glass w-full text-lg">ดาวน์โหลดสำหรับ Android</a>
                </div>
            </div>
            <p class="mt-6 text-center text-sm lvp-muted">
                <img src="{{ $img('icons/update.webp') }}" alt="" width="24" height="24" class="inline-block w-6 h-6 align-middle mr-1" loading="lazy">
                แอปอัปเดตตัวเองจาก xman4289.com ทั้งรุ่นฟรีและ Pro ·
                <a href="{{ route('localvpn.install-guide') }}" class="lvp-aqua hover:text-white underline">วิธีติดตั้งทีละขั้น</a>
            </p>
        </div>
    </section>

    {{-- ============================== FAQ ============================== --}}
    <section class="lvp-section" id="faq">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-10">
                <p class="lvp-eyebrow mb-3">FAQ</p>
                <h2 class="text-3xl md:text-4xl font-bold">คำถามที่พบบ่อย</h2>
            </div>
            <div class="space-y-3">
                @foreach([
                    ['ทดลอง Pro ฟรีทำอย่างไร?', 'เปิดแอปครั้งแรกตอนต่ออินเทอร์เน็ตแล้วกดเริ่มทดลอง ได้ใช้ Pro ครบทุกฟีเจอร์ 30 วัน ไม่ต้องผูกบัตร สิทธิ์ทดลองมีครั้งเดียวต่อเครื่อง — ลบแอปลงใหม่หรือลง Windows ใหม่ก็ไม่ได้สิทธิ์ซ้ำ'],
                    ['ครบ 30 วันแล้วเป็นอย่างไร?', 'แอปกลับมาเป็นรุ่นฟรีเองโดยไม่ต้องทำอะไร ยังสร้าง/เข้าวง LAN ได้ (สูงสุด 5 คนต่อวง) ส่งไฟล์และใช้ Torrent ได้ ใช้ VPN ประเทศฟรีได้ และยังได้รับอัปเดตอัตโนมัติ ส่วนลดปิงเกม สมาชิก 50 คน และ VPN ทุกประเทศต้องใช้ Pro'],
                    ['ลดปิงได้จริงไหม?', 'ลดได้เฉพาะเมื่อเส้นทางผ่านรีเลย์ดีกว่าเส้นทางตรงของเน็ตคุณ เช่น ตอนที่ผู้ให้บริการส่งข้อมูลอ้อม แอปวัดให้เห็นก่อนทุกครั้งและบอกตรง ๆ ถ้าทางตรงดีที่สุดอยู่แล้ว ปิงไม่มีทางต่ำกว่าระยะทางจริงถึงเซิร์ฟเวอร์เกม บน Windows ยังช่วยลดอาการปิงกระตุกจาก Wi-Fi และการประหยัดพลังงานได้'],
                    ['1 คีย์ใช้ได้กี่เครื่อง?', '1 คีย์ใช้ได้ ' . $devices . ' เครื่องพร้อมกัน เช่น คอม 1 เครื่อง + มือถือ 1 เครื่อง ถ้าจะย้ายไปเครื่องใหม่ให้กดยกเลิก License บนเครื่องเดิม หรือย้ายจากเครื่องใหม่ได้เลย'],
                    ['Pro ราคาเท่าไร?', 'รายเดือน ฿' . number_format($prices['monthly'] ?? 0) . ' · รายปี ฿' . number_format($prices['yearly'] ?? 0) . ' · ตลอดชีพ ฿' . number_format($prices['lifetime'] ?? 0) . ' — ไม่ตัดเงินอัตโนมัติ หมดอายุแล้วแอปกลับเป็นรุ่นฟรี'],
                    ['ใช้กับเกมอะไรได้บ้าง?', 'เกมที่มีโหมด LAN หรือให้ต่อด้วย IP โดยตรง เล่นข้ามบ้านได้เหมือนนั่งข้างกัน ส่วนตัวลดปิงใช้กับเกมออนไลน์ที่มีเซิร์ฟเวอร์ในภูมิภาคที่เลือก'],
                    ['ทำไม Windows ต้องขอสิทธิ์ผู้ดูแลระบบ?', 'ตอนติดตั้งต้องลงบริการเครือข่ายและการ์ดเครือข่ายเสมือน (Wintun) ซึ่ง Windows อนุญาตเฉพาะผู้ดูแลระบบ ขอครั้งเดียวตอนติดตั้ง'],
                    ['ข้อมูลของฉันไปที่ไหน?', 'ไฟล์และทราฟฟิกในวงส่งตรงระหว่างเครื่องแบบ P2P เมื่อทำได้ ถ้าต่อตรงไม่ได้จึงผ่านรีเลย์ แอปติดต่อ xman4289.com เพื่อลงทะเบียนเครื่อง ตรวจ License จัดการวง และเช็กอัปเดต'],
                ] as [$q, $a])
                    <details class="lvp-faq lvp-glass-soft px-5 py-4">
                        <summary class="flex items-center justify-between gap-4 font-semibold text-white">
                            <span>{{ $q }}</span>
                            <span class="pm flex-none w-7 h-7 rounded-lg border border-white/20 flex items-center justify-center lvp-aqua text-lg leading-none" aria-hidden="true">+</span>
                        </summary>
                        <p class="mt-3 lvp-muted leading-relaxed">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- =========================== FINAL CTA =========================== --}}
    <section class="lvp-section lvp-hero">
        <div class="lvp-hero-bg" aria-hidden="true" style="opacity:.55"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
            <img src="{{ $img('logo.webp') }}" alt="" width="88" height="88" class="w-20 h-20 mx-auto mb-5" loading="lazy">
            <h2 class="text-3xl md:text-5xl font-bold mb-4">ชวนเพื่อนมาเข้าวงเดียวกัน</h2>
            <p class="text-lg lvp-muted mb-8">ทดลอง Pro ฟรี 30 วันบนทุกเครื่องใหม่ แล้วเลือกเองว่าจะใช้รุ่นฟรีต่อหรืออัป Pro เริ่ม ฿{{ $monthly }}/เดือน</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ $windowsUrl }}" class="lvp-btn text-lg">ดาวน์โหลด Windows</a>
                <a href="{{ $androidUrl }}" class="lvp-btn-glass text-lg">ดาวน์โหลด Android</a>
                <a href="#pricing" class="lvp-btn-gold text-lg">ดูแผน Pro</a>
            </div>
            <p class="lvp-muted text-sm mt-7">Windows 10/11 x64 · Android 7.0+ · ทดลอง Pro 30 วัน · 1 คีย์ = {{ $devices }} เครื่อง</p>
            <a href="{{ route('products.index') }}" class="inline-block mt-4 text-sm lvp-aqua hover:text-white"><x-bi th="ดูผลิตภัณฑ์อื่นของ XMAN Studio" en="More from XMAN Studio" /> →</a>
        </div>
    </section>

</div>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
