{{--
    The guide — a gothic twin-tail girl who welcomes the visitor at the gate
    and flies the universe with them (resources/js/universe/ui/Guide.js).

    One transparent illustration per pose in public_html/artwork/universe/guide
    (drawn with ChatGPT). Poses other than the first load on demand: Guide.js
    copies data-src into src the first time it needs one.

    Her pictures are decorative: what she says at each stop is also said by the
    page itself. Clicking her opens the menu, which the menu button does for
    keyboard users.

    When the site's AI assistant is switched on (Setting ai_chat_enabled), her
    speech bubble carries an "ask me" field, and what is typed there opens a
    chat with that assistant (resources/js/universe/ui/Chat.js), in her name.
--}}
@php
    $xuGuide = asset('artwork/universe/guide');
    $xuChatOn = (bool) \App\Models\Setting::getValue('ai_chat_enabled', false);
    $xuBotName = \App\Models\Setting::getValue('ai_bot_name', 'AI Assistant');
    $xuGuideLines = [
        'core' => ['th' => 'ยินดีต้อนรับค่ะ! เลื่อนลงแล้วบินไปด้วยกันนะ', 'en' => 'Welcome aboard! Scroll down and fly with me'],
        'origin.0' => ['th' => 'ตัวเลขพวกนี้มาจากงานจริงทั้งหมดเลยนะ ✦', 'en' => 'Every one of these numbers is real work'],
        'origin.1' => ['th' => 'นี่แหละเหตุผลที่ลูกค้าไว้ใจเรา', 'en' => 'This is why clients trust us'],
        'services' => ['th' => 'อยากได้ระบบแบบไหน เลือกเลย ทีมเราพร้อมลุย!', 'en' => 'Pick a service — the team is ready!'],
        'products' => ['th' => 'ซอฟต์แวร์พร้อมใช้ เลื่อนต่อเพื่อดูทั้งหมดนะ', 'en' => 'Ready-made software — keep scrolling'],
        'platforms' => ['th' => 'แวะเที่ยวดาวแพลตฟอร์มของเราทีละดวงกัน!', 'en' => 'Let’s visit our platform planets one by one'],
        'stack' => ['th' => 'ทั้ง Claude, OpenAI, Gemini เราใช้เป็นทุกตัวเลย ✌', 'en' => 'Claude, OpenAI, Gemini: we work with all of them'],
        'reviews' => ['th' => 'ฟังเสียงจากลูกค้าจริงของเราสิ 💜', 'en' => 'Hear it from our customers'],
        'launch' => ['th' => 'ถึงประตูมิติแล้ว! พร้อมเริ่มโปรเจคของคุณหรือยัง?', 'en' => 'We made it to the gate! Ready to start your project?'],
        'control' => ['th' => 'ขอบคุณที่บินมาด้วยกันนะคะ แวะมาใหม่นะ ✦', 'en' => 'Thanks for flying with me — come back soon!'],
    ];
@endphp
<div id="xu-guide" class="xu-guide">
    <div class="xu-guide__trail" aria-hidden="true"></div>
    <div class="xu-guide__body" aria-hidden="true">
        <span class="xu-guide__aura"></span>
        @foreach(['welcome', 'fly', 'present', 'moon', 'cheer', 'bye'] as $pose)
            <img data-pose="{{ $pose }}" data-src="{{ $xuGuide }}/{{ $pose }}.webp" alt="" decoding="async" draggable="false">
        @endforeach
        <span class="xu-guide__hit"></span>
    </div>
    <button type="button" class="xu-guide__avatar" tabindex="-1" aria-hidden="true">
        <img src="{{ $xuGuide }}/face.webp" alt="" decoding="async" draggable="false">
    </button>
    <div class="xu-guide__bubble">
        <p class="xu-guide__line" aria-hidden="true"><b></b><small></small></p>
        @if($xuChatOn)
            <form class="xu-guide__ask" id="xu-guide-ask">
                <label class="xu-sr" for="xu-guide-ask-input">ถามผู้ช่วย AI / Ask the AI assistant</label>
                <input type="text" id="xu-guide-ask-input" maxlength="2000" autocomplete="off"
                       placeholder="พิมพ์ถามหนูได้เลย · Ask me">
                <button type="submit" aria-label="ส่ง / Send">@include('partials.nova-icon', ['name' => 'arrow'])</button>
            </form>
        @endif
    </div>
</div>

@if($xuChatOn)
    <section id="xu-chat" class="xu-chat" role="dialog" aria-labelledby="xu-chat-title" hidden>
        <header class="xu-chat__head">
            <img src="{{ $xuGuide }}/face.webp" alt="" class="xu-chat__avatar">
            <div class="xu-chat__who">
                <h2 id="xu-chat-title">{{ $xuBotName }}</h2>
                <small id="xu-chat-status">ออนไลน์ · Online</small>
            </div>
            <button type="button" class="xu-chat__close" data-xu-chat-close aria-label="ปิดแชท / Close chat">✕</button>
        </header>
        <div class="xu-chat__log" id="xu-chat-log" role="log" aria-live="polite"></div>
        <form class="xu-chat__form" id="xu-chat-form">
            <label class="xu-sr" for="xu-chat-input">ข้อความ / Message</label>
            <textarea id="xu-chat-input" rows="1" maxlength="2000" placeholder="พิมพ์ข้อความ... · Type a message"></textarea>
            <button type="submit" id="xu-chat-send" aria-label="ส่ง / Send">@include('partials.nova-icon', ['name' => 'arrow'])</button>
        </form>
        <p class="xu-chat__foot">ตอบโดย AI อาจคลาดเคลื่อนได้ · AI answers may be imperfect</p>
    </section>
@endif

<script type="application/json" id="xu-guide-lines">@json($xuGuideLines)</script>
