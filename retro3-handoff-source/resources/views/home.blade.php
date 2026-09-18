@extends('layouts.retro', ['activeNav' => 'home'])

@section('title', 'XMAN Studio — Digital Craft Division · Est. MMXVIII')
@section('description', 'XMAN Studio — Blockchain, AI, Web & Mobile development. Bangkok · Est. MMXVIII')

@section('content')
    @include('partials.retro-hero')

    {{-- Ticker --}}
    <div style="border-top:1px solid rgba(212,175,55,.3);border-bottom:1px solid rgba(212,175,55,.3);background:linear-gradient(180deg,rgba(212,175,55,.04),transparent);overflow:hidden;padding:14px 0;">
        <div style="display:flex;gap:32px;white-space:nowrap;animation:retro-ticker 40s linear infinite;">
            @for($r = 0; $r < 3; $r++)
                @foreach(['BLOCKCHAIN','◆','WEB','◆','MOBILE','◆','AI & ML','◆','IOT','◆','CONSULTING','◆','METAL-X RECORDS','◆','EST · MMXVIII','◆'] as $t)
                    <span style="font-family:var(--font-ui);font-size:12px;letter-spacing:.3em;color:{{ $t === '◆' ? 'var(--tron-gold)' : 'var(--fg-2)' }};">{{ $t }}</span>
                @endforeach
            @endfor
        </div>
        <style>@keyframes retro-ticker{from{transform:translateX(0)}to{transform:translateX(-33.333%)}}</style>
    </div>

    @include('partials.retro-services')

    @include('partials.retro-metalx')

    {{-- Manifesto seal block --}}
    <section style="padding:100px 32px;background:linear-gradient(180deg,var(--tron-void),var(--tron-navy));border-top:1px solid rgba(212,175,55,.25);border-bottom:1px solid rgba(212,175,55,.25);">
        <div style="max-width:820px;margin:0 auto;text-align:center;">
            <div class="tron-seal-ring" style="width:90px;height:90px;border-radius:50%;margin:0 auto 32px;display:flex;align-items:center;justify-content:center;flex-direction:column;">
                <span style="font-family:var(--font-display);font-size:14px;color:var(--tron-gold);line-height:1;">EST</span>
                <span style="font-family:var(--font-display);font-size:22px;color:var(--tron-gold);line-height:1;">MMXVIII</span>
            </div>
            <p style="font-family:var(--font-serif);font-size:28px;line-height:1.4;font-style:italic;color:var(--fg-1);margin:0 0 24px;">
                "งานฝีมือไม่ได้เร็วเสมอไป — แต่ทุกบรรทัดของโค้ด ทุกพิกเซลของดีไซน์ เราสลักชื่อไว้"
            </p>
            <div style="font-family:var(--font-ui);font-size:11px;letter-spacing:.3em;color:var(--tron-gold);text-transform:uppercase;">— XMAN STUDIO · BANGKOK —</div>
        </div>
    </section>
@endsection
