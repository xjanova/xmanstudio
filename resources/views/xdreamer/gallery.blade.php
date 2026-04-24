@extends('layouts.xdreamer')
@section('title', 'Gallery · X-DREAMER')
@section('meta_description', 'สำรวจปราสาทสาธารณะ — 2.4 ล้านความฝันที่ทอขึ้นจากชุมชน X-DREAMER')

@php
    $hueShift = 70;
    // $generations from XdreamerController — live AIXMAN data (empty if no AIXMAN tables)
    $generations = $generations ?? [];

    if (! empty($generations)) {
        // Map live data → template shape
        $ratios = [1.2, 0.8, 1.4, 1, 1.3, 0.9];
        $items = [];
        foreach ($generations as $i => $g) {
            $items[] = [
                'title' => $g['title'] ?? 'Untitled #'.($g['id'] ?? $i+1),
                'author' => 'user_'.($g['user_id'] ?? '?'),
                'hue' => crc32((string) ($g['id'] ?? $i)) % 360,
                'likes' => (int) ($g['likes_count'] ?? 0),
                'mode' => (string) ($g['mode'] ?? 'image'),
                'ratio' => $ratios[$i % 6],
                'thumb_url' => $g['thumb_url'] ?? null,
            ];
        }
    } else {
        // Placeholder community feed (when AIXMAN not connected)
        $titles = ['ปราสาทหมอกจันทรา','Dream Loop','เส้นใยดวงดาว','Whisper Loom','มโนทัศน์มรกต','Violet Study'];
        $authors = ['นภาลัย','kairos','Theo','จิตรา','nine','arc_ot'];
        $modes = ['image','video','audio','3d'];
        $ratios = [1.2, 0.8, 1.4, 1, 1.3, 0.9];
        $items = [];
        for ($i = 0; $i < 24; $i++) {
            $items[] = [
                'title' => $titles[$i % 6] . ' #' . ($i + 1),
                'author' => $authors[$i % 6],
                'hue' => ($i * 37) % 360,
                'likes' => 50 + ($i * 73) % 1950,
                'mode' => $modes[$i % 4],
                'ratio' => $ratios[$i % 6],
                'thumb_url' => null,
            ];
        }
    }
@endphp

@section('content')
<div style="padding:110px 48px 80px;max-width:1500px;margin:0 auto;">
    <div style="text-align:center;margin-bottom:48px;">
        <div style="font-size:12px;letter-spacing:0.16em;color:#a5f3fc;text-transform:uppercase;margin-bottom:14px;">· สำรวจชุมชน</div>
        <h1 style="font-size:clamp(48px, 6vw, 80px);font-weight:300;color:#fff;letter-spacing:-0.02em;line-height:1.05;margin:0;">
            ปราสาท<span class="xdr-italic-th" style="font-style:italic;color:#c4b5fd;"> สาธารณะ</span>
        </h1>
        <p style="margin-top:18px;color:rgba(203,213,225,0.7);font-size:17px;font-weight:300;">
            2.4 ล้านความฝัน ทอขึ้นจากชุมชน 67,000 คน
        </p>
    </div>

    <div x-data="{ filter:'ทั้งหมด', sort:'trending' }" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:16px;">
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            @foreach(['ทั้งหมด','Image','Video','Audio','3D','Collections'] as $f)
            <button @click="filter='{{ $f }}'"
                :style="filter==='{{ $f }}' ? 'background:rgba(255,255,255,0.12);color:#fff;' : 'background:transparent;color:#94a3b8;'"
                style="padding:8px 16px;border-radius:999px;font-size:13px;border:1px solid rgba(255,255,255,0.1);cursor:pointer;">{{ $f }}</button>
            @endforeach
        </div>
        <div style="display:flex;gap:8px;">
            @foreach(['trending','ใหม่ล่าสุด','top week'] as $s)
            <button @click="sort='{{ $s }}'"
                :style="sort==='{{ $s }}' ? 'background:rgba(139,92,246,0.15);color:#fff;border-color:rgba(139,92,246,0.3);' : 'background:transparent;color:#94a3b8;border-color:rgba(255,255,255,0.08);'"
                style="padding:8px 14px;border-radius:8px;font-size:12px;border:1px solid;cursor:pointer;">{{ $s }}</button>
            @endforeach
        </div>
    </div>

    {{-- Masonry-ish grid --}}
    <div class="rp-gallery-mason" style="column-count:4;column-gap:14px;">
        @foreach($items as $i => $item)
        @php $h1 = ($item['hue'] + $hueShift) % 360; $h2 = ($h1 + 60) % 360; @endphp
        <div style="break-inside:avoid;margin-bottom:14px;border-radius:14px;overflow:hidden;position:relative;
            background:linear-gradient(135deg, hsl({{ $h1 }}, 60%, 14%), hsl({{ $h2 }}, 60%, 8%));
            border:1px solid rgba(255,255,255,0.06);cursor:pointer;">
            <div style="padding-bottom:{{ $item['ratio'] * 100 }}%;position:relative;">
                <svg width="100%" height="100%" style="position:absolute;inset:0;" preserveAspectRatio="none" viewBox="0 0 100 100">
                    <defs>
                        <linearGradient id="gd{{ $i }}" x1="0" x2="1" y1="0" y2="1">
                            <stop offset="0%" stop-color="hsl({{ $h1 }}, 85%, 65%)"/>
                            <stop offset="100%" stop-color="hsl({{ $h2 }}, 85%, 70%)"/>
                        </linearGradient>
                    </defs>
                    @for($j = 0; $j < 14; $j++)
                    @php
                        $sx = -5 + $j*8; $sy = 110 + sin($j+$i)*5;
                        $cx = 30 + cos($j*1.3+$i*0.7)*40; $cy = 50 + sin($j*0.8)*25;
                        $ex = 105 - $j*7; $ey = -5 + cos($j+$i)*5;
                        $sw = 0.3 + ($j%4)*0.2; $op = 0.4 + ($j%3)*0.2;
                    @endphp
                    <path d="M{{ $sx }} {{ $sy }} Q{{ $cx }} {{ $cy }} {{ $ex }} {{ $ey }}" stroke="url(#gd{{ $i }})" stroke-width="{{ $sw }}" fill="none" opacity="{{ $op }}"/>
                    @endfor
                </svg>
                <div style="position:absolute;top:10px;left:12px;">
                    <span style="font-size:10px;padding:3px 8px;border-radius:999px;background:rgba(0,0,0,0.5);backdrop-filter:blur(8px);color:#fff;letter-spacing:0.1em;text-transform:uppercase;">{{ $item['mode'] }}</span>
                </div>
            </div>
            <div style="padding:12px 14px;display:flex;justify-content:space-between;align-items:center;">
                <div style="min-width:0;flex:1;">
                    <div style="font-size:13px;color:#fff;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $item['title'] }}</div>
                    <div style="font-size:11px;color:#94a3b8;">@‌{{ $item['author'] }}</div>
                </div>
                <div style="font-size:11px;color:#94a3b8;display:flex;align-items:center;gap:4px;">♡ {{ $item['likes'] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <div style="text-align:center;margin-top:40px;">
        <button style="padding:14px 28px;border-radius:12px;background:rgba(255,255,255,0.06);color:#fff;border:1px solid rgba(255,255,255,0.12);font-size:14px;cursor:pointer;">
            โหลดเพิ่ม ({{ count($items) }} / 2.4M)
        </button>
    </div>
</div>
@endsection
