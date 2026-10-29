{{--
    The first screen of a Services page, in the product-page look (x-xp.page): grid, glow, key art
    screen-blended on the right. x-xp.hero always links back to the products list; this one carries
    a breadcrumb instead, and a smaller title for long Thai service names (`compact`).
    crumbs: [[label, href|null], …] — the last one is the page itself.
    artClass: where the art sits, for art whose subject is not on its right (the default places it right).
    Slots: chips, title (the h1 contents), actions, notes, badges (li items), visual.
    Also carries the styles every svc component shares, so a page that has this hero has them all.
--}}
@props(['art' => null, 'artClass' => null, 'crumbs' => [], 'tagline' => null, 'taglineEn' => null, 'lead' => null, 'leadEn' => null, 'compact' => false])
@pushOnce('styles')
<style>
    /* in the components layer, so a utility on the same element (h-40, opacity-30 …) still wins */
    @layer components {
    /* a sale reads red-pink on every service, apart from the amber PRO of the product pages */
    .svc-sale { background: linear-gradient(105deg, #ff4d6d, #ff2e93); color: #fff; }
    /* a framed box with a glowing hairline: the category tiles, featured services, the option picture */
    .svc-box { position: relative; isolation: isolate; border-radius: 1.25rem; padding: 1px;
        background: linear-gradient(140deg, rgb(var(--xp-glow) / .5), rgba(139, 92, 246, .22) 45%, rgba(255, 255, 255, .06));
        box-shadow: 0 40px 90px -40px rgb(var(--xp-glow) / .35), 0 20px 50px -24px rgba(0, 0, 0, .8); }
    .svc-box > .svc-in { position: relative; isolation: isolate; height: 100%; border-radius: calc(1.25rem - 1px); overflow: hidden; background: #070b17; }
    /* artwork painted on black, screen-blended into the box it sits in */
    .svc-cover { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; mix-blend-mode: screen; z-index: -1; pointer-events: none; user-select: none; }
    .svc-fade { position: absolute; inset: 0; z-index: -1; pointer-events: none; background: linear-gradient(180deg, rgba(7, 11, 23, .05) 0%, rgba(7, 11, 23, .55) 55%, #070b17 100%); }
    .svc-cat { display: block; height: 100%; transition: transform .3s ease, box-shadow .3s ease; }
    .svc-cat:hover { transform: translateY(-3px); }
    .svc-cat:hover .svc-cover { transform: scale(1.06); }
    .svc-cat .svc-cover { transition: transform .6s ease; }
    .svc-cat[aria-expanded="true"] { background: linear-gradient(140deg, var(--xp-a), var(--xp-b)); box-shadow: 0 30px 80px -30px color-mix(in srgb, var(--xp-a) 60%, transparent); }
    .svc-icon { display: inline-flex; align-items: center; justify-content: center; width: 3.25rem; height: 3.25rem; border-radius: .9rem; font-size: 1.75rem; line-height: 1;
        background: rgba(4, 6, 13, .65); border: 1px solid rgba(255, 255, 255, .14); backdrop-filter: blur(6px); }
    /* a glass panel for forms and lists (the quotation builder); a border utility on it still sets its own colour */
    .svc-surface { background: linear-gradient(180deg, rgba(14, 19, 34, .9), rgba(8, 11, 22, .9)); border: 1px solid rgba(255, 255, 255, .08); backdrop-filter: blur(10px); }
    .svc-panel { animation: svc-open .35s ease-out; }
    @keyframes svc-open { from { opacity: 0; transform: translateY(-12px); } to { opacity: 1; transform: none; } }
    .svc-step { width: 3.25rem; height: 3.25rem; border-radius: 9999px; flex: none; display: inline-flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.25rem;
        background: linear-gradient(140deg, var(--xp-a), var(--xp-b)); color: #06110a; box-shadow: 0 10px 30px -10px color-mix(in srgb, var(--xp-a) 60%, transparent); }
    .svc-line { width: 2px; flex: 1; min-height: 1.5rem; margin: .4rem auto; background: linear-gradient(180deg, color-mix(in srgb, var(--xp-a) 60%, transparent), transparent); }
    @media (prefers-reduced-motion: reduce) {
        .svc-panel { animation: none; }
        .svc-cat, .svc-cat .svc-cover { transition: none; }
        .svc-cat:hover, .svc-cat:hover .svc-cover { transform: none; }
    }
    }
    /* the TPIX tag is styled for the light site; unlayered on purpose, to beat its utilities — on this dark stage it takes the amber of the coin */
    .svc-price [data-tpix-price] { background: rgba(251, 191, 36, .1); color: #fcd34d; }
    .svc-price [data-tpix-price] > span:first-child { color: #fcd34d; }
    .svc-price div[data-tpix-price] { background: none; }
    /* a disabled main button stays put (x-xp.page lifts .xp-btn-main on hover) */
    .xp .xp-btn-main:disabled, .xp .xp-btn-main:disabled:hover { transform: none; filter: none; cursor: not-allowed; }
</style>
@endPushOnce
<section class="relative isolate">
    <div class="xp-grid absolute inset-0 -z-10"></div>
    <div class="xp-glow -z-10 w-[36rem] h-[36rem] -top-40 -left-40" style="background: rgb(var(--xp-glow) / .18)"></div>
    <div class="xp-glow -z-10 w-[30rem] h-[30rem] top-40 right-0" style="background: color-mix(in srgb, var(--xp-b) 12%, transparent)"></div>
    @if($art)
        <x-xp.art :src="$art" :eager="true" :class="$artClass ?? 'top-[-4%] right-[-30%] w-[150%] opacity-50 sm:right-[-12%] sm:w-[110%] lg:right-[-8%] lg:w-[78%] lg:opacity-90'" />
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 pb-20 lg:pt-12 lg:pb-24">
        @if($crumbs)
            <nav class="mb-10" aria-label="breadcrumb">
                <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-400">
                    @foreach($crumbs as [$label, $href])
                        <li>
                            @if($href)
                                <a href="{{ $href }}" class="xp-link">{{ $label }}</a>
                            @else
                                <span class="text-gray-200" aria-current="page">{{ $label }}</span>
                            @endif
                        </li>
                        @unless($loop->last)<li aria-hidden="true" class="text-gray-600">/</li>@endunless
                    @endforeach
                </ol>
            </nav>
        @endif

        <div class="grid lg:grid-cols-12 gap-12 items-center">
            <div class="lg:col-span-6 min-w-0">
                @isset($chips)<div class="flex flex-wrap items-center gap-2 mb-6">{{ $chips }}</div>@endisset

                <h1 class="{{ $compact ? 'text-4xl sm:text-5xl lg:text-6xl leading-[1.1]' : 'text-5xl sm:text-6xl lg:text-7xl leading-[1.02]' }} font-black tracking-tight text-white mb-5 break-words">{{ $title }}</h1>
                @if($tagline)<p class="text-2xl sm:text-3xl font-bold text-white mb-2">{{ $tagline }}</p>@endif
                @if($taglineEn)<p class="text-base sm:text-lg text-gray-400 mb-6">{{ $taglineEn }}</p>@endif

                @if($lead)<p class="text-lg text-gray-300 leading-relaxed mb-2">{{ $lead }}</p>@endif
                @if($leadEn)<p class="text-sm text-gray-500 leading-relaxed mb-8">{{ $leadEn }}</p>@endif

                {{ $slot }}

                @isset($actions)<div class="flex flex-wrap items-center gap-3">{{ $actions }}</div>@endisset
                {{ $notes ?? '' }}

                @isset($badges)<ul class="mt-8 grid sm:grid-cols-3 gap-3 text-sm">{{ $badges }}</ul>@endisset
            </div>

            <div class="lg:col-span-6 relative min-w-0">
                {{ $visual ?? '' }}
            </div>
        </div>
    </div>
</section>
