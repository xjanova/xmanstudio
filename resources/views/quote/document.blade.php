@extends($publicLayout ?? 'layouts.app')

@section('title', 'ใบเสนอราคา ' . $quotation->quote_number)
@section('meta_description', 'ใบเสนอราคา ' . $quotation->quote_number . ' จาก XMAN Studio')

{{-- layouts.app มีแค่ stack 'styles' (ใน head) กับ 'scripts' — ไม่มี 'head'
     push ผิดชื่อจะเงียบหาย ไม่มี error ให้เห็น --}}
@push('styles')
    {{-- เอกสารส่วนตัวของลูกค้า ไม่ควรถูกเก็บเข้าดัชนีค้นหา --}}
    <meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 2);
    $rate = rtrim(rtrim(number_format((float) $doc['vat_rate'], 2), '0'), '.');
    $status = $quotation->status;
    $tone = match ($status) {
        'accepted' => ['bg' => 'bg-emerald-500/15', 'border' => 'border-emerald-400/40', 'text' => 'text-emerald-200', 'label' => 'ตอบรับแล้ว'],
        'rejected' => ['bg' => 'bg-slate-500/15', 'border' => 'border-slate-400/40', 'text' => 'text-slate-200', 'label' => 'ไม่รับข้อเสนอ'],
        'paid' => ['bg' => 'bg-emerald-500/15', 'border' => 'border-emerald-400/40', 'text' => 'text-emerald-200', 'label' => 'ชำระเงินแล้ว'],
        default => ['bg' => 'bg-sky-500/15', 'border' => 'border-sky-400/40', 'text' => 'text-sky-200', 'label' => 'รอคำตอบ'],
    };
    $expired = $quotation->isExpired();
@endphp

