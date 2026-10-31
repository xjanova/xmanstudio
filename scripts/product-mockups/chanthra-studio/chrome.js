// The app's frame around every Chanthra Studio mock-up: the title bar (Controls/TitleBar.xaml), the 14-item icon rail
// in the order of Controls/Sidebar.xaml (icon paths from Themes/Icons.xaml) and the status bar (StatusBarViewModel labels).
// <body data-on="generate"> marks the open view.
(function () {
    var ico = {
        generate: 'M12 3 L13.8 9.5 L20.5 11.3 L13.8 13.1 L12 19.8 L10.2 13.1 L3.5 11.3 L10.2 9.5 Z M19 3 V7 M17 5 H21',
        seedance: 'M3 10 H21 V20 H3 Z M3 10 L4 5.6 L20 4 L21 8.4 M8 9.6 L9.3 5.1 M13 9.1 L14.3 4.6 M18 8.6 L19 4.2',
        storyboard: 'M4 4 H20 V20 H4 Z M4 9 H20 M4 15 H20 M8 4 V20 M16 4 V20',
        editor: 'M6 3 A3 3 0 1 0 6 9 A3 3 0 1 0 6 3 M6 15 A3 3 0 1 0 6 21 A3 3 0 1 0 6 15 M8.12 8.12 L20 20 M14.83 14.83 L20 9.17 M8.12 15.88 L11 13',
        voice: 'M12 2 A3 3 0 0 0 9 5 V11 A3 3 0 0 0 15 11 V5 A3 3 0 0 0 12 2 M5 10 V11 A7 7 0 0 0 19 11 V10 M12 18 V22 M8 22 H16',
        nodeflow: 'M5 5 A2 2 0 1 0 5 9 A2 2 0 1 0 5 5 M19 5 A2 2 0 1 0 19 9 A2 2 0 1 0 19 5 M19 15 A2 2 0 1 0 19 19 A2 2 0 1 0 19 15 M5 15 A2 2 0 1 0 5 19 A2 2 0 1 0 5 15 M7 7 H17 M7 17 H17 M5 9 V15 M19 9 V15',
        web: 'M12 3 A9 9 0 1 0 12 21 A9 9 0 1 0 12 3 M3.5 12 H20.5 M12 3 C7.6 7 7.6 17 12 21 C16.4 17 16.4 7 12 3',
        library: 'M3 5 H8 V19 H3 Z M10 5 H15 V19 H10 Z M17.5 5 L21 6 L18 19 L14.5 18 Z',
        models: 'M9 4 A4 4 0 0 0 5 8 A3 3 0 0 0 4 11 A3 3 0 0 0 5 14 A3 3 0 0 0 4 17 A3 3 0 0 0 8 20 A3 3 0 0 0 12 22 V4 A3 3 0 0 0 9 4 M15 4 A4 4 0 0 1 19 8 A3 3 0 0 1 20 11 A3 3 0 0 1 19 14 A3 3 0 0 1 20 17 A3 3 0 0 1 16 20 A3 3 0 0 1 12 22',
        queue: 'M3 6 H6 M3 12 H6 M3 18 H6 M9 6 H21 M9 12 H21 M9 18 H21',
        schedule: 'M12 3 A9 9 0 1 0 12 21 A9 9 0 1 0 12 3 M12 7 V12 L16 14',
        gpu: 'M7 7 H17 V17 H7 Z M4 9 H7 M4 12 H7 M4 15 H7 M17 9 H20 M17 12 H20 M17 15 H20 M9 4 V7 M12 4 V7 M15 4 V7 M9 17 V20 M12 17 V20 M15 17 V20',
        usage: 'M12 3 A9 9 0 1 0 12 21 A9 9 0 1 0 12 3 M16 8 H10 A2 2 0 1 0 10 12 H14 A2 2 0 1 1 14 16 H8 M12 6 V18',
        settings: 'M12 9 A3 3 0 1 0 12 15 A3 3 0 1 0 12 9 M12 2 V5 M12 19 V22 M4.9 4.9 L7 7 M17 17 L19.1 19.1 M2 12 H5 M19 12 H22 M4.9 19.1 L7 17 M17 7 L19.1 4.9',
    };
    var order = ['generate', 'seedance', 'storyboard', 'editor', 'voice', 'nodeflow', 'web', 'library', 'models', 'queue', 'schedule', 'gpu', 'usage'];
    var on = document.body.getAttribute('data-on');
    var svg = function (d) { return '<svg viewBox="0 0 24 24"><path d="' + d + '"/></svg>'; };

    var bar = document.createElement('header');
    bar.className = 'titlebar';
    bar.innerHTML =
        '<div class="brand"><svg width="22" height="22" viewBox="0 0 32 32"><defs><radialGradient id="mg" cx="35%" cy="35%"><stop offset="0" stop-color="#f0cb88"/><stop offset="1" stop-color="#8b6938"/></radialGradient>' +
        '<mask id="cm"><rect width="32" height="32" fill="#fff"/><circle cx="20" cy="13" r="8.5" fill="#000"/></mask></defs>' +
        '<circle cx="16" cy="16" r="14.5" fill="none" stroke="#d4a76a" stroke-width=".8" opacity=".7"/><circle cx="15" cy="16" r="10" fill="url(#mg)" mask="url(#cm)"/></svg>' +
        '<span>Chanthra <em>Studio</em></span></div><div></div>' +
        '<div class="search"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="7"/><path d="M20 20 L16 16"/></svg>Search prompts, scenes, models…<kbd>Ctrl K</kbd></div>' +
        '<div class="wc"><span>&#8212;</span><span>&#9633;</span><span>&#10005;</span></div>';

    var rail = document.createElement('nav');
    rail.className = 'rail';
    rail.innerHTML = order.map(function (k) { return '<i class="' + (k === on ? 'on' : '') + '">' + svg(ico[k]) + '</i>'; }).join('') +
        '<div class="bottom"><i class="' + (on === 'settings' ? 'on' : '') + '">' + svg(ico.settings) + '</i><div class="avatar">J</div></div>';

    var status = document.createElement('footer');
    status.className = 'statusbar';
    status.innerHTML = document.body.getAttribute('data-status') ||
        '<span><span class="dot"></span>ComfyUI · พร้อม</span><span>RTX 4090<span class="bar"><b style="width:73%"></b></span> 73%</span><span>VRAM 17.8 / 24 GB</span><span>64°C</span>' +
        '<span>Queue · 2 running</span><span class="sp"></span><span class="g">YEARLY ☾</span><span>฿1,240 / ฿3,000 · 41%</span><span>v 0.11.0</span><span>Saved · 4s ago</span>';

    document.body.prepend(status);
    document.body.prepend(rail);
    document.body.prepend(bar);
})();
