@extends('layouts.admin')

@section('title', 'LocalVPN Dashboard')
@section('page-title', 'LocalVPN - Virtual LAN Relay Server')

@section('content')
{{-- Navigation Tabs --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="LocalVPN Admin Navigation">
        <a href="{{ route('admin.localvpn.dashboard') }}" class="whitespace-nowrap border-b-2 border-emerald-500 pb-3 px-1 text-sm font-medium text-emerald-600">Dashboard</a>
        <a href="{{ route('admin.localvpn.networks') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">เครือข่าย</a>
        <a href="{{ route('admin.localvpn.members') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">อุปกรณ์</a>
        <a href="{{ route('admin.localvpn.sessions') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Sessions</a>
        <a href="{{ route('admin.localvpn.traffic') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Traffic Logs</a>
        <a href="{{ route('admin.localvpn.settings') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">ตั้งค่า</a>
    </nav>
</div>

{{-- Header --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-700 via-emerald-600 to-teal-500 p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-grid-white/10"></div>
    <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 bg-emerald-400/20 rounded-full blur-3xl"></div>
    <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-white">LocalVPN Dashboard</h1>
            </div>
            <p class="text-emerald-100 text-lg">Virtual LAN Relay Server Management</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <div class="inline-flex items-center px-4 py-2.5 bg-white/20 backdrop-blur-sm text-white font-medium rounded-xl border border-white/25">
                <span class="w-3 h-3 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                <span>Relay Server: Online</span>
            </div>
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
    {{-- Total Networks --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">เครือข่ายทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalNetworks) }}</p>
            </div>
        </div>
    </div>

    {{-- Active Networks --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">เครือข่ายที่ใช้งาน</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($activeNetworks) }}</p>
            </div>
        </div>
    </div>

    {{-- Online Devices --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">อุปกรณ์ออนไลน์</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($onlineDevices) }}</p>
            </div>
        </div>
    </div>

    {{-- Total Members --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">สมาชิกทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalMembers) }}</p>
            </div>
        </div>
    </div>

    {{-- Active Sessions --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Relay Sessions</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($activeSessions) }}</p>
            </div>
        </div>
    </div>

    {{-- Total Traffic --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500 to-rose-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">ข้อมูลทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900">
                    @if($totalTrafficBytes >= 1073741824)
                        {{ number_format($totalTrafficBytes / 1073741824, 2) }} GB
                    @elseif($totalTrafficBytes >= 1048576)
                        {{ number_format($totalTrafficBytes / 1048576, 2) }} MB
                    @elseif($totalTrafficBytes >= 1024)
                        {{ number_format($totalTrafficBytes / 1024, 2) }} KB
                    @else
                        {{ number_format($totalTrafficBytes) }} B
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>

{{-- Chart Area --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">เครือข่ายใหม่ & การเชื่อมต่อ (30 วัน)</h3>
        <canvas id="activityChart" height="200"></canvas>
    </div>
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">ปริมาณข้อมูล Relay (30 วัน)</h3>
        <canvas id="trafficChart" height="200"></canvas>
    </div>
</div>

{{-- Recent Activity --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">กิจกรรมล่าสุด</h3>
    @if($recentActivity->isEmpty())
        <p class="text-gray-500 text-center py-8">ยังไม่มีกิจกรรม</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-gray-600 font-medium">เวลา</th>
                        <th class="text-left py-3 px-4 text-gray-600 font-medium">เครือข่าย</th>
                        <th class="text-left py-3 px-4 text-gray-600 font-medium">สมาชิก</th>
                        <th class="text-left py-3 px-4 text-gray-600 font-medium">Action</th>
                        <th class="text-right py-3 px-4 text-gray-600 font-medium">Bytes</th>
                        <th class="text-left py-3 px-4 text-gray-600 font-medium">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($recentActivity as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4 text-gray-500">{{ $log->created_at?->format('d/m H:i:s') }}</td>
                        <td class="py-3 px-4">{{ $log->network->name ?? '-' }}</td>
                        <td class="py-3 px-4">{{ $log->member->display_name ?? '-' }}</td>
                        <td class="py-3 px-4">
                            @switch($log->action)
                                @case('join')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Join</span>
                                    @break
                                @case('leave')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Leave</span>
                                    @break
                                @case('data_relay')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Relay</span>
                                    @break
                                @case('heartbeat')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Heartbeat</span>
                                    @break
                                @case('network_create')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Create</span>
                                    @break
                                @case('network_delete')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">Delete</span>
                                    @break
                            @endswitch
                        </td>
                        <td class="py-3 px-4 text-right text-gray-500">{{ $log->bytes > 0 ? number_format($log->bytes) : '-' }}</td>
                        <td class="py-3 px-4 text-gray-500 font-mono text-xs">{{ $log->ip_address ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    const chartData = @json($chartData);

    // Activity Chart
    new Chart(document.getElementById('activityChart'), {
        type: 'line',
        data: {
            labels: chartData.map(d => d.date.substring(5)),
            datasets: [
                {
                    label: 'เครือข่ายใหม่',
                    data: chartData.map(d => d.networks_created),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'การเชื่อมต่อ',
                    data: chartData.map(d => d.joins),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    // Traffic Chart
    new Chart(document.getElementById('trafficChart'), {
        type: 'bar',
        data: {
            labels: chartData.map(d => d.date.substring(5)),
            datasets: [{
                label: 'ข้อมูล (KB)',
                data: chartData.map(d => Math.round(d.traffic_bytes / 1024)),
                backgroundColor: 'rgba(16, 185, 129, 0.6)',
                borderColor: '#10b981',
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
@endpush
