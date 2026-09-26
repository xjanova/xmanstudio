/**
 * XMAN UNIVERSE — the full-3D home page.
 *
 * Boots the flight: renderer and world behind the loader, then the gate, the
 * intro dive through the portal, and from there every frame — scroll → camera
 * → world → overlays → HUD → sound.
 *
 * Contract with partials/universe/detect.blade.php: that inline script has
 * already decided this browser can run WebGL2 well enough, set html.xu-js,
 * and armed a watchdog; this module disarms it by setting __xuStarted, and
 * calls window.__xuFallback(reason) to leave for the classic page whenever
 * something goes wrong — a failed boot, a lost GPU context, or a frame rate
 * the governor cannot rescue.
 */
import { Vector3 } from 'three';
import { Sound } from './audio/Sound.js';
import { Engine } from './engine/Engine.js';
import { Journey } from './engine/Journey.js';
import { introStart } from './engine/Path.js';
import { platforms as platformChoreo } from './engine/choreo.js';
import { clamp, damp, easeInOutSine, nextFrame, sleep, smoothstep } from './lib/math.js';
import { ANCHORS } from './world/anchors.js';
import { place, prefs } from './lib/prefs.js';
import { Chat } from './ui/Chat.js';
import { Guide } from './ui/Guide.js';
import { Hud } from './ui/Hud.js';
import { Loader } from './ui/Loader.js';
import { Menu } from './ui/Menu.js';
import { Stations } from './ui/Stations.js';
import { bindPointerPolish } from './ui/Tilt.js';
import { World } from './world/World.js';

window.__xuStarted = true;
clearTimeout(window.__xuWatchdog);

const html = document.documentElement;

function giveUp(reason, transient = false) {
    if (window.__xuFallback) window.__xuFallback(reason, transient);
    else window.location.replace('/?view=classic');
}

const layoutInfo = () => {
    const aspect = window.innerWidth / Math.max(1, window.innerHeight);
    return { aspect, portrait: aspect < 0.9 };
};

// Which menu card the ring opens on, given where the visitor is.
const MENU_FOR = { core: 0, origin: 0, services: 1, products: 2, stack: 1, reviews: 9, launch: 9, control: 9 };
const MENU_FOR_PLANET = { domains: 4, vps: 5, xdreamer: 6, brainx: 2, metalx: 8, academy: 7 };

