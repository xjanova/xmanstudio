import { clamp, damp, easeInOutCubic, lerp } from '../lib/math.js';

/**
 * The guide — a gothic twin-tail girl who flies the universe with the visitor.
 *
 * She is HTML, not WebGL: one transparent illustration per pose, layered
 * under the content panels, so text always stays on top of her. Each stop
 * has a place for her (PLAN below): pose, side, size and what she says. When
 * the stop changes she switches to her flying pose, arcs across the screen to
 * the new place trailing sparkles, and lands with a little hop. In between
 * she floats — bobbing, swaying, breathing — flies alongside when the visitor
 * scrolls fast, and drifts with the pointer. Clicking her opens the menu.
 *
 * Dense stops (the service grid, the footer) and phones get her compact
 * form: a round portrait in the corner with the speech bubble beside it.
 */

// h: height as a share of the screen; side: the edge she keeps to; x: gap from
// that edge (share of width); y: gap from the bottom (share of height);
// mirror: face the other way. `m` overrides on phones ('compact' = portrait).
const PLAN = {
    core: { pose: 'welcome', side: 'right', h: 0.82, x: 0.03, y: 0, mirror: true, m: { side: 'right', h: 0.3, x: 0.02, y: 0.56, mirror: true } },
    origin: [
        { upTo: 0.74, pose: 'present', side: 'left', h: 0.6, x: 0.025, y: 0.02, m: 'compact' },
        { pose: 'moon', side: 'right', h: 0.34, x: 0.015, y: 0.5, m: 'compact' },
    ],
    services: { compact: true, pose: 'cheer' },
    products: { pose: 'moon', side: 'right', h: 0.3, x: 0.03, y: 0.54, m: 'compact' },
    platforms: { pose: 'present', side: 'planet', h: 0.5, x: 0.02, y: 0.02, m: 'compact' },
    stack: { pose: 'cheer', side: 'right', h: 0.44, x: 0.02, y: 0.01, m: 'compact' },
    reviews: { pose: 'moon', side: 'left', h: 0.36, x: 0.02, y: 0.04, m: 'compact' },
    launch: { pose: 'welcome', side: 'left', h: 0.42, x: 0.03, y: 0, m: 'compact' },
    // The footer leaves her a column on the right on wide screens (universe.css).
    control: { pose: 'bye', side: 'right', h: 0.62, x: 0.02, y: 0.02, m: 'compact', narrow: 'compact' },
};

const FLY_TIME = 1.05; // seconds for a hop between places

// Where her head is across each picture (0 = left edge), for the bubble's tail.
const HEAD_X = { welcome: 0.5, present: 0.52, moon: 0.57, cheer: 0.47, bye: 0.42, fly: 0.66 };

// Bottom edge of the HUD bar (universe.css --xu-hud-h, 68px; 60px on phones).
const HUD_BOTTOM = 68;

