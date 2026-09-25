import { AdditiveBlending, Color, DoubleSide, Group, Mesh, PlaneGeometry, RingGeometry, ShaderMaterial, TorusGeometry, MeshBasicMaterial } from 'three';
import { BILLBOARD_VERT, GLOW_FRAG, NOISE } from '../lib/glsl.js';
import { clamp } from '../lib/math.js';
import { ANCHORS } from './anchors.js';

/**
 * The gate the intro flies through: the loader's emblem, grown into a ring of
 * light with a swirling membrane. It sits behind the core; once passed, the
 * visitor is inside the universe.
 */
export class Portal {
    constructor({ scene }) {
        this.group = new Group();
        this.group.position.copy(ANCHORS.portal);
        scene.add(this.group);

        const R = 20;

        this.ring = new Mesh(
            new TorusGeometry(R, 0.28, 8, 256),
            new MeshBasicMaterial({ color: new Color('#ffd479').multiplyScalar(3.5), transparent: true }),
        );
        this.group.add(this.ring);

        this.ring2 = new Mesh(
            new TorusGeometry(R * 1.12, 0.1, 6, 256),
            new MeshBasicMaterial({ color: new Color('#22d3ee').multiplyScalar(3), transparent: true }),
        );
        this.group.add(this.ring2);

        this.membrane = new ShaderMaterial({
            transparent: true,
            depthWrite: false,
            side: DoubleSide,
            blending: AdditiveBlending,
            defines: { FBM_OCTAVES: 4 },
            uniforms: { uTime: { value: 0 }, uAlpha: { value: 1 }, uInner: { value: 0 }, uOuter: { value: R } },
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
                uniform float uAlpha;
                uniform float uOuter;
                varying vec2 vPos;
                void main() {
                    float r = length(vPos) / uOuter;
                    float a = atan(vPos.y, vPos.x);
                    float swirl = xuFbm(vec3(cos(a + r * 5.0 - uTime * 0.6) * 2.0, sin(a + r * 5.0 - uTime * 0.6) * 2.0, r * 3.0 - uTime * 0.3));
                    vec3 col = mix(vec3(0.55, 0.25, 1.0), vec3(0.15, 0.85, 1.0), swirl);
                    col = mix(col, vec3(1.0, 0.8, 0.45), smoothstep(0.75, 1.0, r) * 0.6);
                    float edge = smoothstep(1.0, 0.92, r);
                    float body = (0.18 + pow(swirl, 2.0) * 0.9) * edge + pow(r, 6.0) * edge * 1.4;
                    gl_FragColor = vec4(col * body * uAlpha, 1.0);
                }
            `,
        });
        this.disc = new Mesh(new RingGeometry(0.01, R, 96, 8), this.membrane);
        this.group.add(this.disc);

        this.glow = new Mesh(
            new PlaneGeometry(1, 1),
            new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                uniforms: {
                    uSize: { value: R * 7 },
                    uColor: { value: new Color('#b388ff') },
                    uIntensity: { value: 0.22 },
                    uFalloff: { value: 3.5 },
                },
                vertexShader: BILLBOARD_VERT,
                fragmentShader: GLOW_FRAG,
            }),
        );
        this.glow.frustumCulled = false;
        this.group.add(this.glow);
    }

    update(s) {
        const t = s.time;
        this.membrane.uniforms.uTime.value = t;
        this.ring.rotation.z = t * 0.2;
        this.ring2.rotation.z = -t * 0.12;

        // Dissolve the membrane as the camera reaches it, so the pass-through is clean.
        const dz = s.camera.position.z - this.group.position.z;
        const alpha = clamp((dz - 4) / 70);
        this.membrane.uniforms.uAlpha.value = alpha;
        // The wide glow would fog the whole screen up close; it goes first.
        this.glow.material.uniforms.uIntensity.value = 0.2 * clamp((dz - 50) / 160);
        this.group.visible = dz > -60;
    }
}
