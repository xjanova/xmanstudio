// The Skidrow Killer window around every mock-up: title bar, the sidebar of MainWindow.xaml (same items,
// same order) and the status bar. <body data-on="scan" data-threats="4"> marks the open page.
(function () {
    var svg = function (d) { return '<svg class="ic" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + d + '</svg>'; };
    var I = {
        home: svg('<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>'),
        scan: svg('<circle cx="11" cy="11" r="7"/><path d="M21 21l-5-5"/>'),
        monitor: svg('<path d="M3 12h4l3-8 4 16 3-8h4"/>'),
        net: svg('<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 010 18M12 3a14 14 0 000 18"/>'),
        warn: svg('<path d="M12 3l10 18H2z"/><path d="M12 10v4M12 17h.01"/>'),
        check: svg('<path d="M5 13l4 4L19 7"/>'),
        backup: svg('<path d="M4 7h16v13H4z"/><path d="M2 3h20v4H2zM10 12h4"/>'),
        gear: svg('<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>'),
    };
    var e = function (c) { return '<span class="ic">' + c + '</span>'; };
    var threats = document.body.getAttribute('data-threats') || '';
    var nav = [
        ['home', 'Home', I.home], ['scan', 'Scan', I.scan], ['monitor', 'Monitor', I.monitor], ['web', 'Web Protection', I.net],
        ['browser', 'Browser Protection', e('🌐')], ['cleanup', 'System Cleanup', e('🧹')], ['usb', 'USB Protection', e('🔌')],
        ['ransomware', 'Ransomware', e('🛡️')], ['scheduled', 'Scheduled Scans', e('📅')], ['gaming', 'Gaming Mode', e('🎮')],
        ['threats', 'Threats', I.warn, threats], ['whitelist', 'Whitelist', I.check], ['backups', 'Backups', I.backup],
        ['quarantine', 'Quarantine', e('🔒')], 'hr', ['intel', 'Threat Intel', e('🛡️')], ['license', 'License', e('🔑')], ['settings', 'Settings', I.gear],
    ];
    var on = document.body.getAttribute('data-on');
    var items = nav.map(function (n) {
        if (n === 'hr') { return '<hr>'; }
        return '<li class="' + (n[0] === on ? 'on' : '') + '">' + n[2] + n[1] + (n[3] ? '<span class="badge">' + n[3] + '</span>' : '') + '</li>';
    }).join('');

    var bar = document.createElement('div');
    bar.className = 'titlebar';
    bar.innerHTML = '<img src="assets/icon.png" alt=""><b>Skidrow Killer</b><span class="wc"><span>&#8212;</span><span>&#9633;</span><span>&#10005;</span></span>';

    var side = document.createElement('aside');
    side.className = 'side';
    side.innerHTML = '<div class="logo"><img src="assets/logo.png" alt=""></div><ul class="nav">' + items + '</ul>';

    var layer = function (name, onState) {
        return '<span class="ly" style="color:' + (onState ? 'var(--ok)' : 'var(--t3)') + '"><span class="dot" style="background:currentColor;margin-right:3px"></span>' + name + '</span>';
    };
    var status = document.createElement('div');
    status.className = 'status';
    status.innerHTML =
        '<span class="grp"><span class="dot" style="background:var(--ok)"></span><span class="ok">Online</span></span>' +
        '<span class="grp">' + (document.body.getAttribute('data-iocs') || '412.8K') + ' IOCs</span>' +
        '<span class="grp">Protection: ' + layer('RT', true) + layer('USB', true) + layer('RW', true) + layer('WEB', true) + layer('GAME', false) + '</span>' +
        '<span class="msg">' + (document.body.getAttribute('data-msg') || '') + '</span>' +
        '<span class="grp"><span class="dot" style="background:var(--ok);width:8px;height:8px"></span><span class="ok" style="font-weight:600">Protected</span></span>' +
        '<span class="grp t3">21:42:07</span>';

    document.body.prepend(status);
    document.body.prepend(side);
    document.body.prepend(bar);
})();
