@extends($customerLayout ?? 'layouts.customer')

@section('title', 'เครื่องของฉัน · GPUxMINE')
@section('page-title')<x-bi th="เครื่องของฉัน (GPUxMINE)" en="My GPUs (GPUxMINE)" />@endsection
@section('page-description')<x-bi th="แชร์การ์ดจอที่บ้านให้รับงาน AI แล้วได้ค่าตอบแทนเข้ากระเป๋า" en="Share your home GPU, take AI jobs, get paid into your wallet" />@endsection

@section('content')
@php
    // นับจากชุดเดียวกับที่แสดงข้างล่าง เพื่อให้เลขบนหัวกับรายการตรงกันเสมอ
    $pairedNodes = $nodes->whereNotNull('paired_at');
    $onlineCount = $pairedNodes->where('online', true)->count();
    $readyCount = $pairedNodes->where('online', true)->where('dispatch_status', 'eligible')->count();
    // รหัสที่ยังไม่หมดอายุ — ต้องเห็นได้แม้กดรีเฟรชแล้ว flash หายไป
    $liveCode = session('pairing_code') ?? $pending?->pairing_code;
    $liveCodeExpiresAt = session('pairing_code') && $pending === null ? null : $pending?->pairing_expires_at;
@endphp

<div class="space-y-6">

    @if (session('success'))
        <div class="rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 text-green-800 dark:text-green-200 px-4 py-3 text-sm animate-fade-in">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-800 dark:text-amber-200 px-4 py-3 text-sm animate-fade-in">{{ session('error') }}</div>
    @endif

    {{-- ══════════ HERO ══════════
         พื้นมืดเพราะภาพฟาร์มการ์ดจอต้องอ่านออก และสีเน้นเป็นอำพัน
         ให้ตรงกับสีเมนู GPUxMINE ในแถบข้าง --}}
    <div class="relative overflow-hidden rounded-2xl shadow-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 animate-fade-in">
        <x-page-art art="hero-gpuxmine" :opacity="55" :scrim="false" fade="bottom" />

        {{-- แสงเรืองสีอำพัน/ฟ้า ให้หัวดูมีชีวิตแม้ยังไม่มีเครื่องเลย --}}
        <div class="absolute inset-0 opacity-40 pointer-events-none" aria-hidden="true">
            <div class="absolute -top-10 -left-10 w-56 h-56 bg-amber-500 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute -bottom-16 right-10 w-56 h-56 bg-cyan-500 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 3s;"></div>
        </div>
        {{-- สองชั้น: ตั้งให้พื้นมืดขึ้น และทางนอนให้มืดกว่ากลาง — ไม่งั้นตัวหนังสือจะทับกับพัดลมการ์ดจอที่สว่าง --}}
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent pointer-events-none" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/95 via-slate-900/45 to-slate-900/85 pointer-events-none" aria-hidden="true"></div>

        <div class="relative px-6 sm:px-8 py-8 sm:py-10">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
                <div class="min-w-0">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 sm:h-16 sm:w-16 rounded-2xl bg-gradient-to-br from-amber-400 to-yellow-600 flex items-center justify-center shadow-lg shadow-amber-500/40 shrink-0">
                            {{-- ชิป/การ์ดจอ --}}
                            <svg class="w-8 h-8 sm:w-9 sm:h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="4" y="4" width="16" height="16" rx="2" stroke-width="1.8"/>
                                <rect x="9" y="9" width="6" height="6" rx="1" stroke-width="1.8"/>
                                <path stroke-linecap="round" stroke-width="1.8" d="M9 1.8V4m3-2.2V4m3-2.2V4M9 20v2.2M12 20v2.2M15 20v2.2M1.8 9H4m-2.2 3H4m-2.2 3H4m16 -6h2.2M20 12h2.2M20 15h2.2"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-amber-300/90 text-xs font-semibold tracking-[0.2em] uppercase mb-1">GPUxMINE</p>
                            <h2 class="text-2xl sm:text-3xl font-bold text-white leading-tight">
                                <x-bi th="การ์ดจอที่บ้าน ทำงานแทนคุณ" en="Your home GPU, earning for you" layout="stack" />
                            </h2>
                        </div>
                    </div>

                    <p class="mt-4 text-slate-300 text-sm sm:text-base max-w-xl">
                        <x-bi th="เปิดโปรแกรมทิ้งไว้ ระบบจะส่งงาน AI มาให้การ์ดจอของคุณทำตามที่เครื่องไหว แล้วโอนค่าตอบแทนเข้ากระเป๋าเงินอัตโนมัติ"
                              en="Leave the client running. We send AI jobs your card can handle and pay you straight into your wallet." />
                    </p>

                    {{-- ป้ายสถานะสด --}}
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur px-3 py-1.5 text-xs font-medium text-white border border-white/15">
                            <span class="relative flex h-2 w-2">
                                @if ($onlineCount > 0)
                                    <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75 animate-ping"></span>
                                @endif
                                <span class="relative inline-flex rounded-full h-2 w-2 {{ $onlineCount > 0 ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                            </span>
                            {{ $onlineCount }} <x-bi th="เครื่องออนไลน์" en="online" />
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 backdrop-blur px-3 py-1.5 text-xs font-medium text-white border border-white/15">
                            {{ $readyCount }} <x-bi th="พร้อมรับงาน" en="ready for jobs" />
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 backdrop-blur px-3 py-1.5 text-xs font-medium text-white border border-white/15">
                            {{ $pairedNodes->count() }} <x-bi th="เครื่องที่ลงทะเบียน" en="registered" />
                        </span>
                    </div>
                </div>

                {{-- ยอดรวม + ปุ่มหลัก --}}
                <div class="shrink-0 lg:text-right">
                    <p class="text-slate-400 text-xs mb-1"><x-bi th="รายได้เข้ากระเป๋าแล้ว" en="Paid into wallet" /></p>
                    <p class="text-3xl sm:text-4xl font-bold text-white tabular-nums">
                        ฿{{ number_format($paidSatang / 100, 2) }}
                    </p>

                    <div class="mt-5 flex flex-col sm:flex-row lg:justify-end gap-2">
                        <form method="POST" action="{{ route('gpuxmine.pair') }}" x-data="{ sending: false }" @submit="sending = true">
                            @csrf
                            <button type="submit"
                                    {{-- ต้องรวมเงื่อนไข relay เข้าไปใน x-bind ด้วย: x-bind:disabled ที่ได้ค่า false
                                         จะ "ถอด" attribute disabled ที่ Blade ใส่ไว้ทิ้ง ปุ่มจะกดได้ทั้งที่ระบบไม่พร้อม --}}
                                    x-bind:disabled="sending || @js(! $relayReady)"
                                    @disabled(! $relayReady)
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-amber-400 to-yellow-500 text-slate-900 text-sm font-bold shadow-lg shadow-amber-500/30 hover:shadow-xl hover:shadow-amber-500/40 hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:translate-y-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span x-show="! sending"><x-bi th="ขอรหัสจับคู่" en="Get pairing code" /></span>
                                <span x-show="sending" x-cloak><x-bi th="กำลังออกรหัส…" en="Issuing…" /></span>
                            </button>
                        </form>
                        <a href="{{ $downloadUrl }}" target="_blank" rel="noopener"
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-white/10 backdrop-blur border border-white/20 text-white text-sm font-medium hover:bg-white/20 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <x-bi th="ดาวน์โหลดโปรแกรม" en="Download client" />
                        </a>
                    </div>
                </div>
            </div>

            @unless ($relayReady)
                <p class="relative mt-6 text-sm text-amber-200 bg-amber-500/15 border border-amber-400/30 rounded-xl px-4 py-3">
                    <x-bi th="ระบบรับเครื่องยังไม่พร้อมใช้งาน — ผู้ดูแลยังไม่ได้ตั้งค่า relay"
                          en="Machine intake is not ready yet — the relay has not been configured by an admin." />
                </p>
            @endunless
        </div>
    </div>

    {{-- ══════════ รหัสจับคู่ ══════════
         แสดงใหญ่เพราะต้องอ่านจากจอนี้ไปพิมพ์อีกจอ และต้องอยู่ต่อแม้กดรีเฟรช --}}
    @if ($liveCode)
        <div class="relative overflow-hidden rounded-2xl border-2 border-amber-300 bg-gradient-to-br from-amber-50 to-yellow-50 p-6 sm:p-8 animate-fade-in"
             x-data="{ copied: false, copy() { const c = @js($liveCode); if (navigator.clipboard) { navigator.clipboard.writeText(c).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); }); } } }">
            <div class="absolute -right-8 -top-8 w-40 h-40 bg-amber-200/50 rounded-full blur-2xl pointer-events-none" aria-hidden="true"></div>

            <div class="relative text-center">
                <p class="inline-flex items-center gap-2 text-sm font-medium text-amber-900">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <x-bi th="พิมพ์รหัสนี้ลงในโปรแกรม GPUxMINE ที่หน้า Settings" en="Type this code into the GPUxMINE client, on its Settings screen" />
                </p>

                <div class="mt-4 flex items-center justify-center gap-3 flex-wrap">
                    <p class="font-mono text-4xl sm:text-5xl font-bold tracking-[0.2em] text-slate-900 select-all">{{ $liveCode }}</p>
                    <button type="button" @click="copy()"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white/80 border border-amber-300 text-amber-900 text-sm font-medium hover:bg-amber-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span x-show="! copied"><x-bi th="คัดลอก" en="Copy" /></span>
                        <span x-show="copied" x-cloak class="text-green-700"><x-bi th="คัดลอกแล้ว" en="Copied" /></span>
                    </button>
                </div>

                <p class="text-xs text-amber-800 mt-4">
                    @if ($liveCodeExpiresAt)
                        <x-bi th="หมดอายุ" en="Expires" /> {{ $liveCodeExpiresAt->diffForHumans() }}
                        ({{ $liveCodeExpiresAt->format('H:i') }})
                    @else
                        <x-bi th="ใช้ได้" en="Valid for" /> {{ \App\Models\GpuNode::PAIRING_TTL_MINUTES }} <x-bi th="นาที" en="minutes" />
                    @endif
                    · <x-bi th="ใช้ได้ครั้งเดียว หมดอายุแล้วกดขอใหม่ได้เลย" en="single use — just request a new one when it expires" />
                </p>
            </div>
        </div>
    @endif

    {{-- ══════════ ตัวเลขสรุป ══════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- เข้ากระเป๋าแล้ว --}}
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in">
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-400/10 to-green-600/10"></div>
            <div class="relative p-5">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400 to-green-600 flex items-center justify-center shadow-lg mb-4 transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m3 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H10a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-gray-500 dark:text-gray-400 text-xs font-medium mb-1"><x-bi th="เข้ากระเป๋าแล้ว" en="Paid" /></h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">฿{{ number_format($paidSatang / 100, 2) }}</p>
                <a href="{{ route('user.wallet.index') }}" class="mt-2 inline-flex items-center text-xs text-emerald-600 hover:text-emerald-700 font-medium group-hover:translate-x-1 transition-transform">
                    <x-bi th="ไปที่กระเป๋าเงิน" en="Open wallet" />
                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

        {{-- รอเข้ากระเป๋า --}}
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in" style="animation-delay: 0.05s;">
            <div class="absolute inset-0 bg-gradient-to-br from-amber-400/10 to-orange-600/10"></div>
            <div class="relative p-5">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-400 to-orange-600 flex items-center justify-center shadow-lg mb-4 transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-gray-500 dark:text-gray-400 text-xs font-medium mb-1"><x-bi th="รอเข้ากระเป๋า" en="Pending" /></h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">฿{{ number_format($pendingSatang / 100, 2) }}</p>
                <p class="mt-2 text-xs text-gray-400"><x-bi th="โอนเป็นรอบ" en="Paid out in batches" /></p>
            </div>
        </div>

        {{-- งานที่ทำไปแล้ว --}}
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in" style="animation-delay: 0.1s;">
            <div class="absolute inset-0 bg-gradient-to-br from-violet-400/10 to-purple-600/10"></div>
            <div class="relative p-5">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-violet-400 to-purple-600 flex items-center justify-center shadow-lg mb-4 transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-gray-500 dark:text-gray-400 text-xs font-medium mb-1"><x-bi th="งานที่ทำไปแล้ว" en="Jobs done" /></h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{{ number_format($jobsTotal) }}</p>
                <p class="mt-2 text-xs text-gray-400"><x-bi th="ทุกเครื่องรวมกัน" en="across all machines" /></p>
            </div>
        </div>

        {{-- เครื่องพร้อมรับงาน --}}
        <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in" style="animation-delay: 0.15s;">
            <div class="absolute inset-0 bg-gradient-to-br from-cyan-400/10 to-blue-600/10"></div>
            <div class="relative p-5">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center shadow-lg mb-4 transform group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                </div>
                <h3 class="text-gray-500 dark:text-gray-400 text-xs font-medium mb-1"><x-bi th="พร้อมรับงาน" en="Ready for jobs" /></h3>
                <p class="text-2xl font-bold text-gray-900 dark:text-white tabular-nums">{{ $readyCount }}<span class="text-sm font-normal text-gray-400">/{{ $pairedNodes->count() }}</span></p>
                <p class="mt-2 text-xs text-gray-400">{{ $onlineCount }} <x-bi th="ออนไลน์อยู่" en="online now" /></p>
            </div>
        </div>
    </div>

    {{-- ══════════ วิธีเริ่มใช้งาน ══════════
         ยังไม่มีเครื่อง = คนนี้เพิ่งมาถึง ต้องเห็นสี่ขั้นตอนเต็มๆ
         มีเครื่องแล้ว = พับเก็บไว้ ไม่ต้องอ่านซ้ำทุกครั้งที่เปิดหน้า --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden animate-fade-in">
        <details @class(['group']) @if ($pairedNodes->isEmpty()) open @endif>
            <summary class="flex items-center justify-between gap-3 px-5 sm:px-6 py-4 cursor-pointer list-none hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-yellow-600 flex items-center justify-center shadow shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white"><x-bi th="เริ่มใช้งานใน 4 ขั้นตอน" en="Get started in 4 steps" /></h2>
                </div>
                <svg class="w-5 h-5 text-gray-400 transition-transform group-open:rotate-180 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>

            <div class="px-5 sm:px-6 pb-6 pt-1 border-t border-gray-100 dark:border-gray-700">
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
                    @foreach ([
                        ['th' => 'ติดตั้งโปรแกรม', 'en' => 'Install the client', 'dth' => 'ลงโปรแกรม GPUxMINE บนเครื่องที่มีการ์ดจอ', 'den' => 'Install GPUxMINE on the PC that has the graphics card'],
                        ['th' => 'ขอรหัสจับคู่', 'en' => 'Get a pairing code', 'dth' => 'กดปุ่มสีอำพันด้านบน จะได้รหัส 8 ตัวอายุ 10 นาที', 'den' => 'Press the amber button above — an 8-character code valid for 10 minutes'],
                        ['th' => 'ผูกเครื่องกับบัญชี', 'en' => 'Pair the machine', 'dth' => 'พิมพ์รหัสในโปรแกรมที่หน้า Settings แล้วกดลงทะเบียน', 'den' => 'Type the code on the client\'s Settings screen and register'],
                        ['th' => 'รับงานอัตโนมัติ', 'en' => 'Jobs arrive on their own', 'dth' => 'โปรแกรมวัดความเร็วการ์ดเอง แล้วเริ่มรับงานตามที่เครื่องไหว', 'den' => 'The client benchmarks your card, then takes the jobs it can handle'],
                    ] as $i => $step)
                        <div class="relative rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-900/40 p-4 pt-5">
                            <span class="absolute -top-3 left-4 w-8 h-8 rounded-lg bg-gradient-to-br from-slate-800 to-slate-900 text-amber-300 text-sm font-bold flex items-center justify-center shadow-lg">{{ $i + 1 }}</span>
                            <h3 class="mt-1 font-semibold text-sm text-gray-900 dark:text-white"><x-bi :th="$step['th']" :en="$step['en']" layout="stack" /></h3>
                            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 leading-relaxed"><x-bi :th="$step['dth']" :en="$step['den']" layout="stack" /></p>
                        </div>
                    @endforeach
                </div>

                <p class="mt-5 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/40 border border-gray-100 dark:border-gray-700 rounded-xl px-4 py-3">
                    <x-bi th="เครื่องจะรับงานได้ก็ต่อเมื่อผ่านการประเมินความเร็วแล้วเท่านั้น โปรแกรมวัดให้เองตอนเปิดครั้งแรก และวัดใหม่เมื่อเปลี่ยนการ์ดจอหรืออัปเดตเวอร์ชัน"
                          en="A machine only receives jobs after it passes the speed assessment. The client measures it on first run, and re-measures when the card or the client version changes." />
                </p>
            </div>
        </details>
    </div>

    {{-- ══════════ รายการเครื่อง ══════════ --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <x-bi th="เครื่องที่ลงทะเบียนไว้" en="Registered machines" />
                <span class="text-xs font-medium text-gray-500 bg-gray-100 dark:bg-gray-700 dark:text-gray-300 rounded-full px-2 py-0.5">{{ $pairedNodes->count() }}</span>
            </h2>
        </div>

        @forelse ($pairedNodes as $node)
            @php
                $isReady = $node->online && $node->dispatch_status === 'eligible';
                $accent = $isReady ? 'from-emerald-400 to-green-600' : ($node->online ? 'from-amber-400 to-orange-600' : 'from-slate-400 to-slate-600');
            @endphp
            <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 animate-fade-in">
                {{-- แถบสีบอกสถานะที่ขอบซ้าย เห็นได้จากหางตาเวลามีหลายเครื่อง --}}
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b {{ $accent }}"></div>

                <div class="relative p-5 sm:p-6 pl-6 sm:pl-7">
                    <div class="flex items-start justify-between gap-4 flex-wrap">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $accent }} flex items-center justify-center shadow shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate">{{ $node->displayName() }}</h3>
                                    <div class="flex items-center gap-2 flex-wrap mt-1">
                                        <span class="text-xs px-2 py-0.5 rounded-full border {{ $node->statusTone() }}">
                                            {{ $node->statusLabel() }}
                                        </span>
                                        @if ($node->assessed)
                                            <span class="text-xs px-2 py-0.5 rounded-full bg-gradient-to-r from-violet-50 to-purple-50 text-purple-700 border border-purple-200 font-medium">
                                                {{ strtoupper($node->tier) }} · {{ number_format($node->score) }} <x-bi th="คะแนน" en="pts" />
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <dl class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400 text-xs mb-0.5"><x-bi th="การ์ดจอ" en="GPU" /></dt>
                                    <dd class="text-gray-900 dark:text-white font-medium truncate">{{ $node->gpu_name ?: '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400 text-xs mb-0.5">VRAM</dt>
                                    <dd class="text-gray-900 dark:text-white font-medium tabular-nums">
                                        {{ $node->vram_total_mb > 0 ? number_format($node->vram_total_mb / 1024, 1) . ' GB' : '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500 dark:text-gray-400 text-xs mb-0.5"><x-bi th="เห็นล่าสุด" en="Last seen" /></dt>
                                    <dd class="text-gray-900 dark:text-white font-medium">{{ $node->last_seen_at?->diffForHumans() ?? '—' }}</dd>
                                </div>
                            </dl>

                            {{-- งานที่เครื่องรับได้ มาจากการวัดจริง ไม่ใช่การประกาศเอง --}}
                            @if ($node->assessed)
                                <div class="mt-4">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5"><x-bi th="งานที่เครื่องนี้รับได้" en="Jobs this machine can take" /></p>
                                    @php
                                        $names = [
                                            'image' => ['th' => 'สร้างภาพ', 'en' => 'Images'],
                                            'video' => ['th' => 'สร้างวิดีโอ', 'en' => 'Video'],
                                            'upscale' => ['th' => 'ขยายภาพ', 'en' => 'Upscale'],
                                            'embed' => ['th' => 'ประมวลผลข้อความ', 'en' => 'Text embeddings'],
                                        ];
                                    @endphp
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse ($node->can_run ?? [] as $kind)
                                            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-lg bg-green-50 dark:bg-green-500/10 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-500/30 font-medium">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                @isset($names[$kind])
                                                    <x-bi :th="$names[$kind]['th']" :en="$names[$kind]['en']" />
                                                @else
                                                    {{ $kind }}
                                                @endisset
                                            </span>
                                        @empty
                                            <span class="text-xs text-amber-700 dark:text-amber-200 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 rounded-lg px-2.5 py-1">
                                                <x-bi th="ยังไม่มีงานประเภทใดที่เครื่องนี้รับไหว — ดูรายละเอียดในโปรแกรมหน้า Benchmark"
                                                      en="No job type fits this machine yet — see the client's Benchmark screen" />
                                            </span>
                                        @endforelse
                                    </div>
                                </div>
                            @endif

                            @if ($node->dispatch_note && $node->dispatch_status !== 'eligible')
                                <p class="mt-4 text-xs text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 rounded-lg px-3 py-2">
                                    {{ $node->dispatch_note }}
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2 shrink-0 w-full sm:w-auto">
                            <form method="POST" action="{{ route('gpuxmine.rename', $node->id) }}" class="flex gap-2">
                                @csrf
                                <input type="text" name="label" value="{{ $node->label }}" maxlength="60"
                                       placeholder="ตั้งชื่อเครื่อง / Name this machine"
                                       class="flex-1 sm:w-40 px-3 py-2 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-amber-500 focus:ring-amber-500">
                                <button type="submit" class="px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <x-bi th="บันทึก" en="Save" />
                                </button>
                            </form>
                            <form method="POST" action="{{ route('gpuxmine.forget', $node->id) }}"
                                  onsubmit="return confirm(@js('ถอนเครื่อง ' . $node->displayName() . ' ออกจากระบบ? จะไม่มีงานส่งมาที่เครื่องนี้อีก (ยอดที่ค้างจ่ายยังอยู่)'));">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full px-3 py-2 text-sm rounded-lg border border-red-200 text-red-700 hover:bg-red-50 dark:border-red-500/40 dark:text-red-300 dark:hover:bg-red-500/10 transition">
                                    <x-bi th="ถอนเครื่องออก" en="Remove machine" />
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="relative overflow-hidden rounded-2xl border-2 border-dashed border-gray-300 dark:border-gray-600 bg-gray-50/70 dark:bg-gray-800/50 p-10 text-center animate-fade-in">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600 shadow-lg mb-4">
                    <svg class="w-8 h-8 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">
                    <x-bi th="ยังไม่มีเครื่องที่ลงทะเบียน" en="No machines registered yet" />
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    <x-bi th="กด “ขอรหัสจับคู่” ด้านบนเพื่อเริ่ม — ใช้เวลาไม่ถึงนาที"
                          en="Press “Get pairing code” above to start — it takes under a minute." />
                </p>
            </div>
        @endforelse
    </div>

    {{-- ══════════ ประวัติการรับเงิน ══════════
         เจ้าของเครื่องเอาการ์ดจอของเขามาให้เราใช้ ตัวเลขว่าได้อะไรกลับไป
         ต้องอยู่ในที่ที่เขาเปิดดูได้ตลอด ไม่ใช่อยู่แค่ในโปรแกรมแล้วหายไป
         เมื่อปิดเครื่อง --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden animate-fade-in">
        <div class="flex items-center justify-between gap-3 px-5 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-400 to-green-600 flex items-center justify-center shadow shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m-6 4h6m-6 4h4M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                    </svg>
                </div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white"><x-bi th="ประวัติการรับเงิน" en="Payout history" /></h2>
            </div>
            <a href="{{ route('user.wallet.index') }}" class="text-sm text-emerald-600 hover:text-emerald-700 font-medium inline-flex items-center gap-1">
                <x-bi th="กระเป๋าเงิน" en="Wallet" />
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="p-5 sm:p-6">
            @if ($earnings->isEmpty())
                {{-- บอกตามจริงว่าทำไมยังว่าง ดีกว่าปล่อยให้เดาว่าระบบพัง --}}
                <div class="rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-dashed border-gray-300 dark:border-gray-600 p-8 text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600 mb-3">
                        <svg class="w-6 h-6 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200"><x-bi th="ยังไม่มีงานที่จ่ายเงิน" en="No paid jobs yet" /></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5 max-w-lg mx-auto leading-relaxed">
                        <x-bi th="เครื่องจะเริ่มได้รับงานเมื่อผ่านการประเมิน และมีโมเดลในระบบที่การ์ดของคุณรับไหว — รายการงานแต่ละชิ้นพร้อมยอดเงินจะขึ้นที่นี่ทันทีที่ทำเสร็จ"
                              en="Jobs start arriving once a machine passes assessment and there is a model your card can handle. Each job and its payout appears here the moment it finishes." />
                    </p>
                </div>
            @else
                <div class="overflow-x-auto -mx-5 sm:-mx-6 px-5 sm:px-6">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <th class="py-2.5 pr-3 font-medium"><x-bi th="เมื่อไร" en="When" /></th>
                                <th class="py-2.5 pr-3 font-medium"><x-bi th="งาน" en="Job" /></th>
                                <th class="py-2.5 pr-3 font-medium"><x-bi th="เครื่อง" en="Machine" /></th>
                                <th class="py-2.5 pr-3 font-medium text-right"><x-bi th="ใช้เวลา" en="Took" /></th>
                                <th class="py-2.5 pr-3 font-medium text-right"><x-bi th="ได้รับ" en="Earned" /></th>
                                <th class="py-2.5 font-medium"><x-bi th="สถานะ" en="Status" /></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($earnings as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                                    <td class="py-2.5 pr-3 text-gray-600 dark:text-gray-300 whitespace-nowrap tabular-nums">
                                        {{ optional($row->completed_at)->format('d/m/y H:i') ?? '—' }}
                                    </td>
                                    <td class="py-2.5 pr-3 text-gray-900 dark:text-white">
                                        {{ $row->kindLabel() }}
                                        @if ($row->lane === 'slow')
                                            <span class="ml-1 text-xs text-amber-600">· <x-bi th="ไม่เร่ง" en="relaxed" /></span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 pr-3 text-gray-600 dark:text-gray-300">
                                        {{ optional($row->node)->displayName() ?? $row->worker_id }}
                                    </td>
                                    <td class="py-2.5 pr-3 text-right text-gray-600 dark:text-gray-300 whitespace-nowrap tabular-nums">
                                        {{ $row->seconds > 0 ? $row->seconds . ' s' : '—' }}
                                    </td>
                                    <td class="py-2.5 pr-3 text-right font-semibold text-gray-900 dark:text-white whitespace-nowrap tabular-nums">
                                        ฿{{ number_format($row->amountBaht(), 2) }}
                                    </td>
                                    <td class="py-2.5">
                                        <span class="text-xs px-2 py-0.5 rounded-full border
                                            {{ $row->status === 'paid'
                                                ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30'
                                                : ($row->status === 'void'
                                                    ? 'bg-gray-100 text-gray-500 border-gray-200'
                                                    : 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-500/30') }}">
                                            {{ $row->statusLabel() }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-xs text-gray-400 mt-3"><x-bi th="แสดง 50 รายการล่าสุด" en="Showing the 50 most recent" /></p>
            @endif
        </div>
    </div>
</div>

@endsection
