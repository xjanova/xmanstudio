@extends('layouts.xdreamer')
@section('title', 'Docs · X-DREAMER')
@section('meta_description', 'คู่มือการใช้งาน X-DREAMER — Getting started, Models, API, Guides')

@php
    $hueShift = 70;
    $page = 'docs';
    $sections = [
        ['group'=>'Intro','items'=>['Getting started','Concepts','Your first weave']],
        ['group'=>'Models','items'=>['loom-v4.2','loom-pro','loom-fast','loom-video','loom-3d']],
        ['group'=>'API','items'=>['Authentication','Generate endpoint','Webhooks','Rate limits','Errors']],
        ['group'=>'Guides','items'=>['Prompt crafting','Negative prompts','Seeds & reproducibility']],
    ];
    $concepts = [
        ['t'=>'Thread','d'=>'หน่วยย่อยของแนวคิด — คำ, สี, mood'],
        ['t'=>'Weave','d'=>'กระบวนการทอ thread หลายเส้นเป็นผืน'],
        ['t'=>'Fabric','d'=>'ผลลัพธ์สุดท้าย: ภาพ วิดีโอ เสียง หรือ 3D'],
        ['t'=>'Citadel','d'=>'ที่เก็บผลงานของคุณในระบบ'],
    ];
@endphp

@section('content')
<div class="rp-docs" x-data="{ active: 'Getting started' }" style="padding-top:80px;min-height:100vh;display:grid;grid-template-columns:260px 1fr 240px;">
    <aside class="rp-docs-left" style="border-right:1px solid rgba(255,255,255,0.06);padding:32px;height:calc(100vh - 80px);overflow-y:auto;">
        <input placeholder="ค้นหา docs..." style="width:100%;padding:10px 12px;border-radius:10px;margin-bottom:24px;background:rgba(2,6,23,0.5);color:#f1f5f9;border:1px solid rgba(255,255,255,0.1);font-size:13px;font-family:inherit;outline:none;">
        @foreach($sections as $s)
        <div style="margin-bottom:24px;">
            <div style="font-size:10px;color:#64748b;letter-spacing:0.14em;text-transform:uppercase;margin-bottom:10px;">{{ $s['group'] }}</div>
            @foreach($s['items'] as $it)
            <div @click="active='{{ $it }}'"
                :style="active==='{{ $it }}' ? 'color:#fff;background:hsla({{ 220+$hueShift }},60%,50%,0.15);border-left:2px solid hsl({{ 220+$hueShift }},70%,60%);' : 'color:#94a3b8;background:transparent;border-left:2px solid transparent;'"
                style="padding:7px 10px;border-radius:8px;font-size:13px;cursor:pointer;">{{ $it }}</div>
            @endforeach
        </div>
        @endforeach
    </aside>

    <main class="rp-docs-center" style="padding:48px 60px;max-width:820px;height:calc(100vh - 80px);overflow-y:auto;">
        <div style="font-size:12px;color:#94a3b8;margin-bottom:8px;">Intro / <span x-text="active"></span></div>
        <h1 style="font-size:44px;font-weight:300;color:#fff;letter-spacing:-0.02em;margin:0 0 20px;" x-text="active"></h1>
        <p style="font-size:17px;color:rgba(203,213,225,0.85);line-height:1.7;font-weight:300;">
            ยินดีต้อนรับสู่ X-DREAMER — แพลตฟอร์มทอความฝันด้วย AI
            คู่มือนี้จะพาคุณผ่านการทอเส้นใยแรก ตั้งแต่การเขียน prompt จนถึงการ export ผลงานออกมาใช้จริง
        </p>

        <h2 style="font-size:24px;font-weight:500;color:#fff;margin-top:48px;margin-bottom:16px;">ติดตั้ง</h2>
        <p style="color:rgba(203,213,225,0.8);line-height:1.7;">ใช้ผ่านเว็บได้ทันที หรือเรียกผ่าน API:</p>
        <pre style="padding:20px;border-radius:12px;margin-top:12px;background:rgba(2,6,23,0.7);border:1px solid rgba(255,255,255,0.08);font-size:13px;font-family:ui-monospace,monospace;color:#a5f3fc;overflow-x:auto;"><code>npm install @xdreamer/loom

import { weave } from '@xdreamer/loom'

const result = await weave({
  prompt: 'ปราสาทลอยฟ้าจากเส้นใยแสง',
  model: 'loom-v4.2',
  aspect: '16:9',
})</code></pre>

        <h2 style="font-size:24px;font-weight:500;color:#fff;margin-top:48px;margin-bottom:16px;">แนวคิดพื้นฐาน</h2>
        <div class="rp-grid-2" style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-top:16px;">
            @foreach($concepts as $c)
            <div style="padding:16px;border-radius:12px;background:rgba(15,23,42,0.4);border:1px solid rgba(255,255,255,0.06);">
                <div style="font-size:13px;color:#a5f3fc;font-weight:500;">{{ $c['t'] }}</div>
                <div style="font-size:12px;color:#94a3b8;margin-top:4px;">{{ $c['d'] }}</div>
            </div>
            @endforeach
        </div>

        <h2 style="font-size:24px;font-weight:500;color:#fff;margin-top:48px;margin-bottom:16px;">ตัวอย่าง</h2>
        <pre style="padding:20px;border-radius:12px;margin-top:12px;background:rgba(2,6,23,0.7);border:1px solid rgba(255,255,255,0.08);font-size:13px;font-family:ui-monospace,monospace;color:#a5f3fc;overflow-x:auto;"><code>// Webhook payload
{
  "event": "weave.completed",
  "weave_id": "wv_8f2k3l",
  "fabric_url": "https://cdn.x-dreamer.ai/...",
  "credits_used": 3
}</code></pre>
    </main>

    <aside class="rp-docs-right" style="border-left:1px solid rgba(255,255,255,0.06);padding:32px;height:calc(100vh - 80px);overflow-y:auto;">
        <div style="font-size:10px;color:#64748b;letter-spacing:0.14em;text-transform:uppercase;margin-bottom:12px;">On this page</div>
        @foreach(['ติดตั้ง','แนวคิดพื้นฐาน','ตัวอย่าง','Next steps'] as $i => $t)
        <div style="padding:6px 0;font-size:12px;color:{{ $i === 0 ? '#fff' : '#94a3b8' }};cursor:pointer;">{{ $t }}</div>
        @endforeach
    </aside>
</div>
@endsection
