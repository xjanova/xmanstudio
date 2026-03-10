@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Tping Data Profiles - Admin')
@section('page-title', 'Tping Data Profiles')

@section('content')
<!-- Header -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-gray-800 via-gray-900 to-black p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl"></div>
    <div class="relative">
        <h1 class="text-2xl font-bold text-white">Tping Data Profiles</h1>
        <p class="mt-1 text-gray-400 text-sm">จัดการ Data Profile ทั้งหมดจากทุก user</p>
    </div>
</div>

@if(session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl">
        {{ session('success') }}
    </div>
@endif

<!-- Stats -->
<div class="grid grid-cols-2 gap-4 mb-8">
    <div class="bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
        <p class="text-sm text-gray-500">Profile ทั้งหมด</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
        <p class="text-sm text-gray-500">ผู้ใช้</p>
        <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['users'] }}</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-lg p-4 mb-6 border border-gray-100">
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหา profile..."
               class="flex-1 rounded-xl border-gray-200 shadow-sm text-sm">
        <input type="text" name="user_id" value="{{ request('user_id') }}" placeholder="User ID..."
               class="w-32 rounded-xl border-gray-200 shadow-sm text-sm">
        <button type="submit" class="px-6 py-2 bg-gray-900 text-white rounded-xl text-sm font-medium hover:bg-gray-800 transition-all">
            ค้นหา
        </button>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
    @if($profiles->isEmpty())
        <div class="p-12 text-center text-gray-400">ยังไม่มี Data Profile</div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">ID</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">ชื่อ</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">ผู้ใช้</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">หมวดหมู่</th>
                        <th class="px-6 py-4 text-center font-semibold text-gray-600">Fields</th>
                        <th class="px-6 py-4 text-left font-semibold text-gray-600">อัปเดต</th>
                        <th class="px-6 py-4 text-center font-semibold text-gray-600">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($profiles as $pf)
                        @php $fieldCount = count(json_decode($pf->fields_json, true) ?? []); @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-gray-400">#{{ $pf->id }}</td>
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.tping.data-profiles.show', $pf) }}" class="font-medium text-gray-900 hover:text-emerald-600">
                                    {{ $pf->name }}
                                </a>
                            </td>
                            <td class="px-6 py-3 text-gray-500">{{ $pf->user->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $pf->category ?: '-' }}</td>
                            <td class="px-6 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700">{{ $fieldCount }}</span>
                            </td>
                            <td class="px-6 py-3 text-gray-400 text-xs">{{ $pf->updated_at->diffForHumans() }}</td>
                            <td class="px-6 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('admin.tping.data-profiles.show', $pf) }}" class="p-2 text-gray-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-50" title="ดู">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.tping.data-profiles.destroy', $pf) }}" onsubmit="return confirm('ลบ profile #{{ $pf->id }}?')" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50" title="ลบ">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($profiles->hasPages())
            <div class="px-6 py-4 border-t">{{ $profiles->links() }}</div>
        @endif
    @endif
</div>
@endsection
