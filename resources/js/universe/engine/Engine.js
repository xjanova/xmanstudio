import {
    ACESFilmicToneMapping,
    HalfFloatType,
    PerspectiveCamera,
    SRGBColorSpace,
    Scene,
    Vector2,
    WebGLRenderTarget,
    WebGLRenderer,
} from 'three';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { ShaderPass } from 'three/addons/postprocessing/ShaderPass.js';
import { UnrealBloomPass } from 'three/addons/postprocessing/UnrealBloomPass.js';

/**
 * Renderer, camera, post-processing and the frame loop — plus the governor
 * that keeps the flight smooth: it watches frame times and gives up quality
 * step by step, and if even the lowest setting cannot hold a usable frame
 * rate it asks main.js to send the visitor to the classic page.
 */

// Quality tiers. `dpr` caps the device pixel ratio, `bloomScale` is the bloom
// buffer's size relative to the screen, `density` scales particle counts.
export const TIERS = {
    high: { dpr: 2, msaa: 4, bloom: true, bloomScale: 0.8, density: 1 },
    mid: { dpr: 1.5, msaa: 0, bloom: true, bloomScale: 0.6, density: 0.7 },
    low: { dpr: 1.25, msaa: 0, bloom: false, bloomScale: 0.3, density: 0.45 },
};

/** Best guess from the GPU name the browser check found; the governor corrects it. */
export function pickTier(gpu = '') {
    const g = gpu.toLowerCase();
    const mobile = window.matchMedia('(pointer: coarse)').matches || /android|iphone|ipad|mobile/i.test(navigator.userAgent);
    const memory = navigator.deviceMemory || 8;

    if (mobile) {
        // Safari reports 4 cores for anything under 8, so the count cannot tell
        // one iPhone from another: Apple GPUs start at mid and the governor
        // steps an older one down if it needs to.
        if (/apple/.test(g)) return 'mid';
        if (/adreno.*\b(6[4-9]\d|7\d\d|8\d\d)\b|mali-g(7[1-9]|[6-9]\d\d)|immortalis|xclipse/.test(g) && memory >= 6) return 'mid';
        return 'low';
    }
    if (/nvidia|geforce|rtx|gtx|quadro|radeon (rx|pro)|apple m\d|apple gpu/.test(g)) return 'high';
    return 'mid';
}

/**
 * The last full-screen pass before tone mapping: warp zoom-blur, chromatic
 * fringing, the gravitational pinch around the wormhole, the white flash of a
 * jump, vignette and a little film grain.
 */
const FinalShader = {
    uniforms: {
        tDiffuse: { value: null },
        uTime: { value: 0 },
        uWarp: { value: 0 },
        uAberration: { value: 0.35 },
        uFlash: { value: 0 },
        uVignette: { value: 0.62 },
        uGrain: { value: 0.028 },
        uLens: { value: 0 },
        uLensPos: { value: new Vector2(0.5, 0.5) },
        uAspect: { value: 1 },
    },
    vertexShader: /* glsl */ `
        varying vec2 vUv;
        void main() {
            vUv = uv;
            gl_Position = projectionMatrix * modelViewMatrix * vec4(position, 1.0);
        }
    `,
    fragmentShader: /* glsl */ `
        uniform sampler2D tDiffuse;
        uniform float uTime;
        uniform float uWarp;
        uniform float uAberration;
        uniform float uFlash;
        uniform float uVignette;
        uniform float uGrain;
        uniform float uLens;
        uniform vec2 uLensPos;
        uniform float uAspect;
        varying vec2 vUv;

        float hash12(vec2 p) {
            vec3 p3 = fract(vec3(p.xyx) * 0.1031);
            p3 += dot(p3, p3.yzx + 33.33);
            return fract((p3.x + p3.y) * p3.z);
        }

        void main() {
            vec2 uv = vUv;

            // Gravitational pinch: pull the image toward the wormhole's mouth.
            if (uLens > 0.001) {
                vec2 d = uv - uLensPos;
                d.x *= uAspect;
                float r2 = dot(d, d);
                float pull = uLens * 0.16 * exp(-r2 * 9.0);
                uv = uLensPos + (uv - uLensPos) * (1.0 - pull);
            }

            vec2 dir = uv - 0.5;
            float dist = length(dir * vec2(uAspect, 1.0));

            vec3 col;
            if (uWarp > 0.01) {
                // Zoom blur toward the centre: the hyperspace smear.
                vec3 acc = vec3(0.0);
                float tot = 0.0;
                for (int i = 0; i < 10; i++) {
                    float fi = float(i);
                    float s = 1.0 - fi * 0.016 * uWarp;
                    float w = 1.0 - fi / 10.0;
                    acc += texture2D(tDiffuse, 0.5 + dir * s).rgb * w;
                    tot += w;
                }
                col = acc / tot;
            } else {
                col = texture2D(tDiffuse, uv).rgb;
            }

            float ca = (uAberration + uWarp * 2.2) * 0.0045 * dist;
            if (ca > 0.00005) {
                col.r = mix(col.r, texture2D(tDiffuse, uv + dir * ca * 1.6).r, 0.85);
                col.b = mix(col.b, texture2D(tDiffuse, uv - dir * ca * 1.6).b, 0.85);
            }

            col *= mix(1.0, smoothstep(1.05, 0.2, dist), uVignette);
            col += uFlash * vec3(0.95, 0.9, 1.0) * clamp(1.3 - dist, 0.35, 1.3);
            col += (hash12(vUv * 1543.0 + fract(uTime) * 91.7) - 0.5) * uGrain;

            gl_FragColor = vec4(max(col, 0.0), 1.0);
        }
    `,
};

