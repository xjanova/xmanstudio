@extends($publicLayout ?? 'layouts.app')

@section('title', 'XMAN Studio - IT Solutions & Software Development ครบวงจร')

@section('content')
<!-- Sale Promotion Banner -->
<div class="relative z-50 overflow-hidden bg-gradient-to-r from-red-600 via-pink-600 to-red-600" style="background-size: 200% 100%; animation: sale-bg-shift 3s ease infinite;">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.1) 10px, rgba(255,255,255,0.1) 20px);"></div>
    </div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-center py-3 gap-3 flex-wrap">
            <span class="inline-flex items-center gap-2 text-white font-bold text-sm md:text-base">
                <span class="flex items-center justify-center w-7 h-7 bg-yellow-400 text-red-600 rounded-full text-xs font-black animate-bounce">%</span>
                <span class="tracking-wide">มหกรรมลดราคา</span>
            </span>
            <span class="px-4 py-1 bg-yellow-400 text-red-700 font-black text-lg md:text-2xl rounded-lg shadow-lg" style="animation: sale-pulse 1.5s ease-in-out infinite;">
                50-70% OFF
            </span>
            <a href="/services" class="inline-flex items-center gap-1 px-4 py-1.5 bg-white text-red-600 font-bold text-sm rounded-full hover:bg-yellow-100 transition-colors shadow-md">
                ดูบริการทั้งหมด
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</div>
<style>
    @keyframes sale-bg-shift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
    @keyframes sale-pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.08); } }
</style>

