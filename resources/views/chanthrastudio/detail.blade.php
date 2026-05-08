@extends('layouts.app')

@section('title', 'Chanthra Studio — AI Video Atelier | XMAN Studio')

@push('styles')
<style>
    /* Lunar atelier palette — mirrors the desktop app */
    :root {
        --void: #060409;
        --bg-base: #0c0814;
        --bg-elev: #1d1530;
        --bg-inset: #0a0612;
        --gold: #d4a76a;
        --gold-hi: #f0cb88;
        --gold-lo: #8b6938;
        --crimson: #b03346;
        --crimson-deep: #6e1f2c;
        --moon: #d8d0e6;
        --moon-deep: #6c5e85;
        --plum: #2a1b3d;
        --plum-light: #4a2e6a;
        --text-1: #f0e7d4;
        --text-2: #b8aec0;
        --text-3: #7d7388;
    }

    .chanthra-page {
        background:
            radial-gradient(ellipse at top right, rgba(176, 51, 70, 0.18), transparent 55%),
            radial-gradient(ellipse at bottom left, rgba(212, 167, 106, 0.14), transparent 55%),
            radial-gradient(ellipse at center top, rgba(108, 94, 133, 0.12), transparent 60%),
            #0c0814;
        color: var(--text-1);
        min-height: 100vh;
    }

    .font-display { font-family: 'Cormorant Garamond', 'Cormorant', Georgia, serif; }
    .font-mono { font-family: 'IBM Plex Mono', 'JetBrains Mono', Consolas, monospace; }

    .hairline { border: 1px solid rgba(232, 213, 179, 0.06); }
    .hairline-gold { border: 1px solid rgba(212, 167, 106, 0.22); }

    .gold-cta {
        background: linear-gradient(135deg, var(--gold-lo), var(--gold), var(--gold-hi));
        color: #1a0d05;
        box-shadow: 0 6px 24px rgba(212, 167, 106, 0.35);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .gold-cta:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(212, 167, 106, 0.5); }

    .glass-card {
        background: linear-gradient(180deg, rgba(26, 18, 48, 0.72) 0%, rgba(18, 9, 30, 0.85) 100%);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(232, 213, 179, 0.06);
        border-radius: 12px;
    }

    .moon-orb {
        background: radial-gradient(circle at 35% 35%, var(--gold-hi), var(--gold-lo));
        box-shadow: 0 0 60px rgba(212, 167, 106, 0.45), 0 0 140px rgba(176, 51, 70, 0.18);
    }

    .clip-strip {
        background: linear-gradient(180deg, var(--gold), var(--gold-lo));
    }
    .clip-crimson {
        background: linear-gradient(180deg, var(--crimson), var(--crimson-deep));
    }
    .clip-plum {
        background: linear-gradient(180deg, var(--plum-light), var(--plum));
    }

    .feature-card {
        background: linear-gradient(180deg, #1a1230 0%, #12091e 100%);
        border: 1px solid rgba(232, 213, 179, 0.06);
        border-radius: 12px;
        transition: border-color 0.2s ease, transform 0.2s ease;
    }
    .feature-card:hover {
        border-color: rgba(212, 167, 106, 0.32);
        transform: translateY(-2px);
    }
</style>
@endpush

@section('content')
<div class="chanthra-page">

    {{-- ═══════════ HERO ═══════════ --}}
    <section class="relative overflow-hidden pt-24 pb-32">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                {{-- Left: pitch --}}
                <div>
                    <div class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-mono tracking-wider hairline-gold mb-8" style="color: var(--gold-hi); background: rgba(212, 167, 106, 0.08);">
                        <span class="inline-block w-1.5 h-1.5 rounded-full mr-2 animate-pulse" style="background: var(--gold-hi); box-shadow: 0 0 8px var(--gold-hi);"></span>
                        LUNAR ATELIER · v0.2.0
                    </div>

                    <h1 class="font-display italic text-6xl md:text-7xl leading-none mb-6">
                        <span style="color: var(--text-1);">Chanthra</span>
                        <span style="color: var(--gold-hi);"> Studio</span>
                    </h1>

                    <p class="text-xl mb-3" style="color: var(--text-1);">
                        AI video atelier บน Windows — script · shot · score · stitch ครบในแอปเดียว
                    </p>
                    <p class="text-base mb-10" style="color: var(--text-2);">
                        Generate ภาพ/วีดีโอด้วย ComfyUI ในเครื่อง หรือผ่าน Replicate / Runway / Pika · พากย์เสียงด้วย OpenAI / ElevenLabs · เขียนสคริปต์ด้วย Claude / Gemini · ต่อคลิปเป็น MP4 ด้วย ffmpeg
                    </p>

                    <div class="flex flex-wrap gap-3 mb-10">
                        <a href="{{ route('chanthra-studio.download') }}" class="gold-cta px-8 py-4 rounded-xl font-display italic text-lg flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            ดาวน์โหลดเวอร์ชันล่าสุด
                        </a>
                        <a href="{{ route('chanthra-studio.pricing') }}" class="px-8 py-4 rounded-xl text-lg font-display italic hairline transition" style="color: var(--text-1); background: rgba(255,255,255,0.04);">
                            ดูแพ็กเกจ License
                        </a>
                        <a href="{{ route('chanthra-studio.manual') }}" class="px-8 py-4 rounded-xl text-base font-mono text-sm hairline transition" style="color: var(--text-2); background: transparent;">
                            อ่านคู่มือ →
                        </a>
                    </div>

                    {{-- Trust strip --}}
                    <div class="flex flex-wrap items-center gap-6 font-mono text-xs" style="color: var(--text-3);">
                        <span class="flex items-center gap-2">
                            <span class="inline-block w-1 h-1 rounded-full" style="background: var(--gold);"></span>
                            Windows 10/11 (x64)
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="inline-block w-1 h-1 rounded-full" style="background: var(--gold);"></span>
                            Auto-update via GitHub Releases
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="inline-block w-1 h-1 rounded-full" style="background: var(--gold);"></span>
                            DPAPI-encrypted API keys
                        </span>
                    </div>
                </div>

                {{-- Right: visual showpiece --}}
                <div class="relative">
                    <div class="glass-card p-8 relative overflow-hidden" style="aspect-ratio: 4/3;">
                        {{-- Title bar mock --}}
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-2 h-2 rounded-full bg-red-400/60"></div>
                            <div class="w-2 h-2 rounded-full bg-yellow-400/60"></div>
                            <div class="w-2 h-2 rounded-full bg-green-400/60"></div>
                            <span class="ml-3 font-mono text-xs" style="color: var(--text-3);">chanthra studio · the empress</span>
                        </div>

                        {{-- Moon orb --}}
                        <div class="absolute right-12 top-16 w-48 h-48 rounded-full moon-orb"></div>
                        <div class="absolute right-20 top-24 w-40 h-40 rounded-full" style="background: radial-gradient(circle, rgba(176,51,70,0.35), transparent 70%);"></div>

                        {{-- Storyboard strip --}}
                        <div class="absolute bottom-12 left-8 right-8 flex gap-3">
                            <div class="flex-1 rounded clip-crimson" style="height: 60px; box-shadow: 0 4px 12px rgba(176,51,70,0.4);"></div>
                            <div class="flex-1 rounded clip-strip" style="height: 60px; box-shadow: 0 4px 12px rgba(212,167,106,0.4);"></div>
                            <div class="flex-1 rounded clip-plum" style="height: 60px;"></div>
                            <div class="flex-1 rounded" style="height: 60px; background: linear-gradient(180deg, var(--gold-hi), var(--gold-lo)); box-shadow: 0 4px 12px rgba(212,167,106,0.4);"></div>
                        </div>

                        <div class="absolute bottom-3 left-8 font-mono text-xs" style="color: var(--gold-hi);">▶ summon scene</div>
                    </div>

                    {{-- Floating badges --}}
                    <div class="absolute -top-4 -left-4 px-3 py-1 rounded-lg font-mono text-xs gold-cta">
                        Phase 6 · Node Flow
                    </div>
                    <div class="absolute -bottom-4 -right-4 px-3 py-1 rounded-lg hairline-gold font-mono text-xs" style="color: var(--gold-hi); background: rgba(176,51,70,0.18); backdrop-filter: blur(6px);">
                        ComfyUI compat
                    </div>
                </div>
            </div>

            {{-- Stats strip --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-24 max-w-4xl mx-auto">
                @php $stats = [
                    ['n' => '8', 'l' => 'phases shipped'],
                    ['n' => '4', 'l' => 'LLM providers'],
                    ['n' => '6', 'l' => 'video providers'],
                    ['n' => '1640×1000', 'l' => 'lunar window'],
                ]; @endphp
                @foreach ($stats as $s)
                    <div class="text-center">
                        <div class="font-display italic text-4xl md:text-5xl" style="color: var(--gold-hi);">{{ $s['n'] }}</div>
                        <div class="font-mono text-xs mt-1 tracking-wider" style="color: var(--text-3);">{{ strtoupper($s['l']) }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════ FEATURES ═══════════ --}}
    <section class="relative py-24" style="background: rgba(6, 4, 13, 0.6);">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-16">
                <div class="font-mono text-xs tracking-widest mb-3" style="color: var(--gold);">— FEATURES —</div>
                <h2 class="font-display italic text-5xl mb-4" style="color: var(--text-1);">ครบทุกขั้นในไฟล์เดียว</h2>
                <p style="color: var(--text-2);">จาก script ไปจนถึงคลิปสำเร็จรูปที่โพสต์ขึ้น Facebook ได้ทันที</p>
            </div>

            @php $features = [
                ['icon' => '✦', 'title' => 'Generate workspace', 'desc' => 'Storyboard รายชอต ตั้ง prompt/style/seed/aspect/camera แล้วส่งเข้า ComfyUI หรือ Replicate / Runway / Pika ดู progress แบบ real-time'],
                ['icon' => '◆', 'title' => 'Sound atelier', 'desc' => 'TTS ด้วย OpenAI / ElevenLabs · เขียน/ขัดเกลาสคริปต์ด้วย Claude / Gemini / OpenAI / OpenRouter · มี wand "✦ Write" สำหรับ enhance prompt'],
                ['icon' => '❍', 'title' => 'Library + Queue', 'desc' => 'เก็บคลิปทั้งหมดใน SQLite ใกล้ ๆ exe มี search + tags + thumbnails · queue เห็นความคืบหน้าจริงทุกชอต'],
                ['icon' => '◐', 'title' => 'Render film', 'desc' => 'ต่อคลิปทั้ง project เป็น MP4 ผ่าน ffmpeg เลือก fps · audio track · voice take จาก Sound atelier ได้โดยตรง'],
                ['icon' => '▶', 'title' => 'Node Flow editor', 'desc' => 'visual graph editor ในแอป สำหรับ ComfyUI workflow มี palette · drag-drop nodes · bezier wires · mini-map · auto-arrange'],
                ['icon' => '⌘', 'title' => 'Auto-update', 'desc' => 'เช็ค GitHub Releases ทุกครั้งที่เปิดแอป มี progress bar + release notes ให้อ่าน ก่อนติดตั้ง — ต้องมี License key ที่ถูกต้อง'],
                ['icon' => '☾', 'title' => 'License system', 'desc' => '1 key = 1 เครื่อง bound ด้วย HWID (CPU + Motherboard + Disk) ย้ายเครื่องผ่าน Deactivate ได้เอง'],
                ['icon' => '※', 'title' => 'Privacy by default', 'desc' => 'API keys ทุก provider เข้ารหัสด้วย Windows DPAPI · settings + library อยู่บนเครื่องตัวเอง · zero telemetry'],
                ['icon' => '✧', 'title' => 'Lunar atelier UI', 'desc' => 'Frameless 1640×1000 window กับ Mica gradient ทอง/ครามม่วง · Cormorant Garamond + IBM Plex font stack · per-row save feedback'],
            ]; @endphp

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($features as $f)
                    <div class="feature-card p-6">
                        <div class="font-display italic text-3xl mb-3" style="color: var(--gold-hi);">{{ $f['icon'] }}</div>
                        <h3 class="font-display italic text-xl mb-2" style="color: var(--text-1);">{{ $f['title'] }}</h3>
                        <p class="text-sm leading-relaxed" style="color: var(--text-2);">{{ $f['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════ PRICING TEASER ═══════════ --}}
    <section class="relative py-24">
        <div class="max-w-6xl mx-auto px-6 lg:px-8">
            <div class="text-center mb-12">
                <div class="font-mono text-xs tracking-widest mb-3" style="color: var(--gold);">— PRICING —</div>
                <h2 class="font-display italic text-5xl" style="color: var(--text-1);">เลือกแพ็กเกจที่เหมาะกับงาน</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach ($pricing as $key => $p)
                    <div class="glass-card p-8 relative {{ $key === 'yearly' ? 'hairline-gold' : '' }}">
                        @if ($key === 'yearly')
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full gold-cta font-mono text-xs">
                                MOST POPULAR
                            </div>
                        @endif
                        <div class="font-mono text-xs tracking-widest mb-2" style="color: var(--gold);">{{ strtoupper($p['name']) }}</div>
                        <div class="font-display italic text-5xl mb-1" style="color: var(--text-1);">฿{{ number_format($p['price']) }}</div>
                        <div class="font-mono text-xs mb-6" style="color: var(--text-3);">
                            {{ $p['duration_days'] ? $p['duration_days'] . ' days' : 'one-time · perpetual' }}
                        </div>

                        <ul class="space-y-2 mb-8 text-sm" style="color: var(--text-2);">
                            @foreach ($p['features'] as $feat)
                                <li class="flex items-start gap-2">
                                    <span class="font-display italic text-base" style="color: var(--gold);">·</span>
                                    {{ $feat }}
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('chanthra-studio.pricing') }}" class="block text-center {{ $key === 'yearly' ? 'gold-cta font-display italic' : 'hairline' }} px-6 py-3 rounded-lg" style="{{ $key === 'yearly' ? '' : 'color: var(--text-1); background: rgba(255,255,255,0.04);' }}">
                            เลือก {{ $p['name_th'] }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══════════ FOOTER CTA ═══════════ --}}
    <section class="relative py-24" style="background: rgba(6, 4, 13, 0.7);">
        <div class="max-w-3xl mx-auto px-6 lg:px-8 text-center">
            <h2 class="font-display italic text-5xl mb-6" style="color: var(--text-1);">
                เริ่มต้นภายใน <span style="color: var(--gold-hi);">5 นาที</span>
            </h2>
            <p class="text-lg mb-10" style="color: var(--text-2);">
                ดาวน์โหลดเวอร์ชันล่าสุดจาก GitHub Releases · กรอก License key ที่ซื้อจาก xman4289.com · เริ่ม generate ได้เลย
            </p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('chanthra-studio.download') }}" class="gold-cta px-8 py-4 rounded-xl font-display italic text-lg">
                    ✦ ดาวน์โหลดเลย
                </a>
                <a href="{{ route('chanthra-studio.manual') }}" class="hairline px-8 py-4 rounded-xl font-display italic text-lg" style="color: var(--text-1); background: rgba(255,255,255,0.04);">
                    อ่านคู่มือก่อน →
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
