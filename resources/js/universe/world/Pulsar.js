import {
    AdditiveBlending,
    Color,
    CylinderGeometry,
    DoubleSide,
    Group,
    IcosahedronGeometry,
    Mesh,
    MeshBasicMaterial,
    PlaneGeometry,
    ShaderMaterial,
} from 'three';
import { BILLBOARD_VERT, GLOW_FRAG } from '../lib/glsl.js';
import { ANCHORS } from './anchors.js';

/**
 * Where the reviews come from: a pulsar — a tiny, blinding star that sweeps
 * two beams around like a lighthouse — broadcasting rings of signal.
 */
export class Pulsar {
    constructor({ scene }) {
        this.group = new Group();
        this.group.position.copy(ANCHORS.reviews);
        scene.add(this.group);

        this.star = new Mesh(new IcosahedronGeometry(3.2, 6), new MeshBasicMaterial({ color: new Color('#dff6ff').multiplyScalar(4.5) }));
        this.group.add(this.star);

        this.glow = this.billboard(GLOW_FRAG, {
            uSize: { value: 70 },
            uColor: { value: new Color('#7dd3fc') },
            uIntensity: { value: 1.1 },
            uFalloff: { value: 4.5 },
        });
        this.group.add(this.glow);

        // The beams. The pivot is tilted off the spin axis, so the beams sweep a cone.
        this.spin = new Group();
        this.spin.rotation.z = 0.46;
        this.group.add(this.spin);
        this.beamMaterial = new ShaderMaterial({
            transparent: true,
            depthWrite: false,
            side: DoubleSide,
            blending: AdditiveBlending,
            uniforms: { uColor: { value: new Color('#a5f3fc') }, uTime: { value: 0 } },
            vertexShader: /* glsl */ `
                varying float vAlong;
                varying vec3 vNormalW;
                varying vec3 vViewW;
                void main() {
                    vAlong = uv.y;
                    vec4 world = modelMatrix * vec4(position, 1.0);
                    vNormalW = normalize(mat3(modelMatrix) * normal);
                    vViewW = cameraPosition - world.xyz;
                    gl_Position = projectionMatrix * viewMatrix * world;
                }
            `,
            fragmentShader: /* glsl */ `
                uniform vec3 uColor;
                uniform float uTime;
                varying float vAlong;
                varying vec3 vNormalW;
                varying vec3 vViewW;
                void main() {
                    float facing = clamp(abs(dot(normalize(vNormalW), normalize(vViewW))), 0.0, 1.0);
                    float core = pow(facing, 2.2);
                    float fade = pow(clamp(1.0 - vAlong, 0.0, 1.0), 1.6);
                    float ripple = 0.75 + 0.25 * sin(vAlong * 40.0 - uTime * 12.0);
                    gl_FragColor = vec4(uColor * core * fade * ripple * 1.1, 1.0);
                }
            `,
        });
        // Narrow end (the cylinder's bottom, uv.y = 0) at the star, widening outward.
        const beamGeo = new CylinderGeometry(9, 0.5, 190, 40, 1, true);
        beamGeo.translate(0, 95, 0);
        const up = new Mesh(beamGeo, this.beamMaterial);
        const down = new Mesh(beamGeo, this.beamMaterial);
        down.rotation.z = Math.PI;
        this.pivot = new Group();
        this.pivot.add(up, down);
        this.spin.add(this.pivot);

        // Signal rings: expanding, fading circles facing the camera.
        this.rings = [];
        for (let i = 0; i < 4; i++) {
            const ring = this.billboard(
                /* glsl */ `
                    uniform vec3 uColor;
                    uniform float uAlpha;
                    varying vec2 vUv;
                    void main() {
                        float r = length(vUv - 0.5) * 2.0;
                        float line = smoothstep(0.035, 0.0, abs(r - 0.94));
                        gl_FragColor = vec4(uColor * line * uAlpha * 1.6, 1.0);
                    }
                `,
                { uSize: { value: 10 }, uColor: { value: new Color(i % 2 ? '#e879f9' : '#22d3ee') }, uAlpha: { value: 0 } },
            );
            this.group.add(ring);
            this.rings.push({ mesh: ring, offset: i / 4 });
        }
    }

    billboard(fragmentShader, uniforms) {
        const mesh = new Mesh(
            new PlaneGeometry(1, 1),
            new ShaderMaterial({
                transparent: true,
                depthWrite: false,
                blending: AdditiveBlending,
                uniforms,
                vertexShader: BILLBOARD_VERT,
                fragmentShader,
            }),
        );
        mesh.frustumCulled = false;
        return mesh;
    }

    update(s, visible) {
        this.group.visible = visible;
        if (!visible) return;
        const t = s.time;
        this.pivot.rotation.y = t * 1.35;
        this.spin.rotation.y = t * 0.05;
        this.beamMaterial.uniforms.uTime.value = t;
        this.glow.material.uniforms.uIntensity.value = 1 + Math.pow(Math.max(0, Math.sin(t * 1.35 * 2)), 18) * 1.6;
        for (const ring of this.rings) {
            const phase = (t * 0.28 + ring.offset) % 1;
            ring.mesh.material.uniforms.uSize.value = 8 + phase * 170;
            ring.mesh.material.uniforms.uAlpha.value = Math.pow(1 - phase, 1.8) * 0.9;
        }
    }
}
