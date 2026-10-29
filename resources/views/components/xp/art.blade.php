{{--
    Key art painted on black, screen-blended into the section behind it (the section needs `isolate`).
    `class` places it: position, width and opacity per breakpoint, as the WinXTools page does.
--}}
@props(['src', 'eager' => false])
<img src="{{ $src }}" alt="" aria-hidden="true" width="1536" height="1024"
     @if($eager) fetchpriority="high" @else loading="lazy" @endif
     {{ $attributes->merge(['class' => 'xp-art absolute -z-10']) }}>
