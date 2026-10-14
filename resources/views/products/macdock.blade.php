@extends($publicLayout ?? 'layouts.app')
{{-- Coming Soon = ปุ่มดาวน์โหลดเป็นป้าย (กฎเจ้าของ 2026-10-12) --}}
@php $csDownload = \App\Models\Product::slugIsComingSoon('macdock'); @endphp

@section('title', 'MacDock — Dock สไตล์ macOS สำหรับ Windows ดาวน์โหลดฟรี | XMAN Studio')
@section('meta_description', $product->short_description ?: 'MacDock Dock สไตล์ macOS สำหรับ Windows ใช้ฟรี')

@section('content')
@php
    // อ่านจาก DB อย่างเดียว เปิดหน้านี้ไม่ถาม GitHub (read-through ใน /macdock/download)
    $latestVersion = $product->latestVersion();
    $features = is_array($product->features) ? $product->features : [];
    // ลูกค้าต้องไม่รู้ repo (กฎเจ้าของ 2026-09-24) — ล้างลิงก์ GitHub ก่อนแสดง
    $releaseNotes = \App\Support\ReleaseNotes::forCustomers($latestVersion?->changelog, \App\Support\ReleaseNotes::studioAccounts());
@endphp
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-sky-950 to-gray-900">

    {{-- ============================ HERO ============================ --}}
    <section class="relative py-16 sm:py-20 overflow-hidden">
        <x-page-art art="hero-product" :opacity="30" />
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-sky-500/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl"></div>

        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-sky-300 hover:text-sky-200 inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <x-bi th="กลับไปรายการผลิตภัณฑ์" en="All products" />
                </a>
            </nav>

            {{-- โหลดไม่สำเร็จ ServesReleaseDownloads พากลับมาที่นี่พร้อม session('error') ซึ่ง layout สาธารณะแสดงให้เอง --}}
            <div class="text-center">
                <div class="inline-flex items-center px-4 py-2 bg-sky-500/20 rounded-full text-sky-200 text-sm mb-6 backdrop-blur-sm border border-sky-500/30">
                    <x-bi th="โปรแกรม Windows · ใช้ฟรี" en="Windows app · Free" />
                </div>

                <h1 class="text-5xl md:text-6xl font-black text-white mb-4">
                    Mac<span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-400 to-indigo-400">Dock</span>
                </h1>

                @if($product->short_description)
                    <p class="text-xl text-gray-200 max-w-2xl mx-auto mb-8">{{ $product->short_description }}</p>
                @endif

                @if($csDownload)
                    <span class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-sky-600 to-indigo-600 text-white font-bold text-lg rounded-2xl shadow-lg shadow-sky-500/25" style="cursor: not-allowed; opacity: .8;" role="status" aria-disabled="true" data-coming-soon>Coming Soon — ยังไม่เปิดให้ดาวน์โหลด</span>
                @else
                <a href="{{ route('macdock.download') }}"
                   class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-sky-600 to-indigo-600 hover:from-sky-700 hover:to-indigo-700 text-white font-bold text-lg rounded-2xl shadow-lg shadow-sky-500/25 transition-all transform hover:scale-105">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    ดาวน์โหลดสำหรับ Windows
                </a>
                @endif

                <p class="text-gray-400 text-sm mt-4">
                    @if($latestVersion)
                        v{{ $latestVersion->version }}
                        @if($latestVersion->file_size)
                            · {{ $latestVersion->file_size_formatted }}
                        @endif
                        ·
                    @endif
                    <x-bi th="Windows 10/11 (64-bit) · ไฟล์เดียว ไม่ต้องติดตั้ง" en="Windows 10/11 (64-bit) · single file, no installer" />
                </p>
            </div>
        </div>
    </section>

    {{-- ============================ FEATURES ============================ --}}
    @if(count($features) > 0)
        <section class="py-16 border-y border-white/5 bg-gray-900/40">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-white text-center mb-10"><x-bi th="ทำอะไรได้บ้าง" en="What it does" layout="stack" /></h2>

                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($features as $feature)
                        @if(is_string($feature) && trim($feature) !== '')
                            <div class="flex items-start gap-3 bg-gray-800/50 rounded-xl p-5 border border-gray-700 hover:border-sky-500/50 transition-colors">
                                <svg class="w-5 h-5 text-sky-300 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-200">{{ trim($feature) }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================ FREE + LICENCE ============================ --}}
    <section class="py-16">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 grid md:grid-cols-2 gap-6">
            <div class="bg-gray-800/50 rounded-2xl p-6 border border-gray-700">
                <h3 class="text-xl font-bold text-white mb-3"><x-bi th="ใช้ฟรีทุกฟีเจอร์" en="Every feature is free" /></h3>
                <p class="text-gray-300 text-sm">
                    ดาวน์โหลด แตกไฟล์ แล้วเปิด MacDock.exe ได้ทันที ไม่ต้องติดตั้ง ไม่ต้องสมัคร
                </p>
            </div>

            <div class="bg-gray-800/50 rounded-2xl p-6 border border-sky-500/40">
                <h3 class="text-xl font-bold text-white mb-3"><x-bi th="ไลเซ่นฟรี = อัปเดตอัตโนมัติ" en="Free licence = automatic updates" /></h3>
                <p class="text-gray-300 text-sm">
                    ในโปรแกรมกด <strong>รับไลเซ่นฟรี</strong> → เข้าสู่ระบบหรือสมัครสมาชิก → กดยืนยัน
                    ไลเซ่นจะผูกกับบัญชีและเครื่องนั้น แล้วโปรแกรมจะอัปเดตตัวเองจาก xman4289.com และตรวจไฟล์ด้วย SHA-256 ก่อนติดตั้งทุกครั้ง
                </p>
            </div>
        </div>
    </section>

    {{-- ============================ RELEASE NOTES ============================ --}}
    @if($releaseNotes)
        <section class="pb-16">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-white text-center mb-6"><x-bi th="มีอะไรใหม่" en="What's new" /> @if($latestVersion)<span class="text-sky-300">v{{ $latestVersion->version }}</span>@endif</h2>
                <div class="bg-gray-800/50 rounded-2xl p-6 border border-gray-700">
                    <div class="prose prose-invert prose-sm max-w-none prose-a:text-sky-300 prose-strong:text-white">
                        {{-- release notes เป็น markdown: แปลงเป็น HTML เอง ตัด HTML ดิบทิ้ง --}}
                        {!! Str::markdown($releaseNotes, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                </div>
            </div>
        </section>
    @endif

</div>
@endsection
