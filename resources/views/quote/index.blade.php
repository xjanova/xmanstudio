@extends($publicLayout ?? 'layouts.app')

@section('title', 'สั่งงาน & รับใบเสนอราคา')
@section('meta_description', 'บอกผลลัพธ์ที่อยากได้ ระบบเลือกรายการที่เข้ากับงานของคุณมาให้ พร้อมราคาและกรอบเวลา ออกใบเสนอราคา PDF ส่งเข้าอีเมลทันที')

@section('content')
@php
    $cat = $catalogue;
@endphp

<div
    x-data="quoteBuilder(@js([
        'catalogue' => $cat,
        'vatModes' => $vatModes,
        'endpoints' => [
            'submit' => route('quote.submit'),
            'pdf' => route('quote.pdf'),
            'previewDocument' => route('quote.preview-document'),
        ],
        'csrf' => csrf_token(),
    ]))"
    x-cloak
>

    {{-- ══════════════════ HERO ══════════════════ --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
        <x-page-art art="hero-quote" :opacity="55" :scrim="false" fade="bottom" />
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/95 via-slate-950/45 to-slate-950/80 pointer-events-none" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/95 via-transparent to-transparent pointer-events-none" aria-hidden="true"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-20">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/20 px-3.5 py-1.5 backdrop-blur mb-5">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                    </span>
                    <span class="text-xs font-semibold text-sky-100">ตอบราคาทันที ไม่ต้องรอทีมงานติดต่อกลับ</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-[1.1] tracking-tight mb-5">
                    สั่งงาน
                    <span class="bg-gradient-to-r from-blue-400 to-cyan-300 bg-clip-text text-transparent">&amp;</span>
                    รับใบเสนอราคา
                </h1>

                <p class="text-base sm:text-lg text-slate-300 leading-relaxed mb-8">
                    บอกเราว่าอยากได้ผลลัพธ์แบบไหน ระบบจะเลือกรายการที่เข้ากับงานของคุณมาให้
                    พร้อมเหตุผลว่าทำไมต้องมี ราคา และกรอบเวลาที่ประเมินจากงานจริง
                </p>

                <div class="flex flex-wrap gap-2.5">
                    @foreach ([
                        ['เห็นราคาระหว่างเลือก', 'M13 2 3 14h9l-1 8 10-12h-9z'],
                        ['ใบเสนอราคา PDF เข้าอีเมล', 'M2 4h20v16H2z M22 7l-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7'],
                        ['ยืนราคา 30 วัน', 'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z M12 6v6l4 2'],
                    ] as [$label, $path])
                        <div class="inline-flex items-center gap-2 rounded-xl bg-white/8 border border-white/15 px-4 py-2.5 backdrop-blur">
                            <svg class="w-4 h-4 text-sky-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="{{ $path }}"/></svg>
                            <span class="text-sm font-medium text-slate-200">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════ BODY ══════════════════ --}}
    <div class="bg-gray-50 dark:bg-gray-900 py-10 sm:py-14">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- ส่งสำเร็จ — แทนที่ตัวสร้างทั้งหมด ไม่ให้กดซ้ำ --}}
            <div x-show="done" x-cloak class="max-w-2xl mx-auto text-center py-12">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 shadow-lg shadow-emerald-500/30 mb-6">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3" x-text="doneTitle"></h2>
                <p class="text-gray-600 dark:text-gray-300 mb-2" x-text="doneMessage"></p>
                <p class="font-mono text-sm text-gray-500 dark:text-gray-400 mb-8" x-text="doneNumber"></p>
                <template x-if="doneUrl">
                    <a :href="doneUrl" class="inline-flex items-center gap-2 px-7 py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-semibold shadow-lg shadow-blue-500/30 hover:-translate-y-0.5 transition-all">
                        เปิดใบเสนอราคา
                    </a>
                </template>
            </div>

            <div x-show="! done" class="grid lg:grid-cols-[minmax(0,1fr)_380px] gap-8 items-start">

                {{-- ─────────── คอลัมน์ซ้าย ─────────── --}}
                <div class="space-y-8 min-w-0">

                    {{-- ══ ขั้น 1 · ผลลัพธ์ ══ --}}
                    <section>
                        <div class="flex items-center gap-3.5 mb-1.5">
                            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 text-white font-bold flex items-center justify-center shadow-lg shadow-blue-500/30 shrink-0">1</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">คุณอยากได้อะไร</h2>
                        </div>
                        <p class="ml-[3.1rem] text-sm text-gray-600 dark:text-gray-400 mb-5">
                            เลือกจาก <strong class="font-semibold">ผลลัพธ์</strong> ที่อยากได้ ไม่ต้องรู้ว่าเราเรียกบริการนั้นว่าอะไร
                        </p>

                        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                            <template x-for="o in catalogue.outcomes" :key="o.key">
                                <button type="button" @click="pickOutcome(o)"
                                        :class="outcome === o.key
                                            ? 'border-blue-600 ring-2 ring-blue-500/25 shadow-xl -translate-y-0.5'
                                            : 'border-gray-200 dark:border-gray-700 hover:border-blue-300 hover:-translate-y-0.5'"
                                        class="group relative text-left bg-white dark:bg-gray-800 border-2 rounded-2xl p-5 shadow-lg transition-all duration-300">
                                    <span x-show="outcome === o.key"
                                          class="absolute top-3.5 right-3.5 w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center">
                                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                                    </span>

                                    <span :class="'bg-gradient-to-br ' + o.accent"
                                          class="flex w-12 h-12 rounded-xl items-center justify-center shadow-lg mb-3.5 transition-transform group-hover:scale-110">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path :d="o.icon"/></svg>
                                    </span>

                                    <span class="block font-bold text-gray-900 dark:text-white mb-1" x-text="o.th"></span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 mb-2.5" x-text="o.en"></span>
                                    <span class="block text-sm text-gray-600 dark:text-gray-300 leading-relaxed" x-text="o.desc_th"></span>
                                </button>
                            </template>
                        </div>
                    </section>

                    {{-- ══ ขั้น 2 · รายการ ══ --}}
                    <section x-show="outcome" x-cloak>
                        <div class="flex items-center gap-3.5 mb-1.5">
                            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 text-white font-bold flex items-center justify-center shadow-lg shadow-blue-500/30 shrink-0">2</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">งานนี้ต้องมีอะไรบ้าง</h2>
                        </div>
                        <p class="ml-[3.1rem] text-sm text-gray-600 dark:text-gray-400 mb-5">
                            แบ่งเป็นสามชั้นตามคำตอบของคุณ — ทุกบรรทัดบอกราคาและเวลาที่ใช้
                        </p>

                        {{-- ชั้นที่ 1: บริการหลัก --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-4">
                            <div class="px-5 sm:px-6 py-3.5 bg-blue-50 dark:bg-blue-500/10 border-b border-blue-100 dark:border-blue-500/20 flex items-center justify-between gap-3 flex-wrap">
                                <div class="flex items-center gap-2.5">
                                    <svg class="w-4.5 h-4.5 text-blue-700 dark:text-blue-300" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                                    <span class="font-bold text-blue-900 dark:text-blue-200" x-text="service ? service.name_th : ''"></span>
                                </div>
                                <span class="text-xs text-blue-800 dark:text-blue-300" x-text="coreCount + ' รายการที่เลือกไว้'"></span>
                            </div>

                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                <template x-for="opt in serviceOptions" :key="opt.key">
                                    <label class="flex items-start gap-3.5 px-5 sm:px-6 py-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                        <input type="checkbox" :value="opt.key" x-model="picked"
                                               :disabled="opt.is_core"
                                               class="mt-0.5 w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 shrink-0 disabled:opacity-60">
                                        <span class="flex-grow min-w-0">
                                            <span class="block font-semibold text-gray-900 dark:text-white" x-text="opt.name_th"></span>
                                            <span class="block text-sm text-gray-600 dark:text-gray-400 mt-0.5"
                                                  x-text="opt.reason_th || opt.desc_th"></span>
                                            <span x-show="opt.is_core" class="inline-block mt-1.5 text-xs px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-200">ไม่มีไม่ได้</span>
                                        </span>
                                        <span class="text-right shrink-0">
                                            <span class="block font-semibold text-gray-900 dark:text-white tabular-nums" x-text="money(opt.price)"></span>
                                            <span x-show="opt.sale_percent > 0" class="block text-xs text-emerald-600 dark:text-emerald-400" x-text="'ลด ' + opt.sale_percent + '%'"></span>
                                            <span x-show="opt.duration_days > 0" class="block text-xs text-gray-500 dark:text-gray-400" x-text="weeks(opt.duration_days)"></span>
                                        </span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- ชั้นที่ 2: แนะนำตามผลลัพธ์ --}}
                        <template x-for="group in suggestedAddonGroups" :key="'s-' + group.key">
                            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-4">
                                <div class="px-5 sm:px-6 py-3.5 bg-emerald-50 dark:bg-emerald-500/10 border-b border-emerald-100 dark:border-emerald-500/20 flex items-center justify-between gap-3 flex-wrap">
                                    <div class="flex items-center gap-2.5">
                                        <svg class="w-4.5 h-4.5 text-emerald-700 dark:text-emerald-300" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 2 9.2 8.6 2 9.2l5.5 4.7L5.8 21 12 17.3 18.2 21l-1.7-7.1L22 9.2l-7.2-.6z"/></svg>
                                        <span class="font-bold text-emerald-900 dark:text-emerald-200" x-text="group.name_th"></span>
                                    </div>
                                    <span class="text-xs text-emerald-800 dark:text-emerald-300" x-text="'แนะนำเพราะคุณเลือก “' + outcomeLabel + '”'"></span>
                                </div>
                                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <template x-for="opt in group.options" :key="opt.key">
                                        <label class="flex items-start gap-3.5 px-5 sm:px-6 py-3.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                            <input type="checkbox" :value="opt.key" x-model="addons"
                                                   class="mt-0.5 w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 shrink-0">
                                            <span class="flex-grow min-w-0">
                                                <span class="block font-medium text-gray-900 dark:text-white" x-text="opt.name_th"></span>
                                                <span x-show="opt.reason_th" class="block text-sm text-gray-600 dark:text-gray-400 mt-0.5" x-text="opt.reason_th"></span>
                                            </span>
                                            <span class="font-semibold text-gray-900 dark:text-white tabular-nums shrink-0" x-text="money(opt.price)"></span>
                                        </label>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- ชั้นที่ 3: ที่เหลือ พับไว้ --}}
                        <div x-show="otherAddonGroups.length" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                            <button type="button" @click="showAll = ! showAll"
                                    class="w-full flex items-center justify-between gap-3 px-5 sm:px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                <span class="flex items-center gap-2.5">
                                    <svg class="w-4.5 h-4.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                                    <span class="font-bold text-gray-800 dark:text-gray-200">เพิ่มได้ถ้าต้องการ</span>
                                </span>
                                <span class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span x-text="otherOptionCount + ' รายการ'"></span>
                                    <svg class="w-5 h-5 transition-transform" :class="showAll && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m19 9-7 7-7-7"/></svg>
                                </span>
                            </button>

                            <div x-show="showAll" x-cloak class="border-t border-gray-100 dark:border-gray-700">
                                <template x-for="group in otherAddonGroups" :key="'o-' + group.key">
                                    <div>
                                        <div class="px-5 sm:px-6 py-2.5 bg-gray-50 dark:bg-gray-900/40 text-xs font-semibold text-gray-600 dark:text-gray-300" x-text="group.name_th"></div>
                                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                            <template x-for="opt in group.options" :key="opt.key">
                                                <label class="flex items-center gap-3.5 px-5 sm:px-6 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/40 transition">
                                                    <input type="checkbox" :value="opt.key" x-model="addons"
                                                           class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 shrink-0">
                                                    <span class="flex-grow min-w-0 text-sm text-gray-800 dark:text-gray-200" x-text="opt.name_th"></span>
                                                    <span class="text-sm font-medium text-gray-900 dark:text-white tabular-nums shrink-0" x-text="money(opt.price)"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </section>

                    {{-- ══ ขั้น 3 · ผู้รับใบ ══ --}}
                    <section x-show="outcome" x-cloak>
                        <div class="flex items-center gap-3.5 mb-1.5">
                            <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 text-white font-bold flex items-center justify-center shadow-lg shadow-blue-500/30 shrink-0">3</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">ส่งใบเสนอราคาไปที่ไหน</h2>
                        </div>
                        <p class="ml-[3.1rem] text-sm text-gray-600 dark:text-gray-400 mb-5">
                            สิ่งที่กรอกตรงนี้จะถูก <strong class="font-semibold">พิมพ์ลงบนเอกสาร</strong> ตามที่พิมพ์เป๊ะ ๆ
                            — ใช้ตั้งเบิกได้เลย ช่องที่มี <span class="text-red-500 font-semibold">*</span> จำเป็นต้องมี
                            ที่เหลือใส่ได้ทีหลัง
                        </p>

                        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5 sm:p-7 space-y-6">

                            {{-- ── ติดต่อกลับ ── --}}
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">ติดต่อกลับได้ที่</span>
                                </div>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="q-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ชื่อผู้ติดต่อ <span class="text-red-500">*</span></label>
                                        <input id="q-name" type="text" x-model="customer.customer_name" required
                                               placeholder="เช่น สมชาย ใจดี"
                                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label for="q-phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">เบอร์โทร <span class="text-red-500">*</span></label>
                                        <input id="q-phone" type="tel" x-model="customer.customer_phone" required inputmode="tel"
                                               placeholder="08X-XXX-XXXX"
                                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="q-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">อีเมล <span class="text-red-500">*</span></label>
                                        <input id="q-email" type="email" x-model="customer.customer_email" required
                                               placeholder="you@company.co.th"
                                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500">
                                        <p class="mt-1.5 flex items-start gap-1.5 text-xs text-blue-700 dark:text-blue-300">
                                            <svg class="w-3.5 h-3.5 shrink-0 mt-px" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                            <span>ไฟล์ PDF และลิงก์สำหรับกดตอบรับจะถูกส่งไปที่อีเมลนี้</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- ── ชื่อบนเอกสาร ──
                                 แยกกล่องออกมาเพราะสามช่องนี้ไม่ได้ใช้ติดต่อ แต่ถูก
                                 "พิมพ์ลงกระดาษ" ซึ่งคนละเรื่องกัน และเป็นจุดที่ลูกค้า
                                 องค์กรต้องกรอกให้ตรงกับที่ฝ่ายบัญชีใช้ --}}
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-900/40 p-4 sm:p-5">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">ชื่อที่จะพิมพ์ลงบนเอกสาร</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">ไม่บังคับ</span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-3.5">
                                    ถ้าเป็นนิติบุคคลและต้องใช้ตั้งเบิก กรอกให้ตรงกับที่ฝ่ายบัญชีใช้
                                    — ถ้าเป็นบุคคลธรรมดา ข้ามได้เลย เราจะใช้ชื่อผู้ติดต่อด้านบนแทน
                                </p>
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div class="sm:col-span-2">
                                        <label for="q-company" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ชื่อบริษัท / ร้าน</label>
                                        <input id="q-company" type="text" x-model="customer.customer_company"
                                               placeholder="เช่น บริษัท ตัวอย่าง จำกัด"
                                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label for="q-tax" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">เลขประจำตัวผู้เสียภาษี</label>
                                        <input id="q-tax" type="text" x-model="customer.customer_tax_id" inputmode="numeric" maxlength="20"
                                               placeholder="13 หลัก"
                                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm tabular-nums focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label for="q-address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">ที่อยู่ตามใบกำกับ</label>
                                        <input id="q-address" type="text" x-model="customer.customer_address"
                                               placeholder="เลขที่ ถนน แขวง เขต จังหวัด รหัสไปรษณีย์"
                                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>

                            {{-- ── เล่างาน ── --}}
                            <div>
                                <label for="q-brief" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    เล่างานให้ฟังสั้น ๆ <span class="font-normal text-gray-500">ไม่บังคับ แต่ช่วยให้เราเสนอได้ตรงขึ้น</span>
                                </label>
                                <textarea id="q-brief" rows="3" x-model="customer.project_description" maxlength="2000"
                                          placeholder="ตอนนี้ทำอะไรอยู่ ติดปัญหาตรงไหน อยากให้จบเมื่อไร"
                                          class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>

                            {{-- ภาษี --}}
                            <div class="rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                                <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">ภาษีมูลค่าเพิ่ม</div>
                                <div class="grid sm:grid-cols-3 gap-2">
                                    <template x-for="m in vatModes" :key="m.key">
                                        <button type="button" @click="vatMode = m.key"
                                                :class="vatMode === m.key
                                                    ? 'border-blue-600 bg-blue-50 dark:bg-blue-500/15 text-blue-900 dark:text-blue-200'
                                                    : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-blue-300'"
                                                class="text-left border-2 rounded-xl px-3.5 py-2.5 transition">
                                            <span class="block text-sm font-semibold" x-text="m.th"></span>
                                            <span class="block text-xs opacity-80 mt-0.5" x-text="m.en"></span>
                                        </button>
                                    </template>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400" x-text="vatHint"></p>

                                <label class="flex items-center gap-2.5 pt-1 cursor-pointer">
                                    <input type="checkbox" :checked="withholding > 0" @change="withholding = $event.target.checked ? 3 : 0"
                                           class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">หักภาษี ณ ที่จ่าย 3% (นิติบุคคลจ่ายค่าบริการ)</span>
                                </label>
                            </div>

                            {{-- กรอบเวลา --}}
                            <div>
                                <div class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">ต้องการงานเมื่อไร</div>
                                <div class="grid sm:grid-cols-3 gap-2">
                                    @foreach ([
                                        ['flexible', 'ยืดหยุ่นได้', 'ไม่มีกำหนดตายตัว'],
                                        ['normal', 'ตามปกติ', 'ตามกรอบเวลาที่ประเมิน'],
                                        ['urgent', 'เร่งด่วน', 'ย่นเวลา คิดเพิ่ม 25%'],
                                    ] as [$val, $label, $hint])
                                        <button type="button" @click="timeline = '{{ $val }}'"
                                                :class="timeline === '{{ $val }}'
                                                    ? 'border-blue-600 bg-blue-50 dark:bg-blue-500/15 text-blue-900 dark:text-blue-200'
                                                    : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-blue-300'"
                                                class="text-left border-2 rounded-xl px-3.5 py-2.5 transition">
                                            <span class="block text-sm font-semibold">{{ $label }}</span>
                                            <span class="block text-xs opacity-80 mt-0.5">{{ $hint }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- ─────────── สรุปยอด ─────────── --}}
                <div class="lg:sticky lg:top-24 space-y-4">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                        <div class="px-5 py-4 bg-gradient-to-br from-slate-900 to-slate-800 flex items-center justify-between">
                            <span class="flex items-center gap-2.5 text-white font-bold">
                                <svg class="w-4.5 h-4.5 text-sky-300" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                                ใบเสนอราคาของคุณ
                            </span>
                            <span class="text-xs font-mono text-blue-300">ร่าง</span>
                        </div>

                        {{-- ยังไม่เลือกอะไร --}}
                        <div x-show="! lines.length" class="px-5 py-10 text-center">
                            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gray-100 dark:bg-gray-700 mb-3">
                                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">เลือกผลลัพธ์ที่อยากได้ด้านซ้าย<br>แล้วราคาจะขึ้นตรงนี้</p>
                        </div>

                        <div x-show="lines.length" x-cloak>
                            <div class="px-5 py-4 space-y-2 border-b border-gray-100 dark:border-gray-700 max-h-60 overflow-y-auto">
                                <template x-for="line in lines" :key="line.key">
                                    <div class="flex justify-between gap-3 text-sm">
                                        <span class="text-gray-600 dark:text-gray-300 min-w-0 truncate" x-text="line.name_th"></span>
                                        <span class="text-gray-900 dark:text-white tabular-nums shrink-0" x-text="money(line.price)"></span>
                                    </div>
                                </template>
                            </div>

                            <div class="px-5 py-4 space-y-2.5">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-300">รวมก่อนส่วนลด</span>
                                    <span class="font-semibold text-gray-900 dark:text-white tabular-nums" x-text="money(subtotal)"></span>
                                </div>

                                <div x-show="discount > 0" class="rounded-xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 px-3.5 py-2.5">
                                    <div class="flex justify-between text-sm mb-0.5">
                                        <span class="font-semibold text-emerald-900 dark:text-emerald-200" x-text="'ส่วนลดขนาดงาน ' + discountPercent + '%'"></span>
                                        <span class="font-semibold text-emerald-700 dark:text-emerald-300 tabular-nums" x-text="'−' + money(discount)"></span>
                                    </div>
                                    <div class="text-xs text-emerald-800 dark:text-emerald-300" x-text="nextTierHint"></div>
                                </div>
                                <div x-show="discount === 0 && nextTierHint" class="rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 px-3.5 py-2.5 text-xs text-gray-600 dark:text-gray-400" x-text="nextTierHint"></div>

                                <div x-show="rushFee > 0" class="flex justify-between text-sm">
                                    <span class="text-amber-700 dark:text-amber-300">ค่าเร่งงาน 25%</span>
                                    <span class="font-semibold text-amber-700 dark:text-amber-300 tabular-nums" x-text="money(rushFee)"></span>
                                </div>

                                <template x-if="vatMode === 'inclusive'">
                                    <div class="flex justify-between text-sm pt-2 border-t border-gray-100 dark:border-gray-700">
                                        <span class="text-gray-600 dark:text-gray-300">มูลค่าสินค้า/บริการ</span>
                                        <span class="text-gray-900 dark:text-white tabular-nums" x-text="money(vatBase)"></span>
                                    </div>
                                </template>
                                <div x-show="vatMode !== 'none'" class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-300">ภาษีมูลค่าเพิ่ม 7%</span>
                                    <span class="text-gray-900 dark:text-white tabular-nums" x-text="money(vat)"></span>
                                </div>
                                <div x-show="vatMode === 'none'" class="text-xs text-gray-500 dark:text-gray-400 text-right">ราคานี้ไม่มีภาษีมูลค่าเพิ่ม</div>

                                <div class="flex justify-between items-baseline pt-3 border-t-2 border-gray-900 dark:border-gray-200">
                                    <span class="font-bold text-gray-900 dark:text-white">ยอดสุทธิ</span>
                                    <span class="text-2xl font-bold text-blue-700 dark:text-blue-300 tabular-nums" x-text="'฿' + money(grandTotal)"></span>
                                </div>

                                <div x-show="withholding > 0" class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                                    <span>หัก ณ ที่จ่าย 3% · ยอดโอนสุทธิ</span>
                                    <span class="tabular-nums" x-text="money(netPayable)"></span>
                                </div>

                                <div x-show="durationWeeks" class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 pt-1">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                                    <span x-text="durationWeeks"></span>
                                </div>

                                <p x-show="pulledIn.length" x-cloak class="text-xs text-blue-800 dark:text-blue-300 bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/30 rounded-lg px-3 py-2"
                                   x-text="'เพิ่มให้อัตโนมัติเพราะรายการที่เลือกต้องใช้: ' + pulledIn.join(', ')"></p>

                                <p x-show="error" x-cloak class="text-sm text-red-600 dark:text-red-400 pt-1" x-text="error"></p>

                                <button type="button" @click="previewDocument()" :disabled="previewing || ! lines.length"
                                        class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 font-semibold hover:bg-gray-50 dark:hover:bg-gray-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg x-show="! previewing" class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg x-show="previewing" x-cloak class="w-4.5 h-4.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    <span x-text="previewing ? 'กำลังจัดหน้า…' : 'ดูตัวอย่างใบเสนอราคา'"></span>
                                </button>

                                <button type="button" @click="submit()" :disabled="sending || ! canSubmit"
                                        class="w-full mt-1 inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 text-white font-bold shadow-lg shadow-blue-500/30 hover:shadow-xl hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:translate-y-0">
                                    <svg x-show="! sending" class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                                    <svg x-show="sending" x-cloak class="w-4.5 h-4.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    <span x-text="sending ? 'กำลังออกใบ…' : 'ส่งใบเสนอราคาเข้าอีเมล'"></span>
                                </button>

                                <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                    ยืนราคา 30 วัน · ยังไม่มีข้อผูกมัดใด ๆ
                                </p>
                            </div>
                        </div>
                    </div>

                    <div x-show="restored" x-cloak class="rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/30 px-4 py-3">
                        <p class="text-sm text-blue-900 dark:text-blue-200">กลับมาต่อจากที่ค้างไว้ให้แล้ว</p>
                        <button type="button" @click="reset()" class="mt-1 text-xs text-blue-700 dark:text-blue-300 underline hover:no-underline">
                            เริ่มเลือกใหม่ทั้งหมด
                        </button>
                    </div>

                    <a href="{{ route('quote.track') }}" class="block text-center text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition">
                        เคยสั่งงานไว้แล้ว? ติดตามสถานะงาน
                    </a>
                </div>
            </div>
        </div>
    </div>
    {{-- ══ ตัวอย่างเอกสาร ══ --}}
    <div x-show="previewHtml" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-8"
         @keydown.escape.window="previewHtml = null">
        <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm" @click="previewHtml = null"></div>
        <div class="relative w-full max-w-3xl max-h-full bg-white dark:bg-gray-800 rounded-2xl shadow-2xl flex flex-col overflow-hidden">
            <div class="flex items-center justify-between gap-4 px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 shrink-0">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white">ตัวอย่างใบเสนอราคา</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ยังไม่ได้ออกเลขที่จริง · กดส่งเพื่อรับฉบับจริงทางอีเมล</p>
                </div>
                <button type="button" @click="previewHtml = null" aria-label="ปิด"
                        class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
            {{-- srcdoc + sandbox: เอกสารเป็น HTML ที่เรนเดอร์จากค่าที่ผู้ใช้พิมพ์
                 ปิดสคริปต์ไว้ ไม่ให้อะไรที่หลุดการ escape ทำงานได้ --}}
            <iframe x-ref="previewFrame" :srcdoc="previewHtml" sandbox=""
                    title="ตัวอย่างใบเสนอราคา"
                    class="flex-grow w-full bg-white" style="min-height: 60vh;"></iframe>
        </div>
    </div>
