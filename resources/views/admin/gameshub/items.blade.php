@extends($adminLayout ?? 'layouts.admin')
@section('title', 'XGamesHub · ไอเท็มผู้สนับสนุน')
@section('page-title', 'XGamesHub · ไอเท็มผู้สนับสนุน')
@section('content')
@include('admin.gameshub._nav')
@php
    $card = 'p-5 rounded-xl bg-white dark:bg-gray-800 shadow border border-gray-100 dark:border-gray-700';
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm';
    $label = 'block text-sm text-gray-700 dark:text-gray-300';
@endphp
<div class="grid gap-6 lg:grid-cols-3">
    <section class="{{ $card }} lg:col-span-1">
        <h2 class="mb-2 font-semibold text-gray-900 dark:text-white">เลือกเกม</h2>
        <p class="mb-3 text-xs text-gray-500">ไอเท็มผูกกับเกม รหัสไอเท็ม (key) คือสิ่งที่โค้ดในเกมใช้ตรวจ เปลี่ยนภายหลังไม่ได้</p>
        <ul class="max-h-[28rem] overflow-y-auto text-sm divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($campaigns as $c)
                <li>
                    <a href="{{ route('admin.gameshub.items', ['game' => $c->slug]) }}" @class([
                        'flex justify-between px-2 py-2 rounded',
                        'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-semibold' => $campaign?->id === $c->id,
                        'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50' => $campaign?->id !== $c->id,
                    ])><span>{{ $c->name }}</span><span class="text-gray-400">{{ $c->items_count }}</span></a>
                </li>
            @endforeach
        </ul>
    </section>

    <div class="space-y-6 lg:col-span-2">
        @if($campaign)
            <section class="{{ $card }}">
                <h2 class="mb-3 font-semibold text-gray-900 dark:text-white">ไอเท็มของ {{ $campaign->name }}</h2>
                @forelse($items as $item)
                    <details class="py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <summary class="flex flex-wrap items-center gap-2 cursor-pointer text-sm">
                            <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-900 text-xs">{{ $item->key }}</code>
                            <b class="text-gray-900 dark:text-white">{{ $item->name }}</b>
                            <span class="text-gray-500">{{ \App\Models\GameItem::KINDS[$item->kind] ?? $item->kind }} · {{ $item->max_devices }} เครื่อง/โค้ด · แจกแล้ว {{ $item->granted_count }}</span>
                            @unless($item->active)<span class="px-2 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600">ปิดใช้</span>@endunless
                        </summary>
                        <form method="post" action="{{ route('admin.gameshub.items.update', $item) }}" class="grid gap-3 mt-3 md:grid-cols-2">
                            @csrf
                            <input type="hidden" name="key" value="{{ $item->key }}">
                            <label class="{{ $label }}">ชื่อไอเท็ม<input name="name" value="{{ $item->name }}" required maxlength="120" class="{{ $input }} mt-1"></label>
                            <label class="{{ $label }}">ประเภท<select name="kind" class="{{ $input }} mt-1">@foreach(\App\Models\GameItem::KINDS as $k => $v)<option value="{{ $k }}" @selected($item->kind === $k)>{{ $v }}</option>@endforeach</select></label>
                            <label class="{{ $label }} md:col-span-2">คำอธิบาย<textarea name="description" rows="2" maxlength="2000" class="{{ $input }} mt-1">{{ $item->description }}</textarea></label>
                            <label class="{{ $label }}">รูปไอเท็ม (https://… ไม่บังคับ)<input name="image_url" value="{{ $item->image_url }}" maxlength="500" class="{{ $input }} mt-1"></label>
                            <label class="{{ $label }}">ใช้ได้กี่เครื่องต่อโค้ด<input type="number" name="max_devices" min="1" max="20" value="{{ $item->max_devices }}" class="{{ $input }} mt-1"></label>
                            <label class="flex gap-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="active" value="1" @checked($item->active)> เปิดใช้ (ปิด = ไม่ใส่ในรางวัลใหม่ โค้ดที่แจกแล้วยังใช้ได้)</label>
                            <div><button class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white">บันทึกไอเท็ม</button></div>
                        </form>
                    </details>
                @empty
                    <p class="text-sm text-gray-500">ยังไม่มีไอเท็มในเกมนี้</p>
                @endforelse

                <details class="mt-4" @if($items->isEmpty()) open @endif>
                    <summary class="cursor-pointer text-sm font-semibold text-indigo-600">+ เพิ่มไอเท็มใหม่</summary>
                    <form method="post" action="{{ route('admin.gameshub.items.store') }}" class="grid gap-3 mt-3 md:grid-cols-2">
                        @csrf
                        <input type="hidden" name="game" value="{{ $campaign->slug }}">
                        <label class="{{ $label }}">รหัสไอเท็ม (key) — ใช้ในโค้ดเกม<input name="key" value="{{ old('key') }}" required maxlength="80" placeholder="founder-badge" class="{{ $input }} mt-1"></label>
                        <label class="{{ $label }}">ชื่อไอเท็ม<input name="name" value="{{ old('name') }}" required maxlength="120" placeholder="ตรา Core Founder" class="{{ $input }} mt-1"></label>
                        <label class="{{ $label }}">ประเภท<select name="kind" class="{{ $input }} mt-1">@foreach(\App\Models\GameItem::KINDS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></label>
                        <label class="{{ $label }}">ใช้ได้กี่เครื่องต่อโค้ด<input type="number" name="max_devices" min="1" max="20" value="{{ old('max_devices', 3) }}" class="{{ $input }} mt-1"></label>
                        <label class="{{ $label }} md:col-span-2">คำอธิบาย<textarea name="description" rows="2" maxlength="2000" class="{{ $input }} mt-1">{{ old('description') }}</textarea></label>
                        <label class="{{ $label }}">รูปไอเท็ม (https://… ไม่บังคับ)<input name="image_url" value="{{ old('image_url') }}" maxlength="500" class="{{ $input }} mt-1"></label>
                        <label class="flex gap-2 text-sm text-gray-700 dark:text-gray-300"><input type="checkbox" name="active" value="1" checked> เปิดใช้</label>
                        <div><button class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white">เพิ่มไอเท็ม</button></div>
                    </form>
                    <p class="mt-2 text-xs text-gray-500">ไอเท็มควรเป็นของแต่ง ฉายา ตรา หรือบัตรผ่าน ไม่ใช่พลังที่ทำให้ได้เปรียบในเกม จากนั้นใส่รหัสไอเท็มในระดับรางวัลที่แท็บ "เกม · รางวัล · ฮีโร่"</p>
                </details>
            </section>

            @if($items->isNotEmpty())
                <section class="{{ $card }}">
                    <h2 class="mb-1 font-semibold text-gray-900 dark:text-white">มอบไอเท็มให้สมาชิกโดยตรง</h2>
                    <p class="mb-3 text-xs text-gray-500">สำหรับชดเชย กิจกรรม หรือรายการที่ตรวจนอกระบบ ทุกครั้งต้องมีเหตุผลเพื่อตรวจย้อนหลัง</p>
                    <form method="post" action="{{ route('admin.gameshub.entitlements.grant') }}" class="grid gap-3 md:grid-cols-3" onsubmit="this.querySelector('button[type=submit],button:not([type])').disabled = true">
                        @csrf
                        <label class="{{ $label }}">ไอเท็ม<select name="item_id" class="{{ $input }} mt-1">@foreach($items as $item)<option value="{{ $item->id }}">{{ $item->name }} ({{ $item->key }})</option>@endforeach</select></label>
                        <label class="{{ $label }}">อีเมล XMAN ID ของสมาชิก<input type="email" name="email" required value="{{ old('email') }}" class="{{ $input }} mt-1"></label>
                        <label class="{{ $label }}">เหตุผล<input name="note" required maxlength="1000" value="{{ old('note') }}" class="{{ $input }} mt-1"></label>
                        <div><button class="px-4 py-2 text-sm font-semibold rounded-lg bg-green-600 text-white">มอบไอเท็มและออกโค้ด</button></div>
                    </form>
                </section>
            @endif
        @else
            <section class="{{ $card }} text-sm text-gray-600 dark:text-gray-300">เลือกเกมทางซ้ายเพื่อจัดการไอเท็ม หรือค้นหาโค้ด / สมาชิกด้านล่าง</section>
        @endif

        <section class="{{ $card }}">
            <div class="flex flex-wrap items-end justify-between gap-3 mb-3">
                <h2 class="font-semibold text-gray-900 dark:text-white">โค้ดที่แจกแล้ว{{ $campaign ? ' · ' . $campaign->name : '' }}</h2>
                <form method="get" class="flex gap-2">
                    @if($campaign)<input type="hidden" name="game" value="{{ $campaign->slug }}">@endif
                    <input name="q" value="{{ $q }}" placeholder="โค้ด XG-… / อีเมล / ชื่อสมาชิก" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    <button class="px-3 py-2 text-sm rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200">ค้นหา</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                    <thead><tr class="text-left text-xs uppercase text-gray-500"><th class="py-2 pr-3">สมาชิก</th><th class="py-2 pr-3">ไอเท็ม</th><th class="py-2 pr-3">โค้ด</th><th class="py-2 pr-3">ใช้แล้ว</th><th class="py-2 pr-3">ที่มา</th><th class="py-2">สถานะ</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($entitlements as $e)
                            <tr class="align-top">
                                <td class="py-2 pr-3 text-gray-900 dark:text-white">{{ $e->user?->name }}<br><span class="text-xs text-gray-500">{{ $e->user?->email }}</span></td>
                                <td class="py-2 pr-3 text-gray-700 dark:text-gray-300">{{ $e->item->name }}<br><span class="text-xs text-gray-500">{{ $e->item->campaign->name }}</span></td>
                                <td class="py-2 pr-3"><code class="text-xs">{{ $e->code }}</code></td>
                                <td class="py-2 pr-3 text-gray-700 dark:text-gray-300">{{ $e->redeem_count }}/{{ $e->item->max_devices }}</td>
                                <td class="py-2 pr-3 text-xs text-gray-500">{{ $e->source === 'donation' ? 'บริจาค' : 'มอบโดยทีม' }} · {{ $e->created_at->format('Y-m-d') }}<br>{{ \Illuminate\Support\Str::limit($e->note, 60) }}</td>
                                <td class="py-2">
                                    @if($e->status === 'granted')
                                        <details>
                                            <summary class="cursor-pointer text-green-600">ใช้งานได้</summary>
                                            <form method="post" action="{{ route('admin.gameshub.entitlements.revoke', $e) }}" class="flex gap-2 mt-2" onsubmit="return confirm('ยกเลิกโค้ดนี้? เกมจะไม่รับโค้ดนี้อีก')">
                                                @csrf
                                                <input name="note" required maxlength="1000" placeholder="เหตุผล" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs">
                                                <button class="px-2 py-1 text-xs rounded bg-red-600 text-white">ยกเลิกโค้ด</button>
                                            </form>
                                        </details>
                                    @else
                                        <span class="text-red-600">ยกเลิกแล้ว</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-3 text-gray-500">ยังไม่มีโค้ด</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $entitlements->links() }}</div>
        </section>
    </div>
</div>
@endsection
