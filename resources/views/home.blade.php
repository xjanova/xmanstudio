@extends('layouts.app')

@section('title', 'XMAN Studio - IT Solutions & Software Development ครบวงจร')

@section('content')
<!-- Hero Section -->
<div class="relative bg-gradient-to-r from-primary-600 to-primary-800 overflow-hidden">
    <div class="max-w-7xl mx-auto">
        <div class="relative z-10 pb-8 sm:pb-16 md:pb-20 lg:pb-28 xl:pb-32">
            <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                <div class="text-center">
                    <h1 class="text-4xl tracking-tight font-extrabold text-white sm:text-5xl md:text-6xl">
                        <span class="block">XMAN STUDIO</span>
                        <span class="block text-primary-200">IT Solutions ครบวงจร</span>
                    </h1>
                    <p class="mt-3 max-w-md mx-auto text-base text-primary-100 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                        ผู้เชี่ยวชาญด้านพัฒนาซอฟต์แวร์ เว็บไซต์ แอพพลิเคชัน Blockchain IoT AI และบริการ IT ครบวงจร พร้อมระบบจัดการสิทธิ์การใช้งานที่ทันสมัย
                    </p>
                    <div class="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8">
                        <div class="rounded-md shadow">
                            <a href="#services" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-primary-700 bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10">
                                ดูบริการของเรา
                            </a>
                        </div>
                        <div class="mt-3 rounded-md shadow sm:mt-0 sm:ml-3">
                            <a href="#contact" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-900 hover:bg-primary-950 md:py-4 md:text-lg md:px-10">
                                ติดต่อเรา
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<!-- Services Section -->
<div id="services" class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                บริการของเรา
            </h2>
            <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
                เราให้บริการด้าน IT ครบวงจร พร้อมทีมงานมืออาชีพ
            </p>
        </div>

        <div class="mt-16">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Blockchain -->
                <div class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-primary-500 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-primary-50 text-primary-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <a href="/services/blockchain" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                Blockchain Development
                            </a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            พัฒนาโซลูชั่น Blockchain, Smart Contracts, DApp และ Cryptocurrency
                        </p>
                    </div>
                </div>

                <!-- Web Development -->
                <div class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-primary-500 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-primary-50 text-primary-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <a href="/services/web" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                เว็บไซต์ล้ำสมัย
                            </a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            ออกแบบและพัฒนาเว็บไซต์สมัยใหม่ Responsive รองรับทุกอุปกรณ์
                        </p>
                    </div>
                </div>

                <!-- Mobile App -->
                <div class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-primary-500 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-primary-50 text-primary-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <a href="/services/mobile" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                แอพพลิเคชันทุกชนิด
                            </a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            พัฒนาแอพ iOS, Android ด้วย Flutter รองรับทุกแพลตฟอร์ม
                        </p>
                    </div>
                </div>

                <!-- IoT -->
                <div class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-primary-500 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-primary-50 text-primary-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <a href="/services/iot" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                IoT Solutions
                            </a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            ออกแบบและพัฒนาระบบ Internet of Things ครบวงจร
                        </p>
                    </div>
                </div>

                <!-- Network & Security -->
                <div class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-primary-500 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-primary-50 text-primary-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <a href="/services/network-security" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                Network & Security
                            </a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            ออกแบบ ติดตั้งระบบ Network และ IT Security ที่ปลอดภัย
                        </p>
                    </div>
                </div>

                <!-- Custom Software -->
                <div class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-primary-500 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-primary-50 text-primary-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <a href="/services/custom-software" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                Custom Software
                            </a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            เขียนโปรแกรมเฉพาะธุรกิจตามความต้องการ
                        </p>
                    </div>
                </div>

                <!-- AI Services -->
                <div class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-primary-500 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-primary-50 text-primary-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <a href="/services/ai" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                AI Services
                            </a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            วีดีโอ AI, สื่อโฆษณา AI, เพลง AI และบริการ AI อื่นๆ
                        </p>
                    </div>
                </div>

                <!-- Flutter/Android -->
                <div class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-primary-500 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-primary-50 text-primary-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <a href="/services/flutter" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                Flutter & Android Studio
                            </a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            พัฒนาแอพด้วย Flutter บน Android Studio แบบมืออาชีพ
                        </p>
                    </div>
                </div>

                <!-- More Services -->
                <div class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-primary-500 rounded-lg shadow-lg hover:shadow-xl transition-shadow">
                    <div>
                        <span class="rounded-lg inline-flex p-3 bg-primary-50 text-primary-700 ring-4 ring-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                            </svg>
                        </span>
                    </div>
                    <div class="mt-8">
                        <h3 class="text-lg font-medium">
                            <a href="/services" class="focus:outline-none">
                                <span class="absolute inset-0" aria-hidden="true"></span>
                                บริการอื่นๆ
                            </a>
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            และบริการด้าน IT อื่นๆ อีกมากมาย ตามความต้องการ
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                ระบบการจัดการที่ครบครัน
            </h2>
            <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
                เราให้บริการพร้อมระบบจัดการที่ทันสมัยและครบครัน
            </p>
        </div>

        <div class="mt-16">
            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">🛒 ระบบตะกร้าสินค้า</h3>
                    <p class="text-gray-600">เลือกซื้อซอฟต์แวร์ได้สะดวก มีระบบชำระเงินที่ปลอดภัย</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">🔑 ระบบ License Key</h3>
                    <p class="text-gray-600">จัดการสิทธิ์การใช้งานอัตโนมัติ ทันสมัย ปลอดภัย</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">📋 ระบบสั่งซื้อแบบกำหนดเอง</h3>
                    <p class="text-gray-600">สั่งทำซอฟต์แวร์ตามความต้องการ พร้อมดาวน์โหลดใบสั่งซื้อ</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">💬 Line OA Integration</h3>
                    <p class="text-gray-600">รับการแจ้งเตือนคำสั่งซื้อผ่าน Line OA อัตโนมัติ</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">🎫 Support Ticket System</h3>
                    <p class="text-gray-600">ระบบแจ้งปัญหาและติดตามสถานะได้ตลอดเวลา</p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">⚙️ Admin Backend</h3>
                    <p class="text-gray-600">ระบบจัดการหลังบ้านที่ครบครันและใช้งานง่าย</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Portfolio Section -->
