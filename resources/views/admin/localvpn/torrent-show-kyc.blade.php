@extends($adminLayout ?? 'layouts.admin')

@section('title', 'BitTorrent - KYC Details')
@section('page-title', 'LocalVPN - รายละเอียด KYC')

@section('content')
{{-- Back Button --}}
<div class="mb-6">
    <a href="{{ route('admin.localvpn.torrent.kyc') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        กลับไปหน้า KYC
    </a>
</div>

{{-- KYC Info Card --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $kyc->display_name }}</h2>
            <div class="flex items-center gap-2">
                @switch($kyc->status)
                    @case('pending')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">รอตรวจ</span>
                        @break
                    @case('approved')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">อนุมัติแล้ว</span>
                        @break
                    @case('rejected')
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">ถูกปฏิเสธ</span>
                        @break
                @endswitch
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
        <div>
            <span class="text-gray-500">Machine ID:</span>
            <p class="font-mono text-xs text-gray-900 mt-0.5">{{ $kyc->machine_id }}</p>
        </div>
        <div>
            <span class="text-gray-500">ชื่อที่แสดง:</span>
            <p class="text-gray-900 font-medium mt-0.5">{{ $kyc->display_name }}</p>
        </div>
        <div>
            <span class="text-gray-500">วันเกิด:</span>
            <p class="text-gray-900 mt-0.5">
                {{ $kyc->birth_date ? \Carbon\Carbon::parse($kyc->birth_date)->format('d/m/Y') : '-' }}
            </p>
        </div>
        <div>
            <span class="text-gray-500">อายุ:</span>
            <p class="text-gray-900 mt-0.5">
                @if($kyc->birth_date)
                    {{ \Carbon\Carbon::parse($kyc->birth_date)->age }} ปี
                @else
                    -
                @endif
            </p>
        </div>
        <div>
            <span class="text-gray-500">ส่งเมื่อ:</span>
            <p class="text-gray-900 mt-0.5">{{ $kyc->created_at?->format('d/m/Y H:i:s') }}</p>
        </div>
        @if($kyc->reviewed_at)
        <div>
            <span class="text-gray-500">ตรวจเมื่อ:</span>
            <p class="text-gray-900 mt-0.5">{{ $kyc->reviewed_at?->format('d/m/Y H:i:s') }}</p>
        </div>
        <div>
            <span class="text-gray-500">ผู้ตรวจ:</span>
            <p class="text-gray-900 mt-0.5">{{ $kyc->reviewer->name ?? 'N/A' }}</p>
        </div>
        @endif
    </div>
</div>

{{-- ID Card Images --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    {{-- Front ID --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">บัตรประชาชน (ด้านหน้า)</h4>
        @if($kyc->id_card_front_url)
            @if(str_starts_with($kyc->id_card_front_url, 'data:'))
                <img src="{{ $kyc->id_card_front_url }}" alt="ID Card Front" class="w-full rounded-lg border border-gray-200">
            @else
                <img src="{{ $kyc->id_card_front_url }}" alt="ID Card Front" class="w-full rounded-lg border border-gray-200">
            @endif
        @else
            <div class="w-full h-48 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                ไม่มีรูปภาพ
            </div>
        @endif
    </div>

    {{-- Back ID --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">บัตรประชาชน (ด้านหลัง)</h4>
        @if($kyc->id_card_back_url)
            @if(str_starts_with($kyc->id_card_back_url, 'data:'))
                <img src="{{ $kyc->id_card_back_url }}" alt="ID Card Back" class="w-full rounded-lg border border-gray-200">
            @else
                <img src="{{ $kyc->id_card_back_url }}" alt="ID Card Back" class="w-full rounded-lg border border-gray-200">
            @endif
        @else
            <div class="w-full h-48 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                ไม่มีรูปภาพ
            </div>
        @endif
    </div>

    {{-- Selfie --}}
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">รูปเซลฟี่</h4>
        @if($kyc->selfie_url)
            @if(str_starts_with($kyc->selfie_url, 'data:'))
                <img src="{{ $kyc->selfie_url }}" alt="Selfie" class="w-full rounded-lg border border-gray-200">
            @else
                <img src="{{ $kyc->selfie_url }}" alt="Selfie" class="w-full rounded-lg border border-gray-200">
            @endif
        @else
            <div class="w-full h-48 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                ไม่มีรูปภาพ
            </div>
        @endif
    </div>
</div>

{{-- Review Actions / Admin Note --}}
@if($kyc->status === 'pending')
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">ตรวจสอบ KYC</h3>
    <form method="POST" action="{{ route('admin.localvpn.torrent.kyc.review', $kyc) }}">
        @csrf
        <div class="mb-4">
            <label for="admin_note" class="block text-sm font-medium text-gray-700 mb-1">หมายเหตุ Admin</label>
            <textarea name="admin_note" id="admin_note" rows="3" placeholder="เหตุผลในการอนุมัติ/ปฏิเสธ (ไม่บังคับ)..."
                      class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">{{ old('admin_note') }}</textarea>
            @error('admin_note')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex gap-3">
            <button type="submit" name="action" value="approve"
                    class="px-6 py-2.5 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors shadow-sm inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                อนุมัติ
            </button>
            <button type="submit" name="action" value="reject"
                    class="px-6 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors shadow-sm inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                ปฏิเสธ
            </button>
        </div>
    </form>
</div>
@else
{{-- Already Reviewed --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">ผลการตรวจสอบ</h3>
    <div class="space-y-3">
        <div>
            <span class="text-sm text-gray-500">สถานะ:</span>
            @if($kyc->status === 'approved')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-1">อนุมัติ</span>
            @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-1">ปฏิเสธ</span>
            @endif
        </div>
        @if($kyc->admin_note)
        <div>
            <span class="text-sm text-gray-500">หมายเหตุ:</span>
            <p class="mt-1 text-gray-900 bg-gray-50 rounded-lg p-3">{{ $kyc->admin_note }}</p>
        </div>
        @endif
        <div>
            <span class="text-sm text-gray-500">ตรวจโดย:</span>
            <span class="text-gray-900 ml-1">{{ $kyc->reviewer->name ?? 'N/A' }}</span>
        </div>
        <div>
            <span class="text-sm text-gray-500">เมื่อ:</span>
            <span class="text-gray-900 ml-1">{{ $kyc->reviewed_at?->format('d/m/Y H:i:s') }}</span>
        </div>
    </div>
</div>
@endif
@endsection
