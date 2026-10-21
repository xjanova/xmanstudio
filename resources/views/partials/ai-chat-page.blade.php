{{--
    Shared by the chat widget (components/ai-chat-widget) and the 3D home's
    guide chat (partials/universe/guide → resources/js/universe/ui/Chat.js).

    window.xmanPageSnapshot() — what the visitor has on screen, sent with each
    question so the assistant knows the page they are asking from
    (App\Services\AiChat\CurrentPage). Headings, the ones in view right now,
    and the main text as the page prints it; never what is typed into a form.

    window.xmanChatClaimHistory() — the conversation kept in this tab
    (sessionStorage) belongs to whoever held it. A guest's chat carries on
    after they sign in; a member's is dropped when they sign out or someone
    else signs in, so the next person on a shared computer never reads it.
--}}
@once
@php
    $aiChatOwner = auth()->check()
        ? substr(hash_hmac('sha256', 'ai-chat-owner|' . auth()->id(), (string) config('app.key')), 0, 16)
        : 'guest';
@endphp
<script>
(function () {
    'use strict';

    var OWNER = @json($aiChatOwner);
    var HISTORY_KEY = 'ai_chat_history';
    var OWNER_KEY = 'ai_chat_owner';
    var SKIP = '#ai-chat-widget, #xu-chat, #xu-guide, script, style, noscript, template, svg, select, input, textarea, [aria-hidden="true"], [hidden], [data-ai-ignore]';
    var BLOCK = 'p, li, h1, h2, h3, h4, h5, h6, td, th, dt, dd, button, label, a, div, section, article';
    var TEXT_LIMIT = 3000;

    window.xmanChatClaimHistory = function () {
        try {
            var held = sessionStorage.getItem(OWNER_KEY);
            if (held && held !== 'guest' && held !== OWNER) {
                sessionStorage.removeItem(HISTORY_KEY);
            }
            sessionStorage.setItem(OWNER_KEY, OWNER);
        } catch (e) {
            // Storage blocked: nothing is kept, so there is nothing to hand over.
        }
    };

    function clean(text, max) {
        text = String(text || '').replace(/\s+/g, ' ').trim();
        return text.length > max ? text.slice(0, max) : text;
    }

    // strict: also hidden by opacity/visibility (a 3D-home panel that is not the current one)
    function shown(el, strict) {
        if (typeof el.checkVisibility === 'function') {
            return strict ? el.checkVisibility({ opacityProperty: true, visibilityProperty: true }) : el.checkVisibility();
        }
        return el.getClientRects().length > 0;
    }

    window.xmanPageSnapshot = function () {
        try {
            var root = document.querySelector('main') || document.body;
            var meta = document.querySelector('meta[name="description"]');
            var headings = [];
            var visible = [];
            var seen = {};
            var list = root.querySelectorAll('h1, h2, h3');

            for (var i = 0; i < list.length && headings.length < 20; i++) {
                var h = list[i];
                if (h.closest(SKIP)) continue;
                // innerText, not textContent: it keeps the line or " / " between the Thai and
                // English halves of a bilingual heading instead of gluing them together.
                var title = clean(h.innerText || h.textContent, 150);
                if (!title || seen[title]) continue;
                seen[title] = 1;
                headings.push(title);
                if (visible.length < 8 && shown(h, true)) {
                    var box = h.getBoundingClientRect();
                    if (box.bottom > 0 && box.top < window.innerHeight) visible.push(title);
                }
            }

            // One line per block. Inside a block the pieces are joined as the page wrote
            // them, spaces included, so "T<span>ping</span>" stays "Tping".
            var lines = [];
            var line = '';
            var size = 0;
            var lastBlock = null;
            var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
                acceptNode: function (node) {
                    var parent = node.parentElement;
                    if (!parent || parent.closest(SKIP) || !shown(parent, false)) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    return NodeFilter.FILTER_ACCEPT;
                }
            });
            var flush = function () {
                var done = clean(line, 400);
                if (done) {
                    lines.push(done);
                    size += done.length + 1;
                }
                line = '';
            };

            while (size < TEXT_LIMIT && walker.nextNode()) {
                var block = walker.currentNode.parentElement.closest(BLOCK);
                if (block !== lastBlock) flush();
                line += walker.currentNode.nodeValue;
                lastBlock = block;
            }
            flush();
            var text = lines.join('\n');

            var h1 = root.querySelector('h1');

            return {
                description: meta ? clean(meta.getAttribute('content'), 300) : '',
                h1: h1 ? clean(h1.innerText || h1.textContent, 200) : '',
                headings: headings,
                visible: visible,
                text: text.slice(0, TEXT_LIMIT)
            };
        } catch (e) {
            return null;
        }
    };
})();
</script>
@endonce
