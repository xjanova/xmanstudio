{{-- Nova services — all 8 services carried over from the original home page.
     Every card links to /services (the detail hub), matching the previous
     behaviour. --}}
@php
    $novaServices = [
        [
            'th' => 'Blockchain Development', 'en' => 'Smart contracts · DeFi · NFT',
            'body' => 'พัฒนาโซลูชั่น Blockchain, Smart Contracts, DeFi และ NFT Marketplace',
            'icon' => 'chain', 'accent' => '#22d3ee', 'badge' => 'ยอดนิยม',
            'art' => 'card-blockchain',
        ],
        [
            'th' => 'Web Development', 'en' => 'Responsive · Modern stack',
            'body' => 'ออกแบบและพัฒนาเว็บไซต์สมัยใหม่ Responsive รองรับทุกอุปกรณ์',
            'icon' => 'globe', 'accent' => '#38bdf8', 'badge' => null,
            'art' => 'card-web',
        ],
        [
            'th' => 'Mobile Application', 'en' => 'iOS · Android',
            'body' => 'พัฒนาแอพ iOS และ Android ด้วย Flutter และ React Native',
            'icon' => 'mobile', 'accent' => '#34d399', 'badge' => null,
            'art' => 'card-mobile',
        ],
        [
            'th' => 'AI Solutions', 'en' => 'Generative AI · Chatbot',
            'body' => 'วีดีโอ AI, เพลง AI, Chatbot และบริการ Generative AI',
            'icon' => 'spark', 'accent' => '#e879f9', 'badge' => 'ใหม่',
            'art' => 'card-ai',
        ],
        [
            'th' => 'IoT Solutions', 'en' => 'Internet of Things',
            'body' => 'ออกแบบและพัฒนาระบบ Internet of Things ครบวงจร',
            'icon' => 'bolt', 'accent' => '#fb923c', 'badge' => null,
            'art' => 'card-iot',
        ],
        [
            'th' => 'Network & IT Security', 'en' => 'Firewall · Pentest',
            'body' => 'ออกแบบ ติดตั้งระบบ Network, Firewall และทดสอบเจาะระบบ',
            'icon' => 'shield', 'accent' => '#fb7185', 'badge' => null,
            'art' => 'card-security',
        ],
        [
            'th' => 'Custom Software', 'en' => 'ERP · CRM · Inventory',
            'body' => 'พัฒนาซอฟต์แวร์เฉพาะ ERP, CRM และระบบจัดการสินค้าคงคลัง',
            'icon' => 'layers', 'accent' => '#8b5cf6', 'badge' => null,
            'art' => 'card-software',
        ],
        [
            'th' => 'Flutter & Android Studio', 'en' => 'Cross-platform · Training',
            'body' => 'พัฒนาแอพ Cross-platform ด้วย Flutter และอบรมการใช้งาน',
            'icon' => 'code', 'accent' => '#0ea5e9', 'badge' => 'Flutter',
            'art' => 'card-flutter',
        ],
    ];
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
