import { Vector3 } from 'three';
import { DEG } from '../lib/math.js';
import { ANCHORS, PLANET_RADII } from '../world/anchors.js';

/**
 * The flight plan: camera keyframes for every stop, in "journey units" u —
 * stop index + progress through that stop (0 → 1). Journey.js threads a
 * Catmull-Rom spline through them, so the camera glides between keyframes
 * instead of cutting.
 *
 * Keyframes are written the way a cinematographer would ask for a shot:
 * look at this target, from this far, from this angle, and put the target
 * here on screen (sx, sy in -1..1). Portrait screens get their own shots —
 * a landscape composition would push a planet off the side of a phone.
 */

const UP = new Vector3(0, 1, 0);

/** Where an object with angular composition (sx, sy) puts the camera and its aim point. */
export function shot(target, { dist, az = 0, el = 0, sx = 0, sy = 0 }, fov, aspect) {
    const a = az * DEG;
    const e = el * DEG;
    const pos = new Vector3(Math.sin(a) * Math.cos(e), Math.sin(e), Math.cos(a) * Math.cos(e))
        .multiplyScalar(dist)
        .add(target);

    const forward = target.clone().sub(pos).normalize();
    const right = new Vector3().crossVectors(forward, UP).normalize();
    const up = new Vector3().crossVectors(right, forward).normalize();
    const halfH = Math.tan((fov * DEG) / 2) * dist;
    const halfW = halfH * aspect;

    // Aim beside the target so the target itself lands at (sx, sy).
    const look = target
        .clone()
        .addScaledVector(right, -sx * halfW)
        .addScaledVector(up, -sy * halfH);

    return { pos, look };
}

/** How far into each stop its content is fully shown — where the rail and the menu jump to. */
export const DWELL = {
    core: 0,
    origin: 0.16,
    services: 0.42,
    products: 0.3,
    platforms: 0.06,
    stack: 0.5,
    reviews: 0.3,
    launch: 0.55,
    control: 0.62,
};

export function buildKeyframes(stations, { aspect, portrait }) {
    const P = portrait;
    const fov = P ? 64 : 55;
    const keys = [];
    const add = (u, target, params, f = fov) => keys.push({ u, fov: f, ...shot(target, params, f, aspect) });

    stations.forEach((st, i) => {
        const at = (p) => i + p;

        switch (st.type) {
            case 'core': {
                const c = ANCHORS.core;
                add(at(0), c, { dist: P ? 100 : 68, az: 0, el: 3, sy: P ? 0.34 : 0.26 });
                add(at(0.38), c, { dist: P ? 86 : 56, az: 18, el: 7, sx: P ? 0 : -0.2, sy: P ? 0.26 : 0.1 });
                add(at(0.78), c, { dist: 40, az: 64, el: 10, sx: 0, sy: 0 }, fov + 6);
                break;
            }

            case 'origin': {
                const o = ANCHORS.origin;
                const d = P ? 150 : 104;
                add(at(0), o, { dist: 250, az: -14, el: 9, sy: 0.12 });
                add(at(0.13), o, { dist: d, az: -4, el: 2, sy: P ? 0.2 : 0.14 });
                add(at(0.7), o, { dist: d - 10, az: 6, el: -1, sy: P ? 0.2 : 0.14 });
                add(at(0.82), o, { dist: d * 0.9, az: 22, el: 2, sx: P ? 0 : 0.44, sy: P ? 0.36 : 0 });
                add(at(0.95), o, { dist: d * 0.84, az: 30, el: 3, sx: P ? 0 : 0.44, sy: P ? 0.36 : 0 });
                break;
            }

            case 'services': {
                const g = ANCHORS.services;
                add(at(0), g, { dist: P ? 420 : 330, az: 10, el: 36, sy: 0.12 });
                add(at(0.2), g, { dist: P ? 360 : 250, az: -6, el: 31, sy: 0.02 });
                add(at(0.56), g, { dist: P ? 340 : 225, az: -26, el: 27 });
                add(at(0.9), g, { dist: P ? 320 : 210, az: -46, el: 22 });
                break;
            }

            case 'products': {
                const n = ANCHORS.products;
                add(at(0), n, { dist: 250, az: 12, el: 7 });
                add(at(0.22), n, { dist: P ? 200 : 165, az: 4, el: 3, sy: -0.06 });
                add(at(0.88), n, { dist: P ? 180 : 135, az: -16, el: 1, sy: -0.06 });
                break;
            }

            case 'platforms': {
                const count = Math.max(1, Math.min(st.count || ANCHORS.platforms.length, ANCHORS.platforms.length));
                for (let k = 0; k < count; k++) {
                    const planet = ANCHORS.platforms[k];
                    const r = PLANET_RADII[k];
                    // Even planets sit on the left of the screen (their card is on the right).
                    const side = k % 2 === 0 ? -1 : 1;
                    const sx = P ? 0 : side * 0.46;
                    const sy = P ? 0.34 : 0.02;
                    add(at((k + 0.04) / count), planet, { dist: r * 7.5, az: -side * 4, el: 5, sx, sy: P ? 0.34 : 0 });
                    add(at((k + 0.36) / count), planet, { dist: r * (P ? 4.7 : 3.5), az: -side * 14, el: 5, sx, sy });
                    add(at((k + 0.72) / count), planet, { dist: r * (P ? 4.5 : 3.3), az: -side * 24, el: 7, sx, sy });
                }
                break;
            }

            case 'stack': {
                const b = ANCHORS.stack;
                add(at(0), b, { dist: P ? 470 : 360, az: 0, el: 15, sy: 0 });
                add(at(0.34), b, { dist: P ? 380 : 270, az: -18, el: 9, sy: P ? -0.24 : -0.16 });
                add(at(0.7), b, { dist: 150, az: -42, el: 2.2 }, fov + 8);
                add(at(0.96), b, { dist: 215, az: -66, el: -8 });
                break;
            }

            case 'reviews': {
                const q = ANCHORS.reviews;
                add(at(0), q, { dist: 230, az: 12, el: 8, sy: 0.1 });
                add(at(0.22), q, { dist: 160, az: 2, el: 6, sy: P ? 0.38 : 0.26 });
                add(at(0.92), q, { dist: 135, az: -18, el: 4, sy: P ? 0.38 : 0.26 });
                break;
            }

            case 'launch': {
                const w = ANCHORS.launch;
                add(at(0), w, { dist: 320, az: 0, el: 5 });
                add(at(0.32), w, { dist: 200, az: 0, el: 1.5 });
                add(at(0.96), w, { dist: 105, az: 0, el: 0 });
                break;
            }

            case 'control': {
                const w = ANCHORS.launch;
                add(at(0.02), w, { dist: 46, az: 0, el: 0 }, fov + 4);
                // Inside the throat: aim down the tunnel.
                keys.push({ u: at(0.5), fov: fov + 6, pos: w.clone().add(new Vector3(0, 0, -40)), look: w.clone().add(new Vector3(0, 0, -320)) });
                keys.push({ u: at(1), fov: fov + 8, pos: w.clone().add(new Vector3(0, 0, -120)), look: w.clone().add(new Vector3(0, 0, -440)) });
                break;
            }

            default:
                break;
        }
    });

    keys.sort((a, b) => a.u - b.u);
    return keys;
}

/** The shot the intro flight ends on, seen from the portal side. */
export function introStart() {
    return {
        pos: new Vector3(0, 3, ANCHORS.portal.z + 210),
        look: new Vector3(0, 0, ANCHORS.portal.z - 200),
    };
}
