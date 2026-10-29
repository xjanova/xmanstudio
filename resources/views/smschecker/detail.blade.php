@extends($publicLayout ?? 'layouts.app')
{{--
    SmsChecker (แอป Android) — หน้าหลักของสินค้า slug `smschecker` · /smschecker (และ /products/smschecker redirect มาที่นี่)
    ปุ่ม "ซื้อ" ในแอปพามาที่ /smschecker/buy → pricing → checkout ของตัวเอง (ผูกคีย์กับเครื่องจาก machine_id)

    ไม่ใช่ตัวเดียวกับ /products/sms-payment-checker — นั่นคือปลั๊กอิน WordPress/WooCommerce (สินค้าอีกตัว ขายผ่านตะกร้า)

    ข้อเท็จจริงทุกข้อมาจากโค้ดแอป D:/Code/SmsChecker (v2.0.x):
      ทดลอง 24 ชม. = ProductLicenseController::TRIAL_DAYS (ไม่ได้ตั้ง → 1 วัน) + LicenseManager LOCAL_TRIAL_DURATION_MS
      หมดทดลอง/หมดอายุ = ทั้งแอปขึ้นหน้า LicenseGateScreen (ไม่มีรุ่นฟรี)
      จำนวนเครื่อง = SmsCheckerController: lifetime 3 เครื่อง อื่น ๆ 1 เครื่อง
--}}
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย (กฎเจ้าของ 2026-10-12) --}}
@php $csDownload = \App\Models\Product::slugIsComingSoon('smschecker'); @endphp

@section('title', 'SmsChecker — ยืนยันยอดโอนจาก SMS ธนาคารอัตโนมัติ · แอป Android · ทดลองฟรี 24 ชม. | XMAN Studio')
@section('meta_description', 'SmsChecker แอป Android อ่าน SMS และการแจ้งเตือนจากแอปธนาคารไทย 14 แห่ง พร้อมเพย์ เป๋าตัง ถุงเงิน LINE Pay แล้วส่งให้เว็บร้านจับคู่บิลจากยอดทศนิยมเฉพาะ อนุมัติออเดอร์อัตโนมัติหรือกดเองในแอป ไม่ต้อง Root ทดลองฟรี 24 ชั่วโมง · Confirms bank transfers from your shop phone\'s bank SMS.')
@section('og_image', asset('images/products/smschecker/v2/og.jpg'))

