@extends($publicLayout ?? 'layouts.app')

@section('title', 'ใบเสนอราคา ' . $quotation->quote_number)
@section('meta_description', 'ใบเสนอราคา ' . $quotation->quote_number . ' จาก XMAN Studio')

{{-- layouts.app มีแค่ stack 'styles' (ใน head) กับ 'scripts' — ไม่มี 'head'
     push ผิดชื่อจะเงียบหาย ไม่มี error ให้เห็น --}}
@push('styles')
    {{-- เอกสารส่วนตัวของลูกค้า ไม่ควรถูกเก็บเข้าดัชนีค้นหา --}}
    <meta name="robots" content="noindex, nofollow">
    <style>
        /* ปุ่ม "พิมพ์" ต้องได้แผ่นเอกสาร ไม่ใช่ทั้งหน้าเว็บบนพื้นดำ (เบราว์เซอร์ไม่พิมพ์พื้นหลัง
           ตัวหนังสือสีอ่อนเลยหายไปหมด) — ซ่อนทุกอย่างยกเว้นแผ่นใบเสนอราคา */
        @media print {
            nav, footer, [data-print-hide] { display: none !important; }
            body * { visibility: hidden; }
            #quote-paper, #quote-paper * { visibility: visible; }
            html, body, main, .xp { background: #fff !important; }
            main { padding: 0 !important; min-height: 0 !important; }
            [data-print-root] { padding: 0 !important; }
            #quote-paper { box-shadow: none !important; border: 0 !important; border-radius: 0 !important; }
            #quote-paper thead { display: table-header-group; }
            #quote-paper tr { break-inside: avoid; }
        }
    </style>
@endpush

@section('content')
@php
    $money = fn ($n) => number_format((float) $n, 2);
    $rate = rtrim(rtrim(number_format((float) $doc['vat_rate'], 2), '0'), '.');
    $status = $quotation->status;
    $tone = match ($status) {
        'accepted' => ['bg' => 'bg-emerald-500/15', 'border' => 'border-emerald-400/40', 'text' => 'text-emerald-200', 'label' => 'ตอบรับแล้ว', 'label_en' => 'Accepted'],
        'rejected' => ['bg' => 'bg-slate-500/15', 'border' => 'border-slate-400/40', 'text' => 'text-slate-200', 'label' => 'ไม่รับข้อเสนอ', 'label_en' => 'Declined'],
        'paid' => ['bg' => 'bg-emerald-500/15', 'border' => 'border-emerald-400/40', 'text' => 'text-emerald-200', 'label' => 'ชำระเงินแล้ว', 'label_en' => 'Paid'],
        default => ['bg' => 'bg-sky-500/15', 'border' => 'border-sky-400/40', 'text' => 'text-sky-200', 'label' => 'รอคำตอบ', 'label_en' => 'Awaiting response'],
    };
    $expired = $quotation->isExpired();
@endphp
<x-xp.page class="dark" a="#67e8f9" b="#a78bfa" c="#7dd3fc" glow="103 232 249" g1="#a78bfa" g2="#67e8f9" g3="#f0abfc">

    {{-- ============================ HERO ============================ --}}
    <div data-print-hide>
        <x-svc.hero :art="asset('artwork/hero-quote.webp')" compact
                    art-class="top-0 right-[-40%] w-[160%] opacity-35 sm:right-[-25%] sm:w-[120%] lg:right-[-14%] lg:w-[72%] lg:opacity-70">
            <x-slot:chips>
                <span class="xp-chip"><i></i><x-bi th="ใบเสนอราคา" en="Quotation" sep=" · " /></span>
                <span class="inline-flex items-center gap-2 rounded-full {{ $tone['bg'] }} border {{ $tone['border'] }} px-3 py-1.5 text-xs font-semibold {{ $tone['text'] }} backdrop-blur">
                    <x-bi :th="$tone['label']" :en="$tone['label_en']" />
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full {{ $expired ? 'bg-red-500/20 border-red-400/40 text-red-100' : 'bg-white/5 border-white/15 text-gray-100' }} border px-3 py-1.5 text-xs font-medium backdrop-blur">
                    <span>@if ($expired)<x-bi th="หมดอายุแล้ว" en="Expired" />@else<x-bi th="ยืนราคาถึง" en="Valid until" />@endif {{ $doc['valid_until'] }}</span>
                </span>
            </x-slot:chips>
            <x-slot:title><span class="font-mono tracking-tight">{{ $quotation->displayNumber() }}</span></x-slot:title>

            <p class="text-xl sm:text-2xl font-bold text-white mb-2 break-words">{{ $quotation->service_name }}</p>
            <p class="text-gray-400 mb-8"><x-bi th="เรียน" en="To" /> <span class="text-gray-200 font-semibold">{{ $quotation->customer_company ?: $quotation->customer_name }}</span></p>

            <x-slot:actions>
                <a href="{{ route('quote.show.pdf', $quotation->public_token) }}"
                   class="xp-btn-main inline-flex items-center justify-center gap-2 px-7 py-4 rounded-xl font-extrabold transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <x-bi th="ดาวน์โหลด PDF" en="Download PDF" />
                </a>
                <button type="button" onclick="window.print()"
                        class="xp-btn-ghost inline-flex items-center justify-center gap-2 px-7 py-4 rounded-xl font-bold transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <x-bi k="common.print" />
                </button>
                @if ($canRespond)
                    <a href="#respond" class="xp-link inline-flex items-center gap-1.5 px-2 py-4 font-bold transition">
                        <x-bi th="ตอบกลับใบนี้" en="Respond" /> <span aria-hidden="true">↓</span>
                    </a>
                @endif
            </x-slot:actions>
        </x-svc.hero>
    </div>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 -mt-6 space-y-6" data-print-root>

        @if (session('quote_success'))
            <div class="rounded-2xl bg-emerald-500/10 border border-emerald-400/30 px-5 py-4 text-sm text-emerald-100 flex items-start gap-3" role="status" data-print-hide>
                <span class="xp-tick !mt-0" aria-hidden="true">✓</span>
                <span>{{ session('quote_success') }}</span>
            </div>
        @endif
        @if (session('quote_error'))
            <div class="rounded-2xl bg-amber-500/10 border border-amber-400/30 px-5 py-4 text-sm text-amber-100" role="alert" data-print-hide>
                {{ session('quote_error') }}
            </div>
        @endif

        {{-- ══ ตัวเอกสาร ══
             แผ่นกระดาษขาวบนพื้นมืด หน้าตาเดียวกับ PDF ที่ดาวน์โหลด เลยไม่มีคลาส dark: —
             หน้านี้บังคับโหมดมืดทั้งหน้า (class="dark" บน x-xp.page) แต่กระดาษต้องขาวเสมอ --}}
        <article id="quote-paper" class="bg-white text-gray-900 rounded-2xl overflow-hidden shadow-[0_40px_90px_-40px_rgba(103,232,249,.35),0_20px_50px_-24px_rgba(0,0,0,.8)] ring-1 ring-white/10">
            {{-- หัวจดหมายแบบเดียวกับบน PDF เพื่อให้หน้าเว็บกับไฟล์ที่ดาวน์โหลด
                 อ่านแล้วรู้สึกว่าเป็นเอกสารฉบับเดียวกัน --}}
            <div class="px-5 sm:px-8 py-6 border-b border-gray-200 flex items-start justify-between gap-5 flex-wrap">
                <div>
                    @if (!empty($companyInfo['logo_url']))
                        <img src="{{ $companyInfo['logo_url'] }}" alt="{{ $companyInfo['name'] }}" class="h-9 w-auto mb-2.5">
                    @else
                        <div class="text-lg font-black tracking-wide text-gray-900 mb-1">{{ $companyInfo['name'] }}</div>
                    @endif
                    <div class="text-xs leading-relaxed text-gray-500">
                        @if ($companyInfo['address']){{ $companyInfo['address'] }}<br>@endif
                        @if ($companyInfo['phone'])<x-bi th="โทร" en="Tel" /> {{ $companyInfo['phone'] }}@endif
                        @if ($companyInfo['phone'] && $companyInfo['email']) · @endif
                        @if ($companyInfo['email']){{ $companyInfo['email'] }}@endif
                        @if ($companyInfo['tax_id'])<br><x-bi th="เลขประจำตัวผู้เสียภาษี" en="Tax ID" /> {{ $companyInfo['tax_id'] }}@endif
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-xs font-semibold tracking-[0.2em] text-gray-400 uppercase"><x-bi th="ใบเสนอราคา" en="Quotation" /></div>
                    <div class="font-mono text-sm font-bold text-gray-900 mt-0.5">{{ $quotation->quote_number }}</div>
                    <div class="text-xs text-gray-500 mt-1"><x-bi th="ออกเมื่อ" en="Issued" /> {{ $doc['quote_date'] }}</div>
                </div>
            </div>

            <div class="px-5 sm:px-8 py-4 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900"><x-bi th="รายการในใบเสนอราคา" en="Quotation items" layout="stack" /></h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 border-b border-gray-200">
                            <th class="py-3 pl-5 sm:pl-8 pr-3 font-medium"><x-bi th="รายการ" en="Description" /></th>
                            <th class="py-3 pr-5 sm:pr-8 font-medium text-right"><x-bi k="common.amount" /></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($doc['items'] as $item)
                            <tr>
                                <td class="py-3 pl-5 sm:pl-8 pr-3 text-gray-900">
                                    {{ $item['name_th'] ?? $item['name'] ?? '-' }}
                                </td>
                                <td class="py-3 pr-5 sm:pr-8 text-right font-semibold text-gray-900 tabular-nums whitespace-nowrap">
                                    {{ $money($item['price'] ?? 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-8 text-center text-gray-500"><x-bi th="ไม่มีรายการ" en="No items" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ══ ยอดรวม ══ --}}
            <div class="px-5 sm:px-8 py-6 bg-gray-50 border-t border-gray-100">
                <div class="max-w-sm ml-auto space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-600"><x-bi th="รวมเป็นเงิน" en="Subtotal" /></span><span class="tabular-nums text-gray-900">{{ $money($doc['subtotal']) }}</span></div>

                    @if ($doc['discount'] > 0)
                        <div class="flex justify-between text-emerald-700"><span><x-bi th="ส่วนลดขนาดงาน" en="Volume discount" /> {{ $doc['discount_percent'] }}%</span><span class="tabular-nums">&minus;{{ $money($doc['discount']) }}</span></div>
                    @endif
                    @if ($doc['rush_fee'] > 0)
                        <div class="flex justify-between text-amber-700"><span><x-bi th="ค่าเร่งงาน" en="Rush fee" /> {{ rtrim(rtrim(number_format(\App\Support\Quotation\Pricing::rushPercent(), 2), '0'), '.') }}%</span><span class="tabular-nums">{{ $money($doc['rush_fee']) }}</span></div>
                    @endif

                    @if ($doc['vat_mode'] === 'inclusive')
                        <div class="flex justify-between pt-2 border-t border-gray-200"><span class="text-gray-600"><x-bi th="มูลค่าสินค้า/บริการ" en="Value before VAT" /></span><span class="tabular-nums text-gray-900">{{ $money($doc['amount_before_vat']) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-600"><x-bi th="ภาษีมูลค่าเพิ่ม" en="VAT" /> {{ $rate }}%</span><span class="tabular-nums text-gray-900">{{ $money($doc['vat']) }}</span></div>
                    @elseif ($doc['vat_mode'] === 'exclusive')
                        @if ($doc['discount'] > 0 || $doc['rush_fee'] > 0)
                            <div class="flex justify-between pt-2 border-t border-gray-200"><span class="text-gray-600"><x-bi th="ราคาหลังหักส่วนลด" en="Price after discount" /></span><span class="tabular-nums text-gray-900">{{ $money($doc['amount_before_vat']) }}</span></div>
                        @endif
                        <div class="flex justify-between"><span class="text-gray-600"><x-bi th="ภาษีมูลค่าเพิ่ม" en="VAT" /> {{ $rate }}%</span><span class="tabular-nums text-gray-900">{{ $money($doc['vat']) }}</span></div>
                    @else
                        <div class="flex justify-end pt-2 border-t border-gray-200"><span class="text-xs text-gray-500"><x-bi th="ราคานี้ไม่มีภาษีมูลค่าเพิ่ม" en="This price is not subject to VAT" /></span></div>
                    @endif

                    <div class="flex justify-between items-baseline gap-3 pt-3 border-t-2 border-gray-900">
                        <span class="font-bold text-gray-900"><x-bi th="จำนวนเงินรวมทั้งสิ้น" en="Grand total" /></span>
                        <span class="tabular-nums text-2xl font-black text-indigo-700 whitespace-nowrap">฿{{ $money($doc['grand_total']) }}</span>
                    </div>

                    @if ($doc['withholding_amount'] > 0)
                        <div class="flex justify-between text-xs text-gray-500"><span><x-bi th="หัก ณ ที่จ่าย" en="Withholding tax" /> {{ rtrim(rtrim(number_format((float) $doc['withholding_pct'], 2), '0'), '.') }}%</span><span class="tabular-nums">&minus;{{ $money($doc['withholding_amount']) }}</span></div>
                        <div class="flex justify-between text-sm font-semibold text-gray-900"><span><x-bi th="ยอดโอนสุทธิ" en="Net amount payable" /></span><span class="tabular-nums">{{ $money($doc['net_payable']) }}</span></div>
                    @endif

                    <p class="pt-2 text-xs text-gray-500 text-right">( {{ $doc['grand_total_words'] }} )</p>
                </div>
            </div>
        </article>

        {{-- ══ ตอบกลับ ══ --}}
        @if ($canRespond)
            <section id="respond" class="svc-surface rounded-2xl overflow-hidden scroll-mt-24" x-data="{ mode: null }" data-print-hide>
                <div class="px-5 sm:px-7 py-5 border-b border-white/10">
                    <h2 class="text-xl font-black text-white"><x-bi th="ตอบกลับใบเสนอราคานี้" en="Respond to this quotation" layout="stack" /></h2>
                    <p class="text-sm text-gray-400 mt-1"><x-bi th="กดปุ่มด้านล่างได้เลย ทีมงานจะได้รับแจ้งทันที" en="Choose an option below — our team is notified right away." /></p>
                </div>

                <div class="p-5 sm:p-7 space-y-5">
                    <div class="grid sm:grid-cols-3 gap-3">
                        <button type="button" @click="mode = mode === 'accept' ? null : 'accept'" :aria-pressed="mode === 'accept'"
                                :class="mode === 'accept' ? 'border-emerald-400 ring-2 ring-emerald-400/30 bg-emerald-500/15' : 'border-white/10 bg-emerald-500/5'"
                                class="flex flex-col items-center gap-2 p-5 rounded-xl border-2 hover:border-emerald-400/70 transition text-center">
                            <svg class="w-7 h-7 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span class="font-bold text-emerald-100"><x-bi th="ตอบรับ" en="Accept quotation" layout="stack" /></span>
                            <span class="text-xs text-emerald-200/70"><x-bi th="เริ่มงานได้เลย" en="Let's get started" layout="stack" /></span>
                        </button>

                        <button type="button" @click="mode = mode === 'negotiate' ? null : 'negotiate'" :aria-pressed="mode === 'negotiate'"
                                :class="mode === 'negotiate' ? 'border-amber-400 ring-2 ring-amber-400/30 bg-amber-500/15' : 'border-white/10 bg-amber-500/5'"
                                class="flex flex-col items-center gap-2 p-5 rounded-xl border-2 hover:border-amber-400/70 transition text-center">
                            <svg class="w-7 h-7 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.9 9.9 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            <span class="font-bold text-amber-100"><x-bi th="ขอต่อรอง" en="Request changes" layout="stack" /></span>
                            <span class="text-xs text-amber-200/70"><x-bi th="บอกได้ว่าติดตรงไหน" en="Tell us what doesn't work" layout="stack" /></span>
                        </button>

                        <button type="button" @click="mode = mode === 'decline' ? null : 'decline'" :aria-pressed="mode === 'decline'"
                                :class="mode === 'decline' ? 'border-gray-300 ring-2 ring-gray-300/20 bg-white/10' : 'border-white/10 bg-white/[.03]'"
                                class="flex flex-col items-center gap-2 p-5 rounded-xl border-2 hover:border-gray-400 transition text-center">
                            <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span class="font-bold text-gray-100"><x-bi th="ยังไม่เอาตอนนี้" en="Not right now" layout="stack" /></span>
                            <span class="text-xs text-gray-400"><x-bi th="ปิดใบนี้ไว้ก่อน" en="Close this quotation for now" layout="stack" /></span>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('quote.respond', $quotation->public_token) }}"
                          x-show="mode" x-cloak class="space-y-3 svc-panel" @submit="$el.querySelector('button[type=submit]').disabled = true">
                        @csrf
                        <input type="hidden" name="action" :value="mode">

                        <div x-show="mode === 'negotiate'">
                            <label for="quote-message" class="block text-sm font-semibold text-gray-200 mb-1.5">
                                <x-bi th="อยากปรับตรงไหนครับ" en="What would you like to change?" />
                            </label>
                            {{-- สองช่องใช้ชื่อ message เหมือนกัน: ช่องที่ไม่ได้ใช้ต้องปิดไว้ ไม่งั้นช่องท้าย (ว่าง) ทับข้อความที่พิมพ์ --}}
                            <textarea id="quote-message" name="message" rows="3" :disabled="mode !== 'negotiate'"
                                      placeholder="เช่น ขอตัดรายการ SEO ออกก่อน หรืองบอยู่ที่ประมาณ 180,000 / e.g. drop the SEO item for now, or our budget is about 180,000"
                                      class="w-full rounded-xl bg-black/30 border-white/10 text-white placeholder-gray-500 text-sm focus:border-cyan-300/60 focus:ring-cyan-300/30">{{ old('message') }}</textarea>
                        </div>

                        <div x-show="mode === 'decline'">
                            <label for="quote-reason" class="block text-sm font-semibold text-gray-200 mb-1.5">
                                <x-bi th="บอกเหตุผลสั้น ๆ ได้ถ้าสะดวก" en="A short reason, if you don't mind" /> <span class="font-normal text-gray-500"><x-bi k="common.optional" /></span>
                            </label>
                            <textarea id="quote-reason" name="message" rows="2" :disabled="mode !== 'decline'"
                                      class="w-full rounded-xl bg-black/30 border-white/10 text-white placeholder-gray-500 text-sm focus:border-cyan-300/60 focus:ring-cyan-300/30">{{ old('message') }}</textarea>
                        </div>

                        <p x-show="mode === 'accept'" class="text-sm text-gray-300">
                            <x-bi th="กดยืนยันแล้วเราจะล็อกราคาและขอบเขตงานตามใบนี้ แล้วติดต่อกลับเพื่อเริ่มงาน" en="Once you confirm, we'll lock in the price and scope in this quotation and get back to you to start the work." />
                        </p>

                        <button type="submit"
                                class="xp-btn-main w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl font-extrabold transition disabled:opacity-60">
                            <x-bi th="ยืนยันคำตอบ" en="Submit response" />
                        </button>
                    </form>
                </div>
            </section>
        @else
            <div class="svc-surface rounded-2xl p-6 text-center" data-print-hide>
                <p class="text-white font-semibold">
                    @if ($quotation->isSuperseded())
                        {{-- ลูกค้าถือลิงก์เก่าไว้ ต้องบอกให้รู้ว่ามีฉบับใหม่ ไม่ใช่ปล่อยให้คิดว่าเราปิดงานไปแล้ว --}}
                        <x-bi th="ใบเสนอราคาฉบับนี้มีฉบับแก้ไขใหม่แทนแล้ว" en="This quotation has been replaced by a revised version." />
                    @elseif ($expired)
                        <x-bi th="ใบเสนอราคานี้หมดอายุแล้ว" en="This quotation has expired." />
                    @else
                        <x-bi :th="'ใบเสนอราคานี้มีคำตอบเรียบร้อยแล้ว — ' . $tone['label']" :en="'This quotation has already been answered — ' . $tone['label_en']" />
                    @endif
                </p>
                <p class="text-sm text-gray-400 mt-1">
                    @if ($quotation->isSuperseded())
                        <x-bi th="ทีมงานส่งฉบับใหม่ไปที่อีเมลของคุณแล้ว ถ้าไม่ได้รับ ติดต่อทีมงานได้ที่" en="We've emailed you the new version. If it hasn't arrived, reach our team via" />
                    @else
                        <x-bi th="ต้องการคุยต่อ ติดต่อทีมงานได้ที่" en="Want to discuss further? Reach our team via" />
                    @endif
                    <a href="{{ route('contact.show') }}" class="xp-link underline-offset-2 hover:underline"><x-bi th="หน้าติดต่อเรา" en="our contact page" /></a>
                </p>
            </div>
        @endif

        <p class="text-xs text-gray-500 text-center" data-print-hide>
            <x-bi th="ลิงก์นี้เป็นของใบเสนอราคาฉบับนี้โดยเฉพาะ กรุณาอย่าส่งต่อให้คนนอกองค์กร" en="This link belongs to this quotation only. Please don't share it outside your organisation." />
        </p>
    </div>
</x-xp.page>
@endsection
