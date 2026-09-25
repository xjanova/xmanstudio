import { clamp, easeOutCubic, wrap } from '../lib/math.js';

/**
 * The command ring. Ten destinations on a 3D ring: the wheel, a drag or the
 * arrow keys spin it (each card that passes the front ticks like a dial),
 * the front card is the selection, Enter or a click on it launches there —
 * the chosen card flies at the viewer, the universe goes to hyperspace, and
 * the browser follows the link. Clicking a card at the side brings it round
 * to the front first. On a portrait screen the ring stands on its side and
 * spins like a drum.
 */
export class Menu {
    constructor({ sound, onOpenChange, onLaunch, onHome, stationToItem }) {
        this.root = document.getElementById('xu-menu');
        this.stage = document.getElementById('xu-menu-stage');
        this.ring = document.getElementById('xu-ring');
        this.button = document.getElementById('xu-menu-btn');
        this.items = [...this.ring.querySelectorAll('.xu-ring__item')];
        this.nowTh = document.getElementById('xu-menu-now-th');
        this.nowEn = document.getElementById('xu-menu-now-en');
        this.aboutTh = document.getElementById('xu-menu-about-th');
        this.aboutEn = document.getElementById('xu-menu-about-en');

        this.sound = sound;
        this.onOpenChange = onOpenChange;
        this.onLaunch = onLaunch;
        this.onHome = onHome;
        this.stationToItem = stationToItem;

        this.n = this.items.length;
        this.angle = 0; // in items, fractional while turning
        this.target = 0;
        this.vel = 0;
        this.front = -1;
        this.isOpen = false;
        this.going = false;
        this.intro = 1;
        this.introT = 1;
        this.vertical = false;
        this.drag = null;
        this.wheelAcc = 0;
        this.wheelAt = 0;

        this.items.forEach((item, i) => {
            item.addEventListener('click', (e) => this.onItemClick(e, i));
            item.addEventListener('focus', () => this.spinTo(i));
            item.addEventListener('pointerenter', () => {
                if (!this.drag) this.sound.hover(this.panOf(item), i);
            });
        });

        this.button.addEventListener('click', () => (this.isOpen ? this.close() : this.open()));
        for (const el of this.root.querySelectorAll('[data-xu-menu-close]')) el.addEventListener('click', () => this.close());

        this.stage.addEventListener('wheel', (e) => this.onWheel(e), { passive: false });
        this.stage.addEventListener('pointerdown', (e) => this.onPointerDown(e));
        window.addEventListener('pointermove', (e) => this.onPointerMove(e));
        window.addEventListener('pointerup', (e) => this.onPointerUp(e));
        window.addEventListener('pointercancel', (e) => this.onPointerUp(e));

        document.addEventListener('keydown', (e) => this.onKey(e));
        for (const link of this.root.querySelectorAll('.xu-dock')) {
            link.addEventListener('pointerenter', () => this.sound.hover(this.panOf(link), 7));
        }

        this.layout();
    }

    panOf(el) {
        const r = el.getBoundingClientRect();
        return clamp(((r.left + r.width / 2) / window.innerWidth) * 2 - 1, -1, 1) * 0.8;
    }

    layout() {
        this.vertical = window.innerWidth < 700 || window.innerHeight > window.innerWidth * 1.15;
        this.ring.classList.toggle('is-vertical', this.vertical);
        const cs = getComputedStyle(this.ring);
        const cw = parseFloat(cs.getPropertyValue('--cw')) || 232;
        const ch = parseFloat(cs.getPropertyValue('--ch')) || 130;
        // Radius that spaces the cards evenly with a gap between neighbours.
        const pitch = this.vertical ? ch + 58 : cw + 36;
        this.R = pitch / (2 * Math.tan(Math.PI / this.n));
        this.ring.style.setProperty('--R', `${this.R.toFixed(1)}px`);
        this.stage.style.setProperty('--persp', `${Math.round(this.R * (this.vertical ? 3.4 : 2.6))}px`);
        this.pxPerItem = this.vertical ? ch * 0.9 : cw * 0.85;
    }

    open() {
        if (this.isOpen || this.going) return;
        this.isOpen = true;
        this.lastFocus = document.activeElement;
        this.layout();

        const start = this.stationToItem?.() ?? 0;
        this.target = start;
        this.angle = start - 2.5; // arrive with a spin
        this.introT = 0;
        this.front = -1;

        this.root.hidden = false;
        this.button.setAttribute('aria-expanded', 'true');
        requestAnimationFrame(() => this.root.classList.add('is-open'));
        this.sound.open();
        this.onOpenChange?.(true);
        setTimeout(() => this.items[start]?.focus({ preventScroll: true }), 420);
    }

