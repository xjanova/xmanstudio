{{--
    The first screen: key art screen-blended on the right, words and buttons on the left, the app on the right.
    Slots: chips, title (the h1 contents), actions (download / buy), notes (under the buttons),
    badges (three li items), visual (the right column: usually a tilted app window and floating cards).
--}}
@props(['art' => null, 'tagline' => null, 'taglineEn' => null, 'lead' => null, 'leadEn' => null])
<section class="relative isolate">
    <div class="xp-grid absolute inset-0 -z-10"></div>
    <div class="xp-glow -z-10 w-[36rem] h-[36rem] -top-40 -left-40" style="background: rgb(var(--xp-glow) / .2)"></div>
    <div class="xp-glow -z-10 w-[30rem] h-[30rem] top-40 right-0" style="background: color-mix(in srgb, var(--xp-a) 10%, transparent)"></div>
    @if($art)
        <x-xp.art :src="$art" :eager="true" class="top-[-4%] right-[-30%] w-[150%] opacity-50 sm:right-[-12%] sm:w-[110%] lg:right-[-8%] lg:w-[78%] lg:opacity-95" />
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-20 lg:pt-12 lg:pb-28">
        <nav class="mb-10">
            <a href="{{ route('products.index') }}" class="xp-link inline-flex items-center text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <x-bi th="กลับไปรายการผลิตภัณฑ์" en="All products" />
            </a>
        </nav>

        <div class="grid lg:grid-cols-12 gap-12 items-center">
            <div class="lg:col-span-6">
                @isset($chips)<div class="flex flex-wrap items-center gap-2 mb-6">{{ $chips }}</div>@endisset

                <h1 class="text-5xl sm:text-6xl lg:text-7xl font-black tracking-tight text-white leading-[1.02] mb-5">{{ $title }}</h1>
                @if($tagline)<p class="text-2xl sm:text-3xl font-bold text-white mb-2">{{ $tagline }}</p>@endif
                @if($taglineEn)<p class="text-base sm:text-lg text-gray-400 mb-6">{{ $taglineEn }}</p>@endif

                @if($lead)<p class="text-lg text-gray-300 leading-relaxed mb-2">{{ $lead }}</p>@endif
                @if($leadEn)<p class="text-sm text-gray-500 leading-relaxed mb-8">{{ $leadEn }}</p>@endif

                @isset($actions)<div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>@endisset
                {{ $notes ?? '' }}

                @isset($badges)<ul class="mt-8 grid sm:grid-cols-3 gap-3 text-sm">{{ $badges }}</ul>@endisset
            </div>

            <div class="lg:col-span-6 relative">
                {{ $visual ?? '' }}
            </div>
        </div>
    </div>
</section>
