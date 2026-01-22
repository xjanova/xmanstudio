@extends('layouts.app')

@section('title', 'GPUsharX - GPU Sharing Platform | XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-emerald-900 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%2310B981\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-emerald-400 hover:text-emerald-300 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับไปรายการผลิตภัณฑ์
                </a>
            </nav>

            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Product Info -->
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-emerald-500/20 rounded-full text-emerald-300 text-sm mb-6 backdrop-blur-sm border border-emerald-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                        GPU Sharing Platform
                    </div>

                    <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                        GPU<span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-400">sharX</span>
                    </h1>

                    <p class="text-xl text-gray-300 mb-8">
                        แพลตฟอร์มแชร์พลัง GPU สำหรับ AI/ML Computing เช่าและให้เช่า GPU ได้ง่ายๆ รองรับการชำระเงินด้วย Crypto
                    </p>

                    <!-- Key Features Tags -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        <span class="px-3 py-1 bg-emerald-500/20 text-emerald-300 rounded-full text-sm border border-emerald-500/30">GPU Sharing</span>
                        <span class="px-3 py-1 bg-teal-500/20 text-teal-300 rounded-full text-sm border border-teal-500/30">AI/ML Ready</span>
                        <span class="px-3 py-1 bg-green-500/20 text-green-300 rounded-full text-sm border border-green-500/30">Crypto Payment</span>
                        <span class="px-3 py-1 bg-cyan-500/20 text-cyan-300 rounded-full text-sm border border-cyan-500/30">REST API</span>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap gap-4">
                        <a href="https://github.com/xjanova/GPUsharX/releases" target="_blank"
                           class="px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-emerald-500/25">
                            ดาวน์โหลด
                        </a>
                        <a href="https://github.com/xjanova/GPUsharX" target="_blank"
                           class="px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                            GitHub Repository
                        </a>
                    </div>
                </div>

                <!-- Right: Preview Image -->
                <div class="relative">
                    <div class="bg-gradient-to-br from-emerald-500/20 to-teal-500/20 rounded-2xl p-8 backdrop-blur-sm border border-emerald-500/30">
                        <div class="aspect-video bg-gray-800 rounded-xl flex items-center justify-center overflow-hidden">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="GPUsharX" class="w-full h-full object-cover">
                            @else
                                <div class="text-center">
                                    <svg class="w-24 h-24 mx-auto text-emerald-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                    </svg>
                                    <p class="text-gray-400">GPUsharX Platform</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">หลักการทำงาน</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">เชื่อมต่อผู้ที่มี GPU เหลือใช้กับผู้ที่ต้องการพลังประมวลผลสำหรับงาน AI/ML</p>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Provider -->
                <div class="bg-gradient-to-br from-emerald-500/10 to-teal-500/10 rounded-xl p-6 border border-emerald-500/30">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-emerald-500/20 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">GPU Provider</h3>
                    </div>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full mr-3 mt-2"></span>
                            <span>ติดตั้ง Provider Agent บนเครื่องที่มี GPU</span>
                        </li>
                        <li class="flex items-start">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full mr-3 mt-2"></span>
                            <span>กำหนดราคาและช่วงเวลาที่ต้องการให้เช่า</span>
                        </li>
                        <li class="flex items-start">
                            <span class="w-2 h-2 bg-emerald-400 rounded-full mr-3 mt-2"></span>
                            <span>รับรายได้จาก Crypto โดยอัตโนมัติ</span>
                        </li>
                    </ul>
                </div>

                <!-- Consumer -->
                <div class="bg-gradient-to-br from-teal-500/10 to-cyan-500/10 rounded-xl p-6 border border-teal-500/30">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-teal-500/20 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">GPU Consumer</h3>
                    </div>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start">
                            <span class="w-2 h-2 bg-teal-400 rounded-full mr-3 mt-2"></span>
                            <span>ค้นหา GPU ที่ตรงกับความต้องการ</span>
                        </li>
                        <li class="flex items-start">
                            <span class="w-2 h-2 bg-teal-400 rounded-full mr-3 mt-2"></span>
                            <span>เช่าและใช้งานผ่าน API หรือ Web Interface</span>
                        </li>
                        <li class="flex items-start">
                            <span class="w-2 h-2 bg-teal-400 rounded-full mr-3 mt-2"></span>
                            <span>จ่ายเฉพาะเวลาที่ใช้งานจริง (Pay-per-use)</span>
                        </li>
                    </ul>
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
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-emerald-500/50 transition-all">
                    <div class="w-12 h-12 bg-emerald-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">GPU Discovery</h3>
                    <p class="text-gray-400">ค้นหา GPU ตามประเภท, VRAM, ราคา และ Availability</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-emerald-500/50 transition-all">
                    <div class="w-12 h-12 bg-teal-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Crypto Payment</h3>
                    <p class="text-gray-400">รองรับการชำระเงินด้วย ETH, USDT และ Crypto อื่นๆ</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-emerald-500/50 transition-all">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">REST API</h3>
                    <p class="text-gray-400">API สำหรับ Integration กับ ML Pipelines และ Automation</p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-emerald-500/50 transition-all">
                    <div class="w-12 h-12 bg-cyan-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Real-time Monitoring</h3>
                    <p class="text-gray-400">ติดตามการใช้งาน GPU, Temperature, Utilization แบบ Real-time</p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-emerald-500/50 transition-all">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Secure Execution</h3>
                    <p class="text-gray-400">รัน Jobs แบบ Isolated ด้วย Container Technology</p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-emerald-500/50 transition-all">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Usage Analytics</h3>
                    <p class="text-gray-400">Dashboard แสดงรายได้, การใช้งาน และสถิติต่างๆ</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Supported GPUs -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">GPU ที่รองรับ</h2>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <span class="w-3 h-3 bg-green-400 rounded-full mr-3"></span>
                        NVIDIA GPUs
                    </h3>
                    <div class="grid grid-cols-2 gap-2 text-gray-400">
                        <span>RTX 4090</span>
                        <span>RTX 4080</span>
                        <span>RTX 3090</span>
                        <span>RTX 3080</span>
                        <span>A100</span>
                        <span>A6000</span>
                        <span>V100</span>
                        <span>T4</span>
                    </div>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <span class="w-3 h-3 bg-red-400 rounded-full mr-3"></span>
                        AMD GPUs
                    </h3>
                    <div class="grid grid-cols-2 gap-2 text-gray-400">
                        <span>RX 7900 XTX</span>
                        <span>RX 7900 XT</span>
                        <span>RX 6900 XT</span>
                        <span>RX 6800 XT</span>
                        <span>MI250X</span>
                        <span>MI210</span>
                        <span>MI100</span>
                        <span>Instinct Series</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- API Section -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">REST API</h2>

            <div class="max-w-3xl mx-auto">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 mb-6">
                    <h3 class="text-lg font-bold text-white mb-4">Available Endpoints</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gray-900/50 rounded-lg">
                            <div class="flex items-center">
                                <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs font-mono rounded mr-3">GET</span>
                                <span class="text-gray-300 font-mono text-sm">/api/gpus</span>
                            </div>
                            <span class="text-gray-500 text-sm">List available GPUs</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-900/50 rounded-lg">
                            <div class="flex items-center">
                                <span class="px-2 py-1 bg-blue-500/20 text-blue-400 text-xs font-mono rounded mr-3">POST</span>
                                <span class="text-gray-300 font-mono text-sm">/api/jobs</span>
                            </div>
                            <span class="text-gray-500 text-sm">Submit a new job</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-900/50 rounded-lg">
                            <div class="flex items-center">
                                <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs font-mono rounded mr-3">GET</span>
                                <span class="text-gray-300 font-mono text-sm">/api/jobs/{'{id}'}</span>
                            </div>
                            <span class="text-gray-500 text-sm">Get job status</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-900/50 rounded-lg">
                            <div class="flex items-center">
                                <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs font-mono rounded mr-3">GET</span>
                                <span class="text-gray-300 font-mono text-sm">/api/providers/stats</span>
                            </div>
                            <span class="text-gray-500 text-sm">Provider statistics</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-bold text-white mb-4">Example Request</h3>
                    <div class="bg-gray-900 rounded-lg p-4 font-mono text-sm overflow-x-auto">
                        <pre class="text-gray-300"><code>curl -X POST https://api.gpusharx.com/api/jobs \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "gpu_type": "RTX_4090",
    "docker_image": "pytorch/pytorch:latest",
    "command": "python train.py",
    "max_duration": 3600
  }'</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Model -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">รูปแบบราคา</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">จ่ายเฉพาะเวลาที่ใช้งานจริง ไม่มีค่าธรรมเนียมแฝง</p>

            <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center">
                    <div class="w-14 h-14 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Pay-per-Hour</h3>
                    <p class="text-gray-400 text-sm">จ่ายตามชั่วโมงที่ใช้งานจริง ตั้งแต่ $0.50/hr</p>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center">
                    <div class="w-14 h-14 bg-teal-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Pay-per-Job</h3>
                    <p class="text-gray-400 text-sm">จ่ายตาม Job ที่รัน เหมาะกับงาน Batch Processing</p>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center">
                    <div class="w-14 h-14 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Reserved</h3>
                    <p class="text-gray-400 text-sm">จอง GPU ล่วงหน้า ได้ส่วนลด 20-40%</p>
                </div>
            </div>
        </div>
    </section>

    <!-- System Requirements -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">ความต้องการระบบ</h2>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-emerald-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                        GPU Provider
                    </h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>Windows 10/11 หรือ Linux</li>
                        <li>NVIDIA GPU (RTX 3060 ขึ้นไป)</li>
                        <li>NVIDIA Driver 525+</li>
                        <li>Docker Desktop (Windows) หรือ Docker Engine (Linux)</li>
                        <li>RAM 16GB+</li>
                        <li>Internet 100 Mbps+</li>
                    </ul>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-emerald-500/50">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-teal-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        GPU Consumer
                    </h3>
                    <ul class="space-y-2 text-gray-300">
                        <li>Web Browser ใดก็ได้</li>
                        <li>หรือใช้ REST API</li>
                        <li>Crypto Wallet (ETH, USDT)</li>
                        <li>Docker Image สำหรับ Jobs</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Use Cases -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">กรณีการใช้งาน</h2>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">AI Model Training</h3>
                    <p class="text-gray-400">Train LLMs, Image Models, และ ML Models ด้วย GPU แรงๆ</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-teal-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Image/Video Rendering</h3>
                    <p class="text-gray-400">Render 3D, AI Image Generation, Video Processing</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Scientific Computing</h3>
                    <p class="text-gray-400">Simulation, Data Analysis, Research Computing</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">เริ่มต้นใช้งาน GPUsharX</h2>
            <p class="text-gray-400 mb-8">แชร์หรือเช่า GPU ได้ง่ายๆ เริ่มต้นวันนี้</p>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="https://github.com/xjanova/GPUsharX/releases" target="_blank"
                   class="px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-emerald-500/25">
                    ดาวน์โหลดเลย
                </a>
                <a href="https://github.com/xjanova/GPUsharX" target="_blank"
                   class="px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                    ดูเอกสาร
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
