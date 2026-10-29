{{--
    The closing call to action of a Services page over its key art (x-xp.cta links on to the
    products list; this one ends on the services). Slot = the buttons; `footnote` = small print.
--}}
@props(['art' => null, 'title', 'glow' => null, 'sub' => null, 'en' => null])
<section class="relative py-24 isolate">
    @if($art)
        <x-xp.art :src="$art" class="left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[160%] sm:w-[110%] lg:w-[80%] opacity-35" />
    @endif
    <div class="absolute inset-0 -z-10" style="background:radial-gradient(ellipse 42% 46% at 50% 52%, rgba(4,6,13,.86) 0%, rgba(4,6,13,.55) 55%, transparent 100%);"></div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        {{-- each half is one block: Chromium otherwise breaks a Thai word in two at the line end --}}
        <h2 class="text-4xl md:text-6xl font-black text-white mb-5"><span class="inline-block">{{ $title }}</span>@if($glow) <span class="xp-text-glow inline-block">{{ $glow }}</span>@endif</h2>
        @if($sub)<p class="text-lg text-gray-300 mb-1">{{ $sub }}</p>@endif
        @if($en)<p class="text-sm text-gray-500">{{ $en }}</p>@endif
        <div class="flex flex-wrap justify-center gap-3 mt-9">{{ $slot }}</div>
        {{ $footnote ?? '' }}
    </div>
</section>
