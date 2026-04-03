@extends($publicLayout ?? 'layouts.app')

@section('title', 'CluadeX - AI Coding Assistant | XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-indigo-900 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%236366F1&quot; fill-opacity=&quot;0.05&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-indigo-400 hover:text-indigo-300 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับไปรายการผลิตภัณฑ์
                </a>
            </nav>

            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Product Info -->
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-indigo-500/20 rounded-full text-indigo-300 text-sm mb-6 backdrop-blur-sm border border-indigo-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        AI-Powered Coding Assistant
                    </div>

                    <h1 class="text-5xl md:text-7xl font-black text-white mb-6">
                        Cluade<span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-400">X</span>
                    </h1>

                    <p class="text-xl text-gray-300 mb-4">
                        ผู้ช่วยเขียนโค้ด AI ระดับมืออาชีพ ทำงานบนเครื่องของคุณ ข้อมูลเป็นส่วนตัว 100%
                    </p>
                    <p class="text-lg text-gray-400 mb-8">
                        Professional AI coding assistant that runs locally. Your data stays private. Supports 5 AI providers, 25+ agent tools, 22+ local models, Thai/English bilingual.
                    </p>

                    <!-- Key Feature Tags -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        <span class="px-3 py-1 bg-indigo-500/20 text-indigo-300 rounded-full text-sm border border-indigo-500/30">🧠 Multi-AI</span>
                        <span class="px-3 py-1 bg-cyan-500/20 text-cyan-300 rounded-full text-sm border border-cyan-500/30">🔒 Privacy-First</span>
                        <span class="px-3 py-1 bg-green-500/20 text-green-300 rounded-full text-sm border border-green-500/30">🆓 Free Tier</span>
                        <span class="px-3 py-1 bg-purple-500/20 text-purple-300 rounded-full text-sm border border-purple-500/30">🇹🇭 ภาษาไทย</span>
                        <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-sm border border-yellow-500/30">⚡ Local GPU</span>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap gap-4">
                        <a href="https://github.com/xjanova/cluadeX/releases/latest"
                           class="px-8 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-green-500/25 text-lg">
                            ⬇️ ดาวน์โหลดฟรี
                        </a>
                        @auth
                            @if(!$hasPurchased)
                                <a href="#pricing"
                                   class="px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-indigo-500/25 text-lg">
                                    🔑 อัพเกรด Pro
                                </a>
                            @endif
                        @else
                            <a href="#pricing"
                               class="px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-indigo-500/25 text-lg">
                                🔑 อัพเกรด Pro
                            </a>
                        @endauth
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-6 mt-10">
                        <div class="text-center">
                            <div class="text-3xl font-black text-white">5</div>
                            <div class="text-sm text-gray-400">AI Providers</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-black text-white">25+</div>
                            <div class="text-sm text-gray-400">Agent Tools</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-black text-white">22+</div>
                            <div class="text-sm text-gray-400">Local Models</div>
                        </div>
                    </div>
                </div>

                <!-- Right: Preview Image -->
                <div class="relative">
                    <div class="bg-gradient-to-br from-indigo-500/20 to-cyan-500/20 rounded-2xl p-6 backdrop-blur-sm border border-indigo-500/30">
                        <div class="aspect-video bg-gray-800 rounded-xl flex items-center justify-center overflow-hidden">
                            {{-- TODO: Replace with actual screenshot --}}
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="CluadeX" class="w-full h-full object-cover">
                            @else
                                <div class="text-center p-8">
                                    <div class="text-8xl mb-4">🤖</div>
                                    <p class="text-gray-400 text-lg">CluadeX Screenshot</p>
                                    <p class="text-gray-500 text-sm mt-2">Catppuccin Mocha Dark Theme</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    <!-- Floating badges -->
                    <div class="absolute -top-4 -right-4 px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-bold rounded-xl shadow-lg transform rotate-6 text-sm">
                        ฟรี!
                    </div>
                    <div class="absolute -bottom-4 -left-4 px-4 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white font-bold rounded-xl shadow-lg transform -rotate-3 text-sm">
                        Zero Telemetry
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Free vs Pro Comparison -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-black text-white text-center mb-4">Free vs Pro</h2>
            <p class="text-gray-400 text-center mb-12">ดาวน์โหลดฟรี ใช้ฟรี อัพเกรดเมื่อพร้อม</p>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Free Tier -->
                <div class="bg-gray-800/50 rounded-2xl p-8 border border-gray-700">
                    <div class="text-center mb-6">
                        <span class="px-4 py-1 bg-green-500/20 text-green-400 rounded-full text-sm font-bold">FREE</span>
                        <h3 class="text-2xl font-bold text-white mt-4">ฟรีตลอดชีพ</h3>
                        <div class="text-4xl font-black text-green-400 mt-2">฿0</div>
                    </div>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-center"><span class="text-green-400 mr-3">✓</span> Local GGUF Inference (22+ models)</li>
                        <li class="flex items-center"><span class="text-green-400 mr-3">✓</span> Ollama Integration</li>
                        <li class="flex items-center"><span class="text-green-400 mr-3">✓</span> Chat Persistence (SQLite + FTS5)</li>
                        <li class="flex items-center"><span class="text-green-400 mr-3">✓</span> Markdown & Syntax Highlighting</li>
                        <li class="flex items-center"><span class="text-green-400 mr-3">✓</span> GPU Auto-Detection</li>
                        <li class="flex items-center"><span class="text-green-400 mr-3">✓</span> HuggingFace Model Hub</li>
                        <li class="flex items-center"><span class="text-green-400 mr-3">✓</span> Buddy Companion (18 species)</li>
                        <li class="flex items-center"><span class="text-green-400 mr-3">✓</span> Thai/English UI</li>
                        <li class="flex items-center"><span class="text-green-400 mr-3">✓</span> Code Execution (Sandbox)</li>
                        <li class="flex items-center"><span class="text-green-400 mr-3">✓</span> File System Tools</li>
                        <li class="flex items-center"><span class="text-green-400 mr-3">✓</span> DPAPI Key Encryption</li>
                        <li class="flex items-center"><span class="text-green-400 mr-3">✓</span> Zero Telemetry</li>
                        <li class="flex items-center text-gray-500"><span class="mr-3">✗</span> Cloud AI Providers</li>
                        <li class="flex items-center text-gray-500"><span class="mr-3">✗</span> Git/GitHub Integration</li>
                        <li class="flex items-center text-gray-500"><span class="mr-3">✗</span> Plugin System</li>
                    </ul>
                    <a href="https://github.com/xjanova/cluadeX/releases/latest"
                       class="block w-full text-center mt-8 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition-all">
                        ดาวน์โหลดฟรี
                    </a>
                </div>

                <!-- Pro Tier -->
                <div class="bg-gradient-to-br from-indigo-900/50 to-purple-900/50 rounded-2xl p-8 border border-indigo-500/50 relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-xs font-bold rounded-full">
                        แนะนำ
                    </div>
                    <div class="text-center mb-6">
                        <span class="px-4 py-1 bg-indigo-500/20 text-indigo-400 rounded-full text-sm font-bold">PRO</span>
                        <h3 class="text-2xl font-bold text-white mt-4">ปลดล็อกทั้งหมด</h3>
                        <div class="text-4xl font-black text-indigo-400 mt-2">฿199<span class="text-lg text-gray-400">/เดือน</span></div>
                    </div>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-center"><span class="text-indigo-400 mr-3">✓</span> <strong>ทุกอย่างใน Free</strong> +</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-3">✓</span> OpenAI (GPT-4o, o1, o3)</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-3">✓</span> Anthropic (Claude 4 Opus/Sonnet)</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-3">✓</span> Google Gemini (2.5 Pro/Flash)</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-3">✓</span> Git Integration (12 commands)</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-3">✓</span> GitHub Integration (PR, Issues)</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-3">✓</span> Plugin System (20+ plugins)</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-3">✓</span> Web Fetch & Context Memory</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-3">✓</span> Smart Code Editing</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-3">✓</span> AI Code Review</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-3">✓</span> Auto-Update</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-3">✓</span> Priority Bug Support</li>
                    </ul>
                    <a href="#pricing"
                       class="block w-full text-center mt-8 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-indigo-500/25">
                        เลือกแพคเกจ Pro
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-black text-white text-center mb-4">ฟีเจอร์ทั้งหมด</h2>
            <p class="text-gray-400 text-center mb-12">ทุกอย่างที่คุณต้องการในการเขียนโค้ดด้วย AI</p>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                $features = [
                    ['icon' => '🧠', 'title' => 'Multi-Step Reasoning', 'desc' => 'Agent loop 15 รอบ — อ่าน → แก้ → ตรวจสอบ → ทดสอบ อัตโนมัติ', 'color' => 'indigo'],
                    ['icon' => '🔄', 'title' => 'Self-Correction', 'desc' => 'ถ้าเจอ error AI แก้ไขตัวเองอัตโนมัติ ลอง 2-3 วิธี', 'color' => 'cyan'],
                    ['icon' => '💻', 'title' => 'Local GGUF', 'desc' => 'รันโมเดล AI บนเครื่องด้วย GPU/CPU ไม่ต้องอินเทอร์เน็ต', 'color' => 'green'],
                    ['icon' => '🔒', 'title' => 'Privacy-First', 'desc' => 'Zero Telemetry — ไม่ส่งข้อมูลใดๆ กลับ โค้ดอยู่ในเครื่องคุณ', 'color' => 'red'],
                    ['icon' => '📊', 'title' => 'LED Context Bar', 'desc' => 'แถบ LED 5 ช่อง เตือนเมื่อ context เต็ม แนะนำเริ่ม session ใหม่', 'color' => 'yellow'],
                    ['icon' => '🔍', 'title' => 'Code Review', 'desc' => 'รีวิวโค้ดด้วย AI ตรวจ bugs, security, improvements ให้ ⭐1-5', 'color' => 'purple'],
                    ['icon' => '🛡️', 'title' => 'Permission System', 'desc' => 'กฎ Allow/Deny/Ask สำหรับไฟล์ คำสั่ง และเครือข่าย', 'color' => 'orange'],
                    ['icon' => '🌿', 'title' => 'Git Integration', 'desc' => '12 คำสั่ง Git ครบ — commit, branch, merge, diff, stash', 'color' => 'emerald'],
                    ['icon' => '🐙', 'title' => 'GitHub Integration', 'desc' => 'สร้าง PR, Issues, ค้นหา repo จากแชท', 'color' => 'gray'],
                    ['icon' => '🧩', 'title' => 'Plugin System', 'desc' => '20+ ปลั๊กอินพร้อมใช้ — lint, test, security, docs, git hooks', 'color' => 'pink'],
                    ['icon' => '🇹🇭', 'title' => 'Thai/English', 'desc' => '170+ คำแปล AI ตอบตามภาษาที่เลือก ใช้ภาษาไทยได้เต็มที่', 'color' => 'blue'],
                    ['icon' => '🐾', 'title' => 'Buddy Companion', 'desc' => 'สัตว์เลี้ยง AI 18 สายพันธุ์ 5 ระดับหายาก หมวก ลูบได้!', 'color' => 'amber'],
                ];
                @endphp

                @foreach($features as $f)
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-{{ $f['color'] }}-500/50 transition-all group">
                    <div class="text-4xl mb-4">{{ $f['icon'] }}</div>
                    <h3 class="text-lg font-bold text-white mb-2 group-hover:text-{{ $f['color'] }}-400 transition-colors">{{ $f['title'] }}</h3>
                    <p class="text-gray-400 text-sm">{{ $f['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Screenshots Gallery -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-black text-white text-center mb-4">หน้าจอโปรแกรม</h2>
            <p class="text-gray-400 text-center mb-12">ดีไซน์สวยงามด้วย Catppuccin Mocha Theme</p>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- TODO: Add screenshots here - replace placeholder divs with actual images --}}
                @php
                $screenshots = [
                    ['title' => 'Chat View', 'desc' => 'แชทกับ AI พร้อม LED context bar'],
                    ['title' => 'Model Manager', 'desc' => 'ดาวน์โหลดโมเดลจาก HuggingFace'],
                    ['title' => 'Plugin Catalog', 'desc' => '20+ ปลั๊กอินพร้อมติดตั้ง'],
                    ['title' => 'Features Page', 'desc' => '27 ฟีเจอร์ เปิด/ปิดได้'],
                    ['title' => 'Git Integration', 'desc' => 'จัดการ Git จากแชท'],
                    ['title' => 'Buddy Companion', 'desc' => 'สัตว์เลี้ยง AI น่ารัก'],
                ];
                @endphp

                @foreach($screenshots as $ss)
                <div class="bg-gray-800 rounded-xl overflow-hidden border border-gray-700 hover:border-indigo-500/50 transition-all">
                    {{-- TODO: Replace with <img src="..." alt="{{ $ss['title'] }}" class="w-full aspect-video object-cover"> --}}
                    <div class="aspect-video bg-gray-700 flex items-center justify-center">
                        <div class="text-center text-gray-500">
                            <div class="text-4xl mb-2">📸</div>
                            <p class="text-sm">{{ $ss['title'] }}</p>
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="text-white font-bold text-sm">{{ $ss['title'] }}</h3>
                        <p class="text-gray-400 text-xs mt-1">{{ $ss['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Supported Models -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-black text-white text-center mb-4">รองรับ 22+ โมเดล AI</h2>
            <p class="text-gray-400 text-center mb-12">โมเดลคัดสรรพร้อม star rating ตามคุณภาพ</p>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                $models = [
                    ['name' => 'Gemma 4 31B', 'stars' => 5, 'badge' => 'NEW', 'provider' => 'Google'],
                    ['name' => 'Qwen2.5 Coder 32B', 'stars' => 5, 'badge' => 'TOP', 'provider' => 'Alibaba'],
                    ['name' => 'DeepSeek R1 14B', 'stars' => 5, 'badge' => '', 'provider' => 'DeepSeek'],
                    ['name' => 'Qwen2.5 Coder 14B', 'stars' => 5, 'badge' => '', 'provider' => 'Alibaba'],
                    ['name' => 'Gemma 4 26B MoE', 'stars' => 4, 'badge' => 'NEW', 'provider' => 'Google'],
                    ['name' => 'Qwen2.5 Coder 7B', 'stars' => 4, 'badge' => '', 'provider' => 'Alibaba'],
                    ['name' => 'DeepSeek R1 7B', 'stars' => 4, 'badge' => '', 'provider' => 'DeepSeek'],
                    ['name' => 'Llama 3.1 8B', 'stars' => 4, 'badge' => '', 'provider' => 'Meta'],
                    ['name' => 'Gemma 3 12B', 'stars' => 4, 'badge' => '', 'provider' => 'Google'],
                    ['name' => 'Gemma 3 27B', 'stars' => 4, 'badge' => '', 'provider' => 'Google'],
                    ['name' => 'DeepSeek Coder V2', 'stars' => 4, 'badge' => '', 'provider' => 'DeepSeek'],
                    ['name' => 'Phi 3.5 Mini', 'stars' => 3, 'badge' => '', 'provider' => 'Microsoft'],
                ];
                @endphp

                @foreach($models as $m)
                <div class="bg-gray-800/50 rounded-lg p-4 border border-gray-700 flex items-center gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-white font-bold text-sm">{{ $m['name'] }}</span>
                            @if($m['badge'])
                                <span class="px-2 py-0.5 bg-indigo-500/30 text-indigo-300 text-[10px] font-bold rounded">{{ $m['badge'] }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-yellow-400 text-xs">{!! str_repeat('⭐', $m['stars']) !!}</span>
                            <span class="text-gray-500 text-xs">{{ $m['provider'] }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <p class="text-center text-gray-500 text-sm mt-6">+ Cloud: GPT-4o, Claude 4 Opus, Gemini 2.5 Pro, Ollama และอื่นๆ อีกมากมาย</p>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-black text-white text-center mb-4">แพคเกจ Pro</h2>
            <p class="text-gray-400 text-center mb-12">ปลดล็อกฟีเจอร์ขั้นสูงทั้งหมด</p>

            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Monthly -->
                <div class="bg-gray-800/50 rounded-2xl p-8 border border-gray-700 hover:border-indigo-500/50 transition-all text-center">
                    <h3 class="text-lg font-bold text-gray-400 mb-2">รายเดือน</h3>
                    <div class="text-5xl font-black text-white mb-1">฿199</div>
                    <div class="text-gray-500 mb-6">/เดือน</div>
                    <ul class="space-y-2 text-gray-300 text-sm text-left mb-8">
                        <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> ทุกฟีเจอร์ Pro</li>
                        <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> อัพเดทอัตโนมัติ</li>
                        <li class="flex items-center"><span class="text-green-400 mr-2">✓</span> ยกเลิกเมื่อไหร่ก็ได้</li>
                    </ul>
                    @auth
                    <button onclick="addToCart('monthly')"
                            class="w-full px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-bold rounded-xl transition-all">
                        เลือกแพคเกจ
                    </button>
                    @else
                    <a href="{{ route('login') }}"
                       class="block w-full px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-bold rounded-xl transition-all text-center">
                        เข้าสู่ระบบเพื่อซื้อ
                    </a>
                    @endauth
                </div>

                <!-- Yearly (Recommended) -->
                <div class="bg-gradient-to-br from-indigo-900/50 to-purple-900/50 rounded-2xl p-8 border border-indigo-500/50 text-center relative transform md:scale-105">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-xs font-bold rounded-full">
                        ประหยัด 63%
                    </div>
                    <h3 class="text-lg font-bold text-indigo-400 mb-2">รายปี</h3>
                    <div class="text-5xl font-black text-white mb-1">฿899</div>
                    <div class="text-gray-500 mb-1">/ปี</div>
                    <div class="text-green-400 text-sm mb-6">≈ ฿75/เดือน (ประหยัด ฿1,489)</div>
                    <ul class="space-y-2 text-gray-300 text-sm text-left mb-8">
                        <li class="flex items-center"><span class="text-indigo-400 mr-2">✓</span> ทุกฟีเจอร์ Pro</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-2">✓</span> อัพเดทอัตโนมัติ</li>
                        <li class="flex items-center"><span class="text-indigo-400 mr-2">✓</span> Priority Support</li>
                    </ul>
                    @auth
                    <button onclick="addToCart('yearly')"
                            class="w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-indigo-500/25">
                        เลือกแพคเกจ
                    </button>
                    @else
                    <a href="{{ route('login') }}"
                       class="block w-full px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all text-center">
                        เข้าสู่ระบบเพื่อซื้อ
                    </a>
                    @endauth
                </div>

                <!-- Lifetime -->
                <div class="bg-gray-800/50 rounded-2xl p-8 border border-yellow-500/30 hover:border-yellow-500/50 transition-all text-center">
                    <h3 class="text-lg font-bold text-yellow-400 mb-2">ตลอดชีพ</h3>
                    <div class="text-5xl font-black text-white mb-1">฿4,999</div>
                    <div class="text-gray-500 mb-1">จ่ายครั้งเดียว</div>
                    <div class="text-yellow-400 text-sm mb-6">ไม่มีค่าใช้จ่ายเพิ่ม ตลอดไป</div>
                    <ul class="space-y-2 text-gray-300 text-sm text-left mb-8">
                        <li class="flex items-center"><span class="text-yellow-400 mr-2">✓</span> ทุกฟีเจอร์ Pro ตลอดชีพ</li>
                        <li class="flex items-center"><span class="text-yellow-400 mr-2">✓</span> อัพเดทตลอดชีพ</li>
                        <li class="flex items-center"><span class="text-yellow-400 mr-2">✓</span> VIP Support</li>
                        <li class="flex items-center"><span class="text-yellow-400 mr-2">✓</span> Early Access ฟีเจอร์ใหม่</li>
                    </ul>
                    @auth
                    <button onclick="addToCart('lifetime')"
                            class="w-full px-6 py-3 bg-gradient-to-r from-yellow-600 to-amber-600 hover:from-yellow-700 hover:to-amber-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-yellow-500/25">
                        ซื้อตลอดชีพ
                    </button>
                    @else
                    <a href="{{ route('login') }}"
                       class="block w-full px-6 py-3 bg-gradient-to-r from-yellow-600 to-amber-600 hover:from-yellow-700 hover:to-amber-700 text-white font-bold rounded-xl transition-all text-center">
                        เข้าสู่ระบบเพื่อซื้อ
                    </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    <!-- System Requirements -->
    <section class="py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-black text-white text-center mb-12">System Requirements</h2>

            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-bold text-green-400 mb-4">ขั้นต่ำ (Minimum)</h3>
                    <ul class="space-y-2 text-gray-300 text-sm">
                        <li><strong>OS:</strong> Windows 10/11 (x64)</li>
                        <li><strong>RAM:</strong> 8 GB</li>
                        <li><strong>Disk:</strong> 500 MB + model size</li>
                        <li><strong>CPU:</strong> Any modern x64 processor</li>
                        <li><strong>GPU:</strong> ไม่จำเป็น (CPU mode)</li>
                    </ul>
                </div>
                <div class="bg-gray-800/50 rounded-xl p-6 border border-indigo-500/30">
                    <h3 class="text-lg font-bold text-indigo-400 mb-4">แนะนำ (Recommended)</h3>
                    <ul class="space-y-2 text-gray-300 text-sm">
                        <li><strong>OS:</strong> Windows 11 (x64)</li>
                        <li><strong>RAM:</strong> 16+ GB</li>
                        <li><strong>Disk:</strong> SSD 50+ GB</li>
                        <li><strong>GPU:</strong> NVIDIA RTX 3060+ (8GB VRAM)</li>
                        <li><strong>CUDA:</strong> 12.x (auto-detected)</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Bug Report & Support -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-black text-white mb-4">Support & Bug Report</h2>
            <p class="text-gray-400 mb-8">พบปัญหา? แจ้งได้จากในแอปโดยตรง หรือผ่าน GitHub Issues</p>

            <div class="flex flex-wrap gap-4 justify-center">
                <a href="https://github.com/xjanova/cluadeX/issues"
                   class="px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-bold rounded-xl transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
                    GitHub Issues
                </a>
                <a href="mailto:support@xman4289.com"
                   class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition-all flex items-center gap-2">
                    📧 Email Support
                </a>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-black text-white mb-4">พร้อมเขียนโค้ดกับ AI แล้วหรือยัง?</h2>
            <p class="text-xl text-gray-400 mb-8">ดาวน์โหลดฟรี ใช้ฟรี ไม่มีค่าใช้จ่าย</p>
            <a href="https://github.com/xjanova/cluadeX/releases/latest"
               class="inline-flex items-center px-10 py-5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-black text-xl rounded-2xl transition-all transform hover:scale-105 shadow-2xl shadow-green-500/25">
                ⬇️ ดาวน์โหลด CluadeX ฟรี
            </a>
            <p class="text-gray-500 text-sm mt-4">Windows 10/11 · .NET 8 · No sign-up required</p>
        </div>
    </section>
</div>

@push('scripts')
<script>
function addToCart(plan) {
    // Integration with xman4289.com cart system
    const productSlug = 'cluadex';
    const planMap = {
        'monthly': { name: 'CluadeX Pro (Monthly)', price: 199 },
        'yearly': { name: 'CluadeX Pro (Yearly)', price: 899 },
        'lifetime': { name: 'CluadeX Pro (Lifetime)', price: 4999 },
    };

    const selected = planMap[plan];
    if (selected) {
        window.location.href = `/cart/add/${productSlug}?plan=${plan}`;
    }
}
</script>
@endpush
@endsection
