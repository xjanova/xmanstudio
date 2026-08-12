{{--
    Reveal-on-scroll for marketing pages. Add `tm-reveal` to anything that should
    fade up as it enters the viewport, then @include this partial once near the
    end of the page's content section.

    The hidden state is scoped to `.tm-js`, which the script below adds only
    after it has parsed. A bare `.tm-reveal{opacity:0}` plus an
    IntersectionObserver is how the BrainX selling page once blanked itself when
    its script failed to run — content must never be hidden by CSS alone.
--}}
@once
<style>
    .tm-js .tm-reveal {
        opacity: 0;
        transform: translateY(22px);
        transition: opacity .7s cubic-bezier(.22, 1, .36, 1),
                    transform .7s cubic-bezier(.22, 1, .36, 1);
        will-change: opacity, transform;
    }
    .tm-js .tm-reveal.is-in { opacity: 1; transform: none; }

    @media (prefers-reduced-motion: reduce) {
        .tm-js .tm-reveal {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
        }
    }
</style>

<script>
(function () {
    var els = document.querySelectorAll('.tm-reveal');
    if (!els.length) return;

    function showAll() {
        for (var i = 0; i < els.length; i++) els[i].classList.add('is-in');
    }

    // Opt in to the hidden state only now that this script is definitely running.
    document.documentElement.classList.add('tm-js');

    if (!('IntersectionObserver' in window)) { showAll(); return; }

    var io = new IntersectionObserver(function (entries) {
        for (var i = 0; i < entries.length; i++) {
            if (entries[i].isIntersecting) {
                entries[i].target.classList.add('is-in');
                io.unobserve(entries[i].target);
            }
        }
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });

    for (var i = 0; i < els.length; i++) io.observe(els[i]);

    // Backstop: if the observer never fires (bfcache restore, odd viewport,
    // zero-height container) the page must not stay blank.
    setTimeout(showAll, 2500);
})();
</script>

<script>
(function () {
    // Count-up for [data-count] once its tile scrolls in. Falls back to simply
    // printing the final number if IntersectionObserver is unavailable, so the
    // figure is never missing.
    var nums = document.querySelectorAll('[data-count]');
    if (!nums.length) return;

    function run(el) {
        var target = parseFloat(el.getAttribute('data-count')) || 0;
        var suffix = el.getAttribute('data-count-suffix') || '';
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) { el.textContent = target + suffix; return; }
        var start = null, dur = 1400;
        function tick(ts) {
            if (start === null) start = ts;
            var p = Math.min((ts - start) / dur, 1);
            // ease-out so it settles rather than stopping dead
            el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))) + suffix;
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    }

    if (!('IntersectionObserver' in window)) {
        for (var i = 0; i < nums.length; i++) {
            nums[i].textContent = nums[i].getAttribute('data-count') + (nums[i].getAttribute('data-count-suffix') || '');
        }
        return;
    }

    var io = new IntersectionObserver(function (entries) {
        for (var i = 0; i < entries.length; i++) {
            if (entries[i].isIntersecting) { run(entries[i].target); io.unobserve(entries[i].target); }
        }
    }, { threshold: 0.4 });

    for (var i = 0; i < nums.length; i++) io.observe(nums[i]);
})();
</script>
@endonce
