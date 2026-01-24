@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการวิดีโอ Metal-X')
@section('page-title', 'จัดการวิดีโอ YouTube')

@push('styles')
<style>
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
</style>
@endpush

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-red-600 via-rose-600 to-pink-500 p-8 shadow-2xl mb-8">
    <div class="absolute top-0 left-0 w-72 h-72 bg-red-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-rose-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    <div class="relative">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-3xl font-bold text-white mb-2">วิดีโอ YouTube</h2>
                <p class="text-red-100">จัดการวิดีโอ YouTube ของช่อง Metal-X</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($isApiConfigured)
                    <form action="{{ route('admin.metal-x.videos.sync-all') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-5 py-2.5 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all flex items-center font-medium">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Sync จาก YouTube
                        </button>
                    </form>
                    <form action="{{ route('admin.metal-x.videos.update-stats') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-5 py-2.5 bg-white/10 backdrop-blur-sm text-white rounded-xl hover:bg-white/20 transition-all flex items-center font-medium">
                            อัพเดทสถิติ
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.metal-x.videos.create') }}"
                   class="px-5 py-2.5 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all flex items-center font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    เพิ่มวิดีโอ
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-xl bg-gradient-to-br from-red-500 to-rose-500 text-white shadow-lg">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">วิดีโอทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 text-white shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">เปิดใช้งาน</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['active']) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-xl bg-gradient-to-br from-yellow-500 to-amber-500 text-white shadow-lg">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">แนะนำ</p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['featured']) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 text-white shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">ยอดวิว</p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['total_views']) }}</p>
            </div>
        </div>
    </div>
</div>

@if(!$isApiConfigured)
    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-2xl p-4 mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700 dark:text-yellow-400">
                    YouTube API ยังไม่ได้ตั้งค่า - <a href="{{ route('admin.metal-x.settings') }}" class="font-medium underline hover:text-yellow-800 dark:hover:text-yellow-300">ตั้งค่า API Key</a> เพื่อเปิดใช้งานการ sync อัตโนมัติ
                </p>
            </div>
        </div>
    </div>
@endif

<!-- Search & Filter -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 mb-6">
    <form action="" method="GET" class="flex flex-wrap gap-4 items-center">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาวิดีโอ..."
                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all">
        </div>
        <div>
            <select name="status" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all">
                <option value="">ทุกสถานะ</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
            </select>
        </div>
        <div>
            <select name="sort" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all">
                <option value="published_at" {{ request('sort') === 'published_at' ? 'selected' : '' }}>วันที่เผยแพร่</option>
                <option value="view_count" {{ request('sort') === 'view_count' ? 'selected' : '' }}>ยอดวิว</option>
                <option value="like_count" {{ request('sort') === 'like_count' ? 'selected' : '' }}>ยอดไลค์</option>
                <option value="order" {{ request('sort') === 'order' ? 'selected' : '' }}>ลำดับ</option>
            </select>
        </div>
        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-xl hover:from-red-600 hover:to-rose-600 transition-all shadow-lg">
            ค้นหา
        </button>
        <a href="{{ route('admin.metal-x.videos.index') }}" class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
            รีเซ็ต
        </a>
    </form>
</div>

<!-- Videos Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @forelse($videos as $video)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden group hover:shadow-xl transition-all">
            <!-- Thumbnail -->
            <div class="relative aspect-video bg-gray-100 dark:bg-gray-900">
                @if($video->best_thumbnail)
                    <img src="{{ $video->best_thumbnail }}" alt="{{ $video->title }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @endif
                <div class="absolute bottom-2 right-2 bg-black/75 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-lg font-medium">
                    {{ $video->formatted_duration }}
                </div>
                @if($video->is_featured)
                    <div class="absolute top-2 left-2 bg-gradient-to-r from-yellow-500 to-amber-500 text-white text-xs px-2.5 py-1 rounded-lg font-medium shadow-lg">
                        แนะนำ
                    </div>
                @endif
                @if(!$video->is_active)
                    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center">
                        <span class="text-white font-semibold bg-gray-800/80 px-3 py-1 rounded-lg">ปิดใช้งาน</span>
                    </div>
                @endif
            </div>

            <!-- Content -->
            <div class="p-4">
                <h3 class="font-semibold text-gray-900 dark:text-white line-clamp-2 mb-2" title="{{ $video->title }}">
                    {{ $video->title }}
                </h3>
                @if($video->title_th)
                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-1 mb-2">{{ $video->title_th }}</p>
                @endif

                <!-- Stats -->
                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 space-x-4 mb-3">
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

                <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">
                    {{ $video->published_at?->format('d M Y') ?? 'ไม่ระบุวันที่' }}
                </p>

                <!-- Actions -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex space-x-1">
                        <a href="{{ $video->youtube_url }}" target="_blank"
                           class="p-2 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors" title="ดูบน YouTube">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                            </svg>
                        </a>
                        <a href="{{ route('admin.metal-x.videos.edit', $video) }}"
                           class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors" title="แก้ไข">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        @if($isApiConfigured)
                            <form action="{{ route('admin.metal-x.videos.sync', $video) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-2 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors" title="Sync จาก YouTube">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="flex space-x-1">
                        <form action="{{ route('admin.metal-x.videos.toggle-featured', $video) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="p-2 rounded-lg {{ $video->is_featured ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-500' : 'bg-gray-100 dark:bg-gray-700 text-gray-400' }} hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors" title="แนะนำ">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </button>
                        </form>
                        <form action="{{ route('admin.metal-x.videos.toggle', $video) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="p-2 rounded-lg {{ $video->is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-400' }} hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors" title="{{ $video->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </button>
                        </form>
                        <button type="button" onclick="confirmDelete({{ $video->id }}, '{{ addslashes($video->title) }}')"
                                class="p-2 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors" title="ลบ">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 py-16 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 mb-4">ไม่พบวิดีโอ</p>
            <a href="{{ route('admin.metal-x.videos.create') }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-xl hover:from-red-600 hover:to-rose-600 transition-all shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                เพิ่มวิดีโอ
            </a>
        </div>
    @endforelse
</div>

@if($videos->hasPages())
    <div class="mt-6">
        {{ $videos->links() }}
    </div>
@endif

<!-- Delete Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 border border-gray-200 dark:border-gray-700">
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="p-6">
                <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white text-center mb-2">ยืนยันการลบ</h3>
                <p class="text-gray-600 dark:text-gray-400 text-center">คุณต้องการลบวิดีโอ "<span id="deleteVideoTitle" class="font-medium text-gray-900 dark:text-white"></span>" หรือไม่?</p>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-center space-x-3 rounded-b-2xl">
                <button type="button" onclick="hideDeleteModal()"
                        class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">ยกเลิก</button>
                <button type="submit"
                        class="px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all shadow-lg">ลบ</button>
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