    close() {
        if (!this.isOpen || this.going) return;
        this.isOpen = false;
        this.root.classList.remove('is-open');
        this.button.setAttribute('aria-expanded', 'false');
        this.sound.close();
        this.onOpenChange?.(false);
        setTimeout(() => {
            if (!this.isOpen) this.root.hidden = true;
        }, 460);
        if (this.lastFocus?.focus) this.lastFocus.focus({ preventScroll: true });
        else this.button.focus({ preventScroll: true });
    }

    /** Reset after the page comes back from the back/forward cache. */
    reset() {
        this.going = false;
        this.isOpen = false;
        this.root.classList.remove('is-open', 'is-going');
        this.root.hidden = true;
        for (const item of this.items) item.classList.remove('is-chosen');
        this.button.setAttribute('aria-expanded', 'false');
    }

    spinTo(i) {
        // Take the short way round.
        this.target = this.angle + wrap(i - this.angle, this.n);
    }

    step(d) {
        this.target = Math.round(this.target) + d;
    }

    onItemClick(e, i) {
        if (this.drag?.moved) {
            e.preventDefault();
            return;
        }
        // Let the browser handle a new-tab click as usual.
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
        e.preventDefault();
        if (i !== this.front) {
            this.spinTo(i);
            return;
        }
        this.launch(i);
    }

    launch(i) {
        if (this.going) return;
        const item = this.items[i];
        const href = item.getAttribute('href');
        const home = new URL(href, location.href);
        if (home.origin === location.origin && home.pathname === '/' && !home.search) {
            // "Home" is where we already are: fly back to the core instead of reloading.
            this.sound.select(i);
            this.close();
            this.onHome?.();
            return;
        }
        this.going = true;
        item.classList.add('is-chosen');
        this.root.classList.add('is-going');
        this.sound.select(i);
        this.onLaunch?.(href, item);
    }

    onWheel(e) {
        e.preventDefault();
        const d = Math.abs(e.deltaY) > Math.abs(e.deltaX) ? e.deltaY : e.deltaX;
        this.wheelAcc += e.deltaMode === 1 ? d * 30 : d;
        const now = performance.now();
        if (Math.abs(this.wheelAcc) > 42 && now - this.wheelAt > 70) {
            this.step(Math.sign(this.wheelAcc));
            this.wheelAcc = 0;
            this.wheelAt = now;
        }
    }

    onPointerDown(e) {
        if (e.button !== 0) return;
        this.drag = {
            id: e.pointerId,
            start: this.vertical ? e.clientY : e.clientX,
            angle: this.angle,
            last: this.vertical ? e.clientY : e.clientX,
            lastT: performance.now(),
            v: 0,
            moved: false,
        };
    }

    onPointerMove(e) {
        const d = this.drag;
        if (!d || e.pointerId !== d.id) return;
        const pos = this.vertical ? e.clientY : e.clientX;
        const dx = pos - d.start;
        if (!d.moved && Math.abs(dx) > 6) {
            d.moved = true;
            this.stage.classList.add('is-dragging');
            try {
                this.stage.setPointerCapture(e.pointerId);
            } catch {
                // ignore
            }
        }
        if (!d.moved) return;
        const now = performance.now();
        const sign = this.vertical ? 1 : -1;
        this.angle = d.angle + (sign * dx) / this.pxPerItem;
        this.target = this.angle;
        const dt = Math.max(1, now - d.lastT) / 1000;
        d.v = (sign * (pos - d.last)) / this.pxPerItem / dt;
        d.last = pos;
        d.lastT = now;
    }

    onPointerUp(e) {
        const d = this.drag;
        if (!d || e.pointerId !== d.id) return;
        this.stage.classList.remove('is-dragging');
        if (d.moved) {
            // Flick: carry the momentum, land on a card.
            this.target = Math.round(this.angle + clamp(d.v * 0.22, -4, 4));
            // Swallow the click that follows the drag.
            setTimeout(() => {
                this.drag = null;
            }, 0);
        } else {
            this.drag = null;
        }
    }

