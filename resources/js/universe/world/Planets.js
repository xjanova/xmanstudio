import {
    AdditiveBlending,
    BackSide,
    BoxGeometry,
    Color,
    DoubleSide,
    Group,
    IcosahedronGeometry,
    InstancedMesh,
    Mesh,
    Object3D,
    RingGeometry,
    ShaderMaterial,
    TorusGeometry,
    Vector3,
} from 'three';
import { NOISE } from '../lib/glsl.js';
import { TAU, rng } from '../lib/math.js';
import { ANCHORS, PLANET_RADII } from './anchors.js';

/**
 * The platform planets — one world per platform, each with its own surface:
 *
 *   domains   an ocean world wrapped in a glowing latitude/longitude grid
 *   vps       machined teal bands with data pulses racing along the lanes
 *   xdreamer  a dreamy pink gas giant with a ring
 *   brainx    a violet sphere covered in a firing neural network
 *   metalx    molten rock whose cracks pulse to a beat, sound-wave rings
 *   academy   an ice world with polar caps and a ring
 *
 * plus `stack`, the amber gas giant of the tech belt (Belt.js).
 */
export const PLANET_STYLES = {
    domains: { style: 0, a: '#0b3a6e', b: '#1f8a8a', c: '#60a5fa', atmo: '#60a5fa' },
    vps: { style: 1, a: '#0c3b3a', b: '#1d6f6a', c: '#2dd4bf', atmo: '#2dd4bf' },
    xdreamer: { style: 2, a: '#7a1f63', b: '#f0a3d9', c: '#ffd0b0', atmo: '#f472b6', ring: '#f9a8d4' },
    brainx: { style: 3, a: '#1b0f3d', b: '#8b5cf6', c: '#e0d4ff', atmo: '#a78bfa' },
    metalx: { style: 4, a: '#1a0a0c', b: '#4a1a1f', c: '#ff5a3c', atmo: '#fb7185' },
    academy: { style: 5, a: '#8fb8d8', b: '#e6f2ff', c: '#38bdf8', atmo: '#7dd3fc', ring: '#bae6fd' },
    stack: { style: 6, a: '#6b3a12', b: '#e8b36a', c: '#22d3ee', atmo: '#ffd479' },
};

