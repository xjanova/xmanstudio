@extends($adminLayout ?? 'layouts.admin')

@section('title', 'การชำระเงิน')
@section('page-title', 'การชำระเงิน')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">รอยืนยัน</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['pending'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">กำลังดำเนินการ</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['processing'] }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">รายได้วันนี้</p>
                <p class="text-2xl font-bold text-gray-900">฿{{ number_format($stats['today_revenue']) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-primary-100 text-primary-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500">รายได้เดือนนี้</p>
                <p class="text-2xl font-bold text-gray-900">฿{{ number_format($stats['month_revenue']) }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form action="{{ route('admin.rentals.payments') }}" method="GET" class="flex flex-wrap gap-4">
        <div>
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                <option value="all">ทุกสถานะ</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอยืนยัน</option>
                <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ไม่สำเร็จ</option>
            </select>
        </div>
        <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            กรอง
        </button>
    </form>
</div>

<!-- Payments Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้ใช้</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">แพ็กเกจ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวน</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วิธี</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($payments as $payment)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $payment->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $payment->user->name }}</div>
                            <div class="text-sm text-gray-500">{{ $payment->user->email }}</div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $payment->userRental?->rentalPackage?->display_name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                        ฿{{ number_format($payment->amount) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($payment->payment_method === 'promptpay')
                            พร้อมเพย์
                        @elseif($payment->payment_method === 'bank_transfer')
                            โอนเงิน
                        @else
                            {{ $payment->payment_method }}
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                            @if($payment->status === 'completed') bg-green-100 text-green-800
                            @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                            @elseif($payment->status === 'processing') bg-blue-100 text-blue-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ $payment->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($payment->status === 'pending' || $payment->status === 'processing')
                            @if($payment->slip_url)
                                <a href="{{ $payment->slip_url }}" target="_blank"
                                   class="text-primary-600 hover:underline mr-3">ดูสลิป</a>
                            @endif
                            <button type="button" onclick="showVerifyModal({{ $payment->id }})"
                                    class="text-green-600 hover:underline mr-3">ยืนยัน</button>
                            <button type="button" onclick="showRejectModal({{ $payment->id }})"
                                    class="text-red-600 hover:underline">ปฏิเสธ</button>
                        @elseif($payment->status === 'completed')
                            <span class="text-gray-400">ยืนยันแล้ว</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        ไม่พบข้อมูล
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($payments->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $payments->links() }}
        </div>
    @endif
</div>

<!-- Verify Modal -->
<div id="verifyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <form id="verifyForm" method="POST">
            @csrf
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ยืนยันการชำระเงิน</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">หมายเหตุ (ถ้ามี)</label>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" onclick="hideVerifyModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">ยืนยัน</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <form id="rejectForm" method="POST">
            @csrf
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ปฏิเสธการชำระเงิน</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เหตุผล</label>
                    <textarea name="reason" rows="3" required
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" onclick="hideRejectModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">ปฏิเสธ</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function showVerifyModal(paymentId) {
        document.getElementById('verifyForm').action = `/admin/rentals/payments/${paymentId}/verify`;
        document.getElementById('verifyModal').classList.remove('hidden');
        document.getElementById('verifyModal').classList.add('flex');
    }

    function hideVerifyModal() {
        document.getElementById('verifyModal').classList.add('hidden');
        document.getElementById('verifyModal').classList.remove('flex');
    }

    function showRejectModal(paymentId) {
        document.getElementById('rejectForm').action = `/admin/rentals/payments/${paymentId}/reject`;
        document.getElementById('rejectModal').classList.remove('hidden');
        document.getElementById('rejectModal').classList.add('flex');
    }

    function hideRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        document.getElementById('rejectModal').classList.remove('flex');
    }
</script>
@endpush
@endsection
