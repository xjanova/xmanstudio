@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตรวจสอบ KYC')
@section('page-title', 'ตรวจสอบการยืนยันตัวตน')

@section('content')
<div class="max-w-5xl space-y-5">

    @if (session('error'))
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <a href="{{ route('admin.kyc.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; กลับไปคิวตรวจ</a>

    <div class="grid lg:grid-cols-2 gap-5">
        {{-- ข้อมูลที่กรอก --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 space-y-3">
            <h2 class="font-semibold text-gray-900">ข้อมูลที่ผู้ใช้กรอก</h2>
            @foreach ([
                'บัญชีผู้ใช้' => ($kyc->user->name ?? '—') . ' (' . ($kyc->user->email ?? '') . ')',
                'ชื่อตามบัตร (ไทย)' => $kyc->full_name_th,
                'ชื่อตามบัตร (อังกฤษ)' => $kyc->full_name_en ?: '—',
                'วันเกิด' => $kyc->birth_date?->format('d/m/Y') ?? '—',
                'เลขบัตร (4 ตัวท้าย)' => '···' . $kyc->id_card_number_last4,
                'ธนาคาร' => $kyc->bank_code,
                'เลขที่บัญชี' => $kyc->bank_account_number,
                'ชื่อบัญชี' => $kyc->bank_account_name,
                'ส่งเมื่อ' => $kyc->submitted_at?->format('d/m/Y H:i') ?? '—',
                'ครั้งที่' => $kyc->attempts,
            ] as $label => $value)
                <div class="flex justify-between gap-4 text-sm border-b border-gray-50 pb-2">
                    <span class="text-gray-500">{{ $label }}</span>
                    <span class="text-gray-900 text-right">{{ $value }}</span>
                </div>
            @endforeach

            <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800">
                ตรวจว่า <strong>ชื่อบัญชีธนาคารตรงกับชื่อบนบัตร</strong> และ <strong>หน้าคนในรูปเซลฟี่ตรงกับรูปบนบัตร</strong>
                — สองข้อนี้คือเหตุผลหลักที่ต้องมีคนตรวจ
            </div>

            @if (! empty($kyc->history))
                <div class="rounded-lg bg-gray-50 border border-gray-200 p-3 text-xs text-gray-600">
                    <div class="font-medium mb-1">เคยถูกปฏิเสธมาก่อน</div>
                    @foreach ($kyc->history as $h)
                        <div>· {{ $h['reason'] ?? '—' }} <span class="text-gray-400">({{ $h['rejected_at'] ?? '' }})</span></div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- เอกสาร --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <h2 class="font-semibold text-gray-900 mb-3">เอกสาร</h2>
            <div class="grid grid-cols-2 gap-3">
                @foreach (['front' => 'บัตร (หน้า)', 'back' => 'บัตร (หลัง)', 'selfie' => 'เซลฟี่คู่บัตร', 'bank' => 'สมุดบัญชี'] as $kind => $label)
                    @php
                        $column = ['front' => 'id_card_front_path', 'back' => 'id_card_back_path', 'selfie' => 'selfie_path', 'bank' => 'bank_book_path'][$kind];
                    @endphp
                    <div>
                        <div class="text-xs text-gray-500 mb-1">{{ $label }}</div>
                        @if ($kyc->{$column})
                            <a href="{{ route('kyc.document', ['id' => $kyc->id, 'kind' => $kind]) }}" target="_blank" rel="noopener">
                                <img src="{{ route('kyc.document', ['id' => $kyc->id, 'kind' => $kind]) }}"
                                     alt="{{ $label }}" class="rounded-lg border border-gray-200 w-full object-cover aspect-[4/3]">
                            </a>
                        @else
                            <div class="rounded-lg border border-dashed border-gray-300 aspect-[4/3] grid place-items-center text-xs text-gray-400">ไม่ได้ส่ง</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- การตัดสิน --}}
    @if ($kyc->isPending())
        <div class="grid sm:grid-cols-2 gap-4">
            <form method="POST" action="{{ route('admin.kyc.approve', $kyc->id) }}"
                  onsubmit="return confirm('อนุมัติการยืนยันตัวตนของ ' + @js($kyc->user->name ?? '') + '?\n\nการอนุมัติจะเปิดสิทธิ์ถอนเงินและเข้าถึงหมวดเนื้อหาสำหรับผู้ใหญ่');"
                  class="rounded-2xl border border-green-200 bg-green-50 p-5">
                @csrf
                <h3 class="font-semibold text-green-900 mb-2">อนุมัติ</h3>
                <p class="text-xs text-green-800 mb-3">เปิดสิทธิ์ถอนเงิน และเข้าถึงหมวดเนื้อหาสำหรับผู้ใหญ่</p>
                <button class="rounded-xl bg-green-600 px-5 py-2.5 text-white text-sm font-medium hover:bg-green-700">อนุมัติการยืนยันตัวตน</button>
            </form>

            <form method="POST" action="{{ route('admin.kyc.reject', $kyc->id) }}" class="rounded-2xl border border-red-200 bg-red-50 p-5">
                @csrf
                <h3 class="font-semibold text-red-900 mb-2">ไม่อนุมัติ</h3>
                <textarea name="rejection_reason" rows="3" required minlength="10" maxlength="500"
                          placeholder="บอกให้ผู้ใช้เข้าใจว่าต้องแก้อะไร เช่น รูปบัตรเบลอจนอ่านเลขไม่ออก / ชื่อบัญชีไม่ตรงกับชื่อบนบัตร"
                          class="w-full rounded-lg border-red-300 text-sm focus:border-red-500 focus:ring-red-500 mb-3">{{ old('rejection_reason') }}</textarea>
                <button class="rounded-xl bg-red-600 px-5 py-2.5 text-white text-sm font-medium hover:bg-red-700">ปฏิเสธและแจ้งเหตุผล</button>
            </form>
        </div>
    @else
        <div class="rounded-2xl border border-gray-200 bg-white p-5 text-sm">
            <div class="font-medium text-gray-900 mb-1">ตรวจแล้ว — {{ $kyc->statusLabel() }}</div>
            <div class="text-gray-600">
                โดย {{ $kyc->reviewer->name ?? '—' }} เมื่อ {{ $kyc->reviewed_at?->format('d/m/Y H:i') ?? '—' }}
                @if ($kyc->rejection_reason)<div class="mt-2 text-red-700">เหตุผล: {{ $kyc->rejection_reason }}</div>@endif
            </div>
        </div>
    @endif
</div>
@endsection