export class Guide {
    constructor({ root, journey, sound, onClick }) {
        this.root = root;
        this.journey = journey;
        this.sound = sound;
        this.body = root.querySelector('.xu-guide__body');
        this.hit = root.querySelector('.xu-guide__hit');
        this.bubble = root.querySelector('.xu-guide__bubble');
        this.bubbleTh = this.bubble.querySelector('b');
        this.bubbleEn = this.bubble.querySelector('small');
        this.avatar = root.querySelector('.xu-guide__avatar');
        this.trail = root.querySelector('.xu-guide__trail');
        this.lines = JSON.parse(document.getElementById('xu-guide-lines')?.textContent || '{}');

        this.poses = {};
        for (const img of root.querySelectorAll('[data-pose]')) {
            this.poses[img.dataset.pose] = img;
            img.addEventListener('load', () => this.measure(img));
            // A missing pose must never show as a broken-image box.
            img.addEventListener('error', () => {
                img.dataset.broken = '1';
                if (this.pose === img.dataset.pose) root.classList.add('is-missing');
            });
        }
        this.avatar.querySelector('img')?.addEventListener('error', () => this.avatar.remove());

        // Anchored at the middle of her feet: cx (centre x), by (bottom y), h (height), px.
        this.cur = { cx: 0, by: 0, h: 0, pose: 'welcome', mirror: false };
        this.compact = false;
        this.target = null;
        this.pose = null;
        this.flight = null;
        this.key = '';
        this.shown = false;
        this.entering = false;
        this.hop = 0;
        this.sayUntil = 0;
        this.lastSpark = 0;
        this.pointer = { x: 0, y: 0 };

        window.addEventListener(
            'pointermove',
            (e) => {
                this.pointer.x = (e.clientX / window.innerWidth) * 2 - 1;
                this.pointer.y = (e.clientY / window.innerHeight) * 2 - 1;
            },
            { passive: true },
        );

        const poke = () => {
            this.hop = 1;
            this.sayUntil = performance.now() / 1000 + 4;
            sound.hover(0.4, 9);
        };
        const open = (e) => {
            e.preventDefault();
            sound.click();
            onClick?.();
        };
        for (const el of [this.hit, this.avatar]) {
            el.addEventListener('pointerenter', poke);
            el.addEventListener('click', open);
        }
    }

    measure(img) {
        img.dataset.aspect = String(img.naturalWidth / Math.max(1, img.naturalHeight));
    }

    aspect(pose) {
        return parseFloat(this.poses[pose]?.dataset.aspect) || (pose === 'fly' ? 1.5 : pose === 'moon' ? 1 : 0.667);
    }

    /** Start loading poses before they are needed (they are ~200 KB each). */
    preload(...names) {
        for (const name of names) {
            const img = this.poses[name];
            if (img && !img.getAttribute('src') && img.dataset.src) img.src = img.dataset.src;
        }
    }

    setPose(name) {
        if (this.pose === name) return;
        this.pose = name;
        this.preload(name);
        for (const [key, img] of Object.entries(this.poses)) img.classList.toggle('is-on', key === name);
        this.root.classList.toggle('is-missing', this.poses[name]?.dataset.broken === '1');
    }

    /** Where she should be for this stop and progress. */
    placeFor(st) {
        let plan = PLAN[st.type];
        if (!plan) return null;
        const steps = [].concat(plan);
        plan = steps.find((step) => step.upTo === undefined || st.p < step.upTo) || steps[steps.length - 1];
        const line = steps.length > 1 ? `${st.type}.${steps.indexOf(plan)}` : st.type;

        const w = window.innerWidth;
        const h = window.innerHeight;
        const phone = w < 760 || h > w * 1.15;
        let spec = plan;
        if (phone && plan.m) spec = plan.m === 'compact' ? { compact: true, pose: plan.pose } : { ...plan, ...plan.m };
        // `narrow`: below 1100px there is no room beside this stop's content.
        if (!phone && w < 1100 && plan.narrow === 'compact') spec = { compact: true, pose: plan.pose };
        if (!phone && w < 1100 && !spec.compact) spec = { ...spec, h: spec.h * 0.8 };
        if (spec.compact) return { compact: true, pose: spec.pose, key: `${line}:c`, line };

        let side = spec.side;
        let mirror = !!spec.mirror;
        if (side === 'planet') {
            // Stand on the planet's side of the screen, turned toward its card.
            const n = Math.max(1, st.count);
            const k = clamp(Math.floor(st.p * n), 0, n - 1);
            side = k % 2 === 0 ? 'left' : 'right';
            mirror = side === 'right';
        }

        const ph = spec.h * h;
        const pw = ph * this.aspect(spec.pose);
        const cx = side === 'left' ? spec.x * w + pw / 2 : w - spec.x * w - pw / 2;
        const by = h - spec.y * h;
        return { compact: false, pose: spec.pose, cx, by, h: ph, mirror, key: `${line}:${side}`, line };
    }

