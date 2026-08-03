{{-- Nova stats + why-choose-us. Numbers and copy carried over from the
     previous home page. --}}
<section class="nova-section nova-section--tight">
    <div class="nova-shell">
        <div class="nova-stats nova-reveal">
            <div class="nova-stat">
                <div class="nova-stat__num nova-grad" data-nova-count="150" data-nova-suffix="+">150+</div>
                <div class="nova-stat__label">โปรเจคสำเร็จ / Projects</div>
            </div>
            <div class="nova-stat">
                <div class="nova-stat__num nova-grad" data-nova-count="50" data-nova-suffix="+">50+</div>
                <div class="nova-stat__label">ลูกค้าพึงพอใจ / Clients</div>
            </div>
            <div class="nova-stat">
                <div class="nova-stat__num nova-grad" data-nova-count="8" data-nova-suffix="+">8+</div>
                <div class="nova-stat__label">ปีประสบการณ์ / Years</div>
            </div>
            <div class="nova-stat">
                <div class="nova-stat__num nova-grad" data-nova-count="24" data-nova-suffix="/7">24/7</div>
                <div class="nova-stat__label">บริการตลอดเวลา / Support</div>
            </div>
        </div>
    </div>
</section>

<section class="nova-section">
    <div class="nova-shell nova-why">
        <div class="nova-reveal">
            <span class="nova-eyebrow">
                <span class="nova-eyebrow__dot"></span>
                ทำไมต้องเลือกเรา / Why us
            </span>
            <h2 class="nova-h2">
                พาร์ทเนอร์ด้านเทคโนโลยี<br><span class="nova-grad">ที่คุณไว้วางใจได้</span>
            </h2>
            <p style="font-size:16px;line-height:1.8;color:var(--nv-fg-2);margin:0 0 8px;max-width:520px;">
                เราเป็นทีมนักพัฒนามืออาชีพที่มีประสบการณ์กว่า 8 ปี
                ในการพัฒนาซอฟต์แวร์และโซลูชั่น IT ให้กับองค์กรชั้นนำ
            </p>
            <p style="font-size:14px;line-height:1.8;color:var(--nv-fg-3);margin:0 0 30px;max-width:520px;">
                A senior team with 15+ developers, shipping production software since 2018.
            </p>
            <a href="{{ route('about') }}" class="nova-btn nova-btn--ghost">
                รู้จักเรา / About us
                @include('partials.nova-icon', ['name' => 'arrow'])
            </a>
        </div>

        <ul class="nova-why__list nova-reveal">
            @php
                $novaWhy = [
                    ['icon' => 'shield', 'accent' => '#34d399', 'th' => 'คุณภาพระดับสากล',  'en' => 'International standards',
                     'body' => 'พัฒนาตามมาตรฐาน International Best Practice'],
                    ['icon' => 'clock',  'accent' => '#22d3ee', 'th' => 'ส่งมอบตรงเวลา',    'en' => 'On-time delivery',
                     'body' => 'บริหารโปรเจคด้วยระบบ Agile ส่งมอบงานตามกำหนด'],
                    ['icon' => 'chat',   'accent' => '#8b5cf6', 'th' => 'ซัพพอร์ตตลอด 24/7', 'en' => 'Round-the-clock support',
                     'body' => 'ทีมซัพพอร์ตพร้อมช่วยเหลือทุกเวลา'],
                    ['icon' => 'wrench', 'accent' => '#ffd479', 'th' => 'ดูแลต่อเนื่องหลังส่งมอบ', 'en' => 'Ongoing maintenance',
                     'body' => 'อัปเดต แก้บั๊ก และปรับปรุงระบบให้ทันสมัยอยู่เสมอ'],
                ];
            @endphp
            @foreach($novaWhy as $w)
                <li class="nova-why__item" style="--nv-accent: {{ $w['accent'] }};">
                    <span class="nova-why__icon" aria-hidden="true">
                        @include('partials.nova-icon', ['name' => $w['icon']])
                    </span>
                    <div>
                        <h3 class="nova-why__title">{{ $w['th'] }}</h3>
                        <span class="nova-card__en">{{ $w['en'] }}</span>
                        <p class="nova-why__body">{{ $w['body'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>

<style>
    .nova-why {
        display: grid;
        gap: 48px;
        align-items: center;
        grid-template-columns: 1fr;
    }
    @media (min-width: 960px) {
        .nova-why { grid-template-columns: 1fr 1fr; gap: 72px; }
    }
    .nova-why__list {
        display: flex;
        flex-direction: column;
        gap: 18px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .nova-why__item {
        display: flex;
        gap: 16px;
        padding: 20px;
        border-radius: 18px;
        border: 1px solid var(--nv-line);
        background: var(--nv-panel);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        transition: border-color .35s var(--nv-ease), transform .35s var(--nv-ease);
    }
    .nova-why__item:hover {
        transform: translateX(5px);
        border-color: var(--nv-accent);
    }
    .nova-why__icon {
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 44px;
        height: 44px;
        border-radius: 13px;
        color: var(--nv-accent);
        background: rgba(255, 255, 255, .04);
        border: 1px solid var(--nv-line-hi);
    }
    .nova-why__icon svg { width: 21px; height: 21px; }
    .nova-why__title {
        margin: 0 0 2px;
        font-size: 16px;
        font-weight: 800;
        color: var(--nv-fg-1);
    }
    .nova-why__body {
        margin: 6px 0 0;
        font-size: 14px;
        line-height: 1.65;
        color: var(--nv-fg-2);
    }
</style>
