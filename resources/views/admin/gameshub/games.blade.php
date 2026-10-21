@extends($adminLayout ?? 'layouts.admin')
@section('title', 'XGamesHub · เกม รางวัล และฮีโร่')
@section('page-title', 'XGamesHub · เกม รางวัล และฮีโร่')
@section('content')
@include('admin.gameshub._nav')
@php
    $card = 'p-5 rounded-xl bg-white dark:bg-gray-800 shadow border border-gray-100 dark:border-gray-700';
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm';
    $label = 'block text-sm text-gray-700 dark:text-gray-300';
@endphp
<div class="{{ $card }} mb-6 text-sm text-gray-600 dark:text-gray-300 space-y-1">
    <p><b class="text-gray-900 dark:text-white">ฮีโร่ของฮับ</b> — ใส่ลำดับ (1, 2, 3 …) เพื่อดันเกมขึ้นก่อนในสไลด์หน้าแรก เว้นว่าง = ตามลำดับเดิมของฮับ ติ๊ก "ซ่อน" เพื่อเอาออกจากสไลด์ (การ์ดในคลังยังอยู่) ฮับอัปเดตภายในหนึ่งนาที</p>
    <p><b class="text-gray-900 dark:text-white">ระดับรางวัล</b> — JSON แต่ละระดับ: <code>minimum</code> (บาท), <code>name</code>, <code>rewards</code> (ข้อความ), <code>items</code> (รหัสไอเท็มในเกม ไม่บังคับ) แก้แล้วมีผลเฉพาะรายการใหม่ เป้าทุน 0 = ยังไม่ประกาศเป้า</p>
</div>
<div class="space-y-3">
@foreach($campaigns->concat([null]) as $campaign)
    <details class="{{ $card }}">
        <summary class="flex flex-wrap items-center gap-3 cursor-pointer">
            @if($campaign)
                <b class="text-gray-900 dark:text-white">{{ $campaign->name }}</b>
                <code class="text-xs text-gray-500">{{ $campaign->slug }}</code>
                @if($campaign->hero_rank)<span class="px-2 py-0.5 text-xs rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">ฮีโร่ลำดับ {{ $campaign->hero_rank }}</span>@endif
                @if($campaign->hero_hidden)<span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">ซ่อนจากฮีโร่</span>@endif
                @unless($campaign->active)<span class="px-2 py-0.5 text-xs rounded-full bg-amber-100 text-amber-800">ปิดรับรายการ</span>@endunless
                <span class="text-xs text-gray-500">ไอเท็ม {{ $campaign->items->count() }}</span>
            @else
                <b class="text-indigo-600">+ เพิ่มเกมใหม่ในศูนย์สนับสนุน</b>
            @endif
        </summary>
        @if($campaign)
            <form method="post" action="{{ route('admin.gameshub.games.hero', $campaign) }}" class="flex flex-wrap items-end gap-3 mt-4 pb-4 border-b border-gray-100 dark:border-gray-700">
                @csrf
                <label class="{{ $label }}">ลำดับในฮีโร่<input type="number" name="hero_rank" min="1" max="999" value="{{ $campaign->hero_rank }}" placeholder="ตามเดิม" class="{{ $input }} mt-1 w-32"></label>
                <label class="flex gap-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="hero_hidden" value="1" @checked($campaign->hero_hidden)> ซ่อนจากฮีโร่</label>
                <button class="px-3 py-2 text-sm rounded-lg bg-indigo-600 text-white">บันทึกการแสดงในฮีโร่</button>
            </form>
        @endif
        <form method="post" action="{{ $campaign ? route('admin.game-support.campaign.update', $campaign) : route('admin.game-support.campaign.create') }}" class="grid gap-3 mt-4 md:grid-cols-2">
            @csrf
            <label class="{{ $label }}">รหัสเกม (slug เดียวกับใน games.ts ของฮับ)<input name="slug" value="{{ $campaign?->slug }}" @readonly($campaign) required class="{{ $input }} mt-1"></label>
            <label class="{{ $label }}">ชื่อเกม<input name="name" value="{{ $campaign?->name }}" required maxlength="180" class="{{ $input }} mt-1"></label>
            <label class="{{ $label }} md:col-span-2">คำอธิบาย<textarea name="description" rows="2" class="{{ $input }} mt-1">{{ $campaign?->description }}</textarea></label>
            <label class="{{ $label }}">เป้าทุน (บาท)<input name="goal" type="number" min="0" max="100000000" value="{{ ($campaign?->goal_satang ?? 0) / 100 }}" required class="{{ $input }} mt-1"></label>
            <label class="flex gap-2 items-center text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="active" value="1" @checked($campaign?->active ?? true)> เปิดรับรายการและแสดงในอันดับ</label>
            <label class="{{ $label }} md:col-span-2">ระดับรางวัล (JSON)
                <textarea name="tiers_json" rows="10" required class="{{ $input }} mt-1 font-mono text-xs">{{ json_encode($campaign?->tiers ?? config('game-support.tiers'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</textarea>
            </label>
            @if($campaign)
                <p class="md:col-span-2 text-xs text-gray-500">
                    รหัสไอเท็มที่ใช้ได้: @forelse($campaign->items as $item)<code class="mr-1">{{ $item->key }}</code>@empty ยังไม่มี — <a class="text-indigo-600" href="{{ route('admin.gameshub.items', ['game' => $campaign->slug]) }}">เพิ่มไอเท็ม</a>@endforelse
                    · ตัวอย่าง: <code>{"minimum": 300, "name": "SALVAGER", "rewards": ["ตราผู้สนับสนุน"], "items": ["founder-badge"]}</code>
                </p>
            @endif
            <div><button class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white">บันทึกโครงการ</button></div>
        </form>
    </details>
@endforeach
</div>
@endsection
