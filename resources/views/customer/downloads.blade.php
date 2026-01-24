@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ดาวน์โหลด')
@section('page-title', 'ศูนย์ดาวน์โหลด')
@section('page-description', 'ดาวน์โหลดซอฟต์แวร์และทรัพยากรสำหรับใบอนุญาตและการสมัครสมาชิกของคุณ')

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 p-6 sm:p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-emerald-300/20 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </div>
                ศูนย์ดาวน์โหลด
            </h1>
            <p class="mt-2 text-white/80 text-sm sm:text-base">ดาวน์โหลดซอฟต์แวร์และทรัพยากรทั้งหมดของคุณ</p>
        </div>
    </div>
</div>

<!-- Licensed Products -->
@if($licensedProducts->count() > 0)
<div class="mb-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="p-2.5 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl shadow-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">ซอฟต์แวร์ที่มีใบอนุญาต</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($licensedProducts as $product)
        <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 border border-gray-100 dark:border-gray-700 hover:-translate-y-1">
            @if($product->image)
            <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-40 object-cover">
            @else
            <div class="w-full h-40 bg-gradient-to-br from-purple-500 via-indigo-500 to-blue-600 flex items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl animate-blob"></div>
                <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-purple-300/20 rounded-full blur-2xl animate-blob animation-delay-2000"></div>
                <svg class="w-16 h-16 text-white/50 relative" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                </svg>
            </div>
            @endif
            <div class="p-5">
                <h3 class="font-bold text-gray-900 dark:text-white text-lg">{{ $product->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ Str::limit($product->description, 80) }}</p>

                <div class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-gray-500 dark:text-gray-400">เวอร์ชัน:</span>
                        <span class="font-semibold text-gray-900 dark:text-white px-2 py-0.5 bg-gradient-to-r from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 rounded">{{ $product->version ?? '1.0.0' }}</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-gray-500 dark:text-gray-400">แพลตฟอร์ม:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $product->platform ?? 'Windows' }}</span>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-700 space-y-2">
                    @if($product->slug === 'autotradex')
                    <a href="https://github.com/xjanova/autotradex/releases/latest" target="_blank"
                       class="w-full px-4 py-3 bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-xl hover:from-emerald-600 hover:to-green-700 flex items-center justify-center font-medium transition-all shadow-lg hover:shadow-xl transform hover:scale-[1.02]">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        ดาวน์โหลดเวอร์ชันล่าสุด
                    </a>
                    <a href="{{ route('products.show', 'autotradex') }}"
                       class="w-full px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center justify-center font-medium transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        คู่มือการใช้งาน
                    </a>
                    @else
                    <button class="w-full px-4 py-3 bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-xl hover:from-emerald-600 hover:to-green-700 flex items-center justify-center font-medium transition-all shadow-lg hover:shadow-xl transform hover:scale-[1.02]">
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
    <div class="flex items-center gap-3 mb-6">
        <div class="p-2.5 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl shadow-lg">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">ทรัพยากรจากการสมัครสมาชิก</h2>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($rentalProducts as $rental)
            <div class="p-5 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="p-2.5 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl shadow-lg flex-shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $rental->rentalPackage->display_name }}</h3>
                            <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    หมดอายุ: {{ $rental->expires_at->format('d/m/Y') }}
                                </span>
                                <span class="text-gray-400 dark:text-gray-500">({{ $rental->expires_at->diffForHumans() }})</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1.5 bg-gradient-to-r from-emerald-400 to-green-500 text-white text-xs font-semibold rounded-full shadow-sm">ใช้งานอยู่</span>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2 ml-14">
                    <button class="inline-flex items-center px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium transition-all">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        เอกสาร
                    </button>
                    <button class="inline-flex items-center px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium transition-all">
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
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-12 text-center border border-gray-100 dark:border-gray-700">
    <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full mb-6 shadow-xl">
        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
    </div>
    <h3 class="text-xl font-bold text-gray-900 dark:text-white">ไม่มีไฟล์ดาวน์โหลด</h3>
    <p class="text-gray-500 dark:text-gray-400 mt-2">ซื้อผลิตภัณฑ์หรือสมัครสมาชิกแพ็คเกจเพื่อเข้าถึงการดาวน์โหลด</p>
    <div class="mt-8 flex flex-col sm:flex-row justify-center gap-3">
        <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-xl hover:from-emerald-600 hover:to-green-700 font-medium transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
            ดูผลิตภัณฑ์
        </a>
        <a href="{{ route('rental.index') }}" class="inline-flex items-center justify-center px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-all">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            ดูแพ็คเกจสมาชิก
        </a>
    </div>
</div>
@endif

<!-- Download Guidelines -->
<div class="mt-8 relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-2xl p-6 shadow-xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl animate-blob"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-purple-300/20 rounded-full blur-3xl animate-blob animation-delay-2000"></div>
    <div class="relative flex items-start gap-4">
        <div class="flex-shrink-0 p-3 bg-white/20 rounded-xl backdrop-blur-sm">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h3 class="font-bold text-white text-lg">คำแนะนำการดาวน์โหลด</h3>
            <ul class="mt-4 space-y-3 text-sm text-white/90">
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 text-emerald-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    ดาวน์โหลดได้เฉพาะใบอนุญาตและการสมัครสมาชิกที่ยังใช้งานอยู่
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 text-emerald-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    ตรวจสอบให้แน่ใจว่าดาวน์โหลดเวอร์ชันที่ถูกต้องสำหรับระบบปฏิบัติการของคุณ
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 text-emerald-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    เตรียม License Key ของคุณไว้สำหรับการเปิดใช้งานซอฟต์แวร์
                </li>
                <li class="flex items-start">
                    <svg class="w-5 h-5 mr-2 mt-0.5 text-emerald-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    หากมีปัญหาการดาวน์โหลด กรุณาติดต่อฝ่ายสนับสนุน
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
