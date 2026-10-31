// The app's frame around every XcluadeAgent mock-up: MainLayout.razor's sidebar (the same ten links,
// same emoji) and header. <body data-on="history"> marks the open page.
(function () {
    var nav = [
        ['dashboard', '📊', 'Dashboard'],
        ['projects', '📁', 'Projects'],
        ['history', '📜', 'Sync History'],
        ['ai', '🤖', 'AI Assistant'],
        ['notifications', '🔔', 'Notifications'],
        ['audit', '📋', 'Audit Log'],
        ['-', '', 'Settings'],
        ['settings', '⚙️', 'Settings'],
        ['users', '👥', 'Users'],
        ['license', '📄', 'License'],
        ['update', '⬆️', 'System Update'],
    ];
    var on = document.body.getAttribute('data-on');
    var links = nav.map(function (n) {
        if (n[0] === '-') { return '<p class="sec">' + n[2] + '</p>'; }
        return '<a class="' + (n[0] === on ? 'on' : '') + '"><span>' + n[1] + '</span>' + n[2] + '</a>';
    }).join('');
    var side = document.createElement('aside');
    side.className = 'side';
    side.innerHTML = '<div class="logo"><span class="i">🔄</span><div><h1>XcluadeAgent</h1><p>by xman studio</p></div></div>' +
        '<nav>' + links + '</nav><div class="foot"><span>by xman studio</span><span>xman4289.com</span></div>';
    var hdr = document.createElement('header');
    hdr.className = 'hdr';
    hdr.innerHTML = '<span class="b">🌙</span><span class="b">🔔<i></i></span>' +
        '<span class="u"><span class="av">A</span>admin <small>▼</small></span>';
    var main = document.querySelector('.main');
    main.prepend(hdr);
    document.body.prepend(side);
})();
