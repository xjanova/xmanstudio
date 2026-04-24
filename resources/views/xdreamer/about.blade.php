@extends('layouts.xdreamer')
@section('title', 'About · X-DREAMER')
@section('meta_description', 'เราเชื่อว่าจินตนาการไม่มีเส้นเขตแดน — manifesto ของ X-DREAMER')

@php
    $hueShift = 70;
    $page = 'about';
    $team = [
        ['n'=>'ฝนทิพย์ ศ.','r'=>'Founder / AI research'],
        ['n'=>'อารียา ก.','r'=>'Design / Product'],
        ['n'=>'ปัณณวิชญ์ ท.','r'=>'Engineering'],
    ];
@endphp

@section('content')
<div style="padding-top:140px;padding-bottom:80px;">
    <div style="max-width:860px;margin:0 auto;padding:0 48px;">
        <div style="font-size:12px;letter-spacing:0.16em;color:#a5f3fc;text-transform:uppercase;margin-bottom:14px;">· manifesto</div>
        <h1 style="font-size:clamp(48px, 7vw, 96px);font-weight:200;color:#fff;letter-spacing:-0.03em;line-height:1;margin:0;">
            เราเชื่อว่า<br>
            <span class="xdr-italic-th" style="font-style:italic;background:linear-gradient(120deg, hsl({{ 160+$hueShift }},80%,70%), hsl({{ 280+$hueShift }},80%,75%));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">จินตนาการ</span><br>
            ไม่มีเส้นเขตแดน
        </h1>
        <div style="margin-top:48px;font-size:19px;line-height:1.7;color:rgba(203,213,225,0.85);font-weight:300;">
            <p>
                X-DREAMER เกิดขึ้นในปี {{ date('Y') - 2 }} ที่เชียงใหม่ จากคำถามหนึ่ง — ถ้าเครื่องมือที่ทรงพลังที่สุดในการสร้างภาพ
                ไม่ใช่การวาด แต่เป็นการ "ทอ" ล่ะ? ทอเส้นใยของความคิด ความรู้สึก ความฝัน เข้าด้วยกันจนกลายเป็นภาพเดียว
            </p>
            <p>
                เราไม่ได้สร้างแค่เครื่องมือ AI — เราสร้างภาษาใหม่สำหรับการสื่อสารกับจินตนาการ
                ภาษาที่ศิลปิน นักเขียน นักออกแบบ และเด็กวัย 10 ขวบ ใช้ได้เท่าเทียมกัน
            </p>
            <p style="font-style:italic;font-size:22px;padding:24px 0;color:#c4b5fd;border-left:2px solid hsl({{ 270+$hueShift }},70%,60%);padding-left:24px;margin-top:40px;">
                "ทุกความฝันควรมีผืนผ้าที่จะถูกทอ"
            </p>
        </div>

        <div class="rp-grid-3" style="margin-top:80px;display:grid;grid-template-columns:repeat(3,1fr);gap:32px;">
            @foreach($team as $i => $p)
            @php $h = (160 + $i * 60 + $hueShift) % 360; @endphp
            <div style="text-align:center;">
                <div style="width:120px;height:120px;border-radius:50%;margin:0 auto 16px;
                    background:conic-gradient(from {{ $i*60 }}deg, hsl({{ $h }},70%,55%), hsl({{ $h+60 }},70%,60%), hsl({{ $h+120 }},70%,55%), hsl({{ $h }},70%,55%));
                    padding:3px;">
                    <div style="width:100%;height:100%;border-radius:50%;
                        background:linear-gradient(135deg, hsl({{ $h }},50%,20%), hsl({{ $h+60 }},50%,10%));
                        display:grid;place-items:center;font-size:36px;color:hsl({{ $h }},70%,75%);font-weight:300;">{{ mb_substr($p['n'], 0, 1) }}</div>
                </div>
                <div style="font-size:16px;color:#fff;font-weight:500;">{{ $p['n'] }}</div>
                <div style="font-size:12px;color:#94a3b8;margin-top:4px;">{{ $p['r'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
