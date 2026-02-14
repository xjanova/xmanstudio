{{-- AI Chat Floating Widget --}}
@php
    $aiChatEnabled = \App\Models\Setting::getValue('ai_chat_enabled', false);
@endphp

@if($aiChatEnabled)
@php
    $aiBotName = \App\Models\Setting::getValue('ai_bot_name', 'AI Assistant');
@endphp

<style>
    /* ===== AI Chat Widget Styles ===== */
    #ai-chat-widget * {
        box-sizing: border-box;
    }

    /* Floating Button */
    .ai-chat-fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        width: 64px;
        height: 64px;
        border-radius: 50%;
        cursor: pointer;
        border: none;
        padding: 0;
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        box-shadow: 0 4px 20px rgba(124, 58, 237, 0.4), 0 0 0 0 rgba(124, 58, 237, 0.3);
        animation: ai-fab-pulse 3s ease-in-out infinite;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: visible;
    }
    .ai-chat-fab:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 28px rgba(124, 58, 237, 0.5);
    }
    .ai-chat-fab.hidden { display: none; }

    @keyframes ai-fab-pulse {
        0%, 100% { box-shadow: 0 4px 20px rgba(124, 58, 237, 0.4), 0 0 0 0 rgba(124, 58, 237, 0.3); }
        50% { box-shadow: 0 4px 20px rgba(124, 58, 237, 0.4), 0 0 0 12px rgba(124, 58, 237, 0); }
    }

    /* Avatar SVG Animations */
    @keyframes ai-blink {
        0%, 90%, 100% { transform: scaleY(1); }
        95% { transform: scaleY(0.1); }
    }
    @keyframes ai-mouth-talk {
        0%, 100% { d: path("M 14 22 Q 20 25 26 22"); ry: 1; }
        25% { d: path("M 14 21 Q 20 28 26 21"); ry: 3; }
        50% { d: path("M 14 22 Q 20 24 26 22"); ry: 1.5; }
        75% { d: path("M 14 21 Q 20 27 26 21"); ry: 2.5; }
    }
    @keyframes ai-mouth-talk-ellipse {
        0%, 100% { ry: 1; }
        25% { ry: 3.5; }
        50% { ry: 1.5; }
        75% { ry: 3; }
    }
    @keyframes ai-float-bob {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }

    .ai-avatar-eyes {
        animation: ai-blink 4s ease-in-out infinite;
        transform-origin: center;
    }
    .ai-avatar-mouth-idle {
        transition: opacity 0.2s ease;
    }
    .ai-avatar-mouth-talk {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .ai-speaking .ai-avatar-mouth-idle {
        opacity: 0;
    }
    .ai-speaking .ai-avatar-mouth-talk {
        opacity: 1;
        animation: ai-mouth-talk-ellipse 0.35s ease-in-out infinite;
    }
    .ai-fab-avatar {
        animation: ai-float-bob 3s ease-in-out infinite;
    }

    /* Chat Window */
    .ai-chat-window {
        position: fixed;
        bottom: 100px;
        right: 24px;
        z-index: 9998;
        width: 380px;
        max-height: 520px;
        border-radius: 20px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        opacity: 0;
        transform: translateY(20px) scale(0.95);
        pointer-events: none;
        transition: opacity 0.3s ease, transform 0.3s ease;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(229, 231, 235, 0.8);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.1);
    }
    .ai-chat-window.open {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }

    /* Dark mode */
    .dark .ai-chat-window {
        background: rgba(31, 41, 55, 0.95);
        border-color: rgba(75, 85, 99, 0.6);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    }

    /* Header */
    .ai-chat-header {
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }
    .ai-chat-header-name {
        color: white;
        font-weight: 600;
        font-size: 15px;
        flex: 1;
    }
    .ai-chat-header-status {
        color: rgba(255, 255, 255, 0.7);
        font-size: 11px;
    }
    .ai-chat-close {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s ease;
        flex-shrink: 0;
    }
    .ai-chat-close:hover {
        background: rgba(255, 255, 255, 0.25);
    }

    /* Messages Area */
    .ai-chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-height: 280px;
        max-height: 340px;
        scrollbar-width: thin;
        scrollbar-color: #d1d5db transparent;
    }
    .dark .ai-chat-messages {
        scrollbar-color: #4b5563 transparent;
    }
    .ai-chat-messages::-webkit-scrollbar { width: 4px; }
    .ai-chat-messages::-webkit-scrollbar-track { background: transparent; }
    .ai-chat-messages::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 2px; }
    .dark .ai-chat-messages::-webkit-scrollbar-thumb { background: #4b5563; }

    /* Message Bubbles */
    .ai-msg { display: flex; gap: 8px; max-width: 100%; }
    .ai-msg-user { flex-direction: row-reverse; }
    .ai-msg-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        flex-shrink: 0;
        overflow: hidden;
    }
    .ai-msg-bubble {
        padding: 10px 14px;
        border-radius: 16px;
        font-size: 13.5px;
        line-height: 1.5;
        max-width: 75%;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    .ai-msg-bot .ai-msg-bubble {
        background: #f3f4f6;
        color: #1f2937;
        border-bottom-left-radius: 4px;
    }
    .dark .ai-msg-bot .ai-msg-bubble {
        background: #374151;
        color: #f3f4f6;
    }
    .ai-msg-user .ai-msg-bubble {
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        color: white;
        border-bottom-right-radius: 4px;
    }

    /* Typing Indicator */
    .ai-typing { display: flex; gap: 8px; align-items: flex-end; }
    .ai-typing-dots {
        display: flex;
        gap: 4px;
        padding: 12px 16px;
        background: #f3f4f6;
        border-radius: 16px;
        border-bottom-left-radius: 4px;
    }
    .dark .ai-typing-dots {
        background: #374151;
    }
    .ai-typing-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #9ca3af;
        animation: ai-typing-bounce 1.2s ease-in-out infinite;
    }
    .ai-typing-dot:nth-child(2) { animation-delay: 0.15s; }
    .ai-typing-dot:nth-child(3) { animation-delay: 0.3s; }
    @keyframes ai-typing-bounce {
        0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
        30% { transform: translateY(-6px); opacity: 1; }
    }

    /* Input Area */
    .ai-chat-input-area {
        padding: 12px 16px;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 8px;
        align-items: flex-end;
        flex-shrink: 0;
        background: rgba(255, 255, 255, 0.5);
    }
    .dark .ai-chat-input-area {
        border-top-color: #4b5563;
        background: rgba(31, 41, 55, 0.5);
    }
    .ai-chat-input {
        flex: 1;
        border: 1px solid #d1d5db;
        border-radius: 12px;
        padding: 10px 14px;
        font-size: 13.5px;
        resize: none;
        max-height: 80px;
        line-height: 1.4;
        outline: none;
        transition: border-color 0.2s ease;
        background: white;
        color: #1f2937;
        font-family: inherit;
    }
    .ai-chat-input:focus {
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }
    .dark .ai-chat-input {
        background: #1f2937;
        border-color: #4b5563;
        color: #f3f4f6;
    }
    .dark .ai-chat-input:focus {
        border-color: #7c3aed;
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
    }
    .ai-chat-input::placeholder {
        color: #9ca3af;
    }
    .ai-chat-send {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: none;
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.2s ease, transform 0.2s ease;
        flex-shrink: 0;
    }
    .ai-chat-send:hover { opacity: 0.9; transform: scale(1.05); }
    .ai-chat-send:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }

    /* Footer */
    .ai-chat-footer {
        text-align: center;
        padding: 6px;
        font-size: 10px;
        color: #9ca3af;
        border-top: 1px solid #f3f4f6;
        flex-shrink: 0;
    }
    .dark .ai-chat-footer {
        border-top-color: #374151;
        color: #6b7280;
    }

    /* Message content formatting */
    .ai-msg-bubble p { margin: 0 0 6px; }
    .ai-msg-bubble p:last-child { margin-bottom: 0; }
    .ai-msg-bubble strong { font-weight: 600; }
    .ai-msg-bubble code {
        background: rgba(0,0,0,0.08);
        padding: 1px 5px;
        border-radius: 4px;
        font-size: 12px;
        font-family: monospace;
    }
    .dark .ai-msg-bubble code {
        background: rgba(255,255,255,0.1);
    }
    .ai-msg-bubble ul, .ai-msg-bubble ol {
        margin: 4px 0;
        padding-left: 18px;
    }
    .ai-msg-bubble li { margin: 2px 0; }

    /* Clickable navigation links in chat */
    .ai-chat-link {
        display: inline-block;
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        color: white !important;
        text-decoration: none !important;
        padding: 5px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 500;
        margin: 3px 2px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
        box-shadow: 0 2px 6px rgba(124, 58, 237, 0.3);
        cursor: pointer;
    }
    .ai-chat-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(124, 58, 237, 0.4);
    }
    .ai-chat-link:active {
        transform: scale(0.95);
    }

    /* Mobile - FAB hidden, use bottom nav center AI button instead */
    @media (max-width: 767px) {
        .ai-chat-fab {
            display: none !important;
        }
        .ai-chat-window {
            right: 0;
            left: 0;
            bottom: 64px; /* Above bottom nav bar */
            width: 100%;
            max-height: calc(100vh - 80px);
            border-radius: 20px 20px 0 0;
            border-bottom: none;
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }
    }

    /* Notification badge */
    .ai-fab-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        width: 16px;
        height: 16px;
        background: #ef4444;
        border-radius: 50%;
        border: 2px solid white;
        animation: ai-badge-pop 0.3s ease;
    }
    .dark .ai-fab-badge { border-color: #1f2937; }
    @keyframes ai-badge-pop {
        0% { transform: scale(0); }
        60% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
</style>

<div id="ai-chat-widget">
    {{-- Floating Avatar Button --}}
    <button class="ai-chat-fab" id="aiChatFab" onclick="window.AiChat.toggle()" aria-label="Chat with AI">
        <svg class="ai-fab-avatar" width="44" height="44" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
            {{-- Head --}}
            <circle cx="20" cy="20" r="18" fill="white" opacity="0.95"/>
            <circle cx="20" cy="20" r="16.5" fill="url(#ai-fab-grad)" opacity="0.1"/>
            {{-- Eyes --}}
            <g class="ai-avatar-eyes">
                <ellipse cx="13.5" cy="16" rx="2.5" ry="2.8" fill="#4f46e5"/>
                <ellipse cx="26.5" cy="16" rx="2.5" ry="2.8" fill="#4f46e5"/>
                {{-- Eye highlights --}}
                <circle cx="14.5" cy="14.8" r="0.9" fill="white"/>
                <circle cx="27.5" cy="14.8" r="0.9" fill="white"/>
            </g>
            {{-- Cheeks --}}
            <ellipse cx="9" cy="21" rx="2.5" ry="1.5" fill="#f9a8d4" opacity="0.4"/>
            <ellipse cx="31" cy="21" rx="2.5" ry="1.5" fill="#f9a8d4" opacity="0.4"/>
            {{-- Mouth - idle (smile) --}}
            <path class="ai-avatar-mouth-idle" d="M 14 23 Q 20 27 26 23" stroke="#4f46e5" stroke-width="1.8" stroke-linecap="round" fill="none"/>
            {{-- Mouth - talking (animated ellipse) --}}
            <ellipse class="ai-avatar-mouth-talk" cx="20" cy="24" rx="4" ry="1" fill="#4f46e5"/>
            {{-- Antenna --}}
            <line x1="20" y1="2" x2="20" y2="5" stroke="#7c3aed" stroke-width="1.5" stroke-linecap="round"/>
            <circle cx="20" cy="1.5" r="1.5" fill="#7c3aed"/>
            <defs>
                <linearGradient id="ai-fab-grad" x1="0" y1="0" x2="40" y2="40">
                    <stop stop-color="#7c3aed"/>
                    <stop offset="1" stop-color="#4f46e5"/>
                </linearGradient>
            </defs>
        </svg>
        <span class="ai-fab-badge" id="aiFabBadge" style="display:none"></span>
    </button>

    {{-- Chat Window --}}
    <div class="ai-chat-window" id="aiChatWindow">
        {{-- Header --}}
        <div class="ai-chat-header">
            <div class="ai-msg-avatar">
                <svg width="30" height="30" viewBox="0 0 40 40" fill="none">
                    <circle cx="20" cy="20" r="18" fill="white" opacity="0.2"/>
                    <g class="ai-avatar-eyes">
                        <ellipse cx="13.5" cy="16" rx="2.2" ry="2.5" fill="white"/>
                        <ellipse cx="26.5" cy="16" rx="2.2" ry="2.5" fill="white"/>
                        <circle cx="14.3" cy="14.8" r="0.7" fill="rgba(255,255,255,0.5)"/>
                        <circle cx="27.3" cy="14.8" r="0.7" fill="rgba(255,255,255,0.5)"/>
                    </g>
                    <path d="M 14 23 Q 20 27 26 23" stroke="white" stroke-width="1.5" stroke-linecap="round" fill="none"/>
                    <line x1="20" y1="3" x2="20" y2="5.5" stroke="white" stroke-width="1.2" stroke-linecap="round" opacity="0.6"/>
                    <circle cx="20" cy="2.5" r="1.2" fill="white" opacity="0.6"/>
                </svg>
            </div>
            <div>
                <div class="ai-chat-header-name">{{ $aiBotName }}</div>
                <div class="ai-chat-header-status" id="aiChatStatus">ออนไลน์</div>
            </div>
            <button class="ai-chat-close" onclick="window.AiChat.close()" aria-label="Close chat">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        {{-- Messages --}}
        <div class="ai-chat-messages" id="aiChatMessages">
            {{-- Welcome message injected by JS --}}
        </div>

        {{-- Input --}}
        <div class="ai-chat-input-area">
            <textarea class="ai-chat-input" id="aiChatInput" placeholder="พิมพ์ข้อความ..." rows="1" maxlength="2000"></textarea>
            <button class="ai-chat-send" id="aiChatSend" onclick="window.AiChat.send()" aria-label="Send message">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>

        {{-- Footer --}}
        <div class="ai-chat-footer">
            Powered by AI &bull; XMAN Studio
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    const CHAT_URL = '{{ route("public.ai-chat") }}';
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const BOT_NAME = @json($aiBotName);
    const MAX_MESSAGES = 20;
    const STORAGE_KEY = 'ai_chat_history';

    let chatHistory = [];
    let isOpen = false;
    let isSending = false;
    let hasOpened = false;

    // DOM refs
    const fab = document.getElementById('aiChatFab');
    const chatWindow = document.getElementById('aiChatWindow');
    const messagesEl = document.getElementById('aiChatMessages');
    const inputEl = document.getElementById('aiChatInput');
    const sendBtn = document.getElementById('aiChatSend');
    const badge = document.getElementById('aiFabBadge');
    const statusEl = document.getElementById('aiChatStatus');

    // Load from session storage
    function loadHistory() {
        try {
            const stored = sessionStorage.getItem(STORAGE_KEY);
            if (stored) {
                chatHistory = JSON.parse(stored);
                chatHistory.forEach(function(msg) {
                    appendMessageDOM(msg.role, msg.content, false);
                });
            }
        } catch (e) {
            chatHistory = [];
        }
    }

    function saveHistory() {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(chatHistory.slice(-MAX_MESSAGES)));
        } catch (e) { /* ignore */ }
    }

    // Create avatar SVG for message bubbles
    function createMiniAvatar(speaking) {
        const div = document.createElement('div');
        div.className = 'ai-msg-avatar' + (speaking ? ' ai-speaking' : '');
        div.innerHTML = '<svg width="30" height="30" viewBox="0 0 40 40" fill="none">' +
            '<circle cx="20" cy="20" r="18" fill="#eef2ff"/>' +
            '<g class="ai-avatar-eyes"><ellipse cx="13.5" cy="16" rx="2.2" ry="2.5" fill="#4f46e5"/><ellipse cx="26.5" cy="16" rx="2.2" ry="2.5" fill="#4f46e5"/><circle cx="14.3" cy="14.8" r="0.7" fill="white"/><circle cx="27.3" cy="14.8" r="0.7" fill="white"/></g>' +
            '<ellipse cx="9" cy="21" rx="2" ry="1.2" fill="#f9a8d4" opacity="0.4"/><ellipse cx="31" cy="21" rx="2" ry="1.2" fill="#f9a8d4" opacity="0.4"/>' +
            '<path class="ai-avatar-mouth-idle" d="M 14 23 Q 20 27 26 23" stroke="#4f46e5" stroke-width="1.5" stroke-linecap="round" fill="none"/>' +
            '<ellipse class="ai-avatar-mouth-talk" cx="20" cy="24" rx="4" ry="1" fill="#4f46e5"/>' +
            '</svg>';
        return div;
    }

    // Append message to DOM
    function appendMessageDOM(role, content, animate) {
        const msgEl = document.createElement('div');
        msgEl.className = 'ai-msg ai-msg-' + (role === 'user' ? 'user' : 'bot');

        if (role !== 'user') {
            msgEl.appendChild(createMiniAvatar(false));
        }

        const bubble = document.createElement('div');
        bubble.className = 'ai-msg-bubble';
        bubble.innerHTML = formatContent(content);
        msgEl.appendChild(bubble);

        if (animate) {
            msgEl.style.opacity = '0';
            msgEl.style.transform = 'translateY(8px)';
            msgEl.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        }

        messagesEl.appendChild(msgEl);

        if (animate) {
            requestAnimationFrame(function() {
                msgEl.style.opacity = '1';
                msgEl.style.transform = 'translateY(0)';
            });
        }

        scrollToBottom();
        return msgEl;
    }

    // Format text content with basic markdown + clickable links
    function formatContent(text) {
        if (!text) return '';
        let html = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/`(.+?)`/g, '<code>$1</code>')
            .replace(/\n- /g, '\n&bull; ')
            .replace(/\n(\d+)\. /g, '\n$1. ');

        // Markdown links [text](url) → clickable buttons (internal only)
        html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, function(match, linkText, url) {
            // Only allow internal links (same domain or relative paths)
            var baseHost = window.location.hostname;
            var isInternal = false;
            try {
                if (url.startsWith('/') || url.startsWith('./')) {
                    isInternal = true;
                } else {
                    var linkUrl = new URL(url);
                    isInternal = linkUrl.hostname === baseHost;
                }
            } catch(e) {
                isInternal = url.startsWith('/');
            }

            if (isInternal) {
                return '<a href="' + url + '" class="ai-chat-link" onclick="event.stopPropagation();">📍 ' + linkText + '</a>';
            }
            return linkText;
        });

        // Auto-link plain URLs that start with our domain or /path
        html = html.replace(/(https?:\/\/[^\s<]+)/g, function(url) {
            try {
                var u = new URL(url);
                if (u.hostname === window.location.hostname) {
                    return '<a href="' + url + '" class="ai-chat-link" onclick="event.stopPropagation();">🔗 ' + u.pathname + '</a>';
                }
            } catch(e) {}
            return url;
        });

        // Paragraphs
        html = html.split('\n\n').map(function(p) { return '<p>' + p.replace(/\n/g, '<br>') + '</p>'; }).join('');
        return html;
    }

    // Show typing indicator with speaking avatar
    function showTyping() {
        const typingEl = document.createElement('div');
        typingEl.className = 'ai-typing';
        typingEl.id = 'aiTypingIndicator';

        typingEl.appendChild(createMiniAvatar(true));

        const dots = document.createElement('div');
        dots.className = 'ai-typing-dots';
        dots.innerHTML = '<div class="ai-typing-dot"></div><div class="ai-typing-dot"></div><div class="ai-typing-dot"></div>';
        typingEl.appendChild(dots);

        messagesEl.appendChild(typingEl);
        scrollToBottom();

        // Activate speaking animation on fab avatar
        fab.classList.add('ai-speaking');
        statusEl.textContent = 'กำลังพิมพ์...';
    }

    function hideTyping() {
        const el = document.getElementById('aiTypingIndicator');
        if (el) el.remove();
        fab.classList.remove('ai-speaking');
        statusEl.textContent = 'ออนไลน์';
    }

    function scrollToBottom() {
        requestAnimationFrame(function() {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        });
    }

    // Send message
    async function sendMessage() {
        const text = inputEl.value.trim();
        if (!text || isSending) return;

        isSending = true;
        sendBtn.disabled = true;
        inputEl.value = '';
        inputEl.style.height = 'auto';

        // Add user message
        chatHistory.push({ role: 'user', content: text });
        appendMessageDOM('user', text, true);
        saveHistory();

        // Show typing
        showTyping();

        try {
            // Prepare messages for API (last MAX_MESSAGES)
            const apiMessages = chatHistory.slice(-MAX_MESSAGES).map(function(m) {
                return { role: m.role, content: m.content };
            });

            const response = await fetch(CHAT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ messages: apiMessages }),
            });

            const data = await response.json();

            hideTyping();

            if (data.success && data.message) {
                chatHistory.push({ role: 'assistant', content: data.message });
                appendMessageDOM('assistant', data.message, true);
                saveHistory();
            } else {
                appendMessageDOM('assistant', data.message || 'ขออภัย เกิดข้อผิดพลาด กรุณาลองใหม่', true);
            }
        } catch (err) {
            hideTyping();
            appendMessageDOM('assistant', 'ขออภัย ไม่สามารถเชื่อมต่อได้ กรุณาลองใหม่อีกครั้ง', true);
        } finally {
            isSending = false;
            sendBtn.disabled = false;
            inputEl.focus();
        }
    }

    // Toggle chat window
    function toggleChat() {
        if (isOpen) {
            closeChat();
        } else {
            openChat();
        }
    }

    function isMobile() {
        return window.innerWidth < 768;
    }

    function openChat() {
        isOpen = true;
        chatWindow.classList.add('open');
        if (!isMobile()) {
            fab.classList.add('hidden');
        }

        if (!hasOpened) {
            hasOpened = true;
            if (chatHistory.length === 0) {
                // Welcome message
                const welcome = 'สวัสดีค่ะ! ฉันคือ ' + BOT_NAME + ' ผู้ช่วย AI ของ XMAN Studio ยินดีตอบคำถามเกี่ยวกับบริการ ผลิตภัณฑ์ และข้อมูลของเราค่ะ 😊';
                appendMessageDOM('assistant', welcome, true);
            }
        }

        setTimeout(function() { inputEl.focus(); }, 300);
    }

    function closeChat() {
        isOpen = false;
        chatWindow.classList.remove('open');
        if (!isMobile()) {
            fab.classList.remove('hidden');
        }
    }

    // Auto-resize textarea
    inputEl.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 80) + 'px';
    });

    // Enter to send, Shift+Enter for newline
    inputEl.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Load saved history on init
    loadHistory();

    // Show badge hint after 5 seconds if never opened
    setTimeout(function() {
        if (!hasOpened && chatHistory.length === 0) {
            badge.style.display = 'block';
        }
    }, 5000);

    // Expose public API
    window.AiChat = {
        toggle: toggleChat,
        open: openChat,
        close: closeChat,
        send: sendMessage,
        isOpen: function() { return isOpen; },
    };
})();
</script>
@endif
