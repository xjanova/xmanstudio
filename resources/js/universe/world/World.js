import { Vector3 } from 'three';
import { origin as originChoreo } from '../engine/choreo.js';
import { clamp, nextFrame, smoothstep } from '../lib/math.js';
import { Belt } from './Belt.js';
import { Constellation } from './Constellation.js';
import { Core } from './Core.js';
import { Galaxy } from './Galaxy.js';
import { Nebula } from './Nebula.js';
import { Planets } from './Planets.js';
import { Portal } from './Portal.js';
import { Pulsar } from './Pulsar.js';
import { Ribbons } from './Ribbons.js';
import { Sky } from './Sky.js';
import { Stars } from './Stars.js';
import { Wormhole } from './Wormhole.js';
import { ANCHORS } from './anchors.js';

/**
 * Builds the universe from what the page actually contains — a stop that the
 * server left out (no products yet, no reviews) gets no world either — and
 * drives every body from the journey each frame.
 */
export class World {
    constructor(engine, journey) {
        this.engine = engine;
        this.journey = journey;
        this.modules = [];
        this.index = {};
        journey.stations.forEach((st) => {
            this.index[st.type] = st.i;
        });
        this.tmp = new Vector3();
    }

    has(type) {
        return this.index[type] !== undefined;
    }

    /** Build step by step, yielding a frame between the heavy parts so the loader keeps moving. */
    async build(step) {
        const e = this.engine;
        const ctx = { scene: e.scene, renderer: e.renderer, tierName: e.tierName, pixelRatio: e.pixelRatio };

        step('sky');
        this.sky = new Sky(ctx);
        await nextFrame();

        this.stars = new Stars(ctx);
        this.portal = new Portal(ctx);
        step('core');
        this.core = new Core(ctx);
        this.ribbons = new Ribbons(ctx);
        await nextFrame();

        step('worlds');
        if (this.has('origin')) {
            const stats = [...document.querySelectorAll('[data-xu-stat]')].map((el) => el.dataset.xuStat);
            await Promise.race([
                document.fonts?.load?.("800 120px 'Unbounded'") ?? Promise.resolve(),
                new Promise((resolve) => setTimeout(resolve, 2500)),
            ]).catch(() => {});
            this.constellation = new Constellation(ctx, stats);
        }
        if (this.has('services')) {
            const accents = [...document.querySelectorAll('[data-xu-beacon]')].map((el) =>
                getComputedStyle(el).getPropertyValue('--accent').trim() || '#22d3ee',
            );
            this.galaxy = new Galaxy(ctx, accents);
        }
        await nextFrame();
        if (this.has('products')) this.nebula = new Nebula(ctx);
        if (this.has('platforms')) {
            const keys = [...document.querySelectorAll('[data-xu-planet]')].map((el) => el.dataset.xuPlanet);
            this.planets = new Planets(ctx, keys);
        }
        await nextFrame();
        if (this.has('stack')) this.belt = new Belt(ctx);
        if (this.has('reviews')) this.pulsar = new Pulsar(ctx);
        this.wormhole = new Wormhole(ctx);

        this.setDensity(e.tier.density);
    }

    setPixelRatio(pr) {
        for (const m of [this.stars, this.core, this.constellation, this.galaxy, this.belt]) m?.setPixelRatio?.(pr);
    }

    setDensity(d) {
        for (const m of [this.stars, this.constellation, this.galaxy, this.belt]) m?.setDensity?.(d);
    }

    setLayout(layout) {
        this.constellation?.setLayout(layout);
    }

    near(type, reach) {
        const i = this.index[type];
        return i !== undefined && Math.abs(this.journey.u - (i + 0.5)) < reach;
    }

    /**
     * @param {object} s time, dt, u, camera, velocity (Vector3, world units/s), warp
     */
    update(s) {
        const stations = this.journey.stations;
        this.stars.update(s);
        this.portal.update(s);

        const coreOn = s.u < (this.index.core ?? 0) + 2.4;
        this.core.group.visible = coreOn;
        this.ribbons.group.visible = coreOn;
        if (coreOn) {
            this.core.update(s);
            this.ribbons.update(s);
        }

        if (this.constellation) {
            const st = stations[this.index.origin];
            const c = originChoreo(st.p);
            // While why-us shows, the camera path frames the X to the side (Path.js).
            this.constellation.update(s, c.state, c.alpha);
        }

        if (this.galaxy) {
            // Only after the numbers are done: seen from the origin it would sit right behind them.
            const i = this.index.services;
            this.galaxy.update(s, smoothstep(i - 0.32, i - 0.04, s.u) * (1 - smoothstep(i + 1.3, i + 1.7, s.u)));
        }
        this.nebula?.update(s, this.near('products', 1.9));
        if (this.planets) {
            const i = this.index.platforms;
            this.planets.update(s, i, i + 1);
        }
        this.belt?.update(s, this.near('stack', 2));
        // Neither shows before its own neighbourhood: seen from the tech belt,
        // the beam and the disk cut straight through the heading.
        if (this.pulsar) {
            const i = this.index.reviews;
            this.pulsar.update(s, s.u > i - 0.3 && s.u < i + 1.6);
        }
        this.wormhole.update(s, s.u > (this.index.launch ?? this.journey.n - 2) - 1.05);

        this.updateLens(s);
    }

    /** Bend the image around the wormhole's mouth as the camera closes in. */
    updateLens(s) {
        const post = this.engine.post;
        const i = this.index.launch;
        if (i === undefined) {
            post.uLens.value = 0;
            return;
        }
        const strength = smoothstep(i + 0.1, i + 0.85, s.u) * (1 - smoothstep(i + 1.05, i + 1.4, s.u));
        if (strength < 0.001) {
            post.uLens.value = 0;
            return;
        }
        this.tmp.copy(ANCHORS.launch).project(s.camera);
        if (this.tmp.z > 1) {
            post.uLens.value = 0;
            return;
        }
        post.uLensPos.value.set(clamp(this.tmp.x * 0.5 + 0.5), clamp(this.tmp.y * 0.5 + 0.5));
        post.uLens.value = strength;
    }
}
