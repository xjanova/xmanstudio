@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Google Ads Management')
@section('page-title', 'Google Ads Management')

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
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-green-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-emerald-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-teal-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">จัดการตำแหน่งโฆษณา</h1>
                    <p class="text-green-100 text-lg">จัดการ Google AdSense และตำแหน่งโฆษณาทั้งหมด</p>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="{{ route('admin.ads.create') }}" class="px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-300 flex items-center font-semibold border border-white/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        เพิ่มตำแหน่งโฆษณา
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @php
            $totalPlacements = $placements->count();
            $activePlacements = $placements->where('enabled', true)->count();
            $inactivePlacements = $placements->where('enabled', false)->count();
        @endphp

        <!-- Total -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ตำแหน่งทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $totalPlacements }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">เปิดใช้งาน</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $activePlacements }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-400 to-cyan-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Inactive -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ปิดใช้งาน</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $inactivePlacements }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-gray-400 to-gray-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Ad Placements Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">ตำแหน่งโฆษณาทั้งหมด</h3>
                <a href="{{ route('admin.ads.create') }}" class="md:hidden px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all duration-300 flex items-center text-sm font-medium shadow-lg">
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
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ชื่อ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ตำแหน่ง</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">หน้าที่แสดง</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($placements as $placement)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $placement->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $placement->slug }}</div>
                                    @if($placement->description)
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $placement->description }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1.5 inline-flex text-xs font-semibold rounded-full shadow-sm
                                    {{ $placement->position === 'header' ? 'bg-gradient-to-r from-purple-400 to-purple-600 text-white' : '' }}
                                    {{ $placement->position === 'sidebar' ? 'bg-gradient-to-r from-blue-400 to-blue-600 text-white' : '' }}
                                    {{ $placement->position === 'in-content' ? 'bg-gradient-to-r from-green-400 to-green-600 text-white' : '' }}
                                    {{ $placement->position === 'footer' ? 'bg-gradient-to-r from-gray-400 to-gray-600 text-white' : '' }}
                                    {{ $placement->position === 'between-products' ? 'bg-gradient-to-r from-yellow-400 to-amber-600 text-white' : '' }}">
                                    {{ ucfirst($placement->position) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @if($placement->pages && in_array('all', $placement->pages))
                                        <span class="px-2 py-1 bg-gradient-to-r from-blue-400 to-indigo-500 text-white rounded-lg text-xs font-medium shadow-sm">ทุกหน้า</span>
                                    @else
                                        @foreach($placement->pages ?? [] as $page)
                                            <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-xs">{{ $page }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 text-gray-700 dark:text-gray-300 font-semibold text-sm">
                                    {{ $placement->priority }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form action="{{ route('admin.ads.toggle', $placement) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="relative inline-flex items-center cursor-pointer group">
                                        <div class="w-11 h-6 {{ $placement->enabled ? 'bg-gradient-to-r from-green-400 to-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }} rounded-full transition-all duration-300 shadow-inner">
                                            <div class="absolute top-[2px] left-[2px] bg-white rounded-full h-5 w-5 transition-all duration-300 shadow {{ $placement->enabled ? 'translate-x-full' : '' }}"></div>
                                        </div>
                                        <span class="ml-2 text-sm font-medium {{ $placement->enabled ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                            {{ $placement->enabled ? 'เปิด' : 'ปิด' }}
                                        </span>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.ads.edit', $placement) }}" class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors duration-200" title="แก้ไข">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.ads.destroy', $placement) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่?')">
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
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-green-100 to-emerald-200 dark:from-green-900/30 dark:to-emerald-900/30 flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium mb-2">ไม่มีตำแหน่งโฆษณา</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mb-4">เริ่มต้นด้วยการสร้างตำแหน่งโฆษณาแรก</p>
                                    <a href="{{ route('admin.ads.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 transition-all duration-300 font-medium shadow-lg hover:shadow-xl">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        เพิ่มตำแหน่งโฆษณา
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Position Guide -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">คำแนะนำตำแหน่งโฆษณา</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="group bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-4 hover:shadow-lg transition-all duration-300">
                <div class="flex items-center mb-3">
                    <span class="px-3 py-1.5 bg-gradient-to-r from-purple-400 to-purple-600 text-white rounded-lg text-xs font-semibold mr-3 shadow-sm">HEADER</span>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Header Top</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">แสดงที่ด้านบนสุดของเว็บไซต์ ก่อน navigation bar - เหมาะกับ banner ads</p>
            </div>

            <div class="group bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 hover:shadow-lg transition-all duration-300">
                <div class="flex items-center mb-3">
                    <span class="px-3 py-1.5 bg-gradient-to-r from-blue-400 to-blue-600 text-white rounded-lg text-xs font-semibold mr-3 shadow-sm">SIDEBAR</span>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Sidebar</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">แสดงที่ด้านข้างของเนื้อหา - เหมาะกับ skyscraper ads (160x600)</p>
            </div>

            <div class="group bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4 hover:shadow-lg transition-all duration-300">
                <div class="flex items-center mb-3">
                    <span class="px-3 py-1.5 bg-gradient-to-r from-green-400 to-green-600 text-white rounded-lg text-xs font-semibold mr-3 shadow-sm">IN-CONTENT</span>
                    <h4 class="font-semibold text-gray-900 dark:text-white">In Content</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">แสดงระหว่างเนื้อหา ตรงกลางหน้า - มี engagement สูง</p>
            </div>

            <div class="group bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-900/20 dark:to-slate-900/20 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-lg transition-all duration-300">
                <div class="flex items-center mb-3">
                    <span class="px-3 py-1.5 bg-gradient-to-r from-gray-400 to-gray-600 text-white rounded-lg text-xs font-semibold mr-3 shadow-sm">FOOTER</span>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Footer Above</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">แสดงก่อน footer ด้านล่างของหน้า</p>
            </div>

            <div class="group bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 hover:shadow-lg transition-all duration-300">
                <div class="flex items-center mb-3">
                    <span class="px-3 py-1.5 bg-gradient-to-r from-yellow-400 to-amber-600 text-white rounded-lg text-xs font-semibold mr-3 shadow-sm">BETWEEN</span>
                    <h4 class="font-semibold text-gray-900 dark:text-white">Between Products</h4>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">แสดงระหว่างรายการสินค้า/บริการ - native ads</p>
            </div>
        </div>
    </div>
</div>
@endsection
