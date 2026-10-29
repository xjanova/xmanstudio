// The app's frame around every PostXAgent mock-up: the title bar, the 10-item sidebar and the status bar
// of MyPostXAgent/src/MyPostXAgent.UI/MainWindow.xaml (labels from LocalizationStrings.Nav, Thai).
// <body data-on="content"> marks the open page. Counters in the status bar are made up.
(function () {
    var nav = [
        ['dashboard', 'แดชบอร์ด', '▦', '#00F5FF'],
        ['posts', 'โพสต์', '✎', '#3B82F6'],
        ['schedule', 'ตั้งเวลา', '◷', '#8B5CF6'],
        ['accounts', 'บัญชี Social', '☻', '#EC4899'],
        ['content', 'สร้างเนื้อหา AI', '✦', '#F97316', 'NEW'],
        ['video', 'วิดีโอ', '▶', '#FF00FF'],
        ['groups', 'ค้นหากลุ่ม', '⚇', '#FBBF24'],
        ['comments', 'ความคิดเห็น', '✉', '#10B981'],
        ['workflows', 'เวิร์กโฟลว์', '⌘', '#00F5FF'],
        ['settings', 'ตั้งค่า', '⚙', '#A1A1AA'],
    ];
    var on = document.body.getAttribute('data-on');
    var items = nav.map(function (n) {
        return '<li class="' + (n[0] === on ? 'on' : '') + '"><i style="color:' + n[3] + '">' + n[2] + '</i>' + n[1] +
            (n[4] ? '<em>' + n[4] + '</em>' : '') + '</li>';
    }).join('');

    var top = document.createElement('div');
    top.className = 'top';
    top.innerHTML = '<div class="brand"><i>🚀</i><span><b>MyPostXAgent</b><small>AI Social Automation</small></span></div>' +
        '<div class="search">⌕ ค้นหา...</div><div class="lang">🇹🇭 TH</div>' +
        '<div class="winbtn"><span>—</span><span>☐</span><span>✕</span></div>';

    var side = document.createElement('aside');
    side.className = 'side';
    side.innerHTML = '<div class="user"><span class="av"><span>☺</span></span><span><small>Welcome</small><b>Pro User</b></span><span class="on"></span></div>' +
        '<div class="div"></div><p class="menu">M E N U</p><ul class="nav">' + items + '</ul>';

    var status = document.createElement('div');
    status.className = 'status';
    status.innerHTML = '<span class="dot"></span><span class="sec" style="font-size:12px;margin-right:8px">ระบบพร้อมใช้งาน</span>' +
        '<span class="chip">✎ โพสต์วันนี้: <b style="color:#00F5FF">12</b></span>' +
        '<span class="chip">◷ คิว: <b style="color:#8B5CF6">7</b></span>' +
        '<span class="chip">☻ บัญชี: <b style="color:#EC4899">6</b></span>' +
        '<span class="chip"><span style="width:8px;height:8px;border-radius:4px;background:#10B981"></span> AI: <b style="color:#10B981">พร้อม</b></span>' +
        '<span class="ver"><b>MyPostXAgent</b> PRO</span>';

    document.body.prepend(status);
    document.body.prepend(side);
    document.body.prepend(top);
})();
