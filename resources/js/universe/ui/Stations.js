import { deck, origin, platforms, reveal } from '../engine/choreo.js';
import { ANCHORS } from '../world/anchors.js';
import { clamp, damp, lerp, smoothstep } from '../lib/math.js';

const FITTABLE = new Set(['core', 'services', 'products', 'stack', 'reviews', 'launch', 'control']);

/**
 * Drives the HTML side of every stop from the journey: when a panel mounts,
 * how far it has arrived (--vin) or left (--vout), and each stop's own
 * choreography — the stat labels, the sliding product track, one platform
 * card at a time, the turning stack ring, the review deck.
 */
export class Stations {
    constructor({ journey, world, sound }) {
        this.journey = journey;
        this.world = world;
        this.sound = sound;
        this.intro = 1;
        this.pointer = { x: 0, y: 0 };

        this.items = journey.stations.map((st) => {
            const panel = st.el.querySelector('.xu-panel');
            const item = { st, panel, mounted: false, active: false, fitted: false };

            switch (st.type) {
                case 'origin':
                    item.statsEl = panel.querySelector('.xu-origin__stats');
                    item.whyEl = panel.querySelector('.xu-origin__why');
                    item.labels = [...panel.querySelectorAll('.xu-stat')];
                    item.pips = [...panel.querySelectorAll('.xu-stats__pips i')];
                    break;
                case 'services':
                    item.grid = panel.querySelector('.xu-svc-grid');
                    this.bindBeacons(panel);
                    break;
                case 'products':
                    item.rail = panel.querySelector('.xu-track__rail');
                    item.cards = [...panel.querySelectorAll('.xu-prod')];
                    item.trackX = 0;
                    break;
                case 'platforms':
                    item.head = panel.querySelector('.xu-planets__head');
                    item.cards = [...panel.querySelectorAll('.xu-planet')].map((el) => ({
                        el,
                        key: el.dataset.xuPlanet,
                        accent: getComputedStyle(el).getPropertyValue('--accent').trim(),
                        label: `${el.querySelector('h3')?.textContent ?? ''}`.toUpperCase(),
                    }));
                    break;
                case 'stack':
                    item.orbit = panel.querySelector('.xu-orbit');
                    item.ring = panel.querySelector('.xu-orbit__ring');
                    item.chips = [...panel.querySelectorAll('.xu-orbit__item')];
                    break;
                case 'reviews':
                    item.cards = [...panel.querySelectorAll('.xu-review')];
                    break;
                default:
                    break;
            }
            return item;
        });

        window.addEventListener(
            'pointermove',
            (e) => {
                this.pointer.x = (e.clientX / window.innerWidth) * 2 - 1;
                this.pointer.y = (e.clientY / window.innerHeight) * 2 - 1;
            },
            { passive: true },
        );
    }

    bindBeacons(panel) {
        const cards = [...panel.querySelectorAll('[data-xu-beacon]')];
        const on = (k) => this.world.galaxy?.highlight(k);
        cards.forEach((card) => {
            const k = parseInt(card.dataset.xuBeacon, 10);
            card.addEventListener('pointerenter', () => on(k));
            card.addEventListener('pointerleave', () => on(-1));
            card.addEventListener('focus', () => on(k));
            card.addEventListener('blur', () => on(-1));
        });
    }

    /**
     * Shrink a panel's content to fit short screens (zoom keeps layout honest).
     * Only stops laid out in normal flow: the origin and platform panels place
     * their parts absolutely, and their scrollHeight says nothing about fit.
     */
    fit(item) {
        item.fitted = true;
        if (!FITTABLE.has(item.st.type)) return;
        const panel = item.panel;
        panel.style.setProperty('--fit', '1');
        const room = panel.clientHeight;
        const need = panel.scrollHeight;
        if (need > room + 2) panel.style.setProperty('--fit', Math.max(0.6, room / need).toFixed(3));
    }

    relayout() {
        for (const item of this.items) item.fitted = false;
    }

    /**
     * @returns {object|null} what the HUD reticle should lock onto this frame
     */
    update(s) {
        let target = null;
        const hide = s.hide || 0;
        document.documentElement.style.setProperty('--hide', hide.toFixed(3));

        for (const item of this.items) {
            const { st, panel } = item;
            const p = st.p;
            const r = reveal(st.type, p);
            let vin = r.vin;
            if (st.type === 'core') vin = Math.min(vin, this.intro);

            const mounted = r.mounted && s.entered !== false;
            if (mounted !== item.mounted) {
                item.mounted = mounted;
                st.el.classList.toggle('is-on', mounted);
                if (mounted && !item.fitted) this.fit(item);
            }
            if (!mounted) {
                if (item.active) {
                    item.active = false;
                    st.el.classList.remove('is-active');
                }
                continue;
            }

            const active = vin > 0.55 && r.vout < 0.45 && hide < 0.3;
            if (active !== item.active) {
                item.active = active;
                st.el.classList.toggle('is-active', active);
            }

            panel.style.setProperty('--vin', vin.toFixed(4));
            panel.style.setProperty('--vout', r.vout.toFixed(4));

            const t = this[st.type]?.(item, p, s, vin);
            if (t) target = t;
        }
        return target;
    }

