@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ดาวน์โหลด')
@section('page-title', 'ศูนย์ดาวน์โหลด')
@section('page-description', 'ดาวน์โหลดซอฟต์แวร์และทรัพยากรสำหรับใบอนุญาตและการสมัครสมาชิกของคุณ')

@section('content')
<!-- Licensed Products -->
@if($licensedProducts->count() > 0)
<div class="mb-8">
    <div class="flex items-center gap-2 mb-4">
        <div class="p-2 bg-primary-100 rounded-lg">
            <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
            </svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-900">ซอฟต์แวร์ที่มีใบอนุญาต</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($licensedProducts as $product)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden hover:shadow-md transition-shadow">
            @if($product->image)
            <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-40 object-cover">
            @else
            <div class="w-full h-40 bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 bg-grid-white/10"></div>
                <svg class="w-16 h-16 text-white/50 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                </svg>
            </div>
            @endif
            <div class="p-5">
                <h3 class="font-semibold text-gray-900">{{ $product->name }}</h3>
                <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ Str::limit($product->description, 80) }}</p>

                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">เวอร์ชัน:</span>
                        <span class="font-medium text-gray-900">{{ $product->version ?? '1.0.0' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">แพลตฟอร์ม:</span>
                        <span class="font-medium text-gray-900">{{ $product->platform ?? 'Windows' }}</span>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100 space-y-2">
                    @if($product->slug === 'autotradex')
                    <a href="https://github.com/xjanova/autotradex/releases/latest" target="_blank"
                       class="w-full px-4 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center justify-center font-medium transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        ดาวน์โหลดเวอร์ชันล่าสุด
                    </a>
                    <a href="{{ route('products.show', 'autotradex') }}"
                       class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center justify-center font-medium transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        คู่มือการใช้งาน
                    </a>
                    @else
                    <button class="w-full px-4 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center justify-center font-medium transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        ดาวน์โหลด
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Subscription Products -->
@if($rentalProducts->count() > 0)
<div class="mb-8">
    <div class="flex items-center gap-2 mb-4">
        <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-900">ทรัพยากรจากการสมัครสมาชิก</h2>
    </div>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-200">
            @foreach($rentalProducts as $rental)
            <div class="p-5 hover:bg-gray-50 transition-colors">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="font-medium text-gray-900">{{ $rental->rentalPackage->display_name }}</h3>
                        <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-gray-500">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                หมดอายุ: {{ $rental->expires_at->format('d/m/Y') }}
                            </span>
                            <span class="text-gray-400">({{ $rental->expires_at->diffForHumans() }})</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full">ใช้งานอยู่</span>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    <button class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        เอกสาร
                    </button>
                    <button class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition-colors">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        API Keys
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- No Downloads Available -->
@if($licensedProducts->count() === 0 && $rentalProducts->count() === 0)
<div class="bg-white rounded-xl shadow-sm p-12 text-center">
    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-6">
        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
    </div>
    <h3 class="text-lg font-semibold text-gray-900">ไม่มีไฟล์ดาวน์โหลด</h3>
    <p class="text-gray-500 mt-2">ซื้อผลิตภัณฑ์หรือสมัครสมาชิกแพ็คเกจเพื่อเข้าถึงการดาวน์โหลด</p>
    <div class="mt-6 flex flex-col sm:flex-row justify-center gap-3">
        <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            ดูผลิตภัณฑ์
        </a>
        <a href="{{ route('rental.index') }}" class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            ดูแพ็คเกจสมาชิก
        </a>
    </div>
</div>
@endif

<!-- Download Guidelines -->
<div class="mt-8 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
    <div class="flex items-start gap-4">
        <div class="flex-shrink-0 p-2 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h3 class="font-semibold text-blue-900">คำแนะนำการดาวน์โหลด</h3>
            <ul class="mt-3 space-y-2 text-sm text-blue-800">
                <li class="flex items-start">
                    <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    ดาวน์โหลดได้เฉพาะใบอนุญาตและการสมัครสมาชิกที่ยังใช้งานอยู่
                </li>
                <li class="flex items-start">
                    <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    ตรวจสอบให้แน่ใจว่าดาวน์โหลดเวอร์ชันที่ถูกต้องสำหรับระบบปฏิบัติการของคุณ
                </li>
                <li class="flex items-start">
                    <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    เตรียม License Key ของคุณไว้สำหรับการเปิดใช้งานซอฟต์แวร์
                </li>
                <li class="flex items-start">
                    <svg class="w-4 h-4 mr-2 mt-0.5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    หากมีปัญหาการดาวน์โหลด กรุณาติดต่อฝ่ายสนับสนุน
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
