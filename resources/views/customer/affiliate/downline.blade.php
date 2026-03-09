@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ลูกทีม Affiliate')
@section('page-title', 'ลูกทีมของคุณ')
@section('page-description', 'สมาชิกที่สมัครผ่านลิงก์แนะนำของคุณ')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('customer.affiliate.dashboard') }}" class="inline-flex items-center text-sm text-pink-600 hover:text-pink-700 font-medium">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับ Dashboard
        </a>
        <p class="text-sm text-gray-500">ลูกทีมทั้งหมด {{ $children->total() }} คน</p>
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500">ลูกทีมตรง</p>
            <p class="text-xl font-bold text-indigo-600">{{ $children->total() }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500">ลิ้งค์เชิญ</p>
            <div class="flex items-center gap-2 mt-1">
                <code class="text-xs bg-gray-50 px-2 py-1 rounded truncate flex-1">{{ $affiliate->referral_url }}</code>
                <button onclick="copyToClipboard('{{ $affiliate->referral_url }}', 'คัดลอกแล้ว!')" class="p-1 hover:bg-gray-100 rounded transition-colors flex-shrink-0">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500">ค่าคอมมิชชั่น</p>
            <p class="text-xl font-bold text-green-600">{{ number_format($affiliate->commission_rate) }}%</p>
        </div>
    </div>

    <!-- Downline Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">ลูกทีมตรง</h3>
        </div>

        @if($children->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">โค้ด</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">รายได้</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">ลูกทีม</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่สมัคร</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($children as $child)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white {{ $child->status === 'active' ? 'bg-green-500' : 'bg-red-400' }}">
                                            {{ strtoupper(substr($child->user->name ?? 'N', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ $child->user->name ?? 'N/A' }}</p>
                                            <p class="text-xs text-gray-500">{{ $child->user->email ?? '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3">
                                    <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">{{ $child->referral_code }}</code>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $child->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}
                                    ">{{ $child->status_label }}</span>
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-semibold text-green-600">฿{{ number_format($child->total_earned) }}</td>
                                <td class="px-6 py-3 text-sm text-center text-gray-600">{{ $child->children_count }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $child->created_at->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $children->links() }}
            </div>
        @else
            <div class="text-center py-12 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p>ยังไม่มีลูกทีม</p>
                <p class="text-sm mt-1">แชร์ลิงก์แนะนำของคุณเพื่อเริ่มสร้างทีม</p>
            </div>
        @endif
    </div>
</div>
@endsection
