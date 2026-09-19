@extends($publicLayout ?? 'layouts.app')

@section('title', 'ใบแจ้งหนี้ ' . $doc['number'])
@section('meta_description', 'ใบแจ้งหนี้ ' . $doc['number'] . ' จาก XMAN Studio')

{{-- layouts.app มีแค่ stack 'styles' (ใน head) กับ 'scripts' — ไม่มี 'head'
     push ผิดชื่อจะเงียบหาย ไม่มี error ให้เห็น --}}
@push('styles')
    {{-- เอกสารการเงินของลูกค้า ห้ามเก็บเข้าดัชนีค้นหา --}}
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 2);
    $tone = match (true) {
        $doc['status'] === 'paid' => ['bg' => 'bg-emerald-500/15', 'border' => 'border-emerald-400/40', 'text' => 'text-emerald-200'],
        $doc['status'] === 'void' => ['bg' => 'bg-slate-500/15', 'border' => 'border-slate-400/40', 'text' => 'text-slate-200'],
        $doc['overdue'] => ['bg' => 'bg-red-500/20', 'border' => 'border-red-400/40', 'text' => 'text-red-100'],
        default => ['bg' => 'bg-amber-500/15', 'border' => 'border-amber-400/40', 'text' => 'text-amber-200'],
    };
@endphp

