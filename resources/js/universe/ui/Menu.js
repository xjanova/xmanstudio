import { clamp, damp, easeOutCubic, wrap } from '../lib/math.js';

/**
 * The command ring. Ten destinations on a big tilted 3D carousel that keeps
 * turning: it drifts round slowly on its own; with a mouse, pointing toward
 * either side steers it that way (faster the further out). It holds still
 * while it is being read — the pointer on the card in front or on the text
 * under the ring, keyboard focus in the menu, or a visitor who asked for
 * reduced motion — so nothing changes under a click. The wheel, a drag or
 * the arrow keys step it card by card, each card that passes the front
 * ticking like a dial. Under the ring, the card in front is explained in
 * plain words: what it is, three highlights, and a way in.
 *
 * Enter or a click on the front card launches there (it flies at the viewer,
 * the universe goes to hyperspace, and the browser follows the link); a click
 * on a card at the side turns it to the front first. On a portrait screen the
 * ring stands on its side and turns like a drum.
 */
const AUTO_SPEED = 0.16; // cards per second while nobody is steering
const STEER_MAX = 1.9; // cards per second with the pointer at the very edge
const DEAD_ZONE = 0.24; // share of the half-width around the middle that does not steer
const HOLD_MS = 3200; // how long a key, a wheel step or a click keeps the ring still

export class Menu {
    constructor({ sound, onOpenChange, onLaunch, onHome, stationToItem }) {
        this.root = document.getElementById('xu-menu');
        this.stage = document.getElementById('xu-menu-stage');
        this.ring = document.getElementById('xu-ring');
        this.button = document.getElementById('xu-menu-btn');
        this.items = [...this.ring.querySelectorAll('.xu-ring__item')];
        this.nowTh = document.getElementById('xu-menu-now-th');
        this.nowEn = document.getElementById('xu-menu-now-en');
        this.descTh = document.getElementById('xu-menu-desc-th');
        this.descEn = document.getElementById('xu-menu-desc-en');
        this.points = document.getElementById('xu-menu-points');
        this.go = document.getElementById('xu-menu-go');
        this.info = this.root.querySelector('.xu-menu__info');

        this.sound = sound;
        this.onOpenChange = onOpenChange;
        this.onLaunch = onLaunch;
        this.onHome = onHome;
        this.stationToItem = stationToItem;

        this.n = this.items.length;
        this.angle = 0; // in cards, fractional while turning
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
        this.holdUntil = 0;
        this.steer = 0; // -1..1 from the pointer, 0 in the middle
        this.pointerIn = false;
        this.pointer = { x: 0, y: 0 };
        this.hoverItem = -1; // the card under a resting mouse
        this.hoverAt = 0;
        this.overInfo = false;
        this.driftDir = 1; // the drift carries on the way it was last steered
        this.keyboard = false; // turned by keys: it stays where they put it
        this.snapping = false;
        this.reduced = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

        this.info.addEventListener('pointerenter', (e) => {
            if (e.pointerType !== 'touch') this.overInfo = true;
        });
        this.info.addEventListener('pointerleave', () => {
            this.overInfo = false;
        });
        // A finger has no hover: any touch in the menu holds the ring for a read.
        this.root.addEventListener('pointerdown', (e) => {
            if (e.pointerType === 'touch') this.hold(6000);
        });
        // The mouse is back in charge once it actually moves.
        this.root.addEventListener('pointermove', (e) => {
            if (e.pointerType === 'mouse' && (e.movementX || e.movementY)) this.keyboard = false;
        });

        this.items.forEach((item, i) => {
            item.addEventListener('click', (e) => this.onItemClick(e, i));
            item.addEventListener('focus', () => {
                if (this.isOpen) this.spinTo(i);
            });
            item.addEventListener('pointerenter', () => {
                if (!this.drag) this.sound.hover(this.panOf(item), i);
            });
        });

        this.go.addEventListener('click', (e) => {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
            e.preventDefault();
            this.launch(this.front);
        });

        // detail 0: the button was pressed from the keyboard.
        this.button.addEventListener('click', (e) => (this.isOpen ? this.close() : this.open(e.detail === 0)));
        for (const el of this.root.querySelectorAll('[data-xu-menu-close]')) el.addEventListener('click', () => this.close());

        this.stage.addEventListener('wheel', (e) => this.onWheel(e), { passive: false });
        this.stage.addEventListener('pointerdown', (e) => this.onPointerDown(e));
        this.stage.addEventListener('pointermove', (e) => this.onHover(e));
        this.stage.addEventListener('pointerleave', () => {
            this.pointerIn = false;
            this.steer = 0;
            this.hoverItem = -1;
        });
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
        const w = window.innerWidth;
        const h = window.innerHeight;
        this.vertical = w < 700 || h > w * 1.15;
        this.ring.classList.toggle('is-vertical', this.vertical);
        // Big cards: a fifth of the screen wide, within reason.
        const cw = this.vertical ? clamp(w * 0.56, 190, 260) : clamp(w * 0.2, 240, 340);
        const ch = Math.round(cw * 0.56);
        this.ring.style.setProperty('--cw', `${Math.round(cw)}px`);
        this.ring.style.setProperty('--ch', `${ch}px`);
        // Radius that spaces the cards evenly with a gap between neighbours.
        const pitch = this.vertical ? ch + 62 : cw + 40;
        this.R = pitch / (2 * Math.tan(Math.PI / this.n));
        this.ring.style.setProperty('--R', `${this.R.toFixed(1)}px`);
        this.stage.style.setProperty('--persp', `${Math.round(this.R * (this.vertical ? 3.2 : 2.9))}px`);
        this.pxPerItem = this.vertical ? ch * 0.9 : cw * 0.85;
    }

