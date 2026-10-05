/* The product hub's 3D layer: one WebGL canvas fixed behind the page.
 *
 * - The sky is a cube map, re-rendered only while its tint is changing.
 * - Planet surfaces are baked once into equirect textures; the per-frame cost
 *   is a texture fetch plus lighting.
 * - Things that belong to a section (the spotlight "device" with the
 *   product's real picture, the studio core) are children of the camera and
 *   are pinned every frame to the screen rect of a DOM "stage" element, so
 *   they scroll with the page while the camera flies through space behind.
 *
 * Talks to the page through window events only: xph:feature (product id),
 * xph:spin (drag px), xph:motion. Missing WebGL → html.no-webgl and the page
 * keeps its CSS fallback.
 */
import * as THREE from 'three';
import {
  ATMO_FRAG, BAKE_FRAG, BAKE_VERT, GLYPH_FRAG, GLYPH_VERT, HALO_FRAG, HALO_VERT,
  LINK_FRAG, LINK_VERT, PLANET_FRAG, PLANET_VERT, PLATE_FRAG, POINTS_FRAG, POINTS_VERT,
  RING_FRAG, RING_VERT, SCREEN_FRAG, SCREEN_VERT, SKY_FRAG, SKY_VERT,
} from 'xph/shaders';

const XPH = window.XPH;
const cat = XPH.catalog;
const root = document.documentElement;

const SCREEN_W = 2.42;
const SCREEN_ASPECT = 16 / 9;
const SCREEN_H = SCREEN_W / SCREEN_ASPECT;
const TEX_W = 1280;
const TEX_H = 720;
const GLYPHS = ['{', '}', '<', '>', '/', ';', '=', '(', ')', '[', ']', '0', '1', '#', '$', '&', '*', '+', 'λ', '→',
  '=>', '&&', '||', '::', '++', '//', '</>', '{}', '[]', '()', '0x', 'fn', 'if', '01', '10', '!=', '==', '->', '<=',
  '>=', '%', '@', '?', '~', '^', '|', '`', '…'];

const ease = (t) => (t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2);

function mulberry(a) {
  return () => {
    a |= 0;
    a = (a + 0x6d2b79f5) | 0;
    let t = Math.imul(a ^ (a >>> 15), 1 | a);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
  };
}

function randomDir(rnd) {
  const u = rnd() * 2 - 1;
  const t = rnd() * Math.PI * 2;
  const s = Math.sqrt(1 - u * u);
  return new THREE.Vector3(s * Math.cos(t), u, s * Math.sin(t));
}

function seedOf(id) {
  let h = 7;
  for (const c of id) h = (h * 31 + c.charCodeAt(0)) % 997;
  return h / 97;
}

const worldOf = (p) => ({ palette: p.palette, style: p.planet, seed: seedOf(p.id) });

/** The pictures a product shows on the screen: its real screenshots, else its key art. */
const picturesOf = (p) => (p.shots && p.shots.length ? p.shots : [p.art]);

class HubEngine {
  constructor(canvas, motion) {
    this.canvas = canvas;
    this.motion = motion;
    this.scene = new THREE.Scene();
    this.camera = new THREE.PerspectiveCamera(45, 1, 0.1, 2500);
    this.timer = new THREE.Timer();
    this.raf = 0;
    this.running = false;
    this.needsRender = true;
    this.time = 0;
    this.w = 1;
    this.h = 1;
    this.dpr = 1;
    this.slowFrames = 0;
    this.disposables = [];
    this.pointMats = [];
    this.lightDir = new THREE.Vector3(-0.62, 0.42, 0.66).normalize();
    this.mouse = new THREE.Vector2();
    this.mouseS = new THREE.Vector2();
    this.scrollS = 0;
    this.fovKick = 0;

    const small = Math.min(window.innerWidth, window.innerHeight) < 700;
    this.maxDpr = small ? 1.4 : 1.75;
    this.renderer = new THREE.WebGLRenderer({ canvas, antialias: !small, alpha: false, powerPreference: 'high-performance' });
    this.renderer.setClearColor(0x04050c, 1);
    const ext = this.renderer.extensions;
    this.texType = ext.has('EXT_color_buffer_float') || ext.has('EXT_color_buffer_half_float') ? THREE.HalfFloatType : THREE.UnsignedByteType;
    this.maxAniso = this.renderer.capabilities.getMaxAnisotropy();
    this.scene.add(this.camera);

    this.buildSky();
    this.buildBake();
    this.atlas = this.buildAtlas();
    this.buildStars();
    this.buildHero();
    this.buildCore();
    this.buildBigPlanets();
    this.resize();
  }

  /* ------------------------------------------------------------------ build */

  buildSky() {
    this.skyScene = new THREE.Scene();
    this.skyMat = new THREE.ShaderMaterial({
      vertexShader: SKY_VERT,
      fragmentShader: SKY_FRAG,
      side: THREE.BackSide,
      depthWrite: false,
      uniforms: { uA: { value: new THREE.Color() }, uB: { value: new THREE.Color() }, uC: { value: new THREE.Color() } },
    });
    const sky = new THREE.Mesh(new THREE.SphereGeometry(10, 64, 32), this.skyMat);
    this.skyScene.add(sky);
    this.cubeRT = new THREE.WebGLCubeRenderTarget(512, { type: this.texType, generateMipmaps: false, minFilter: THREE.LinearFilter, magFilter: THREE.LinearFilter });
    this.cubeCam = new THREE.CubeCamera(0.1, 100, this.cubeRT);
    this.scene.background = this.cubeRT.texture;
    this.skyFrom = [new THREE.Color(), new THREE.Color(), new THREE.Color()];
    this.skyTo = [new THREE.Color(), new THREE.Color(), new THREE.Color()];
    this.skyT = 1;
    this.skyDirty = true;
    this.disposables.push(this.cubeRT, sky.geometry, this.skyMat);
  }