<div id="portfolio" class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                ตัวอย่างผลงาน
            </h2>
            <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
                ดูผลงานคุณภาพของเราได้ที่
            </p>
        </div>

        <div class="mt-12 text-center">
            <div class="inline-flex items-center justify-center p-8 bg-red-50 rounded-lg">
                <svg class="w-12 h-12 text-red-600 mr-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
                <div class="text-left">
                    <h3 class="text-2xl font-bold text-gray-900">Metal-X Project</h3>
                    <p class="text-gray-600">ช่อง YouTube ที่รวมผลงานด้าน AI และเทคโนโลยี</p>
                    <a href="https://youtube.com/@metal-xproject" target="_blank" class="mt-2 inline-block text-primary-600 hover:text-primary-800 font-semibold">
                        ดูผลงานของเรา →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div id="contact" class="bg-primary-700">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:py-16 lg:px-8 lg:flex lg:items-center lg:justify-between">
        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
            <span class="block">พร้อมที่จะเริ่มโปรเจคของคุณ?</span>
            <span class="block text-primary-200">ติดต่อเราวันนี้</span>
        </h2>
        <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
            <div class="inline-flex rounded-md shadow">
                <a href="/products" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-primary-700 bg-white hover:bg-primary-50">
                    ดูผลิตภัณฑ์
                </a>
            </div>
            <div class="ml-3 inline-flex rounded-md shadow">
                <a href="/support" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary-900 hover:bg-primary-950">
                    ติดต่อเรา
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Technology Stack -->
<div class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-gray-900 sm:text-4xl">
                เทคโนโลยีที่เราใช้
            </h2>
        </div>
        <div class="grid grid-cols-2 gap-8 md:grid-cols-4 lg:grid-cols-6 items-center justify-items-center">
            <div class="text-center">
                <div class="text-4xl mb-2">⚛️</div>
                <p class="text-sm text-gray-600">React</p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-2">📱</div>
                <p class="text-sm text-gray-600">Flutter</p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-2">🎯</div>
                <p class="text-sm text-gray-600">Laravel</p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-2">🔗</div>
                <p class="text-sm text-gray-600">Blockchain</p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-2">🤖</div>
                <p class="text-sm text-gray-600">AI/ML</p>
            </div>
            <div class="text-center">
                <div class="text-4xl mb-2">☁️</div>
                <p class="text-sm text-gray-600">Cloud</p>
            </div>
        </div>
    </div>
</div>
@endsection
