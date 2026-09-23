@extends($publicLayout ?? 'layouts.app')

@section('title', 'จดโดเมน ' . $domain . ' | XMAN Studio')

@section('content')
{{--
    ฟอร์มจดทะเบียนโดเมน

    ข้อมูลในหน้านี้กลายเป็น "ผู้ถือครอง" ของโดเมนตามทะเบียนสากล ไม่ใช่แค่
    ที่อยู่ส่งของ — ชื่อที่กรอกคือชื่อที่จะปรากฏว่าเป็นเจ้าของ และเป็นชื่อที่
    ใช้ยืนยันสิทธิ์ตอนย้ายโดเมนออก จึงบอกไว้ตรง ๆ บนหน้าแทนที่จะซ่อนใน
    เงื่อนไขการใช้งาน
--}}
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

        <form method="POST" action="{{ route('domains.register.store', $domain) }}"
              x-data="{
                  submitting: false,
                  mode: @js($contacts->isNotEmpty() ? 'saved' : 'new'),
                  contactId: @js(optional($contacts->firstWhere('is_default', true))->id ?? optional($contacts->first())->id),
              }"
              @submit="submitting = true">
            @csrf

            {{-- ผู้ถือครอง --}}
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-6 mb-5">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-1">
                        <x-bi th="ผู้ถือครองโดเมน" en="Domain registrant" />
                    </h2>
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <x-bi th="ชื่อนี้จะถูกบันทึกเป็นเจ้าของโดเมนตามทะเบียนสากล และใช้ยืนยันสิทธิ์เวลาย้ายโดเมน กรุณากรอกข้อมูลจริง"
                              en="This goes on the international registry as the domain's owner, and is what proves it is yours if you move it. Please use real details."
                              layout="stack" />
                    </p>
                </div>

                @if($contacts->isNotEmpty())
                    <div class="flex gap-2 mb-5 p-1 rounded-xl bg-slate-100 dark:bg-slate-900/60">
                        <button type="button" @click="mode = 'saved'"
                                :class="mode === 'saved' ? 'bg-white dark:bg-slate-700 shadow-sm text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'"
                                class="flex-1 px-4 py-2 rounded-lg text-sm font-medium transition">
                            <x-bi th="ใช้ข้อมูลที่บันทึกไว้" en="Use saved details" />
                        </button>
                        <button type="button" @click="mode = 'new'"
                                :class="mode === 'new' ? 'bg-white dark:bg-slate-700 shadow-sm text-slate-900 dark:text-white' : 'text-slate-500 dark:text-slate-400'"
                                class="flex-1 px-4 py-2 rounded-lg text-sm font-medium transition">
                            <x-bi th="กรอกใหม่" en="Enter new" />
                        </button>
                    </div>

                    <div x-show="mode === 'saved'" class="space-y-2">
                        @foreach($contacts as $c)
                            <label class="flex items-start gap-3 p-4 rounded-xl border cursor-pointer transition"
                                   :class="contactId === {{ $c->id }} ? 'border-indigo-400 dark:border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300'">
                                <input type="radio" name="contact_id" value="{{ $c->id }}" x-model.number="contactId"
                                       class="mt-1 text-indigo-600 focus:ring-indigo-500">
                                <span class="min-w-0">
                                    <span class="block font-medium text-slate-900 dark:text-white">{{ $c->fullName() }}</span>
                                    <span class="block text-sm text-slate-500 dark:text-slate-400">{{ $c->summary() }}</span>
                                    <span class="block text-sm text-slate-500 dark:text-slate-400">{{ $c->email }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                {{-- ฟอร์มใหม่ — ตอนอยู่โหมด saved ต้อง disable ไม่ใช่แค่ซ่อน
                     ไม่งั้นค่าที่ค้างอยู่จะถูกส่งไปพร้อมกันและกฎ required_without
                     จะเห็นทั้งสองชุด --}}
                <div x-show="mode === 'new'" class="grid sm:grid-cols-2 gap-4" x-bind:inert="mode !== 'new'">
                    @php
                        $inputClass = 'w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm';
                        $labelClass = 'block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5';
                    @endphp

                    <div>
                        <label class="{{ $labelClass }}"><x-bi th="ชื่อ" en="First name" /> <span class="text-red-500">*</span></label>
                        <input type="text" name="first_name" value="{{ old('first_name') }}" x-bind:disabled="mode !== 'new'" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}"><x-bi th="นามสกุล" en="Last name" /> <span class="text-red-500">*</span></label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" x-bind:disabled="mode !== 'new'" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}"><x-bi th="ชื่อองค์กร (ถ้ามี)" en="Organisation (optional)" /></label>
                        <input type="text" name="organization" value="{{ old('organization') }}" x-bind:disabled="mode !== 'new'" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}"><x-bi th="อีเมล" en="Email" /> <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" x-bind:disabled="mode !== 'new'" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}"><x-bi th="โทรศัพท์" en="Phone" /> <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <input type="text" name="phone_country_code" value="{{ old('phone_country_code', '+66') }}"
                                   x-bind:disabled="mode !== 'new'" class="{{ $inputClass }} w-20 shrink-0" placeholder="+66">
                            <input type="tel" name="phone" value="{{ old('phone') }}" x-bind:disabled="mode !== 'new'" class="{{ $inputClass }}" placeholder="812345678">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}"><x-bi th="ที่อยู่" en="Address" /> <span class="text-red-500">*</span></label>
                        <input type="text" name="address1" value="{{ old('address1') }}" x-bind:disabled="mode !== 'new'" class="{{ $inputClass }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="{{ $labelClass }}"><x-bi th="ที่อยู่ (เพิ่มเติม)" en="Address line 2" /></label>
                        <input type="text" name="address2" value="{{ old('address2') }}" x-bind:disabled="mode !== 'new'" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}"><x-bi th="เขต/อำเภอ/จังหวัด" en="City" /> <span class="text-red-500">*</span></label>
                        <input type="text" name="city" value="{{ old('city') }}" x-bind:disabled="mode !== 'new'" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}"><x-bi th="รัฐ/จังหวัด" en="State / Province" /></label>
                        <input type="text" name="state" value="{{ old('state') }}" x-bind:disabled="mode !== 'new'" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}"><x-bi th="รหัสไปรษณีย์" en="Postal code" /> <span class="text-red-500">*</span></label>
                        <input type="text" name="zip" value="{{ old('zip') }}" x-bind:disabled="mode !== 'new'" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}"><x-bi th="ประเทศ" en="Country" /> <span class="text-red-500">*</span></label>
                        <select name="country" x-bind:disabled="mode !== 'new'" class="{{ $inputClass }}">
                            @php
                                $countries = ['TH' => 'ไทย / Thailand', 'SG' => 'สิงคโปร์ / Singapore', 'MY' => 'มาเลเซีย / Malaysia',
                                    'LA' => 'ลาว / Laos', 'KH' => 'กัมพูชา / Cambodia', 'MM' => 'เมียนมา / Myanmar', 'VN' => 'เวียดนาม / Vietnam',
                                    'JP' => 'ญี่ปุ่น / Japan', 'KR' => 'เกาหลีใต้ / South Korea', 'CN' => 'จีน / China', 'TW' => 'ไต้หวัน / Taiwan',
                                    'HK' => 'ฮ่องกง / Hong Kong', 'AU' => 'ออสเตรเลีย / Australia', 'GB' => 'สหราชอาณาจักร / United Kingdom',
                                    'US' => 'สหรัฐอเมริกา / United States', 'DE' => 'เยอรมนี / Germany', 'FR' => 'ฝรั่งเศส / France'];
                            @endphp
                            @foreach($countries as $code => $name)
                                <option value="{{ $code }}" @selected(old('country', 'TH') === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="save_contact" value="1" checked x-bind:disabled="mode !== 'new'"
                                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-slate-700 dark:text-slate-300">
                                <x-bi th="เสนอข้อมูลชุดนี้ให้เลือกในครั้งต่อไป" en="Offer these details again next time" />
                                {{-- พูดตรง ๆ ว่าข้อมูลถูกเก็บอยู่แล้ว เพราะมันคือ
                                     ผู้ถือครองโดเมนตามทะเบียน ไม่ใช่ที่อยู่ส่งของ
                                     ที่จะลบทิ้งได้ · ช่องนี้คุมแค่การแสดงในตัวเลือก --}}
                                <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    <x-bi th="ข้อมูลผู้ถือครองถูกเก็บไว้กับโดเมนเสมอตามข้อกำหนดทะเบียนสากล ช่องนี้เลือกแค่ว่าจะให้ขึ้นเป็นตัวเลือกให้กดเลือกซ้ำหรือไม่"
                                          en="Registrant details are always kept with the domain, as the registry requires. This only chooses whether they appear as a saved option." />
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                @if($extraFields)
                    <div class="mt-5 rounded-xl bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/30 p-4 text-sm text-blue-900 dark:text-blue-200">
                        <x-bi th="นามสกุลนี้ต้องใช้เอกสารเพิ่มเติม ทีมงานจะติดต่อกลับหลังสั่งซื้อเพื่อขอข้อมูล"
                              en="This extension needs extra documents — we'll contact you after ordering to collect them." />
                    </div>
                @endif
            </div>

            {{-- ตัวเลือก --}}
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-6 mb-5 space-y-4">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                    <x-bi th="ตัวเลือก" en="Options" />
                </h2>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="privacy" value="1" @checked(old('privacy', true))
                           class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>
                        <span class="block font-medium text-slate-900 dark:text-white">
                            <x-bi th="ปกปิดข้อมูลส่วนตัวใน WHOIS (ฟรี)" en="Hide my details in public WHOIS (free)" />
                        </span>
                        <span class="block text-sm text-slate-500 dark:text-slate-400">
                            <x-bi th="ชื่อ ที่อยู่ และเบอร์โทรของคุณจะไม่แสดงในฐานข้อมูลสาธารณะ ลดสแปมและการถูกติดต่อโดยไม่ต้องการ"
                                  en="Your name, address and phone stay out of the public database — less spam, fewer cold calls." />
                        </span>
                    </span>
                </label>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="auto_renew" value="1" @checked(old('auto_renew', false))
                           class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>
                        <span class="block font-medium text-slate-900 dark:text-white">
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
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-6">
                <label class="flex items-start gap-3 cursor-pointer mb-5">
                    <input type="checkbox" name="accept_terms" value="1" required
                           class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-slate-700 dark:text-slate-300">
                        <x-bi th="ข้าพเจ้ายืนยันว่าข้อมูลผู้ถือครองเป็นความจริง และรับทราบว่าค่าจดทะเบียนโดเมนไม่สามารถขอคืนได้หลังจดสำเร็จ ตามข้อกำหนดของผู้ดูแลทะเบียนโดเมนสากล"
                              en="I confirm the registrant details are accurate, and understand that domain registration fees are non-refundable once the name is registered, per international registry rules."
                              layout="stack" />
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
                            class="px-8 py-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:scale-[1.02] active:scale-[0.99] transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
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
