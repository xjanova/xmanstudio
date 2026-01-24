@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตั้งค่า AI')
@section('page-title', 'ตั้งค่า AI')

@push('styles')
<style>
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Premium Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-500 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-violet-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

        <div class="relative">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-white">ตั้งค่า AI</h1>
                    </div>
                    <p class="text-purple-100 text-lg">กำหนดค่า AI Provider และพารามิเตอร์สำหรับระบบ AI</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-xl shadow-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl shadow-lg">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.ai-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- AI Provider Selection -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-500 to-violet-600 flex items-center justify-center mr-3 shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                เลือก AI Provider
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- OpenAI -->
                <label class="provider-card relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all {{ $settings['ai_provider'] === 'openai' ? 'border-green-500 bg-green-50 dark:bg-green-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500' }}">
                    <input type="radio" name="ai_provider" value="openai" {{ $settings['ai_provider'] === 'openai' ? 'checked' : '' }} class="sr-only" onchange="updateProviderSelection(this)">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-black rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white font-bold text-lg">AI</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">OpenAI (GPT)</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">GPT-4o, GPT-4o-mini</p>
                        </div>
                    </div>
                    <div class="provider-check absolute top-2 right-2 {{ $settings['ai_provider'] === 'openai' ? '' : 'hidden' }}">
                        <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </label>

                <!-- Claude -->
                <label class="provider-card relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all {{ $settings['ai_provider'] === 'claude' ? 'border-green-500 bg-green-50 dark:bg-green-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500' }}">
                    <input type="radio" name="ai_provider" value="claude" {{ $settings['ai_provider'] === 'claude' ? 'checked' : '' }} class="sr-only" onchange="updateProviderSelection(this)">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center mr-4">
                            <span class="text-white font-bold text-lg">C</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">Anthropic (Claude)</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Claude 3.5 Sonnet, Haiku</p>
                        </div>
                    </div>
                    <div class="provider-check absolute top-2 right-2 {{ $settings['ai_provider'] === 'claude' ? '' : 'hidden' }}">
                        <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </label>

                <!-- Ollama (Local) -->
                <label class="provider-card relative flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all {{ $settings['ai_provider'] === 'ollama' ? 'border-green-500 bg-green-50 dark:bg-green-900/30' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500' }}">
                    <input type="radio" name="ai_provider" value="ollama" {{ $settings['ai_provider'] === 'ollama' ? 'checked' : '' }} class="sr-only" onchange="updateProviderSelection(this)">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">Ollama (Local)</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">LLaMA, Mistral, Qwen</p>
                        </div>
                    </div>
                    <div class="provider-check absolute top-2 right-2 {{ $settings['ai_provider'] === 'ollama' ? '' : 'hidden' }}">
                        <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </label>
            </div>

            <div class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <strong>Ollama:</strong> รัน AI ในเครื่องของคุณเอง ไม่ต้องเสียค่า API ไม่ต้องส่งข้อมูลออกไปข้างนอก
                    <a href="https://ollama.com" target="_blank" class="underline hover:text-blue-600 dark:hover:text-blue-300">ดาวน์โหลด Ollama</a>
                </p>
            </div>
        </div>

        <!-- OpenAI Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 bg-black rounded-xl flex items-center justify-center mr-3">
                    <span class="text-white font-bold text-sm">AI</span>
                </div>
                OpenAI Settings
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">เปิดใช้งาน OpenAI</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">เปิดใช้งาน OpenAI API สำหรับฟีเจอร์ AI</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="openai_enabled" value="1" {{ $settings['openai_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-14 h-7 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-green-500 peer-checked:to-emerald-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">API Key</label>
                    <input type="password" name="openai_api_key" value="{{ $settings['openai_api_key'] }}" placeholder="sk-..."
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">รับ API Key ได้ที่ <a href="https://platform.openai.com/api-keys" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline">platform.openai.com</a></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                    <select name="openai_model" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                        <option value="gpt-4o" {{ $settings['openai_model'] === 'gpt-4o' ? 'selected' : '' }}>GPT-4o (แนะนำ)</option>
                        <option value="gpt-4o-mini" {{ $settings['openai_model'] === 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini (ประหยัด)</option>
                        <option value="gpt-4-turbo" {{ $settings['openai_model'] === 'gpt-4-turbo' ? 'selected' : '' }}>GPT-4 Turbo</option>
                        <option value="gpt-3.5-turbo" {{ $settings['openai_model'] === 'gpt-3.5-turbo' ? 'selected' : '' }}>GPT-3.5 Turbo</option>
                        <option value="o1-preview" {{ $settings['openai_model'] === 'o1-preview' ? 'selected' : '' }}>o1-preview (Reasoning)</option>
                        <option value="o1-mini" {{ $settings['openai_model'] === 'o1-mini' ? 'selected' : '' }}>o1-mini (Reasoning ประหยัด)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Claude Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center mr-3">
                    <span class="text-white font-bold text-sm">C</span>
                </div>
                Claude (Anthropic) Settings
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">เปิดใช้งาน Claude</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">เปิดใช้งาน Anthropic Claude API สำหรับฟีเจอร์ AI</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="claude_enabled" value="1" {{ $settings['claude_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-14 h-7 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-orange-400 peer-checked:to-orange-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">API Key</label>
                    <input type="password" name="claude_api_key" value="{{ $settings['claude_api_key'] }}" placeholder="sk-ant-..."
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">รับ API Key ได้ที่ <a href="https://console.anthropic.com/" target="_blank" class="text-purple-600 dark:text-purple-400 hover:underline">console.anthropic.com</a></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                    <select name="claude_model" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                        <option value="claude-sonnet-4-20250514" {{ $settings['claude_model'] === 'claude-sonnet-4-20250514' ? 'selected' : '' }}>Claude Sonnet 4 (ใหม่ล่าสุด)</option>
                        <option value="claude-3-5-sonnet-20241022" {{ $settings['claude_model'] === 'claude-3-5-sonnet-20241022' ? 'selected' : '' }}>Claude 3.5 Sonnet (แนะนำ)</option>
                        <option value="claude-3-haiku-20240307" {{ $settings['claude_model'] === 'claude-3-haiku-20240307' ? 'selected' : '' }}>Claude 3 Haiku (ประหยัด)</option>
                        <option value="claude-3-opus-20240229" {{ $settings['claude_model'] === 'claude-3-opus-20240229' ? 'selected' : '' }}>Claude 3 Opus (พรีเมียม)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Ollama Settings (Local AI) -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                </div>
                Ollama Settings (Local AI)
                <span class="ml-2 px-2.5 py-1 text-xs bg-gradient-to-r from-green-400 to-emerald-500 text-white rounded-full shadow">ไม่เสียค่าใช้จ่าย</span>
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">เปิดใช้งาน Ollama</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">รัน AI ในเครื่อง ข้อมูลไม่ออกนอกเครือข่าย</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ollama_enabled" value="1" {{ $settings['ollama_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-14 h-7 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-purple-600"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ollama Host URL</label>
                    <input type="text" name="ollama_host" value="{{ $settings['ollama_host'] }}" placeholder="http://localhost:11434"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">URL ของ Ollama server (default: http://localhost:11434)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                    <div class="flex gap-2">
                        <input type="text" name="ollama_model" id="ollama_model" value="{{ $settings['ollama_model'] }}" placeholder="llama3.2"
                               class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                        <button type="button" onclick="refreshOllamaModels()" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition-colors">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    </div>
                    <div id="ollama-models-list" class="mt-2 hidden">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">โมเดลที่พบ:</p>
                        <div class="flex flex-wrap gap-2" id="ollama-models-buttons"></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">โมเดลยอดนิยม: llama3.2, mistral, qwen2.5, gemma2, phi3</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Keep Alive</label>
                    <select name="ollama_keep_alive" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                        <option value="5m" {{ $settings['ollama_keep_alive'] === '5m' ? 'selected' : '' }}>5 นาที</option>
                        <option value="15m" {{ $settings['ollama_keep_alive'] === '15m' ? 'selected' : '' }}>15 นาที</option>
                        <option value="30m" {{ $settings['ollama_keep_alive'] === '30m' ? 'selected' : '' }}>30 นาที</option>
                        <option value="1h" {{ $settings['ollama_keep_alive'] === '1h' ? 'selected' : '' }}>1 ชั่วโมง</option>
                        <option value="-1" {{ $settings['ollama_keep_alive'] === '-1' ? 'selected' : '' }}>ตลอดเวลา (ใช้ RAM มาก)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ระยะเวลาเก็บโมเดลไว้ใน memory</p>
                </div>
            </div>
        </div>

        <!-- General AI Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-gray-500 to-gray-700 flex items-center justify-center mr-3 shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                ตั้งค่าการสร้างคำตอบ
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Tokens</label>
                    <input type="number" name="ai_max_tokens" value="{{ $settings['ai_max_tokens'] }}" min="100" max="32000"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ความยาวคำตอบสูงสุด (100-32000)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Temperature</label>
                    <input type="number" name="ai_temperature" value="{{ $settings['ai_temperature'] }}" min="0" max="2" step="0.1"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">0=แม่นยำ, 2=สร้างสรรค์</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Top P</label>
                    <input type="number" name="ai_top_p" value="{{ $settings['ai_top_p'] }}" min="0" max="1" step="0.1"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Nucleus sampling (0-1)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Frequency Penalty</label>
                    <input type="number" name="ai_frequency_penalty" value="{{ $settings['ai_frequency_penalty'] }}" min="-2" max="2" step="0.1"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ลดการซ้ำคำ (-2 ถึง 2)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Presence Penalty</label>
                    <input type="number" name="ai_presence_penalty" value="{{ $settings['ai_presence_penalty'] }}" min="-2" max="2" step="0.1"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เพิ่มความหลากหลาย (-2 ถึง 2)</p>
                </div>
            </div>
        </div>

        <!-- AI Bot Behavior -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mr-3 shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                พฤติกรรม AI Bot
            </h3>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ Bot</label>
                        <input type="text" name="ai_bot_name" value="{{ $settings['ai_bot_name'] }}" placeholder="AI Assistant"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ภาษาในการตอบ</label>
                        <select name="ai_response_language" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                            <option value="th" {{ $settings['ai_response_language'] === 'th' ? 'selected' : '' }}>ภาษาไทย</option>
                            <option value="en" {{ $settings['ai_response_language'] === 'en' ? 'selected' : '' }}>English</option>
                            <option value="auto" {{ $settings['ai_response_language'] === 'auto' ? 'selected' : '' }}>ตามภาษาที่ถาม</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สไตล์การตอบ</label>
                        <select name="ai_response_style" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                            <option value="professional" {{ $settings['ai_response_style'] === 'professional' ? 'selected' : '' }}>มืออาชีพ</option>
                            <option value="friendly" {{ $settings['ai_response_style'] === 'friendly' ? 'selected' : '' }}>เป็นมิตร</option>
                            <option value="casual" {{ $settings['ai_response_style'] === 'casual' ? 'selected' : '' }}>ผ่อนคลาย</option>
                            <option value="formal" {{ $settings['ai_response_style'] === 'formal' ? 'selected' : '' }}>ทางการ</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความยาวคำตอบ</label>
                        <select name="ai_response_length" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                            <option value="short" {{ $settings['ai_response_length'] === 'short' ? 'selected' : '' }}>สั้น กระชับ</option>
                            <option value="medium" {{ $settings['ai_response_length'] === 'medium' ? 'selected' : '' }}>ปานกลาง</option>
                            <option value="long" {{ $settings['ai_response_length'] === 'long' ? 'selected' : '' }}>ยาว ละเอียด</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">System Prompt (คำสั่งพื้นฐาน)</label>
                    <textarea name="ai_system_prompt" rows="5" placeholder="เช่น: คุณเป็นผู้ช่วยของร้าน XmanStudio ที่ช่วยตอบคำถามเกี่ยวกับสินค้าและบริการ..."
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">{{ $settings['ai_system_prompt'] }}</textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">กำหนดบุคลิกและขอบเขตการตอบของ AI (ถ้าว่างจะใช้ค่าเริ่มต้น)</p>
                </div>
            </div>
        </div>

        <!-- Knowledge & Context Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center mr-3 shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                ขอบเขตความรู้ของ AI
            </h3>

            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">เลือกข้อมูลที่ AI สามารถเข้าถึงได้เพื่อตอบคำถาม</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">ข้อมูลสินค้า</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ชื่อ ราคา รายละเอียดสินค้า</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_use_product_data" value="1" {{ $settings['ai_use_product_data'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 dark:peer-focus:ring-yellow-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-yellow-400 peer-checked:to-amber-500"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">ข้อมูลบริการ</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">รายละเอียดบริการต่างๆ</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_use_service_data" value="1" {{ $settings['ai_use_service_data'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 dark:peer-focus:ring-yellow-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-yellow-400 peer-checked:to-amber-500"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">FAQ / คำถามที่พบบ่อย</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">คำตอบที่เตรียมไว้แล้ว</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_use_faq_data" value="1" {{ $settings['ai_use_faq_data'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 dark:peer-focus:ring-yellow-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-yellow-400 peer-checked:to-amber-500"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">ข้อมูลบริษัท</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ที่อยู่ เบอร์โทร ช่องทางติดต่อ</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_use_company_info" value="1" {{ $settings['ai_use_company_info'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 dark:peer-focus:ring-yellow-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-yellow-400 peer-checked:to-amber-500"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">ประวัติการสั่งซื้อ</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">เฉพาะของลูกค้าที่ถาม</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_use_order_history" value="1" {{ $settings['ai_use_order_history'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 dark:peer-focus:ring-yellow-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-yellow-400 peer-checked:to-amber-500"></div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ความรู้เพิ่มเติม (Custom Knowledge Base)</label>
                <textarea name="ai_custom_knowledge" rows="6" placeholder="ใส่ข้อมูลเพิ่มเติมที่ต้องการให้ AI รู้ เช่น:
- นโยบายการคืนสินค้า: สินค้าทุกชิ้นคืนได้ภายใน 7 วัน
- เวลาทำการ: จันทร์-ศุกร์ 9:00-18:00
- โปรโมชั่นปัจจุบัน: ลด 20% ทุกสินค้า"
                          class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all font-mono text-sm">{{ $settings['ai_custom_knowledge'] }}</textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ข้อมูลที่ใส่ที่นี่จะถูกนำไปใช้ในทุกการตอบคำถาม</p>
            </div>
        </div>

        <!-- Response Restrictions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center mr-3 shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                ข้อจำกัดการตอบ
            </h3>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หัวข้อที่อนุญาต</label>
                        <textarea name="ai_allowed_topics" rows="3" placeholder="สินค้า, บริการ, ราคา, การสั่งซื้อ, การจัดส่ง"
                                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">{{ $settings['ai_allowed_topics'] }}</textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">คั่นด้วยเครื่องหมายคอมมา (ว่างไว้ = ตอบทุกเรื่อง)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">หัวข้อที่ห้ามตอบ</label>
                        <textarea name="ai_forbidden_topics" rows="3" placeholder="การเมือง, ศาสนา, ความรุนแรง, คู่แข่ง"
                                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">{{ $settings['ai_forbidden_topics'] }}</textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">หัวข้อที่ AI จะปฏิเสธตอบ</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ข้อความเมื่อตอบไม่ได้</label>
                    <input type="text" name="ai_fallback_message" value="{{ $settings['ai_fallback_message'] }}"
                           placeholder="ขออภัย ฉันไม่สามารถตอบคำถามนี้ได้ กรุณาติดต่อทีมงานโดยตรง"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                </div>

                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">ส่งต่อให้พนักงาน</label>
                        <p class="text-sm text-gray-500 dark:text-gray-400">เมื่อเจอคำสำคัญ จะแจ้งให้พนักงานเข้ามาช่วย</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_require_human_handoff" value="1" {{ $settings['ai_require_human_handoff'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-yellow-300 dark:peer-focus:ring-yellow-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-yellow-400 peer-checked:to-amber-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำสำคัญส่งต่อพนักงาน</label>
                    <input type="text" name="ai_handoff_keywords" value="{{ $settings['ai_handoff_keywords'] }}"
                           placeholder="ติดต่อพนักงาน, คุยกับคน, ต้องการความช่วยเหลือ, มีปัญหา"
                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">คั่นด้วยเครื่องหมายคอมมา</p>
                </div>
            </div>
        </div>

        <!-- Feature Toggles -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center mr-3 shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                เปิด/ปิดฟีเจอร์ AI
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">AI Chat</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">แชทบอทช่วยลูกค้า</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_chat_enabled" value="1" {{ $settings['ai_chat_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-cyan-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">สร้างเนื้อหา</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">AI เขียนบทความ/คำอธิบาย</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_content_generation" value="1" {{ $settings['ai_content_generation'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-cyan-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">Code Assistant</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">AI ช่วยเขียนโค้ด</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_code_assistant" value="1" {{ $settings['ai_code_assistant'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-cyan-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">ตอบ Line อัตโนมัติ</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">AI ตอบข้อความ Line</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_auto_reply_line" value="1" {{ $settings['ai_auto_reply_line'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-green-500 peer-checked:to-emerald-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">แปลภาษาอัตโนมัติ</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">แปลข้อความเข้า-ออก</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_auto_translate" value="1" {{ $settings['ai_auto_translate'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-cyan-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div>
                        <label class="font-medium text-gray-900 dark:text-white">วิเคราะห์อารมณ์</label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ตรวจจับความพึงพอใจ</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_sentiment_analysis" value="1" {{ $settings['ai_sentiment_analysis'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-cyan-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <div class="flex gap-2 flex-wrap">
                <button type="button" onclick="testConnection('openai')" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-all text-sm text-gray-700 dark:text-gray-200">
                    <span class="flex items-center">
                        <div class="w-5 h-5 bg-black rounded mr-2 flex items-center justify-center">
                            <span class="text-white text-xs font-bold">AI</span>
                        </div>
                        ทดสอบ OpenAI
                    </span>
                </button>
                <button type="button" onclick="testConnection('claude')" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-all text-sm text-gray-700 dark:text-gray-200">
                    <span class="flex items-center">
                        <div class="w-5 h-5 bg-orange-500 rounded mr-2 flex items-center justify-center">
                            <span class="text-white text-xs font-bold">C</span>
                        </div>
                        ทดสอบ Claude
                    </span>
                </button>
                <button type="button" onclick="testConnection('ollama')" class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 font-medium transition-all text-sm text-gray-700 dark:text-gray-200">
                    <span class="flex items-center">
                        <div class="w-5 h-5 bg-gradient-to-r from-blue-500 to-purple-600 rounded mr-2"></div>
                        ทดสอบ Ollama
                    </span>
                </button>
            </div>

            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-600 to-violet-600 text-white rounded-xl hover:from-purple-700 hover:to-violet-700 font-medium transition-all shadow-lg">
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

<script>
function updateProviderSelection(input) {
    document.querySelectorAll('.provider-card').forEach(card => {
        card.classList.remove('border-green-500', 'bg-green-50', 'dark:bg-green-900/30');
        card.classList.add('border-gray-200', 'dark:border-gray-600');
        card.querySelector('.provider-check').classList.add('hidden');
    });

    const card = input.closest('.provider-card');
    card.classList.remove('border-gray-200', 'dark:border-gray-600');
    card.classList.add('border-green-500', 'bg-green-50', 'dark:bg-green-900/30');
    card.querySelector('.provider-check').classList.remove('hidden');
}

function testConnection(provider) {
    const btn = event.target.closest('button');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>กำลังทดสอบ...';
    btn.disabled = true;

    fetch('{{ route("admin.ai-settings.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ provider: provider })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('สำเร็จ: ' + data.message);
            if (data.models && provider === 'ollama') {
                showOllamaModels(data.models);
            }
        } else {
            alert('ผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        alert('เกิดข้อผิดพลาด: ' + error.message);
    })
    .finally(() => {
        btn.innerHTML = originalContent;
        btn.disabled = false;
    });
}

function refreshOllamaModels() {
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

    testConnection('ollama');

    setTimeout(() => {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
    }, 2000);
}

function showOllamaModels(models) {
    const container = document.getElementById('ollama-models-list');
    const buttons = document.getElementById('ollama-models-buttons');

    if (models.length > 0) {
        buttons.innerHTML = models.map(model =>
            `<button type="button" onclick="selectOllamaModel('${model}')" class="px-3 py-1 text-sm bg-gradient-to-r from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 text-blue-800 dark:text-blue-200 rounded-full hover:from-blue-200 hover:to-purple-200 dark:hover:from-blue-800/30 dark:hover:to-purple-800/30 transition-colors">${model}</button>`
        ).join('');
        container.classList.remove('hidden');
    }
}

function selectOllamaModel(model) {
    document.getElementById('ollama_model').value = model;
}
</script>
@endsection
