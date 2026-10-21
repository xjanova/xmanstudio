/**
 * The universe's sound: all of it synthesised live with the Web Audio API,
 * so there is not a byte of audio to download.
 *
 *   score     a generative soundtrack that never stands still. Every stop
 *             has its own four-chord progression, a new chord landing on the
 *             downbeat every two bars; a warm pad breathes into each chord, a
 *             wordless choir swells and fades, a glassy arpeggio echoes from
 *             ear to ear, a deep sub rises under every change and crystal
 *             bells ring out like stars, all in one long hall. Moving (and
 *             the menu) lifts its energy; standing still lets it settle.
 *   effects   wind that rises with flight speed, a cinematic swell, bell and
 *             boom on arriving at a stop, hover blips tuned to the score's
 *             scale and panned to where the pointer is, the ring's rotary
 *             ticks, lock / select chords, the menu's power-up and power-down,
 *             and hyperspace: a riser, a whistle, a sub drop and an impact.
 *
 * Browsers only let a page make sound after a click or key press, so the
 * context is created in unlock(), which main.js calls from those gestures.
 * Everything else is safe to call before that: it simply does nothing.
 */

const midi = (m) => 440 * Math.pow(2, (m - 69) / 12);

// Five voices per chord, voice-led so each voice moves a short way. All in
// D major, so nothing clashes with the interface's pentatonic blips.
const CH = {
    Dmaj9: [50, 57, 61, 64, 66],
    Dsus2: [50, 57, 62, 64, 69],
    Dadd9: [38, 50, 57, 64, 66],
    Bm11: [47, 54, 57, 62, 64],
    Gmaj9: [43, 54, 57, 59, 62],
    Gmaj7: [43, 50, 54, 59, 62],
    A69: [45, 52, 59, 61, 66],
    Fsm11: [42, 52, 57, 61, 64],
    Em9: [40, 55, 59, 62, 66],
};

// A four-chord progression for each stop.
const PROGRESSIONS = {
    core: [CH.Dmaj9, CH.Bm11, CH.Gmaj9, CH.A69],
    origin: [CH.Bm11, CH.Gmaj9, CH.Dmaj9, CH.A69],
    services: [CH.Gmaj9, CH.A69, CH.Fsm11, CH.Bm11],
    products: [CH.Em9, CH.A69, CH.Dmaj9, CH.Gmaj9],
    platforms: [CH.A69, CH.Fsm11, CH.Gmaj9, CH.Dmaj9],
    stack: [CH.Fsm11, CH.Gmaj9, CH.Em9, CH.A69],
    reviews: [CH.Gmaj7, CH.Dmaj9, CH.Em9, CH.A69],
    launch: [CH.Dsus2, CH.Gmaj9, CH.A69, CH.Dmaj9],
    control: [CH.Dadd9, CH.Bm11, CH.Gmaj9, CH.A69],
};

// D major pentatonic from D4 upward.
const SCALE = [62, 64, 66, 69, 71, 74, 76, 78, 81, 83, 86, 88];

const BPM = 72;
const EIGHTH = 60 / BPM / 2;
const STEPS_PER_CHORD = 16; // two bars of eighths

// The arpeggio's shape over one bar, as indices into the chord's arp pool.
const ARP = [0, 2, 1, 3, 2, 4, 3, 5];

export class Sound {
    constructor(enabled = false) {
        this.enabled = enabled;
        this.ctx = null;
        this.station = 'core';
        this.pendingStation = null;
        this.progression = PROGRESSIONS.core;
        this.chordIndex = -1;
        this.chord = this.progression[0];
        this.lastHover = 0;
        this.motion = 0;
        this.energy = 0.45;
        this.menuBoost = 0;
        const AC = window.AudioContext || window.webkitAudioContext;
        this.supported = !!AC;
        this.AC = AC;

        document.addEventListener('visibilitychange', () => {
            if (!this.ctx) return;
            if (document.hidden) {
                this.stopScore();
                this.ctx.suspend().catch(() => {});
            } else if (this.enabled) {
                this.ctx.resume().catch(() => {});
                // Do not try to catch up on the notes missed while hidden.
                this.nextNote = this.ctx.currentTime + 0.1;
                this.startScore();
            }
        });
    }