<!-- Hero Hyperdrive Section -->
<div class="hyperdrive-hero relative h-screen overflow-hidden bg-black" x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 100)">
    <!-- Hyperdrive Stars Background -->
    <div class="hyperdrive-container absolute inset-0">
        <div class="hyperdrive-stars"></div>
        <div class="hyperdrive-stars hyperdrive-stars-2"></div>
        <div class="hyperdrive-stars hyperdrive-stars-3"></div>
    </div>

    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-b from-transparent via-black/20 to-black/60 z-10"></div>

    <!-- Central Content -->
    <div class="relative z-20 h-full flex items-center justify-center">
        <div class="text-center max-w-5xl mx-auto px-4"
             :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-10'"
             style="transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);">
            <!-- Badge -->
            <div class="mb-6"
                 :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'"
                 style="transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.2s;">
                <span class="inline-flex items-center px-6 py-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-full text-white/90 text-sm font-medium">
                    <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                    IT Solutions ครบวงจร
                </span>
            </div>

            <!-- Main Title / Logo -->
            @php
                $heroLogo = \App\Models\Setting::getValue('site_logo');
            @endphp
            <h1 class="mb-8">
                @if($heroLogo)
                    <div :class="loaded ? 'opacity-100 scale-100' : 'opacity-0 scale-150'"
                         style="transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1) 0.3s;">
                        <img src="{{ asset('storage/' . $heroLogo) }}" alt="XMAN STUDIO" class="mx-auto h-28 md:h-40 lg:h-52 w-auto drop-shadow-2xl">
                    </div>
                @else
                    <span class="block text-5xl md:text-7xl lg:text-9xl font-black text-white tracking-tight hyperdrive-title"
                          :class="loaded ? 'opacity-100 scale-100' : 'opacity-0 scale-150'"
                          style="transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1) 0.3s;">
                        XMAN
                    </span>
                    <span class="block text-4xl md:text-6xl lg:text-8xl font-black bg-gradient-to-r from-primary-400 via-purple-400 to-pink-400 bg-clip-text text-transparent mt-2"
                          :class="loaded ? 'opacity-100 scale-100' : 'opacity-0 scale-150'"
                          style="transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1) 0.4s; background-size: 200% 200%; animation: gradient-shift 5s ease infinite;">
                        STUDIO
                    </span>
                @endif
            </h1>

            <!-- Subtitle -->
            <p class="text-lg md:text-2xl lg:text-3xl text-white/80 font-light max-w-3xl mx-auto mb-12 leading-relaxed"
               :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
               style="transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.5s;">
                สร้างสรรค์นวัตกรรมดิจิทัล<br class="hidden md:block">
                <span class="text-primary-400">Blockchain</span> •
                <span class="text-purple-400">AI</span> •
                <span class="text-pink-400">Web & Mobile</span> •
                <span class="text-cyan-400">IoT</span> •
                <span class="text-red-400">Music AI</span>
            </p>

            <!-- CTA Buttons -->
            <div class="flex flex-wrap justify-center gap-4 md:gap-6"
                 :class="loaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
                 style="transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.6s;">
                <a href="/support" class="group relative inline-flex items-center px-8 py-4 bg-white text-gray-900 font-bold rounded-2xl overflow-hidden transition-all duration-500 hover:shadow-2xl hover:shadow-white/25 hover:scale-105">
                    <span class="relative z-10">เริ่มโปรเจคของคุณ</span>
                    <svg class="w-5 h-5 ml-2 relative z-10 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                    <div class="absolute inset-0 bg-gradient-to-r from-primary-500 to-purple-500 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <span class="absolute inset-0 z-10 flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 font-bold">
                        เริ่มโปรเจคของคุณ
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </span>
                </a>
                <a href="#services" class="inline-flex items-center px-8 py-4 border-2 border-white/30 text-white font-semibold rounded-2xl backdrop-blur-sm transition-all duration-500 hover:bg-white/10 hover:border-white/60 hover:scale-105">
                    สำรวจบริการ
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 z-30">
        <div class="flex flex-col items-center text-white/60 animate-bounce">
            <span class="text-sm mb-2 tracking-widest uppercase">Scroll</span>
            <div class="w-6 h-10 border-2 border-white/30 rounded-full flex justify-center pt-2">
                <div class="w-1.5 h-3 bg-white/60 rounded-full animate-scroll-down"></div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="relative z-30 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 -mt-20">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="text-center" x-data="{ count: 0 }" x-init="setTimeout(() => { let interval = setInterval(() => { if(count < 150) count += 3; else { count = 150; clearInterval(interval); } }, 30) }, 500)">
                <div class="text-4xl md:text-5xl font-bold text-primary-600" x-text="count + '+'">0+</div>
                <div class="text-gray-600 dark:text-gray-300 mt-2">โปรเจคสำเร็จ</div>
            </div>
            <div class="text-center" x-data="{ count: 0 }" x-init="setTimeout(() => { let interval = setInterval(() => { if(count < 50) count += 1; else clearInterval(interval); }, 50) }, 700)">
                <div class="text-4xl md:text-5xl font-bold text-primary-600" x-text="count + '+'">0+</div>
                <div class="text-gray-600 dark:text-gray-300 mt-2">ลูกค้าพึงพอใจ</div>
            </div>
            <div class="text-center" x-data="{ count: 0 }" x-init="setTimeout(() => { let interval = setInterval(() => { if(count < 8) count += 1; else clearInterval(interval); }, 200) }, 900)">
                <div class="text-4xl md:text-5xl font-bold text-primary-600" x-text="count + '+'">0+</div>
                <div class="text-gray-600 dark:text-gray-300 mt-2">ปีประสบการณ์</div>
            </div>
            <div class="text-center" x-data="{ count: 0 }" x-init="setTimeout(() => { let interval = setInterval(() => { if(count < 24) count += 1; else clearInterval(interval); }, 80) }, 1100)">
                <div class="text-4xl md:text-5xl font-bold text-primary-600" x-text="count + '/7'">0/7</div>
                <div class="text-gray-600 dark:text-gray-300 mt-2">บริการตลอด 24 ชม.</div>
            </div>
        </div>
    </div>
</div>

