@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Dashboard - ภาพรวมระบบ')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 dark:from-gray-900 dark:via-blue-900 dark:to-purple-900">

    <!-- Premium Header Section -->
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl mb-8 animate-fade-in">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="absolute inset-0 backdrop-blur-3xl"></div>

        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl animate-blob"></div>
            <div class="absolute top-0 -right-4 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-4000"></div>
        </div>

        <div class="relative px-8 py-12">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-3 animate-slide-in">
                        ยินดีต้อนรับกลับมา, Admin 👋
                    </h1>
                    <p class="text-blue-100 text-lg">
                        ภาพรวมผลการดำเนินงานของระบบในวันนี้
                    </p>
                </div>
                <div class="hidden lg:flex items-center space-x-4">
                    <div class="text-right">
                        <div class="text-white/80 text-sm">วันที่</div>
                        <div class="text-white font-semibold text-lg">{{ now()->locale('th')->translatedFormat('d M Y') }}</div>
                    </div>
                    <div class="w-16 h-16 rounded-full bg-white/20 backdrop-blur-lg flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Premium Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

        <!-- Revenue Card -->
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in">
            <div class="absolute inset-0 bg-gradient-to-br from-green-400/10 to-emerald-600/10"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        +12.5%
                    </span>
                </div>
                <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">รายได้รวม</h3>
                <div class="flex items-end justify-between">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        ฿1,245,680
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-semibold text-green-600">฿85,240</span> จากเดือนที่แล้ว
                    </p>
                </div>
            </div>
        </div>

        <!-- Customers Card -->
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in animation-delay-200">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-400/10 to-cyan-600/10"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-400 to-cyan-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        +8.3%
                    </span>
                </div>
                <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">ลูกค้าทั้งหมด</h3>
                <div class="flex items-end justify-between">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        3,456
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-semibold text-blue-600">+245</span> ลูกค้าใหม่เดือนนี้
                    </p>
                </div>
            </div>
        </div>

        <!-- Orders Card -->
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in animation-delay-400">
            <div class="absolute inset-0 bg-gradient-to-br from-purple-400/10 to-pink-600/10"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-400 to-pink-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        +15.2%
                    </span>
                </div>
                <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">คำสั่งซื้อ</h3>
                <div class="flex items-end justify-between">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        2,847
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-semibold text-purple-600">156</span> รอดำเนินการ
                    </p>
                </div>
            </div>
        </div>

        <!-- Active Subscriptions Card -->
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in animation-delay-600">
            <div class="absolute inset-0 bg-gradient-to-br from-orange-400/10 to-red-600/10"></div>
            <div class="relative p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-400 to-red-600 flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        +6.7%
                    </span>
                </div>
                <h3 class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Subscriptions</h3>
                <div class="flex items-end justify-between">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        1,234
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-semibold text-orange-600">MRR ฿456,780</span>
                    </p>
                </div>
            </div>
        </div>

    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">

        <!-- Revenue Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 animate-fade-in animation-delay-800">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">กราฟรายได้</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">รายได้ในช่วง 12 เดือนที่ผ่านมา</p>
                </div>
                <div class="flex space-x-2">
                    <button class="px-3 py-1 text-xs font-medium rounded-lg bg-blue-500 text-white">เดือน</button>
                    <button class="px-3 py-1 text-xs font-medium rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200">สัปดาห์</button>
                    <button class="px-3 py-1 text-xs font-medium rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200">วัน</button>
                </div>
            </div>
            <div class="relative h-80">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Product Distribution Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 animate-fade-in animation-delay-1000">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">สัดส่วนผลิตภัณฑ์</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ยอดขายแยกตามหมวดหมู่</p>
                </div>
                <button class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                    </svg>
                </button>
            </div>
            <div class="relative h-80 flex items-center justify-center">
                <canvas id="productChart"></canvas>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-6">
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Software (45%)</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-purple-500 mr-2"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Hardware (30%)</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-pink-500 mr-2"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Services (15%)</span>
                </div>
                <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-orange-500 mr-2"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Other (10%)</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Tables Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">

        <!-- Recent Orders -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden animate-fade-in animation-delay-1200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">คำสั่งซื้อล่าสุด</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">รายการสั่งซื้อที่อัพเดทล่าสุด</p>
                    </div>
                    <a href="#" class="text-blue-600 hover:text-blue-700 text-sm font-medium flex items-center">
                        ดูทั้งหมด
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ลูกค้า</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ผลิตภัณฑ์</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ยอดเงิน</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">#ORD-2456</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold text-xs">
                                        สพ
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">สมพร ใจดี</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Metal-X Pro License</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">฿12,500</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    สำเร็จ
                                </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">#ORD-2455</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white font-semibold text-xs">
                                        วช
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">วิชัย รักษ์ดี</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Website Builder</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">฿8,900</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                    รอดำเนินการ
                                </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">#ORD-2454</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-pink-400 to-pink-600 flex items-center justify-center text-white font-semibold text-xs">
                                        นพ
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">นพดล สุขสันต์</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Enterprise Package</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">฿45,000</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    สำเร็จ
                                </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">#ORD-2453</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-semibold text-xs">
                                        อร
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">อรุณี มีสุข</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Basic Rental</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">฿3,500</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    กำลังส่ง
                                </span>
                            </td>
                        </tr>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">#ORD-2452</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white font-semibold text-xs">
                                        ชว
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">ชวลิต ประดิษฐ์</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">Premium License</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">฿18,900</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    สำเร็จ
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Products & Activity -->
        <div class="space-y-6">

            <!-- Top Products -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 animate-fade-in animation-delay-1400">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">สินค้ายอดนิยม</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Metal-X Pro</p>
                                <p class="text-xs text-gray-500">856 ขาย</p>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-green-600">฿425K</span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Website Builder</p>
                                <p class="text-xs text-gray-500">645 ขาย</p>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-green-600">฿323K</span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-pink-400 to-pink-600 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">SEO Tools</p>
                                <p class="text-xs text-gray-500">432 ขาย</p>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-green-600">฿216K</span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">Design Suite</p>
                                <p class="text-xs text-gray-500">389 ขาย</p>
                            </div>
                        </div>
                        <span class="text-sm font-bold text-green-600">฿195K</span>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 animate-fade-in animation-delay-1600">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">กิจกรรมล่าสุด</h2>
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 rounded-full bg-green-500 mt-2"></div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900 dark:text-white">คำสั่งซื้อใหม่ <span class="font-semibold">#ORD-2456</span></p>
                            <p class="text-xs text-gray-500">2 นาทีที่แล้ว</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 rounded-full bg-blue-500 mt-2"></div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900 dark:text-white">ลูกค้าใหม่สมัครสมาชิก</p>
                            <p class="text-xs text-gray-500">15 นาทีที่แล้ว</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 rounded-full bg-purple-500 mt-2"></div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900 dark:text-white">อัพเดทสินค้า Metal-X Pro</p>
                            <p class="text-xs text-gray-500">1 ชั่วโมงที่แล้ว</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 rounded-full bg-yellow-500 mt-2"></div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900 dark:text-white">Ticket ใหม่จากลูกค้า</p>
                            <p class="text-xs text-gray-500">3 ชั่วโมงที่แล้ว</p>
                        </div>
                    </div>

                    <div class="flex items-start space-x-3">
                        <div class="w-2 h-2 rounded-full bg-orange-500 mt-2"></div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-900 dark:text-white">รีวิวใหม่ 5 ดาว</p>
                            <p class="text-xs text-gray-500">5 ชั่วโมงที่แล้ว</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'],
            datasets: [{
                label: 'รายได้ (บาท)',
                data: [85000, 92000, 78000, 105000, 118000, 135000, 142000, 128000, 155000, 168000, 175000, 185000],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'รายได้: ฿' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function(value) {
                            return '฿' + (value / 1000) + 'K';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Product Distribution Chart
    const productCtx = document.getElementById('productChart').getContext('2d');
    new Chart(productCtx, {
        type: 'doughnut',
        data: {
            labels: ['Software', 'Hardware', 'Services', 'Other'],
            datasets: [{
                data: [45, 30, 15, 10],
                backgroundColor: [
                    'rgb(59, 130, 246)',
                    'rgb(168, 85, 247)',
                    'rgb(236, 72, 153)',
                    'rgb(251, 146, 60)'
                ],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
});
</script>

<style>
@keyframes blob {
    0%, 100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
}

.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

.animation-delay-200 {
    animation-delay: 0.2s;
}

.animation-delay-400 {
    animation-delay: 0.4s;
}

.animation-delay-600 {
    animation-delay: 0.6s;
}

.animation-delay-800 {
    animation-delay: 0.8s;
}

.animation-delay-1000 {
    animation-delay: 1s;
}

.animation-delay-1200 {
    animation-delay: 1.2s;
}

.animation-delay-1400 {
    animation-delay: 1.4s;
}

.animation-delay-1600 {
    animation-delay: 1.6s;
}
</style>
@endpush

@endsection
