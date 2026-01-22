@extends('layouts.admin')

@section('title', 'ตั้งค่า AI')
@section('page-title', 'ตั้งค่า AI')

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('admin.ai-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- AI Provider Selection -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                เลือก AI Provider
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all {{ $settings['ai_provider'] === 'openai' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}">
                    <input type="radio" name="ai_provider" value="openai" {{ $settings['ai_provider'] === 'openai' ? 'checked' : '' }} class="sr-only" onchange="this.closest('form').querySelectorAll('label[class*=border-2]').forEach(l => l.classList.remove('border-green-500', 'bg-green-50')); this.closest('label').classList.add('border-green-500', 'bg-green-50');">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-black rounded-lg flex items-center justify-center mr-4">
                            <span class="text-white font-bold text-lg">AI</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">OpenAI (GPT)</p>
                            <p class="text-sm text-gray-500">GPT-4o, GPT-4o-mini, GPT-4 Turbo</p>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 {{ $settings['ai_provider'] === 'openai' ? '' : 'hidden' }}">
                        <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </label>

                <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all {{ $settings['ai_provider'] === 'claude' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}">
                    <input type="radio" name="ai_provider" value="claude" {{ $settings['ai_provider'] === 'claude' ? 'checked' : '' }} class="sr-only" onchange="this.closest('form').querySelectorAll('label[class*=border-2]').forEach(l => l.classList.remove('border-green-500', 'bg-green-50')); this.closest('label').classList.add('border-green-500', 'bg-green-50');">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-orange-600 rounded-lg flex items-center justify-center mr-4">
                            <span class="text-white font-bold text-lg">C</span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Anthropic (Claude)</p>
                            <p class="text-sm text-gray-500">Claude 3.5 Sonnet, Claude 3 Haiku</p>
                        </div>
                    </div>
                    <div class="absolute top-2 right-2 {{ $settings['ai_provider'] === 'claude' ? '' : 'hidden' }}">
                        <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </label>
            </div>
        </div>

        <!-- OpenAI Settings -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <div class="w-8 h-8 bg-black rounded flex items-center justify-center mr-2">
                    <span class="text-white font-bold text-xs">AI</span>
                </div>
                OpenAI Settings
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="font-medium text-gray-900">เปิดใช้งาน OpenAI</label>
                        <p class="text-sm text-gray-500">เปิดใช้งาน OpenAI API สำหรับฟีเจอร์ AI</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="openai_enabled" value="1" {{ $settings['openai_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                    <input type="password" name="openai_api_key" value="{{ $settings['openai_api_key'] }}" placeholder="sk-..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <p class="mt-1 text-xs text-gray-500">รับ API Key ได้ที่ <a href="https://platform.openai.com/api-keys" target="_blank" class="text-primary-600 hover:underline">platform.openai.com</a></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <select name="openai_model" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="gpt-4o" {{ $settings['openai_model'] === 'gpt-4o' ? 'selected' : '' }}>GPT-4o (แนะนำ)</option>
                        <option value="gpt-4o-mini" {{ $settings['openai_model'] === 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini (ประหยัด)</option>
                        <option value="gpt-4-turbo" {{ $settings['openai_model'] === 'gpt-4-turbo' ? 'selected' : '' }}>GPT-4 Turbo</option>
                        <option value="gpt-3.5-turbo" {{ $settings['openai_model'] === 'gpt-3.5-turbo' ? 'selected' : '' }}>GPT-3.5 Turbo</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Claude Settings -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-orange-600 rounded flex items-center justify-center mr-2">
                    <span class="text-white font-bold text-xs">C</span>
                </div>
                Claude (Anthropic) Settings
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <label class="font-medium text-gray-900">เปิดใช้งาน Claude</label>
                        <p class="text-sm text-gray-500">เปิดใช้งาน Anthropic Claude API สำหรับฟีเจอร์ AI</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="claude_enabled" value="1" {{ $settings['claude_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-500"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                    <input type="password" name="claude_api_key" value="{{ $settings['claude_api_key'] }}" placeholder="sk-ant-..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <p class="mt-1 text-xs text-gray-500">รับ API Key ได้ที่ <a href="https://console.anthropic.com/" target="_blank" class="text-primary-600 hover:underline">console.anthropic.com</a></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                    <select name="claude_model" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                        <option value="claude-3-5-sonnet-20241022" {{ $settings['claude_model'] === 'claude-3-5-sonnet-20241022' ? 'selected' : '' }}>Claude 3.5 Sonnet (แนะนำ)</option>
                        <option value="claude-3-haiku-20240307" {{ $settings['claude_model'] === 'claude-3-haiku-20240307' ? 'selected' : '' }}>Claude 3 Haiku (ประหยัด)</option>
                        <option value="claude-3-opus-20240229" {{ $settings['claude_model'] === 'claude-3-opus-20240229' ? 'selected' : '' }}>Claude 3 Opus (พรีเมียม)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- General AI Settings -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                ตั้งค่าทั่วไป
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Max Tokens</label>
                    <input type="number" name="ai_max_tokens" value="{{ $settings['ai_max_tokens'] }}" min="100" max="8000"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <p class="mt-1 text-xs text-gray-500">จำนวน token สูงสุดในการตอบกลับ (100-8000)</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Temperature</label>
                    <input type="number" name="ai_temperature" value="{{ $settings['ai_temperature'] }}" min="0" max="2" step="0.1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
                    <p class="mt-1 text-xs text-gray-500">ความสร้างสรรค์ของ AI (0=แม่นยำ, 2=สร้างสรรค์)</p>
                </div>
            </div>
        </div>

        <!-- Feature Toggles -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                เปิด/ปิดฟีเจอร์ AI
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <label class="font-medium text-gray-900">AI Chat Assistant</label>
                        <p class="text-sm text-gray-500">เปิดใช้งาน AI Chat สำหรับช่วยเหลือลูกค้า</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_chat_enabled" value="1" {{ $settings['ai_chat_enabled'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <label class="font-medium text-gray-900">Content Generation</label>
                        <p class="text-sm text-gray-500">ใช้ AI สร้างเนื้อหา บทความ คำอธิบายสินค้า</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_content_generation" value="1" {{ $settings['ai_content_generation'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <label class="font-medium text-gray-900">Code Assistant</label>
                        <p class="text-sm text-gray-500">ใช้ AI ช่วยเขียนโค้ด แก้ไขบัก</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="ai_code_assistant" value="1" {{ $settings['ai_code_assistant'] ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <button type="button" onclick="testConnection()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    ทดสอบการเชื่อมต่อ
                </span>
            </button>

            <button type="submit" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium transition-colors">
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

<script>
function testConnection() {
    const btn = event.target.closest('button');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2 inline" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>กำลังทดสอบ...';
    btn.disabled = true;

    fetch('{{ route("admin.ai-settings.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('สำเร็จ: ' + data.message);
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
</script>
@endsection
