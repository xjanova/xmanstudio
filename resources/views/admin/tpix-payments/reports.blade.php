@extends($adminLayout ?? 'layouts.admin')

@section('title', 'รายงาน TPIX')
@section('page-title', 'รายงาน TPIX')

@php
    $k = $report['kpis'];
    $thb = fn ($v) => '฿' . number_format((float) $v, 2);
    $tpix = fn ($v) => number_format((float) $v, (float) $v >= 1000 ? 2 : 4) . ' TPIX';
    $statusLabels = [
        'pending' => 'รอชำระ', 'confirming' => 'รอยืนยันบนเชน', 'paid' => 'ชำระแล้ว', 'credited_wallet' => 'เข้ากระเป๋าเงิน',
        'late' => 'โอนหลังหมดเวลา', 'held' => 'รอแอดมินอนุมัติ', 'expired' => 'หมดเวลา', 'cancelled' => 'ยกเลิก', 'failed' => 'ไม่สำเร็จ',
    ];
    $statusColors = [
        'pending' => '#f59e0b', 'confirming' => '#3b82f6', 'paid' => '#10b981', 'credited_wallet' => '#14b8a6',
        'late' => '#f97316', 'held' => '#a855f7', 'expired' => '#9ca3af', 'cancelled' => '#6b7280', 'failed' => '#ef4444',
    ];
    $statusChart = [
        'labels' => array_map(fn ($s) => $statusLabels[$s] ?? $s, array_keys($report['statuses'])),
        'values' => array_values($report['statuses']),
        'colors' => array_map(fn ($s) => $statusColors[$s] ?? '#64748b', array_keys($report['statuses'])),
    ];
    $hasPrice = count($report['price']['labels']) > 0;
