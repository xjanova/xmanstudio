import {
    AdditiveBlending,
    BufferAttribute,
    BufferGeometry,
    Color,
    DirectionalLight,
    Group,
    HemisphereLight,
    IcosahedronGeometry,
    InstancedMesh,
    Mesh,
    MeshStandardMaterial,
    Object3D,
    Points,
    ShaderMaterial,
    Vector3,
} from 'three';
import { TAU, gauss, rng } from '../lib/math.js';
import { ANCHORS } from './anchors.js';
import { atmosphere, planetMaterial } from './Planets.js';

/**
 * The tech belt: an amber gas giant inside a wide ring of tumbling rocks and
 * fine dust. The flight dips into the ring plane, so the rocks rush past
 * close to the lens — the one place in the universe with real parallax at
 * arm's length.
 */
const ROCKS = { high: 1500, mid: 850, low: 380 };
const DUST = { high: 9000, mid: 5000, low: 2400 };
const INNER = 92;
const OUTER = 172;

export class Belt {
    constructor({ scene, tierName, pixelRatio }) {
        this.group = new Group();
        this.group.position.copy(ANCHORS.stack);
        scene.add(this.group);

        const rand = rng(53);
        const radius = 50;
        const light = new Vector3(0.7, 0.45, 0.55);

        this.body = new Mesh(
            new IcosahedronGeometry(radius, tierName === 'low' ? 24 : 48),
            planetMaterial('stack', { octaves: tierName === 'low' ? 3 : 5, seed: 12, light }),
        );
        this.group.add(this.body);
        this.group.add(atmosphere('#ffd479', radius, light));

        this.ring = new Group();
        this.ring.rotation.set(0.22, 0, 0.12);
        this.group.add(this.ring);

        // Rocks: one lumpy icosahedron, instanced.
        const rockGeo = new IcosahedronGeometry(1, 1);
        const p = rockGeo.attributes.position;
        const v = new Vector3();
        for (let i = 0; i < p.count; i++) {
            v.fromBufferAttribute(p, i);
            v.multiplyScalar(0.72 + rand() * 0.5);
            p.setXYZ(i, v.x, v.y, v.z);
        }
        rockGeo.computeVertexNormals();

        const count = ROCKS[tierName];
        this.rocks = new InstancedMesh(
            rockGeo,
            new MeshStandardMaterial({ color: '#8b93b8', roughness: 0.92, metalness: 0.08, flatShading: true, emissive: '#0b1030' }),
            count,
        );
        const dummy = new Object3D();
        for (let i = 0; i < count; i++) {
            const r = INNER + Math.pow(rand(), 0.8) * (OUTER - INNER);
            const a = rand() * TAU;
            const edge = Math.min(r - INNER, OUTER - r) / ((OUTER - INNER) / 2);
            dummy.position.set(Math.cos(a) * r, gauss(rand) * 2.2 * edge, Math.sin(a) * r);
            dummy.rotation.set(rand() * TAU, rand() * TAU, rand() * TAU);
            const s = 0.35 + Math.pow(rand(), 3.5) * 3.4;
            dummy.scale.set(s, s * (0.6 + rand() * 0.6), s * (0.7 + rand() * 0.6));
            dummy.updateMatrix();
            this.rocks.setMatrixAt(i, dummy.matrix);
        }
        this.ring.add(this.rocks);

        // Dust: a fine glittering band.
        const nDust = DUST[tierName];
        const pos = new Float32Array(nDust * 3);
        const col = new Float32Array(nDust * 3);
        const c = new Color();
        for (let i = 0; i < nDust; i++) {
            const r = INNER - 6 + rand() * (OUTER - INNER + 20);
            const a = rand() * TAU;
            pos[i * 3] = Math.cos(a) * r;
            pos[i * 3 + 1] = gauss(rand) * 1.2;
            pos[i * 3 + 2] = Math.sin(a) * r;
            c.set(rand() < 0.5 ? '#bfd4ff' : rand() < 0.5 ? '#ffe2b0' : '#c4b5fd');
            const b = 0.25 + rand() * 0.55;
            col[i * 3] = c.r * b;
            col[i * 3 + 1] = c.g * b;
            col[i * 3 + 2] = c.b * b;
        }
        const dustGeo = new BufferGeometry();
        dustGeo.setAttribute('position', new BufferAttribute(pos, 3));
        dustGeo.setAttribute('aColor', new BufferAttribute(col, 3));
        this.dustCount = nDust;
        this.dust = new Points(
            dustGeo,
            new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                uniforms: { uPixelRatio: { value: pixelRatio } },
                vertexShader: /* glsl */ `
                    uniform float uPixelRatio;
                    attribute vec3 aColor;
                    varying vec3 vColor;
                    void main() {
                        vec4 mv = modelViewMatrix * vec4(position, 1.0);
                        gl_Position = projectionMatrix * mv;
                        vColor = aColor * smoothstep(1.0, 14.0, -mv.z);
                        gl_PointSize = clamp(uPixelRatio * (140.0 / -mv.z), 0.0, 10.0);
                    }
                `,
                fragmentShader: /* glsl */ `
                    varying vec3 vColor;
                    void main() {
                        float r = length(gl_PointCoord - 0.5);
                        gl_FragColor = vec4(vColor * smoothstep(0.5, 0.0, r), 1.0);
                    }
                `,
            }),
        );
        this.dust.frustumCulled = false;
        this.ring.add(this.dust);

        // Standard materials (the rocks) need light; the shader worlds ignore these.
        this.sun = new DirectionalLight('#ffe8c8', 2.4);
        this.sun.position.copy(light).multiplyScalar(100);
        this.group.add(this.sun);
        this.group.add(this.sun.target);
        this.sky = new HemisphereLight('#7c6cf0', '#0a0f24', 0.9);
        this.group.add(this.sky);
    }

    setPixelRatio(pr) {
        this.dust.material.uniforms.uPixelRatio.value = pr;
    }

    setDensity(d) {
        this.rocks.count = Math.max(100, Math.floor(this.rocks.instanceMatrix.count * d));
        this.dust.geometry.setDrawRange(0, Math.floor(this.dustCount * d));
    }

    update(s, visible) {
        this.group.visible = visible;
        if (!visible) return;
        this.body.material.uniforms.uTime.value = s.time;
        this.body.rotation.y = s.time * 0.03;
        this.ring.rotation.y = s.time * 0.018;
    }
}
