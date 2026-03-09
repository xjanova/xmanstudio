@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ประวัติคอมมิชชั่น')
@section('page-title', 'ประวัติคอมมิชชั่น')
@section('page-description', 'รายการค่าแนะนำทั้งหมดของคุณ')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <a href="{{ route('customer.affiliate.dashboard') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-pink-600 transition-colors">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        กลับไป Dashboard
    </a>

    {{-- Filter --}}
    <form method="GET" class="flex items-center gap-2">
        <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-pink-500" onchange="this.form.submit()">
            <option value="">ทั้งหมด</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอตรวจสอบ</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
        </select>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    @if($commissions->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">คำสั่งซื้อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้ซื้อ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">ยอดสั่งซื้อ</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">อัตรา</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">คอมมิชชั่น</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">จ่ายเมื่อ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($commissions as $commission)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $commission->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-3 text-sm font-mono text-gray-700">{{ $commission->order->order_number ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600">{{ $commission->referredUser->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-700 text-right">฿{{ number_format($commission->order_amount) }}</td>
                            <td class="px-6 py-3 text-sm text-gray-500 text-center">{{ number_format($commission->commission_rate) }}%</td>
                            <td class="px-6 py-3 text-sm font-semibold text-green-600 text-right">+฿{{ number_format($commission->commission_amount) }}</td>
                            <td class="px-6 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $commission->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $commission->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $commission->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                ">{{ $commission->status_label }}</span>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-500">{{ $commission->paid_at?->format('d/m/Y') ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-100">
            {{ $commissions->withQueryString()->links() }}
        </div>
    @else
        <div class="text-center py-12 text-gray-500">
            <p>ไม่พบรายการคอมมิชชั่น</p>
        </div>
    @endif
</div>
@endsection
