<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'XMAN Studio - IT Solutions & Software Development')</title>
    <meta name="description" content="@yield('meta_description', 'XMAN Studio ผู้เชี่ยวชาญด้าน IT Solutions ครบวงจร ทำเว็บไซต์ แอพพลิเคชัน Blockchain IoT Network Security AI และอื่นๆ')">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/" class="text-2xl font-bold text-primary-600">XMAN STUDIO</a>
                    </div>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <a href="/" class="border-primary-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            หน้าหลัก
                        </a>
                        <a href="/services" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            บริการ
                        </a>
                        <a href="/products" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            ผลิตภัณฑ์
                        </a>
                        <a href="/portfolio" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            ผลงาน
                        </a>
                        <a href="/support" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            ติดต่อ/สนับสนุน
                        </a>
                    </div>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:items-center space-x-4">
                    <a href="/cart" class="relative p-2 text-gray-600 hover:text-primary-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-primary-600 rounded-full">0</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1">
                    <h3 class="text-2xl font-bold mb-4">XMAN STUDIO</h3>
                    <p class="text-gray-400">ผู้เชี่ยวชาญด้าน IT Solutions ครบวงจร</p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">บริการ</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#blockchain" class="hover:text-white">Blockchain Development</a></li>
                        <li><a href="#web" class="hover:text-white">พัฒนาเว็บไซต์</a></li>
                        <li><a href="#mobile" class="hover:text-white">แอพพลิเคชัน</a></li>
                        <li><a href="#ai" class="hover:text-white">AI Services</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">ช่วยเหลือ</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/support" class="hover:text-white">ติดต่อสนับสนุน</a></li>
                        <li><a href="/docs" class="hover:text-white">เอกสารประกอบ</a></li>
                        <li><a href="/license" class="hover:text-white">จัดการ License</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">ติดต่อเรา</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li>Email: info@xmanstudio.com</li>
                        <li>Line OA: @xmanstudio</li>
                        <li>YouTube: Metal-X Project</li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 pt-8 border-t border-gray-800 text-center text-gray-400">
                <p>&copy; 2024 XMAN Studio. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
