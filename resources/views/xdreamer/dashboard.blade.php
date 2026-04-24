@extends('layouts.xdreamer')
@section('title', 'Dashboard · X-DREAMER')
@section('meta_description', 'ปราสาทแห่งความคิดของคุณ — ดูสถิติ ผลงานล่าสุด และจัดการ collections')

@php
    $hueShift = 70;
    // $credits, $stats come from XdreamerController (null if not connected to AIXMAN yet)
    $credits = $credits ?? null;
    $stats = $stats ?? null;

    // Build stat cards from live data with sensible "—" placeholders
    $statCards = [
        [
            'l' => 'ผลงานทั้งหมด',
            'v' => $stats ? number_format($stats['total']) : '—',
            'd' => $stats ? '+'.$stats['this_week'].' สัปดาห์นี้' : 'ยังไม่ได้เริ่ม',
            'hue' => 160,
        ],
        [
            'l' => 'Credits เหลือ',
            'v' => $credits ? number_format($credits['balance']) : '—',
            'd' => $credits ? 'รีเซตใน '.$credits['reset_in_days'].' วัน' : 'ยังไม่มีแพ็กเกจ',
            'hue' => 200,
        ],
        [
            'l' => 'ใช้งานเดือนนี้',
            'v' => $stats ? number_format($stats['this_month']) : '0',
            'd' => $credits ? 'จาก '.number_format($credits['monthly_cap']).' cap' : '—',
            'hue' => 250,
        ],
        [
            'l' => 'รุ่นล่าสุด',
            'v' => 'loom-v4.2',
            'd' => 'พร้อมใช้',
            'hue' => 290,
        ],
    ];

    // Usage breakdown — derived from $stats by_mode if available
    $byMode = $stats['by_mode'] ?? [];
    $usageTotal = max(1, array_sum($byMode));
    $usage = [
        ['l' => 'Image', 'v' => (int) round(($byMode['image'] ?? 0) / $usageTotal * 100), 'hue' => 160],
        ['l' => 'Video', 'v' => (int) round(($byMode['video'] ?? 0) / $usageTotal * 100), 'hue' => 220],
        ['l' => 'Audio', 'v' => (int) round(($byMode['audio'] ?? 0) / $usageTotal * 100), 'hue' => 270],
        ['l' => '3D',    'v' => (int) round(($byMode['3d']    ?? 0) / $usageTotal * 100), 'hue' => 290],
    ];
    // Fallback breakdown when no data yet — sample numbers so chart looks alive
    if (empty($byMode)) {
        $usage = [['l'=>'Image','v'=>68,'hue'=>160],['l'=>'Video','v'=>22,'hue'=>220],['l'=>'Audio','v'=>7,'hue'=>270],['l'=>'3D','v'=>3,'hue'=>290]];
    }

    $collections = [
        ['n'=>'ฝันกลางวันของเดือนเมษา','c'=>48,'hue'=>160],
        ['n'=>'Dream City series','c'=>24,'hue'=>220],
        ['n'=>'เส้นใยสีไวโอเลต','c'=>67,'hue'=>280],
    ];
@endphp

