// Draws the MacDock bar the way DockWindow.cs lays it out:
//   Gap 6 · Pad 8 · ItemGap 4 · HandleLen 16 · SepLen 12 · icon 56 · zoom 1.7
//   icons sit on the screen edge and grow away from it; the blue dot sits on the edge side;
//   a header is a dark pill "▾ name" (in a side dock it is S wide and trimmed with "…");
//   the tooltip opens away from the edge.
// MacDock.dock({ edge, items, hover, tip }) — items: {g: glyph, name, run} | '|' | {hdr, col}
(function () {
    var G = {
        browser: ['#4fa3ff', '#2563eb', '<circle cx="12" cy="12" r="9"/><ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18"/>'],
        code: ['#a78bfa', '#6d28d9', '<path d="M8 7l-5 5 5 5M16 7l5 5-5 5M14 5l-4 14"/>'],
        terminal: ['#475569', '#0f172a', '<path d="M5 8l4 4-4 4M12 16h7"/>'],
        music: ['#fb7185', '#e11d48', '<path d="M9 18V6l11-2v12"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/>'],
        folder: ['#fcd34d', '#f59e0b', '<path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>'],
        project: ['#93c5fd', '#3b82f6', '<path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><path d="M9 13h6"/>'],
        notes: ['#fde047', '#ca8a04', '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>'],
        sheet: ['#4ade80', '#16a34a', '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M4 10h16M4 15h16M10 4v16"/>'],
        slides: ['#fb923c', '#ea580c', '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M12 16v4M8 20h8"/>'],
        photo: ['#f472b6', '#c026d3', '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="M21 15l-5-5L5 19"/>'],
        video: ['#f87171', '#dc2626', '<rect x="3" y="6" width="13" height="12" rx="2"/><path d="M16 10l5-3v10l-5-3z"/>'],
        chat: ['#2dd4bf', '#0d9488', '<path d="M21 12a8 8 0 01-11.6 7.1L4 20l1-4.6A8 8 0 1121 12z"/>'],
        mail: ['#60a5fa', '#1d4ed8', '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>'],
        calc: ['#94a3b8', '#475569', '<rect x="5" y="3" width="14" height="18" rx="2"/><rect x="8" y="6" width="8" height="4"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>'],
        gear: ['#9ca3af', '#4b5563', '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>'],
        printer: ['#a5b4fc', '#6366f1', '<path d="M6 9V3h12v6"/><rect x="4" y="9" width="16" height="7" rx="2"/><rect x="7" y="14" width="10" height="7"/>'],
        game: ['#818cf8', '#4338ca', '<rect x="2" y="7" width="20" height="10" rx="5"/><path d="M6 12h4M8 10v4M15 11h.01M18 13h.01"/>'],
        download: ['#38bdf8', '#0284c7', '<path d="M12 4v11M7 11l5 5 5-5M5 20h14"/>'],
        cloud: ['#7dd3fc', '#0ea5e9', '<path d="M7 18a4 4 0 010-8 6 6 0 0111.5 1.5A3.5 3.5 0 0118 18z"/>'],
    };
    var Gap = 6, Pad = 8, ItemGap = 4, Handle = 16, SepLen = 12;

    function el(cls, css, html) {
        var d = document.createElement('div');
        d.className = cls;
        Object.keys(css || {}).forEach(function (k) { d.style[k] = typeof css[k] === 'number' ? css[k] + 'px' : css[k]; });
        if (html) { d.innerHTML = html; }
        return d;
    }

    function hdrLen(text) {
        var c = document.createElement('canvas').getContext('2d');
        c.font = '600 13px "Segoe UI Semibold", "Segoe UI", NotoThai';
        return Math.ceil(c.measureText(text).width) + 28;
    }

    window.MacDock = {
        dock: function (o) {
            var S = o.size || 56, zoom = o.zoom || 1.7;
            var edge = o.edge || 'top', horiz = edge === 'top' || edge === 'bottom';
            var W = 1440, H = 900;
            var items = o.items.map(function (it) {
                if (it === '|') { return { kind: 'sep', base: SepLen }; }
                if (it.hdr) {
                    var label = (it.col ? '▸ ' : '▾ ') + it.hdr;
                    return { kind: 'hdr', label: label, base: horiz ? hdrLen(label) : 30 };
                }
                return { kind: 'ico', g: it.g, name: it.name, run: !!it.run, base: S };
            });
            // magnification: a smooth fall-off around the icon under the pointer
            var pos = 0;
            items.forEach(function (it) { it.c0 = pos + it.base / 2; pos += it.base + ItemGap; });
            var hc = o.hover != null ? items[o.hover].c0 : null;
            items.forEach(function (it) {
                it.size = it.base;
                if (it.kind === 'ico' && hc !== null) {
                    var d = Math.min(Math.abs(it.c0 - hc) / (S * 3.2), 1);
                    it.size = S * (1 + (zoom - 1) * Math.cos(d * Math.PI / 2));
                }
            });
            var len = items.reduce(function (s, it) { return s + it.size + ItemGap; }, 0) - ItemGap;
            var along = len + 2 * Pad + Handle, cross = S + 2 * Pad;
            var start = Math.round(((horiz ? W : H) - along) / 2);

            var dock = el('dock', { left: 0, top: 0, width: W, height: H });
            // bar rectangle
            var bar = horiz
                ? { left: start, width: along, height: cross, top: edge === 'top' ? Gap : H - Gap - cross }
                : { top: start, height: along, width: cross, left: edge === 'left' ? Gap : W - Gap - cross };
            var b = el('bar', bar);
            var grip = el('grip', horiz
                ? { left: 0, top: 0, bottom: 0, width: Handle, borderRadius: '17px 0 0 17px', flexDirection: 'column' }
                : { left: 0, right: 0, top: 0, height: Handle, borderRadius: '17px 17px 0 0', flexDirection: 'row' }, '<i></i><i></i><i></i>');
            b.appendChild(grip);
            dock.appendChild(b);

            // the edge-side line every item hangs from
            var line = edge === 'top' ? Gap + Pad : edge === 'left' ? Gap + Pad : edge === 'bottom' ? H - Gap - Pad : W - Gap - Pad;
            var cur = start + Handle + Pad;
            var tipAt = null;
            items.forEach(function (it, i) {
                var a0 = cur, sz = it.size;
                cur += sz + ItemGap;
                // box along the dock (a0..a0+sz) and across it (S, or sz for a magnified icon)
                var acr = it.kind === 'ico' ? sz : S;
                var x, y, w, h;
                if (horiz) { x = a0; w = sz; h = acr; y = edge === 'top' ? line : line - acr; }
                else { y = a0; h = sz; w = acr; x = edge === 'left' ? line : line - acr; }
                if (it.kind === 'sep') {
                    var lw = horiz ? 1.5 : S * .7, lh = horiz ? S * .7 : 1.5;
                    dock.appendChild(el('sep', { left: x + (w - lw) / 2, top: y + (h - lh) / 2, width: lw, height: lh }));
                    return;
                }
                if (it.kind === 'hdr') {
                    var hw = horiz ? w - 4 : S - 4;
                    var hx = horiz ? x + 2 : x + 2, hy = y + (h - 26) / 2;
                    var p = el('hdr', { left: hx, top: hy, width: hw }, '');
                    p.textContent = it.label;
                    dock.appendChild(p);
                    return;
                }
                var hot = o.hover === i;
                var gl = (hot ? 1 : it.run ? .85 : 0);
                if (gl) {
                    var m = S * .25;
                    dock.appendChild(el('glow', { left: x - m, top: y - m, width: w + 2 * m, height: h + 2 * m, opacity: gl * .75 }));
                }
                var c = G[it.g] || G.gear;
                var ic = el('ico', { left: x, top: y, width: w, height: h, background: 'linear-gradient(160deg,' + c[0] + ',' + c[1] + ')' },
                    '<svg viewBox="0 0 24 24">' + c[2] + '</svg>');
                if (gl) { ic.style.boxShadow = 'inset 0 1px 0 rgba(255,255,255,.35), 0 0 ' + Math.max(18, S * .45) + 'px rgba(40,160,255,' + (gl * .9) + ')'; }
                dock.appendChild(ic);
                if (it.run) {
                    var off = Pad * .7;
                    var dx = horiz ? x + w / 2 - 3 : (edge === 'left' ? x - off : x + w + off - 6);
                    var dy = horiz ? (edge === 'top' ? y - off : y + h + off - 6) : y + h / 2 - 3;
                    dock.appendChild(el('dot', { left: dx, top: dy }));
                }
                if (hot && o.tip !== false) { tipAt = { x: x, y: y, w: w, h: h, name: it.name }; }
            });
            if (tipAt) {
                var t = el('tip', {});
                t.textContent = tipAt.name;
                if (edge === 'top') { t.style.left = tipAt.x + 'px'; t.style.top = (tipAt.y + tipAt.h + 4) + 'px'; }
                if (edge === 'bottom') { t.style.left = tipAt.x + 'px'; t.style.top = (tipAt.y - 30) + 'px'; }
                if (edge === 'left') { t.style.left = (tipAt.x + tipAt.w + 6) + 'px'; t.style.top = (tipAt.y + tipAt.h / 2 - 12) + 'px'; }
                if (edge === 'right') { t.style.right = (W - tipAt.x + 6) + 'px'; t.style.top = (tipAt.y + tipAt.h / 2 - 12) + 'px'; }
                dock.appendChild(t);
            }
            document.body.appendChild(dock);
            return { bar: bar };
        },
    };
})();
