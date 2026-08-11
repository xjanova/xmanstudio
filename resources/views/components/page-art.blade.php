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
])

@php
    // These values land inside a CSS url()/background-position, so keep them to a
    // known-safe shape rather than trusting the caller.
    $artSlug = preg_match('/^[a-z0-9-]+$/', $art) ? $art : 'hero-network';
    $artPosition = preg_match('/^[a-z0-9%. ]+$/i', $position) ? $position : 'center';
    $artOpacity = max(0, min(100, (int) $opacity)) / 100;
@endphp

<div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
    <div class="absolute inset-0 bg-cover bg-no-repeat"
         style="background-image:url('{{ asset('artwork/' . $artSlug . '.webp') }}');background-position:{{ $artPosition }};opacity:{{ $artOpacity }};"></div>
    @if($scrim)
        <div class="absolute inset-0"
             style="background:radial-gradient(ellipse at center, rgba(2,6,23,0.20) 0%, rgba(2,6,23,0.78) 100%);"></div>
    @endif
</div>