@section('content')
@php
    // ราคาทุกแผนมาจาก config/licenses.php 'plans.smschecker' ผ่าน SmsCheckerController::pricedPlans() — ห้ามเขียนตัวเลขราคาลงหน้านี้
    $prices = collect($pricing)->map(fn ($plan) => (int) $plan['price'])->all();
    $monthly = isset($prices['monthly']) ? number_format($prices['monthly']) : null;
    $fromPrice = $prices ? number_format(min($prices)) : null;
    $yearlySaving = \App\Support\LicensePlans::yearlySaving('smschecker');

    // ส่วนลดจ่ายด้วย Wallet — ค่าคงที่ใน SmsCheckerController (อ่านจากที่นั่น ไม่เขียนซ้ำ)
    $walletDiscount = (new \ReflectionClassConstant(\App\Http\Controllers\SmsCheckerController::class, 'WALLET_DISCOUNT_PERCENT'))->getValue();

    // ซื้อไม่ได้ระหว่าง Coming Soon (ComingSoonPurchase ปฏิเสธที่ checkout อยู่แล้ว — หน้านี้ไม่ชวนกดด้วย)
    $csBuy = $product?->isComingSoon() ?? false;

    $shot = fn (string $file) => asset('images/products/smschecker/v2/' . $file);

    // จำนวนเครื่องต่อคีย์: SmsCheckerController (max_activations: lifetime 3, อื่น ๆ 1)
    $plans = [
        'monthly' => ['unit' => '/ เดือน', 'note' => 'ซื้อทีละ 30 วัน ไม่ตัดเงินอัตโนมัติ', 'days' => '30 วัน', 'devices' => 1],
        'yearly' => ['unit' => '/ ปี', 'note' => $yearlySaving ? 'ถูกกว่ารายเดือน ' . $yearlySaving . '%' : 'จ่ายครั้งเดียวใช้ทั้งปี', 'days' => '365 วัน', 'devices' => 1],
        'lifetime' => ['unit' => 'ครั้งเดียว', 'note' => 'จ่ายครั้งเดียว ใช้ได้ตลอด', 'days' => 'ไม่หมดอายุ', 'devices' => 3],
    ];

    // หน้าจอหลักของแอป — สตริงและการทำงานจาก ui/smshistory, ui/orders, ui/qrscanner ของแอป
    $coreRows = [
        [
            'img' => $shot('sms.webp'), 'mock' => true, 'phone' => true, 'name' => 'อ่านยอดเงินเข้าให้เอง', 'eyebrow' => 'Bank SMS + bank-app alerts',
            'th' => 'อ่าน SMS เงินเข้าและเงินออกจากธนาคารไทย 14 แห่งและพร้อมเพย์ รวมถึงการแจ้งเตือนจากแอปธนาคาร เป๋าตัง ถุงเงิน และ LINE Pay แยกยอดเงิน ธนาคาร และเวลาออกมาเป็นรายการ แล้วส่งให้เว็บร้านทันที',
            'en' => 'Reads incoming and outgoing bank SMS (14 Thai banks and PromptPay) plus notifications from banking apps, Pao Tang, Thung Ngern and LINE Pay.',
            'points' => ['SMS จากผู้ส่งที่แอปไม่รู้จัก กำหนดเองได้ว่าเป็นธนาคารไหน', 'ทำงานเบื้องหลังตลอด เปิดเครื่องใหม่ก็กลับมาทำงานเอง', 'เลขบัญชีแสดงแบบปิดบางหลัก'],
            'alt' => 'ภาพตัวอย่างหน้าข้อความ SMS ของ SmsChecker: รายการเงินเข้าเงินออกจากหลายธนาคาร พร้อมป้ายจับคู่และชื่อเว็บ',
        ],
        [
            'img' => $shot('orders.webp'), 'mock' => true, 'phone' => true, 'name' => 'จับคู่บิล อนุมัติออเดอร์', 'eyebrow' => 'Order approval',
            'th' => 'เว็บร้านให้ลูกค้าโอนยอดที่มีเศษสตางค์เฉพาะของแต่ละบิล เมื่อ SMS เงินเข้าตรงยอด บิลนั้นถูกจับคู่ทันที เลือกได้ว่าจะให้อนุมัติเอง หรือรอคุณกดในแอป',
            'en' => 'Each bill gets its own satang amount. When a matching transfer lands, the bill is matched — approved for you, or waiting for your tap.',
            'points' => ['3 โหมด: อนุมัติอัตโนมัติ · ตรวจสอบด้วยตนเอง · โหมดอัจฉริยะ (ชัดเจนอนุมัติ ไม่ชัดเจนรอตรวจ)', 'อนุมัติ ปฏิเสธ หรืออนุมัติทั้งหมดจากในแอป และแตะดูสลิปที่ใช้อนุมัติได้', 'ค้นหาเลขบิล ชื่อสินค้า หรือชื่อลูกค้า'],
            'alt' => 'ภาพตัวอย่างหน้าจัดการออเดอร์ของ SmsChecker: บิลรอจับคู่พร้อมยอดทศนิยมเฉพาะ ปุ่มอนุมัติ และบิลที่อนุมัติผ่าน SMS แล้ว',
        ],
        [
            'img' => $shot('qr.webp'), 'mock' => true, 'phone' => true, 'name' => 'เชื่อมเว็บด้วยการสแกน QR', 'eyebrow' => 'Pair in one scan',
            'th' => 'เปิดหน้าแอดมินของเว็บร้าน กดแสดง QR ของอุปกรณ์ แล้วสแกนด้วยแอป ที่อยู่เว็บ คีย์ และรหัสลับถูกตั้งให้เอง ไม่ต้องพิมพ์ และเพิ่มได้หลายเว็บในมือถือเครื่องเดียว',
            'en' => 'Scan the device QR in your shop\'s admin page: address and keys are set for you. One phone can serve several sites.',
            'points' => ['รับเฉพาะเว็บที่เป็น HTTPS', 'ใส่รหัสด้วยตนเองก็ได้ ถ้าสแกนไม่สะดวก'],
            'alt' => 'ภาพตัวอย่างหน้าเชื่อมต่ออุปกรณ์ของ SmsChecker: ช่องสแกน QR และการเชื่อมต่อที่เข้ารหัส AES-256-GCM กับ HMAC-SHA256',
        ],
    ];

    $tools = [
        [
            'key' => 'sites', 'name' => 'หลายเว็บ บัญชีเดียว', 'short' => 'บอกว่ายอดนี้ของเว็บไหน',
            'th' => 'มือถือเครื่องเดียวรับยอดให้หลายเว็บได้ ทุกรายการมีป้ายบอกว่าเป็นของเว็บไหน และถ้ายอดหนึ่งตรงกับบิลของหลายเว็บพร้อมกัน แอปเตือนให้ตรวจก่อน',
            'en' => 'One phone, several sites: every credit is tagged with its site, and a credit that matches bills on two sites is flagged for you.',
            'points' => ['การ์ดบิลบอกชื่อเว็บที่บิลนั้นมา', 'กราฟรายได้บิลแยกตามเซิร์ฟเวอร์'],
        ],
        [
            'key' => 'realtime', 'name' => 'แจ้งทันที แม้ปิดจอ', 'short' => 'WebSocket · Push · คิวออฟไลน์',
            'th' => 'เชื่อมเว็บแบบสดผ่าน WebSocket และรับ Push เมื่อมีบิลใหม่ ถ้าเน็ตหลุด รายการถูกเก็บในคิวออฟไลน์แล้วส่งให้เองเมื่อกลับมาออนไลน์',
            'en' => 'Live sync over WebSocket with push for new bills. Offline, items wait in a queue and go out when the phone is back online.',
            'points' => [],
        ],
        [
            'key' => 'tts', 'name' => 'อ่านยอดออกเสียง', 'short' => '"เงินเข้า … จำนวน … บาท"',
            'th' => 'เปิดให้แอปพูดยอดเงินเข้าเงินออกได้ เช่น "เงินเข้า กสิกรไทย จำนวน 1,250 บาท" และบอกเลขออเดอร์เมื่อจับคู่ได้ เลือกพูดไทยหรืออังกฤษ',
            'en' => 'Speaks every credit and debit aloud, with the order it matched — in Thai or English.',
            'points' => ['ถ้าเครื่องยังไม่มีเสียงภาษาไทย แอปพาไปดาวน์โหลดให้'],
        ],
        [
            'key' => 'orphans', 'name' => 'ยอดที่หาบิลไม่เจอ', 'short' => 'ช่วยหาบิลที่น่าจะใช่',
            'th' => 'เงินเข้าที่ไม่ตรงบิลไหนเลย (ลูกค้าโอนไม่ตรงเศษสตางค์) ไม่หายไปไหน กด "หาบิลที่ตรงกัน" ให้แอปถามเว็บหาบิลที่น่าจะใช่ แล้วคุณยืนยันผูกเข้าบิลเอง',
            'en' => 'A credit that matched nothing is kept. Ask the site for likely bills and confirm the match yourself.',
            'points' => [],
        ],
        [
            'key' => 'stats', 'name' => 'กราฟรายได้', 'short' => '7 วัน · 30 วัน · 1 ปี · กำหนดเอง',
            'th' => 'ดูเงินเข้าเงินออกย้อนหลังเป็นกราฟ แตะแต่ละจุดเพื่อดูรายละเอียด แยกรายได้บิลตามเซิร์ฟเวอร์ และเทียบกับยอดดิบจาก SMS ธนาคาร',
            'en' => 'Income and outgoings over a week, a month, a year or your own range — bill income per server beside the raw bank SMS.',
            'points' => [],
        ],
        [
            'key' => 'health', 'name' => 'สุขภาพระบบ', 'short' => 'ตรวจเอง ซ่อมเอง',
            'th' => 'หน้าสุขภาพระบบบอกสถานะการรับ SMS การเชื่อมเว็บ และเสียงแจ้งเตือน มีปุ่มทดสอบเสียงและรีสตาร์ทบริการ และแอปเฝ้าบริการของตัวเองให้กลับมาทำงานเองถ้าถูกปิด',
            'en' => 'A health page for SMS intake, server links and the voice, with a service watchdog that restarts what the phone stops.',
            'points' => ['อัปเดตแอปได้จากในแอป ไฟล์มาจาก xman4289.com'],
        ],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'SmsChecker',
        'operatingSystem' => 'Android 8.0+',
        'applicationCategory' => 'BusinessApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'แอป Android อ่าน SMS และการแจ้งเตือนจากแอปธนาคารไทย แล้วส่งให้เว็บร้านจับคู่บิลและอนุมัติออเดอร์อัตโนมัติ',
        'image' => asset('images/products/smschecker/v2/og.jpg'),
        'url' => route('smschecker.detail'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => collect($pricing)->map(fn ($plan) => ['@type' => 'Offer', 'name' => 'SmsChecker ' . $plan['name'], 'price' => (string) $plan['price'], 'priceCurrency' => 'THB'])->values()->all(),
    ];
    if ($version) {
        $ld['softwareVersion'] = $version->version;
    }
@endphp
<x-xp.page a="#5ce98a" b="#5ec8d6" c="#5ec8d6" glow="94 200 214" g1="#5ce98a" g2="#5ec8d6" g3="#f2dd9a">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="ยืนยันยอดโอนจาก SMS ธนาคาร อัตโนมัติ"
               tagline-en="Confirms bank transfers from your shop phone, automatically."
               lead="แอป Android บนมือถือที่รับเงินของร้าน อ่าน SMS และการแจ้งเตือนจากแอปธนาคาร แล้วส่งให้เว็บร้านจับคู่กับบิลที่รอชำระ อนุมัติออเดอร์ให้เอง หรือรอคุณกดยืนยันจากในแอป ไม่ต้องนั่งเช็กสลิปทีละใบ"
               lead-en="It reads the bank SMS and banking-app alerts on the phone that receives your money and hands them to your site to match against open bills.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $version ? 'SmsChecker v' . $version->version : 'SmsChecker' }}</span>
            <span class="xp-chip">Android 8.0+</span>
        </x-slot:chips>
        <x-slot:title>Sms<span class="xp-text-glow">Checker</span></x-slot:title>
        <x-slot:actions>
            <x-xp.download :href="route('smschecker.download')" :coming-soon="$csDownload" th="ดาวน์โหลด · ทดลองฟรี 24 ชม." en="Free 24-hour trial" />
            @unless($csBuy || ! $fromPrice)
                <a href="#pricing" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">ซื้อ License — เริ่ม ฿{{ $fromPrice }}{{ $monthly ? '/เดือน' : '' }}</a>
            @endunless
        </x-slot:actions>
        <x-slot:notes>
            <p class="mt-4 text-sm text-gray-400">
                @if($version)เวอร์ชันล่าสุด v{{ $version->version }} · @endif
                ไฟล์ .apk จาก xman4289.com โดยตรง · Android 8.0 ขึ้นไป
            </p>
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ทดลองฟรี 24 ชม.</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ไม่ต้อง Root</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> เข้ารหัส AES-256-GCM</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="max-w-xs mx-auto w-full">
                <x-xp.screen phone :src="$shot('dashboard.webp')" :eager="true" :mock="true" :width="390" :height="845"
                             alt="ภาพตัวอย่างแดชบอร์ดของ SmsChecker: เงินเข้าวันนี้ กราฟ 7 วัน ตัวเลขซิงค์และรอตรวจสอบ ธนาคารยอดนิยม และรายการล่าสุด" />
            </div>
            <div class="xp-float absolute -left-1 sm:left-0 bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">ยอดที่ต้องโอน · Unique amount</p>
                <p class="text-white font-extrabold text-lg leading-tight">฿1,250<span class="text-emerald-300">.37</span><br><span class="text-gray-400 text-sm font-normal">เศษสตางค์บอกว่าเป็นบิลไหน</span></p>
            </div>
            <div class="xp-float-2 absolute right-0 top-24 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">อ่านจาก · Reads</p>
                <p class="text-white font-bold leading-tight">SMS + แอปธนาคาร<br><span class="text-gray-400 text-sm font-normal">เป๋าตัง · ถุงเงิน · LINE Pay</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================== CORE ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="How it works" title="เงินเข้า SMS เด้ง" title2="บิลอนุมัติให้เอง"
                       sub="มือถือที่ผูกบัญชีรับเงินของร้านเป็นตัวรับ SMS ส่วนเว็บร้านเป็นตัวจับคู่บิล แอปเชื่อมสองฝั่งนี้เข้าด้วยกัน"
                       en="The phone that receives the money reads the SMS; your site matches the bill; SmsChecker links the two." />
            <x-xp.rows :items="$coreRows" />
        </div>
    </section>

    {{-- =========================== MORE TOOLS =========================== --}}
    <section class="relative py-20 isolate">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 right-[-30%] lg:right-[-16%] lg:w-[68%] lg:opacity-80" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="left" class="mb-10" eyebrow="Built for a busy shop" title="ไม่พลาดยอด" title2="แม้วันที่ยุ่งที่สุด"
                       sub="ทุกอย่างในรายการนี้รวมอยู่ใน License ทุกแผน และเปิดให้ลองครบระหว่างทดลอง 24 ชั่วโมง"
                       en="Everything here comes with every plan, and with the 24-hour trial." />
            <x-xp.explorer id="tools" label="เครื่องมือของ SmsChecker" :tools="$tools" />
        </div>
    </section>

    {{-- ============================ TRUST ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Safe by design" title="ยอดเงินของร้าน" title2="ส่งถึงเว็บอย่างปลอดภัย"
                       sub="ข้อมูลยอดเงินวิ่งระหว่างมือถือกับเว็บของคุณเท่านั้น และถูกเข้ารหัสทุกครั้ง"
                       en="Transactions travel between your phone and your own site, encrypted every time." />
            <x-xp.cards :items="[
                ['icon' => 'lock', 'title' => 'เข้ารหัสทุกครั้ง', 'body' => 'ข้อมูลระหว่างแอปกับเว็บเข้ารหัสด้วย AES-256-GCM และเซ็นด้วย HMAC-SHA256 แยกคีย์เข้ารหัสกับคีย์เซ็นออกจากกัน', 'en' => 'AES-256-GCM with HMAC-SHA256 signatures'],
                ['icon' => 'globe', 'title' => 'HTTPS เท่านั้น', 'body' => 'QR ที่ชี้ไปเว็บแบบ http ธรรมดา หรือคีย์ที่สั้นเกินไป ถูกแอปปฏิเสธตั้งแต่ตอนสแกน', 'en' => 'Insecure links are refused at the scan'],
                ['icon' => 'user', 'title' => 'ไม่ต้อง Root', 'body' => 'ใช้สิทธิ์อ่าน SMS และสิทธิ์อ่านการแจ้งเตือนมาตรฐานของ Android เท่านั้น', 'en' => 'Standard Android permissions, no root'],
                ['icon' => 'eye', 'title' => 'ปิดเลขบัญชีบางหลัก', 'body' => 'รายการในแอปแสดงเลขบัญชีแบบปิดบางหลัก คนที่เห็นหน้าจอไม่ได้เลขบัญชีเต็มไปด้วย', 'en' => 'Account numbers shown masked'],
                ['icon' => 'power', 'title' => 'ทำงานตลอด 24 ชม.', 'body' => 'รับ SMS เป็นบริการเบื้องหลัง เปิดเครื่องใหม่ก็กลับมาเอง และมีตัวเฝ้าบริการคอยรีสตาร์ทถ้าระบบปิดไป', 'en' => 'A background service with a watchdog'],
                ['icon' => 'key', 'title' => 'คีย์ผูกกับเครื่อง', 'body' => 'License ผูกกับมือถือที่ใช้งาน ซื้อจากปุ่มในแอปแล้วคีย์ผูกกับเครื่องนั้นให้เอง', 'en' => 'Each key is bound to its phone'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="ลองฟรี 24 ชั่วโมง" title2="ถูกใจค่อยซื้อ License"
                       sub="ไฟล์เดียวกันทั้งทดลองและใช้จริง · หมดทดลองแล้วแอปขอ License ก่อนใช้งานต่อ · แผนรายเดือนและรายปีใช้ได้ 1 เครื่อง ตลอดชีพใช้ได้ 3 เครื่อง"
                       en="One app for trial and paid use. After the trial it asks for a licence. Monthly and yearly run on one phone, lifetime on three." />

            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-5 max-w-6xl mx-auto">
                <div class="xp-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">ทดลองฟรี</h3>
                        <x-xp.badge tier="free" label="24 ชม." />
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="ทุกฟีเจอร์ ครั้งเดียวต่อเครื่อง" en="Everything, once per phone" /></p>
                    <div class="mb-6"><span class="text-5xl font-black text-white">฿0</span></div>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ใช้ได้ทุกฟีเจอร์ 24 ชั่วโมง', 'เริ่มเองตอนเปิดแอปครั้งแรก', 'ไม่ต้องสมัคร ไม่ต้องผูกบัตร']" />
                    <x-xp.download block :href="route('smschecker.download')" :coming-soon="$csDownload" th="ดาวน์โหลดแอป" en="Download" />
                </div>

                @foreach($plans as $term => $plan)
                    @continue(! isset($pricing[$term]))
                    @php($featured = $term === 'yearly')
                    <div class="{{ $featured ? 'xp-featured' : 'xp-card' }}">
                        <div class="p-7 flex flex-col h-full">
                            @if($featured)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2"><x-xp.badge tier="pro" label="คุ้มที่สุด · BEST VALUE" class="shadow-lg" /></div>
                            @endif
                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-2xl font-black text-white">{{ $pricing[$term]['name_th'] }}</h3>
                                <x-xp.badge tier="pro" :label="strtoupper($pricing[$term]['name'])" />
                            </div>
                            <p class="text-amber-100/80 text-sm mb-6">{{ $plan['note'] }}</p>
                            <div class="mb-6 flex items-baseline gap-2">
                                <span class="text-5xl font-black text-white">฿{{ number_format($pricing[$term]['price']) }}</span>
                                <span class="text-gray-300">{{ $plan['unit'] }}</span>
                            </div>
                            <x-tpix-price variant="detail" :thb="$pricing[$term]['price']" class="-mt-4 mb-6" />
                            <x-xp.ticks pro class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ทุกฟีเจอร์ในแอป', 'ใช้ได้ ' . $plan['devices'] . ' เครื่อง', 'อายุ License: ' . $plan['days']]" />
                            @if($csBuy)
                                <span class="xp-btn-ghost xp-btn-off block w-full py-3.5 text-center rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
                            @else
                                {{-- หน้าราคาของแอปเอง: เก็บ machine_id ที่แอปส่งมา แล้วไป checkout ของแผนนั้น --}}
                                <a href="{{ route('smschecker.pricing') }}" class="{{ $featured ? 'xp-btn-pro' : 'xp-btn-ghost' }} block w-full py-3.5 text-center rounded-xl font-extrabold transition">
                                    ซื้อ{{ $pricing[$term]['name_th'] }} — ฿{{ number_format($pricing[$term]['price']) }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-center text-xs text-gray-500 mt-8">จ่ายได้ทั้ง PromptPay โอนผ่านธนาคาร หรือ Wallet ของเว็บ (จ่ายด้วย Wallet ลดเพิ่ม {{ $walletDiscount }}% และได้คีย์ทันที) · ต้องเข้าสู่ระบบก่อนชำระเงิน · ได้ License key ในหน้าบัญชีของฉัน แล้วใส่ในแอปได้เลย</p>
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['มือถือ Android 8.0 ขึ้นไป', 'เครื่องที่ใส่ซิมเบอร์ที่ผูกกับบัญชีรับเงินของร้าน หรือเครื่องที่ลงแอปธนาคารของบัญชีนั้น', 'Android 8.0+ on the phone that gets the bank alerts'],
            ['สิทธิ์ในเครื่อง', 'อ่าน SMS · อ่านการแจ้งเตือน (สำหรับแอปธนาคาร) · กล้อง (สแกน QR) · ไม่จำกัดแบตให้แอป', 'SMS, notification access, camera, no battery limits'],
            ['เว็บร้านที่รองรับ', 'เว็บต้องติดตั้งตัวเชื่อม SmsChecker ฝั่งเซิร์ฟเวอร์ ที่มีหน้าแอดมินออก QR ให้สแกน และต้องเป็น HTTPS', 'A site running the SmsChecker server side, over HTTPS'],
            ['อินเทอร์เน็ต', 'ใช้ส่งยอดให้เว็บ รับบิลใหม่ และตรวจ License · เน็ตหลุดรายการจะรอในคิวแล้วส่งให้ทีหลัง', 'Online to sync and check the licence'],
        ]"
        :steps="[
            ['ดาวน์โหลด APK แล้วติดตั้ง', 'ไฟล์มาจาก xman4289.com โดยตรง ใช้ไฟล์เดียวกันทั้งทดลองและใช้จริง', 'Download and install the APK'],
            ['เปิดแอป ให้สิทธิ์ แล้วเริ่มทดลอง', 'ให้สิทธิ์อ่าน SMS และการแจ้งเตือน แอปเริ่มทดลองฟรี 24 ชั่วโมงให้เอง', 'Grant permissions; the 24-hour trial starts'],
            ['สแกน QR จากหน้าแอดมินของเว็บ', 'แอปตั้งค่าเว็บให้เอง แล้วเริ่มส่งยอดเงินเข้าให้เว็บจับคู่บิลทันที', 'Scan your site\'s QR and you are live'],
        ]" />
    @unless($csDownload)
        <p class="text-center -mt-10 mb-6 relative">
            <a href="{{ route('smschecker.download') }}" class="xp-link text-sm font-semibold">อ่านคู่มือติดตั้งแบบละเอียด →</a>
        </p>
    @endunless

    <x-xp.faq :items="[
        ['SmsChecker คืออะไร?', 'แอป Android ที่อ่าน SMS และการแจ้งเตือนจากแอปธนาคารบนมือถือที่รับเงินของร้าน แล้วส่งยอดเงินเข้าให้เว็บร้านจับคู่กับบิลที่รอชำระ อนุมัติออเดอร์อัตโนมัติหรือให้คุณกดอนุมัติเองในแอป'],
        ['รองรับธนาคารไหนบ้าง?', 'SMS และแอปของกสิกรไทย ไทยพาณิชย์ กรุงไทย กรุงเทพ ออมสิน กรุงศรีอยุธยา ทีทีบี ซีไอเอ็มบีไทย เกียรตินาคินภัทร แลนด์ แอนด์ เฮ้าส์ ทิสโก้ ยูโอบี ไอซีบีซี (ไทย) ธ.ก.ส. และพร้อมเพย์ รวมถึงการแจ้งเตือนจากเป๋าตัง ถุงเงิน และ LINE Pay · ผู้ส่ง SMS ที่แอปไม่รู้จัก กำหนดเองได้ว่าเป็นธนาคารไหน'],
        ['ต้อง Root เครื่องไหม?', 'ไม่ต้อง แอปใช้สิทธิ์อ่าน SMS และสิทธิ์อ่านการแจ้งเตือนมาตรฐานของ Android'],
        ['ทดลองฟรีได้นานแค่ไหน?', 'ทดลองได้ทุกฟีเจอร์ 24 ชั่วโมง ครั้งเดียวต่อเครื่อง เริ่มเองตอนเปิดแอปครั้งแรก ไม่ต้องสมัครหรือผูกบัตร หมดเวลาแล้วแอปขอ License ก่อนใช้งานต่อ'],
        ['ราคาเท่าไร ใช้ได้กี่เครื่อง?', collect($pricing)->map(fn ($p) => $p['name_th'] . ' ฿' . number_format($p['price']))->implode(' · ') . ' — แผนรายเดือนและรายปีใช้ได้ 1 เครื่อง แผนตลอดชีพใช้ได้สูงสุด 3 เครื่อง'],
        ['ชำระเงินอย่างไร?', 'PromptPay (สแกน QR) โอนผ่านธนาคาร หรือ Wallet บนเว็บ จ่ายด้วย Wallet ลดเพิ่ม ' . $walletDiscount . '% และได้คีย์ทันที ส่วนการโอนรอแอดมินตรวจยอดก่อนออกคีย์'],
        ['ใช้กับเว็บของฉันได้ไหม?', 'ได้ถ้าเว็บติดตั้งตัวเชื่อม SmsChecker ฝั่งเซิร์ฟเวอร์ (มีหน้าแอดมินที่ออก QR ให้แอปสแกน และเว็บเป็น HTTPS) ถ้ายังไม่มี ทักทีมงานให้ช่วยประเมินได้จากหน้าขอใบเสนอราคา'],
        ['ข้อมูลยอดเงินไปที่ไหน?', 'แอปส่งรายการไปเฉพาะเว็บที่คุณสแกน QR เพิ่มไว้ เข้ารหัสด้วย AES-256-GCM และเซ็นด้วย HMAC-SHA256 ทุกครั้ง ส่วนการติดต่อ xman4289.com มีไว้ตรวจ License และเช็กอัปเดตแอป'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="เลิกนั่งเช็กสลิป" glow="ให้ SMS ทำงานแทน"
              :sub="'ดาวน์โหลดแล้วลองฟรี 24 ชั่วโมง ถูกใจค่อยซื้อ License' . ($fromPrice ? ' เริ่มเพียง ฿' . $fromPrice . ($monthly ? ' ต่อเดือน' : '') : '')"
              en="Stop checking slips by hand.">
        <x-xp.download :href="route('smschecker.download')" :coming-soon="$csDownload" class="!px-8" th="ดาวน์โหลดแอป" en="Free 24-hour trial" />
        @unless($csBuy)
            <a href="{{ route('smschecker.pricing') }}" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ดูราคาแพ็กเกจ</a>
        @endunless
        <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ให้ทีมงานช่วยเชื่อมเว็บ</a>
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">
                Android 8.0+ · ไม่ต้อง Root · ทดลองฟรี 24 ชม.
                @if($version) · เวอร์ชันล่าสุด v{{ $version->version }}@endif
            </p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
