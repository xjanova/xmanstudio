@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย (กฎเจ้าของ 2026-10-12) --}}
@php
    $csDownload = \App\Models\Product::slugIsComingSoon('localvpn');
    // ภาพชุด v2 (scripts/product-mockups/build.py localvpn) — key art / og ใช้เมื่อไฟล์มีแล้วเท่านั้น หน้านี้ไม่ชี้ไปหาไฟล์ที่ไม่มี
    $v2 = fn (string $file) => is_file(public_path('images/products/localvpn/v2/' . $file)) ? asset('images/products/localvpn/v2/' . $file) : null;
    $ogImage = $v2('og.jpg') ?? asset('images/products/localvpn/og.jpg');
@endphp

@section('title', 'LocalVPN — LAN เสมือนผ่านอินเทอร์เน็ต + ลดปิงเกม · Windows และ Android · ทดลอง Pro ฟรี 30 วัน · Pro เริ่ม ฿' . number_format(\App\Support\LicensePlans::price('localvpn', 'monthly') ?? 0) . '/เดือน | XMAN Studio')
@section('meta_description', 'LocalVPN ต่อคอมและมือถือของเพื่อนให้อยู่วง LAN เดียวกันผ่านอินเทอร์เน็ต เล่นเกมโหมด LAN และส่งไฟล์ถึงกันแบบเข้ารหัส ฟรี 5 คนต่อวง · Pro เพิ่มวงละ 50 คน ลดปิงเกมที่วัดเส้นทางจริงก่อน ปรับแต่ง Windows และ VPN ทุกประเทศ ทดลอง Pro ฟรี 30 วัน · Virtual LAN over the internet for Windows and Android.')
@section('og_image', $ogImage)

