// The client's frame around every GPUxMINE mock-up: title strip, the 10-item CONSOLE sidebar with the
// DETECTED GPU and POOL LINK panels, and the status bar (GpuXmine src/GpuxMine.App/MainWindow.xaml).
// <body data-on="power"> marks the open screen. Worker id, GPU and numbers are made up.
(function () {
    var nav = [
        ['dashboard', 'Dashboard', '◉'],
        ['power', 'Power & Limits', 'ϟ'],
        ['models', 'Models & Jobs', '▦'],
        ['queue', 'Live Queue', '≡'],
        ['history', 'Job History', '≡'],
        ['wallet', 'Wallet', '฿'],
        ['benchmark', 'Benchmark', '◔'],
        ['log', 'Activity Log', '›'],
        ['referrals', 'Referrals', '⚇'],
        ['settings', 'Settings', '⚙'],
    ];
    var on = document.body.getAttribute('data-on');
    var main = document.querySelector('main');
    var items = nav.map(function (n) {
        return '<li class="' + (n[0] === on ? 'on' : '') + '"><b>' + n[2] + '</b>' + n[1].replace('&', '&amp;') + '</li>';
    }).join('');
    var win = document.createElement('div');
    win.className = 'win';
    win.innerHTML =
        '<div class="title"><img src="assets/logo.png" alt=""><span class="wid">Worker Node — gxm-7f3a2c91d0e4 · v0.1.18</span>' +
        '<span class="btns"><i></i><i></i><i class="x">✕</i></span></div>' +
        '<div class="body"><aside class="side">' +
            '<div class="lbl">Console</div><ul class="nav">' + items + '</ul>' +
            '<div class="chrome" style="padding:10px;margin-top:10px">' +
                '<div class="lbl" style="font-size:10px;margin-bottom:6px">Detected GPU</div>' +
                '<div style="font-weight:bold;font-size:12.5px">NVIDIA GeForce RTX 3060</div>' +
                '<div class="mono" style="font-size:11px;color:#4e565f;margin-top:2px">12 GB · 3584 cores</div>' +
                '<div class="mono" style="font-size:11px;color:#4e565f">driver 566.36</div>' +
                '<div class="bar" style="margin:8px 0 4px"><i style="width:58%"></i></div>' +
                '<div class="mono" style="font-size:10.5px;color:#4e565f">VRAM 7.0 / 12.0 GB</div>' +
            '</div>' +
            '<div class="lcd" style="margin-top:auto;padding:9px 10px">' +
                '<div class="green mono" style="font-size:12px"><span class="led"></span>POOL LINK · 38 ms</div>' +
                '<div style="height:12px;margin:8px 0 4px;background:#0a0e12;border:1px solid #3a444f;border-radius:3px"></div>' +
                '<div class="l">tasks in ▸ results out</div>' +
            '</div>' +
        '</aside></div>' +
        '<div class="foot"><span>SHARING · ACTIVE</span><span>draw 142 W</span><span>ระดับฟรี</span><span class="r">node gxm-7f3a2c91d0e4 · relay relay.example.com</span></div>';
    win.querySelector('.body').appendChild(main);
    document.body.appendChild(win);
})();
