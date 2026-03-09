@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการ Affiliate')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการ Affiliate</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ตรวจสอบพันธมิตร ค่าคอมมิชชั่น และการจ่ายเงิน</p>
        </div>
        <div class="flex items-center gap-2">
        <a href="{{ route('admin.affiliates.tree') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            แผนผังสายงาน
        </a>
        <a href="{{ route('admin.affiliates.commissions') }}" class="inline-flex items-center px-4 py-2 bg-pink-600 hover:bg-pink-700 text-white rounded-lg transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            คอมมิชชั่นรอตรวจสอบ
            @if($totalPending > 0)
                <span class="ml-2 bg-white/20 text-white text-xs font-bold px-2 py-0.5 rounded-full">฿{{ number_format($totalPending) }}</span>
            @endif
        </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">Affiliates ทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($totalAffiliates) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">คอมมิชชั่นรวม</p>
            <p class="text-2xl font-bold text-green-600 mt-1">฿{{ number_format($totalEarned) }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">รอจ่าย</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1">฿{{ number_format($totalPending) }}</p>
        </div>
    </div>

    <!-- Search -->
    <form method="GET" class="flex items-center gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาชื่อ, อีเมล, โค้ด..."
               class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-pink-500 w-64">
        <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm" onchange="this.form.submit()">
            <option value="">ทุกสถานะ</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>ใช้งาน</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>ระงับ</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm">ค้นหา</button>
    </form>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ใช้</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">โค้ด</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">อัตรา</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รายได้</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จ่ายแล้ว</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รอจ่าย</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คลิก/ขาย</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($affiliates as $aff)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                            <td class="px-6 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $aff->user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $aff->user->email }}</div>
                            </td>
                            <td class="px-6 py-3 text-sm font-mono text-gray-700 dark:text-gray-300">{{ $aff->referral_code }}</td>
                            <td class="px-6 py-3 text-sm text-center text-gray-700 dark:text-gray-300">{{ number_format($aff->commission_rate) }}%</td>
                            <td class="px-6 py-3 text-sm text-right font-semibold text-green-600">฿{{ number_format($aff->total_earned) }}</td>
                            <td class="px-6 py-3 text-sm text-right text-blue-600">฿{{ number_format($aff->total_paid) }}</td>
                            <td class="px-6 py-3 text-sm text-right text-yellow-600">฿{{ number_format($aff->total_pending) }}</td>
                            <td class="px-6 py-3 text-sm text-center text-gray-600 dark:text-gray-400">{{ $aff->total_clicks }}/{{ $aff->total_conversions }}</td>
                            <td class="px-6 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $aff->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}
                                ">{{ $aff->status_label }}</span>
                            </td>
                            <td class="px-6 py-3">
                                <a href="{{ route('admin.affiliates.show', $aff) }}" class="text-pink-600 hover:text-pink-700 text-sm font-medium">ดูรายละเอียด</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">ไม่พบข้อมูล Affiliate</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            {{ $affiliates->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
