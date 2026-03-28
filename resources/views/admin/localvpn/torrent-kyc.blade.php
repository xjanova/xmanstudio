@extends($adminLayout ?? 'layouts.admin')

@section('title', 'BitTorrent - KYC')
@section('page-title', 'LocalVPN - ตรวจสอบ KYC')

@section('content')
@include('admin.localvpn._tabs')

{{-- Filter Tabs --}}
<div class="flex flex-wrap gap-2 mb-6">
    <a href="{{ route('admin.localvpn.torrent.kyc') }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ !request('status') ? 'bg-violet-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
        ทั้งหมด
    </a>
    <a href="{{ route('admin.localvpn.torrent.kyc', ['status' => 'pending']) }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors inline-flex items-center gap-1.5
           {{ request('status') === 'pending' ? 'bg-amber-500 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
        รอตรวจ
        @if(($pendingCount ?? 0) > 0)
            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-xs font-bold {{ request('status') === 'pending' ? 'bg-white/30 text-white' : 'bg-amber-100 text-amber-800' }}">
                {{ $pendingCount }}
            </span>
        @endif
    </a>
    <a href="{{ route('admin.localvpn.torrent.kyc', ['status' => 'approved']) }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request('status') === 'approved' ? 'bg-green-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
        อนุมัติ
    </a>
    <a href="{{ route('admin.localvpn.torrent.kyc', ['status' => 'rejected']) }}"
       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request('status') === 'rejected' ? 'bg-red-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
        ปฏิเสธ
    </a>
</div>

{{-- KYC Table --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900">รายการ KYC ({{ $submissions->total() }})</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">Machine ID</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ชื่อที่แสดง</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">วันเกิด</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">สถานะ</th>
                    <th class="text-left py-3 px-4 text-gray-600 font-medium">ส่งเมื่อ</th>
                    <th class="text-center py-3 px-4 text-gray-600 font-medium">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($submissions as $kyc)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-4 font-mono text-xs text-gray-600">{{ Str::limit($kyc->machine_id, 16) }}</td>
                    <td class="py-3 px-4 font-medium text-gray-900">{{ $kyc->display_name }}</td>
                    <td class="py-3 px-4 text-gray-600">{{ $kyc->birth_date ? \Carbon\Carbon::parse($kyc->birth_date)->format('d/m/Y') : '-' }}</td>
                    <td class="py-3 px-4 text-center">
                        @switch($kyc->status)
                            @case('pending')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">รอตรวจ</span>
                                @break
                            @case('approved')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">อนุมัติ</span>
                                @break
                            @case('rejected')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">ปฏิเสธ</span>
                                @break
                        @endswitch
                    </td>
                    <td class="py-3 px-4 text-gray-500 text-xs">{{ $kyc->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="py-3 px-4 text-center">
                        <a href="{{ route('admin.localvpn.torrent.kyc.show', $kyc) }}" class="inline-flex items-center px-3 py-1 bg-violet-100 text-violet-700 rounded-lg text-xs font-medium hover:bg-violet-200 transition-colors">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            ดูรายละเอียด
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-gray-500">ไม่มีรายการ KYC</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($submissions->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $submissions->links() }}
    </div>
    @endif
</div>
@endsection
