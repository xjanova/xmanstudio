@extends($adminLayout ?? 'layouts.admin')

@section('title', 'LocalVPN - Relay Fleet')
@section('page-title', 'LocalVPN - Relay Fleet: โหนด')

@section('content')
@include('admin.localvpn._tabs')
@include('admin.localvpn._fleet_tabs')

@php
    $statusBadge = [
        'active' => ['ใช้งาน', 'bg-green-100 text-green-800'],
        'draining' => ['ระบายผู้ใช้', 'bg-amber-100 text-amber-800'],
        'disabled' => ['ปิดใช้งาน', 'bg-gray-200 text-gray-700'],
    ];
    $roleBadge = [
        'relay' => 'bg-sky-100 text-sky-800',
        'game' => 'bg-violet-100 text-violet-800',
        'vpn' => 'bg-indigo-100 text-indigo-800',
    ];
@endphp

{{-- Summary --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="rounded-2xl bg-white p-5 shadow-lg border border-gray-100">
        <p class="text-xs text-gray-500">โหนดทั้งหมด</p>
        <p class="text-2xl font-bold text-gray-900">{{ $nodes->count() }}</p>
        <p class="text-xs text-gray-400 mt-1">ออนไลน์ {{ $nodes->filter(fn ($n) => $n->isFresh())->count() }}</p>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-lg border border-gray-100">
        <p class="text-xs text-gray-500">ค่าใช้จ่ายเดือนนี้ถึงตอนนี้</p>
        <p class="text-2xl font-bold text-gray-900">฿{{ number_format($snapshot->costSoFarThb, 0) }}</p>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-lg border border-gray-100">
        <p class="text-xs text-gray-500">คาดการณ์ทั้งเดือน</p>
        <p class="text-2xl font-bold {{ $snapshot->overBudget ? 'text-red-600' : 'text-gray-900' }}">฿{{ number_format($snapshot->projectedCostThb, 0) }}</p>
        <p class="text-xs text-gray-400 mt-1">งบ ฿{{ number_format($snapshot->monthlyBudgetThb, 0) }}</p>
    </div>
    <div class="rounded-2xl bg-white p-5 shadow-lg border border-gray-100 flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500">Overage</p>
            @if($snapshot->overBudget)
                <p class="text-sm font-semibold text-red-600 mt-1">ปิดทุกโหนด (เกินงบ)</p>
            @else
                <p class="text-sm font-semibold text-emerald-600 mt-1">ตามการตั้งค่าโหนด</p>
            @endif
        </div>
        <a href="{{ route('admin.localvpn.fleet.nodes.create') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors shadow-sm">+ เพิ่มโหนด</a>
    </div>
</div>

{{-- Nodes table --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">โหนด ({{ $nodes->count() }})</h3>
        <p class="text-xs text-gray-500 mt-1">ปริมาณข้อมูลนับตามรอบบิลของแต่ละโหนด (GB = 10⁹ ไบต์) · ไม่เห็นการซิงก์เกิน 60 วินาที = ไม่แจกตั๋ว relay</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">โหนด</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ประเทศ</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">บทบาท</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">สถานะ</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">ผู้ใช้</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">GB เดือนนี้ / แพ็กเกจ</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">ถึงตอนนี้</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">คาดการณ์</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">เห็นล่าสุด</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($nodes as $node)
                    @php
                        $b = $snapshot->forNode($node->id);
                        $pct = $b && is_finite($b->usedPct) ? $b->usedPct : ($b ? 100 : 0);
                        $fresh = $node->isFresh();
                        [$statusLabel, $statusClass] = $statusBadge[$node->status] ?? [$node->status, 'bg-gray-100 text-gray-700'];
                        $server = $node->wireguardServer;
                    @endphp
                    <tr class="hover:bg-gray-50 {{ $fresh ? '' : 'bg-red-50/40' }}">
                        <td class="py-3 px-4">
                            <div class="font-medium text-gray-900">{{ $node->code }}</div>
                            <div class="text-xs text-gray-500">{{ $node->name }}{{ $node->provider ? ' · ' . $node->provider : '' }}</div>
                            <div class="text-xs text-gray-400 font-mono">#{{ $node->id }} · {{ $node->host }}:{{ $node->relay_port }}</div>
                        </td>
                        <td class="py-3 px-4 text-gray-600">{{ $node->country }}{{ $node->city ? ' · ' . $node->city : '' }}</td>
                        <td class="py-3 px-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach((array) $node->roles as $role)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $roleBadge[$role] ?? 'bg-gray-100 text-gray-700' }}">{{ $role }}</span>
                                @endforeach
                            </div>
                            @if($server)
                                <div class="text-xs text-gray-400 mt-1">WG {{ $server->address }} · {{ $server->connected_clients_count ?? 0 }} peers{{ $server->public_key ? '' : ' · รอคีย์จากโหนด' }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                            @if($b && $node->status === 'active')
                                <div class="text-xs mt-1 {{ $b->acceptNew() ? 'text-gray-400' : 'text-red-600 font-medium' }}">
                                    {{ $b->acceptNew() ? 'รับตั้งแต่ ' . $b->minTier() : 'ไม่รับผู้ใช้ใหม่' }}
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right">
                            <span class="font-medium">{{ number_format($node->current_clients) }}</span>
                            <span class="text-gray-400">/ {{ number_format($node->max_clients) }}</span>
                        </td>
                        <td class="py-3 px-4 min-w-[180px]">
                            <div class="flex items-center gap-2">
                                <div class="w-24 bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $pct >= 90 ? 'bg-red-500' : ($pct >= 75 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, max(0, $pct)) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-600 whitespace-nowrap">{{ number_format($b?->usedGb ?? 0, 1) }} / {{ number_format($node->included_gb) }}</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-1">{{ $node->billing_mode }} · รอบ {{ $b?->periodStart->format('d/m') }}–{{ $b?->periodEnd->format('d/m') }}{{ $node->allow_overage ? ' · overage' : '' }}</div>
                        </td>
                        <td class="py-3 px-4 text-right text-gray-700">฿{{ number_format($b?->costSoFarThb ?? 0, 0) }}</td>
                        <td class="py-3 px-4 text-right">
                            <span class="{{ $b && $b->projectedCostThb > (float) $node->monthly_cost_thb ? 'text-red-600 font-semibold' : 'text-gray-700' }}">฿{{ number_format($b?->projectedCostThb ?? 0, 0) }}</span>
                            <div class="text-xs text-gray-400">{{ number_format($b?->projectedGb ?? 0, 0) }} GB</div>
                        </td>
                        <td class="py-3 px-4 text-xs whitespace-nowrap">
                            @if($node->last_seen_at)
                                <span class="{{ $fresh ? 'text-gray-500' : 'text-red-600 font-semibold' }}">{{ $node->last_seen_at->diffForHumans() }}</span>
                            @else
                                <span class="text-red-600 font-semibold">ยังไม่เคยซิงก์</span>
                            @endif
                            @if(!empty($node->last_sync['agent']))
                                <div class="text-gray-400">{{ $node->last_sync['agent'] }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right whitespace-nowrap">
                            <a href="{{ route('admin.localvpn.fleet.nodes.edit', $node) }}" class="text-emerald-600 hover:text-emerald-800 text-sm font-medium">แก้ไข</a>
                            <form method="POST" action="{{ route('admin.localvpn.fleet.nodes.toggle', $node) }}" class="inline"
                                  @if($node->status !== 'disabled') onsubmit="return confirm('ปิดใช้งานโหนด ' + @js($node->code) + '? relay จะปฏิเสธทุกคนและ WireGuard peers ถูกถอดออก')" @endif>
                                @csrf
                                <button type="submit" class="ml-2 text-sm font-medium {{ $node->status === 'disabled' ? 'text-sky-600 hover:text-sky-800' : 'text-amber-600 hover:text-amber-800' }}">
                                    {{ $node->status === 'disabled' ? 'เปิด' : 'ปิด' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="py-12 text-center text-gray-500">
                            ยังไม่มีโหนด — <a href="{{ route('admin.localvpn.fleet.nodes.create') }}" class="text-emerald-600 hover:underline">เพิ่มโหนดแรก</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
