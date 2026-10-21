import {
    AdditiveBlending,
    BufferAttribute,
    BufferGeometry,
    Color,
    Group,
    IcosahedronGeometry,
    Mesh,
    MeshBasicMaterial,
    PlaneGeometry,
    Points,
    Quaternion,
    ShaderMaterial,
    TorusGeometry,
    Vector3,
} from 'three';
import { BILLBOARD_VERT, GLOW_FRAG, NOISE } from '../lib/glsl.js';
import { ANCHORS } from './anchors.js';

/**
 * The XMAN core — the star the whole universe turns around. A boiling
 * plasma surface, a corona of slow rays, and two crossed rings that read as
 * an X from the front (the brand mark) and open into a gyroscope as the
 * camera swings around. Sparks run along the rings.
 */
const RADIUS = 9;

export class Core {
    constructor({ scene, tierName }) {
        this.group = new Group();
        this.group.position.copy(ANCHORS.core);
        scene.add(this.group);

        const octaves = tierName === 'low' ? 4 : 5;

        // ---- plasma surface --------------------------------------------
        this.sunMaterial = new ShaderMaterial({
            defines: { FBM_OCTAVES: octaves },
            uniforms: { uTime: { value: 0 } },
            vertexShader: /* glsl */ `
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
            `,
            fragmentShader: /* glsl */ `
                ${NOISE}
                uniform float uTime;
                varying vec3 vObj;
                varying vec3 vNormalW;
                varying vec3 vViewW;
                void main() {
                    vec3 p = normalize(vObj);
                    float t = uTime * 0.05;
                    float warp = xuFbm(p * 2.3 + vec3(t, -t * 0.7, t * 0.4));
                    float n = xuFbm(p * 3.6 + warp * 1.9 + vec3(-t * 0.5, t, 0.0));
                    float gran = xuNoise(p * 16.0 + vec3(0.0, uTime * 0.32, 0.0));
                    float heat = clamp(n * 0.9 + gran * 0.28, 0.0, 1.0);

                    vec3 deep = vec3(1.0, 0.24, 0.03);
                    vec3 gold = vec3(1.0, 0.62, 0.2);
                    vec3 white = vec3(1.0, 0.93, 0.78);
                    vec3 col = mix(deep, gold, smoothstep(0.28, 0.6, heat));
                    col = mix(col, white, smoothstep(0.7, 0.95, heat));

                    vec3 N = normalize(vNormalW);
                    vec3 V = normalize(vViewW);
                    float mu = clamp(dot(N, V), 0.0, 1.0);
                    col *= 0.55 + 0.45 * pow(mu, 0.45); // limb darkening
                    float rim = pow(1.0 - mu, 3.0);
                    col += vec3(1.0, 0.45, 0.85) * rim * 1.3; // the brand's magenta at the limb

                    gl_FragColor = vec4(col * 1.2, 1.0);
                }
            `,
        });
        this.sun = new Mesh(new IcosahedronGeometry(RADIUS, tierName === 'low' ? 24 : 48), this.sunMaterial);
        this.group.add(this.sun);

        // ---- corona -----------------------------------------------------
        this.coronaMaterial = new ShaderMaterial({
            transparent: true,
            depthWrite: false,
            blending: AdditiveBlending,
            defines: { FBM_OCTAVES: 4 },
            uniforms: { uTime: { value: 0 }, uSize: { value: RADIUS * 6.4 }, uIntensity: { value: 1 } },
            vertexShader: BILLBOARD_VERT,
            fragmentShader: /* glsl */ `
                ${NOISE}
                uniform float uTime;
                uniform float uIntensity;
                varying vec2 vUv;
                void main() {
                    vec2 c = vUv - 0.5;
                    float r = length(c) * 2.0;
                    float a = atan(c.y, c.x);
                    float glow = exp(-r * 6.5) * 1.15 + exp(-r * 3.2) * 0.07;
                    float rays = xuFbm(vec3(cos(a) * 2.2, sin(a) * 2.2, r * 1.6 - uTime * 0.07));
                    rays = pow(rays, 2.6) * smoothstep(0.95, 0.3, r) * smoothstep(0.2, 0.34, r) * 1.5;
                    vec3 gold = vec3(1.0, 0.7, 0.32);
                    vec3 cool = mix(vec3(0.85, 0.35, 1.0), vec3(0.2, 0.8, 1.0), 0.5 + 0.5 * sin(a * 2.0 + uTime * 0.12));
                    vec3 col = gold * glow + mix(gold, cool, smoothstep(0.3, 0.9, r)) * rays;
                    col *= smoothstep(1.0, 0.5, r);
                    gl_FragColor = vec4(col * uIntensity, 1.0);
                }
            `,
        });
        this.corona = new Mesh(new PlaneGeometry(1, 1), this.coronaMaterial);
        this.corona.frustumCulled = false;
        this.group.add(this.corona);

        // A wide, faint halo that washes the space around the star.
        this.halo = new Mesh(
            new PlaneGeometry(1, 1),
            new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                uniforms: {
                    uSize: { value: RADIUS * 26 },
                    uColor: { value: new Color('#ffb46b') },
                    uIntensity: { value: 0.07 },
                    uFalloff: { value: 5.0 },
                },
                vertexShader: BILLBOARD_VERT,
                fragmentShader: GLOW_FRAG,
            }),
        );
        this.halo.frustumCulled = false;
        this.group.add(this.halo);

        // ---- the X: two crossed rings -----------------------------------
        this.rings = new Group();
        this.group.add(this.rings);
        const ringGeo = new TorusGeometry(RADIUS * 1.75, 0.075, 6, 320);
        const specs = [
            { tilt: Math.PI / 4, color: new Color('#22d3ee').multiplyScalar(4) },
            { tilt: -Math.PI / 4, color: new Color('#e879f9').multiplyScalar(4) },
        ];
        this.ringAxes = [];
        for (const spec of specs) {
            // A ring whose plane holds the view axis appears edge-on: a line.
            // Two of them at ±45° make the X.
            const dir = new Vector3(Math.cos(spec.tilt), Math.sin(spec.tilt), 0);
            const normal = new Vector3().crossVectors(new Vector3(0, 0, 1), dir).normalize();
            const ring = new Mesh(ringGeo, new MeshBasicMaterial({ color: spec.color, transparent: true, opacity: 0.9 }));
            ring.quaternion.copy(new Quaternion().setFromUnitVectors(new Vector3(0, 0, 1), normal));
            this.rings.add(ring);
            this.ringAxes.push({ dir, normal, color: spec.color });
        }

        // ---- sparks running along the rings, each with a fading tail -----
        this.trail = 14;
        this.sparkCount = 4;
        const n = this.sparkCount * this.trail;
        this.sparkPos = new Float32Array(n * 3);
        const sparkCol = new Float32Array(n * 3);
        const sparkSize = new Float32Array(n);
        for (let s = 0; s < this.sparkCount; s++) {
            const color = this.ringAxes[s % 2].color.clone().multiplyScalar(0.35);
            for (let k = 0; k < this.trail; k++) {
                const i = s * this.trail + k;
                const fade = Math.pow(1 - k / this.trail, 1.6);
                sparkCol[i * 3] = color.r * fade + fade * 0.6;
                sparkCol[i * 3 + 1] = color.g * fade + fade * 0.6;
                sparkCol[i * 3 + 2] = color.b * fade + fade * 0.6;
                sparkSize[i] = (k === 0 ? 5.5 : 3.2) * fade + 0.6;
            }
        }
        const sparkGeo = new BufferGeometry();
        sparkGeo.setAttribute('position', new BufferAttribute(this.sparkPos, 3));
        sparkGeo.setAttribute('aColor', new BufferAttribute(sparkCol, 3));
        sparkGeo.setAttribute('aSize', new BufferAttribute(sparkSize, 1));
        this.sparks = new Points(
            sparkGeo,
            new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                uniforms: { uPixelRatio: { value: 1 } },
                vertexShader: /* glsl */ `
                    uniform float uPixelRatio;
                    attribute vec3 aColor;
                    attribute float aSize;
                    varying vec3 vColor;
                    void main() {
                        vec4 mv = modelViewMatrix * vec4(position, 1.0);
                        gl_Position = projectionMatrix * mv;
                        vColor = aColor;
                        gl_PointSize = aSize * uPixelRatio * (70.0 / -mv.z);
                    }
                `,
                fragmentShader: /* glsl */ `
                    varying vec3 vColor;
                    void main() {
                        float r = length(gl_PointCoord - 0.5);
                        float a = smoothstep(0.5, 0.0, r);
                        gl_FragColor = vec4(vColor * a * a * 3.0, 1.0);
                    }
                `,
            }),
        );
        this.sparks.frustumCulled = false;
        this.rings.add(this.sparks);

        this.tmpA = new Vector3();
        this.tmpB = new Vector3();
    }

    setPixelRatio(pr) {
        this.sparks.material.uniforms.uPixelRatio.value = pr;
    }

    update(s) {
        const t = s.time;
        this.sunMaterial.uniforms.uTime.value = t;
        this.coronaMaterial.uniforms.uTime.value = t;
        this.sun.rotation.y = t * 0.03;

        // The X turns slowly and nods, like a gyroscope finding its balance.
        this.rings.rotation.z = t * 0.06;
        this.rings.rotation.x = Math.sin(t * 0.21) * 0.1;
        this.rings.rotation.y = Math.sin(t * 0.17) * 0.12;

        const R = RADIUS * 1.75;
        for (let sIdx = 0; sIdx < this.sparkCount; sIdx++) {
            const axis = this.ringAxes[sIdx % 2];
            // Basis of the ring plane: its in-plane direction and the view axis.
            const e1 = axis.dir;
            const e2 = this.tmpA.set(0, 0, 1);
            const speed = 0.9 + (sIdx % 2) * 0.25;
            const start = sIdx < 2 ? 0 : Math.PI;
            for (let k = 0; k < this.trail; k++) {
                const ang = start + (sIdx % 2 ? -1 : 1) * (t * speed - k * 0.045);
                const i = (sIdx * this.trail + k) * 3;
                this.tmpB.copy(e1).multiplyScalar(Math.cos(ang) * R).addScaledVector(e2, Math.sin(ang) * R);
                this.sparkPos[i] = this.tmpB.x;
                this.sparkPos[i + 1] = this.tmpB.y;
                this.sparkPos[i + 2] = this.tmpB.z;
            }
        }
        this.sparks.geometry.attributes.position.needsUpdate = true;

        // A slow breath in the corona.
        this.coronaMaterial.uniforms.uIntensity.value = 1 + Math.sin(t * 0.8) * 0.06 + s.warp * 0.6;
    }
}

export const CORE_RADIUS = RADIUS;
