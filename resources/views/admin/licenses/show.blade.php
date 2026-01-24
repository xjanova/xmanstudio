@extends($adminLayout ?? 'layouts.admin')

@section('title', 'รายละเอียด License')
@section('page-title', 'รายละเอียด License')

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-purple-600 via-violet-600 to-indigo-600 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/5 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-400/10 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-white/20 text-white backdrop-blur-sm">
                    {{ ucfirst($license->license_type) }}
                </span>
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                    @if($license->isValid()) bg-green-400/30 text-green-100
                    @elseif($license->status === 'revoked') bg-red-400/30 text-red-100
                    @else bg-amber-400/30 text-amber-100 @endif backdrop-blur-sm">
                    @if($license->isValid()) Active
                    @elseif($license->status === 'revoked') Revoked
                    @else Expired @endif
                </span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-xl sm:text-2xl font-mono font-bold text-white" id="licenseKey">{{ $license->license_key }}</h1>
                <button onclick="copyLicenseKey()" class="p-2 bg-white/20 hover:bg-white/30 rounded-lg transition-all" title="คัดลอก">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
            @if($license->product)
                <p class="mt-2 text-white/80 text-sm">{{ $license->product->name }}</p>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            @if($license->status === 'active')
                <button onclick="showRevokeModal()"
                        class="inline-flex items-center px-4 py-2 bg-red-500/80 text-white rounded-xl hover:bg-red-500 transition-all font-medium backdrop-blur-sm">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    ยกเลิก
                </button>
            @elseif($license->status === 'revoked')
                <form action="{{ route('admin.licenses.reactivate', $license) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-green-500/80 text-white rounded-xl hover:bg-green-500 transition-all font-medium backdrop-blur-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        เปิดใช้งานใหม่
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.licenses.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white/20 text-white rounded-xl hover:bg-white/30 transition-all font-medium backdrop-blur-sm">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info Column -->
    <div class="lg:col-span-2 space-y-6">
        <!-- License Info Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ข้อมูล License
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">สร้างเมื่อ</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $license->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @if($license->product)
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">ผลิตภัณฑ์</span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                            {{ $license->product->name }}
                        </span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">ประเภท</span>
                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full
                            @if($license->license_type === 'lifetime') bg-gradient-to-r from-purple-400 to-violet-500 text-white
                            @elseif($license->license_type === 'yearly') bg-gradient-to-r from-blue-400 to-indigo-500 text-white
                            @elseif($license->license_type === 'monthly') bg-gradient-to-r from-cyan-400 to-teal-500 text-white
                            @else bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 @endif">
                            {{ ucfirst($license->license_type) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-3">
                        <span class="text-gray-500 dark:text-gray-400">Activation</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $license->activations ?? 0 }} / {{ $license->max_activations ?? 1 }}</span>
                    </div>
                </div>

                <div class="space-y-4">
                    @if($license->activated_at)
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">เปิดใช้งานเมื่อ</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $license->activated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">หมดอายุ</span>
                        @if($license->license_type === 'lifetime')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                ตลอดชีพ
                            </span>
                        @elseif($license->expires_at)
                            <span class="text-gray-900 dark:text-white font-medium">{{ $license->expires_at->format('d/m/Y H:i') }}</span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </div>
                    @if($license->expires_at && $license->license_type !== 'lifetime')
                    <div class="flex justify-between items-center py-3 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">เหลือ</span>
                        @if($license->isExpired())
                            <span class="text-red-600 font-medium">หมดอายุแล้ว</span>
                        @else
                            <span class="text-gray-900 dark:text-white font-medium {{ $license->daysRemaining() <= 7 ? 'text-red-500' : '' }}">
                                {{ $license->daysRemaining() }} วัน
                            </span>
                        @endif
                    </div>
                    @endif
                    @if($license->last_validated_at)
                    <div class="flex justify-between items-center py-3">
                        <span class="text-gray-500 dark:text-gray-400">ตรวจสอบล่าสุด</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $license->last_validated_at->format('d/m/Y H:i') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Machine Info Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    ข้อมูลเครื่อง
                </h3>
                @if($license->machine_id)
                    <form action="{{ route('admin.licenses.reset-machine', $license) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-orange-600 hover:text-orange-700 hover:bg-orange-50 dark:hover:bg-orange-900/30 rounded-xl transition font-medium">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            รีเซ็ตเครื่อง
                        </button>
                    </form>
                @endif
            </div>

            @if($license->machine_id)
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Activated</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">License กำลังใช้งานบนเครื่องนี้</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Machine ID</span>
                            <code class="font-mono text-gray-900 dark:text-white bg-gray-200 dark:bg-gray-600 px-2 py-0.5 rounded text-xs">{{ $license->machine_id }}</code>
                        </div>
                        @if($license->machine_fingerprint)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Fingerprint</span>
                            <code class="font-mono text-gray-900 dark:text-white bg-gray-200 dark:bg-gray-600 px-2 py-0.5 rounded text-xs">{{ Str::limit($license->machine_fingerprint, 20) }}</code>
                        </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่ได้เปิดใช้งานบนเครื่องใด</p>
                </div>
            @endif
        </div>

        <!-- Extend License Card -->
        @if($license->license_type !== 'lifetime' && $license->status !== 'revoked')
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ขยายเวลา License
            </h3>
            <form action="{{ route('admin.licenses.extend', $license) }}" method="POST">
                @csrf
                <div class="grid grid-cols-4 gap-2 mb-4">
                    <button type="button" onclick="setExtendDays(7)" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/30 text-sm font-medium transition">7 วัน</button>
                    <button type="button" onclick="setExtendDays(30)" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/30 text-sm font-medium transition">30 วัน</button>
                    <button type="button" onclick="setExtendDays(90)" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/30 text-sm font-medium transition">90 วัน</button>
                    <button type="button" onclick="setExtendDays(365)" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/30 text-sm font-medium transition">1 ปี</button>
                </div>
                <div class="flex gap-4">
                    <input type="number" name="days" id="extendDays" min="1" max="365" value="30" required
                           class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white">
                    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl hover:from-blue-600 hover:to-indigo-700 font-medium shadow-lg transition transform hover:scale-105">
                        ขยายเวลา
                    </button>
                </div>
            </form>
        </div>
        @endif

        <!-- Activity Timeline -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ประวัติกิจกรรม
            </h3>
            <div id="activityTimeline" class="space-y-4 max-h-96 overflow-y-auto">
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    กำลังโหลด...
                </div>
            </div>
        </div>
    </div>

    <!-- Right Sidebar -->
    <div class="space-y-6">
        <!-- QR Code Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                QR Code
            </h3>
            <div class="text-center">
                <div id="qrcode" class="inline-block p-4 bg-white rounded-xl shadow-inner"></div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">สแกนเพื่อคัดลอก License Key</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Quick Actions
            </h3>
            <div class="space-y-2">
                <button onclick="copyLicenseKey()" class="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">คัดลอก License Key</span>
                </button>
                <a href="{{ route('admin.licenses.analytics') }}" class="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">ดู Analytics</span>
                </a>
                <form action="{{ route('admin.licenses.destroy', $license) }}" method="POST" onsubmit="return confirm('คุณต้องการลบ License นี้หรือไม่?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-xl transition text-red-600 dark:text-red-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span>ลบ License</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Metadata -->
        @if($license->metadata)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
                Metadata
            </h3>
            <pre class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl text-xs overflow-auto max-h-48">{{ json_encode($license->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        @endif
    </div>
