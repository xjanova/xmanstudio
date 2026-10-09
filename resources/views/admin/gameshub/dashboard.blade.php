@extends($adminLayout ?? 'layouts.admin')
@section('title', 'XGamesHub · ภาพรวม')
@section('page-title', 'XGamesHub · หลังบ้านเว็บเกม')
@section('content')
@include('admin.gameshub._nav')
@php
    $card = 'p-5 rounded-xl bg-white dark:bg-gray-800 shadow border border-gray-100 dark:border-gray-700';
    $statusLabel = ['pending' => 'รอตรวจ', 'approved' => 'ยืนยันแล้ว', 'rejected' => 'ไม่ผ่าน', 'void' => 'ยกเลิกยอด'];
@endphp
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <a href="{{ route('admin.game-support.index') }}" class="{{ $card }} hover:border-amber-400">
        <p class="text-sm text-gray-500 dark:text-gray-400">สลิปรอตรวจ</p>
        <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($pending['donations']) }}</p>
    </a>
    <a href="{{ route('admin.gameshub.reviews') }}" class="{{ $card }} hover:border-amber-400">
        <p class="text-sm text-gray-500 dark:text-gray-400">รีวิวรออนุมัติ</p>
        <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($pending['reviews']) }}</p>
        <p class="text-xs text-gray-500">อนุมัติแล้ว {{ number_format($approvedReviews) }}</p>
    </a>
    <a href="{{ route('admin.gameshub.comments') }}" class="{{ $card }} hover:border-amber-400">
        <p class="text-sm text-gray-500 dark:text-gray-400">ความเห็นรอตรวจ</p>
        <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($pending['comments']) }}</p>
    </a>
    <div class="{{ $card }}">
        <p class="text-sm text-gray-500 dark:text-gray-400">ยอดสนับสนุนที่ยืนยันแล้ว</p>
        <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">฿{{ number_format($raised, 2) }}</p>
        <p class="text-xs text-gray-500">{{ number_format($supporters) }} ผู้สนับสนุน</p>
    </div>
    <a href="{{ route('admin.gameshub.items') }}" class="{{ $card }} hover:border-indigo-400">
        <p class="text-sm text-gray-500 dark:text-gray-400">ไอเท็มผู้สนับสนุน</p>
        <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($granted) }}</p>
        <p class="text-xs text-gray-500">โค้ดที่ใช้งานได้ · ใช้ในเกมแล้ว {{ number_format($redeemed) }} · ไอเท็มในระบบ {{ number_format($items) }}</p>
    </a>
    <a href="{{ route('admin.gameshub.announcements') }}" class="{{ $card }} hover:border-indigo-400">
        <p class="text-sm text-gray-500 dark:text-gray-400">ประกาศที่แสดงอยู่บนฮับ</p>
        <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($liveAnnouncements) }}</p>
    </a>
</div>

<div class="grid gap-6 mt-6 lg:grid-cols-2">
    <section class="{{ $card }}">
        <h2 class="mb-3 font-semibold text-gray-900 dark:text-white">รายการบริจาคล่าสุด</h2>
        <ul class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
            @forelse($recentDonations as $d)
                <li class="flex justify-between gap-3 py-2">
                    <span class="text-gray-700 dark:text-gray-300">{{ $d->campaign->name }} · {{ $d->display_name }}</span>
                    <span class="whitespace-nowrap text-gray-500">฿{{ number_format($d->amount_satang / 100, 2) }} · {{ $statusLabel[$d->status] ?? $d->status }}</span>
                </li>
            @empty
                <li class="py-2 text-gray-500">ยังไม่มีรายการ</li>
            @endforelse
        </ul>
    </section>
    <section class="{{ $card }}">
        <h2 class="mb-3 font-semibold text-gray-900 dark:text-white">รีวิวรออนุมัติ</h2>
        <ul class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
            @forelse($recentReviews as $r)
                <li class="py-2">
                    <p class="text-gray-700 dark:text-gray-300">{{ str_repeat('★', $r->rating) }} · {{ $r->reviewable?->name }} · {{ $r->user?->name }}</p>
                    <p class="text-gray-500 truncate">{{ $r->comment }}</p>
                </li>
            @empty
                <li class="py-2 text-gray-500">ไม่มีรีวิวค้างตรวจ</li>
            @endforelse
        </ul>
    </section>
</div>

<section class="{{ $card }} mt-6 text-sm text-gray-600 dark:text-gray-300 space-y-2">
    <h2 class="font-semibold text-gray-900 dark:text-white">ระบบทำงานอย่างไร</h2>
    <p><b>บริจาค → ไอเท็ม:</b> สร้างไอเท็มของเกมในแท็บ "ไอเท็มผู้สนับสนุน" แล้วใส่รหัสไอเท็มในระดับรางวัล (แท็บ "เกม · รางวัล · ฮีโร่") เมื่ออนุมัติสลิป ระบบออกโค้ดให้ผู้บริจาคอัตโนมัติ ผู้บริจาคเห็นโค้ดที่หน้า "ไอเท็มของฉัน" แล้วนำไปกรอกในเกม เกมตรวจโค้ดกับ <code>POST /api/gameshub/redeem</code> ยกเลิกยอดเมื่อไหร่ โค้ดถูกยกเลิกตามทันที</p>
    <p><b>รีวิวและความเห็น:</b> ผู้เล่นเขียนรีวิว (ดาว + ข้อความ) ที่หน้าเกมในศูนย์ชุมชน ดาวนับทันที ข้อความแสดงบนหน้าเกมและในฮับหลังอนุมัติเท่านั้น การแก้รีวิวจะกลับมารออนุมัติใหม่</p>
    <p><b>หน้าแรกของฮับ:</b> ประกาศและลำดับเกมในฮีโร่ ฮับอ่านจาก <code>/games-support/hub.json</code> ทุกครั้งที่เปิดหน้า (แคช 1 นาที) ไม่ต้อง build ฮับใหม่</p>
</section>
@endsection
