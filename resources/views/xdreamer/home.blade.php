@extends('layouts.xdreamer')

@section('title', 'X-DREAMER · ทอความฝันจากเส้นใยแห่งความคิด')
@section('meta_description', 'แพลตฟอร์ม AI generate สำหรับศิลปินและนักฝัน — สร้างภาพ วิดีโอ เสียง และฉาก 3 มิติจากประโยคเดียว')

@php
    $hueShift = 70;
    $promptSamples = [
        'ปราสาทลอยฟ้าที่ทอด้วยเส้นใยแสง, ออโรร่าไหลผ่าน, โทนเขียวหยก',
        'เส้นใยความคิดของมนุษย์ในวันฝันกลางวัน, สีม่วงนุ่ม, เรืองรองอ่อน',
        'ป่าลึกใต้น้ำที่มีเงาแสงสีฟ้าเต้นระบำ, ฟิล์มแนว cinematic',
        'เมืองแห่งความฝันที่สร้างจากเส้นด้ายจักรวาล, เขียวมรกต + ไวโอเลต',
    ];
    $bannerSlides = [
        ['id'=>'seedance','badge'=>'NEW MODEL','title'=>'Seedance 2.0','subtitle'=>'โมเดลวิดีโอรุ่นใหม่','desc'=>'สร้างวิดีโอ 10 วินาที 1080p จากข้อความ พร้อม motion ที่สมจริงและควบคุมกล้องได้','cta'=>'ลองใช้เลย','stats'=>[['k'=>'1080p','l'=>'ความละเอียด'],['k'=>'10s','l'=>'ความยาวสูงสุด'],['k'=>'60fps','l'=>'ลื่นไหล']],'hues'=>[200,260,300],'pattern'=>'waves'],
        ['id'=>'voxel','badge'=>'3D STUDIO','title'=>'Voxel Forge','subtitle'=>'ปั้นโลก 3D จากคำบรรยาย','desc'=>'ปราสาท ดินแดน หรือตัวละคร — สร้างโมเดล 3D พร้อม texture ในไม่กี่นาที','cta'=>'เข้าสู่ Voxel Forge','stats'=>[['k'=>'.GLB','l'=>'ส่งออกมาตรฐาน'],['k'=>'PBR','l'=>'Material'],['k'=>'4K','l'=>'Texture']],'hues'=>[160,180,220],'pattern'=>'voxel'],
        ['id'=>'loom-live','badge'=>'LIVE COLLAB','title'=>'Loom Live','subtitle'=>'ทอความฝันร่วมกัน · real-time','desc'=>'เชิญเพื่อนมาทอ prompt พร้อมกัน เห็น cursor, เห็น thread, แก้พร้อมกัน','cta'=>'เปิดห้องใหม่','stats'=>[['k'=>'8','l'=>'ผู้ร่วมงาน'],['k'=>'0ms','l'=>'sync latency'],['k'=>'∞','l'=>'ประวัติ versioning']],'hues'=>[280,320,200],'pattern'=>'threads'],
        ['id'=>'audio-muse','badge'=>'AUDIO · BETA','title'=>'Muse Audio v3','subtitle'=>'เสียงประกอบ · เพลง · บรรยากาศ','desc'=>'จากข้อความสู่ score ภาพยนตร์ · ambient · foley — มี stem แยกสำหรับตัดต่อ','cta'=>'ฟังตัวอย่าง','stats'=>[['k'=>'48kHz','l'=>'คุณภาพสตูดิโอ'],['k'=>'4 stems','l'=>'แยก track'],['k'=>'3 min','l'=>'ความยาว']],'hues'=>[30,340,280],'pattern'=>'audio'],
        ['id'=>'workflow','badge'=>'AUTOMATION','title'=>'Workflow Nodes','subtitle'=>'ร้อยโมเดลเป็น pipeline ของคุณ','desc'=>'Prompt → Image → Upscale → Video → Audio — ลาก connect ต่อเป็น workflow แบบ node-based','cta'=>'ดู workflows','stats'=>[['k'=>'40+','l'=>'Nodes พร้อมใช้'],['k'=>'JSON','l'=>'Export / Import'],['k'=>'API','l'=>'Trigger']],'hues'=>[220,180,260],'pattern'=>'nodes'],
    ];
    $features = [
        ['eyebrow'=>'01 · FABRIC','title'=>'เส้นใยเจตจำนง','desc'=>'ควบคุม prompt ผ่านเส้นใยที่ลากต่อเนื่อง — ปรับแสง, อารมณ์, และเรื่องราวได้แบบ real-time โดยไม่ต้องเริ่มใหม่','hue'=>160],
        ['eyebrow'=>'02 · LOOM','title'=>'ทอแบบข้ามสื่อ','desc'=>'เริ่มจากภาพแล้วเปลี่ยนเป็นวิดีโอ, เริ่มจากเสียงแล้วแปลงเป็นฉาก 3D โมเดลของเราไหลข้ามสื่อได้เป็นธรรมชาติ','hue'=>200],
        ['eyebrow'=>'03 · DREAM CITADEL','title'=>'ปราสาทแห่งแนวคิด','desc'=>'เก็บจินตนาการของคุณเป็นห้องสมุดที่มีชีวิต — แต่ละแนวคิดทอติดกันด้วยเส้นใยความสัมพันธ์ที่ AI มองเห็น','hue'=>270],
    ];
    $galleryItems = [
        ['title'=>'ปราสาทในหมอกจันทรา','author'=>'นภาลัย','hue'=>270,'ratio'=>'3/4','mode'=>'image'],
        ['title'=>'Dream Loop · 12s','author'=>'kairos','hue'=>200,'ratio'=>'1/1','mode'=>'video'],
        ['title'=>'ผืนป่าความคิด','author'=>'จิตรา','hue'=>160,'ratio'=>'3/4','mode'=>'image'],
        ['title'=>'Whisper of the Loom','author'=>'Theo','hue'=>290,'ratio'=>'4/5','mode'=>'audio'],
        ['title'=>'เส้นใยดวงดาว','author'=>'พิรุณ','hue'=>230,'ratio'=>'3/4','mode'=>'image'],
        ['title'=>'Citadel · 3D scan','author'=>'arc_ot','hue'=>180,'ratio'=>'1/1','mode'=>'3d'],
        ['title'=>'มโนทัศน์สีมรกต','author'=>'สิริกาญจน์','hue'=>150,'ratio'=>'4/5','mode'=>'image'],
        ['title'=>'Violet Thread Study','author'=>'nine','hue'=>280,'ratio'=>'3/4','mode'=>'image'],
    ];
    $steps = [
        ['n'=>'01','t'=>'ทอเส้นใยแรก','d'=>'เขียน prompt หรือ sketch — ระบบทอเป็นโครงแนวคิด','hue'=>160],
        ['n'=>'02','t'=>'เลือกผืนผ้า','d'=>'เลือกจาก 4 รูปแบบ — ภาพ, วิดีโอ, เสียง, หรือฉาก 3D','hue'=>200],
        ['n'=>'03','t'=>'ปรับผืนผ้า','d'=>'ลากเส้นใยเพื่อปรับอารมณ์ สี องค์ประกอบ ได้แบบ live','hue'=>240],
        ['n'=>'04','t'=>'ส่งต่อความฝัน','d'=>'Export 8K, แชร์ในชุมชน, หรือเก็บในปราสาทส่วนตัว','hue'=>280],
    ];
    // $packages comes from XdreamerController (live AIXMAN data with fallback)
    // — normalise into $tiers shape for the existing template loop
    $tiers = collect($packages ?? [])->map(fn ($p) => [
        'slug'  => $p['slug'] ?? null,
        'name'  => $p['name'],
        'price' => ((int) $p['price_thb']) === 0 ? 'ฟรี' : '฿'.number_format((int) $p['price_thb']),
        'note'  => $p['note'] ?? '/ เดือน',
        'feats' => $p['features'] ?? [],
        'hue'   => (int) ($p['hue'] ?? 220),
        'pop'   => (bool) ($p['is_popular'] ?? false),
    ])->all();
