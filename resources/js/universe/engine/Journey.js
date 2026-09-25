import { CatmullRomCurve3 } from 'three';
import { clamp, damp, easeInOutCubic, lerp, smoothstep } from '../lib/math.js';
import { DWELL, buildKeyframes } from './Path.js';

/**
 * Scroll → flight.
 *
 * The page scrolls natively (wheel, trackpad, touch, keyboard and scrollbar
 * all keep working); every section is a spacer whose height sets how long
 * the flight lingers at that stop. Each frame the real scroll position is
 * eased into a smooth one, and that smooth position is what the camera, the
 * world and the overlays follow — so a mouse wheel's steps become a glide.
 */
export class Journey {
    constructor(sections) {
        this.stations = sections.map((el, i) => ({
            el,
            i,
            type: el.dataset.station,
            len: parseFloat(el.dataset.len) || 1,
            th: el.dataset.labelTh || '',
            en: el.dataset.labelEn || '',
            count: el.querySelectorAll('[data-xu-planet]').length,
            top: 0,
            height: 1,
            p: 0,
        }));
        this.n = this.stations.length;

        // Room after the last stop, so its progress can reach 1.
        this.tail = document.createElement('div');
        this.tail.className = 'xu-tail';
        this.tail.setAttribute('aria-hidden', 'true');
        sections[sections.length - 1].after(this.tail);

        this.scroll = window.scrollY;
        this.target = this.scroll;
        this.velocity = 0; // px/s of the smooth scroll
        this.u = 0;
        this.jump = null;
        this.frozen = false;

        this.keys = [];
        this.posCurve = null;
        this.lookCurve = null;

        const cancel = () => {
            if (this.jump && !this.jump.locked) this.jump = null;
        };
        window.addEventListener('wheel', cancel, { passive: true });
        window.addEventListener('touchstart', cancel, { passive: true });
        window.addEventListener('keydown', (e) => {
            if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '].includes(e.key)) cancel();
        });
    }

    /** Size the spacers for this screen and rebuild the flight plan. */
    layout({ aspect, portrait }) {
        const vh = window.innerHeight;
        for (const st of this.stations) {
            st.height = Math.max(1, Math.round(st.len * vh));
            st.el.style.setProperty('--xu-len-px', `${st.height}px`);
        }
        this.tail.style.height = `${vh}px`;

        const scrollY = window.scrollY;
        for (const st of this.stations) {
            st.top = st.el.getBoundingClientRect().top + scrollY;
        }
        const last = this.stations[this.n - 1];
        this.end = last.top + last.height;

        this.keys = buildKeyframes(this.stations, { aspect, portrait });
        this.posCurve = new CatmullRomCurve3(this.keys.map((k) => k.pos), false, 'centripetal');
        this.lookCurve = new CatmullRomCurve3(this.keys.map((k) => k.look), false, 'centripetal');
    }

    /** Journey units (stop index + progress) at a scroll offset. */
    uAt(scroll) {
        const first = this.stations[0];
        if (scroll <= first.top) return 0;
        for (const st of this.stations) {
            if (scroll < st.top + st.height) return st.i + (scroll - st.top) / st.height;
        }
        return this.n;
    }

    /** Scroll offset of a point in journey units. */
    scrollAt(u) {
        const i = clamp(Math.floor(u), 0, this.n - 1);
        const st = this.stations[i];
        return st.top + st.height * clamp(u - i, 0, 1);
    }

    update(dt) {
        if (this.jump) this.stepJump(dt);

        this.target = window.scrollY;
        const before = this.scroll;
        this.scroll = damp(this.scroll, this.target, this.jump ? 16 : 6.2, dt);
        if (Math.abs(this.scroll - this.target) < 0.15) this.scroll = this.target;

        const v = (this.scroll - before) / Math.max(dt, 1e-4);
        this.velocity = damp(this.velocity, v, 8, dt);
        this.u = this.uAt(this.scroll);

        for (const st of this.stations) st.p = (this.scroll - st.top) / st.height;
    }

    /** Camera pose at u, written into pos/look. Returns the field of view. */
    pose(u, pos, look) {
        const k = this.keys;
        const last = k.length - 1;
        let j = 0;
        let f = 0;
        if (u <= k[0].u) {
            j = 0;
        } else if (u >= k[last].u) {
            j = last;
        } else {
            while (j < last && u >= k[j + 1].u) j++;
            f = (u - k[j].u) / (k[j + 1].u - k[j].u);
        }
        const t = last > 0 ? clamp((j + f) / last) : 0;
        this.posCurve.getPoint(t, pos);
        this.lookCurve.getPoint(t, look);
        const next = k[Math.min(j + 1, last)];
        return lerp(k[j].fov, next.fov, smoothstep(0, 1, f));
    }

    /** The index of the stop the visitor is at (the one whose content shows). */
    get current() {
        return clamp(Math.floor(this.u + 0.08), 0, this.n - 1);
    }

    /** Fly to a stop: scrolls there over a time that grows with the distance. */
    jumpTo(index, p = null, { locked = false } = {}) {
        const st = this.stations[clamp(index, 0, this.n - 1)];
        const at = p ?? DWELL[st.type] ?? 0.3;
        const to = Math.round(st.top + st.height * at);
        const from = window.scrollY;
        const screens = Math.abs(to - from) / window.innerHeight;
        if (screens < 0.02) return null;
        this.jump = {
            from,
            to,
            t: 0,
            duration: clamp(0.85 + screens * 0.1, 0.9, 2.8),
            screens,
            locked,
        };
        return this.jump;
    }

    stepJump(dt) {
        const j = this.jump;
        j.t = Math.min(1, j.t + dt / j.duration);
        window.scrollTo(0, lerp(j.from, j.to, easeInOutCubic(j.t)));
        if (j.t >= 1) this.jump = null;
    }

    /** 0 → 1 → 0 over a long jump: how hard to throw the hyperspace effects. */
    get jumpWarp() {
        const j = this.jump;
        if (!j || j.screens < 2) return 0;
        return Math.sin(Math.PI * j.t) * clamp((j.screens - 2) / 6, 0.35, 1);
    }
}
