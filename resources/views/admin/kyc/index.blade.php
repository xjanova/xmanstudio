@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ยืนยันตัวตน (KYC)')
@section('page-title', 'ยืนยันตัวตน (KYC)')

@section('content')
<div class="space-y-5">

    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center gap-2">
        @foreach (['pending' => 'รอตรวจ', 'approved' => 'ผ่าน', 'rejected' => 'ไม่ผ่าน', 'all' => 'ทั้งหมด'] as $key => $label)
            <a href="{{ route('admin.kyc.index', ['status' => $key, 'q' => $search]) }}"
               class="rounded-lg px-3 py-1.5 text-sm border {{ $status === $key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                {{ $label }}
                @if ($key !== 'all')<span class="opacity-70">({{ $counts[$key] ?? 0 }})</span>@endif
            </a>
        @endforeach

        <form method="GET" action="{{ route('admin.kyc.index') }}" class="ml-auto flex gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="ชื่อ / อีเมล / ชื่อบัญชี"
                   class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <button class="rounded-lg bg-gray-800 px-4 py-2 text-sm text-white hover:bg-gray-900">ค้นหา</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">ผู้ใช้</th>
                    <th class="px-4 py-3 text-left font-medium">ชื่อตามบัตร</th>
                    <th class="px-4 py-3 text-left font-medium">บัญชีรับเงิน</th>
                    <th class="px-4 py-3 text-left font-medium">ส่งเมื่อ</th>
                    <th class="px-4 py-3 text-left font-medium">สถานะ</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($items as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $item->user->name ?? '—' }}</div>
                            <div class="text-gray-500 text-xs">{{ $item->user->email ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-gray-900">{{ $item->full_name_th }}</div>
                            <div class="text-gray-500 text-xs font-mono">···{{ $item->id_card_number_last4 }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-gray-900">{{ $item->bank_code }} ···{{ substr($item->bank_account_number ?? '', -4) }}</div>
                            <div class="text-gray-500 text-xs">{{ $item->bank_account_name }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $item->submitted_at?->format('d/m/Y H:i') ?? '—' }}
                            @if ($item->attempts > 1)<span class="text-xs text-amber-600">· ครั้งที่ {{ $item->attempts }}</span>@endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $badge = match ($item->status) {
                                    'approved' => 'bg-green-100 text-green-800',
                                    'pending'  => 'bg-blue-100 text-blue-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    default    => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $badge }}">{{ $item->statusLabel() }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.kyc.show', $item->id) }}"
                               class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs hover:bg-gray-50">ตรวจสอบ</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">ไม่มีรายการ</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $items->links() }}
</div>
@endsection