    core(item, p, s) {
        // Tag the star while the camera swings past it.
        const o = smoothstep(0.3, 0.45, p) * (1 - smoothstep(0.62, 0.74, p)) * (window.innerWidth > 700 ? 1 : 0);
        if (o < 0.01) return null;
        return { pos: ANCHORS.core, radius: 9, label: 'XMAN CORE · ONLINE', accent: '#ffd479', opacity: o };
    }

    origin(item, p) {
        const c = origin(p);
        const panel = item.panel;
        item.statsEl.style.setProperty('--sa', c.stats.toFixed(3));
        item.labels.forEach((el, k) => el.style.setProperty('--on', c.label(k).toFixed(3)));
        item.pips.forEach((el, k) => el.style.setProperty('--fill', c.fill(k).toFixed(3)));
        item.whyEl.style.setProperty('--wa', c.why.toFixed(3));
        panel.classList.toggle('has-why', c.why > 0.001);
        // Links in the hidden half must not catch the pointer.
        item.whyEl.style.pointerEvents = c.why > 0.6 ? 'auto' : 'none';
        return null;
    }

    services(item, p, s) {
        const tiltY = lerp(9, -9, smoothstep(0.1, 0.9, p)) + this.pointer.x * 3;
        const tiltX = 7 - this.pointer.y * 3;
        item.grid.style.setProperty('--tilt-y', `${tiltY.toFixed(2)}deg`);
        item.grid.style.setProperty('--tilt-x', `${tiltX.toFixed(2)}deg`);
        return null;
    }

    products(item, p, s) {
        const vw = window.innerWidth;
        const railW = item.rail.scrollWidth;
        const travel = Math.max(0, railW - vw);
        const goal = travel > 0 ? lerp(vw * 0.08, -travel, smoothstep(0.14, 0.86, p)) : 0;
        item.trackX = damp(item.trackX, goal, 10, s.dt);
        item.rail.style.setProperty('--track-x', `${item.trackX.toFixed(1)}px`);
        // Cover-flow: cards turn to face the middle of the screen.
        for (const card of item.cards) {
            const rect = card.getBoundingClientRect();
            const off = (rect.left + rect.width / 2 - vw / 2) / vw;
            card.style.setProperty('--ry', `${clamp(-off * 34, -28, 28).toFixed(2)}deg`);
            card.style.setProperty('--tz', `${(-Math.abs(off) * 90).toFixed(1)}px`);
        }
        return null;
    }

    platforms(item, p, s) {
        const n = item.cards.length;
        const c = platforms(p, n);
        item.head.style.setProperty('--ha', c.head.toFixed(3));
        let target = null;
        item.cards.forEach((card, k) => {
            const on = c.card(k);
            card.el.style.setProperty('--on', on.toFixed(3));
            const visible = on > 0.004;
            if (visible !== card.visible) {
                card.visible = visible;
                card.el.classList.toggle('is-on', visible);
            }
            card.el.style.pointerEvents = on > 0.6 ? 'auto' : 'none';
            if (k === c.focus && on > 0.01) {
                const planet = this.world.planets?.target(k);
                if (planet) {
                    target = {
                        pos: planet.pos,
                        radius: planet.radius,
                        label: `${String(k + 1).padStart(2, '0')} · ${card.key.toUpperCase()}`,
                        accent: card.accent,
                        opacity: on,
                    };
                }
            }
        });
        return target;
    }

    stack(item, p, s) {
        const n = item.chips.length;
        const spin = p * 220 + s.time * 7;
        const radius = Math.min(430, window.innerWidth * 0.36);
        item.orbit.style.setProperty('--orbit-r', `${radius.toFixed(0)}px`);
        item.ring.style.setProperty('--spin', `${(-spin).toFixed(2)}deg`);
        item.chips.forEach((chip, j) => {
            const deg = (j * 360) / n - spin;
            const face = 0.2 + 0.8 * ((Math.cos(deg * (Math.PI / 180)) + 1) / 2);
            chip.style.setProperty('--face', face.toFixed(3));
            chip.style.setProperty('--counter', `${(-deg).toFixed(2)}deg`);
            // Near chips in front of far ones.
            chip.style.zIndex = String(Math.round(face * 100));
        });
        return null;
    }

    reviews(item, p) {
        const n = item.cards.length;
        const f = deck(p, n);
        item.cards.forEach((card, d) => {
            const rel = d - f;
            let dy;
            let dz;
            let rx;
            let o;
            let sc;
            if (rel <= 0) {
                // Dealt: lifts toward the viewer and fades.
                const t = -rel;
                dy = -t * 70;
                dz = t * 180;
                rx = -t * 8;
                o = clamp(1 - t * 1.7);
                sc = 1 + t * 0.04;
            } else {
                // Still in the deck: behind, smaller, dimmer.
                dy = -rel * 26;
                dz = -rel * 120;
                rx = 0;
                o = clamp(1 - rel * 0.32);
                sc = 1 - rel * 0.04;
            }
            const st = card.style;
            st.setProperty('--dy', `${dy.toFixed(1)}px`);
            st.setProperty('--dz', `${dz.toFixed(1)}px`);
            st.setProperty('--drx', `${rx.toFixed(2)}deg`);
            st.setProperty('--ds', sc.toFixed(3));
            st.setProperty('--do', o.toFixed(3));
            st.setProperty('--dv', o > 0.01 ? 'visible' : 'hidden');
            st.zIndex = String(100 - Math.round(Math.abs(rel) * 10));
            st.pointerEvents = Math.abs(rel) < 0.4 ? 'auto' : 'none';
        });
        return null;
    }
}
