@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Banner Management')
@section('page-title', 'Banner Management')

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
<div class="space-y-6">
    <!-- Premium Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-yellow-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-orange-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-red-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">จัดการแบนเนอร์</h1>
                    <p class="text-amber-100 text-lg">อัปโหลดและจัดการแบนเนอร์โฆษณาทั้งหมด</p>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="{{ route('admin.banners.create') }}" class="px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-300 flex items-center font-semibold border border-white/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        เพิ่มแบนเนอร์ใหม่
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl flex items-center shadow-lg">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-emerald-500 flex items-center justify-center mr-4">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        @php
            $totalBanners = $banners->count();
            $activeBanners = $banners->where('status_text', 'กำลังแสดง')->count();
            $totalViews = $banners->sum('views');
            $totalClicks = $banners->sum('clicks');
        @endphp

        <!-- Total Banners -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">แบนเนอร์ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $totalBanners }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Banners -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">กำลังแสดง</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $activeBanners }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Views -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Views ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($totalViews) }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Clicks -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Clicks ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($totalClicks) }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-400 to-pink-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Banners Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">รายการแบนเนอร์</h3>
                <a href="{{ route('admin.banners.create') }}" class="md:hidden px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl hover:from-amber-600 hover:to-orange-700 transition-all duration-300 flex items-center text-sm font-medium shadow-lg">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    เพิ่ม
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">แบนเนอร์</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ตำแหน่ง</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">หน้าที่แสดง</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ระยะเวลา</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถิติ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($banners as $banner)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="relative group">
                                        <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" class="h-16 w-auto object-contain rounded-lg shadow-md group-hover:shadow-xl transition-shadow duration-300">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $banner->title }}</div>
                                        @if ($banner->description)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($banner->description, 50) }}</div>
                                        @endif
                                        @if ($banner->link_url)
                                            <a href="{{ $banner->link_url }}" target="_blank" class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-1 block">
                                                {{ Str::limit($banner->link_url, 40) }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1.5 inline-flex text-xs font-semibold rounded-full shadow-sm
                                    {{ $banner->position === 'header' ? 'bg-gradient-to-r from-purple-400 to-purple-600 text-white' : '' }}
                                    {{ $banner->position === 'sidebar' ? 'bg-gradient-to-r from-blue-400 to-blue-600 text-white' : '' }}
                                    {{ $banner->position === 'in-content' ? 'bg-gradient-to-r from-green-400 to-green-600 text-white' : '' }}
                                    {{ $banner->position === 'footer' ? 'bg-gradient-to-r from-gray-400 to-gray-600 text-white' : '' }}
                                    {{ $banner->position === 'between-products' ? 'bg-gradient-to-r from-yellow-400 to-amber-600 text-white' : '' }}">
                                    {{ ucfirst($banner->position) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @if ($banner->pages && in_array('all', $banner->pages))
                                        <span class="px-2 py-1 bg-gradient-to-r from-blue-400 to-indigo-500 text-white rounded-lg text-xs font-medium shadow-sm">ทุกหน้า</span>
                                    @else
                                        @foreach ($banner->pages ?? [] as $page)
                                            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-xs">{{ $page }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                @if ($banner->start_date)
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $banner->start_date->format('d/m/Y H:i') }}
                                    </div>
                                @else
                                    <div class="text-gray-400">ไม่กำหนด</div>
                                @endif
                                @if ($banner->end_date)
                                    <div class="flex items-center mt-1">
                                        <svg class="w-4 h-4 mr-1 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        {{ $banner->end_date->format('d/m/Y H:i') }}
                                    </div>
                                @else
                                    <div class="text-gray-400 mt-1">ไม่กำหนด</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="space-y-1">
                                    <div class="flex items-center text-gray-700 dark:text-gray-300">
                                        <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        {{ number_format($banner->views) }}
                                    </div>
                                    <div class="flex items-center text-gray-700 dark:text-gray-300">
                                        <svg class="w-4 h-4 mr-1 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                                        </svg>
                                        {{ number_format($banner->clicks) }}
                                    </div>
                                    @if ($banner->views > 0)
                                        <div class="text-xs font-medium px-2 py-0.5 rounded-full bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-700 dark:text-green-400 inline-block">
                                            CTR: {{ $banner->ctr }}%
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="mb-3">
                                    <span class="px-3 py-1.5 text-xs font-semibold rounded-full shadow-sm
                                        {{ $banner->status_text === 'กำลังแสดง' ? 'bg-gradient-to-r from-green-400 to-emerald-500 text-white' : '' }}
                                        {{ $banner->status_text === 'รอเริ่ม' ? 'bg-gradient-to-r from-yellow-400 to-amber-500 text-white' : '' }}
                                        {{ $banner->status_text === 'หมดอายุ' ? 'bg-gradient-to-r from-red-400 to-rose-500 text-white' : '' }}
                                        {{ $banner->status_text === 'ปิดใช้งาน' ? 'bg-gradient-to-r from-gray-400 to-gray-500 text-white' : '' }}">
                                        {{ $banner->status_text }}
                                    </span>
                                </div>
                                <form action="{{ route('admin.banners.toggle', $banner) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="relative inline-flex items-center cursor-pointer group">
                                        <div class="w-11 h-6 {{ $banner->enabled ? 'bg-gradient-to-r from-green-400 to-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }} rounded-full transition-all duration-300 shadow-inner">
                                            <div class="absolute top-[2px] left-[2px] bg-white rounded-full h-5 w-5 transition-all duration-300 shadow {{ $banner->enabled ? 'translate-x-full' : '' }}"></div>
                                        </div>
                                        <span class="ml-2 text-sm font-medium {{ $banner->enabled ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                            {{ $banner->enabled ? 'เปิด' : 'ปิด' }}
                                        </span>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.banners.edit', $banner) }}" class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors duration-200" title="แก้ไข">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors duration-200" title="ลบ">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-amber-100 to-orange-200 dark:from-amber-900/30 dark:to-orange-900/30 flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium mb-2">ยังไม่มีแบนเนอร์</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mb-4">สร้างแบนเนอร์แรกของคุณเพื่อเริ่มต้น</p>
                                    <a href="{{ route('admin.banners.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl hover:from-amber-600 hover:to-orange-700 transition-all duration-300 font-medium shadow-lg hover:shadow-xl">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        สร้างแบนเนอร์แรก
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
