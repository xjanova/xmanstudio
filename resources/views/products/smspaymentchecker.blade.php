@extends($publicLayout ?? 'layouts.app')
{{--
    SMS Payment Checker for WordPress — custom view ของสินค้า slug `sms-payment-checker` (/products/sms-payment-checker)
    ปลั๊กอิน WordPress/WooCommerce ขายผ่านตะกร้า (config/licenses.php 'cart_products') ราคาจาก 'plans.sms-payment-checker'

    ไม่ใช่ตัวเดียวกับ /smschecker — นั่นคือแอป Android SmsChecker (สินค้าอีกตัว ราคาอีกชุด checkout ของตัวเอง)

    ข้อเท็จจริงทุกข้อมาจาก wordpress-plugin/sms-payment-checker (v1.7.0):
      ความต้องการ = header ของ sms-payment-checker.php (WP 5.8 · PHP 8.0 · WC 6.0)
      ธนาคาร = SMS_Payment_Checker::get_supported_banks()
      1 คีย์ = 1 เว็บ = class-spc-license.php (machine id จาก site_url + DB_NAME + AUTH_KEY) และ LicensePlans::devicesPerKey
--}}
@section('title', $product->name . ' — ปลั๊กอิน WooCommerce ยืนยันยอดโอนจาก SMS ธนาคาร | XMAN Studio')
@section('meta_description', 'SMS Payment Checker ปลั๊กอิน WordPress/WooCommerce เพิ่มช่องทางชำระ "โอนผ่านธนาคาร" ที่ให้ลูกค้าแต่ละบิลโอนยอดทศนิยมเฉพาะ แล้วจับคู่กับ SMS เงินเข้าจากมือถือของร้าน อนุมัติออเดอร์อัตโนมัติหรือกดยืนยันเอง รองรับธนาคารไทย 14 แห่งและพร้อมเพย์ · Confirms WooCommerce bank transfers from your bank SMS.')
@section('og_image', asset('images/products/smschecker/v2/og.jpg'))

@section('content')
@php
    // ราคาจาก config/licenses.php 'plans.sms-payment-checker' — ห้ามเขียนตัวเลขราคาลงหน้านี้
    $slug = 'sms-payment-checker';
    $prices = \App\Support\LicensePlans::for($slug);
    $yearlySaving = \App\Support\LicensePlans::yearlySaving($slug);
    $sites = \App\Support\LicensePlans::devicesPerKey($slug);
    $fromPrice = $prices ? number_format(min($prices)) : null;
    $comingSoon = $product->isComingSoon();

    // แอป Android อยู่หน้าของตัวเอง — ลิงก์ไปเฉพาะตอนเปิดขาย (ปิดขาย /smschecker ตอบ 404)
    $appOnSale = \App\Models\Product::where('slug', 'smschecker')->where('is_active', true)->exists();

    $shot = fn (string $file) => asset('images/products/smschecker/v2/' . $file);

    $plans = [
        'monthly' => ['name' => 'รายเดือน', 'en' => 'MONTHLY', 'unit' => '/ เดือน', 'note' => 'ต่ออายุเองทุก 30 วัน', 'days' => '30 วัน'],
        'yearly' => ['name' => 'รายปี', 'en' => 'YEARLY', 'unit' => '/ ปี', 'note' => $yearlySaving ? 'ถูกกว่ารายเดือน ' . $yearlySaving . '%' : 'จ่ายครั้งเดียวใช้ทั้งปี', 'days' => '365 วัน'],
        'lifetime' => ['name' => 'ตลอดชีพ', 'en' => 'LIFETIME', 'unit' => 'ครั้งเดียว', 'note' => 'จ่ายครั้งเดียว ใช้ได้ตลอด', 'days' => 'ไม่หมดอายุ'],
    ];

    // ชื่อธนาคาร = SMS_Payment_Checker::get_supported_banks()
    $banks = ['กสิกรไทย', 'ไทยพาณิชย์', 'กรุงไทย', 'กรุงเทพ', 'ออมสิน', 'กรุงศรีอยุธยา', 'ทีทีบี', 'ซีไอเอ็มบีไทย', 'เกียรตินาคินภัทร', 'แลนด์ แอนด์ เฮ้าส์', 'ทิสโก้', 'ยูโอบี', 'ไอซีบีซี (ไทย)', 'ธ.ก.ส.', 'พร้อมเพย์'];

    $coreRows = [
        [
            'img' => $shot('wp-orders.webp'), 'mock' => true, 'name' => 'หน้า Pending Orders', 'eyebrow' => 'WooCommerce admin',
            'th' => 'ออเดอร์ที่จ่ายด้วย "โอนผ่านธนาคาร" รออยู่ในหน้าเดียว พร้อมยอดที่ต้องโอนและยอดเดิม ข้างกันคือ SMS เงินเข้าที่ยังไม่ตรงบิลไหน กดยืนยันหรือปฏิเสธได้จากตรงนั้นเลย',
            'en' => 'Orders waiting for a transfer sit on one page beside the SMS that matched nothing. Confirm or reject from there.',
            'points' => ['ค้นหาจากเลขออเดอร์ ชื่อ หรืออีเมล', 'แดชบอร์ดสรุปยอดและสถานะในเมนู SMS Payment'],
            'alt' => 'ภาพตัวอย่างหน้า Pending Orders ของปลั๊กอินในแผงควบคุม WordPress: ตารางออเดอร์รอชำระพร้อมยอดทศนิยมเฉพาะ และการ์ด SMS ที่ยังไม่จับคู่',
        ],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => $product->name,
        'operatingSystem' => 'WordPress 5.8+ / WooCommerce 6.0+',
        'applicationCategory' => 'BusinessApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'ปลั๊กอิน WordPress/WooCommerce ยืนยันการโอนเงินจาก SMS ธนาคาร ด้วยยอดทศนิยมเฉพาะของแต่ละออเดอร์',
        'image' => asset('images/products/smschecker/v2/og.jpg'),
        'url' => route('products.show', $product->slug),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => collect($plans)->filter(fn ($p, $term) => isset($prices[$term]))
            ->map(fn ($p, $term) => ['@type' => 'Offer', 'name' => $product->name . ' ' . $p['en'], 'price' => (string) $prices[$term], 'priceCurrency' => 'THB'])
            ->values()->all(),
    ];
