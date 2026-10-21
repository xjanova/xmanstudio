import { Vector3 } from 'three';
import { clamp, damp } from '../lib/math.js';

/**
 * The flight HUD: journey rail, coordinates, speed, the targeting reticle
 * around planets, the scroll hint and toasts.
 */
export class Hud {
    constructor({ journey, sound, onJump }) {
        this.journey = journey;
        this.sound = sound;
        this.rail = document.getElementById('xu-rail');
        this.coordsSector = document.getElementById('xu-coords-sector');
        this.coordsXyz = document.getElementById('xu-coords-xyz');
        this.coordsV = document.getElementById('xu-coords-v');
        this.reticle = document.getElementById('xu-reticle');
        this.reticleLabel = document.getElementById('xu-reticle-label');
        this.hint = document.getElementById('xu-scrollhint');
        this.toastEl = document.getElementById('xu-toast');
        this.soundBtn = document.getElementById('xu-sound');

        this.fill = document.createElement('span');
        this.fill.className = 'xu-rail__fill';
        this.rail.appendChild(this.fill);

        this.stops = journey.stations.map((st, i) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'xu-rail__stop';
            b.setAttribute('aria-label', `${String(i + 1).padStart(2, '0')} ${st.th} / ${st.en}`);
            b.innerHTML = `<span class="xu-rail__label"><b></b><small></small></span><span class="xu-rail__pip">${String(i + 1).padStart(2, '0')}</span>`;
            b.querySelector('b').textContent = st.th;
            b.querySelector('small').textContent = st.en;
            b.addEventListener('click', () => onJump(i));
            this.rail.appendChild(b);
            return b;
        });

        this.here = -1;
        this.lastCoords = 0;
        this.ret = { x: 0, y: 0, r: 60, o: 0 };
        this.tmp = new Vector3();
    }

    setSound(on) {
        const btn = this.soundBtn;
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        for (const el of btn.querySelectorAll('[data-on]')) el.textContent = on ? el.dataset.on : el.dataset.off;
        btn.setAttribute('aria-label', on ? 'ปิดเสียง / Sound off' : 'เปิดเสียง / Sound on');
    }

    toast(text, ms = 3200) {
        this.toastEl.textContent = text;
        this.toastEl.classList.add('is-on');
        clearTimeout(this.toastTimer);
        this.toastTimer = setTimeout(() => this.toastEl.classList.remove('is-on'), ms);
    }

    /**
     * @param {object} s frame state from main.js
     * @param {object|null} target reticle target: { pos, radius, label, accent } or null
     */
    update(s, target) {
        const j = this.journey;
        const here = j.current;
        if (here !== this.here) {
            this.here = here;
            this.stops.forEach((b, i) => {
                b.classList.toggle('is-here', i === here);
                b.classList.toggle('is-past', i < here);
                if (i === here) b.setAttribute('aria-current', 'step');
                else b.removeAttribute('aria-current');
            });
            this.coordsSector.textContent = `SECTOR ${String(here + 1).padStart(2, '0')} · ${j.stations[here].en.toUpperCase()}`;
        }
        this.fill.style.setProperty('--progress', clamp(j.u / Math.max(1, j.n - 1)).toFixed(4));
        this.hint.classList.toggle('is-on', s.entered && j.u < 0.06 && !s.menuOpen);

        // Coordinates: a few times a second is plenty, and cheaper.
        if (s.time - this.lastCoords > 0.12) {
            this.lastCoords = s.time;
            const p = s.camera.position;
            this.coordsXyz.textContent = `${p.x.toFixed(1)} · ${p.y.toFixed(1)} · ${p.z.toFixed(1)}`;
            this.coordsV.textContent = `v ${(s.velocity.length() / 1200).toFixed(2)}c`;
        }

        this.updateReticle(s, target);
    }

    updateReticle(s, target) {
        const r = this.ret;
        let goal = 0;
        if (target && target.opacity > 0.01) {
            const cam = s.camera;
            this.tmp.copy(target.pos).project(cam);
            if (this.tmp.z < 1) {
                const w = window.innerWidth;
                const h = window.innerHeight;
                const x = (this.tmp.x * 0.5 + 0.5) * w;
                const y = (-this.tmp.y * 0.5 + 0.5) * h;
                // Screen radius from the angular size.
                const dist = cam.position.distanceTo(target.pos);
                const ang = Math.asin(clamp(target.radius / dist, 0, 0.999));
                const px = (Math.tan(ang) / Math.tan((cam.fov * Math.PI) / 360)) * (h / 2);
                const k = r.o < 0.02 ? 1 : 1 - Math.exp(-14 * s.dt);
                r.x += (x - r.x) * k;
                r.y += (y - r.y) * k;
                r.r += (px * 1.28 + 18 - r.r) * k;
                goal = target.opacity;
                if (this.reticleLabel.textContent !== target.label) this.reticleLabel.textContent = target.label;
                this.reticle.style.setProperty('--accent', target.accent);
                this.reticle.classList.toggle('is-flip', x > w * 0.62);
            }
        }
        r.o = damp(r.o, goal, 8, s.dt);
        const size = Math.max(40, r.r * 2);
        this.reticle.style.opacity = r.o.toFixed(3);
        this.reticle.style.width = `${size}px`;
        this.reticle.style.height = `${size}px`;
        this.reticle.style.transform = `translate3d(${(r.x - size / 2).toFixed(1)}px, ${(r.y - size / 2).toFixed(1)}px, 0)`;
    }
}
