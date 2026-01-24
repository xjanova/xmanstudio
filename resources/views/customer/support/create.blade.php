@extends($customerLayout ?? 'layouts.customer')

@section('title', 'สร้าง Support Ticket')
@section('page-title', 'สร้าง Support Ticket')

@push('styles')
<style>
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
</style>
@endpush

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <!-- Premium Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-500 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-cyan-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-emerald-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-teal-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

        <div class="relative">
            <div class="flex items-center space-x-3 mb-2">
                <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white">สร้าง Support Ticket</h1>
            </div>
            <p class="text-teal-100 text-lg">กรอกรายละเอียดเพื่อสร้าง Ticket ใหม่ ทีมงานจะตอบกลับโดยเร็วที่สุด</p>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <form action="{{ route('customer.support.store') }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-6">
            @csrf

            {{-- Subject --}}
            <div>
                <label for="subject" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    หัวข้อ <span class="text-red-500">*</span>
                </label>
                <input type="text" name="subject" id="subject" value="{{ old('subject') }}"
                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 transition-all @error('subject') border-red-500 @enderror"
                       placeholder="อธิบายปัญหาหรือคำถามของคุณ">
                @error('subject')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Category & Priority --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="category" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        หมวดหมู่ <span class="text-red-500">*</span>
                    </label>
                    <select name="category" id="category"
                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 transition-all @error('category') border-red-500 @enderror">
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}" {{ old('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="priority" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ความสำคัญ <span class="text-red-500">*</span>
                    </label>
                    <select name="priority" id="priority"
                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 transition-all @error('priority') border-red-500 @enderror">
                        @foreach($priorities as $value => $label)
                            <option value="{{ $value }}" {{ old('priority', 'medium') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('priority')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Related Order --}}
            @if($orders->isNotEmpty())
            <div>
                <label for="order_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    เกี่ยวข้องกับคำสั่งซื้อ (ถ้ามี)
                </label>
                <select name="order_id" id="order_id"
                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 transition-all">
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
                <label for="message" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    รายละเอียด <span class="text-red-500">*</span>
                </label>
                <textarea name="message" id="message" rows="6"
                          class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 transition-all @error('message') border-red-500 @enderror"
                          placeholder="อธิบายปัญหาหรือคำถามของคุณอย่างละเอียด รวมถึงข้อความ error (ถ้ามี)">{{ old('message') }}</textarea>
                @error('message')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Attachments --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ไฟล์แนบ</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-xl hover:border-teal-500 dark:hover:border-teal-400 transition-colors bg-gray-50 dark:bg-gray-700/50">
                    <div class="space-y-1 text-center">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-teal-100 to-cyan-100 dark:from-teal-900/30 dark:to-cyan-900/30 flex items-center justify-center mx-auto mb-3">
                            <svg class="h-8 w-8 text-teal-500 dark:text-teal-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="flex text-sm text-gray-600 dark:text-gray-400">
                            <label for="attachments" class="relative cursor-pointer rounded-md font-semibold text-teal-600 dark:text-teal-400 hover:text-teal-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-teal-500">
                                <span>อัพโหลดไฟล์</span>
                                <input id="attachments" name="attachments[]" type="file" class="sr-only" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.zip">
                            </label>
                            <p class="pl-1">หรือลากไฟล์มาวาง</p>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF, PDF, DOC, ZIP ไม่เกิน 10MB</p>
                    </div>
                </div>
                <div id="file-list" class="mt-3 space-y-2"></div>
                @error('attachments.*')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('customer.support.index') }}" class="px-5 py-2.5 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-medium transition-colors">
                    ยกเลิก
                </a>
                <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 text-white rounded-xl hover:from-emerald-600 hover:via-teal-600 hover:to-cyan-600 font-semibold shadow-lg transition-all">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        สร้าง Ticket
                    </span>
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
        div.className = 'flex items-center text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 rounded-lg px-3 py-2';
        div.innerHTML = `
            <svg class="w-4 h-4 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
            <span class="flex-1">${file.name}</span>
            <span class="text-gray-400 ml-2">(${(file.size / 1024).toFixed(1)} KB)</span>
        `;
        fileList.appendChild(div);
    }
});
</script>
@endpush
@endsection
