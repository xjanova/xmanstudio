@extends($publicLayout ?? 'layouts.app')

@section('title', 'Live x Shop Pro - Live Shopping Platform | XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-pink-900 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23EC4899\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-pink-400 hover:text-pink-300 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับไปรายการผลิตภัณฑ์
                </a>
            </nav>

            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Product Info -->
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-pink-500/20 rounded-full text-pink-300 text-sm mb-6 backdrop-blur-sm border border-pink-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        Live Shopping Platform
                    </div>

                    <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                        Live x Shop <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-rose-400">Pro</span>
                    </h1>

                    <p class="text-xl text-gray-300 mb-8">
                        แพลตฟอร์ม Live Shopping ครบวงจร รองรับการขายสินค้าผ่านการ Live Stream พร้อมระบบ Multi-Platform Streaming และ Shoutout อัตโนมัติ
                    </p>

                    <!-- Key Features Tags -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        <span class="px-3 py-1 bg-pink-500/20 text-pink-300 rounded-full text-sm border border-pink-500/30">Live Streaming</span>
                        <span class="px-3 py-1 bg-rose-500/20 text-rose-300 rounded-full text-sm border border-rose-500/30">Multi-Platform</span>
                        <span class="px-3 py-1 bg-red-500/20 text-red-300 rounded-full text-sm border border-red-500/30">Auto Shoutout</span>
                        <span class="px-3 py-1 bg-orange-500/20 text-orange-300 rounded-full text-sm border border-orange-500/30">OBS Integration</span>
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
                    <div class="bg-gradient-to-br from-pink-500/20 to-rose-500/20 rounded-2xl p-8 backdrop-blur-sm border border-pink-500/30">
                        <div class="aspect-video bg-gray-800 rounded-xl flex items-center justify-center overflow-hidden">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="Live x Shop Pro" class="w-full h-full object-cover">
                            @else
                                <div class="text-center">
                                    <svg class="w-24 h-24 mx-auto text-pink-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-gray-400">Live x Shop Pro Interface</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Supported Platforms -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">รองรับหลายแพลตฟอร์ม</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">Stream ไปยังหลายแพลตฟอร์มพร้อมกันด้วยการตั้งค่าเพียงครั้งเดียว</p>

            <div class="flex flex-wrap justify-center gap-6">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center w-32">
                    <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-green-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 5.17 3.98 9.48 9.15 9.97l.02.02h.05c.47.04.95.06 1.43.06 5.46 0 9.91-4.45 9.91-9.91 0-5.46-4.45-9.91-9.91-9.91-.23 0-.47.01-.7.02h-.04zm4.98 6.37c.22 0 .45.03.68.1.83.24 1.45.86 1.69 1.69.24.83.04 1.73-.54 2.41l-.04.04c-.17.17-.36.33-.57.47-.14.09-.29.17-.44.24-.15.07-.31.12-.47.17l-.5.12c-.17.03-.35.05-.53.06H15.8c-.04 0-.09-.01-.13-.02l-.52-.13c-.35-.11-.68-.27-.98-.47-.3-.2-.57-.44-.81-.71-.24-.27-.44-.57-.6-.89-.16-.32-.28-.66-.35-1.01-.07-.35-.1-.71-.08-1.07.02-.36.08-.71.19-1.05.11-.34.26-.66.44-.96.19-.3.41-.57.66-.82.25-.25.52-.47.82-.66.3-.19.62-.34.96-.45.34-.11.69-.18 1.05-.2z"/>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">LINE</span>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center w-32">
                    <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">Facebook</span>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center w-32">
                    <div class="w-16 h-16 bg-pink-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-pink-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">Instagram</span>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center w-32">
                    <div class="w-16 h-16 bg-black/50 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"/>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">TikTok</span>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center w-32">
                    <div class="w-16 h-16 bg-red-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">YouTube</span>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center w-32">
                    <div class="w-16 h-16 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-8 h-8 text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714Z"/>
                        </svg>
                    </div>
                    <span class="text-white font-semibold">Twitch</span>
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
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-pink-500/50 transition-all">
                    <div class="w-12 h-12 bg-pink-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Multi-Platform Streaming</h3>
                    <p class="text-gray-400">Stream ไปยัง LINE, Facebook, Instagram, TikTok, YouTube พร้อมกัน</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-pink-500/50 transition-all">
                    <div class="w-12 h-12 bg-rose-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Auto Shoutout</h3>
                    <p class="text-gray-400">ระบบเรียกชื่อลูกค้าและประกาศขอบคุณอัตโนมัติเมื่อมีการสั่งซื้อ</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-pink-500/50 transition-all">
                    <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Chat Integration</h3>
                    <p class="text-gray-400">รวม Chat จากทุกแพลตฟอร์มไว้ในที่เดียว ตอบลูกค้าได้ง่าย</p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-pink-500/50 transition-all">
                    <div class="w-12 h-12 bg-orange-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">OBS Integration</h3>
                    <p class="text-gray-400">ควบคุม OBS Studio ได้โดยตรง เปลี่ยน Scene และ Source อัตโนมัติ</p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-pink-500/50 transition-all">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Product Showcase</h3>
                    <p class="text-gray-400">แสดงสินค้าบนหน้าจอ Live พร้อมราคาและปุ่มสั่งซื้อ</p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-pink-500/50 transition-all">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Sales Analytics</h3>
                    <p class="text-gray-400">แดชบอร์ดสรุปยอดขาย วิเคราะห์ช่วงเวลาขายดี และพฤติกรรมลูกค้า</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">วิธีการทำงาน</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">เริ่มต้น Live ขายของได้ง่ายๆ ใน 4 ขั้นตอน</p>

            <div class="grid md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-pink-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-pink-500">
                        <span class="text-2xl font-bold text-pink-400">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">เชื่อมต่อแพลตฟอร์ม</h3>
                    <p class="text-gray-400 text-sm">เชื่อมต่อบัญชี Social Media ที่ต้องการ</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-pink-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-pink-500">
                        <span class="text-2xl font-bold text-pink-400">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">เพิ่มสินค้า</h3>
                    <p class="text-gray-400 text-sm">อัพโหลดรูปและข้อมูลสินค้าที่จะขาย</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-pink-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-pink-500">
                        <span class="text-2xl font-bold text-pink-400">3</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">เริ่ม Live</h3>
                    <p class="text-gray-400 text-sm">กดปุ่มเดียว Stream ไปทุกแพลตฟอร์ม</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-pink-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-pink-500">
                        <span class="text-2xl font-bold text-pink-400">4</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">รับออเดอร์</h3>
                    <p class="text-gray-400 text-sm">ระบบจัดการออเดอร์และ Shoutout อัตโนมัติ</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Advanced Features -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">ฟีเจอร์ขั้นสูง</h2>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-gradient-to-br from-pink-500/10 to-rose-500/10 rounded-xl p-6 border border-pink-500/30">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-pink-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                        Overlay Customization
                    </h3>
                    <ul class="space-y-2 text-gray-300">
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-pink-400 rounded-full mr-3"></span>
                            ออกแบบ Overlay ได้เอง ด้วย Drag & Drop
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-pink-400 rounded-full mr-3"></span>
                            Animation Effects สำหรับการแจ้งเตือน
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-pink-400 rounded-full mr-3"></span>
                            Template สำเร็จรูปหลายแบบ
                        </li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-rose-500/10 to-red-500/10 rounded-xl p-6 border border-rose-500/30">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-rose-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Order Management
                    </h3>
                    <ul class="space-y-2 text-gray-300">
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-rose-400 rounded-full mr-3"></span>
                            ระบบจัดการออเดอร์แบบ Real-time
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-rose-400 rounded-full mr-3"></span>
                            สรุปยอดขายอัตโนมัติ
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-rose-400 rounded-full mr-3"></span>
                            Export รายการส่งสินค้า
                        </li>
                    </ul>
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
                        <li>Windows 10 (64-bit)</li>
                        <li>.NET 8.0 Runtime</li>
                        <li>RAM 8GB</li>
                        <li>CPU: Intel i5 / AMD Ryzen 5</li>
                        <li>Internet: 10 Mbps Upload</li>
                    </ul>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-pink-500/50">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-pink-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        แนะนำ
                    </h3>
                    <ul class="space-y-2 text-gray-300">
                        <li>Windows 11 (64-bit)</li>
                        <li>.NET 8.0 Runtime</li>
                        <li>RAM 16GB+</li>
                        <li>CPU: Intel i7 / AMD Ryzen 7</li>
                        <li>GPU: NVIDIA for Hardware Encoding</li>
                        <li>Internet: 50 Mbps Upload</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">เริ่มต้น Live ขายของวันนี้</h2>
            <p class="text-gray-400 mb-8">ดาวน์โหลดฟรี และเพิ่มยอดขายด้วย Live Shopping</p>

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
