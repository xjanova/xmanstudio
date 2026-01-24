@extends($adminLayout ?? 'layouts.admin')

@section('title', 'รายละเอียดการเช่า')
@section('page-title', 'รายละเอียดการเช่า')

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
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-amber-600 via-orange-600 to-red-500 p-8 shadow-2xl mb-8">
    <div class="absolute top-0 left-0 w-72 h-72 bg-amber-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-orange-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-red-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    <div class="relative">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.rentals.index') }}" class="p-2 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-3xl font-bold text-white mb-2">รายละเอียดการเช่า</h2>
                    <p class="text-amber-100">{{ $rental->rentalPackage->display_name }}</p>
                </div>
            </div>
            <span class="px-4 py-2 rounded-xl text-sm font-semibold backdrop-blur-sm
                @if($rental->status === 'active') bg-green-500/30 text-white border border-green-300/50
                @elseif($rental->status === 'pending') bg-yellow-500/30 text-white border border-yellow-300/50
                @elseif($rental->status === 'suspended') bg-red-500/30 text-white border border-red-300/50
                @else bg-gray-500/30 text-white border border-gray-300/50 @endif">
                {{ $rental->status_label }}
            </span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Rental Details -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="w-8 h-8 bg-gradient-to-r from-amber-500 to-orange-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </span>
                    ข้อมูลการเช่า
                </h2>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">แพ็กเกจ</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $rental->rentalPackage->display_name }}</dd>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">สถานะ</dt>
                        <dd class="mt-1">
                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full
                                @if($rental->status === 'active') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400
                                @elseif($rental->status === 'pending') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400
                                @elseif($rental->status === 'suspended') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400
                                @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-400 @endif">
                                {{ $rental->status_label }}
                            </span>
                        </dd>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">วันที่เริ่ม</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $rental->starts_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">วันหมดอายุ</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $rental->expires_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/30 dark:to-amber-900/30 rounded-xl p-4 border border-orange-100 dark:border-orange-800">
                        <dt class="text-sm text-orange-600 dark:text-orange-400">เหลืออีก</dt>
                        <dd class="mt-1 text-lg font-bold {{ $rental->days_remaining <= 7 ? 'text-red-600 dark:text-red-400' : 'text-orange-900 dark:text-orange-100' }}">
                            {{ $rental->days_remaining }} วัน
                        </dd>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">ต่ออายุอัตโนมัติ</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $rental->auto_renew ? 'เปิด' : 'ปิด' }}</dd>
                    </div>
                </dl>

                @if($rental->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <dt class="text-sm text-gray-500 dark:text-gray-400 mb-2">หมายเหตุ</dt>
                        <dd class="text-sm text-gray-900 dark:text-white whitespace-pre-line bg-gray-50 dark:bg-gray-700/50 p-4 rounded-xl">{{ $rental->notes }}</dd>
                    </div>
                @endif
            </div>
        </div>

        <!-- Payments -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="w-8 h-8 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </span>
                    การชำระเงิน
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จำนวน</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">วิธี</th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($rental->payments as $payment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 px-3 py-1 rounded-lg">
                                        ฿{{ number_format($payment->amount) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $payment->payment_method }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full
                                        @if($payment->status === 'completed') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400
                                        @elseif($payment->status === 'pending') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400
                                        @else bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-400 @endif">
                                        {{ $payment->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                    </svg>
                                    ไม่มีข้อมูลการชำระเงิน
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- User Info -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="w-8 h-8 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </span>
                    ข้อมูลผู้ใช้
                </h2>
            </div>
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-indigo-500 text-white flex items-center justify-center text-xl font-bold">
                        {{ strtoupper(substr($rental->user->name, 0, 1)) }}
                    </div>
                    <div class="ml-4">
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $rental->user->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $rental->user->email }}</p>
                    </div>
                </div>
                @if($rental->user->phone)
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        <span class="text-gray-500 dark:text-gray-400">โทร:</span> {{ $rental->user->phone }}
                    </p>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                    <span class="w-8 h-8 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                    </span>
                    การดำเนินการ
                </h2>
            </div>
            <div class="p-6 space-y-3">
                @if($rental->status === 'active')
                    <button type="button" onclick="showSuspendModal()"
                            class="w-full py-2.5 px-4 border border-red-600 dark:border-red-500 text-red-600 dark:text-red-400 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors font-medium">
                        ระงับการใช้งาน
                    </button>
                @elseif($rental->status === 'suspended')
                    <form action="{{ route('admin.rentals.reactivate', $rental) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full py-2.5 px-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl hover:from-green-600 hover:to-emerald-600 transition-all shadow-lg font-medium">
                            เปิดใช้งานอีกครั้ง
                        </button>
                    </form>
                @endif

                <button type="button" onclick="showExtendModal()"
                        class="w-full py-2.5 px-4 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg font-medium">
                    ขยายเวลา
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Extend Modal -->
<div id="extendModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full mx-4 border border-gray-200 dark:border-gray-700">
        <form action="{{ route('admin.rentals.extend', $rental) }}" method="POST">
            @csrf
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ขยายเวลาการเช่า</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">จำนวนวัน</label>
                        <input type="number" name="days" min="1" max="365" required
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล</label>
                        <textarea name="reason" rows="3" required
                                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:bg-gray-700 dark:text-white transition-all"></textarea>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end space-x-3 rounded-b-2xl">
                <button type="button" onclick="hideExtendModal()"
                        class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg">ขยายเวลา</button>
            </div>
        </form>
    </div>
</div>

<!-- Suspend Modal -->
<div id="suspendModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full mx-4 border border-gray-200 dark:border-gray-700">
        <form action="{{ route('admin.rentals.suspend', $rental) }}" method="POST">
            @csrf
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ระงับการใช้งาน</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เหตุผล</label>
                    <textarea name="reason" rows="3" required
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white transition-all"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end space-x-3 rounded-b-2xl">
                <button type="button" onclick="hideSuspendModal()"
                        class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2.5 bg-gradient-to-r from-red-500 to-rose-500 text-white rounded-xl hover:from-red-600 hover:to-rose-600 transition-all shadow-lg">ระงับ</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function showExtendModal() {
        document.getElementById('extendModal').classList.remove('hidden');
        document.getElementById('extendModal').classList.add('flex');
    }

    function hideExtendModal() {
        document.getElementById('extendModal').classList.add('hidden');
        document.getElementById('extendModal').classList.remove('flex');
    }

    function showSuspendModal() {
        document.getElementById('suspendModal').classList.remove('hidden');
        document.getElementById('suspendModal').classList.add('flex');
    }

    function hideSuspendModal() {
        document.getElementById('suspendModal').classList.add('hidden');
        document.getElementById('suspendModal').classList.remove('flex');
    }
</script>
@endpush
@endsection
