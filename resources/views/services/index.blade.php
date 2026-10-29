@extends($publicLayout ?? 'layouts.app')

@section('title', 'บริการของเรา - XMAN Studio')
@section('meta_description', 'บริการ IT ครบวงจร ตั้งแต่ Blockchain, AI, Web & Mobile ไปจนถึงระบบ IoT และความปลอดภัย · ดูราคาแต่ละบริการ แล้วสั่งงานรับใบเสนอราคาได้ทันที')

@section('content')
@php
    // ทุกหมวด ทุกบริการ และทุกราคามาจาก QuotationCategory / QuotationOption ที่แอดมินตั้ง —
    // ส่วนลดมาจาก Pricing::saleDiscount() ตัวเดียวกับที่ตัวคิดราคาใช้ ห้ามพิมพ์ราคาลงหน้านี้
    $optionCount = $categories->sum(fn ($category) => $category->activeOptions->count());
    $maxSale = $categories->map(fn ($category) => (int) round(\App\Support\Quotation\Pricing::saleDiscount($category->key) * 100))->max();
    $art = asset('artwork/hero-services.webp');

    $chatbotPoints = [
        ['icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 'title' => 'เข้าใจภาษาธรรมชาติ', 'body' => 'รองรับภาษาไทยและอังกฤษ เข้าใจบริบท คำสแลง และ Typo ได้อย่างแม่นยำ'],
        ['icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'ให้บริการ 24/7', 'body' => 'ตอบคำถามลูกค้าได้ตลอด 24 ชั่วโมง ไม่มีวันหยุด ไม่ต้องรอคิว'],
        ['icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'title' => 'ตอบกลับทันทีใน 3 วินาที', 'body' => 'ลด Bounce Rate เพิ่ม Conversion ลูกค้าได้รับคำตอบโดยไม่ต้องรอ'],
        ['icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'title' => 'ลดต้นทุน CS สูงถึง 60%', 'body' => 'รับมือคำถามซ้ำๆ ได้เอง ทีมโฟกัสกับเคสที่ซับซ้อนจริงๆ'],
    ];
    $tradePoints = [
        ['icon' => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z', 'title' => 'AI วิเคราะห์ตลาด', 'body' => 'Deep Learning วิเคราะห์ Pattern ตลาดจากข้อมูลนับล้าน ไม่ใช่แค่ Indicator ธรรมดา'],
        ['icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'ทองคำ น้ำมัน หุ้น', 'body' => 'รองรับหลายตลาดพร้อมกัน — Gold XAU/USD, Crude Oil WTI/Brent, SET, NASDAQ, Forex'],
        ['icon' => 'M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z', 'title' => 'Risk Management อัจฉริยะ', 'body' => 'Dynamic Stop Loss / Take Profit ปรับตามสภาวะตลาดอัตโนมัติ Monitor 24/7'],
    ];
    $featured = [
        [
            'art' => asset('artwork/card-ai.webp'), 'eyebrow' => 'แนะนำบริการ · Featured', 'title' => 'AI Chatbot อัจฉริยะ',
            'lead' => 'ยกระดับธุรกิจด้วย Chatbot ที่ขับเคลื่อนโดย AI เข้าใจภาษาไทย ตอบได้ทันที พร้อมให้บริการ 24/7',
            'cta' => 'พร้อมทดลองใช้งาน Chatbot', 'ctaBody' => 'ดูตัวอย่างการทำงานจริงของ AI Chatbot ที่พัฒนาให้ลูกค้าของเรา พร้อมฟีเจอร์ครบครัน',
            'points' => $chatbotPoints,
            'primary' => ['href' => 'https://cus008.xman4289.com', 'label' => 'ดูรายละเอียด', 'external' => true],
            'secondary' => ['href' => route('quote.index'), 'label' => 'ติดต่อสอบถาม'],
        ],
        [
            'art' => asset('artwork/card-autotradex.webp'), 'eyebrow' => 'AI-Powered Trading', 'title' => 'AI AutoTrade ระบบเทรดอัตโนมัติ',
            'lead' => 'พัฒนาโปรแกรมเทรดอัตโนมัติด้วย AI ล้ำกว่าระบบ AutoTrade ทั่วไปในตลาด',
            'points' => $tradePoints,
            'primary' => ['href' => route('ai-autotrade'), 'label' => 'ดูรายละเอียด'],
            'secondary' => ['href' => route('quote.index'), 'label' => 'ปรึกษาฟรี'],
        ],
    ];

    // ข้อมูลโครงสร้างสำหรับ Google: รายการบริการพร้อมราคาขายจริง (หลังหักส่วนลด) จาก DB
    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'บริการของ XMAN Studio',
        'url' => route('services.index'),
        'itemListElement' => $categories->flatMap(fn ($category) => $category->activeOptions->map(fn ($option) => [
            'name' => $option->display_name,
            'url' => route('service.detail', [$category->key, $option->key]),
            'price' => (string) round($option->price * (1 - \App\Support\Quotation\Pricing::saleDiscount($category->key))),
        ]))->values()->map(fn ($item, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'item' => [
                '@type' => 'Service',
                'name' => $item['name'],
                'url' => $item['url'],
                'provider' => ['@type' => 'Organization', 'name' => 'XMAN Studio'],
                'offers' => ['@type' => 'Offer', 'price' => $item['price'], 'priceCurrency' => 'THB'],
            ],
        ])->all(),
    ];
@endphp
<x-xp.page a="#67e8f9" b="#a78bfa" c="#7dd3fc" glow="103 232 249" g1="#a78bfa" g2="#67e8f9" g3="#f0abfc">

    {{-- ============================ HERO ============================ --}}
    <x-svc.hero :art="$art"
                tagline="บริการ IT ครบวงจร"
                tagline-en="End-to-end IT services, built by XMAN Studio."
                lead="ตั้งแต่ Blockchain, AI, Web & Mobile ไปจนถึงระบบ IoT และความปลอดภัย"
                lead-en="From blockchain, AI, web and mobile to IoT and security.">
        <x-slot:chips>
            <span class="xp-chip"><i></i>Our Services</span>
            @if($categories->isNotEmpty())
                <span class="xp-chip">{{ $categories->count() }} หมวด · {{ $optionCount }} บริการ</span>
            @endif
        </x-slot:chips>
        <x-slot:title>บริการ<span class="xp-text-glow">ของเรา</span></x-slot:title>
        <x-slot:actions>
            @if($categories->isNotEmpty())
                <a href="#catalog" class="xp-btn-main inline-flex items-center gap-2 px-6 py-4 rounded-xl font-extrabold transition">
                    ดูบริการทั้งหมด
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </a>
            @endif
            <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center px-6 py-4 rounded-xl font-bold transition">สั่งซื้อบริการ</a>
        </x-slot:actions>
        @if($maxSale)
            <x-slot:notes>
                <p class="mt-4 text-sm text-gray-400"><span class="xp-badge svc-sale mr-2">SALE</span>ราคาทุกบริการลดแล้วสูงสุด {{ $maxSale }}% · กดดูรายละเอียดเพื่อเห็นราคาจริงของแต่ละบริการ</p>
            </x-slot:notes>
        @endif
        <x-slot:visual>
            @if($categories->isNotEmpty())
                <div class="xp-tilt grid grid-cols-2 gap-3 sm:gap-4">
                    @foreach($categories->take(4) as $category)
                        <a href="#category-{{ $category->key }}" class="svc-box svc-cat {{ $loop->index % 2 === 1 ? 'translate-y-6' : '' }}">
                            <div class="svc-in h-40 sm:h-48 p-4 flex flex-col justify-end">
                                <x-svc.cover :category="$category" />
                                <div class="svc-fade"></div>
                                <span class="svc-icon absolute top-3 right-3 !w-11 !h-11 !text-2xl">{{ $category->icon }}</span>
                                <p class="text-white font-extrabold leading-tight">{{ $category->display_name }}</p>
                                <p class="text-xs text-gray-300 mt-0.5">{{ $category->activeOptions->count() }} บริการ</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </x-slot:visual>
    </x-svc.hero>

    {{-- ========================== FEATURED ========================== --}}
    <section class="relative py-20">
        <div class="xp-glow w-[34rem] h-[34rem] -left-40 top-10" style="background: rgb(var(--xp-glow) / .1)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-xp.head class="mb-12" eyebrow="Featured services" title="บริการ" title2="ที่แนะนำ"
                       sub="ระบบ AI ที่เราพัฒนาให้ลูกค้าใช้งานจริง"
                       en="AI systems we build for clients and run in production." />
            <div class="grid lg:grid-cols-2 gap-6">
                @foreach($featured as $item)
                    <article class="svc-box">
                        <div class="svc-in flex flex-col">
                            <div class="relative isolate h-48 sm:h-56">
                                <img src="{{ $item['art'] }}" alt="" aria-hidden="true" loading="lazy" decoding="async" class="svc-cover">
                                <div class="svc-fade"></div>
                            </div>
                            <div class="relative -mt-16 p-6 sm:p-8 pt-0 flex flex-col flex-1">
                                <p class="xp-eyebrow mb-3">{{ $item['eyebrow'] }}</p>
                                <h3 class="text-2xl md:text-3xl font-black text-white mb-3">{{ $item['title'] }}</h3>
                                <p class="text-gray-300 leading-relaxed mb-6">{{ $item['lead'] }}</p>

                                <ul class="grid sm:grid-cols-2 gap-3 mb-6">
                                    @foreach($item['points'] as $point)
                                        <li class="xp-card p-4">
                                            <div class="flex items-center gap-2.5 mb-1.5">
                                                <span class="w-9 h-9 rounded-lg flex flex-none items-center justify-center" style="background: color-mix(in srgb, var(--xp-a) 10%, transparent); border: 1px solid color-mix(in srgb, var(--xp-a) 25%, transparent);">
                                                    <svg class="w-5 h-5" style="color: var(--xp-a)" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $point['icon'] }}"/></svg>
                                                </span>
                                                <h4 class="text-white font-bold leading-snug">{{ $point['title'] }}</h4>
                                            </div>
                                            <p class="text-sm text-gray-400 leading-relaxed">{{ $point['body'] }}</p>
                                        </li>
                                    @endforeach
                                </ul>

                                @if(! empty($item['cta']))
                                    <p class="text-white font-bold">{{ $item['cta'] }}</p>
                                    <p class="text-sm text-gray-400 mb-6">{{ $item['ctaBody'] }}</p>
                                @endif

                                <div class="mt-auto flex flex-wrap gap-3">
                                    <a href="{{ $item['primary']['href'] }}" @if(! empty($item['primary']['external'])) target="_blank" rel="noopener noreferrer" @endif
                                       class="xp-btn-main inline-flex items-center gap-2 px-6 py-3.5 rounded-xl font-extrabold transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        {{ $item['primary']['label'] }}
                                    </a>
                                    <a href="{{ $item['secondary']['href'] }}" class="xp-btn-ghost inline-flex items-center px-6 py-3.5 rounded-xl font-bold transition">{{ $item['secondary']['label'] }}</a>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ========================== CATALOGUE ========================== --}}
    <section class="relative py-20 scroll-mt-20" id="catalog" data-svc-catalog>
        <div class="xp-glow w-[40rem] h-[26rem] left-1/2 -translate-x-1/2 top-32" style="background: color-mix(in srgb, var(--xp-b) 10%, transparent)"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($categories->isEmpty())
                <div class="xp-card text-center py-16 px-6 max-w-xl mx-auto">
                    <svg class="w-20 h-20 mx-auto text-gray-600 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <h2 class="text-xl font-bold text-white">ยังไม่มีบริการ</h2>
                    <p class="text-gray-400 mt-2">กรุณากลับมาใหม่ภายหลัง</p>
                </div>
            @else
                <x-xp.head class="mb-12" eyebrow="All services" title="เลือกหมวด" title2="แล้วดูบริการทั้งหมด"
                           sub="กดที่หมวดเพื่อดูบริการ ราคา และรายละเอียดของแต่ละบริการ"
                           en="Pick a category to see its services, prices and details." />

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 mb-12">
                    @foreach($categories as $category)
                        <a href="#category-{{ $category->key }}" class="svc-box svc-cat group" data-svc-cat="{{ $category->key }}"
                           aria-controls="category-{{ $category->key }}" aria-expanded="false">
                            <div class="svc-in flex flex-col">
                                <div class="relative isolate h-44">
                                    <x-svc.cover :category="$category" />
                                    <div class="svc-fade"></div>
                                    <span class="absolute top-4 left-4 xp-badge svc-sale shadow-lg">SALE {{ (int) round(\App\Support\Quotation\Pricing::saleDiscount($category->key) * 100) }}%</span>
                                    <span class="svc-icon absolute top-3 right-3">{{ $category->icon }}</span>
                                    <span class="absolute bottom-3 left-4 xp-chip !py-1 !px-3 !text-xs">{{ $category->activeOptions->count() }} บริการ</span>
                                </div>
                                <div class="p-5 pt-3 flex flex-col flex-1">
                                    <h3 class="text-lg font-bold text-white mb-1.5">{{ $category->display_name }}</h3>
                                    @if($category->display_description)
                                        <p class="text-sm text-gray-400 mb-4 line-clamp-2">{{ $category->display_description }}</p>
                                    @endif
                                    <span class="xp-link mt-auto inline-flex items-center gap-1 text-sm font-semibold">
                                        ดูบริการทั้งหมด
                                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- หนึ่งหมวดต่อครั้ง: กดการ์ดหมวดเพื่อเปิด/ปิด · ลิงก์ #category-<key> เปิดหมวดนั้นตรง ๆ --}}
                @foreach($categories as $category)
                    @php $discount = \App\Support\Quotation\Pricing::saleDiscount($category->key); @endphp
                    <div id="category-{{ $category->key }}" class="svc-panel scroll-mt-24 mb-12" data-svc-panel="{{ $category->key }}" hidden>
                        <div class="svc-box mb-6">
                            <div class="svc-in p-6 sm:p-8 flex flex-wrap items-center justify-between gap-5">
                                <x-svc.cover :category="$category" class="opacity-30" />
                                <div class="svc-fade" style="background: linear-gradient(90deg, #070b17 30%, rgba(7,11,23,.6))"></div>
                                <div class="flex items-center gap-4 min-w-0">
                                    <span class="svc-icon !w-16 !h-16 !text-4xl flex-none">{{ $category->icon }}</span>
                                    <div class="min-w-0">
                                        <h2 class="text-2xl sm:text-3xl font-black text-white">{{ $category->display_name }}</h2>
                                        @if($category->display_description)
                                            <p class="text-gray-400 mt-1">{{ $category->display_description }}</p>
                                        @endif
                                    </div>
                                </div>
                                <button type="button" data-svc-close class="xp-btn-ghost inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    ปิด
                                </button>
                            </div>
                        </div>

                        @if($category->activeOptions->count() > 0)
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                                @foreach($category->activeOptions as $option)
                                    @php
                                        $features = $option->features_th ?? $option->features;
                                        $featuresList = is_array($features) ? $features : [];
                                    @endphp
                                    <article class="xp-card overflow-hidden flex flex-col transition group">
                                        @if($option->image)
                                            <div class="h-40 overflow-hidden">
                                                <img src="{{ asset('storage/' . $option->image) }}" alt="{{ $option->display_name }}" loading="lazy" decoding="async"
                                                     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                            </div>
                                        @endif
                                        <div class="p-6 flex flex-col flex-1">
                                            @unless($option->image)
                                                <span class="svc-icon mb-4">{{ $category->icon }}</span>
                                            @endunless
                                            <h3 class="text-lg font-bold text-white mb-2">{{ $option->display_name }}</h3>
                                            @if($option->display_description)
                                                <p class="text-sm text-gray-400 mb-4 line-clamp-2">{{ $option->display_description }}</p>
                                            @endif
                                            @if(count($featuresList) > 0)
                                                <ul class="text-xs text-gray-400 mb-4 space-y-1.5">
                                                    @foreach(array_slice($featuresList, 0, 2) as $feature)
                                                        <li class="flex items-center gap-2"><span class="xp-dot !w-1.5 !h-1.5"></span><span class="line-clamp-1">{{ $feature }}</span></li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                            <div class="mt-auto pt-4 border-t border-white/10 flex items-end justify-between gap-3">
                                                <x-svc.price :price="$option->price" :discount="$discount" />
                                                <a href="{{ route('service.detail', [$category->key, $option->key]) }}"
                                                   class="xp-btn-main flex-none px-4 py-2.5 rounded-lg text-sm font-extrabold transition">รายละเอียด</a>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="xp-card text-center py-12">
                                <p class="text-gray-400">ไม่มีตัวเลือกบริการในหมวดนี้ในขณะนี้</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </section>

    {{-- ============================= CTA ============================= --}}
    <x-svc.cta :art="$art" title="พร้อมเริ่มโปรเจค" glow="ของคุณแล้วหรือยัง?"
               sub="ทีมผู้เชี่ยวชาญของเราพร้อมให้คำปรึกษาและวางแผนโซลูชันที่เหมาะสมกับธุรกิจของคุณ"
               en="Tell us what you need — we plan the solution that fits your business.">
        <a href="{{ route('quote.index') }}" class="xp-btn-main inline-flex items-center px-8 py-4 rounded-xl font-extrabold transition">สั่งซื้อบริการ</a>
        <a href="{{ route('contact.show') }}" class="xp-btn-ghost inline-flex items-center px-8 py-4 rounded-xl font-bold transition">ติดต่อเรา</a>
    </x-svc.cta>
</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>

@push('scripts')
<script>
(function () {
    // หมวดบริการ: เปิดทีละหมวด · กดการ์ดซ้ำ ปุ่ม "ปิด" หรือกดนอกรายการ = ปิด · #category-<key> ในลิงก์เปิดหมวดนั้น
    var root = document.querySelector('[data-svc-catalog]');
    if (!root) { return; }
    var cards = Array.prototype.slice.call(root.querySelectorAll('[data-svc-cat]'));
    var panels = Array.prototype.slice.call(root.querySelectorAll('[data-svc-panel]'));
    var motion = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';

    function panelFor(key) { return panels.filter(function (p) { return p.dataset.svcPanel === key; })[0] || null; }
    function mark(key) { cards.forEach(function (c) { c.setAttribute('aria-expanded', c.dataset.svcCat === key ? 'true' : 'false'); }); }
    function setHash(key) { history.replaceState(null, '', key ? '#category-' + key : location.pathname + location.search); }

    function open(key) {
        var target = panelFor(key);
        if (!target) { return; }
        panels.forEach(function (p) { p.hidden = p !== target; });
        mark(key);
        setHash(key);
        setTimeout(function () { target.scrollIntoView({ behavior: motion, block: 'start' }); }, 60);
    }
    function closeAll(scroll) {
        if (panels.every(function (p) { return p.hidden; })) { return; }
        panels.forEach(function (p) { p.hidden = true; });
        mark(null);
        setHash(null);
        if (scroll) { root.scrollIntoView({ behavior: motion, block: 'start' }); }
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href^="#category-"]');
        if (link) {
            var key = link.getAttribute('href').slice('#category-'.length);
            var target = panelFor(key);
            if (!target) { return; }
            e.preventDefault();
            // the category cards toggle; any other link to a category only opens it
            if (link.hasAttribute('data-svc-cat') && !target.hidden) { closeAll(false); } else { open(key); }
            return;
        }
        if (e.target.closest('[data-svc-close]')) { closeAll(true); return; }
        if (!e.target.closest('[data-svc-panel]')) { closeAll(false); }
    });

    if (location.hash.indexOf('#category-') === 0) { open(decodeURIComponent(location.hash.slice('#category-'.length))); }
})();
</script>
@endpush
@endsection
