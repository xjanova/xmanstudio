@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการ License')
@section('page-title', 'จัดการ License')

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-600 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-400/10 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                จัดการ License
            </h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">จัดการ License Keys ทั้งหมดในระบบ</p>
        </div>
        <a href="{{ route('admin.licenses.create') }}"
           class="inline-flex items-center px-4 py-2 bg-white text-purple-600 rounded-xl hover:bg-gray-100 transition-all font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            สร้าง License
        </a>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">ทั้งหมด</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
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
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Active</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['active'] }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute inset-0 bg-gradient-to-br from-purple-400/10 to-violet-600/10"></div>
        <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-400 to-violet-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Activated</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['activated'] }}</p>
        </div>
    </div>

    <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 to-orange-600/10"></div>
        <div class="relative p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Expired</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['expired'] }}</p>
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
            <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Revoked</h3>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['revoked'] }}</p>
        </div>
    </div>
</div>

<!-- Filters & Actions -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
    <form action="{{ route('admin.licenses.index') }}" method="GET" class="flex flex-wrap gap-4 items-center">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white"
                   placeholder="ค้นหา License Key...">
        </div>
        <div>
            <select name="status" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white">
                <option value="">ทุกสถานะ</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>Revoked</option>
            </select>
        </div>
        <div>
            <select name="type" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white">
                <option value="">ทุกประเภท</option>
                <option value="demo" {{ request('type') === 'demo' ? 'selected' : '' }}>Demo</option>
                <option value="monthly" {{ request('type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                <option value="yearly" {{ request('type') === 'yearly' ? 'selected' : '' }}>Yearly</option>
                <option value="lifetime" {{ request('type') === 'lifetime' ? 'selected' : '' }}>Lifetime</option>
            </select>
        </div>
        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-violet-600 text-white rounded-xl hover:from-purple-600 hover:to-violet-700 transition-all font-medium shadow-lg hover:shadow-xl transform hover:scale-105">
            ค้นหา
        </button>
    </form>
</div>

<!-- Licenses Table -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-750">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">License Key</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ประเภท</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">เครื่อง</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">หมดอายุ</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">การดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($licenses as $license)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-400 to-violet-600 flex items-center justify-center mr-4 shadow-md">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-mono font-medium text-gray-900 dark:text-white">{{ $license->license_key }}</div>
                                    @if($license->product)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $license->product->name }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-3 py-1.5 text-xs font-semibold rounded-full shadow-sm
                                @if($license->license_type === 'lifetime') bg-gradient-to-r from-purple-400 to-violet-500 text-white
                                @elseif($license->license_type === 'yearly') bg-gradient-to-r from-blue-400 to-indigo-500 text-white
                                @elseif($license->license_type === 'monthly') bg-gradient-to-r from-cyan-400 to-teal-500 text-white
                                @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 @endif">
                                {{ ucfirst($license->license_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-3 py-1.5 text-xs font-semibold rounded-full shadow-sm
                                @if($license->status === 'active' && !$license->isExpired()) bg-gradient-to-r from-green-400 to-emerald-500 text-white
                                @elseif($license->status === 'revoked') bg-gradient-to-r from-red-400 to-rose-500 text-white
                                @else bg-gradient-to-r from-amber-400 to-orange-500 text-white @endif">
                                @if($license->status === 'active' && $license->isExpired())
                                    Expired
                                @else
                                    {{ ucfirst($license->status) }}
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($license->machine_id)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300" title="{{ $license->machine_id }}">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Activated
                                </span>
                            @else
                                <span class="text-gray-400 dark:text-gray-500">Not activated</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            @if($license->license_type === 'lifetime')
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    ตลอดชีพ
                                </span>
                            @elseif($license->expires_at)
                                <span class="font-medium">{{ $license->expires_at->format('d/m/Y') }}</span>
                                @if(!$license->isExpired() && $license->daysRemaining() <= 7)
                                    <span class="ml-1 text-red-500 font-semibold">({{ $license->daysRemaining() }} วัน)</span>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center space-x-1">
                                <a href="{{ route('admin.licenses.show', $license) }}"
                                   class="p-2 text-gray-500 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/30 rounded-lg transition"
                                   title="ดูรายละเอียด">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>

                                @if($license->status === 'active')
                                    <button type="button" onclick="showRevokeModal({{ $license->id }}, '{{ $license->license_key }}')"
                                            class="p-2 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition"
                                            title="ยกเลิก License">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    </button>
                                @elseif($license->status === 'revoked')
                                    <form action="{{ route('admin.licenses.reactivate', $license) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="p-2 text-green-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition"
                                                title="เปิดใช้งานอีกครั้ง">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                @if($license->machine_id)
                                    <form action="{{ route('admin.licenses.reset-machine', $license) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="p-2 text-orange-500 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/30 rounded-lg transition"
                                                title="รีเซ็ตเครื่อง">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-purple-400 to-violet-600 rounded-full mb-4 shadow-xl">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </div>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">ไม่พบ License</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">เริ่มต้นสร้าง License แรกของคุณ</p>
                            <a href="{{ route('admin.licenses.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-500 to-violet-600 text-white rounded-xl hover:from-purple-600 hover:to-violet-700 transition-all font-medium shadow-lg">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                สร้าง License ใหม่
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($licenses->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            {{ $licenses->links() }}
        </div>
    @endif
</div>

<!-- Revoke Modal -->
<div id="revokeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
        <form id="revokeForm" method="POST">
            @csrf
            <div class="p-6">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 text-center">ยกเลิก License</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4 text-center">License: <span id="revokeLicenseKey" class="font-mono font-bold text-purple-600 dark:text-purple-400"></span></p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล (ถ้ามี)</label>
                    <textarea name="reason" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 flex justify-end space-x-3">
                <button type="button" onclick="hideRevokeModal()"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:from-red-600 hover:to-rose-700 font-medium shadow-lg transition transform hover:scale-105">ยืนยันยกเลิก</button>
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
    function showRevokeModal(licenseId, licenseKey) {
        document.getElementById('revokeForm').action = `/admin/licenses/${licenseId}/revoke`;
        document.getElementById('revokeLicenseKey').textContent = licenseKey;
        document.getElementById('revokeModal').classList.remove('hidden');
        document.getElementById('revokeModal').classList.add('flex');
    }

    function hideRevokeModal() {
        document.getElementById('revokeModal').classList.add('hidden');
        document.getElementById('revokeModal').classList.remove('flex');
    }
</script>
@endpush
@endsection
