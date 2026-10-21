// Small numeric helpers shared by the universe modules.

export const DEG = Math.PI / 180;
export const TAU = Math.PI * 2;

export const clamp = (v, lo = 0, hi = 1) => (v < lo ? lo : v > hi ? hi : v);
export const lerp = (a, b, t) => a + (b - a) * t;
export const invLerp = (a, b, v) => clamp((v - a) / (b - a));

export function smoothstep(e0, e1, x) {
    const t = clamp((x - e0) / (e1 - e0));
    return t * t * (3 - 2 * t);
}

/** Frame-rate independent exponential approach of `a` toward `b`. */
export const damp = (a, b, lambda, dt) => lerp(a, b, 1 - Math.exp(-lambda * dt));

export const easeOutCubic = (t) => 1 - Math.pow(1 - t, 3);
export const easeInOutCubic = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);
export const easeInOutSine = (t) => -(Math.cos(Math.PI * t) - 1) / 2;
export const easeOutExpo = (t) => (t >= 1 ? 1 : 1 - Math.pow(2, -10 * t));

/** Rises 0 → 1 over [a, b] and falls back 1 → 0 over [c, d]. */
export const window4 = (x, a, b, c, d) => smoothstep(a, b, x) * (1 - smoothstep(c, d, x));

/** Wrap x into [-n/2, n/2) — the shortest signed distance around a ring of n. */
export function wrap(x, n) {
    let r = ((x % n) + n) % n;
    if (r >= n / 2) r -= n;
    return r;
}

/** Deterministic pseudo-random numbers, so the universe looks the same on every visit. */
export function rng(seed = 1) {
    let s = seed >>> 0;
    return () => {
        s = (s + 0x6d2b79f5) >>> 0;
        let t = s;
        t = Math.imul(t ^ (t >>> 15), t | 1);
        t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
}

/** Standard normal sample (Box–Muller) from a uniform source. */
export function gauss(rand) {
    let u = 0;
    let v = 0;
    while (u === 0) u = rand();
    while (v === 0) v = rand();
    return Math.sqrt(-2 * Math.log(u)) * Math.cos(TAU * v);
}

export const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
export const nextFrame = () => new Promise((resolve) => requestAnimationFrame(() => resolve()));
