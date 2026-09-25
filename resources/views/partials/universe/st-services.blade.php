{{--
    Stop 3 — the service galaxy. Each card has a beacon on the galaxy's arms
    (data-xu-beacon), which flares while the card is hovered or focused.
--}}
<section id="xu-services" class="xu-st" data-station="services" data-len="2.1"
         data-label-th="กาแล็กซีบริการ" data-label-en="Services" aria-labelledby="xu-services-title">
    <div class="xu-panel xu-services">
        <header class="xu-head">
            <p class="xu-eyebrow xu-r" style="--i: 0;">
                <span class="xu-eyebrow__dot" aria-hidden="true"></span>
                บริการของเรา / Our Services
            </p>
            <h2 id="xu-services-title" class="xu-h2 xu-r" style="--i: 0.6;">
                โซลูชั่นที่ตอบโจทย์ <span class="xu-grad">ทุกความต้องการ</span>
            </h2>
            <p class="xu-lede xu-r" style="--i: 1.2;">
                บริการครบวงจรจากทีมผู้เชี่ยวชาญ พร้อมเทคโนโลยีล่าสุด
                <small>End-to-end delivery from a specialist team.</small>
            </p>
        </header>

        <div class="xu-svc-grid">
            @foreach(\App\Support\HomeContent::services() as $k => $s)
                <a href="{{ route('services.index') }}"
                   class="xu-card xu-svc xu-r"
                   data-xu-beacon="{{ $k }}"
                   style="--i: {{ 1.6 + $k * 0.35 }}; --accent: {{ $s['accent'] }};">
                    <span class="xu-svc__media" aria-hidden="true">
                        <img src="{{ asset('artwork/' . $s['art'] . '.webp') }}" alt="" loading="lazy" decoding="async">
                    </span>
                    @if($s['badge'])
                        <span class="xu-badge">{{ $s['badge'] }}</span>
                    @endif
                    <span class="xu-svc__icon" aria-hidden="true">@include('partials.nova-icon', ['name' => $s['icon']])</span>
                    <h3 class="xu-svc__title">{{ $s['th'] }}</h3>
                    <span class="xu-svc__en">{{ $s['en'] }}</span>
                    <p class="xu-svc__body">{{ $s['body'] }}</p>
                    <span class="xu-go">ดูรายละเอียด / Details @include('partials.nova-icon', ['name' => 'arrow'])</span>
                </a>
            @endforeach
        </div>

        <div class="xu-center xu-r" style="--i: 4.6;">
            <a href="{{ route('services.index') }}" class="xu-btn xu-btn--primary">
                <span>ดูบริการทั้งหมด <small>All services</small></span>
                @include('partials.nova-icon', ['name' => 'arrow'])
            </a>
        </div>
    </div>
</section>