    open(byKeyboard = false) {
        if (this.isOpen || this.going) return;
        this.isOpen = true;
        this.keyboard = byKeyboard;
        this.lastFocus = document.activeElement;
        this.layout();

        const start = this.stationToItem?.() ?? 0;
        this.target = start;
        this.angle = start - 2.5; // arrive with a spin
        this.vel = 0;
        this.introT = 0;
        this.front = -1;
        this.holdUntil = performance.now() + 1800;

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

    /**
     * Put the menu away without ceremony: after the page comes back from the
     * back/forward cache, or when a launch never left the page. Tells main.js
     * too, or the HUD would stay hidden and the stops dimmed behind a menu
     * that is no longer there.
     */
    reset() {
        const wasOpen = this.isOpen || this.going;
        this.going = false;
        this.isOpen = false;
        this.root.classList.remove('is-open', 'is-going');
        this.root.hidden = true;
        for (const item of this.items) item.classList.remove('is-chosen');
        this.button.setAttribute('aria-expanded', 'false');
        if (wasOpen) this.onOpenChange?.(false);
    }

    hold(ms = HOLD_MS) {
        this.holdUntil = performance.now() + ms;
    }

    spinTo(i) {
        // Take the short way round.
        this.target = this.angle + wrap(i - this.angle, this.n);
        this.hold();
    }

    step(d) {
        this.target = Math.round(this.target) + d;
        this.hold();
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
        if (this.going || i < 0) return;
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

    /** Mouse or pen over the stage: steer toward the side it points at. Touch has no hover. */
    onHover(e) {
        if (e.pointerType === 'touch') return;
        this.pointerIn = true;
        this.pointer.x = e.clientX;
        this.pointer.y = e.clientY;
        if (this.vertical) return;
        const r = this.stage.getBoundingClientRect();
        const nx = ((e.clientX - (r.left + r.width / 2)) / (r.width / 2)) || 0;
        const a = Math.abs(nx);
        this.steer = a < DEAD_ZONE ? 0 : Math.sign(nx) * Math.pow(Math.min(1, (a - DEAD_ZONE) / (1 - DEAD_ZONE)), 1.4);
        if (this.steer !== 0) this.driftDir = Math.sign(this.steer);
    }

    /**
     * Which card is under the mouse. Looked up a few times a second rather than
     * from pointer events: the cards move under a pointer that stands still.
     */
    trackHover() {
        const now = performance.now();
        if (!this.pointerIn || now - this.hoverAt < 120) return;
        this.hoverAt = now;
        const el = document.elementFromPoint(this.pointer.x, this.pointer.y);
        const item = el?.closest?.('.xu-ring__item');
        this.hoverItem = item ? this.items.indexOf(item) : -1;
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
            this.vel = 0;
            this.hold();
            // Swallow the click that follows the drag.
            setTimeout(() => {
                this.drag = null;
            }, 0);
        } else {
            this.drag = null;
        }
    }

    onKey(e) {
        // No single-key shortcut to open it: one that cannot be turned off fails
        // WCAG 2.1.4, and it fired under the loader before the flight began.
        // The menu button is in the Tab order, and the guide opens it too.
        if (!this.isOpen) return;
        if (!['Shift', 'Control', 'Alt', 'Meta'].includes(e.key)) this.keyboard = true;
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
                if (!e.target.closest?.('.xu-ring__item, .xu-dock, button, a')) {
                    e.preventDefault();
                    this.launch(this.front);
                }
                return;
            case 'Tab':
                this.hold(6000);
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
        const focusables = [...this.root.querySelectorAll('a[href], button:not([disabled])')].filter(
            (el) => el.tabIndex >= 0 && (el.offsetParent !== null || el.closest('.xu-ring')),
        );
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

    /** Critically damped spring toward this.target. */
    spring(dt) {
        const k = 90;
        const c = 2 * Math.sqrt(k);
        this.vel += (k * (this.target - this.angle) - c * this.vel) * dt;
        this.angle += this.vel * dt;
        if (Math.abs(this.target - this.angle) < 0.0005 && Math.abs(this.vel) < 0.001) {
            this.angle = this.target;
            this.vel = 0;
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

        this.trackHover();
        const reading =
            this.overInfo ||
            (this.hoverItem !== -1 && this.hoverItem === this.front) ||
            // Keyboard users move through the cards themselves.
            this.keyboard ||
            this.reduced;

        let driven = true; // someone is turning it (ticks and locks are heard)
        if (this.drag?.moved) {
            // The pointer has it.
        } else if (this.going || performance.now() < this.holdUntil) {
            this.spring(dt);
        } else if (this.pointerIn && this.steer !== 0) {
            // Steering: glide toward the side the pointer is on.
            this.snapping = false;
            this.vel = damp(this.vel, this.steer * STEER_MAX, 5, dt);
            this.angle += this.vel * dt;
            this.target = this.angle;
        } else if (reading) {
            // Being read, or about to be clicked: the nearest card settles square in front.
            if (!this.snapping) {
                this.snapping = true;
                this.target = Math.round(this.angle + this.vel * 0.25);
            }
            this.spring(dt);
        } else {
            // The slow, endless drift.
            driven = false;
            this.snapping = false;
            this.vel = damp(this.vel, AUTO_SPEED * this.driftDir, 1.6, dt);
            this.angle += this.vel * dt;
            this.target = this.angle;
        }

        const step = 360 / this.n;
        const rot = this.vertical ? this.angle * step : -this.angle * step;
        this.ring.style.setProperty('--rot', `${rot.toFixed(3)}deg`);

        const front = ((Math.round(this.angle) % this.n) + this.n) % this.n;
        if (front !== this.front) {
            if (this.front !== -1 && !this.going && driven) this.sound.tick(0);
            this.front = front;
            this.items.forEach((item, i) => item.classList.toggle('is-front', i === front));
            this.describe(front);
            this.settled = false;
        }
        if (!this.settled && driven && Math.abs(this.target - this.angle) < 0.02 && Math.abs(this.vel) < 0.05 && this.introT >= 1) {
            this.settled = true;
            if (!this.going) this.sound.lock(front);
        }

        this.items.forEach((item, i) => {
            const d = wrap(i - this.angle, this.n);
            const facing = Math.cos((d * step * Math.PI) / 180);
            const near = Math.max(0, 1 - Math.abs(d));
            // The far side stays visible, dimmed, so the loop reads as a loop.
            item.style.setProperty('--face', (0.26 + 0.74 * Math.pow(Math.max(0, (facing + 1) / 2), 1.8)).toFixed(3));
            item.style.setProperty('--glow', near.toFixed(3));
            item.style.setProperty('--lift', (1 + near * 0.1).toFixed(3));
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
        this.descTh.textContent = item.dataset.descTh || item.dataset.aboutTh || '';
        this.descEn.textContent = item.dataset.descEn || item.dataset.aboutEn || '';
        this.points.replaceChildren(
            ...(item.dataset.points || '')
                .split('|')
                .filter(Boolean)
                .map((text) => {
                    const li = document.createElement('li');
                    li.textContent = text;
                    return li;
                }),
        );
        this.go.href = item.getAttribute('href');
        const accent = getComputedStyle(item).getPropertyValue('--accent');
        this.root.style.setProperty('--xu-accent-now', accent);
        // Replay the entrance so each new card's story arrives, rather than swaps.
        this.info.classList.remove('is-new');
        void this.info.offsetWidth;
        this.info.classList.add('is-new');
    }
}
