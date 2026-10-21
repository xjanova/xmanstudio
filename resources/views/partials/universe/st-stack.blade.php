{{--
    Stop 6 — the tech belt: a gas giant whose asteroid ring the flight passes
    through (world/Belt.js), and what we build with on two 3D rings of chips
    turning opposite ways: the AI models above, the development stack below.
--}}
@php
    $xuRings = [
        'ai' => ['th' => 'โมเดล AI ชั้นนำ', 'en' => 'AI models', 'items' => \App\Support\HomeContent::aiTech()],
        'dev' => ['th' => 'เทคโนโลยีพัฒนา', 'en' => 'Dev stack', 'items' => \App\Support\HomeContent::tech()],
    ];
@endphp
<section id="xu-stack" class="xu-st" data-station="stack" data-len="1.6"
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
                ทั้งเฟรมเวิร์กที่ทั่วโลกไว้ใจ และโมเดล AI ชั้นนำอย่าง Claude, OpenAI และ Gemini
                เราเลือกเครื่องมือที่เหมาะกับงานของคุณที่สุด
                <small>Frameworks the world trusts and the leading AI models, picked for your project.</small>
            </p>
        </header>

        <div class="xu-orbit xu-r" style="--i: 1.8;" data-xu-orbit>
            @foreach($xuRings as $ring => $spec)
                <div class="xu-orbit__group xu-orbit__group--{{ $ring }}">
                    <p class="xu-orbit__caption"><b>{{ $spec['th'] }}</b> <small>{{ $spec['en'] }}</small></p>
                    <ul class="xu-orbit__ring xu-orbit__ring--{{ $ring }}" style="--n: {{ count($spec['items']) }};" data-ring="{{ $ring }}">
                        @foreach($spec['items'] as $j => [$label, $src, $invert])
                            <li class="xu-orbit__item" style="--j: {{ $j }};">
                                <span class="xu-chip xu-chip--{{ $ring }}">
                                    <img src="{{ $src }}" alt="" loading="lazy" decoding="async" @if($invert) style="filter: invert(1);" @endif>
                                    {{ $label }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>