    get live() {
        return !!this.ctx && this.enabled && this.ctx.state === 'running';
    }

    /** Must run inside a user gesture (click, key, touch). */
    unlock() {
        if (!this.supported || !this.enabled) return;
        if (!this.ctx) this.build();
        if (this.ctx.state !== 'running') this.ctx.resume().catch(() => {});
        this.fadeMaster(0.85, 1.6);
        this.startScore();
    }

    setEnabled(on) {
        this.enabled = on;
        if (on) {
            this.unlock();
        } else if (this.ctx) {
            this.fadeMaster(0, 0.35);
            clearTimeout(this.suspendTimer);
            this.suspendTimer = setTimeout(() => {
                if (this.enabled) return;
                this.stopScore();
                this.ctx.suspend().catch(() => {});
            }, 450);
        }
    }

    /** The score's clock runs only while there is sound to make. */
    startScore() {
        if (this.ctx && !this.scoreTimer) this.scoreTimer = setInterval(() => this.schedule(), 25);
    }

    stopScore() {
        clearInterval(this.scoreTimer);
        this.scoreTimer = null;
    }

    fadeMaster(value, seconds) {
        if (!this.ctx) return;
        const now = this.ctx.currentTime;
        const g = this.master.gain;
        g.cancelScheduledValues(now);
        g.setValueAtTime(g.value, now);
        g.linearRampToValueAtTime(value, now + seconds);
    }

    // ------------------------------------------------------------------
    // Graph
    // ------------------------------------------------------------------

    build() {
        const ctx = new this.AC({ latencyHint: 'interactive' });
        this.ctx = ctx;

        this.master = ctx.createGain();
        this.master.gain.value = 0;
        const limiter = ctx.createDynamicsCompressor();
        limiter.threshold.value = -10;
        limiter.knee.value = 10;
        limiter.ratio.value = 6;
        limiter.attack.value = 0.004;
        limiter.release.value = 0.3;
        this.master.connect(limiter).connect(ctx.destination);

        // One long, generated hall shared by everything.
        this.reverb = ctx.createConvolver();
        this.reverb.buffer = this.impulse(4.8, 2.2);
        this.reverbIn = ctx.createGain();
        const reverbOut = ctx.createGain();
        reverbOut.gain.value = 0.72;
        this.reverbIn.connect(this.reverb).connect(reverbOut).connect(this.master);

        // A ping-pong echo for the arpeggio: the two sides a dotted eighth apart.
        this.echoIn = ctx.createGain();
        const echoL = ctx.createDelay(2);
        const echoR = ctx.createDelay(2);
        echoL.delayTime.value = EIGHTH * 1.5;
        echoR.delayTime.value = EIGHTH * 1.5;
        const fbL = ctx.createGain();
        const fbR = ctx.createGain();
        fbL.gain.value = 0.36;
        fbR.gain.value = 0.36;
        const echoTone = ctx.createBiquadFilter();
        echoTone.type = 'lowpass';
        echoTone.frequency.value = 3400;
        const panL = ctx.createStereoPanner();
        const panR = ctx.createStereoPanner();
        panL.pan.value = -0.75;
        panR.pan.value = 0.75;
        this.echoIn.connect(echoTone).connect(echoL);
        echoL.connect(fbL).connect(echoR);
        echoR.connect(fbR).connect(echoL);
        echoL.connect(panL);
        echoR.connect(panR);
        for (const pan of [panL, panR]) {
            pan.connect(this.master);
            pan.connect(this.reverbIn);
        }

        // The score's own bus, so a hyperspace jump can duck it.
        this.music = ctx.createGain();
        this.music.gain.value = 0;
        this.music.connect(this.master);
        const musicSend = ctx.createGain();
        musicSend.gain.value = 0.62;
        this.music.connect(musicSend).connect(this.reverbIn);

        this.sfx = ctx.createGain();
        this.sfx.gain.value = 0.9;
        this.sfx.connect(this.master);
        const sfxSend = ctx.createGain();
        sfxSend.gain.value = 0.34;
        this.sfx.connect(sfxSend).connect(this.reverbIn);

        this.noise = this.noiseBuffer(2);
        this.buildPad();
        this.buildChoir();
        this.buildSub();
        this.buildWind();

        const now = ctx.currentTime;
        this.music.gain.setValueAtTime(0, now);
        this.music.gain.linearRampToValueAtTime(1, now + 3.5);

        this.nextNote = now + 0.12;
        this.step = 0;
    }

