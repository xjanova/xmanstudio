@extends('layouts.admin')

@section('title', 'จัดการวิดีโอ Metal-X')
@section('page-title', 'จัดการวิดีโอ YouTube')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-600">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">วิดีโอทั้งหมด</p>
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
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">แนะนำ</p>
                <p class="text-2xl font-semibold">{{ number_format($stats['featured']) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">ยอดวิว</p>
                <p class="text-2xl font-semibold">{{ number_format($stats['total_views']) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <p class="text-gray-600">จัดการวิดีโอ YouTube ของช่อง Metal-X</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($isApiConfigured)
            <form action="{{ route('admin.metal-x.videos.sync-all') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Sync จาก YouTube
                </button>
            </form>
            <form action="{{ route('admin.metal-x.videos.update-stats') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    อัพเดทสถิติ
                </button>
            </form>
        @endif
        <a href="{{ route('admin.metal-x.videos.create') }}"
           class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            + เพิ่มวิดีโอ
        </a>
    </div>
</div>

@if(!$isApiConfigured)
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    YouTube API ยังไม่ได้ตั้งค่า - <a href="{{ route('admin.metal-x.settings') }}" class="font-medium underline">ตั้งค่า API Key</a> เพื่อเปิดใช้งานการ sync อัตโนมัติ
                </p>
            </div>
        </div>
    </div>
@endif

<!-- Search & Filter -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form action="" method="GET" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาวิดีโอ..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
        </div>
        <div>
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                <option value="">ทุกสถานะ</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
            </select>
        </div>
        <div>
            <select name="sort" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                <option value="published_at" {{ request('sort') === 'published_at' ? 'selected' : '' }}>วันที่เผยแพร่</option>
                <option value="view_count" {{ request('sort') === 'view_count' ? 'selected' : '' }}>ยอดวิว</option>
                <option value="like_count" {{ request('sort') === 'like_count' ? 'selected' : '' }}>ยอดไลค์</option>
                <option value="order" {{ request('sort') === 'order' ? 'selected' : '' }}>ลำดับ</option>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">ค้นหา</button>
        <a href="{{ route('admin.metal-x.videos.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">รีเซ็ต</a>
    </form>
</div>

<!-- Videos Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @forelse($videos as $video)
        <div class="bg-white rounded-lg shadow overflow-hidden group">
            <!-- Thumbnail -->
            <div class="relative aspect-video bg-gray-100">
                @if($video->best_thumbnail)
                    <img src="{{ $video->best_thumbnail }}" alt="{{ $video->title }}"
                         class="w-full h-full object-cover">
                @endif
                <div class="absolute bottom-2 right-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
                    {{ $video->formatted_duration }}
                </div>
                @if($video->is_featured)
                    <div class="absolute top-2 left-2 bg-yellow-500 text-white text-xs px-2 py-1 rounded">
                        แนะนำ
                    </div>
                @endif
                @if(!$video->is_active)
                    <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                        <span class="text-white font-semibold">ปิดใช้งาน</span>
                    </div>
                @endif
            </div>

            <!-- Content -->
            <div class="p-4">
                <h3 class="font-semibold text-gray-900 line-clamp-2 mb-2" title="{{ $video->title }}">
                    {{ $video->title }}
                </h3>
                @if($video->title_th)
                    <p class="text-sm text-gray-500 line-clamp-1 mb-2">{{ $video->title_th }}</p>
                @endif

                <!-- Stats -->
                <div class="flex items-center text-sm text-gray-500 space-x-4 mb-3">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        {{ $video->formatted_view_count }}
                    </span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                        </svg>
                        {{ $video->formatted_like_count }}
                    </span>
                </div>

                <p class="text-xs text-gray-400 mb-3">
                    {{ $video->published_at?->format('d M Y') ?? 'ไม่ระบุวันที่' }}
                </p>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex space-x-2">
                        <a href="{{ $video->youtube_url }}" target="_blank"
                           class="text-red-600 hover:text-red-800" title="ดูบน YouTube">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                            </svg>
                        </a>
                        <a href="{{ route('admin.metal-x.videos.edit', $video) }}"
                           class="text-blue-600 hover:text-blue-800" title="แก้ไข">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        @if($isApiConfigured)
                            <form action="{{ route('admin.metal-x.videos.sync', $video) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-800" title="Sync จาก YouTube">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="flex space-x-2">
                        <form action="{{ route('admin.metal-x.videos.toggle-featured', $video) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="{{ $video->is_featured ? 'text-yellow-500' : 'text-gray-400' }} hover:text-yellow-600" title="แนะนำ">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </button>
                        </form>
                        <form action="{{ route('admin.metal-x.videos.toggle', $video) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="{{ $video->is_active ? 'text-green-600' : 'text-gray-400' }} hover:text-green-700" title="{{ $video->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </button>
                        </form>
                        <button type="button" onclick="confirmDelete({{ $video->id }}, '{{ addslashes($video->title) }}')"
                                class="text-red-600 hover:text-red-800" title="ลบ">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full text-center py-12 text-gray-500">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <p>ไม่พบวิดีโอ</p>
            <a href="{{ route('admin.metal-x.videos.create') }}" class="mt-4 inline-block text-primary-600 hover:underline">+ เพิ่มวิดีโอ</a>
        </div>
    @endforelse
</div>

@if($videos->hasPages())
    <div class="mt-6">
        {{ $videos->links() }}
    </div>
@endif

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ยืนยันการลบ</h3>
                <p class="text-gray-600">คุณต้องการลบวิดีโอ "<span id="deleteVideoTitle" class="font-medium"></span>" หรือไม่?</p>
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
    function confirmDelete(videoId, videoTitle) {
        document.getElementById('deleteForm').action = `/admin/metal-x/videos/${videoId}`;
        document.getElementById('deleteVideoTitle').textContent = videoTitle;
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