@endphp
<x-xp.page a="#5ce98a" b="#5ec8d6" c="#5ec8d6" glow="94 200 214" g1="#5ce98a" g2="#5ec8d6" g3="#f2dd9a">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="ยืนยันยอดโอนของร้าน WooCommerce จาก SMS ธนาคาร"
               tagline-en="Confirms WooCommerce bank transfers from your bank SMS."
               lead="เพิ่มช่องทางชำระ “โอนผ่านธนาคาร” ที่ให้ทุกออเดอร์มียอดทศนิยมเฉพาะของตัวเอง เมื่อ SMS เงินเข้าจากมือถือของร้านตรงยอด ออเดอร์นั้นถูกจับคู่ทันที อนุมัติให้เองหรือรอคุณกดยืนยัน"
               lead-en="Each order gets its own satang amount. When the shop phone's bank SMS matches it, the order is matched — confirmed for you or waiting for your click.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>WordPress Plugin</span>
            <span class="xp-chip">WooCommerce 6.0+</span>
        </x-slot:chips>
        <x-slot:title>SMS Payment <span class="xp-text-glow">Checker</span></x-slot:title>
        <x-slot:actions>
            @if($comingSoon)
                <span class="xp-btn-ghost xp-btn-off inline-flex items-center px-7 py-4 rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
            @elseif($fromPrice)
                <a href="#pricing" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">ซื้อ License — เริ่ม ฿{{ $fromPrice }}</a>
            @endif
            <a href="#features" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ดูการทำงาน</a>
        </x-slot:actions>
        <x-slot:notes>
            @if(session('success'))
                <p class="mt-4 text-sm text-emerald-200/90">{{ session('success') }}</p>
            @endif
            <p class="mt-4 text-sm text-gray-400">1 License ใช้ได้ {{ $sites }} เว็บ · ต้องใช้คู่กับแอป SmsChecker บนมือถือ Android ที่รับ SMS ของบัญชีร้าน</p>
            @if(isset($userLicense) && $userLicense)
                @if(! $userLicense->isValid())
                    <p class="mt-3 text-sm text-red-300">
                        License ของคุณหมดอายุแล้ว @if($userLicense->expires_at)เมื่อ {{ $userLicense->expires_at->format('d/m/Y') }}@endif ·
                        <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline hover:text-white">ต่ออายุตอนนี้</a>
                    </p>
                @elseif($userLicense->expires_at && $userLicense->license_type !== 'lifetime' && $userLicense->daysRemaining() <= 7)
                    <p class="mt-3 text-sm text-amber-200">
                        License ใกล้หมดอายุ — เหลืออีก {{ $userLicense->daysRemaining() }} วัน ({{ $userLicense->expires_at->format('d/m/Y') }}) ·
                        <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline hover:text-white">ต่ออายุตอนนี้</a>
                    </p>
                @else
                    <p class="mt-3 text-sm text-emerald-200/90">
                        คุณมี License แล้ว · {{ $userLicense->expires_at && $userLicense->license_type !== 'lifetime' ? 'หมดอายุ ' . $userLicense->expires_at->format('d/m/Y') : 'ไม่หมดอายุ' }} ·
                        <a href="{{ route('customer.licenses.show', $userLicense) }}" class="font-semibold underline decoration-emerald-300/50 hover:text-white">ดู License ของฉัน</a>
                    </p>
                @endif
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> ธนาคารไทย 14 แห่ง + พร้อมเพย์</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> เข้ารหัส AES-256-GCM</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> 1 License = {{ $sites }} เว็บ</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('wp-orders.webp')" :eager="true" :mock="true" :width="1600" :height="1000"
                             alt="ภาพตัวอย่างหน้า Pending Orders ของปลั๊กอินในแผงควบคุม WordPress: ออเดอร์รอชำระพร้อมยอดทศนิยมเฉพาะ และ SMS ที่ยังไม่จับคู่" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">ยอดที่ต้องโอน · Unique amount</p>
                <p class="text-white font-extrabold text-lg leading-tight">฿1,250<span class="text-emerald-300">.37</span></p>
            </div>
            <div class="xp-float-2 absolute -left-3 sm:-left-8 -top-8 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">โหมดอนุมัติ · Approval</p>
                <p class="text-white font-bold leading-tight">อัตโนมัติ · ด้วยตนเอง · อัจฉริยะ</p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================== HOW IT WORKS ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="How it works" title="ลูกค้าโอน SMS เด้ง" title2="ออเดอร์ขยับเอง"
                       sub="ปลั๊กอินอยู่บนเว็บ WooCommerce ของคุณ ส่วนแอป SmsChecker บนมือถือของร้านส่ง SMS เงินเข้ามาให้ปลั๊กอินจับคู่"
                       en="The plugin runs on your WooCommerce site; the SmsChecker app on the shop phone feeds it the bank SMS." />
            <x-xp.rows :items="$coreRows" />

            <x-xp.cards class="mt-16" :items="[
                ['icon' => 'bolt', 'title' => 'ยอดทศนิยมเฉพาะ', 'body' => 'ทุกออเดอร์ได้เศษสตางค์ของตัวเอง ยอดเดียวกันรอพร้อมกันได้สูงสุด 99 ออเดอร์ ยอดค้างหมดอายุตามเวลาที่ตั้ง (ค่าเริ่มต้น 30 นาที ตั้งได้ 5 นาทีถึง 24 ชั่วโมง)', 'en' => 'A unique satang amount per order'],
                ['icon' => 'doc', 'title' => 'บอกลูกค้าชัดเจน', 'body' => 'หน้าขอบคุณและอีเมลออเดอร์บอกยอดที่ต้องโอน ชื่อธนาคาร และเลขบัญชีของร้าน ตามข้อความที่คุณตั้งเอง', 'en' => 'The exact amount on the thank-you page and email'],
                ['icon' => 'user', 'title' => '3 โหมดอนุมัติ', 'body' => 'อนุมัติอัตโนมัติเมื่อตรงยอด ตรวจด้วยตนเองทุกออเดอร์ หรือโหมดอัจฉริยะ (ชัดเจนอนุมัติ ไม่ชัดเจนรอตรวจ)', 'en' => 'Auto, manual or smart approval'],
                ['icon' => 'phone', 'title' => 'หลายมือถือ หลายบัญชี', 'body' => 'เพิ่มอุปกรณ์ได้หลายเครื่องในหน้า Devices แต่ละเครื่องมี API key และรหัสลับของตัวเอง พร้อม QR สำหรับตั้งค่า', 'en' => 'Each device has its own key and secret'],
                ['icon' => 'lock', 'title' => 'เข้ารหัสทุกครั้ง', 'body' => 'ข้อมูลระหว่างมือถือกับเว็บเข้ารหัสด้วย AES-256-GCM และตรวจลายเซ็น HMAC ก่อนรับทุกครั้ง', 'en' => 'AES-256-GCM with HMAC signatures'],
                ['icon' => 'server', 'title' => 'อยู่บนเว็บของคุณ', 'body' => 'ปลั๊กอินเพิ่ม REST API ในเว็บ WordPress ของคุณเอง SMS และออเดอร์อยู่ในฐานข้อมูลของเว็บคุณ', 'en' => 'Runs inside your own WordPress'],
            ]" />

            <div class="mt-12 text-center">
                <p class="text-sm text-gray-400 mb-4"><x-bi th="ธนาคารที่ปลั๊กอินรู้จัก" en="Banks the plugin knows" /></p>
                <div class="flex flex-wrap justify-center gap-2 max-w-4xl mx-auto">
                    @foreach($banks as $bank)
                        <span class="xp-chip">{{ $bank }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="License ปลั๊กอิน" title2="1 License ต่อ {{ $sites }} เว็บ"
                       sub="ทุกแผนได้ปลั๊กอินตัวเต็มเหมือนกัน ต่างกันที่อายุ License · แอป SmsChecker บนมือถือคิด License แยกจากปลั๊กอิน"
                       en="Every plan is the full plugin; only the term differs. The SmsChecker phone app is licensed separately." />

            <div class="grid md:grid-cols-3 gap-5 max-w-5xl mx-auto">
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
                                <x-xp.badge tier="pro" :label="$plan['en']" />
                            </div>
                            <p class="text-amber-100/80 text-sm mb-6">{{ $plan['note'] }}</p>
                            <div class="mb-6 flex items-baseline gap-2">
                                <span class="text-5xl font-black text-white">฿{{ number_format($prices[$term]) }}</span>
                                <span class="text-gray-300">{{ $plan['unit'] }}</span>
                            </div>
                            <x-tpix-price variant="detail" :thb="$prices[$term]" class="-mt-4 mb-6" />
                            <x-xp.ticks pro class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ปลั๊กอินตัวเต็ม', 'ใช้ได้ ' . $sites . ' เว็บ', 'อายุ License: ' . $plan['days']]" />
                            @if($comingSoon)
                                <span class="xp-btn-ghost xp-btn-off block w-full py-3.5 text-center rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
                            @else
                                {{-- ราคาคิดจาก license_type ที่ CartController (config) ไม่ใช่จากฟอร์ม --}}
                                <form action="{{ route('cart.add', $product) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="quantity" value="1">
                                    <input type="hidden" name="license_type" value="{{ $term }}">
                                    <button type="submit" class="{{ $featured ? 'xp-btn-pro' : 'xp-btn-ghost' }} block w-full py-3.5 text-center rounded-xl font-extrabold transition cursor-pointer">
                                        ใส่ตะกร้า — ฿{{ number_format($prices[$term]) }}
                                    </button>
                                </form>
                            @endif
                            @if(isset($userLicense) && $userLicense)
                                <a href="{{ route('customer.licenses.show', $userLicense) }}" class="xp-link block text-center text-sm mt-4">ดู License ของฉัน →</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-center text-xs text-gray-500 mt-8">ชำระผ่านตะกร้าของเว็บ · ได้ License key ในหน้าบัญชีของฉัน แล้วใส่ในหน้าตั้งค่าของปลั๊กอิน</p>
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['WordPress 5.8 ขึ้นไป', 'PHP 8.0 ขึ้นไป', 'WordPress 5.8+ and PHP 8.0+'],
            ['WooCommerce 6.0 ขึ้นไป', 'ปลั๊กอินเพิ่มช่องทางชำระในหน้า WooCommerce ▸ Payments', 'WooCommerce 6.0+'],
            ['เว็บเป็น HTTPS', 'มือถือส่งข้อมูลเข้าเว็บผ่าน REST API ของปลั๊กอิน', 'Your site over HTTPS'],
            ['มือถือ Android ของร้าน', 'ลงแอป SmsChecker บนเครื่องที่รับ SMS ของบัญชีรับเงิน · แอปมี License แยกหลังทดลอง 24 ชั่วโมง', 'An Android phone running SmsChecker'],
        ]"
        :steps="[
            ['ติดตั้งและเปิดใช้ปลั๊กอิน', 'อัปโหลดปลั๊กอินในหน้า Plugins ของ WordPress แล้วใส่ License key ในหน้าตั้งค่า', 'Install, activate, enter your key'],
            ['ตั้งค่าช่องทางชำระ', 'เปิด “Bank Transfer (SMS Verified)” ใน WooCommerce แล้วใส่ชื่อธนาคาร เลขบัญชี และข้อความแนะนำลูกค้า', 'Set up the payment method'],
            ['เพิ่มมือถือในหน้า Devices', 'สร้างอุปกรณ์ให้มือถือของร้าน แล้วตั้งค่าแอปบนมือถือด้วยคีย์ของอุปกรณ์นั้น', 'Add the shop phone as a device'],
        ]" />

    <x-xp.faq :items="[
        ['SMS Payment Checker คืออะไร?', 'ปลั๊กอิน WordPress/WooCommerce ที่เพิ่มช่องทางชำระแบบโอนผ่านธนาคาร ให้แต่ละออเดอร์มียอดทศนิยมเฉพาะ แล้วจับคู่กับ SMS เงินเข้าที่มือถือของร้านส่งมา เพื่ออนุมัติออเดอร์อัตโนมัติหรือให้คุณกดยืนยันเอง'],
        ['ต่างจากแอป SmsChecker อย่างไร?', 'ปลั๊กอินอยู่บนเว็บ WordPress ของคุณ ทำหน้าที่ออกยอดและจับคู่ออเดอร์ ส่วนแอป SmsChecker อยู่บนมือถือ Android ทำหน้าที่อ่าน SMS ธนาคารแล้วส่งเข้าเว็บ ต้องใช้ทั้งสองอย่าง และ License แยกกัน'],
        ['รองรับธนาคารไหนบ้าง?', implode(' · ', $banks)],
        ['1 License ใช้ได้กี่เว็บ?', $sites . ' เว็บต่อ 1 License — License ผูกกับเว็บที่ติดตั้ง ถ้ามีหลายเว็บให้ซื้อ License ตามจำนวนเว็บ'],
        ['ลูกค้าโอนไม่ตรงเศษสตางค์ทำอย่างไร?', 'SMS นั้นจะขึ้นในช่อง Unmatched SMS ของหน้า Pending Orders ให้คุณตรวจ แล้วกดยืนยันออเดอร์ที่ถูกต้องเองได้'],
        ['ราคาเท่าไร?', collect($plans)->filter(fn ($p, $t) => isset($prices[$t]))->map(fn ($p, $t) => $p['name'] . ' ฿' . number_format($prices[$t]))->implode(' · ') . ' — ทุกแผนเป็นปลั๊กอินตัวเต็ม'],
        ['ใช้กับเว็บที่ไม่ใช่ WordPress ได้ไหม?', 'ปลั๊กอินนี้ใช้กับ WordPress + WooCommerce เท่านั้น ถ้าเว็บของคุณสร้างด้วยระบบอื่น ทักทีมงานผ่านหน้าขอใบเสนอราคาหรือบริการพัฒนาเว็บได้'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="ให้ SMS ธนาคาร" glow="ยืนยันออเดอร์แทนคุณ"
              :sub="$fromPrice ? 'License ปลั๊กอินเริ่ม ฿' . $fromPrice . ' · 1 License ต่อ ' . $sites . ' เว็บ' : 'ปลั๊กอิน WordPress/WooCommerce'"
              en="Let the bank SMS confirm your WooCommerce orders.">
        @if($comingSoon)
            <span class="xp-btn-ghost xp-btn-off inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
        @else
            <a href="#pricing" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ดูราคา License</a>
        @endif
        <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ขอใบเสนอราคา</a>
        @if($appOnSale)
            <a href="{{ route('smschecker.detail') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">แอป SmsChecker</a>
        @endif
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">
                เว็บไม่ได้ใช้ WordPress? <a href="{{ route('services.index') }}" class="xp-link">ดูบริการพัฒนาเว็บของเรา</a>
            </p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
