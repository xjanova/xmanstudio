{{-- Nova services — all 8 services carried over from the original home page.
     Every card links to /services (the detail hub), matching the previous
     behaviour. The list is shared with the 3D universe home (HomeContent). --}}
@php
    $novaServices = \App\Support\HomeContent::services();
@endphp

<section id="nova-services" class="nova-section">
    <x-page-art art="hero-services" :opacity="26" :scrim="false" />
    <div class="nova-section__glow" aria-hidden="true"></div>
    <div class="nova-shell" style="position:relative;z-index:1;">
        <header class="nova-head nova-reveal">
            <span class="nova-eyebrow">
                <span class="nova-eyebrow__dot"></span>
                บริการของเรา / Our Services
            </span>
            <h2 class="nova-h2">
                โซลูชั่นที่ตอบโจทย์ <span class="nova-grad">ทุกความต้องการ</span>
            </h2>
            <p class="nova-lede">
                บริการครบวงจรจากทีมผู้เชี่ยวชาญ พร้อมเทคโนโลยีล่าสุด<br>
                <span style="color:var(--nv-fg-3);">End-to-end delivery from a specialist team.</span>
            </p>
        </header>

        <div class="nova-grid">
            @foreach($novaServices as $s)
                <a href="{{ route('services.index') }}"
                   class="nova-card nova-reveal"
                   style="--nv-accent: {{ $s['accent'] }}; transition-delay: {{ $loop->index * 0.05 }}s;">
                    <span class="nova-card__media" aria-hidden="true">
                        <img src="{{ asset('artwork/' . $s['art'] . '.webp') }}" alt="" loading="lazy" decoding="async">
                    </span>
                    @if($s['badge'])
                        <span class="nova-badge">{{ $s['badge'] }}</span>
                    @endif
                    <span class="nova-card__icon" aria-hidden="true">
                        @include('partials.nova-icon', ['name' => $s['icon']])
                    </span>
                    <h3 class="nova-card__title">{{ $s['th'] }}</h3>
                    <span class="nova-card__en">{{ $s['en'] }}</span>
                    <p class="nova-card__body">{{ $s['body'] }}</p>
                    <span class="nova-card__cta">
                        ดูรายละเอียด / Details
                        @include('partials.nova-icon', ['name' => 'arrow'])
                    </span>
                </a>
            @endforeach
        </div>

        <div style="text-align:center;margin-top:44px;" class="nova-reveal">
            <a href="{{ route('services.index') }}" class="nova-btn nova-btn--primary">
                ดูบริการทั้งหมด / All services
                @include('partials.nova-icon', ['name' => 'arrow'])
            </a>
        </div>
    </div>
</section>
