@extends($customerLayout ?? 'layouts.customer')

@section('title', 'VPS ของฉัน')
@section('page-title')<x-bi th="VPS ของฉัน" en="My servers" />@endsection
@section('page-description')<x-bi th="เซิร์ฟเวอร์ที่คุณเช่า เปิด/ปิดเครื่อง ตั้งรหัสผ่าน และต่ออายุได้จากที่นี่" en="The servers you rent — power, passwords and renewals from here" />@endsection

@section('content')
{{--
    หมายเหตุสี: layouts/customer-premium บังคับพื้น .bg-white และพื้นอ่อน
    ให้มืดด้วย !important แต่ไม่ได้ remap สีตัวอักษร กล่องพื้นอ่อนทุกใบใน
    หน้านี้จึงต้องเขียนคู่ light/dark เอง (bg-x-50 dark:bg-x-500/10 +
    text-x-900 dark:text-x-200) ไม่งั้นตัวหนังสือจะจมหายบนพื้นดำ
    (ดู docs/artwork.md หัวข้อ member-area)
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
        <x-page-art art="hero-vps" :opacity="45" :scrim="false" fade="bottom" />
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
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <rect x="3" y="4" width="18" height="6" rx="2" stroke-width="1.8"/>
                                <rect x="3" y="14" width="18" height="6" rx="2" stroke-width="1.8"/>
                                <path stroke-linecap="round" stroke-width="1.8" d="M7 7h.01M7 17h.01"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-indigo-300/90 text-xs font-semibold tracking-[0.2em] uppercase mb-1">VPS</p>
                            <h1 class="text-2xl sm:text-3xl font-bold text-white">
                                <x-bi th="VPS ของฉัน" en="My servers" />
                            </h1>
                            <p class="text-slate-300 text-sm mt-1">
                                {{ $servers->count() }} <x-bi th="เครื่อง" en="total" />
                                @if($expiringSoon->isNotEmpty())
                                    · <span class="text-amber-300">{{ $expiringSoon->count() }} <x-bi th="ใกล้หมดอายุ" en="expiring soon" /></span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('vps.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold backdrop-blur-sm transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <x-bi th="เช่าเซิร์ฟเวอร์เพิ่ม" en="Rent another server" />
                </a>
            </div>
        </div>
    </div>

    {{-- เตือนใกล้หมดอายุ — controller กรองมาแล้วเฉพาะเครื่องที่ใช้งานอยู่ --}}
    @if($expiringSoon->isNotEmpty())
        <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-5 py-4">
            <p class="font-semibold text-amber-900 dark:text-amber-200 mb-1.5 flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M12 7v5l3 2"/></svg>
                <x-bi th="ใกล้หมดอายุ" en="Expiring soon" />
            </p>
            <ul class="text-sm text-amber-800 dark:text-amber-300/90 space-y-1">
                @foreach($expiringSoon as $s)
                    @php $d = $s->daysUntilExpiry(); @endphp
                    <li>
                        <a href="{{ route('customer.vps.show', $s->id) }}" class="font-medium underline hover:no-underline break-all">{{ $s->hostname }}</a>
                        —
                        @if($d === 0)
                            <x-bi th="หมดอายุวันนี้" en="expires today" />
                        @else
                            <x-bi th="เหลืออีก" en="in" /> {{ $d }} <x-bi th="วัน" en="days" />
                        @endif
                        @if($s->auto_renew)
                            · <x-bi th="ต่ออายุอัตโนมัติ — เตรียมยอดในกระเป๋าเงินให้พอ" en="auto-renews — keep enough in your wallet" />
                        @else
                            · <span class="font-semibold"><x-bi th="ยังไม่ได้เปิดต่ออายุอัตโนมัติ" en="auto-renew is off" /></span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- รายการ --}}
    @if($servers->isEmpty())
        <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-center py-16 px-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-700 mb-4">
                <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="6" rx="2" stroke-width="1.5"/>
                    <rect x="3" y="14" width="18" height="6" rx="2" stroke-width="1.5"/>
                    <path stroke-linecap="round" stroke-width="1.5" d="M7 7h.01M7 17h.01"/>
                </svg>
            </div>
            <p class="font-semibold text-slate-900 dark:text-white mb-1">
                <x-bi th="ยังไม่มีเซิร์ฟเวอร์" en="No servers yet" />
            </p>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-sm mx-auto">
                <x-bi th="เช่า VPS จ่ายจากกระเป๋าเงิน เครื่องพร้อมใช้ปกติไม่เกิน 15 นาที จัดการเองได้ทุกอย่างจากหน้านี้ และมีทีมงานคนไทยช่วยตลอด"
                      en="Rent a VPS from your wallet — usually running within 15 minutes, managed entirely from here, with our Thai team on hand." />
            </p>
            <a href="{{ route('vps.index') }}"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-cyan-500 text-white font-semibold shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 transition">
                <x-bi th="ดูแพ็กเกจ VPS" en="See VPS plans" />
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </a>
        </div>
    @else
        <div class="grid gap-3">
            @foreach($servers as $s)
                @php
                    $badge = $s->statusBadge();
                    $power = $s->stateLabel();
                    $days = $s->daysUntilExpiry();
                    $settingUp = in_array($s->status, [\App\Models\VpsInstance::STATUS_PENDING, \App\Models\VpsInstance::STATUS_PROVISIONING], true);
                    // สีเป็นสตริงคลาสเต็ม — Tailwind สร้างเฉพาะคลาสที่เห็นทั้งคำตอน build
                    $expiryTone = match (true) {
                        $days !== null && $days < 0 => 'text-red-700 dark:text-red-300 font-semibold',
                        $days !== null && $days <= 7 => 'text-amber-700 dark:text-amber-300 font-semibold',
                        default => 'text-slate-600 dark:text-slate-400',
                    };
                @endphp
                <a href="{{ route('customer.vps.show', $s->id) }}"
                   class="group block rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-indigo-300 dark:hover:border-indigo-500/50 hover:shadow-md transition p-5">
                    <div class="flex items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-lg font-bold text-slate-900 dark:text-white break-all">{{ $s->hostname }}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge['classes'] }}">
                                    <x-bi :th="$badge['label_th']" :en="$badge['label_en']" />
                                </span>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                {{ $s->plan_name }}
                                @if($s->period)
                                    · <x-bi :th="$s->periodLabel()" :en="$s->periodLabel('en')" />
                                @endif
                            </p>

                            <dl class="mt-3 grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-3 text-sm">
                                <div class="min-w-0">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400">IPv4</dt>
                                    <dd class="font-mono text-slate-900 dark:text-white break-all">{{ $s->ipv4 ?: '—' }}</dd>
                                </div>
                                <div class="min-w-0">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="สถานะเครื่อง" en="Power" /></dt>
                                    <dd class="text-slate-900 dark:text-white">
                                        @if($s->state && ! $settingUp)
                                            <span class="inline-flex items-center gap-1.5">
                                                <span class="w-2 h-2 rounded-full shrink-0 {{ $power['dot'] }}" aria-hidden="true"></span>
                                                <x-bi :th="$power['th']" :en="$power['en']" />
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </dd>
                                </div>
                                <div class="min-w-0">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="ระบบปฏิบัติการ" en="OS" /></dt>
                                    <dd class="text-slate-900 dark:text-white break-words">{{ $s->template_name ?: '—' }}</dd>
                                </div>
                                <div class="min-w-0">
                                    <dt class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="ศูนย์ข้อมูล" en="Data center" /></dt>
                                    <dd class="text-slate-900 dark:text-white break-words">{{ $s->data_center_name ?: '—' }}</dd>
                                </div>
                            </dl>

                            <p class="mt-3 text-sm">
                                @if($s->expires_at)
                                    <span class="{{ $expiryTone }}">
                                        <x-bi th="หมดอายุ" en="Expires" /> {{ $s->expires_at->format('j M Y') }}
                                        {{-- วงเล็บอยู่ในข้อความเอง: คอมโพเนนต์จบด้วยขึ้นบรรทัด จะกลายเป็นช่องว่างก่อน ")" --}}
                                        @if($days !== null)
                                            @if($days < 0)
                                                <x-bi th="(หมดอายุแล้ว" en="expired)" />
                                            @elseif($days === 0)
                                                <x-bi th="(หมดวันนี้" en="ends today)" />
                                            @else
                                                <x-bi :th="'(เหลือ ' . $days . ' วัน'" :en="$days . ($days === 1 ? ' day' : ' days') . ' left)'" />
                                            @endif
                                        @endif
                                    </span>
                                    @if($s->auto_renew && $s->isActive())
                                        · <span class="text-emerald-600 dark:text-emerald-400"><x-bi th="ต่ออายุอัตโนมัติ" en="auto-renews" /></span>
                                    @endif
                                @elseif($settingUp)
                                    <span class="text-amber-700 dark:text-amber-300">
                                        <x-bi th="กำลังติดตั้ง ปกติใช้เวลา 5–15 นาที" en="Setting up — usually 5–15 minutes" />
                                    </span>
                                @elseif($s->status === \App\Models\VpsInstance::STATUS_FAILED)
                                    <span class="text-red-700 dark:text-red-300">
                                        <x-bi th="ทีมงานกำลังตรวจสอบการติดตั้ง ไม่ต้องสั่งซ้ำ" en="Our team is checking the setup — no need to order again" />
                                    </span>
                                @endif
                            </p>
                        </div>
                        <svg class="w-5 h-5 mt-1 text-slate-300 dark:text-slate-600 group-hover:text-indigo-500 transition shrink-0 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
