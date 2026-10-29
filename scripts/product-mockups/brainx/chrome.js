// The BrainX window around every mock-up: the title strip and the status line of the real capture
// (sites/product.xman4289.com/assets/img/hero-hud.jpg). The brain name and address are made up.
(function () {
    var bar = document.createElement('div');
    bar.className = 'titlebar';
    bar.innerHTML = '<span class="burger"></span><img src="assets/logo.png" alt=""><b>BrainX</b>' +
        '<span class="sub">— Neural Knowledge Engine</span><span class="who">(Demo Brain · 0xBRAIN-0000…)</span>' +
        '<span class="ctl"><span>—</span><span>▢</span><span>✕</span></span>';
    var status = document.createElement('div');
    status.className = 'status';
    status.innerHTML = '<span class="ok">' + (document.body.getAttribute('data-status') || 'ready') + '</span>' +
        '<span class="r"><span class="ok">CLI ✓</span><span class="ok">DT ✓</span><span class="dot grey">idle</span></span>';
    document.body.prepend(bar);
    document.body.append(status);
})();
