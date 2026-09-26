/**
 * Talking to the guide: the speech bubble's "ask me" field, and the chat
 * window it opens, answered by the site's own AI assistant (POST
 * /ai-chat, PublicChatController, the same one the chat button on every
 * other page uses).
 *
 * The conversation is kept in sessionStorage under the same key that button
 * uses, so it carries on when the visitor moves to another page.
 *
 * Replies are drawn with DOM nodes, never innerHTML: the text comes from a
 * language model, and a model can be talked into writing markup. Only links
 * to this site become links.
 */
const STORAGE_KEY = 'ai_chat_history';
const MAX_MESSAGES = 20;

export class Chat {
    constructor({ root, url, avatar, sound, onOpenChange }) {
        this.root = root;
        this.url = url;
        this.avatar = avatar;
        this.sound = sound;
        this.onOpenChange = onOpenChange;
        this.log = root.querySelector('#xu-chat-log');
        this.form = root.querySelector('#xu-chat-form');
        this.input = root.querySelector('#xu-chat-input');
        this.send = root.querySelector('#xu-chat-send');
        this.status = root.querySelector('#xu-chat-status');
        this.history = [];
        this.busy = false;
        this.isOpen = false;
        this.greeted = false;
        this.opener = null;
        this.token = document.querySelector('meta[name="csrf-token"]')?.content || '';

        try {
            const saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]');
            if (Array.isArray(saved)) this.history = saved.filter((m) => m && (m.role === 'user' || m.role === 'assistant') && typeof m.content === 'string');
        } catch {
            this.history = [];
        }
        for (const m of this.history) this.append(m.role, m.content, false);

        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.ask(this.input.value);
        });
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
                e.preventDefault();
                this.ask(this.input.value);
            }
        });
        root.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                e.stopPropagation();
                this.close(true);
            }
            // On a phone the chat is a sheet over the page: Tab goes round inside it.
            if (e.key === 'Tab' && window.matchMedia('(max-width: 700px)').matches) {
                const stops = [...root.querySelectorAll('a[href], button:not([disabled]), textarea')].filter((el) => el.offsetParent !== null);
                const first = stops[0];
                const last = stops[stops.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });
        this.input.addEventListener('input', () => this.grow());
        // Closed from the keyboard (detail 0), focus goes back where the chat was opened from.
        for (const b of root.querySelectorAll('[data-xu-chat-close]')) b.addEventListener('click', (e) => this.close(e.detail === 0));
    }

    grow() {
        this.input.style.height = 'auto';
        this.input.style.height = `${Math.min(this.input.scrollHeight, 96)}px`;
    }

    open(first = '') {
        if (!this.isOpen) {
            this.isOpen = true;
            const active = document.activeElement;
            this.opener = active && active !== document.body && !this.root.contains(active) ? active : null;
            this.root.hidden = false;
            requestAnimationFrame(() => requestAnimationFrame(() => this.isOpen && this.root.classList.add('is-open')));
            this.onOpenChange?.(true);
            this.sound.lock(4);
            // Her hello is for the window only: it never goes to the assistant.
            if (!this.history.length && !first && !this.greeted) {
                this.greeted = true;
                this.append('assistant', 'สวัสดีค่ะ! อยากรู้เรื่องบริการ ผลิตภัณฑ์ หรือราคาอะไร ถามหนูได้เลยนะคะ ✦\nHi! Ask me anything about our services, products or prices.', true);
            }
        }
        if (first) this.ask(first);
        setTimeout(() => this.isOpen && this.input.focus({ preventScroll: true }), 120);
        this.scroll();
    }

    close(restoreFocus = false) {
        if (!this.isOpen) return;
        this.isOpen = false;
        this.root.classList.remove('is-open');
        this.onOpenChange?.(false);
        this.sound.toggle(false);
        if (restoreFocus && this.opener?.isConnected) this.opener.focus({ preventScroll: true });
        else if (this.root.contains(document.activeElement)) document.activeElement.blur();
        setTimeout(() => {
            if (!this.isOpen) this.root.hidden = true;
        }, 320);
    }

    save() {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(this.history.slice(-MAX_MESSAGES)));
        } catch {
            // ignore
        }
    }

    scroll() {
        requestAnimationFrame(() => {
            this.log.scrollTop = this.log.scrollHeight;
        });
    }

    async ask(text) {
        const message = String(text || '').trim().slice(0, 2000);
        if (!message || this.busy) return;
        if (!this.isOpen) {
            this.open(message);
            return;
        }
        this.busy = true;
        this.send.disabled = true;
        this.input.value = '';
        this.grow();

        this.history.push({ role: 'user', content: message });
        this.append('user', message, true);
        this.save();
        this.sound.message(false);
        const typing = this.typing();
        this.status.textContent = 'กำลังพิมพ์... · typing';
        this.root.classList.add('is-thinking');

        // A question that gets no answer leaves the history again: the assistant
        // should not be sent two questions in a row, the first one unanswered.
        const forget = () => {
            if (this.history[this.history.length - 1]?.content === message) {
                this.history.pop();
                this.save();
            }
        };

        try {
            const response = await fetch(this.url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.token,
                },
                body: JSON.stringify({
                    messages: this.history.slice(-MAX_MESSAGES).map((m) => ({ role: m.role, content: m.content })),
                    current_url: location.href,
                    current_path: location.pathname,
                    page_title: document.title,
                }),
            });
            const data = await response.json().catch(() => ({}));
            typing.remove();
            if (response.ok && data.success && data.message) {
                this.history.push({ role: 'assistant', content: String(data.message) });
                this.append('assistant', String(data.message), true);
                this.save();
                this.sound.message(true);
            } else if (response.status === 429) {
                forget();
                this.append('assistant', 'ถามถี่ไปนิดนึงค่ะ รอสักครู่แล้วลองใหม่นะคะ\nToo many messages, please wait a moment.', true);
            } else if (response.status === 419) {
                // The page sat open until its session ran out.
                forget();
                this.append('assistant', 'หน้านี้เปิดไว้นานไปหน่อยค่ะ รีเฟรชหน้าแล้วถามใหม่ได้เลยนะคะ\nThis page has been open a while: refresh it and ask again.', true);
            } else {
                // The controller's own refusals are written for visitors; a framework
                // validation error ({message, errors}) is not.
                forget();
                const told = data.message && !data.errors ? String(data.message) : '';
                this.append('assistant', told || 'ขออภัยค่ะ ตอนนี้ตอบไม่ได้ ลองใหม่อีกครั้งนะคะ\nSorry, please try again.', true);
            }
        } catch {
            typing.remove();
            forget();
            this.append('assistant', 'เชื่อมต่อไม่ได้ค่ะ ลองใหม่อีกครั้งนะคะ\nCould not connect, please try again.', true);
        } finally {
            this.busy = false;
            this.send.disabled = false;
            this.status.textContent = 'ออนไลน์ · Online';
            this.root.classList.remove('is-thinking');
            if (this.isOpen) this.input.focus({ preventScroll: true });
        }
    }

    typing() {
        const row = document.createElement('div');
        row.className = 'xu-chat__msg xu-chat__msg--bot';
        row.append(this.face());
        const dots = document.createElement('div');
        dots.className = 'xu-chat__dots';
        dots.append(document.createElement('i'), document.createElement('i'), document.createElement('i'));
        row.append(dots);
        this.log.append(row);
        this.scroll();
        return row;
    }

    face() {
        const img = document.createElement('img');
        img.src = this.avatar;
        img.alt = '';
        img.className = 'xu-chat__face';
        return img;
    }

    append(role, content, animate) {
        const row = document.createElement('div');
        row.className = `xu-chat__msg xu-chat__msg--${role === 'user' ? 'user' : 'bot'}`;
        if (role !== 'user') row.append(this.face());
        const bubble = document.createElement('div');
        bubble.className = 'xu-chat__bubble';
        this.render(bubble, content);
        row.append(bubble);
        if (animate) row.classList.add('is-new');
        this.log.append(row);
        this.scroll();
    }

    /** Plain text, **bold**, line breaks, and links to this site only. */
    render(el, text) {
        const lines = String(text).split('\n');
        lines.forEach((line, i) => {
            if (i) el.append(document.createElement('br'));
            this.renderLine(el, line);
        });
    }

    renderLine(el, line) {
        const pattern = /\[([^\]]+)\]\(([^)\s]+)\)|(https?:\/\/[^\s)\]]+)|\*\*([^*]+)\*\*/g;
        let last = 0;
        let m;
        while ((m = pattern.exec(line))) {
            if (m.index > last) el.append(line.slice(last, m.index));
            if (m[4] !== undefined) {
                const b = document.createElement('strong');
                b.textContent = m[4];
                el.append(b);
            } else {
                const href = m[2] ?? m[3];
                const label = m[1] ?? href;
                const link = this.safeLink(href, label);
                el.append(link ?? label);
            }
            last = pattern.lastIndex;
        }
        if (last < line.length) el.append(line.slice(last));
    }

    safeLink(href, label) {
        let url;
        try {
            url = new URL(href, location.origin);
        } catch {
            return null;
        }
        if (url.origin !== location.origin || !/^https?:$/.test(url.protocol)) return null;
        const a = document.createElement('a');
        a.href = url.href;
        a.textContent = label === href ? url.pathname : label;
        a.className = 'xu-chat__link';
        return a;
    }
}