const PLANET_FRAG = /* glsl */ `
    ${NOISE}
    uniform float uTime;
    uniform float uStyle;
    uniform float uSeed;
    uniform vec3 uA;
    uniform vec3 uB;
    uniform vec3 uC;
    uniform vec3 uAtmo;
    uniform vec3 uLight;
    varying vec3 vObj;
    varying vec3 vNormalW;
    varying vec3 vViewW;

    float lineAt(float x, float sharp) {
        return pow(1.0 - abs(fract(x) - 0.5) * 2.0, sharp);
    }

    void main() {
        vec3 p = normalize(vObj);
        vec3 N = normalize(vNormalW);
        vec3 V = normalize(vViewW);
        vec3 L = normalize(uLight);
        float ndl = dot(N, L);
        float day = smoothstep(-0.2, 0.45, ndl);
        float lat = asin(clamp(p.y, -1.0, 1.0));
        float lon = atan(p.z, p.x);
        float t = uTime;

        vec3 base;
        vec3 emit = vec3(0.0);

        if (uStyle < 0.5) {
            // domains
            float land = smoothstep(0.5, 0.57, xuFbm(p * 2.6 + uSeed));
            base = mix(uA, uB, land) * (0.7 + 0.3 * xuFbm(p * 9.0 + uSeed));
            float grid = lineAt(lat * 3.8197, 60.0) + lineAt(lon * 1.9099, 60.0);
            float sweep = smoothstep(0.96, 1.0, sin(lon * 1.0 - t * 0.6) * 0.5 + 0.5);
            emit += uC * grid * (0.35 + 0.65 * (1.0 - day) + sweep * 1.5);
            float city = step(0.982, xuHash(floor(p * 110.0) + uSeed)) * land;
            emit += vec3(1.0, 0.82, 0.55) * city * (1.0 - day) * 2.4;
        } else if (uStyle < 1.5) {
            // vps
            float b = xuFbm(vec3(p.y * 9.0, p.y * 3.0, uSeed) + xuNoise(p * 3.0) * 0.6);
            base = mix(uA, uB, smoothstep(0.35, 0.7, b));
            float lane = floor(p.y * 16.0);
            float lanes = lineAt(p.y * 16.0, 30.0);
            float pulse = smoothstep(0.93, 1.0, fract(lon / 6.2831 * 5.0 - t * (0.15 + fract(lane * 0.37) * 0.3) + lane * 0.37));
            emit += uC * lanes * (0.2 + pulse * 3.0);
        } else if (uStyle < 2.5) {
            // xdreamer
            float w = xuFbm(p * 2.0 + vec3(0.0, t * 0.02, uSeed));
            float bands = max(0.0, sin(p.y * 15.0 + w * 5.5 + t * 0.05) * 0.5 + 0.5);
            base = mix(uA, uB, bands);
            base = mix(base, uC, smoothstep(0.62, 0.95, xuFbm(p * 5.0 + w + uSeed)) * 0.55);
            emit += uB * pow(bands, 6.0) * 0.12;
        } else if (uStyle < 3.5) {
            // brainx
            base = uA * (0.6 + 0.4 * xuFbm(p * 3.0 + uSeed));
            vec3 q = p * 4.6 + uSeed;
            vec3 cell = floor(q);
            vec3 f = fract(q);
            float d1 = 8.0;
            float d2 = 8.0;
            for (int x = -1; x <= 1; x++) {
                for (int y = -1; y <= 1; y++) {
                    for (int z = -1; z <= 1; z++) {
                        vec3 o = vec3(float(x), float(y), float(z));
                        vec3 h = vec3(xuHash(cell + o), xuHash(cell + o + 11.0), xuHash(cell + o + 23.0));
                        vec3 r = o + h - f;
                        float d = dot(r, r);
                        if (d < d1) { d2 = d1; d1 = d; } else if (d < d2) { d2 = d; }
                    }
                }
            }
            float e = sqrt(d2) - sqrt(d1);
            float edge = 1.0 - smoothstep(0.0, 0.07, e);
            float node = 1.0 - smoothstep(0.0, 0.13, sqrt(d1));
            float fire = max(0.0, 0.5 + 0.5 * sin(sqrt(d1) * 16.0 - t * 3.2 + xuHash(cell) * 6.2831));
            emit += uB * edge * (0.3 + pow(fire, 3.0) * 1.6) + uC * node * (1.0 + fire);
        } else if (uStyle < 4.5) {
            // metalx
            float ridge = 1.0 - abs(xuFbm(p * 3.2 + uSeed) * 2.0 - 1.0);
            float cracks = smoothstep(0.8, 0.97, ridge);
            base = mix(uA, uB, xuFbm(p * 6.0 + uSeed)) * 0.7;
            float beat = 0.55 + 0.45 * pow(max(0.0, 0.5 + 0.5 * sin(t * 4.4)), 10.0);
            emit += uC * cracks * 2.6 * beat + uC * pow(ridge, 6.0) * 0.4;
        } else if (uStyle < 5.5) {
            // academy
            float n = xuFbm(p * 4.0 + uSeed);
            base = mix(uA, uB, n);
            float ice = 1.0 - abs(xuFbm(p * 7.0 + 3.0) * 2.0 - 1.0);
            base += uC * smoothstep(0.88, 0.98, ice) * 0.6;
            base = mix(base, vec3(0.96, 0.98, 1.0), smoothstep(0.7, 0.9, abs(p.y)));
            float glint = step(0.994, xuHash(floor(p * 140.0) + uSeed));
            emit += uC * glint * (0.6 + 0.4 * sin(t * 3.0 + xuHash(floor(p * 140.0)) * 20.0));
        } else {
            // stack — banded amber giant with a storm
            float w = xuFbm(p * 1.6 + vec3(0.0, t * 0.015, uSeed));
            float bands = sin(p.y * 11.0 + w * 4.2) * 0.5 + 0.5;
            base = mix(uA, uB, bands);
            vec3 spot = normalize(vec3(0.6, -0.25, 0.75));
            float storm = smoothstep(0.24, 0.05, distance(p, spot) + (xuFbm(p * 8.0) - 0.5) * 0.1);
            base = mix(base, vec3(0.95, 0.55, 0.3), storm * 0.8);
            emit += uC * smoothstep(0.93, 1.0, abs(p.y)) * 0.25;
        }

        vec3 col = base * (0.03 + day * 0.78) + emit * 0.85;
        float fres = pow(1.0 - clamp(dot(N, V), 0.0, 1.0), 3.0);
        col += uAtmo * fres * (0.08 + day * 0.6);
        gl_FragColor = vec4(col, 1.0);
    }
`;

