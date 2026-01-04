@extends('layouts.customer')

@section('title', 'สร้าง Support Ticket')
@section('page-title', 'สร้าง Support Ticket')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">ขอความช่วยเหลือ</h2>
            <p class="mt-1 text-sm text-gray-600">กรอกรายละเอียดเพื่อสร้าง Ticket ใหม่ ทีมงานจะตอบกลับโดยเร็วที่สุด</p>
        </div>

        <form action="{{ route('customer.support.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            {{-- Subject --}}
            <div>
                <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">หัวข้อ <span class="text-red-500">*</span></label>
                <input type="text" name="subject" id="subject" value="{{ old('subject') }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('subject') border-red-500 @enderror"
                       placeholder="อธิบายปัญหาหรือคำถามของคุณ">
                @error('subject')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Category & Priority --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่ <span class="text-red-500">*</span></label>
                    <select name="category" id="category"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('category') border-red-500 @enderror">
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}" {{ old('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">ความสำคัญ <span class="text-red-500">*</span></label>
                    <select name="priority" id="priority"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('priority') border-red-500 @enderror">
                        @foreach($priorities as $value => $label)
                            <option value="{{ $value }}" {{ old('priority', 'medium') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('priority')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Related Order --}}
            @if($orders->isNotEmpty())
            <div>
                <label for="order_id" class="block text-sm font-medium text-gray-700 mb-1">เกี่ยวข้องกับคำสั่งซื้อ (ถ้ามี)</label>
                <select name="order_id" id="order_id"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">- ไม่เกี่ยวข้องกับคำสั่งซื้อ -</option>
                    @foreach($orders as $order)
                        <option value="{{ $order->id }}" {{ old('order_id', $preselectedOrder) == $order->id ? 'selected' : '' }}>
                            #{{ $order->order_number }} - {{ number_format($order->total_amount) }} บาท ({{ $order->created_at->format('d/m/Y') }})
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Message --}}
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด <span class="text-red-500">*</span></label>
                <textarea name="message" id="message" rows="6"
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 @error('message') border-red-500 @enderror"
                          placeholder="อธิบายปัญหาหรือคำถามของคุณอย่างละเอียด รวมถึงข้อความ error (ถ้ามี)">{{ old('message') }}</textarea>
                @error('message')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Attachments --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ไฟล์แนบ</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-primary-500 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="attachments" class="relative cursor-pointer rounded-md font-medium text-primary-600 hover:text-primary-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                <span>อัพโหลดไฟล์</span>
                                <input id="attachments" name="attachments[]" type="file" class="sr-only" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip">
                            </label>
                            <p class="pl-1">หรือลากไฟล์มาวาง</p>
                        </div>
                        <p class="text-xs text-gray-500">PNG, JPG, GIF, PDF, DOC, ZIP ไม่เกิน 10MB</p>
                    </div>
                </div>
                <div id="file-list" class="mt-2 space-y-1"></div>
                @error('attachments.*')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                <a href="{{ route('customer.support.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">
                    ยกเลิก
                </a>
                <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                    สร้าง Ticket
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('attachments').addEventListener('change', function(e) {
    const fileList = document.getElementById('file-list');
    fileList.innerHTML = '';

    for (const file of e.target.files) {
        const div = document.createElement('div');
        div.className = 'flex items-center text-sm text-gray-600';
        div.innerHTML = `
            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
            ${file.name} (${(file.size / 1024).toFixed(1)} KB)
        `;
        fileList.appendChild(div);
    }
});
</script>
@endpush
@endsection
