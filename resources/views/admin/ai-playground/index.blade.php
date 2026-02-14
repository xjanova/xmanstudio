@extends($adminLayout ?? 'layouts.admin')

@section('title', 'AI Playground')
@section('page-title', 'AI Playground')

@section('content')
<div class="max-w-5xl mx-auto">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-500 rounded-2xl shadow-xl p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center mr-4">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold">AI Playground</h2>
                    <p class="text-white/80 text-sm">ทดสอบแชทกับ AI ก่อนเปิดใช้งานจริง</p>
                </div>
            </div>
            <a href="{{ route('admin.ai-settings.index') }}" class="flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl transition-colors text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                ตั้งค่า AI
            </a>
        </div>
    </div>

    {{-- Provider Info Bar --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 mb-4 border border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    @if($isConfigured)
                        <span class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></span>
                        <span class="text-sm font-medium text-green-600 dark:text-green-400">พร้อมใช้งาน</span>
                    @else
                        <span class="w-3 h-3 bg-red-400 rounded-full"></span>
                        <span class="text-sm font-medium text-red-600 dark:text-red-400">ยังไม่ได้ตั้งค่า</span>
                    @endif
                </div>
                <span class="text-gray-300 dark:text-gray-600">|</span>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full
                        @switch($providerInfo['provider'])
                            @case('openai') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 @break
                            @case('gemini') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 @break
                            @case('claude') bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 @break
                            @case('ollama') bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 @break
                        @endswitch
                    ">{{ $providerInfo['provider_name'] }}</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $providerInfo['model'] }}</span>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="toggleSystemPrompt()" class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors" title="แก้ไข System Prompt ชั่วคราว">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    System Prompt
                </button>
                <button onclick="clearChat()" class="px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg transition-colors">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    ล้างแชท
                </button>
            </div>
        </div>

        {{-- System Prompt Override (collapsible) --}}
        <div id="system-prompt-panel" class="hidden mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                System Prompt (แก้ไขชั่วคราว - ไม่บันทึก)
            </label>
            <textarea id="system-prompt-override" rows="3"
                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                placeholder="ว่างไว้ = ใช้ค่าจากการตั้งค่า AI">{{ $settings['ai_system_prompt'] }}</textarea>
            <p class="mt-1 text-xs text-gray-400">แก้ไขได้ตามต้องการเพื่อทดสอบ จะไม่ถูกบันทึกลงระบบ</p>
        </div>
    </div>

    {{-- Chat Container --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 flex flex-col" style="height: calc(100vh - 380px); min-height: 400px;">

        {{-- Chat Messages --}}
        <div id="chat-messages" class="flex-1 overflow-y-auto p-6 space-y-4">
            {{-- Welcome message --}}
            <div class="flex items-start space-x-3" id="welcome-message">
                <div class="flex-shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="max-w-[75%]">
                    <p class="text-xs text-gray-400 mb-1">{{ $settings['ai_bot_name'] }} <span class="text-gray-300">&bull;</span> {{ $providerInfo['provider_name'] }}</p>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl rounded-tl-sm px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                        @if($isConfigured)
                            สวัสดีครับ! ผม <strong>{{ $settings['ai_bot_name'] }}</strong> พร้อมให้บริการแล้ว ลองพิมพ์ข้อความเพื่อทดสอบการสนทนาได้เลยครับ
                        @else
                            <span class="text-red-500">AI ยังไม่ได้ตั้งค่า กรุณาไปที่ <a href="{{ route('admin.ai-settings.index') }}" class="underline font-medium">ตั้งค่า AI</a> เพื่อเลือก Provider และใส่ API Key ก่อนครับ</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Typing Indicator --}}
        <div id="typing-indicator" class="hidden px-6 pb-2">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl rounded-tl-sm px-4 py-3">
                    <div class="flex space-x-1.5">
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input Area --}}
        <div class="border-t border-gray-200 dark:border-gray-700 p-4">
            <form id="chat-form" class="flex items-end gap-3">
                <div class="flex-1 relative">
                    <textarea id="chat-input" rows="1"
                        class="w-full px-4 py-3 pr-12 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none transition-all"
                        placeholder="พิมพ์ข้อความ... (Enter = ส่ง, Shift+Enter = ขึ้นบรรทัดใหม่)"
                        @if(!$isConfigured) disabled @endif
                        style="max-height: 120px;"></textarea>
                </div>
                <button type="submit" id="send-btn"
                    class="flex-shrink-0 w-11 h-11 flex items-center justify-center bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    @if(!$isConfigured) disabled @endif>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </button>
            </form>
            <div class="flex items-center justify-between mt-2 px-1">
                <p class="text-xs text-gray-400">Enter = ส่ง &bull; Shift+Enter = ขึ้นบรรทัดใหม่</p>
                <p class="text-xs text-gray-400" id="msg-count">0 ข้อความ</p>
            </div>
        </div>
    </div>
</div>

<script>
const CHAT_URL = '{{ route("admin.ai-playground.chat") }}';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
const BOT_NAME = @json($settings['ai_bot_name']);
const PROVIDER_NAME = @json($providerInfo['provider_name']);
const MAX_HISTORY = 40;

let chatHistory = [];
let isLoading = false;

