@extends($publicLayout ?? 'layouts.app')

@section('title', 'WinXTools - Windows Network & System Management | XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%233B82F6\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-blue-400 hover:text-blue-300 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับไปรายการผลิตภัณฑ์
                </a>
            </nav>

            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Product Info -->
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-blue-500/20 rounded-full text-blue-300 text-sm mb-6 backdrop-blur-sm border border-blue-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Network & System Tools
                    </div>

                    <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                        Win<span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400">X</span>Tools
                    </h1>

                    <p class="text-xl text-gray-300 mb-8">
                        ชุดเครื่องมือจัดการระบบเครือข่ายและ Windows แบบครบวงจร รวม Network Scanner, Port Scanner, System Info และเครื่องมืออื่นๆ ในแอปเดียว
                    </p>

                    <!-- Key Features Tags -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        <span class="px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-sm border border-blue-500/30">Network Scanner</span>
                        <span class="px-3 py-1 bg-cyan-500/20 text-cyan-300 rounded-full text-sm border border-cyan-500/30">Port Scanner</span>
                        <span class="px-3 py-1 bg-indigo-500/20 text-indigo-300 rounded-full text-sm border border-indigo-500/30">System Info</span>
                        <span class="px-3 py-1 bg-sky-500/20 text-sky-300 rounded-full text-sm border border-sky-500/30">Wake-on-LAN</span>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap gap-4">
                        <a href="https://github.com/xjanova/winxtools/releases" target="_blank"
                           class="px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-blue-500/25">
                            ดาวน์โหลด
                        </a>
                        <a href="https://github.com/xjanova/winxtools" target="_blank"
                           class="px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                            GitHub Repository
                        </a>
                    </div>
                </div>

                <!-- Right: Preview Image -->
                <div class="relative">
                    <div class="bg-gradient-to-br from-blue-500/20 to-cyan-500/20 rounded-2xl p-8 backdrop-blur-sm border border-blue-500/30">
                        <div class="aspect-video bg-gray-800 rounded-xl flex items-center justify-center overflow-hidden">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="WinXTools" class="w-full h-full object-cover">
                            @else
                                <div class="text-center">
                                    <svg class="w-24 h-24 mx-auto text-blue-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                    </svg>
                                    <p class="text-gray-400">WinXTools Dashboard</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tools Grid -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">เครื่องมือทั้งหมด</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">รวมเครื่องมือจัดการเครือข่ายและระบบที่จำเป็นไว้ในที่เดียว</p>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Network Scanner -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Network Scanner</h3>
                    <p class="text-gray-400 text-sm">สแกนหาอุปกรณ์ในเครือข่าย แสดง IP, MAC Address และ Hostname</p>
                </div>

                <!-- Port Scanner -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-cyan-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Port Scanner</h3>
                    <p class="text-gray-400 text-sm">สแกน Port ที่เปิดอยู่ รองรับ TCP/UDP และ Range Scan</p>
                </div>

                <!-- System Info -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-indigo-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">System Info</h3>
                    <p class="text-gray-400 text-sm">แสดงข้อมูลระบบ CPU, RAM, Disk, Network Adapters</p>
                </div>

                <!-- Wake-on-LAN -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Wake-on-LAN</h3>
                    <p class="text-gray-400 text-sm">ปลุกคอมพิวเตอร์ผ่านเครือข่าย ด้วย Magic Packet</p>
                </div>

                <!-- Ping Monitor -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Ping Monitor</h3>
                    <p class="text-gray-400 text-sm">ติดตาม Latency แบบ Real-time พร้อม Charts</p>
                </div>

                <!-- DNS Lookup -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">DNS Lookup</h3>
                    <p class="text-gray-400 text-sm">ค้นหา DNS Records ทุกประเภท A, AAAA, MX, TXT</p>
                </div>

                <!-- Traceroute -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-orange-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Traceroute</h3>
                    <p class="text-gray-400 text-sm">ติดตามเส้นทาง Network จาก Hop to Hop</p>
                </div>

                <!-- IP Calculator -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-blue-500/50 transition-all">
                    <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">IP Calculator</h3>
                    <p class="text-gray-400 text-sm">คำนวณ Subnet, CIDR, Network Range</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Network Scanner Details -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">Network Scanner</h2>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-gradient-to-br from-blue-500/10 to-cyan-500/10 rounded-xl p-6 border border-blue-500/30">
                    <h3 class="text-xl font-bold text-white mb-4">Scan Methods</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-blue-400 rounded-full mr-3"></span>
                            ARP Scan - เร็วและแม่นยำสำหรับ Local Network
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-blue-400 rounded-full mr-3"></span>
                            ICMP Ping Sweep - หา Live Hosts
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-blue-400 rounded-full mr-3"></span>
                            TCP Connect Scan - ตรวจสอบ Services
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-blue-400 rounded-full mr-3"></span>
                            NetBIOS Query - หาชื่อ Windows Machines
                        </li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-cyan-500/10 to-indigo-500/10 rounded-xl p-6 border border-cyan-500/30">
                    <h3 class="text-xl font-bold text-white mb-4">ข้อมูลที่แสดง</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-cyan-400 rounded-full mr-3"></span>
                            IP Address และ MAC Address
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-cyan-400 rounded-full mr-3"></span>
                            Hostname และ NetBIOS Name
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-cyan-400 rounded-full mr-3"></span>
                            Vendor (จาก OUI Database)
                        </li>
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-cyan-400 rounded-full mr-3"></span>
                            Open Ports และ Services
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Port Scanner Details -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">Port Scanner</h2>

            <div class="grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center">
                    <div class="w-14 h-14 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Quick Scan</h3>
                    <p class="text-gray-400 text-sm">สแกน Common Ports 100 ports ที่ใช้บ่อย</p>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center">
                    <div class="w-14 h-14 bg-cyan-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Range Scan</h3>
                    <p class="text-gray-400 text-sm">กำหนด Port Range เอง เช่น 1-1000</p>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 text-center">
                    <div class="w-14 h-14 bg-indigo-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Service Detection</h3>
                    <p class="text-gray-400 text-sm">ตรวจจับ Service และ Version</p>
                </div>
            </div>
        </div>
    </section>

    <!-- System Requirements -->
    <section class="py-16">
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
                        <li>RAM 2GB</li>
                        <li>พื้นที่ว่าง 100MB</li>
                        <li>Network Adapter</li>
                    </ul>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-blue-500/50">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        แนะนำ
                    </h3>
                    <ul class="space-y-2 text-gray-300">
                        <li>Windows 11 (64-bit)</li>
                        <li>.NET 8.0 Runtime</li>
                        <li>RAM 4GB+</li>
                        <li>SSD Storage</li>
                        <li>Administrator Privileges (บางฟังก์ชัน)</li>
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
                    <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Network Admin</h3>
                    <p class="text-gray-400">ผู้ดูแลระบบเครือข่ายที่ต้องตรวจสอบอุปกรณ์และบริการ</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-cyan-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Security Audit</h3>
                    <p class="text-gray-400">ตรวจสอบความปลอดภัยเครือข่าย หา Port ที่เปิดโดยไม่จำเป็น</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Troubleshooting</h3>
                    <p class="text-gray-400">แก้ไขปัญหาเครือข่าย วิเคราะห์ Connectivity Issues</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">เริ่มต้นใช้งาน WinXTools</h2>
            <p class="text-gray-400 mb-8">ดาวน์โหลดฟรี เครื่องมือจัดการเครือข่ายครบวงจร</p>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="https://github.com/xjanova/winxtools/releases" target="_blank"
                   class="px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-blue-500/25">
                    ดาวน์โหลดเลย
                </a>
                <a href="https://github.com/xjanova/winxtools" target="_blank"
                   class="px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                    ดูเอกสาร
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
