@extends('game-support.layout')
@section('title', 'อันดับเกมและผู้สนับสนุน')
@section('crumb', 'อันดับและทุกเกม')
@section('content')
<div class="gs-hero gs-art" style="--art: url('{{ asset('images/gameshub/community.webp') }}')"><span class="gs-tag">BUILD THE NEXT WORLD</span><h1>เลือกเกมที่อยากให้ไปต่อ</h1><p>ช่วยด้วยการลองเล่น ฝากคำแนะนำ โหวต ให้ดาว หรือร่วมสนับสนุนการพัฒนา</p></div>
<div class="gs-grid gs-three">
@foreach(['raised' => 'ยอดสนับสนุนที่ยืนยันแล้ว', 'votes' => 'เกมที่อยากให้ทำมากที่สุด', 'stars' => 'คะแนนดาวจากผู้เล่น'] as $metric => $label)
<section class="gs-card"><h2>{{ $label }}</h2><ol class="gs-ranking">
@forelse(collect($games)->filter(fn ($g) => $metric === 'stars' ? $g['rating_count'] > 0 : $g[$metric] > 0)->sort(function ($a, $b) use ($metric) { return ($b[$metric] <=> $a[$metric]) ?: ($metric === 'stars' ? ($b['rating_count'] <=> $a['rating_count']) : 0) ?: strcmp($a['slug'], $b['slug']); })->take(10) as $g)
<li><a href="{{ route('game-support.show', $g['slug']) }}">{{ $g['name'] }}</a><strong>{{ $metric === 'raised' ? '฿'.number_format($g[$metric], 2) : ($metric === 'stars' ? ($g['rating_count'] ? number_format($g['stars'], 2).' ★' : 'ยังไม่มีคะแนน') : $g[$metric].' โหวต') }}</strong>@if($metric === 'stars')<small>{{ $g['rating_count'] }} คะแนน</small>@endif</li>
@empty<li>ยังไม่มีข้อมูลในหมวดนี้</li>@endforelse</ol></section>@endforeach
</div><p class="gs-muted">หนึ่งบัญชีเลือกเกมที่อยากให้ทำมากที่สุดได้หนึ่งเกม เปลี่ยนได้ทุกเมื่อ และให้ดาวแต่ละเกมได้แยกกัน คะแนนเท่ากันไม่หมายถึงเกมหนึ่งได้รับความนิยมมากกว่าอีกเกม</p>
<h2>ทุกโครงการ</h2><div class="gs-grid gs-three">@foreach($games as $g)<article class="gs-card"><h3><a href="{{ route('game-support.show', $g['slug']) }}">{{ $g['name'] }}</a></h3><p>฿{{ number_format($g['raised'], 2) }} @if($g['goal'])/ เป้า ฿{{ number_format($g['goal']) }}@else · ยังไม่กำหนดเป้าทุน@endif</p><p>{{ $g['votes'] }} โหวต · {{ $g['rating_count'] ? number_format($g['stars'], 2).' ★ ('.$g['rating_count'].')' : 'ยังไม่มีคะแนนดาว' }}</p><a class="gs-button" href="{{ route('game-support.show', $g['slug']) }}">ร่วมสร้างเกมนี้ →</a></article>@endforeach</div>
@endsection
