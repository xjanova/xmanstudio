@extends($publicLayout ?? 'layouts.app')

@section('title', 'PhoneX Manager - Android Device Management | XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-green-900 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%2310B981\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-green-400 hover:text-green-300 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับไปรายการผลิตภัณฑ์
                </a>
            </nav>

            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Product Info -->
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-green-500/20 rounded-full text-green-300 text-sm mb-6 backdrop-blur-sm border border-green-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Android Device Management
                    </div>

                    <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                        Phone<span class="text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-emerald-400">X</span> Manager
                    </h1>

                    <p class="text-xl text-gray-300 mb-8">
                        ระบบจัดการอุปกรณ์ Android ผ่าน ADB จากคอมพิวเตอร์ รองรับการจัดการหลายเครื่องพร้อมกัน ติดตั้ง APK, จัดการไฟล์, และควบคุมอุปกรณ์แบบ Remote
                    </p>

                    <!-- Key Features Tags -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        <span class="px-3 py-1 bg-green-500/20 text-green-300 rounded-full text-sm border border-green-500/30">Multi-Device</span>
                        <span class="px-3 py-1 bg-emerald-500/20 text-emerald-300 rounded-full text-sm border border-emerald-500/30">ADB Integration</span>
                        <span class="px-3 py-1 bg-teal-500/20 text-teal-300 rounded-full text-sm border border-teal-500/30">Remote Control</span>
                        <span class="px-3 py-1 bg-lime-500/20 text-lime-300 rounded-full text-sm border border-lime-500/30">Batch Operations</span>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap gap-4">
                        @auth
                            @if($hasPurchased)
                                <a href="{{ route('customer.downloads') }}"
                                   class="px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-green-500/25">
                                    ดาวน์โหลด
                                </a>
                            @else
                                <a href="{{ route('products.index') }}"
                                   class="px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25">
                                    ดูแพคเกจ
                                </a>
                            @endif
                        @else
                            <a href="{{ route('products.index') }}"
                               class="px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25">
                                ดูแพคเกจ
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Right: Preview Image -->
                <div class="relative">
                    <div class="bg-gradient-to-br from-green-500/20 to-emerald-500/20 rounded-2xl p-8 backdrop-blur-sm border border-green-500/30">
                        <div class="aspect-video bg-gray-800 rounded-xl flex items-center justify-center overflow-hidden">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="PhoneX Manager" class="w-full h-full object-cover">
                            @else
                                <div class="text-center">
                                    <svg class="w-24 h-24 mx-auto text-green-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-gray-400">PhoneX Manager Interface</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">ฟีเจอร์หลัก</h2>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-green-500/50 transition-all">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Multi-Device Support</h3>
                    <p class="text-gray-400">จัดการอุปกรณ์ Android หลายเครื่องพร้อมกัน ทั้ง USB และ WiFi ADB</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-green-500/50 transition-all">
                    <div class="w-12 h-12 bg-emerald-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">APK Management</h3>
                    <p class="text-gray-400">ติดตั้ง, ถอนการติดตั้ง APK แบบ Batch พร้อมดู App Info</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-green-500/50 transition-all">
                    <div class="w-12 h-12 bg-teal-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">File Manager</h3>
                    <p class="text-gray-400">จัดการไฟล์บนอุปกรณ์ ถ่ายโอนไฟล์ระหว่าง PC และ Android</p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-green-500/50 transition-all">
                    <div class="w-12 h-12 bg-lime-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Screenshot & Record</h3>
                    <p class="text-gray-400">จับภาพหน้าจอและบันทึกวิดีโอจากอุปกรณ์ Android</p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-green-500/50 transition-all">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Shell Commands</h3>
                    <p class="text-gray-400">รัน ADB Shell Commands โดยตรง พร้อม Command History</p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-green-500/50 transition-all">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Device Monitoring</h3>
                    <p class="text-gray-400">แสดงสถานะอุปกรณ์ แบตเตอรี่ หน่วยความจำ และ CPU Usage</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Device Management -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">การจัดการอุปกรณ์</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">รองรับการเชื่อมต่อทั้ง USB และ WiFi ADB สำหรับความยืดหยุ่นสูงสุด</p>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- USB Connection -->
                <div class="bg-gradient-to-br from-green-500/10 to-emerald-500/10 rounded-xl p-6 border border-green-500/30">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">USB Connection</h3>
                    </div>
                    <ul class="space-y-2 text-gray-300">
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-3"></span>
                            ตรวจจับอุปกรณ์อัตโนมัติเมื่อเสียบสาย
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-3"></span>
                            ไม่ต้องตั้งค่าเพิ่มเติม
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-3"></span>
                            ความเร็วถ่ายโอนสูงสุด
                        </li>
                    </ul>
                </div>

                <!-- WiFi Connection -->
                <div class="bg-gradient-to-br from-emerald-500/10 to-teal-500/10 rounded-xl p-6 border border-emerald-500/30">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">WiFi ADB</h3>
                    </div>
                    <ul class="space-y-2 text-gray-300">
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full mr-3"></span>
                            เชื่อมต่อแบบไร้สายในเครือข่ายเดียวกัน
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full mr-3"></span>
                            จัดการหลายเครื่องโดยไม่ต้องใช้สาย
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full mr-3"></span>
                            รองรับ Android 11+ WiFi Pairing
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Batch Operations -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">Batch Operations</h2>

            <div class="grid md:grid-cols-4 gap-6">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center">
                    <div class="w-14 h-14 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Batch Install</h3>
                    <p class="text-gray-400 text-sm">ติดตั้ง APK หลายไฟล์พร้อมกันในหลายอุปกรณ์</p>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center">
                    <div class="w-14 h-14 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Batch Uninstall</h3>
                    <p class="text-gray-400 text-sm">ถอนการติดตั้งแอปจากหลายอุปกรณ์พร้อมกัน</p>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center">
                    <div class="w-14 h-14 bg-teal-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Batch Push</h3>
                    <p class="text-gray-400 text-sm">ส่งไฟล์ไปยังหลายอุปกรณ์พร้อมกัน</p>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center">
                    <div class="w-14 h-14 bg-lime-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Batch Commands</h3>
                    <p class="text-gray-400 text-sm">รันคำสั่ง Shell เดียวกันในหลายอุปกรณ์</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Advanced Features -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">ฟีเจอร์ขั้นสูง</h2>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white mb-2">Screen Mirror</h3>
                        <p class="text-gray-400">แสดงหน้าจออุปกรณ์ Android บนคอมพิวเตอร์แบบ Real-time</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white mb-2">Remote Input</h3>
                        <p class="text-gray-400">ควบคุมอุปกรณ์ด้วย Mouse และ Keyboard จากคอมพิวเตอร์</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 bg-teal-500/20 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white mb-2">Logcat Viewer</h3>
                        <p class="text-gray-400">ดู Android Logcat แบบ Real-time พร้อมตัวกรองขั้นสูง</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0 w-10 h-10 bg-lime-500/20 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white mb-2">Backup & Restore</h3>
                        <p class="text-gray-400">สำรองและกู้คืนข้อมูลแอปพลิเคชัน รวมถึง Full Device Backup</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- System Requirements -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">ความต้องการระบบ</h2>

            <div class="grid md:grid-cols-2 gap-8 max-w-3xl mx-auto">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        PC Requirements
                    </h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>Windows 10/11 (64-bit)</li>
                        <li>.NET 8.0 Runtime</li>
                        <li>RAM 4GB</li>
                        <li>พื้นที่ว่าง 500MB</li>
                        <li>USB Port หรือ WiFi</li>
                    </ul>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-green-500/50">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Android Requirements
                    </h3>
                    <ul class="space-y-2 text-gray-300">
                        <li>Android 5.0+ (API 21)</li>
                        <li>USB Debugging Enabled</li>
                        <li>Developer Options Unlocked</li>
                        <li>WiFi ADB: Android 11+ (แนะนำ)</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Use Cases -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">กรณีการใช้งาน</h2>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Developers</h3>
                    <p class="text-gray-400">นักพัฒนาที่ต้องการ Debug และทดสอบแอปบนหลายอุปกรณ์</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">QA Testing</h3>
                    <p class="text-gray-400">ทีม QA ที่ต้องทดสอบแอปบนอุปกรณ์หลากหลายรุ่น</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-teal-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Enterprise</h3>
                    <p class="text-gray-400">องค์กรที่ต้องจัดการอุปกรณ์พนักงานจำนวนมาก</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">เริ่มต้นใช้งาน PhoneX Manager</h2>
            <p class="text-gray-400 mb-8">ดาวน์โหลดฟรี และจัดการอุปกรณ์ Android ของคุณอย่างมืออาชีพ</p>

            <div class="flex flex-wrap justify-center gap-4">
                @auth
                    @if($hasPurchased)
                        <a href="{{ route('customer.downloads') }}"
                           class="px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-green-500/25">
                            ดาวน์โหลด
                        </a>
                    @else
                        <a href="{{ route('products.index') }}"
                           class="px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25">
                            ดูแพคเกจ
                        </a>
                    @endif
                @else
                    <a href="{{ route('products.index') }}"
                       class="px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25">
                        ดูแพคเกจ
                    </a>
                @endauth
            </div>
        </div>
    </section>
</div>
@endsection
