{{--
    The closing call to action over the hero art. Slot = the buttons; `footnote` = the small print under them.
--}}
@props(['art' => null, 'title', 'glow' => null, 'sub' => null, 'en' => null])
<section class="relative py-24 isolate">
    @if($art)
        <x-xp.art :src="$art" class="left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[160%] sm:w-[110%] lg:w-[80%] opacity-35" />
    @endif
    <div class="absolute inset-0 -z-10" style="background:radial-gradient(ellipse 42% 46% at 50% 52%, rgba(4,6,13,.86) 0%, rgba(4,6,13,.55) 55%, transparent 100%);"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl md:text-6xl font-black text-white mb-5">{{ $title }}@if($glow)<span class="xp-text-glow">{{ $glow }}</span>@endif</h2>
        @if($sub)<p class="text-lg text-gray-300 mb-1">{{ $sub }}</p>@endif
        @if($en)<p class="text-sm text-gray-500 mb-9">{{ $en }}</p>@endif
        <div class="flex flex-wrap justify-center gap-3">{{ $slot }}</div>
        {{ $footnote ?? '' }}
        <a href="{{ route('products.index') }}" class="xp-link inline-block mt-4 text-sm"><x-bi th="ดูผลิตภัณฑ์อื่นของ XMAN Studio" en="More from XMAN Studio" /> →</a>
    </div>
</section>
