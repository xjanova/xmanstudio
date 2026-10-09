@extends('game-support.layout')
@section('title', 'ไอเท็มของฉัน')
@section('content')
<a href="{{ route('game-support.index') }}">← อันดับและเกมทั้งหมด</a>
<div class="gs-hero"><span class="gs-tag">SUPPORTER ITEMS</span><h1>ไอเท็มและโค้ดของฉัน</h1><p>ไอเท็มที่ได้จากการสนับสนุนที่ทีมยืนยันแล้ว หรือที่ทีมมอบให้ กด "แลกบนเว็บเกม" หรือกรอกโค้ดที่ปุ่ม "แลกโค้ด" ใน XMAN GAMES HUB เกมบนฮับที่รองรับจะปลดล็อกให้เอง โค้ดหนึ่งใช้ได้ตามจำนวนเครื่องที่ระบุ อย่าแชร์โค้ดให้คนอื่น</p></div>
@php($kinds = \App\Models\GameItem::KINDS)
@forelse($entitlements->groupBy(fn ($e) => $e->item->campaign->name) as $game => $rows)
<section class="gs-card"><h2>{{ $game }}</h2>
@foreach($rows as $e)
<div class="gs-reward"><strong>{{ $e->item->name }}</strong> <span class="gs-tag">{{ $kinds[$e->item->kind] ?? $e->item->kind }}</span>
@if($e->item->description)<p>{{ $e->item->description }}</p>@endif
@if($e->status === 'granted')<p>โค้ด <code class="gs-code">{{ $e->code }}</code> <a class="gs-small" href="{{ rtrim(config('game-support.hub_origin'), '/') }}/#redeem={{ $e->code }}" target="_blank" rel="noopener">แลกบนเว็บเกม ↗</a></p><p class="gs-muted">ใช้แล้ว {{ $e->redeem_count }} จาก {{ $e->item->max_devices }} เครื่อง · {{ $e->source === 'donation' ? 'จากการสนับสนุน '.($e->donation?->public_id ?? '') : 'ทีมมอบให้' }} · {{ $e->created_at->format('Y-m-d') }}</p>
@else<p class="gs-muted">โค้ดนี้ถูกยกเลิกแล้ว (เช่น ยกเลิกยอดสนับสนุน) ติดต่อทีมหากคิดว่าไม่ถูกต้อง</p>@endif
<a href="{{ route('game-support.show', $e->item->campaign->slug) }}">หน้าเกม →</a></div>
@endforeach
</section>
@empty
<section class="gs-card"><p>ยังไม่มีไอเท็ม เมื่อทีมยืนยันยอดสนับสนุนของเกมที่มีไอเท็มในระดับรางวัล โค้ดจะขึ้นที่นี่</p><a class="gs-button" href="{{ route('game-support.index') }}">ดูเกมที่เปิดรับการสนับสนุน</a></section>
@endforelse
@endsection
