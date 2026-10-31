// The window around every Live x Shop Pro mock-up, as Views/Main/MainWindow.xaml lays it out: the MetroWindow
// title bar with its right-hand commands (live status, mobile link, notifications, settings), the 260px sidebar
// (logo, quick stats, the five nav groups, licence box, version) and the page header (title, subtitle, search,
// "เริ่มไลฟ์"). <body data-on="live" data-title="…" data-sub="…" data-live="1"> picks the open page.
// Icons stand in for the app's Material Design PackIcons.
(function () {
    var P = {
        logo: 'M19 6h-2c0-2.76-2.24-5-5-5S7 3.24 7 6H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-7-3c1.66 0 3 1.34 3 3H9c0-1.66 1.34-3 3-3zm7 17H5V8h14v12zm-7-8c-1.66 0-3-1.34-3-3H7c0 2.76 2.24 5 5 5s5-2.24 5-5h-2c0 1.66-1.34 3-3 3z',
        dashboard: 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z',
        live: 'M12 10a2 2 0 100 4 2 2 0 000-4zM7.76 7.76A5.97 5.97 0 006 12c0 1.66.67 3.16 1.76 4.24l1.41-1.41A3.99 3.99 0 018 12c0-1.1.45-2.1 1.17-2.83L7.76 7.76zm8.48 0l-1.41 1.41A3.99 3.99 0 0116 12c0 1.1-.45 2.1-1.17 2.83l1.41 1.41A5.97 5.97 0 0018 12c0-1.66-.67-3.16-1.76-4.24zM4.93 4.93A9.97 9.97 0 002 12c0 2.76 1.12 5.26 2.93 7.07l1.41-1.41A7.97 7.97 0 014 12c0-2.21.9-4.21 2.34-5.66L4.93 4.93zm14.14 0l-1.41 1.41A7.97 7.97 0 0120 12c0 2.21-.9 4.21-2.34 5.66l1.41 1.41A9.97 9.97 0 0022 12c0-2.76-1.12-5.26-2.93-7.07z',
        chat: 'M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z',
        orders: 'M19 3h-4.18C14.4 1.84 13.3 1 12 1s-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-2 14H7v-2h3v2zm0-4H7v-2h3v2zm0-4H7V7h3v2zm7 8h-5v-2h5v2zm0-4h-5v-2h5v2zm0-4h-5V7h5v2z',
        box: 'M21 16.5c0 .38-.21.71-.53.88l-7.9 4.44c-.16.12-.36.18-.57.18s-.41-.06-.57-.18l-7.9-4.44A.991.991 0 013 16.5v-9c0-.38.21-.71.53-.88l7.9-4.44c.16-.12.36-.18.57-.18s.41.06.57.18l7.9 4.44c.32.17.53.5.53.88v9zM12 4.15L6.04 7.5 12 10.85l5.96-3.35L12 4.15z',
        people: 'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z',
        pos: 'M2 17h20v4H2zm2-8h16v7H4zm2-6h9v5H6zm11 1h2v4h-2z',
        card: 'M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z',
        slip: 'M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm-3.06 16L7.4 14.46l1.41-1.41 2.12 2.12 4.24-4.24 1.41 1.41L10.94 18zM13 9V3.5L18.5 9H13z',
        truck: 'M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z',
        pack: 'M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z',
        bars: 'M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z',
        trend: 'M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z',
        shield: 'M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z',
        phone: 'M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z',
        bell: 'M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z',
        cog: 'M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58a.49.49 0 00.12-.61l-1.92-3.32a.488.488 0 00-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54a.484.484 0 00-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58a.49.49 0 00-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z',
        search: 'M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z',
        money: 'M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z',
        addUser: 'M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z',
        fb: 'M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z',
        music: 'M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z',
        qr: 'M3 11h8V3H3v8zm2-6h4v4H5V5zm8-2v8h8V3h-8zm6 6h-4V5h4v4zM3 21h8v-8H3v8zm2-6h4v4H5v-4zm8-2h2v2h-2zm2 2h2v2h-2zm2-2h2v2h-2zm-4 4h2v2h-2zm4 0h2v2h-2zm2 2h2v2h-2zm-2 0h-2v2h2z',
        plus: 'M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z',
        down: 'M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z',
        up: 'M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z',
        filter: 'M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z',
        eye: 'M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z',
        printer: 'M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z',
        refresh: 'M17.65 6.35A7.958 7.958 0 0012 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08A5.99 5.99 0 0112 18c-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z',
        check: 'M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z',
        skip: 'M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z',
        barcode: 'M2 6h2v12H2zm3 0h1v12H5zm2 0h3v12H7zm4 0h1v12h-1zm3 0h2v12h-2zm3 0h1v12h-1zm2 0h3v12h-3z',
        tel: 'M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z',
        pin: 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 010-5 2.5 2.5 0 010 5z',
        pencil: 'M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a.996.996 0 000-1.41l-2.34-2.34a.996.996 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z',
        stop: 'M6 6h12v12H6z',
        cart: 'M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0020 4H5.21l-.94-2H1z',
    };
    window.ic = function (name, cls) {
        return '<svg class="i ' + (cls || '') + '" viewBox="0 0 24 24" aria-hidden="true"><path d="' + P[name] + '"/></svg>';
    };

    var b = document.body;
    var on = b.getAttribute('data-on');
    var isLive = b.getAttribute('data-live') === '1';
    var groups = [
        ['หลัก', [['dashboard', 'dashboard', 'แดชบอร์ด'], ['live', 'live', 'ไลฟ์สด'], ['chat', 'chat', 'แชท CF']]],
        ['ขาย', [['orders', 'orders', 'ออเดอร์'], ['products', 'box', 'สินค้า'], ['customers', 'people', 'ลูกค้า'], ['pos', 'pos', 'POS']]],
        ['การเงิน', [['payments', 'card', 'การชำระเงิน'], ['slip', 'slip', 'ตรวจสลิป']]],
        ['จัดส่ง', [['shipping', 'truck', 'การจัดส่ง'], ['packing', 'pack', 'สถานีแพ็ค']]],
        ['รายงาน', [['reports', 'bars', 'รายงาน'], ['analytics', 'trend', 'วิเคราะห์']]],
    ];
    var nav = groups.map(function (g) {
        return '<div class="grp">' + g[0] + '</div>' + g[1].map(function (it) {
            return '<div class="it' + (it[0] === on ? ' on' : '') + '">' + ic(it[1]) + '<span>' + it[2] + '</span>'
                + (it[0] === 'live' && isLive ? '<span class="live">LIVE</span>' : '') + '</div>';
        }).join('');
    }).join('');

    var stats = (b.getAttribute('data-stats') || '86|12|฿48,250').split('|');
    var html =
        '<div class="titlebar">' + ic('logo', 'pri') + '<span class="ttl">Live x Shop Pro - ระบบจัดการไลฟ์ขายของครบวงจร</span>' +
        '<div class="cmds">' +
        '<span class="cmd"><span class="led' + (isLive ? ' on' : '') + '"></span>' + (isLive ? 'กำลัง Live อยู่' : 'ไม่ได้ Live') + '</span>' +
        '<span class="cmd">' + ic('phone') + (isLive ? 'เชื่อมต่อแล้ว' : 'ไม่ได้เชื่อมต่อ') + '</span>' +
        '<span class="cmd"><span class="badge">' + ic('bell') + '<b>3</b></span></span>' +
        '<span class="cmd">' + ic('cog') + '</span>' +
        '<span class="caps"><i>—</i><i>☐</i><i>✕</i></span></div></div>' +
        '<div class="app"><aside class="side">' +
        '<div class="logo"><div class="brand">' + ic('logo') + '<div><div class="n">Live x Shop Pro</div><div class="s">by Xman Studio</div></div></div>' +
        '<div class="qs"><div><b>' + stats[0] + '</b><span>ออเดอร์</span></div><div><b>' + stats[1] + '</b><span>รอแพ็ค</span></div><div><b>' + stats[2] + '</b><span>ยอดขาย</span></div></div></div>' +
        '<nav>' + nav + '</nav>' +
        '<div class="foot"><div class="lic">' + ic('shield') + 'License: ใช้งานอยู่</div><div class="ver">v1.0.0 | Xman Studio</div></div>' +
        '</aside><section class="main">' +
        '<div class="head"><div><div class="t">' + b.getAttribute('data-title') + '</div><div class="st">' + b.getAttribute('data-sub') + '</div></div>' +
        '<div class="right"><div class="search">' + ic('search') + 'ค้นหา... (Ctrl+K)</div><span class="btn pri">' + ic('live') + 'เริ่มไลฟ์</span></div></div>' +
        '<div class="content" id="slot"></div></section></div>';

    var page = document.getElementById('page');
    var holder = document.createElement('div');
    holder.innerHTML = html;
    b.insertBefore(holder, page);
    document.getElementById('slot').appendChild(page);
    // a page's own markup may use {ic:name} placeholders for icons
    page.innerHTML = page.innerHTML.replace(/\{ic:(\w+)(?: (\w+))?\}/g, function (_, n, c) { return ic(n, c); });
})();
