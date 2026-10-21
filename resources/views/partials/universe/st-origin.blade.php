{{--
    Stop 2 — origin: the four headline numbers, each drawn by a cloud of stars
    that forms it (world/Constellation.js), then why us. The numbers are also
    written out here, for screen readers and the no-JavaScript layout.
--}}
@php
    $xuStats = \App\Support\HomeContent::stats();
@endphp
<section id="xu-origin" class="xu-st" data-station="origin" data-len="2.9"
         data-label-th="จุดกำเนิด" data-label-en="Origin" aria-labelledby="xu-origin-title">
    <div class="xu-panel xu-origin">
        <div class="xu-origin__stats">
            <p class="xu-eyebrow">
                <span class="xu-eyebrow__dot" aria-hidden="true"></span>
                ตัวเลขที่พิสูจน์แล้ว / Proven in numbers
            </p>
            <h2 id="xu-origin-title" class="xu-sr">XMAN Studio ในตัวเลข / XMAN Studio in numbers</h2>
            <ol class="xu-stats">
                @foreach($xuStats as $k => $stat)
                    <li class="xu-stat" data-xu-stat="{{ $stat['value'] . $stat['suffix'] }}" style="--k: {{ $k }};">
                        <span class="xu-stat__num">{{ $stat['value'] . $stat['suffix'] }}</span>
                        <span class="xu-stat__label"><b>{{ $stat['th'] }}</b><small>{{ $stat['en'] }}</small></span>
                    </li>
                @endforeach
            </ol>
            <div class="xu-stats__pips" aria-hidden="true">
                @foreach($xuStats as $k => $stat)
                    <i style="--k: {{ $k }};"></i>
                @endforeach
            </div>
        </div>

        <div class="xu-origin__why">
            <p class="xu-eyebrow xu-r" style="--i: 0;">
                <span class="xu-eyebrow__dot" aria-hidden="true"></span>
                ทำไมต้องเลือกเรา / Why us
            </p>
            <h2 class="xu-h2 xu-r" style="--i: 1;">
                พาร์ทเนอร์ด้านเทคโนโลยี<br><span class="xu-grad">ที่คุณไว้วางใจได้</span>
            </h2>
            <p class="xu-lede xu-r" style="--i: 2;">
                เราเป็นทีมนักพัฒนามืออาชีพที่มีประสบการณ์กว่า 8 ปี
                ในการพัฒนาซอฟต์แวร์และโซลูชั่น IT ให้กับองค์กรชั้นนำ
                <small>A senior team with 15+ developers, shipping production software since 2018.</small>
            </p>
            <ul class="xu-why">
                @foreach(\App\Support\HomeContent::why() as $j => $w)
                    <li class="xu-why__item xu-r" style="--i: {{ 3 + $j * 0.6 }}; --accent: {{ $w['accent'] }};">
                        <span class="xu-why__icon" aria-hidden="true">@include('partials.nova-icon', ['name' => $w['icon']])</span>
                        <span>
                            <b>{{ $w['th'] }}</b>
                            <small>{{ $w['en'] }}</small>
                            <span class="xu-why__body">{{ $w['body'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('about') }}" class="xu-btn xu-btn--ghost xu-r" style="--i: 6;">
                <span>รู้จักเรา <small>About us</small></span>
                @include('partials.nova-icon', ['name' => 'arrow'])
            </a>
        </div>
    </div>
</section>
