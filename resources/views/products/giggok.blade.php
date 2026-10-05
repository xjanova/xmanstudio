@extends($publicLayout ?? 'layouts.app')

@section('title', 'GigGok — เลขาสาว 3D บนมือถือ ดาวน์โหลดฟรี | XMAN Studio')
@section('meta_description', $product->short_description ?: 'GigGok เลขาสาว 3D ผู้ช่วยส่วนตัวบนมือถือ ดาวน์โหลดฟรี')

@section('content')
@php
    // อ่านจาก DB อย่างเดียว เปิดหน้านี้ไม่ถาม GitHub — ปุ่มโหลดตามทัน release ใหม่เอง (read-through ใน /giggok/download)
    $latestVersion = $product->latestVersion();
    $features = is_array($product->features) ? $product->features : [];
    // ลูกค้าต้องไม่รู้ repo (กฎเจ้าของ 2026-09-24) — ล้างลิงก์ GitHub ก่อนแสดง
    $releaseNotes = \App\Support\ReleaseNotes::forCustomers($latestVersion?->changelog, \App\Support\ReleaseNotes::studioAccounts());
@endphp
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-fuchsia-950 to-gray-900">

    {{-- ============================ HERO ============================ --}}
    <section class="relative py-16 sm:py-20 overflow-hidden">
        <x-page-art art="hero-product" :opacity="30" />
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-fuchsia-500/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-pink-500/20 rounded-full blur-3xl"></div>

        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-fuchsia-300 hover:text-fuchsia-200 inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <x-bi th="กลับไปรายการผลิตภัณฑ์" en="All products" />
                </a>
            </nav>

            {{-- โหลดไม่สำเร็จ (ยังไม่มีไฟล์ / ช่องส่งไฟล์เต็ม) ServesReleaseDownloads พากลับมาที่นี่พร้อม session('error')
                 ซึ่ง layout สาธารณะแสดงให้เองแล้ว --}}
            <div class="text-center">
                <div class="inline-flex items-center px-4 py-2 bg-fuchsia-500/20 rounded-full text-fuchsia-200 text-sm mb-6 backdrop-blur-sm border border-fuchsia-500/30">
                    <x-bi th="แอป Android · ใช้ฟรี" en="Android app · Free" />
                </div>

                <h1 class="text-5xl md:text-6xl font-black text-white mb-4">
                    Gig<span class="text-transparent bg-clip-text bg-gradient-to-r from-fuchsia-400 to-pink-400">Gok</span>
                </h1>

                @if($product->short_description)
                    <p class="text-xl text-gray-200 max-w-2xl mx-auto mb-8">{{ $product->short_description }}</p>
                @endif

                <a href="{{ route('giggok.download') }}"
                   class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-fuchsia-600 to-pink-600 hover:from-fuchsia-700 hover:to-pink-700 text-white font-bold text-lg rounded-2xl shadow-lg shadow-fuchsia-500/25 transition-all transform hover:scale-105">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    ดาวน์โหลด APK
                </a>

                <p class="text-gray-400 text-sm mt-4">
                    @if($latestVersion)
                        v{{ $latestVersion->version }}
                        @if($latestVersion->file_size)
                            · {{ $latestVersion->file_size_formatted }}
                        @endif
                        ·
                    @endif
                    <x-bi th="Android 8.0 ขึ้นไป" en="Android 8.0 or later" />
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
                            <div class="flex items-start gap-3 bg-gray-800/50 rounded-xl p-5 border border-gray-700 hover:border-fuchsia-500/50 transition-colors">
                                <svg class="w-5 h-5 text-fuchsia-300 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
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

    {{-- ============================ FREE + PACKS + LINK ============================ --}}
    <section class="py-16">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 grid md:grid-cols-2 gap-6">
            <div class="bg-gray-800/50 rounded-2xl p-6 border border-gray-700">
                <h3 class="text-xl font-bold text-white mb-3"><x-bi th="ใช้ฟรี ไม่ต้องสมัคร" en="Free, no sign-up" /></h3>
                <p class="text-gray-300 text-sm">
                    ติดตั้งแล้วเปิดใช้ได้ทันที ทุกเครื่องได้ License ฟรีอัตโนมัติตอนเปิดแอปครั้งแรก
                    แอปอัปเดตตัวเองและตรวจไฟล์ด้วย SHA-256 ก่อนติดตั้งทุกครั้ง
                </p>
            </div>

            <div class="bg-gray-800/50 rounded-2xl p-6 border border-fuchsia-500/40">
                <h3 class="text-xl font-bold text-white mb-3"><x-bi th="ผูกเครื่องกับบัญชี" en="Link your phone" /></h3>
                <p class="text-gray-300 text-sm mb-4">
                    ชุดตัวมายด์ที่ซื้อบนเว็บจะขึ้นในร้านชุดของแอป เมื่อผูก License Key ของเครื่อง (ดูได้ที่หน้าตั้งค่าในแอป) กับบัญชี XMAN ของคุณ
                </p>
                <a href="{{ route('giggok.link') }}"
                   class="inline-flex items-center px-5 py-2.5 bg-fuchsia-600 hover:bg-fuchsia-700 text-white text-sm font-semibold rounded-xl transition-colors">
                    @auth
                        ผูกเครื่องกับบัญชี
                    @else
                        เข้าสู่ระบบเพื่อผูกเครื่องกับบัญชี
                    @endauth
                    →
                </a>
            </div>
        </div>
    </section>

    {{-- ============================ RELEASE NOTES ============================ --}}
    @if($releaseNotes)
        <section class="pb-16">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-white text-center mb-6"><x-bi th="มีอะไรใหม่" en="What's new" /> @if($latestVersion)<span class="text-fuchsia-300">v{{ $latestVersion->version }}</span>@endif</h2>
                <div class="bg-gray-800/50 rounded-2xl p-6 border border-gray-700">
                    <div class="prose prose-invert prose-sm max-w-none prose-a:text-fuchsia-300 prose-strong:text-white">
                        {{-- release notes เป็น markdown: แปลงเป็น HTML เอง ตัด HTML ดิบทิ้ง --}}
                        {!! Str::markdown($releaseNotes, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                </div>
            </div>
        </section>
    @endif

</div>
@endsection
