import {
    AdditiveBlending,
    BackSide,
    Color,
    CylinderGeometry,
    DoubleSide,
    Group,
    Mesh,
    PlaneGeometry,
    RingGeometry,
    ShaderMaterial,
} from 'three';
import { BILLBOARD_VERT, GLOW_FRAG, NOISE } from '../lib/glsl.js';
import { ANCHORS } from './anchors.js';

/**
 * The launch gate at the end of the route: a wormhole — a spinning accretion
 * disk around a mouth that opens into a tunnel of streaming light. The
 * footer is read from inside it.
 */
const MOUTH = 38;
const LENGTH = 760;

export class Wormhole {
    constructor({ scene, tierName }) {
        this.group = new Group();
        this.group.position.copy(ANCHORS.launch);
        scene.add(this.group);

        const octaves = tierName === 'low' ? 3 : 4;

        // The tunnel runs from the mouth (z = 0) back along -Z. After the
        // rotation the cylinder's bottom (radiusBottom, uv.y = 0) is the mouth.
        const tunnelGeo = new CylinderGeometry(10, MOUTH, LENGTH, 96, 96, true);
        tunnelGeo.rotateX(-Math.PI / 2);
        tunnelGeo.translate(0, 0, -LENGTH / 2);
        this.tunnelMaterial = new ShaderMaterial({
            side: BackSide,
            depthWrite: false,
            transparent: true,
            blending: AdditiveBlending,
            defines: { FBM_OCTAVES: octaves },
            uniforms: {
                uTime: { value: 0 },
                uC1: { value: new Color('#22d3ee') },
                uC2: { value: new Color('#8b5cf6') },
                uC3: { value: new Color('#e879f9') },
                uC4: { value: new Color('#ffd479') },
            },
            vertexShader: /* glsl */ `
                varying vec2 vUv;
                void main() {
                    vUv = uv;
                    gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
                }
            `,
            fragmentShader: /* glsl */ `
                ${NOISE}
                uniform float uTime;
                uniform vec3 uC1;
                uniform vec3 uC2;
                uniform vec3 uC3;
                uniform vec3 uC4;
                varying vec2 vUv;
                void main() {
                    float a = vUv.x * 6.2831;
                    float depth = vUv.y; // 0 at the mouth, 1 deep inside
                    // Noise is sampled on a circle, so the tunnel has no seam where u wraps.
                    vec2 ring = vec2(cos(a), sin(a));
                    float swirl = a * 3.0 + depth * 22.0 - uTime * 1.7;
                    float n = xuNoise(vec3(ring * 2.3, depth * 9.0 - uTime * 0.9));
                    float stripes = pow(max(0.0, 0.5 + 0.5 * sin(swirl + n * 3.0)), 3.0);
                    float flow = xuFbm(vec3(ring * 1.6, depth * 5.0 - uTime * 1.1));
                    float h = fract(depth * 1.6 - uTime * 0.12 + flow * 0.35);
                    vec3 col = mix(mix(uC1, uC2, smoothstep(0.0, 0.33, h)), mix(uC3, uC4, smoothstep(0.66, 1.0, h)), smoothstep(0.33, 0.66, h));
                    float glow = stripes * (0.35 + flow) * (0.45 + depth * 2.4);
                    float mouthFade = smoothstep(0.0, 0.1, depth);
                    vec3 outc = col * glow * mouthFade * 1.05 + vec3(1.0, 0.95, 1.0) * pow(clamp(depth, 0.0, 1.0), 7.0) * 1.8;
                    gl_FragColor = vec4(outc, 1.0);
                }
            `,
        });
        this.tunnel = new Mesh(tunnelGeo, this.tunnelMaterial);
        this.tunnel.frustumCulled = false;
        this.group.add(this.tunnel);

        // The accretion disk around the mouth.
        this.diskMaterial = new ShaderMaterial({
            side: DoubleSide,
            transparent: true,
            depthWrite: false,
            blending: AdditiveBlending,
            defines: { FBM_OCTAVES: octaves },
            uniforms: { uTime: { value: 0 }, uInner: { value: MOUTH }, uOuter: { value: MOUTH * 3.2 } },
            vertexShader: /* glsl */ `
                varying vec2 vPos;
                void main() {
                    vPos = position.xy;
                    gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
                }
            `,
            fragmentShader: /* glsl */ `
                ${NOISE}
                uniform float uTime;
                uniform float uInner;
                uniform float uOuter;
                varying vec2 vPos;
                void main() {
                    float r = length(vPos);
                    float rr = (r - uInner) / (uOuter - uInner);
                    float a = atan(vPos.y, vPos.x);
                    float arms = max(0.0, 0.5 + 0.5 * sin(a * 3.0 + log(max(r, 1.0)) * 11.0 - uTime * 1.4));
                    float turb = xuFbm(vec3(cos(a) * 3.0, sin(a) * 3.0, rr * 4.0 - uTime * 0.25));
                    float bright = arms * arms * (0.4 + turb) * pow(clamp(1.0 - rr, 0.0, 1.0), 2.2) * smoothstep(0.0, 0.05, rr);
                    vec3 hot = vec3(1.0, 0.86, 0.6);
                    vec3 col = mix(hot, mix(vec3(0.9, 0.35, 1.0), vec3(0.2, 0.8, 1.0), turb), smoothstep(0.05, 0.6, rr));
                    gl_FragColor = vec4(col * bright * 1.5, 1.0);
                }
            `,
        });
        this.disk = new Mesh(new RingGeometry(MOUTH * 0.98, MOUTH * 3.2, 192, 12), this.diskMaterial);
        this.group.add(this.disk);

        this.glow = new Mesh(
            new PlaneGeometry(1, 1),
            new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                uniforms: {
                    uSize: { value: MOUTH * 6 },
                    uColor: { value: new Color('#c4b5fd') },
                    uIntensity: { value: 0.26 },
                    uFalloff: { value: 2.6 },
                },
                vertexShader: BILLBOARD_VERT,
                fragmentShader: GLOW_FRAG,
            }),
        );
        this.glow.frustumCulled = false;
        this.group.add(this.glow);
    }

    update(s, visible) {
        this.group.visible = visible;
        if (!visible) return;
        this.tunnelMaterial.uniforms.uTime.value = s.time;
        this.diskMaterial.uniforms.uTime.value = s.time;
        this.disk.rotation.z = s.time * 0.05;
    }
}

export const WORMHOLE_MOUTH = MOUTH;
