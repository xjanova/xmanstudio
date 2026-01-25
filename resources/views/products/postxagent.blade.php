@extends($publicLayout ?? 'layouts.app')

@section('title', 'PostXAgent - AI Brand Promotion Manager | XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-orange-900 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23F97316\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-orange-400 hover:text-orange-300 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับไปรายการผลิตภัณฑ์
                </a>
            </nav>

            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Product Info -->
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-orange-500/20 rounded-full text-orange-300 text-sm mb-6 backdrop-blur-sm border border-orange-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                        AI Brand Promotion
                    </div>

                    <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                        Post<span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-400">X</span>Agent
                    </h1>

                    <p class="text-xl text-gray-300 mb-8">
                        AI Agent สำหรับจัดการการโปรโมทแบรนด์อัตโนมัติ สร้างคอนเทนต์, วางแผนโพสต์, และวิเคราะห์ผลลัพธ์ด้วย AI
                    </p>

                    <!-- Key Features Tags -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        <span class="px-3 py-1 bg-orange-500/20 text-orange-300 rounded-full text-sm border border-orange-500/30">AI Content</span>
                        <span class="px-3 py-1 bg-amber-500/20 text-amber-300 rounded-full text-sm border border-amber-500/30">Auto Posting</span>
                        <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-sm border border-yellow-500/30">Multi-Platform</span>
                        <span class="px-3 py-1 bg-red-500/20 text-red-300 rounded-full text-sm border border-red-500/30">Analytics</span>
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
                                <a href="{{ route('packages.index') }}"
                                   class="px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25">
                                    ดูแพคเกจ
                                </a>
                            @endif
                        @else
                            <a href="{{ route('packages.index') }}"
                               class="px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25">
                                ดูแพคเกจ
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Right: Preview Image -->
                <div class="relative">
                    <div class="bg-gradient-to-br from-orange-500/20 to-amber-500/20 rounded-2xl p-8 backdrop-blur-sm border border-orange-500/30">
                        <div class="aspect-video bg-gray-800 rounded-xl flex items-center justify-center overflow-hidden">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="PostXAgent" class="w-full h-full object-cover">
                            @else
                                <div class="text-center">
                                    <svg class="w-24 h-24 mx-auto text-orange-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                    </svg>
                                    <p class="text-gray-400">PostXAgent Dashboard</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- AI Features -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">AI-Powered Marketing</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">ใช้พลัง AI สร้างและจัดการคอนเทนต์การตลาดอัตโนมัติ</p>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Content Generation -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-orange-500/50 transition-all">
                    <div class="w-12 h-12 bg-orange-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">AI Content Generation</h3>
                    <p class="text-gray-400">สร้างเนื้อหาโพสต์, แคปชัน, แฮชแท็กอัตโนมัติตามธีมแบรนด์</p>
                </div>

                <!-- Image Analysis -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-orange-500/50 transition-all">
                    <div class="w-12 h-12 bg-amber-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Image Analysis</h3>
                    <p class="text-gray-400">วิเคราะห์รูปภาพและแนะนำแคปชันที่เหมาะสม</p>
                </div>

                <!-- Trend Analysis -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-orange-500/50 transition-all">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Trend Analysis</h3>
                    <p class="text-gray-400">ติดตาม Trends และแนะนำหัวข้อที่กำลังมาแรง</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">ฟีเจอร์หลัก</h2>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-orange-500/50 transition-all">
                    <div class="w-12 h-12 bg-orange-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Post Scheduling</h3>
                    <p class="text-gray-400">ตั้งเวลาโพสต์ล่วงหน้า รองรับ Timezone ต่างๆ</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-orange-500/50 transition-all">
                    <div class="w-12 h-12 bg-amber-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Content Calendar</h3>
                    <p class="text-gray-400">ปฏิทินคอนเทนต์ วางแผนการโพสต์ทั้งเดือน</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-orange-500/50 transition-all">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Hashtag Manager</h3>
                    <p class="text-gray-400">จัดการ Hashtag Sets แนะนำแฮชแท็กยอดนิยม</p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-orange-500/50 transition-all">
                    <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Analytics Dashboard</h3>
                    <p class="text-gray-400">แดชบอร์ดวิเคราะห์ Engagement, Reach, Impressions</p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-orange-500/50 transition-all">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Team Collaboration</h3>
                    <p class="text-gray-400">ทำงานร่วมกันในทีม อนุมัติโพสต์ก่อนเผยแพร่</p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-orange-500/50 transition-all">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Template Library</h3>
                    <p class="text-gray-400">คลัง Template สำเร็จรูปสำหรับทุกโอกาส</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Supported Platforms -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">แพลตฟอร์มที่รองรับ</h2>

            <div class="flex flex-wrap justify-center gap-6">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center w-32">
                    <div class="w-14 h-14 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">Facebook</span>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center w-32">
                    <div class="w-14 h-14 bg-pink-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-pink-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">Instagram</span>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center w-32">
                    <div class="w-14 h-14 bg-sky-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-sky-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">X (Twitter)</span>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center w-32">
                    <div class="w-14 h-14 bg-blue-600/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">LinkedIn</span>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center w-32">
                    <div class="w-14 h-14 bg-black/50 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"/>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">TikTok</span>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center w-32">
                    <div class="w-14 h-14 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-7 h-7 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 5.17 3.98 9.48 9.15 9.97l.02.02h.05c.47.04.95.06 1.43.06 5.46 0 9.91-4.45 9.91-9.91 0-5.46-4.45-9.91-9.91-9.91-.23 0-.47.01-.7.02h-.04z"/>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">LINE</span>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">วิธีการทำงาน</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">ให้ AI ช่วยจัดการการตลาดของคุณใน 4 ขั้นตอน</p>

            <div class="grid md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-orange-500">
                        <span class="text-2xl font-bold text-orange-400">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">ตั้งค่าแบรนด์</h3>
                    <p class="text-gray-400 text-sm">กำหนด Brand Voice, โทนสี และ Guidelines</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-orange-500">
                        <span class="text-2xl font-bold text-orange-400">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">สร้างคอนเทนต์</h3>
                    <p class="text-gray-400 text-sm">AI สร้างเนื้อหาตาม Brand Guidelines</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-orange-500">
                        <span class="text-2xl font-bold text-orange-400">3</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">ตั้งเวลาโพสต์</h3>
                    <p class="text-gray-400 text-sm">กำหนดเวลาโพสต์ที่เหมาะสมที่สุด</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-orange-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-orange-500">
                        <span class="text-2xl font-bold text-orange-400">4</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">วิเคราะห์ผล</h3>
                    <p class="text-gray-400 text-sm">ดูรายงานและปรับปรุงกลยุทธ์</p>
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
                        ขั้นต่ำ
                    </h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>Windows 10/11 (64-bit)</li>
                        <li>.NET 8.0 Runtime</li>
                        <li>RAM 4GB</li>
                        <li>พื้นที่ว่าง 500MB</li>
                        <li>Internet Connection</li>
                    </ul>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-orange-500/50">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-orange-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        ต้องการเพิ่มเติม
                    </h3>
                    <ul class="space-y-2 text-gray-300">
                        <li>OpenAI API Key หรือ Claude API Key</li>
                        <li>Social Media API Access</li>
                        <li>Facebook/Instagram Business Account</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">เริ่มต้นใช้งาน PostXAgent</h2>
            <p class="text-gray-400 mb-8">ให้ AI ช่วยจัดการการตลาดของคุณวันนี้</p>

            <div class="flex flex-wrap justify-center gap-4">
                @auth
                    @if($hasPurchased)
                        <a href="{{ route('customer.downloads') }}"
                           class="px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-green-500/25">
                            ดาวน์โหลด
                        </a>
                    @else
                        <a href="{{ route('packages.index') }}"
                           class="px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25">
                            ดูแพคเกจ
                        </a>
                    @endif
                @else
                    <a href="{{ route('packages.index') }}"
                       class="px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25">
                        ดูแพคเกจ
                    </a>
                @endauth
            </div>
        </div>
    </section>
</div>
@endsection