const PLANET_VERT = /* glsl */ `
    varying vec3 vObj;
    varying vec3 vNormalW;
    varying vec3 vViewW;
    void main() {
        vObj = position;
        vec4 world = modelMatrix * vec4(position, 1.0);
        vNormalW = normalize(mat3(modelMatrix) * normal);
        vViewW = cameraPosition - world.xyz;
        gl_Position = projectionMatrix * viewMatrix * world;
    }
`;

export function planetMaterial(key, { octaves = 5, seed = 1, light = new Vector3(0.6, 0.35, 0.7) } = {}) {
    const spec = PLANET_STYLES[key];
    return new ShaderMaterial({
        defines: { FBM_OCTAVES: octaves },
        uniforms: {
            uTime: { value: 0 },
            uStyle: { value: spec.style },
            uSeed: { value: seed },
            uA: { value: new Color(spec.a) },
            uB: { value: new Color(spec.b) },
            uC: { value: new Color(spec.c) },
            uAtmo: { value: new Color(spec.atmo) },
            uLight: { value: light.clone().normalize() },
        },
        vertexShader: PLANET_VERT,
        fragmentShader: PLANET_FRAG,
    });
}

export function atmosphere(color, radius, light) {
    return new Mesh(
        new IcosahedronGeometry(radius * 1.16, 16),
        new ShaderMaterial({
            side: BackSide,
            transparent: true,
            depthWrite: false,
            blending: AdditiveBlending,
            uniforms: { uColor: { value: new Color(color) }, uLight: { value: light.clone().normalize() } },
            vertexShader: /* glsl */ `
                varying vec3 vNormalV;
                varying vec3 vNormalW;
                void main() {
                    vNormalV = normalize(normalMatrix * normal);
                    vNormalW = normalize(mat3(modelMatrix) * normal);
                    gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
                }
            `,
            fragmentShader: /* glsl */ `
                uniform vec3 uColor;
                uniform vec3 uLight;
                varying vec3 vNormalV;
                varying vec3 vNormalW;
                void main() {
                    // Only the far half of this shell shows, in the band between the
                    // planet's limb (normal.z ≈ -0.5 for a 1.16× shell) and the shell's
                    // own silhouette (normal.z = 0): brightest at the limb, gone at the edge.
                    float k = clamp(-vNormalV.z / 0.5, 0.0, 1.0);
                    float rim = pow(k, 1.7);
                    float lit = 0.22 + 0.78 * smoothstep(-0.5, 0.55, dot(vNormalW, normalize(uLight)));
                    gl_FragColor = vec4(uColor * rim * lit * 0.8, 1.0);
                }
            `,
        }),
    );
}

