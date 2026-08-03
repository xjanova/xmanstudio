{{-- Nova tech stack + customer reviews. --}}
@php
    $novaTech = [
        ['React',      'react/react-original'],
        ['Flutter',    'flutter/flutter-original'],
        ['Laravel',    'laravel/laravel-original'],
        ['Node.js',    'nodejs/nodejs-original'],
        ['Python',     'python/python-original'],
        ['AWS',        'amazonwebservices/amazonwebservices-plain-wordmark'],
        ['PHP',        'php/php-original'],
        ['TypeScript', 'typescript/typescript-original'],
        ['Docker',     'docker/docker-original'],
        ['PostgreSQL', 'postgresql/postgresql-original'],
        ['Tailwind',   'tailwindcss/tailwindcss-original'],
        ['Solidity',   'solidity/solidity-original'],
    ];
@endphp

<section class="nova-section nova-section--tight">
    <div class="nova-shell">
        <header class="nova-head nova-reveal" style="margin-bottom:36px;">
            <h2 class="nova-h2" style="font-size:clamp(22px,3vw,32px);">
                เทคโนโลยีที่เราใช้ <span class="nova-grad">/ Our stack</span>
            </h2>
            <p class="nova-lede" style="font-size:15px;">
                พัฒนาด้วยเทคโนโลยีล่าสุดและเป็นที่ยอมรับในอุตสาหกรรม
            </p>
        </header>
    </div>

    {{-- Full-bleed marquee. The track is duplicated so the -50% translate
         loops seamlessly; aria-hidden on the copy keeps it out of the
         accessibility tree. --}}
    <div class="nova-marquee nova-reveal">
        <div class="nova-marquee__track">
            @for($pass = 0; $pass < 2; $pass++)
                <div style="display:flex;gap:16px;" @if($pass === 1) aria-hidden="true" @endif>
                    @foreach($novaTech as [$label, $icon])
                        <span class="nova-tech">
                            <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/{{ $icon }}.svg"
                                 alt="" loading="lazy" decoding="async"
                                 @if($icon === 'amazonwebservices/amazonwebservices-plain-wordmark') style="filter:invert(1);" @endif>
                            {{ $label }}
                        </span>
                    @endforeach
                </div>
            @endfor
        </div>
    </div>
</section>

@if(isset($featuredReviews) && $featuredReviews->isNotEmpty())
<section class="nova-section">
    <div class="nova-section__glow" aria-hidden="true"></div>
    <div class="nova-shell">
        <header class="nova-head nova-reveal">
            <span class="nova-eyebrow">
                <span class="nova-eyebrow__dot"></span>
                รีวิวจากลูกค้า / Reviews
            </span>
            <h2 class="nova-h2">
                เสียงจาก <span class="nova-grad">ผู้ใช้งานจริง</span>
            </h2>
        </header>

        <div class="nova-grid">
            @foreach($featuredReviews as $review)
                <article class="nova-card nova-reveal"
                         style="--nv-accent:#ffd479; transition-delay: {{ $loop->index * 0.05 }}s;">
                    <div class="nova-review__stars"
                         aria-label="{{ $review->rating }} เต็ม 5 ดาว / {{ $review->rating }} out of 5 stars">
                        @for($s = 1; $s <= 5; $s++)
                            <svg viewBox="0 0 20 20" fill="{{ $s <= $review->rating ? 'currentColor' : 'none' }}"
                                 stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                                <path d="M10 2.5l2.3 4.9 5.2.7-3.8 3.7.9 5.3-4.6-2.5-4.6 2.5.9-5.3L2.5 8.1l5.2-.7z"/>
                            </svg>
                        @endfor
                    </div>

                    @if($review->title)
                        <h3 class="nova-card__title" style="font-size:16px;">{{ $review->title }}</h3>
                    @endif

                    <p class="nova-card__body">{{ Str::limit($review->comment, 190) }}</p>

                    <div class="nova-review__who">
                        @if($review->user?->avatar)
                            <img src="{{ $review->user->avatar_url }}"
                                 alt="" class="nova-review__avatar" loading="lazy" decoding="async">
                        @else
                            <span class="nova-review__avatar" aria-hidden="true">
                                {{ mb_substr($review->user?->name ?? '?', 0, 1) }}
                            </span>
                        @endif
                        <div>
                            <div style="font-size:14px;font-weight:700;color:var(--nv-fg-1);">
                                {{ $review->user?->name ?? 'ผู้ใช้' }}
                            </div>
                            <div style="font-size:12px;color:var(--nv-fg-3);">
                                {{ $review->reviewable_name }} &middot; {{ $review->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
