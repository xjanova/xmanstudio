@extends('layouts.admin')

@section('title', 'คอมมิชชั่นรอตรวจสอบ')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">คอมมิชชั่นรอตรวจสอบ</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">อนุมัติหรือปฏิเสธค่าคอมมิชชั่น Affiliate (จ่ายเข้า Wallet อัตโนมัติ)</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.affiliates.index') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm transition">
                กลับ Affiliate
            </a>
            @if($commissions->where('status', 'pending')->count() > 0)
                <form action="{{ route('admin.affiliates.commission.bulk-approve') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition" onclick="return confirm('อนุมัติและจ่ายทั้งหมด?')">
                        จ่ายทั้งหมด ({{ $commissions->where('status', 'pending')->count() }} รายการ)
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" class="flex items-center gap-2">
        <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" onchange="this.form.submit()">
            <option value="">รอตรวจสอบ</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>จ่ายแล้ว</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
        </select>
    </form>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Affiliate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คำสั่งซื้อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ซื้อ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ยอดสั่งซื้อ</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">อัตรา</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คอมมิชชั่น</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($commissions as $c)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $c->affiliate->user->name ?? '-' }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ $c->affiliate->referral_code ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-3 text-sm font-mono text-gray-700 dark:text-gray-300">{{ $c->order->order_number ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $c->referredUser->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-right text-gray-700 dark:text-gray-300">฿{{ number_format($c->order_amount) }}</td>
                            <td class="px-6 py-3 text-sm text-center text-gray-500">{{ number_format($c->commission_rate) }}%</td>
                            <td class="px-6 py-3 text-sm text-right font-semibold text-green-600">+฿{{ number_format($c->commission_amount) }}</td>
                            <td class="px-6 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $c->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $c->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $c->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                ">{{ $c->status_label }}</span>
                            </td>
                            <td class="px-6 py-3">
                                @if($c->status === 'pending')
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('admin.affiliates.commission.approve', $c) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg">จ่าย</button>
                                        </form>
                                        <form action="{{ route('admin.affiliates.commission.reject', $c) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg">ปฏิเสธ</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">{{ $c->paid_at?->format('d/m/y') ?? '-' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">ไม่มีรายการ</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            {{ $commissions->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
