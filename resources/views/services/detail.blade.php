@extends($publicLayout ?? 'layouts.app')

@section('title', $option->name . ' - XMAN Studio')
@section('meta_description', \Illuminate\Support\Str::limit(trim(($option->name_th ?? $option->name) . ' — ' . ($option->description_th ?? $option->description ?? ($category->name_th ?? $category->name))), 160))

@section('content')
@php
    // ราคาและส่วนลดมาจาก QuotationOption + Pricing::saleDiscount() ตัวเดียวกับที่ตัวคิดราคาคิดเงินจริง
    $discount = \App\Support\Quotation\Pricing::saleDiscount($category->key);
    $salePrice = $option->price * (1 - $discount);
    $art = asset('artwork/hero-services.webp');
    $categoryName = $category->name_th ?? $category->name;
    $optionName = $option->name_th ?? $option->name;

    // เนื้อหายาว คุณสมบัติ และขั้นตอน: แอดมินใส่ได้ทั้งเป็นรายการธรรมดาหรือเป็นบล็อกจาก page builder (JSON ที่ขึ้นต้นด้วย [)
    $longDesc = $option->long_description_th ?? $option->long_description;
    $isPageBuilder = is_string($longDesc) && str_starts_with(trim($longDesc), '[');
    $features = $option->features_th ?? $option->features;
    $featuresIsPageBuilder = is_string($features) && str_starts_with(trim($features), '[');
    $steps = $option->steps_th ?? $option->steps;
    $stepsIsPageBuilder = is_string($steps) && str_starts_with(trim($steps), '[');

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $optionName,
        'serviceType' => $categoryName,
        'description' => $option->description_th ?? $option->description,
        'url' => route('service.detail', [$category->key, $option->key]),
        'provider' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
        'offers' => ['@type' => 'Offer', 'price' => (string) round($salePrice), 'priceCurrency' => 'THB', 'url' => route('quote.index')],
    ];
    if ($option->image) {
        $ld['image'] = asset('storage/' . $option->image);
    }
