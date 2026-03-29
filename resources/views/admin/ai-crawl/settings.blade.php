@extends($adminLayout ?? 'layouts.admin')

@section('title', 'AI Crawl Settings')
@section('page-title', 'AI Crawl Control - Settings')

@push('styles')
<style>
    .animate-blob { animation: blob 7s infinite; }
    .animation-delay-2000 { animation-delay: 2s; }
    .animation-delay-4000 { animation-delay: 4s; }
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
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-700 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-cyan-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-indigo-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-white">AI Crawl Settings</h1>
                    </div>
                    <p class="text-cyan-100 text-lg">ตั้งค่าการควบคุม AI Bots, llms.txt และ Blocked Paths</p>
                </div>
                <div class="hidden md:block">
                    <a href="{{ route('admin.ai-crawl.index') }}" class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                        Back to Analytics
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-2xl p-4 flex items-center space-x-3">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-green-700 dark:text-green-300">{{ session('success') }}</span>
        </div>
    @endif

    <form action="{{ route('admin.ai-crawl.update-settings') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- General Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">General Settings</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เปิด/ปิดระบบ AI Crawl Control</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Enabled -->
                <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">AI Crawl Control</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">เปิดใช้ระบบตรวจจับและควบคุม AI bots</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="enabled" value="1" class="sr-only peer" {{ $setting->enabled ? 'checked' : '' }}>
                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-indigo-500"></div>
                    </label>
                </div>

                <!-- Logging -->
                <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">Activity Logging</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">บันทึก log ทุกครั้งที่ AI bot เข้ามา</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="logging_enabled" value="1" class="sr-only peer" {{ $setting->logging_enabled ? 'checked' : '' }}>
                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-indigo-500"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Category Controls -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Category Rules</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ควบคุม AI bots ตามประเภท</p>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Block Training Bots -->
                <div class="flex items-center justify-between p-4 rounded-xl bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">Block Training Bots</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">บล็อก bots ที่ scrape ข้อมูลไป train AI (GPTBot, Google-Extended, CCBot, etc.)</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="block_training_bots" value="1" class="sr-only peer" {{ $setting->block_training_bots ? 'checked' : '' }}>
                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-red-500 peer-checked:to-red-600"></div>
                    </label>
                </div>

                <!-- Allow Assistant Bots -->
                <div class="flex items-center justify-between p-4 rounded-xl bg-green-50 dark:bg-green-900/10 border border-green-100 dark:border-green-900/30">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">Allow Assistant Bots</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">อนุญาตให้ AI assistants เข้าถึง (ChatGPT-User, ClaudeBot, PerplexityBot)</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="allow_assistant_bots" value="1" class="sr-only peer" {{ $setting->allow_assistant_bots ? 'checked' : '' }}>
                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-green-500 peer-checked:to-green-600"></div>
                    </label>
                </div>

                <!-- Allow Search Bots -->
                <div class="flex items-center justify-between p-4 rounded-xl bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-900/30">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">Allow Search Bots</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">อนุญาต AI search bots (Googlebot, Bingbot)</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="allow_search_bots" value="1" class="sr-only peer" {{ $setting->allow_search_bots ? 'checked' : '' }}>
                        <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Per-Bot Controls -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Per-Bot Control</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ควบคุมแต่ละ bot แบบเจาะจง (override category rules)</p>
                </div>
            </div>

            @php
                $customRules = collect($setting->custom_bot_rules ?? []);
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 font-semibold text-gray-600 dark:text-gray-300">Bot Name</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-600 dark:text-gray-300">Owner</th>
                            <th class="text-center py-3 px-4 font-semibold text-gray-600 dark:text-gray-300">Category</th>
                            <th class="text-center py-3 px-4 font-semibold text-gray-600 dark:text-gray-300">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($knownBots as $botName => $info)
                            @php
                                $existingRule = $customRules->firstWhere('bot_name', $botName);
                                $currentAction = $existingRule['action'] ?? 'default';
                                $catBadge = match($info['category']) {
                                    'training' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    'assistant' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                    'search' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
                                };
                            @endphp
                            <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="py-3 px-4 font-medium text-gray-900 dark:text-white">{{ $botName }}</td>
                                <td class="py-3 px-4 text-gray-500 dark:text-gray-400">{{ $info['owner'] }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $catBadge }}">{{ ucfirst($info['category']) }}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <select name="bot_rule_{{ $botName }}" class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-3 py-1.5 focus:ring-2 focus:ring-blue-500">
                                        <option value="default" {{ $currentAction === 'default' ? 'selected' : '' }}>Default (use category)</option>
                                        <option value="allow" {{ $currentAction === 'allow' ? 'selected' : '' }}>Allow</option>
                                        <option value="block" {{ $currentAction === 'block' ? 'selected' : '' }}>Block</option>
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Blocked Paths -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Blocked Paths</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">URL paths ที่ AI bots ไม่สามารถเข้าถึงได้ (1 path ต่อ 1 บรรทัด)</p>
                </div>
            </div>

            <textarea name="blocked_paths" rows="6"
                class="w-full font-mono text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-4 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                placeholder="/admin/&#10;/customer/&#10;/api/&#10;/downloads/">{{ old('blocked_paths', implode("\n", $setting->blocked_paths ?? [])) }}</textarea>
        </div>

        <!-- llms.txt -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-green-600 flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">llms.txt</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">เนื้อหาที่ AI จะอ่านเพื่อทำความรู้จักเว็บไซต์ของคุณ (Markdown format)</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="llms_txt_enabled" value="1" class="sr-only peer" {{ $setting->llms_txt_enabled ? 'checked' : '' }}>
                    <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-green-500 peer-checked:to-green-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-100">Enabled</span>
                </label>
            </div>

            <div class="mb-3 p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                <div class="flex items-start space-x-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>llms.txt</strong> คือมาตรฐานใหม่ที่ให้ AI models รู้จักเว็บไซต์ของคุณ เมื่อคนถาม AI ว่า "XMAN Studio คืออะไร" — AI จะอ่านไฟล์นี้เพื่อตอบได้ถูกต้อง
                        @if($setting->llms_txt_enabled)
                            <br>Preview: <a href="{{ route('llms-txt') }}" target="_blank" class="underline">{{ url('/llms.txt') }}</a>
                        @endif
                    </div>
                </div>
            </div>

            <textarea name="llms_txt_content" rows="16"
                class="w-full font-mono text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl p-4 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all"
                placeholder="# Site Name&#10;> Short description&#10;&#10;## Products&#10;- Product 1&#10;- Product 2">{{ old('llms_txt_content', $setting->llms_txt_content) }}</textarea>
        </div>

        <!-- Generated robots.txt Preview -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-400 to-purple-600 flex items-center justify-center mr-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Generated robots.txt Rules (Preview)</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">กฎ AI bot ที่จะถูกเพิ่มใน robots.txt (อ่านอย่างเดียว) — คัดลอกไปวางใน SEO > robots.txt ได้</p>
                </div>
            </div>

            <pre class="bg-gray-900 text-green-400 p-4 rounded-xl overflow-x-auto text-sm font-mono max-h-96 overflow-y-auto">{{ $setting->generateRobotsTxtRules() }}</pre>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl shadow-lg hover:from-blue-700 hover:to-indigo-700 transition-all text-lg font-semibold">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
