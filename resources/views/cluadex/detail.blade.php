@extends('layouts.app')

@section('title', 'CluadeX - ผู้ช่วยเขียนโค้ด AI | XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-indigo-950 to-gray-900">

    {{-- ═══════ HERO SECTION ═══════ --}}
    <section class="relative py-20 overflow-hidden">
        {{-- Background pattern --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 25% 25%, rgba(99,102,241,0.3) 0%, transparent 50%), radial-gradient(circle at 75% 75%, rgba(6,182,212,0.2) 0%, transparent 50%);"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">

                {{-- Left: Info --}}
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-indigo-500/20 rounded-full text-indigo-300 text-sm mb-6 backdrop-blur-sm border border-indigo-500/30">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                        AI-Powered Coding Assistant
                    </div>

                    <h1 class="text-5xl md:text-7xl font-black text-white mb-6 leading-tight">
                        Cluade<span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-400">X</span>
                    </h1>

                    <p class="text-xl text-gray-300 mb-3">
                        ผู้ช่วยเขียนโค้ด AI ระดับมืออาชีพ ทำงานบนเครื่องของคุณ
                    </p>
                    <p class="text-lg text-gray-400 mb-8">
                        Privacy-first AI coding assistant. 5 providers, 28 tools, 22+ local models. Thai/English bilingual.
                    </p>

                    {{-- Tags --}}
                    <div class="flex flex-wrap gap-2 mb-8">
                        <span class="px-3 py-1.5 bg-indigo-500/20 text-indigo-300 rounded-lg text-sm border border-indigo-500/20">🧠 Multi-Step Agent</span>
                        <span class="px-3 py-1.5 bg-cyan-500/20 text-cyan-300 rounded-lg text-sm border border-cyan-500/20">🔒 Zero Telemetry</span>
                        <span class="px-3 py-1.5 bg-green-500/20 text-green-300 rounded-lg text-sm border border-green-500/20">🆓 ฟรี</span>
                        <span class="px-3 py-1.5 bg-purple-500/20 text-purple-300 rounded-lg text-sm border border-purple-500/20">🇹🇭 ภาษาไทย</span>
                        <span class="px-3 py-1.5 bg-yellow-500/20 text-yellow-300 rounded-lg text-sm border border-yellow-500/20">⚡ GPU / CPU</span>
                    </div>

                    {{-- CTA --}}
                    <div class="flex flex-wrap gap-4">
                        <a href="https://github.com/xjanova/cluadeX/releases/latest"
                           class="px-8 py-4 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-green-500/25 flex items-center gap-2 text-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            ดาวน์โหลดฟรี
                        </a>
                        <a href="{{ route('cluadex.pricing') }}"
                           class="px-8 py-4 bg-white/10 hover:bg-white/20 text-white font-bold rounded-xl transition-all border border-white/20 flex items-center gap-2 text-lg backdrop-blur-sm">
                            ดูแพ็กเกจราคา
                        </a>
                    </div>

                    {{-- Trust badges --}}
                    <div class="flex items-center gap-6 mt-8 text-sm text-gray-500">
                        <span class="flex items-center gap-1"><svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg> ปลอดภัย</span>
                        <span class="flex items-center gap-1"><svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> Windows 10/11</span>
                        <span class="flex items-center gap-1"><svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> ติดตั้ง 30 วินาที</span>
                    </div>
                </div>

                {{-- Right: Preview --}}
                <div class="relative">
                    <div class="bg-gradient-to-br from-indigo-500/10 to-cyan-500/10 rounded-2xl p-4 backdrop-blur-sm border border-indigo-500/20">
                        @if($product && $product->image)
                            <img src="{{ Storage::url($product->image) }}" alt="CluadeX" class="w-full rounded-xl shadow-2xl">
                        @else
                            {{-- TODO: Replace with actual app screenshot --}}
                            <div class="aspect-video bg-gray-800/80 rounded-xl flex items-center justify-center border border-gray-700">
                                <div class="text-center p-8">
                                    <div class="text-7xl mb-4">🤖</div>
                                    <p class="text-2xl font-bold text-white">CluadeX</p>
                                    <p class="text-gray-400 mt-2">AI Coding Assistant</p>
                                    <p class="text-gray-500 text-sm mt-1">Catppuccin Mocha Dark Theme</p>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="absolute -top-3 -right-3 px-3 py-1.5 bg-gradient-to-r from-green-500 to-emerald-500 text-white font-bold rounded-lg shadow-lg text-sm transform rotate-3">FREE</div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mt-20 max-w-3xl mx-auto">
                <div class="text-center"><div class="text-4xl font-black text-white">5</div><div class="text-sm text-gray-400 mt-1">AI Providers</div></div>
                <div class="text-center"><div class="text-4xl font-black text-white">28</div><div class="text-sm text-gray-400 mt-1">Agent Tools</div></div>
                <div class="text-center"><div class="text-4xl font-black text-white">22+</div><div class="text-sm text-gray-400 mt-1">Local Models</div></div>
                <div class="text-center"><div class="text-4xl font-black text-white">0</div><div class="text-sm text-gray-400 mt-1">Telemetry</div></div>
            </div>
        </div>
    </section>

    {{-- ═══════ FEATURES ═══════ --}}
    <section class="py-20 bg-gray-900/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-indigo-400 font-semibold text-sm tracking-wider uppercase">FEATURES</span>
                <h2 class="text-4xl font-black text-white mt-3">ทำได้มากกว่า Chat ทั่วไป</h2>
                <p class="text-gray-400 mt-3 max-w-2xl mx-auto">Agent อัจฉริยะที่อ่าน เขียน แก้ไข ทดสอบโค้ดให้อัตโนมัติ พร้อม 28 เครื่องมือ</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                $features = [
                    ['icon' => '🧠', 'title' => 'Multi-Step Agent', 'desc' => 'วนลูป 15 รอบ: อ่าน → แก้ → ตรวจสอบ → ทดสอบ อัตโนมัติ ถ้าเจอ error แก้เองได้', 'color' => 'indigo'],
                    ['icon' => '💻', 'title' => 'Local GGUF + Cloud', 'desc' => 'รัน AI บนเครื่องด้วย GPU หรือเชื่อมต่อ OpenAI, Anthropic, Gemini, Ollama', 'color' => 'green'],
                    ['icon' => '🔒', 'title' => 'Privacy-First', 'desc' => 'Zero Telemetry, DPAPI Encryption, ไม่ส่งข้อมูลใดๆ กลับ โค้ดอยู่ในเครื่องคุณ', 'color' => 'red'],
                    ['icon' => '📊', 'title' => 'LED Context Bar', 'desc' => 'แถบ LED 5 สี เตือนเมื่อ context เต็ม แนะนำเริ่ม session ใหม่เพื่อคุณภาพดีที่สุด', 'color' => 'yellow'],
                    ['icon' => '🔍', 'title' => 'Code Review', 'desc' => 'รีวิวโค้ดด้วย AI ตรวจ bugs, security, improvements ให้คะแนน ⭐ 1-5 ดาว', 'color' => 'purple'],
                    ['icon' => '📋', 'title' => 'Plan Mode & TODO', 'desc' => 'วางแผนก่อนเขียน ติดตาม TODO อัตโนมัติ เหมือน Claude Code', 'color' => 'cyan'],
                    ['icon' => '🌿', 'title' => 'Git + GitHub', 'desc' => '12 คำสั่ง Git + 5 GitHub tools ครบ commit, PR, issues จากแชท', 'color' => 'emerald'],
                    ['icon' => '🧩', 'title' => 'Plugin System', 'desc' => '20+ ปลั๊กอินพร้อมใช้: lint, test, security scan, auto-docs, backup', 'color' => 'pink'],
                    ['icon' => '🌡️', 'title' => 'GPU Monitoring', 'desc' => 'แสดงอุณหภูมิ GPU, การใช้งาน %, VRAM ที่ใช้ แบบ real-time ทุก 5 วินาที', 'color' => 'orange'],
                    ['icon' => '🇹🇭', 'title' => 'ภาษาไทย/อังกฤษ', 'desc' => '170+ คำแปล System Prompt ไทย AI ตอบภาษาไทยได้อย่างเป็นธรรมชาติ', 'color' => 'blue'],
                    ['icon' => '🐾', 'title' => 'Buddy Companion', 'desc' => 'สัตว์เลี้ยง AI 18 สายพันธุ์ 5 ระดับหายาก 8 หมวก 1% shiny ลูบได้!', 'color' => 'amber'],
                    ['icon' => '🔄', 'title' => 'Auto-Update', 'desc' => 'ตรวจสอบอัพเดทอัตโนมัติจาก xman4289.com เวอร์ชันใหม่แจ้งเตือนทันที', 'color' => 'teal'],
                ];
                @endphp

                @foreach($features as $f)
                <div class="bg-gray-800/40 rounded-xl p-6 border border-gray-700/50 hover:border-{{ $f['color'] }}-500/40 transition-all duration-300 group hover:bg-gray-800/60">
                    <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">{{ $f['icon'] }}</div>
                    <h3 class="text-lg font-bold text-white mb-2">{{ $f['title'] }}</h3>
                    <p class="text-gray-400 text-sm leading-relaxed">{{ $f['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════ HOW IT WORKS ═══════ --}}
    <section class="py-20">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-cyan-400 font-semibold text-sm tracking-wider uppercase">HOW IT WORKS</span>
                <h2 class="text-4xl font-black text-white mt-3">เริ่มต้นใน 3 ขั้นตอน</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center text-white text-2xl font-black mx-auto mb-4 shadow-lg shadow-green-500/25">1</div>
                    <h3 class="text-lg font-bold text-white mb-2">ดาวน์โหลด</h3>
                    <p class="text-gray-400 text-sm">ดาวน์โหลดฟรีจาก GitHub<br>แตกไฟล์แล้วเปิด CluadeX.exe</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-2xl flex items-center justify-center text-white text-2xl font-black mx-auto mb-4 shadow-lg shadow-indigo-500/25">2</div>
                    <h3 class="text-lg font-bold text-white mb-2">เลือกโมเดล</h3>
                    <p class="text-gray-400 text-sm">โหลดโมเดล GGUF จาก HuggingFace<br>หรือเชื่อม API Key ของ Cloud</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-2xl flex items-center justify-center text-white text-2xl font-black mx-auto mb-4 shadow-lg shadow-cyan-500/25">3</div>
                    <h3 class="text-lg font-bold text-white mb-2">เขียนโค้ด!</h3>
                    <p class="text-gray-400 text-sm">เปิดโฟลเดอร์โปรเจกต์<br>Agent ช่วยเขียน แก้ ทดสอบ ครบ</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════ SUPPORTED MODELS ═══════ --}}
    <section class="py-20 bg-gray-900/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <span class="text-yellow-400 font-semibold text-sm tracking-wider uppercase">AI MODELS</span>
                <h2 class="text-4xl font-black text-white mt-3">รองรับ 22+ โมเดล AI</h2>
                <p class="text-gray-400 mt-3">โมเดลคัดสรรพร้อม ⭐ Star Rating ตามคุณภาพ</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @php
                $models = [
                    ['name' => 'Gemma 4 31B', 'stars' => 5, 'badge' => 'NEW', 'size' => '20GB VRAM', 'use' => 'ทั่วไป, Vision, Reasoning'],
                    ['name' => 'Qwen2.5 Coder 32B', 'stars' => 5, 'badge' => 'TOP', 'size' => '20GB VRAM', 'use' => 'เขียนโค้ดระดับสูง'],
                    ['name' => 'DeepSeek R1 14B', 'stars' => 5, 'badge' => '', 'size' => '10GB VRAM', 'use' => 'Reasoning, แก้ปัญหา'],
                    ['name' => 'Qwen2.5 Coder 14B', 'stars' => 5, 'badge' => '', 'size' => '10GB VRAM', 'use' => 'เขียนโค้ดคุณภาพ'],
                    ['name' => 'Gemma 4 26B MoE', 'stars' => 4, 'badge' => 'NEW', 'size' => '16GB VRAM', 'use' => '256K context, เร็ว'],
                    ['name' => 'Qwen2.5 Coder 7B', 'stars' => 4, 'badge' => '', 'size' => '6GB VRAM', 'use' => 'เขียนโค้ดยอดนิยม'],
                    ['name' => 'DeepSeek R1 7B', 'stars' => 4, 'badge' => '', 'size' => '6GB VRAM', 'use' => 'Reasoning แบบกระทัดรัด'],
                    ['name' => 'Llama 3.1 8B', 'stars' => 4, 'badge' => '', 'size' => '6GB VRAM', 'use' => 'ทั่วไป + โค้ด'],
                    ['name' => 'Gemma 3 12B', 'stars' => 4, 'badge' => '', 'size' => '9GB VRAM', 'use' => 'ทั่วไป, Multimodal'],
                    ['name' => 'Phi 3.5 Mini', 'stars' => 3, 'badge' => '', 'size' => '4GB VRAM', 'use' => 'เครื่องเบาๆ'],
                    ['name' => 'Llama 3.2 3B', 'stars' => 3, 'badge' => '', 'size' => '4GB VRAM', 'use' => 'CPU mode ได้'],
                    ['name' => 'Qwen2.5 Coder 1.5B', 'stars' => 2, 'badge' => '', 'size' => '2GB VRAM', 'use' => 'เครื่องเก่าก็รันได้'],
                ];
                @endphp

                @foreach($models as $m)
                <div class="bg-gray-800/40 rounded-lg p-4 border border-gray-700/50 hover:border-indigo-500/30 transition-all">
                    <div class="flex items-start justify-between mb-2">
                        <span class="text-white font-bold text-sm">{{ $m['name'] }}</span>
                        @if($m['badge'])
                            <span class="px-2 py-0.5 bg-indigo-500/30 text-indigo-300 text-[10px] font-bold rounded">{{ $m['badge'] }}</span>
                        @endif
                    </div>
                    <div class="text-yellow-400 text-xs mb-1">{!! str_repeat('⭐', $m['stars']) !!}{!! str_repeat('☆', 5 - $m['stars']) !!}</div>
                    <div class="text-gray-500 text-xs">{{ $m['size'] }}</div>
                    <div class="text-gray-400 text-xs mt-1">{{ $m['use'] }}</div>
                </div>
                @endforeach
            </div>
            <p class="text-center text-gray-500 text-sm mt-6">+ Cloud: GPT-4o · Claude 4 Opus · Gemini 2.5 Pro · Ollama และอื่นๆ</p>
        </div>
    </section>

    {{-- ═══════ FREE VS PRO ═══════ --}}
    <section class="py-20">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-green-400 font-semibold text-sm tracking-wider uppercase">PRICING</span>
                <h2 class="text-4xl font-black text-white mt-3">ฟรี vs Pro</h2>
                <p class="text-gray-400 mt-3">ดาวน์โหลดฟรี ใช้ฟรี อัพเกรดเมื่อพร้อม</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                {{-- Free --}}
                <div class="bg-gray-800/40 rounded-2xl p-8 border border-gray-700/50">
                    <div class="text-center mb-6">
                        <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs font-bold">FREE</span>
                        <h3 class="text-xl font-bold text-white mt-3">ฟรีตลอดชีพ</h3>
                        <div class="text-4xl font-black text-green-400 mt-2">฿0</div>
                    </div>
                    <ul class="space-y-2.5 text-gray-300 text-sm">
                        <li class="flex items-start gap-2"><span class="text-green-400 mt-0.5">✓</span> Local GGUF Inference (22+ models)</li>
                        <li class="flex items-start gap-2"><span class="text-green-400 mt-0.5">✓</span> Ollama Integration</li>
                        <li class="flex items-start gap-2"><span class="text-green-400 mt-0.5">✓</span> Chat Persistence (SQLite + FTS5)</li>
                        <li class="flex items-start gap-2"><span class="text-green-400 mt-0.5">✓</span> Markdown + Syntax Highlighting</li>
                        <li class="flex items-start gap-2"><span class="text-green-400 mt-0.5">✓</span> GPU Auto-Detection + Monitoring</li>
                        <li class="flex items-start gap-2"><span class="text-green-400 mt-0.5">✓</span> Code Execution (Sandbox)</li>
                        <li class="flex items-start gap-2"><span class="text-green-400 mt-0.5">✓</span> File System Tools</li>
                        <li class="flex items-start gap-2"><span class="text-green-400 mt-0.5">✓</span> Buddy Companion (18 species)</li>
                        <li class="flex items-start gap-2"><span class="text-green-400 mt-0.5">✓</span> Thai/English (170+ translations)</li>
                        <li class="flex items-start gap-2"><span class="text-green-400 mt-0.5">✓</span> DPAPI Encryption + Zero Telemetry</li>
                        <li class="flex items-start gap-2 text-gray-500"><span class="mt-0.5">✗</span> Cloud AI Providers</li>
                        <li class="flex items-start gap-2 text-gray-500"><span class="mt-0.5">✗</span> Git/GitHub Integration</li>
                    </ul>
                    <a href="https://github.com/xjanova/cluadeX/releases/latest" class="block w-full text-center mt-8 px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl transition-all">ดาวน์โหลดฟรี</a>
                </div>

                {{-- Pro --}}
                <div class="bg-gradient-to-br from-indigo-900/40 to-purple-900/40 rounded-2xl p-8 border border-indigo-500/40 relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-xs font-bold rounded-full shadow-lg">แนะนำ</div>
                    <div class="text-center mb-6">
                        <span class="px-3 py-1 bg-indigo-500/20 text-indigo-400 rounded-full text-xs font-bold">PRO</span>
                        <h3 class="text-xl font-bold text-white mt-3">ปลดล็อกทั้งหมด</h3>
                        <div class="text-4xl font-black text-indigo-400 mt-2">฿199<span class="text-base text-gray-400">/เดือน</span></div>
                    </div>
                    <ul class="space-y-2.5 text-gray-300 text-sm">
                        <li class="flex items-start gap-2"><span class="text-indigo-400 mt-0.5">✓</span> <strong>ทุกอย่างใน Free</strong> +</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-400 mt-0.5">✓</span> OpenAI (GPT-4o, o1, o3)</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-400 mt-0.5">✓</span> Anthropic (Claude 4 Opus/Sonnet)</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-400 mt-0.5">✓</span> Google Gemini (2.5 Pro/Flash)</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-400 mt-0.5">✓</span> Git Integration (12 commands)</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-400 mt-0.5">✓</span> GitHub Integration (PR, Issues)</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-400 mt-0.5">✓</span> Plugin System (20+ plugins)</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-400 mt-0.5">✓</span> Web Fetch + Context Memory</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-400 mt-0.5">✓</span> AI Code Review + Smart Editing</li>
                        <li class="flex items-start gap-2"><span class="text-indigo-400 mt-0.5">✓</span> Auto-Update + Priority Support</li>
                    </ul>
                    <a href="{{ route('cluadex.pricing') }}" class="block w-full text-center mt-8 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-indigo-500/25">เลือกแพ็กเกจ Pro</a>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════ SCREENSHOTS ═══════ --}}
    <section class="py-20 bg-gray-900/60">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <span class="text-purple-400 font-semibold text-sm tracking-wider uppercase">SCREENSHOTS</span>
                <h2 class="text-4xl font-black text-white mt-3">หน้าจอโปรแกรม</h2>
                <p class="text-gray-400 mt-3">Catppuccin Mocha Dark Theme — สวยงาม สบายตา</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                $screenshots = [
                    ['title' => 'Chat View', 'desc' => 'แชทกับ AI พร้อม LED Context Bar และ Per-Turn Stats'],
                    ['title' => 'Model Manager', 'desc' => 'ดาวน์โหลดโมเดล GGUF จาก HuggingFace พร้อม Star Rating'],
                    ['title' => 'Plugin Catalog', 'desc' => '20+ ปลั๊กอินพร้อมติดตั้งด้วยคลิกเดียว'],
                    ['title' => 'Features Page', 'desc' => '27 ฟีเจอร์ เปิด/ปิดได้ พร้อม Activation Key'],
                    ['title' => 'Agent Tools', 'desc' => '28 เครื่องมือ: File, Git, GitHub, Web Fetch'],
                    ['title' => 'Buddy Companion', 'desc' => 'สัตว์เลี้ยง AI 18 สายพันธุ์ ลูบได้!'],
                ];
                @endphp
                @foreach($screenshots as $ss)
                <div class="bg-gray-800/40 rounded-xl overflow-hidden border border-gray-700/50 hover:border-purple-500/30 transition-all group">
                    {{-- TODO: Replace with <img src="..." alt="{{ $ss['title'] }}" class="w-full aspect-video object-cover"> --}}
                    <div class="aspect-video bg-gray-700/30 flex items-center justify-center">
                        <div class="text-center text-gray-500 group-hover:text-gray-400 transition-colors">
                            <div class="text-3xl mb-2">📸</div>
                            <p class="text-sm font-medium">{{ $ss['title'] }}</p>
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

    {{-- ═══════ SYSTEM REQUIREMENTS ═══════ --}}
    <section class="py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-black text-white">System Requirements</h2>
            </div>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-gray-800/40 rounded-xl p-6 border border-gray-700/50">
                    <h3 class="text-lg font-bold text-green-400 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        ขั้นต่ำ
                    </h3>
                    <ul class="space-y-2 text-gray-300 text-sm">
                        <li><strong class="text-gray-200">OS:</strong> Windows 10/11 (x64)</li>
                        <li><strong class="text-gray-200">RAM:</strong> 8 GB</li>
                        <li><strong class="text-gray-200">Disk:</strong> 500 MB + model size</li>
                        <li><strong class="text-gray-200">GPU:</strong> ไม่จำเป็น (CPU mode ได้)</li>
                    </ul>
                </div>
                <div class="bg-gray-800/40 rounded-xl p-6 border border-indigo-500/30">
                    <h3 class="text-lg font-bold text-indigo-400 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        แนะนำ
                    </h3>
                    <ul class="space-y-2 text-gray-300 text-sm">
                        <li><strong class="text-gray-200">OS:</strong> Windows 11 (x64)</li>
                        <li><strong class="text-gray-200">RAM:</strong> 16+ GB</li>
                        <li><strong class="text-gray-200">Disk:</strong> SSD 50+ GB</li>
                        <li><strong class="text-gray-200">GPU:</strong> NVIDIA RTX 3060+ (8GB VRAM, CUDA 12)</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════ FINAL CTA ═══════ --}}
    <section class="py-24 bg-gradient-to-b from-gray-900/60 to-gray-900">
        <div class="max-w-3xl mx-auto px-4 text-center">
            <h2 class="text-4xl md:text-5xl font-black text-white mb-4">พร้อมเขียนโค้ดกับ AI?</h2>
            <p class="text-xl text-gray-400 mb-8">ดาวน์โหลดฟรี ไม่ต้องสมัคร ไม่มีค่าใช้จ่าย</p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="https://github.com/xjanova/cluadeX/releases/latest"
                   class="px-10 py-5 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-black text-lg rounded-2xl transition-all transform hover:scale-105 shadow-2xl shadow-green-500/25 flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    ดาวน์โหลด CluadeX ฟรี
                </a>
                <a href="{{ route('cluadex.pricing') }}"
                   class="px-10 py-5 bg-white/10 hover:bg-white/20 text-white font-bold text-lg rounded-2xl transition-all border border-white/20 backdrop-blur-sm">
                    ดูแพ็กเกจ Pro
                </a>
            </div>
            <p class="text-gray-500 text-sm mt-6">Windows 10/11 · .NET 8 · No sign-up required · MIT License</p>
        </div>
    </section>

</div>
@endsection
