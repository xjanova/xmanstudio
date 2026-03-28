@extends($adminLayout ?? 'layouts.admin')

@section('title', 'LocalVPN - อุปกรณ์ออนไลน์')
@section('page-title', 'LocalVPN - อุปกรณ์ออนไลน์')

@section('content')
@include('admin.localvpn._tabs')

{{-- Search --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
    <form method="GET" class="flex gap-4 items-end">
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">ค้นหา</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ชื่อ, Virtual IP, Public IP..."
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">ค้นหา</button>
        @if(request('search'))
            <a href="{{ route('admin.localvpn.members') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">ล้าง</a>
        @endif
    </form>
</div>

{{-- Members Table --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">อุปกรณ์ออนไลน์ ({{ $members->total() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ชื่อ</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">เครือข่าย</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Virtual IP</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Public IP</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">Port</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Heartbeat ล่าสุด</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($members as $member)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            <span class="font-medium text-gray-900">{{ $member->display_name }}</span>
                        </div>
                    </td>
                    <td class="py-3 px-4">
                        <a href="{{ route('admin.localvpn.networks.show', $member->network) }}" class="text-emerald-600 hover:text-emerald-800 hover:underline">
                            {{ $member->network->name ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="py-3 px-4 font-mono text-xs text-gray-600">{{ $member->virtual_ip }}</td>
                    <td class="py-3 px-4 font-mono text-xs text-gray-600">{{ $member->public_ip ?? '-' }}</td>
                    <td class="py-3 px-4 text-center text-gray-600">{{ $member->public_port ?? '-' }}</td>
                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $member->last_heartbeat_at?->diffForHumans() ?? '-' }}</td>
                    <td class="py-3 px-4 text-center">
                        <form method="POST" action="{{ route('admin.localvpn.members.kick', $member) }}"
                              onsubmit="return confirm('เตะ \'{{ $member->display_name }}\' ออกจากเครือข่าย?')">
                            @csrf
                            <button type="submit" class="px-3 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-medium hover:bg-red-200 transition-colors">เตะ</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-gray-500">ไม่มีอุปกรณ์ออนไลน์</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($members->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $members->links() }}
    </div>
    @endif
</div>
@endsection