// DOM elements
const chatMessages = document.getElementById('chat-messages');
const chatInput = document.getElementById('chat-input');
const chatForm = document.getElementById('chat-form');
const sendBtn = document.getElementById('send-btn');
const typingIndicator = document.getElementById('typing-indicator');
const msgCount = document.getElementById('msg-count');

// Form submit
chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    sendMessage();
});

// Enter to send, Shift+Enter for newline
chatInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Auto-resize textarea
chatInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

async function sendMessage() {
    const message = chatInput.value.trim();
    if (!message || isLoading) return;

    // Add user message
    appendMessage('user', message);
    chatHistory.push({ role: 'user', content: message });
    chatInput.value = '';
    chatInput.style.height = 'auto';
    updateMsgCount();

    // Show typing
    isLoading = true;
    sendBtn.disabled = true;
    typingIndicator.classList.remove('hidden');
    scrollToBottom();

    try {
        const systemOverride = getSystemPromptOverride();
        const response = await fetch(CHAT_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                messages: chatHistory.slice(-MAX_HISTORY),
                system_prompt_override: systemOverride || null,
            }),
        });

        const data = await response.json();

        if (data.success) {
            appendMessage('assistant', data.message, data.provider, data.model);
            chatHistory.push({ role: 'assistant', content: data.message });
        } else {
            appendError(data.message || 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ');
        }
    } catch (error) {
        appendError('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ กรุณาลองใหม่');
    } finally {
        isLoading = false;
        sendBtn.disabled = false;
        typingIndicator.classList.add('hidden');
        updateMsgCount();
        scrollToBottom();
        chatInput.focus();
    }
}

function appendMessage(role, content, provider, model) {
    const div = document.createElement('div');

    if (role === 'user') {
        div.className = 'flex items-start justify-end space-x-3';
        div.innerHTML = `
            <div class="max-w-[75%]">
                <p class="text-xs text-gray-400 mb-1 text-right">คุณ</p>
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl rounded-tr-sm px-4 py-3 text-sm text-white whitespace-pre-wrap">${escapeHtml(content)}</div>
            </div>
            <div class="flex-shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-gray-600 to-gray-800 flex items-center justify-center shadow-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            </div>
        `;
    } else {
        const providerBadge = model ? `<span class="text-gray-300 dark:text-gray-500">&bull;</span> <span class="text-xs text-gray-400">${escapeHtml(model)}</span>` : '';
        div.className = 'flex items-start space-x-3';
        div.innerHTML = `
            <div class="flex-shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div class="max-w-[75%]">
                <p class="text-xs text-gray-400 mb-1">${escapeHtml(BOT_NAME)} ${providerBadge}</p>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl rounded-tl-sm px-4 py-3 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">${formatMessage(content)}</div>
            </div>
        `;
    }

    chatMessages.appendChild(div);
    scrollToBottom();
}

function appendError(message) {
    const div = document.createElement('div');
    div.className = 'flex justify-center';
    div.innerHTML = `
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl px-4 py-2 text-sm text-red-600 dark:text-red-400 max-w-[80%]">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            ${escapeHtml(message)}
        </div>
    `;
    chatMessages.appendChild(div);
    scrollToBottom();
}

function formatMessage(content) {
    // Simple markdown-like formatting
    let html = escapeHtml(content);
    // Bold: **text**
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    // Inline code: `code`
    html = html.replace(/`([^`]+)`/g, '<code class="bg-gray-200 dark:bg-gray-600 px-1.5 py-0.5 rounded text-xs font-mono">$1</code>');
    // Code blocks: ```code```
    html = html.replace(/```(\w*)\n?([\s\S]*?)```/g, '<pre class="bg-gray-800 text-gray-100 rounded-lg p-3 my-2 overflow-x-auto text-xs font-mono"><code>$2</code></pre>');
    // Lists: - item
    html = html.replace(/^- (.+)$/gm, '<span class="flex items-start"><span class="mr-2 text-purple-500">&bull;</span><span>$1</span></span>');
    // Numbered lists: 1. item
    html = html.replace(/^(\d+)\. (.+)$/gm, '<span class="flex items-start"><span class="mr-2 text-purple-500 font-medium">$1.</span><span>$2</span></span>');
    return html;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function scrollToBottom() {
    requestAnimationFrame(() => {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
}

function clearChat() {
    if (chatHistory.length === 0) return;
    if (!confirm('ล้างประวัติแชททั้งหมด?')) return;

    chatHistory = [];
    // Remove all messages except the welcome message
    const messages = chatMessages.querySelectorAll(':scope > div:not(#welcome-message)');
    messages.forEach(msg => msg.remove());
    updateMsgCount();
    chatInput.focus();
}

function toggleSystemPrompt() {
    const panel = document.getElementById('system-prompt-panel');
    panel.classList.toggle('hidden');
}

function getSystemPromptOverride() {
    const textarea = document.getElementById('system-prompt-override');
    return textarea ? textarea.value.trim() : '';
}

function updateMsgCount() {
    const userMsgs = chatHistory.filter(m => m.role === 'user').length;
    msgCount.textContent = `${userMsgs} ข้อความ`;
}

// Focus input on load
if (chatInput && !chatInput.disabled) {
    chatInput.focus();
}
</script>
@endsection