<!-- Services Section -->
<div id="services" class="py-24 bg-gray-50 dark:bg-gray-900 relative z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="inline-block px-4 py-2 bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 text-sm font-semibold rounded-full mb-4">บริการของเรา</span>
            <h2 class="text-3xl md:text-5xl font-extrabold text-gray-900 dark:text-white mb-6">
                โซลูชั่น IT ครบวงจร
            </h2>
            <p class="max-w-2xl mx-auto text-xl text-gray-600 dark:text-gray-300">
                ให้บริการด้านเทคโนโลยีสารสนเทศแบบครบวงจร ด้วยทีมงานมืออาชีพ
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Service Card 1 -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=600&h=400&fit=crop"
                         alt="Blockchain Development"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="absolute top-4 right-4">
                    <span class="px-3 py-1 bg-primary-600 text-white text-xs font-semibold rounded-full">ยอดนิยม</span>
                </div>
                <div class="p-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-primary-700 rounded-xl flex items-center justify-center mb-4 -mt-12 relative z-10 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Blockchain Development</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">พัฒนาโซลูชั่น Blockchain, Smart Contracts, DeFi และ NFT Marketplace</p>
                    <a href="/services" class="inline-flex items-center text-primary-600 dark:text-primary-400 font-semibold hover:text-primary-700">
                        ดูรายละเอียด
                        <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Service Card 2 -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=600&h=400&fit=crop"
                         alt="Web Development"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="p-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl flex items-center justify-center mb-4 -mt-12 relative z-10 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Web Development</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">ออกแบบและพัฒนาเว็บไซต์สมัยใหม่ Responsive รองรับทุกอุปกรณ์</p>
                    <a href="/services" class="inline-flex items-center text-primary-600 dark:text-primary-400 font-semibold hover:text-primary-700">
                        ดูรายละเอียด
                        <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Service Card 3 -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=600&h=400&fit=crop"
                         alt="Mobile App Development"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="p-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-700 rounded-xl flex items-center justify-center mb-4 -mt-12 relative z-10 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Mobile Application</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">พัฒนาแอพ iOS และ Android ด้วย Flutter และ React Native</p>
                    <a href="/services" class="inline-flex items-center text-primary-600 dark:text-primary-400 font-semibold hover:text-primary-700">
                        ดูรายละเอียด
                        <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Service Card 4 -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1677442136019-21780ecad995?w=600&h=400&fit=crop"
                         alt="AI Services"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="absolute top-4 right-4">
                    <span class="px-3 py-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-xs font-semibold rounded-full">ใหม่</span>
                </div>
                <div class="p-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mb-4 -mt-12 relative z-10 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">AI Solutions</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">วีดีโอ AI, เพลง AI, Chatbot และบริการ Generative AI</p>
                    <a href="/services" class="inline-flex items-center text-primary-600 dark:text-primary-400 font-semibold hover:text-primary-700">
                        ดูรายละเอียด
                        <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Service Card 5 -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1558346490-a72e53ae2d4f?w=600&h=400&fit=crop"
                         alt="IoT Solutions"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="p-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center mb-4 -mt-12 relative z-10 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">IoT Solutions</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">ออกแบบและพัฒนาระบบ Internet of Things ครบวงจร</p>
                    <a href="/services" class="inline-flex items-center text-primary-600 dark:text-primary-400 font-semibold hover:text-primary-700">
                        ดูรายละเอียด
                        <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Service Card 6 -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=600&h=400&fit=crop"
                         alt="Network & IT Security"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="p-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-rose-700 rounded-xl flex items-center justify-center mb-4 -mt-12 relative z-10 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Network & IT Security</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">ออกแบบ ติดตั้งระบบ Network, Firewall และทดสอบเจาะระบบ</p>
                    <a href="/services" class="inline-flex items-center text-primary-600 dark:text-primary-400 font-semibold hover:text-primary-700">
                        ดูรายละเอียด
                        <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Service Card 7 - Custom Software -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=600&h=400&fit=crop"
                         alt="Custom Software"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="p-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-xl flex items-center justify-center mb-4 -mt-12 relative z-10 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Custom Software</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">พัฒนาซอฟต์แวร์เฉพาะ ERP, CRM และระบบจัดการสินค้าคงคลัง</p>
                    <a href="/services" class="inline-flex items-center text-primary-600 dark:text-primary-400 font-semibold hover:text-primary-700">
                        ดูรายละเอียด
                        <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Service Card 8 - Flutter Development -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1551650975-87deedd944c3?w=600&h=400&fit=crop"
                         alt="Flutter Development"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="absolute top-4 right-4">
                    <span class="px-3 py-1 bg-sky-500 text-white text-xs font-semibold rounded-full">Flutter</span>
                </div>
                <div class="p-6">
                    <div class="w-14 h-14 bg-gradient-to-br from-sky-400 to-blue-600 rounded-xl flex items-center justify-center mb-4 -mt-12 relative z-10 shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Flutter & Android Studio</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">พัฒนาแอพ Cross-platform ด้วย Flutter และอบรมการใช้งาน</p>
                    <a href="/services" class="inline-flex items-center text-primary-600 dark:text-primary-400 font-semibold hover:text-primary-700">
                        ดูรายละเอียด
                        <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-12">
            <a href="/services" class="inline-flex items-center px-8 py-4 bg-primary-600 text-white font-semibold rounded-xl transition-all duration-300 hover:bg-primary-700 hover:shadow-xl hover:shadow-primary-600/20">
                ดูบริการทั้งหมด
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</div>

