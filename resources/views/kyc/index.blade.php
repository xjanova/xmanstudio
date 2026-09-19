@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ยืนยันตัวตน')
@section('page-title')<x-bi th="ยืนยันตัวตน" en="Identity verification" />@endsection
@section('page-description')<x-bi th="จำเป็นสำหรับการรับเงิน ถอนเงิน และการเข้าถึงหมวดเนื้อหาสำหรับผู้ใหญ่" en="Required to receive money, withdraw, and unlock adult content" />@endsection

@section('content')
@php
    $status = $kyc->status ?? 'not_submitted';
    // ขั้นที่เดินมาถึงแล้ว ใช้ระบายสีรางความคืบหน้าให้ตรงกับสถานะจริง
    $stage = match ($status) {
        'approved' => 3,
        'pending' => 2,
        default => 1,
    };
    $isRejected = $status === 'rejected';
    $canSubmit = ! $kyc || $kyc->canResubmit();
@endphp

<div class="space-y-6">

    @if (session('success'))
        <div class="rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 text-green-800 dark:text-green-200 px-4 py-3 text-sm animate-fade-in">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-800 dark:text-amber-200 px-4 py-3 text-sm animate-fade-in">{{ session('error') }}</div>
    @endif

    {{-- ══════════ HERO ══════════
         ภาพเป็นโล่/ลายนิ้วมือแบบนามธรรม ไม่ใช่รูปเอกสารจริง — หน้านี้ขอ
         เลขบัตรประชาชน ภาพที่ดูเหมือนบัตรจริงจะทำให้เข้าใจผิดว่าเป็นตัวอย่าง --}}
    <div class="relative overflow-hidden rounded-2xl shadow-2xl bg-gradient-to-br from-slate-900 via-emerald-950 to-slate-900 animate-fade-in">
        <x-page-art art="hero-kyc" :opacity="50" :scrim="false" fade="bottom" />

        <div class="absolute inset-0 opacity-40 pointer-events-none" aria-hidden="true">
            <div class="absolute -top-12 -right-8 w-56 h-56 bg-emerald-500 rounded-full mix-blend-screen filter blur-3xl animate-blob"></div>
            <div class="absolute -bottom-16 left-10 w-56 h-56 bg-teal-400 rounded-full mix-blend-screen filter blur-3xl animate-blob" style="animation-delay: 3s;"></div>
        </div>
        {{-- สองชั้น: ตั้งให้พื้นมืดขึ้น และทางนอนให้มืดกว่ากลาง — ไม่งั้นโล่กับแม่กุญแจจะทับตัวหนังสือ --}}
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent pointer-events-none" aria-hidden="true"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/95 via-slate-900/45 to-slate-900/85 pointer-events-none" aria-hidden="true"></div>

        <div class="relative px-6 sm:px-8 py-8 sm:py-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="min-w-0">
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 sm:h-16 sm:w-16 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center shadow-lg shadow-emerald-500/40 shrink-0">
                            {{-- โล่ + ติ๊กถูก --}}
                            <svg class="w-8 h-8 sm:w-9 sm:h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2.5l7.5 3v6c0 4.6-3.1 8.6-7.5 10-4.4-1.4-7.5-5.4-7.5-10v-6l7.5-3z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2.2 2.2L15.5 10"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-emerald-300/90 text-xs font-semibold tracking-[0.2em] uppercase mb-1">XMAN ID · KYC</p>
                            <h2 class="text-2xl sm:text-3xl font-bold text-white leading-tight">
                                <x-bi th="ยืนยันว่าเงินเข้าบัญชีของคุณจริง" en="Prove the payout account is really yours" layout="stack" />
                            </h2>
                        </div>
                    </div>

                    <p class="mt-4 text-slate-300 text-sm sm:text-base max-w-xl">
                        <x-bi th="ยืนยันครั้งเดียว เปิดสิทธิ์รับเงิน ถอนเงิน และหมวดเนื้อหาสำหรับผู้ใหญ่ ทีมงานตรวจภายใน 1–3 วันทำการ"
                              en="Verify once to unlock payouts, withdrawals and the adult content tier. Reviewed by our team within 1–3 business days." />
                    </p>

                    {{-- ป้ายสถานะ --}}
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        @php
                            $pillTone = match ($status) {
                                'approved' => 'bg-emerald-400/20 border-emerald-300/40 text-emerald-100',
                                'pending' => 'bg-sky-400/20 border-sky-300/40 text-sky-100',
                                'rejected' => 'bg-red-400/20 border-red-300/40 text-red-100',
                                default => 'bg-white/10 border-white/15 text-white',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-2 rounded-full backdrop-blur px-3 py-1.5 text-xs font-semibold border {{ $pillTone }}">
                            @if ($status === 'approved')
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            @elseif ($status === 'pending')
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full rounded-full bg-sky-300 opacity-75 animate-ping"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-sky-300"></span>
                                </span>
                            @endif
                            {{ $kyc?->statusLabel() ?? 'ยังไม่ได้ส่ง' }}
                        </span>
                        @if ($kyc?->submitted_at)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 backdrop-blur px-3 py-1.5 text-xs font-medium text-white border border-white/15">
                                <x-bi th="ส่งเมื่อ" en="Submitted" /> {{ $kyc->submitted_at->format('d/m/Y H:i') }}
                            </span>
                        @endif
                        @if (($kyc?->attempts ?? 0) > 1)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 backdrop-blur px-3 py-1.5 text-xs font-medium text-white border border-white/15">
                                <x-bi th="ครั้งที่" en="Attempt" /> {{ $kyc->attempts }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- รางความคืบหน้า 3 ขั้น --}}
                <div class="shrink-0 w-full lg:w-72 rounded-2xl bg-white/5 backdrop-blur border border-white/10 p-4">
                    @foreach ([
                        ['th' => 'ส่งเอกสาร', 'en' => 'Submit documents'],
                        ['th' => 'ทีมงานตรวจสอบ', 'en' => 'Our team reviews'],
                        ['th' => 'เปิดสิทธิ์ใช้งาน', 'en' => 'Access unlocked'],
                    ] as $i => $step)
                        @php
                            $n = $i + 1;
                            $done = $n < $stage || ($n === 3 && $stage === 3);
                            $current = $n === $stage && ! $done;
                        @endphp
                        <div class="flex items-start gap-3 {{ $i < 2 ? 'pb-3' : '' }}">
                            <div class="relative flex flex-col items-center shrink-0">
                                <span @class([
                                    'w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold',
                                    'bg-emerald-400 text-slate-900' => $done,
                                    // ไม่ใช้ bg-white — customer-premium บังคับ .bg-white เป็นน้ำเงินเข้ม เลข "ขั้นที่อยู่" จะหายไปทั้งดวง
                                    'bg-emerald-300 text-slate-900 ring-4 ring-emerald-300/25' => $current,
                                    'bg-white/15 text-white/60' => ! $done && ! $current,
                                ])>
                                    @if ($done)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3.5" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        {{ $n }}
                                    @endif
                                </span>
                                @if ($i < 2)
                                    <span class="w-px flex-1 min-h-[14px] mt-1 {{ $done ? 'bg-emerald-400/60' : 'bg-white/15' }}"></span>
                                @endif
                            </div>
                            <div class="min-w-0 -mt-0.5">
                                <p class="text-sm font-medium {{ $done || $current ? 'text-white' : 'text-white/50' }}">
                                    <x-bi :th="$step['th']" :en="$step['en']" layout="stack" />
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════ ผลการตรวจ ══════════ --}}
    @if ($status === 'approved')
        <div class="relative overflow-hidden rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/30 p-5 sm:p-6 animate-fade-in">
            <div class="absolute -right-8 -top-8 w-40 h-40 bg-emerald-200/40 rounded-full blur-2xl pointer-events-none" aria-hidden="true"></div>
            <div class="relative flex items-start gap-4 flex-wrap">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center shadow-lg shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="font-semibold text-emerald-900 dark:text-emerald-200"><x-bi th="ยืนยันตัวตนเรียบร้อยแล้ว" en="Identity verified" /></h3>
                    <p class="text-sm text-emerald-800/80 dark:text-emerald-200/80 mt-1">
                        <x-bi th="บัญชีนี้รับเงินและถอนเงินได้แล้ว และเข้าถึงหมวดเนื้อหาสำหรับผู้ใหญ่บน ai.xman4289.com ได้"
                              en="This account can receive and withdraw money, and access the adult content tier on ai.xman4289.com." />
                    </p>
                    <div class="mt-4 inline-flex items-center gap-3 rounded-xl bg-white/70 dark:bg-white/5 border border-emerald-200 dark:border-emerald-500/30 px-4 py-3">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/>
                        </svg>
                        <div class="text-sm">
                            <p class="text-xs text-gray-500"><x-bi th="บัญชีรับเงิน" en="Payout account" /></p>
                            <p class="font-medium text-gray-900 tabular-nums">
                                {{ $kyc->bank_code }} ···{{ substr($kyc->bank_account_number ?? '', -4) }}
                                <span class="font-normal text-gray-600">({{ $kyc->bank_account_name }})</span>
                            </p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('user.wallet.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-sm font-medium shadow-lg shadow-emerald-500/30 hover:shadow-xl transition-all hover:-translate-y-0.5">
                    <x-bi th="ไปที่กระเป๋าเงิน" en="Open wallet" />
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    @elseif ($status === 'pending')
        <div class="rounded-2xl bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/30 p-5 sm:p-6 animate-fade-in">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center shadow-lg shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="font-semibold text-sky-900 dark:text-sky-200"><x-bi th="เอกสารอยู่ในคิวตรวจสอบ" en="Your documents are in the review queue" /></h3>
                    <p class="text-sm text-sky-800/80 dark:text-sky-200/80 mt-1">
                        <x-bi th="ทีมงานตรวจภายใน 1–3 วันทำการ ระหว่างนี้ส่งเอกสารซ้ำไม่ได้ เพื่อไม่ให้คิวตรวจเต็มไปด้วยใบเดิม — ผลออกแล้วสถานะบนหน้านี้จะเปลี่ยนเอง"
                              en="We review within 1–3 business days. Re-submitting is blocked meanwhile so the queue does not fill with duplicates — this page updates itself once a decision is made." />
                    </p>
                </div>
            </div>
        </div>
    @elseif ($isRejected && $kyc->rejection_reason)
        <div class="rounded-2xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 p-5 sm:p-6 animate-fade-in">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-rose-600 flex items-center justify-center shadow-lg shrink-0">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="font-semibold text-red-900 dark:text-red-200"><x-bi th="เหตุผลที่ไม่ผ่าน" en="Why it was rejected" /></h3>
                    <p class="text-sm text-red-800 dark:text-red-200 mt-1">{{ $kyc->rejection_reason }}</p>
                    <p class="text-xs text-red-700/80 dark:text-red-300/80 mt-2">
                        <x-bi th="แก้ตามนี้แล้วส่งใหม่ได้เลยในฟอร์มด้านล่าง" en="Fix this and re-submit using the form below." />
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ สิ่งที่การยืนยันตัวตนปลดล็อก ══════════ --}}
    <div class="grid sm:grid-cols-3 gap-4">
        @foreach ([
            ['th' => 'รับเงินค่าเช่าการ์ดจอ', 'en' => 'GPUxMINE payouts', 'dth' => 'ค่าตอบแทนจาก GPUxMINE เข้ากระเป๋าได้', 'den' => 'Earnings from sharing your GPU land in your wallet', 'grad' => 'from-amber-400 to-yellow-600', 'icon' => 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z'],
            ['th' => 'ถอนเงินเข้าบัญชีธนาคาร', 'en' => 'Bank withdrawals', 'dth' => 'โอนออกเข้าบัญชีที่ยืนยันชื่อไว้แล้ว', 'den' => 'Transfer out to the bank account you verified', 'grad' => 'from-emerald-400 to-green-600', 'icon' => 'M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z'],
            ['th' => 'หมวดเนื้อหาสำหรับผู้ใหญ่', 'en' => 'Adult content tier', 'dth' => 'เปิดหมวด 18+ บน ai.xman4289.com', 'den' => 'Unlocks the 18+ tier on ai.xman4289.com', 'grad' => 'from-violet-400 to-purple-600', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
        ] as $perk)
            <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 animate-fade-in">
                <div class="relative p-5">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br {{ $perk['grad'] }} flex items-center justify-center shadow-lg mb-4 transform group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $perk['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-sm text-gray-900 dark:text-white"><x-bi :th="$perk['th']" :en="$perk['en']" layout="stack" /></h3>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 leading-relaxed"><x-bi :th="$perk['dth']" :en="$perk['den']" layout="stack" /></p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ══════════ ฟอร์ม ══════════ --}}
    @if ($canSubmit)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden animate-fade-in">
            <div class="px-5 sm:px-8 py-5 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <x-bi th="ส่งเอกสารยืนยันตัวตน" en="Submit your verification documents" />
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    <x-bi th="ข้อมูลนี้ใช้ยืนยันว่าบัญชีธนาคารปลายทางเป็นของคุณจริง และใช้เปิดสิทธิ์ตามข้อกำหนดการใช้งาน"
                          en="We use this to confirm the destination bank account is yours, and to unlock access under our terms of use." />
                </p>
            </div>

            <div class="p-5 sm:p-8">
                @if ($errors->any())
                    <div class="mb-6 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-4 py-3 text-sm text-red-800 dark:text-red-200">
                        <p class="font-medium mb-1.5"><x-bi th="กรุณาแก้ไขรายการต่อไปนี้" en="Please fix the following" /></p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('kyc.store') }}" enctype="multipart/form-data" class="space-y-8"
                      x-data="{ sending: false }" @submit="sending = true">
                    @csrf

                    {{-- ── 1. ข้อมูลตามบัตรประชาชน ── --}}
                    <section>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-800 to-slate-900 text-emerald-300 text-sm font-bold flex items-center justify-center shadow shrink-0">1</span>
                            <h3 class="font-semibold text-gray-900 dark:text-white"><x-bi th="ข้อมูลตามบัตรประชาชน" en="Details from your ID card" /></h3>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="id_card_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    <x-bi th="เลขบัตรประชาชน (13 หลัก)" en="Thai ID number (13 digits)" />
                                </label>
                                <input type="text" id="id_card_number" name="id_card_number" value="{{ old('id_card_number') }}"
                                       inputmode="numeric" maxlength="20" required autocomplete="off"
                                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white tabular-nums tracking-wide focus:border-emerald-500 focus:ring-emerald-500 @error('id_card_number') border-red-400 @enderror">
                                @error('id_card_number')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                                @if ($kyc && $isRejected)
                                    {{-- ช่องอื่นเติมค่าเดิมกลับให้ได้ แต่เลขบัตรเก็บเป็นแฮชอย่างเดียว --}}
                                    <p class="mt-1.5 text-xs text-gray-400"><x-bi th="ต้องกรอกใหม่ — ระบบไม่ได้เก็บเลขเต็มไว้" en="Re-enter it — we never stored the full number" /></p>
                                @endif
                            </div>
                            <div>
                                <label for="birth_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    <x-bi th="วันเกิด" en="Date of birth" />
                                </label>
                                <input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date', $kyc?->birth_date?->format('Y-m-d')) }}" required
                                       max="{{ now()->subDay()->toDateString() }}"
                                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 @error('birth_date') border-red-400 @enderror">
                                @error('birth_date')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="full_name_th" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    <x-bi th="ชื่อ-นามสกุล (ภาษาไทย)" en="Full name (Thai)" />
                                </label>
                                <input type="text" id="full_name_th" name="full_name_th" value="{{ old('full_name_th', $kyc?->full_name_th) }}" required maxlength="150"
                                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 @error('full_name_th') border-red-400 @enderror">
                                @error('full_name_th')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="full_name_en" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                    <x-bi th="ชื่อ-นามสกุล (อังกฤษ)" en="Full name (English)" />
                                    <span class="text-gray-400 font-normal">· <x-bi th="ไม่บังคับ" en="optional" /></span>
                                </label>
                                <input type="text" id="full_name_en" name="full_name_en" value="{{ old('full_name_en', $kyc?->full_name_en) }}" maxlength="150"
                                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500">
                            </div>
                        </div>
                    </section>

                    {{-- ── 2. บัญชีรับเงิน ── --}}
                    <section>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-800 to-slate-900 text-emerald-300 text-sm font-bold flex items-center justify-center shadow shrink-0">2</span>
                            <h3 class="font-semibold text-gray-900 dark:text-white"><x-bi th="บัญชีที่จะรับเงิน" en="Where we send your money" /></h3>
                        </div>

                        <div class="grid sm:grid-cols-3 gap-4">
                            <div>
                                <label for="bank_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"><x-bi th="ธนาคาร" en="Bank" /></label>
                                <select id="bank_code" name="bank_code" required
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 @error('bank_code') border-red-400 @enderror">
                                    <option value="">— เลือก / Select —</option>
                                    @foreach ($banks as $code => $label)
                                        <option value="{{ $code }}" @selected(old('bank_code', $kyc?->bank_code) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('bank_code')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="bank_account_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"><x-bi th="เลขที่บัญชี" en="Account number" /></label>
                                <input type="text" id="bank_account_number" name="bank_account_number" value="{{ old('bank_account_number', $kyc?->bank_account_number) }}" required
                                       inputmode="numeric" maxlength="30" autocomplete="off"
                                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white tabular-nums focus:border-emerald-500 focus:ring-emerald-500 @error('bank_account_number') border-red-400 @enderror">
                                @error('bank_account_number')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="bank_account_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"><x-bi th="ชื่อบัญชี" en="Account name" /></label>
                                <input type="text" id="bank_account_name" name="bank_account_name" value="{{ old('bank_account_name', $kyc?->bank_account_name) }}" required maxlength="150"
                                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:border-emerald-500 focus:ring-emerald-500 @error('bank_account_name') border-red-400 @enderror">
                                @error('bank_account_name')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <p class="mt-3 flex items-start gap-2 text-xs text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 rounded-xl px-3 py-2.5">
                            <svg class="w-4 h-4 shrink-0 mt-px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span><x-bi th="ชื่อบัญชีธนาคารต้องตรงกับชื่อบนบัตรประชาชน มิฉะนั้นจะไม่ผ่านการตรวจสอบ"
                                        en="The bank account name must match the name on your ID card, or the review will fail." /></span>
                        </p>
                    </section>

                    {{-- ── 3. เอกสารแนบ ── --}}
                    <section>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-slate-800 to-slate-900 text-emerald-300 text-sm font-bold flex items-center justify-center shadow shrink-0">3</span>
                            <h3 class="font-semibold text-gray-900 dark:text-white"><x-bi th="รูปเอกสาร" en="Document photos" /></h3>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            @foreach ([
                                ['name' => 'id_card_front', 'th' => 'บัตรประชาชน (ด้านหน้า)', 'en' => 'ID card (front)', 'required' => true,
                                 'icon' => 'M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z'],
                                ['name' => 'id_card_back', 'th' => 'บัตรประชาชน (ด้านหลัง)', 'en' => 'ID card (back)', 'required' => false,
                                 'icon' => 'M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z'],
                                ['name' => 'selfie', 'th' => 'รูปถ่ายตัวเองคู่กับบัตร', 'en' => 'Selfie holding your ID', 'required' => true,
                                 'icon' => 'M15 8a3 3 0 11-6 0 3 3 0 016 0zM4.5 19.5a7.5 7.5 0 0115 0'],
                                ['name' => 'bank_book', 'th' => 'หน้าสมุดบัญชี / สลิปแสดงชื่อบัญชี', 'en' => 'Bank book page or slip showing the account name', 'required' => false,
                                 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                            ] as $doc)
                                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-900/40 p-4 @error($doc['name']) border-red-300 dark:border-red-500/40 bg-red-50/50 dark:bg-red-500/10 @enderror"
                                     x-data="{ picked: '' }">
                                    <div class="flex items-start gap-3 mb-3">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                                             :class="picked ? 'bg-gradient-to-br from-emerald-400 to-teal-600' : 'bg-gray-200 dark:bg-gray-700'">
                                            <svg class="w-5 h-5 transition-colors" :class="picked ? 'text-white' : 'text-gray-500 dark:text-gray-300'"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path x-show="! picked" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $doc['icon'] }}"/>
                                                <path x-show="picked" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <label for="{{ $doc['name'] }}" class="block text-sm font-medium text-gray-800 dark:text-gray-200">
                                                <x-bi :th="$doc['th']" :en="$doc['en']" layout="stack" />
                                            </label>
                                            @unless ($doc['required'])
                                                <span class="text-xs text-gray-400"><x-bi th="ไม่บังคับ" en="optional" /></span>
                                            @endunless
                                        </div>
                                    </div>

                                    <input type="file" id="{{ $doc['name'] }}" name="{{ $doc['name'] }}"
                                           accept="image/jpeg,image/png" @required($doc['required'])
                                           @change="picked = $event.target.files.length ? $event.target.files[0].name : ''"
                                           class="w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-emerald-700 file:font-medium file:cursor-pointer hover:file:bg-emerald-100">

                                    <p x-show="picked" x-cloak class="mt-2 text-xs text-emerald-700 truncate" x-text="picked"></p>
                                    @error($doc['name'])<p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>

                        {{-- คำอธิบายความปลอดภัย — หน้านี้ขอบัตรประชาชน คนมีสิทธิ์รู้ว่าเราทำอะไรกับมัน --}}
                        <div class="mt-4 rounded-xl bg-slate-50 dark:bg-gray-900/40 border border-slate-200 dark:border-gray-700 p-4">
                            <p class="flex items-center gap-2 text-sm font-medium text-slate-800 dark:text-slate-200 mb-2.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                <x-bi th="เราดูแลเอกสารของคุณอย่างไร" en="How we handle your documents" />
                            </p>
                            <ul class="space-y-1.5 text-xs text-slate-600 dark:text-slate-400">
                                @foreach ([
                                    ['th' => 'ไฟล์ JPEG หรือ PNG ไม่เกิน 5 MB ต่อรูป', 'en' => 'JPEG or PNG, up to 5 MB each'],
                                    ['th' => 'ระบบเข้ารหัสรูปใหม่ตอนอัปโหลด ข้อมูลตำแหน่ง (GPS) ที่ติดมากับรูปจากมือถือถูกลบทิ้ง', 'en' => 'Images are re-encoded on upload, which strips the GPS location your phone attaches'],
                                    ['th' => 'เอกสารเก็บในพื้นที่ปิด ไม่มี URL สาธารณะ เปิดดูได้เฉพาะคุณและเจ้าหน้าที่ตรวจสอบ', 'en' => 'Stored on private disk with no public URL — only you and our reviewers can open them'],
                                    ['th' => 'เลขบัตรประชาชนเก็บเป็นค่าที่ย้อนกลับไม่ได้ ไม่ได้เก็บเลขเต็มลงฐานข้อมูล', 'en' => 'The ID number is stored as a one-way value, never as the full number'],
                                ] as $note)
                                    <li class="flex items-start gap-2">
                                        <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        <span><x-bi :th="$note['th']" :en="$note['en']" /></span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </section>

                    {{-- ── ความยินยอม ── --}}
                    <label class="flex items-start gap-3 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 p-4 cursor-pointer hover:border-emerald-300 transition-colors @error('consent') border-red-300 dark:border-red-500/40 bg-red-50/50 dark:bg-red-500/10 @enderror">
                        <input type="checkbox" name="consent" value="1" required
                               class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 shrink-0">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            <x-bi th="ข้าพเจ้ายินยอมให้ XMAN Studio เก็บและใช้ข้อมูลบัตรประชาชน รูปถ่าย และข้อมูลบัญชีธนาคาร เพื่อยืนยันตัวตนและตรวจสอบสิทธิ์รับเงิน และรับทราบว่าข้อมูลนี้อาจถูกเปิดเผยต่อเจ้าหน้าที่เมื่อมีคำสั่งที่ชอบด้วยกฎหมาย"
                                  en="I consent to XMAN Studio storing and using my ID card details, photos and bank account information to verify my identity and my eligibility to receive money, and I understand this information may be disclosed to authorities under lawful order." />
                            @error('consent')<span class="block mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</span>@enderror
                        </span>
                    </label>

                    <div class="flex flex-col sm:flex-row items-center gap-3">
                        <button type="submit" x-bind:disabled="sending"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 px-7 py-3.5 text-white font-semibold shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 hover:-translate-y-0.5 transition-all disabled:opacity-60 disabled:cursor-not-allowed disabled:translate-y-0">
                            <svg class="w-5 h-5" x-show="! sending" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2.5l7.5 3v6c0 4.6-3.1 8.6-7.5 10-4.4-1.4-7.5-5.4-7.5-10v-6l7.5-3z"/>
                            </svg>
                            <svg class="w-5 h-5 animate-spin" x-show="sending" x-cloak fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <span x-show="! sending"><x-bi th="ส่งเอกสารยืนยันตัวตน" en="Submit for verification" /></span>
                            <span x-show="sending" x-cloak><x-bi th="กำลังอัปโหลด…" en="Uploading…" /></span>
                        </button>
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center sm:text-left">
                            <x-bi th="ส่งแล้วจะแก้ไขไม่ได้จนกว่าผลตรวจจะออก" en="Once submitted you cannot edit until the review is decided." />
                        </p>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