    onKey(e) {
        if (!this.isOpen) {
            if ((e.key === 'm' || e.key === 'M') && !e.target.closest?.('input, textarea, [contenteditable]') && !e.metaKey && !e.ctrlKey && !e.altKey) {
                e.preventDefault();
                this.open();
            }
            return;
        }
        const back = this.vertical ? 'ArrowUp' : 'ArrowLeft';
        const fwd = this.vertical ? 'ArrowDown' : 'ArrowRight';
        switch (e.key) {
            case 'Escape':
                e.preventDefault();
                this.close();
                return;
            case fwd:
            case 'ArrowRight':
            case 'ArrowDown':
                e.preventDefault();
                this.step(1);
                this.focusFrontSoon();
                return;
            case back:
            case 'ArrowLeft':
            case 'ArrowUp':
                e.preventDefault();
                this.step(-1);
                this.focusFrontSoon();
                return;
            case 'Home':
                e.preventDefault();
                this.spinTo(0);
                return;
            case 'End':
                e.preventDefault();
                this.spinTo(this.n - 1);
                return;
            case 'Enter':
                if (!e.target.closest?.('.xu-ring__item, .xu-dock, button')) {
                    e.preventDefault();
                    this.launch(this.front);
                }
                return;
            case 'Tab':
                this.trapFocus(e);
                return;
            default:
                if (/^[0-9]$/.test(e.key)) {
                    const i = e.key === '0' ? 9 : parseInt(e.key, 10) - 1;
                    if (i < this.n) {
                        e.preventDefault();
                        this.spinTo(i);
                    }
                }
        }
    }

    focusFrontSoon() {
        clearTimeout(this.focusTimer);
        this.focusTimer = setTimeout(() => {
            const i = ((Math.round(this.target) % this.n) + this.n) % this.n;
            this.items[i]?.focus({ preventScroll: true });
        }, 60);
    }

    trapFocus(e) {
        const focusables = [...this.root.querySelectorAll('a[href], button:not([disabled])')].filter((el) => el.offsetParent !== null || el.closest('.xu-ring'));
        if (!focusables.length) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus({ preventScroll: true });
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus({ preventScroll: true });
        }
    }

    update(dt) {
        if (!this.isOpen && !this.going) return;

        // Intro: the ring swells in from small as it spins into place.
        if (this.introT < 1) {
            this.introT = Math.min(1, this.introT + dt / 0.9);
            this.intro = 0.55 + 0.45 * easeOutCubic(this.introT);
            this.ring.style.setProperty('--intro', this.intro.toFixed(4));
        }

        if (!this.drag?.moved) {
            // Critically damped spring toward the target card.
            const k = 90;
            const c = 2 * Math.sqrt(k);
            this.vel += (k * (this.target - this.angle) - c * this.vel) * dt;
            this.angle += this.vel * dt;
            if (Math.abs(this.target - this.angle) < 0.0005 && Math.abs(this.vel) < 0.001) {
                this.angle = this.target;
                this.vel = 0;
            }
        }

        const step = 360 / this.n;
        const rot = this.vertical ? this.angle * step : -this.angle * step;
        this.ring.style.setProperty('--rot', `${rot.toFixed(3)}deg`);

        const front = ((Math.round(this.angle) % this.n) + this.n) % this.n;
        if (front !== this.front) {
            if (this.front !== -1 && !this.going) this.sound.tick(0);
            this.front = front;
            this.items.forEach((item, i) => item.classList.toggle('is-front', i === front));
            this.describe(front);
            this.settled = false;
        }
        if (!this.settled && Math.abs(this.target - this.angle) < 0.02 && this.introT >= 1) {
            this.settled = true;
            if (!this.going) this.sound.lock(front);
        }

        this.items.forEach((item, i) => {
            const d = wrap(i - this.angle, this.n);
            const facing = Math.cos((d * step * Math.PI) / 180);
            const near = Math.max(0, 1 - Math.abs(d));
            item.style.setProperty('--face', (0.14 + 0.86 * Math.pow(Math.max(0, (facing + 1) / 2), 2.2)).toFixed(3));
            item.style.setProperty('--glow', near.toFixed(3));
            item.style.setProperty('--lift', (1 + near * 0.12).toFixed(3));
            // Only cards near the front take Tab; focusing one turns the ring to it.
            const tab = Math.abs(d) < 3.5 ? 0 : -1;
            if (item.tabIndex !== tab) item.tabIndex = tab;
        });
    }

    describe(i) {
        const item = this.items[i];
        const label = item.querySelector('.xu-ring__label');
        this.nowTh.textContent = label.querySelector('b').textContent;
        this.nowEn.textContent = label.querySelector('small').textContent;
        this.aboutTh.textContent = item.dataset.aboutTh || '';
        this.aboutEn.textContent = item.dataset.aboutEn || '';
        this.root.style.setProperty('--xu-accent-now', getComputedStyle(item).getPropertyValue('--accent'));
    }
}
