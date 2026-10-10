@extends($publicLayout ?? 'layouts.app')

@section('title', 'จดโดเมน ' . $domain . ' | XMAN Studio')

@section('content')
{{--
    ฟอร์มจดทะเบียนโดเมน

    ข้อมูลในหน้านี้กลายเป็น "ผู้ถือครอง" ของโดเมนตามทะเบียนสากล ไม่ใช่แค่
    ที่อยู่ส่งของ — ชื่อที่กรอกคือชื่อที่จะปรากฏว่าเป็นเจ้าของ และเป็นชื่อที่
    ใช้ยืนยันสิทธิ์ตอนย้ายโดเมนออก จึงบอกไว้ตรง ๆ บนหน้าแทนที่จะซ่อนใน
    เงื่อนไขการใช้งาน

    กฎของทะเบียน (ที่อยู่บรรทัดเดียวไม่เกิน 50 ตัว, จังหวัดต้องตรงรายการ,
    รหัสไปรษณีย์ตามรูปแบบประเทศ) มาจาก config/domain_whois.php — สคริปต์
    ท้ายหน้าตรวจตามกฎชุดเดียวกับ App\Support\WhoisContact ฝั่งเซิร์ฟเวอร์
    เพื่อให้ลูกค้ารู้ก่อนจ่าย ไม่ใช่รู้จากการคืนเงิน

    ทุกช่องมี autocomplete ตามมาตรฐาน เพื่อให้เบราว์เซอร์เติมชื่อ/ที่อยู่ที่
    บันทึกไว้ได้ในคลิกเดียว — จังหวัดเป็นช่องพิมพ์ (ไม่ใช่ dropdown) เพราะ
    autofill เติม "กรุงเทพมหานคร" ลง dropdown ที่ค่าเป็น "Bangkok" ไม่ได้
--}}
@php
    $firstUsable = $contacts->first(fn ($c) => empty($contactProblems[$c->id]));
    $defaultUsable = $contacts->first(fn ($c) => $c->is_default && empty($contactProblems[$c->id]));
    $startContact = optional($defaultUsable ?? $firstUsable)->id;
    $hasOldNew = old('first_name') !== null || old('address1') !== null;
    $startMode = (! $hasOldNew && $startContact) ? 'saved' : 'new';

    $initial = [
        'first_name' => old('first_name', ''),
        'last_name' => old('last_name', ''),
        'organization' => old('organization', ''),
        'email' => old('email', auth()->user()->email ?? ''),
        'phone_country_code' => old('phone_country_code', '+66'),
        'phone' => old('phone', ''),
        'address1' => old('address1', ''),
        'address2' => old('address2', ''),
        'city' => old('city', ''),
        'state' => old('state', ''),
        'zip' => old('zip', ''),
        'country' => old('country', 'TH'),
    ];

    $serverErrors = collect(['first_name', 'last_name', 'organization', 'email', 'phone_country_code', 'phone', 'address1', 'address2', 'city', 'state', 'zip', 'country'])
        ->mapWithKeys(fn ($f) => [$f => $errors->first($f)])
        ->filter()
        ->all();
@endphp

@include('domains.partials.registrant-script')

