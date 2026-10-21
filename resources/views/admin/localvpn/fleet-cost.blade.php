@extends($adminLayout ?? 'layouts.admin')

@section('title', 'LocalVPN - ค่าใช้จ่าย Fleet')
@section('page-title', 'LocalVPN - Relay Fleet: ค่าใช้จ่าย')

@section('content')
@include('admin.localvpn._tabs')
@include('admin.localvpn._fleet_tabs')

@php
    $budgetPct = $snapshot->monthlyBudgetThb > 0 ? $snapshot->projectedCostThb / $snapshot->monthlyBudgetThb * 100 : 0;
    $formatBytes = function ($bytes) {
        $bytes = (int) $bytes;
        if ($bytes >= 1_000_000_000) return number_format($bytes / 1_000_000_000, 2) . ' GB';
        if ($bytes >= 1_000_000) return number_format($bytes / 1_000_000, 1) . ' MB';
        return number_format($bytes / 1000, 0) . ' KB';
    };
@endphp

{{-- Totals --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="rounded-2xl bg-white p-5 shadow-lg border border-gray-100">
        <p class="text-xs text-gray-500">ค่าใช้จ่ายเดือนนี้ถึงตอนนี้</p>
        <p class="text-2xl font-bold text-gray-900">฿{{ number_format($snapshot->costSoFarThb, 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">ค่าเช่าเฉลี่ยตามวันที่ผ่านไป + ส่วนเกินที่ใช้จริง</p>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-lg border border-gray-100">
        <p class="text-xs text-gray-500">คาดการณ์ทั้งรอบบิล</p>
        <p class="text-2xl font-bold {{ $snapshot->overBudget ? 'text-red-600' : 'text-gray-900' }}">฿{{ number_format($snapshot->projectedCostThb, 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">ต่อเส้นตรงจากการใช้งานถึงวันนี้</p>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-lg border border-gray-100">
        <p class="text-xs text-gray-500">เทียบกับงบ ฿{{ number_format($snapshot->monthlyBudgetThb, 0) }}</p>
        <div class="flex items-center gap-3 mt-2">
            <div class="flex-1 bg-gray-200 rounded-full h-3">
                <div class="h-3 rounded-full {{ $budgetPct >= 100 ? 'bg-red-500' : ($budgetPct >= 80 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, max(0, $budgetPct)) }}%"></div>
            </div>
            <span class="text-sm font-semibold text-gray-700">{{ number_format($budgetPct, 0) }}%</span>
        </div>
        <p class="text-xs mt-2 {{ $snapshot->overBudget ? 'text-red-600 font-medium' : 'text-gray-400' }}">
            {{ $snapshot->overBudget ? 'เกินงบ — overage ถูกปิดทุกโหนด' : 'อยู่ในงบ' }}
        </p>
    </div>
</div>

{{-- Per node --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">รายโหนด</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">โหนด</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">รอบบิล</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">ใช้แล้ว (GB)</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">แพ็กเกจ (GB)</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">คาดการณ์ (GB)</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">ค่าเช่า/เดือน</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">ถึงตอนนี้</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">คาดการณ์</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($nodes as $node)
                    @php $b = $snapshot->forNode($node->id); @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <div class="font-medium text-gray-900">{{ $node->code }}</div>
                            <div class="text-xs text-gray-500">{{ $node->country }} · {{ $node->billing_mode }}{{ $node->allow_overage ? ' · overage' : '' }}</div>
                        </td>
                        <td class="py-3 px-4 text-xs text-gray-500 whitespace-nowrap">{{ $b?->periodStart->format('d/m/Y') }} – {{ $b?->periodEnd->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-right">{{ number_format($b?->usedGb ?? 0, 2) }}</td>
                        <td class="py-3 px-4 text-right text-gray-500">{{ number_format($node->included_gb) }}</td>
                        <td class="py-3 px-4 text-right {{ $b && $b->projectedGb > $node->included_gb ? 'text-red-600 font-semibold' : '' }}">{{ number_format($b?->projectedGb ?? 0, 1) }}</td>
                        <td class="py-3 px-4 text-right text-gray-500">฿{{ number_format((float) $node->monthly_cost_thb, 2) }}</td>
                        <td class="py-3 px-4 text-right">฿{{ number_format($b?->costSoFarThb ?? 0, 2) }}</td>
                        <td class="py-3 px-4 text-right font-medium">฿{{ number_format($b?->projectedCostThb ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-gray-500">ยังไม่มีโหนด</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Last 30 days --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">ปริมาณข้อมูลรายวัน ทุกโหนด (30 วัน, GB)</h3>
    <canvas id="fleetDailyChart" height="110"></canvas>
    <details class="mt-4">
        <summary class="text-sm text-emerald-600 cursor-pointer">ดูเป็นตาราง</summary>
        <div class="overflow-x-auto mt-3">
            <table class="w-full text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-2 px-3 text-gray-600 font-medium">วันที่</th>
                        <th class="text-right py-2 px-3 text-gray-600 font-medium">ขาออก</th>
                        <th class="text-right py-2 px-3 text-gray-600 font-medium">ขาเข้า</th>
                        <th class="text-right py-2 px-3 text-gray-600 font-medium">Relay</th>
                        <th class="text-right py-2 px-3 text-gray-600 font-medium">Exit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach(array_reverse($days) as $day)
                        <tr>
                            <td class="py-1.5 px-3 text-gray-600">{{ $day['date'] }}</td>
                            <td class="py-1.5 px-3 text-right">{{ number_format($day['out_gb'], 2) }}</td>
                            <td class="py-1.5 px-3 text-right">{{ number_format($day['in_gb'], 2) }}</td>
                            <td class="py-1.5 px-3 text-right">{{ number_format($day['relay_gb'], 2) }}</td>
                            <td class="py-1.5 px-3 text-right">{{ number_format($day['exit_gb'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</div>

{{-- Top users --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @foreach(['relay' => ['ผู้ใช้ relay สูงสุด', $topRelay], 'exit' => ['ผู้ใช้ exit (ลดปิง/VPN) สูงสุด', $topExit]] as $kind => [$title, $rows])
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                <p class="text-xs text-gray-500">ตั้งแต่ {{ \Illuminate\Support\Carbon::parse($monthStart)->format('d/m/Y') }} (เวลาไทย) · 20 อันดับ</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left py-2 px-4 text-gray-600 font-medium">#</th>
                            <th class="text-left py-2 px-4 text-gray-600 font-medium">อุปกรณ์</th>
                            <th class="text-left py-2 px-4 text-gray-600 font-medium">ไลเซนส์</th>
                            <th class="text-right py-2 px-4 text-gray-600 font-medium">ปริมาณ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $i => $row)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2 px-4 text-gray-400">{{ $i + 1 }}</td>
                                <td class="py-2 px-4 font-mono text-xs text-gray-700" title="{{ $row->machine_id }}">{{ \Illuminate\Support\Str::limit($row->machine_id, 18) }}</td>
                                <td class="py-2 px-4 text-xs text-gray-600">
                                    @if($row->license)
                                        {{ $row->license->license_type }}
                                        @if($row->license->user)
                                            · {{ $row->license->user->name }}
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-2 px-4 text-right font-medium">{{ $formatBytes($row->total_bytes) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-gray-500">ยังไม่มีข้อมูล</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function () {
        const days = @json($days);
        new Chart(document.getElementById('fleetDailyChart'), {
            type: 'bar',
            data: {
                labels: days.map(d => d.date.substring(5)),
                datasets: [
                    { label: 'ขาออก (GB)', data: days.map(d => d.out_gb), backgroundColor: 'rgba(16, 185, 129, 0.7)' },
                    { label: 'ขาเข้า (GB)', data: days.map(d => d.in_gb), backgroundColor: 'rgba(59, 130, 246, 0.5)' },
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    })();
</script>
@endpush
