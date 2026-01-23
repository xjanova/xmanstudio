@extends($publicLayout ?? 'layouts.app')

@section('title', 'ผลงานของเรา - XMAN Studio')

@section('content')
<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-gray-900 via-primary-900 to-gray-900 text-white py-20 overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1920')] bg-cover bg-center opacity-10"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-2 bg-primary-600/30 text-primary-300 text-sm font-semibold rounded-full mb-6 backdrop-blur-sm border border-primary-500/30">
            Portfolio
        </span>
        <h1 class="text-4xl md:text-6xl font-extrabold mb-6">ผลงานของเรา</h1>
        <p class="text-xl text-gray-300 max-w-3xl mx-auto">
            โปรเจคที่เราภูมิใจนำเสนอ จากลูกค้าที่ไว้วางใจ
        </p>
    </div>
</section>

<!-- Portfolio Grid -->
<section class="py-20 bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Filter Tabs -->
        <div class="flex flex-wrap justify-center gap-4 mb-12" x-data="{ activeTab: 'all' }">
            <button @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'" class="px-6 py-3 rounded-full font-semibold transition-all hover:shadow-lg">
                ทั้งหมด
            </button>
            <button @click="activeTab = 'blockchain'" :class="activeTab === 'blockchain' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'" class="px-6 py-3 rounded-full font-semibold transition-all hover:shadow-lg">
                Blockchain
            </button>
            <button @click="activeTab = 'web'" :class="activeTab === 'web' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'" class="px-6 py-3 rounded-full font-semibold transition-all hover:shadow-lg">
                Web
            </button>
            <button @click="activeTab = 'mobile'" :class="activeTab === 'mobile' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'" class="px-6 py-3 rounded-full font-semibold transition-all hover:shadow-lg">
                Mobile
            </button>
            <button @click="activeTab = 'ai'" :class="activeTab === 'ai' ? 'bg-primary-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300'" class="px-6 py-3 rounded-full font-semibold transition-all hover:shadow-lg">
                AI
            </button>
        </div>

        <!-- Portfolio Items -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Project 1 -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=600&h=400&fit=crop" alt="DeFi Platform" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="absolute bottom-0 left-0 right-0 p-6 transform translate-y-full group-hover:translate-y-0 transition-transform duration-300">
                    <span class="inline-block px-3 py-1 bg-primary-600 text-white text-xs rounded-full mb-2">Blockchain</span>
                    <h3 class="text-xl font-bold text-white mb-2">DeFi Lending Platform</h3>
                    <p class="text-gray-300 text-sm">แพลตฟอร์มกู้ยืม Crypto แบบ Decentralized</p>
                </div>
                <div class="p-6">
                    <span class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 text-xs rounded-full mb-2">Blockchain</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">DeFi Lending Platform</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">แพลตฟอร์มกู้ยืม Crypto แบบ Decentralized</p>
                </div>
            </div>

            <!-- Project 2 -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=600&h=400&fit=crop" alt="E-commerce" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="p-6">
                    <span class="inline-block px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs rounded-full mb-2">Web</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">E-commerce Platform</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ระบบ E-commerce ครบวงจรสำหรับธุรกิจค้าปลีก</p>
                </div>
            </div>

            <!-- Project 3 -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=600&h=400&fit=crop" alt="Mobile App" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="p-6">
                    <span class="inline-block px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs rounded-full mb-2">Mobile</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Food Delivery App</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">แอปสั่งอาหารออนไลน์ด้วย Flutter</p>
                </div>
            </div>

            <!-- Project 4 -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1677442136019-21780ecad995?w=600&h=400&fit=crop" alt="AI Chatbot" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="p-6">
                    <span class="inline-block px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-xs rounded-full mb-2">AI</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">AI Customer Service Bot</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Chatbot อัจฉริยะสำหรับบริการลูกค้า 24/7</p>
                </div>
            </div>

            <!-- Project 5 -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1551650975-87deedd944c3?w=600&h=400&fit=crop" alt="NFT Marketplace" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="p-6">
                    <span class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 text-xs rounded-full mb-2">Blockchain</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">NFT Marketplace</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">แพลตฟอร์มซื้อขาย NFT Art</p>
                </div>
            </div>

            <!-- Project 6 -->
            <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500">
                <div class="aspect-video overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1558346490-a72e53ae2d4f?w=600&h=400&fit=crop" alt="IoT Dashboard" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>
                <div class="p-6">
                    <span class="inline-block px-3 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 text-xs rounded-full mb-2">IoT</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Smart Farm Dashboard</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">ระบบ IoT สำหรับฟาร์มอัจฉริยะ</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- YouTube Channel Section -->
<section class="py-20 bg-white dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">ดูผลงาน AI บน YouTube</h2>
            <p class="text-lg text-gray-600 dark:text-gray-300">ติดตามผลงาน AI Video และ AI Music ของเราได้ที่ช่อง Metal-X Project</p>
        </div>

        <div class="flex justify-center">
            <a href="https://youtube.com/@metal-xproject" target="_blank" class="group inline-flex items-center px-8 py-4 bg-red-600 text-white font-bold text-lg rounded-xl transition-all duration-300 hover:bg-red-700 hover:shadow-xl hover:shadow-red-600/20 hover:scale-105">
                <svg class="w-8 h-8 mr-3" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
                ดูผลงานบน YouTube
                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gradient-to-r from-primary-600 to-purple-700">
    <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">พร้อมสร้างโปรเจคของคุณ?</h2>
        <p class="text-xl text-primary-100 mb-8">ติดต่อเราเพื่อรับใบเสนอราคาฟรี!</p>
        <a href="/support" class="inline-flex items-center px-8 py-4 bg-white text-primary-700 font-bold text-lg rounded-xl transition-all duration-300 hover:shadow-2xl hover:scale-105">
            รับใบเสนอราคา
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
            </svg>
        </a>
    </div>
</section>
@endsection
