@extends($customerLayout ?? 'layouts.customer')

@section('title', $profile->name . ' - Tping Data Profile')
@section('page-title', $profile->name)
@section('page-description')<x-bi th="รายละเอียด Data Profile" en="Data Profile details" />@endsection

@section('content')
<!-- Back Link -->
<div class="mb-6">
    <a href="{{ route('customer.tping.data-profiles.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-emerald-600 transition-colors">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <x-bi th="กลับไปรายการ Data Profile" en="Back to Data Profiles" />
    </a>
</div>

@if(session('success'))
    <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center gap-3">
        <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
@endif

<!-- Info Card -->
<div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-gray-100">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $profile->name }}</h2>
            @if($profile->category)
                <p class="text-sm text-gray-500 mt-1">
                    <x-bi th="หมวดหมู่" en="Category" />:
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                        {{ $profile->category }}
                    </span>
                </p>
            @endif
            <p class="text-xs text-gray-400 mt-1">
                <x-bi th="สร้างเมื่อ" en="Created" /> {{ $profile->created_at->format('d/m/Y H:i') }}
                &middot; <x-bi th="อัปเดตเมื่อ" en="Updated" /> {{ $profile->updated_at->diffForHumans() }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('customer.tping.data-profiles.edit', $profile) }}" class="px-4 py-2 bg-blue-50 text-blue-600 rounded-xl text-sm font-medium hover:bg-blue-100 transition-all">
                <x-bi k="common.edit" />
            </a>
            <form method="POST" action="{{ route('customer.tping.data-profiles.destroy', $profile) }}" onsubmit="return confirm('ลบ profile นี้? / Delete this profile?')" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 bg-red-50 text-red-600 rounded-xl text-sm font-medium hover:bg-red-100 transition-all">
                    <x-bi k="common.delete" />
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Fields Data -->
<div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
    <h3 class="text-lg font-bold text-gray-900 mb-4"><x-bi th="ข้อมูล" en="Data" /> ({{ count($fields) }} fields)</h3>

    @if(empty($fields))
        <p class="text-gray-400 text-center py-8"><x-bi th="ไม่มีข้อมูล" en="No data" /></p>
    @else
        <div class="space-y-2">
            @foreach($fields as $key => $value)
                <div class="flex items-start gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                    <div class="flex-shrink-0 min-w-[120px]">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-100 text-emerald-700 font-mono">
                            {{ $key }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-700 break-all">{{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : ($value ?: '-') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