    impulse(seconds, decay) {
        const rate = this.ctx.sampleRate;
        const length = Math.floor(rate * seconds);
        const buffer = this.ctx.createBuffer(2, length, rate);
        for (let c = 0; c < 2; c++) {
            const data = buffer.getChannelData(c);
            for (let i = 0; i < length; i++) {
                const t = i / length;
                const attack = Math.min(1, i / (rate * 0.02));
                data[i] = (Math.random() * 2 - 1) * Math.pow(1 - t, decay) * attack;
            }
        }
        return buffer;
    }

    noiseBuffer(seconds) {
        const rate = this.ctx.sampleRate;
        const buffer = this.ctx.createBuffer(1, Math.floor(rate * seconds), rate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < data.length; i++) data[i] = Math.random() * 2 - 1;
        return buffer;
    }

    /** Warm, slightly detuned saws with an airy sine an octave up, under a breathing filter. */
    buildPad() {
        const ctx = this.ctx;
        this.padFilter = ctx.createBiquadFilter();
        this.padFilter.type = 'lowpass';
        this.padFilter.frequency.value = 900;
        this.padFilter.Q.value = 0.8;
        this.padFilter.connect(this.music);

        const lfo = ctx.createOscillator();
        lfo.frequency.value = 0.07;
        const depth = ctx.createGain();
        depth.gain.value = 320;
        lfo.connect(depth).connect(this.padFilter.frequency);
        lfo.start();

        this.pad = this.chord.map((note, v) => {
            const pan = ctx.createStereoPanner();
            pan.pan.value = (v / (this.chord.length - 1)) * 1.4 - 0.7;
            const gain = ctx.createGain();
            gain.gain.value = 0;
            gain.connect(pan).connect(this.padFilter);
            const oscs = [-9, 9].map((cents) => {
                const o = ctx.createOscillator();
                o.type = 'sawtooth';
                o.frequency.value = midi(note);
                o.detune.value = cents + (Math.random() - 0.5) * 5;
                o.connect(gain);
                o.start();
                return o;
            });
            const air = ctx.createOscillator();
            air.type = 'sine';
            air.frequency.value = midi(note + 12);
            const airGain = ctx.createGain();
            airGain.gain.value = 0.35;
            air.connect(airGain).connect(gain);
            air.start();
            oscs.push(air);
            return { oscs, gain, level: v === 0 ? 0.05 : 0.036 };
        });
    }

    /** A wordless "aah": saws through three vowel formants, with a slow vibrato. */
    buildChoir() {
        const ctx = this.ctx;
        this.choirGain = ctx.createGain();
        this.choirGain.gain.value = 0;
        this.choirGain.connect(this.music);

        const vibrato = ctx.createOscillator();
        vibrato.frequency.value = 4.6;
        const vibDepth = ctx.createGain();
        vibDepth.gain.value = 7;
        vibrato.connect(vibDepth);
        vibrato.start();

        const formants = [
            [800, 11, 1],
            [1150, 12, 0.55],
            [2900, 14, 0.22],
        ];
        this.choir = [2, 3, 4].map((v, k) => {
            const o = ctx.createOscillator();
            o.type = 'sawtooth';
            o.frequency.value = midi(this.chord[v] + 12);
            o.detune.value = (k - 1) * 6;
            vibDepth.connect(o.detune);
            const pan = ctx.createStereoPanner();
            pan.pan.value = (k - 1) * 0.55;
            pan.connect(this.choirGain);
            for (const [freq, q, level] of formants) {
                const bp = ctx.createBiquadFilter();
                bp.type = 'bandpass';
                bp.frequency.value = freq;
                bp.Q.value = q;
                const g = ctx.createGain();
                g.gain.value = level * 0.5;
                o.connect(bp).connect(g).connect(pan);
            }
            o.start();
            return o;
        });
    }

