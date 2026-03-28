@extends($adminLayout ?? 'layouts.admin')

@section('title', 'BitTorrent Dashboard')
@section('page-title', 'LocalVPN - BitTorrent Dashboard')

@section('content')
@include('admin.localvpn._tabs')

{{-- Header --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-700 via-violet-600 to-purple-500 p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-grid-white/10"></div>
    <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 bg-violet-400/20 rounded-full blur-3xl"></div>
    <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-white">BitTorrent Dashboard</h1>
            </div>
            <p class="text-violet-100 text-lg">P2P File Sharing Management</p>
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
    {{-- Total Files --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-violet-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">ไฟล์ทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalFiles ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Active Files --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">ไฟล์ที่ใช้งาน</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($activeFiles ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Categories --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-purple-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">หมวดหมู่</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalCategories ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Online Seeders --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Seeders ออนไลน์</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($onlineSeeders ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Total Downloads --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">ดาวน์โหลดทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalDownloads ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Pending KYC --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">KYC รอตรวจ</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($pendingKyc ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Total Users --}}
    <div class="rounded-2xl bg-white p-6 shadow-lg border border-gray-100">
        <div class="flex items-center gap-4">
            <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500 to-rose-600 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">ผู้ใช้ทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($totalUsers ?? 0) }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Top 5 Uploaders --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Top 5 Uploaders</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4 text-gray-600 font-medium">#</th>
                        <th class="text-left py-3 px-4 text-gray-600 font-medium">ชื่อ</th>
                        <th class="text-right py-3 px-4 text-gray-600 font-medium">ไฟล์</th>
                        <th class="text-right py-3 px-4 text-gray-600 font-medium">ขนาดรวม</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($topUploaders ?? [] as $index => $uploader)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4">
                            @if($index < 3)
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold
                                    {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : ($index === 1 ? 'bg-gray-100 text-gray-700' : 'bg-orange-100 text-orange-700') }}">
                                    {{ $index + 1 }}
                                </span>
                            @else
                                <span class="text-gray-500">{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-medium text-gray-900">{{ $uploader->display_name ?? 'N/A' }}</td>
                        <td class="py-3 px-4 text-right text-gray-600">{{ number_format($uploader->total_files_shared ?? 0) }}</td>
                        <td class="py-3 px-4 text-right text-gray-600">
                            @php $bytes = $uploader->total_uploaded_bytes ?? 0; @endphp
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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-gray-500">ยังไม่มีข้อมูล</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Files --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">ไฟล์ล่าสุด</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left py-3 px-4 text-gray-600 font-medium">ชื่อไฟล์</th>
                        <th class="text-left py-3 px-4 text-gray-600 font-medium">หมวดหมู่</th>
                        <th class="text-right py-3 px-4 text-gray-600 font-medium">ขนาด</th>
                        <th class="text-left py-3 px-4 text-gray-600 font-medium">เวลา</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentFiles ?? [] as $file)
                    <tr class="hover:bg-gray-50">
                        <td class="py-3 px-4">
                            <a href="{{ route('admin.localvpn.torrent.files.show', $file) }}" class="text-violet-600 hover:text-violet-800 hover:underline font-medium">
                                {{ Str::limit($file->file_name, 30) }}
                            </a>
                        </td>
                        <td class="py-3 px-4 text-gray-500">{{ $file->category->name ?? '-' }}</td>
                        <td class="py-3 px-4 text-right text-gray-600">
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
                        <td class="py-3 px-4 text-gray-500 text-xs">{{ $file->created_at?->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-gray-500">ยังไม่มีไฟล์</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