/** UnrealBloomPass that works at a fraction of the screen's resolution. */
class ScaledBloomPass extends UnrealBloomPass {
    constructor(scale, ...args) {
        super(...args);
        this.scale = scale;
    }

    setSize(width, height) {
        super.setSize(Math.max(64, Math.round(width * this.scale)), Math.max(64, Math.round(height * this.scale)));
    }
}

export class Engine {
    constructor(mount, { gpu = '' } = {}) {
        this.mount = mount;
        this.tierName = pickTier(gpu);
        this.tier = { ...TIERS[this.tierName] };

        this.renderer = new WebGLRenderer({
            antialias: false,
            alpha: false,
            stencil: false,
            depth: true,
            powerPreference: 'high-performance',
        });
        this.renderer.outputColorSpace = SRGBColorSpace;
        this.renderer.toneMapping = ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 0.9;
        this.renderer.setClearColor(0x03040b, 1);
        mount.appendChild(this.renderer.domElement);

        this.scene = new Scene();
        this.camera = new PerspectiveCamera(55, 1, 0.1, 12000);

        this.width = 0;
        this.height = 0;
        this.dpr = 1;

        this.buildComposer();
        this.resize(true);

        this.frameHandlers = [];
        this.running = false;
        this.last = 0;
        this.time = 0;

        this.governor = { frames: 0, ema: 1 / 60, lastCheck: 0, step: 0, hold: false };
        this.onDegrade = null;
        this.onGiveUp = null;

        this.resizeQueued = false;
        window.addEventListener('resize', () => {
            if (this.resizeQueued) return;
            this.resizeQueued = true;
            requestAnimationFrame(() => {
                this.resizeQueued = false;
                this.resize();
            });
        });

        const canvas = this.renderer.domElement;
        canvas.addEventListener('webglcontextlost', (event) => {
            event.preventDefault();
            this.lost = true;
            this.onContextLost?.();
        });
        canvas.addEventListener('webglcontextrestored', () => {
            this.lost = false;
            this.onContextRestored?.();
        });
    }

    buildComposer() {
        const target = new WebGLRenderTarget(1, 1, { type: HalfFloatType, samples: this.tier.msaa });
        this.composer = new EffectComposer(this.renderer, target);
        this.composer.addPass(new RenderPass(this.scene, this.camera));

        // Strength, radius, threshold. The threshold sits well above the dim
        // stuff (sky, dust, cards' backdrop) so only true light sources glow.
        this.bloom = new ScaledBloomPass(this.tier.bloomScale, new Vector2(256, 256), 0.7, 0.38, 0.62);
        this.bloom.enabled = this.tier.bloom;
        this.composer.addPass(this.bloom);

        this.final = new ShaderPass(FinalShader);
        this.composer.addPass(this.final);
        this.composer.addPass(new OutputPass());

        this.post = this.final.uniforms;
    }