@endphp

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 p-6 sm:p-8 shadow-xl">
        <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-white">รายงาน TPIX</h1>
                <p class="text-amber-50 mt-1 text-sm sm:text-base">ยอดรับชำระด้วยเหรียญ TPIX · ราคาและสภาพคล่องพูล · อัปเดตทุก 1 นาที (ข้อมูล ณ {{ $report['generated_at'] }} น.)</p>
                <a href="{{ route('admin.tpix-payments.index') }}" class="inline-flex items-center gap-1 mt-3 text-sm font-semibold text-white/90 hover:text-white underline-offset-4 hover:underline">
                    ← กลับไปหน้าชำระด้วย TPIX
                </a>
            </div>

            {{-- CSV export --}}
            <form action="{{ route('admin.tpix-payments.reports.csv') }}" method="GET" class="flex flex-col sm:flex-row sm:items-end gap-2 bg-white/15 backdrop-blur-sm rounded-xl p-3">
                <label class="flex flex-col text-xs text-white/90">
                    ตั้งแต่
                    <input type="date" name="from" value="{{ old('from', $exportFrom) }}" required class="mt-1 px-3 py-2 rounded-lg border-0 bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                </label>
                <label class="flex flex-col text-xs text-white/90">
                    ถึง
                    <input type="date" name="to" value="{{ old('to', $exportTo) }}" required class="mt-1 px-3 py-2 rounded-lg border-0 bg-white/90 dark:bg-gray-800 text-gray-900 dark:text-white text-sm">
                </label>
                <button type="submit" class="px-4 py-2 rounded-lg bg-white text-orange-600 font-semibold text-sm hover:bg-orange-50 transition">
                    ส่งออก CSV
                </button>
            </form>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    {{-- Received --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach([['วันนี้', $k['today']], ['7 วันล่าสุด', $k['d7']], ['30 วันล่าสุด', $k['d30']]] as [$label, $period])
            <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">รับชำระ{{ $label }}</p>
                <p class="mt-1 text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ $thb($period['thb']) }}</p>
                <p class="mt-1 text-sm text-amber-600 dark:text-amber-400 font-medium">{{ $tpix($period['tpix']) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ number_format($period['count']) }} รายการ</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">ยอดเฉลี่ยต่อรายการ (30 วัน)</p>
            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $thb($k['average_thb']) }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">อัตราชำระสำเร็จ (30 วัน)</p>
            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $k['conversion_pct'] === null ? '—' : $k['conversion_pct'] . '%' }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($k['settled_of_quotes_30d']) }} / {{ number_format($k['quotes_30d']) }} ใบเสนอราคา</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400">ใบเสนอราคาที่ยังเปิดอยู่</p>
            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($k['open_quotes']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">รอยืนยันบนเชน {{ number_format($k['confirming']) }}</p>
        </div>
        <a href="{{ route('admin.tpix-payments.index', ['status' => 'held']) }}" class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg border {{ $k['held'] + $k['late'] > 0 ? 'border-purple-300 dark:border-purple-700' : 'border-gray-100 dark:border-gray-700' }} p-4 hover:shadow-xl transition">
            <p class="text-xs text-gray-500 dark:text-gray-400">รอแอดมินอนุมัติ</p>
            <p class="mt-1 text-xl font-bold {{ $k['held'] > 0 ? 'text-purple-600 dark:text-purple-400' : 'text-gray-900 dark:text-white' }}">{{ number_format($k['held']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $thb($k['held_thb']) }} · โอนหลังหมดเวลา {{ number_format($k['late']) }}</p>
        </a>
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 p-4 col-span-2 lg:col-span-1">
            <p class="text-xs text-gray-500 dark:text-gray-400">เข้ากระเป๋าเงินแทน (30 วัน)</p>
            <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $thb($k['credited_thb_30d']) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($k['credited_count_30d']) }} รายการ</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 p-5">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">ยอดรับรายวัน 30 วัน (บาท) แยกตามประเภท</h2>
            <div class="relative h-72"><canvas id="tpixDailyChart" aria-label="ยอดรับรายวัน"></canvas></div>
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 p-5">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">สถานะใบเสนอราคา (30 วัน)</h2>
            @if(count($report['statuses']) > 0)
                <div class="relative h-72"><canvas id="tpixStatusChart" aria-label="สถานะใบเสนอราคา"></canvas></div>
            @else
                <p class="h-72 flex items-center justify-center text-sm text-gray-500 dark:text-gray-400">ยังไม่มีใบเสนอราคาใน 30 วันที่ผ่านมา</p>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 p-5">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">ราคา TPIX 7 วัน (บาท / USD) · เส้นค่าเฉลี่ย 30 นาที</h2>
            @if($hasPrice)
                <div class="relative h-72"><canvas id="tpixPriceChart" aria-label="ราคา TPIX"></canvas></div>
            @else
                <p class="h-72 flex items-center justify-center text-sm text-gray-500 dark:text-gray-400">ยังไม่มีข้อมูลราคาใน 7 วันที่ผ่านมา</p>
            @endif
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 p-5">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">สภาพคล่องพูล WTPIX/USDT 7 วัน</h2>
            @if($hasPrice)
                <div class="relative h-72"><canvas id="tpixPoolChart" aria-label="สภาพคล่องพูล"></canvas></div>
            @else
                <p class="h-72 flex items-center justify-center text-sm text-gray-500 dark:text-gray-400">ยังไม่มีข้อมูลพูลใน 7 วันที่ผ่านมา</p>
            @endif
        </div>
    </div>

    {{-- Top payers --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white px-5 pt-5 pb-3">ผู้ชำระสูงสุด (30 วัน)</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-300 text-xs uppercase">
                    <tr>
                        <th class="px-5 py-3 text-left">#</th>
                        <th class="px-5 py-3 text-left">ลูกค้า</th>
                        <th class="px-5 py-3 text-right">รายการ</th>
                        <th class="px-5 py-3 text-right">ยอด (บาท)</th>
                        <th class="px-5 py-3 text-right">TPIX</th>
                        <th class="px-5 py-3 text-right whitespace-nowrap">ชำระล่าสุด</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($report['top_payers'] as $i => $payer)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $i + 1 }}</td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $payer['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 break-all">{{ $payer['email'] }}</p>
                            </td>
                            <td class="px-5 py-3 text-right text-gray-700 dark:text-gray-300">{{ number_format($payer['count']) }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-white whitespace-nowrap">{{ $thb($payer['thb']) }}</td>
                            <td class="px-5 py-3 text-right text-amber-600 dark:text-amber-400 whitespace-nowrap">{{ $tpix($payer['tpix']) }}</td>
                            <td class="px-5 py-3 text-right text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $payer['last_paid'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-500 dark:text-gray-400">ยังไม่มีการชำระด้วย TPIX ใน 30 วันที่ผ่านมา</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;

    const daily = @json($report['daily']);
    const price = @json($report['price']);
    const statuses = @json($statusChart);
    const charts = [];

    function palette() {
        const dark = document.documentElement.classList.contains('dark');
        return { text: dark ? '#d1d5db' : '#4b5563', grid: dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)' };
    }

    function axes(extra) {
        const c = palette();
        const base = {
            x: { ticks: { color: c.text, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }, grid: { color: c.grid } },
            y: { ticks: { color: c.text }, grid: { color: c.grid }, beginAtZero: true },
        };
        return Object.assign(base, extra || {});
    }

    function common() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { labels: { color: palette().text, boxWidth: 12 } } },
        };
    }

    function build() {
        charts.splice(0).forEach(ch => ch.destroy());
        const c = palette();

        const dailyEl = document.getElementById('tpixDailyChart');
        if (dailyEl) {
            charts.push(new Chart(dailyEl, {
                type: 'bar',
                data: {
                    labels: daily.labels,
                    datasets: [
                        { label: 'คำสั่งซื้อ', data: daily.series.order || [], backgroundColor: '#f59e0b', stack: 'thb', borderRadius: 4 },
                        { label: 'เติมเงิน Wallet', data: daily.series.wallet_topup || [], backgroundColor: '#6366f1', stack: 'thb', borderRadius: 4 },
                    ],
                },
                options: Object.assign(common(), { scales: axes({ x: { stacked: true, ticks: { color: c.text, maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { color: c.text }, grid: { color: c.grid } } }) }),
            }));
        }

        const statusEl = document.getElementById('tpixStatusChart');
        if (statusEl) {
            charts.push(new Chart(statusEl, {
                type: 'doughnut',
                data: { labels: statuses.labels, datasets: [{ data: statuses.values, backgroundColor: statuses.colors, borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { color: c.text, boxWidth: 12 } } } },
            }));
        }

        const priceEl = document.getElementById('tpixPriceChart');
        if (priceEl) {
            charts.push(new Chart(priceEl, {
                type: 'line',
                data: {
                    labels: price.labels,
                    datasets: [
                        { label: 'บาท/TPIX', data: price.thb, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.12)', fill: true, pointRadius: 0, borderWidth: 1.5, tension: 0.2, spanGaps: true, yAxisID: 'y' },
                        { label: 'เฉลี่ย 30 นาที (บาท)', data: price.thb_ma30, borderColor: '#ef4444', borderDash: [5, 4], pointRadius: 0, borderWidth: 1.5, tension: 0.2, spanGaps: true, yAxisID: 'y' },
                        { label: 'USD/TPIX', data: price.usd, borderColor: '#3b82f6', pointRadius: 0, borderWidth: 1, tension: 0.2, spanGaps: true, yAxisID: 'y1' },
                    ],
                },
                options: Object.assign(common(), { scales: axes({
                    y: { position: 'left', beginAtZero: false, ticks: { color: c.text }, grid: { color: c.grid }, title: { display: true, text: 'THB', color: c.text } },
                    y1: { position: 'right', beginAtZero: false, ticks: { color: c.text }, grid: { drawOnChartArea: false }, title: { display: true, text: 'USD', color: c.text } },
                }) }),
            }));
        }

        const poolEl = document.getElementById('tpixPoolChart');
        if (poolEl) {
            charts.push(new Chart(poolEl, {
                type: 'line',
                data: {
                    labels: price.labels,
                    datasets: [
                        { label: 'USDT ในพูล', data: price.reserve_usdt, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.12)', fill: true, pointRadius: 0, borderWidth: 1.5, tension: 0.2, spanGaps: true, yAxisID: 'y' },
                        { label: 'TPIX ในพูล', data: price.reserve_tpix, borderColor: '#f59e0b', pointRadius: 0, borderWidth: 1.5, tension: 0.2, spanGaps: true, yAxisID: 'y1' },
                    ],
                },
                options: Object.assign(common(), { scales: axes({
                    y: { position: 'left', beginAtZero: false, ticks: { color: c.text }, grid: { color: c.grid }, title: { display: true, text: 'USDT', color: c.text } },
                    y1: { position: 'right', beginAtZero: false, ticks: { color: c.text }, grid: { drawOnChartArea: false }, title: { display: true, text: 'TPIX', color: c.text } },
                }) }),
            }));
        }
    }

    build();

    // Redraw with the right text/grid colours when the admin flips dark mode (only then)
    let wasDark = document.documentElement.classList.contains('dark');
    new MutationObserver(function () {
        const dark = document.documentElement.classList.contains('dark');
        if (dark !== wasDark) { wasDark = dark; build(); }
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
})();
</script>
@endpush
