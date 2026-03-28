@extends($adminLayout ?? 'layouts.admin')

@section('title', 'BitTorrent - ไฟล์')
@section('page-title', 'LocalVPN - จัดการไฟล์ BitTorrent')

@section('content')
@include('admin.localvpn._tabs')

{{-- Search & Filters --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">ค้นหา</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ชื่อไฟล์, info_hash..."
                   class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
        </div>
        <div class="w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
            <select name="category_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
                <option value="">ทั้งหมด</option>
                @foreach($categories ?? [] as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->icon }} {{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-36">
            <label class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
            <select name="status" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
                <option value="">ทั้งหมด</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิด</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition-colors">ค้นหา</button>
        @if(request()->hasAny(['search', 'category_id', 'status']))
            <a href="{{ route('admin.localvpn.torrent.files') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">ล้าง</a>
        @endif
    </form>
</div>

{{-- Files Table --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">ไฟล์ทั้งหมด ({{ $files->total() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-3 text-gray-600 font-medium w-12"></th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ชื่อไฟล์</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">หมวดหมู่</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ผู้อัปโหลด</th>
                    <th class="text-right py-3 px-4 text-gray-600 font-medium">ขนาด</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">ดาวน์โหลด</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">Seeders</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">สถานะ</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($files as $file)
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-3">
                        @if($file->thumbnail_url)
                            @if(str_starts_with($file->thumbnail_url, 'data:'))
                                <img src="{{ $file->thumbnail_url }}" alt="" class="w-10 h-10 rounded-lg object-cover">
                            @else
                                <img src="{{ $file->thumbnail_url }}" alt="" class="w-10 h-10 rounded-lg object-cover">
                            @endif
                        @else
                            <div class="w-10 h-10 rounded-lg bg-violet-50 flex items-center justify-center">
                                <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                        @endif
                    </td>
                    <td class="py-3 px-4">
                        <a href="{{ route('admin.localvpn.torrent.files.show', $file) }}" class="text-violet-600 hover:text-violet-800 hover:underline font-medium">
                            {{ Str::limit($file->file_name, 40) }}
                        </a>
                    </td>
                    <td class="py-3 px-4 text-gray-500">{{ $file->category->name ?? '-' }}</td>
                    <td class="py-3 px-4 text-gray-600">{{ $file->uploader_display_name ?? 'N/A' }}</td>
                    <td class="py-3 px-4 text-right text-gray-600 font-mono text-xs whitespace-nowrap">
                        @php $bytes = $file->file_size ?? 0; @endphp
                        @if($bytes >= 1073741824)
                            {{ number_format($bytes / 1073741824, 2) }} GB
                        @elseif($bytes >= 1048576)
                            {{ number_format($bytes / 1048576, 2) }} MB
                        @elseif($bytes >= 1024)
                            {{ number_format($bytes / 1024, 2) }} KB
                        @else
                            {{ number_format($bytes) }} B
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center text-gray-600">{{ number_format($file->download_count ?? 0) }}</td>
                    <td class="py-3 px-4 text-center">
                        @php
                            $onlineSeeders = $file->online_seeders_count ?? 0;
                            $totalSeeders = $file->seeders_count ?? 0;
                        @endphp
                        @if($onlineSeeders > 0)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ $onlineSeeders }}/{{ $totalSeeders }}
                            </span>
                        @else
                            <span class="text-gray-400">0/{{ $totalSeeders }}</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center">
                        @if($file->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">ใช้งาน</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">ปิด</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.localvpn.torrent.files.show', $file) }}" class="text-violet-600 hover:text-violet-800" title="ดูรายละเอียด">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('admin.localvpn.torrent.files.toggle', $file) }}" class="inline">
                                @csrf
                                <button type="submit" class="{{ $file->is_active ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800' }}" title="{{ $file->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                    @if($file->is_active)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    @endif
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.localvpn.torrent.files.delete', $file) }}" class="inline"
                                  onsubmit="return confirm('ลบไฟล์ \'{{ $file->file_name }}\'? การกระทำนี้ไม่สามารถย้อนกลับได้')">
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
                    <td colspan="9" class="py-12 text-center text-gray-500">ไม่พบไฟล์</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($files->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $files->links() }}
    </div>
    @endif
</div>
@endsection
