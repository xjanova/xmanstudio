import {
    BackSide,
    CubeCamera,
    HalfFloatType,
    LinearFilter,
    Mesh,
    Scene,
    ShaderMaterial,
    SphereGeometry,
    UnsignedByteType,
    WebGLCubeRenderTarget,
} from 'three';
import { NOISE } from '../lib/glsl.js';

/**
 * The backdrop: nebulae, a tilted galactic band with dust lanes, and a fine
 * dust of faint stars — rendered once into a cube map and used as the
 * scene's background. Nothing here moves, so there is no reason to pay for
 * five octaves of noise across the whole screen every frame.
 */
export class Sky {
    constructor({ renderer, scene, tierName }) {
        this.renderer = renderer;
        this.scene = scene;
        const size = tierName === 'high' ? 1024 : tierName === 'mid' ? 768 : 512;

        this.target = new WebGLCubeRenderTarget(size, {
            type: tierName === 'low' ? UnsignedByteType : HalfFloatType,
            generateMipmaps: false,
            minFilter: LinearFilter,
            magFilter: LinearFilter,
        });
        this.bake();
        scene.background = this.target.texture;
    }

    bake() {
        const bakeScene = new Scene();
        const material = new ShaderMaterial({
            side: BackSide,
            depthWrite: false,
            defines: { FBM_OCTAVES: 5 },
            vertexShader: /* glsl */ `
                varying vec3 vDir;
                void main() {
                    vDir = position;
                    gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
                }
            `,
            fragmentShader: /* glsl */ `
                ${NOISE}
                varying vec3 vDir;

                void main() {
                    vec3 d = normalize(vDir);

                    // The galactic band, tilted across the sky.
                    vec3 bandN = normalize(vec3(0.28, 1.0, 0.42));
                    float bandD = dot(d, bandN);
                    float band = exp(-bandD * bandD / 0.05);

                    float n1 = xuFbm(d * 2.1 + vec3(3.1, 1.7, 5.3));
                    float n2 = xuFbm(d * 4.2 + vec3(11.0, 7.0, 2.0) + n1 * 1.6);
                    float n3 = xuFbm(d * 9.5 + 31.0);

                    float cloud = smoothstep(0.46, 0.92, n2) * (0.4 + 0.6 * n1);

                    vec3 violet = vec3(0.16, 0.05, 0.34);
                    vec3 cyan = vec3(0.02, 0.17, 0.27);
                    vec3 magenta = vec3(0.27, 0.035, 0.2);
                    vec3 tint = mix(violet, cyan, smoothstep(0.38, 0.68, n1));
                    tint = mix(tint, magenta, smoothstep(0.55, 0.85, n3) * 0.55);

                    vec3 col = vec3(0.0025, 0.003, 0.012);
                    col += tint * cloud * 0.5;
                    col += mix(tint, vec3(0.3, 0.26, 0.4), 0.35) * band * (0.14 + 0.36 * n3);
                    // Dust lanes eat into the band.
                    col *= 1.0 - band * smoothstep(0.52, 0.72, n3) * 0.65;

                    // Faint star dust, denser along the band.
                    vec3 g = d * 460.0;
                    vec3 cell = floor(g);
                    float h = xuHash(cell);
                    float lit = step(0.9962 - band * 0.006, h);
                    float mag = pow(xuHash(cell + 7.0), 3.0);
                    float r = length(fract(g) - 0.5);
                    vec3 hue = mix(vec3(0.72, 0.82, 1.0), vec3(1.0, 0.86, 0.7), xuHash(cell + 3.0));
                    col += lit * mag * smoothstep(0.5, 0.0, r) * hue * 1.4;

                    gl_FragColor = vec4(col, 1.0);
                }
            `,
        });
        const mesh = new Mesh(new SphereGeometry(50, 96, 48), material);
        bakeScene.add(mesh);

        const camera = new CubeCamera(1, 200, this.target);
        camera.update(this.renderer, bakeScene);

        mesh.geometry.dispose();
        material.dispose();
    }
}
