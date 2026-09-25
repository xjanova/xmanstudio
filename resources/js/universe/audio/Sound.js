/**
 * The universe's sound, synthesised live with the Web Audio API — no audio
 * files to download.
 *
 *   ambience  a slow five-voice pad that glides into a new chord at every
 *             stop, a sub drone under it, star "pings" sprinkled on top, and
 *             wind that rises with flight speed and opens the pad's filter
 *   interface hover blips tuned to a pentatonic scale and panned to where
 *             the pointer is, the ring's rotary ticks, lock / select chords,
 *             menu power-up and power-down sweeps, the hyperspace riser and
 *             the boom at the end of a jump
 *
 * Browsers only let a page make sound after a click or key press, so the
 * context is created in unlock(), which main.js calls from those gestures.
 * Everything else is safe to call before that: it simply does nothing.
 */

const midi = (m) => 440 * Math.pow(2, (m - 69) / 12);

// One chord per stop, five notes each, voice-led so every voice glides a short way.
const CHORDS = {
    core: [50, 57, 64, 66, 73], // Dmaj9
    origin: [47, 54, 62, 64, 69], // Bm11
    services: [43, 50, 59, 66, 69], // Gmaj9
    products: [52, 59, 62, 66, 71], // Em9
    platforms: [45, 52, 59, 61, 66], // A6/9
    stack: [42, 49, 57, 64, 69], // F#m11
    reviews: [43, 50, 62, 66, 71], // Gmaj7
    launch: [50, 57, 64, 69, 76], // Dsus2 (open, rising)
    control: [38, 45, 62, 66, 69], // D — home
};

// D major pentatonic from D4 upward.
const SCALE = [62, 64, 66, 69, 71, 74, 76, 78, 81, 83, 86, 88];

