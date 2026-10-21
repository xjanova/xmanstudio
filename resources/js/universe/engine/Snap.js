import { clamp } from '../lib/math.js';
import { DWELL } from './Path.js';
import { deckBeats, originBeats, platformBeats, railBeats } from './choreo.js';

/**
 * One flick, one stop.
 *
 * A wheel notch, a trackpad flick, a swipe or an arrow key flies the camera
 * to the next stop (or back to the one before) and parks it where that stop's
 * content is fully shown. Visitors who had to scroll and scroll through empty
 * space to reach the next thing took the page for finished and left.
 *
 * The stops are the moments the choreography builds to (choreo.js): each of
 * the origin's numbers and then why-us, each platform planet, each page of the
 * product rail, each review card, and one per stop everywhere else.
 *
 * The page still scrolls natively underneath: the scrollbar, find-in-page and
 * Tab focus move it, and wherever they leave it, it settles on the nearest stop.
 */

// Flight time for one flick: [base s, s per screen, max s] (Journey.jumpTo).
const PACE = [0.6, 0.16, 1.5];

// A wheel event this long after the last one starts a new gesture (ms).
const GESTURE_GAP = 200;

/**
 * Would this element scroll itself with a wheel/swipe in direction dy (0 = either)?
 * The chat log and a long textarea do; a one-line field does not.
 */
function scrollsItself(el, dy) {
    for (let n = el; n && n.nodeType === 1 && n !== document.body && n !== document.documentElement; n = n.parentElement) {
        if (n.scrollHeight <= n.clientHeight + 1) continue;
        const oy = getComputedStyle(n).overflowY;
        if (oy !== 'auto' && oy !== 'scroll') continue;
        if (dy === 0) return true;
        if (dy > 0 ? n.scrollTop + n.clientHeight < n.scrollHeight - 1 : n.scrollTop > 0) return true;
    }
    return false;
}

export class Snap {
    /**
     * @param {object} o
     * @param {import('./Journey.js').Journey} o.journey
     * @param {(index:number, p:number, opts:object) => object|null} o.go starts a flight (main.js)
     * @param {() => boolean} o.enabled false during the intro, the menu and a launch
     */
    constructor({ journey, go, enabled }) {
        this.journey = journey;
        this.go = go;
        this.enabled = enabled;
        this.stops = [];
        this.target = -1; // the stop a flight of ours is heading for
        this.wheel = { t: 0, abs: 0, dir: 0, acc: 0, fired: false };
        this.touch = null;
        this.settleTimer = 0;

        window.addEventListener('wheel', (e) => this.onWheel(e), { passive: false });
        window.addEventListener('touchstart', (e) => this.onTouchStart(e), { passive: true });
        window.addEventListener('touchmove', (e) => this.onTouchMove(e), { passive: false });
        window.addEventListener('touchend', (e) => this.onTouchEnd(e), { passive: true });
        window.addEventListener('touchcancel', () => this.onTouchCancel(), { passive: true });
        window.addEventListener('keydown', (e) => this.onKey(e));
        window.addEventListener('scroll', () => this.onScroll(), { passive: true });
    }

    /** Recompute the stops (after Journey.layout, and before each flick: the product rail's width can change). */
    layout() {
        const j = this.journey;
        const vw = window.innerWidth;
        const stops = [];
        for (const st of j.stations) {
            let beats;
            switch (st.type) {
                case 'origin':
                    beats = originBeats();
                    break;
                case 'platforms':
                    beats = platformBeats(st.count);
                    break;
                case 'reviews':
                    beats = deckBeats(st.el.querySelectorAll('.xu-review').length);
                    break;
                case 'products': {
                    // The rail is display:none until the flight gets near it: estimate it
                    // from the cards then (universe.css: clamp(250px, 24vw, 320px), 18px gaps).
                    const rail = st.el.querySelector('.xu-track__rail');
                    const n = st.el.querySelectorAll('.xu-prod').length;
                    const railW = rail?.scrollWidth || n * (clamp(vw * 0.24, 250, 320) + 18) + 48;
                    const travel = Math.max(0, railW - vw);
                    beats = railBeats(travel > 0 ? 1 + Math.ceil(travel / (vw * 0.6)) : 1);
                    break;
                }
                default:
                    beats = [DWELL[st.type] ?? 0.3];
            }
            for (const p of beats) stops.push({ i: st.i, p, y: j.scrollAt(st.i + p) });
        }
        this.stops = stops;
    }

    /** The stop nearest a scroll offset. */
    nearest(y) {
        let best = 0;
        let bd = Infinity;
        this.stops.forEach((s, k) => {
            const d = Math.abs(s.y - y);
            if (d < bd) {
                bd = d;
                best = k;
            }
        });
        return best;
    }

    /** Fly to stop k. */
    fly(k) {
        const s = this.stops[clamp(k, 0, this.stops.length - 1)];
        if (!s) return;
        const jump = this.go(s.i, s.p, { locked: true, pace: PACE });
        this.target = jump ? this.stops.indexOf(s) : -1;
    }

    /** The first stop of stop-station i (the HUD rail, the menu and in-page links land there). */
    toStation(i) {
        this.layout();
        const k = this.stops.findIndex((s) => s.i === i);
        if (k >= 0) this.fly(k);
    }

