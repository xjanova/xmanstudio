@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการเพลย์ลิสต์ Metal-X')
@section('page-title', 'จัดการเพลย์ลิสต์')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">เพลย์ลิสต์ทั้งหมด</p>
                <p class="text-2xl font-semibold">{{ number_format($stats['total']) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">เปิดใช้งาน</p>
                <p class="text-2xl font-semibold">{{ number_format($stats['active']) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">Synced จาก YouTube</p>
                <p class="text-2xl font-semibold">{{ number_format($stats['synced']) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <p class="text-gray-600">จัดการเพลย์ลิสต์วิดีโอ</p>
    </div>
    <a href="{{ route('admin.metal-x.playlists.create') }}"
       class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
        + สร้างเพลย์ลิสต์
    </a>
</div>

@if(!$isApiConfigured)
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <p class="text-sm text-yellow-700">
            YouTube API ยังไม่ได้ตั้งค่า - <a href="{{ route('admin.metal-x.settings') }}" class="font-medium underline">ตั้งค่า API Key</a> เพื่อเปิดใช้งานการ sync เพลย์ลิสต์จาก YouTube
        </p>
    </div>
@endif

<!-- Playlists List -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เพลย์ลิสต์</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วิดีโอ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($playlists as $playlist)
                <tr>
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-16 w-24 bg-gray-100 rounded overflow-hidden">
                                @if($playlist->thumbnail_url)
                                    <img src="{{ $playlist->thumbnail_url }}" alt="{{ $playlist->title }}"
                                         class="h-full w-full object-cover">
                                @else
                                    <div class="h-full w-full flex items-center justify-center text-gray-400">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">{{ $playlist->title }}</div>
                                @if($playlist->title_th)
                                    <div class="text-sm text-gray-500">{{ $playlist->title_th }}</div>
                                @endif
                                @if($playlist->youtube_id)
                                    <a href="{{ $playlist->youtube_url }}" target="_blank"
                                       class="text-xs text-red-600 hover:underline">ดูบน YouTube</a>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-900">{{ $playlist->videos_count ?? $playlist->video_count }} วิดีโอ</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex flex-col space-y-1">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full w-fit
                                {{ $playlist->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ $playlist->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                            </span>
                            @if($playlist->is_featured)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 w-fit">
                                    แนะนำ
                                </span>
                            @endif
                            @if($playlist->is_synced)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 w-fit">
                                    Synced
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex space-x-3">
                            <a href="{{ route('admin.metal-x.playlists.edit', $playlist) }}"
                               class="text-primary-600 hover:underline">แก้ไข</a>
                            @if($playlist->youtube_id && $isApiConfigured)
                                <form action="{{ route('admin.metal-x.playlists.sync', $playlist) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-blue-600 hover:underline">Sync</button>
                                </form>
                            @endif
                            <form action="{{ route('admin.metal-x.playlists.toggle', $playlist) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="{{ $playlist->is_active ? 'text-orange-600' : 'text-green-600' }} hover:underline">
                                    {{ $playlist->is_active ? 'ปิด' : 'เปิด' }}
                                </button>
                            </form>
                            <button type="button" onclick="confirmDelete({{ $playlist->id }}, '{{ addslashes($playlist->title) }}')"
                                    class="text-red-600 hover:underline">ลบ</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        <p>ไม่พบเพลย์ลิสต์</p>
                        <a href="{{ route('admin.metal-x.playlists.create') }}" class="mt-2 inline-block text-primary-600 hover:underline">+ สร้างเพลย์ลิสต์</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($playlists->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $playlists->links() }}
        </div>
    @endif
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ยืนยันการลบ</h3>
                <p class="text-gray-600">คุณต้องการลบเพลย์ลิสต์ "<span id="deletePlaylistTitle" class="font-medium"></span>" หรือไม่?</p>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" onclick="hideDeleteModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">ลบ</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function confirmDelete(playlistId, playlistTitle) {
        document.getElementById('deleteForm').action = `/admin/metal-x/playlists/${playlistId}`;
        document.getElementById('deletePlaylistTitle').textContent = playlistTitle;
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function hideDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }
</script>
@endpush
@endsection