    /** A deep sine an octave under the chord's root that swells into each change. */
    buildSub() {
        const ctx = this.ctx;
        this.subGain = ctx.createGain();
        this.subGain.gain.value = 0;
        this.subGain.connect(this.music);
        this.sub = ctx.createOscillator();
        this.sub.type = 'sine';
        this.sub.frequency.value = midi(this.chord[0] - 12);
        this.sub.connect(this.subGain);
        this.sub.start();
    }

    buildWind() {
        const ctx = this.ctx;
        const src = ctx.createBufferSource();
        src.buffer = this.noise;
        src.loop = true;
        this.windFilter = ctx.createBiquadFilter();
        this.windFilter.type = 'bandpass';
        this.windFilter.frequency.value = 300;
        this.windFilter.Q.value = 0.8;
        this.windGain = ctx.createGain();
        this.windGain.gain.value = 0;
        src.connect(this.windFilter).connect(this.windGain).connect(this.master);
        src.start();
    }

    // ------------------------------------------------------------------
    // The score
    // ------------------------------------------------------------------

    /** Look-ahead scheduler: queue every eighth note due in the next 120 ms. */
    schedule() {
        if (!this.ctx || this.ctx.state !== 'running' || !this.enabled) return;
        const now = this.ctx.currentTime;
        if (this.nextNote < now - 0.2) this.nextNote = now + 0.05;
        const horizon = now + 0.12;
        while (this.nextNote < horizon) {
            this.playStep(this.step, this.nextNote);
            this.nextNote += EIGHTH;
            this.step++;
        }
    }

    playStep(step, t) {
        const inChord = step % STEPS_PER_CHORD;
        if (inChord === 0) this.nextChord(t);
        this.arpNote(inChord, t);
        if (inChord === 0 || (inChord === 11 && Math.random() < 0.5)) this.starBell(t);
    }

    /** On the downbeat: the next chord of this stop's progression (or the first of a new stop's). */
    nextChord(t) {
        if (this.pendingStation) {
            this.station = this.pendingStation;
            this.pendingStation = null;
            this.progression = PROGRESSIONS[this.station] || PROGRESSIONS.core;
            this.chordIndex = 0;
        } else {
            this.chordIndex = (this.chordIndex + 1) % this.progression.length;
        }
        const chord = this.progression[this.chordIndex];
        this.chord = chord;
        const e = this.energy;

        this.pad.forEach((voice, v) => {
            const f = midi(chord[v]);
            voice.oscs[0].frequency.setTargetAtTime(f, t, 0.3);
            voice.oscs[1].frequency.setTargetAtTime(f, t, 0.3);
            voice.oscs[2].frequency.setTargetAtTime(f * 2, t, 0.3);
            // Breathe: dip, then bloom into the new chord.
            voice.gain.gain.setTargetAtTime(voice.level * 0.55, t, 0.06);
            voice.gain.gain.setTargetAtTime(voice.level * (0.8 + e * 0.35), t + 0.18, 0.9);
        });
        this.padFilter.frequency.setTargetAtTime(700 + e * 900, t, 0.8);

        [2, 3, 4].forEach((v, k) => this.choir[k].frequency.setTargetAtTime(midi(chord[v] + 12), t, 0.25));
        // The choir comes and goes: louder on the first chord of each pass, and with energy.
        const choirLevel = (this.chordIndex === 0 ? 0.075 : 0.045) * (0.6 + e * 0.8);
        this.choirGain.gain.setTargetAtTime(choirLevel, t, 1.2);

        this.sub.frequency.setTargetAtTime(midi(chord[0] - 12), t, 0.2);
        this.subGain.gain.setTargetAtTime(0.2, t, 0.35);
        this.subGain.gain.setTargetAtTime(0.1, t + 1.4, 1.2);
    }