</div>

@push('scripts')
<script>
function quoteBuilder(config) {
    // ปัดสองตำแหน่งให้ตรงกับฝั่ง PHP — ลอยตัวดิบ ๆ จะเพี้ยนหลักสตางค์
    const r2 = (n) => Math.round((n + Number.EPSILON) * 100) / 100;

    return {
        catalogue: config.catalogue,
        vatModes: config.vatModes,
        endpoints: config.endpoints,
        csrf: config.csrf,

        outcome: null,
        picked: [],
        addons: [],
        showAll: false,
        timeline: 'normal',
        vatMode: 'exclusive',
        withholding: 0,
        customer: {
            customer_name: '', customer_company: '', customer_email: '',
            customer_phone: '', customer_address: '', customer_tax_id: '',
            project_description: '',
        },
        sending: false,
        previewing: false,
        previewHtml: null,
        restored: false,
        error: null,
        done: false,
        doneTitle: '', doneMessage: '', doneNumber: '', doneUrl: null,

        /** คีย์ร่างใน localStorage — ขึ้นเวอร์ชันเมื่อรูปร่าง state เปลี่ยน */
        storageKey: 'xman.quote.draft.v1',

        init() {
            this.restore();

            // เก็บทุกครั้งที่อะไรก็ตามเปลี่ยน ไม่ต้องไล่ผูกทีละช่อง
            this.$watch('outcome', () => this.persist());
            this.$watch('picked', () => { this.applyRequires(); this.persist(); });
            this.$watch('addons', () => { this.applyRequires(); this.persist(); });
            this.$watch('customer', () => this.persist());
            this.$watch('timeline', () => this.persist());
            this.$watch('vatMode', () => this.persist());
            this.$watch('withholding', () => this.persist());
        },

        /**
         * เก็บร่างไว้ในเครื่องของลูกค้าเอง
         *
         * ปิดแท็บแล้วกลับมาต้องได้ของเดิม — คนเลือกไปสิบกว่ารายการแล้วต้องเริ่มใหม่
         * เพราะเผลอกดย้อนกลับ คือเหตุผลที่คนเลิกกลางคัน
         * ไม่เก็บอะไรขึ้นเซิร์ฟเวอร์ และ localStorage อาจใช้ไม่ได้ (โหมดส่วนตัว /
         * ปิดคุกกี้) จึงต้องพังแบบเงียบ ไม่ใช่ทำให้หน้าล่ม
         */
        persist() {
            try {
                localStorage.setItem(this.storageKey, JSON.stringify({
                    at: Date.now(),
                    outcome: this.outcome,
                    picked: this.picked,
                    addons: this.addons,
                    customer: this.customer,
                    timeline: this.timeline,
                    vatMode: this.vatMode,
                    withholding: this.withholding,
                }));
            } catch (e) { /* โหมดส่วนตัว หรือพื้นที่เต็ม — ไม่ใช่เรื่องที่ต้องบอกลูกค้า */ }
        },

        restore() {
            let raw = null;
            try { raw = localStorage.getItem(this.storageKey); } catch (e) { return; }
            if (!raw) return;

            let d;
            try { d = JSON.parse(raw); } catch (e) { this.forget(); return; }

            // ร่างเก่ากว่า 14 วันไม่น่าใช่สิ่งที่เขาตั้งใจกลับมาทำต่อ
            if (!d || !d.at || Date.now() - d.at > 14 * 864e5) { this.forget(); return; }

            // แคตตาล็อกอาจเปลี่ยนไปแล้ว — ผลลัพธ์ที่ไม่มีอยู่จริงต้องไม่ถูกคืนค่า
            const outcome = this.catalogue.outcomes.find((o) => o.key === d.outcome);
            if (!outcome) { this.forget(); return; }

            this.outcome = outcome.key;

            // คืนเฉพาะคีย์ที่ยังมีอยู่ ไม่งั้นราคาบนจอจะนับของที่เซิร์ฟเวอร์ตัดทิ้ง
            const validService = new Set(this.serviceOptions.map((o) => o.key));
            const validAddons = new Set(
                Object.values(this.catalogue.addons).flatMap((g) => g.options.map((o) => o.key))
            );
            this.picked = (d.picked || []).filter((k) => validService.has(k));
            this.addons = (d.addons || []).filter((k) => validAddons.has(k));

            // แกนหลักต้องติดกลับมาเสมอ แม้ร่างเก่าจะบันทึกไว้ตอนที่ยังไม่ได้ตั้งเป็นแกนหลัก
            for (const o of this.serviceOptions) {
                if (o.is_core && !this.picked.includes(o.key)) this.picked.push(o.key);
            }

            if (d.customer) Object.assign(this.customer, d.customer);
            if (d.timeline) this.timeline = d.timeline;
            if (d.vatMode) this.vatMode = d.vatMode;
            if (typeof d.withholding === 'number') this.withholding = d.withholding;

            this.restored = this.picked.length > 0 || this.addons.length > 0;
        },

        forget() {
            try { localStorage.removeItem(this.storageKey); } catch (e) { /* ไม่เป็นไร */ }
        },

        reset() {
            this.forget();
            this.outcome = null;
            this.picked = [];
            this.addons = [];
            this.restored = false;
            this.error = null;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /**
         * เลือกผลลัพธ์ใหม่ = เริ่มรายการใหม่ ไม่งั้นของเก่าจากหมวดอื่นค้างอยู่ในตะกร้า
         * แล้วเซิร์ฟเวอร์จะตัดทิ้งเงียบ ๆ ตอนคิดเงิน (มันวนเฉพาะ option ของ
         * service_type ที่ส่งมา) ลูกค้าจะเห็นราคาบนจอไม่ตรงกับในใบ
         */
        pickOutcome(o) {
            if (this.outcome === o.key) return;
            this.outcome = o.key;
            this.picked = this.serviceOptions.filter((x) => x.is_core).map((x) => x.key);
            // ของเสริมที่แอดมินตั้งเป็นแกนหลักก็ติ๊กให้ด้วย แต่เฉพาะกลุ่มที่
            // แนะนำกับผลลัพธ์นี้ — ไม่งั้น "แกนหลัก" ของงานอื่นจะโผล่มาทุกครั้ง
            this.addons = this.suggestedAddonGroups
                .flatMap((g) => g.options)
                .filter((x) => x.is_core)
                .map((x) => x.key);
            this.showAll = false;
            this.pulledIn = [];
            this.error = null;
        },

        /**
         * ตัวเลือกที่ถูกดึงมาด้วยเพราะของที่ติ๊กไว้ต้องใช้
         *
         * แอดมินกำหนดคู่ความสัมพันธ์ไว้ที่ `requires` บนตัวเลือกนั้น ๆ
         * (หน้า /admin/quotations/options) ไม่ได้ฝังไว้ในหน้านี้
         */
        applyRequires() {
            const byKey = {};
            for (const o of this.serviceOptions) byKey[o.key] = { o, bucket: 'picked' };
            for (const g of Object.values(this.catalogue.addons)) {
                for (const o of g.options) byKey[o.key] = { o, bucket: 'addons' };
            }

            // ตามต่อเป็นทอด ๆ เผื่อ A ต้องการ B แล้ว B ต้องการ C
            // `seen` กัน requires ที่วนกลับมาหากันเองจนลูปไม่จบ
            const seen = new Set();
            const queue = [...this.picked, ...this.addons];
            const pulled = [];

            while (queue.length) {
                const key = queue.shift();
                if (seen.has(key)) continue;
                seen.add(key);

                const entry = byKey[key];
                if (!entry) continue;

                for (const need of entry.o.requires || []) {
                    const target = byKey[need];
                    if (!target) continue;
                    const list = target.bucket === 'picked' ? this.picked : this.addons;
                    if (!list.includes(need)) {
                        list.push(need);
                        pulled.push(target.o.name_th);
                    }
                    queue.push(need);
                }
            }

            // อย่าทับด้วยค่าว่าง: การ push เข้า picked/addons ข้างบนจะปลุก watcher
            // อีกรอบ และรอบนั้นไม่มีอะไรให้ดึงแล้ว ถ้าเซ็ตตรง ๆ ป้ายจะหายทันที
            if (pulled.length) {
                this.pulledIn = pulled;
                clearTimeout(this._pulledTimer);
                // เป็นคำอธิบายชั่วคราว ไม่ใช่สถานะถาวรของตะกร้า
                this._pulledTimer = setTimeout(() => { this.pulledIn = []; }, 8000);
            }
        },

        pulledIn: [],
        _pulledTimer: null,

        get outcomeObj() {
            return this.catalogue.outcomes.find((o) => o.key === this.outcome) || null;
        },
        get outcomeLabel() { return this.outcomeObj ? this.outcomeObj.th : ''; },
        get service() {
            return this.outcomeObj ? (this.catalogue.services[this.outcomeObj.category] || null) : null;
        },
        get serviceOptions() { return this.service ? this.service.options : []; },
        get coreCount() { return this.picked.length; },

        get suggestedAddonGroups() {
            if (!this.outcomeObj) return [];
            return this.outcomeObj.suggested_addons
                .map((k) => this.catalogue.addons[k])
                .filter(Boolean);
        },
        get otherAddonGroups() {
            if (!this.outcomeObj) return [];
            const shown = new Set(this.outcomeObj.suggested_addons);
            return Object.values(this.catalogue.addons).filter((g) => !shown.has(g.key));
        },
        get otherOptionCount() {
            return this.otherAddonGroups.reduce((n, g) => n + g.options.length, 0);
        },

        /** ทุกบรรทัดที่จะขึ้นบนใบ เรียงแบบเดียวกับที่ฝั่งเซิร์ฟเวอร์ประกอบ */
        get lines() {
            const out = [];
            for (const o of this.serviceOptions) {
                if (this.picked.includes(o.key)) out.push(o);
            }
            for (const g of Object.values(this.catalogue.addons)) {
                for (const o of g.options) {
                    if (this.addons.includes(o.key)) out.push(o);
                }
            }
            return out;
        },

        get subtotal() { return r2(this.lines.reduce((s, l) => s + Number(l.price), 0)); },

        get discountPercent() {
            let pct = 0;
            for (const t of this.catalogue.discountTiers) {
                if (this.subtotal >= t.from) pct = t.percent;
            }
            return pct;
        },
        get discount() { return r2(this.subtotal * this.discountPercent / 100); },

        get nextTierHint() {
            const next = this.catalogue.discountTiers.find((t) => this.subtotal < t.from);
            if (!next) return this.discountPercent ? 'ได้ส่วนลดสูงสุดแล้ว' : '';
            const gap = next.from - this.subtotal;
            if (this.subtotal === 0) return 'ยอดถึง ' + this.money(next.from) + ' ลด ' + next.percent + '%';
            return 'อีก ' + this.money(gap) + ' จะได้ส่วนลด ' + next.percent + '%';
        },

        get afterDiscount() { return r2(this.subtotal - this.discount); },
        get rushFee() { return this.timeline === 'urgent' ? r2(this.afterDiscount * 0.25) : 0; },
        get beforeVat() { return r2(this.afterDiscount + this.rushFee); },

        /** ต้องให้ base + vat = total เป๊ะ เหมือน App\Support\Quotation\VatMode */
        get taxSplit() {
            const amount = this.beforeVat;
            if (this.vatMode === 'none') return { base: amount, vat: 0, total: amount };
            if (this.vatMode === 'inclusive') {
                const base = r2(amount / 1.07);
                return { base, vat: r2(amount - base), total: amount };
            }
            const total = r2(amount * 1.07);
            return { base: amount, vat: r2(total - amount), total };
        },
        get vatBase() { return this.taxSplit.base; },
        get vat() { return this.taxSplit.vat; },
        get grandTotal() { return this.taxSplit.total; },
        get netPayable() {
            return r2(this.grandTotal - (this.withholding > 0 ? r2(this.vatBase * this.withholding / 100) : 0));
        },

        get durationWeeks() {
            const days = this.lines.reduce((s, l) => s + Number(l.duration_days || 0), 0);
            if (!days) return '';
            const w = Math.ceil(days / 5);
            const shown = this.timeline === 'urgent' ? Math.max(1, Math.ceil(w / 2)) : w;
            return 'ประเมินเสร็จราว ' + shown + ' สัปดาห์';
        },

        get vatHint() {
            const m = this.vatModes.find((x) => x.key === this.vatMode);
            return m ? m.hint_th : '';
        },

        get canSubmit() {
            return this.lines.length > 0
                && this.customer.customer_name.trim() !== ''
                && this.customer.customer_email.trim() !== ''
                && this.customer.customer_phone.trim() !== '';
        },

        money(n) {
            return Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        weeks(days) {
            return '~' + Math.ceil(days / 5) + ' สัปดาห์';
        },

        /** เอกสารจริงตามที่เลือกอยู่ ยังไม่ออกเลขที่ ยังไม่เก็บอะไร */
        async previewDocument() {
            if (this.previewing || !this.lines.length) return;
            this.previewing = true;
            this.error = null;

            try {
                const res = await fetch(this.endpoints.previewDocument, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                    body: JSON.stringify(this.payload()),
                });

                if (!res.ok) {
                    this.error = 'ยังจัดหน้าตัวอย่างไม่ได้ ลองเลือกรายการให้ครบแล้วลองใหม่';
                    return;
                }

                this.previewHtml = await res.text();
            } catch (e) {
                this.error = 'ติดต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่';
            } finally {
                this.previewing = false;
            }
        },

        /** ชุดข้อมูลเดียวกันทั้งตอนดูตัวอย่างและตอนส่งจริง */
        payload() {
            return {
                ...this.customer,
                service_type: this.outcomeObj.category,
                outcome: this.outcome,
                service_options: this.picked,
                additional_options: this.addons,
                timeline: this.timeline,
                vat_mode: this.vatMode,
                withholding_pct: this.withholding,
            };
        },

        async submit() {
            if (this.sending || !this.canSubmit) return;
            this.sending = true;
            this.error = null;

            try {
                const res = await fetch(this.endpoints.submit, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify({ ...this.payload(), action_type: 'quotation' }),
                });

                const data = await res.json().catch(() => null);

                if (!res.ok) {
                    // 422 คือ validation — บอกให้ตรงช่องดีกว่าบอกว่า "ผิดพลาด"
                    this.error = data && data.errors
                        ? Object.values(data.errors).flat()[0]
                        : (data && data.message) || 'ส่งไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
                    return;
                }

                // ออกใบไปแล้ว ร่างเดิมไม่ใช่ของค้างอีกต่อไป
                this.forget();
                this.done = true;
                this.doneTitle = data.mailed ? 'ส่งใบเสนอราคาแล้ว' : 'บันทึกคำขอแล้ว';
                this.doneMessage = data.message;
                this.doneNumber = data.quote_number;
                this.doneUrl = data.document_url || null;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (e) {
                this.error = 'ติดต่อเซิร์ฟเวอร์ไม่ได้ กรุณาตรวจอินเทอร์เน็ตแล้วลองใหม่';
            } finally {
                this.sending = false;
            }
        },
    };
}
</script>
@endpush
@endsection
