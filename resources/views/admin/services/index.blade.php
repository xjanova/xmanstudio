@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการบริการ')
@section('page-title', 'จัดการบริการ')

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
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-4000"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-white mb-2">จัดการบริการ</h1>
                    <p class="text-purple-100 text-lg">จัดการรายการบริการทั้งหมดของบริษัท</p>
                </div>
                <div class="hidden md:flex items-center space-x-4">
                    <a href="{{ route('admin.services.create') }}" class="px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-300 flex items-center font-semibold border border-white/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        เพิ่มบริการใหม่
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        @php
            $totalServices = $services->count();
            $activeServices = $services->where('is_active', true)->count();
            $featuredServices = $services->where('is_featured', true)->count();
            $comingSoonServices = $services->where('is_coming_soon', true)->count();
        @endphp

        <!-- Total Services -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">บริการทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $totalServices }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-400 to-purple-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Services -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">เปิดใช้งาน</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $activeServices }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Featured Services -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">แนะนำ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $featuredServices }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-yellow-400 to-amber-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Coming Soon -->
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Coming Soon</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ $comingSoonServices }}</p>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-400 to-red-600 flex items-center justify-center transform group-hover:scale-110 transition-transform duration-300 shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Services Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">รายการบริการ</h3>
                <a href="{{ route('admin.services.create') }}" class="md:hidden px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:from-indigo-600 hover:to-purple-700 transition-all duration-300 flex items-center text-sm font-medium shadow-lg">
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
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ลำดับ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">บริการ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ราคาเริ่มต้น</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($services as $service)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 text-gray-700 dark:text-gray-300 font-semibold text-sm">
                                    {{ $service->order }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    @if($service->icon)
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 flex items-center justify-center mr-4">
                                            <span class="text-2xl">{{ $service->icon }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $service->name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($service->description, 50) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                    {{ $service->formatted_price }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex px-3 py-1.5 text-xs font-semibold rounded-full shadow-sm
                                        {{ $service->is_active ? 'bg-gradient-to-r from-green-400 to-emerald-500 text-white' : 'bg-gradient-to-r from-gray-400 to-gray-500 text-white' }}">
                                        {{ $service->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                                    </span>
                                    @if($service->is_featured)
                                        <span class="inline-flex px-3 py-1.5 text-xs font-semibold rounded-full bg-gradient-to-r from-yellow-400 to-amber-500 text-white shadow-sm">
                                            แนะนำ
                                        </span>
                                    @endif
                                    @if($service->is_coming_soon)
                                        <span class="inline-flex px-3 py-1.5 text-xs font-semibold rounded-full bg-gradient-to-r from-orange-400 to-red-500 text-white shadow-sm">
                                            Coming Soon
                                            @if($service->coming_soon_until)
                                                <span class="ml-1">({{ $service->coming_soon_until->format('d/m') }})</span>
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-1">
                                    <a href="{{ route('admin.services.edit', $service) }}"
                                       class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors duration-200" title="แก้ไข">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.services.toggle-coming-soon', $service) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 {{ $service->is_coming_soon ? 'text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20' : 'text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }} rounded-lg transition-colors duration-200" title="{{ $service->is_coming_soon ? 'ปิด Coming Soon' : 'เปิด Coming Soon' }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.services.toggle', $service) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 {{ $service->is_active ? 'text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20' : 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20' }} rounded-lg transition-colors duration-200" title="{{ $service->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                            @if($service->is_active)
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                    <button type="button" onclick="confirmDelete({{ $service->id }}, '{{ $service->name }}')"
                                            class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors duration-200" title="ลบ">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-indigo-100 to-purple-200 dark:from-indigo-900/30 dark:to-purple-900/30 flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium mb-2">ไม่พบข้อมูลบริการ</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mb-4">เริ่มต้นด้วยการสร้างบริการแรกของคุณ</p>
                                    <a href="{{ route('admin.services.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl hover:from-indigo-600 hover:to-purple-700 transition-all duration-300 font-medium shadow-lg hover:shadow-xl">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                        </svg>
                                        เพิ่มบริการใหม่
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($services->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="p-6">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-red-100 to-rose-200 dark:from-red-900/30 dark:to-rose-900/30 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 text-center">ยืนยันการลบ</h3>
                <p class="text-gray-600 dark:text-gray-400 text-center">คุณต้องการลบบริการ "<span id="deleteServiceName" class="font-semibold text-gray-900 dark:text-white"></span>" หรือไม่?</p>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-center space-x-3 rounded-b-2xl">
                <button type="button" onclick="hideDeleteModal()"
                        class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 font-medium">ยกเลิก</button>
                <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:from-red-600 hover:to-rose-700 transition-all duration-200 font-medium shadow-lg">ลบบริการ</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function confirmDelete(serviceId, serviceName) {
        document.getElementById('deleteForm').action = `/admin/services/${serviceId}`;
        document.getElementById('deleteServiceName').textContent = serviceName;
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