    /** One note of the arpeggio: glassy, plucked, echoing between the ears. */
    arpNote(inChord, t) {
        const chord = this.chord;
        const pool = [chord[1] + 12, chord[2] + 12, chord[3] + 12, chord[4] + 12, chord[2] + 24, chord[3] + 24];
        // The second bar climbs a step higher than the first.
        const idx = Math.min(ARP[inChord % 8] + (inChord >= 8 ? 1 : 0), pool.length - 1);
        const note = pool[idx];
        const accent = inChord % 8 === 0 ? 1.5 : 1;
        const vel = (0.018 + this.energy * 0.04) * accent;
        const ctx = this.ctx;

        const o = ctx.createOscillator();
        o.type = 'triangle';
        o.frequency.value = midi(note);
        const glass = ctx.createOscillator();
        glass.type = 'sine';
        glass.frequency.value = midi(note + 19); // a fifth over the octave: the glassy edge
        const glassGain = ctx.createGain();
        glassGain.gain.value = 0.18;
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(vel, t + 0.006);
        g.gain.exponentialRampToValueAtTime(0.0001, t + 0.85);
        const p = ctx.createStereoPanner();
        p.pan.value = Math.sin(inChord * 1.3) * 0.5;
        o.connect(g);
        glass.connect(glassGain).connect(g);
        g.connect(p);
        p.connect(this.music);
        p.connect(this.echoIn);
        o.start(t);
        glass.start(t);
        o.stop(t + 0.9);
        glass.stop(t + 0.9);
    }

    /** A high crystal bell from the chord, rung into the hall like a star. */
    starBell(t) {
        const note = this.chord[3 + Math.floor(Math.random() * 2)] + 24;
        this.bell(midi(note), 0.02 + this.energy * 0.022, Math.random() * 1.4 - 0.7, t, this.reverbIn);
    }

    // ------------------------------------------------------------------
    // Following the flight
    // ------------------------------------------------------------------

    /** A new stop: its progression takes over on the next downbeat. */
    setStation(type) {
        if (!PROGRESSIONS[type] || type === this.station) return;
        this.pendingStation = type;
    }

    /** 0 = still, 1 = flat out. The wind follows it, and it lifts the score's energy. */
    setMotion(v) {
        if (!this.ctx) return;
        const m = Math.min(1, Math.max(0, v));
        const energy = Math.min(1, 0.4 + m * 0.45 + this.menuBoost);
        this.energy += (energy - this.energy) * 0.05;
        if (Math.abs(m - this.motion) < 0.01) return;
        this.motion = m;
        const now = this.ctx.currentTime;
        this.windGain.gain.setTargetAtTime(m * m * 0.22, now, 0.12);
        this.windFilter.frequency.setTargetAtTime(260 + m * 2600, now, 0.15);
    }

    /** Dip the score under a big effect, then bring it back. */
    duck(seconds) {
        if (!this.ctx) return;
        const now = this.ctx.currentTime;
        const g = this.music.gain;
        g.cancelScheduledValues(now);
        g.setValueAtTime(g.value, now);
        g.linearRampToValueAtTime(0.35, now + 0.2);
        g.linearRampToValueAtTime(1, now + seconds + 1.6);
    }

    /** Arriving at a stop: a swell, then a bell pair, a soft boom and a sparkle up the chord. */
    chime(type) {
        if (!this.live) return;
        const chord = PROGRESSIONS[type]?.[0] || this.chord;
        this.swell(0.9, 0.12);
        setTimeout(() => {
            if (!this.live) return;
            const t = this.ctx.currentTime;
            this.bell(midi(chord[2] + 24), 0.09, -0.2);
            this.thump(70, 0.22, 0.9);
            [1, 2, 3, 4].forEach((v, i) => this.ping(midi(chord[v] + 24), 0.03, (i - 1.5) * 0.4, 1.6, this.reverbIn, 'sine', t + i * 0.07));
        }, 850);
        setTimeout(() => this.live && this.bell(midi(chord[4] + 24), 0.06, 0.3), 1000);
    }

    // ------------------------------------------------------------------
    // Interface sounds
    // ------------------------------------------------------------------

