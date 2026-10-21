@extends($adminLayout ?? 'layouts.admin')
@section('title', 'XGamesHub · ความเห็นและคำแนะนำ')
@section('page-title', 'XGamesHub · ความเห็นและคำแนะนำ')
@section('content')
@include('admin.gameshub._nav')
@php
    $card = 'p-5 rounded-xl bg-white dark:bg-gray-800 shadow border border-gray-100 dark:border-gray-700';
    $input = 'rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm';
    $labels = ['pending' => 'รอตรวจ', 'approved' => 'เผยแพร่แล้ว', 'rejected' => 'ไม่เผยแพร่'];
@endphp
<form method="get" class="flex flex-wrap items-center gap-3 mb-4">
    <div class="flex flex-wrap gap-2">
        @foreach($labels as $key => $label)
            <a href="{{ route('admin.gameshub.comments', array_filter(['status' => $key, 'game' => $campaign?->slug])) }}" @class([
                'px-3 py-1.5 text-sm rounded-lg',
                'bg-indigo-600 text-white' => $status === $key,
                'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' => $status !== $key,
            ])>{{ $label }} <span class="opacity-70">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>
    <input type="hidden" name="status" value="{{ $status }}">
    <select name="game" class="{{ $input }}" onchange="this.form.submit()">
        <option value="">ทุกเกม</option>
        @foreach($campaigns as $c)<option value="{{ $c->slug }}" @selected($campaign?->id === $c->id)>{{ $c->name }}</option>@endforeach
    </select>
</form>
<p class="mb-4 text-sm text-gray-500">คำแนะนำที่ผู้เล่นฝากถึงทีม (รวมที่แนบมากับการบริจาคและเลือกให้เผยแพร่) แสดงต่อสาธารณะเฉพาะที่อนุมัติ เปลี่ยนผลตรวจเพื่อซ่อนข้อความที่เคยเผยแพร่ได้</p>

@if($comments->isNotEmpty())
    <form id="bulk-comments" method="post" action="{{ route('admin.gameshub.comments.bulk') }}" class="{{ $card }} flex flex-wrap items-end gap-3 mb-4">
        @csrf
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" onclick="document.querySelectorAll('[data-bulk-comment]').forEach(c => c.checked = this.checked)"> เลือกทั้งหมดในหน้านี้</label>
        <input name="moderation_note" maxlength="500" placeholder="บันทึกของทีม (ไม่บังคับ)" class="{{ $input }} flex-1 min-w-[14rem]">
        <button name="status" value="approved" class="px-3 py-2 text-sm rounded-lg bg-green-600 text-white">เผยแพร่ที่เลือก</button>
        <button name="status" value="rejected" class="px-3 py-2 text-sm rounded-lg bg-red-600 text-white">ไม่เผยแพร่ที่เลือก</button>
    </form>
@endif

<div class="space-y-3">
@forelse($comments as $c)
    <article class="{{ $card }}">
        <div class="flex items-start gap-3">
            <input type="checkbox" name="ids[]" value="{{ $c->id }}" form="bulk-comments" data-bulk-comment class="mt-1" aria-label="เลือกความเห็นนี้">
            <div class="flex-1 min-w-0">
                <p class="text-sm"><b class="text-gray-900 dark:text-white">{{ $c->display_name }}</b> <span class="text-gray-500">· {{ $c->campaign->name }} · {{ $c->created_at->diffForHumans() }}{{ $c->game_donation_id ? ' · แนบมากับการบริจาค' : '' }}</span></p>
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line break-words">{{ $c->body }}</p>
                @if($c->moderation_note)<p class="mt-1 text-xs text-gray-500">บันทึกทีม: {{ $c->moderation_note }}</p>@endif
            </div>
        </div>
        <form method="post" action="{{ route('admin.game-support.moderate', $c) }}" class="flex flex-wrap gap-2 mt-3">
            @csrf
            <input name="moderation_note" maxlength="500" placeholder="บันทึกของทีม (ไม่บังคับ)" class="{{ $input }}">
            @if($c->status !== 'approved')<button name="status" value="approved" class="px-3 py-1.5 text-sm rounded-lg bg-green-600 text-white">เผยแพร่</button>@endif
            @if($c->status !== 'rejected')<button name="status" value="rejected" class="px-3 py-1.5 text-sm rounded-lg bg-red-600 text-white">ไม่เผยแพร่</button>@endif
        </form>
    </article>
@empty
    <p class="{{ $card }} text-sm text-gray-500">ไม่มีความเห็นในสถานะนี้</p>
@endforelse
</div>
<div class="mt-4">{{ $comments->links() }}</div>
@endsection
