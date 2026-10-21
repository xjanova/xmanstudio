import { clamp } from '../lib/math.js';

/**
 * The boot screen: a progress meter and a short log of what is being built,
 * then the gate — "enter with sound", "enter without", or just scroll.
 */
const STEPS = {
    link: { at: 0.08, th: 'เชื่อมต่อจักรวาล XMAN', en: 'linking to the XMAN universe' },
    engine: { at: 0.2, th: 'ติดตั้งกล้องและเลนส์', en: 'mounting camera and lenses' },
    sky: { at: 0.34, th: 'วาดแผนที่ดวงดาว', en: 'charting the stars' },
    core: { at: 0.5, th: 'จุดประกายดาว XMAN', en: 'igniting the XMAN core' },
    worlds: { at: 0.7, th: 'ปั้นกาแล็กซีและดาวเคราะห์', en: 'forming galaxies and planets' },
    compile: { at: 0.88, th: 'คอมไพล์แสงและเงา', en: 'compiling light' },
    ready: { at: 1, th: 'พร้อมออกเดินทาง', en: 'ready for launch' },
};

export class Loader {
    constructor(root) {
        this.root = root;
        this.bar = root.querySelector('#xu-loader-bar');
        this.pct = root.querySelector('#xu-loader-pct');
        this.stepEl = root.querySelector('#xu-loader-step');
        this.log = root.querySelector('#xu-loader-log');
        this.boot = root.querySelector('#xu-loader-boot');
        this.gateEl = root.querySelector('#xu-loader-gate');
        this.target = 0;
        this.shown = 0;
        this.lastLine = null;
        this.raf = requestAnimationFrame(() => this.animate());
    }

    step(key) {
        const s = STEPS[key];
        if (!s) return;
        this.target = Math.max(this.target, s.at);
        if (this.lastLine) this.lastLine.classList.add('is-ok');
        const li = document.createElement('li');
        li.textContent = `${s.th} · ${s.en}`;
        this.log.appendChild(li);
        this.lastLine = li;
        this.stepEl.textContent = s.th;
    }

    animate() {
        this.shown += (this.target - this.shown) * 0.12;
        if (Math.abs(this.target - this.shown) < 0.002) this.shown = this.target;
        const v = clamp(this.shown);
        this.root.style.setProperty('--load', v.toFixed(4));
        this.bar.style.setProperty('--load', v.toFixed(4));
        this.pct.textContent = `${Math.round(v * 100)}%`;
        if (!this.stopped) this.raf = requestAnimationFrame(() => this.animate());
    }

    /**
     * Show the gate and wait for the visitor. Resolves with { sound } — true
     * for "enter with sound", false for the quiet button or a scroll.
     */
    gate() {
        if (this.lastLine) this.lastLine.classList.add('is-ok');
        this.boot.classList.add('is-done');
        this.gateEl.hidden = false;
        const enterBtn = this.gateEl.querySelector('[data-xu-enter="sound"]');
        setTimeout(() => enterBtn?.focus({ preventScroll: true }), 60);

        return new Promise((resolve) => {
            let done = false;
            const finish = (sound) => {
                if (done) return;
                done = true;
                cleanup();
                resolve({ sound });
            };
            const onClick = (e) => {
                const btn = e.target.closest('[data-xu-enter]');
                if (btn) finish(btn.dataset.xuEnter === 'sound');
            };
            const onWheel = () => finish(false);
            const onKey = (e) => {
                if (e.key === 'ArrowDown' || e.key === 'PageDown' || e.key === ' ') {
                    // Space on a focused button already clicks it.
                    if (e.key === ' ' && e.target.closest?.('[data-xu-enter]')) return;
                    e.preventDefault();
                    finish(false);
                }
            };
            let touchY = null;
            const onTouchStart = (e) => {
                touchY = e.touches[0]?.clientY ?? null;
            };
            const onTouchMove = (e) => {
                if (touchY !== null && Math.abs((e.touches[0]?.clientY ?? touchY) - touchY) > 30) finish(false);
            };
            const cleanup = () => {
                this.root.removeEventListener('click', onClick);
                window.removeEventListener('wheel', onWheel);
                window.removeEventListener('keydown', onKey);
                window.removeEventListener('touchstart', onTouchStart);
                window.removeEventListener('touchmove', onTouchMove);
            };
            this.root.addEventListener('click', onClick);
            window.addEventListener('wheel', onWheel, { passive: true });
            window.addEventListener('keydown', onKey);
            window.addEventListener('touchstart', onTouchStart, { passive: true });
            window.addEventListener('touchmove', onTouchMove, { passive: true });
        });
    }

    /** The emblem grows into the portal while the screen opens onto the scene. */
    leave() {
        this.root.classList.add('is-leaving');
        this.root.classList.add('is-gone');
        setTimeout(() => {
            this.stopped = true;
            cancelAnimationFrame(this.raf);
            this.root.hidden = true;
        }, 1300);
    }
}