function ringMaterial(color, inner, outer) {
    return new ShaderMaterial({
        side: DoubleSide,
        transparent: true,
        depthWrite: false,
        blending: AdditiveBlending,
        defines: { FBM_OCTAVES: 3 },
        uniforms: { uColor: { value: new Color(color) }, uInner: { value: inner }, uOuter: { value: outer } },
        vertexShader: /* glsl */ `
            varying vec2 vPos;
            void main() {
                vPos = position.xy;
                gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
            }
        `,
        fragmentShader: /* glsl */ `
            ${NOISE}
            uniform vec3 uColor;
            uniform float uInner;
            uniform float uOuter;
            varying vec2 vPos;
            void main() {
                float rr = (length(vPos) - uInner) / (uOuter - uInner);
                float bands = 0.45 + 0.55 * sin(rr * 70.0 + xuNoise(vec3(rr * 24.0, 0.0, 0.0)) * 5.0);
                float gaps = smoothstep(0.1, 0.3, xuNoise(vec3(rr * 9.0, 3.0, 0.0)));
                float a = bands * gaps * smoothstep(0.0, 0.06, rr) * smoothstep(1.0, 0.85, rr);
                gl_FragColor = vec4(uColor * a * 0.55, 1.0);
            }
        `,
    });
}

export class Planets {
    constructor({ scene, tierName }, keys) {
        this.planets = [];
        const octaves = tierName === 'low' ? 3 : tierName === 'mid' ? 4 : 5;
        const detail = tierName === 'low' ? 24 : 40;
        const rand = rng(41);

        keys.slice(0, ANCHORS.platforms.length).forEach((key, k) => {
            const spec = PLANET_STYLES[key] ? key : 'domains';
            const radius = PLANET_RADII[k];
            // Lit from the side that faces the middle of the screen: even planets
            // sit on the left, so their light comes from the right.
            const side = k % 2 === 0 ? -1 : 1;
            const light = new Vector3(-side * 0.72, 0.34, 0.6);

            const group = new Group();
            group.position.copy(ANCHORS.platforms[k]);
            scene.add(group);

            const body = new Mesh(new IcosahedronGeometry(radius, detail), planetMaterial(spec, { octaves, seed: 3 + k * 7.3, light }));
            body.rotation.z = (rand() - 0.5) * 0.5;
            group.add(body);
            group.add(atmosphere(PLANET_STYLES[spec].atmo, radius, light));

            const planet = { key: spec, group, body, radius, extras: [] };

            if (PLANET_STYLES[spec].ring) {
                const inner = radius * 1.45;
                const outer = radius * 2.35;
                const ring = new Mesh(new RingGeometry(inner, outer, 160, 1), ringMaterial(PLANET_STYLES[spec].ring, inner, outer));
                ring.rotation.x = -Math.PI / 2 + 0.42;
                ring.rotation.y = side * 0.25;
                group.add(ring);
            }

            if (spec === 'vps') {
                // A ring of server blades in orbit.
                const n = 42;
                const blades = new InstancedMesh(
                    new BoxGeometry(1.6, 0.55, 2.4),
                    new ShaderMaterial({
                        uniforms: { uColor: { value: new Color('#2dd4bf') } },
                        vertexShader: /* glsl */ `
                            varying vec2 vUv;
                            void main() {
                                vUv = uv;
                                gl_Position = projectionMatrix * viewMatrix * modelMatrix * instanceMatrix * vec4(position, 1.0);
                            }
                        `,
                        fragmentShader: /* glsl */ `
                            uniform vec3 uColor;
                            varying vec2 vUv;
                            void main() {
                                float e = min(min(vUv.x, 1.0 - vUv.x), min(vUv.y, 1.0 - vUv.y));
                                float edge = smoothstep(0.12, 0.0, e);
                                gl_FragColor = vec4(vec3(0.02, 0.05, 0.07) + uColor * edge * 2.2, 1.0);
                            }
                        `,
                    }),
                    n,
                );
                const dummy = new Object3D();
                for (let i = 0; i < n; i++) {
                    const a = (i / n) * TAU;
                    const r = radius * 1.75 + (rand() - 0.5) * 2;
                    dummy.position.set(Math.cos(a) * r, (rand() - 0.5) * 1.5, Math.sin(a) * r);
                    dummy.rotation.set(0, -a, 0);
                    dummy.updateMatrix();
                    blades.setMatrixAt(i, dummy.matrix);
                }
                const orbit = new Group();
                orbit.rotation.set(0.35, 0, side * 0.2);
                orbit.add(blades);
                group.add(orbit);
                planet.extras.push({ kind: 'spin', obj: orbit, speed: 0.12 });
            }

            if (spec === 'metalx') {
                // Sound waves: rings that swell out of the equator on the beat.
                for (let i = 0; i < 3; i++) {
                    const ring = new Mesh(
                        new TorusGeometry(radius * 1.2, 0.14, 6, 160),
                        new ShaderMaterial({
                            transparent: true,
                            depthWrite: false,
                            blending: AdditiveBlending,
                            uniforms: { uColor: { value: new Color('#fb7185') }, uAlpha: { value: 1 } },
                            vertexShader: /* glsl */ `
                                void main() {
                                    gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
                                }
                            `,
                            fragmentShader: /* glsl */ `
                                uniform vec3 uColor;
                                uniform float uAlpha;
                                void main() {
                                    gl_FragColor = vec4(uColor * 2.4 * uAlpha, 1.0);
                                }
                            `,
                        }),
                    );
                    ring.rotation.x = Math.PI / 2 + 0.3;
                    group.add(ring);
                    planet.extras.push({ kind: 'wave', obj: ring, offset: i / 3 });
                }
            }

            if (spec === 'domains') {
                // Two small moons.
                for (let i = 0; i < 2; i++) {
                    const moon = new Mesh(new IcosahedronGeometry(radius * (0.12 + i * 0.05), 8), planetMaterial('academy', { octaves: 3, seed: 90 + i, light }));
                    group.add(moon);
                    planet.extras.push({ kind: 'moon', obj: moon, r: radius * (1.9 + i * 0.6), speed: 0.25 - i * 0.08, phase: i * 2.2, tilt: 0.3 + i * 0.25 });
                }
            }

            this.planets.push(planet);
        });
    }

