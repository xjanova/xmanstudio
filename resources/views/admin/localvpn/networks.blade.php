@extends('layouts.admin')

@section('title', 'LocalVPN - เครือข่าย')
@section('page-title', 'LocalVPN - จัดการเครือข่าย')

@section('content')
{{-- Navigation Tabs --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-6 overflow-x-auto">
        <a href="{{ route('admin.localvpn.dashboard') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dashboard</a>
        <a href="{{ route('admin.localvpn.networks') }}" class="whitespace-nowrap border-b-2 border-emerald-500 pb-3 px-1 text-sm font-medium text-emerald-600">เครือข่าย</a>
        <a href="{{ route('admin.localvpn.members') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">อุปกรณ์</a>
        <a href="{{ route('admin.localvpn.sessions') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Sessions</a>
        <a href="{{ route('admin.localvpn.traffic') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Traffic Logs</a>
        <a href="{{ route('admin.localvpn.settings') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">ตั้งค่า</a>
    </nav>
</div>

{{-- Search & Filters --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">ค้นหา</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ชื่อหรือ slug..."
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        <div class="w-40">
            <label class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
            <select name="status" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">ทั้งหมด</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
            </select>
        </div>
        <div class="w-40">
            <label class="block text-sm font-medium text-gray-700 mb-1">ประเภท</label>
            <select name="type" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                <option value="">ทั้งหมด</option>
                <option value="public" {{ request('type') === 'public' ? 'selected' : '' }}>สาธารณะ</option>
                <option value="private" {{ request('type') === 'private' ? 'selected' : '' }}>ส่วนตัว</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">ค้นหา</button>
        @if(request()->hasAny(['search', 'status', 'type']))
            <a href="{{ route('admin.localvpn.networks') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">ล้าง</a>
        @endif
    </form>
</div>

{{-- Networks Table --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ชื่อ</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Slug</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">เจ้าของ</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">สมาชิก</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">ออนไลน์</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">ประเภท</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">สถานะ</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">สร้างเมื่อ</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($networks as $network)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $network->name }}</td>
                    <td class="py-3 px-4 text-gray-500 font-mono text-xs">{{ $network->slug }}</td>
                    <td class="py-3 px-4 text-gray-600">{{ $network->owner->name ?? 'N/A' }}</td>
                    <td class="py-3 px-4 text-center">{{ $network->members_count }}/{{ $network->max_members }}</td>
                    <td class="py-3 px-4 text-center">
                        @php $onlineCount = $network->members()->where('is_online', true)->count(); @endphp
                        @if($onlineCount > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $onlineCount }}</span>
                        @else
                            <span class="text-gray-400">0</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center">
                        @if($network->is_public)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">สาธารณะ</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">ส่วนตัว</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center">
                        @if($network->is_active)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">ใช้งาน</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">ปิด</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center text-gray-500 text-xs">{{ $network->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.localvpn.networks.show', $network) }}" class="text-emerald-600 hover:text-emerald-800" title="ดูรายละเอียด">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('admin.localvpn.networks.toggle', $network) }}" class="inline">
                                @csrf
                                <button type="submit" class="{{ $network->is_active ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800' }}" title="{{ $network->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                    @if($network->is_active)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endif
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.localvpn.networks.delete', $network) }}" class="inline"
                                  onsubmit="return confirm('คุณแน่ใจหรือว่าต้องการลบเครือข่าย \'{{ $network->name }}\'? การกระทำนี้ไม่สามารถย้อนกลับได้')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" title="ลบ">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="py-12 text-center text-gray-500">ไม่พบเครือข่าย</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($networks->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $networks->links() }}
    </div>
    @endif
</div>
@endsection