<div class="bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        <a href="{{ route('domains.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-5 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <x-bi th="ค้นหาชื่ออื่น" en="Search another name" />
        </a>

        @if (session('error'))
            <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-5 py-4 mb-6 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-5 py-4 mb-6 text-sm">
                <p class="font-semibold mb-1"><x-bi th="กรุณาตรวจสอบข้อมูลต่อไปนี้" en="Please check the following" /></p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- สรุปรายการ --}}
        <div class="rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-7 mb-6 shadow-xl relative overflow-hidden">
            <x-page-art art="hero-domains" :opacity="25" :scrim="false" />
            <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-indigo-300/80 text-xs font-semibold tracking-[0.2em] uppercase mb-1.5">
                        <x-bi th="กำลังจด" en="Registering" />
                    </p>
                    <p class="text-2xl sm:text-3xl font-bold break-all">{{ $domain }}</p>
                </div>
                <div class="text-left sm:text-right shrink-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-300">
                        <x-bi th="ราคาปีแรก" en="First year" />
                    </p>
                    <p class="text-3xl font-bold">{{ $priceDisplay }}</p>
                    <p class="text-sm text-indigo-200/70"><x-bi th="สำหรับ 1 ปี" en="for 1 year" /></p>
                </div>
            </div>
            {{-- ราคาปีต่อไปแสดงเสมอ ไม่ใช่เฉพาะตอนแพงกว่า — ลูกค้าต้องรู้ทั้งสองตัวเลข
                 ก่อนกดจ่าย และวันที่เราแจ้งเตือนอ่านจากหลังบ้าน ไม่เขียนตายว่า 30 วัน --}}
            <p class="relative mt-4 pt-4 border-t border-white/10 text-sm {{ $renewalIsDearer ? 'text-amber-200' : 'text-indigo-200/80' }}">
                <x-bi th="ปีต่อไป (ต่ออายุ)" en="Following years (renewal)" />
                <span class="font-bold text-white">{{ $renewDisplay }}</span>
                <x-bi th="ต่อปี" en="per year" />
                @if($renewalIsDearer)
                    <span class="block text-xs mt-1 text-amber-200/80">
                        <x-bi th="ราคาปีแรกเป็นราคาโปรโมชันของนามสกุลนี้ — เราแจ้งเตือนล่วงหน้าก่อนถึงกำหนดต่ออายุทุกครั้ง"
                              en="This extension's first year is promotional — we always remind you before a renewal is due." />
                    </span>
                @endif
            </p>
        </div>

        {{-- ยอดเงินไม่พอ: หยุดตรงนี้ ไม่ต้องให้กรอกฟอร์มยาวแล้วค่อยบอก --}}
        @if(! $sufficient)
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 p-6 mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-amber-900 dark:text-amber-200 mb-1">
                            <x-bi th="ยอดเงินในกระเป๋าไม่พอ" en="Not enough in your wallet" />
                        </p>
                        <p class="text-sm text-amber-800 dark:text-amber-300/90 mb-3">
                            <x-bi th="ต้องใช้" en="Needs" /> <span class="font-semibold">{{ $priceDisplay }}</span> ·
                            <x-bi th="มีอยู่" en="you have" /> <span class="font-semibold">{{ $balanceDisplay }}</span> ·
                            <x-bi th="ขาดอีก" en="short by" /> <span class="font-semibold">{{ \App\Support\DomainPricing::format($shortfall) }}</span>
                        </p>
                        <a href="{{ route('user.wallet.index') }}"
                           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold shadow-sm transition">
                            <x-bi th="เติมเงิน" en="Top up" />
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('domains.register.store', $domain) }}" novalidate
              x-data="domainRegistrant(@js([
                  'mode' => $startMode,
                  'contactId' => $startContact,
                  'initial' => $initial,
                  'countries' => $whoisCountries,
                  'postcodes' => $thPostcodes,
                  'account' => $accountPrefill,
                  'addressMax' => $addressMax,
                  'serverErrors' => (object) $serverErrors,
                  'csrfUrl' => route('csrf.refresh'),
              ]))"
              @submit="onSubmit($event)">
            @csrf

            {{-- ผู้ถือครอง --}}
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm p-5 sm:p-7 mb-5">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-1">
                            <x-bi th="ผู้ถือครองโดเมน" en="Domain registrant" />
                        </h2>
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            <x-bi th="ชื่อนี้จะถูกบันทึกเป็นเจ้าของโดเมนตามทะเบียนสากล และใช้ยืนยันสิทธิ์เวลาย้ายโดเมน กรุณากรอกข้อมูลจริง"
                                  en="This goes on the international registry as the domain's owner, and is what proves it is yours if you move it. Please use real details."
                                  layout="stack" />
                        </p>
                    </div>
                    <button type="button" x-show="mode === 'new'" @click="fillFromAccount()"
                            class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-indigo-200 dark:border-indigo-500/40 bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 text-sm font-semibold hover:bg-indigo-100 dark:hover:bg-indigo-500/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0A17.9 17.9 0 0112 21.75c-2.68 0-5.22-.59-7.5-1.65z"/></svg>
                        <x-bi th="ใช้ข้อมูลจากบัญชีของฉัน" en="Use my account" />
                    </button>
                </div>

                <p x-show="note.account" x-text="note.account" x-cloak
                   class="mb-5 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 text-indigo-800 dark:text-indigo-200 text-sm px-4 py-3"></p>

                @if($contacts->isNotEmpty())
                    <div class="flex gap-2 mb-6 p-1.5 rounded-2xl bg-slate-100 dark:bg-slate-900/60">
                        <button type="button" @click="mode = 'saved'"
                                :class="mode === 'saved' ? 'bg-white dark:bg-slate-700 shadow text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'"
                                class="flex-1 px-4 py-3 rounded-xl text-sm sm:text-base font-semibold transition">
                            <x-bi th="ใช้ข้อมูลที่บันทึกไว้" en="Use saved details" />
                        </button>
                        <button type="button" @click="mode = 'new'"
                                :class="mode === 'new' ? 'bg-white dark:bg-slate-700 shadow text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'"
                                class="flex-1 px-4 py-3 rounded-xl text-sm sm:text-base font-semibold transition">
                            <x-bi th="กรอกใหม่" en="Enter new" />
                        </button>
                    </div>

                    <div x-show="mode === 'saved'" class="space-y-3" x-bind:inert="mode !== 'saved'">
                        @foreach($contacts as $c)
                            @php $broken = ! empty($contactProblems[$c->id]); @endphp
                            <label class="flex items-start gap-3 p-4 rounded-2xl border-2 transition {{ $broken ? 'opacity-70 cursor-not-allowed border-slate-200 dark:border-slate-700' : 'cursor-pointer' }}"
                                   @unless($broken) :class="contactId === {{ $c->id }} ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'" @endunless>
                                <input type="radio" name="contact_id" value="{{ $c->id }}" x-model.number="contactId"
                                       x-bind:disabled="{{ $broken ? 'true' : "mode !== 'saved'" }}" class="mt-1">
                                <span class="min-w-0">
                                    <span class="block font-semibold text-slate-900 dark:text-white">{{ $c->fullName() }}</span>
                                    <span class="block text-sm text-slate-500 dark:text-slate-400">{{ $c->summary() }}</span>
                                    <span class="block text-sm text-slate-500 dark:text-slate-400">{{ $c->email }}</span>
                                    @if($broken)
                                        <span class="mt-2 block text-sm text-amber-700 dark:text-amber-300">
                                            <x-bi th="ข้อมูลชุดนี้ยังไม่ครบตามที่ทะเบียนต้องการ — กรุณากดกรอกใหม่" en="Missing details the registry now needs — please enter new" />
                                            <span class="block text-xs mt-0.5">{{ $contactProblems[$c->id][0] }}</span>
                                        </span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                {{-- ฟอร์มใหม่ — ตอนอยู่โหมด saved ต้อง disable ไม่ใช่แค่ซ่อน
                     ไม่งั้นค่าที่ค้างอยู่จะถูกส่งไปพร้อมกันและกฎ required_without
                     จะเห็นทั้งสองชุด --}}
                @include('domains.partials.registrant-fields')

                @if($extraFields)
                    <div class="mt-6 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/30 p-4 text-sm text-blue-900 dark:text-blue-200">
                        <x-bi th="นามสกุลนี้ต้องใช้เอกสารเพิ่มเติม ทีมงานจะติดต่อกลับหลังสั่งซื้อเพื่อขอข้อมูล"
                              en="This extension needs extra documents — we'll contact you after ordering to collect them." />
                    </div>
                @endif
            </div>

            {{-- ตัวเลือก --}}
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm p-5 sm:p-7 mb-5 space-y-5">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white">
                    <x-bi th="ตัวเลือก" en="Options" />
                </h2>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="privacy" value="1" @checked(old('privacy', true)) class="mt-1">
                    <span>
                        <span class="block font-semibold text-slate-900 dark:text-white">
                            <x-bi th="ปกปิดข้อมูลส่วนตัวใน WHOIS (ฟรี)" en="Hide my details in public WHOIS (free)" />
                        </span>
                        <span class="block text-sm text-slate-500 dark:text-slate-400">
                            <x-bi th="ชื่อ ที่อยู่ และเบอร์โทรของคุณจะไม่แสดงในฐานข้อมูลสาธารณะ ลดสแปมและการถูกติดต่อโดยไม่ต้องการ"
                                  en="Your name, address and phone stay out of the public database — less spam, fewer cold calls." />
                        </span>
                    </span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="auto_renew" value="1" @checked(old('auto_renew', false)) class="mt-1">
                    <span>
                        <span class="block font-semibold text-slate-900 dark:text-white">
                            <x-bi th="ต่ออายุอัตโนมัติ" en="Renew automatically" />
                        </span>
                        <span class="block text-sm text-slate-500 dark:text-slate-400">
                            {{-- The day count comes from settings. Written out, this sentence
                                 becomes a false promise the moment an operator moves it. --}}
                            <x-bi th="ตัดจากกระเป๋าเงินก่อนหมดอายุ {{ \App\Support\DomainReminders::chargeDays() }} วัน เราแจ้งล่วงหน้าทุกครั้ง และปิดได้ตลอดเวลา"
                                  en="Charged from your wallet {{ \App\Support\DomainReminders::chargeDays() }} days before expiry. We always warn you first, and you can turn it off any time." />
                        </span>
                    </span>
                </label>
            </div>

            {{-- ยืนยัน --}}
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm p-5 sm:p-7">
                <label class="flex items-start gap-3 cursor-pointer mb-5 rounded-xl p-3 -m-3 transition"
                       :class="termsMissing ? 'bg-red-50 dark:bg-red-500/10 ring-2 ring-red-300 dark:ring-red-500/40' : ''">
                    <input type="checkbox" name="accept_terms" value="1" required x-ref="terms"
                           @change="termsMissing = false" class="mt-1">
                    <span class="text-sm text-slate-700 dark:text-slate-300">
                        <x-bi th="ข้าพเจ้ายืนยันว่าข้อมูลผู้ถือครองเป็นความจริง และรับทราบว่าค่าจดทะเบียนโดเมนไม่สามารถขอคืนได้หลังจดสำเร็จ ตามข้อกำหนดของผู้ดูแลทะเบียนโดเมนสากล"
                              en="I confirm the registrant details are accurate, and understand that domain registration fees are non-refundable once the name is registered, per international registry rules."
                              layout="stack" />
                        <span x-show="termsMissing" x-cloak class="field-error">
                            <x-bi th="กรุณาติ๊กยืนยันก่อนกดจด" en="Please tick to confirm first" />
                        </span>
                    </span>
                </label>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-5 border-t border-slate-200 dark:border-slate-700">
                    <div>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><x-bi th="ยอดชำระวันนี้ (ปีแรก)" en="Due today (first year)" /></p>
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ $priceDisplay }}</p>
                        <p class="text-xs {{ $renewalIsDearer ? 'text-amber-700 dark:text-amber-300' : 'text-slate-500 dark:text-slate-400' }}">
                            <x-bi th="ปีต่อไป" en="Following years" /> {{ $renewDisplay }}/<x-bi th="ปี" en="yr" />
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            <x-bi th="หักจากกระเป๋าเงิน คงเหลือ" en="From your wallet — balance" /> {{ $balanceDisplay }}
                        </p>
                    </div>

                    {{-- กดซ้ำไม่ได้: ปุ่มถูกปิดทันทีที่ส่ง และฝั่งเซิร์ฟเวอร์ยัง
                         มีคีย์กันซ้ำอีกชั้นเผื่อ JS ไม่ทำงาน --}}
                    <button type="submit"
                            @disabled(! $sufficient)
                            x-bind:disabled="submitting || @js(! $sufficient)"
                            class="px-8 py-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-lg font-bold shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:scale-[1.02] active:scale-[0.99] transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                        <span x-show="!submitting">
                            <x-bi th="ยืนยันและจดโดเมน" en="Confirm and register" />
                        </span>
                        <span x-show="submitting" x-cloak class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <x-bi th="กำลังดำเนินการ อย่าปิดหน้านี้" en="Working — don't close this page" />
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
