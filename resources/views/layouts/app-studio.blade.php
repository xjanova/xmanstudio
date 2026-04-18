<!DOCTYPE html>
<html lang="th" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO & Social Media Preview --}}
    <x-seo-meta
        :title="View::yieldContent('title', 'XMAN Studio - IT Solutions & Software Development')"
        :description="View::yieldContent('meta_description', 'XMAN Studio ผู้เชี่ยวชาญด้าน IT Solutions ครบวงจร ทำเว็บไซต์ แอพพลิเคชัน Blockchain IoT Network Security AI และอื่นๆ')"
        :image="View::yieldContent('og_image', '')"
    />

    {{-- Favicon --}}
    @php
        $siteFavicon = \App\Models\Setting::getValue('site_favicon');
    @endphp
    @if($siteFavicon)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $siteFavicon) }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('storage/' . $siteFavicon) }}">
    @else
        <link rel="icon" type="image/x-icon" href="{{ asset('public_html/favicon.ico') }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    {{-- Dark Mode Script (prevent flash) --}}
    <script>
        if (localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    {{-- Custom Head Code (Tracking & Verification) --}}
    @php
        $customHeadCode = \App\Models\Setting::getValue('custom_code_head', '');
    @endphp
    @if($customHeadCode)
        {!! $customHeadCode !!}
    @endif

    <style>
        /* Studio theme — design-system signature surfaces */
        .studio-theme {
            background: linear-gradient(180deg, #f9fafb 0%, #ffffff 60%);
        }
        .dark .studio-theme {
            background: #030712;
        }
        .studio-nav-link {
            transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body class="studio-theme text-gray-900 dark:text-gray-100 transition-colors duration-300">
    {{-- Custom Body Start Code --}}
    @php
        $customBodyStartCode = \App\Models\Setting::getValue('custom_code_body_start', '');
    @endphp
    @if($customBodyStartCode)
        {!! $customBodyStartCode !!}
    @endif

    {{-- Golden Fireflies - Fixed Top Layer (Design-system signature) --}}
    <div id="fireflies-layer" class="fireflies-fixed-layer"></div>

    {{-- Toast Container --}}
    <div id="toast-container" class="toast-container"></div>

    {{-- Navigation — rainbow-per-destination, ds-wordmark --}}
    <nav class="bg-white/90 dark:bg-gray-900/90 backdrop-blur-lg sticky top-0 z-50 border-b border-gray-100 dark:border-gray-800 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    @php
                        $siteLogo = \App\Models\Setting::getValue('site_logo');
                    @endphp
                    <a href="/" class="flex items-center flex-shrink-0">
                        @if($siteLogo)
                            <img src="{{ asset('storage/' . $siteLogo) }}" alt="XMAN STUDIO" class="h-10 w-auto">
                        @else
                            <span class="ds-wordmark text-2xl font-black">XMAN STUDIO</span>
                        @endif
                    </a>
                    <div class="hidden lg:flex lg:ml-8 lg:items-center lg:gap-1">
                        <a href="/" class="studio-nav-link px-3 py-2 text-sm font-medium rounded-xl {{ request()->is('/') ? 'bg-gradient-to-r from-emerald-500 to-green-500 text-white shadow-lg shadow-emerald-500/30' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">
                            หน้าหลัก
                        </a>
                        <a href="/services" class="studio-nav-link px-3 py-2 text-sm font-medium rounded-xl {{ request()->is('services*') ? 'bg-gradient-to-r from-yellow-400 to-amber-500 text-white shadow-lg shadow-amber-500/30' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">
                            บริการ
                        </a>
                        <a href="/products" class="studio-nav-link px-3 py-2 text-sm font-medium rounded-xl {{ request()->is('products*') ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-lg shadow-orange-500/30' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">
                            ผลิตภัณฑ์
                        </a>
                        <a href="/rental" class="studio-nav-link px-3 py-2 text-sm font-medium rounded-xl {{ request()->is('rental*') ? 'bg-gradient-to-r from-pink-500 to-rose-500 text-white shadow-lg shadow-pink-500/30' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">
                            เช่าบริการ
                        </a>
                        <a href="/portfolio" class="studio-nav-link px-3 py-2 text-sm font-medium rounded-xl {{ request()->is('portfolio*') ? 'bg-gradient-to-r from-purple-500 to-violet-500 text-white shadow-lg shadow-purple-500/30' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">
                            ผลงาน
                        </a>
                        <a href="/support" class="studio-nav-link px-3 py-2 text-sm font-medium rounded-xl {{ request()->is('support') ? 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-lg shadow-blue-500/30' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">
                            ติดต่อ/สั่งซื้อ
                        </a>
                        <a href="/tracking" class="studio-nav-link px-3 py-2 text-sm font-medium rounded-xl {{ request()->is('tracking*') ? 'bg-gradient-to-r from-teal-500 to-cyan-500 text-white shadow-lg shadow-teal-500/30' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white' }}">
                            ติดตามงาน
                        </a>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    @include('partials.public-nav-icons', ['theme' => 'classic'])
                </div>
            </div>
        </div>

        @include('partials.public-nav-mobile', ['theme' => 'classic'])
    </nav>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="animate-fade-in-down">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl flex items-center justify-between" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-green-700 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="animate-fade-in-down">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl flex items-center justify-between" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-red-700 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Main Content --}}
    <main class="min-h-screen pb-20 md:pb-0">
        @yield('content')
    </main>

    {{-- Footer — design-system 4-col dark, rainbow wordmark --}}
    <footer class="bg-gray-900 dark:bg-black text-white relative z-30">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="lg:col-span-1">
                    @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="XMAN STUDIO" class="h-12 w-auto mb-4">
                    @else
                        <h3 class="ds-wordmark text-2xl font-black mb-4">XMAN STUDIO</h3>
                    @endif
                    <p class="text-gray-400 text-sm leading-relaxed mb-4">ผู้เชี่ยวชาญด้าน IT Solutions ครบวงจร พัฒนาซอฟต์แวร์และบริการเทคโนโลยีสารสนเทศ</p>
                    <div class="flex gap-4 text-gray-400">
                        <a href="https://www.facebook.com/xmanenterprise/" target="_blank" class="hover:text-blue-500 transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <a href="https://youtube.com/@metal-xproject" target="_blank" class="hover:text-red-500 transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold mb-4">บริการ</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="/services" class="hover:text-white transition-colors">Blockchain Development</a></li>
                        <li><a href="/services" class="hover:text-white transition-colors">พัฒนาเว็บไซต์</a></li>
                        <li><a href="/services" class="hover:text-white transition-colors">แอพพลิเคชัน</a></li>
                        <li><a href="/services" class="hover:text-white transition-colors">AI Services</a></li>
                        <li><a href="/services" class="hover:text-white transition-colors">IoT Solutions</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold mb-4">ช่วยเหลือ</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="/about" class="hover:text-white transition-colors">เกี่ยวกับเรา</a></li>
                        <li><a href="/team" class="hover:text-white transition-colors">ทีมงาน</a></li>
                        <li><a href="/support" class="hover:text-white transition-colors">ติดต่อ/สั่งซื้อ</a></li>
                        <li><a href="/rental" class="hover:text-white transition-colors">เช่าบริการ</a></li>
                        <li><a href="/portfolio" class="hover:text-white transition-colors">ผลงาน</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold mb-4">ติดต่อเรา</h4>
                    @php
                        $footerPhone = \App\Models\Setting::getValue('contact_phone', '080-6038278');
                        $footerEmail = \App\Models\Setting::getValue('contact_email', 'xjanovax@gmail.com');
                        $footerLineId = \App\Models\Setting::getValue('contact_line_id', '@xmanstudio');
                    @endphp
                    <ul class="space-y-3 text-gray-400 text-sm">
                        @if($footerPhone)
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11 11 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            {{ $footerPhone }}
                        </li>
                        @endif
                        @if($footerEmail)
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ $footerEmail }}
                        </li>
                        @endif
                        @if($footerLineId)
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>
                            Line: {{ $footerLineId }}
                        </li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="mt-12 pt-6 border-t border-gray-800">
                <div class="flex flex-col md:flex-row justify-between gap-2 text-sm text-gray-400">
                    <div>&copy; {{ date('Y') }} {{ config('app.name', 'XMAN Studio') }}. All rights reserved.</div>
                    <div class="flex gap-6">
                        <a href="/privacy" class="hover:text-white transition-colors">นโยบายความเป็นส่วนตัว</a>
                        <a href="/terms" class="hover:text-white transition-colors">ข้อกำหนดการใช้งาน</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    {{-- Scripts --}}
    <script>
        // Dark Mode Toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        darkModeToggle?.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        });

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        mobileMenuBtn?.addEventListener('click', () => {
            mobileMenu?.classList.toggle('hidden');
        });

        // Toast Notification Function
        window.showToast = function(message, type = 'info', duration = 5000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast-${type}`;
            toast.innerHTML = `
                <span>${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-2 hover:opacity-75">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            `;
            container?.appendChild(toast);
            setTimeout(() => toast.remove(), duration);
        };

        // Golden Fireflies — design-system signature
        (function() {
            const container = document.getElementById('fireflies-layer');
            if (!container) return;

            for (let i = 0; i < 35; i++) {
                const x = Math.random() * 100;
                const y = Math.random() * 100;
                const size = 2 + Math.random() * 3;
                const floatDuration = 8 + Math.random() * 12;
                const glowDuration = 2 + Math.random() * 4;
                const delay = Math.random() * 10;

                const wrapper = document.createElement('div');
                wrapper.className = 'firefly-wrapper';
                wrapper.style.cssText = `
                    position: absolute;
                    left: ${x}%;
                    top: ${y}%;
                    animation: firefly-float ${floatDuration}s ease-in-out infinite;
                    animation-delay: ${delay}s;
                `;

                const core = document.createElement('div');
                core.className = 'firefly-core firefly-gold';
                core.style.cssText = `
                    width: ${size}px;
                    height: ${size}px;
                    animation: firefly-glow ${glowDuration}s ease-in-out infinite alternate;
                    animation-delay: ${delay + 0.5}s;
                `;

                wrapper.appendChild(core);
                container.appendChild(wrapper);
            }
        })();
    </script>

    {{-- Mobile Bottom Navigation Bar --}}
    @include('components.mobile-bottom-nav')

    {{-- AI Chat Floating Widget --}}
    @include('components.ai-chat-widget')

    @stack('scripts')

    {{-- Custom Body End Code --}}
    @php
        $customBodyEndCode = \App\Models\Setting::getValue('custom_code_body_end', '');
    @endphp
    @if($customBodyEndCode)
        {!! $customBodyEndCode !!}
    @endif
</body>
</html>