<div class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
    <x-page-art art="hero-quote" :opacity="40" :scrim="false" fade="bottom" />
    <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/50 to-slate-950/85 pointer-events-none" aria-hidden="true"></div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <p class="text-xs font-semibold tracking-[0.2em] uppercase text-blue-300/90 mb-2">ใบเสนอราคา · Quotation</p>
        <h1 class="text-3xl sm:text-4xl font-black text-white mb-3">{{ $quotation->displayNumber() }}</h1>
        <p class="text-slate-300 text-base sm:text-lg mb-6">{{ $quotation->service_name }}</p>

        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full {{ $tone['bg'] }} border {{ $tone['border'] }} px-3 py-1.5 text-xs font-semibold {{ $tone['text'] }} backdrop-blur">
                {{ $tone['label'] }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 border border-white/15 px-3 py-1.5 text-xs font-medium text-white backdrop-blur">
                เรียน {{ $quotation->customer_company ?: $quotation->customer_name }}
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full {{ $expired ? 'bg-red-500/20 border-red-400/40 text-red-100' : 'bg-white/10 border-white/15 text-white' }} border px-3 py-1.5 text-xs font-medium backdrop-blur">
                {{ $expired ? 'หมดอายุแล้ว' : 'ยืนราคาถึง' }} {{ $doc['valid_until'] }}
            </span>
        </div>
    </div>
</div>

<div class="bg-gray-50 dark:bg-gray-900 py-10 sm:py-14">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if (session('quote_success'))
            <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 px-5 py-4 text-sm text-emerald-800 dark:text-emerald-200">
                {{ session('quote_success') }}
            </div>
        @endif
        @if (session('quote_error'))
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-5 py-4 text-sm text-amber-800 dark:text-amber-200">
                {{ session('quote_error') }}
            </div>
        @endif

        {{-- ══ รายการ ══ --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
            {{-- หัวจดหมายแบบเดียวกับบน PDF เพื่อให้หน้าเว็บกับไฟล์ที่ดาวน์โหลด
                 อ่านแล้วรู้สึกว่าเป็นเอกสารฉบับเดียวกัน --}}
            <div class="px-5 sm:px-7 py-5 border-b border-gray-100 dark:border-gray-700 flex items-start justify-between gap-5 flex-wrap">
                <div>
                    @if (!empty($companyInfo['logo_url']))
                        <img src="{{ $companyInfo['logo_url'] }}" alt="{{ $companyInfo['name'] }}" class="h-9 w-auto mb-2.5">
                    @else
                        <div class="text-lg font-black tracking-wide text-gray-900 dark:text-white mb-1">{{ $companyInfo['name'] }}</div>
                    @endif
                    <div class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                        @if ($companyInfo['address']){{ $companyInfo['address'] }}<br>@endif
                        @if ($companyInfo['phone'])โทร {{ $companyInfo['phone'] }}@endif
                        @if ($companyInfo['phone'] && $companyInfo['email']) · @endif
                        @if ($companyInfo['email']){{ $companyInfo['email'] }}@endif
                        @if ($companyInfo['tax_id'])<br>เลขประจำตัวผู้เสียภาษี {{ $companyInfo['tax_id'] }}@endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs font-semibold tracking-[0.2em] text-gray-400 uppercase">Quotation</div>
                    <div class="font-mono text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $quotation->quote_number }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">ออกเมื่อ {{ $doc['quote_date'] }}</div>
                </div>
            </div>

            <div class="px-5 sm:px-7 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">รายการในใบเสนอราคา</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <th class="py-3 pl-5 sm:pl-7 pr-3 font-medium">รายการ</th>
                            <th class="py-3 pr-5 sm:pr-7 font-medium text-right">จำนวนเงิน</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($doc['items'] as $item)
                            <tr>
                                <td class="py-3 pl-5 sm:pl-7 pr-3 text-gray-900 dark:text-white">
                                    {{ $item['name_th'] ?? $item['name'] ?? '-' }}
                                </td>
                                <td class="py-3 pr-5 sm:pr-7 text-right font-semibold text-gray-900 dark:text-white tabular-nums whitespace-nowrap">
                                    {{ $money($item['price'] ?? 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-8 text-center text-gray-500">ไม่มีรายการ</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ══ ยอดรวม ══ --}}
            <div class="px-5 sm:px-7 py-5 bg-gray-50 dark:bg-gray-900/40 border-t border-gray-100 dark:border-gray-700">
                <div class="max-w-sm ml-auto space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">รวมเป็นเงิน</span><span class="tabular-nums text-gray-900 dark:text-white">{{ $money($doc['subtotal']) }}</span></div>

                    @if ($doc['discount'] > 0)
                        <div class="flex justify-between text-emerald-700 dark:text-emerald-300"><span>ส่วนลดขนาดงาน {{ $doc['discount_percent'] }}%</span><span class="tabular-nums">&minus;{{ $money($doc['discount']) }}</span></div>
                    @endif
                    @if ($doc['rush_fee'] > 0)
                        <div class="flex justify-between text-amber-700 dark:text-amber-300"><span>ค่าเร่งงาน {{ rtrim(rtrim(number_format(\App\Support\Quotation\Pricing::rushPercent(), 2), '0'), '.') }}%</span><span class="tabular-nums">{{ $money($doc['rush_fee']) }}</span></div>
                    @endif

                    @if ($doc['vat_mode'] === 'inclusive')
                        <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700"><span class="text-gray-600 dark:text-gray-300">มูลค่าสินค้า/บริการ</span><span class="tabular-nums text-gray-900 dark:text-white">{{ $money($doc['amount_before_vat']) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">ภาษีมูลค่าเพิ่ม {{ $rate }}%</span><span class="tabular-nums text-gray-900 dark:text-white">{{ $money($doc['vat']) }}</span></div>
                    @elseif ($doc['vat_mode'] === 'exclusive')
                        @if ($doc['discount'] > 0 || $doc['rush_fee'] > 0)
                            <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700"><span class="text-gray-600 dark:text-gray-300">ราคาหลังหักส่วนลด</span><span class="tabular-nums text-gray-900 dark:text-white">{{ $money($doc['amount_before_vat']) }}</span></div>
                        @endif
                        <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-300">ภาษีมูลค่าเพิ่ม {{ $rate }}%</span><span class="tabular-nums text-gray-900 dark:text-white">{{ $money($doc['vat']) }}</span></div>
                    @else
                        <div class="flex justify-end pt-2 border-t border-gray-200 dark:border-gray-700"><span class="text-xs text-gray-500 dark:text-gray-400">ราคานี้ไม่มีภาษีมูลค่าเพิ่ม</span></div>
                    @endif

                    <div class="flex justify-between items-baseline pt-3 border-t-2 border-gray-900 dark:border-gray-200">
                        <span class="font-bold text-gray-900 dark:text-white">จำนวนเงินรวมทั้งสิ้น</span>
                        <span class="tabular-nums text-2xl font-bold text-blue-700 dark:text-blue-300">฿{{ $money($doc['grand_total']) }}</span>
                    </div>

                    @if ($doc['withholding_amount'] > 0)
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400"><span>หัก ณ ที่จ่าย {{ rtrim(rtrim(number_format((float) $doc['withholding_pct'], 2), '0'), '.') }}%</span><span class="tabular-nums">&minus;{{ $money($doc['withholding_amount']) }}</span></div>
                        <div class="flex justify-between text-sm font-semibold text-gray-900 dark:text-white"><span>ยอดโอนสุทธิ</span><span class="tabular-nums">{{ $money($doc['net_payable']) }}</span></div>
                    @endif

                    <p class="pt-2 text-xs text-gray-500 dark:text-gray-400 text-right">( {{ $doc['grand_total_words'] }} )</p>
                </div>
            </div>
        </div>

        {{-- ══ ปุ่มดาวน์โหลด ══ --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('quote.show.pdf', $quotation->public_token) }}"
               class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold shadow-lg shadow-blue-500/30 hover:shadow-xl hover:-translate-y-0.5 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                ดาวน์โหลด PDF
            </a>
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                พิมพ์
            </button>
        </div>

        {{-- ══ ตอบกลับ ══ --}}
        @if ($canRespond)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden" x-data="{ mode: null }">
                <div class="px-5 sm:px-7 py-5 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">ตอบกลับใบเสนอราคานี้</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">กดปุ่มด้านล่างได้เลย ทีมงานจะได้รับแจ้งทันที</p>
                </div>

                <div class="p-5 sm:p-7 space-y-4">
                    <div class="grid sm:grid-cols-3 gap-3">
                        <button type="button" @click="mode = mode === 'accept' ? null : 'accept'"
                                :class="mode === 'accept' ? 'border-emerald-500 ring-2 ring-emerald-500/30' : 'border-gray-200 dark:border-gray-700'"
                                class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 bg-emerald-50 dark:bg-emerald-500/10 hover:border-emerald-400 transition text-center">
                            <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="font-semibold text-emerald-900 dark:text-emerald-200">ตอบรับ</span>
                            <span class="text-xs text-emerald-800/70 dark:text-emerald-200/70">เริ่มงานได้เลย</span>
                        </button>

                        <button type="button" @click="mode = mode === 'negotiate' ? null : 'negotiate'"
                                :class="mode === 'negotiate' ? 'border-amber-500 ring-2 ring-amber-500/30' : 'border-gray-200 dark:border-gray-700'"
                                class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 bg-amber-50 dark:bg-amber-500/10 hover:border-amber-400 transition text-center">
                            <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.9 9.9 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <span class="font-semibold text-amber-900 dark:text-amber-200">ขอต่อรอง</span>
                            <span class="text-xs text-amber-800/70 dark:text-amber-200/70">บอกได้ว่าติดตรงไหน</span>
                        </button>

                        <button type="button" @click="mode = mode === 'decline' ? null : 'decline'"
                                :class="mode === 'decline' ? 'border-gray-500 ring-2 ring-gray-500/20' : 'border-gray-200 dark:border-gray-700'"
                                class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 bg-gray-50 dark:bg-gray-900/40 hover:border-gray-400 transition text-center">
                            <svg class="w-7 h-7 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span class="font-semibold text-gray-800 dark:text-gray-200">ยังไม่เอาตอนนี้</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">ปิดใบนี้ไว้ก่อน</span>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('quote.respond', $quotation->public_token) }}"
                          x-show="mode" x-cloak class="space-y-3" @submit="$el.querySelector('button[type=submit]').disabled = true">
                        @csrf
                        <input type="hidden" name="action" :value="mode">

                        <div x-show="mode === 'negotiate'">
                            <label for="quote-message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                อยากปรับตรงไหนครับ
                            </label>
                            <textarea id="quote-message" name="message" rows="3"
                                      placeholder="เช่น ขอตัดรายการ SEO ออกก่อน หรืองบอยู่ที่ประมาณ 180,000"
                                      class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('message') }}</textarea>
                        </div>

                        <div x-show="mode === 'decline'">
                            <label for="quote-reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                บอกเหตุผลสั้น ๆ ได้ถ้าสะดวก <span class="font-normal text-gray-500">ไม่บังคับ</span>
                            </label>
                            <textarea id="quote-reason" name="message" rows="2"
                                      class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('message') }}</textarea>
                        </div>

                        <p x-show="mode === 'accept'" class="text-sm text-gray-600 dark:text-gray-300">
                            กดยืนยันแล้วเราจะล็อกราคาและขอบเขตงานตามใบนี้ แล้วติดต่อกลับเพื่อเริ่มงาน
                        </p>

                        <button type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold shadow-lg shadow-blue-500/30 hover:shadow-xl transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                            ยืนยันคำตอบ
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 text-center">
                <p class="text-gray-700 dark:text-gray-200 font-medium">
                    @if ($quotation->isSuperseded())
                        {{-- ลูกค้าถือลิงก์เก่าไว้ ต้องบอกให้รู้ว่ามีฉบับใหม่ ไม่ใช่ปล่อยให้คิดว่าเราปิดงานไปแล้ว --}}
                        ใบเสนอราคาฉบับนี้มีฉบับแก้ไขใหม่แทนแล้ว
                    @elseif ($expired)
                        ใบเสนอราคานี้หมดอายุแล้ว
                    @else
                        ใบเสนอราคานี้มีคำตอบเรียบร้อยแล้ว — {{ $tone['label'] }}
                    @endif
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    @if ($quotation->isSuperseded())
                        ทีมงานส่งฉบับใหม่ไปที่อีเมลของคุณแล้ว ถ้าไม่ได้รับ
                    @else
                        ต้องการคุยต่อ
                    @endif
                    ติดต่อทีมงานได้ที่
                    <a href="{{ route('contact.show') }}" class="text-blue-600 dark:text-blue-400 hover:underline">หน้าติดต่อเรา</a>
                </p>
            </div>
        @endif

        <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
            ลิงก์นี้เป็นของใบเสนอราคาฉบับนี้โดยเฉพาะ กรุณาอย่าส่งต่อให้คนนอกองค์กร
        </p>
    </div>
</div>
@endsection