<div class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
    <x-page-art art="hero-quote" :opacity="40" :scrim="false" fade="bottom" />
    <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/50 to-slate-950/85 pointer-events-none" aria-hidden="true"></div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <p class="text-xs font-semibold tracking-[0.2em] uppercase text-teal-300/90 mb-2">ใบแจ้งหนี้ · Invoice</p>
        <h1 class="text-3xl sm:text-4xl font-black text-white mb-3">{{ $doc['number'] }}</h1>
        <p class="text-slate-300 text-base sm:text-lg mb-6">{{ $doc['title'] }}</p>

        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full {{ $tone['bg'] }} border {{ $tone['border'] }} px-3 py-1.5 text-xs font-semibold {{ $tone['text'] }} backdrop-blur">
                {{ $doc['overdue'] ? 'เกินกำหนดชำระ' : $doc['status_label'] }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/15 px-3 py-1.5 text-xs font-medium text-white backdrop-blur">
                เรียน {{ $doc['customer']['company'] ?: $doc['customer']['name'] }}
            </span>
            @if ($doc['due'])
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/15 px-3 py-1.5 text-xs font-medium text-white backdrop-blur">
                    กำหนดชำระ {{ $doc['due'] }}
                </span>
            @endif
        </div>
    </div>
</div>

<div class="bg-gray-50 dark:bg-gray-900 py-10 sm:py-14">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- ยอดที่ต้องจ่าย ให้เห็นก่อนอย่างอื่น --}}
        <div class="rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl overflow-hidden">
            <div class="px-6 sm:px-8 py-6 border-b border-gray-100 dark:border-gray-700 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <div class="text-xs font-semibold tracking-widest uppercase text-gray-400">ยอดชำระงวดนี้</div>
                    <div class="text-3xl sm:text-4xl font-black text-teal-700 dark:text-teal-300 tabular-nums mt-1">
                        ฿{{ $money($doc['amount']) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $doc['amount_words'] }}</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('invoice.download', $invoice->public_token) }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-teal-600 hover:bg-teal-700 px-5 py-2.5 text-sm font-bold text-white shadow-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        ดาวน์โหลด PDF
                    </a>
                    <button type="button" onclick="window.print()"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-300 dark:border-gray-600 px-5 py-2.5 text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        พิมพ์
                    </button>
                </div>
            </div>

            <div class="px-6 sm:px-8 py-6 grid sm:grid-cols-2 gap-6 text-sm">
                <div>
                    <div class="text-xs font-semibold tracking-widest uppercase text-gray-400 mb-1.5">เรียกเก็บจาก</div>
                    <div class="font-bold text-gray-900 dark:text-white">{{ $doc['customer']['company'] ?: $doc['customer']['name'] }}</div>
                    <div class="text-gray-500 dark:text-gray-400 mt-0.5 space-y-0.5">
                        @if ($doc['customer']['company'])<div>{{ $doc['customer']['name'] }}</div>@endif
                        @if ($doc['customer']['address'])<div>{{ $doc['customer']['address'] }}</div>@endif
                        <div>{{ $doc['customer']['email'] }}</div>
                    </div>
                </div>
                <div>
                    <div class="text-xs font-semibold tracking-widest uppercase text-gray-400 mb-1.5">งานที่เรียกเก็บ</div>
                    <div class="font-bold text-gray-900 dark:text-white">{{ $doc['project']['name'] ?: $doc['title'] }}</div>
                    <div class="text-gray-500 dark:text-gray-400 mt-0.5 space-y-0.5">
                        <div>งวดที่ {{ $doc['installment'] }} · {{ $doc['percent'] }}% ของยอดงาน</div>
                        @if ($doc['project']['number'])<div>เลขที่โครงการ {{ $doc['project']['number'] }}</div>@endif
                        @if ($doc['quote_number'])<div>อ้างอิงใบเสนอราคา {{ $doc['quote_number'] }}</div>@endif
                    </div>
                </div>
            </div>

            <div class="px-6 sm:px-8 pb-6">
                <div class="rounded-2xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 px-5 py-4 space-y-2 text-sm">
                    @if ($doc['vat_mode'] === 'none')
                        <div class="flex justify-between text-gray-600 dark:text-gray-300">
                            <span>จำนวนเงิน</span><span class="tabular-nums">{{ $money($doc['amount']) }}</span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">ไม่มีภาษีมูลค่าเพิ่ม</div>
                    @else
                        <div class="flex justify-between text-gray-600 dark:text-gray-300">
                            <span>มูลค่าสินค้า/บริการ</span><span class="tabular-nums">{{ $money($doc['base']) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-300">
                            <span>ภาษีมูลค่าเพิ่ม {{ $doc['vat_rate'] }}%</span><span class="tabular-nums">{{ $money($doc['vat']) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700 font-bold text-teal-700 dark:text-teal-300">
                        <span>ยอดชำระ</span><span class="tabular-nums">฿{{ $money($doc['amount']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- วิธีชำระ --}}
        <div class="rounded-3xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg px-6 sm:px-8 py-6">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3">ชำระเงินอย่างไร</h2>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300 list-disc list-inside">
                <li>โอนเข้าบัญชีบริษัทตามที่แจ้งไว้ในอีเมล หรือสแกนพร้อมเพย์ที่ทีมงานส่งให้</li>
                <li>ส่งหลักฐานการโอนกลับมาที่{{ $companyInfo['email'] ? ' ' . $companyInfo['email'] : 'อีเมลของเรา' }} เพื่อออกใบเสร็จรับเงิน</li>
                <li>ใบเสร็จรับเงิน/ใบกำกับภาษีออกให้หลังได้รับเงินเรียบร้อยแล้ว</li>
            </ul>
            @if ($doc['status'] === 'paid')
                <div class="mt-4 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 px-5 py-4 text-sm text-emerald-800 dark:text-emerald-200">
                    ได้รับชำระเรียบร้อยแล้ว{{ $doc['paid_at'] ? ' เมื่อ ' . $doc['paid_at'] : '' }} — ขอบคุณครับ
                </div>
            @elseif ($doc['status'] === 'void')
                <div class="mt-4 rounded-2xl bg-slate-100 dark:bg-slate-500/10 border border-slate-200 dark:border-slate-500/30 px-5 py-4 text-sm text-slate-700 dark:text-slate-200">
                    ใบแจ้งหนี้นี้ถูกยกเลิกแล้ว ไม่ต้องชำระ
                </div>
            @elseif ($doc['overdue'])
                <div class="mt-4 rounded-2xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-5 py-4 text-sm text-red-800 dark:text-red-200">
                    เลยกำหนดชำระ {{ $doc['due'] }} แล้ว หากชำระไปแล้วรบกวนแจ้งทีมงานเพื่อตัดยอดให้ครับ
                </div>
            @endif
        </div>

        <p class="text-center text-xs text-gray-400 dark:text-gray-500">
            ลิงก์นี้เป็นเอกสารเฉพาะของคุณ กรุณาอย่าเผยแพร่ต่อ
        </p>
    </div>
</div>
@endsection
