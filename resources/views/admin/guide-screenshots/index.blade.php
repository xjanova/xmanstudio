@extends($adminLayout ?? 'layouts.admin')

@section('title', 'จัดการรูปคู่มือ - ' . $product->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.products.versions.index', $product) }}" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">รูปคู่มือติดตั้ง: {{ $product->name }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                อัพโหลดรูปแคปหน้าจอจริง — ถ้าไม่อัพโหลดจะแสดง mockup อัตโนมัติ
                | <a href="{{ route('tping.install-guide') }}" target="_blank" class="text-violet-600 hover:underline">ดูหน้า Guide &rarr;</a>
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    <!-- Steps Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($steps as $step => $label)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{-- Preview --}}
                <div class="aspect-[9/16] max-h-80 bg-gray-100 dark:bg-gray-900 flex items-center justify-center overflow-hidden">
                    @if(isset($screenshots[$step]))
                        <img src="{{ Storage::url($screenshots[$step]) }}" alt="Step {{ $step }}" class="w-full h-full object-contain">
                    @else
                        <div class="text-center p-4">
                            <div class="w-16 h-16 mx-auto bg-gray-200 dark:bg-gray-700 rounded-2xl flex items-center justify-center mb-3">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500">ยังไม่มีรูป</p>
                            <p class="text-xs text-gray-400 mt-1">ใช้ mockup อัตโนมัติ</p>
                        </div>
                    @endif
                </div>

                {{-- Info & Actions --}}
                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="flex-shrink-0 w-7 h-7 bg-violet-600 text-white rounded-full flex items-center justify-center text-sm font-bold">{{ $step }}</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</span>
                    </div>

                    {{-- Upload Form --}}
                    <form action="{{ route('admin.guide-screenshots.upload', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                        @csrf
                        <input type="hidden" name="step" value="{{ $step }}">
                        <input type="file" name="screenshot" accept="image/png,image/jpeg,image/webp"
                               class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100 dark:file:bg-violet-900/30 dark:file:text-violet-300">
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 px-3 py-1.5 bg-violet-600 hover:bg-violet-700 text-white text-xs rounded-lg transition">
                                {{ isset($screenshots[$step]) ? 'เปลี่ยนรูป' : 'อัพโหลด' }}
                            </button>
                            @if(isset($screenshots[$step]))
                                <a href="{{ route('admin.guide-screenshots.destroy', [$product, $step]) }}"
                                   onclick="event.preventDefault(); if(confirm('ลบรูปนี้? จะกลับไปใช้ mockup')) document.getElementById('delete-step-{{ $step }}').submit();"
                                   class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs rounded-lg transition">
                                    ลบ
                                </a>
                            @endif
                        </div>
                    </form>

                    @if(isset($screenshots[$step]))
                        <form id="delete-step-{{ $step }}" action="{{ route('admin.guide-screenshots.destroy', [$product, $step]) }}" method="POST" class="hidden">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tips --}}
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-5">
        <h3 class="font-semibold text-amber-800 dark:text-amber-200 mb-2">แนะนำ</h3>
        <ul class="text-sm text-amber-700 dark:text-amber-300 space-y-1 list-disc list-inside">
            <li>ใช้รูปแคปจากมือถือจริง ขนาดแนะนำ <strong>1080x1920px</strong> (Full HD Portrait)</li>
            <li>รองรับไฟล์ PNG, JPG, WebP ขนาดไม่เกิน 5 MB</li>
            <li>ถ้าลบรูป ระบบจะกลับไปแสดง mockup อัตโนมัติ</li>
            <li>ภาพจะแสดงในกรอบมือถือบนหน้า <a href="{{ route('tping.install-guide') }}" target="_blank" class="underline">คู่มือติดตั้ง</a></li>
        </ul>
    </div>
</div>
@endsection
