// The visitor's own settings for the universe, kept in localStorage.
// Storage can be missing or throw (private windows, blocked site data); every
// read falls back to the defaults and every write is best-effort.

const KEY = 'xu:prefs:v1';

const defaults = {
    sound: false, // chose "enter with sound" at the gate, or turned it on later
    seen: false, // has been through the gate before — skip it next time
};

function read() {
    try {
        const raw = window.localStorage.getItem(KEY);
        return raw ? { ...defaults, ...JSON.parse(raw) } : { ...defaults };
    } catch {
        return { ...defaults };
    }
}

export const prefs = {
    ...read(),

    set(key, value) {
        this[key] = value;
        try {
            window.localStorage.setItem(KEY, JSON.stringify({ sound: this.sound, seen: this.seen }));
        } catch {
            // Not persisted; the choice still holds for this visit.
        }
    },
};

/** Where the visitor was, so Back from another page lands them there again. */
export const place = {
    save(u) {
        try {
            window.sessionStorage.setItem('xu:u', String(u));
        } catch {
            // ignore
        }
    },

    take() {
        try {
            const raw = window.sessionStorage.getItem('xu:u');
            window.sessionStorage.removeItem('xu:u');
            const u = raw === null ? NaN : parseFloat(raw);
            return Number.isFinite(u) ? u : null;
        } catch {
            return null;
        }
    },
};
