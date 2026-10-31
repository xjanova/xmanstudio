{{--
    The product page frame every app page shares (the WinXTools 1.1 look): a near-black stage,
    AI key art screen-blended into it, glowing app windows, free/PRO badges.
    Each product brings its own colours — a/b = the main button and "free" gradient, c = eyebrows
    and links, glow = the bloom under app windows (an "r g b" triplet), g1–g3 = the headline glow.
    PRO stays amber/orange on every product so a buyer reads it the same way everywhere.
--}}
@props([
    'a' => '#b9ff3a',
    'b' => '#43e08a',
    'c' => '#22d3ee',
    'glow' => '34 211 238',
    'g1' => null,
    'g2' => null,
    'g3' => null,
])
@pushOnce('styles')
<style>
    .xp { background: #04060d; color: #d7dce5; --xp-amber: #ffcf3a; --xp-orange: #ff8a1f; }
    .xp h1, .xp h2, .xp h3 { text-wrap: balance; }
    .xp-grid { background-image: linear-gradient(rgba(148, 163, 184, .07) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, .07) 1px, transparent 1px);
        background-size: 56px 56px; -webkit-mask-image: radial-gradient(ellipse 75% 65% at 50% 35%, #000 25%, transparent 75%); mask-image: radial-gradient(ellipse 75% 65% at 50% 35%, #000 25%, transparent 75%); }
    .xp-glow { position: absolute; border-radius: 9999px; filter: blur(90px); pointer-events: none; }
    /* the AI art is painted on black: screen-blending adds its light to the page instead of sitting in a box,
       and the elliptical mask dissolves every edge */
    .xp-art { mix-blend-mode: screen; pointer-events: none; user-select: none; max-width: none;
        -webkit-mask-image: radial-gradient(ellipse 58% 60% at 56% 50%, #000 42%, transparent 100%); mask-image: radial-gradient(ellipse 58% 60% at 56% 50%, #000 42%, transparent 100%); }
    .xp-text-glow { background: linear-gradient(92deg, var(--xp-g1) 0%, var(--xp-g2) 40%, var(--xp-g3) 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
    .xp-badge { display: inline-flex; align-items: center; gap: .3rem; font-weight: 800; font-size: .72rem; letter-spacing: .06em; line-height: 1; padding: .34rem .62rem .38rem; border-radius: .5rem; white-space: nowrap; }
    .xp-free { background: linear-gradient(105deg, var(--xp-a), var(--xp-b)); color: #06110a; }
    .xp-pro { background: linear-gradient(105deg, var(--xp-amber), var(--xp-orange)); color: #1a1004; }
    .xp-soon { background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .18); color: #e6ebf3; }
    .xp-chip { display: inline-flex; align-items: center; gap: .45rem; padding: .45rem .85rem; border-radius: 9999px; font-size: .85rem; color: #e6ebf3;
        background: rgba(255, 255, 255, .04); border: 1px solid rgba(255, 255, 255, .09); backdrop-filter: blur(6px); }
    .xp-chip i { width: .45rem; height: .45rem; border-radius: 9999px; background: var(--xp-a); box-shadow: 0 0 10px var(--xp-a); }
    /* the app's own window: a glowing hairline frame, rounded inner corners, a soft bloom underneath */
    .xp-window { position: relative; isolation: isolate; border-radius: 1.15rem; padding: 1px;
        background: linear-gradient(140deg, rgb(var(--xp-glow) / .65), rgba(139, 92, 246, .22) 42%, color-mix(in srgb, var(--xp-a) 50%, transparent));
        box-shadow: 0 40px 90px -30px rgb(var(--xp-glow) / .35), 0 20px 50px -20px rgba(0, 0, 0, .8); }
    .xp-window > div { border-radius: calc(1.15rem - 1px); overflow: hidden; background: #060a16; position: relative; }
    .xp-window img { display: block; width: 100%; height: auto; }
    .xp-window::after { content: ""; position: absolute; left: 8%; right: 8%; bottom: -34px; height: 46px; border-radius: 50%;
        background: radial-gradient(ellipse 50% 50% at 50% 50%, rgb(var(--xp-glow) / .28), transparent 70%); filter: blur(12px); z-index: -1; }
    .xp-phone { border-radius: 1.6rem; padding: 1px; background: linear-gradient(160deg, rgb(var(--xp-glow) / .6), rgba(255, 255, 255, .08) 50%, color-mix(in srgb, var(--xp-a) 45%, transparent));
        box-shadow: 0 30px 70px -30px rgb(var(--xp-glow) / .4); }
    .xp-phone > div { border-radius: calc(1.6rem - 1px); overflow: hidden; background: #060a16; position: relative; }
    .xp-phone img { display: block; width: 100%; height: auto; }
    /* a mock-up waiting for the real screen: said on the picture itself, never passed off as a capture */
    .xp-mock-tag { position: absolute; top: .7rem; right: .7rem; z-index: 2; font-size: .68rem; font-weight: 800; letter-spacing: .08em; padding: .3rem .55rem; border-radius: .45rem;
        color: #e6ebf3; background: rgba(4, 6, 13, .72); border: 1px solid rgba(255, 255, 255, .18); backdrop-filter: blur(6px); }
    .xp-tilt { transform: perspective(1800px) rotateY(-9deg) rotateX(3deg); transform-origin: 60% 50%; transition: transform .6s ease; }
    .xp-tilt:hover { transform: perspective(1800px) rotateY(-3deg) rotateX(1deg); }
    .xp-card { background: linear-gradient(180deg, rgba(255, 255, 255, .045), rgba(255, 255, 255, .015)); border: 1px solid rgba(255, 255, 255, .08); border-radius: 1.1rem; }
    .xp-card:hover { border-color: color-mix(in srgb, var(--xp-a) 28%, transparent); }
    .xp-eyebrow { font-size: .72rem; font-weight: 800; letter-spacing: .28em; text-transform: uppercase; color: color-mix(in srgb, var(--xp-c) 85%, transparent); }
    .xp-link { color: color-mix(in srgb, var(--xp-c) 90%, white); }
    .xp-link:hover { color: #fff; }
    .xp-dot { width: .5rem; height: .5rem; border-radius: 9999px; flex: none; background: var(--xp-c); box-shadow: 0 0 10px var(--xp-c); }
    .xp-tick { flex: none; width: 1.35rem; height: 1.35rem; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(140deg, var(--xp-a), var(--xp-b)); color: #06110a; font-size: .75rem; font-weight: 900; margin-top: .15rem; }
    .xp-tick-pro { background: linear-gradient(140deg, var(--xp-amber), var(--xp-orange)); color: #1a1004; }
    .xp-btn-main { background: linear-gradient(105deg, var(--xp-a), var(--xp-b)); color: #06110a; box-shadow: 0 10px 40px -10px color-mix(in srgb, var(--xp-a) 55%, transparent); }
    .xp-btn-main:hover { filter: brightness(1.06); transform: translateY(-1px); }
    .xp-btn-pro { background: linear-gradient(105deg, var(--xp-amber), var(--xp-orange)); color: #1a1004; box-shadow: 0 10px 40px -12px rgba(255, 160, 40, .55); }
    .xp-btn-pro:hover { filter: brightness(1.05); transform: translateY(-1px); }
    .xp-btn-ghost { background: rgba(255, 255, 255, .05); border: 1px solid rgba(255, 255, 255, .14); color: #fff; }
    .xp-btn-ghost:hover { background: rgba(255, 255, 255, .09); }
    .xp-btn-off { cursor: not-allowed; opacity: .8; }
    .xp-tab { text-align: left; border: 1px solid rgba(255, 255, 255, .08); background: rgba(7, 10, 20, .82); backdrop-filter: blur(10px); border-radius: .9rem; transition: background .2s, border-color .2s; }
    .xp-tab:hover { background: rgba(14, 19, 34, .9); }
    .xp-tab[aria-selected="true"] { background: linear-gradient(105deg, color-mix(in srgb, var(--xp-a) 12%, transparent), color-mix(in srgb, var(--xp-c) 6%, transparent)); border-color: color-mix(in srgb, var(--xp-a) 45%, transparent); }
    .xp-tab[aria-selected="true"] .n { color: #fff; }
    .xp-panel[hidden] { display: none; }
    .xp-panel > .xp-card { background: linear-gradient(180deg, rgba(10, 14, 26, .9), rgba(7, 10, 20, .86)); backdrop-filter: blur(12px); }
    .xp-table th, .xp-table td { border-bottom: 1px solid rgba(255, 255, 255, .06); }
    .xp-featured { position: relative; border-radius: 1.1rem; padding: 1px; background: linear-gradient(135deg, rgba(252, 211, 77, .8), rgba(249, 115, 22, .4), color-mix(in srgb, var(--xp-a) 60%, transparent));
        box-shadow: 0 30px 90px -30px rgba(255, 160, 40, .45); }
    .xp-featured > div { height: 100%; border-radius: 1.05rem; background: #0b0d14; }
    .xp-faq summary { list-style: none; cursor: pointer; }
    .xp-faq summary::-webkit-details-marker { display: none; }
    .xp-faq[open] summary .pm { transform: rotate(45deg); }
    .xp-faq summary .pm { transition: transform .2s ease; color: var(--xp-a); }
    .xp code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .8rem; color: #e6ebf3; background: rgba(255, 255, 255, .06); padding: .1rem .35rem; border-radius: .3rem; }
    @keyframes xp-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-9px); } }
    .xp-float { animation: xp-float 7s ease-in-out infinite; }
    .xp-float-2 { animation: xp-float 8s ease-in-out -3s infinite; }
    @media (prefers-reduced-motion: reduce) {
        .xp-float, .xp-float-2 { animation: none; }
        .xp-tilt, .xp-tilt:hover { transition: none; }
    }
    @media (max-width: 1023px) { .xp-tilt, .xp-tilt:hover { transform: none; } }
</style>
@endPushOnce
@pushOnce('scripts')
<script>
(function () {
    // tool explorers: every panel is visible without script; with it, one panel at a time
    document.querySelectorAll('[data-xp-explorer]').forEach(function (ex) {
        var tabs = Array.prototype.slice.call(ex.querySelectorAll('[data-tab]'));
        var panels = Array.prototype.slice.call(ex.querySelectorAll('[data-panel]'));
        function show(i, focus) {
            tabs.forEach(function (t, k) { t.setAttribute('aria-selected', k === i ? 'true' : 'false'); t.tabIndex = k === i ? 0 : -1; });
            panels.forEach(function (p, k) { p.hidden = k !== i; });
            if (focus) { tabs[i].focus(); }
        }
        tabs.forEach(function (t, i) {
            t.addEventListener('click', function () { show(i, false); });
            t.addEventListener('keydown', function (e) {
                var n = tabs.length, j = null;
                if (e.key === 'ArrowDown' || e.key === 'ArrowRight') { j = (i + 1) % n; }
                if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') { j = (i - 1 + n) % n; }
                if (e.key === 'Home') { j = 0; }
                if (e.key === 'End') { j = n - 1; }
                if (j !== null) { e.preventDefault(); show(j, true); }
            });
        });
        show(0, false);
    });
})();
</script>
@endPushOnce
<div {{ $attributes->merge(['class' => 'xp relative overflow-hidden']) }}
     style="--xp-a: {{ $a }}; --xp-b: {{ $b }}; --xp-c: {{ $c }}; --xp-glow: {{ $glow }}; --xp-g1: {{ $g1 ?? $a }}; --xp-g2: {{ $g2 ?? $b }}; --xp-g3: {{ $g3 ?? $c }};">
    {{ $slot }}
</div>