    hover(pan = 0, index = 0) {
        if (!this.live) return;
        const now = performance.now();
        if (now - this.lastHover < 45) return;
        this.lastHover = now;
        const f = midi(SCALE[((index % SCALE.length) + SCALE.length) % SCALE.length] + 12);
        this.ping(f, 0.07, pan, 0.22);
        this.ping(f * 2.005, 0.018, pan, 0.14);
    }

    tick(pan = 0) {
        if (!this.live) return;
        const ctx = this.ctx;
        const t = ctx.currentTime;
        const src = ctx.createBufferSource();
        src.buffer = this.noise;
        const bp = ctx.createBiquadFilter();
        bp.type = 'bandpass';
        bp.frequency.value = 2600;
        bp.Q.value = 5;
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.28, t);
        g.gain.exponentialRampToValueAtTime(0.0001, t + 0.03);
        const p = ctx.createStereoPanner();
        p.pan.value = pan;
        src.connect(bp).connect(g).connect(p).connect(this.sfx);
        src.start(t, Math.random() * 1.5, 0.04);
        this.ping(1900, 0.02, pan, 0.03);
    }

    lock(index = 0) {
        if (!this.live) return;
        const f = midi(SCALE[index % SCALE.length]);
        this.ping(f, 0.06, 0, 0.5);
        setTimeout(() => this.live && this.ping(f * 1.5, 0.045, 0, 0.6), 55);
        setTimeout(() => this.live && this.ping(f * 2, 0.03, 0, 1.2, this.reverbIn), 110);
    }

    click() {
        if (!this.live) return;
        this.ping(midi(SCALE[5] + 12), 0.06, 0, 0.12);
        this.thump(140, 0.12, 0.12);
    }

    /** Launching a destination: a rising chord stab over a boom. */
    select(index = 0) {
        if (!this.live) return;
        const root = midi(SCALE[index % 5] + 12);
        [1, 1.25, 1.5, 2, 2.5].forEach((ratio, i) => {
            setTimeout(() => this.live && this.ping(root * ratio, 0.07, (i - 2) * 0.3, 1.2, null, 'triangle'), i * 45);
        });
        this.thump(90, 0.45, 0.5);
        this.swell(0.5, 0.1);
    }

    /** The menu powers up: a filter sweep, a choir swell and the chord sparkling upward. */
    open() {
        if (!this.live) return;
        this.menuBoost = 0.35;
        this.sweep(110, 220, 180, 3200, 0.55, 0.11);
        const t = this.ctx.currentTime;
        this.choirGain.gain.setTargetAtTime(0.1, t, 0.3);
        this.chord.forEach((note, i) => this.ping(midi(note + 24), 0.03, (i - 2) * 0.35, 1.5, this.reverbIn, 'sine', t + 0.12 + i * 0.06));
    }

    close() {
        if (!this.live) return;
        this.menuBoost = 0;
        this.sweep(220, 110, 2600, 220, 0.35, 0.09);
        const t = this.ctx.currentTime;
        [...this.chord].reverse().forEach((note, i) => this.ping(midi(note + 12), 0.025, (2 - i) * 0.3, 0.8, null, 'sine', t + i * 0.05));
    }

    toggle(on) {
        if (!this.live) return;
        const a = midi(on ? SCALE[3] : SCALE[5]);
        const b = midi(on ? SCALE[5] : SCALE[3]);
        this.ping(a, 0.06, 0, 0.18);
        setTimeout(() => this.live && this.ping(b, 0.06, 0, 0.3), 90);
    }

    /** A chat message: a soft pop down for the visitor's, a little bell up for the guide's. */
    message(incoming = true) {
        if (!this.live) return;
        const t = this.ctx.currentTime;
        const [lo, hi] = incoming ? [this.chord[2], this.chord[4]] : [this.chord[4], this.chord[2]];
        this.ping(midi(lo + 24), 0.045, incoming ? -0.2 : 0.3, 0.25, null, 'sine', t);
        this.ping(midi(hi + 24), 0.04, incoming ? 0.2 : 0.3, incoming ? 0.9 : 0.3, incoming ? this.reverbIn : null, 'sine', t + 0.08);
        if (incoming) this.bell(midi(this.chord[3] + 36), 0.02, 0, t + 0.17, this.reverbIn);
    }

    /** Hyperspace: a rising roar and whistle over `seconds`, ending in an impact. */
    warp(seconds = 1.2, boom = true) {
        if (!this.live) return;
        this.duck(seconds);
        const ctx = this.ctx;
        const t = ctx.currentTime;
        const end = t + seconds;

        // The roar.
        const src = ctx.createBufferSource();
        src.buffer = this.noise;
        src.loop = true;
        const bp = ctx.createBiquadFilter();
        bp.type = 'bandpass';
        bp.Q.value = 1.1;
        bp.frequency.setValueAtTime(260, t);
        bp.frequency.exponentialRampToValueAtTime(5200, end);
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(0.36, end - 0.05);
        g.gain.exponentialRampToValueAtTime(0.0001, end + 0.25);
        src.connect(bp).connect(g).connect(this.sfx);
        src.start(t);
        src.stop(end + 0.3);

        // The engine.
        const o = ctx.createOscillator();
        o.type = 'sawtooth';
        o.frequency.setValueAtTime(55, t);
        o.frequency.exponentialRampToValueAtTime(640, end);
        const lp = ctx.createBiquadFilter();
        lp.type = 'lowpass';
        lp.frequency.value = 1400;
        const og = ctx.createGain();
        og.gain.setValueAtTime(0.0001, t);
        og.gain.exponentialRampToValueAtTime(0.09, end - 0.05);
        og.gain.exponentialRampToValueAtTime(0.0001, end + 0.15);
        o.connect(lp).connect(og).connect(this.sfx);
        o.start(t);
        o.stop(end + 0.2);

        // The whistle: a thin glissando high above, into the hall.
        const w = ctx.createOscillator();
        w.type = 'sine';
        w.frequency.setValueAtTime(420, t);
        w.frequency.exponentialRampToValueAtTime(2600, end);
        const wg = ctx.createGain();
        wg.gain.setValueAtTime(0.0001, t);
        wg.gain.exponentialRampToValueAtTime(0.035, end - 0.1);
        wg.gain.exponentialRampToValueAtTime(0.0001, end + 0.1);
        w.connect(wg);
        wg.connect(this.sfx);
        wg.connect(this.reverbIn);
        w.start(t);
        w.stop(end + 0.15);

        if (boom) setTimeout(() => this.impact(), seconds * 1000);
    }

    /** The end of a jump: a sub drop, a burst of air and a bright chord ringing out. */
    impact() {
        if (!this.live) return;
        const ctx = this.ctx;
        const t = ctx.currentTime;
        const o = ctx.createOscillator();
        o.type = 'sine';
        o.frequency.setValueAtTime(92, t);
        o.frequency.exponentialRampToValueAtTime(30, t + 1.4);
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(0.75, t + 0.015);
        g.gain.exponentialRampToValueAtTime(0.0001, t + 1.6);
        o.connect(g).connect(this.sfx);
        o.start(t);
        o.stop(t + 1.7);
        this.burst(900, 0.24, 0.6);
        this.chord.forEach((note, i) => this.bell(midi(note + 24), 0.035, (i - 2) * 0.35, t + 0.02 + i * 0.025, this.reverbIn));
    }

    // ------------------------------------------------------------------
    // Building blocks
    // ------------------------------------------------------------------

    /** A short note with a quick attack and exponential decay. */
    ping(freq, gain, pan = 0, decay = 0.3, dest = null, type = 'sine', at = null) {
        const ctx = this.ctx;
        const t = at ?? ctx.currentTime;
        const o = ctx.createOscillator();
        o.type = type;
        o.frequency.value = freq;
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(gain, t + 0.006);
        g.gain.exponentialRampToValueAtTime(0.0001, t + decay);
        const p = ctx.createStereoPanner();
        p.pan.value = Math.max(-1, Math.min(1, pan));
        o.connect(g).connect(p).connect(dest || this.sfx);
        o.start(t);
        o.stop(t + decay + 0.05);
    }

    /** An FM bell. */
    bell(freq, gain, pan = 0, at = null, dest = null) {
        const ctx = this.ctx;
        const t = at ?? ctx.currentTime;
        const carrier = ctx.createOscillator();
        carrier.frequency.value = freq;
        const mod = ctx.createOscillator();
        mod.frequency.value = freq * 3.5;
        const index = ctx.createGain();
        index.gain.setValueAtTime(freq * 2.2, t);
        index.gain.exponentialRampToValueAtTime(1, t + 1.6);
        mod.connect(index).connect(carrier.frequency);
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(gain, t + 0.008);
        g.gain.exponentialRampToValueAtTime(0.0001, t + 2.8);
        const p = ctx.createStereoPanner();
        p.pan.value = pan;
        carrier.connect(g).connect(p).connect(dest || this.sfx);
        if (!dest) g.connect(this.reverbIn);
        carrier.start(t);
        mod.start(t);
        carrier.stop(t + 2.9);
        mod.stop(t + 2.9);
    }

    /** A pitched-down sine: the body of a boom or a soft thump. */
    thump(freq, gain, decay) {
        const ctx = this.ctx;
        const t = ctx.currentTime;
        const o = ctx.createOscillator();
        o.type = 'sine';
        o.frequency.setValueAtTime(freq, t);
        o.frequency.exponentialRampToValueAtTime(freq * 0.45, t + decay);
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(gain, t + 0.01);
        g.gain.exponentialRampToValueAtTime(0.0001, t + decay);
        o.connect(g).connect(this.sfx);
        o.start(t);
        o.stop(t + decay + 0.05);
    }

    /** A low-passed noise burst. */
    burst(cutoff, gain, decay) {
        const ctx = this.ctx;
        const t = ctx.currentTime;
        const src = ctx.createBufferSource();
        src.buffer = this.noise;
        const lp = ctx.createBiquadFilter();
        lp.type = 'lowpass';
        lp.frequency.value = cutoff;
        const g = ctx.createGain();
        g.gain.setValueAtTime(gain, t);
        g.gain.exponentialRampToValueAtTime(0.0001, t + decay);
        src.connect(lp).connect(g).connect(this.sfx);
        src.start(t, Math.random(), decay + 0.05);
    }

    /** A reversed-cymbal swell: air rising into a moment `seconds` from now. */
    swell(seconds, gain) {
        const ctx = this.ctx;
        const t = ctx.currentTime;
        const src = ctx.createBufferSource();
        src.buffer = this.noise;
        const hp = ctx.createBiquadFilter();
        hp.type = 'highpass';
        hp.frequency.setValueAtTime(1200, t);
        hp.frequency.exponentialRampToValueAtTime(6000, t + seconds);
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(gain, t + seconds);
        g.gain.exponentialRampToValueAtTime(0.0001, t + seconds + 0.12);
        src.connect(hp).connect(g);
        g.connect(this.sfx);
        g.connect(this.reverbIn);
        src.start(t, Math.random(), seconds + 0.2);
    }

    /** A filtered sawtooth sweep: the menu's power-up / power-down. */
    sweep(f0, f1, c0, c1, seconds, gain) {
        const ctx = this.ctx;
        const t = ctx.currentTime;
        const o = ctx.createOscillator();
        o.type = 'sawtooth';
        o.frequency.setValueAtTime(f0, t);
        o.frequency.exponentialRampToValueAtTime(f1, t + seconds);
        const lp = ctx.createBiquadFilter();
        lp.type = 'lowpass';
        lp.Q.value = 6;
        lp.frequency.setValueAtTime(c0, t);
        lp.frequency.exponentialRampToValueAtTime(c1, t + seconds);
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, t);
        g.gain.exponentialRampToValueAtTime(gain, t + seconds * 0.3);
        g.gain.exponentialRampToValueAtTime(0.0001, t + seconds);
        o.connect(lp).connect(g).connect(this.sfx);
        o.start(t);
        o.stop(t + seconds + 0.05);
    }
}
