import { AdditiveBlending, Color, DoubleSide, Group, Mesh, PlaneGeometry, ShaderMaterial } from 'three';
import { ANCHORS } from './anchors.js';

/**
 * Aurora ribbons around the core — the flowing cyan / violet / magenta light
 * of the hero artwork, rebuilt in 3D: long strips that wave in two
 * directions while bright pulses run along them.
 */
const SPECS = [
    { y: -6, z: -70, len: 620, amp: 16, freq: 0.012, speed: 0.34, width: 7, phase: 0.0, c: ['#22d3ee', '#8b5cf6', '#e879f9'], i: 1.0 },
    { y: 10, z: -90, len: 680, amp: 22, freq: 0.009, speed: 0.26, width: 9, phase: 1.7, c: ['#8b5cf6', '#22d3ee', '#a78bfa'], i: 0.8 },
    { y: -20, z: -40, len: 560, amp: 12, freq: 0.015, speed: 0.4, width: 5, phase: 3.1, c: ['#e879f9', '#8b5cf6', '#22d3ee'], i: 0.9 },
    { y: 22, z: -130, len: 760, amp: 26, freq: 0.008, speed: 0.22, width: 11, phase: 4.4, c: ['#38bdf8', '#e879f9', '#8b5cf6'], i: 0.6 },
    { y: -32, z: 18, len: 520, amp: 9, freq: 0.017, speed: 0.46, width: 3.5, phase: 5.2, c: ['#22d3ee', '#e879f9', '#ffd479'], i: 0.75 },
    { y: 4, z: -160, len: 820, amp: 30, freq: 0.007, speed: 0.2, width: 13, phase: 2.4, c: ['#a78bfa', '#22d3ee', '#f472b6'], i: 0.5 },
];

export class Ribbons {
    constructor({ scene, tierName }) {
        this.group = new Group();
        this.group.position.copy(ANCHORS.core);
        this.group.rotation.z = -0.08;
        scene.add(this.group);

        const segments = tierName === 'low' ? 160 : 320;
        const geometry = new PlaneGeometry(1, 1, segments, 1);
        const specs = tierName === 'low' ? SPECS.slice(0, 4) : SPECS;
        this.materials = [];

        for (const spec of specs) {
            const material = new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                side: DoubleSide,
                blending: AdditiveBlending,
                uniforms: {
                    uTime: { value: 0 },
                    uLen: { value: spec.len },
                    uAmp: { value: spec.amp },
                    uFreq: { value: spec.freq },
                    uSpeed: { value: spec.speed },
                    uWidth: { value: spec.width },
                    uPhase: { value: spec.phase },
                    uC1: { value: new Color(spec.c[0]) },
                    uC2: { value: new Color(spec.c[1]) },
                    uC3: { value: new Color(spec.c[2]) },
                    uIntensity: { value: spec.i },
                },
                vertexShader: /* glsl */ `
                    uniform float uTime;
                    uniform float uLen;
                    uniform float uAmp;
                    uniform float uFreq;
                    uniform float uSpeed;
                    uniform float uWidth;
                    uniform float uPhase;
                    varying vec2 vUv;
                    void main() {
                        vUv = uv;
                        float x = position.x * uLen;
                        float t = uTime * uSpeed;
                        float y = sin(x * uFreq + t + uPhase) * uAmp
                                + sin(x * uFreq * 2.3 - t * 0.6 + uPhase * 1.7) * uAmp * 0.35;
                        float z = cos(x * uFreq * 0.7 + t * 0.5 + uPhase) * uAmp * 1.8;
                        float w = uWidth * (0.4 + 0.6 * (0.5 + 0.5 * sin(x * 0.01 + uPhase + t * 0.4)));
                        vec3 p = vec3(x, y + position.y * w, z);
                        gl_Position = projectionMatrix * modelViewMatrix * vec4(p, 1.0);
                    }
                `,
                fragmentShader: /* glsl */ `
                    uniform float uTime;
                    uniform float uSpeed;
                    uniform vec3 uC1;
                    uniform vec3 uC2;
                    uniform vec3 uC3;
                    uniform float uIntensity;
                    varying vec2 vUv;
                    void main() {
                        // max(0.0, ...) on every pow() base: interpolation can land a hair
                        // below zero at the edge, pow() of that is NaN, and one NaN pixel
                        // is smeared across the whole screen by the bloom pass.
                        float across = max(0.0, 1.0 - abs(vUv.y * 2.0 - 1.0));
                        float core = pow(across, 7.0);
                        float soft = pow(across, 1.7);
                        float ends = smoothstep(0.0, 0.2, vUv.x) * smoothstep(1.0, 0.8, vUv.x);
                        float flow = 0.45 + 0.55 * pow(max(0.0, 0.5 + 0.5 * sin(vUv.x * 42.0 - uTime * 2.4 * (0.5 + uSpeed))), 3.0);
                        vec3 col = mix(uC1, uC2, smoothstep(0.08, 0.55, vUv.x));
                        col = mix(col, uC3, smoothstep(0.55, 0.95, vUv.x));
                        vec3 outc = col * (soft * 0.1 + core * 1.35 * flow) * ends * uIntensity;
                        gl_FragColor = vec4(outc, 1.0);
                    }
                `,
            });
            const mesh = new Mesh(geometry, material);
            mesh.position.set(0, spec.y, spec.z);
            mesh.frustumCulled = false;
            this.group.add(mesh);
            this.materials.push(material);
        }
    }

    update(s) {
        for (const m of this.materials) m.uniforms.uTime.value = s.time;
    }
}
