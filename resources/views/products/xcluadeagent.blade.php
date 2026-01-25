@extends($publicLayout ?? 'layouts.app')

@section('title', 'XcluadeAgent - GitHub Sync with AI | XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-violet-900 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%238B5CF6\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-violet-400 hover:text-violet-300 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับไปรายการผลิตภัณฑ์
                </a>
            </nav>

            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Product Info -->
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-violet-500/20 rounded-full text-violet-300 text-sm mb-6 backdrop-blur-sm border border-violet-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        AI-Powered GitHub Sync
                    </div>

                    <h1 class="text-5xl md:text-6xl font-black text-white mb-6">
                        Xcluade<span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-purple-400">Agent</span>
                    </h1>

                    <p class="text-xl text-gray-300 mb-8">
                        Windows Desktop Service สำหรับ Sync Local Folders กับ GitHub Repositories พร้อม AI Assistant จาก Claude ช่วยจัดการโค้ดอัตโนมัติ
                    </p>

                    <!-- Key Features Tags -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        <span class="px-3 py-1 bg-violet-500/20 text-violet-300 rounded-full text-sm border border-violet-500/30">Claude AI</span>
                        <span class="px-3 py-1 bg-purple-500/20 text-purple-300 rounded-full text-sm border border-purple-500/30">GitHub Sync</span>
                        <span class="px-3 py-1 bg-blue-500/20 text-blue-300 rounded-full text-sm border border-blue-500/30">Auto Commit</span>
                        <span class="px-3 py-1 bg-indigo-500/20 text-indigo-300 rounded-full text-sm border border-indigo-500/30">Windows Service</span>
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
                    <div class="bg-gradient-to-br from-violet-500/20 to-purple-500/20 rounded-2xl p-8 backdrop-blur-sm border border-violet-500/30">
                        <div class="aspect-video bg-gray-800 rounded-xl flex items-center justify-center overflow-hidden">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="XcluadeAgent" class="w-full h-full object-cover">
                            @else
                                <div class="text-center">
                                    <svg class="w-24 h-24 mx-auto text-violet-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                    </svg>
                                    <p class="text-gray-400">XcluadeAgent Dashboard</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Architecture Section -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">สถาปัตยกรรมระบบ</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">พัฒนาด้วย Clean Architecture บน .NET 8 WPF รองรับการทำงานเบื้องหลังแบบ Background Service</p>

            <div class="grid md:grid-cols-4 gap-6">
                <!-- Presentation Layer -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-violet-500/50 transition-all">
                    <div class="w-12 h-12 bg-violet-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Presentation</h3>
                    <p class="text-gray-400 text-sm">WPF MVVM UI พร้อม System Tray Integration</p>
                </div>

                <!-- Application Layer -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-violet-500/50 transition-all">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Application</h3>
                    <p class="text-gray-400 text-sm">Business Logic, Commands/Queries CQRS Pattern</p>
                </div>

                <!-- Domain Layer -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-violet-500/50 transition-all">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Domain</h3>
                    <p class="text-gray-400 text-sm">Core Entities, Interfaces, Domain Events</p>
                </div>

                <!-- Infrastructure Layer -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-violet-500/50 transition-all">
                    <div class="w-12 h-12 bg-indigo-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Infrastructure</h3>
                    <p class="text-gray-400 text-sm">GitHub API, Claude API, File System</p>
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
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-violet-500/50 transition-all">
                    <div class="w-12 h-12 bg-violet-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Auto-Sync GitHub</h3>
                    <p class="text-gray-400">ซิงค์โฟลเดอร์กับ GitHub Repository อัตโนมัติ รองรับ Multiple Repositories</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-violet-500/50 transition-all">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Claude AI Assistant</h3>
                    <p class="text-gray-400">AI ช่วยสร้าง Commit Messages, Code Review และแนะนำการปรับปรุงโค้ด</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-violet-500/50 transition-all">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Change Detection</h3>
                    <p class="text-gray-400">ตรวจจับการเปลี่ยนแปลงไฟล์แบบ Real-time ด้วย FileSystemWatcher</p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-violet-500/50 transition-all">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Smart Commit</h3>
                    <p class="text-gray-400">AI สร้าง Commit Message ที่มีความหมาย ตาม Conventional Commits</p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-violet-500/50 transition-all">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Scheduled Sync</h3>
                    <p class="text-gray-400">ตั้งเวลา Sync อัตโนมัติตาม Schedule หรือทำทันทีเมื่อมีการเปลี่ยนแปลง</p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-violet-500/50 transition-all">
                    <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">System Tray</h3>
                    <p class="text-gray-400">ทำงานเบื้องหลังใน System Tray พร้อม Quick Actions และการแจ้งเตือน</p>
                </div>
            </div>
        </div>
    </section>

    <!-- AI Features -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">Claude AI Integration</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">ใช้พลัง Claude AI จาก Anthropic ช่วยจัดการ Git Workflow อย่างชาญฉลาด</p>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-gradient-to-br from-violet-500/10 to-purple-500/10 rounded-xl p-6 border border-violet-500/30">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-violet-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Intelligent Commit Messages
                    </h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start">
                            <span class="w-2 h-2 bg-violet-400 rounded-full mr-3 mt-2"></span>
                            <span>วิเคราะห์ Diff เพื่อสร้าง Commit Message ที่มีความหมาย</span>
                        </li>
                        <li class="flex items-start">
                            <span class="w-2 h-2 bg-violet-400 rounded-full mr-3 mt-2"></span>
                            <span>รองรับ Conventional Commits Format</span>
                        </li>
                        <li class="flex items-start">
                            <span class="w-2 h-2 bg-violet-400 rounded-full mr-3 mt-2"></span>
                            <span>ปรับแต่ง Style และ Format ได้ตามต้องการ</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-gradient-to-br from-purple-500/10 to-blue-500/10 rounded-xl p-6 border border-purple-500/30">
                    <h3 class="text-xl font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-purple-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        Code Analysis
                    </h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start">
                            <span class="w-2 h-2 bg-purple-400 rounded-full mr-3 mt-2"></span>
                            <span>ตรวจสอบ Code Quality และแนะนำปรับปรุง</span>
                        </li>
                        <li class="flex items-start">
                            <span class="w-2 h-2 bg-purple-400 rounded-full mr-3 mt-2"></span>
                            <span>ตรวจจับ Potential Bugs และ Security Issues</span>
                        </li>
                        <li class="flex items-start">
                            <span class="w-2 h-2 bg-purple-400 rounded-full mr-3 mt-2"></span>
                            <span>สร้าง Code Summary สำหรับ Pull Requests</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Configuration -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">การตั้งค่า</h2>

            <div class="max-w-3xl mx-auto">
                <div class="bg-gray-800/50 rounded-xl p-8 border border-gray-700">
                    <h3 class="text-xl font-bold text-white mb-6">ไฟล์ Configuration</h3>

                    <div class="bg-gray-900 rounded-lg p-4 font-mono text-sm overflow-x-auto">
                        <pre class="text-gray-300"><code>{
  <span class="text-green-400">"GitHubToken"</span>: <span class="text-yellow-400">"ghp_xxxxxxxxxxxx"</span>,
  <span class="text-green-400">"ClaudeApiKey"</span>: <span class="text-yellow-400">"sk-ant-xxxxxxxxxxxx"</span>,
  <span class="text-green-400">"Repositories"</span>: [
    {
      <span class="text-green-400">"LocalPath"</span>: <span class="text-yellow-400">"C:\\Projects\\MyApp"</span>,
      <span class="text-green-400">"RemoteUrl"</span>: <span class="text-yellow-400">"github.com/user/myapp"</span>,
      <span class="text-green-400">"Branch"</span>: <span class="text-yellow-400">"main"</span>,
      <span class="text-green-400">"AutoSync"</span>: <span class="text-blue-400">true</span>
    }
  ],
  <span class="text-green-400">"SyncInterval"</span>: <span class="text-blue-400">300</span>,
  <span class="text-green-400">"AIAssist"</span>: <span class="text-blue-400">true</span>
}</code></pre>
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
                        ขั้นต่ำ
                    </h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>Windows 10 version 1903+</li>
                        <li>.NET 8.0 Runtime</li>
                        <li>RAM 4GB</li>
                        <li>พื้นที่ว่าง 200MB</li>
                        <li>Internet Connection</li>
                    </ul>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-violet-500/50">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-violet-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        ต้องการเพิ่มเติม
                    </h3>
                    <ul class="space-y-2 text-gray-300">
                        <li>GitHub Account + Personal Access Token</li>
                        <li>Claude API Key (Anthropic)</li>
                        <li>Git installed on system</li>
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
                    <div class="w-16 h-16 bg-violet-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Solo Developers</h3>
                    <p class="text-gray-400">นักพัฒนาคนเดียวที่ต้องการ Backup โค้ดไปยัง GitHub อัตโนมัติ</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Team Projects</h3>
                    <p class="text-gray-400">ทีมที่ต้องการให้สมาชิกทุกคน Sync โค้ดแบบ Consistent</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Learning Projects</h3>
                    <p class="text-gray-400">นักเรียนที่เรียน Git และต้องการ AI ช่วยเขียน Commits</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">เริ่มต้นใช้งาน XcluadeAgent</h2>
            <p class="text-gray-400 mb-8">ดาวน์โหลดฟรี และให้ AI ช่วยจัดการ Git Workflow ของคุณ</p>

            <div class="flex flex-wrap justify-center gap-4">
                @auth
                    @if($hasPurchased)
                        <a href="{{ route('customer.downloads') }}"
                           class="px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-green-500/25">
                            ดาวน์โหลดเลย
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
