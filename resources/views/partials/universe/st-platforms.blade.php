{{--
    Stop 5 — the platform planets: one planet per platform we run ourselves
    (world/Planets.js), visited one after another. Same copy and links as
    nova-ecosystem.blade.php, from HomeContent::platforms().
--}}
@php
    $xuPlatforms = \App\Support\HomeContent::platforms();
@endphp
<section id="xu-platforms" class="xu-st" data-station="platforms" data-len="{{ count($xuPlatforms) * 0.7 }}"
         data-label-th="ดาวเคราะห์แพลตฟอร์ม" data-label-en="Platforms" aria-labelledby="xu-platforms-title">
    <div class="xu-panel xu-planets">
        <header class="xu-planets__head">
            <p class="xu-eyebrow">
                <span class="xu-eyebrow__dot" aria-hidden="true"></span>
                แพลตฟอร์มของเรา / Our Platforms
            </p>
            <h2 id="xu-platforms-title" class="xu-h2">ระบบนิเวศ <span class="xu-grad">XMAN</span></h2>
            <p class="xu-lede">
                นอกจากงานรับพัฒนา เรายังสร้างแพลตฟอร์มของเราเองที่เปิดให้ใช้งานจริง
                <small>Products we build, run, and use ourselves.</small>
            </p>
        </header>

        @foreach($xuPlatforms as $k => $pl)
            <article class="xu-planet {{ $k % 2 === 0 ? 'xu-planet--right' : 'xu-planet--left' }}"
                     data-xu-planet="{{ $pl['key'] }}"
                     style="--k: {{ $k }}; --accent: {{ $pl['accent'] }};">
                <div class="xu-card xu-planet__card">
                    <p class="xu-planet__index">
                        <span>{{ str_pad((string) ($k + 1), 2, '0', STR_PAD_LEFT) }}</span> / {{ str_pad((string) count($xuPlatforms), 2, '0', STR_PAD_LEFT) }}
                        @if($pl['badge'])
                            <span class="xu-badge">{{ $pl['badge'] }}</span>
                        @endif
                    </p>
                    <div class="xu-planet__title">
                        <span class="xu-planet__icon" aria-hidden="true">@include('partials.nova-icon', ['name' => $pl['icon']])</span>
                        <span>
                            <h3>{{ $pl['title'] }}</h3>
                            <small>{{ $pl['en'] }}</small>
                        </span>
                    </div>
                    <p class="xu-planet__body">{{ $pl['body'] }}</p>
                    <div class="xu-planet__links">
                        @foreach($pl['links'] as $link)
                            @continue(($link['auth'] ?? false) && ! auth()->check())
                            @if($link['kind'] === 'note')
                                <span class="xu-planet__note">{{ $link['label'] }}</span>
                            @else
                                <a href="{{ $link['href'] }}"
                                   @if($link['external'] ?? false) target="_blank" rel="noopener noreferrer" @endif
                                   class="{{ match ($link['kind']) { 'primary' => 'xu-btn xu-btn--primary xu-btn--sm', 'ghost' => 'xu-btn xu-btn--ghost xu-btn--sm', default => 'xu-planet__sub' } }}">
                                    <span>{{ $link['label'] }}</span>
                                    @if($link['kind'] === 'primary')
                                        @include('partials.nova-icon', ['name' => ($link['external'] ?? false) ? 'external' : 'arrow'])
                                    @endif
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
