import { clamp } from '../lib/math.js';

/**
 * Pointer polish for the overlays: cards tilt toward the pointer with a glare
 * that follows it, primary buttons lean toward the cursor, and every link or
 * button gives a soft hover blip panned to where it sits on screen.
 * Mouse and pen only — touch has no hover to follow.
 */
export function bindPointerPolish(root, sound) {
    const fine = window.matchMedia('(pointer: fine)').matches;

    // Hover sounds, one listener for the whole page.
    let lastEl = null;
    root.addEventListener('pointerover', (e) => {
        const el = e.target.closest?.('a[href], button');
        if (!el || el === lastEl || el.closest('#xu-menu')) return;
        lastEl = el;
        const r = el.getBoundingClientRect();
        const pan = clamp(((r.left + r.width / 2) / window.innerWidth) * 2 - 1, -1, 1) * 0.7;
        const pitch = Math.round((1 - (r.top + r.height / 2) / window.innerHeight) * 9);
        sound.hover(pan, pitch);
    });
    root.addEventListener('pointerout', (e) => {
        if (lastEl && !lastEl.contains(e.relatedTarget)) lastEl = null;
    });
    root.addEventListener('click', (e) => {
        if (e.target.closest?.('a[href], button') && !e.target.closest('#xu-menu')) sound.click();
    });

    if (!fine) return;

    // Card tilt + glare.
    root.addEventListener('pointermove', (e) => {
        const card = e.target.closest?.('.xu-live .xu-card');
        if (!card) return;
        const r = card.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width;
        const y = (e.clientY - r.top) / r.height;
        card.style.setProperty('--gx', `${(x * 100).toFixed(1)}%`);
        card.style.setProperty('--gy', `${(y * 100).toFixed(1)}%`);
        const ax = (0.5 - y) * 2;
        const ay = (x - 0.5) * 2;
        card.style.rotate = `${ax.toFixed(3)} ${ay.toFixed(3)} 0 ${(Math.hypot(ax, ay) * 5).toFixed(2)}deg`;
    });
    // pointerleave does not bubble; a capturing listener still sees it for every element.
    root.addEventListener(
        'pointerleave',
        (e) => {
            if (e.target.matches?.('.xu-card')) e.target.style.rotate = '';
        },
        true,
    );

    // Magnetic primary buttons.
    root.addEventListener('pointermove', (e) => {
        const btn = e.target.closest?.('.xu-live .xu-btn--primary, .xu-enter');
        if (!btn) return;
        const r = btn.getBoundingClientRect();
        const dx = e.clientX - (r.left + r.width / 2);
        const dy = e.clientY - (r.top + r.height / 2);
        btn.style.translate = `${(dx * 0.14).toFixed(1)}px ${(dy * 0.22).toFixed(1)}px`;
    });
    root.addEventListener(
        'pointerleave',
        (e) => {
            if (e.target.matches?.('.xu-btn--primary, .xu-enter')) e.target.style.translate = '';
        },
        true,
    );
}
