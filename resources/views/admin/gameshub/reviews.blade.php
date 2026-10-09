@extends($adminLayout ?? 'layouts.admin')
@section('title', 'XGamesHub · รีวิวเกม')
@section('page-title', 'XGamesHub · รีวิวเกม')
@section('content')
@include('admin.gameshub._nav')
@php
    $card = 'p-5 rounded-xl bg-white dark:bg-gray-800 shadow border border-gray-100 dark:border-gray-700';
    $input = 'rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm';
    $labels = ['pending' => 'รออนุมัติ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ไม่อนุมัติ'];
@endphp
<form method="get" class="flex flex-wrap items-center gap-3 mb-4">
    <div class="flex flex-wrap gap-2">
        @foreach($labels as $key => $label)
            <a href="{{ route('admin.gameshub.reviews', array_filter(['status' => $key, 'game' => $campaign?->slug])) }}" @class([
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

@if($reviews->isNotEmpty())
    <form id="bulk-reviews" method="post" action="{{ route('admin.gameshub.reviews.bulk') }}" class="{{ $card }} flex flex-wrap items-end gap-3 mb-4">
        @csrf
        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" onclick="document.querySelectorAll('[data-bulk-review]').forEach(c => c.checked = this.checked)"> เลือกทั้งหมดในหน้านี้</label>
        <input name="admin_note" maxlength="1000" placeholder="เหตุผล (จำเป็นเมื่อไม่อนุมัติ)" class="{{ $input }} flex-1 min-w-[14rem]">
        <button name="status" value="approved" class="px-3 py-2 text-sm rounded-lg bg-green-600 text-white">อนุมัติที่เลือก</button>
        <button name="status" value="rejected" class="px-3 py-2 text-sm rounded-lg bg-red-600 text-white">ไม่อนุมัติที่เลือก</button>
    </form>
@endif

<div class="space-y-3">
@forelse($reviews as $r)
    <article class="{{ $card }}">
        <div class="flex flex-wrap items-start gap-3">
            <input type="checkbox" name="ids[]" value="{{ $r->id }}" form="bulk-reviews" data-bulk-review class="mt-1" aria-label="เลือกรีวิวนี้">
            <div class="flex-1 min-w-0">
                <p class="text-sm">
                    <span class="text-amber-500">{{ str_repeat('★', $r->rating) }}<span class="text-gray-300 dark:text-gray-600">{{ str_repeat('★', 5 - $r->rating) }}</span></span>
                    <b class="text-gray-900 dark:text-white">{{ $r->reviewable?->name }}</b>
                    <span class="text-gray-500">· {{ $r->user?->name }} ({{ $r->user?->email }}) · แสดงเป็น "{{ \App\Http\Controllers\GamesHubController::publicName($r->user?->name) }}" · {{ $r->updated_at->diffForHumans() }}</span>
                    @if($r->is_featured)<span class="ml-1 px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">ปักไว้บนสุด</span>@endif
                </p>
                @if($r->title)<p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $r->title }}</p>@endif
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line break-words">{{ $r->comment }}</p>
                @if($r->admin_note)<p class="mt-1 text-xs text-gray-500">บันทึกทีม: {{ $r->admin_note }}</p>@endif
            </div>
        </div>
        <div class="flex flex-wrap items-end gap-2 mt-3">
            @if($r->status !== 'approved')
                <form method="post" action="{{ route('admin.gameshub.reviews.moderate', $r) }}">@csrf<button name="status" value="approved" class="px-3 py-1.5 text-sm rounded-lg bg-green-600 text-white">อนุมัติ</button></form>
            @else
                <form method="post" action="{{ route('admin.gameshub.reviews.feature', $r) }}">@csrf<button class="px-3 py-1.5 text-sm rounded-lg bg-purple-600 text-white">{{ $r->is_featured ? 'เลิกปัก' : 'ปักไว้บนสุด' }}</button></form>
            @endif
            @if($r->status !== 'rejected')
                <form method="post" action="{{ route('admin.gameshub.reviews.moderate', $r) }}" class="flex gap-2">
                    @csrf
                    <input name="admin_note" required maxlength="1000" placeholder="เหตุผลที่ไม่อนุมัติ" class="{{ $input }}">
                    <button name="status" value="rejected" class="px-3 py-1.5 text-sm rounded-lg bg-red-600 text-white">ไม่อนุมัติ</button>
                </form>
            @endif
        </div>
    </article>
@empty
    <p class="{{ $card }} text-sm text-gray-500">ไม่มีรีวิวในสถานะนี้</p>
@endforelse
</div>
<div class="mt-4">{{ $reviews->links() }}</div>
@endsection
