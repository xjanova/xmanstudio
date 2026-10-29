// The app's frame around every Anti X mock-up: title bar + the 10-item sidebar of 1.14 (MainWindow.xaml).
// <body data-on="reports"> marks the open page.
(function () {
    var nav = [
        ['dashboard', 'ภาพรวม', 'ic-dashboard'],
        ['live', 'เชื่อมต่อสด', 'ic-live', '18'],
        ['blocks', 'แบล็คลิสต์', 'ic-blocks', '15', 'red'],
        ['suspects', 'ผู้ต้องสงสัย', '🕵️', '3', 'red'],
        ['reports', 'รายงาน / ผลตรวจ', '📊'],
        ['events', 'บันทึกเหตุการณ์', 'ic-events'],
        ['whitelist', 'ไวท์ลิสต์', 'ic-whitelist'],
        ['machines', 'เครื่องที่ดูแล', 'ic-machines'],
        ['mobile', 'อุปกรณ์มือถือ', '📱'],
        ['settings', 'ตั้งค่า', 'ic-settings'],
    ];
    var on = document.body.getAttribute('data-on');
    var items = nav.map(function (n) {
        var icon = n[2].indexOf('ic-') === 0 ? '<i style="background-image:url(assets/' + n[2] + '.png)"></i>' : '<i class="emo">' + n[2] + '</i>';
        var badge = n[3] ? '<b class="' + (n[4] || '') + '">' + n[3] + '</b>' : '';
        return '<li class="' + (n[0] === on ? 'on' : '') + '">' + icon + n[1] + badge + '</li>';
    }).join('');
    var side = document.createElement('aside');
    side.className = 'side';
    side.innerHTML = '<span class="logo"></span><hr><ul class="nav">' + items + '</ul>' +
        '<div class="ver"><strong>Anti X</strong>GeoIP: db-ip.com</div>';
    var bar = document.createElement('div');
    bar.className = 'titlebar';
    document.body.prepend(side);
    document.body.prepend(bar);
})();
