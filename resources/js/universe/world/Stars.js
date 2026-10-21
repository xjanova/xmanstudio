import {
    AdditiveBlending,
    BufferAttribute,
    BufferGeometry,
    Color,
    Group,
    LineSegments,
    Points,
    ShaderMaterial,
    Vector3,
} from 'three';
import { TAU, clamp, gauss, rng } from '../lib/math.js';
import { routePolyline } from './anchors.js';

/**
 * Two layers of stars.
 *
 * Far stars ride along with the camera, so like the sky they never get
 * closer — but they twinkle, and the brightest wear diffraction spikes.
 *
 * Near dust is scattered along the route. It is what sells the speed: it
 * streams past as the camera flies, and at speed every mote stretches into
 * a streak along the direction of travel (the hyperspace look).
 */
const FAR = { high: 7000, mid: 4600, low: 2600 };
const NEAR = { high: 3200, mid: 2200, low: 1300 };

const STAR_TINTS = ['#cfe3ff', '#ffffff', '#fff1d6', '#d7c8ff', '#bff4ff', '#ffd6f4'];

export class Stars {
    constructor({ scene, tierName, pixelRatio }) {
        const rand = rng(7);
        this.group = new Group();
        scene.add(this.group);

        // ---- far layer -------------------------------------------------
        const nFar = FAR[tierName];
        const pos = new Float32Array(nFar * 3);
        const col = new Float32Array(nFar * 3);
        const size = new Float32Array(nFar);
        const phase = new Float32Array(nFar);
        const c = new Color();
        for (let i = 0; i < nFar; i++) {
            // Uniform on a sphere.
            const u = rand() * 2 - 1;
            const t = rand() * TAU;
            const s = Math.sqrt(1 - u * u);
            const r = 1800;
            pos[i * 3] = Math.cos(t) * s * r;
            pos[i * 3 + 1] = u * r;
            pos[i * 3 + 2] = Math.sin(t) * s * r;
            c.set(STAR_TINTS[Math.floor(rand() * STAR_TINTS.length)]);
            const bright = 0.35 + Math.pow(rand(), 3) * 1.4;
            col[i * 3] = c.r * bright;
            col[i * 3 + 1] = c.g * bright;
            col[i * 3 + 2] = c.b * bright;
            size[i] = 1.1 + Math.pow(rand(), 5) * 5.2;
            phase[i] = rand();
        }
        const farGeo = new BufferGeometry();
        farGeo.setAttribute('position', new BufferAttribute(pos, 3));
        farGeo.setAttribute('aColor', new BufferAttribute(col, 3));
        farGeo.setAttribute('aSize', new BufferAttribute(size, 1));
        farGeo.setAttribute('aPhase', new BufferAttribute(phase, 1));

        this.farMaterial = new ShaderMaterial({
            transparent: true,
            depthWrite: false,
            blending: AdditiveBlending,
            uniforms: {
                uTime: { value: 0 },
                uPixelRatio: { value: pixelRatio },
                uFade: { value: 1 },
            },
            vertexShader: /* glsl */ `
                uniform float uTime;
                uniform float uPixelRatio;
                attribute vec3 aColor;
                attribute float aSize;
                attribute float aPhase;
                varying vec3 vColor;
                varying float vSize;
                void main() {
                    vec4 mv = modelViewMatrix * vec4(position, 1.0);
                    gl_Position = projectionMatrix * mv;
                    float tw = 0.72 + 0.28 * sin(uTime * (0.7 + aPhase * 2.4) + aPhase * 40.0);
                    vColor = aColor * tw;
                    vSize = aSize;
                    gl_PointSize = aSize * uPixelRatio * (0.9 + 0.2 * tw);
                }
            `,
            fragmentShader: /* glsl */ `
                uniform float uFade;
                varying vec3 vColor;
                varying float vSize;
                void main() {
                    vec2 c = gl_PointCoord - 0.5;
                    float r = length(c);
                    float core = smoothstep(0.5, 0.0, r);
                    core *= core;
                    float spike = 0.0;
                    if (vSize > 3.2) {
                        spike = (max(0.0, 1.0 - abs(c.x) * 22.0) + max(0.0, 1.0 - abs(c.y) * 22.0))
                            * smoothstep(0.5, 0.05, r) * 0.55;
                    }
                    gl_FragColor = vec4(vColor * (core + spike) * uFade, 1.0);
                }
            `,
        });
        this.far = new Points(farGeo, this.farMaterial);
        this.far.frustumCulled = false;
        this.far.renderOrder = -10;
        this.group.add(this.far);

        // ---- near dust along the route ---------------------------------
        const nNear = NEAR[tierName];
        this.nearCount = nNear;
        const route = routePolyline();
        const seg = [];
        let total = 0;
        for (let i = 0; i < route.length - 1; i++) {
            const len = route[i].distanceTo(route[i + 1]);
            seg.push({ a: route[i], b: route[i + 1], len });
            total += len;
        }
        const dPos = new Float32Array(nNear * 3);
        const dCol = new Float32Array(nNear * 3);
        const dSize = new Float32Array(nNear);
        const tmp = new Vector3();
        const tints = ['#7dd3fc', '#c4b5fd', '#f0abfc', '#e0f2fe', '#ffffff'];
        for (let i = 0; i < nNear; i++) {
            let d = rand() * total;
            let k = 0;
            while (k < seg.length - 1 && d > seg[k].len) {
                d -= seg[k].len;
                k++;
            }
            tmp.lerpVectors(seg[k].a, seg[k].b, clamp(d / seg[k].len));
            const radius = 10 + Math.pow(rand(), 0.7) * 190;
            const ang = rand() * TAU;
            dPos[i * 3] = tmp.x + Math.cos(ang) * radius;
            dPos[i * 3 + 1] = tmp.y + Math.sin(ang) * radius * 0.7;
            dPos[i * 3 + 2] = tmp.z + gauss(rand) * 40;
            c.set(tints[Math.floor(rand() * tints.length)]);
            const b = 0.5 + rand() * 0.9;
            dCol[i * 3] = c.r * b;
            dCol[i * 3 + 1] = c.g * b;
            dCol[i * 3 + 2] = c.b * b;
            dSize[i] = 0.6 + Math.pow(rand(), 2) * 2.4;
        }

        this.dustUniforms = {
            uPixelRatio: { value: pixelRatio },
            uVel: { value: new Vector3() },
            uStreak: { value: 0 },
            uAlpha: { value: 1 },
        };

        const dustGeo = new BufferGeometry();
        dustGeo.setAttribute('position', new BufferAttribute(dPos, 3));
        dustGeo.setAttribute('aColor', new BufferAttribute(dCol, 3));
        dustGeo.setAttribute('aSize', new BufferAttribute(dSize, 1));
        this.dust = new Points(
            dustGeo,
            new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                uniforms: this.dustUniforms,
                vertexShader: /* glsl */ `
                    uniform float uPixelRatio;
                    uniform float uAlpha;
                    attribute vec3 aColor;
                    attribute float aSize;
                    varying vec3 vColor;
                    void main() {
                        vec4 mv = modelViewMatrix * vec4(position, 1.0);
                        gl_Position = projectionMatrix * mv;
                        float z = -mv.z;
                        // Fade in from the distance, and out right before hitting the lens.
                        float fade = smoothstep(900.0, 300.0, z) * smoothstep(1.5, 12.0, z);
                        vColor = aColor * fade * uAlpha;
                        gl_PointSize = clamp(aSize * uPixelRatio * (260.0 / z), 0.0, 40.0);
                    }
                `,
                fragmentShader: /* glsl */ `
                    varying vec3 vColor;
                    void main() {
                        float r = length(gl_PointCoord - 0.5);
                        float a = smoothstep(0.5, 0.0, r);
                        gl_FragColor = vec4(vColor * a * a, 1.0);
                    }
                `,
            }),
        );
        this.dust.frustumCulled = false;
        this.group.add(this.dust);

        // Streaks: every mote again as a line from where it is to where it was.
        const sPos = new Float32Array(nNear * 6);
        const sCol = new Float32Array(nNear * 6);
        const sEnd = new Float32Array(nNear * 2);
        for (let i = 0; i < nNear; i++) {
            for (let e = 0; e < 2; e++) {
                sPos.set([dPos[i * 3], dPos[i * 3 + 1], dPos[i * 3 + 2]], (i * 2 + e) * 3);
                sCol.set([dCol[i * 3], dCol[i * 3 + 1], dCol[i * 3 + 2]], (i * 2 + e) * 3);
                sEnd[i * 2 + e] = e;
            }
        }
        const streakGeo = new BufferGeometry();
        streakGeo.setAttribute('position', new BufferAttribute(sPos, 3));
        streakGeo.setAttribute('aColor', new BufferAttribute(sCol, 3));
        streakGeo.setAttribute('aEnd', new BufferAttribute(sEnd, 1));
        this.streaks = new LineSegments(
            streakGeo,
            new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                uniforms: this.dustUniforms,
                vertexShader: /* glsl */ `
                    uniform vec3 uVel;
                    uniform float uStreak;
                    attribute vec3 aColor;
                    attribute float aEnd;
                    varying vec3 vColor;
                    void main() {
                        vec3 p = position - uVel * aEnd * uStreak;
                        vec4 mv = modelViewMatrix * vec4(p, 1.0);
                        gl_Position = projectionMatrix * mv;
                        float z = -mv.z;
                        float fade = smoothstep(700.0, 200.0, z) * smoothstep(2.0, 16.0, z);
                        vColor = aColor * fade * (1.0 - aEnd) * 1.4;
                    }
                `,
                fragmentShader: /* glsl */ `
                    varying vec3 vColor;
                    void main() {
                        gl_FragColor = vec4(vColor, 1.0);
                    }
                `,
            }),
        );
        this.streaks.frustumCulled = false;
        this.streaks.visible = false;
        this.group.add(this.streaks);
    }

    setPixelRatio(pr) {
        this.farMaterial.uniforms.uPixelRatio.value = pr;
        this.dustUniforms.uPixelRatio.value = pr;
    }

    setDensity(d) {
        this.dust.geometry.setDrawRange(0, Math.floor(this.nearCount * d));
        this.streaks.geometry.setDrawRange(0, Math.floor(this.nearCount * d) * 2);
    }

    /**
     * @param {object} s frame state: time, camera, velocity (world units/s), warp (0..1)
     */
    update(s) {
        this.farMaterial.uniforms.uTime.value = s.time;
        this.far.position.copy(s.camera.position);

        const speed = s.velocity.length();
        // Trail length in seconds of travel; grows with speed and with a jump.
        const streak = clamp((speed - 60) / 900) * 0.09 + s.warp * 0.16;
        this.dustUniforms.uStreak.value = streak;
        this.dustUniforms.uVel.value.copy(s.velocity);
        this.streaks.visible = streak > 0.004;
        this.dustUniforms.uAlpha.value = 1 - clamp(streak * 3) * 0.5;
    }
}
