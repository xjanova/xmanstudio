{{--
    The guide — a gothic twin-tail girl who welcomes the visitor at the gate
    and flies the universe with them (resources/js/universe/ui/Guide.js).

    One transparent illustration per pose in public_html/artwork/universe/guide
    (drawn with ChatGPT). Poses other than the first load on demand: Guide.js
    copies data-src into src the first time it needs one.

    Decorative: everything she says is also said by the page itself, so the
    whole layer is hidden from assistive tech. Clicking her opens the menu,
    which the menu button does for keyboard users.
--}}
@php
    $xuGuide = asset('artwork/universe/guide');
    $xuGuideLines = [
        'core' => ['th' => 'ยินดีต้อนรับค่ะ! เลื่อนลงแล้วบินไปด้วยกันนะ', 'en' => 'Welcome aboard! Scroll down and fly with me'],
        'origin.0' => ['th' => 'ตัวเลขพวกนี้มาจากงานจริงทั้งหมดเลยนะ ✦', 'en' => 'Every one of these numbers is real work'],
        'origin.1' => ['th' => 'นี่แหละเหตุผลที่ลูกค้าไว้ใจเรา', 'en' => 'This is why clients trust us'],
        'services' => ['th' => 'อยากได้ระบบแบบไหน เลือกเลย ทีมเราพร้อมลุย!', 'en' => 'Pick a service — the team is ready!'],
        'products' => ['th' => 'ซอฟต์แวร์พร้อมใช้ เลื่อนต่อเพื่อดูทั้งหมดนะ', 'en' => 'Ready-made software — keep scrolling'],
        'platforms' => ['th' => 'แวะเที่ยวดาวแพลตฟอร์มของเราทีละดวงกัน!', 'en' => 'Let’s visit our platform planets one by one'],
        'stack' => ['th' => 'เครื่องมือระดับโลกที่เราใช้กันทุกวัน ✌', 'en' => 'World-class tools we use every day'],
        'reviews' => ['th' => 'ฟังเสียงจากลูกค้าจริงของเราสิ 💜', 'en' => 'Hear it from our customers'],
        'launch' => ['th' => 'ถึงประตูมิติแล้ว! พร้อมเริ่มโปรเจคของคุณหรือยัง?', 'en' => 'We made it to the gate! Ready to start your project?'],
        'control' => ['th' => 'ขอบคุณที่บินมาด้วยกันนะคะ แวะมาใหม่นะ ✦', 'en' => 'Thanks for flying with me — come back soon!'],
    ];
@endphp
<div id="xu-guide" class="xu-guide" aria-hidden="true">
    <div class="xu-guide__trail"></div>
    <div class="xu-guide__body">
        <span class="xu-guide__aura"></span>
        @foreach(['welcome', 'fly', 'present', 'moon', 'cheer', 'bye'] as $pose)
            <img data-pose="{{ $pose }}" data-src="{{ $xuGuide }}/{{ $pose }}.webp" alt="" decoding="async" draggable="false">
        @endforeach
        <span class="xu-guide__hit"></span>
    </div>
    <button type="button" class="xu-guide__avatar" tabindex="-1">
        <img src="{{ $xuGuide }}/face.webp" alt="" decoding="async" draggable="false">
    </button>
    <div class="xu-guide__bubble"><b></b><small></small></div>
</div>
<script type="application/json" id="xu-guide-lines">@json($xuGuideLines)</script>
