@extends('layouts.admin')

@section('title', 'LocalVPN - Traffic Logs')
@section('page-title', 'LocalVPN - Traffic Logs')

@section('content')
{{-- Navigation Tabs --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-6 overflow-x-auto">
        <a href="{{ route('admin.localvpn.dashboard') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dashboard</a>
        <a href="{{ route('admin.localvpn.networks') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">เครือข่าย</a>
        <a href="{{ route('admin.localvpn.members') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">อุปกรณ์</a>
        <a href="{{ route('admin.localvpn.sessions') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Sessions</a>
        <a href="{{ route('admin.localvpn.traffic') }}" class="whitespace-nowrap border-b-2 border-emerald-500 pb-3 px-1 text-sm font-medium text-emerald-600">Traffic Logs</a>
        <a href="{{ route('admin.localvpn.settings') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">ตั้งค่า</a>
    </nav>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Action</label>
            <select name="action" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">ทั้งหมด</option>
                <option value="join" {{ request('action') === 'join' ? 'selected' : '' }}>Join</option>
                <option value="leave" {{ request('action') === 'leave' ? 'selected' : '' }}>Leave</option>
                <option value="data_relay" {{ request('action') === 'data_relay' ? 'selected' : '' }}>Data Relay</option>
                <option value="heartbeat" {{ request('action') === 'heartbeat' ? 'selected' : '' }}>Heartbeat</option>
                <option value="network_create" {{ request('action') === 'network_create' ? 'selected' : '' }}>Network Create</option>
                <option value="network_delete" {{ request('action') === 'network_delete' ? 'selected' : '' }}>Network Delete</option>
            </select>
        </div>
        <div class="w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">เครือข่าย</label>
            <select name="network_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">ทั้งหมด</option>
                @foreach($networks as $id => $name)
                    <option value="{{ $id }}" {{ request('network_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-44">
            <label class="block text-sm font-medium text-gray-700 mb-1">จากวันที่</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        <div class="w-44">
            <label class="block text-sm font-medium text-gray-700 mb-1">ถึงวันที่</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">กรอง</button>
        @if(request()->hasAny(['action', 'network_id', 'date_from', 'date_to']))
            <a href="{{ route('admin.localvpn.traffic') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">ล้าง</a>
        @endif
    </form>
</div>

{{-- Logs Table --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">Traffic Logs ({{ $logs->total() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">เวลา</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">เครือข่าย</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">สมาชิก</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Action</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">Bytes</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">IP</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Metadata</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 text-gray-500 text-xs whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                    <td class="py-3 px-4">
                        @if($log->network)
                            <a href="{{ route('admin.localvpn.networks.show', $log->network_id) }}" class="text-emerald-600 hover:text-emerald-800 hover:underline">
                                {{ $log->network->name }}
                            </a>
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-gray-600">{{ $log->member->display_name ?? '-' }}</td>
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
                    <td class="py-3 px-4 text-right text-gray-500 font-mono text-xs">{{ $log->bytes > 0 ? number_format($log->bytes) : '-' }}</td>
                    <td class="py-3 px-4 text-gray-500 font-mono text-xs">{{ $log->ip_address ?? '-' }}</td>
                    <td class="py-3 px-4 text-gray-500 text-xs max-w-xs truncate">
                        @if($log->metadata)
                            <span title="{{ json_encode($log->metadata) }}">{{ Str::limit(json_encode($log->metadata), 50) }}</span>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-gray-500">ไม่มี Traffic Logs</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
