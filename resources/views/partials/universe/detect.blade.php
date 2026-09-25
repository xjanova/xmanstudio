{{--
    XMAN Universe — the browser check.

    The first thing in <head>, ahead of every stylesheet and script of the
    3D page. A browser that cannot run it is sent straight to the theme's own
    home page (?view=classic) before anything paints, and a cookie remembers
    that for a week so the next visit is served the classic page directly
    (App\Support\UniverseHome reads it).

    Kept to ES5 on purpose: it has to run, and fail safely, on exactly the old
    browsers it is screening out.

    "Cannot run it" means: no ES modules; no WebGL2, or WebGL2 only through a
    software rasteriser (failIfMajorPerformanceCaveat, SwiftShader, llvmpipe);
    prefers-reduced-motion (the page IS motion); Save-Data or a 2G link; under
    4 GB of memory or under 4 CPU cores (Safari never reports fewer than 4, so
    that one only screens out old Android and dual-core laptops). A visitor who
    pressed the 3D switch on the classic page (xu_mode=universe) skips
    everything but the hard WebGL2 requirement.

    window.__xuFallback is also how main.js gives up at runtime (a crash, a
    lost GPU, too few frames per second). The watchdog covers the case where
    the module never arrives at all.
--}}
<script>
(function () {
    'use strict';
    var d = document, w = window, n = navigator, root = d.documentElement;
    var CLASSIC = @json($classicUrl);

    function cookie(name) {
        var m = d.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : null;
    }

    function remember(value, days) {
        d.cookie = 'xu_mode=' + value + '; path=/; max-age=' + (days * 86400) + '; SameSite=Lax'
            + (location.protocol === 'https:' ? '; Secure' : '');
    }

    var forced = cookie('xu_mode') === 'universe';

    w.__xuFallback = function (reason, transient) {
        if (w.__xuLeaving) return;
        w.__xuLeaving = true;
        // A network hiccup should not cost the visitor the 3D page for a week.
        // A real failure does, even for one who asked for 3D: otherwise their
        // device would load, stutter and bounce on every visit for a year. The
        // pill on the classic page still takes them back in.
        if (!transient) remember('lite', 7);
        root.className += ' xu-leaving';
        // Carry the rest of the query string (an affiliate ?ref=, say) along.
        var extra = location.search.replace(/^\?/, '').split('&').filter(function (p) {
            return p && !/^(view|xu)=/.test(p);
        }).join('&');
        location.replace(CLASSIC + (extra ? '&' + extra : '') + (reason ? '&xu=' + encodeURIComponent(reason) : ''));
    };

    var why = '';
    try {
        var mq = function (q) { return !!(w.matchMedia && w.matchMedia(q).matches); };
        var link = n.connection || {};
        if (!('noModule' in d.createElement('script')) || !w.Promise || !w.requestAnimationFrame) {
            why = 'browser';
        } else if (!forced && mq('(prefers-reduced-motion: reduce)')) {
            why = 'motion';
        } else if (!forced && (link.saveData || /(^|-)2g$/.test(link.effectiveType || ''))) {
            why = 'data';
        } else if (!forced && n.deviceMemory && n.deviceMemory < 4) {
            why = 'memory';
        } else if (!forced && n.hardwareConcurrency && n.hardwareConcurrency < 4) {
            why = 'cpu';
        } else {
            var gl = d.createElement('canvas').getContext('webgl2', {
                failIfMajorPerformanceCaveat: !forced,
                powerPreference: 'high-performance'
            });
            if (!gl) {
                why = 'webgl';
            } else {
                var info = gl.getExtension('WEBGL_debug_renderer_info');
                var gpu = String(gl.getParameter(info ? info.UNMASKED_RENDERER_WEBGL : gl.RENDERER) || '');
                if (!forced && /swiftshader|llvmpipe|softpipe|software|basic render/i.test(gpu)) {
                    why = 'software';
                } else if (gl.getParameter(gl.MAX_TEXTURE_SIZE) < 4096) {
                    why = 'gpu';
                }
                w.__xuGpu = gpu;
                // Hand the context back: browsers cap how many a page may hold.
                var lose = gl.getExtension('WEBGL_lose_context');
                if (lose) lose.loseContext();
            }
        }
    } catch (e) {
        why = 'error';
    }

    if (why) {
        w.__xuFallback(why);
        return;
    }

    root.className += ' xu-js';
    w.__xuForced = forced;
    w.__xuWatchdog = w.setTimeout(function () {
        if (!w.__xuStarted) w.__xuFallback('timeout', true);
    }, 15000);
})();
</script>
