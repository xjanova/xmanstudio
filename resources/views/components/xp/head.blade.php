{{--
    A section heading: eyebrow, a title in two halves that never break mid-word (Chromium splits Thai
    words in balanced headings otherwise), a Thai line and its English line under it.
--}}
@props(['eyebrow' => null, 'title', 'title2' => null, 'sub' => null, 'en' => null, 'align' => 'center', 'size' => 'lg'])
@php
    $box = ['center' => 'text-center max-w-3xl mx-auto', 'left' => 'max-w-2xl', 'right' => 'max-w-2xl lg:ml-auto lg:text-right'][$align] ?? 'text-center max-w-3xl mx-auto';
    $h = $size === 'lg' ? 'text-3xl md:text-5xl' : 'text-3xl md:text-4xl';
@endphp
<div {{ $attributes->merge(['class' => $box]) }}>
    @if($eyebrow)<p class="xp-eyebrow mb-3">{{ $eyebrow }}</p>@endif
    <h2 class="{{ $h }} font-black text-white mb-4"><span class="whitespace-nowrap">{{ $title }}</span>@if($title2) <span class="whitespace-nowrap">{{ $title2 }}</span>@endif</h2>
    @if($sub)<p class="text-gray-300">{{ $sub }}</p>@endif
    @if($en)<p class="text-sm text-gray-500 mt-1">{{ $en }}</p>@endif
    {{ $slot }}
</div>