    /** One stop forward (dir 1) or back (-1), counted from scroll offset `y`. */
    step(dir, y = window.scrollY) {
        this.layout();
        const last = this.stops.length - 1;
        if (last < 0) return;
        let to;
        if (this.journey.jump && this.target >= 0) {
            // Another flick while flying: carry on past the stop we were heading for.
            to = this.target + dir;
        } else {
            const k = this.nearest(y);
            if (Math.abs(this.stops[k].y - y) < window.innerHeight * 0.03) {
                to = k + dir;
            } else {
                // Between two stops: the next one in that direction.
                to = dir > 0 ? last : 0;
                for (let s = 0; s <= last; s++) {
                    if (dir > 0 && this.stops[s].y > y) {
                        to = s;
                        break;
                    }
                    if (dir < 0 && this.stops[s].y < y) to = s;
                }
            }
        }
        this.fly(clamp(to, 0, last));
    }

    // ---- input ------------------------------------------------------------

    onWheel(e) {
        if (!this.enabled() || e.ctrlKey || e.defaultPrevented) return;
        const unit = e.deltaMode === 1 ? 40 : e.deltaMode === 2 ? window.innerHeight : 1;
        const dy = e.deltaY * unit;
        if (Math.abs(dy) < Math.abs(e.deltaX * unit)) return;
        if (scrollsItself(e.target, dy)) return;
        e.preventDefault();

        // One gesture, one step. A trackpad flick keeps sending ever smaller
        // deltas for a second after the finger lifts; a new gesture shows up as
        // a pause, a sudden bigger delta, or a change of direction. A mouse
        // notch after the flight has landed counts as new too.
        const w = this.wheel;
        const now = performance.now();
        const abs = Math.abs(dy);
        const dir = Math.sign(dy);
        const gap = now - w.t;
        const fresh =
            gap > GESTURE_GAP ||
            dir !== w.dir ||
            abs > w.abs * 1.8 + 6 ||
            (abs >= 50 && gap > 90 && !this.journey.jump);
        w.t = now;
        w.abs = abs;
        w.dir = dir;
        if (fresh) {
            w.acc = 0;
            w.fired = false;
        }
        if (w.fired) return;
        w.acc += dy;
        if (Math.abs(w.acc) >= 24) {
            w.fired = true;
            this.step(Math.sign(w.acc));
        }
    }

    onTouchStart(e) {
        if (e.touches.length !== 1 || !this.enabled()) {
            this.touch = null;
            return;
        }
        const t = e.touches[0];
        this.touch = {
            x: t.clientX,
            y: t.clientY,
            t: performance.now(),
            scroll: window.scrollY,
            native: scrollsItself(e.target, 0),
            axis: null,
        };
    }

    onTouchMove(e) {
        const tc = this.touch;
        if (!tc || tc.native) return;
        if (e.touches.length !== 1) {
            this.touch = null;
            return;
        }
        const t = e.touches[0];
        const dx = t.clientX - tc.x;
        const dy = tc.y - t.clientY;
        if (!tc.axis && Math.hypot(dx, dy) > 8) tc.axis = Math.abs(dy) > Math.abs(dx) ? 'y' : 'x';
        if (tc.axis !== 'y') return;
        if (e.cancelable) e.preventDefault();
        // Follow the finger a little, so the swipe feels held; the release decides.
        if (!this.journey.jump) window.scrollTo(0, tc.scroll + dy * 0.35);
    }

    onTouchEnd(e) {
        const tc = this.touch;
        this.touch = null;
        if (!tc || tc.native || tc.axis !== 'y') return;
        const t = e.changedTouches[0];
        const dy = tc.y - t.clientY;
        const speed = Math.abs(dy) / Math.max(1, performance.now() - tc.t); // px/ms
        if (Math.abs(dy) > 40 || (Math.abs(dy) > 14 && speed > 0.35)) {
            // Count from where the swipe started, not from where the finger pulled it.
            this.step(Math.sign(dy), tc.scroll);
        } else {
            this.settle();
        }
    }

    onTouchCancel() {
        const tc = this.touch;
        this.touch = null;
        if (tc && !tc.native && tc.axis === 'y') this.settle();
    }

    onKey(e) {
        if (!this.enabled() || e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey) return;
        const el = e.target;
        if (el?.closest?.('input, textarea, select, [contenteditable="true"], [role="dialog"]')) return;
        let dir = 0;
        switch (e.key) {
            case 'ArrowDown':
            case 'PageDown':
                dir = 1;
                break;
            case 'ArrowUp':
            case 'PageUp':
                dir = -1;
                break;
            case ' ':
                // Space on a button presses it.
                if (el?.closest?.('button, summary, [role="button"]')) return;
                dir = e.shiftKey ? -1 : 1;
                break;
            case 'Home':
                e.preventDefault();
                this.layout();
                this.fly(0);
                return;
            case 'End':
                e.preventDefault();
                this.layout();
                this.fly(this.stops.length - 1);
                return;
            default:
                return;
        }
        e.preventDefault();
        // A held key moves one stop per flight.
        if (e.repeat && this.journey.jump) return;
        this.step(dir);
    }

    onScroll() {
        clearTimeout(this.settleTimer);
        this.settleTimer = setTimeout(() => this.settle(), 420);
    }

    /** Left between stops (scrollbar, Tab, find-in-page, a short swipe): glide to the nearest one. */
    settle() {
        if (!this.enabled() || this.journey.jump || this.touch || !this.stops.length) return;
        if (performance.now() - this.wheel.t < 400) return;
        this.layout();
        const k = this.nearest(window.scrollY);
        if (Math.abs(this.stops[k].y - window.scrollY) > 3) this.fly(k);
    }
}
