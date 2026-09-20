@extends($publicLayout ?? 'layouts.app')

@section('title', 'จดโดเมน — ค้นหาชื่อเว็บของคุณ | XMAN Studio')
@section('meta_description', 'ค้นหาและจดทะเบียนโดเมนในชื่อของคุณเอง ราคาชัดเจน ต่ออายุอัตโนมัติ จัดการ DNS ได้เองจากหน้าเว็บ พร้อมทีมงานคนไทยดูแลตลอดอายุการใช้งาน')

@section('content')
{{--
    หน้าค้นหาโดเมน

    ช่องค้นหารับได้สามแบบ และลูกค้าไม่ต้องรู้ว่ากำลังพิมพ์แบบไหน:
      myshop              → ชื่อ ตรวจให้ทุกนามสกุลยอดนิยม
      myshop.com          → โดเมนเจาะจง ตรวจอันนั้นก่อน
      ร้านขายเสื้อผ้าออนไลน์ → คำบรรยาย ให้ระบบคิดชื่อให้

    ผลลัพธ์เรนเดอร์จากเซิร์ฟเวอร์เมื่อมี ?q= เพื่อให้ลิงก์ที่แชร์กันใช้ได้จริง
    และให้ Google เก็บหน้าได้ ส่วน Alpine ทำหน้าที่ค้นซ้ำโดยไม่โหลดหน้าใหม่
--}}
<div x-data="domainSearch(@js([
        'endpoint' => route('domains.search'),
        'initialQuery' => $query,
        'initial' => $results,
    ]))" x-init="init()">

    {{-- ══════════ HERO + ช่องค้นหา ══════════ --}}
    <section class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white overflow-hidden">
        <x-page-art art="hero-domains" :opacity="40" :scrim="false" fade="bottom" />

        <div class="absolute inset-0 opacity-40 pointer-events-none" aria-hidden="true">
            <div class="absolute -top-20 left-1/4 w-72 h-72 bg-indigo-500 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute -bottom-24 right-1/4 w-72 h-72 bg-cyan-500 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 3s;"></div>
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/30 to-transparent pointer-events-none" aria-hidden="true"></div>

        <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="text-center mb-8">
                <span class="inline-block px-4 py-1.5 bg-indigo-600/30 text-indigo-200 text-xs font-semibold rounded-full mb-5 backdrop-blur-sm border border-indigo-400/30 tracking-[0.2em] uppercase">
                    Domains
                </span>
                <h1 class="text-3xl sm:text-5xl font-bold mb-4 leading-tight">
                    <x-bi th="ชื่อเว็บของคุณ เริ่มที่นี่" en="Your name on the web starts here" layout="stack" />
                </h1>
                <p class="text-slate-300 text-base sm:text-lg max-w-2xl mx-auto">
                    <x-bi th="พิมพ์ชื่อที่อยากได้ หรือเล่าว่าธุรกิจคุณทำอะไร แล้วเราหาชื่อที่ว่างให้"
                          en="Type a name you want, or describe your business and we'll find one that's free" />
                </p>
            </div>

            {{-- ช่องค้นหา --}}
            <form @submit.prevent="run()" class="relative max-w-3xl mx-auto">
                <div class="relative flex flex-col sm:flex-row gap-3 p-2 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 shadow-2xl">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                            </svg>
                        </div>
                        <input type="search" x-model="query" @input.debounce.600ms="run()"
                               autocomplete="off" autocapitalize="off" spellcheck="false"
                               maxlength="120"
                               placeholder="myshop.com หรือ ร้านกาแฟย่านอารีย์"
                               aria-label="ค้นหาโดเมน / Search domains"
                               class="w-full pl-12 pr-4 py-4 bg-transparent text-white placeholder-slate-400 border-0 focus:ring-0 text-base sm:text-lg">
                    </div>
                    <button type="submit" :disabled="loading || query.trim().length === 0"
                            class="px-8 py-4 rounded-xl bg-gradient-to-r from-indigo-500 to-cyan-500 text-white font-semibold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 hover:scale-[1.02] active:scale-[0.99] transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100 whitespace-nowrap">
                        <span x-show="!loading">ค้นหา / Search</span>
                        <span x-show="loading" x-cloak class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            กำลังค้นหา
                        </span>
                    </button>
                </div>

                {{-- ราคาเรียกน้ำย่อย แสดงก่อนที่ใครจะค้นหา --}}
                @if($popular->isNotEmpty())
                    {{-- แต่ละราคาเป็นชิปของตัวเอง ไม่ใช่ข้อความไหลต่อกัน — ตอนวางเรียง
                         แบบ inline ตัว "฿" ของราคาหนึ่งไปติดกับจุดนำหน้าของนามสกุล
                         ถัดไป กลายเป็น "590 ฿.net" ซึ่งอ่านผิดได้ทันที --}}
                    <div class="mt-5 flex flex-wrap items-center justify-center gap-2" x-show="!hasSearched" x-cloak>
                        @foreach($popular as $p)
                            <span class="inline-flex items-baseline gap-1.5 px-3 py-1.5 rounded-full bg-white/10 border border-white/10 backdrop-blur-sm text-sm">
                                <span class="font-semibold text-white">.{{ $p['tld'] }}</span>
                                <span class="text-cyan-300">{{ $p['price'] }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif
            </form>
        </div>
    </section>

    {{-- ══════════ ผลการค้นหา ══════════ --}}
    <section class="bg-gray-50 dark:bg-slate-900 min-h-[40vh]">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">

            {{-- ข้อผิดพลาด: บอกว่าระบบมีปัญหา ไม่บอกว่าเป็นของใคร --}}
            <template x-if="error">
                <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-900 dark:text-amber-200 px-5 py-4 mb-6 flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <span x-text="error" class="text-sm"></span>
                </div>
            </template>

            {{-- โดเมนที่พิมพ์มาตรง ๆ — เด่นกว่าใบอื่น เพราะคือสิ่งที่เขาอยากได้จริง --}}
            <template x-if="exact">
                <div class="mb-8">
                    <p class="text-xs font-semibold tracking-[0.15em] uppercase text-slate-500 dark:text-slate-400 mb-3">
                        <x-bi th="ชื่อที่คุณค้นหา" en="What you searched for" />
                    </p>
                    <div class="rounded-2xl border-2 p-5 sm:p-6 shadow-lg transition"
                         :class="exact.available
                            ? 'bg-white dark:bg-slate-800 border-emerald-400 dark:border-emerald-500/60 shadow-emerald-500/10'
                            : 'bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700'">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <template x-if="exact.available">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-xs font-semibold">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-8 8a1 1 0 01-1.4 0l-4-4a1 1 0 011.4-1.4L8 12.6l7.3-7.3a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>
                                            ว่าง / Available
                                        </span>
                                    </template>
                                    <template x-if="!exact.available">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold">
                                            ถูกจดแล้ว / Taken
                                        </span>
                                    </template>
                                </div>
                                <p class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white break-all" x-text="exact.domain"></p>
                            </div>
                            <template x-if="exact.available">
                                <div class="flex items-center gap-4 shrink-0">
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-slate-900 dark:text-white" x-text="exact.price_display"></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            <x-bi th="ต่อปี" en="per year" />
                                        </p>
                                    </div>
                                    <button type="button" @click="choose(exact)"
                                            class="px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-semibold shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:scale-[1.02] active:scale-[0.99] transition">
                                        เลือก / Choose
                                    </button>
                                </div>
                            </template>
                        </div>
                        {{-- ราคาต่ออายุที่ต่างจากปีแรก ต้องบอกก่อนซื้อ ไม่ใช่ตอนต่ออายุ --}}
                        <template x-if="exact.available && exact.renewal_is_dearer">
                            <p class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400">
                                <x-bi th="ต่ออายุปีถัดไป" en="Renews at" />
                                <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="exact.renew_price_display"></span>
                                <x-bi th="ต่อปี" en="per year" />
                            </p>
                        </template>
                    </div>
                </div>
            </template>

            {{-- รายการที่ว่าง --}}
            <template x-if="results.length > 0">
                <div>
                    <div class="flex items-baseline justify-between mb-3">
                        <p class="text-xs font-semibold tracking-[0.15em] uppercase text-slate-500 dark:text-slate-400">
                            <span x-show="mode === 'description'"><x-bi th="ชื่อที่เราคิดให้" en="Names we suggest" /></span>
                            <span x-show="mode !== 'description'"><x-bi th="ชื่ออื่นที่ยังว่าง" en="Other names available" /></span>
                        </p>
                        <p class="text-xs text-slate-400" x-text="results.length + ' รายการ'"></p>
                    </div>

                    <div class="grid gap-3">
                        <template x-for="row in results" :key="row.domain">
                            <div class="group rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-500/50 hover:shadow-md transition p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white break-all">
                                            <span x-text="row.label"></span><span class="text-indigo-600 dark:text-indigo-400" x-text="'.' + row.tld"></span>
                                        </p>
                                        <template x-if="row.renewal_is_dearer">
                                            <p class="text-xs text-slate-400 mt-0.5">
                                                <x-bi th="ต่ออายุ" en="Renews" /> <span x-text="row.renew_price_display"></span>/<x-bi th="ปี" en="yr" />
                                            </p>
                                        </template>
                                    </div>
                                    <div class="flex items-center justify-between sm:justify-end gap-4 shrink-0">
                                        <div class="text-right">
                                            <p class="text-lg font-bold text-slate-900 dark:text-white" x-text="row.price_display"></p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400"><x-bi th="ต่อปี" en="per year" /></p>
                                        </div>
                                        <button type="button" @click="choose(row)"
                                                class="px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-sm hover:shadow-md transition">
                                            เลือก / Choose
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- ค้นแล้วไม่เจออะไรเลย --}}
            <template x-if="hasSearched && !loading && !error && results.length === 0 && !exact">
                <div class="text-center py-14">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                        </svg>
                    </div>
                    <p class="text-slate-700 dark:text-slate-200 font-semibold mb-1">
                        <x-bi th="ยังไม่เจอชื่อที่ว่าง" en="No available names yet" />
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                        <x-bi th="ลองพิมพ์สั้นลง หรือเล่าว่าธุรกิจคุณทำอะไรเป็นภาษาไทยก็ได้ เราจะคิดชื่อให้"
                              en="Try something shorter, or just describe your business — we'll think of names for you" />
                    </p>
                </div>
            </template>

            {{-- ยังไม่ได้ค้น: อธิบายว่าซื้อกับเราแล้วได้อะไร --}}
            <div x-show="!hasSearched" x-cloak>
                <div class="grid sm:grid-cols-3 gap-5 mb-10">
                    @php
                        $selling = [
                            ['icon' => 'shield', 'th' => 'จดในชื่อคุณ', 'en' => 'Registered to you',
                             'body_th' => 'ผู้ถือครองคือชื่อคุณ ไม่ใช่ชื่อเรา ย้ายออกเมื่อไหร่ก็ได้ ขอรหัสย้ายได้ตลอด',
                             'body_en' => "You are the registrant, not us. Move it out whenever you like — the transfer code is yours on request."],
                            ['icon' => 'dns', 'th' => 'ตั้ง DNS เองได้', 'en' => 'Full DNS control',
                             'body_th' => 'เพิ่ม A, CNAME, MX, TXT ได้เองจากหน้าเว็บ ไม่ต้องเปิดตั๋ว ไม่ต้องรอ',
                             'body_en' => 'Add A, CNAME, MX and TXT records yourself from your dashboard. No tickets, no waiting.'],
                            ['icon' => 'support', 'th' => 'ทีมงานคนไทยดูแล', 'en' => 'Thai team on call',
                             'body_th' => 'ตั้งค่าไม่เป็น ทักมาได้ เราตั้งให้ถึงใช้งานได้จริง ตลอดอายุโดเมน',
                             'body_en' => "Stuck on a record? Message us and we'll set it up for you, for as long as you hold the domain."],
                        ];
                    @endphp
                    @foreach($selling as $s)
                        <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5">
                            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center mb-3 shadow-lg shadow-indigo-500/20">
                                @if($s['icon'] === 'shield')
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3l7 3v5c0 4.5-3 8.3-7 10-4-1.7-7-5.5-7-10V6l7-3z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.5 12l1.8 1.8 3.4-3.6"/></svg>
                                @elseif($s['icon'] === 'dns')
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="6" rx="2" stroke-width="1.8"/><rect x="3" y="14" width="18" height="6" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M7 7h.01M7 17h.01"/></svg>
                                @else
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 13v4a3 3 0 01-3 3H9a3 3 0 01-3-3v-4M12 3a6 6 0 016 6v4H6V9a6 6 0 016-6z"/></svg>
                                @endif
                            </div>
                            <p class="font-semibold text-slate-900 dark:text-white mb-1">
                                <x-bi :th="$s['th']" :en="$s['en']" />
                            </p>
                            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                                <x-bi :th="$s['body_th']" :en="$s['body_en']" layout="stack" />
                            </p>
                        </div>
                    @endforeach
                </div>

                @if($featured->isNotEmpty())
                    <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-6">
                        <div class="flex items-baseline justify-between mb-4">
                            <p class="font-semibold text-slate-900 dark:text-white">
                                <x-bi th="นามสกุลยอดนิยม" en="Popular extensions" />
                            </p>
                            <a href="{{ route('domains.pricing') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                                <x-bi th="ดูราคาทั้งหมด" en="All prices" /> →
                            </a>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach($featured as $t)
                                <button type="button" @click="query = query.trim() || ''; suggestTld(@js($t->tld))"
                                        class="text-left rounded-xl border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-500/50 p-3 transition">
                                    <p class="font-bold text-slate-900 dark:text-white">.{{ $t->tld }}</p>
                                    <p class="text-sm text-indigo-600 dark:text-indigo-400">{{ \App\Support\DomainPricing::format($t->registerPriceThb()) }}</p>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
