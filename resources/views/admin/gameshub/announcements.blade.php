@extends($adminLayout ?? 'layouts.admin')
@section('title', 'XGamesHub · ประกาศบนฮับ')
@section('page-title', 'XGamesHub · ประกาศบนฮับ')
@section('content')
@include('admin.gameshub._nav')
@php
    $card = 'p-5 rounded-xl bg-white dark:bg-gray-800 shadow border border-gray-100 dark:border-gray-700';
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm';
    $label = 'block text-sm text-gray-700 dark:text-gray-300';
@endphp
<section class="{{ $card }} mb-6">
    <h2 class="mb-1 font-semibold text-gray-900 dark:text-white">เพิ่มประกาศ</h2>
    <p class="mb-3 text-xs text-gray-500">แถบประกาศเหนือสไลด์หน้าแรกของฮับ แสดงสูงสุด 3 อันล่าสุดที่อยู่ในช่วงเวลา เว้นเวลาว่าง = แสดงทันทีและไม่มีกำหนดหยุด</p>
    @include('admin.gameshub._announcement-form', ['a' => null, 'action' => route('admin.gameshub.announcements.store'), 'input' => $input, 'label' => $label])
</section>

<div class="space-y-3">
@forelse($announcements as $a)
    <details class="{{ $card }}">
        <summary class="flex flex-wrap items-center gap-2 cursor-pointer text-sm">
            <span @class([
                'px-2 py-0.5 text-xs rounded-full',
                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' => $a->isLive(),
                'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => ! $a->isLive(),
            ])>{{ $a->isLive() ? 'แสดงอยู่' : 'ไม่แสดง' }}</span>
            <span class="text-gray-500">{{ \App\Models\GamesHubAnnouncement::TONES[$a->tone] ?? $a->tone }}</span>
            <b class="text-gray-900 dark:text-white">{{ \Illuminate\Support\Str::limit($a->message, 90) }}</b>
            <span class="text-xs text-gray-500">{{ $a->starts_at?->timezone('Asia/Bangkok')->format('Y-m-d H:i') ?? 'ทันที' }} → {{ $a->ends_at?->timezone('Asia/Bangkok')->format('Y-m-d H:i') ?? 'ไม่กำหนด' }}</span>
        </summary>
        <div class="mt-4">
            @include('admin.gameshub._announcement-form', ['a' => $a, 'action' => route('admin.gameshub.announcements.update', $a), 'input' => $input, 'label' => $label])
            <form method="post" action="{{ route('admin.gameshub.announcements.destroy', $a) }}" class="mt-3" onsubmit="return confirm('ลบประกาศนี้ถาวร?')">
                @csrf @method('DELETE')
                <button class="px-3 py-1.5 text-sm rounded-lg bg-red-600 text-white">ลบประกาศ</button>
            </form>
        </div>
    </details>
@empty
    <p class="{{ $card }} text-sm text-gray-500">ยังไม่มีประกาศ</p>
@endforelse
</div>
<div class="mt-4">{{ $announcements->links() }}</div>
@endsection
