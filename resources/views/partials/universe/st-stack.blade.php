{{--
    Stop 6 — the tech belt: a gas giant whose asteroid ring the flight passes
    through (world/Belt.js), and our stack on a turning 3D ring of chips.
--}}
@php
    $xuTech = \App\Support\HomeContent::tech();
@endphp
<section id="xu-stack" class="xu-st" data-station="stack" data-len="1.5"
         data-label-th="วงแหวนเทคโนโลยี" data-label-en="Stack" aria-labelledby="xu-stack-title">
    <div class="xu-panel xu-stack">
        <header class="xu-head">
            <p class="xu-eyebrow xu-r" style="--i: 0;">
                <span class="xu-eyebrow__dot" aria-hidden="true"></span>
                เทคโนโลยีที่เราใช้ / Our stack
            </p>
            <h2 id="xu-stack-title" class="xu-h2 xu-r" style="--i: 0.6;">
                เครื่องมือระดับโลก <span class="xu-grad">ในมือทีมเรา</span>
            </h2>
            <p class="xu-lede xu-r" style="--i: 1.2;">
                พัฒนาด้วยเทคโนโลยีล่าสุดและเป็นที่ยอมรับในอุตสาหกรรม
                <small>Proven, current technology across every layer.</small>
            </p>
        </header>

        <div class="xu-orbit xu-r" style="--i: 1.8; --n: {{ count($xuTech) }};" data-xu-orbit>
            <ul class="xu-orbit__ring">
                @foreach($xuTech as $j => [$label, $icon])
                    <li class="xu-orbit__item" style="--j: {{ $j }};">
                        <span class="xu-chip">
                            <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/{{ $icon }}.svg"
                                 alt="" loading="lazy" decoding="async"
                                 @if($icon === 'amazonwebservices/amazonwebservices-plain-wordmark') style="filter: invert(1);" @endif>
                            {{ $label }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>