<!-- Metal-X Project Music Channel Section -->
<div class="py-24 bg-gradient-to-br from-purple-900 via-pink-900 to-red-900 relative overflow-hidden z-30">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48cGF0aCBkPSJNMzYgMzRjMC0yLjIxIDEuNzktNCA0LTRzNCAxLjc5IDQgNC0xLjc5IDQtNCA0LTQtMS43OS00LTR6bS0yMCAwYzAtMi4yMSAxLjc5LTQgNC00czQgMS43OSA0IDQtMS43OSA0LTQgNC00LTEuNzktNC00eiIvPjwvZz48L2c+PC9zdmc+')]"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <div class="inline-flex items-center px-6 py-2 bg-white/10 backdrop-blur-md border border-white/20 rounded-full text-white/90 text-sm font-medium mb-6">
                <span class="w-2 h-2 bg-red-500 rounded-full mr-2 animate-pulse"></span>
                Music Channel
            </div>
            <h2 class="text-4xl md:text-5xl font-black text-white mb-4">
                <span class="bg-gradient-to-r from-red-400 via-pink-400 to-purple-400 bg-clip-text text-transparent">
                    Metal-X Project
                </span>
            </h2>
            <p class="text-xl text-gray-200 max-w-2xl mx-auto">
                สำรวจผลงานเพลงและ Music Video จากทีมผู้เชี่ยวชาญของเรา
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-8 mb-12">
            <!-- YouTube Channel Card -->
            <div class="group relative bg-white/10 backdrop-blur-md rounded-2xl overflow-hidden border border-white/20 hover:border-white/40 transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl hover:shadow-red-500/20">
                <div class="aspect-video bg-gradient-to-br from-red-600 to-pink-600 flex items-center justify-center">
                    <svg class="w-24 h-24 text-white/90" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </div>
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-white mb-2">YouTube Channel</h3>
                    <p class="text-gray-200 mb-4">ติดตามผลงานเพลงและ Music Video ล่าสุดของเรา</p>
                    <a href="https://www.youtube.com/@Metal-XProject" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center text-red-400 hover:text-red-300 font-semibold transition-colors">
                        เยี่ยมชมช่อง YouTube
                        <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Team & Portfolio Card -->
            <div class="group relative bg-white/10 backdrop-blur-md rounded-2xl overflow-hidden border border-white/20 hover:border-white/40 transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl hover:shadow-purple-500/20">
                <div class="aspect-video bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center">
                    <svg class="w-24 h-24 text-white/90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-white mb-2">ทีมงาน & ผลงาน</h3>
                    <p class="text-gray-200 mb-4">พบกับทีมมืออาชีพและผลงานเพลงทั้งหมดของเรา</p>
                    <a href="{{ route('metal-x.index') }}"
                       class="inline-flex items-center text-purple-400 hover:text-purple-300 font-semibold transition-colors">
                        ดูทีมงานและผลงาน
                        <svg class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('metal-x.index') }}"
               class="inline-flex items-center px-8 py-4 bg-white text-gray-900 font-bold rounded-xl hover:bg-gray-100 transition-all duration-300 hover:scale-105 shadow-lg">
                <svg class="w-6 h-6 mr-2 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                </svg>
                สำรวจ Metal-X Project
            </a>
        </div>
    </div>
</div>

<!-- Why Choose Us Section -->
<div class="py-24 bg-white dark:bg-gray-800 relative z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-16 items-center">
            <div class="relative">
                <div class="absolute -inset-4 bg-gradient-to-r from-primary-600 to-purple-600 rounded-2xl opacity-20 blur-2xl"></div>
                <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&h=600&fit=crop"
                     alt="Our Team"
                     class="relative rounded-2xl shadow-2xl">
                <div class="absolute -bottom-8 -right-8 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <div class="flex items-center space-x-4">
                        <div class="flex -space-x-3">
                            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop" class="w-12 h-12 rounded-full border-2 border-white" alt="">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&h=100&fit=crop" class="w-12 h-12 rounded-full border-2 border-white" alt="">
                            <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&h=100&fit=crop" class="w-12 h-12 rounded-full border-2 border-white" alt="">
                        </div>
                        <div>
                            <div class="font-bold text-gray-900 dark:text-white">ทีมผู้เชี่ยวชาญ</div>
                            <div class="text-sm text-gray-600 dark:text-gray-300">15+ นักพัฒนามืออาชีพ</div>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <span class="inline-block px-4 py-2 bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 text-sm font-semibold rounded-full mb-4">ทำไมต้องเลือกเรา</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-white mb-6">
                    พาร์ทเนอร์ด้านเทคโนโลยี<br>ที่คุณไว้วางใจได้
                </h2>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">
                    เราเป็นทีมนักพัฒนามืออาชีพที่มีประสบการณ์กว่า 8 ปี ในการพัฒนาซอฟต์แวร์และโซลูชั่น IT ให้กับองค์กรชั้นนำ
                </p>
                <div class="space-y-6">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white">คุณภาพระดับสากล</h4>
                            <p class="text-gray-600 dark:text-gray-300">พัฒนาตามมาตรฐาน International Best Practice</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white">ส่งมอบตรงเวลา</h4>
                            <p class="text-gray-600 dark:text-gray-300">บริหารโปรเจคด้วยระบบ Agile ส่งมอบงานตามกำหนด</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 dark:text-white">ซัพพอร์ตตลอด 24/7</h4>
                            <p class="text-gray-600 dark:text-gray-300">ทีมซัพพอร์ตพร้อมช่วยเหลือทุกเวลา</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Technology Stack -->
