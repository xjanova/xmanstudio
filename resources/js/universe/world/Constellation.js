import { AdditiveBlending, BufferAttribute, BufferGeometry, Color, Group, Points, ShaderMaterial } from 'three';
import { TAU, gauss, rng } from '../lib/math.js';
import { ANCHORS } from './anchors.js';

/**
 * The headline numbers, written in stars. A few thousand particles hold six
 * shapes at once — a loose cloud, the four stats, and the XMAN "X" — and the
 * vertex shader morphs between them as the visitor scrolls, scattering into
 * a swirl half-way through each change.
 *
 * The glyphs are rasterised with the page's display font on a 2D canvas and
 * sampled into target points, so they match the site's typography.
 */
const COUNT = { high: 5200, mid: 3800, low: 2600 };
const WIDTH = 92; // world units across the widest glyph run

function sampleCanvas(draw, count, rand) {
    const W = 1024;
    const H = 320;
    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;
    const g = canvas.getContext('2d', { willReadFrequently: true });
    g.fillStyle = '#fff';
    g.strokeStyle = '#fff';
    draw(g, W, H);

    const data = g.getImageData(0, 0, W, H).data;
    const hits = [];
    for (let y = 0; y < H; y += 2) {
        for (let x = 0; x < W; x += 2) {
            if (data[(y * W + x) * 4 + 3] > 128) hits.push(x, y);
        }
    }

    const out = new Float32Array(count * 3);
    const n = hits.length / 2;
    const scale = WIDTH / W;
    for (let i = 0; i < count; i++) {
        const k = n > 0 ? Math.floor(rand() * n) : 0;
        const x = n > 0 ? hits[k * 2] + rand() * 2 : W / 2;
        const y = n > 0 ? hits[k * 2 + 1] + rand() * 2 : H / 2;
        out[i * 3] = (x - W / 2) * scale;
        out[i * 3 + 1] = -(y - H / 2) * scale;
        out[i * 3 + 2] = gauss(rand) * 0.9;
    }
    return out;
}

function drawText(text, family) {
    return (g, W, H) => {
        let size = 250;
        const font = (s) => `800 ${s}px ${family}`;
        g.font = font(size);
        const w = g.measureText(text).width;
        if (w > W * 0.9) {
            size *= (W * 0.9) / w;
            g.font = font(size);
        }
        g.textAlign = 'center';
        g.textBaseline = 'middle';
        g.fillText(text, W / 2, H / 2 + size * 0.04);
    };
}

// The XMAN mark: an X of two brushed strokes inside a thin orbit.
function drawMark(g, W, H) {
    const cx = W / 2;
    const cy = H / 2;
    const r = H * 0.42;
    g.lineCap = 'round';
    g.lineWidth = H * 0.1;
    g.beginPath();
    g.moveTo(cx - r * 0.68, cy - r * 0.68);
    g.lineTo(cx + r * 0.68, cy + r * 0.68);
    g.moveTo(cx + r * 0.68, cy - r * 0.68);
    g.lineTo(cx - r * 0.68, cy + r * 0.68);
    g.stroke();
    g.lineWidth = H * 0.022;
    g.beginPath();
    g.ellipse(cx, cy, r * 1.25, r * 1.02, 0, 0, TAU);
    g.stroke();
}

