import { AdditiveBlending, BufferAttribute, BufferGeometry, Color, Group, Mesh, PlaneGeometry, Points, ShaderMaterial } from 'three';
import { BILLBOARD_VERT, GLOW_FRAG } from '../lib/glsl.js';
import { TAU, damp, gauss, rng } from '../lib/math.js';
import { ANCHORS } from './anchors.js';

/**
 * The service galaxy: a four-armed spiral that turns the way real ones do —
 * the core faster than the rim — with one beacon on its arms per service.
 * A beacon flares while its card is hovered or focused.
 */
const COUNT = { high: 46000, mid: 26000, low: 13000 };
const RADIUS = 190;
const ARMS = 4;
const TWIST = 0.021;

export class Galaxy {
    constructor({ scene, tierName, pixelRatio }, accents = []) {
        this.group = new Group();
        this.group.position.copy(ANCHORS.services);
        this.group.rotation.set(0.38, 0, -0.16);
        scene.add(this.group);

        const count = COUNT[tierName];
        this.count = count;
        const rand = rng(23);

        const radius = new Float32Array(count);
        const theta = new Float32Array(count);
        const height = new Float32Array(count);
        const color = new Float32Array(count * 3);
        const size = new Float32Array(count);

        const core = new Color('#fff1d6');
        const inner = new Color('#ffd479');
        const cyan = new Color('#22d3ee');
        const violet = new Color('#8b5cf6');
        const magenta = new Color('#e879f9');
        const c = new Color();

        for (let i = 0; i < count; i++) {
            const t = Math.pow(rand(), 1.7);
            const r = t * RADIUS + 1.5;
            const arm = Math.floor(rand() * ARMS);
            const spread = gauss(rand) * (0.18 + 0.55 * (1 - t)) * (rand() < 0.12 ? 3 : 1);
            radius[i] = r;
            theta[i] = (arm / ARMS) * TAU + r * TWIST + spread;
            height[i] = gauss(rand) * (1.5 + 9 * Math.pow(1 - t, 2.5));

            if (t < 0.08) c.copy(core).lerp(inner, t / 0.08);
            else if (t < 0.35) c.copy(inner).lerp(cyan, (t - 0.08) / 0.27);
            else if (t < 0.7) c.copy(cyan).lerp(violet, (t - 0.35) / 0.35);
            else c.copy(violet).lerp(magenta, (t - 0.7) / 0.3);
            c.offsetHSL((rand() - 0.5) * 0.06, 0, (rand() - 0.5) * 0.1);
            const b = 0.38 + rand() * 0.5 + (t < 0.1 ? 0.35 : 0);
            color[i * 3] = c.r * b;
            color[i * 3 + 1] = c.g * b;
            color[i * 3 + 2] = c.b * b;
            size[i] = 0.6 + Math.pow(rand(), 4) * 3.2;
        }

        const geometry = new BufferGeometry();
        geometry.setAttribute('position', new BufferAttribute(new Float32Array(count * 3), 3));
        geometry.setAttribute('aRadius', new BufferAttribute(radius, 1));
        geometry.setAttribute('aTheta', new BufferAttribute(theta, 1));
        geometry.setAttribute('aHeight', new BufferAttribute(height, 1));
        geometry.setAttribute('aColor', new BufferAttribute(color, 3));
        geometry.setAttribute('aSize', new BufferAttribute(size, 1));

        this.uniforms = {
            uTime: { value: 0 },
            uPixelRatio: { value: pixelRatio },
            uAlpha: { value: 1 },
        };

        this.points = new Points(
            geometry,
            new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                uniforms: this.uniforms,
                vertexShader: /* glsl */ `
                    uniform float uTime;
                    uniform float uPixelRatio;
                    attribute float aRadius;
                    attribute float aTheta;
                    attribute float aHeight;
                    attribute vec3 aColor;
                    attribute float aSize;
                    varying vec3 vColor;
                    void main() {
                        // Differential rotation: inner stars lap the outer ones.
                        float th = aTheta + uTime * 0.05 / (0.25 + aRadius / ${RADIUS.toFixed(1)});
                        vec3 p = vec3(cos(th) * aRadius, aHeight, sin(th) * aRadius);
                        vec4 mv = modelViewMatrix * vec4(p, 1.0);
                        gl_Position = projectionMatrix * mv;
                        vColor = aColor;
                        gl_PointSize = clamp(aSize * uPixelRatio * (300.0 / -mv.z), 0.0, 24.0);
                    }
                `,
                fragmentShader: /* glsl */ `
                    uniform float uAlpha;
                    varying vec3 vColor;
                    void main() {
                        float r = length(gl_PointCoord - 0.5);
                        float a = smoothstep(0.5, 0.0, r);
                        gl_FragColor = vec4(vColor * a * a * uAlpha, 1.0);
                    }
                `,
            }),
        );
        this.points.frustumCulled = false;
        this.group.add(this.points);

        // The bright bulge at the centre.
        this.bulge = this.glow('#ffd9a0', 130, 0.42, 5.0);
        this.group.add(this.bulge);

        // ---- beacons, one per service card -------------------------------
        this.beacons = [];
        const brand = accents.length ? accents : ['#22d3ee', '#38bdf8', '#34d399', '#e879f9', '#fb923c', '#fb7185', '#8b5cf6', '#0ea5e9'];
        brand.forEach((accent, k) => {
            const r = 42 + (k / Math.max(1, brand.length - 1)) * 118;
            const arm = k % ARMS;
            const beacon = {
                r,
                theta: (arm / ARMS) * TAU + r * TWIST,
                hot: 0,
                target: 0,
                mesh: this.glow(accent, 22, 1.3, 3.6),
                ring: this.ringGlow(accent),
            };
            this.group.add(beacon.mesh, beacon.ring);
            this.beacons.push(beacon);
        });
    }

    glow(color, size, intensity, falloff) {
        const mesh = new Mesh(
            new PlaneGeometry(1, 1),
            new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                uniforms: {
                    uSize: { value: size },
                    uColor: { value: new Color(color) },
                    uIntensity: { value: intensity },
                    uFalloff: { value: falloff },
                },
                vertexShader: BILLBOARD_VERT,
                fragmentShader: GLOW_FRAG,
            }),
        );
        mesh.frustumCulled = false;
        return mesh;
    }

    ringGlow(color) {
        const mesh = new Mesh(
            new PlaneGeometry(1, 1),
            new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                uniforms: { uSize: { value: 30 }, uColor: { value: new Color(color) }, uPhase: { value: 0 }, uHot: { value: 0 } },
                vertexShader: BILLBOARD_VERT,
                fragmentShader: /* glsl */ `
                    uniform vec3 uColor;
                    uniform float uPhase;
                    uniform float uHot;
                    varying vec2 vUv;
                    void main() {
                        float r = length(vUv - 0.5) * 2.0;
                        float ring = smoothstep(0.06, 0.0, abs(r - uPhase)) * (1.0 - uPhase);
                        gl_FragColor = vec4(uColor * ring * (0.8 + uHot * 2.5), 1.0);
                    }
                `,
            }),
        );
        mesh.frustumCulled = false;
        return mesh;
    }

    /** Card k is hovered (or k = -1: none). */
    highlight(k) {
        this.beacons.forEach((b, i) => {
            b.target = i === k ? 1 : 0;
        });
    }

    setPixelRatio(pr) {
        this.uniforms.uPixelRatio.value = pr;
    }

    setDensity(d) {
        this.points.geometry.setDrawRange(0, Math.floor(this.count * d));
    }

    /**
     * @param {number} alpha 0..1 — faded in on approach rather than popped in
     */
    update(s, alpha) {
        this.group.visible = alpha > 0.003;
        if (!this.group.visible) return;
        const t = s.time;
        this.uniforms.uTime.value = t;
        this.uniforms.uAlpha.value = alpha;

        for (const [k, b] of this.beacons.entries()) {
            b.hot = damp(b.hot, b.target, 9, s.dt);
            const th = b.theta + (t * 0.05) / (0.25 + b.r / RADIUS);
            const x = Math.cos(th) * b.r;
            const z = Math.sin(th) * b.r;
            b.mesh.position.set(x, 1.5, z);
            b.ring.position.set(x, 1.5, z);
            const pulse = 0.85 + Math.sin(t * 2.2 + k) * 0.15;
            b.mesh.material.uniforms.uSize.value = 18 * pulse + b.hot * 34;
            b.mesh.material.uniforms.uIntensity.value = (0.9 + b.hot * 3.2) * alpha;
            const phase = ((t * 0.55 + k * 0.37) % 1 + 1) % 1;
            b.ring.material.uniforms.uPhase.value = phase;
            b.ring.material.uniforms.uHot.value = b.hot * alpha;
            b.ring.material.uniforms.uSize.value = 30 + b.hot * 40;
        }
        this.bulge.material.uniforms.uIntensity.value = (0.42 + Math.sin(t * 0.6) * 0.03) * alpha;
    }
}
