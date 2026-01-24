@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการ Devices')
@section('page-title', 'จัดการ Devices')

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-600 via-teal-600 to-emerald-600 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-teal-400/10 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                จัดการ Devices
            </h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">ติดตามและจัดการ Devices ที่ลงทะเบียนทั้งหมดในระบบ</p>
        </div>
    </div>
</div>

<!-- Premium Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-indigo-600/10"></div>
        <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-400 to-indigo-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">ทั้งหมด</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute inset-0 bg-gradient-to-br from-green-400/10 to-emerald-600/10"></div>
        <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Licensed</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['licensed']) }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute inset-0 bg-gradient-to-br from-cyan-400/10 to-teal-600/10"></div>
        <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-cyan-400 to-teal-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Trial</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['trial']) }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute inset-0 bg-gradient-to-br from-red-400/10 to-rose-600/10"></div>
        <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Blocked</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['blocked']) }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 to-orange-600/10"></div>
        <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Suspicious</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['suspicious']) }}</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
    <form action="{{ route('admin.devices.index') }}" method="GET" class="flex flex-wrap gap-4 items-center">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-teal-500 focus:border-teal-500 dark:bg-gray-700 dark:text-white"
                   placeholder="ค้นหา Machine ID, ชื่อ, IP...">
        </div>
        <div>
            <select name="product_id" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-teal-500 focus:border-teal-500 dark:bg-gray-700 dark:text-white">
                <option value="">ทุกผลิตภัณฑ์</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="status" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-teal-500 focus:border-teal-500 dark:bg-gray-700 dark:text-white">
                <option value="">ทุกสถานะ</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Trial</option>
                <option value="licensed" {{ request('status') === 'licensed' ? 'selected' : '' }}>Licensed</option>
                <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Blocked</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="demo" {{ request('status') === 'demo' ? 'selected' : '' }}>Demo</option>
            </select>
        </div>
        <div class="flex items-center">
            <label class="inline-flex items-center cursor-pointer">
                <input type="checkbox" name="suspicious" value="1" {{ request('suspicious') ? 'checked' : '' }}
                       class="sr-only peer">
                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-500"></div>
                <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Suspicious เท่านั้น</span>
            </label>
        </div>
        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-teal-500 to-emerald-600 text-white rounded-xl hover:from-teal-600 hover:to-emerald-700 transition-all font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
            ค้นหา
        </button>
        @if(request()->hasAny(['search', 'product_id', 'status', 'suspicious']))
            <a href="{{ route('admin.devices.index') }}" class="px-4 py-2.5 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                ล้างตัวกรอง
            </a>
        @endif
    </form>
</div>

<!-- Devices Table -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Device</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ผลิตภัณฑ์</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">IP</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Trial</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">เห็นล่าสุด</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">การดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($devices as $device)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors {{ $device->is_suspicious ? 'bg-amber-50 dark:bg-amber-900/10' : '' }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-400 to-teal-600 flex items-center justify-center mr-4 shadow-md">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $device->machine_name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ Str::limit($device->machine_id, 20) }}</div>
                                    @if($device->is_suspicious)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            Suspicious
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($device->product)
                                <span class="text-sm text-gray-900 dark:text-white">{{ $device->product->name }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-3 py-1.5 text-xs font-semibold rounded-full {{ $device->getStatusBadgeClass() }}">
                                {{ $device->getStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $device->last_ip ?? $device->first_ip ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($device->status === 'trial' && $device->trial_expires_at)
                                @if($device->isTrialExpired())
                                    <span class="text-red-600 dark:text-red-400">หมดอายุ</span>
                                @else
                                    <span class="text-green-600 dark:text-green-400">{{ $device->trialDaysRemaining() }} วัน</span>
                                @endif
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $device->last_seen_at ? $device->last_seen_at->diffForHumans() : '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-1">
                                <a href="{{ route('admin.devices.show', $device) }}"
                                   class="p-2 text-gray-500 hover:text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-900/30 rounded-lg transition"
                                   title="ดูรายละเอียด">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>

                                @if($device->status !== 'blocked')
                                    <button type="button" onclick="showBlockModal({{ $device->id }}, '{{ $device->machine_name ?? $device->machine_id }}')"
                                            class="p-2 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition"
                                            title="บล็อก">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </button>
                                @else
                                    <form action="{{ route('admin.devices.unblock', $device) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="p-2 text-green-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition"
                                                title="ปลดบล็อก">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-cyan-400 to-teal-600 rounded-full mb-4 shadow-xl">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">ไม่พบ Device</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ยังไม่มี Device ที่ลงทะเบียนในระบบ</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($devices->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            {{ $devices->links() }}
        </div>
    @endif
</div>

<!-- Block Modal -->
<div id="blockModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
        <form id="blockForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 text-center">บล็อก Device</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4 text-center">Device: <span id="blockDeviceName" class="font-bold text-red-600 dark:text-red-400"></span></p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white"
                              placeholder="ระบุเหตุผลในการบล็อก..."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 flex justify-end space-x-3">
                <button type="button" onclick="hideBlockModal()"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:from-red-600 hover:to-rose-700 font-medium shadow-lg transition transform hover:scale-105">ยืนยันบล็อก</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<style>
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
}
.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
</style>
<script>
    function showBlockModal(deviceId, deviceName) {
        document.getElementById('blockForm').action = `/admin/devices/${deviceId}/block`;
        document.getElementById('blockDeviceName').textContent = deviceName;
        document.getElementById('blockModal').classList.remove('hidden');
        document.getElementById('blockModal').classList.add('flex');
    }

    function hideBlockModal() {
        document.getElementById('blockModal').classList.add('hidden');
        document.getElementById('blockModal').classList.remove('flex');
    }
</script>
@endpush
@endsection