export class Constellation {
    constructor({ scene, tierName, pixelRatio }, stats) {
        this.group = new Group();
        this.group.position.copy(ANCHORS.origin);
        scene.add(this.group);

        const count = COUNT[tierName];
        this.count = count;
        const rand = rng(11);
        const family = "Unbounded, 'Kanit', 'Inter', system-ui, sans-serif";

        // State 0: a loose cloud.
        const cloud = new Float32Array(count * 3);
        for (let i = 0; i < count; i++) {
            const u = rand() * 2 - 1;
            const t = rand() * TAU;
            const s = Math.sqrt(1 - u * u);
            const r = 30 + Math.pow(rand(), 0.6) * 60;
            cloud[i * 3] = Math.cos(t) * s * r * 1.4;
            cloud[i * 3 + 1] = u * r * 0.7;
            cloud[i * 3 + 2] = Math.sin(t) * s * r;
        }

        const shapes = [cloud];
        const labels = stats.slice(0, 4);
        while (labels.length < 4) labels.push(labels[labels.length - 1] || 'XMAN');
        for (const label of labels) shapes.push(sampleCanvas(drawText(label, family), count, rand));
        shapes.push(sampleCanvas(drawMark, count, rand));

        const geometry = new BufferGeometry();
        geometry.setAttribute('position', new BufferAttribute(new Float32Array(count * 3), 3));
        shapes.forEach((shape, k) => geometry.setAttribute(`aP${k}`, new BufferAttribute(shape, 3)));
        const r2 = new Float32Array(count * 2);
        for (let i = 0; i < count * 2; i++) r2[i] = rand();
        geometry.setAttribute('aRand', new BufferAttribute(r2, 2));

        this.uniforms = {
            uState: { value: 0 },
            uTime: { value: 0 },
            uPixelRatio: { value: pixelRatio },
            uAlpha: { value: 0 },
            uC1: { value: new Color('#22d3ee') },
            uC2: { value: new Color('#8b5cf6') },
            uC3: { value: new Color('#e879f9') },
            uGold: { value: new Color('#ffd479') },
        };

        this.points = new Points(
            geometry,
            new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                uniforms: this.uniforms,
                vertexShader: /* glsl */ `
                    uniform float uState;
                    uniform float uTime;
                    uniform float uPixelRatio;
                    uniform vec3 uC1;
                    uniform vec3 uC2;
                    uniform vec3 uC3;
                    uniform vec3 uGold;
                    attribute vec3 aP0;
                    attribute vec3 aP1;
                    attribute vec3 aP2;
                    attribute vec3 aP3;
                    attribute vec3 aP4;
                    attribute vec3 aP5;
                    attribute vec2 aRand;
                    varying vec3 vColor;

                    vec3 shape(float k) {
                        if (k < 0.5) return aP0;
                        if (k < 1.5) return aP1;
                        if (k < 2.5) return aP2;
                        if (k < 3.5) return aP3;
                        if (k < 4.5) return aP4;
                        return aP5;
                    }

                    void main() {
                        // Each particle leaves a touch early or late, so shapes dissolve rather than flip.
                        float s = clamp(uState + (aRand.x - 0.5) * 0.22, 0.0, 5.0);
                        float k = floor(s);
                        float f = s - k;
                        f = f * f * (3.0 - 2.0 * f);
                        vec3 a = shape(k);
                        vec3 b = shape(min(k + 1.0, 5.0));
                        vec3 p = mix(a, b, f);

                        float swirl = sin(f * 3.14159);
                        p += vec3(
                            sin(aRand.y * 40.0 + uTime * 1.3),
                            cos(aRand.x * 31.0 + uTime * 1.1),
                            sin(aRand.x * 17.0 - uTime)
                        ) * swirl * 9.0;
                        p += vec3(sin(uTime * 0.7 + aRand.x * 20.0), cos(uTime * 0.9 + aRand.y * 20.0), 0.0) * 0.16;

                        vec4 mv = modelViewMatrix * vec4(p, 1.0);
                        gl_Position = projectionMatrix * mv;

                        float formed = 1.0 - swirl;
                        float hx = clamp(p.x / 92.0 + 0.5, 0.0, 1.0);
                        vec3 col = mix(uC1, uC2, smoothstep(0.0, 0.55, hx));
                        col = mix(col, uC3, smoothstep(0.5, 1.0, hx));
                        // The X glows in the core's gold.
                        col = mix(col, uGold, smoothstep(4.2, 5.0, uState) * 0.75);
                        float tw = 0.7 + 0.3 * sin(uTime * 2.0 + aRand.y * 60.0);
                        vColor = col * (0.55 + formed * 0.9) * tw;
                        gl_PointSize = uPixelRatio * (0.8 + aRand.y * 1.3) * (190.0 / -mv.z) * (0.9 + formed * 0.3);
                    }
                `,
                fragmentShader: /* glsl */ `
                    uniform float uAlpha;
                    varying vec3 vColor;
                    void main() {
                        float r = length(gl_PointCoord - 0.5);
                        float a = smoothstep(0.5, 0.0, r);
                        gl_FragColor = vec4(vColor * a * a * 1.8 * uAlpha, 1.0);
                    }
                `,
            }),
        );
        this.points.frustumCulled = false;
        this.group.add(this.points);
    }

    setPixelRatio(pr) {
        this.uniforms.uPixelRatio.value = pr;
    }

    setDensity(d) {
        this.points.geometry.setDrawRange(0, Math.floor(this.count * Math.max(0.5, d)));
    }

    /** Narrow screens get a smaller constellation so the widest number still fits. */
    setLayout({ aspect, portrait }) {
        const s = portrait ? Math.min(1, aspect / 0.5) * 0.95 : 1;
        this.group.scale.setScalar(s);
    }

    update(s, state, alpha) {
        this.uniforms.uTime.value = s.time;
        this.uniforms.uState.value = state;
        this.uniforms.uAlpha.value = alpha;
        this.group.visible = alpha > 0.002;
    }
}
