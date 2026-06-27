@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ประวัติคอมมิชชั่น')
@section('page-title')<x-bi th="ประวัติคอมมิชชั่น" en="Commission History" />@endsection
@section('page-description')<x-bi th="รายการค่าแนะนำทั้งหมดของคุณ" en="All your referral commissions" />@endsection

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <a href="{{ route('customer.affiliate.dashboard') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-pink-600 transition-colors">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        <x-bi th="กลับไป Dashboard" en="Back to Dashboard" />
    </a>

    {{-- Filter --}}
    <form method="GET" class="flex items-center gap-2">
        <select name="status" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-pink-500" onchange="this.form.submit()">
            <option value="">ทั้งหมด / All</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอตรวจสอบ / Pending</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว / Paid</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ / Rejected</option>
        </select>
    </form>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    @if($commissions->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><x-bi th="วันที่" en="Date" /></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><x-bi th="คำสั่งซื้อ" en="Order" /></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><x-bi th="ผู้ซื้อ" en="Buyer" /></th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"><x-bi th="ยอดสั่งซื้อ" en="Order Amount" /></th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><x-bi th="อัตรา" en="Rate" /></th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase"><x-bi th="คอมมิชชั่น" en="Commission" /></th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase"><x-bi th="สถานะ" en="Status" /></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"><x-bi th="จ่ายเมื่อ" en="Paid On" /></th>
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
            <p><x-bi th="ไม่พบรายการคอมมิชชั่น" en="No commissions found" /></p>
        </div>
    @endif
</div>
@endsection