@section('content')
<div style="padding:110px 48px 80px;max-width:1400px;margin:0 auto;">
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:40px;flex-wrap:wrap;gap:16px;">
        <div>
            <div style="font-size:12px;letter-spacing:0.16em;color:#a5f3fc;text-transform:uppercase;margin-bottom:8px;">
                · สวัสดี, {{ auth()->user()->name ?? 'ฝนทิพย์' }}
            </div>
            <h1 style="font-size:48px;font-weight:300;color:#fff;letter-spacing:-0.02em;margin:0;">
                ปราสาทแห่งความคิด <span class="xdr-italic-th" style="font-style:italic;color:#c4b5fd;">ของคุณ</span>
            </h1>
        </div>
        <a href="{{ route('xdreamer.studio') }}" style="
            padding:12px 20px;border-radius:12px;
            background:linear-gradient(135deg, hsl({{ 160+$hueShift }},70%,50%), hsl({{ 270+$hueShift }},70%,60%));
            color:#fff;border:none;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;">+ เริ่มงานใหม่</a>
    </div>

    {{-- Stat cards --}}
    <div class="rp-stat-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:40px;">
        @foreach($statCards as $s)
        @php $h = ($s['hue'] + $hueShift) % 360; @endphp
        <div style="padding:22px;border-radius:16px;position:relative;overflow:hidden;
            background:rgba(15,23,42,0.5);border:1px solid rgba(255,255,255,0.06);">
            <div style="position:absolute;top:0;right:0;width:80px;height:80px;
                background:radial-gradient(circle, hsla({{ $h }},70%,55%,0.35), transparent 70%);filter:blur(10px);"></div>
            <div style="font-size:11px;color:#94a3b8;letter-spacing:0.05em;">{{ $s['l'] }}</div>
            <div style="font-size:36px;font-weight:300;color:#fff;margin-top:6px;letter-spacing:-0.02em;">{{ $s['v'] }}</div>
            <div style="font-size:11px;color:hsl({{ $h }},70%,65%);margin-top:4px;">{{ $s['d'] }}</div>
        </div>
        @endforeach
    </div>

    <div class="rp-dash-main" style="display:grid;grid-template-columns:1.6fr 1fr;gap:24px;">
        {{-- Recent works --}}
        <div style="padding:24px;border-radius:20px;background:rgba(15,23,42,0.5);border:1px solid rgba(255,255,255,0.06);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <div style="font-size:15px;color:#fff;font-weight:500;">ผลงานล่าสุด</div>
                <a href="{{ route('xdreamer.gallery') }}" style="font-size:12px;color:#a5f3fc;text-decoration:none;">ดูทั้งหมด →</a>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;">
                @for($i = 0; $i < 8; $i++)
                @php $h1 = ($i*41+$hueShift)%360; $h2 = ($i*41+60+$hueShift)%360; @endphp
                <div style="aspect-ratio:1;border-radius:10px;background:linear-gradient(135deg, hsl({{ $h1 }},55%,16%), hsl({{ $h2 }},55%,9%));border:1px solid rgba(255,255,255,0.06);position:relative;overflow:hidden;">
                    <svg width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none" style="position:absolute;inset:0;">
                        @for($j = 0; $j < 8; $j++)
                        @php $hh = ($i*41+$j*10+$hueShift)%360; $cx = 30 + sin($j+$i)*30; $sx = -5 + $j*14; $ex = 105 - $j*12; @endphp
                        <path d="M{{ $sx }} 110 Q{{ $cx }} 50 {{ $ex }} -5" stroke="hsl({{ $hh }}, 80%, 65%)" stroke-width="0.5" fill="none" opacity="0.7"/>
                        @endfor
                    </svg>
                </div>
                @endfor
            </div>
        </div>

        {{-- Usage chart --}}
        <div style="padding:24px;border-radius:20px;background:rgba(15,23,42,0.5);border:1px solid rgba(255,255,255,0.06);">
            <div style="font-size:15px;color:#fff;font-weight:500;margin-bottom:6px;">การใช้งาน 30 วัน</div>
            <div style="font-size:28px;font-weight:300;color:#fff;">{{ $stats ? number_format($stats['this_month']) : '0' }} <span style="font-size:13px;color:#64748b;">งาน</span></div>
            <svg width="100%" height="140" viewBox="0 0 300 140" style="margin-top:16px;">
                <defs>
                    <linearGradient id="chart-fill" x1="0" x2="0" y1="0" y2="1">
                        <stop offset="0%" stop-color="hsl({{ 220+$hueShift }},80%,60%)" stop-opacity="0.4"/>
                        <stop offset="100%" stop-color="hsl({{ 220+$hueShift }},80%,60%)" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                @php
                    $points = [];
                    for ($i = 0; $i < 30; $i++) {
                        $x = $i * 10;
                        $y = 100 - (sin($i * 0.4) * 20 + sin($i * 0.15) * 30 + 50);
                        $points[] = "L$x,$y";
                    }
                    $pathLine = 'M0,110 ' . implode(' ', $points);
                    $pathFill = $pathLine . ' L300,140 L0,140 Z';
                @endphp
                <path d="{{ $pathFill }}" fill="url(#chart-fill)"/>
                <path d="{{ $pathLine }}" stroke="hsl({{ 220+$hueShift }},80%,65%)" stroke-width="1.5" fill="none"/>
            </svg>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.06);">
                <div style="font-size:11px;color:#94a3b8;margin-bottom:8px;">การแบ่งใช้งาน</div>
                @foreach($usage as $r)
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;font-size:12px;">
                    <div style="width:60px;color:#94a3b8;">{{ $r['l'] }}</div>
                    <div style="flex:1;height:4px;border-radius:4px;background:rgba(255,255,255,0.06);overflow:hidden;">
                        <div style="width:{{ $r['v'] }}%;height:100%;background:hsl({{ $r['hue']+$hueShift }},70%,60%);"></div>
                    </div>
                    <div style="width:36px;text-align:right;color:#e2e8f0;font-family:ui-monospace,monospace;">{{ $r['v'] }}%</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Collections --}}
    <div style="margin-top:40px;">
        <div style="font-size:15px;color:#fff;font-weight:500;margin-bottom:20px;">ปราสาทย่อยของคุณ (collections)</div>
        <div class="rp-grid-3" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
            @foreach($collections as $c)
            @php $h = ($c['hue'] + $hueShift) % 360; @endphp
            <div style="padding:20px;border-radius:16px;
                background:linear-gradient(160deg, hsla({{ $h }},50%,20%,0.5), hsla({{ $h+30 }},50%,10%,0.5));
                border:1px solid hsla({{ $h }},60%,40%,0.3);cursor:pointer;">
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:4px;margin-bottom:14px;">
                    @for($j = 0; $j < 3; $j++)
                    <div style="aspect-ratio:1;border-radius:6px;
                        background:linear-gradient(135deg, hsl({{ $h+$j*20 }}, 70%, 55%), hsl({{ $h+$j*20+30 }}, 70%, 45%));
                        opacity:{{ 0.8 - $j*0.15 }};"></div>
                    @endfor
                </div>
                <div style="font-size:14px;color:#fff;font-weight:500;">{{ $c['n'] }}</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:4px;">{{ $c['c'] }} ผลงาน</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
