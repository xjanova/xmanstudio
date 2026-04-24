@extends('layouts.xdreamer')
@section('title', 'Studio · X-DREAMER')
@section('meta_description', 'พื้นที่ทำงาน AI generation — เขียน prompt, เลือก style, ปรับ parameters และทอผืนผ้า')

@php
    $hueShift = 70;
    $page = 'studio';
    $styles = ['dreamy','mystical','cinematic','painterly','concept art','anime','photoreal'];
    $aspects = ['1:1','16:9','9:16','4:5','21:9'];
    $models = ['loom-v4.2','loom-pro','loom-fast','loom-video','loom-3d'];
@endphp

@section('content')
<div class="rp-studio" x-data="xdrStudio()" style="padding-top:80px;min-height:100vh;display:grid;grid-template-columns:320px 1fr 340px;gap:0;">

    {{-- LEFT: prompt & controls --}}
    <aside class="rp-studio-left" style="border-right:1px solid rgba(255,255,255,0.06);padding:24px;
        display:flex;flex-direction:column;gap:20px;background:rgba(15,23,42,0.25);
        height:calc(100vh - 80px);overflow-y:auto;">

        <div>
            <div style="font-size:11px;letter-spacing:0.14em;color:#a5f3fc;margin-bottom:10px;text-transform:uppercase;">Prompt</div>
            <textarea x-model="prompt" rows="5"
                style="width:100%;padding:14px;border-radius:12px;background:rgba(2,6,23,0.6);color:#f1f5f9;
                    border:1px solid rgba(255,255,255,0.1);font-size:14px;line-height:1.5;font-family:inherit;resize:vertical;outline:none;"></textarea>
            <div style="display:flex;gap:6px;margin-top:10px;flex-wrap:wrap;">
                @foreach(['cinematic lighting','8k','volumetric','aurora','jade'] as $tag)
                <button style="padding:5px 10px;border-radius:999px;font-size:11px;background:rgba(255,255,255,0.05);color:#94a3b8;border:1px solid rgba(255,255,255,0.1);cursor:pointer;">+ {{ $tag }}</button>
                @endforeach
            </div>
        </div>

        <div>
            <div style="font-size:11px;letter-spacing:0.14em;color:#a5f3fc;margin-bottom:10px;text-transform:uppercase;">Negative prompt</div>
            <input placeholder="blurry, low quality, text..." style="width:100%;padding:12px;border-radius:10px;background:rgba(2,6,23,0.6);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:13px;font-family:inherit;outline:none;">
        </div>

        <div>
            <div style="font-size:11px;color:#94a3b8;margin-bottom:8px;letter-spacing:0.04em;">Style</div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                @foreach($styles as $s)
                <button @click="style='{{ $s }}'"
                        :style="style === '{{ $s }}' ? 'background:hsla({{ 220+$hueShift }},60%,50%,0.3);color:#fff;border:1px solid hsla({{ 220+$hueShift }},70%,60%,0.5);' : 'background:rgba(255,255,255,0.04);color:#94a3b8;border:1px solid rgba(255,255,255,0.08);'"
                        style="padding:6px 12px;border-radius:8px;font-size:12px;cursor:pointer;">{{ $s }}</button>
                @endforeach
            </div>
        </div>

        <div>
            <div style="font-size:11px;color:#94a3b8;margin-bottom:8px;letter-spacing:0.04em;">Aspect ratio</div>
            <div style="display:flex;gap:6px;">
                @foreach($aspects as $a)
                <button @click="aspect='{{ $a }}'"
                        :style="aspect === '{{ $a }}' ? 'background:hsla({{ 220+$hueShift }},60%,50%,0.3);color:#fff;border:1px solid hsla({{ 220+$hueShift }},70%,60%,0.5);' : 'background:rgba(255,255,255,0.04);color:#94a3b8;border:1px solid rgba(255,255,255,0.08);'"
                        style="flex:1;padding:8px 0;border-radius:8px;font-size:12px;cursor:pointer;">{{ $a }}</button>
                @endforeach
            </div>
        </div>

        <div>
            <div style="font-size:11px;color:#94a3b8;margin-bottom:8px;letter-spacing:0.04em;">Model</div>
            <select x-model="model" style="width:100%;padding:10px;border-radius:10px;background:rgba(2,6,23,0.6);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:13px;font-family:inherit;outline:none;">
                @foreach($models as $m)
                <option value="{{ $m }}" style="background:#0f172a;">{{ $m }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:#94a3b8;margin-bottom:8px;">
                <span>Steps</span><span style="font-family:ui-monospace,monospace;color:#e2e8f0;" x-text="steps"></span>
            </div>
            <input type="range" min="10" max="80" x-model.number="steps" style="width:100%;accent-color:#8b5cf6;">
        </div>

        <div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:#94a3b8;margin-bottom:8px;">
                <span>Guidance</span><span style="font-family:ui-monospace,monospace;color:#e2e8f0;" x-text="guidance"></span>
            </div>
            <input type="range" min="1" max="20" step="0.5" x-model.number="guidance" style="width:100%;accent-color:#8b5cf6;">
        </div>

        <div>
            <div style="font-size:11px;color:#94a3b8;margin-bottom:8px;letter-spacing:0.04em;">Seed</div>
            <div style="display:flex;gap:6px;">
                <input x-model.number="seed" style="flex:1;padding:10px;border-radius:10px;background:rgba(2,6,23,0.6);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:13px;font-family:ui-monospace,monospace;outline:none;">
                <button @click="seed = Math.floor(Math.random()*99999)" style="padding:0 12px;border-radius:10px;background:rgba(255,255,255,0.05);color:#94a3b8;border:1px solid rgba(255,255,255,0.1);cursor:pointer;">↻</button>
            </div>
        </div>

        <button @click="generate" :disabled="generating"
            :style="generating ? 'opacity:0.7;cursor:wait;' : 'opacity:1;cursor:pointer;'"
            style="margin-top:auto;padding:16px;border-radius:12px;
                background:linear-gradient(135deg, hsl({{ 160+$hueShift }},70%,45%), hsl({{ 280+$hueShift }},70%,55%));
                color:#fff;border:none;font-size:15px;font-weight:600;
                box-shadow:0 10px 24px -8px hsla({{ 270+$hueShift }},70%,50%,0.5);">
            <span x-text="generating ? '⟳ กำลังทอ...' : 'ทอ ✦ 4 ภาพ · 12 credits'"></span>
        </button>
    </aside>

    {{-- CENTER: canvas --}}
    <main class="rp-studio-center" style="padding:24px;overflow-y:auto;height:calc(100vh - 80px);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <div style="display:flex;gap:8px;">
                @foreach(['ภาพ 4 ใบ','Variations','Upscale','Inpaint','History'] as $i => $tab)
                <button style="padding:7px 14px;border-radius:8px;font-size:12px;background:{{ $i === 0 ? 'rgba(255,255,255,0.08)' : 'transparent' }};color:{{ $i === 0 ? '#fff' : '#94a3b8' }};border:1px solid rgba(255,255,255,0.08);cursor:pointer;">{{ $tab }}</button>
                @endforeach
            </div>
            <div style="font-size:12px;color:#64748b;font-family:ui-monospace,monospace;">session · weaver_42</div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;">
            @for($i = 0; $i < 4; $i++)
            @php $hue1 = (140 + $i * 35 + $hueShift) % 360; $hue2 = ($hue1 + 60) % 360; @endphp
            <div onmouseenter="this.querySelector('.tools').style.opacity=1" onmouseleave="this.querySelector('.tools').style.opacity=0"
                 style="aspect-ratio:1/1;border-radius:14px;position:relative;overflow:hidden;
                    background:linear-gradient(135deg, hsl({{ $hue1 }},60%,14%), hsl({{ $hue2 }},60%,8%));
                    border:1px solid rgba(255,255,255,0.08);cursor:pointer;">
                <svg width="100%" height="100%" style="position:absolute;inset:0;" preserveAspectRatio="none" viewBox="0 0 100 100">
                    <defs>
                        <linearGradient id="ssg{{ $i }}" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="hsl({{ $hue1 }},85%,65%)" stop-opacity="0.9"/>
                            <stop offset="100%" stop-color="hsl({{ $hue2 }},85%,70%)" stop-opacity="0.9"/>
                        </linearGradient>
                    </defs>
                    @for($j = 0; $j < 20; $j++)
                    @php
                        $sx = -5 + $j * 6; $sy = 110 + sin($j) * 8;
                        $cx = 40 + sin($j) * 35; $cy = 50 + cos($j) * 25;
                        $ex = 105 - $j * 5; $ey = -5 + cos($j) * 8;
                        $sw = 0.3 + ($j % 4) * 0.25; $op = 0.45 + ($j % 3) * 0.15;
                    @endphp
                    <path d="M{{ $sx }} {{ $sy }} Q{{ $cx }} {{ $cy }} {{ $ex }} {{ $ey }}" stroke="url(#ssg{{ $i }})" stroke-width="{{ $sw }}" fill="none" opacity="{{ $op }}"/>
                    @endfor
                </svg>
                <div x-show="generating" x-transition style="position:absolute;inset:0;background:rgba(0,0,0,0.4);backdrop-filter:blur(4px);display:grid;place-items:center;color:#fff;font-size:12px;letter-spacing:0.1em;">
                    <div style="text-align:center;">
                        <div style="font-size:24px;margin-bottom:8px;animation:spin 2s linear infinite;">⟳</div>
                        WEAVING...
                    </div>
                </div>
                <div class="tools" style="position:absolute;top:10px;right:10px;display:flex;gap:4px;opacity:0;transition:opacity 200ms;">
                    @foreach(['✦','↻','↓','⤢'] as $ico)
                    <button style="width:30px;height:30px;border-radius:8px;background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);color:#fff;border:1px solid rgba(255,255,255,0.15);font-size:13px;cursor:pointer;">{{ $ico }}</button>
                    @endforeach
                </div>
                <div style="position:absolute;left:12px;bottom:10px;font-size:10px;color:rgba(255,255,255,0.55);letter-spacing:0.08em;font-family:ui-monospace,monospace;">#{{ str_pad($i+1, 2, '0', STR_PAD_LEFT) }}</div>
            </div>
            @endfor
        </div>

        <div style="margin-top:32px;">
            <div style="font-size:11px;color:#64748b;letter-spacing:0.1em;margin-bottom:12px;text-transform:uppercase;">· รุ่นก่อนหน้า (history)</div>
            <div class="rp-history" style="display:grid;grid-template-columns:repeat(8,1fr);gap:8px;">
                @for($i = 0; $i < 16; $i++)
                @php $h1 = ($i*23+$hueShift)%360; $h2 = ($i*23+60+$hueShift)%360; @endphp
                <div style="aspect-ratio:1;border-radius:8px;background:linear-gradient(135deg, hsl({{ $h1 }},50%,15%), hsl({{ $h2 }},50%,8%));border:1px solid rgba(255,255,255,0.05);cursor:pointer;"></div>
                @endfor
            </div>
        </div>
    </main>

    {{-- RIGHT: layers / reference --}}
    <aside class="rp-studio-right" style="border-left:1px solid rgba(255,255,255,0.06);padding:24px;background:rgba(15,23,42,0.25);height:calc(100vh - 80px);overflow-y:auto;">
        <div style="font-size:11px;letter-spacing:0.14em;color:#a5f3fc;margin-bottom:14px;text-transform:uppercase;">Reference images</div>
        <div style="height:140px;border-radius:12px;border:1.5px dashed rgba(255,255,255,0.15);display:grid;place-items:center;color:#64748b;font-size:13px;cursor:pointer;margin-bottom:24px;background:rgba(2,6,23,0.3);">
            <div style="text-align:center;">
                <div style="font-size:22px;margin-bottom:4px;">↑</div>
                ลาก & วางภาพ ที่นี่
            </div>
        </div>

        <div style="font-size:11px;letter-spacing:0.14em;color:#a5f3fc;margin-bottom:14px;text-transform:uppercase;">Concept threads</div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach([['n'=>'castle · dreamy','w'=>85,'h'=>270],['n'=>'aurora light','w'=>72,'h'=>200],['n'=>'jade palette','w'=>90,'h'=>155],['n'=>'volumetric fog','w'=>64,'h'=>220],['n'=>'floating','w'=>55,'h'=>240]] as $c)
            <div style="padding:12px;border-radius:10px;background:rgba(2,6,23,0.4);border:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;gap:10px;">
                <div style="width:6px;height:28px;border-radius:3px;background:hsl({{ $c['h']+$hueShift }}, 70%, 60%);box-shadow:0 0 8px hsl({{ $c['h']+$hueShift }}, 70%, 50%);"></div>
                <div style="flex:1;">
                    <div style="font-size:13px;color:#f1f5f9;">{{ $c['n'] }}</div>
                    <div style="margin-top:4px;height:2px;border-radius:2px;background:rgba(255,255,255,0.06);overflow:hidden;">
                        <div style="width:{{ $c['w'] }}%;height:100%;background:hsl({{ $c['h']+$hueShift }}, 70%, 60%);"></div>
                    </div>
                </div>
                <div style="font-size:11px;color:#64748b;font-family:ui-monospace,monospace;">{{ $c['w'] }}%</div>
            </div>
            @endforeach
        </div>

        <div style="margin-top:28px;font-size:11px;letter-spacing:0.14em;color:#a5f3fc;margin-bottom:14px;text-transform:uppercase;">Credits</div>
        <div style="padding:14px;border-radius:12px;background:linear-gradient(135deg, hsla({{ 220+$hueShift }},60%,25%,0.4), hsla({{ 280+$hueShift }},60%,20%,0.4));border:1px solid rgba(255,255,255,0.08);">
            <div style="display:flex;justify-content:space-between;align-items:baseline;">
                <div style="font-size:28px;font-weight:300;color:#fff;">2,847</div>
                <div style="font-size:11px;color:#94a3b8;">/ 5,000</div>
            </div>
            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">credits ที่เหลือเดือนนี้</div>
            <div style="margin-top:10px;height:4px;border-radius:4px;background:rgba(255,255,255,0.08);overflow:hidden;">
                <div style="height:100%;width:57%;background:linear-gradient(90deg, hsl({{ 160+$hueShift }},70%,55%), hsl({{ 280+$hueShift }},70%,60%));"></div>
            </div>
            <button style="width:100%;margin-top:14px;padding:8px;border-radius:8px;background:rgba(255,255,255,0.08);color:#fff;border:1px solid rgba(255,255,255,0.1);font-size:12px;cursor:pointer;">+ เติม credits</button>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script>
function xdrStudio(){
    return {
        prompt: 'ปราสาทลอยฟ้าที่ทอด้วยเส้นใยแสง, ออโรร่าไหลผ่าน',
        style: 'dreamy', aspect: '1:1', model: 'loom-v4.2',
        steps: 42, guidance: 7.5, seed: 18234, generating: false,
        generate(){
            this.generating = true;
            setTimeout(() => { this.generating = false; }, 2400);
        }
    };
}
</script>
@endpush