</div>

<!-- Revoke Modal -->
<div id="revokeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
        <form action="{{ route('admin.licenses.revoke', $license) }}" method="POST">
            @csrf
            <div class="p-6">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 text-center">ยกเลิก License</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4 text-center">คุณต้องการยกเลิก License นี้หรือไม่?</p>
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
                        class="px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:from-red-600 hover:to-rose-700 font-medium shadow-lg transition">ยืนยัน</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-xl shadow-lg transform translate-y-20 opacity-0 transition-all duration-300 z-50">
    <div class="flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span id="toastMessage">คัดลอกแล้ว!</span>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
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
    // Generate QR Code
    document.addEventListener('DOMContentLoaded', function() {
        QRCode.toCanvas(document.createElement('canvas'), '{{ $license->license_key }}', {
            width: 150,
            margin: 1,
            color: { dark: '#6366f1', light: '#ffffff' }
        }, function(error, canvas) {
            if (error) console.error(error);
            document.getElementById('qrcode').appendChild(canvas);
        });

        // Load activity timeline
        loadActivityTimeline();
    });

    function loadActivityTimeline() {
        fetch('{{ route("admin.licenses.analytics.activity") }}?license_id={{ $license->id }}')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('activityTimeline');
                if (data.length === 0) {
                    container.innerHTML = '<div class="text-center py-8 text-gray-500 dark:text-gray-400">ไม่มีประวัติกิจกรรม</div>';
                    return;
                }

                container.innerHTML = data.map(activity => `
                    <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br ${activity.action_color} flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${activity.action_icon}"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">${activity.action_label}</p>
                            ${activity.ip_address ? `<p class="text-xs text-gray-500 dark:text-gray-400">IP: ${activity.ip_address}</p>` : ''}
                            ${activity.notes ? `<p class="text-xs text-gray-500 dark:text-gray-400">${activity.notes}</p>` : ''}
                            <p class="text-xs text-gray-400 mt-1" title="${activity.created_at_full}">${activity.created_at}</p>
                        </div>
                    </div>
                `).join('');
            })
            .catch(error => {
                console.error('Error loading activities:', error);
                document.getElementById('activityTimeline').innerHTML = '<div class="text-center py-8 text-gray-500 dark:text-gray-400">ไม่สามารถโหลดข้อมูลได้</div>';
            });
    }

    function copyLicenseKey() {
        const licenseKey = '{{ $license->license_key }}';
        navigator.clipboard.writeText(licenseKey).then(() => {
            showToast('คัดลอก License Key แล้ว!');
        }).catch(() => {
            // Fallback
            const textArea = document.createElement('textarea');
            textArea.value = licenseKey;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showToast('คัดลอก License Key แล้ว!');
        });
    }

    function showToast(message) {
        const toast = document.getElementById('toast');
        document.getElementById('toastMessage').textContent = message;
        toast.classList.remove('translate-y-20', 'opacity-0');
        setTimeout(() => {
            toast.classList.add('translate-y-20', 'opacity-0');
        }, 3000);
    }

    function setExtendDays(days) {
        document.getElementById('extendDays').value = days;
    }

    function showRevokeModal() {
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