@endphp
<x-xp.page a="#67e8f9" b="#a78bfa" c="#7dd3fc" glow="103 232 249" g1="#a78bfa" g2="#67e8f9" g3="#f0abfc">

    {{-- ============================ HERO ============================ --}}
    <x-svc.hero :art="$art" compact :crumbs="[
        ['หน้าแรก', route('home')],
        ['บริการ', route('services.index')],
        [$categoryName, route('services.index') . '#category-' . $category->key],
        [$optionName, null],
    ]">
        <x-slot:chips>
            <a href="{{ route('services.index') }}#category-{{ $category->key }}" class="xp-chip hover:!border-white/25"><span class="text-base leading-none">{{ $category->icon }}</span>{{ $categoryName }}</a>
        </x-slot:chips>
        <x-slot:title>{{ $optionName }}</x-slot:title>

        @if($option->name_th && $option->name !== $option->name_th)
            <p class="text-xl text-gray-400 -mt-2 mb-6">{{ $option->name }}</p>
        @endif

        <x-svc.price size="lg" :price="$option->price" :discount="$discount" class="mb-8" />

        @if($option->description_th || $option->description)
            <p class="text-lg text-gray-300 leading-relaxed mb-8">{{ $option->description_th ?? $option->description }}</p>
        @endif

        <x-slot:actions>
            <a href="{{ route('quote.index') }}#quotation-form" class="xp-btn-main inline-flex items-center px-7 py-4 rounded-xl font-extrabold text-lg transition">สั่งซื้อบริการนี้</a>
            <a href="{{ route('quote.index') }}" class="xp-btn-ghost inline-flex items-center px-7 py-4 rounded-xl font-bold text-lg transition">เลือกบริการอื่น</a>
        </x-slot:actions>
        <x-slot:badges>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-tick">✓</span> มืออาชีพ</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-tick">⚡</span> ส่งมอบรวดเร็ว</li>
            <li class="flex items-center gap-2 text-gray-300"><span class="xp-tick">💎</span> คุณภาพสูง</li>
        </x-slot:badges>
        <x-slot:visual>
            <div class="xp-tilt">
                @if($option->image)
                    <x-xp.screen :src="asset('storage/' . $option->image)" :alt="$option->name" :eager="true" />
                @else
                    <div class="svc-box">
                        <div class="svc-in aspect-[16/10] flex flex-col items-center justify-center text-center p-8">
                            <x-svc.cover :category="$category" class="opacity-90" />
                            <div class="absolute inset-0 -z-10" style="background: radial-gradient(ellipse 55% 55% at 50% 55%, rgba(7,11,23,.55), transparent 75%)"></div>
                            <span class="text-7xl sm:text-8xl mb-4 drop-shadow-[0_8px_30px_rgba(0,0,0,.6)]">{{ $category->icon }}</span>
                            <p class="text-xl sm:text-2xl font-black text-white">{{ $option->name }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </x-slot:visual>
    </x-svc.hero>

    {{-- ======================= LONG DESCRIPTION ======================= --}}
    @if($longDesc)
        <section class="relative py-16">
            <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-0" style="background: rgb(var(--xp-glow) / .1)"></div>
            <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <x-xp.head align="left" size="md" class="mb-8" eyebrow="About this service" title="รายละเอียดบริการ" />
                <div class="xp-card p-6 sm:p-10">
                    @if($isPageBuilder)
                        <x-page-builder-render :content="$longDesc" theme="dark" />
                    @else
                        <p class="text-gray-300 text-lg leading-relaxed whitespace-pre-line">{{ $longDesc }}</p>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- =========================== FEATURES =========================== --}}
    @if($features)
        <section class="relative py-16">
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <x-xp.head class="mb-12" size="md" eyebrow="What you get" title="คุณสมบัติเด่น" />
                @if($featuresIsPageBuilder)
                    <div class="max-w-5xl mx-auto"><x-page-builder-render :content="$features" theme="dark" /></div>
                @else
                    <ul class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach((is_array($features) ? $features : []) as $feature)
                            <li class="xp-card p-6 flex items-start gap-4 transition">
                                <span class="xp-tick !w-9 !h-9 !text-base !mt-0">✓</span>
                                <p class="text-gray-200 leading-relaxed">{{ $feature }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    @endif

    {{-- ============================ STEPS ============================ --}}
    @if($steps)
        <section class="relative py-16">
            <div class="xp-glow w-[36rem] h-[24rem] left-1/2 -translate-x-1/2 top-24" style="background: color-mix(in srgb, var(--xp-b) 10%, transparent)"></div>
            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <x-xp.head class="mb-12" size="md" eyebrow="How we work" title="ขั้นตอนการทำงาน" />
                @if($stepsIsPageBuilder)
                    <x-page-builder-render :content="$steps" theme="dark" />
                @else
                    <ol>
                        @foreach((is_array($steps) ? $steps : []) as $index => $step)
                            <li class="flex gap-5">
                                <div class="flex flex-col items-center">
                                    <span class="svc-step">{{ $index + 1 }}</span>
                                    @unless($loop->last)<span class="svc-line" aria-hidden="true"></span>@endunless
                                </div>
                                <div class="xp-card flex-1 p-5 sm:p-6 {{ $loop->last ? '' : 'mb-5' }}">
                                    <p class="text-lg text-gray-200 leading-relaxed">{{ $step }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </section>
    @endif

    {{-- ======================= RELATED SERVICES ======================= --}}
    @if($relatedServices->count() > 0)
        <section class="relative py-16">
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <x-xp.head class="mb-12" size="md" eyebrow="More in {{ $category->name }}" title="บริการที่เกี่ยวข้อง" />
                <div class="grid md:grid-cols-3 gap-5">
                    @foreach($relatedServices as $related)
                        <a href="{{ route('service.detail', [$category->key, $related->key]) }}" class="xp-card p-6 flex flex-col transition group">
                            <h3 class="text-xl font-bold text-white mb-2 group-hover:text-cyan-200 transition">{{ $related->name_th ?? $related->name }}</h3>
                            @if($related->description_th || $related->description)
                                <p class="text-gray-400 text-sm mb-5 line-clamp-2">{{ $related->description_th ?? $related->description }}</p>
                            @endif
                            <div class="mt-auto flex items-end justify-between gap-3">
                                <x-svc.price size="sm" :price="$related->price" :discount="$discount" />
                                <span class="xp-link text-xl transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================= CTA ============================= --}}
    <x-svc.cta :art="$art" title="พร้อมที่จะ" glow="เริ่มต้นแล้วหรือยัง?"
               sub="ติดต่อเราวันนี้เพื่อรับคำปรึกษาฟรี" en="Talk to us today — the first consultation is free.">
        <a href="{{ route('quote.index') }}#quotation-form" class="xp-btn-main inline-flex items-center px-8 py-4 rounded-xl font-extrabold transition">สั่งซื้อเลย</a>
        <a href="{{ route('services.index') }}" class="xp-btn-ghost inline-flex items-center px-8 py-4 rounded-xl font-bold transition">ดูบริการทั้งหมด</a>
    </x-svc.cta>
</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