    /** World position and radius of planet k, for the HUD reticle. */
    target(k) {
        const planet = this.planets[k];
        return planet ? { pos: planet.group.position, radius: planet.radius } : null;
    }

    update(s, visibleFrom, visibleTo) {
        const t = s.time;
        this.planets.forEach((planet, k) => {
            // Only the planets near the camera cost anything.
            const near = s.u > visibleFrom - 0.6 && s.u < visibleTo + 0.4;
            planet.group.visible = near && planet.group.position.distanceTo(s.camera.position) < 900;
            if (!planet.group.visible) return;

            planet.body.material.uniforms.uTime.value = t;
            planet.body.rotation.y = t * 0.04 + k;
            for (const extra of planet.extras) {
                if (extra.kind === 'spin') {
                    extra.obj.rotation.y = t * extra.speed;
                } else if (extra.kind === 'wave') {
                    const phase = (t * 0.45 + extra.offset) % 1;
                    extra.obj.scale.setScalar(1 + phase * 1.6);
                    extra.obj.material.uniforms.uAlpha.value = Math.pow(1 - phase, 2);
                } else if (extra.kind === 'moon') {
                    const a = t * extra.speed + extra.phase;
                    extra.obj.position.set(Math.cos(a) * extra.r, Math.sin(a) * extra.r * extra.tilt, Math.sin(a) * extra.r);
                    extra.obj.material.uniforms.uTime.value = t;
                }
            }
        });
    }
}
