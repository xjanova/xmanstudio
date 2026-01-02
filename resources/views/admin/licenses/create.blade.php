@extends('layouts.admin')

@section('title', 'สร้าง License')
@section('page-title', 'สร้าง License ใหม่')

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('admin.licenses.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">รายละเอียด License</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ประเภท License <span class="text-red-500">*</span></label>
                    <select name="type" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        <option value="monthly">Monthly (30 วัน)</option>
                        <option value="yearly">Yearly (1 ปี)</option>
                        <option value="lifetime">Lifetime (ตลอดชีพ)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">จำนวน <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" value="1" min="1" max="100" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <p class="mt-1 text-sm text-gray-500">สูงสุด 100 keys ต่อครั้ง</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนเครื่องที่ใช้ได้ <span class="text-red-500">*</span></label>
                    <input type="number" name="max_activations" value="1" min="1" max="10" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                    <p class="mt-1 text-sm text-gray-500">จำนวนเครื่องที่สามารถ activate ได้ต่อ 1 license</p>
                </div>

                @if($products->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ผลิตภัณฑ์</label>
                    <select name="product_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500">
                        <option value="">-- ไม่ระบุผลิตภัณฑ์ --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.licenses.index') }}"
               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-100">ยกเลิก</a>
            <button type="submit"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">สร้าง License</button>
        </div>
    </form>
</div>
@endsection
