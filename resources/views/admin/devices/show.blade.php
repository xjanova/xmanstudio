@extends($adminLayout ?? 'layouts.admin')

@section('title', 'รายละเอียด Device')
@section('page-title', 'รายละเอียด Device')

@section('content')
<div class="max-w-6xl">
    <!-- Back Button & Header -->
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('admin.devices.index') }}" class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            กลับไปรายการ Device
        </a>
        @if($device->status !== 'blocked')
            <button type="button" onclick="showBlockModal()"
                    class="px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:from-red-600 hover:to-rose-700 font-medium shadow-lg transition">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                บล็อก Device
            </button>
        @else
            <form action="{{ route('admin.devices.unblock', $device) }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl hover:from-green-600 hover:to-emerald-700 font-medium shadow-lg transition">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ปลดบล็อก
                </button>
            </form>
        @endif
    </div>

    <!-- Main Info Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
            <div class="flex items-start space-x-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-cyan-400 to-teal-600 flex items-center justify-center shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $device->machine_name ?? 'Unknown Device' }}</h2>
                    <p class="text-sm font-mono text-gray-500 dark:text-gray-400 mt-1">{{ $device->machine_id }}</p>
                    <div class="flex items-center space-x-2 mt-3">
                        <span class="inline-flex px-3 py-1.5 text-sm font-semibold rounded-full {{ $device->getStatusBadgeClass() }}">
                            {{ $device->getStatusLabel() }}
                        </span>
                        @if($device->is_suspicious)
                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Suspicious
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="text-right">
                @if($device->product)
                    <p class="text-sm text-gray-500 dark:text-gray-400">ผลิตภัณฑ์</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $device->product->name }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Device Information -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ข้อมูล Device
            </h3>
            <dl class="space-y-4">
                <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                    <dt class="text-gray-500 dark:text-gray-400">OS Version</dt>
                    <dd class="text-gray-900 dark:text-white font-medium">{{ $device->os_version ?? '-' }}</dd>
                </div>
                <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                    <dt class="text-gray-500 dark:text-gray-400">App Version</dt>
                    <dd class="text-gray-900 dark:text-white font-medium">{{ $device->app_version ?? '-' }}</dd>
                </div>
                <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                    <dt class="text-gray-500 dark:text-gray-400">Hardware Hash</dt>
                    <dd class="text-gray-900 dark:text-white font-mono text-sm">{{ Str::limit($device->hardware_hash, 20) ?? '-' }}</dd>
                </div>
                <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                    <dt class="text-gray-500 dark:text-gray-400">First IP</dt>
                    <dd class="text-gray-900 dark:text-white font-mono">{{ $device->first_ip ?? '-' }}</dd>
                </div>
                <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                    <dt class="text-gray-500 dark:text-gray-400">Last IP</dt>
                    <dd class="text-gray-900 dark:text-white font-mono">{{ $device->last_ip ?? '-' }}</dd>
                </div>
                <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                    <dt class="text-gray-500 dark:text-gray-400">เห็นครั้งแรก</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $device->first_seen_at ? $device->first_seen_at->format('d/m/Y H:i') : '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">เห็นล่าสุด</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $device->last_seen_at ? $device->last_seen_at->format('d/m/Y H:i') : '-' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Trial Information -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                ข้อมูล Trial
            </h3>
            <dl class="space-y-4">
                <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                    <dt class="text-gray-500 dark:text-gray-400">Trial Attempts</dt>
                    <dd class="text-gray-900 dark:text-white font-medium">{{ $device->trial_attempts }} / 3</dd>
                </div>
                <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                    <dt class="text-gray-500 dark:text-gray-400">Trial แรก</dt>
                    <dd class="text-gray-900 dark:text-white">{{ $device->first_trial_at ? $device->first_trial_at->format('d/m/Y H:i') : '-' }}</dd>
                </div>
                <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                    <dt class="text-gray-500 dark:text-gray-400">Trial หมดอายุ</dt>
                    <dd class="text-gray-900 dark:text-white">
                        @if($device->trial_expires_at)
                            {{ $device->trial_expires_at->format('d/m/Y H:i') }}
                            @if($device->isTrialExpired())
                                <span class="text-red-500 text-sm">(หมดอายุ)</span>
                            @else
                                <span class="text-green-500 text-sm">(เหลือ {{ $device->trialDaysRemaining() }} วัน)</span>
                            @endif
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between border-b border-gray-100 dark:border-gray-700 pb-2">
                    <dt class="text-gray-500 dark:text-gray-400">Early Bird</dt>
                    <dd class="text-gray-900 dark:text-white">
                        @if($device->early_bird_used)
                            <span class="text-purple-600 dark:text-purple-400">ใช้แล้ว ({{ $device->early_bird_used_at->format('d/m/Y') }})</span>
                        @elseif($device->isEligibleForEarlyBird())
                            <span class="text-green-600 dark:text-green-400">สามารถใช้ได้</span>
                        @else
                            <span class="text-gray-400">ไม่มีสิทธิ์</span>
                        @endif
                    </dd>
                </div>
            </dl>

            <!-- Reset Trial Form -->
            @if($device->status !== 'blocked')
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">รีเซ็ต Trial</h4>
                <form action="{{ route('admin.devices.reset-trial', $device) }}" method="POST" class="flex items-end space-x-3">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">จำนวนวัน</label>
                        <input type="number" name="days" min="1" max="30" value="7"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-teal-500 focus:border-teal-500 dark:bg-gray-700 dark:text-white text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 text-sm font-medium transition">
                        รีเซ็ต
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

    <!-- License Information -->
    @if($device->license)
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            License ที่เชื่อมต่อ
        </h3>
        <div class="flex items-center justify-between">
            <div>
                <p class="font-mono text-lg text-gray-900 dark:text-white">{{ $device->license->license_key }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ ucfirst($device->license->license_type) }} | {{ $device->license->status }}</p>
            </div>
            <a href="{{ route('admin.licenses.show', $device->license) }}"
               class="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-xl hover:bg-purple-200 dark:hover:bg-purple-900/50 transition font-medium">
                ดู License
            </a>
        </div>
    </div>
    @endif

    <!-- Abuse Information -->
    @if($device->is_suspicious || $device->abuse_reason)
    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-2xl shadow-xl p-6 mb-6 border border-amber-200 dark:border-amber-800">
        <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            ข้อมูล Abuse Detection
        </h3>
        @if($device->abuse_reason)
            <p class="text-amber-700 dark:text-amber-300 mb-4">{{ $device->abuse_reason }}</p>
        @endif
        <form action="{{ route('admin.devices.clear-suspicious', $device) }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="px-4 py-2 bg-amber-200 dark:bg-amber-800 text-amber-800 dark:text-amber-200 rounded-xl hover:bg-amber-300 dark:hover:bg-amber-700 transition font-medium">
                ลบ Suspicious Flag
            </button>
        </form>
    </div>
    @endif

    <!-- Related Devices -->
    @if($relatedByIp->isNotEmpty() || $relatedByHardware->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Related Devices
        </h3>

        @if($relatedByIp->isNotEmpty())
        <div class="mb-4">
            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">By IP Address ({{ $relatedByIp->count() }})</h4>
            <div class="space-y-2">
                @foreach($relatedByIp as $related)
                    <a href="{{ route('admin.devices.show', $related) }}"
                       class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $related->machine_name ?? 'Unknown' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $related->first_ip }} / {{ $related->last_ip }}</p>
                        </div>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $related->getStatusBadgeClass() }}">
                            {{ $related->getStatusLabel() }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        @if($relatedByHardware->isNotEmpty())
        <div>
            <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">By Hardware Hash ({{ $relatedByHardware->count() }})</h4>
            <div class="space-y-2">
                @foreach($relatedByHardware as $related)
                    <a href="{{ route('admin.devices.show', $related) }}"
                       class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $related->machine_name ?? 'Unknown' }}</p>
                            <p class="text-sm font-mono text-gray-500 dark:text-gray-400">{{ Str::limit($related->hardware_hash, 20) }}</p>
                        </div>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $related->getStatusBadgeClass() }}">
                            {{ $related->getStatusLabel() }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Delete Device -->
    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <form action="{{ route('admin.devices.destroy', $device) }}" method="POST"
              onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ Device นี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium">
                ลบ Device นี้
            </button>
        </form>
    </div>
</div>

<!-- Block Modal -->
<div id="blockModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
        <form action="{{ route('admin.devices.block', $device) }}" method="POST">
            @csrf
            <div class="p-6">
                <div class="w-16 h-16 rounded-full bg-gradient-to-br from-red-400 to-rose-600 flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 text-center">บล็อก Device</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4 text-center">{{ $device->machine_name ?? $device->machine_id }}</p>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 flex justify-end space-x-3">
                <button type="button" onclick="hideBlockModal()"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl hover:from-red-600 hover:to-rose-700 font-medium shadow-lg transition">ยืนยันบล็อก</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function showBlockModal() {
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