@section('content')
@php
    // ราคาทุกแผนมาจาก config/licenses.php 'plans' ที่เดียว (ตะกร้าคิดตามนี้) — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $prices = \App\Support\LicensePlans::for('localvpn');
    $monthly = number_format($prices['monthly'] ?? 0);
    $yearlySaving = \App\Support\LicensePlans::yearlySaving('localvpn');
    $devices = \App\Support\LicensePlans::devicesPerKey('localvpn');

    // ปุ่มซื้อต้องมีเสมอ — แอปส่งลูกค้ามาหน้านี้ (/localvpn#monthly …) และคนที่ซื้อแล้วก็ซื้อคีย์เพิ่มได้
    $ownsLocalVpn = auth()->check() && $hasPurchased;

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว — เปิดหน้านี้ต้องไม่ถามแหล่งไฟล์ภายนอก)
    $latest = $product->latestVersion();

    // ดาวน์โหลดจาก xman4289.com เท่านั้น — ตัวติดตั้ง Windows ใช้ route ของมันเมื่อเว็บมีแล้ว ไม่งั้นหน้าโหลดของ LocalVPN
    $windowsUrl = \Illuminate\Support\Facades\Route::has('localvpn.download.windows')
        ? route('localvpn.download.windows')
        : route('localvpn.download');
    $androidUrl = route('localvpn.download.apk');
    // release ที่ไม่มีตัวติดตั้ง .exe (เช่น release ที่มีแต่ APK) = ยังไม่มีอะไรให้ Windows — ไม่ยื่นลิงก์ที่พาไปเจอ error
    // (ตัวเดียวกับที่หน้า /localvpn/download และ update/check?platform=windows ใช้)
    $windowsReady = ! $latest || \App\Http\Controllers\LocalVpnWebController::windowsInstaller($latest) !== null;

    // ภาพหน้าจอจริงจากแอป (ข้อมูลตัวอย่างของตัวจับภาพในแอป · เบลอเลขเวอร์ชันเก่าแล้ว)
    $shot = fn (string $file) => asset('images/products/localvpn/v2/' . $file);
    $artHero = $v2('art-hero.webp');
    $art2 = $v2('art-2.webp');

    $plans = [
        'monthly' => ['name' => 'รายเดือน', 'en' => 'Monthly', 'unit' => '/ เดือน', 'note' => 'ซื้อทีละ 30 วัน ไม่ตัดเงินอัตโนมัติ', 'days' => '30 วัน'],
        'yearly' => ['name' => 'รายปี', 'en' => 'Yearly', 'unit' => '/ ปี', 'note' => $yearlySaving ? 'ถูกกว่ารายเดือน ' . $yearlySaving . '%' : 'จ่ายครั้งเดียวใช้ทั้งปี', 'days' => '365 วัน'],
        'lifetime' => ['name' => 'ตลอดชีพ', 'en' => 'Lifetime', 'unit' => 'ครั้งเดียว', 'note' => 'จ่ายครั้งเดียว ใช้ได้ตลอด', 'days' => 'ไม่หมดอายุ'],
    ];

    // ใช้ได้ในรุ่นฟรี — ข้อเท็จจริงจากโค้ดแอป (LicenseState.maxNetworkMembers) และเซิร์ฟเวอร์ (LocalVpnRelayController::join)
    $coreRows = [
        [
            'img' => $shot('network.webp'), 'name' => 'วง LAN เสมือน ข้ามบ้าน', 'eyebrow' => 'Virtual LAN', 'tier' => 'free',
            'th' => 'สร้างวงหรือเข้าวงเพื่อนด้วยรหัสวง ทุกเครื่องในวงได้ IP ชุดเดียวกัน (เช่น 10.10.7.x) และมองเห็นกันเหมือนเสียบสาย LAN เดียวกัน เปิดเกมแล้วเลือกโหมด LAN หรือต่อด้วย IP ได้เลย คอม Windows กับมือถือ Android อยู่วงเดียวกันได้',
            'en' => 'Create a network or join a friend\'s with its code. Every device gets an address on one subnet and sees the others as if on one cable.',
            'points' => ['วงส่วนตัวตั้งรหัสผ่าน หรือเปิดเป็นวงสาธารณะ', 'รุ่นฟรีรับได้ 5 คนต่อวง · วงของผู้ใช้ Pro รับได้ 50 คน', 'หน้าสมาชิกบอกว่าแต่ละเครื่องต่อตรง P2P หรือผ่านรีเลย์'],
            'alt' => 'หน้ารายละเอียดวงของ LocalVPN: เชื่อมต่อ LAN แล้ว Virtual IP 10.10.7.2 สมาชิกออนไลน์ 4 จาก 6 เครื่อง แต่ละเครื่องต่อตรง P2P หรือผ่านรีเลย์',
        ],
        [
            'img' => $shot('networks.webp'), 'name' => 'ล็อบบี้วงสาธารณะ', 'eyebrow' => 'Find a game', 'tier' => 'free',
            'th' => 'ไม่มีเพื่อนในวง? ค้นหาวงสาธารณะที่เปิดอยู่จากชื่อวง คำอธิบาย หรือรหัสวง กรองเฉพาะวงที่มีคนออนไลน์ แล้วกดเข้าร่วมได้ทันที',
            'en' => 'Browse public networks by name, description or code, filter to the ones with players online, and join in one click.',
            'points' => ['เรียงตามคนออนไลน์มากสุด ใหม่ล่าสุด หรือชื่อวง', 'วงส่วนตัวขึ้นรูปกุญแจ ต้องใช้รหัสผ่านก่อนเข้า'],
            'alt' => 'หน้าเครือข่าย LAN ของ LocalVPN ธีมท้องฟ้า: ช่องค้นหาวง ตัวกรอง และรายการวงสาธารณะพร้อมจำนวนสมาชิกที่ออนไลน์',
        ],
        [
            'img' => $shot('files.webp'), 'name' => 'ส่งไฟล์ถึงเพื่อนในวง', 'eyebrow' => 'P2P file transfer', 'tier' => 'free',
            'th' => 'เลือกไฟล์ (ได้หลายไฟล์) แล้วทุกเครื่องในวงเห็นและกดรับได้ทันที ส่งตรงถึงกันแบบ P2P ถ้าต่อตรงไม่ได้จะส่งผ่านรีเลย์ให้อัตโนมัติ และนอกวงยังมี Torrent ชุมชน แชร์ไฟล์แยกหมวดกับผู้ใช้ LocalVPN คนอื่น พร้อมอันดับนักแชร์และถ้วยรางวัล',
            'en' => 'Send files to everyone in the network, directly or through the relay — plus a community torrent library with leaderboards.',
            'points' => ['ไฟล์ในวงเข้ารหัสระหว่างเครื่อง เหมือนทราฟฟิก LAN', 'ไฟล์ที่เพื่อนแชร์ในวง ยิ่งมีคนแชร์ต่อยิ่งโหลดเร็ว'],
            'alt' => 'หน้าส่งไฟล์ของ LocalVPN ธีมท้องฟ้า: กล่องเลือกไฟล์ รายชื่อผู้รับในวงที่ต่อตรงหรือผ่านรีเลย์ และไฟล์ที่เพื่อนแชร์มา',
        ],
    ];

    // Pro — ทุกข้อเช็กกับด่านในโค้ด: GameBoostScreen._requirePro, VpnProxy/WireguardController (FREE_COUNTRIES),
    // LocalVpnRelayController (5 คน), FleetPolicy (relay tiers)
    $proTools = [
        [
            'key' => 'boost', 'name' => 'ลดปิงเกม', 'short' => 'วัดจริงก่อน แนะนำเมื่อเร็วกว่า', 'tier' => 'pro',
            'img' => $shot('boost.webp'),
            'th' => 'วัดเส้นทางจริงไป 11 ภูมิภาคของเซิร์ฟเวอร์เกม (สิงคโปร์ ฮ่องกง ญี่ปุ่น เกาหลีใต้ อินเดีย ออสเตรเลีย สหรัฐฯ สองฝั่ง ยุโรป อังกฤษ ตะวันออกกลาง) เทียบเส้นทางตรงของเน็ตคุณกับรีเลย์ใกล้เคียง ทั้งปิงเฉลี่ย ความกระชาก (jitter) และแพ็กเก็ตหาย แล้วแนะนำรีเลย์เฉพาะเมื่อมันเร็วกว่าจริง',
            'en' => 'Measures the real path to the game\'s region, direct and through nearby relays, and recommends a relay only when it wins.',
            'points' => ['วัดปิงได้ฟรีทุกคน — การสลับเส้นทางเป็นของ Pro', 'ถ้าทางตรงดีกว่า แอปเตือนก่อนว่ารีเลย์อาจทำให้ปิงสูงขึ้น', 'มีรายการเกมยอดนิยมให้เลือก หรือใส่ปลายทางเองได้', 'บน Windows ต้องเปิดแอปแบบผู้ดูแลระบบ'],
            'alt' => 'หน้าลดปิงเกมของ LocalVPN: กำลังลดปิงผ่านสิงคโปร์ ปิง 31 ms กระชาก 3 ms แพ็กเก็ตหาย 0% พร้อมกราฟปิงและรายการเกม',
        ],
        [
            'key' => 'tuneup', 'name' => 'ปรับแต่ง Windows ระหว่างเล่น', 'short' => 'Wi-Fi · พลังงาน · Nagle', 'tier' => 'pro',
            'img' => $shot('tuneup.webp'),
            'th' => 'ลดอาการปิงกระชากที่มาจากเครื่องเอง: หยุดการสแกนหา Wi-Fi เบื้องหลังที่ทำให้ปิงกระชาก 100–300 ms ราวนาทีละครั้ง ตั้งแผนพลังงานประสิทธิภาพสูงระหว่างลดปิง และปิด Nagle สำหรับเกมที่ใช้ TCP',
            'en' => 'Stops background Wi-Fi scans, switches to a high-performance power plan and turns off Nagle for TCP games — each one reversible.',
            'points' => ['Wi-Fi และแผนพลังงานคืนค่าเดิมเองเมื่อปิดโหมดลดปิง', 'Nagle เปิดกลับได้ทุกเมื่อจากสวิตช์เดียวกัน', 'ไม่มี "ล้างแรม" หรือแต่งรีจิสทรีที่ไม่ได้ทำให้ปิงลดจริง', 'Windows เท่านั้น · ต้องเปิดแอปแบบผู้ดูแลระบบ'],
            'alt' => 'หน้าลดปิงเกมของ LocalVPN ธีมท้องฟ้า แสดงการ์ดปรับแต่ง Windows: หยุดสแกน Wi-Fi แผนพลังงานสูง และปิด Nagle',
        ],
        [
            'key' => 'members', 'name' => 'วงใหญ่ 50 คน', 'short' => 'รุ่นฟรี 5 คนต่อวง', 'tier' => 'pro',
            'th' => 'วงที่เจ้าของวงใช้ Pro (หรือกำลังทดลอง Pro) รับสมาชิกได้สูงสุด 50 คน ส่วนวงของผู้ใช้ฟรีรับได้ 5 คน — นับตามสิทธิ์ของเจ้าของวงคนเดียว เพื่อนที่เข้าวงไม่ต้องซื้อ Pro',
            'en' => 'A network owned by a Pro user takes up to 50 members; a free owner\'s network stops at 5. Only the owner needs Pro.',
            'points' => ['ตั้งจำนวนสมาชิกสูงสุดของวงเองได้ตอนสร้างวง'],
        ],
        [
            'key' => 'vpn', 'name' => 'VPN ทุกประเทศ', 'short' => 'WireGuard · ฟรีเฉพาะบางประเทศ', 'tier' => 'pro',
            'img' => $shot('vpn.webp'),
            'th' => 'เปลี่ยนประเทศของอินเทอร์เน็ตผ่าน WireGuard เพื่อเข้าเกมหรือเว็บของต่างประเทศ ดูปิงและโหลดของแต่ละเซิร์ฟเวอร์ก่อนเลือก รุ่นฟรีเลือกได้เฉพาะประเทศกลุ่มฟรี ส่วน Pro ปลดทุกประเทศที่มีเซิร์ฟเวอร์เปิดอยู่',
            'en' => 'Change your internet\'s country over WireGuard. Free covers a set of countries; Pro unlocks every country with a server.',
            'points' => ['เชื่อมต่อด่วนกับเซิร์ฟเวอร์ที่ดีที่สุดได้ในปุ่มเดียว', 'บน Windows ต้องเปิดแอปแบบผู้ดูแลระบบ · Android ขออนุญาต VPN ครั้งแรก'],
            'alt' => 'หน้า VPN ประเทศของ LocalVPN ธีมท้องฟ้า: รายการประเทศพร้อมปิงและโหลดของเซิร์ฟเวอร์',
        ],
        [
            'key' => 'relay', 'name' => 'รีเลย์เต็มกำลัง', 'short' => 'เมื่อต่อตรงไม่ได้', 'tier' => 'pro',
            'th' => 'เครือข่ายบางแบบต่อตรงถึงกันไม่ได้ (เช่น NAT ของมือถือบางเครือข่าย) ข้อมูลจึงต้องผ่านรีเลย์ของเรา ผู้ใช้ Pro ได้แบนด์วิดท์และโควตารายวันของรีเลย์สูงกว่ารุ่นฟรี เล่นเกมและส่งไฟล์ผ่านรีเลย์ได้ลื่นกว่า',
            'en' => 'When a direct path is impossible, traffic goes through our relays — Pro gets more relay bandwidth and a larger daily allowance.',
            'points' => ['ทราฟฟิกผ่านรีเลย์ยังเข้ารหัสระหว่างเครื่อง รีเลย์อ่านไม่ได้'],
        ],
    ];

    // ตารางเทียบ — ตรงกับ LicenseState.isPaid ในแอปและด่าน Pro ฝั่งเซิร์ฟเวอร์
    $compare = [
        ['วง LAN · Virtual LAN', [
            ['สร้างวง / เข้าวง (สาธารณะ หรือตั้งรหัสผ่าน) · วงละ 5 คน', false],
            ['วงละสูงสุด 50 คน', true],
            ['ต่อตรง P2P ทะลุ NAT · รีเลย์สำรองอัตโนมัติ', false],
            ['เข้ารหัสระหว่างเครื่อง (X25519 + ChaCha20-Poly1305)', false],
            ['แบนด์วิดท์และโควตารีเลย์สูงขึ้น', true],
        ]],
        ['ไฟล์ · Files', [
            ['ส่งไฟล์ในวง (P2P)', false],
            ['Torrent ชุมชน · อันดับนักแชร์', false],
        ]],
        ['เกมและ VPN · Games & VPN', [
            ['วัดปิงไปภูมิภาคเซิร์ฟเวอร์เกม', false],
            ['ลดปิงเกมผ่านรีเลย์', true],
            ['ปรับแต่ง Windows (Wi-Fi · พลังงาน · Nagle)', true],
            ['VPN ประเทศ — กลุ่มประเทศฟรี', false],
            ['VPN ประเทศ — ทุกประเทศ', true],
        ]],
        ['ทั่วไป · General', [
            ['Windows + Android วงเดียวกัน', false],
            ['ธีมกลางคืน / ท้องฟ้า · อัปเดตอัตโนมัติ', false],
        ]],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'LocalVPN',
        'operatingSystem' => 'Windows 10, Windows 11, Android 7.0+',
        'applicationCategory' => 'NetworkingApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'LAN เสมือนผ่านอินเทอร์เน็ตสำหรับ Windows และ Android ส่งไฟล์แบบเข้ารหัส ลดปิงเกมที่วัดเส้นทางจริงก่อน และ VPN เลือกประเทศ',
        'image' => $ogImage,
        'url' => route('products.show', 'localvpn'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => array_values(array_merge(
            [['@type' => 'Offer', 'name' => 'Free', 'price' => '0', 'priceCurrency' => 'THB']],
            collect($prices)->map(fn ($price, $term) => ['@type' => 'Offer', 'name' => 'Pro (' . ($plans[$term]['en'] ?? $term) . ')', 'price' => (string) $price, 'priceCurrency' => 'THB'])->values()->all(),
        )),
    ];

    $winIcon = '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 5.1 10.4 4v7.1H3V5.1Zm0 13.8 7.4 1.1v-7H3v5.9ZM11.3 3.9 21 2.5v8.6h-9.7V3.9Zm0 16.2 9.7 1.4v-8.5h-9.7v7.1Z"/></svg>';
    $androidIcon = '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.6 9.48 19.44 6.3a.38.38 0 0 0-.66-.38l-1.86 3.22a11.4 11.4 0 0 0-9.84 0L5.22 5.92a.38.38 0 0 0-.66.38L6.4 9.48A10.8 10.8 0 0 0 1 18h22a10.8 10.8 0 0 0-5.4-8.52ZM7 15.25a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5Zm10 0a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5Z"/></svg>';
@endphp
<x-xp.page a="#38d9f5" b="#8ef0ff" c="#6fd8ff" glow="56 217 245" g1="#38d9f5" g2="#60a5fa" g3="#f2c75c">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$artHero"
               tagline="LAN เสมือนผ่านอินเทอร์เน็ต"
               tagline-en="A virtual LAN over the internet, for Windows and Android."
               lead="ต่อคอมกับมือถือของเพื่อนให้อยู่วง LAN เดียวกันจากคนละบ้าน เล่นเกมโหมด LAN และส่งไฟล์ถึงกันแบบเข้ารหัส พร้อมตัวลดปิงเกมที่วัดเส้นทางจริงก่อนแนะนำ และ VPN เลือกประเทศ"
               lead-en="Put your friends' PCs and phones on one LAN from different homes — play LAN games, share files, and cut game ping where a better route exists.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'LocalVPN ' . $latest->version : 'LocalVPN' }}</span>
            <span class="xp-chip">Windows 10/11 · Android 7.0+</span>
        </x-slot:chips>
        <x-slot:title>Local<span class="xp-text-glow">VPN</span></x-slot:title>
        <x-slot:actions>
            @if($csDownload)
                <x-xp.download :href="$windowsUrl" :coming-soon="true" />
            @else
                @if($windowsReady)
                    <a href="{{ $windowsUrl }}" class="xp-btn-main inline-flex items-center gap-2 px-6 py-4 rounded-xl font-extrabold transition">{!! $winIcon !!}<x-bi th="ดาวน์โหลดสำหรับ Windows" en="Windows 10/11 · 64-bit" /></a>
                @endif
                <a href="{{ $androidUrl }}" class="{{ $windowsReady ? 'xp-btn-ghost font-bold' : 'xp-btn-main font-extrabold' }} inline-flex items-center gap-2 px-6 py-4 rounded-xl transition">{!! $androidIcon !!}<x-bi th="ดาวน์โหลดสำหรับ Android" en="APK · Android 7.0+" /></a>
            @endif
            <a href="#pricing" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">
                {{ $ownsLocalVpn ? 'ซื้อ License เพิ่ม' : 'ซื้อ Pro' }} — เริ่ม ฿{{ $monthly }}/เดือน
            </a>
        </x-slot:actions>
        <x-slot:notes>
            @if($latest)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด {{ $latest->version }} · ไฟล์จาก xman4289.com โดยตรง · ไฟล์เดียวกันทั้งรุ่นฟรีและ Pro</p>
            @endif
            @if(! $csDownload && ! $windowsReady)
                <p class="mt-2 text-sm text-amber-100/80" data-windows-pending>ตัวติดตั้ง Windows ยังไม่อยู่ในเวอร์ชันที่เปิดให้ดาวน์โหลดตอนนี้ — มีเฉพาะไฟล์ Android</p>
            @endif
            @if($ownsLocalVpn)
                <p class="mt-3 text-sm text-gray-300">
                    คุณมี License แล้ว · 1 คีย์ใช้ได้ {{ $devices }} เครื่อง ·
                    <a href="{{ route('customer.licenses') }}" class="xp-link font-semibold underline">ดูคีย์ License ของฉัน</a>
                </p>
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="free" /> 5 คนต่อวง ใช้ได้ตลอด</li>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="pro" /> ทดลองฟรี 30 วัน</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> คอม + มือถือ วงเดียวกัน</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('home.webp')" :eager="true" :width="1424" :height="891"
                             alt="หน้าภาพรวมของ LocalVPN บน Windows: เชื่อมต่อ LAN แล้ว สมาชิกออนไลน์ 4 จาก 6 เครื่อง ต่อตรง P2P 2 เครื่อง ปิงเกม 31 ms ผ่านสิงคโปร์" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">ต่อตรงก่อน · Direct P2P first</p>
                <p class="text-white font-extrabold text-lg leading-tight">เข้ารหัส<span class="text-cyan-300">ทุกแพ็กเก็ต</span></p>
            </div>
            <div class="xp-float-2 absolute -right-2 sm:-right-6 -top-5 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">1 คีย์ · One key</p>
                <p class="text-white font-bold leading-tight">คอม + มือถือ<br><span class="text-gray-400 text-sm font-normal">ใช้พร้อมกัน {{ $devices }} เครื่อง</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================== FREE CORE ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Free · works forever" title="เพื่อนต่างบ้าน" title2="เล่นด้วยกันเหมือนนั่งข้างกัน"
                       sub="รุ่นฟรีสร้างวง LAN เสมือน ต่อตรงแบบ P2P เข้ารหัสระหว่างเครื่อง ส่งไฟล์ในวง และใช้ Torrent ชุมชนได้ ไม่มีวันหมดอายุ"
                       en="The free edition gives you the virtual LAN, direct encrypted P2P, file transfer and the community torrent — with no time limit." />
            <x-xp.rows :items="$coreRows" />
        </div>
    </section>

    {{-- =========================== PRO TOOLS =========================== --}}
    <section class="relative py-20 isolate" id="ping">
        @if($art2)
            <x-xp.art :src="$art2" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        @endif
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="Pro · for players" title="ลดปิงเมื่อลดได้จริง" title2="บอกตรง ๆ เมื่อลดไม่ได้"
                       sub="ปิงที่สูงเกินมักมาจากเส้นทางอ้อม รีเลย์บนเส้นทางที่ดีกว่าช่วยตัดทางอ้อมนั้นได้ แต่ไม่มีโปรแกรมไหนทำให้ข้อมูลวิ่งเร็วกว่าระยะทางจริง Pro ปลดการลดปิง การปรับแต่ง Windows วงใหญ่ 50 คน และ VPN ทุกประเทศ"
                       en="Pro unlocks the ping booster, Windows tune-ups, 50-member networks and every VPN country." />
            <x-xp.explorer id="pro" label="ฟีเจอร์ Pro ของ LocalVPN" :tools="$proTools" />
        </div>
    </section>

    {{-- ============================ TRUST ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Private by design" title="ต่อถึงกันตรง ๆ" title2="และไม่มีใครแอบอ่าน"
                       sub="ข้อมูลในวงวิ่งระหว่างเครื่องของคุณกับเพื่อน เซิร์ฟเวอร์ของเราแค่ช่วยให้หากันเจอ"
                       en="Traffic in a network runs between your devices; our servers only help them find each other." />
            <x-xp.cards :items="[
                ['icon' => 'lock', 'title' => 'เข้ารหัสระหว่างเครื่อง', 'body' => 'ทุกแพ็กเก็ตและไฟล์ระหว่างสมาชิก ทั้งต่อตรงและผ่านรีเลย์ เข้ารหัสด้วย X25519 + ChaCha20-Poly1305 เซิร์ฟเวอร์ส่งต่อแค่กุญแจสาธารณะ', 'en' => 'End-to-end encrypted between members, relay included'],
                ['icon' => 'bolt', 'title' => 'ต่อตรงก่อน รีเลย์สำรอง', 'body' => 'พยายามต่อตรงทะลุ NAT ก่อนเสมอ เครือข่ายไหนต่อตรงไม่ได้จึงส่งผ่านรีเลย์ และหน้าสมาชิกบอกชัดว่าแต่ละเครื่องต่อแบบไหน', 'en' => 'Direct P2P first, relay only when needed'],
                ['icon' => 'chip', 'title' => 'การ์ดเครือข่ายจริงบน Windows', 'body' => 'บริการ LocalVPNService ดูแลการ์ดเครือข่ายเสมือน (Wintun) ให้ เกมเห็นวง LAN เหมือนการ์ดแลนจริง ขอสิทธิ์ผู้ดูแลครั้งเดียวตอนติดตั้ง', 'en' => 'A real adapter, run by a Windows service'],
                ['icon' => 'chart', 'title' => 'ลดปิงแบบซื่อตรง', 'body' => 'วัดก่อนทุกครั้ง ถ้าเส้นทางตรงของคุณดีที่สุดอยู่แล้ว แอปบอกให้ใช้ทางตรง ไม่เปิดรีเลย์ให้ปิงแย่ลง', 'en' => 'Measures first, says so when direct is best'],
                ['icon' => 'undo', 'title' => 'ปรับแต่งแล้วคืนค่าได้', 'body' => 'การปรับแต่ง Windows ทุกอย่างย้อนกลับได้ และ Wi-Fi กับแผนพลังงานคืนค่าเดิมเองเมื่อปิดโหมดลดปิง', 'en' => 'Every tune-up is reversible'],
                ['icon' => 'download', 'title' => 'อัปเดตที่ตรวจไฟล์ก่อนติดตั้ง', 'body' => 'แอปอัปเดตตัวเองจาก xman4289.com และเทียบ sha256 ของไฟล์กับที่เซิร์ฟเวอร์ประกาศ ไม่ตรงก็ไม่ติดตั้ง', 'en' => 'Updates are checked before they install'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    {{-- the app's "buy" links point at /localvpn#monthly, #yearly, #lifetime — /localvpn redirects here, each card carries that id --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="เริ่มฟรี" title2="อยากได้ครบค่อยอัป Pro"
                       :sub="'ไฟล์เดียวกันทั้งรุ่นฟรีและ Pro · 1 คีย์ใช้ได้ ' . $devices . ' เครื่องพร้อมกัน (เช่น คอม + มือถือ) · เครื่องใหม่ได้ทดลอง Pro ฟรี 30 วัน (ครั้งเดียวต่อเครื่อง)'"
                       en="One download. One key for a PC and a phone. Every new device tries Pro free for 30 days first." />

            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-5 max-w-6xl mx-auto">
                <div class="xp-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">Free</h3>
                        <x-xp.badge tier="free" />
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="ใช้ได้ตลอด ไม่มีวันหมดอายุ" en="No time limit" /></p>
                    <div class="mb-6"><span class="text-5xl font-black text-white">฿0</span></div>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['วง LAN เสมือน 5 คนต่อวง', 'ต่อตรง P2P + เข้ารหัสระหว่างเครื่อง', 'ส่งไฟล์ในวง + Torrent ชุมชน', 'วัดปิง · VPN กลุ่มประเทศฟรี', 'อัปเดตอัตโนมัติ']" />
                    {{-- ไฟล์เดียวกับ Pro — Pro ปลดในแอปด้วย License key --}}
                    <div class="grid gap-3">
                        @if($csDownload)
                            <x-xp.download block :href="$windowsUrl" :coming-soon="true" />
                        @else
                            @if($windowsReady)
                                <a href="{{ $windowsUrl }}" class="xp-btn-main block w-full py-3.5 text-center rounded-xl font-extrabold transition">ดาวน์โหลด Windows</a>
                            @endif
                            <a href="{{ $androidUrl }}" class="{{ $windowsReady ? 'xp-btn-ghost font-bold' : 'xp-btn-main font-extrabold' }} block w-full py-3.5 text-center rounded-xl transition">ดาวน์โหลด Android</a>
                        @endif
                    </div>
                </div>

                @foreach($plans as $term => $plan)
                    @continue(! isset($prices[$term]))
                    @php($featured = $term === 'yearly')
                    <div id="{{ $term }}" class="{{ $featured ? 'xp-featured' : 'xp-card' }} scroll-mt-24">
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
                            <x-xp.ticks pro class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ทุกอย่างในรุ่นฟรี', 'ลดปิงเกม + ปรับแต่ง Windows', 'วงละสูงสุด 50 คน · VPN ทุกประเทศ', '1 คีย์ใช้ได้ ' . $devices . ' เครื่อง · อายุ ' . $plan['days']]" />
                            {{-- ราคามาจาก config/licenses.php ไม่ใช่จากฟอร์ม — ตะกร้าคิดตามแผนที่ส่งไป --}}
                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="license_type" value="{{ $term }}">
                                <input type="hidden" name="buy_now" value="1">
                                <button type="submit" class="{{ $featured ? 'xp-btn-pro' : 'xp-btn-ghost' }} block w-full py-3.5 text-center rounded-xl font-extrabold transition cursor-pointer">
                                    {{ $ownsLocalVpn ? 'ซื้อ License เพิ่ม' : 'ซื้อ Pro' }} {{ $plan['name'] }} — ฿{{ number_format($prices[$term]) }}
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($ownsLocalVpn)
                <p class="text-center mt-6">
                    <a href="{{ route('customer.licenses') }}" class="xp-link text-sm font-semibold">มี License แล้ว? ดูคีย์ของคุณ →</a>
                </p>
            @endif
            <p class="text-center text-xs text-gray-500 mt-6">ซื้อแล้วได้ License key ทางอีเมลและในหน้า บัญชีของฉัน · ใส่คีย์ในแอปหน้า ตั้งค่า › ไลเซนส์ ได้เลย ไม่ต้องลงใหม่ · ย้ายเครื่องได้ด้วยการยกเลิก License บนเครื่องเดิม · คีย์ที่จ่ายแล้วใช้ต่อได้ 7 วันแม้ติดต่อเซิร์ฟเวอร์ไม่ได้ · หมดอายุแล้วแอปกลับเป็นรุ่นฟรีเอง</p>

            <x-xp.compare id="free-vs-pro" class="mt-14 scroll-mt-20" :groups="$compare" note="ระหว่างทดลอง 30 วันใช้ได้ทุกฟีเจอร์ Pro · วงใหญ่ 50 คนนับตามสิทธิ์ของเจ้าของวง · บน Windows การลดปิงและ VPN ประเทศต้องเปิดแอปแบบผู้ดูแลระบบ" />
        </div>
    </section>

    {{-- ========================= DOWNLOAD / PLATFORMS ========================= --}}
    <section class="relative py-20 scroll-mt-20" id="download">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-12 gap-10 items-center">
                <div class="lg:col-span-7">
                    <p class="xp-eyebrow mb-3">Windows + Android</p>
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-4">แอปเดียว <span class="whitespace-nowrap">ทั้งคอมและมือถือ</span></h2>
                    <p class="text-lg text-gray-300 leading-relaxed mb-2">ไฟล์จาก xman4289.com โดยตรง ใช้ไฟล์เดียวกันทั้งรุ่นฟรีและ Pro คอม Windows กับมือถือ Android เข้าวงเดียวกันได้ และ 1 คีย์ Pro ใช้กับทั้งสองเครื่องพร้อมกัน</p>
                    <p class="text-sm text-gray-500 leading-relaxed mb-8">Same app on Windows and Android, same networks, one Pro key for both.</p>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div class="xp-card p-6 flex flex-col">
                            <h3 class="text-xl font-black text-white mb-1 flex items-center gap-2">{!! $winIcon !!} Windows</h3>
                            <p class="text-sm text-gray-400 mb-4">ตัวติดตั้ง .exe</p>
                            <x-xp.ticks class="!space-y-2.5 text-sm mb-6 flex-1" :items="['Windows 10 (1809) ขึ้นไป หรือ Windows 11 แบบ 64-bit', 'ขอสิทธิ์ผู้ดูแลครั้งเดียวตอนติดตั้ง เพื่อลงบริการ LocalVPNService และการ์ดเครือข่ายเสมือน', 'ย่อไว้ที่ถาดข้างนาฬิกาได้ วงยังต่ออยู่']" />
                            @if($csDownload)
                                <x-xp.download block :href="$windowsUrl" :coming-soon="true" />
                            @elseif($windowsReady)
                                <a href="{{ $windowsUrl }}" class="xp-btn-main block w-full py-3.5 text-center rounded-xl font-extrabold transition">ดาวน์โหลดสำหรับ Windows</a>
                            @else
                                <span class="xp-btn-ghost xp-btn-off block w-full py-3.5 text-center rounded-xl font-bold" role="status" aria-disabled="true" data-windows-pending>Windows — ยังไม่เปิดให้ดาวน์โหลด</span>
                            @endif
                        </div>
                        <div class="xp-card p-6 flex flex-col">
                            <h3 class="text-xl font-black text-white mb-1 flex items-center gap-2">{!! $androidIcon !!} Android</h3>
                            <p class="text-sm text-gray-400 mb-4">ไฟล์ APK</p>
                            <x-xp.ticks class="!space-y-2.5 text-sm mb-6 flex-1" :items="['Android 7.0 ขึ้นไป', 'อนุญาตการติดตั้งแอปจากไฟล์ (APK) ครั้งแรก', 'อนุญาตการเชื่อมต่อ VPN เมื่อแอปถาม']" />
                            @if($csDownload)
                                <x-xp.download block :href="$androidUrl" :coming-soon="true" />
                            @else
                                <a href="{{ $androidUrl }}" class="{{ $windowsReady ? 'xp-btn-ghost font-bold' : 'xp-btn-main font-extrabold' }} block w-full py-3.5 text-center rounded-xl transition">ดาวน์โหลดสำหรับ Android</a>
                            @endif
                        </div>
                    </div>
                    <p class="mt-5 text-sm text-gray-400">
                        แอปอัปเดตตัวเองจาก xman4289.com ทั้งรุ่นฟรีและ Pro ·
                        <a href="{{ route('localvpn.install-guide') }}" class="xp-link underline">วิธีติดตั้งทีละขั้น</a>
                    </p>
                </div>
                <div class="lg:col-span-5">
                    <div class="grid grid-cols-2 gap-4 sm:gap-6 max-w-md mx-auto">
                        @foreach([['phone-networks', 'ค้นหาและเข้าวง'], ['phone-network', 'สมาชิกในวง']] as [$phoneShot, $caption])
                            <figure class="text-center">
                                <x-xp.screen phone :src="$shot($phoneShot . '.webp')" alt="LocalVPN บนจอขนาดมือถือ — {{ $caption }}" :width="396" :height="813" />
                                <figcaption class="mt-3 text-xs text-gray-400">{{ $caption }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                    <p class="mt-3 text-center text-xs text-gray-500">หน้าจอจริงของแอปเมื่อจอแคบแบบมือถือ (เมนูอยู่ด้านล่าง) · ข้อมูลในภาพเป็นข้อมูลตัวอย่าง</p>
                </div>
            </div>
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['Windows', 'Windows 10 (1809) ขึ้นไป หรือ Windows 11 แบบ 64-bit', 'Windows 10/11, x64'],
            ['Android', 'Android 7.0 ขึ้นไป ติดตั้งจากไฟล์ APK', 'Android 7.0+ (APK)'],
            ['สิทธิ์ผู้ดูแล (Windows)', 'ขอครั้งเดียวตอนติดตั้ง หลังจากนั้นเปิดวง LAN ได้โดยไม่ต้องเปิดแอปแบบผู้ดูแล', 'Admin once, at install'],
            ['อินเทอร์เน็ต', 'ต่อเน็ตตอนเปิดครั้งแรกเพื่อลงทะเบียนเครื่องและเริ่มทดลอง Pro', 'Online to register and start the trial'],
        ]"
        specs-note="การลดปิงเกมและ VPN ประเทศบน Windows ยังต้องกด เปิดใหม่แบบผู้ดูแล ในแอป เพราะใช้อุปกรณ์ WireGuard ของระบบ · ยังไม่มีแอป iOS"
        :steps="[
            ['ดาวน์โหลดแอป', 'ไฟล์มาจาก xman4289.com โดยตรง ใช้ได้ทั้งรุ่นฟรีและ Pro', 'Download for Windows or Android'],
            ['ติดตั้งแล้วเปิดแอป', 'เครื่องใหม่ได้ทดลอง Pro ครบทุกฟีเจอร์ 30 วันอัตโนมัติ ไม่ต้องผูกบัตร', 'Install and open — the 30-day Pro trial starts by itself'],
            ['สร้างวงแล้วชวนเพื่อน', 'ส่งรหัสวงให้เพื่อน หรือค้นหาวงสาธารณะ ครบ 30 วันแล้วยังใช้รุ่นฟรีต่อได้', 'Create a network and invite friends'],
        ]" />

    <x-xp.faq id="faq" :items="[
        ['ฟรีจริงไหม ใช้ได้แค่ไหน?', 'ฟรีจริง ไม่มีวันหมดอายุ สร้างหรือเข้าวง LAN ได้ (วงของคุณรับได้ 5 คน) ต่อตรงแบบเข้ารหัส ส่งไฟล์ ใช้ Torrent ชุมชน วัดปิง และใช้ VPN กลุ่มประเทศฟรีได้ ส่วนการลดปิง ปรับแต่ง Windows วง 50 คน และ VPN ทุกประเทศเป็นของ Pro'],
        ['ทดลอง Pro ทำอย่างไร?', 'เปิดแอปครั้งแรกตอนต่ออินเทอร์เน็ต เครื่องใหม่ได้ทดลอง Pro ครบทุกฟีเจอร์ 30 วันเอง ไม่ต้องผูกบัตร สิทธิ์ทดลองมีครั้งเดียวต่อเครื่อง ลบแอปลงใหม่หรือลง Windows ใหม่ก็ไม่ได้สิทธิ์ซ้ำ ครบแล้วแอปกลับเป็นรุ่นฟรีเองโดยไม่ต้องทำอะไร'],
        ['Pro ราคาเท่าไร ใช้ได้กี่เครื่อง?', 'รายเดือน ฿' . number_format($prices['monthly'] ?? 0) . ' · รายปี ฿' . number_format($prices['yearly'] ?? 0) . ' · ตลอดชีพ ฿' . number_format($prices['lifetime'] ?? 0) . ' — 1 คีย์ใช้ได้ ' . $devices . ' เครื่องพร้อมกัน เช่น คอม 1 เครื่อง + มือถือ 1 เครื่อง ไม่ตัดเงินอัตโนมัติ'],
        ['เพื่อนในวงต้องซื้อ Pro ด้วยไหม?', 'ไม่ต้อง จำนวนสมาชิกสูงสุดของวงนับตามสิทธิ์ของเจ้าของวง ถ้าเจ้าของวงใช้ Pro วงนั้นรับได้ 50 คน ส่วนการลดปิงและ VPN ทุกประเทศเป็นสิทธิ์ของแต่ละเครื่อง'],
        ['ลดปิงได้จริงไหม?', 'ลดได้เฉพาะเมื่อเส้นทางผ่านรีเลย์ดีกว่าเส้นทางตรงของเน็ตคุณ เช่น ตอนที่ผู้ให้บริการส่งข้อมูลอ้อม แอปวัดให้เห็นก่อนทุกครั้งและบอกตรง ๆ ถ้าทางตรงดีที่สุดอยู่แล้ว ปิงไม่มีทางต่ำกว่าระยะทางจริงถึงเซิร์ฟเวอร์เกม บน Windows ยังช่วยลดอาการปิงกระชากจาก Wi-Fi และการประหยัดพลังงานได้'],
        ['ใช้กับเกมอะไรได้บ้าง?', 'วง LAN ใช้กับเกมที่มีโหมด LAN หรือให้ต่อด้วย IP โดยตรง ส่วนตัวลดปิงใช้กับเกมออนไลน์ที่มีเซิร์ฟเวอร์ในภูมิภาคที่เลือก'],
        ['ข้อมูลของฉันปลอดภัยไหม?', 'ทราฟฟิกและไฟล์ระหว่างสมาชิกเข้ารหัสระหว่างเครื่อง (X25519 + ChaCha20-Poly1305) ทั้งตอนต่อตรงและผ่านรีเลย์ เซิร์ฟเวอร์ของเราเห็นแค่กุญแจสาธารณะ แอปติดต่อ xman4289.com เพื่อลงทะเบียนเครื่อง ตรวจ License จัดการวง และเช็กอัปเดต'],
        ['ทำไม Windows ต้องขอสิทธิ์ผู้ดูแลระบบ?', 'ตอนติดตั้งต้องลงบริการ LocalVPNService และการ์ดเครือข่ายเสมือน (Wintun) ซึ่ง Windows อนุญาตเฉพาะผู้ดูแลระบบ หลังจากนั้นเปิดวง LAN ได้ตามปกติ ส่วนการลดปิงและ VPN ประเทศยังต้องเปิดแอปแบบผู้ดูแลระบบ'],
        ['มีแอป iPhone ไหม?', 'ยังไม่มี ตอนนี้ LocalVPN มีสำหรับ Windows 10/11 และ Android 7.0 ขึ้นไป'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$artHero" title="ชวนเพื่อนมา" glow="เข้าวงเดียวกัน"
              :sub="'ใช้รุ่นฟรีได้ทันที ทุกเครื่องใหม่ทดลอง Pro ฟรี 30 วัน แล้วอัป Pro เริ่มเพียง ฿' . $monthly . ' ต่อเดือน'"
              en="Bring your friends onto one LAN.">
        @if($csDownload)
            <x-xp.download :href="$windowsUrl" :coming-soon="true" class="!px-8" />
        @else
            @if($windowsReady)
                <a href="{{ $windowsUrl }}" class="xp-btn-main inline-flex items-center gap-2 px-8 py-4 rounded-xl font-extrabold text-lg transition">{!! $winIcon !!} ดาวน์โหลด Windows</a>
            @endif
            <a href="{{ $androidUrl }}" class="{{ $windowsReady ? 'xp-btn-ghost font-bold' : 'xp-btn-main font-extrabold' }} inline-flex items-center gap-2 px-8 py-4 rounded-xl text-lg transition">{!! $androidIcon !!} ดาวน์โหลด Android</a>
        @endif
        <a href="#pricing" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ดูแผน Pro</a>
        @if($ownsLocalVpn)
            <a href="{{ route('customer.licenses') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ดูคีย์ License ของฉัน</a>
        @endif
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows 10/11 x64 · Android 7.0+ · ทดลอง Pro 30 วัน · 1 คีย์ = {{ $devices }} เครื่อง</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
