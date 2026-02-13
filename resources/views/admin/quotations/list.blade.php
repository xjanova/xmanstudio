@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการใบเสนอราคา')
@section('page-title', 'จัดการใบเสนอราคา')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 p-8 shadow-2xl">
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white">ใบเสนอราคา & ใบสั่งงาน</h1>
                </div>
                <p class="text-indigo-100 text-lg">ใบเสนอราคาและคำสั่งซื้อที่ลูกค้าส่งจากหน้าเว็บ</p>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.quotations.list') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ !request('status') ? 'ring-2 ring-indigo-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $counts['all'] }}</p>
        </a>
        <a href="{{ route('admin.quotations.list', ['status' => 'sent']) }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ in_array(request('status'), ['draft', 'sent', 'viewed']) ? 'ring-2 ring-blue-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">รอดำเนินการ</p>
            <p class="text-2xl font-bold text-blue-600">{{ $counts['pending'] }}</p>
        </a>
        <a href="{{ route('admin.quotations.list', ['status' => 'accepted']) }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ request('status') === 'accepted' ? 'ring-2 ring-green-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">ยอมรับแล้ว</p>
            <p class="text-2xl font-bold text-green-600">{{ $counts['accepted'] }}</p>
        </a>
        <a href="{{ route('admin.quotations.list', ['status' => 'paid']) }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ request('status') === 'paid' ? 'ring-2 ring-emerald-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">ชำระแล้ว</p>
            <p class="text-2xl font-bold text-emerald-600">{{ $counts['paid'] }}</p>
        </a>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="เลขที่, ชื่อ, อีเมล, บริษัท..."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">สถานะ</label>
                <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>ร่าง</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>ส่งแล้ว</option>
                    <option value="viewed" {{ request('status') === 'viewed' ? 'selected' : '' }}>เปิดดูแล้ว</option>
                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>ยอมรับ</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>ชำระแล้ว</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>ปฏิเสธ</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">ประเภท</label>
                <select name="action_type" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="quotation" {{ request('action_type') === 'quotation' ? 'selected' : '' }}>ใบเสนอราคา</option>
                    <option value="order" {{ request('action_type') === 'order' ? 'selected' : '' }}>สั่งซื้อ</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">ค้นหา</button>
            @if(request()->hasAny(['search', 'status', 'action_type']))
                <a href="{{ route('admin.quotations.list') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium hover:bg-gray-300 transition">ล้าง</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">เลขที่</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">ลูกค้า</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">บริการ</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">ยอดรวม</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">ประเภท</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">สถานะ</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">โครงการ</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($quotations as $q)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.quotations.detail', $q) }}" class="font-mono text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">{{ $q->quote_number }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $q->customer_name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $q->customer_company ?: $q->customer_email }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ Str::limit($q->service_name, 30) }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white whitespace-nowrap">฿{{ number_format($q->grand_total, 0) }}</td>
                        <td class="px-4 py-3">
                            @if($q->action_type === 'order')
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">สั่งซื้อ</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">ใบเสนอราคา</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusBadge = [
                                    'draft' => 'bg-gray-100 text-gray-700',
                                    'sent' => 'bg-blue-100 text-blue-700',
                                    'viewed' => 'bg-purple-100 text-purple-700',
                                    'accepted' => 'bg-green-100 text-green-700',
                                    'paid' => 'bg-emerald-100 text-emerald-700',
                                    'expired' => 'bg-red-100 text-red-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                ];
                                $statusLabel = [
                                    'draft' => 'ร่าง', 'sent' => 'ส่งแล้ว', 'viewed' => 'เปิดดูแล้ว',
                                    'accepted' => 'ยอมรับ', 'paid' => 'ชำระแล้ว', 'expired' => 'หมดอายุ', 'rejected' => 'ปฏิเสธ',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $statusBadge[$q->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $statusLabel[$q->status] ?? $q->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($q->project)
                                <a href="{{ route('admin.projects.show', $q->project) }}" class="inline-flex items-center px-2 py-0.5 text-xs font-mono font-bold bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 transition">
                                    {{ $q->project->project_number }}
                                </a>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $q->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.quotations.detail', $q) }}" class="px-3 py-1.5 text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-200 transition">ดูรายละเอียด</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center">
                            <p class="text-gray-500 dark:text-gray-400 font-medium">ไม่พบใบเสนอราคา</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quotations->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $quotations->links() }}</div>
        @endif
    </div>
</div>
@endsection
