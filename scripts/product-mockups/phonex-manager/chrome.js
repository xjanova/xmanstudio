// The PhoneX Manager window around every mock-up (Views/MainWindow.xaml): title bar, the connection bar
// with the device selector, the tabs (Engineer Tools left out on purpose: not shown on the sales page), then the progress row, the Log panel and the status bar.
// <body data-on="Device" data-dev="Samsung Galaxy A54 (…)" data-brand="Samsung" data-model="Galaxy A54" data-op="Ready" data-prog="0" data-progtext="">
// and an optional <script type="text/plain" id="log"> with one log line per line.
(function () {
    var tabs = ['⚙ Setup', 'Device', 'Backup', 'Hex Editor', 'Downloads', 'ROM Database', 'ROM Search', 'ROM Sources', 'Guides', 'Tools', 'Settings', 'Flash'];
    var b = document.body, on = b.getAttribute('data-on'), dev = b.getAttribute('data-dev') || '';
    var model = b.getAttribute('data-model') || 'No Device';
    // MainViewModel: ConnectionStatus = "Connected: {Brand} {Model}" or "Disconnected"
    var status = dev ? 'Connected: ' + (b.getAttribute('data-brand') || '') + ' ' + model : 'Disconnected';
    var prog = +(b.getAttribute('data-prog') || 0), progText = b.getAttribute('data-progtext') || '';
    var logEl = document.getElementById('log');
    var lines = logEl ? logEl.textContent.trim().split('\n') : [];
    var content = document.createElement('div');
    content.className = 'content';
    while (b.firstChild) {
        if (b.firstChild === logEl) { b.removeChild(logEl); continue; }
        content.appendChild(b.firstChild);
    }
    var ff = '';
    [[90, 210], [260, 640], [520, 160], [760, 520], [980, 260], [1210, 610], [1330, 190], [640, 700], [1120, 420], [380, 430]].forEach(function (p, i) {
        ff += '<span class="ff" style="left:' + p[0] + 'px;top:' + p[1] + 'px;opacity:' + (0.35 + (i % 4) * 0.15) + '"></span>';
    });
    var win = document.createElement('div');
    win.className = 'win';
    win.innerHTML = ff + '<div class="layer">' +
        '<div class="tb"><img src="assets/icon.png" alt=""><span class="n">PhoneX Manager</span><span class="v"></span>' +
        '<span class="mid">Advanced Repair Suite</span><span class="wb">─</span><span class="wb">□</span><span class="wb x">X</span></div>' +
        '<div class="hb"><span class="dot' + (dev ? '' : ' off') + '"></span><span class="st">' + status + '</span>' +
        '<span class="sel">' + dev + '</span><span class="dk">Refresh</span></div>' +
        '<div class="tabs">' + tabs.map(function (t) { return '<span class="' + (t === on ? 'on' : '') + '">' + t + '</span>'; }).join('') + '</div>' +
        '<div id="slot"></div>' +
        '<div class="prog"><div class="bar"><i style="width:' + prog + '%"></i></div><p>' + progText + '</p></div>' +
        '<div class="log"><b>Log</b>' + lines.slice(-4).map(function (l) { return '<p>' + l.replace(/</g, '&lt;') + '</p>'; }).join('') + '</div>' +
        '<div class="sb"><span class="dot' + (dev ? '' : ' off') + '"></span>' + status + '<span class="sep"></span>' + model +
        '<span class="op">' + (b.getAttribute('data-op') || 'Ready') + '</span>' +
        '<span class="gr" style="margin-right:2px">↑</span>' + (b.getAttribute('data-up') || '0 KB') + '<span class="cy" style="margin:0 2px 0 8px">↓</span>' + (b.getAttribute('data-down') || '0 KB') +
        '<span class="sep"></span><span class="dbg">Debug</span></div></div>';
    b.appendChild(win);
    var slot = win.querySelector('#slot');
    slot.parentNode.replaceChild(content, slot);
})();
