@extends('layouts.admin')

@section('title', 'รายละเอียดการเช่า')
@section('page-title', 'รายละเอียดการเช่า')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.rentals.index') }}" class="text-primary-600 hover:underline">
        &larr; กลับ
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Rental Details -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">ข้อมูลการเช่า</h2>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500">แพ็กเกจ</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $rental->rentalPackage->display_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">สถานะ</dt>
                        <dd class="mt-1">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                @if($rental->status === 'active') bg-green-100 text-green-800
                                @elseif($rental->status === 'pending') bg-yellow-100 text-yellow-800
                                @elseif($rental->status === 'suspended') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $rental->status_label }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">วันที่เริ่ม</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $rental->starts_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">วันหมดอายุ</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $rental->expires_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">เหลืออีก</dt>
                        <dd class="mt-1 text-sm {{ $rental->days_remaining <= 7 ? 'text-red-600 font-semibold' : 'text-gray-900' }}">
                            {{ $rental->days_remaining }} วัน
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500">ต่ออายุอัตโนมัติ</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $rental->auto_renew ? 'เปิด' : 'ปิด' }}</dd>
                    </div>
                </dl>

                @if($rental->notes)
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <dt class="text-sm text-gray-500 mb-2">หมายเหตุ</dt>
                        <dd class="text-sm text-gray-900 whitespace-pre-line bg-gray-50 p-4 rounded-lg">{{ $rental->notes }}</dd>
                    </div>
                @endif
            </div>
        </div>

        <!-- Payments -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">การชำระเงิน</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">จำนวน</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วิธี</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($rental->payments as $payment)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">฿{{ number_format($payment->amount) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $payment->payment_method }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        @if($payment->status === 'completed') bg-green-100 text-green-800
                                        @elseif($payment->status === 'pending') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $payment->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">ไม่มีข้อมูล</td>
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
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">ข้อมูลผู้ใช้</h2>
            </div>
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-xl font-bold">
                        {{ strtoupper(substr($rental->user->name, 0, 1)) }}
                    </div>
                    <div class="ml-4">
                        <p class="font-semibold text-gray-900">{{ $rental->user->name }}</p>
                        <p class="text-sm text-gray-500">{{ $rental->user->email }}</p>
                    </div>
                </div>
                @if($rental->user->phone)
                    <p class="text-sm text-gray-600">
                        <span class="text-gray-500">โทร:</span> {{ $rental->user->phone }}
                    </p>
                @endif
            </div>
        </div>

        <!-- Actions -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">การดำเนินการ</h2>
            </div>
            <div class="p-6 space-y-3">
                @if($rental->status === 'active')
                    <button type="button" onclick="showSuspendModal()"
                            class="w-full py-2 px-4 border border-red-600 text-red-600 rounded-lg hover:bg-red-50">
                        ระงับการใช้งาน
                    </button>
                @elseif($rental->status === 'suspended')
                    <form action="{{ route('admin.rentals.reactivate', $rental) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="w-full py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            เปิดใช้งานอีกครั้ง
                        </button>
                    </form>
                @endif

                <button type="button" onclick="showExtendModal()"
                        class="w-full py-2 px-4 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    ขยายเวลา
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Extend Modal -->
<div id="extendModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <form action="{{ route('admin.rentals.extend', $rental) }}" method="POST">
            @csrf
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ขยายเวลาการเช่า</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนวัน</label>
                        <input type="number" name="days" min="1" max="365" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">เหตุผล</label>
                        <textarea name="reason" rows="3" required
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" onclick="hideExtendModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">ขยายเวลา</button>
            </div>
        </form>
    </div>
</div>

<!-- Suspend Modal -->
<div id="suspendModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
        <form action="{{ route('admin.rentals.suspend', $rental) }}" method="POST">
            @csrf
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">ระงับการใช้งาน</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เหตุผล</label>
                    <textarea name="reason" rows="3" required
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                <button type="button" onclick="hideSuspendModal()"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</button>
                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">ระงับ</button>
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
