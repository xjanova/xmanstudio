@extends($publicLayout ?? 'layouts.app')

@section('title', $service->name . ' - XMAN Studio')
@section('meta_description', \Illuminate\Support\Str::limit($service->localized_description, 160))

@section('content')
@php
    // ทุกอย่างมาจากแถวในตาราง services ที่แอดมินตั้ง (ชื่อ คำอธิบาย ไอคอน ราคาเริ่มต้น/หน่วย คุณสมบัติ Coming Soon)
    $comingSoon = $service->isComingSoon();
    $art = asset('artwork/hero-services.webp');
    // รูปที่แอดมินใส่เป็นที่อยู่เต็ม หรือเป็นไฟล์ใน storage
    $image = $service->image ? (preg_match('#^(https?:)?//|^/#', $service->image) ? $service->image : asset('storage/' . $service->image)) : null;
    $features = $service->localized_features;

    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => $service->localized_name,
        'description' => $service->localized_description,
        'url' => route('services.show', $service->slug),
        'provider' => ['@type' => 'Organization', 'name' => 'XMAN Studio', 'url' => url('/')],
    ];
    if ($service->starting_price) {
        $ld['offers'] = ['@type' => 'Offer', 'price' => (string) round($service->starting_price), 'priceCurrency' => 'THB'];
    }
@endphp
<x-xp.page a="#67e8f9" b="#a78bfa" c="#7dd3fc" glow="103 232 249" g1="#a78bfa" g2="#67e8f9" g3="#f0abfc">

    @if($comingSoon)
        <div class="relative z-10 border-b border-amber-300/20 bg-amber-500/10 text-amber-100 py-3 px-4">
            <p class="max-w-7xl mx-auto flex flex-wrap items-center justify-center gap-2 text-sm">
                <x-xp.badge tier="soon" label="Coming Soon" />
                @if($service->coming_soon_until)
                    <span>เปิดให้บริการวันที่ {{ $service->coming_soon_until->format('d/m/Y H:i') }}</span>
                @else
                    <span>บริการนี้จะเปิดให้บริการเร็วๆ นี้</span>
                @endif
            </p>
        </div>
    @endif

    {{-- ============================ HERO ============================ --}}
    <x-svc.hero :art="$art" compact :crumbs="[
        ['หน้าแรก', route('home')],
        ['บริการ', route('services.index')],
        [$service->localized_name, null],
    ]">
        <x-slot:title>{{ $service->localized_name }}</x-slot:title>

        @if($service->name_th && $service->name !== $service->localized_name)
            <p class="text-xl text-gray-400 -mt-2 mb-6">{{ $service->name }}</p>
        @endif
        <p class="text-lg text-gray-300 leading-relaxed">{{ $service->localized_description }}</p>

        <div class="svc-price mb-8 mt-4">
            @if($service->starting_price)
                <p class="text-sm text-gray-400 mb-1">ราคาเริ่มต้น</p>
                <p class="text-white font-black leading-none"><span class="text-4xl sm:text-5xl">{{ number_format($service->starting_price) }}</span> <span class="text-xl text-gray-300 font-bold">บาท/{{ $service->price_unit }}</span></p>
                <x-tpix-price variant="detail" :thb="$service->starting_price" class="mt-3" />
            @else
                <p class="text-2xl font-bold text-white">{{ $service->formatted_price }}</p>
            @endif
        </div>

        <x-slot:actions>
            @if($comingSoon)
                <span class="xp-btn-off xp-btn-ghost inline-flex items-center gap-2 px-7 py-4 rounded-xl font-bold text-lg" aria-disabled="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Coming Soon
                    @if($service->coming_soon_until)
                        - {{ $service->coming_soon_until->format('d/m/Y') }}
                    @endif
                </span>
            @else
                <a href="{{ route('quote.index') }}" class="xp-btn-main inline-flex items-center px-7 py-4 rounded-xl font-extrabold text-lg transition">ขอใบเสนอราคา</a>
            @endif
            <a href="{{ route('services.index') }}" class="xp-btn-ghost inline-flex items-center px-7 py-4 rounded-xl font-bold text-lg transition">ดูบริการอื่นๆ</a>
        </x-slot:actions>
        <x-slot:visual>
            <div class="xp-tilt">
                @if($image)
                    <x-xp.screen :src="$image" :alt="$service->localized_name" :eager="true" />
                @else
                    <div class="svc-box">
                        <div class="svc-in aspect-[16/10] flex flex-col items-center justify-center text-center p-8">
                            <x-svc.cover :key="$service->slug" class="opacity-90" />
                            <div class="absolute inset-0 -z-10" style="background: radial-gradient(ellipse 55% 55% at 50% 55%, rgba(7,11,23,.55), transparent 75%)"></div>
                            @if($service->icon)
                                <span class="text-7xl sm:text-8xl mb-4 drop-shadow-[0_8px_30px_rgba(0,0,0,.6)]">{{ $service->icon }}</span>
                            @endif
                            <p class="text-xl sm:text-2xl font-black text-white">{{ $service->name }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </x-slot:visual>
    </x-svc.hero>

    {{-- =========================== FEATURES =========================== --}}
    @if(count($features) > 0)
        <section class="relative py-16">
            <div class="xp-glow w-[34rem] h-[34rem] -right-40 top-0" style="background: rgb(var(--xp-glow) / .1)"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <x-xp.head class="mb-12" size="md" eyebrow="What you get" title="สิ่งที่คุณจะได้รับ" />
                <ul class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($features as $feature)
                        <li class="xp-card p-6 flex items-start gap-4 transition">
                            <span class="xp-tick !w-9 !h-9 !text-base !mt-0">✓</span>
                            <p class="text-gray-200 font-semibold leading-relaxed">{{ $feature }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ======================= RELATED SERVICES ======================= --}}
    @if($relatedServices->count() > 0)
        <section class="relative py-16">
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <x-xp.head class="mb-12" size="md" eyebrow="More services" title="บริการอื่นๆ" />
                <div class="grid md:grid-cols-3 gap-5">
                    @foreach($relatedServices as $related)
                        <a href="{{ route('services.show', $related->slug) }}" class="xp-card p-6 flex flex-col transition group">
                            <div class="flex items-center gap-3 mb-3">
                                @if($related->icon)<span class="svc-icon !w-11 !h-11 !text-2xl">{{ $related->icon }}</span>@endif
                                <h3 class="text-xl font-bold text-white group-hover:text-cyan-200 transition">{{ $related->localized_name }}</h3>
                            </div>
                            <p class="text-gray-400 text-sm mb-5 line-clamp-2">{{ $related->localized_description }}</p>
                            <div class="mt-auto flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-gray-200">{{ $related->formatted_price }}</span>
                                <span class="xp-link text-xl transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================= CTA ============================= --}}
    <x-svc.cta :art="$art" title="พร้อม" glow="เริ่มต้นแล้วหรือยัง?"
               sub="ติดต่อเราวันนี้เพื่อรับคำปรึกษาฟรี และใบเสนอราคาที่ออกแบบมาเฉพาะสำหรับธุรกิจของคุณ"
               en="Talk to us for a free consultation and a quotation made for your business.">
        <a href="{{ route('quote.index') }}" class="xp-btn-main inline-flex items-center px-8 py-4 rounded-xl font-extrabold transition">ติดต่อเรา</a>
        <a href="{{ route('services.index') }}" class="xp-btn-ghost inline-flex items-center px-8 py-4 rounded-xl font-bold transition">ดูบริการอื่นๆ</a>
    </x-svc.cta>
</x-xp.page>

<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection
