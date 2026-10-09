@extends($adminLayout ?? 'layouts.admin')
@section('title', 'XGamesHub · ตรวจสลิปบริจาค')
@section('page-title', 'XGamesHub · ตรวจสลิปบริจาค')
@section('content')
@include('admin.gameshub._nav')
@php
    $card = 'p-5 rounded-xl bg-white dark:bg-gray-800 shadow border border-gray-100 dark:border-gray-700';
    $input = 'w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm';
    $labels = ['pending' => 'รอตรวจ', 'approved' => 'ยืนยันแล้ว', 'rejected' => 'ไม่ผ่าน', 'void' => 'ยกเลิกยอด'];
    $rewardLabels = ['pending' => 'รอยืนยัน', 'available' => 'ได้รับสิทธิ์แล้ว', 'delivered' => 'ส่งมอบแล้ว', 'not_eligible' => 'ไม่ได้รับสิทธิ์', 'revoked' => 'ยกเลิกสิทธิ์'];
@endphp
<div class="{{ $card }} mb-6 text-sm text-gray-600 dark:text-gray-300">
    <p><b class="text-gray-900 dark:text-white">ตรวจสลิปด้วยเจ้าหน้าที่</b> — บัญชีกลาง {{ config('game-support.bank.name') }} · {{ config('game-support.bank.account') }} · {{ config('game-support.bank.holder') }}</p>
    <p class="mt-1">ตรวจผู้รับ ยอด เวลา และเงินเข้าจากธนาคารก่อนอนุมัติ ระบบกันไฟล์ซ้ำและเลขอ้างอิงซ้ำ แต่ไม่ได้รับรองความแท้ของภาพสลิปอัตโนมัติ เมื่ออนุมัติ ระบบออกโค้ดไอเท็มตามระดับรางวัลให้ผู้บริจาคทันที</p>
</div>

<form method="get" class="flex flex-wrap items-end gap-3 mb-4">
    <div class="flex flex-wrap gap-2">
        @foreach($labels as $key => $label)
            <a href="{{ route('admin.game-support.index', array_filter(['status' => $key, 'game' => $game])) }}" @class([
                'px-3 py-1.5 text-sm rounded-lg',
                'bg-indigo-600 text-white' => $status === $key,
                'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' => $status !== $key,
            ])>{{ $label }} <span class="opacity-70">{{ $counts[$key] ?? 0 }}</span></a>
        @endforeach
    </div>
    <input type="hidden" name="status" value="{{ $status }}">
    <select name="game" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm" onchange="this.form.submit()">
        <option value="">ทุกเกม</option>
        @foreach($campaigns as $c)<option value="{{ $c->slug }}" @selected($game === $c->slug)>{{ $c->name }}</option>@endforeach
    </select>
</form>

<div class="space-y-4">
@forelse($donations as $d)
    <article class="{{ $card }}">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $d->campaign->name }} · ฿{{ number_format($d->amount_satang / 100, 2) }} · {{ $d->display_name }}</h3>
                <p class="text-xs text-gray-500">รายการ {{ $d->public_id }} · สมาชิก #{{ $d->user_id }} · {{ $d->created_at }} · {{ $d->publish_name ? 'ยินยอมแสดงชื่อ' : 'ไม่เปิดเผยชื่อ' }}</p>
            </div>
            <a href="{{ route('admin.game-support.slip', $d) }}" target="_blank" rel="noopener" class="px-3 py-1.5 text-sm rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">เปิดสลิปส่วนตัว ↗</a>
        </div>
        <div class="grid gap-2 mt-3 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-2">
            <p>ผู้รับที่บันทึกตอนส่ง: {{ $d->bank_snapshot['name'] }} {{ $d->bank_snapshot['account'] }} {{ $d->bank_snapshot['holder'] }}</p>
            <p>รางวัล: <b>{{ $d->reward_snapshot['name'] }}</b> · {{ $rewardLabels[$d->reward_status] ?? $d->reward_status }}</p>
            <p class="md:col-span-2 whitespace-pre-line">คำแนะนำ: {{ $d->comment ?: '—' }}</p>
            <p class="md:col-span-2">{{ implode(' · ', $d->reward_snapshot['rewards']) }}</p>
            @if(! empty($d->reward_snapshot['items']))
                <p class="md:col-span-2">ไอเท็มในเกม: {{ collect($d->reward_snapshot['items'])->pluck('name')->implode(', ') }}
                    @if($d->entitlements->isNotEmpty())
                        — ออกโค้ดแล้ว {{ $d->entitlements->where('status', 'granted')->count() }} · ใช้ในเกมแล้ว {{ $d->entitlements->where('redeem_count', '>', 0)->count() }}
                    @endif
                </p>
            @endif
        </div>

        @if($d->status === 'pending')
            <form action="{{ route('admin.game-support.review', $d) }}" method="post" class="grid gap-3 mt-4 md:grid-cols-2">
                @csrf
                <label class="text-sm text-gray-700 dark:text-gray-300">ผลตรวจ
                    <select name="decision" required class="{{ $input }} mt-1"><option value="rejected">ไม่ผ่าน / ข้อมูลไม่ครบ</option><option value="approved">อนุมัติยอด</option></select>
                </label>
                <label class="text-sm text-gray-700 dark:text-gray-300">ยอดที่ตรวจจากธนาคาร (จำเป็นเมื่ออนุมัติ)
                    <input name="verified_amount" inputmode="decimal" placeholder="กรอกยอดที่ตรวจได้จริง" class="{{ $input }} mt-1">
                </label>
                <label class="text-sm text-gray-700 dark:text-gray-300">เลขอ้างอิงธุรกรรมจากธนาคาร
                    <input name="bank_reference" maxlength="120" placeholder="ตัวอักษร ตัวเลข หรือขีด 6–120 ตัว" class="{{ $input }} mt-1">
                </label>
                <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                    <label class="flex gap-2"><input type="checkbox" name="recipient_checked" value="1"> ตรวจผู้รับตรงกับบัญชีบริษัทแล้ว</label>
                    <label class="flex gap-2"><input type="checkbox" name="money_received" value="1"> ตรวจเงินเข้าจากธนาคารแล้ว (ไม่ใช่อาศัยภาพสลิปอย่างเดียว)</label>
                </div>
                <label class="text-sm text-gray-700 dark:text-gray-300 md:col-span-2">ข้อความแจ้งผู้บริจาค / เหตุผล
                    <textarea name="review_note" maxlength="2000" required rows="2" class="{{ $input }} mt-1"></textarea>
                </label>
                <div><button type="submit" class="px-4 py-2 text-sm font-semibold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">บันทึกผลตรวจ</button></div>
            </form>
        @elseif($d->status === 'approved')
            <div class="grid gap-3 mt-4 md:grid-cols-2">
                @if($d->reward_status === 'available')
                    <form method="post" action="{{ route('admin.game-support.reward', $d) }}" class="flex gap-2 items-end">
                        @csrf
                        <label class="flex-1 text-sm text-gray-700 dark:text-gray-300">บันทึกการส่งมอบรางวัลนอกเกม
                            <input name="note" required maxlength="1000" placeholder="ระบุรางวัลและวิธีส่งมอบ" class="{{ $input }} mt-1">
                        </label>
                        <button class="px-3 py-2 text-sm rounded-lg bg-green-600 text-white">บันทึกว่าส่งมอบแล้ว</button>
                    </form>
                @endif
                <details class="text-sm">
                    <summary class="cursor-pointer text-red-600">ยกเลิกการนับยอดกรณีตรวจผิดหรือคืนเงินแล้ว</summary>
                    <form method="post" action="{{ route('admin.game-support.void', $d) }}" class="flex gap-2 items-end mt-2" onsubmit="return confirm('ยกเลิกยอดนี้และยกเลิกโค้ดไอเท็มทั้งหมดของรายการ?')">
                        @csrf
                        <label class="flex-1 text-gray-700 dark:text-gray-300">เหตุผลที่จำเป็นต่อการตรวจย้อนหลัง
                            <input name="note" required maxlength="1000" class="{{ $input }} mt-1">
                        </label>
                        <button class="px-3 py-2 rounded-lg bg-red-600 text-white">ยกเลิกยอดและสิทธิ์รางวัล</button>
                    </form>
                    <p class="mt-1 text-xs text-gray-500">ระบบนี้ไม่โอนเงินคืนให้อัตโนมัติ</p>
                </details>
            </div>
        @endif
        @if($d->audit)
            <details class="mt-3 text-xs text-gray-500">
                <summary class="cursor-pointer">ประวัติการตรวจ</summary>
                @foreach($d->audit as $event)<p>{{ $event['at'] }} · {{ $event['action'] }} · ผู้ดูแล #{{ $event['by'] }} · {{ $event['note'] }}</p>@endforeach
            </details>
        @endif
    </article>
@empty
    <p class="{{ $card }} text-sm text-gray-500">ไม่มีรายการในสถานะนี้</p>
@endforelse
</div>
<div class="mt-4">{{ $donations->links() }}</div>
@endsection
