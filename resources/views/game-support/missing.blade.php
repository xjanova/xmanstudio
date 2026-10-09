@extends('game-support.layout')
@section('title', 'ไม่พบหน้านี้')
@section('crumb', 'ไม่พบหน้านี้')
@section('content')
<div class="gs-hero gs-art gs-lost" style="--art: url('{{ asset('images/gameshub/nova-lost.webp') }}')">
    <span class="gs-tag">404 · SIGNAL LOST</span>
    <h1>{{ isset($campaign) ? $campaign->name . ' ยังไม่เปิดในศูนย์ชุมชน' : 'โนวาหาหน้านี้ไม่เจอ' }}</h1>
    <p>{{ isset($campaign) ? 'เกมนี้ปิดรับการสนับสนุนชั่วคราว ลองดูเกมอื่นในศูนย์ชุมชน หรือกลับไปเล่นที่ฮับก่อนนะ' : 'ลิงก์นี้อาจเปลี่ยนไปแล้ว หรือเกมนี้ยังไม่มีหน้าชุมชน ลองกลับไปเลือกเกมจากหน้าอันดับ' }}</p>
    <div class="gs-presets">
        <a class="gs-button" href="{{ route('game-support.index') }}">ดูทุกเกมในชุมชน</a>
        <a href="{{ rtrim(config('game-support.hub_origin'), '/') }}/">กลับ XMAN GAMES HUB</a>
    </div>
</div>
@endsection
