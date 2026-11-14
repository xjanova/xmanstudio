@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตัวเลือกเสริมของแพ็กเช่า')
@section('page-title', 'ตัวเลือกเสริมของแพ็กเช่า')

@section('content')
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-pink-500 via-rose-500 to-red-500 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white">ตัวเลือกเสริม (Add-on)</h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">
                ราคาต่อหนึ่งรอบของแพ็กหลัก ใส่เป็นบาท — ซื้อทีหลังคิดตามวันที่เหลือ แล้วต่ออายุพร้อมแพ็กหลักในราคาเต็ม
            </p>
        </div>
        <a href="{{ route('admin.rentals.packages') }}"
           class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all font-medium">
            กลับไปแพ็กเกจ
        </a>
    </div>
</div>

@foreach(['success' => 'bg-green-100 border-green-400 text-green-700', 'error' => 'bg-red-100 border-red-400 text-red-700'] as $key => $classes)
    @if(session($key))
        <div class="mb-6 border px-4 py-3 rounded-xl {{ $classes }}">{{ session($key) }}</div>
    @endif
@endforeach
@if($errors->any())
    <div class="mb-6 border px-4 py-3 rounded-xl bg-red-100 border-red-400 text-red-700">{{ $errors->first() }}</div>
@endif

<div class="space-y-4">
    @forelse($addons as $addon)
        <form method="POST" action="{{ route('admin.rentals.addons.update', $addon) }}"
              class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            @csrf
            @method('PUT')
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <span class="font-mono text-xs text-gray-500">{{ $addon->product_key }} · {{ $addon->code }}</span>
                    @if($addon->exclusive_group)
                        <span class="ml-2 text-xs text-gray-500">กลุ่ม {{ $addon->exclusive_group }} (เลือกได้ระดับเดียว)</span>
                    @endif
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <span class="text-gray-500">ใช้งานอยู่ {{ $addon->active_count }} รายการ</span>
                    @if($addon->price_is_proposal)
                        <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">ราคาเสนอรอบแรก</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <label class="md:col-span-2 text-sm">
                    <span class="text-gray-600 dark:text-gray-300">ชื่อ (ไทย)</span>
                    <input type="text" name="name_th" value="{{ $addon->name_th }}" required
                           class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                </label>
                <label class="text-sm">
                    <span class="text-gray-600 dark:text-gray-300">ราคา/เดือน (บาท)</span>
                    <input type="text" name="price_monthly" inputmode="decimal" required
                           value="{{ \App\Support\Satang::toBaht($addon->price_monthly_satang) }}"
                           class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                </label>
                <label class="text-sm">
                    <span class="text-gray-600 dark:text-gray-300">ราคา/ปี (บาท)</span>
                    <input type="text" name="price_yearly" inputmode="decimal" required
                           value="{{ \App\Support\Satang::toBaht($addon->price_yearly_satang) }}"
                           class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                </label>
                <label class="text-sm">
                    <span class="text-gray-600 dark:text-gray-300">จำนวนสูงสุด{{ $addon->unit_label_th ? ' (' . $addon->unit_label_th . ')' : '' }}</span>
                    <input type="number" name="max_quantity" min="1" max="99" value="{{ $addon->max_quantity }}" required
                           class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                </label>
                <label class="text-sm">
                    <span class="text-gray-600 dark:text-gray-300">ลำดับ</span>
                    <input type="number" name="sort_order" value="{{ $addon->sort_order }}"
                           class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                </label>
                <label class="md:col-span-6 text-sm">
                    <span class="text-gray-600 dark:text-gray-300">คำอธิบาย</span>
                    <input type="text" name="description_th" value="{{ $addon->description_th }}"
                           class="mt-1 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg">
                </label>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap gap-6 text-sm text-gray-700 dark:text-gray-300">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" @checked($addon->is_active) class="rounded">
                        เปิดขาย
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="price_is_proposal" value="1" @checked($addon->price_is_proposal) class="rounded">
                        ยังเป็นราคาเสนอ (ยังไม่ยืนยัน)
                    </label>
                </div>
                <button type="submit" class="px-5 py-2 rounded-xl bg-pink-600 text-white hover:bg-pink-700 font-medium">บันทึก</button>
            </div>
        </form>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 text-center text-gray-500">ยังไม่มีตัวเลือกเสริม</div>
    @endforelse
</div>
@endsection
