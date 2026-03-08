@extends('layouts.admin')

@section('title', 'Affiliate: ' . $affiliate->user->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.affiliates.index') }}" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $affiliate->user->name }}</h1>
            <p class="text-sm text-gray-500">{{ $affiliate->user->email }} | Code: {{ $affiliate->referral_code }}</p>
        </div>
    </div>

    <!-- Stats + Edit Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Stats --}}
        <div class="lg:col-span-2 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500">รายได้รวม</p>
                <p class="text-xl font-bold text-green-600">฿{{ number_format($affiliate->total_earned) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500">จ่ายแล้ว</p>
                <p class="text-xl font-bold text-blue-600">฿{{ number_format($affiliate->total_paid) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500">รอจ่าย</p>
                <p class="text-xl font-bold text-yellow-600">฿{{ number_format($affiliate->total_pending) }}</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500">คลิก / ขาย</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $affiliate->total_clicks }} / {{ $affiliate->total_conversions }}</p>
                <p class="text-xs text-gray-400">{{ $affiliate->conversion_rate }}% conv.</p>
            </div>
        </div>

        {{-- Edit Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ตั้งค่า</h3>
            <form action="{{ route('admin.affiliates.update', $affiliate) }}" method="POST" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">สถานะ</label>
                    <select name="status" class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                        <option value="active" {{ $affiliate->status === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                        <option value="suspended" {{ $affiliate->status === 'suspended' ? 'selected' : '' }}>ระงับ</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">อัตราคอมมิชชั่น (%)</label>
                    <input type="number" name="commission_rate" value="{{ $affiliate->commission_rate }}" min="1" max="50" step="0.5"
                           class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                </div>
                <div>
                    <label class="text-sm text-gray-600 dark:text-gray-400">หมายเหตุ</label>
                    <textarea name="notes" rows="2" class="w-full mt-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">{{ $affiliate->notes }}</textarea>
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg text-sm transition">บันทึก</button>
            </form>
        </div>
    </div>

    <!-- Commissions Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">ประวัติคอมมิชชั่น</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คำสั่งซื้อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ซื้อ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ยอด</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คอมมิชชั่น</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($commissions as $c)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                            <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-3 text-sm font-mono">{{ $c->order->order_number ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm">{{ $c->referredUser->name ?? '-' }}</td>
                            <td class="px-6 py-3 text-sm text-right">฿{{ number_format($c->order_amount) }}</td>
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
                                            <button type="submit" class="text-xs px-3 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg" onclick="return confirm('จ่ายค่าคอมมิชชั่น ฿{{ number_format($c->commission_amount) }} เข้า Wallet?')">จ่าย</button>
                                        </form>
                                        <form action="{{ route('admin.affiliates.commission.reject', $c) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg" onclick="return confirm('ปฏิเสธคอมมิชชั่นนี้?')">ปฏิเสธ</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">{{ $c->paid_at?->format('d/m/y') ?? '-' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">ไม่มีข้อมูลคอมมิชชั่น</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            {{ $commissions->links() }}
        </div>
    </div>
</div>
@endsection
