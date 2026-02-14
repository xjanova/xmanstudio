{{-- Mobile Bottom Navigation Bar - App-like experience --}}
<style>
    /* ===== Mobile Bottom Nav ===== */
    .mobile-bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 9990;
        display: none;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-top: 1px solid rgba(229, 231, 235, 0.6);
        padding-bottom: env(safe-area-inset-bottom, 0px);
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
    }
    .dark .mobile-bottom-nav {
        background: rgba(17, 24, 39, 0.92);
        border-top-color: rgba(55, 65, 81, 0.6);
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 767px) {
        .mobile-bottom-nav { display: block; }
    }

    .mobile-nav-items {
        display: flex;
        justify-content: space-around;
        align-items: center;
        height: 64px;
        max-width: 500px;
        margin: 0 auto;
        padding: 0 4px;
    }

    /* Nav Item */
    .mobile-nav-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex: 1;
        height: 100%;
        padding: 6px 2px 4px;
        text-decoration: none;
        position: relative;
        -webkit-tap-highlight-color: transparent;
        transition: transform 0.15s ease;
        cursor: pointer;
        border: none;
        background: none;
    }
    .mobile-nav-item:active {
        transform: scale(0.9);
    }

    /* Icon container */
    .mobile-nav-icon {
        width: 26px;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 2px;
        transition: transform 0.2s ease, color 0.2s ease;
        color: #9ca3af;
    }
    .dark .mobile-nav-icon { color: #6b7280; }

    .mobile-nav-icon svg {
        width: 22px;
        height: 22px;
        stroke-width: 1.8;
    }

    /* Label */
    .mobile-nav-label {
        font-size: 10px;
        font-weight: 500;
        line-height: 1;
        color: #9ca3af;
        transition: color 0.2s ease;
        letter-spacing: -0.01em;
    }
    .dark .mobile-nav-label { color: #6b7280; }

    /* Active indicator dot */
    .mobile-nav-dot {
        position: absolute;
        top: 2px;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        opacity: 0;
        transform: scale(0);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    /* Active states per color */
    .mobile-nav-item.active .mobile-nav-dot {
        opacity: 1;
        transform: scale(1);
    }
    .mobile-nav-item.active .mobile-nav-icon {
        transform: translateY(-1px);
    }

    /* Home - Emerald */
    .mobile-nav-item.nav-home.active .mobile-nav-icon { color: #10b981; }
    .mobile-nav-item.nav-home.active .mobile-nav-label { color: #10b981; }
    .mobile-nav-item.nav-home .mobile-nav-dot { background: #10b981; }
    .dark .mobile-nav-item.nav-home.active .mobile-nav-icon { color: #34d399; }
    .dark .mobile-nav-item.nav-home.active .mobile-nav-label { color: #34d399; }

    /* Services - Amber */
    .mobile-nav-item.nav-services.active .mobile-nav-icon { color: #f59e0b; }
    .mobile-nav-item.nav-services.active .mobile-nav-label { color: #f59e0b; }
    .mobile-nav-item.nav-services .mobile-nav-dot { background: #f59e0b; }
    .dark .mobile-nav-item.nav-services.active .mobile-nav-icon { color: #fbbf24; }
    .dark .mobile-nav-item.nav-services.active .mobile-nav-label { color: #fbbf24; }

    /* Products - Orange */
    .mobile-nav-item.nav-products.active .mobile-nav-icon { color: #f97316; }
    .mobile-nav-item.nav-products.active .mobile-nav-label { color: #f97316; }
    .mobile-nav-item.nav-products .mobile-nav-dot { background: #f97316; }
    .dark .mobile-nav-item.nav-products.active .mobile-nav-icon { color: #fb923c; }
    .dark .mobile-nav-item.nav-products.active .mobile-nav-label { color: #fb923c; }

    /* Portfolio - Purple */
    .mobile-nav-item.nav-portfolio.active .mobile-nav-icon { color: #8b5cf6; }
    .mobile-nav-item.nav-portfolio.active .mobile-nav-label { color: #8b5cf6; }
    .mobile-nav-item.nav-portfolio .mobile-nav-dot { background: #8b5cf6; }
    .dark .mobile-nav-item.nav-portfolio.active .mobile-nav-icon { color: #a78bfa; }
    .dark .mobile-nav-item.nav-portfolio.active .mobile-nav-label { color: #a78bfa; }

    /* Support - Blue */
    .mobile-nav-item.nav-support.active .mobile-nav-icon { color: #3b82f6; }
    .mobile-nav-item.nav-support.active .mobile-nav-label { color: #3b82f6; }
    .mobile-nav-item.nav-support .mobile-nav-dot { background: #3b82f6; }
    .dark .mobile-nav-item.nav-support.active .mobile-nav-icon { color: #60a5fa; }
    .dark .mobile-nav-item.nav-support.active .mobile-nav-label { color: #60a5fa; }

    /* AI Chat button - special */
    .mobile-nav-ai {
        position: relative;
    }
    .mobile-nav-ai-icon {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: linear-gradient(135deg, #7c3aed, #4f46e5);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1px;
        box-shadow: 0 2px 8px rgba(124, 58, 237, 0.35);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .mobile-nav-item:active .mobile-nav-ai-icon {
        transform: scale(0.88);
    }
    .mobile-nav-ai-icon svg {
        width: 18px;
        height: 18px;
    }
    .mobile-nav-ai .mobile-nav-label {
        color: #7c3aed;
        font-weight: 600;
    }
    .dark .mobile-nav-ai .mobile-nav-label {
        color: #a78bfa;
    }

    /* AI active state (chat open) */
    .mobile-nav-ai.ai-chat-active .mobile-nav-ai-icon {
        box-shadow: 0 2px 12px rgba(124, 58, 237, 0.5);
        animation: mobile-ai-pulse 1.5s ease-in-out infinite;
    }
    @keyframes mobile-ai-pulse {
        0%, 100% { box-shadow: 0 2px 8px rgba(124, 58, 237, 0.35); }
        50% { box-shadow: 0 2px 16px rgba(124, 58, 237, 0.55); }
    }

    /* AI notification dot */
    .mobile-nav-ai-dot {
        position: absolute;
        top: 4px;
        right: calc(50% - 20px);
        width: 8px;
        height: 8px;
        background: #ef4444;
        border-radius: 50%;
        border: 1.5px solid white;
        display: none;
    }
    .dark .mobile-nav-ai-dot { border-color: #111827; }
    .mobile-nav-ai.has-notification .mobile-nav-ai-dot { display: block; }
</style>

<nav class="mobile-bottom-nav" id="mobileBottomNav" aria-label="Mobile navigation">
    <div class="mobile-nav-items">
        {{-- Home --}}
        <a href="/" class="mobile-nav-item nav-home {{ request()->is('/') ? 'active' : '' }}">
            <span class="mobile-nav-dot"></span>
            <span class="mobile-nav-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
            </span>
            <span class="mobile-nav-label">หน้าหลัก</span>
        </a>

        {{-- Services --}}
        <a href="/services" class="mobile-nav-item nav-services {{ request()->is('services*') ? 'active' : '' }}">
            <span class="mobile-nav-dot"></span>
            <span class="mobile-nav-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </span>
            <span class="mobile-nav-label">บริการ</span>
        </a>

        {{-- Products --}}
        <a href="/products" class="mobile-nav-item nav-products {{ request()->is('products*') ? 'active' : '' }}">
            <span class="mobile-nav-dot"></span>
            <span class="mobile-nav-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </span>
            <span class="mobile-nav-label">สินค้า</span>
        </a>

        {{-- AI Chat (center special button) --}}
        @php
            $aiChatEnabledNav = \App\Models\Setting::getValue('ai_chat_enabled', false);
        @endphp
        @if($aiChatEnabledNav)
        <button type="button" class="mobile-nav-item mobile-nav-ai" id="mobileNavAiBtn" onclick="window.AiChat && window.AiChat.toggle()">
            <span class="mobile-nav-ai-dot"></span>
            <span class="mobile-nav-ai-icon">
                <svg width="18" height="18" viewBox="0 0 40 40" fill="none">
                    <circle cx="20" cy="20" r="16" fill="white" opacity="0.95"/>
                    <ellipse cx="13.5" cy="16" rx="2" ry="2.2" fill="#4f46e5"/>
                    <ellipse cx="26.5" cy="16" rx="2" ry="2.2" fill="#4f46e5"/>
                    <circle cx="14.3" cy="15" r="0.7" fill="white"/>
                    <circle cx="27.3" cy="15" r="0.7" fill="white"/>
                    <path d="M 15 23 Q 20 26 25 23" stroke="#4f46e5" stroke-width="1.5" stroke-linecap="round" fill="none"/>
                    <line x1="20" y1="4" x2="20" y2="6" stroke="#7c3aed" stroke-width="1.2" stroke-linecap="round"/>
                    <circle cx="20" cy="3.5" r="1.2" fill="#7c3aed"/>
                </svg>
            </span>
            <span class="mobile-nav-label">AI Chat</span>
        </button>
        @else
        {{-- If AI chat disabled, show rental instead --}}
        <a href="/rental" class="mobile-nav-item nav-portfolio {{ request()->is('rental*') ? 'active' : '' }}">
            <span class="mobile-nav-dot"></span>
            <span class="mobile-nav-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </span>
            <span class="mobile-nav-label">เช่า</span>
        </a>
        @endif

        {{-- Portfolio --}}
        <a href="/portfolio" class="mobile-nav-item nav-portfolio {{ request()->is('portfolio*') ? 'active' : '' }}">
            <span class="mobile-nav-dot"></span>
            <span class="mobile-nav-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </span>
            <span class="mobile-nav-label">ผลงาน</span>
        </a>

        {{-- Support --}}
        <a href="/support" class="mobile-nav-item nav-support {{ request()->is('support*') ? 'active' : '' }}">
            <span class="mobile-nav-dot"></span>
            <span class="mobile-nav-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </span>
            <span class="mobile-nav-label">ติดต่อ</span>
        </a>
    </div>
</nav>

<script>
(function() {
    'use strict';

    // Sync AI Chat button active state
    const aiBtn = document.getElementById('mobileNavAiBtn');
    if (aiBtn && window.AiChat) {
        // Observe chat state changes
        const origToggle = window.AiChat.toggle;
        const origOpen = window.AiChat.open;
        const origClose = window.AiChat.close;

        function syncAiState(isOpen) {
            if (isOpen) {
                aiBtn.classList.add('ai-chat-active');
            } else {
                aiBtn.classList.remove('ai-chat-active');
            }
        }

        window.AiChat.toggle = function() {
            origToggle();
            // Check state after toggle
            const chatWindow = document.getElementById('aiChatWindow');
            syncAiState(chatWindow && chatWindow.classList.contains('open'));
        };
        window.AiChat.open = function() {
            origOpen();
            syncAiState(true);
        };
        window.AiChat.close = function() {
            origClose();
            syncAiState(false);
        };
    }

    // Show AI notification dot after delay
    if (aiBtn) {
        setTimeout(function() {
            const chatWindow = document.getElementById('aiChatWindow');
            if (!chatWindow || !chatWindow.classList.contains('open')) {
                aiBtn.classList.add('has-notification');
            }
        }, 5000);
    }
})();
</script>