  buildBake() {
    this.bakeScene = new THREE.Scene();
    this.bakeCam = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);
    this.bakeMat = new THREE.ShaderMaterial({
      vertexShader: BAKE_VERT,
      fragmentShader: BAKE_FRAG,
      depthTest: false,
      depthWrite: false,
      uniforms: { uA: { value: new THREE.Color() }, uB: { value: new THREE.Color() }, uC: { value: new THREE.Color() }, uStyle: { value: 0 }, uSeed: { value: 0 } },
    });
    const quad = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), this.bakeMat);
    quad.frustumCulled = false;
    this.bakeScene.add(quad);
    this.disposables.push(quad.geometry, this.bakeMat);
  }

  /** 8×8 atlas of code glyphs, drawn once into a canvas. */
  buildAtlas() {
    const c = document.createElement('canvas');
    c.width = c.height = 512;
    const g = c.getContext('2d');
    g.fillStyle = '#000';
    g.fillRect(0, 0, 512, 512);
    g.fillStyle = '#fff';
    g.textAlign = 'center';
    g.textBaseline = 'middle';
    GLYPHS.forEach((s, i) => {
      const x = (i % 8) * 64 + 32;
      const y = Math.floor(i / 8) * 64 + 33;
      g.font = `600 ${s.length > 2 ? 22 : s.length > 1 ? 28 : 38}px "JetBrains Mono", ui-monospace, Consolas, monospace`;
      g.fillText(s, x, y);
    });
    const t = new THREE.CanvasTexture(c);
    t.colorSpace = THREE.NoColorSpace;
    t.minFilter = THREE.LinearFilter;
    t.generateMipmaps = false;
    this.disposables.push(t);
    return t;
  }

  makeRT(w, h) {
    const rt = new THREE.WebGLRenderTarget(w, h, {
      type: this.texType,
      generateMipmaps: true,
      minFilter: THREE.LinearMipmapLinearFilter,
      magFilter: THREE.LinearFilter,
      wrapS: THREE.RepeatWrapping,
    });
    rt.texture.anisotropy = 4;
    this.disposables.push(rt);
    return rt;
  }

  bake(world, rt) {
    const u = this.bakeMat.uniforms;
    u.uA.value.set(world.palette[0]);
    u.uB.value.set(world.palette[1]);
    u.uC.value.set(world.palette[2]);
    u.uStyle.value = world.style;
    u.uSeed.value = world.seed;
    this.renderer.setRenderTarget(rt);
    this.renderer.render(this.bakeScene, this.bakeCam);
    this.renderer.setRenderTarget(null);
  }

  planetMaterial(rt, accent, glow = 1) {
    const mat = new THREE.ShaderMaterial({
      vertexShader: PLANET_VERT,
      fragmentShader: PLANET_FRAG,
      uniforms: {
        uTex0: { value: rt.texture }, uTex1: { value: rt.texture }, uMix: { value: 0 },
        uC0: { value: new THREE.Color(accent) }, uC1: { value: new THREE.Color(accent) },
        uLight: { value: this.lightDir }, uGlow: { value: glow },
      },
    });
    this.disposables.push(mat);
    return mat;
  }

  atmoMaterial(color, power) {
    const mat = new THREE.ShaderMaterial({
      vertexShader: PLANET_VERT,
      fragmentShader: ATMO_FRAG,
      side: THREE.BackSide,
      blending: THREE.AdditiveBlending,
      transparent: true,
      depthWrite: false,
      uniforms: { uColor: { value: new THREE.Color(color) }, uLight: { value: this.lightDir }, uPower: { value: power } },
    });
    this.disposables.push(mat);
    return mat;
  }

  haloMaterial(color, strength) {
    const mat = new THREE.ShaderMaterial({
      vertexShader: HALO_VERT,
      fragmentShader: HALO_FRAG,
      transparent: true,
      depthWrite: false,
      blending: THREE.AdditiveBlending,
      uniforms: { uColor: { value: new THREE.Color(color) }, uStrength: { value: strength } },
    });
    this.disposables.push(mat);
    return mat;
  }

  pointsObject(geo, mat, parent = this.scene) {
    const p = new THREE.Points(geo, mat);
    p.frustumCulled = false;
    parent.add(p);
    this.pointMats.push(mat);
    this.disposables.push(geo, mat);
    return p;
  }

  pointGeometry(pos, size, phase, col, glyph) {
    const g = new THREE.BufferGeometry();
    g.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    g.setAttribute('aSize', new THREE.BufferAttribute(size, 1));
    g.setAttribute('aPhase', new THREE.BufferAttribute(phase, 1));
    g.setAttribute('aColor', new THREE.BufferAttribute(col, 3));
    if (glyph) g.setAttribute('aGlyph', new THREE.BufferAttribute(glyph, 1));
    return g;
  }

  pointsMaterial(atten) {
    return new THREE.ShaderMaterial({
      vertexShader: POINTS_VERT,
      fragmentShader: POINTS_FRAG,
      transparent: true,
      depthWrite: false,
      blending: THREE.AdditiveBlending,
      uniforms: { uTime: { value: 0 }, uPR: { value: 1 }, uAtten: { value: atten } },
    });
  }

  glyphMaterial(atten, k = 1) {
    return new THREE.ShaderMaterial({
      vertexShader: GLYPH_VERT,
      fragmentShader: GLYPH_FRAG,
      transparent: true,
      depthWrite: false,
      blending: THREE.AdditiveBlending,
      uniforms: { uTime: { value: 0 }, uPR: { value: 1 }, uAtten: { value: atten }, uK: { value: k }, uAtlas: { value: this.atlas } },
    });
  }

  buildStars() {
    const rnd = mulberry(11);
    // far stars
    const N = 3200;
    const pos = new Float32Array(N * 3);
    const size = new Float32Array(N);
    const phase = new Float32Array(N);
    const col = new Float32Array(N * 3);
    const tints = ['#ffffff', '#cfd8ff', '#b9a4ff', '#67e8f9', '#f5c56b'].map((c) => new THREE.Color(c));
    for (let i = 0; i < N; i++) {
      const v = randomDir(rnd).multiplyScalar(700 + rnd() * 500);
      pos.set([v.x, v.y, v.z], i * 3);
      size[i] = rnd() < 0.04 ? 2.6 + rnd() * 2.2 : 0.8 + rnd() * 1.5;
      phase[i] = rnd();
      const c = tints[rnd() < 0.82 ? (rnd() < 0.6 ? 0 : 1) : 2 + Math.floor(rnd() * 3)];
      const b = 0.35 + rnd() * 0.65;
      col.set([c.r * b, c.g * b, c.b * b], i * 3);
    }
    this.stars = this.pointsObject(this.pointGeometry(pos, size, phase, col), this.pointsMaterial(0));
    this.stars.renderOrder = -2;

    // dust along the camera path: what makes scrolling feel like flying
    const M = 760;
    const dp = new Float32Array(M * 3);
    const ds = new Float32Array(M);
    const dph = new Float32Array(M);
    const dc = new Float32Array(M * 3);
    const dt = ['#8b5cf6', '#38e1ff', '#f5c56b', '#ffffff'].map((c) => new THREE.Color(c));
    for (let i = 0; i < M; i++) {
      let x = 0;
      let y = 0;
      do {
        x = (rnd() - 0.5) * 140;
        y = (rnd() - 0.5) * 90;
      } while (Math.hypot(x, y) < 9);
      dp.set([x, y, 30 - rnd() * 360], i * 3);
      ds[i] = 1.2 + rnd() * 3.5;
      dph[i] = rnd();
      const c = dt[Math.floor(rnd() * dt.length)];
      const b = 0.25 + rnd() * 0.45;
      dc.set([c.r * b, c.g * b, c.b * b], i * 3);
    }
    this.dust = this.pointsObject(this.pointGeometry(dp, ds, dph, dc), this.pointsMaterial(160));
    this.dust.renderOrder = -1;

    // code drifting with the dust: the dev-house touch
    const G = 300;
    const gp = new Float32Array(G * 3);
    const gs = new Float32Array(G);
    const gph = new Float32Array(G);
    const gc = new Float32Array(G * 3);
    const gg = new Float32Array(G);
    const gt = ['#67e8f9', '#a78bfa', '#f5c56b'].map((c) => new THREE.Color(c));
    for (let i = 0; i < G; i++) {
      let x = 0;
      let y = 0;
      do {
        x = (rnd() - 0.5) * 120;
        y = (rnd() - 0.5) * 80;
      } while (Math.hypot(x, y) < 11);
      gp.set([x, y, 20 - rnd() * 330], i * 3);
      gs[i] = 4 + rnd() * 5;
      gph[i] = rnd();
      gg[i] = Math.floor(rnd() * GLYPHS.length);
      const c = gt[Math.floor(rnd() * gt.length)];
      const b = 0.18 + rnd() * 0.3;
      gc.set([c.r * b, c.g * b, c.b * b], i * 3);
    }
    this.codeDust = this.pointsObject(this.pointGeometry(gp, gs, gph, gc, gg), this.glyphMaterial(160));
    this.codeDust.renderOrder = -1;
  }

  buildHero() {
    this.hero = new THREE.Group();

    // halo behind everything
    this.heroHalo = this.haloMaterial('#6b46ff', 0.5);
    const halo = new THREE.Mesh(new THREE.PlaneGeometry(6.4, 6.4), this.heroHalo);
    halo.position.set(0.3, 0.1, -2.4);
    halo.renderOrder = -1;
    this.disposables.push(halo.geometry);

    // the product's planet, behind the device
    this.heroRT = [this.makeRT(1024, 512), this.makeRT(1024, 512)];
    this.heroFront = 0;
    this.heroMix = 1;
    this.heroMat = this.planetMaterial(this.heroRT[0], '#67e8f9', 1.1);
    const sphere = new THREE.SphereGeometry(1, 96, 64);
    this.heroPlanet = new THREE.Mesh(sphere, this.heroMat);
    this.heroAtmo = this.atmoMaterial('#67e8f9', 1.6);
    const atmo = new THREE.Mesh(sphere, this.heroAtmo);
    atmo.scale.setScalar(1.14);
    this.heroRing = new THREE.ShaderMaterial({
      vertexShader: RING_VERT,
      fragmentShader: RING_FRAG,
      side: THREE.DoubleSide,
      transparent: true,
      depthWrite: false,
      blending: THREE.AdditiveBlending,
      uniforms: { uA: { value: new THREE.Color('#c9d2ff') }, uB: { value: new THREE.Color('#6b4cff') }, uTime: { value: 0 }, uInner: { value: 1.38 }, uOuter: { value: 2.05 } },
    });
    const ring = new THREE.Mesh(new THREE.RingGeometry(1.38, 2.05, 160, 1), this.heroRing);
    ring.renderOrder = 2;
    const ringTilt = new THREE.Group();
    // open enough to read as a ring, not a line across the stage
    ringTilt.rotation.x = Math.PI / 2 - 0.5;
    ringTilt.add(ring);
    this.heroSpin = new THREE.Group();
    this.heroSpin.add(this.heroPlanet);
    const planetTilt = new THREE.Group();
    planetTilt.rotation.set(0.32, 0, -0.28);
    planetTilt.add(this.heroSpin, atmo, ringTilt);
    this.planetGroup = new THREE.Group();
    // the product's world rises behind the device, its ring circling past the screen
    this.planetGroup.position.set(0.32, 0.5, -2.3);
    this.planetGroup.scale.setScalar(1.12);
    this.planetGroup.add(planetTilt);
    this.rotY = 0.6;
    this.rotV = 0.0025;
    this.disposables.push(sphere, ring.geometry, this.heroRing);

    // the device: back plate + curved glass showing the product's real picture
    this.device = new THREE.Group();
    this.deviceYaw = 0;
    this.deviceYawV = 0;
    const plateGeo = new THREE.PlaneGeometry(SCREEN_W * 1.035, SCREEN_H * 1.06, 1, 1);
    this.plateMat = new THREE.ShaderMaterial({
      vertexShader: HALO_VERT,
      fragmentShader: PLATE_FRAG,
      transparent: true,
      depthWrite: false,
      uniforms: { uAspect: { value: (SCREEN_W * 1.035) / (SCREEN_H * 1.06) }, uAccent: { value: new THREE.Color('#67e8f9') } },
    });
    const plate = new THREE.Mesh(plateGeo, this.plateMat);
    plate.position.z = -0.09;
    const blank = this.blankTexture();
    this.screenMat = new THREE.ShaderMaterial({
      vertexShader: SCREEN_VERT,
      fragmentShader: SCREEN_FRAG,
      transparent: true,
      depthWrite: false,
      side: THREE.DoubleSide,
      uniforms: {
        uTex0: { value: blank }, uTex1: { value: blank }, uMix: { value: 1 }, uTime: { value: 0 },
        uAspect: { value: SCREEN_ASPECT }, uAccent: { value: new THREE.Color('#67e8f9') }, uPower: { value: 1.0 }, uBend: { value: 0.07 },
      },
    });
    const screenGeo = new THREE.PlaneGeometry(SCREEN_W, SCREEN_H, 48, 1);
    this.screen = new THREE.Mesh(screenGeo, this.screenMat);
    this.screen.renderOrder = 3;
    // a soft pool of light under the device
    this.floorMat = this.haloMaterial('#67e8f9', 0.35);
    const floor = new THREE.Mesh(new THREE.PlaneGeometry(3.2, 0.9), this.floorMat);
    floor.position.set(0, -SCREEN_H * 0.62, -0.2);
    this.device.add(floor, plate, this.screen);
    this.deviceY = -0.15;
    this.device.position.set(0, this.deviceY, 0.25);
    this.disposables.push(plateGeo, screenGeo, this.plateMat, this.screenMat, floor.geometry);

    // a ring of code glyphs orbiting the device
    const rnd = mulberry(23);
    const R = 96;
    const pos = new Float32Array(R * 3);
    const size = new Float32Array(R);
    const phase = new Float32Array(R);
    const col = new Float32Array(R * 3);
    const gl = new Float32Array(R);
    for (let i = 0; i < R; i++) {
      const a = (i / R) * Math.PI * 2 + rnd() * 0.05;
      const r = 1.72 + (rnd() - 0.5) * 0.12;
      pos.set([Math.cos(a) * r, (rnd() - 0.5) * 0.08, Math.sin(a) * r], i * 3);
      size[i] = 12 + rnd() * 9;
      phase[i] = rnd();
      gl[i] = Math.floor(rnd() * GLYPHS.length);
      col.set([1, 1, 1], i * 3);
    }
    this.glyphGeo = this.pointGeometry(pos, size, phase, col, gl);
    this.glyphMat = this.glyphMaterial(0, 1);
    this.glyphRing = new THREE.Group();
    this.glyphRing.rotation.set(0.28, 0, -0.12);
    this.glyphSpin = new THREE.Group();
    this.glyphRing.add(this.glyphSpin);
    this.glyphSpin.add(new THREE.Points(this.glyphGeo, this.glyphMat));
    this.glyphSpin.children[0].frustumCulled = false;
    this.pointMats.push(this.glyphMat);
    this.disposables.push(this.glyphGeo, this.glyphMat);
    this.glyphRing.position.copy(this.device.position);

    this.hero.add(halo, this.planetGroup, this.glyphRing, this.device);
    this.hero.visible = false;
    this.camera.add(this.hero);

    // screen pictures
    this.loader = new THREE.TextureLoader();
    this.texCache = new Map();
    this.screenFrom = blank;
    this.screenMix = 1;
    this.slide = { id: null, list: [], i: 0, next: 0 };
    this.heroPulse = 1;
  }

  blankTexture() {
    const c = document.createElement('canvas');
    c.width = 64;
    c.height = 36;
    const g = c.getContext('2d');
    const gr = g.createLinearGradient(0, 0, 64, 36);
    gr.addColorStop(0, '#0b1024');
    gr.addColorStop(1, '#141032');
    g.fillStyle = gr;
    g.fillRect(0, 0, 64, 36);
    const t = new THREE.CanvasTexture(c);
    t.colorSpace = THREE.SRGBColorSpace;
    this.disposables.push(t);
    return t;
  }

  buildCore() {
    this.core = new THREE.Group();
    this.coreSpin = new THREE.Group();
    this.orbit = new THREE.Group();
    this.minis = [];

    // the XMAN "X": two crossing bars
    const bars = [
      [12, 15, 25, 15, 52, 49, 39, 49],
      [39, 15, 52, 15, 25, 49, 12, 49],
    ].map((b) => {
      const s = new THREE.Shape();
      const pt = (i) => new THREE.Vector2((b[i] - 32) / 18, -(b[i + 1] - 32) / 18);
      s.moveTo(pt(0).x, pt(0).y);
      for (let i = 2; i < 8; i += 2) s.lineTo(pt(i).x, pt(i).y);
      s.closePath();
      return s;
    });
    const xg = new THREE.ExtrudeGeometry(bars, { depth: 0.34, bevelEnabled: true, bevelThickness: 0.06, bevelSize: 0.05, bevelSegments: 3 });
    xg.center();
    const xm = new THREE.MeshStandardMaterial({ color: 0x5ee7ff, emissive: 0x1d6f9a, emissiveIntensity: 0.6, roughness: 0.26, metalness: 0.55 });
    const x = new THREE.Mesh(xg, xm);
    const edges = new THREE.LineSegments(new THREE.EdgesGeometry(xg, 30), new THREE.LineBasicMaterial({ color: 0xe0f8ff, transparent: true, opacity: 0.55 }));
    x.add(edges);
    this.coreSpin.add(x);

    const ringA = new THREE.Mesh(new THREE.TorusGeometry(1.75, 0.012, 8, 180), new THREE.MeshBasicMaterial({ color: 0x67e8f9, transparent: true, opacity: 0.7 }));
    ringA.rotation.x = Math.PI / 2 - 0.35;
    const ringB = new THREE.Mesh(new THREE.TorusGeometry(2.35, 0.008, 8, 200), new THREE.MeshBasicMaterial({ color: 0x8b5cf6, transparent: true, opacity: 0.6 }));
    ringB.rotation.set(Math.PI / 2 + 0.5, 0.3, 0);
    this.orbit.rotation.set(0.42, 0, -0.18);

    const halo = new THREE.Mesh(new THREE.PlaneGeometry(6, 6), this.haloMaterial('#1f4f7a', 0.6));
    halo.position.z = -1.2;

    const key = new THREE.DirectionalLight(0xffffff, 2.6);
    key.position.set(-3, 4, 5);
    this.core.add(key.target);
    const rim = new THREE.PointLight(0x8b5cf6, 30, 12);
    rim.position.set(2.5, -1.5, 1.5);
    const fill = new THREE.PointLight(0xf5c56b, 10, 10);
    fill.position.set(-2, -2, 3);
    this.core.add(new THREE.AmbientLight(0x6070a0, 0.8), key, rim, fill, halo, this.coreSpin, ringA, ringB, this.orbit);
    this.core.visible = false;
    this.camera.add(this.core);
    this.disposables.push(xg, xm, edges.geometry, edges.material, ringA.geometry, ringA.material, ringB.geometry, ringB.material, halo.geometry);
  }

  /** One node per product orbiting the studio core, each wired back to it. */
  setNodes(products) {
    const g = new THREE.SphereGeometry(0.15, 40, 24);
    this.disposables.push(g);
    const n = products.length;
    const lp = new Float32Array(n * 2 * 3);
    const lt = new Float32Array(n * 2);
    const lph = new Float32Array(n * 2);
    const lc = new Float32Array(n * 2 * 3);
    products.forEach((p, i) => {
      const rt = this.makeRT(256, 128);
      this.bake(worldOf(p), rt);
      const mesh = new THREE.Mesh(g, this.planetMaterial(rt, p.palette[2], 1.3));
      const a = (i / n) * Math.PI * 2;
      const r = 2.95;
      mesh.userData.a = a;
      mesh.position.set(Math.cos(a) * r, 0, Math.sin(a) * r);
      this.orbit.add(mesh);
      this.minis.push(mesh);
      const c = new THREE.Color(p.palette[2]);
      lp.set([Math.cos(a) * 0.55, 0, Math.sin(a) * 0.55, Math.cos(a) * (r - 0.18), 0, Math.sin(a) * (r - 0.18)], i * 6);
      lt.set([0, 1], i * 2);
      lph.set([i / n, i / n], i * 2);
      lc.set([c.r, c.g, c.b, c.r, c.g, c.b], i * 6);
    });
    const lg = new THREE.BufferGeometry();
    lg.setAttribute('position', new THREE.BufferAttribute(lp, 3));
    lg.setAttribute('aT', new THREE.BufferAttribute(lt, 1));
    lg.setAttribute('aPhase', new THREE.BufferAttribute(lph, 1));
    lg.setAttribute('aColor', new THREE.BufferAttribute(lc, 3));
    this.linkMat = new THREE.ShaderMaterial({
      vertexShader: LINK_VERT,
      fragmentShader: LINK_FRAG,
      transparent: true,
      depthWrite: false,
      blending: THREE.AdditiveBlending,
      uniforms: { uTime: { value: 0 } },
    });
    this.orbit.add(new THREE.LineSegments(lg, this.linkMat));
    this.disposables.push(lg, this.linkMat);
    this.needsRender = true;
  }

  buildBigPlanets() {
    this.bigPlanets = [];
    const defs = [
      { w: { palette: ['#7c5cff', '#1a1450', '#8fe3ff'], style: 0, seed: 1.7 }, pos: [-150, -78, -420], r: 70, atmo: '#7c5cff' },
      { w: { palette: ['#38e1ff', '#0c2236', '#a78bfa'], style: 3, seed: 6.3 }, pos: [-260, 40, -520], r: 24, atmo: '#38e1ff' },
    ];
    const g = new THREE.SphereGeometry(1, 64, 40);
    this.disposables.push(g);
    for (const d of defs) {
      const rt = this.makeRT(512, 256);
      this.bake(d.w, rt);
      const m = new THREE.Mesh(g, this.planetMaterial(rt, d.w.palette[2], 0.8));
      m.position.set(...d.pos);
      m.scale.setScalar(d.r);
      const a = new THREE.Mesh(g, this.atmoMaterial(d.atmo, 1.1));
      a.scale.setScalar(1.12);
      m.add(a);
      this.scene.add(m);
      this.bigPlanets.push(m);
    }
  }

  /* ---------------------------------------------------------------- pictures */

  /** The product's picture framed as an app window (title bar + image), as a texture. */
  windowTexture(p, url) {
    const key = p.id + '|' + url;
    if (this.texCache.has(key)) {
      const hit = this.texCache.get(key);
      this.texCache.delete(key);
      this.texCache.set(key, hit);
      return hit;
    }
    const entry = { ready: false, tex: null, waiters: [] };
    this.texCache.set(key, entry);
    const im = new Image();
    im.decoding = 'async';
    im.onload = () => {
      const c = document.createElement('canvas');
      c.width = TEX_W;
      c.height = TEX_H;
      const g = c.getContext('2d');
      const bar = 40;
      g.fillStyle = '#070a16';
      g.fillRect(0, 0, TEX_W, TEX_H);
      // cover-fit the picture under the title bar
      const aw = TEX_W;
      const ah = TEX_H - bar;
      const s = Math.max(aw / im.width, ah / im.height);
      const dw = im.width * s;
      const dh = im.height * s;
      g.drawImage(im, (aw - dw) / 2, bar + (ah - dh) / 2, dw, dh);
      // title bar
      const tb = g.createLinearGradient(0, 0, 0, bar);
      tb.addColorStop(0, '#141a33');
      tb.addColorStop(1, '#0c1024');
      g.fillStyle = tb;
      g.fillRect(0, 0, TEX_W, bar);
      g.fillStyle = p.palette[2];
      g.globalAlpha = 0.85;
      g.fillRect(0, bar - 2, TEX_W, 2);
      g.globalAlpha = 1;
      ['#ff5f57', '#febc2e', '#28c840'].forEach((dot, i) => {
        g.fillStyle = dot;
        g.beginPath();
        g.arc(24 + i * 22, bar / 2, 6.5, 0, Math.PI * 2);
        g.fill();
      });
      g.font = '600 17px "JetBrains Mono", ui-monospace, Consolas, monospace';
      g.textBaseline = 'middle';
      g.fillStyle = '#e8ecff';
      g.fillText(p.name, 100, bar / 2 + 1);
      const nameW = g.measureText(p.name).width;
      g.fillStyle = '#7d86ad';
      g.font = '500 14px "JetBrains Mono", ui-monospace, Consolas, monospace';
      const meta = (p.version ? 'v' + p.version + '  ·  ' : '') + p.platforms.join(' / ');
      g.fillText(meta, 100 + nameW + 18, bar / 2 + 1);
      const real = p.shots && p.shots.length && url !== p.art;
      if (real) {
        g.textAlign = 'right';
        g.fillStyle = '#5eead4';
        g.fillText('● LIVE CAPTURE', TEX_W - 22, bar / 2 + 1);
        g.textAlign = 'left';
      }
      const t = new THREE.CanvasTexture(c);
      t.colorSpace = THREE.SRGBColorSpace;
      t.anisotropy = Math.min(8, this.maxAniso);
      t.generateMipmaps = true;
      t.minFilter = THREE.LinearMipmapLinearFilter;
      entry.tex = t;
      entry.ready = true;
      entry.waiters.splice(0).forEach((fn) => fn(t));
      this.trimCache();
    };
    im.onerror = () => {
      this.texCache.delete(key);
      entry.waiters.splice(0).forEach((fn) => fn(null));
    };
    im.src = url;
    return entry;
  }

  trimCache() {
    const keep = 8;
    const live = new Set([this.screenMat.uniforms.uTex0.value, this.screenMat.uniforms.uTex1.value]);
    for (const [k, e] of this.texCache) {
      if (this.texCache.size <= keep) break;
      if (!e.ready || live.has(e.tex)) continue;
      e.tex.dispose();
      this.texCache.delete(k);
    }
  }

  /** Show a picture on the glass: the new one boots in behind a scan line. */
  showPicture(p, url, animate) {
    const entry = this.windowTexture(p, url);
    const apply = (t) => {
      if (!t || this.slide.id !== p.id) return;
      const u = this.screenMat.uniforms;
      if (u.uTex1.value === t) return;
      if (!animate || !this.motion) {
        u.uTex0.value = t;
        u.uTex1.value = t;
        u.uMix.value = 1;
        this.screenMix = 1;
      } else {
        u.uTex0.value = u.uTex1.value;
        u.uTex1.value = t;
        u.uMix.value = 0;
        this.screenMix = 0;
      }
      this.invalidate();
    };
    if (entry.ready) apply(entry.tex);
    else entry.waiters.push(apply);
  }

  /** Warm the cache for the first picture of every featured product. */
  preload(ids) {
    for (const id of ids) {
      const p = cat.byId(id);
      if (p) this.windowTexture(p, picturesOf(p)[0]);
    }
  }

  /* ---------------------------------------------------------------- control */

  /** Switch the spotlight product: bake its planet into the back buffer, cross-fade, boot its picture. */
  setFeatured(p, animate = true) {
    const world = worldOf(p);
    const back = 1 - this.heroFront;
    this.bake(world, this.heroRT[back]);
    const u = this.heroMat.uniforms;
    if (!animate) {
      u.uTex0.value = this.heroRT[back].texture;
      u.uTex1.value = this.heroRT[back].texture;
      u.uC0.value.set(world.palette[2]);
      u.uC1.value.set(world.palette[2]);
      u.uMix.value = 0;
      this.heroMix = 1;
    } else {
      u.uTex0.value = this.heroRT[this.heroFront].texture;
      u.uTex1.value = this.heroRT[back].texture;
      u.uC0.value.copy(u.uC1.value);
      u.uC1.value.set(world.palette[2]);
      u.uMix.value = 0;
      this.heroMix = 0;
      this.heroPulse = 0;
      this.fovKick = 1;
      this.rotV += 0.05;
      this.deviceYawV -= 0.9;
    }
    this.heroFront = back;
    this.heroAtmo.uniforms.uColor.value.set(world.palette[2]);
    this.heroRing.uniforms.uA.value.set(world.palette[2]).lerp(new THREE.Color('#ffffff'), 0.45);
    this.heroRing.uniforms.uB.value.set(world.palette[0]);
    this.heroHalo.uniforms.uColor.value.set(world.palette[0]);
    this.screenMat.uniforms.uAccent.value.set(world.palette[2]);
    this.plateMat.uniforms.uAccent.value.set(world.palette[2]);
    this.floorMat.uniforms.uColor.value.set(world.palette[2]);
    const gc = new THREE.Color(world.palette[2]).lerp(new THREE.Color('#ffffff'), 0.25);
    const ga = this.glyphGeo.getAttribute('aColor');
    for (let i = 0; i < ga.count; i++) {
      const k = i % 5 === 0 ? 1.0 : 0.55;
      ga.setXYZ(i, gc.r * k, gc.g * k, gc.b * k);
    }
    ga.needsUpdate = true;
    this.setSky(world.palette, animate);

    const list = picturesOf(p);
    this.slide = { id: p.id, list, i: 0, next: this.time + 5.2 };
    this.showPicture(p, list[0], animate);
    // the next shot of a gallery, ahead of time
    if (list.length > 1) this.windowTexture(p, list[1]);
    this.needsRender = true;
  }

  setSky(pal, animate) {
    const u = this.skyMat.uniforms;
    // a little of the product's colour bleeds into the nebula, never all of it
    const base = [new THREE.Color('#5b3bff'), new THREE.Color('#0e1b4d'), new THREE.Color('#22d3ee')];
    const target = pal.map((c, i) => new THREE.Color(c).lerp(base[i], 0.6));
    for (let i = 0; i < 3; i++) {
      this.skyFrom[i].copy(animate ? [u.uA, u.uB, u.uC][i].value : target[i]);
      this.skyTo[i].copy(target[i]);
    }
    if (!animate) [u.uA, u.uB, u.uC].forEach((un, i) => un.value.copy(target[i]));
    this.skyT = animate ? 0 : 1;
    this.skyDirty = true;
  }

  spin(dx) {
    this.rotV += dx * 0.0009;
    this.deviceYawV += dx * 0.012;
    if (!this.motion) {
      this.rotY += dx * 0.01;
      this.deviceYaw = Math.max(-1.1, Math.min(1.1, this.deviceYaw + dx * 0.006));
    }
    this.invalidate();
  }

  pointer(x, y) {
    this.mouse.set(x, y);
    if (!this.motion) this.mouseS.copy(this.mouse);
  }

  setMotion(on) {
    this.motion = on;
    this.needsRender = true;
    if (on && this.running && !this.raf) this.raf = requestAnimationFrame(this.frame);
  }

  invalidate() {
    this.needsRender = true;
    if (this.running && !this.raf) this.raf = requestAnimationFrame(this.frame);
  }

  resize() {
    const w = this.canvas.clientWidth || window.innerWidth;
    const h = this.canvas.clientHeight || window.innerHeight;
    this.w = w;
    this.h = h;
    this.dpr = Math.min(window.devicePixelRatio || 1, this.maxDpr);
    this.renderer.setPixelRatio(this.dpr);
    this.renderer.setSize(w, h, false);
    this.camera.aspect = w / h;
    this.camera.updateProjectionMatrix();
    for (const m of this.pointMats) m.uniforms.uPR.value = this.dpr;
    this.needsRender = true;
  }

  async warmup() {
    // Compile every program before the first visible frame (Windows/ANGLE
    // compiles are slow; doing it up front avoids a hitch on first scroll).
    this.hero.visible = true;
    this.core.visible = true;
    try {
      await this.renderer.compileAsync(this.scene, this.camera);
    } catch {
      /* older drivers: compile happens on first render instead */
    }
  }

  start() {
    if (this.running) return;
    this.running = true;
    this.timer.reset();
    this.raf = requestAnimationFrame(this.frame);
  }

  stop() {
    this.running = false;
    cancelAnimationFrame(this.raf);
    this.raf = 0;
  }

  /* ------------------------------------------------------------------ frame */

  frame = (ts) => {
    this.raf = 0;
    this.timer.update(ts);
    const dt = Math.min(this.timer.getDelta(), 0.05);
    const animating = this.motion || this.skyT < 1 || this.heroMix < 1 || this.screenMix < 1;
    if (this.running && (animating || this.needsRender)) {
      this.update(animating ? dt : 0);
      this.render();
      this.needsRender = false;
      if (this.motion) this.adapt(dt);
    }
    if (this.running && (this.motion || this.skyT < 1 || this.heroMix < 1 || this.screenMix < 1)) {
      this.raf = requestAnimationFrame(this.frame);
    }
  };

  adapt(dt) {
    // Hold ~45 fps or better: step the pixel ratio down on slow machines.
    if (dt > 0.024) this.slowFrames++;
    else this.slowFrames = Math.max(0, this.slowFrames - 1);
    if (this.slowFrames > 90 && this.dpr > 1) {
      this.maxDpr = Math.max(1, this.dpr - 0.25);
      this.slowFrames = 0;
      this.resize();
    }
  }

  update(dt) {
    this.time += dt;
    const k = this.motion ? 1 - Math.pow(0.0015, dt) : 1;

    // camera: scroll flies forward and turns a little, pointer adds parallax
    const max = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
    const p = Math.min(1, Math.max(0, window.scrollY / max));
    this.scrollS += (p - this.scrollS) * (this.motion ? 1 - Math.pow(0.02, dt) : 1);
    this.mouseS.lerp(this.mouse, k * 0.6);
    this.camera.position.set(this.mouseS.x * 0.8, -this.mouseS.y * 0.5, -this.scrollS * 150);
    this.camera.rotation.set(-this.scrollS * 0.1 + this.mouseS.y * 0.02, this.scrollS * 0.42 - this.mouseS.x * 0.03, 0, 'YXZ');
    if (this.fovKick > 0) this.fovKick = Math.max(0, this.fovKick - dt * 1.6);
    const fov = 45 + Math.sin(this.fovKick * Math.PI) * 3.5;
    if (Math.abs(this.camera.fov - fov) > 1e-3) {
      this.camera.fov = fov;
      this.camera.updateProjectionMatrix();
    }

    for (const m of this.pointMats) m.uniforms.uTime.value = this.time;

    // sky tint
    if (this.skyT < 1) {
      this.skyT = Math.min(1, this.skyT + dt / 1.2);
      const e = ease(this.skyT);
      const u = this.skyMat.uniforms;
      [u.uA, u.uB, u.uC].forEach((un, i) => un.value.copy(this.skyFrom[i]).lerp(this.skyTo[i], e));
      this.skyDirty = true;
    }

    // spotlight planet
    if (this.heroMix < 1) {
      this.heroMix = Math.min(1, this.heroMix + dt / 1.1);
      this.heroMat.uniforms.uMix.value = ease(this.heroMix);
      if (this.heroMix >= 1) {
        const u = this.heroMat.uniforms;
        u.uTex0.value = u.uTex1.value;
        u.uC0.value.copy(u.uC1.value);
        u.uMix.value = 0;
      }
    }
    if (this.heroPulse < 1) this.heroPulse = Math.min(1, this.heroPulse + dt / 0.9);
    this.rotV += (0.0025 - this.rotV) * (this.motion ? 1 - Math.pow(0.25, dt) : 1);
    this.rotY += this.motion ? this.rotV * dt * 60 : 0;
    this.heroSpin.rotation.y = this.rotY;
    this.heroRing.uniforms.uTime.value = this.time;

    // the screen: picture boot-in, then the gallery steps on by itself
    const su = this.screenMat.uniforms;
    su.uTime.value = this.time;
    if (this.screenMix < 1) {
      this.screenMix = Math.min(1, this.screenMix + dt / 0.85);
      su.uMix.value = this.screenMix;
      if (this.screenMix >= 1) su.uTex0.value = su.uTex1.value;
    }
    const sl = this.slide;
    if (this.motion && sl.list.length > 1 && this.time > sl.next && this.hero.visible && !document.hidden) {
      const prod = cat.byId(sl.id);
      sl.i = (sl.i + 1) % sl.list.length;
      sl.next = this.time + 4.6;
      this.showPicture(prod, sl.list[sl.i], true);
      this.windowTexture(prod, sl.list[(sl.i + 1) % sl.list.length]);
      window.dispatchEvent(new CustomEvent('xph:slide', { detail: { id: sl.id, i: sl.i } }));
    }

    // wide stages (phones, mid widths) hold the device big and centred; the tall
    // stage beside Nova sets it lower, so her words above never cover the screen
    const st = this.stageRect('spotlight-stage');
    const tall = !!st && st.height > st.width * 0.95;
    this.deviceY = tall ? -0.42 : -0.12;

    // the device turns on a spring back to its resting three-quarter view
    const rest = -0.2;
    if (this.motion) {
      const acc = -this.deviceYaw * 7.0 - this.deviceYawV * 3.4;
      this.deviceYawV += acc * dt;
      this.deviceYaw += this.deviceYawV * dt;
      this.deviceYaw = Math.max(-1.25, Math.min(1.25, this.deviceYaw));
    }
    const bob = this.motion ? Math.sin(this.time * 0.9) * 0.035 : 0;
    this.device.rotation.set(-0.04 + this.mouseS.y * 0.05, rest + this.deviceYaw + Math.sin(this.time * 0.35) * 0.04, 0);
    this.device.position.y = this.deviceY + bob;
    this.glyphSpin.rotation.y = this.time * 0.18;
    this.glyphRing.position.y = this.deviceY + bob * 0.6;

    this.pin(this.hero, 'spotlight-stage', tall ? 3.25 : 2.55, 2.7);
    if (this.hero.visible) {
      const pulse = 0.92 + 0.08 * ease(this.heroPulse);
      this.hero.scale.multiplyScalar(pulse);
      // glyph size follows the stage scale (px per unit / 100)
      this.glyphMat.uniforms.uK.value = Math.max(0.55, Math.min(1.25, this.heroPx / 150));
    }
    this.hero.rotation.set(this.mouseS.y * 0.06, this.mouseS.x * 0.1, 0);

    // studio core
    this.coreSpin.rotation.y = Math.sin(this.time * 0.5) * 0.45 + this.mouseS.x * 0.3;
    this.coreSpin.rotation.x = this.mouseS.y * 0.2;
    this.orbit.rotation.y = this.time * 0.12;
    for (const m of this.minis) m.rotation.y = this.time * 0.8 + m.userData.a;
    if (this.linkMat) this.linkMat.uniforms.uTime.value = this.time;
    this.pin(this.core, 'core-stage', 1 / 0.24, 1 / 0.24);

    for (const b of this.bigPlanets) b.rotation.y = this.time * 0.01;
  }

  stageRect(id) {
    const el = document.getElementById(id);
    return el ? el.getBoundingClientRect() : null;
  }

  /** Place a camera-child group so it sits on a DOM element's screen rect.
      unitsH / unitsW: how many scene units the element's height / width holds. */
  pin(group, id, unitsH, unitsW) {
    const el = document.getElementById(id);
    if (!el) {
      group.visible = false;
      return;
    }
    const r = el.getBoundingClientRect();
    if (r.width === 0 || r.bottom < -r.height * 0.5 || r.top > this.h + r.height * 0.5) {
      group.visible = false;
      return;
    }
    group.visible = true;
    const D = 12;
    const wpp = (2 * D * Math.tan(THREE.MathUtils.degToRad(this.camera.fov / 2))) / this.h;
    const cx = r.left + r.width / 2;
    const cy = r.top + r.height / 2;
    group.position.set((cx - this.w / 2) * wpp, -(cy - this.h / 2) * wpp, -D);
    const px = Math.min(r.height / unitsH, r.width / unitsW);
    if (group === this.hero) this.heroPx = px;
    group.scale.setScalar(px * wpp);
  }

  render() {
    if (this.skyDirty) {
      this.cubeCam.update(this.renderer, this.skyScene);
      this.skyDirty = false;
    }
    this.renderer.render(this.scene, this.camera);
  }

  dispose() {
    this.stop();
    for (const e of this.texCache.values()) e.tex?.dispose();
    for (const d of this.disposables) d.dispose();
    this.renderer.dispose();
  }
}

