@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ยืนยันตัวตน')
@section('page-title', 'ยืนยันตัวตน')
@section('page-description', 'จำเป็นสำหรับการรับเงิน ถอนเงิน และการเข้าถึงหมวดเนื้อหาสำหรับผู้ใหญ่')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    {{-- สถานะปัจจุบัน --}}
    @if ($kyc)
        @php
            $tone = match ($kyc->status) {
                'approved' => ['bg-green-50', 'border-green-200', 'text-green-800'],
                'pending'  => ['bg-blue-50', 'border-blue-200', 'text-blue-800'],
                'rejected' => ['bg-red-50', 'border-red-200', 'text-red-800'],
                default    => ['bg-gray-50', 'border-gray-200', 'text-gray-700'],
            };
        @endphp
        <div class="rounded-2xl border {{ $tone[1] }} {{ $tone[0] }} p-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="text-sm text-gray-500">สถานะ</div>
                    <div class="text-lg font-semibold {{ $tone[2] }}">{{ $kyc->statusLabel() }}</div>
                </div>
                @if ($kyc->submitted_at)
                    <div class="text-right text-sm text-gray-500">
                        ส่งเมื่อ {{ $kyc->submitted_at->format('d/m/Y H:i') }}
                        @if ($kyc->attempts > 1)<div>ครั้งที่ {{ $kyc->attempts }}</div>@endif
                    </div>
                @endif
            </div>

            @if ($kyc->status === 'rejected' && $kyc->rejection_reason)
                <div class="mt-4 rounded-lg bg-white/70 border border-red-200 p-3 text-sm text-red-800">
                    <div class="font-medium mb-1">เหตุผลที่ไม่ผ่าน</div>
                    {{ $kyc->rejection_reason }}
                </div>
            @endif

            @if ($kyc->status === 'approved')
                <div class="mt-4 text-sm text-gray-600">
                    บัญชีรับเงิน: {{ $kyc->bank_code }} ···{{ substr($kyc->bank_account_number ?? '', -4) }}
                    ({{ $kyc->bank_account_name }})
                </div>
            @endif
        </div>
    @endif

    {{-- ฟอร์ม --}}
    @if (! $kyc || $kyc->canResubmit())
        <div class="rounded-2xl border border-gray-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">ส่งเอกสารยืนยันตัวตน</h2>
            <p class="text-sm text-gray-500 mb-6">
                ข้อมูลนี้ใช้ยืนยันว่าบัญชีธนาคารปลายทางเป็นของคุณจริง และใช้เปิดสิทธิ์ตามข้อกำหนดการใช้งาน
                ทีมงานตรวจสอบภายใน 1–3 วันทำการ
            </p>

            @if ($errors->any())
                <div class="mb-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('kyc.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">เลขบัตรประชาชน (13 หลัก)</label>
                        <input type="text" name="id_card_number" value="{{ old('id_card_number') }}" inputmode="numeric"
                               maxlength="20" required
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">วันเกิด</label>
                        <input type="date" name="birth_date" value="{{ old('birth_date') }}" required
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล (ภาษาไทย)</label>
                        <input type="text" name="full_name_th" value="{{ old('full_name_th') }}" required maxlength="150"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล (อังกฤษ) <span class="text-gray-400">ไม่บังคับ</span></label>
                        <input type="text" name="full_name_en" value="{{ old('full_name_en') }}" maxlength="150"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <hr class="border-gray-100">

                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ธนาคาร</label>
                        <select name="bank_code" required class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">— เลือก —</option>
                            @foreach ($banks as $code => $label)
                                <option value="{{ $code }}" @selected(old('bank_code') === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">เลขที่บัญชี</label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}" required
                               inputmode="numeric" maxlength="30"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อบัญชี</label>
                        <input type="text" name="bank_account_name" value="{{ old('bank_account_name') }}" required maxlength="150"
                               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
                <p class="text-xs text-gray-500 -mt-2">ชื่อบัญชีธนาคารต้องตรงกับชื่อบนบัตรประชาชน มิฉะนั้นจะไม่ผ่านการตรวจสอบ</p>

                <hr class="border-gray-100">

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">รูปบัตรประชาชน (ด้านหน้า)</label>
                        <input type="file" name="id_card_front" accept="image/jpeg,image/png" required
                               class="w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-indigo-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">รูปบัตรประชาชน (ด้านหลัง) <span class="text-gray-400">ไม่บังคับ</span></label>
                        <input type="file" name="id_card_back" accept="image/jpeg,image/png"
                               class="w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-50 file:px-3 file:py-2 file:text-gray-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">รูปถ่ายตัวเองคู่กับบัตร</label>
                        <input type="file" name="selfie" accept="image/jpeg,image/png" required
                               class="w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-indigo-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">หน้าสมุดบัญชี / สลิปแสดงชื่อบัญชี <span class="text-gray-400">ไม่บังคับ</span></label>
                        <input type="file" name="bank_book" accept="image/jpeg,image/png"
                               class="w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-50 file:px-3 file:py-2 file:text-gray-700">
                    </div>
                </div>
                <p class="text-xs text-gray-500 -mt-2">
                    ไฟล์ JPEG หรือ PNG ไม่เกิน 5 MB ต่อรูป · ระบบจะลบข้อมูลตำแหน่ง (GPS) ที่ติดมากับรูปให้อัตโนมัติ
                    · เอกสารเก็บในพื้นที่ปิด เข้าถึงได้เฉพาะคุณและเจ้าหน้าที่ตรวจสอบ
                </p>

                <label class="flex items-start gap-3 rounded-xl bg-gray-50 border border-gray-200 p-4">
                    <input type="checkbox" name="consent" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">
                        ข้าพเจ้ายินยอมให้ XMAN Studio เก็บและใช้ข้อมูลบัตรประชาชน รูปถ่าย และข้อมูลบัญชีธนาคาร
                        เพื่อยืนยันตัวตนและตรวจสอบสิทธิ์รับเงิน และรับทราบว่าข้อมูลนี้อาจถูกเปิดเผยต่อเจ้าหน้าที่
                        เมื่อมีคำสั่งที่ชอบด้วยกฎหมาย
                    </span>
                </label>

                <button type="submit"
                        class="w-full sm:w-auto rounded-xl bg-indigo-600 px-6 py-3 text-white font-medium hover:bg-indigo-700 transition">
                    ส่งเอกสารยืนยันตัวตน
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
