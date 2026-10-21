@extends($customerLayout ?? 'layouts.customer')

@section('title', 'โดเมนของฉัน · XMAN Studio')
@section('page-title')<x-bi th="โดเมนของฉัน" en="My domains" />@endsection
@section('page-description')<x-bi th="โดเมนที่คุณถือครอง ตั้งค่า DNS และต่ออายุได้จากที่นี่" en="The domains you hold — set DNS and renew from here" />@endsection

@section('content')
{{--
    หมายเหตุสี: layouts/customer-premium บังคับพื้น .bg-white และพื้นอ่อน
    ให้มืดด้วย !important แต่ไม่ได้ remap สีตัวอักษร กล่องพื้นอ่อนทุกใบใน
    หน้านี้จึงต้องเขียนคู่ light/dark เอง (bg-x-50 dark:bg-x-500/10 +
    text-x-900 dark:text-x-200) ไม่งั้นตัวหนังสือจะจมหายบนพื้นดำ
--}}
<div class="space-y-6">

    @if (session('success'))
        <div class="rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 text-green-900 dark:text-green-200 px-4 py-3 text-sm animate-fade-in">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-4 py-3 text-sm animate-fade-in">{{ session('error') }}</div>
    @endif

    {{-- ══════════ HERO ══════════ --}}
    <div class="relative overflow-hidden rounded-2xl shadow-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 animate-fade-in">
        <x-page-art art="hero-domains" :opacity="45" :scrim="false" fade="bottom" />
        <div class="absolute inset-0 opacity-40 pointer-events-none" aria-hidden="true">
            <div class="absolute -top-10 -left-10 w-56 h-56 bg-indigo-500 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute -bottom-16 right-10 w-56 h-56 bg-cyan-500 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 3s;"></div>
        </div>
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent pointer-events-none" aria-hidden="true"></div>

        <div class="relative px-6 sm:px-8 py-8">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5">
                <div class="min-w-0">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-cyan-500 flex items-center justify-center shadow-lg shadow-indigo-500/40 shrink-0">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9" stroke-width="1.8"/>
                                <path stroke-width="1.8" d="M3.6 9h16.8M3.6 15h16.8"/>
                                <path stroke-width="1.8" d="M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-indigo-300/90 text-xs font-semibold tracking-[0.2em] uppercase mb-1">Domains</p>
                            <h1 class="text-2xl sm:text-3xl font-bold text-white">
                                <x-bi th="โดเมนของฉัน" en="My domains" />
                            </h1>
                            <p class="text-slate-300 text-sm mt-1">
                                {{ $domains->count() }} <x-bi th="รายการ" en="total" />
                                @if($expiringSoon->isNotEmpty())
                                    · <span class="text-amber-300">{{ $expiringSoon->count() }} <x-bi th="ใกล้หมดอายุ" en="expiring soon" /></span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('domains.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold backdrop-blur-sm transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <x-bi th="จดโดเมนใหม่" en="Register a domain" />
                </a>
            </div>
        </div>
    </div>

    {{-- เตือนใกล้หมดอายุ --}}
    @if($expiringSoon->isNotEmpty())
        <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-5 py-4">
            <p class="font-semibold text-amber-900 dark:text-amber-200 mb-1.5 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M12 7v5l3 2"/></svg>
                <x-bi th="ใกล้หมดอายุ" en="Expiring soon" />
            </p>
            <ul class="text-sm text-amber-800 dark:text-amber-300/90 space-y-1">
                @foreach($expiringSoon as $d)
                    <li>
                        <a href="{{ route('customer.domains.show', $d->id) }}" class="font-medium underline hover:no-underline">{{ $d->domain }}</a>
                        — <x-bi th="เหลืออีก" en="in" /> {{ $d->daysUntilExpiry() }} <x-bi th="วัน" en="days" />
                        @unless($d->auto_renew)
                            · <span class="font-semibold"><x-bi th="ยังไม่ได้เปิดต่ออายุอัตโนมัติ" en="auto-renew is off" /></span>
                        @endunless
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- รายการ --}}
    @if($domains->isEmpty())
        <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center py-16 px-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-700 mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9" stroke-width="1.5"/><path stroke-width="1.5" d="M3.6 9h16.8M3.6 15h16.8M12 3a15 15 0 010 18M12 3a15 15 0 000 18"/>
                </svg>
            </div>
            <p class="font-semibold text-slate-900 dark:text-white mb-1">
                <x-bi th="ยังไม่มีโดเมน" en="No domains yet" />
            </p>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-sm mx-auto">
                <x-bi th="จดโดเมนในชื่อของคุณเอง ตั้งค่า DNS ได้เองจากหน้านี้ และมีทีมงานคนไทยช่วยตลอด"
                      en="Register a domain in your own name, manage its DNS from here, with our Thai team on hand." />
            </p>
            <a href="{{ route('domains.index') }}"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-cyan-500 text-white font-semibold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 transition">
                <x-bi th="ค้นหาโดเมน" en="Search for a domain" />
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    @else
        <div class="grid gap-3">
            @foreach($domains as $d)
                @php $badge = $d->statusBadge(); @endphp
                <a href="{{ route('customer.domains.show', $d->id) }}"
                   class="group rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-500/50 hover:shadow-md transition p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-lg font-bold text-slate-900 dark:text-white break-all">{{ $d->domain }}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge['classes'] }}">
                                    <x-bi :th="$badge['label_th']" :en="$badge['label_en']" />
                                </span>
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                @if($d->expires_at)
                                    <x-bi th="หมดอายุ" en="Expires" /> {{ $d->expires_at->format('j M Y') }}
                                    @if($d->auto_renew)
                                        · <span class="text-emerald-600 dark:text-emerald-400"><x-bi th="ต่ออายุอัตโนมัติ" en="auto-renews" /></span>
                                    @endif
                                @elseif($d->status === \App\Models\DomainRegistration::STATUS_REGISTERING)
                                    <x-bi th="กำลังดำเนินการ ปกติไม่เกิน 15 นาที" en="In progress — usually under 15 minutes" />
                                @endif
                            </p>
                        </div>
                        <svg class="w-5 h-5 text-slate-300 dark:text-slate-600 group-hover:text-indigo-500 transition shrink-0 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
