{{--
    "3D Universe" pill on the theme's home page — the way back into the 3D home
    for a visitor who chose the classic page, or whose browser was sent here by
    partials/universe/detect. Shown only while the universe is switched on, and
    only when this browser has WebGL2 at all; pressing it records the choice in
    the xu_mode cookie, which also lifts the soft checks (reduced motion, memory)
    on the next visit — the visitor asked for it.

    $aboveBottomNav: the classic/premium layouts have a 64px bottom nav on
    phones; the pill sits above it there.
--}}
@if(\App\Support\UniverseHome::enabled())
<a href="{{ url('/') }}" id="xu-switch" class="xu-switch{{ ($aboveBottomNav ?? false) ? ' xu-switch--nav' : '' }}" hidden>
    <span class="xu-switch__orb" aria-hidden="true"></span>
    <span class="xu-switch__text">เข้าสู่จักรวาล 3D <small>3D Universe</small></span>
</a>
<style>
    .xu-switch {
        position: fixed;
        left: 18px;
        bottom: 18px;
        z-index: 9989;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 9px 16px 9px 10px;
        border-radius: 999px;
        border: 1px solid rgba(160, 190, 255, .35);
        background: linear-gradient(120deg, rgba(8, 11, 28, .92), rgba(34, 16, 58, .92));
        box-shadow: 0 12px 34px rgba(0, 0, 0, .45), 0 0 26px -8px rgba(139, 92, 246, .9);
        color: #eaf0ff;
        font: 600 13px/1.2 'Noto Sans Thai', 'Inter', system-ui, sans-serif;
        text-decoration: none;
        transition: transform .3s cubic-bezier(.22, 1, .36, 1), box-shadow .3s ease;
    }
    .xu-switch[hidden] { display: none; }
    .xu-switch small { display: block; font-size: 10px; font-weight: 500; letter-spacing: .08em; color: #a8b4d4; }
    .xu-switch:hover, .xu-switch:focus-visible {
        transform: translateY(-2px);
        box-shadow: 0 16px 40px rgba(0, 0, 0, .5), 0 0 34px -6px rgba(34, 211, 238, .95);
    }
    .xu-switch__orb {
        position: relative;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: radial-gradient(circle at 40% 35%, #fff7de, #ffd479 38%, #b8641c 75%);
        box-shadow: 0 0 14px 2px rgba(255, 212, 121, .75);
    }
    .xu-switch__orb::after {
        content: "";
        position: absolute;
        inset: -5px -9px;
        border-radius: 50%;
        border: 1px solid rgba(34, 211, 238, .8);
        transform: rotate(-24deg);
        animation: xu-switch-spin 6s linear infinite;
    }
    @keyframes xu-switch-spin { to { transform: rotate(336deg); } }
    @media (max-width: 767px) {
        .xu-switch--nav { bottom: calc(78px + env(safe-area-inset-bottom, 0px)); }
    }
    @media (prefers-reduced-motion: reduce) {
        .xu-switch__orb::after { animation: none; }
    }
</style>
<script>
(function () {
    var el = document.getElementById('xu-switch');
    if (!el) return;
    // Probing WebGL costs a moment on the weak devices this page mostly
    // serves, so it waits until the page is idle.
    var idle = window.requestIdleCallback
        ? window.requestIdleCallback.bind(window)
        : function (fn) { return setTimeout(fn, 400); };
    idle(function () {
        try {
            if (!('noModule' in document.createElement('script'))) return;
            var gl = document.createElement('canvas').getContext('webgl2');
            if (!gl) return;
            var lose = gl.getExtension('WEBGL_lose_context');
            if (lose) lose.loseContext();
        } catch (e) {
            return;
        }
        // Out of <main>: the layouts give <main> and the footer their own
        // stacking contexts, and the footer would paint over the pill.
        document.body.appendChild(el);
        el.hidden = false;
    }, { timeout: 2500 });
    el.addEventListener('click', function (e) {
        e.preventDefault();
        document.cookie = 'xu_mode=universe; path=/; max-age=' + (365 * 86400) + '; SameSite=Lax'
            + (location.protocol === 'https:' ? '; Secure' : '');
        location.href = el.href;
    });
})();
</script>
@endif
