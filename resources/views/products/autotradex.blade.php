@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย และซื้อไม่ได้ (กฎเจ้าของ 2026-10-12) --}}
@php $csDownload = \App\Models\Product::slugIsComingSoon('autotradex'); @endphp

@section('title', 'AutoTradeX — บอทเทรดคริปโต Arbitrage ข้าม 6 กระดาน · ลองด้วยเงินเสมือนฟรี · License เริ่ม ฿' . number_format(\App\Support\LicensePlans::price('autotradex', 'monthly') ?? 0) . '/เดือน | XMAN Studio')
@section('meta_description', 'AutoTradeX โปรแกรม Windows เฝ้าราคาเหรียญเดียวกันบน Binance KuCoin OKX Bybit Gate.io และ Bitkub แล้วซื้อฝั่งถูกขายฝั่งแพงให้อัตโนมัติ มี Smart Scanner และ AI Trading 6 กลยุทธ์ ลองทุกหน้าจอด้วยเงินเสมือน $10,000 ฟรี ทดลองบอทอัตโนมัติ 7 วัน · Cross-exchange crypto arbitrage bot for Windows.')
@section('og_image', asset('images/products/autotradex/v2/og.jpg'))

@section('content')
@php
    // ราคาทุกแผนมาจาก config/licenses.php 'plans' ที่เดียว (checkout /autotradex/checkout/{plan} คิดตามนี้) — ห้ามเขียนตัวเลขราคาลงหน้านี้ตรง ๆ
    $prices = \App\Support\LicensePlans::for('autotradex');
    $monthly = number_format($prices['monthly'] ?? 0);
    $yearlySaving = \App\Support\LicensePlans::yearlySaving('autotradex');

    // ซื้อแล้วก็ยังซื้อเพิ่มได้ — 1 คีย์ใช้ได้ 1 เครื่อง (max_activations = LicensePlans::devicesPerKey = 1)
    $ownsAutoTradeX = auth()->check() && $hasPurchased;

    // เวอร์ชันที่เว็บนี้ส่งให้ (อ่านจาก DB อย่างเดียว)
    $latest = $product->latestVersion();

    // ภาพหน้าจอ: ยังไม่มีภาพจริงของแอป ทุกภาพเป็น mockup วาดตาม XAML ของ v0.3.0 (ข้อมูลในภาพเป็นตัวเลขสมมุติ)
    // สร้างใหม่ได้ด้วย scripts/product-mockups/build.py autotradex
    $shot = fn (string $file) => asset('images/products/autotradex/v2/' . $file);

    $plans = [
        'monthly' => ['name' => 'รายเดือน', 'en' => 'Monthly', 'unit' => '/ เดือน', 'note' => 'ซื้อทีละ 30 วัน ไม่ตัดเงินอัตโนมัติ', 'days' => '30 วัน'],
        'yearly' => ['name' => 'รายปี', 'en' => 'Yearly', 'unit' => '/ ปี', 'note' => $yearlySaving ? 'ถูกกว่ารายเดือน ' . $yearlySaving . '%' : 'จ่ายครั้งเดียวใช้ทั้งปี', 'days' => '365 วัน'],
        'lifetime' => ['name' => 'ตลอดชีพ', 'en' => 'Lifetime', 'unit' => 'ครั้งเดียว', 'note' => 'จ่ายครั้งเดียว ใช้ได้ตลอด', 'days' => 'ไม่หมดอายุ'],
    ];

    // หน้าจอหลัก — ข้อเท็จจริงจากโค้ดแอป (ArbEngine, ArbitrageMode, SmartScannerService, TradeHistoryService)
    $mainRows = [
        [
            'img' => $shot('trading.webp'), 'mock' => true, 'name' => 'บอท Arbitrage ข้ามกระดาน', 'eyebrow' => 'Cross-exchange arbitrage',
            'th' => 'เทียบราคาเหรียญคู่เดียวกันบนสองกระดานตลอดเวลา เมื่อส่วนต่างเกินขั้นต่ำที่คุณตั้ง บอทซื้อฝั่งที่ถูกและขายฝั่งที่แพงให้ คิดค่าธรรมเนียมทั้งสองฝั่งก่อนประเมินกำไร',
            'en' => 'Watches one pair on two exchanges, buys the cheap side and sells the dear side once the spread clears your minimum, fees counted.',
            'points' => [
                'โหมดสองกระเป๋า (ค่าเริ่มต้น): ถือ USDT ไว้กระดานหนึ่ง เหรียญไว้อีกกระดาน ซื้อ-ขายพร้อมกัน ไม่ต้องรอโอน',
                'โหมดโอนจริง: บอทซื้อแล้วบอกที่อยู่ฝากและค่าถอนให้ คุณโอนเองแล้วกด "ฉันโอนแล้ว" บอทขายต่อให้',
                'จัดคู่เหรียญเป็นโปรเจกต์ได้ โปรเจกต์ละไม่เกิน 10 คู่',
            ],
            'alt' => 'ภาพตัวอย่างหน้า Trading ของ AutoTradeX: ราคาซื้อที่ Binance ราคาขายที่ KuCoin ส่วนต่าง และขั้นตอนโหมดโอนจริง',
        ],
        [
            'img' => $shot('scanner.webp'), 'mock' => true, 'name' => 'Smart Scanner', 'eyebrow' => 'Find the pair worth trading',
            'th' => 'สแกนทุกคู่เหรียญบนกระดานที่เชื่อมไว้ แล้วเรียงตามคะแนน เลือกได้ว่าจะหาอะไร: ส่วนต่างระหว่างกระดาน ราคาร่วง ความผันผวนสูง Volume พุ่ง โมเมนตัมขาขึ้น หรือเหรียญที่ขึ้นแรงสุดวันนี้',
            'en' => 'Scans every pair on your connected exchanges and ranks them by score, with six ways to look.',
            'points' => ['ตั้งส่วนต่างขั้นต่ำ ความถี่สแกน และคะแนนขั้นต่ำเองได้', 'โหมดอัตโนมัติ: ให้บอทหยิบคู่ที่ดีที่สุดเอง และแจ้งเตือนเมื่อเจอโอกาส'],
            'alt' => 'ภาพตัวอย่างหน้า Scanner ของ AutoTradeX: รายการคู่เหรียญ เส้นทางซื้อขาย ส่วนต่าง และคะแนน',
        ],
        [
            'img' => $shot('analytics.webp'), 'mock' => true, 'name' => 'ประวัติและวิเคราะห์ผล', 'eyebrow' => 'Every trade on record',
            'th' => 'ทุกเทรดถูกเก็บไว้ในเครื่อง ดูกำไร-ขาดทุนแยกตามคู่เหรียญ อัตราชนะ ค่าธรรมเนียมรวม กำไรสะสม และรายละเอียดทีละเทรด เลือกช่วงวันได้ แล้วส่งออกเป็น CSV',
            'en' => 'Every trade kept on your PC: profit by pair, win rate, fees, cumulative P&L, exportable to CSV.',
            'points' => [],
            'alt' => 'ภาพตัวอย่างหน้า Trade Analytics ของ AutoTradeX: สรุปผล กำไรขาดทุนแยกตามคู่เทรด และกราฟกำไรสะสม',
        ],
    ];

    // AI Trading — ตรงกับ AITradingService / AITradingModels / MarketIntelligenceService ของแอป
    $aiTools = [
        [
            'key' => 'ai', 'name' => 'หน้า AI Trading', 'short' => 'กราฟ · สัญญาณ · Position',
            'img' => $shot('ai-trading.webp'), 'mock' => true,
            'th' => 'เลือกกระดาน คู่เหรียญ และกลยุทธ์ แล้วให้บอทออกสัญญาณซื้อ-ขายพร้อมความมั่นใจเป็นเปอร์เซ็นต์ ราคาเข้า จุดทำกำไร และจุดตัดขาดทุน เปิด Position ได้เองหรือให้บอทเทรดตามสัญญาณ',
            'en' => 'Pick an exchange, a pair and a strategy; the bot signals buy or sell with its confidence, entry, take-profit and stop-loss.',
            'points' => ['เปิดมาเป็นโหมด DEMO เสมอ ใช้กระเป๋าเงินเสมือน $10,000', 'สลับไปโหมดเงินจริงต้องหยุดบอทก่อนและกดยืนยัน', 'ถือได้พร้อมกันสูงสุด 10 คู่เป็นแท็บ'],
            'alt' => 'ภาพตัวอย่างหน้า AI Trading ของ AutoTradeX: กราฟแท่งเทียน สัญญาณซื้อ ความมั่นใจ และ Position ปัจจุบัน',
        ],
        [
            'key' => 'strategies', 'name' => '6 กลยุทธ์', 'short' => 'Scalping ถึง Smart DCA',
            'th' => 'แต่ละกลยุทธ์มีวิธีคิดสัญญาณของตัวเองจากตัวชี้วัดทางเทคนิค หรือเปิด Auto Strategy ให้บอทเลือกกลยุทธ์ตามสภาพตลาดตอนนั้น',
            'en' => 'Six strategies, each with its own signal logic, or Auto Strategy to pick one by market regime.',
            'points' => ['Scalping: RSI สุดขั้ว + spread แคบ', 'Momentum: EMA ตัดกัน + MACD + SMA50', 'Mean Reversion: Bollinger Bands + RSI', 'Grid Trading: ระยะกริดจาก ATR + แนวรับแนวต้าน', 'Breakout: ทะลุ Bollinger Bands พร้อม Volume ยืนยัน', 'Smart DCA: จังหวะสะสมจาก RSI + SMA200'],
        ],
        [
            'key' => 'intel', 'name' => 'ข่าวและหลายกรอบเวลา', 'short' => 'ข่าว · Fear & Greed · 15m/1h/4h',
            'th' => 'ก่อนออกสัญญาณ บอทดูแนวโน้มกรอบ 15 นาที 1 ชั่วโมง และ 4 ชั่วโมงประกอบกัน อ่านหัวข้อข่าวคริปโตล่าสุด ดัชนี Fear & Greed และสถิติ 30 วันของเหรียญ แล้วปรับความมั่นใจของสัญญาณขึ้นหรือลง',
            'en' => 'Signals are weighed against 15m/1h/4h trends, recent crypto headlines, the Fear & Greed index and 30-day coin stats.',
            'points' => ['ข่าวร้ายแรงหรือกรอบใหญ่สวนทาง: เปลี่ยนสัญญาณซื้อเป็นรอ (ไม่สั่งขายเองเพราะข่าว)', 'ข้อมูลภายนอกดึงไม่ได้ บอทก็ยังทำงานต่อด้วยตัวชี้วัดอย่างเดียว'],
        ],
        [
            'key' => 'risk', 'name' => 'คุมความเสี่ยง', 'short' => 'TP/SL · ขาดทุนต่อวัน · หยุดฉุกเฉิน',
            'th' => 'ทุกเทรดมีจุดทำกำไรและจุดตัดขาดทุน จำกัดจำนวนเทรดต่อชั่วโมงและยอดขาดทุนต่อวัน เมื่อแตะเพดานบอทหยุดเอง และมีปุ่ม EMERGENCY STOP ปิด Position แล้วหยุดทันที',
            'en' => 'Take-profit and stop-loss on every trade, trades-per-hour and daily-loss caps, and an emergency stop.',
            'points' => ['บอท Arbitrage หยุดเมื่อขาดทุนต่อวันหรือ Drawdown เกินที่ตั้ง', 'ขนาดไม้ลดลงเองเมื่อสัญญาณมั่นใจน้อย ไม่เกินยอดสูงสุดที่ตั้งไว้'],
        ],
    ];

    // ตารางเทียบ — ไม่มี License = Demo (SimulationExchangeClient) · License ที่ใช้งานได้ = เทรดจริง (App.xaml.cs, MainWindow.StartBot_Click)
    $compare = [
        ['ใช้ได้ทันที · Demo', [
            ['ราคาเรียลไทม์ + Smart Scanner 6 แบบ', false],
            ['เชื่อม API Key ของ 6 กระดาน', false],
            ['AI Trading ด้วยกระเป๋าเงินเสมือน $10,000', false],
            ['ประวัติ · วิเคราะห์ผล · ส่งออก CSV', false],
        ]],
        ['License · PRO', [
            ['บอท Arbitrage อัตโนมัติ (7 วันแรกทดลองฟรีด้วยเงินจำลอง)', true],
            ['บอท Arbitrage ส่งคำสั่งซื้อขายจริงบนกระดาน', true],
        ]],
    ];

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'AutoTradeX',
        'operatingSystem' => 'Windows 10/11 (64-bit)',
        'applicationCategory' => 'FinanceApplication',
        'inLanguage' => ['th', 'en'],
        'description' => 'บอทเทรดคริปโตแบบ Arbitrage ข้าม Binance KuCoin OKX Bybit Gate.io และ Bitkub พร้อม Smart Scanner และ AI Trading 6 กลยุทธ์ ลองด้วยเงินเสมือนได้ก่อนเทรดจริง',
        'image' => asset('images/products/autotradex/v2/og.jpg'),
        'url' => route('products.show', 'autotradex'),
        'publisher' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => array_values(array_merge(
            [['@type' => 'Offer', 'name' => 'Demo', 'price' => '0', 'priceCurrency' => 'THB']],
            collect($prices)->map(fn ($price, $term) => ['@type' => 'Offer', 'name' => 'License (' . ($plans[$term]['en'] ?? $term) . ')', 'price' => (string) $price, 'priceCurrency' => 'THB'])->values()->all(),
        )),
    ];
    if ($latest) {
        $ld['softwareVersion'] = $latest->version;
    }
