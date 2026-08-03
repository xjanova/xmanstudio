{{-- Nova hero — the XMAN star is the primary navigation trigger.
     `data-nova-star` is what the layout script binds to; the same attribute on
     any other element would also open the menu. --}}
@php
    $heroLogo = \App\Models\Setting::getValue('site_logo');
@endphp

<section id="nova-hero" class="nova-hero">
    <div class="nova-shell nova-hero__inner">

        <span class="nova-eyebrow nova-reveal">
            <span class="nova-eyebrow__dot"></span>
            IT Solutions ครบวงจร · Since 2018
        </span>

        {{-- The star. A real <button> so it is keyboard reachable and
             announced correctly; the no-JS fallback nav above stays visible
             because .nova-star is display:none until html.nova-js exists. --}}
        <button type="button"
                class="nova-star"
                data-nova-star
                aria-controls="nova-nav"
                aria-expanded="false">
            <span class="nova-sr">เปิดเมนูหลัก / Open main menu</span>
            <span class="nova-star__halo" aria-hidden="true"></span>
            <span class="nova-star__ring" aria-hidden="true"></span>
            <span class="nova-star__ring" aria-hidden="true"></span>
            <span class="nova-star__core" aria-hidden="true">
                @if($heroLogo)
                    <img src="{{ asset('storage/' . $heroLogo) }}" alt="" class="nova-star__logo">
                @else
                    <span class="nova-star__word nova-grad-core">XMAN</span>
                @endif
            </span>
            <span class="nova-star__hint" aria-hidden="true">คลิกที่ดาว · Click the star</span>
        </button>

        <h1 class="nova-hero__title nova-reveal">
            <span class="nova-hero__th">สร้างสรรค์นวัตกรรมดิจิทัล</span>
            <span class="nova-hero__en nova-grad">Engineering the Digital Frontier</span>
        </h1>

        <p class="nova-hero__tags nova-reveal">
            <span style="color:#22d3ee;">Blockchain</span>
            <span style="color:#8b5cf6;">AI</span>
            <span style="color:#e879f9;">Web &amp; Mobile</span>
            <span style="color:#34d399;">IoT</span>
            <span style="color:#fb7185;">Music AI</span>
            <span style="color:#ffd479;">Security</span>
        </p>

        <div class="nova-hero__cta nova-reveal">
            <a href="{{ route('support.index') }}" class="nova-btn nova-btn--primary">
                เริ่มโปรเจคของคุณ / Start a project
                @include('partials.nova-icon', ['name' => 'arrow'])
            </a>
            <a href="#nova-services" class="nova-btn nova-btn--ghost">
                สำรวจบริการ / Explore services
            </a>
        </div>

        <div class="nova-hero__scroll" aria-hidden="true">
            <span>SCROLL</span>
            <span class="nova-hero__mouse"><span class="nova-hero__wheel"></span></span>
        </div>
    </div>
</section>

<style>
    .nova-hero {
        position: relative;
        min-height: 100svh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 60px 0 80px;
    }
    .nova-hero__inner {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 26px;
        width: 100%;
    }
    .nova-hero__title {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin: 14px 0 0;
    }
    .nova-hero__th {
        font-size: clamp(28px, 5.6vw, 58px);
        font-weight: 900;
        letter-spacing: -0.025em;
        line-height: 1.1;
        color: var(--nv-fg-1);
    }
    .nova-hero__en {
        font-size: clamp(15px, 2.4vw, 26px);
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    .nova-hero__tags {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px 20px;
        margin: 0;
        font-size: clamp(13px, 1.7vw, 17px);
        font-weight: 600;
    }
    .nova-hero__cta {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 14px;
    }
    .nova-hero__scroll {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        margin-top: 18px;
        font-size: 10px;
        letter-spacing: 0.3em;
        color: var(--nv-fg-3);
    }
    .nova-hero__mouse {
        display: block;
        width: 22px;
        height: 36px;
        border: 1px solid var(--nv-line-hi);
        border-radius: 12px;
        position: relative;
    }
    .nova-hero__wheel {
        position: absolute;
        left: 50%;
        top: 7px;
        width: 3px;
        height: 7px;
        margin-left: -1.5px;
        border-radius: 2px;
        background: var(--nv-cyan);
        animation: nova-wheel 1.8s var(--nv-ease-in-out) infinite;
    }
    @keyframes nova-wheel {
        0%   { transform: translateY(0);    opacity: 1; }
        70%  { transform: translateY(12px); opacity: 0; }
        100% { transform: translateY(12px); opacity: 0; }
    }
    /* Stagger the hero reveal. Delays are zeroed under reduced motion by the
       global rule in nova-theme.css.

       All four selectors carry the SAME specificity on purpose. An earlier
       `.nova-reveal:nth-of-type(1)` rule scored (0,4,0) and silently beat the
       per-element rules below it, collapsing the whole stagger to one delay —
       and because the hero's children are each a different element type
       (span / h1 / p / div), that :nth-of-type(1) matched all of them. */
    .nova-js .nova-hero .nova-eyebrow     { transition-delay: .05s; }
    .nova-js .nova-hero .nova-hero__title { transition-delay: .18s; }
    .nova-js .nova-hero .nova-hero__tags  { transition-delay: .26s; }
    .nova-js .nova-hero .nova-hero__cta   { transition-delay: .34s; }
    @media (prefers-reduced-motion: reduce) {
        .nova-hero__wheel { animation: none; }
    }
</style>
