// The window around every SpiderX mock-up: the Windows title bar and the app's six Shell tabs
// (AppShell.xaml: Home, Chats, Nearby, LAN, Files, Settings). <body data-on="Nearby"> marks the open tab.
(function () {
    var tabs = ['Home', 'Chats', 'Nearby', 'LAN', 'Files', 'Settings'];
    var on = document.body.getAttribute('data-on');
    var bar = document.createElement('div');
    bar.className = 'titlebar';
    bar.innerHTML = '<span>🕷️</span><span>SpiderX</span><span class="caps"><i>—</i><i>☐</i><i>✕</i></span>';
    var nav = document.createElement('div');
    nav.className = 'tabs';
    nav.innerHTML = tabs.map(function (t) { return '<span class="' + (t === on ? 'on' : '') + '">' + t + '</span>'; }).join('');
    document.body.prepend(nav);
    document.body.prepend(bar);
})();