<div class="py-20 bg-gray-900 dark:bg-black relative z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-white mb-4">เทคโนโลยีที่เราใช้</h2>
            <p class="text-gray-400">พัฒนาด้วยเทคโนโลยีล่าสุดและเป็นที่ยอมรับในอุตสาหกรรม</p>
        </div>
        <div class="grid grid-cols-3 md:grid-cols-6 gap-8">
            <div class="flex flex-col items-center p-6 bg-white/5 rounded-xl backdrop-blur hover:bg-white/10 transition-colors">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/react/react-original.svg" class="w-12 h-12 mb-3" alt="React">
                <span class="text-sm text-gray-300">React</span>
            </div>
            <div class="flex flex-col items-center p-6 bg-white/5 rounded-xl backdrop-blur hover:bg-white/10 transition-colors">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/flutter/flutter-original.svg" class="w-12 h-12 mb-3" alt="Flutter">
                <span class="text-sm text-gray-300">Flutter</span>
            </div>
            <div class="flex flex-col items-center p-6 bg-white/5 rounded-xl backdrop-blur hover:bg-white/10 transition-colors">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/laravel/laravel-original.svg" class="w-12 h-12 mb-3" alt="Laravel">
                <span class="text-sm text-gray-300">Laravel</span>
            </div>
            <div class="flex flex-col items-center p-6 bg-white/5 rounded-xl backdrop-blur hover:bg-white/10 transition-colors">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/nodejs/nodejs-original.svg" class="w-12 h-12 mb-3" alt="Node.js">
                <span class="text-sm text-gray-300">Node.js</span>
            </div>
            <div class="flex flex-col items-center p-6 bg-white/5 rounded-xl backdrop-blur hover:bg-white/10 transition-colors">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/python/python-original.svg" class="w-12 h-12 mb-3" alt="Python">
                <span class="text-sm text-gray-300">Python</span>
            </div>
            <div class="flex flex-col items-center p-6 bg-white/5 rounded-xl backdrop-blur hover:bg-white/10 transition-colors">
                <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/amazonwebservices/amazonwebservices-plain-wordmark.svg" class="w-12 h-12 mb-3 invert" alt="AWS">
                <span class="text-sm text-gray-300">AWS</span>
            </div>
        </div>
    </div>
</div>

<!-- CTA Section -->
<div class="relative py-24 overflow-hidden z-30">
    <div class="absolute inset-0 bg-gradient-to-br from-primary-600 via-primary-700 to-purple-800"></div>
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1920')] bg-cover bg-center opacity-10"></div>
    <div class="relative max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl md:text-5xl font-extrabold text-white mb-6">
            พร้อมที่จะเริ่มโปรเจคของคุณ?
        </h2>
        <p class="text-xl text-primary-100 mb-10">
            ปรึกษาเราฟรี! ทีมผู้เชี่ยวชาญพร้อมให้คำแนะนำและวางแผนโปรเจคให้คุณ
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="/support" class="inline-flex items-center px-8 py-4 bg-white text-primary-700 font-bold rounded-xl transition-all duration-300 hover:bg-gray-100 hover:shadow-2xl hover:scale-105">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                ติดต่อเรา
            </a>
            <a href="/products" class="inline-flex items-center px-8 py-4 bg-transparent border-2 border-white text-white font-bold rounded-xl transition-all duration-300 hover:bg-white/10">
                ดูผลิตภัณฑ์
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</div>
@endsection

