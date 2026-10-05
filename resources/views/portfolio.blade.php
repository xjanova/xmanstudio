@extends($publicLayout ?? 'layouts.app')

@section('title', 'ผลงานจริงของเรา - XMAN Studio')
@section('meta_description', 'ผลงานที่เปิดใช้งานจริงของ XMAN Studio — ซูเปอร์แอป Thai Prompt, เว็บบริษัทกอย่งเชียงกรุ๊ป, ลูกโลกสภาพอากาศ ATMOS 3D, บล็อกเชนและกระดานเทรด TPIX, GenLotto Lab และแอปมือถือที่เราสร้าง ภาพทั้งหมดแคปจากหน้าจอจริง')

@php
    $featured = \App\Support\PortfolioContent::featured();
    $more = \App\Support\PortfolioContent::more();
    $apps = \App\Support\PortfolioContent::apps();
    $img = fn (string $file) => \App\Support\PortfolioContent::img($file);
    $liveCount = count($featured) + count($more);
@endphp

@push('styles')
<style>
    /* Portfolio showcase — always the dark "screening room", whatever the theme: the work is the
       light source here. Per-project colour arrives as --a / --a2 on each case. */
    /* the glows reach past the edges on purpose; clip them so phones never scroll sideways */
    .pf { background: #05060d; color: #eef1f8; overflow-x: hidden; overflow-x: clip; }
    .pf-mono { font-family: "JetBrains Mono", ui-monospace, "Cascadia Code", Consolas, monospace; }
    .pf-glow { position: absolute; border-radius: 9999px; filter: blur(90px); pointer-events: none; }
    .pf-browser {
        position: relative; border-radius: 18px; overflow: hidden; background: #0b0e1c;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 50px 90px -40px rgba(0, 0, 0, 0.9), 0 0 70px -24px var(--a);
        transition: transform 0.7s cubic-bezier(0.2, 0.8, 0.2, 1), box-shadow 0.5s;
    }
    @media (min-width: 1024px) {
        .pf-case .pf-browser { transform: perspective(1800px) rotateY(var(--tilt, -5deg)) rotateX(2deg); }
        .pf-case.is-flip .pf-browser { --tilt: 5deg; }
        .pf-case .pf-stage:hover .pf-browser { transform: perspective(1800px) rotateY(0) rotateX(0); box-shadow: 0 50px 90px -40px rgba(0, 0, 0, 0.9), 0 0 90px -18px var(--a); }
    }
    .pf-bar { display: flex; align-items: center; gap: 7px; height: 38px; padding: 0 14px; background: linear-gradient(180deg, #151a33, #0d1124); border-bottom: 1px solid rgba(255, 255, 255, 0.07); }
    .pf-bar i { width: 10px; height: 10px; border-radius: 50%; background: #ff5f57; flex: none; }
    .pf-bar i:nth-child(2) { background: #febc2e; }
    .pf-bar i:nth-child(3) { background: #28c840; }
    .pf-url { margin-left: 10px; flex: 1; min-width: 0; height: 24px; display: flex; align-items: center; gap: 6px; padding: 0 10px; border-radius: 7px; background: rgba(255, 255, 255, 0.05); font-size: 12px; color: #aeb5d2; white-space: nowrap; overflow: hidden; }
    .pf-live { display: inline-flex; align-items: center; gap: 5px; font-size: 10px; letter-spacing: 0.14em; color: #5eead4; flex: none; }
    .pf-live::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #5eead4; box-shadow: 0 0 8px #5eead4; animation: pfPulse 2.2s ease-in-out infinite; }
    @keyframes pfPulse { 50% { opacity: 0.3; } }
    .pf-phone {
        position: absolute; z-index: 2; width: 23%; min-width: 118px; max-width: 210px; aspect-ratio: 390 / 844;
        border-radius: 26px; padding: 5px; background: linear-gradient(160deg, #2a2f45, #0b0d16);
        box-shadow: 0 40px 70px -24px rgba(0, 0, 0, 0.95), 0 0 0 1px rgba(255, 255, 255, 0.08), 0 0 50px -18px var(--a);
        transition: transform 0.7s cubic-bezier(0.2, 0.8, 0.2, 1);
    }
    .pf-phone img { width: 100%; height: 100%; object-fit: cover; object-position: top; border-radius: 21px; display: block; }
    .pf-stage:hover .pf-phone { transform: translateY(-10px); }
    .pf-chip { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 8px; font-size: 12px; border: 1px solid rgba(255, 255, 255, 0.12); background: rgba(255, 255, 255, 0.03); color: #cfd5ee; }
    .pf-accent { color: var(--a); }
    .pf-tag { color: var(--a); border-color: color-mix(in srgb, var(--a) 45%, transparent); background: color-mix(in srgb, var(--a) 10%, transparent); }
    .pf-btn { display: inline-flex; align-items: center; gap: 10px; height: 50px; padding: 0 22px; border-radius: 12px; font-weight: 700; transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s; }
    .pf-btn-main { background: var(--a); color: #06070d; box-shadow: 0 14px 30px -10px var(--a); }
    .pf-btn-main:hover { transform: translateY(-3px); box-shadow: 0 20px 40px -10px var(--a); }
    .pf-btn-ghost { border: 1px solid rgba(255, 255, 255, 0.18); color: #fff; background: rgba(255, 255, 255, 0.03); }
    .pf-btn-ghost:hover { border-color: var(--a); transform: translateY(-2px); }
    .pf-thumb { border: 1px solid rgba(255, 255, 255, 0.1); background: rgba(255, 255, 255, 0.03); transition: border-color 0.2s, background 0.2s; }
    .pf-thumb[aria-pressed="true"] { border-color: var(--a); background: color-mix(in srgb, var(--a) 12%, transparent); }
    .pf-card { border: 1px solid rgba(255, 255, 255, 0.09); background: linear-gradient(180deg, rgba(20, 23, 44, 0.9), rgba(9, 11, 24, 0.95)); transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1), border-color 0.3s, box-shadow 0.3s; }
    .pf-card:hover { transform: translateY(-6px); border-color: color-mix(in srgb, var(--a) 50%, transparent); box-shadow: 0 30px 60px -30px rgba(0, 0, 0, 0.9), 0 0 50px -20px var(--a); }
    .pf-card:hover .pf-card-img { transform: scale(1.04); }
    .pf-card-img { transition: transform 0.8s cubic-bezier(0.2, 0.8, 0.2, 1); }
    .pf-grid-bg { background-image: linear-gradient(rgba(150, 170, 255, 0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(150, 170, 255, 0.05) 1px, transparent 1px); background-size: 48px 48px; }
    .pf .bi-en { opacity: 0.6; }
    /* mobile apps: three real screens fanned on a phone stage */
    .pf-app { border: 1px solid rgba(255, 255, 255, 0.09); background: linear-gradient(180deg, rgba(20, 23, 44, 0.92), rgba(8, 9, 20, 0.96)); transition: border-color 0.3s, box-shadow 0.3s; }
    .pf-app:hover { border-color: color-mix(in srgb, var(--a) 45%, transparent); box-shadow: 0 30px 70px -30px rgba(0, 0, 0, 0.9), 0 0 60px -24px var(--a); }
    .pf-app-stage { position: relative; height: 400px; overflow: hidden; background: radial-gradient(ellipse 70% 80% at 50% 25%, color-mix(in srgb, var(--a2) 70%, transparent), transparent 72%), radial-gradient(ellipse 40% 40% at 50% 70%, color-mix(in srgb, var(--a) 22%, transparent), transparent 70%); }
    .pf-app-stage::after { content: ""; position: absolute; inset: auto 0 0; height: 30%; background: linear-gradient(0deg, rgba(10, 11, 24, 0.98), transparent); pointer-events: none; z-index: 5; }
    .pf-app-phone {
        position: absolute; left: 50%; top: 9%; width: min(38%, 212px); aspect-ratio: 390 / 844; border-radius: 28px; padding: 5px; margin: 0;
        background: linear-gradient(160deg, #2c3148, #0b0d16);
        box-shadow: 0 34px 60px -24px rgba(0, 0, 0, 0.95), 0 0 0 1px rgba(255, 255, 255, 0.08);
        transition: transform 0.7s cubic-bezier(0.2, 0.8, 0.2, 1);
    }
    .pf-app-phone img { width: 100%; height: 100%; display: block; object-fit: cover; object-position: top; border-radius: 23px; }
    .pf-app-phone.pos-0 { z-index: 3; transform: translateX(-50%); box-shadow: 0 34px 60px -24px rgba(0, 0, 0, 0.95), 0 0 0 1px rgba(255, 255, 255, 0.1), 0 0 50px -16px var(--a); }
    .pf-app-phone.pos-1 { z-index: 2; transform: translateX(-122%) translateY(7%) rotate(-8deg) scale(0.88); }
    .pf-app-phone.pos-2 { z-index: 1; transform: translateX(22%) translateY(7%) rotate(8deg) scale(0.88); }
    .pf-app:hover .pf-app-phone.pos-0 { transform: translateX(-50%) translateY(-4%); }
    .pf-app:hover .pf-app-phone.pos-1 { transform: translateX(-132%) translateY(5%) rotate(-11deg) scale(0.88); }
    .pf-app:hover .pf-app-phone.pos-2 { transform: translateX(32%) translateY(5%) rotate(11deg) scale(0.88); }
    @media (max-width: 640px) { .pf-app-stage { height: 330px; } }
    @media (prefers-reduced-motion: reduce) {
        .pf-browser, .pf-phone, .pf-card, .pf-card-img, .pf-btn, .pf-app-phone { transition: none; }
        .pf-live::before { animation: none; }
    }
</style>
@endpush

@section('content')
<div class="pf">

    {{-- ================================================================ hero --}}
    <section class="relative overflow-hidden pt-20 pb-16 md:pt-28 md:pb-20">
        <x-page-art art="hero-portfolio" :opacity="38" fade="bottom" />
        <div class="pf-glow w-[520px] h-[520px] -top-40 left-1/2 -translate-x-1/2 bg-violet-600/25"></div>
        <div class="pf-glow w-[380px] h-[380px] top-40 -left-24 bg-cyan-500/10"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 text-emerald-300 text-sm font-semibold backdrop-blur-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-400 shadow-[0_0_10px_#34d399] animate-pulse"></span>
                <x-bi th="ใช้งานจริงทุกชิ้น" en="Live in production" />
            </span>
            <h1 class="mt-6 text-4xl md:text-6xl lg:text-7xl font-extrabold tracking-tight leading-[1.08]">
                ผลงานจริง<br>
                <span class="bg-gradient-to-r from-cyan-300 via-violet-300 to-amber-200 bg-clip-text text-transparent">ที่เปิดใช้งานอยู่ตอนนี้</span>
            </h1>
            <p class="mt-3 text-lg md:text-xl text-gray-400 font-medium">Real work, live right now.</p>
            <p class="mt-6 text-base md:text-lg text-gray-300 max-w-3xl mx-auto leading-relaxed">
                ทุกชิ้นด้านล่างคือเว็บและแอปที่เราสร้างและใช้งานจริง — ภาพทั้งหมดแคปจากหน้าจอจริง ไม่ใช่ภาพจำลอง
                <span class="block text-sm text-gray-500 mt-1">Every site and app below is real and in use — every picture is a real capture, not a mock-up.</span>
            </p>

            <div class="mt-10 grid grid-cols-2 md:grid-cols-4 gap-px max-w-3xl mx-auto rounded-2xl overflow-hidden border border-white/10 bg-white/10 backdrop-blur-sm">
                <div class="py-5 px-2 bg-[#0b0d1a]">
                    <div class="text-3xl md:text-4xl font-extrabold">{{ str_pad((string) $liveCount, 2, '0', STR_PAD_LEFT) }}</div>
                    <div class="mt-1 text-xs md:text-sm text-gray-400"><x-bi th="เว็บที่ใช้งานจริง" en="Live sites" layout="stack" /></div>
                </div>
                <div class="py-5 px-2 bg-[#0b0d1a]">
                    <div class="text-3xl md:text-4xl font-extrabold">{{ str_pad((string) count($apps), 2, '0', STR_PAD_LEFT) }}</div>
                    <div class="mt-1 text-xs md:text-sm text-gray-400"><x-bi th="แอปมือถือ" en="Mobile apps" layout="stack" /></div>
                </div>
                <div class="py-5 px-2 bg-[#0b0d1a]">
                    <div class="text-3xl md:text-4xl font-extrabold">{{ str_pad((string) count($featured), 2, '0', STR_PAD_LEFT) }}</div>
                    <div class="mt-1 text-xs md:text-sm text-gray-400"><x-bi th="กรณีศึกษาเต็ม" en="Case studies" layout="stack" /></div>
                </div>
                <div class="py-5 px-2 bg-[#0b0d1a]">
                    <div class="text-3xl md:text-4xl font-extrabold">100%</div>
                    <div class="mt-1 text-xs md:text-sm text-gray-400"><x-bi th="ภาพจากหน้าจอจริง" en="Real screenshots" layout="stack" /></div>
                </div>
            </div>

            <nav class="mt-8 flex flex-wrap justify-center gap-2" aria-label="ไปยังผลงาน / Jump to a project">
                @foreach ($featured as $p)
                    <a href="#case-{{ $p['id'] }}" class="pf-chip hover:border-white/30 hover:text-white transition-colors" style="--a: {{ $p['accent'] }}">
                        <span class="w-2 h-2 rounded-full" style="background: {{ $p['accent'] }}"></span>{{ $p['name'] }}
                    </a>
                @endforeach
                <a href="#apps" class="pf-chip hover:border-white/30 hover:text-white transition-colors"><x-bi th="แอปมือถือ" en="Apps" /> ↓</a>
                <a href="#more-work" class="pf-chip hover:border-white/30 hover:text-white transition-colors"><x-bi th="ผลงานอื่น" en="More" /> ↓</a>
            </nav>
        </div>
    </section>

    {{-- ================================================================ case studies --}}
    @foreach ($featured as $i => $p)
        @php $flip = $i % 2 === 1; @endphp
        <section id="case-{{ $p['id'] }}" class="pf-case relative py-16 md:py-24 scroll-mt-20 {{ $flip ? 'is-flip' : '' }}"
                 style="--a: {{ $p['accent'] }}; --a2: {{ $p['accent2'] }}" aria-labelledby="case-title-{{ $p['id'] }}">
            <div class="pf-glow w-[560px] h-[420px] top-10 {{ $flip ? '-right-40' : '-left-40' }} opacity-30" style="background: {{ $p['accent2'] }}"></div>
            <div class="pf-glow w-[380px] h-[300px] bottom-0 {{ $flip ? 'left-1/3' : 'right-1/4' }} opacity-[0.12]" style="background: {{ $p['accent'] }}"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-12 gap-12 lg:gap-14 items-center">

                {{-- the real site, in a browser and on a phone --}}
                <div class="lg:col-span-7 {{ $flip ? 'lg:order-2' : '' }}" x-data="{ shot: 0 }">
                    <div class="pf-stage relative pb-10 {{ $flip ? 'pl-0 lg:pl-8' : 'pr-0 lg:pr-8' }}">
                        <div class="pf-browser">
                            <div class="pf-bar">
                                <i></i><i></i><i></i>
                                <span class="pf-url pf-mono">
                                    <svg class="w-3.5 h-3.5 flex-none text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 1 1 8 0v3"/></svg>
                                    {{ $p['domain'] }}
                                </span>
                                <span class="pf-live pf-mono">LIVE</span>
                            </div>
                            <div class="relative aspect-[16/10] bg-black overflow-hidden">
                                @foreach ($p['images'] as $k => $im)
                                    <img src="{{ $img($im['file']) }}" width="1280" height="800" loading="lazy" decoding="async"
                                         alt="{{ $p['name'] }} — {{ $im['th'] }} (ภาพหน้าจอจริงบนเดสก์ท็อป)"
                                         class="absolute inset-0 w-full h-full object-cover object-top transition-opacity duration-500 {{ $k === 0 ? 'opacity-100' : 'opacity-0' }}"
                                         :class="shot === {{ $k }} ? 'opacity-100' : 'opacity-0'">
                                @endforeach
                            </div>
                        </div>
                        <div class="pf-phone bottom-0 {{ $flip ? 'left-0 lg:-left-2' : 'right-0 lg:-right-2' }}">
                            <img src="{{ $img($p['mobile']) }}" width="540" height="1169" loading="lazy" decoding="async"
                                 alt="{{ $p['name'] }} บนมือถือ (ภาพหน้าจอจริง)">
                        </div>
                    </div>
                    @if (count($p['images']) > 1)
                        <div class="mt-4 flex flex-wrap gap-2 {{ $flip ? 'lg:justify-end' : '' }}" role="group" aria-label="เลือกหน้าที่จะดู / Pick a page">
                            @foreach ($p['images'] as $k => $im)
                                <button type="button" class="pf-thumb inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-gray-200"
                                        @click="shot = {{ $k }}" :aria-pressed="(shot === {{ $k }}).toString()" aria-pressed="{{ $k === 0 ? 'true' : 'false' }}">
                                    <img src="{{ $img($im['file']) }}" alt="" loading="lazy" decoding="async" class="w-14 h-9 rounded-md object-cover object-top">
                                    <x-bi :th="$im['th']" :en="$im['en']" />
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- the story --}}
                <div class="lg:col-span-5 {{ $flip ? 'lg:order-1' : '' }}">
                    <div class="flex items-center gap-4">
                        <span class="pf-mono text-5xl font-bold text-white/10 leading-none">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="pf-chip pf-tag"><x-bi :th="$p['type_th']" :en="$p['type_en']" /></span>
                    </div>
                    <h2 id="case-title-{{ $p['id'] }}" class="mt-5 text-4xl md:text-5xl font-extrabold tracking-tight">
                        {{ $p['name'] }}
                        @isset($p['name_en'])<span class="block mt-1 text-lg md:text-xl font-semibold text-gray-400">{{ $p['name_en'] }}</span>@endisset
                    </h2>
                    <p class="mt-3 text-sm pf-accent font-medium">{{ $p['client_th'] }}</p>
                    <p class="mt-5 text-gray-200 leading-relaxed">{{ $p['summary_th'] }}</p>
                    <p class="mt-2 text-sm text-gray-500">{{ $p['summary_en'] }}</p>

                    <h3 class="mt-8 text-xs pf-mono tracking-[0.2em] text-gray-400"><x-bi th="สิ่งที่เราทำ" en="WHAT WE BUILT" /></h3>
                    <ul class="mt-3 space-y-2.5">
                        @foreach ($p['built'] as $b)
                            <li class="flex gap-3 text-gray-200">
                                <svg class="w-5 h-5 mt-0.5 flex-none pf-accent" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ $b['th'] }} <span class="text-gray-500 text-sm">· {{ $b['en'] }}</span></span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8 grid grid-cols-3 gap-3">
                        @foreach ($p['facts'] as $f)
                            <div class="rounded-xl border border-white/10 bg-white/[0.03] px-3 py-3">
                                <div class="text-lg md:text-xl font-extrabold pf-accent whitespace-nowrap">{{ $f['value'] }}</div>
                                <div class="mt-1 text-xs text-gray-400 leading-snug">{{ $f['th'] }}<span class="block text-gray-600">{{ $f['en'] }}</span></div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($p['stack'] as $t)
                            <span class="pf-chip pf-mono text-[11px]">{{ $t }}</span>
                        @endforeach
                    </div>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ $p['url'] }}" target="_blank" rel="noopener" class="pf-btn pf-btn-main">
                            <x-bi th="เปิดเว็บจริง" en="Visit live site" />
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M9 7h8v8"/></svg>
                        </a>
                        @foreach ($p['links'] as $l)
                            <a href="{{ $l['href'] }}" target="_blank" rel="noopener" class="pf-btn pf-btn-ghost"><x-bi :th="$l['th']" :en="$l['en']" /> ↗</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endforeach

    {{-- ================================================================ mobile apps --}}
    @if (count($apps))
        <section id="apps" class="relative py-16 md:py-24 scroll-mt-20 border-t border-white/5" aria-labelledby="apps-title">
            <div class="pf-glow w-[700px] h-[420px] left-1/2 -translate-x-1/2 top-10 bg-violet-700/20"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto">
                    <span class="pf-mono text-xs tracking-[0.25em] text-gray-400">MOBILE APPS</span>
                    <h2 id="apps-title" class="mt-3 text-3xl md:text-5xl font-extrabold tracking-tight"><x-bi th="แอปมือถือที่เราสร้าง" en="Mobile apps we built" layout="stack" /></h2>
                    <p class="mt-4 text-gray-300">ออกแบบและเขียนเองทุกหน้าจอ — ภาพด้านล่างแคปจากหน้าจอของแอปจริง</p>
                    <p class="mt-1 text-sm text-gray-500">Designed and built in-house — every screen below is captured from the real app.</p>
                </div>
                <div class="mt-12 grid lg:grid-cols-2 gap-8">
                    @foreach ($apps as $a)
                        <article class="pf-app rounded-3xl overflow-hidden flex flex-col" style="--a: {{ $a['accent'] }}; --a2: {{ $a['accent2'] }}" aria-labelledby="app-title-{{ $a['id'] }}">
                            <div class="pf-app-stage">
                                @foreach (array_slice($a['screens'], 0, 3) as $k => $sc)
                                    <figure class="pf-app-phone pos-{{ $k }}">
                                        <img src="{{ $img($sc['file']) }}" width="540" height="1169" loading="lazy" decoding="async"
                                             alt="{{ $a['name'] }} — {{ $sc['th'] }} (ภาพหน้าจอแอปจริง)">
                                    </figure>
                                @endforeach
                            </div>
                            <div class="relative z-10 -mt-10 p-6 md:p-8 flex flex-col flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="pf-chip pf-tag"><x-bi :th="$a['type_th']" :en="$a['type_en']" /></span>
                                    @foreach ($a['platforms'] as $pl)
                                        <span class="pf-chip pf-mono text-[11px]">{{ $pl }}</span>
                                    @endforeach
                                </div>
                                <h3 id="app-title-{{ $a['id'] }}" class="mt-4 text-3xl font-extrabold tracking-tight">{{ $a['name'] }}</h3>
                                <p class="mt-3 text-gray-200 leading-relaxed">{{ $a['summary_th'] }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $a['summary_en'] }}</p>
                                <ul class="mt-5 grid sm:grid-cols-2 gap-x-4 gap-y-2">
                                    @foreach ($a['features'] as $f)
                                        <li class="flex gap-2 text-sm text-gray-300">
                                            <svg class="w-4 h-4 mt-0.5 flex-none pf-accent" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                            <span>{{ $f }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="mt-5 flex flex-wrap gap-2">
                                    @foreach ($a['stack'] as $t)
                                        <span class="pf-chip pf-mono text-[11px]">{{ $t }}</span>
                                    @endforeach
                                </div>
                                <p class="mt-4 text-xs text-gray-500">
                                    <x-bi th="หน้าจอที่แสดง" en="Screens shown" />: {{ collect(array_slice($a['screens'], 0, 3))->pluck('th')->implode(' · ') }}
                                </p>
                                @if (! empty($a['link']))
                                    <div class="mt-auto pt-6">
                                        <a href="{{ $a['link']['href'] }}" target="_blank" rel="noopener" class="pf-btn pf-btn-ghost"><x-bi :th="$a['link']['th']" :en="$a['link']['en']" /> ↗</a>
                                    </div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ================================================================ more work --}}
    <section id="more-work" class="relative py-16 md:py-24 scroll-mt-20 border-t border-white/5">
        <div class="absolute inset-0 pf-grid-bg [mask-image:radial-gradient(ellipse_at_center,#000,transparent_75%)] pointer-events-none"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto">
                <span class="pf-mono text-xs tracking-[0.25em] text-gray-400">MORE LIVE WORK</span>
                <h2 class="mt-3 text-3xl md:text-4xl font-extrabold"><x-bi th="ผลงานอื่นที่เปิดใช้งานอยู่" en="More from the studio" layout="stack" /></h2>
                <p class="mt-4 text-gray-400">งานที่สตูดิโอสร้างและดูแลเอง เปิดดูได้จริงทุกชิ้น</p>
            </div>
            <div class="mt-12 grid sm:grid-cols-2 gap-6 lg:gap-8">
                @foreach ($more as $p)
                    <a href="{{ $p['url'] }}" target="_blank" rel="noopener" class="pf-card group block rounded-2xl overflow-hidden" style="--a: {{ $p['accent'] }}">
                        <div class="pf-bar">
                            <i></i><i></i><i></i>
                            <span class="pf-url pf-mono">{{ $p['domain'] }}</span>
                            <span class="pf-live pf-mono">LIVE</span>
                        </div>
                        <div class="aspect-[16/10] overflow-hidden bg-black">
                            <img src="{{ $img($p['image']) }}" width="1280" height="800" loading="lazy" decoding="async"
                                 alt="{{ $p['name'] }} (ภาพหน้าจอจริง)" class="pf-card-img w-full h-full object-cover object-top">
                        </div>
                        <div class="p-6">
                            <span class="pf-chip pf-tag"><x-bi :th="$p['type_th']" :en="$p['type_en']" /></span>
                            <h3 class="mt-3 text-2xl font-bold text-white">{{ $p['name'] }}</h3>
                            <p class="mt-2 text-gray-300">{{ $p['summary_th'] }}</p>
                            <p class="mt-1 text-sm text-gray-500">{{ $p['summary_en'] }}</p>
                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                @foreach ($p['stack'] as $t)
                                    <span class="pf-chip pf-mono text-[11px]">{{ $t }}</span>
                                @endforeach
                                <span class="ml-auto pf-accent font-semibold text-sm whitespace-nowrap"><x-bi th="เปิดเว็บ" en="Open" /> ↗</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ================================================================ AI video on YouTube --}}
    <section class="relative py-14 border-t border-white/5">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-red-500/25 bg-gradient-to-r from-red-500/10 via-white/[0.02] to-transparent p-6 md:p-8 flex flex-col md:flex-row items-center gap-6">
                <div class="flex-none w-16 h-16 rounded-2xl bg-red-600 grid place-items-center shadow-lg shadow-red-600/30">
                    <svg class="w-9 h-9 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                </div>
                <div class="flex-1 text-center md:text-left">
                    <h2 class="text-xl md:text-2xl font-bold"><x-bi th="ผลงาน AI Video และ AI Music" en="AI video & music" /></h2>
                    <p class="mt-1 text-gray-400">ติดตามผลงานวิดีโอและเพลงที่สร้างด้วย AI ของเราได้ที่ช่อง Metal-X Project</p>
                </div>
                <a href="https://youtube.com/@metal-xproject" target="_blank" rel="noopener" class="pf-btn bg-red-600 hover:bg-red-700 text-white" style="--a: #dc2626">
                    <x-bi th="ดูบน YouTube" en="Watch" /> ↗
                </a>
            </div>
        </div>
    </section>

    {{-- ================================================================ CTA --}}
    <section class="relative py-20 md:py-24 overflow-hidden">
        <div class="pf-glow w-[600px] h-[400px] left-1/2 -translate-x-1/2 top-0 bg-violet-600/25"></div>
        <div class="relative max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-5xl font-extrabold tracking-tight">งานชิ้นต่อไป <span class="bg-gradient-to-r from-cyan-300 to-violet-300 bg-clip-text text-transparent">อาจเป็นของคุณ</span></h2>
            <p class="mt-2 text-gray-400">Your project could be the next one on this page.</p>
            <p class="mt-6 text-lg text-gray-300">เว็บไซต์ ระบบหลังบ้าน แอป บล็อกเชน หรือ AI — เล่าโจทย์ให้เราฟัง แล้วรับใบเสนอราคาฟรี</p>
            <div class="mt-10 flex flex-wrap justify-center gap-4" style="--a: #67e8f9">
                <a href="{{ route('quote.index') }}" class="pf-btn pf-btn-main"><x-bi th="ขอใบเสนอราคา" en="Get a quote" /> →</a>
                <a href="{{ route('contact.show') }}" class="pf-btn pf-btn-ghost"><x-bi th="คุยกับทีม" en="Talk to us" /></a>
            </div>
        </div>
    </section>
</div>
@endsection