@endphp
<x-xp.page a="#34d399" b="#a3e635" c="#2dd4bf" glow="45 212 191" g1="#ffcf3a" g2="#34d399" g3="#22d3ee">

    {{-- ============================ HERO ============================ --}}
    <x-xp.hero :art="$shot('art-hero.webp')"
               tagline="บอทเทรดคริปโต Arbitrage ข้าม 6 กระดาน"
               tagline-en="A cross-exchange crypto arbitrage bot for Windows."
               lead="เฝ้าราคาเหรียญเดียวกันบน Binance, KuCoin, OKX, Bybit, Gate.io และ Bitkub พร้อมกัน เจอส่วนต่างเกินที่คุณตั้งก็ซื้อฝั่งถูกขายฝั่งแพงให้อัตโนมัติ มี Smart Scanner และ AI Trading 6 กลยุทธ์ — ลองทุกหน้าจอด้วยเงินเสมือน $10,000 ก่อนใช้เงินจริง"
               lead-en="Watches six exchanges at once and trades the spread for you, with a scanner and six AI strategies. Try it all with $10,000 of demo money first.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>{{ $latest ? 'AutoTradeX v' . $latest->version : 'AutoTradeX' }}</span>
            <span class="xp-chip">Windows 10/11 · 64-bit</span>
        </x-slot:chips>
        <x-slot:title>Auto<span class="xp-text-glow">TradeX</span></x-slot:title>
        <x-slot:actions>
            {{-- ทุกคนโหลดได้ฟรี ไฟล์ส่งจาก xman4289.com เอง ห้ามลิงก์ไป GitHub (กฎเจ้าของ 2026-09-24) --}}
            <x-xp.download :href="route('autotradex.download')" :coming-soon="$csDownload" />
            <a href="#pricing" class="xp-btn-pro inline-flex items-center px-6 py-4 rounded-xl font-extrabold transition">
                {{ $ownsAutoTradeX ? 'ซื้อ License เพิ่ม' : 'ซื้อ License' }} — เริ่ม ฿{{ $monthly }}/เดือน
            </a>
        </x-slot:actions>
        <x-slot:notes>
            @if($latest)
                <p class="mt-4 text-sm text-gray-400">เวอร์ชันล่าสุด v{{ $latest->version }}@if($latest->file_size) · {{ $latest->file_size_formatted }}@endif · ไฟล์ .zip รุ่น Portable จาก xman4289.com โดยตรง</p>
            @endif
            @if($ownsAutoTradeX)
                <p class="mt-3 text-sm text-emerald-200/90">
                    คุณมี License แล้ว · 1 คีย์ใช้ได้ 1 เครื่อง — ซื้อเพิ่มได้สำหรับเครื่องอื่น ·
                    <a href="{{ route('customer.licenses') }}" class="font-semibold underline decoration-emerald-300/50 hover:text-white">ดูคีย์ License ของฉัน</a>
                </p>
            @endif
        </x-slot:notes>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="free" /> Demo เงินเสมือน $10,000</li>
            <li class="flex items-center gap-2 text-gray-300"><x-xp.badge tier="pro" /> License = เทรดจริง</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-dot"></span> API Key อยู่ในเครื่องคุณ</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                <x-xp.screen :src="$shot('dashboard.webp')" :eager="true" :mock="true"
                             alt="ภาพตัวอย่างหน้า Dashboard ของ AutoTradeX: ยอดรวม โหมด Arbitrage ราคาซื้อขายสองกระดาน ผลงาน และ Live Monitor" />
            </div>
            <div class="xp-float absolute -left-3 sm:-left-8 bottom-6 sm:bottom-10 xp-card backdrop-blur-md px-4 py-3 shadow-2xl">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">โหมดสองกระเป๋า · Dual-Balance</p>
                <p class="text-white font-extrabold text-lg leading-tight">ซื้อ-ขายพร้อมกัน <span class="text-emerald-300">ไม่ต้องรอโอน</span></p>
            </div>
            <div class="xp-float-2 absolute -right-2 sm:-right-6 -top-5 xp-card backdrop-blur-md px-4 py-3 shadow-2xl hidden sm:block">
                <p class="text-[11px] tracking-widest uppercase text-gray-400">6 กระดาน · 6 exchanges</p>
                <p class="text-white font-bold leading-tight">รวม Bitkub<br><span class="text-gray-400 text-sm font-normal">คู่เงินบาท THB ด้วย</span></p>
            </div>
        </x-slot:visual>
    </x-xp.hero>

    {{-- ========================== MAIN SCREENS ========================== --}}
    <section class="relative py-20" id="features">
        <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-10" style="background: rgb(var(--xp-glow) / .12)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-16" eyebrow="Demo · every screen, day one" title="ลองได้ทุกหน้าจอ" title2="ด้วยเงินเสมือน"
                       sub="ไม่มี License โปรแกรมทำงานในโหมด DEMO: ดูราคาจริง สแกนหาโอกาส และเทรดด้วยเงินเสมือน $10,000 ได้ทุกหน้าจอ ใส่ License เมื่อพร้อมให้บอทเทรดด้วยเงินจริง"
                       en="Without a licence it runs in demo mode: real prices, simulated money. Add a licence when you want the bot to trade for real." />
            <x-xp.rows :items="$mainRows" />
        </div>
    </section>

    {{-- =========================== AI TRADING =========================== --}}
    <section class="relative py-20 isolate" id="ai-trading">
        <x-xp.art :src="$shot('art-2.webp')" class="top-0 w-[130%] opacity-35 left-0 lg:-left-2 lg:w-[68%] lg:opacity-70" />
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head align="right" class="mb-10" eyebrow="AI Trading · 6 strategies" title="ให้บอทอ่านกราฟ" title2="บนกระดานเดียว"
                       sub="นอกจาก Arbitrage แล้ว AutoTradeX เทรดเหรียญบนกระดานเดียวตามสัญญาณได้ด้วย สัญญาณมาจากตัวชี้วัดทางเทคนิคของแต่ละกลยุทธ์ ผสานแนวโน้มหลายกรอบเวลา ข่าว และดัชนี Fear & Greed"
                       en="Beyond arbitrage, it trades one exchange on signals: technical indicators per strategy, weighed with multi-timeframe trend, news and Fear & Greed." />
            <x-xp.explorer id="ai" label="ฟีเจอร์ AI Trading ของ AutoTradeX" :tools="$aiTools" />
        </div>
    </section>

    {{-- ============================ SAFETY ============================ --}}
    <section class="relative py-20">
        <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Your keys, your money" title="เงินอยู่ในบัญชีคุณ" title2="กุญแจอยู่ในเครื่องคุณ"
                       sub="AutoTradeX สั่งซื้อขายผ่าน API Key ของคุณเอง เงินไม่เคยผ่าน XMAN Studio"
                       en="It trades through your own API keys. Your money never passes through us." />
            <x-xp.cards :items="[
                ['icon' => 'lock', 'title' => 'API Key เข้ารหัสในเครื่อง', 'body' => 'เก็บในฐานข้อมูลบนเครื่องคุณ เข้ารหัส AES-256 ด้วยกุญแจที่ผูกกับเครื่องนั้น', 'en' => 'Stored on your PC, AES-256 encrypted'],
                ['icon' => 'shield', 'title' => 'ไม่มีคำสั่งถอนเงิน', 'body' => 'โปรแกรมไม่ถอนเหรียญหรือเงินออกจากบัญชี จึงสร้าง API Key แบบปิดสิทธิ์ถอนได้ โหมดโอนจริงก็ให้คุณโอนเอง', 'en' => 'No withdrawals — keys can have withdraw off'],
                ['icon' => 'eye', 'title' => 'Demo ก่อนเสมอ', 'body' => 'AI Trading เปิดมาในโหมด DEMO สลับไปเงินจริงต้องหยุดบอทและกดยืนยันก่อน ไม่มี License บอท Arbitrage ใช้กระดานจำลอง', 'en' => 'Demo by default, real money only on purpose'],
                ['icon' => 'power', 'title' => 'EMERGENCY STOP', 'body' => 'ปุ่มเดียวปิด Position ที่เปิดอยู่และหยุดบอททันที ทั้งหน้า Trading และ AI Trading', 'en' => 'One button closes positions and stops the bot'],
                ['icon' => 'chart', 'title' => 'เพดานขาดทุน', 'body' => 'ตั้งยอดขาดทุนต่อวัน Drawdown สูงสุด และจำนวนเทรดต่อชั่วโมง แตะเพดานเมื่อไรบอทหยุดเอง', 'en' => 'Daily-loss and drawdown caps stop the bot'],
                ['icon' => 'globe', 'title' => 'USDT และเงินบาท', 'body' => 'เทียบคู่ THB บน Bitkub กับคู่ USDT บนกระดานอื่นได้ โปรแกรมแปลงค่าเงินให้จากอัตราล่าสุด', 'en' => 'THB pairs on Bitkub, converted for you'],
            ]" />
        </div>
    </section>

    {{-- =========================== PRICING =========================== --}}
    <section class="relative py-20 scroll-mt-20" id="pricing">
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32 bg-amber-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Pricing" title="ลองฟรีก่อน" title2="พร้อมเทรดจริงค่อยซื้อ"
                       sub="ไฟล์เดียวกันทั้ง Demo และ License · 1 คีย์ใช้ได้ 1 เครื่อง · เปิดโปรแกรมครั้งแรกได้ทดลองบอทอัตโนมัติ 7 วัน (ครั้งเดียวต่อเครื่อง)"
                       en="One download. One key per PC. The first launch starts a 7-day trial of the automatic bot." />

            <div class="grid md:grid-cols-2 xl:grid-cols-4 gap-5 max-w-6xl mx-auto">
                <div class="xp-card p-7 flex flex-col">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-2xl font-black text-white">Demo</h3>
                        <x-xp.badge tier="free" />
                    </div>
                    <p class="text-gray-400 text-sm mb-6"><x-bi th="ทุกหน้าจอด้วยเงินเสมือน" en="Every screen, demo money" /></p>
                    <div class="mb-6"><span class="text-5xl font-black text-white">฿0</span></div>
                    <x-xp.ticks class="!space-y-2.5 text-sm mb-8 flex-1" :items="['ราคาเรียลไทม์ + Smart Scanner', 'AI Trading ด้วยเงินเสมือน $10,000', 'ประวัติ + วิเคราะห์ผล', 'บอท Arbitrage ทดลอง 7 วัน (เงินจำลอง)']" />
                    {{-- ไฟล์เดียวกับรุ่นมี License — ใส่คีย์ในโปรแกรมเพื่อเทรดจริง --}}
                    <x-xp.download block :href="route('autotradex.download')" :coming-soon="$csDownload" />
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
                                <x-xp.badge tier="pro" />
                            </div>
                            <p class="text-amber-100/80 text-sm mb-6">{{ $plan['note'] }}</p>
                            <div class="mb-6 flex flex-wrap items-baseline gap-x-2">
                                <span class="text-5xl font-black text-white">฿{{ number_format($prices[$term]) }}</span>
                                <span class="text-gray-300 whitespace-nowrap">{{ $plan['unit'] }}</span>
                            </div>
                            <x-tpix-price variant="detail" :thb="$prices[$term]" class="-mt-4 mb-6" />
                            <x-xp.ticks pro class="!space-y-2.5 text-sm mb-8 flex-1" :items="array_merge(
                                ['ทุกอย่างในรุ่น Demo', 'บอท Arbitrage เทรดจริงบนกระดาน', 'อายุ License: ' . $plan['days']],
                                $term === 'lifetime' ? ['ย้ายเครื่องเองได้ (Reset Device) ทุก 30 วัน'] : []
                            )" />
                            {{-- ราคามาจาก config/licenses.php ไม่ใช่จากลิงก์ — checkout ของ AutoTradeX คิดตามแผนที่ส่งไป และผูกกับ machine_id ถ้าเปิดมาจากแอป --}}
                            @if($csDownload)
                                <span class="xp-btn-ghost xp-btn-off block w-full py-3.5 text-center rounded-xl font-extrabold" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดขาย</span>
                            @else
                                <a href="{{ route('autotradex.checkout', $term) }}" class="{{ $featured ? 'xp-btn-pro' : 'xp-btn-ghost' }} block w-full py-3.5 text-center rounded-xl font-extrabold transition" data-plan="{{ $term }}">
                                    {{ $ownsAutoTradeX ? 'ซื้อ License เพิ่ม' : 'ซื้อ' }} {{ $plan['name'] }} — ฿{{ number_format($prices[$term]) }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-center mt-6 text-sm">
                @if($ownsAutoTradeX)
                    <a href="{{ route('customer.licenses') }}" class="xp-link font-semibold">มี License แล้ว? ดูคีย์ของคุณ →</a>
                    <span class="text-gray-600 mx-2">·</span>
                @endif
                <a href="{{ route('autotradex.pricing') }}" class="xp-link font-semibold">ซื้อจากในโปรแกรมระหว่างทดลอง? ดูส่วนลด Early Bird →</a>
            </p>
            <p class="text-center text-xs text-gray-500 mt-4">ซื้อแล้วได้ License key ทางอีเมลและในหน้า บัญชีของฉัน · ใส่คีย์กับอีเมลที่ใช้ซื้อในหน้าต่าง License ของโปรแกรม ไม่ต้องลงใหม่ · ย้ายเครื่อง: กด Deactivate บนเครื่องเดิมแล้ว Activate บนเครื่องใหม่</p>

            <x-xp.compare class="mt-14" :groups="$compare" free-label="Demo" pro-label="License" note="ไม่มี License หรือ License หมดอายุ: บอท Arbitrage ใช้กระดานจำลองเท่านั้น และหลังทดลอง 7 วันปุ่ม START BOT ถูกล็อกจนกว่าจะใส่ License" />

            <div class="xp-card max-w-3xl mx-auto mt-10 p-6" role="note">
                <p class="text-amber-300 font-bold mb-2">คำเตือนความเสี่ยง · Risk warning</p>
                <p class="text-sm text-gray-300 leading-relaxed">การเทรดคริปโตมีความเสี่ยงสูง ราคาเปลี่ยนเร็ว ส่วนต่างอาจหายไปก่อนคำสั่งครบทั้งสองฝั่ง และอาจขาดทุนเงินทุนทั้งหมด AutoTradeX เป็นเครื่องมือช่วยเทรด ไม่ใช่คำแนะนำการลงทุน และไม่รับประกันกำไร ผลในอดีตหรือในโหมด Demo ไม่ได้บอกผลในอนาคต</p>
                <p class="text-xs text-gray-500 mt-2">Crypto trading is high risk. AutoTradeX is a tool, not investment advice, and guarantees no profit.</p>
            </div>
        </div>
    </section>

    <x-xp.setup
        :specs="[
            ['ระบบปฏิบัติการ', 'Windows 10 หรือ 11 แบบ 64-bit', 'Windows 10/11 x64'],
            ['ไม่ต้องติดตั้ง .NET', 'รุ่น Portable รวมทุกอย่างไว้ใน zip เดียว แตกไฟล์แล้วเปิดได้เลย', 'Self-contained'],
            ['อินเทอร์เน็ต', 'ต้องต่อเน็ตตอนเปิดโปรแกรม เพื่อตรวจ License และดึงราคาจากกระดาน', 'Online to start'],
            ['บัญชีกระดานเทรด', 'API Key ของกระดานที่จะใช้ (Binance, KuCoin, OKX, Bybit, Gate.io, Bitkub) ใส่ในหน้า Settings', 'Your own exchange API keys'],
        ]"
        :steps="[
            ['ดาวน์โหลดไฟล์ .zip', 'ไฟล์มาจาก xman4289.com โดยตรง ใช้ได้ทั้ง Demo และ License', 'Download the .zip'],
            ['แตกไฟล์แล้วเปิด AutoTradeX', 'เปิดครั้งแรกโปรแกรมลงทะเบียนเครื่องและเริ่มทดลองบอทอัตโนมัติ 7 วันให้เอง', 'Unzip and open AutoTradeX'],
            ['ใส่ API Key แล้วลองใน Demo', 'กด Test Connection ในหน้า Settings ลองจนมั่นใจ แล้วกดปุ่ม Activate ใส่ License key เมื่อจะเทรดจริง', 'Add your keys, try it in demo, then activate'],
        ]" />

    <x-xp.faq :items="[
        ['ใช้ฟรีได้แค่ไหน?', 'ดาวน์โหลดฟรีไม่ต้องล็อกอิน ทุกหน้าจอใช้ได้ในโหมด Demo: ดูราคาจริง สแกนหาโอกาส ดูกราฟ และเทรด AI Trading ด้วยกระเป๋าเงินเสมือน $10,000 ส่วนบอท Arbitrage อัตโนมัติทดลองได้ 7 วันแรกด้วยเงินจำลอง'],
        ['ทดลอง 7 วันเริ่มเมื่อไร?', 'เปิดโปรแกรมครั้งแรกตอนต่อเน็ต โปรแกรมเริ่มทดลองให้เอง ครั้งเดียวต่อเครื่อง หมดแล้วยังใช้โหมด Demo ต่อได้ แต่บอท Arbitrage จะเริ่มไม่ได้จนกว่าจะใส่ License'],
        ['License ราคาเท่าไร ใช้ได้กี่เครื่อง?', 'รายเดือน ฿' . number_format($prices['monthly'] ?? 0) . ' · รายปี ฿' . number_format($prices['yearly'] ?? 0) . ' · ตลอดชีพ ฿' . number_format($prices['lifetime'] ?? 0) . ' — 1 คีย์ใช้ได้ 1 เครื่อง มีหลายเครื่องซื้อคีย์เพิ่มได้'],
        ['มีส่วนลดไหม?', 'มี กดซื้อจากปุ่มในโปรแกรมระหว่างทดลอง หรือภายใน 7 วันหลังทดลองหมด ได้ส่วนลด Early Bird 20% ครั้งเดียวต่อเครื่อง ราคาที่ลดแล้วแสดงในหน้าชำระเงิน'],
        ['ย้ายไปเครื่องใหม่ได้ไหม?', 'ได้ กด Deactivate ในหน้าต่าง License บนเครื่องเดิม แล้ว Activate คีย์เดิมบนเครื่องใหม่ ถ้าเครื่องเดิมใช้ไม่ได้แล้วและเป็น License ตลอดชีพ Reset Device เองได้ที่หน้า Reset Device ด้วยอีเมลที่ใช้ซื้อ (ทุก 30 วัน)'],
        ['บอททำกำไรได้แน่ไหม?', 'ไม่มีใครรับประกันได้ ส่วนต่างราคาอาจหายไปก่อนคำสั่งครบ ค่าธรรมเนียมและราคาที่ขยับระหว่างส่งคำสั่งกินกำไรได้ ควรลองในโหมด Demo ให้เข้าใจก่อน และเริ่มเงินจริงด้วยยอดที่เสียได้'],
        ['ต้องมีเงินไว้กี่กระดาน?', 'โหมดสองกระเป๋า (ค่าเริ่มต้น) ต้องมี USDT อยู่กระดานหนึ่งและเหรียญอยู่อีกกระดาน เพื่อซื้อ-ขายพร้อมกัน ถ้ามีเงินกระดานเดียวใช้โหมดโอนจริงได้ แต่ต้องโอนเหรียญเองและรอบล็อกเชนยืนยัน'],
        ['API Key ปลอดภัยไหม?', 'API Key เก็บในเครื่องคุณ เข้ารหัส AES-256 และโปรแกรมไม่มีคำสั่งถอนเงิน จึงควรสร้างคีย์แบบเปิดเฉพาะสิทธิ์อ่านและเทรด ปิดสิทธิ์ถอน'],
    ]" />

    {{-- =========================== FINAL CTA =========================== --}}
    <x-xp.cta :art="$shot('art-hero.webp')" title="ลองด้วยเงินเสมือน" glow="ก่อนใช้เงินจริง"
              :sub="'ดาวน์โหลดฟรีแล้วลองได้ทุกหน้าจอทันที พร้อมให้บอทเทรดจริงค่อยซื้อ License เริ่มเพียง ฿' . $monthly . ' ต่อเดือน'"
              en="Practise with demo money, then let the bot trade for real.">
        <x-xp.download :href="route('autotradex.download')" :coming-soon="$csDownload" class="!px-8" />
        <a href="#pricing" class="xp-btn-pro inline-flex items-center px-8 py-4 rounded-xl font-extrabold text-lg transition">ดูแผน License</a>
        @if($ownsAutoTradeX)
            <a href="{{ route('customer.licenses') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-semibold transition">ดูคีย์ License ของฉัน</a>
        @endif
        <x-slot:footnote>
            <p class="text-gray-500 text-sm mt-7">Windows 10/11 (64-bit) · ไม่ต้องลง .NET · ทดลองบอทอัตโนมัติ 7 วัน · 1 คีย์ = 1 เครื่อง</p>
        </x-slot:footnote>
    </x-xp.cta>

</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
