import {
    AdditiveBlending,
    Color,
    Group,
    IcosahedronGeometry,
    Mesh,
    OctahedronGeometry,
    PlaneGeometry,
    ShaderMaterial,
} from 'three';
import { BILLBOARD_VERT, NOISE } from '../lib/glsl.js';
import { rng } from '../lib/math.js';
import { ANCHORS } from './anchors.js';

/**
 * The product nebula: layered clouds of glowing gas at different depths —
 * so they drift against each other as the camera moves, which is what makes
 * flat sprites read as volume — with iridescent crystals turning inside.
 */
const PALETTES = [
    ['#e879f9', '#8b5cf6'],
    ['#22d3ee', '#8b5cf6'],
    ['#f472b6', '#fb923c'],
    ['#8b5cf6', '#22d3ee'],
    ['#e879f9', '#22d3ee'],
    ['#a78bfa', '#f0abfc'],
];

export class Nebula {
    constructor({ scene, tierName }) {
        this.group = new Group();
        this.group.position.copy(ANCHORS.products);
        scene.add(this.group);

        const rand = rng(31);
        const clouds = tierName === 'low' ? 5 : tierName === 'mid' ? 7 : 9;
        this.cloudMaterials = [];

        for (let i = 0; i < clouds; i++) {
            const pal = PALETTES[i % PALETTES.length];
            const material = new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                defines: { FBM_OCTAVES: tierName === 'low' ? 3 : 5 },
                uniforms: {
                    uSize: { value: 170 + rand() * 230 },
                    uTime: { value: 0 },
                    uSeed: { value: rand() * 50 },
                    uA: { value: new Color(pal[0]) },
                    uB: { value: new Color(pal[1]) },
                    uAlpha: { value: 0.1 + rand() * 0.1 },
                },
                vertexShader: BILLBOARD_VERT,
                fragmentShader: /* glsl */ `
                    ${NOISE}
                    uniform float uTime;
                    uniform float uSeed;
                    uniform vec3 uA;
                    uniform vec3 uB;
                    uniform float uAlpha;
                    varying vec2 vUv;
                    void main() {
                        vec2 c = vUv - 0.5;
                        float r = length(c) * 2.0;
                        vec3 q = vec3(c * 2.6, uSeed + uTime * 0.012);
                        float w = xuFbm(q + vec3(0.0, 0.0, uTime * 0.01));
                        float n = xuFbm(q * 1.7 + w * 2.2);
                        float cloud = smoothstep(0.46, 0.92, n) * smoothstep(1.0, 0.3, r);
                        float wisps = pow(xuFbm(q * 4.0 - w), 3.0) * smoothstep(1.0, 0.4, r);
                        vec3 col = mix(uA, uB, smoothstep(0.3, 0.75, w));
                        gl_FragColor = vec4(col * (cloud + wisps * 0.6) * uAlpha * 1.35, 1.0);
                    }
                `,
            });
            const mesh = new Mesh(new PlaneGeometry(1, 1), material);
            mesh.position.set((rand() - 0.5) * 260, (rand() - 0.5) * 120, (rand() - 0.5) * 320);
            mesh.frustumCulled = false;
            this.group.add(mesh);
            this.cloudMaterials.push(material);
        }

        // ---- crystals ------------------------------------------------------
        this.crystalMaterial = new ShaderMaterial({
            uniforms: { uTime: { value: 0 } },
            vertexShader: /* glsl */ `
                varying vec3 vNormalW;
                varying vec3 vViewW;
                void main() {
                    vec4 world = modelMatrix * vec4(position, 1.0);
                    vNormalW = normalize(mat3(modelMatrix) * normal);
                    vViewW = cameraPosition - world.xyz;
                    gl_Position = projectionMatrix * viewMatrix * world;
                }
            `,
            fragmentShader: /* glsl */ `
                uniform float uTime;
                varying vec3 vNormalW;
                varying vec3 vViewW;
                void main() {
                    vec3 N = normalize(vNormalW);
                    vec3 V = normalize(vViewW);
                    float f = clamp(1.0 - abs(dot(N, V)), 0.0, 1.0);
                    // Thin-film interference: hue shifts with the viewing angle.
                    vec3 film = 0.5 + 0.5 * cos(6.2831 * (vec3(0.0, 0.33, 0.67) + f * 1.3 + uTime * 0.04));
                    vec3 col = film * (0.18 + f * 1.5) + vec3(0.85, 0.9, 1.0) * pow(f, 5.0) * 2.4;
                    gl_FragColor = vec4(col, 1.0);
                }
            `,
        });
        this.crystals = [];
        // Polyhedra are non-indexed already, so these normals come out per face: facets.
        const geometries = [new IcosahedronGeometry(1, 0), new OctahedronGeometry(1, 0)];
        for (const geo of geometries) geo.computeVertexNormals();
        const crystalCount = tierName === 'low' ? 6 : 10;
        for (let i = 0; i < crystalCount; i++) {
            const mesh = new Mesh(geometries[i % 2], this.crystalMaterial);
            const s = 2.5 + rand() * 6;
            mesh.scale.set(s, s * (1.2 + rand() * 0.8), s);
            mesh.position.set((rand() - 0.5) * 230, (rand() - 0.5) * 90, 40 + (rand() - 0.5) * 260);
            mesh.rotation.set(rand() * 6, rand() * 6, rand() * 6);
            this.group.add(mesh);
            this.crystals.push({ mesh, spin: 0.1 + rand() * 0.3, bob: rand() * 6, y: mesh.position.y });
        }
    }

    update(s, visible) {
        this.group.visible = visible;
        if (!visible) return;
        const t = s.time;
        for (const m of this.cloudMaterials) m.uniforms.uTime.value = t;
        this.crystalMaterial.uniforms.uTime.value = t;
        for (const c of this.crystals) {
            c.mesh.rotation.y += c.spin * s.dt;
            c.mesh.rotation.x += c.spin * 0.4 * s.dt;
            c.mesh.position.y = c.y + Math.sin(t * 0.5 + c.bob) * 3;
        }
    }
}
