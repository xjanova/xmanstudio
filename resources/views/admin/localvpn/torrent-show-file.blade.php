@extends($adminLayout ?? 'layouts.admin')

@section('title', 'BitTorrent - ' . $file->file_name)
@section('page-title', 'LocalVPN - รายละเอียดไฟล์')

@section('content')
{{-- Back Button --}}
<div class="mb-6">
    <a href="{{ route('admin.localvpn.torrent.files') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        กลับไปหน้ารายการไฟล์
    </a>
</div>

{{-- File Info Card --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
    <div class="flex flex-col lg:flex-row gap-6">
        {{-- Thumbnail --}}
        <div class="flex-shrink-0">
            @if($file->thumbnail_url)
                @if(str_starts_with($file->thumbnail_url, 'data:'))
                    <img src="{{ $file->thumbnail_url }}" alt="Thumbnail" class="w-48 h-48 rounded-xl object-cover border border-gray-200">
                @else
                    <img src="{{ $file->thumbnail_url }}" alt="Thumbnail" class="w-48 h-48 rounded-xl object-cover border border-gray-200">
                @endif
            @else
                <div class="w-48 h-48 rounded-xl bg-violet-50 flex items-center justify-center border border-violet-100">
                    <svg class="w-16 h-16 text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
            @endif
        </div>

        {{-- Details --}}
        <div class="flex-1">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-1">{{ $file->file_name }}</h2>
                    <div class="flex items-center gap-2">
                        @if($file->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">ใช้งาน</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">ปิด</span>
                        @endif
                        @if($file->category)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-800">
                                {{ $file->category->icon ?? '' }} {{ $file->category->name }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('admin.localvpn.torrent.files.toggle', $file) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium {{ $file->is_active ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : 'bg-green-100 text-green-800 hover:bg-green-200' }} transition-colors">
                            {{ $file->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.localvpn.torrent.files.destroy', $file) }}"
                          onsubmit="return confirm('ลบไฟล์นี้? การกระทำนี้ไม่สามารถย้อนกลับได้')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-100 text-red-800 rounded-lg text-sm font-medium hover:bg-red-200 transition-colors">ลบไฟล์</button>
                    </form>
                </div>
            </div>

            @if($file->description)
                <p class="text-gray-600 mb-4">{{ $file->description }}</p>
            @endif

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">ผู้อัปโหลด:</span>
                    <span class="text-gray-900 font-medium">{{ $file->uploader_display_name ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Machine ID:</span>
                    <span class="font-mono text-xs text-gray-900">{{ Str::limit($file->uploader_machine_id ?? '-', 16) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">ขนาดไฟล์:</span>
                    <span class="text-gray-900">
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
                    </span>
                </div>
                <div>
                    <span class="text-gray-500">ดาวน์โหลด:</span>
                    <span class="text-gray-900">{{ number_format($file->download_count ?? 0) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Info Hash:</span>
                    <span class="font-mono text-xs text-gray-900">{{ $file->info_hash ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Chunks:</span>
                    <span class="text-gray-900">{{ number_format($file->total_chunks ?? 0) }}</span>
                </div>
                <div>
                    <span class="text-gray-500">สร้างเมื่อ:</span>
                    <span class="text-gray-900">{{ $file->created_at?->format('d/m/Y H:i') }}</span>
                </div>
                <div>
                    <span class="text-gray-500">อัปเดตล่าสุด:</span>
                    <span class="text-gray-900">{{ $file->updated_at?->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Seeders Table --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">Seeders ({{ count($seeders ?? []) }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Machine ID</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ชื่อ</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Public IP</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">Port</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">สถานะ</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">เห็นล่าสุด</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">Chunks</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($seeders ?? [] as $seeder)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 font-mono text-xs text-gray-600">{{ Str::limit($seeder->machine_id, 16) }}</td>
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $seeder->display_name ?? 'N/A' }}</td>
                    <td class="py-3 px-4 font-mono text-xs text-gray-600">{{ $seeder->public_ip ?? '-' }}</td>
                    <td class="py-3 px-4 text-center text-gray-600">{{ $seeder->public_port ?? '-' }}</td>
                    <td class="py-3 px-4 text-center">
                        @if($seeder->is_online)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1 animate-pulse"></span>
                                Online
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Offline</span>
                        @endif
                    </td>
                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $seeder->last_seen_at?->diffForHumans() ?? '-' }}</td>
                    <td class="py-3 px-4 text-center text-gray-600">
                        @if(isset($seeder->chunks_owned) && isset($file->total_chunks) && $file->total_chunks > 0)
                            <div class="flex items-center justify-center gap-1">
                                <span>{{ $seeder->chunks_owned }}/{{ $file->total_chunks }}</span>
                                <div class="w-16 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-violet-500 rounded-full" style="width: {{ min(100, ($seeder->chunks_owned / $file->total_chunks) * 100) }}%"></div>
                                </div>
                            </div>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-12 text-center text-gray-500">ยังไม่มี Seeders</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