@endphp

@push('styles')
<style>
    body[data-xdr-page="home"] { /* enables hero canvas boost */ }
</style>
@endpush

@section('content')
<script>document.body.dataset.xdrPage = 'home';</script>

{{-- ================== HERO ================== --}}
<section style="position:relative;min-height:100vh;padding-top:120px;padding-bottom:80px;overflow:hidden;"
         x-data="xdrHeroTyper({{ json_encode($promptSamples, JSON_UNESCAPED_UNICODE) }})">
    {{-- Soft vignette --}}
    <div style="position:absolute;inset:0;pointer-events:none;
        background:radial-gradient(ellipse at 50% 40%, transparent 0%, rgba(3,6,18,0.5) 70%, rgba(3,6,18,0.95) 100%);"></div>

    <div class="rp-container" style="position:relative;max-width:1280px;margin:0 auto;padding:0 48px;">
        {{-- Floating logo mark --}}
        <div class="rp-hero-logo-wrap" style="
            position:absolute;top:-20px;right:48px;z-index:2;
            display:flex;flex-direction:column;align-items:center;gap:10px;
        ">
            <div style="position:relative;animation:floatY 6s ease-in-out infinite;">
                <div style="position:absolute;inset:-20px;border-radius:50%;
                    background:radial-gradient(circle, hsla({{ 270+$hueShift }},80%,55%,0.45), transparent 65%);
                    filter:blur(20px);"></div>
                <img class="rp-hero-logo" src="{{ asset('images/xdreamer/logo.png') }}" alt="X-DREAMER" style="
                    position:relative;width:180px;height:180px;border-radius:28px;object-fit:cover;
                    box-shadow:0 30px 60px -15px hsla({{ 270+$hueShift }},70%,40%,0.6), 0 0 0 1px rgba(255,255,255,0.08);
                ">
            </div>
            <div style="font-size:10px;letter-spacing:0.24em;color:#a5f3fc;text-transform:uppercase;">AI Video Generation</div>
        </div>

        {{-- Eyebrow --}}
        <div style="display:inline-flex;align-items:center;gap:10px;padding:6px 14px;border-radius:999px;
            background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);
            font-size:12px;letter-spacing:0.16em;color:#a5f3fc;text-transform:uppercase;">
            <span style="width:6px;height:6px;border-radius:999px;background:#34d399;box-shadow:0 0 8px #34d399;animation:pulse 2s infinite;"></span>
            GENERATIVE FABRIC · REAL-TIME
        </div>

        <h1 class="rp-hero-h1" style="
            margin-top:28px;font-size:clamp(56px, 8.5vw, 128px);font-weight:300;
            line-height:0.95;letter-spacing:-0.03em;color:#fff;text-wrap:balance;">
            ทอ<span class="xdr-italic-th" style="font-style:italic;font-weight:200;
                background:linear-gradient(120deg, hsl({{ 160+$hueShift }},80%,65%) 0%, hsl({{ 200+$hueShift }},85%,70%) 45%, hsl({{ 270+$hueShift }},80%,72%) 100%);
                -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;"> ความฝัน </span>
            <br>
            <span style="font-weight:700;">จากเส้นใย</span>
            <span style="font-weight:200;opacity:0.6;">แห่งความคิด</span>
        </h1>

        <p style="margin-top:28px;max-width:640px;font-size:19px;line-height:1.55;color:rgba(226,232,240,0.78);font-weight:300;">
            แพลตฟอร์ม AI generate สำหรับศิลปินและนักฝัน — สร้างภาพ วิดีโอ เสียง และฉาก 3 มิติจากประโยคเดียว
            ด้วยโมเดลที่เรียนรู้จากผืนผ้าความหมายของจักรวาลทั้งมวล
        </p>

        {{-- Prompt studio --}}
        <div style="margin-top:48px;max-width:820px;background:rgba(15,23,42,0.45);
            backdrop-filter:blur(20px) saturate(1.4);-webkit-backdrop-filter:blur(20px) saturate(1.4);
            border:1px solid rgba(255,255,255,0.08);border-radius:24px;padding:6px;
            box-shadow:0 40px 80px -20px rgba(0,0,0,0.6), inset 0 1px 0 rgba(255,255,255,0.08);">

            {{-- Mode tabs --}}
            <div class="rp-mode-tabs" style="display:flex;padding:10px 14px 4px;gap:4px;">
                @foreach([['id'=>'image','label'=>'Image','icon'=>'▧'],['id'=>'video','label'=>'Video','icon'=>'▶'],['id'=>'audio','label'=>'Audio','icon'=>'◎'],['id'=>'3d','label'=>'3D Scene','icon'=>'◈']] as $m)
                <button @click="mode='{{ $m['id'] }}'"
                    :style="mode === '{{ $m['id'] }}' ? 'background:linear-gradient(135deg, hsla({{ 160+$hueShift }},70%,55%,0.25), hsla({{ 270+$hueShift }},70%,60%,0.25));color:#fff;box-shadow:inset 0 0 0 1px rgba(255,255,255,0.1);' : 'background:transparent;color:rgba(226,232,240,0.55);'"
                    style="padding:8px 16px;border-radius:10px;border:none;font-size:13px;font-weight:500;
                        cursor:pointer;display:flex;align-items:center;gap:8px;">
                    <span style="font-size:13px;">{{ $m['icon'] }}</span>{{ $m['label'] }}
                </button>
                @endforeach
                <div style="margin-left:auto;font-size:11px;color:#64748b;padding:10px 8px;letter-spacing:0.08em;">
                    MODEL · loom-v4.2
                </div>
            </div>

            {{-- Prompt input row --}}
            <div class="rp-prompt-row" style="display:flex;align-items:center;gap:14px;
                padding:18px 20px 18px 22px;background:rgba(2,6,23,0.5);
                border-radius:20px;margin:4px;border:1px solid rgba(255,255,255,0.05);">
                <div :style="generating ? 'background:#fbbf24;box-shadow:0 0 12px #fbbf24;animation:pulse 0.8s infinite;' : 'background:#34d399;box-shadow:0 0 8px #34d399;animation:pulse 2s infinite;'"
                     style="width:10px;height:10px;border-radius:999px;flex-shrink:0;"></div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:10px;color:#64748b;letter-spacing:0.12em;margin-bottom:4px;">PROMPT</div>
                    <div style="font-size:17px;color:#f1f5f9;line-height:1.4;font-weight:400;min-height:24px;">
                        <span x-text="typed"></span><span style="display:inline-block;width:8px;height:20px;background:#a5f3fc;margin-left:3px;vertical-align:middle;animation:blink 1s step-end infinite;"></span>
                    </div>
                </div>
                <a href="{{ route('xdreamer.studio') }}" style="
                    background:linear-gradient(135deg, hsl({{ 160+$hueShift }},70%,50%), hsl({{ 270+$hueShift }},70%,60%));
                    color:#fff;border:none;padding:12px 22px;border-radius:12px;
                    font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;
                    display:flex;align-items:center;gap:8px;
                    box-shadow:0 10px 24px -8px hsl({{ 270+$hueShift }},70%,50%);flex-shrink:0;">
                    <span x-text="generating ? 'กำลังทอ...' : 'ทอเลย'"></span> <span style="font-size:16px;">→</span>
                </a>
            </div>

            {{-- Generation tray (4 frames) --}}
            <div class="rp-gen-tray" style="padding:10px 10px 12px;display:grid;grid-template-columns:repeat(4,1fr);gap:8px;">
                @for($i = 0; $i < 4; $i++)
                @php
                    $hue1 = (140 + $i * 35 + $hueShift) % 360;
                    $hue2 = ($hue1 + 60) % 360;
                @endphp
                <div x-data="xdrGenFrame({{ $i }}, {{ $hue1 }}, {{ $hue2 }})"
                     style="aspect-ratio:1/1.15;border-radius:14px;position:relative;overflow:hidden;
                        background:linear-gradient(135deg, hsl({{ $hue1 }},60%,12%), hsl({{ $hue2 }},60%,8%));
                        border:1px solid rgba(255,255,255,0.06);">
                    <svg width="100%" height="100%" style="position:absolute;inset:0;" preserveAspectRatio="none" viewBox="0 0 100 115">
                        <defs>
                            <linearGradient id="hg{{ $i }}" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="hsl({{ $hue1 }}, 85%, 65%)" stop-opacity="0.9"/>
                                <stop offset="100%" stop-color="hsl({{ $hue2 }}, 85%, 70%)" stop-opacity="0.9"/>
                            </linearGradient>
                        </defs>
                        @for($j = 0; $j < 12; $j++)
                            @php
                                $cx = 50 + sin($j) * 30;
                                $cy = 60 + $j * 2;
                                $startX = -5 + $j * 9;
                                $startY = 115 + $j * 3;
                                $endX = 105 - $j * 6;
                                $endY = -5 + $j * 4;
                                $sw = 0.4 + ($j % 3) * 0.3;
                            @endphp
                            <path :style="{'opacity': progress > {{ ($j / 12) * 100 }} ? 0.7 : 0, 'transition': 'opacity 0.4s ease'}"
                                d="M{{ $startX }} {{ $startY }} Q{{ $cx }} {{ $cy }} {{ $endX }} {{ $endY }}"
                                stroke="url(#hg{{ $i }})" stroke-width="{{ $sw }}" fill="none"/>
                        @endfor
                    </svg>
                    <div style="position:absolute;left:10px;right:10px;bottom:10px;height:2px;border-radius:999px;background:rgba(255,255,255,0.08);overflow:hidden;">
                        <div :style="`width:${progress}%`" style="height:100%;background:linear-gradient(90deg, hsl({{ $hue1 }},85%,60%), hsl({{ $hue2 }},85%,70%));transition:width 0.1s linear;box-shadow:0 0 8px hsl({{ $hue1 }},85%,60%);"></div>
                    </div>
                    <div style="position:absolute;top:8px;left:10px;font-size:9px;color:rgba(255,255,255,0.5);letter-spacing:0.1em;font-family:ui-monospace, Menlo, monospace;">
                        #{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }} · <span x-text="progress < 100 ? 'weaving' : 'ready'"></span>
                    </div>
                </div>
                @endfor
            </div>
        </div>

        {{-- Stat strip --}}
        <div style="display:flex;gap:48px;margin-top:56px;flex-wrap:wrap;">
            @foreach([['v'=>'2.4M+','l'=>'ความฝันถูกทอทุกวัน'],['v'=>'48','l'=>'โมเดลเฉพาะทาง'],['v'=>'<2s','l'=>'เวลาสร้างเฉลี่ย'],['v'=>'99.2%','l'=>'uptime ปีที่ผ่านมา']] as $i => $s)
            <div>
                <div style="font-size:36px;font-weight:300;color:#fff;
                    background:linear-gradient(180deg, #fff 0%, hsl({{ 180+$hueShift+$i*30 }},70%,75%) 100%);
                    -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">{{ $s['v'] }}</div>
                <div style="font-size:12px;color:#64748b;margin-top:4px;letter-spacing:0.05em;">{{ $s['l'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================== BANNER SLIDER ================== --}}
<section style="position:relative;padding:0 0 80px;"
         x-data='xdrBannerSlider({!! json_encode($bannerSlides, JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}, {{ $hueShift }})'>
    <div class="rp-container" style="max-width:1280px;margin:0 auto;padding:0 48px;">
        <div class="rp-banner"
             @mouseenter="paused=true" @mouseleave="paused=false"
             :style="`background:linear-gradient(135deg, hsl(${h1},65%,10%) 0%, hsl(${h2},65%,6%) 100%);box-shadow:0 40px 80px -30px hsla(${h1},70%,30%,0.6), 0 0 0 1px rgba(255,255,255,0.04);`"
             style="position:relative;height:420px;border-radius:28px;overflow:hidden;border:1px solid rgba(255,255,255,0.08);">

            {{-- Animated canvas pattern background (replaces SVG x-for which doesn't work in SVG namespace) --}}
            <canvas id="xdr-banner-canvas" data-banner-pattern
                    style="position:absolute;inset:0;width:100%;height:100%;opacity:0.85;display:block;"></canvas>

            {{-- Darken overlay --}}
            <div style="position:absolute;inset:0;background:linear-gradient(90deg, rgba(3,6,18,0.85) 0%, rgba(3,6,18,0.55) 50%, rgba(3,6,18,0.2) 100%);"></div>

            {{-- Content --}}
            <div class="rp-banner-content"
                 :key="slide.id"
                 style="position:relative;height:100%;padding:56px 64px;display:grid;grid-template-columns:1.4fr 1fr;gap:32px;align-items:center;animation:bannerIn 600ms cubic-bezier(0.4,0,0.2,1);">
                <div>
                    <div :style="`background:hsla(${h3},80%,60%,0.15);border:1px solid hsla(${h3},80%,60%,0.35);color:hsl(${h3},90%,80%);`"
                         style="display:inline-flex;align-items:center;gap:8px;padding:5px 12px;border-radius:999px;
                                font-size:10px;letter-spacing:0.22em;text-transform:uppercase;font-weight:600;margin-bottom:18px;">
                        <span :style="`background:hsl(${h3},90%,65%);box-shadow:0 0 10px hsl(${h3},90%,70%);`" style="width:6px;height:6px;border-radius:50%;"></span>
                        <span x-text="slide.badge"></span>
                    </div>
                    <div style="font-size:12px;letter-spacing:0.14em;color:#a5f3fc;text-transform:uppercase;margin-bottom:8px;" x-text="slide.subtitle"></div>
                    <h2 style="font-size:clamp(36px, 4.8vw, 64px);font-weight:200;color:#fff;letter-spacing:-0.02em;line-height:1.05;margin:0;font-family:Inter,sans-serif;">
                        <span class="xdr-italic-th"
                              :style="`background:linear-gradient(120deg, hsl(${h1},80%,75%) 0%, hsl(${h2},85%,72%) 50%, hsl(${h3},80%,78%) 100%); -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text; padding-bottom:0.1em;`"
                              x-text="slide.title"></span>
                    </h2>
                    <p style="margin-top:18px;font-size:16px;color:rgba(203,213,225,0.8);font-weight:300;line-height:1.55;max-width:520px;" x-text="slide.desc"></p>
                    <div style="margin-top:28px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                        <button :style="`background:linear-gradient(135deg, hsl(${h1},75%,55%) 0%, hsl(${h2},75%,55%) 50%, hsl(${h3},75%,55%) 100%);box-shadow:0 12px 30px -10px hsla(${h2},80%,50%,0.7);`"
                                style="color:#fff;border:none;padding:12px 22px;border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;">
                            <span x-text="slide.cta"></span> →
                        </button>
                        <button style="background:rgba(255,255,255,0.06);color:#e2e8f0;border:1px solid rgba(255,255,255,0.15);padding:11px 20px;border-radius:12px;font-size:14px;font-weight:500;cursor:pointer;">เรียนรู้เพิ่มเติม</button>
                    </div>
                </div>
                <div class="rp-banner-stats" style="display:flex;flex-direction:column;gap:14px;padding:24px;border-radius:18px;
                    background:rgba(3,6,18,0.45);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);
                    border:1px solid rgba(255,255,255,0.08);">
                    <div style="font-size:10px;letter-spacing:0.2em;color:#94a3b8;text-transform:uppercase;">· ข้อมูลจำเพาะ</div>
                    <template x-for="(s,i) in slide.stats" :key="i">
                        <div :style="i < slide.stats.length-1 ? 'padding-bottom:12px;border-bottom:1px solid rgba(255,255,255,0.06);' : ''"
                             style="display:flex;align-items:baseline;justify-content:space-between;">
                            <div style="font-size:13px;color:rgba(203,213,225,0.75);" x-text="s.l"></div>
                            <div style="font-size:22px;font-weight:300;color:#fff;font-family:Inter;letter-spacing:-0.01em;" x-text="s.k"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Progress bar --}}
            <div style="position:absolute;bottom:0;left:0;right:0;height:3px;background:rgba(255,255,255,0.06);">
                <div :style="`width:${progress*100}%;background:linear-gradient(90deg, hsl(${h1},85%,65%), hsl(${h3},85%,70%));box-shadow:0 0 12px hsl(${h2},80%,60%);`"
                     style="height:100%;transition:width 60ms linear;"></div>
            </div>
        </div>

        {{-- Dots --}}
        <div style="display:flex;justify-content:center;gap:10px;margin-top:20px;align-items:center;">
            <template x-for="(s,i) in slides" :key="s.id">
                <button @click="goTo(i)" :aria-label="'Go to slide '+(i+1)"
                        :style="i===idx ? `width:28px;background:linear-gradient(90deg, hsl(${h1},85%,65%), hsl(${h3},85%,70%));` : 'width:8px;background:rgba(255,255,255,0.18);'"
                        style="height:8px;border-radius:999px;border:none;cursor:pointer;padding:0;
                            transition:width 300ms cubic-bezier(0.4,0,0.2,1), background 300ms;"></button>
            </template>
            <div style="font-size:11px;color:#64748b;margin-left:12px;letter-spacing:0.1em;"
                 x-text="String(idx+1).padStart(2,'0')+' / '+String(slides.length).padStart(2,'0')"></div>
        </div>
    </div>
</section>

{{-- ================== FEATURES ================== --}}
<section class="rp-section" style="position:relative;padding:120px 48px;max-width:1400px;margin:0 auto;">
    <div style="margin-bottom:72px;max-width:720px;">
        <div style="font-size:12px;letter-spacing:0.16em;color:#a5f3fc;text-transform:uppercase;margin-bottom:14px;">· สามหลักการ</div>
        <h2 class="rp-h2" style="font-size:clamp(40px, 5vw, 64px);font-weight:300;color:#fff;letter-spacing:-0.02em;line-height:1.05;">
            เครื่องทอ<span class="xdr-italic-th" style="font-style:italic;font-weight:200;color:#6ee7b7;"> ที่เข้าใจ</span><br>
            ว่าจินตนาการไม่ใช่เส้นตรง
        </h2>
    </div>
    <div class="rp-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:24px;">
        @foreach($features as $f)
        @php $h1 = ($f['hue'] + $hueShift) % 360; @endphp
        <div onmouseenter="this.style.borderColor='hsla({{ $h1 }},70%,60%,0.5)'" onmouseleave="this.style.borderColor='rgba(255,255,255,0.08)'"
             style="position:relative;padding:32px;border-radius:22px;background:rgba(15,23,42,0.45);
                border:1px solid rgba(255,255,255,0.08);backdrop-filter:blur(18px);overflow:hidden;
                transition:all 400ms;cursor:default;">
            <svg width="100%" height="120" style="position:absolute;top:-20px;left:0;right:0;opacity:0.5;" viewBox="0 0 400 120" preserveAspectRatio="none">
                @for($j = 0; $j < 14; $j++)
                @php $hh = $h1 + $j * 4; $sw = 0.5 + ($j % 3) * 0.3; $cx = 150 + sin($j) * 50; $cy = 40 + $j * 3; $sx = -20 + $j * 30; $ex = 420 - $j * 28; @endphp
                <path d="M{{ $sx }} 130 Q{{ $cx }} {{ $cy }} {{ $ex }} -10" stroke="hsl({{ $hh }}, 80%, 65%)" stroke-width="{{ $sw }}" fill="none" opacity="0.5"/>
                @endfor
            </svg>
            <div style="position:relative;margin-top:90px;">
                <div style="font-size:11px;letter-spacing:0.16em;color:hsl({{ $h1 }}, 70%, 70%);margin-bottom:14px;">{{ $f['eyebrow'] }}</div>
                <h3 style="font-size:28px;font-weight:500;color:#fff;margin-bottom:14px;letter-spacing:-0.01em;">{{ $f['title'] }}</h3>
                <p style="font-size:15px;line-height:1.6;color:rgba(203,213,225,0.75);font-weight:300;">{{ $f['desc'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
</section>

{{-- ================== GALLERY ================== --}}
<section class="rp-section" style="position:relative;padding:140px 48px;max-width:1400px;margin:0 auto;">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:56px;flex-wrap:wrap;gap:24px;">
        <div>
            <div style="font-size:12px;letter-spacing:0.16em;color:#a5f3fc;text-transform:uppercase;margin-bottom:14px;">· ผืนผ้าที่ถูกทอในวันนี้</div>
            <h2 class="rp-h2" style="font-size:clamp(40px, 5vw, 64px);font-weight:300;color:#fff;letter-spacing:-0.02em;line-height:1.05;max-width:720px;">
                ความฝันที่ <span class="xdr-italic-th" style="font-style:italic;font-weight:200;color:#c4b5fd;">ชุมชนของเรา</span><br>
                ทอขึ้นในช่วง 24 ชั่วโมง
            </h2>
        </div>
        <div class="rp-filter-row" style="display:flex;gap:10px;">
            @foreach(['ทั้งหมด','Image','Video','Audio','3D'] as $i => $t)
            <button style="padding:8px 16px;border-radius:999px;background:{{ $i === 0 ? 'rgba(255,255,255,0.1)' : 'transparent' }};color:{{ $i === 0 ? '#fff' : 'rgba(226,232,240,0.55)' }};border:1px solid rgba(255,255,255,0.1);font-size:13px;cursor:pointer;">{{ $t }}</button>
            @endforeach
        </div>
    </div>
    <div class="rp-grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;">
        @foreach($galleryItems as $item)
        @php $h1 = ($item['hue'] + $hueShift) % 360; $h2 = ($h1 + 50) % 360; @endphp
        <div onmouseenter="this.style.transform='translateY(-4px)';this.style.boxShadow='0 25px 50px -15px hsla({{ $h1 }},80%,55%,0.4)'"
             onmouseleave="this.style.transform='';this.style.boxShadow='0 10px 30px -10px rgba(0,0,0,0.6)'"
             style="position:relative;border-radius:18px;overflow:hidden;aspect-ratio:{{ $item['ratio'] }};
                background:linear-gradient(135deg, hsl({{ $h1 }}, 65%, 14%), hsl({{ $h2 }}, 65%, 8%));
                border:1px solid rgba(255,255,255,0.06);cursor:pointer;
                transition:transform 400ms cubic-bezier(0.4,0,0.2,1), box-shadow 400ms;
                box-shadow:0 10px 30px -10px rgba(0,0,0,0.6);">
            <svg width="100%" height="100%" style="position:absolute;inset:0;" preserveAspectRatio="none" viewBox="0 0 100 100">
                <defs>
                    <linearGradient id="gg{{ $item['hue'] }}" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0%" stop-color="hsl({{ $h1 }}, 85%, 65%)" stop-opacity="0.9"/>
                        <stop offset="100%" stop-color="hsl({{ $h2 }}, 85%, 70%)" stop-opacity="0.9"/>
                    </linearGradient>
                </defs>
                @for($i = 0; $i < 18; $i++)
                @php
                    $sx = -5 + ($i * 7) % 110;
                    $sy = 110 + sin($i) * 5;
                    $cx = 30 + cos($i * 1.3) * 40;
                    $cy = 50 + sin($i * 0.7) * 30;
                    $ex = 105 - ($i * 6) % 110;
                    $ey = -5 + cos($i) * 5;
                    $sw = 0.3 + ($i % 4) * 0.25;
                    $op = 0.35 + ($i % 3) * 0.2;
                @endphp
                <path d="M{{ $sx }} {{ $sy }} Q{{ $cx }} {{ $cy }} {{ $ex }} {{ $ey }}" stroke="url(#gg{{ $item['hue'] }})" stroke-width="{{ $sw }}" fill="none" opacity="{{ $op }}"/>
                @endfor
            </svg>
            <div style="position:absolute;inset:0;background:linear-gradient(180deg, transparent 40%, rgba(0,0,0,0.7) 100%);"></div>
            <div style="position:absolute;top:12px;left:14px;display:flex;gap:6px;">
                <span style="font-size:10px;padding:3px 8px;border-radius:999px;background:rgba(255,255,255,0.12);backdrop-filter:blur(8px);color:#fff;letter-spacing:0.1em;text-transform:uppercase;">{{ $item['mode'] }}</span>
            </div>
            <div style="position:absolute;left:16px;right:16px;bottom:14px;color:#fff;">
                <div style="font-size:15px;font-weight:500;line-height:1.2;">{{ $item['title'] }}</div>
                <div style="font-size:11px;color:rgba(255,255,255,0.65);margin-top:4px;letter-spacing:0.02em;">@‌{{ $item['author'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
</section>

{{-- ================== HOW IT WORKS ================== --}}
<section class="rp-section" style="padding:120px 48px;max-width:1400px;margin:0 auto;position:relative;">
    <div style="margin-bottom:64px;display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:24px;">
        <div>
            <div style="font-size:12px;letter-spacing:0.16em;color:#a5f3fc;text-transform:uppercase;margin-bottom:14px;">· วิธีการทำงาน</div>
            <h2 class="rp-h2" style="font-size:clamp(40px, 5vw, 64px);font-weight:300;color:#fff;letter-spacing:-0.02em;line-height:1.05;max-width:720px;">
                จากความคิด<span class="xdr-italic-th" style="font-style:italic;font-weight:200;color:#a5b4fc;">...สู่ปราสาท</span><br>
                ในสี่จังหวะ
            </h2>
        </div>
    </div>
    <div style="position:relative;">
        <svg width="100%" height="4" style="position:absolute;top:22px;left:0;right:0;" preserveAspectRatio="none" viewBox="0 0 100 4">
            <line x1="0" y1="2" x2="100" y2="2" stroke="url(#thread-grad)" stroke-width="0.5" stroke-dasharray="0.5 1"/>
            <defs>
                <linearGradient id="thread-grad" x1="0" x2="1">
                    <stop offset="0%" stop-color="hsl({{ 160+$hueShift }}, 80%, 65%)"/>
                    <stop offset="50%" stop-color="hsl({{ 220+$hueShift }}, 80%, 70%)"/>
                    <stop offset="100%" stop-color="hsl({{ 285+$hueShift }}, 80%, 70%)"/>
                </linearGradient>
            </defs>
        </svg>
        <div class="rp-grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:32px;">
            @foreach($steps as $s)
            @php $h = ($s['hue'] + $hueShift) % 360; @endphp
            <div style="position:relative;">
                <div style="width:44px;height:44px;border-radius:999px;
                    background:radial-gradient(circle at 30% 30%, hsl({{ $h }}, 80%, 65%), hsl({{ $h+30 }}, 70%, 45%));
                    box-shadow:0 0 24px hsla({{ $h }}, 80%, 60%, 0.6), inset 0 0 8px rgba(255,255,255,0.3);
                    display:grid;place-items:center;font-size:14px;font-weight:700;color:#fff;
                    margin-bottom:24px;border:1px solid rgba(255,255,255,0.2);">{{ $s['n'] }}</div>
                <h3 style="font-size:22px;font-weight:500;color:#fff;margin-bottom:10px;letter-spacing:-0.01em;">{{ $s['t'] }}</h3>
                <p style="font-size:14px;line-height:1.6;color:rgba(203,213,225,0.7);font-weight:300;">{{ $s['d'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================== PRICING ================== --}}
<section class="rp-section" style="padding:120px 48px;max-width:1400px;margin:0 auto;">
    <div style="text-align:center;margin-bottom:64px;">
        <div style="font-size:12px;letter-spacing:0.16em;color:#a5f3fc;text-transform:uppercase;margin-bottom:14px;">· แผนการใช้งาน</div>
        <h2 class="rp-h2" style="font-size:clamp(40px, 5vw, 64px);font-weight:300;color:#fff;letter-spacing:-0.02em;line-height:1.05;">
            เริ่มฟรี — <span class="xdr-italic-th" style="font-style:italic;font-weight:200;color:#c4b5fd;">จ่ายเมื่อความฝันใหญ่ขึ้น</span>
        </h2>
    </div>
    <div class="rp-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;max-width:1100px;margin:0 auto;">
        @foreach($tiers as $t)
        @php $h = ($t['hue'] + $hueShift) % 360; @endphp
        <div style="padding:36px;border-radius:22px;position:relative;
            background:{{ $t['pop'] ? "linear-gradient(160deg, hsla($h,60%,20%,0.65), hsla(".($h+40).",60%,12%,0.65))" : 'rgba(15,23,42,0.45)' }};
            border:{{ $t['pop'] ? "1px solid hsla($h, 70%, 55%, 0.5)" : '1px solid rgba(255,255,255,0.08)' }};
            backdrop-filter:blur(18px);
            box-shadow:{{ $t['pop'] ? "0 30px 60px -20px hsla($h, 70%, 50%, 0.35)" : 'none' }};">
            @if($t['pop'])
                <div style="position:absolute;top:-12px;left:24px;padding:4px 12px;border-radius:999px;
                    background:linear-gradient(90deg, hsl({{ $h }}, 80%, 60%), hsl({{ $h+40 }}, 80%, 65%));
                    font-size:11px;font-weight:600;color:#fff;letter-spacing:0.08em;">ยอดนิยม</div>
            @endif
            <div style="font-size:14px;color:#a5f3fc;letter-spacing:0.08em;margin-bottom:18px;">{{ $t['name'] }}</div>
            <div style="display:flex;align-items:baseline;gap:6px;margin-bottom:28px;">
                <div style="font-size:44px;font-weight:300;color:#fff;letter-spacing:-0.02em;">{{ $t['price'] }}</div>
                <div style="font-size:14px;color:#64748b;">{{ $t['note'] }}</div>
            </div>
            <ul style="list-style:none;padding:0;margin:0 0 32px;">
                @foreach($t['feats'] as $f)
                <li style="font-size:14px;color:rgba(226,232,240,0.8);margin-bottom:10px;display:flex;gap:10px;font-weight:300;">
                    <span style="color:hsl({{ $h }}, 80%, 70%);flex-shrink:0;">✦</span> {{ $f }}
                </li>
                @endforeach
            </ul>
            @php
                // Free tier → signup; paid tier → checkout (auth-gated, redirects to login if needed)
                $isFree = ! $t['slug'] || $t['price'] === 'ฟรี';
                $tierUrl = $isFree
                    ? route('xdreamer.signup')
                    : route('xdreamer.checkout.show', $t['slug']);
                $tierLabel = $isFree ? 'เริ่มฟรี' : ($t['pop'] ? 'เริ่มทอเลย' : 'เลือกแผนนี้');
            @endphp
            <a href="{{ $tierUrl }}"
               style="display:block;text-align:center;width:100%;padding:14px;border-radius:12px;
                background:{{ $t['pop'] ? "linear-gradient(135deg, hsl($h,70%,50%), hsl(".($h+40).",70%,60%))" : 'rgba(255,255,255,0.05)' }};
                color:#fff;border:{{ $t['pop'] ? 'none' : '1px solid rgba(255,255,255,0.15)' }};
                font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;">
                {{ $tierLabel }}
            </a>
        </div>
        @endforeach
    </div>
</section>

{{-- ================== FOOTER CTA ================== --}}
<section style="padding:140px 48px 80px;position:relative;text-align:center;">
    <div style="position:absolute;top:20%;left:50%;transform:translateX(-50%);
        width:600px;height:600px;border-radius:50%;
        background:radial-gradient(circle, hsla({{ 220+$hueShift }}, 80%, 50%, 0.25), transparent 60%);
        filter:blur(40px);pointer-events:none;"></div>
    <div style="position:relative;max-width:900px;margin:0 auto;">
        <h2 style="font-size:clamp(48px, 7vw, 96px);font-weight:200;color:#fff;letter-spacing:-0.03em;line-height:1;">
            เริ่มทอความฝัน<br>
            <span class="xdr-italic-th" style="font-style:italic;background:linear-gradient(120deg, hsl({{ 160+$hueShift }},80%,70%), hsl({{ 280+$hueShift }},80%,75%));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">ของคุณวันนี้</span>
        </h2>
        <p style="margin-top:28px;font-size:18px;color:rgba(203,213,225,0.75);font-weight:300;">
            ฟรี 50 งานทุกเดือน · ไม่ต้องใช้บัตรเครดิต · เริ่มได้ภายใน 30 วินาที
        </p>
        <div style="margin-top:40px;display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="{{ route('xdreamer.signup') }}" style="
                padding:16px 32px;border-radius:14px;
                background:linear-gradient(135deg, hsl({{ 160+$hueShift }}, 70%, 50%) 0%, hsl({{ 220+$hueShift }}, 70%, 55%) 50%, hsl({{ 280+$hueShift }}, 70%, 60%) 100%);
                color:#fff;border:none;font-size:15px;font-weight:600;cursor:pointer;text-decoration:none;
                box-shadow:0 20px 40px -10px hsla({{ 220+$hueShift }}, 70%, 50%, 0.6);">สร้างบัญชีฟรี →</a>
            <a href="{{ route('xdreamer.gallery') }}" style="padding:16px 28px;border-radius:14px;
                background:rgba(255,255,255,0.05);color:#fff;border:1px solid rgba(255,255,255,0.15);
                font-size:15px;font-weight:500;cursor:pointer;text-decoration:none;">ดู Gallery ทั้งหมด</a>
        </div>
    </div>
    <div style="margin-top:120px;padding-top:40px;border-top:1px solid rgba(255,255,255,0.06);
        display:flex;justify-content:space-between;color:#64748b;font-size:13px;
        max-width:1300px;margin-left:auto;margin-right:auto;flex-wrap:wrap;gap:20px;">
        <div>© {{ date('Y') }} X-DREAMER · ทอด้วย ♥ ในเชียงใหม่ · powered by <a href="/" style="color:inherit;">XMAN STUDIO</a></div>
        <div style="display:flex;gap:24px;">
            <a href="{{ route('terms') }}" style="color:inherit;text-decoration:none;">Terms</a>
            <a href="{{ route('privacy') }}" style="color:inherit;text-decoration:none;">Privacy</a>
            <a href="{{ route('xdreamer.docs') }}" style="color:inherit;text-decoration:none;">Docs</a>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script>
    // Hero prompt typer
    function xdrHeroTyper(samples){
        return {
            samples: samples,
            promptIdx: 0,
            typed: '',
            generating: false,
            mode: 'image',
            init(){
                const cycle = () => {
                    const target = this.samples[this.promptIdx];
                    this.typed = '';
                    let i = 0;
                    const id = setInterval(() => {
                        i++;
                        this.typed = target.slice(0, i);
                        if (i >= target.length) clearInterval(id);
                    }, 28);
                };
                cycle();
                setInterval(() => {
                    this.promptIdx = (this.promptIdx + 1) % this.samples.length;
                    cycle();
                }, 5200);
                setInterval(() => {
                    this.generating = true;
                    setTimeout(() => this.generating = false, 1800);
                }, 5200);
            },
        };
    }

    // Gen frame progress
    function xdrGenFrame(index, hue1, hue2){
        return {
            progress: 40 + index * 15,
            init(){
                this.progress = 40 + index * 15;
                window.addEventListener('xdr:generate', () => {
                    this.progress = 0;
                    const start = Date.now();
                    const delay = index * 180;
                    const id = setInterval(() => {
                        const t = Date.now() - start - delay;
                        if (t < 0) return;
                        const p = Math.min(100, (t / 1400) * 100);
                        this.progress = p;
                        if (p >= 100) clearInterval(id);
                    }, 40);
                });
            },
        };
    }

    // Banner slider — Alpine reactive state + canvas-driven pattern animation
    function xdrBannerSlider(slides, hueShift){
        return {
            slides, idx: 0, t: 0, paused: false, progress: 0,
            SLIDE_MS: 6000,
            get slide(){ return this.slides[this.idx]; },
            get h1(){ return (this.slide.hues[0] + hueShift) % 360; },
            get h2(){ return (this.slide.hues[1] + hueShift) % 360; },
            get h3(){ return (this.slide.hues[2] + hueShift) % 360; },
            init(){
                this.startCanvas();
                this.startTimer();
            },
            startCanvas(){
                const canvas = document.getElementById('xdr-banner-canvas');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                if (!ctx) return;
                const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
                let w = 0, h = 0, lastFrame = 0;
                const frameMs = 1000 / 30; // 30fps cap
                let visible = true;
                const io = new IntersectionObserver(es => { visible = es[0].isIntersecting; }, { threshold: 0 });
                io.observe(canvas);
                const resize = () => {
                    const rect = canvas.getBoundingClientRect();
                    w = rect.width; h = rect.height;
                    if (w === 0 || h === 0) return;
                    canvas.width = w * dpr; canvas.height = h * dpr;
                    ctx.setTransform(dpr,0,0,dpr,0,0);
                };
                resize();
                window.addEventListener('resize', resize);

                const draw = (now) => {
                    requestAnimationFrame(draw);
                    if (!visible) return;
                    if (now - lastFrame < frameMs) return;
                    lastFrame = now;
                    if (!w || !h) { resize(); return; }
                    if (!this.paused) this.t += 0.4;
                    const t = this.t, hues = this.slide.hues, hs = hueShift;
                    const h1 = (hues[0] + hs) % 360;
                    const h2 = (hues[1] + hs) % 360;
                    const h3 = (hues[2] + hs) % 360;
                    // Background
                    ctx.clearRect(0, 0, w, h);
                    const bg = ctx.createLinearGradient(0, 0, w, h);
                    bg.addColorStop(0, `hsl(${h1},65%,10%)`);
                    bg.addColorStop(1, `hsl(${h2},65%,6%)`);
                    ctx.fillStyle = bg;
                    ctx.fillRect(0, 0, w, h);

                    const pattern = this.slide.pattern;
                    if (pattern === 'waves') drawWaves(ctx, w, h, t, [h1, h2, h3]);
                    else if (pattern === 'voxel') drawVoxel(ctx, w, h, t, [h1, h2, h3]);
                    else if (pattern === 'threads') drawThreads(ctx, w, h, t, [h1, h2, h3]);
                    else if (pattern === 'audio') drawAudio(ctx, w, h, t, [h1, h2, h3]);
                    else if (pattern === 'nodes') drawNodes(ctx, w, h, t, [h1, h2, h3]);
                };
                requestAnimationFrame(draw);
            },
            startTimer(){
                if (this._tid) clearInterval(this._tid);
                this.progress = 0;
                const start = Date.now();
                this._tid = setInterval(() => {
                    if (this.paused) return;
                    const elapsed = Date.now() - start;
                    this.progress = Math.min(1, elapsed / this.SLIDE_MS);
                    if (elapsed >= this.SLIDE_MS){
                        clearInterval(this._tid);
                        this.idx = (this.idx + 1) % this.slides.length;
                        this.startTimer();
                    }
                }, 40);
            },
            goTo(i){
                this.idx = i;
                this.startTimer();
            },
        };
    }

    // Pattern renderers — operate on canvas in 600x400 coordinate space scaled to actual size
    function withScale(ctx, w, h, fn){
        const sx = w / 600, sy = h / 400;
        ctx.save(); ctx.scale(sx, sy);
        fn();
        ctx.restore();
    }
    function drawWaves(ctx, w, h, t, hues){
        withScale(ctx, w, h, () => {
            ctx.lineCap = 'round';
            for (let i = 0; i < 12; i++){
                const phase = t * 0.6 + i * 0.5;
                const y = 200 + Math.sin(phase) * (40 + i * 5);
                const hue = (hues[i % 3] + t * 6) % 360;
                ctx.strokeStyle = `hsla(${hue},80%,65%,${0.35 + (i % 3) * 0.12})`;
                ctx.lineWidth = 1.2;
                ctx.beginPath();
                ctx.moveTo(0, y);
                ctx.quadraticCurveTo(150, y + Math.cos(phase) * 60, 300, y);
                ctx.quadraticCurveTo(450, y + Math.sin(phase + 1) * 50 * 0.5, 600, y + Math.sin(phase + 1) * 50);
                ctx.stroke();
            }
        });
    }
    function drawVoxel(ctx, w, h, t, hues){
        withScale(ctx, w, h, () => {
            ctx.save();
            ctx.translate(300, 220);
            ctx.rotate((t * 4) * Math.PI / 180);
            for (let layer = 0; layer < 5; layer++){
                ctx.save();
                ctx.translate(0, -layer * 22);
                ctx.transform(1, 0, Math.tan(-20 * Math.PI / 180), 1, 0, 0);
                for (let x = 0; x < 5; x++){
                    for (let y = 0; y < 5; y++){
                        const show = (x + y + layer) % 2 === 0 && (x !== 2 || y !== 2);
                        if (!show) continue;
                        const hue = (hues[(x + layer) % 3] + t * 3) % 360;
                        ctx.fillStyle = `hsla(${hue},70%,${45 + layer * 5}%,${0.6 + layer * 0.08})`;
                        ctx.strokeStyle = `hsl(${hue},85%,70%)`;
                        ctx.lineWidth = 0.4;
                        const px = (x - 2) * 28 + (y - 2) * 14;
                        const py = (y - 2) * 28 - (x - 2) * 14;
                        ctx.fillRect(px, py, 26, 26);
                        ctx.strokeRect(px, py, 26, 26);
                    }
                }
                ctx.restore();
            }
            ctx.restore();
        });
    }
    function drawThreads(ctx, w, h, t, hues){
        withScale(ctx, w, h, () => {
            ctx.lineCap = 'round';
            for (let i = 0; i < 24; i++){
                const a = (i / 24) * Math.PI * 2 + t * 0.03;
                const r1 = 80, r2 = 180;
                const x1 = 300 + Math.cos(a) * r1, y1 = 200 + Math.sin(a) * r1;
                const x2 = 300 + Math.cos(a + t * 0.02) * r2, y2 = 200 + Math.sin(a + t * 0.02) * r2;
                const hue = (hues[i % 3] + t * 4) % 360;
                ctx.strokeStyle = `hsla(${hue},80%,65%,0.5)`;
                ctx.lineWidth = 1;
                ctx.beginPath(); ctx.moveTo(x1, y1); ctx.lineTo(x2, y2); ctx.stroke();
            }
            for (let i = 0; i < 3; i++){
                const a = t * 0.08 + i * 2.1;
                const x = 300 + Math.cos(a) * 140, y = 200 + Math.sin(a) * 140;
                ctx.fillStyle = `hsla(${hues[i]},90%,70%,0.9)`;
                ctx.beginPath(); ctx.arc(x, y, 5, 0, Math.PI * 2); ctx.fill();
            }
        });
    }
    function drawAudio(ctx, w, h, t, hues){
        withScale(ctx, w, h, () => {
            for (let i = 0; i < 48; i++){
                const x = 20 + i * 12;
                const bh = Math.abs(Math.sin(t * 0.3 + i * 0.4) * Math.cos(t * 0.1 + i * 0.08)) * 180 + 20;
                const hue = (hues[i % 3] + t * 3) % 360;
                ctx.fillStyle = `hsla(${hue},75%,${55 + (i % 5) * 4}%,0.75)`;
                roundRect(ctx, x, 200 - bh / 2, 8, bh, 2);
                ctx.fill();
            }
        });
    }
    function drawNodes(ctx, w, h, t, hues){
        withScale(ctx, w, h, () => {
            const nodes = [{x:120,y:130,label:'Prompt'},{x:280,y:90,label:'Image'},{x:280,y:200,label:'Style'},{x:440,y:130,label:'Video'},{x:440,y:270,label:'Audio'}];
            const edges = [[0,1],[0,2],[1,3],[2,3],[2,4]];
            edges.forEach((e, i) => {
                const a = nodes[e[0]], b = nodes[e[1]];
                const hue = (hues[i % 3] + t * 4) % 360;
                ctx.strokeStyle = `hsla(${hue},70%,50%,0.45)`;
                ctx.lineWidth = 1.2;
                ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
                const p = (t * 0.02 + i * 0.2) % 1;
                const dotX = a.x + (b.x - a.x) * p, dotY = a.y + (b.y - a.y) * p;
                ctx.fillStyle = `hsla(${hue},90%,75%,0.95)`;
                ctx.beginPath(); ctx.arc(dotX, dotY, 3, 0, Math.PI * 2); ctx.fill();
            });
            ctx.font = '11px Inter, sans-serif';
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            nodes.forEach((n, i) => {
                const hue = (hues[i % 3] + t * 3) % 360;
                ctx.fillStyle = `hsl(${hue},70%,12%)`;
                ctx.strokeStyle = `hsl(${hue},80%,55%)`;
                ctx.lineWidth = 1;
                roundRect(ctx, n.x - 36, n.y - 16, 72, 32, 8);
                ctx.fill(); ctx.stroke();
                ctx.fillStyle = '#fff';
                ctx.fillText(n.label, n.x, n.y);
            });
        });
    }
    function roundRect(ctx, x, y, w, h, r){
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
    }
</script>
@endpush