    get pixelRatio() {
        return this.dpr;
    }

    resize(force = false) {
        const w = window.innerWidth;
        const h = window.innerHeight;
        const dpr = Math.min(window.devicePixelRatio || 1, this.tier.dpr);
        if (!force && w === this.width && h === this.height && dpr === this.dpr) return;

        this.width = w;
        this.height = h;
        this.dpr = dpr;
        this.renderer.setPixelRatio(dpr);
        this.renderer.setSize(w, h, false);
        this.composer.setPixelRatio(dpr);
        this.composer.setSize(w, h);
        this.camera.aspect = w / h;
        this.camera.updateProjectionMatrix();
        this.post.uAspect.value = w / h;
        this.onResize?.(w, h);
    }

    onFrame(handler) {
        this.frameHandlers.push(handler);
    }

    start() {
        if (this.running) return;
        this.running = true;
        this.last = performance.now();
        this.renderer.setAnimationLoop((now) => this.tick(now));
    }

    stop() {
        this.running = false;
        this.renderer.setAnimationLoop(null);
    }

    tick(now) {
        if (this.lost) return;
        // Clamp: a tab coming back from the background must not fast-forward the world.
        const dt = Math.min(0.05, Math.max(0.0001, (now - this.last) / 1000));
        this.last = now;
        this.time += dt;
        this.post.uTime.value = this.time;

        for (const handler of this.frameHandlers) handler(dt, this.time);
        this.composer.render(dt);
        this.govern(dt, now);
    }

    /** One full frame, used while booting to warm shaders up. */
    renderOnce() {
        this.composer.render(0.016);
    }

    async compile() {
        try {
            if (this.renderer.compileAsync) await this.renderer.compileAsync(this.scene, this.camera);
            else this.renderer.compile(this.scene, this.camera);
        } catch {
            this.renderer.compile(this.scene, this.camera);
        }
    }

    /** Watch frame times; shed quality before the flight turns into a slideshow. */
    govern(dt, now) {
        const g = this.governor;
        g.frames++;
        if (g.hold || g.frames < 90 || document.hidden) {
            g.lastCheck = now;
            return;
        }
        g.ema = g.ema * 0.94 + dt * 0.06;
        if (now - g.lastCheck < 2200) return;
        g.lastCheck = now;

        // Under ~27 fps. Not 30: a device capped there (iOS Low Power Mode,
        // Chrome's Energy Saver) runs a steady 33 ms, and would otherwise be
        // stepped all the way down to a blurry floor it never needed.
        if (g.ema > 1 / 27) this.degrade(g.ema);
    }

    /** Pause judging performance (a jump, a menu opening, anything that is a burst on purpose). */
    holdGovernor(ms = 1500) {
        const g = this.governor;
        g.hold = true;
        clearTimeout(this.holdTimer);
        this.holdTimer = setTimeout(() => {
            g.hold = false;
            g.lastCheck = performance.now();
        }, ms);
    }

    degrade(ema) {
        const g = this.governor;

        if (this.tier.dpr > 1 && Math.min(window.devicePixelRatio || 1, this.tier.dpr) > 1) {
            this.tier.dpr = Math.max(1, this.tier.dpr - 0.5);
            this.resize(true);
        } else if (this.tier.msaa > 0) {
            this.tier.msaa = 0;
            this.composer.renderTarget1.samples = 0;
            this.composer.renderTarget2.samples = 0;
            this.composer.renderTarget1.dispose();
            this.composer.renderTarget2.dispose();
        } else if (this.bloom.enabled) {
            this.bloom.enabled = false;
            this.tier.bloom = false;
        } else if (this.tier.density > 0.4) {
            this.tier.density = 0.35;
        } else if (this.tier.dpr > 0.75) {
            this.tier.dpr = 0.75;
            this.resize(true);
        } else {
            // Nothing left to give. A steady 30 fps (iOS Low Power Mode caps
            // it there) is still a fine flight; below ~24 it is not.
            if (ema > 1 / 24) this.onGiveUp?.();
            return;
        }
        g.step++;
        g.ema = 1 / 45; // give the new setting a fair start
        this.onDegrade?.(this.tier, g.step);
    }
}
