// The app's frame around every AutoTradeX mock-up: the 60px title bar with the tab row of v0.3.0
// (MainWindow.xaml) and the 40px status bar. <body data-on="Scanner"> marks the open tab.
// The mode pill is the app's own unlicensed state ("DEMO MODE • $10,000"): every mock-up shows demo money.
(function () {
    var tabs = ['Dashboard', 'Trading', 'AI Trading', 'Scanner', 'History', 'Strategy', 'Projects', 'Analytics', 'Settings'];
    var on = document.body.getAttribute('data-on');
    var bar = document.createElement('div');
    bar.className = 'titlebar';
    bar.innerHTML =
        '<div class="brand"><img src="assets/logo.png" alt=""><div><strong>AutoTrade-X</strong><small>by Xman Studio • DEMO VERSION</small></div></div>' +
        '<div class="tabs">' + tabs.map(function (t) {
            return '<span class="' + (t === on ? 'on' : '') + '">' + t + (t === 'AI Trading' ? '<i>NEW</i>' : '') + '</span>';
        }).join('') + '</div>' +
        '<div class="right"><span class="activate">🔑 Activate</span><span class="mode">DEMO MODE • $10,000</span></div>' +
        '<div class="winbtns"><span>─</span><span>□</span><span>✕</span></div>';
    var status = document.createElement('div');
    status.className = 'statusbar';
    status.innerHTML =
        '<span><span class="dot"></span>Connection<b>3 exchanges</b></span>' +
        '<span>Bot<b class="grn">' + (document.body.getAttribute('data-bot') || 'RUNNING') + '</b></span>' +
        '<span>P&amp;L<b class="grn">+$184.62</b></span>' +
        '<span>Trades<b>47</b></span>' +
        '<span>RAM<b>212 MB</b></span>' +
        '<span class="end"><span class="xs">Xman Studio</span><span class="mono" style="color:rgba(255,255,255,.55);font-size:11px">14:32:08</span> UTC</span>';
    document.body.prepend(bar);
    document.body.append(status);
})();