export class Sound {
    constructor(enabled = false) {
        this.enabled = enabled;
        this.ctx = null;
        this.chord = CHORDS.core;
        this.lastHover = 0;
        this.motion = 0;
        const AC = window.AudioContext || window.webkitAudioContext;
        this.supported = !!AC;
        this.AC = AC;

        document.addEventListener('visibilitychange', () => {
            if (!this.ctx) return;
            if (document.hidden) this.ctx.suspend().catch(() => {});
            else if (this.enabled) this.ctx.resume().catch(() => {});
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
    }

    setEnabled(on) {
        this.enabled = on;
        if (on) {
            this.unlock();
        } else if (this.ctx) {
            this.fadeMaster(0, 0.35);
            clearTimeout(this.suspendTimer);
            this.suspendTimer = setTimeout(() => {
                if (!this.enabled) this.ctx.suspend().catch(() => {});
            }, 450);
        }
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
        limiter.threshold.value = -12;
        limiter.knee.value = 10;
        limiter.ratio.value = 5;
        limiter.attack.value = 0.004;
        limiter.release.value = 0.28;
        this.master.connect(limiter).connect(ctx.destination);

        // A generated hall reverb shared by everything.
        this.reverb = ctx.createConvolver();
        this.reverb.buffer = this.impulse(3.6, 2.4);
        this.reverbIn = ctx.createGain();
        const reverbOut = ctx.createGain();
        reverbOut.gain.value = 0.62;
        this.reverbIn.connect(this.reverb).connect(reverbOut).connect(this.master);

        this.music = ctx.createGain();
        this.music.gain.value = 0;
        this.music.connect(this.master);
        const musicSend = ctx.createGain();
        musicSend.gain.value = 0.7;
        this.music.connect(musicSend).connect(this.reverbIn);

        this.sfx = ctx.createGain();
        this.sfx.gain.value = 0.9;
        this.sfx.connect(this.master);
        const sfxSend = ctx.createGain();
        sfxSend.gain.value = 0.32;
        this.sfx.connect(sfxSend).connect(this.reverbIn);

        this.noise = this.noiseBuffer(2);
        this.buildPad();
        this.buildWind();
        this.scheduleShimmer();

        const now = ctx.currentTime;
        this.music.gain.setValueAtTime(0, now);
        this.music.gain.linearRampToValueAtTime(0.2, now + 4);
    }

    impulse(seconds, decay) {
        const rate = this.ctx.sampleRate;
        const length = Math.floor(rate * seconds);
        const buffer = this.ctx.createBuffer(2, length, rate);
        for (let c = 0; c < 2; c++) {
            const data = buffer.getChannelData(c);
            for (let i = 0; i < length; i++) {
                const t = i / length;
                const attack = Math.min(1, i / (rate * 0.015));
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

    buildPad() {
        const ctx = this.ctx;
        this.padFilter = ctx.createBiquadFilter();
        this.padFilter.type = 'lowpass';
        this.padFilter.frequency.value = 760;
        this.padFilter.Q.value = 0.7;
        this.padFilter.connect(this.music);

        // Slow sweep on the filter, so the pad breathes.
        const lfo = ctx.createOscillator();
        lfo.frequency.value = 0.055;
        const lfoDepth = ctx.createGain();
        lfoDepth.gain.value = 280;
        lfo.connect(lfoDepth).connect(this.padFilter.frequency);
        lfo.start();

        this.voices = this.chord.map((note, v) => {
            const pan = ctx.createStereoPanner();
            pan.pan.value = (v / (this.chord.length - 1)) * 1.2 - 0.6;
            const gain = ctx.createGain();
            gain.gain.value = v === 0 ? 0.075 : 0.055;
            gain.connect(pan).connect(this.padFilter);
            const oscs = [-7, 7].map((cents) => {
                const o = ctx.createOscillator();
                o.type = 'sawtooth';
                o.frequency.value = midi(note);
                o.detune.value = cents + (Math.random() - 0.5) * 4;
                o.connect(gain);
                o.start();
                return o;
            });
            return { oscs, gain };
        });

        const subGain = ctx.createGain();
        subGain.gain.value = 0.14;
        subGain.connect(this.music);
        this.sub = ctx.createOscillator();
        this.sub.type = 'sine';
        this.sub.frequency.value = midi(this.chord[0] - 12);
        this.sub.connect(subGain);
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

    scheduleShimmer() {
        const next = 1400 + Math.random() * 2600;
        this.shimmerTimer = setTimeout(() => {
            if (this.live) {
                const note = this.chord[1 + Math.floor(Math.random() * 4)] + 24;
                this.ping(midi(note), 0.022 + Math.random() * 0.02, Math.random() * 1.6 - 0.8, 2.8, this.reverbIn);
            }
            this.scheduleShimmer();
        }, next);
    }

    // ------------------------------------------------------------------
    // Ambience control
    // ------------------------------------------------------------------

    setStation(type) {
        const chord = CHORDS[type];
        if (!chord || chord === this.chord) return;
        this.chord = chord;
        if (!this.ctx) return;
        const now = this.ctx.currentTime;
        this.voices.forEach((voice, v) => {
            for (const o of voice.oscs) o.frequency.setTargetAtTime(midi(chord[v]), now, 0.55);
        });
        this.sub.frequency.setTargetAtTime(midi(chord[0] - 12), now, 0.8);
    }

    /** 0 = still, 1 = flat out. Wind and pad brightness follow it. */
    setMotion(v) {
        if (!this.ctx) return;
        const m = Math.min(1, Math.max(0, v));
        if (Math.abs(m - this.motion) < 0.01) return;
        this.motion = m;
        const now = this.ctx.currentTime;
        this.windGain.gain.setTargetAtTime(m * m * 0.22, now, 0.12);
        this.windFilter.frequency.setTargetAtTime(260 + m * 2600, now, 0.15);
        this.padFilter.frequency.setTargetAtTime(760 + m * 1300, now, 0.25);
    }

    /** The arrival bell of a stop. */
    chime(type) {
        if (!this.live) return;
        const chord = CHORDS[type] || this.chord;
        this.bell(midi(chord[2] + 12), 0.1, 0);
        setTimeout(() => this.live && this.bell(midi(chord[4] + 12), 0.06, 0.3), 140);
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
    }

    click() {
        if (!this.live) return;
        this.ping(midi(SCALE[5] + 12), 0.06, 0, 0.12);
        this.thump(140, 0.12, 0.12);
    }

    select(index = 0) {
        if (!this.live) return;
        const root = midi(SCALE[index % 5] + 12);
        [1, 1.25, 1.5, 2].forEach((ratio, i) => {
            setTimeout(() => this.live && this.ping(root * ratio, 0.075, (i - 1.5) * 0.3, 0.9, null, 'triangle'), i * 48);
        });
        this.thump(96, 0.4, 0.3);
    }

    open() {
        if (!this.live) return;
        this.sweep(110, 220, 180, 3200, 0.55, 0.11);
        [0, 1, 2].forEach((i) => setTimeout(() => this.live && this.ping(midi(this.chord[2 + i] + 24), 0.035, (i - 1) * 0.6, 1.4, this.reverbIn), 120 + i * 70));
    }

    close() {
        if (!this.live) return;
        this.sweep(220, 110, 2600, 220, 0.35, 0.09);
    }

    toggle(on) {
        if (!this.live) return;
        const a = midi(on ? SCALE[3] : SCALE[5]);
        const b = midi(on ? SCALE[5] : SCALE[3]);
        this.ping(a, 0.06, 0, 0.18);
        setTimeout(() => this.live && this.ping(b, 0.06, 0, 0.3), 90);
    }

    /** Hyperspace: a rising roar over `seconds`, ending in a boom. */
    warp(seconds = 1.2, boom = true) {
        if (!this.live) return;
        const ctx = this.ctx;
        const t = ctx.currentTime;
        const end = t + seconds;

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

        if (boom) {
            setTimeout(() => {
                if (!this.live) return;
                this.thump(72, 0.7, 1.1);
                this.burst(900, 0.22, 0.5);
                this.chime('launch');
            }, seconds * 1000);
        }
    }

    // ------------------------------------------------------------------
    // Building blocks
    // ------------------------------------------------------------------

    /** A short sine (or other wave) note with a quick attack and exponential decay. */
    ping(freq, gain, pan = 0, decay = 0.3, dest = null, type = 'sine') {
        const ctx = this.ctx;
        const t = ctx.currentTime;
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
    bell(freq, gain, pan = 0) {
        const ctx = this.ctx;
        const t = ctx.currentTime;
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
        g.gain.exponentialRampToValueAtTime(0.0001, t + 2.6);
        const p = ctx.createStereoPanner();
        p.pan.value = pan;
        carrier.connect(g).connect(p).connect(this.sfx);
        g.connect(this.reverbIn);
        carrier.start(t);
        mod.start(t);
        carrier.stop(t + 2.7);
        mod.stop(t + 2.7);
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