/* ------------------------------------------------------------------ boot */

function boot() {
  const canvas = document.getElementById('universe');
  if (!canvas) return;
  let engine;
  try {
    engine = new HubEngine(canvas, XPH.prefs.motion());
  } catch (e) {
    root.classList.add('no-webgl');
    return;
  }
  XPH.engine = engine;
  engine.setNodes(cat.PRODUCTS);
  const first = cat.byId(XPH.state?.featured) || cat.byId(cat.FEATURED[0].id);
  engine.setFeatured(first, false);
  engine.preload(cat.FEATURED.map((f) => f.id));
  let shown = first.id;

  engine.warmup().then(() => {
    engine.start();
    engine.invalidate();
    canvas.classList.add('ready');
    root.classList.add('webgl-ready');
  });

  window.addEventListener('resize', () => {
    engine.resize();
    engine.invalidate();
  });
  window.addEventListener('scroll', () => engine.invalidate(), { passive: true });
  window.addEventListener('pointermove', (e) => {
    if (e.pointerType !== 'mouse') return;
    engine.pointer((e.clientX / window.innerWidth) * 2 - 1, (e.clientY / window.innerHeight) * 2 - 1);
    if (!engine.motion) engine.invalidate();
  }, { passive: true });
  window.addEventListener('xph:spin', (e) => engine.spin(e.detail));
  window.addEventListener('xph:feature', (e) => {
    const p = cat.byId(e.detail);
    if (!p || p.id === shown) return;
    shown = p.id;
    engine.setFeatured(p, XPH.prefs.motion());
    engine.invalidate();
  });
  window.addEventListener('xph:motion', () => {
    engine.setMotion(XPH.prefs.motion());
    engine.invalidate();
  });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) engine.stop();
    else {
      engine.start();
      engine.invalidate();
    }
  });
  canvas.addEventListener('webglcontextlost', (e) => {
    e.preventDefault();
    engine.stop();
    root.classList.add('no-webgl');
    root.classList.remove('webgl-ready');
  });
}

// The glyph atlas and window title bars draw text: give the mono face a moment to arrive.
const fontsReady = document.fonts && document.fonts.load
  ? Promise.race([document.fonts.load('600 32px "JetBrains Mono"'), new Promise((r) => setTimeout(r, 1200))])
  : Promise.resolve();
fontsReady.then(boot, boot);