function domainSearch(config) {
    return {
        endpoint: config.endpoint,
        query: config.initialQuery || '',
        loading: false,
        error: null,
        mode: 'domain',
        exact: null,
        results: [],
        hasSearched: false,
        // กันผลลัพธ์ของคำค้นเก่ามาทับของใหม่ เมื่อคำสั้นตอบช้ากว่าคำยาว
        seq: 0,

        init() {
            const initial = config.initial;
            if (initial) {
                this.apply(initial);
                this.hasSearched = true;
            }
        },

        suggestTld(tld) {
            const base = (this.query || '').trim().split('.')[0];
            if (!base) {
                // ยังไม่ได้พิมพ์อะไร — พาไปที่ช่องค้นหาแทนที่จะค้นคำว่าง
                document.querySelector('input[type=search]')?.focus();
                return;
            }
            this.query = base + '.' + tld;
            this.run();
        },

        async run() {
            const q = (this.query || '').trim();

            if (q.length === 0) {
                this.results = [];
                this.exact = null;
                this.error = null;
                this.hasSearched = false;
                return;
            }

            const mySeq = ++this.seq;
            this.loading = true;
            this.error = null;

            try {
                const url = this.endpoint + '?q=' + encodeURIComponent(q);
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                // คำค้นใหม่แซงไปแล้ว ทิ้งคำตอบนี้
                if (mySeq !== this.seq) return;

                if (res.status === 429) {
                    this.error = 'ค้นหาถี่เกินไป กรุณารอสักครู่แล้วลองใหม่';
                    this.results = [];
                    this.exact = null;
                    return;
                }

                if (!res.ok) {
                    this.error = 'ระบบค้นหาโดเมนไม่พร้อมใช้งานชั่วคราว กรุณาลองใหม่อีกครั้ง';
                    return;
                }

                this.apply(await res.json());
            } catch (e) {
                if (mySeq !== this.seq) return;
                this.error = 'เชื่อมต่อไม่สำเร็จ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่';
            } finally {
                if (mySeq === this.seq) {
                    this.loading = false;
                    this.hasSearched = true;
                    this.syncUrl(q);
                }
            }
        },

        apply(payload) {
            this.mode = payload.mode || 'domain';
            this.exact = payload.exact || null;
            this.results = Array.isArray(payload.results) ? payload.results : [];
            this.error = payload.error || null;
        },

        // ให้ปุ่มย้อนกลับและการแชร์ลิงก์ทำงาน โดยไม่เพิ่มประวัติทุกตัวอักษร
        syncUrl(q) {
            try {
                const url = new URL(window.location.href);
                url.searchParams.set('q', q);
                window.history.replaceState({}, '', url);
            } catch (e) { /* ไม่สำคัญพอจะให้พัง */ }
        },

        choose(row) {
            if (!row || !row.available) return;
            window.location.href = '{{ url('/domains/register') }}/' + encodeURIComponent(row.domain);
        },
    };
}
</script>
@endpush
