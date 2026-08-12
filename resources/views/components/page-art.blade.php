{{--
    Decorative artwork layer for page heroes.
    Files live in public_html/artwork/ and are generated in-house (see docs/artwork.md).
    Purely decorative: absolutely positioned, non-interactive, hidden from assistive tech.
    The parent element must be position:relative.
--}}
@props([
    'art' => 'hero-network',
    'opacity' => 30,
    'position' => 'center',
    'scrim' => true,
    // Softens where the artwork stops. Without it the image ends on a hard
    // horizontal line at the section boundary, which reads as a seam once the
    // artwork is brighter than whatever sits below it.
    //   true (default) — fade top and bottom
    //   'bottom'       — keep the top at full strength (use at the top of a page)
    //   'top' | false
    'fade' => true,
])

@php
    // These values land inside a CSS url()/background-position, so keep them to a
    // known-safe shape rather than trusting the caller.
    $artSlug = preg_match('/^[a-z0-9-]+$/', $art) ? $art : 'hero-network';
    $artPosition = preg_match('/^[a-z0-9%. ]+$/i', $position) ? $position : 'center';
    $artOpacity = max(0, min(100, (int) $opacity)) / 100;

    // Built from a fixed set of strings — nothing from the caller reaches the CSS.
    $artMask = match ($fade) {
        false, 'none' => null,
        'bottom' => 'linear-gradient(to bottom, #000 0%, #000 55%, rgba(0,0,0,0.55) 80%, transparent 100%)',
        'top' => 'linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.55) 20%, #000 45%, #000 100%)',
        default => 'linear-gradient(to bottom, transparent 0%, #000 15%, #000 85%, transparent 100%)',
    };
@endphp

<div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true"
     @if($artMask) style="-webkit-mask-image:{{ $artMask }};mask-image:{{ $artMask }};" @endif>
    <div class="absolute inset-0 bg-cover bg-no-repeat"
         style="background-image:url('{{ asset('artwork/' . $artSlug . '.webp') }}');background-position:{{ $artPosition }};opacity:{{ $artOpacity }};"></div>
    @if($scrim)
        <div class="absolute inset-0"
             style="background:radial-gradient(ellipse at center, rgba(2,6,23,0.20) 0%, rgba(2,6,23,0.78) 100%);"></div>
    @endif
</div>