    /** Appear: she comes flying out of the portal at the middle of the screen. */
    show() {
        this.shown = true;
        this.root.classList.add('is-on');
        this.preload('welcome', 'fly', 'present');
        Object.assign(this.cur, { cx: window.innerWidth / 2, by: window.innerHeight * 0.52, h: 70, pose: 'fly', mirror: false });
        this.key = '';
        this.entering = true;
    }

    say(line, seconds = 6) {
        const text = this.lines[line];
        if (!text) return;
        this.bubbleTh.textContent = text.th;
        this.bubbleEn.textContent = text.en;
        this.bubbleW = 0; // re-measured on the next frame
        this.sayUntil = performance.now() / 1000 + seconds;
    }

    spark(x, y) {
        if (this.trail.childElementCount > 40) return;
        const s = document.createElement('i');
        s.className = 'xu-spark';
        s.style.left = `${x.toFixed(0)}px`;
        s.style.top = `${y.toFixed(0)}px`;
        s.style.setProperty('--hue', String(Math.round(180 + Math.random() * 140)));
        s.style.setProperty('--size', `${(4 + Math.random() * 8).toFixed(1)}px`);
        this.trail.appendChild(s);
        setTimeout(() => s.remove(), 950);
    }

    /**
     * @param {object} s frame state from main.js (time, dt, cruise, lean, hide, menuOpen)
     */
    update(s) {
        if (!this.shown) return;
        const st = this.journey.stations[this.journey.current];
        const place = st ? this.placeFor(st) : null;
        if (!place) return;

        if (place.key !== this.key) {
            this.key = place.key;
            this.target = place;
            this.compact = place.compact;
            this.root.classList.toggle('is-compact', place.compact);
            document.documentElement.classList.toggle('xu-guide-compact', place.compact);
            if (!place.compact) {
                if (this.cur.h > 0 && (this.flight || this.entering || !this.wasCompact)) {
                    this.flight = { t: 0, from: { ...this.cur } };
                    if (!this.entering) this.sound.hover(0, 11);
                } else {
                    // Back from the corner portrait: rise into place from below.
                    Object.assign(this.cur, { cx: place.cx, by: place.by + window.innerHeight * 0.5, h: place.h, pose: place.pose, mirror: place.mirror });
                }
            }
            this.wasCompact = place.compact;
            this.entering = false;
            this.say(place.line);
            const next = this.journey.stations[this.journey.current + 1];
            if (next && PLAN[next.type]) this.preload(...[].concat(PLAN[next.type]).map((p) => p.pose));
        }

        const talking = performance.now() / 1000 < this.sayUntil && !s.menuOpen && s.hide < 0.5;
        this.root.classList.toggle('is-talking', talking);
        this.root.classList.toggle('is-away', s.hide > 0.5 && s.cruise < 0.2);

        const tgt = this.target;
        if (!tgt) return;
        if (tgt.compact) {
            this.setPose(tgt.pose);
            this.bubble.style.transform = '';
            return;
        }

        const W = window.innerWidth;
        const H = window.innerHeight;
        let { cx, by, h: height, mirror, pose } = tgt;
        let tilt = 0;
        let flying = false;

        if (this.flight) {
            const f = this.flight;
            f.t = Math.min(1, f.t + s.dt / FLY_TIME);
            const e = easeInOutCubic(f.t);
            const from = f.from;
            const cruiseH = Math.min(from.h, tgt.h) * 0.6 + 50;
            cx = lerp(from.cx, tgt.cx, e);
            by = lerp(from.by, tgt.by, e) - Math.sin(Math.PI * f.t) * H * 0.16;
            if (f.t < 0.15) height = lerp(from.h, cruiseH, f.t / 0.15);
            else if (f.t > 0.85) height = lerp(tgt.h, cruiseH, (1 - f.t) / 0.15);
            else height = cruiseH;
            const goingLeft = tgt.cx < from.cx - 4;
            flying = f.t > 0.1 && f.t < 0.9;
            pose = flying ? 'fly' : f.t < 0.5 ? from.pose : tgt.pose;
            mirror = flying ? goingLeft : f.t < 0.5 ? from.mirror : tgt.mirror;
            tilt = flying ? (goingLeft ? 7 : -7) : 0;
            if (f.t >= 1) {
                this.flight = null;
                this.hop = 0.9;
            }
        } else if (s.cruise > 0.55) {
            // Fast travel: she flies along beside the camera.
            flying = true;
            pose = 'fly';
            height = Math.min(tgt.h * 0.55, H * 0.3);
            const right = tgt.cx > W / 2;
            cx = right ? W * 0.8 : W * 0.2;
            by = H * 0.55;
            mirror = right;
            tilt = right ? 6 : -6;
        }

        if (flying && s.time - this.lastSpark > 0.035) {
            this.lastSpark = s.time;
            const a = this.aspect('fly');
            // Sparkles fall off her trailing side.
            this.spark(cx + (mirror ? 1 : -1) * height * a * 0.36, by - height * 0.45 + (Math.random() - 0.5) * height * 0.4);
        }

        this.setPose(pose);
        const k = this.flight ? 1 : 1 - Math.exp(-4.5 * s.dt);
        this.cur.cx = lerp(this.cur.cx, cx, k);
        this.cur.by = lerp(this.cur.by, by, k);
        this.cur.h = lerp(this.cur.h, height, k);
        this.cur.mirror = mirror;
        this.cur.pose = pose;

        // Alive: bob, sway, breathe, drift with the pointer, lean into speed.
        this.hop = damp(this.hop, 0, 4.5, s.dt);
        const bob = Math.sin(s.time * 1.7) * 9 - Math.sin(this.hop * Math.PI) * 26;
        const sway = Math.sin(s.time * 1.1) * 1.4 + tilt + clamp(s.lean, -1, 1) * 3;
        const breathe = 1 + Math.sin(s.time * 2.3) * 0.009;
        const dx = -this.pointer.x * 12;
        const dy = -this.pointer.y * 7;
        const hh = this.cur.h;
        const ww = hh * this.aspect(pose);
        const left = this.cur.cx - ww / 2 + dx;
        const top = this.cur.by - hh + dy + bob;

        const b = this.body.style;
        b.width = `${ww.toFixed(1)}px`;
        b.height = `${hh.toFixed(1)}px`;
        b.transform = `translate3d(${left.toFixed(1)}px, ${top.toFixed(1)}px, 0) rotate(${sway.toFixed(2)}deg) scale(${this.cur.mirror ? -1 : 1}, ${breathe.toFixed(4)})`;
        this.body.classList.toggle('is-flying', pose === 'fly');

        // The bubble rides above her head, leaning toward the edge she keeps to,
        // so it stays in her column instead of sliding under the content.
        if (!this.bubbleW) {
            this.bubbleW = this.bubble.offsetWidth || 280;
            this.bubbleH = this.bubble.offsetHeight || 70;
        }
        const right = this.cur.cx > W / 2;
        const headFrac = HEAD_X[pose] ?? 0.5;
        const headX = left + ww * (this.cur.mirror ? 1 - headFrac : headFrac);
        const bw = this.bubbleW;
        const bx = clamp(headX - bw * (right ? 0.78 : 0.22), 8, W - bw - 8);
        // Never up under the HUD bar: sink onto her hair rather than cover the buttons.
        const byb = Math.max(top + hh * 0.06, HUD_BOTTOM + 10 + this.bubbleH);
        this.bubble.style.transform = `translate3d(${bx.toFixed(0)}px, ${byb.toFixed(0)}px, 0) translateY(-100%)`;
        this.bubble.style.setProperty('--tail', `${clamp(headX - bx, 18, bw - 18).toFixed(0)}px`);
    }
}
