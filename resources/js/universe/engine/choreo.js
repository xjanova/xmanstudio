import { clamp, smoothstep, window4 } from '../lib/math.js';

/**
 * Timing shared by the 3D world and the HTML overlays, as functions of a
 * stop's progress p (0 → 1). Keeping it in one place is what makes a number
 * of stars finish forming at the very moment its label arrives.
 */

// [in-start, in-end, out-start, out-end] for stops whose content simply
// arrives, stays, and leaves.
export const WINDOWS = {
    core: [-1, -0.5, 0.26, 0.56],
    services: [0.03, 0.26, 0.82, 0.98],
    products: [0.03, 0.24, 0.84, 0.98],
    stack: [0.05, 0.28, 0.82, 0.98],
    reviews: [0.04, 0.22, 0.88, 0.99],
    launch: [0.06, 0.3, 0.92, 1.12],
    control: [0.12, 0.42, 9, 10],
    origin: [0.02, 0.12, 0.97, 1.04],
    platforms: [0, 0.02, 0.985, 1.02],
};

export function reveal(type, p) {
    const [a, b, c, d] = WINDOWS[type] || WINDOWS.services;
    return {
        vin: smoothstep(a, b, p),
        vout: smoothstep(c, d, p),
        mounted: p > a - 0.4 && p < d + 0.05,
    };
}

const SLOT = 0.145; // share of the stop each number holds
const FIRST = 0.13;

/** The origin stop: four numbers drawn in stars, then why-us beside the XMAN mark. */
export function origin(p) {
    const s = clamp((p - FIRST) / SLOT, 0, 3.999);
    let state;
    if (p < FIRST) {
        state = smoothstep(-0.12, FIRST - 0.01, p);
    } else if (p < FIRST + SLOT * 4) {
        const k = Math.floor(s);
        state = 1 + k + (k < 3 ? smoothstep(0.62, 1, s - k) : 0);
    } else {
        state = 4 + smoothstep(FIRST + SLOT * 4, FIRST + SLOT * 4 + 0.09, p);
    }

    const statsEnd = FIRST + SLOT * 4;
    return {
        state,
        alpha: window4(p, -0.35, 0.02, 0.99, 1.2),
        stats: window4(p, 0.04, 0.12, statsEnd - 0.02, statsEnd + 0.04),
        label: (k) =>
            k < 3
                ? smoothstep(-0.2, 0, s - k) * (1 - smoothstep(0.62, 0.8, s - k))
                : smoothstep(-0.2, 0, s - 3) * (1 - smoothstep(statsEnd - 0.02, statsEnd + 0.03, p)),
        fill: (k) => clamp(s - k),
        why: window4(p, statsEnd + 0.05, statsEnd + 0.15, 0.97, 1.03),
    };
}

/** The platform stop: n planets, one card at a time. */
export function platforms(p, n) {
    const q = p * n;
    return {
        q,
        head: window4(q, -0.25, 0.12, 0.6, 0.85),
        card: (k) => window4(q - k, 0.14, 0.32, 0.8, 0.96),
        focus: clamp(Math.floor(q), 0, n - 1),
    };
}

/** The reviews deck: which card is on top (fractional). */
export function deck(p, n) {
    return smoothstep(0.1, 0.9, p) * Math.max(0, n - 1);
}