async function boot() {
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    const navType = performance.getEntriesByType?.('navigation')?.[0]?.type;
    const saved = navType === 'back_forward' ? place.take() : (place.take(), null);

    const loader = new Loader(document.getElementById('xu-loader'));
    loader.step('link');

    // The detect script's watchdog only covered the module arriving. Building
    // and compiling can still hang on a sick GPU driver: never leave the
    // visitor on the loader for good.
    // Not transient: a device that needs half a minute to get here is better
    // served by the classic page for the week.
    const bootWatchdog = setTimeout(() => giveUp('boot-timeout'), 30000);

    const engine = new Engine(document.getElementById('xu-canvas'), { gpu: window.__xuGpu || '' });
    html.classList.add(engine.tierName === 'high' ? 'xu-q-hi' : engine.tierName === 'mid' ? 'xu-q-mid' : 'xu-q-low');

    // Watch the GPU context from the start: building the world is when memory
    // pressure is highest, and a context that dies then must not leave a
    // black screen behind the gate.
    let world = null;
    let lostTimer = null;
    engine.onContextLost = () => {
        clearTimeout(lostTimer);
        lostTimer = setTimeout(() => giveUp('context', true), 4000);
    };
    engine.onContextRestored = () => {
        clearTimeout(lostTimer);
        world?.sky?.bake();
    };

    loader.step('engine');
    await nextFrame();

    // Live layout first: the spacers must exist before anything is measured.
    html.classList.add('xu-live');
    const sections = [...document.querySelectorAll('.xu-st')];
    const journey = new Journey(sections);
    journey.layout(layoutInfo());

    world = new World(engine, journey);
    await world.build((key) => loader.step(key));
    world.setLayout(layoutInfo());

    const start = introStart();
    engine.camera.position.copy(start.pos);
    engine.camera.lookAt(start.look);

    loader.step('compile');
    await engine.compile();
    engine.renderOnce();
    await nextFrame();

    const config = (() => {
        try {
            return JSON.parse(document.getElementById('xu-config')?.textContent || '{}');
        } catch {
            return {};
        }
    })();

    const sound = new Sound(prefs.sound);
    const stations = new Stations({ journey, world, sound });

    // ?xu-debug exposes the internals to the console (and to automated checks).
    if (new URLSearchParams(location.search).has('xu-debug')) window.__xu = { engine, journey, world, stations, sound };
    const state = {
        entered: false,
        menuOpen: false,
        launching: 0, // 0 → 1 while leaving for another page
        flash: 0,
    };

    // ---- navigation helpers --------------------------------------------
    const jump = (index, p = null) => {
        const j = journey.jumpTo(index, p);
        if (!j) return;
        engine.holdGovernor(j.duration * 1000 + 800);
        if (j.screens > 2.2) sound.warp(Math.min(j.duration * 0.8, 1.8), true);
    };

    // Give the page back if a launch never leaves it: Stop pressed, a network
    // error, a download link. Otherwise the white-out and the click lock stay.
    let launchTimer = null;
    const abortLaunch = () => {
        clearTimeout(launchTimer);
        state.launching = 0;
        state.flash = 0;
        menu.reset();
        html.style.overflow = '';
    };

    const launchTo = (href) => {
        if (state.launching) return;
        state.launching = 0.0001;
        engine.holdGovernor(4000);
        sound.warp(0.9, false);
        place.save(journey.u);
        setTimeout(() => {
            window.location.href = href;
        }, 900);
        clearTimeout(launchTimer);
        launchTimer = setTimeout(abortLaunch, 6000);
    };

    const hud = new Hud({ journey, sound, onJump: (i) => jump(i) });
    hud.setSound(sound.enabled);

    let chat = null; // ui/Chat.js, below
    const menu = new Menu({
        sound,
        onOpenChange: (open) => {
            if (open) chat?.close();
            state.menuOpen = open;
            html.style.overflow = open ? 'hidden' : '';
            html.classList.toggle('xu-menu-open', open);
            engine.holdGovernor(1500);
        },
        onLaunch: (href) => launchTo(href),
        onHome: () => jump(0, 0),
        stationToItem: () => {
            const st = journey.stations[journey.current];
            if (st.type === 'platforms') {
                const card = [...st.el.querySelectorAll('[data-xu-planet]')][platformChoreo(st.p, st.count).focus];
                return MENU_FOR_PLANET[card?.dataset.xuPlanet] ?? 4;
            }
            return MENU_FOR[st.type] ?? 0;
        },
    });

    if (window.__xu) window.__xu.menu = menu;

    // Talking to the guide: her speech bubble's "ask me" field opens a chat with
    // the site's AI assistant (only when an admin has switched that on).
    const guide = new Guide({
        root: document.getElementById('xu-guide'),
        journey,
        sound,
        onClick: () => menu.open(),
        onAsk: (text) => chat?.open(text),
    });
    const chatRoot = document.getElementById('xu-chat');
    if (chatRoot && config.chat) {
        chat = new Chat({
            root: chatRoot,
            url: config.chat,
            avatar: document.querySelector('.xu-guide__avatar img')?.src || '',
            sound,
            onOpenChange: (open) => {
                guide.setChatting(open);
                html.classList.toggle('xu-chatting', open);
            },
        });
    }

    bindPointerPolish(document.body, sound);

    // Sound on/off.
    document.getElementById('xu-sound').addEventListener('click', () => {
        const on = !sound.enabled;
        prefs.set('sound', on);
        sound.setEnabled(on);
        hud.setSound(on);
        sound.toggle(on);
    });

    // Browsers allow sound only after a gesture: resume on the first one.
    const unlock = () => sound.unlock();
    window.addEventListener('pointerdown', unlock, { passive: true });
    window.addEventListener('keydown', unlock);

    // Links: in-page jumps, the classic page, and a hyperspace exit for the rest.
    document.addEventListener('click', (e) => {
        const a = e.target.closest?.('a[href]');
        if (!a || e.defaultPrevented) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;

        if (a.hasAttribute('data-xu-classic')) {
            document.cookie = `xu_mode=classic; path=/; max-age=${365 * 86400}; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
            return; // the href itself is ?view=classic
        }
        if (a.hasAttribute('data-xu-top')) {
            e.preventDefault();
            jump(0, 0);
            return;
        }
        if (a.dataset.xuJump) {
            e.preventDefault();
            const i = journey.stations.findIndex((st) => st.type === a.dataset.xuJump);
            if (i >= 0) jump(i);
            return;
        }
        if (a.closest('#xu-menu')) return; // the ring handles its own
        if (a.target === '_blank') return;
        const url = new URL(a.href, location.href);
        if (url.origin === location.origin && url.pathname === location.pathname && url.hash) return;
        e.preventDefault();
        launchTo(a.href);
    });

    // Leaving: remember where we were, so Back lands here again.
    window.addEventListener('pagehide', () => {
        clearTimeout(launchTimer);
        place.save(journey.u);
    });
    window.addEventListener('pageshow', (e) => {
        // Back from the bfcache: undo the launch (and the menu it left from).
        if (e.persisted) abortLaunch();
    });

    // ---- quality and failure ------------------------------------------------
    engine.onDegrade = (tier) => {
        world.setDensity(tier.density);
        world.setPixelRatio(engine.pixelRatio);
        if (!tier.bloom) {
            html.classList.remove('xu-q-hi', 'xu-q-mid');
            html.classList.add('xu-q-low');
        }
    };
    engine.onGiveUp = () => {
        hud.toast('เครื่องนี้แสดงผล 3D ได้ไม่ลื่น — กำลังพาไปหน้าเว็บแบบปกติ / Switching to the classic page for a smoother ride');
        setTimeout(() => giveUp('slow'), 1800);
    };
    let laidOut = { w: window.innerWidth, h: window.innerHeight };
    engine.onResize = (w, h) => {
        world.setPixelRatio(engine.pixelRatio);
        menu.layout();
        // A phone's toolbar sliding in and out as the visitor scrolls changes the
        // height a little on every flick. Re-laying out the journey then would
        // yank the scroll position mid-gesture; the spacers can keep their size.
        if (w === laidOut.w && Math.abs(h - laidOut.h) < laidOut.h * 0.2) return;
        laidOut = { w, h };
        const u = journey.u;
        journey.layout(layoutInfo());
        world.setLayout(layoutInfo());
        stations.relayout();
        if (state.entered) {
            window.scrollTo(0, journey.scrollAt(u));
            journey.scroll = window.scrollY;
        }
    };

    // ---- the frame -----------------------------------------------------------
    const cam = engine.camera;
    const pos = new Vector3();
    const look = new Vector3();
    const fwd = new Vector3();
    const right = new Vector3();
    const up = new Vector3();
    const prev = new Vector3().copy(cam.position);
    const velocity = new Vector3();
    const UP = new Vector3(0, 1, 0);
    const pointer = { x: 0, y: 0, sx: 0, sy: 0 };
    window.addEventListener(
        'pointermove',
        (e) => {
            pointer.x = (e.clientX / window.innerWidth) * 2 - 1;
            pointer.y = (e.clientY / window.innerHeight) * 2 - 1;
        },
        { passive: true },
    );

    // cross: the moment (0 → 1) the dive passes through the portal ring.
    const intro = { active: false, t: 0, dur: 2.6, cross: 0.41, fromPos: start.pos.clone(), fromLook: start.look.clone() };
    let fov = cam.fov;
    let roll = 0;
    let heading = null;
    let menuAmt = 0;
    let warpSmooth = 0;
    let station = { pending: -1, since: 0, current: -1 };
    let errors = 0;

    const frame = (dt, time) => {
        journey.update(dt);
        let targetFov = journey.pose(journey.u, pos, look);

        let introWarp = 0;
        if (intro.active) {
            intro.t = Math.min(1, intro.t + dt / intro.dur);
            const t = intro.t;
            const e = easeInOutSine(t);
            pos.lerpVectors(intro.fromPos, pos, e);
            look.lerpVectors(intro.fromLook, look, smoothstep(0.1, 1, t));
            introWarp = Math.sin(Math.PI * clamp(t * 1.15)) * 0.95;
            targetFov += introWarp * 34;
            stations.intro = smoothstep(0.72, 1, t);
            if (t > 0.62 && !state.entered) {
                state.entered = true;
                html.classList.add('xu-entered');
            }
            if (t >= 1) {
                intro.active = false;
                stations.intro = 1;
                guide.show();
            }
        }

        // Pointer parallax, a slow breath, and the menu's step back.
        pointer.sx = damp(pointer.sx, pointer.x, 2.6, dt);
        pointer.sy = damp(pointer.sy, pointer.y, 2.6, dt);
        fwd.subVectors(look, pos).normalize();
        right.crossVectors(fwd, UP).normalize();
        up.crossVectors(right, fwd).normalize();
        pos.addScaledVector(right, pointer.sx * 2.4).addScaledVector(up, -pointer.sy * 1.5 + Math.sin(time * 0.33) * 0.35);
        look.addScaledVector(right, pointer.sx * 0.9).addScaledVector(up, -pointer.sy * 0.6);
        menuAmt = damp(menuAmt, state.menuOpen ? 1 : 0, 3.2, dt);
        pos.addScaledVector(fwd, -menuAmt * 16);
        targetFov += menuAmt * 7;

        cam.position.copy(pos);
        cam.lookAt(look);

        // Bank into turns.
        const h = Math.atan2(fwd.x, fwd.z);
        if (heading !== null) {
            let dh = h - heading;
            if (dh > Math.PI) dh -= Math.PI * 2;
            if (dh < -Math.PI) dh += Math.PI * 2;
            roll = damp(roll, clamp((-dh / Math.max(dt, 1e-3)) * 0.1, -0.22, 0.22), 2.5, dt);
        }
        heading = h;
        cam.rotateZ(roll);

        // Speed → hyperspace.
        const screensPerSec = Math.abs(journey.velocity) / window.innerHeight;
        const speedWarp = clamp((screensPerSec - 0.9) / 3.5) * 0.55;
        if (state.launching) state.launching = Math.min(1, state.launching + dt / 0.9);
        const launchWarp = state.launching ? smoothstep(0, 1, state.launching) : 0;
        const warp = Math.max(speedWarp, journey.jumpWarp, introWarp, launchWarp);
        warpSmooth = damp(warpSmooth, warp, 7, dt);

        fov = damp(fov, targetFov + warpSmooth * 20, 5, dt);
        if (Math.abs(cam.fov - fov) > 0.01) {
            cam.fov = fov;
            cam.updateProjectionMatrix();
        }

        velocity.subVectors(cam.position, prev).divideScalar(Math.max(dt, 1e-3));
        prev.copy(cam.position);

        // Flash: at the portal crossing, and at the end of a launch.
        const portalFlash = intro.active ? Math.pow(Math.max(0, 1 - Math.abs(intro.t - intro.cross) / 0.05), 2) * 0.55 : 0;
        const launchFlash = state.launching ? smoothstep(0.7, 1, state.launching) * 1.4 : 0;
        state.flash = Math.max(damp(state.flash, 0, 5, dt), portalFlash, launchFlash);
        engine.post.uFlash.value = state.flash;
        engine.post.uWarp.value = warpSmooth;
        engine.post.uAberration.value = 0.35 + menuAmt * 0.4;

        const s = {
            time,
            dt,
            u: journey.u,
            camera: cam,
            velocity,
            warp: warpSmooth,
            entered: state.entered,
            menuOpen: state.menuOpen,
            // Overlays step aside in hyperspace.
            hide: Math.max(clamp(journey.jumpWarp * 1.6), launchWarp, menuAmt * 0.9),
            // How hard the visitor is travelling (the guide flies alongside above ~0.55).
            cruise: Math.max(clamp((screensPerSec - 1.1) / 2.2), journey.jumpWarp),
            lean: Math.sign(journey.velocity) * clamp(screensPerSec / 3),
        };

        world.update(s);
        const target = stations.update(s);
        hud.update(s, target);
        menu.update(dt);
        guide.update(s);
        sound.setMotion(Math.max(clamp(screensPerSec / 3), warpSmooth));

        // The stop's chord and bell, once the visitor has settled there.
        const cur = journey.current;
        if (cur !== station.pending) {
            station.pending = cur;
            station.since = time;
        }
        if (station.pending !== station.current && time - station.since > 0.4 && !journey.jump && state.entered) {
            station.current = station.pending;
            const type = journey.stations[cur].type;
            sound.setStation(type);
            sound.chime(type);
        }
    };

    engine.onFrame((dt, time) => {
        try {
            frame(dt, time);
        } catch (err) {
            errors++;
            console.error('[xu] frame', err);
            if (errors > 3) giveUp('frame');
        }
    });
    engine.start();

    loader.step('ready');
    await sleep(380);
    // Built and drawing. From here the frame loop's own guards take over.
    clearTimeout(bootWatchdog);

    // ---- enter -----------------------------------------------------------
    // Restore a Back-button position straight away; otherwise start at the core.
    if (saved !== null && saved > 0.05) {
        window.scrollTo(0, journey.scrollAt(saved));
        journey.scroll = window.scrollY;
        journey.update(0.016);
    } else {
        window.scrollTo(0, 0);
    }

    const returning = prefs.seen;
    let choice = { sound: prefs.sound };
    if (!returning) {
        // Unlock audio inside the click itself — Safari wants it in the gesture.
        const early = (e) => {
            if (e.target.closest?.('[data-xu-enter="sound"]')) {
                sound.enabled = true;
                sound.unlock();
            }
        };
        document.getElementById('xu-loader').addEventListener('click', early, true);
        choice = await loader.gate();
    }

    prefs.set('seen', true);
    prefs.set('sound', choice.sound);
    sound.setEnabled(choice.sound);
    hud.setSound(choice.sound);

    // A context that died while the gate was up would dive into a black screen.
    if (engine.lost) {
        giveUp('context', true);
        return;
    }

    loader.leave();
    intro.dur = saved !== null ? 1.6 : returning ? 2.1 : 2.8;
    // When does the straight dive cross the portal? (The ease is a half cosine.)
    const target = new Vector3();
    journey.pose(journey.u, target, new Vector3());
    const e = clamp((start.pos.z - ANCHORS.portal.z) / Math.max(1, start.pos.z - target.z));
    intro.cross = Math.acos(1 - 2 * e) / Math.PI;
    intro.t = 0;
    intro.active = true;
    engine.holdGovernor(intro.dur * 1000 + 1500);
    sound.warp(intro.dur * intro.cross, true);
}

// The browser check may already be on its way to the classic page; a module
// that still runs would only burn the GPU for a page that is being replaced.
if (!window.__xuLeaving) {
    boot().catch((err) => {
        console.error('[xu] boot failed', err);
        giveUp('boot');
    });
}
