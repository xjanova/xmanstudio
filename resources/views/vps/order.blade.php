@extends($publicLayout ?? 'layouts.app')

@section('title', 'เช่า ' . $plan->name . ' | XMAN Studio')
@section('meta_description', 'สั่งเช่า ' . $plan->name . ' — เลือกรอบบิล ระบบปฏิบัติการ และศูนย์ข้อมูล ชำระจากกระเป๋าเงิน เห็นราคารอบถัดไปก่อนยืนยันทุกครั้ง')

@section('content')
{{--
    ฟอร์มสั่งเช่า VPS

    - expected_amount คือราคารอบแรกที่ลูกค้าเห็นบนหน้านี้ ฝั่งเซิร์ฟเวอร์เทียบกับ
      ราคาจริงอีกครั้ง ถ้าราคาขยับระหว่างเปิดหน้าค้างไว้ ระบบจะถามใหม่ ไม่ตัดเงิน
      ตัวเลขที่ลูกค้าไม่เคยเห็น
    - รหัสผ่าน root ไม่ถูกเติมกลับเด็ดขาด (ไม่อ่าน old) ทุกครั้งที่เซิร์ฟเวอร์ตีกลับ
      ลูกค้าต้องตั้งใหม่ จึงตรวจกฎเดียวกับฝั่งเซิร์ฟเวอร์ไว้ที่นี่ก่อนส่ง
    - ตัวเลือกระบบปฏิบัติการเรนเดอร์ด้วย Blade ไม่ใช่ x-for: x-model บน select
      ที่ยังไม่มี option ตอน Alpine เริ่มทำงาน จะตกไปที่ตัวเลือกแรกเสมอ
    - auto_renew มี hidden "0" นำหน้า: checkbox ที่ไม่ติ๊กจะไม่ถูกส่งมาเลย และ
      ฝั่งเซิร์ฟเวอร์อ่านค่าที่หายไปว่า "เปิด" — ลูกค้าจะปิดไม่ได้
    - ส่งซ้ำหลังถูกตีกลับใช้ order_token เดิม ถ้าครั้งก่อนตัดเงินไปแล้วแต่เกิด
      ข้อผิดพลาดที่ไม่คาดคิด ครั้งนี้จะพาไปที่เครื่องเดิม ไม่ซื้อเครื่องที่สอง
    - ข้อความ flash จาก session แสดงที่ layout อยู่แล้ว หน้านี้จึงไม่แสดงซ้ำ
--}}
@php
    $periodCopy = [
        '1m' => ['first_th' => 'เดือนแรก', 'first_en' => 'First month', 'next_th' => 'เดือนถัดไป (ต่ออายุ)', 'next_en' => 'Following months (renewal)'],
        '1y' => ['first_th' => 'ปีแรก', 'first_en' => 'First year', 'next_th' => 'ปีต่อไป (ต่ออายุ)', 'next_en' => 'Following years (renewal)'],
        '2y' => ['first_th' => '2 ปีแรก', 'first_en' => 'First 2 years', 'next_th' => '2 ปีต่อไป (ต่ออายุ)', 'next_en' => 'Following 2-year terms (renewal)'],
    ];
    $fallbackCopy = ['first_th' => 'รอบแรก', 'first_en' => 'First term', 'next_th' => 'รอบถัดไป (ต่ออายุ)', 'next_en' => 'Following terms (renewal)'];

    $firstByPeriod = $periods->mapWithKeys(fn ($p) => [$p['key'] => (float) $p['first']])->all();

    // ยอดในกระเป๋ามีเศษสตางค์ได้ (เติมเงินแบบยอดเฉพาะ) ถ้าปัดเป็นบาทเต็ม
    // 459.63 จะกลายเป็น "460 ฿" ซึ่งอ่านว่าพอจ่าย 460 ฿ ทั้งที่ไม่พอ
    $baht = fn (float $amount) => abs($amount - round($amount)) < 0.005
        ? \App\Support\VpsPricing::format(round($amount))
        : number_format($amount, 2) . ' ฿';
    $balanceText = $baht((float) $balance);
    $shortfalls = $periods->mapWithKeys(fn ($p) => [$p['key'] => $baht(max(0.0, (float) $p['first'] - (float) $balance))])->all();
    $shortDefault = (float) $balance < ($firstByPeriod[$defaultPeriod] ?? INF);

    // ระบบปฏิบัติการ: แผนที่ id → ข้อมูล ให้ Alpine ใช้แสดงคำอธิบายและสรุปรายการ
    $groups = \App\Support\VpsCatalog::GROUPS;
    $templateMap = [];
    foreach ($templates as $list) {
        foreach ($list as $t) {
            $templateMap[(int) $t['id']] = [
                'name' => (string) $t['name'],
                'description' => (string) ($t['description'] ?? ''),
                'licensed' => ! empty($t['licensed']),
            ];
        }
    }
    $selectedTemplate = (int) old('template_id', $defaultTemplate);
    if (! isset($templateMap[$selectedTemplate])) {
        $selectedTemplate = (int) (array_key_first($templateMap) ?? 0);
    }

    $dcMap = [];
    foreach ($dataCenters as $dc) {
        $dcMap[(int) $dc['id']] = ['label' => \App\Support\VpsCatalog::describeDataCenter($dc)];
    }
    $selectedDc = (int) old('data_center_id', $defaultDataCenter);
    if (! isset($dcMap[$selectedDc])) {
        $selectedDc = (int) (array_key_first($dcMap) ?? 0);
    }

    // รายการมาจาก API ที่แคชไว้ ถ้าดึงไม่สำเร็จจะว่างเปล่า — สั่งเช่าไม่ได้จนกว่าจะรีโหลด
    $catalogReady = $templateMap !== [] && $dcMap !== [];
    $isPaused = $paused !== null;

    // old() ทุกตัวตรวจชนิดก่อนใช้ ค่าที่ถูกแก้ส่งมาเป็น array ต้องไม่ทำหน้าพัง
    $retrying = old('order_token') !== null;
    $oldToken = old('order_token');
    $formToken = \Illuminate\Support\Str::isUuid($oldToken) ? $oldToken : $orderToken;
    $hostnameValue = is_string(old('hostname')) ? old('hostname') : $suggestedHostname;
    $publicKeyValue = is_string(old('public_key')) ? old('public_key') : '';
    $autoRenewOn = old('auto_renew') !== null ? (bool) old('auto_renew') : true;
    $acceptedOld = (bool) old('accept_terms');

    $known = fn ($label) => is_string($label) && $label !== '' && ! str_starts_with($label, '0 ');
    $memory = \App\Models\VpsPlan::sizeLabel($plan->memory_mb);
    $disk = \App\Models\VpsPlan::sizeLabel($plan->disk_mb);
    $bandwidth = \App\Models\VpsPlan::sizeLabel($plan->bandwidth_mb);
    $specChips = array_values(array_filter([
        $plan->cpus . ' vCPU',
        $known($memory) ? $memory . ' RAM' : null,
        $known($disk) ? $disk . ' NVMe SSD' : null,
        $known($bandwidth) ? $bandwidth . ' แบนด์วิดท์' : null,
        $plan->network_mbps ? $plan->network_mbps . ' Mbps' : null,
    ]));

    $serverBlocked = $isPaused || ! $catalogReady || $shortDefault;

    $inputClass = 'w-full min-w-0 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white px-3.5 py-2.5 text-base sm:text-sm shadow-sm placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition';
    $labelClass = 'block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5';
    $cardClass = 'rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5 sm:p-6 mb-5';

    $terms = [
        ['th' => 'ค่าเช่าไม่สามารถขอคืนได้หลังเครื่องพร้อมใช้งาน', 'en' => 'Rent is non-refundable once the server is ready.'],
        ['th' => 'ห้ามใช้ทำสิ่งผิดกฎหมาย ส่งสแปม หรือขุดคริปโต', 'en' => 'No illegal use, spam or crypto mining.'],
        ['th' => 'เมื่อหมดอายุและไม่ต่ออายุ ข้อมูลในเครื่องจะถูกลบ — สำรองข้อมูลสำคัญไว้เสมอ', 'en' => "When a rental expires and isn't renewed, the server's data is deleted — keep your own backups."],
    ];

    $formConfig = [
        'period' => $defaultPeriod,
        'firsts' => (object) $firstByPeriod,
        'balance' => (float) $balance,
        'paused' => $isPaused,
        'catalogReady' => $catalogReady,
        'template' => $selectedTemplate,
        'templates' => (object) $templateMap,
        'dataCenter' => $selectedDc,
        'dataCenters' => (object) $dcMap,
        'hostname' => $hostnameValue,
        'publicKey' => $publicKeyValue,
        'showKey' => $publicKeyValue !== '' || $errors->has('public_key'),
        'accepted' => $acceptedOld,
    ];
@endphp

<div class="bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12"
         x-data="vpsOrderForm(@js($formConfig))"
         @pageshow.window="if ($event.persisted) submitting = false">

        <a href="{{ route('vps.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-5 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <x-bi th="ดูแพ็กเกจทั้งหมด" en="All plans" />
        </a>

        @if ($errors->any())
            <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-5 py-4 mb-6 text-sm" role="alert">
                <p class="font-semibold mb-1"><x-bi th="กรุณาตรวจสอบข้อมูลต่อไปนี้" en="Please check the following" /></p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ร้านจ่ายเงินต้นทางไม่ได้ชั่วคราว: บอกก่อนลูกค้ากรอก และยังไม่มีเงินขยับ --}}
        @if($isPaused)
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-900 dark:text-amber-200 p-5 mb-6 flex items-start gap-3" role="status">
                <svg class="w-6 h-6 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <div class="min-w-0">
                    <p class="font-semibold"><x-bi th="ปิดรับคำสั่งเช่าชั่วคราว" en="Ordering is paused for now" /></p>
                    <p class="mt-1 text-sm text-amber-800 dark:text-amber-300/90">{{ \App\Support\UpstreamBilling::customerMessage() }}</p>
                </div>
            </div>
        @endif

        {{-- สรุปแพ็กเกจ --}}
        <div class="rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-7 mb-6 shadow-xl relative overflow-hidden">
            <x-page-art art="hero-vps" :opacity="25" :scrim="false" />
            <div class="relative flex flex-col sm:flex-row sm:items-start sm:justify-between gap-5">
                <div class="min-w-0">
                    <p class="text-indigo-300/80 text-xs font-semibold tracking-[0.2em] uppercase mb-1.5">
                        <x-bi th="กำลังเช่า" en="Renting" />
                    </p>
                    <p class="text-2xl sm:text-3xl font-bold break-words">{{ $plan->name }}</p>
                    <ul class="mt-3 flex flex-wrap gap-1.5 text-xs">
                        @foreach($specChips as $chip)
                            <li class="px-2.5 py-1 rounded-full bg-white/10 border border-white/10 backdrop-blur-sm whitespace-nowrap">{{ $chip }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="text-left sm:text-right shrink-0">
                    @foreach($periods as $p)
                        @php $copy = $periodCopy[$p['key']] ?? $fallbackCopy; @endphp
                        <div x-show="period === @js($p['key'])" @if($p['key'] !== $defaultPeriod) x-cloak @endif>
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-300">
                                <x-bi :th="$copy['first_th']" :en="$copy['first_en']" />
                            </p>
                            <p class="text-3xl font-bold">{{ $p['first_display'] }}</p>
                            <p class="text-sm text-indigo-200/70"><x-bi :th="$p['label_th']" :en="$p['label_en']" /></p>
                        </div>
                    @endforeach
                </div>
            </div>
            {{-- ราคารอบถัดไปแสดงเสมอ ไม่ใช่เฉพาะตอนแพงกว่า --}}
            <div class="relative mt-4 pt-4 border-t border-white/10 text-sm">
                @foreach($periods as $p)
                    @php
                        $copy = $periodCopy[$p['key']] ?? $fallbackCopy;
                        $dearer = (float) $p['renew'] > (float) $p['first'];
                    @endphp
                    <p x-show="period === @js($p['key'])" @if($p['key'] !== $defaultPeriod) x-cloak @endif
                       class="{{ $dearer ? 'text-amber-200' : 'text-indigo-200/80' }}">
                        <x-bi :th="$copy['next_th']" :en="$copy['next_en']" />
                        <span class="font-bold text-white whitespace-nowrap">{{ $p['renew_display'] }}</span>/<x-bi :th="$p['unit_th']" :en="\App\Support\VpsPricing::unitLabel($p['key'], 'en')" />
                        @if($dearer)
                            <span class="block text-xs mt-1 text-amber-200/80">
                                <x-bi th="ราคารอบแรกเป็นราคาโปรโมชัน — เราแจ้งเตือนล่วงหน้าก่อนถึงกำหนดต่ออายุทุกครั้ง"
                                      en="The first term is promotional — we always remind you before a renewal is due." />
                            </span>
                        @endif
                    </p>
                @endforeach
            </div>
        </div>

        {{-- ดึงรายการ OS / ศูนย์ข้อมูลไม่สำเร็จ — เลือกไม่ได้ก็สั่งไม่ได้ บอกตรง ๆ --}}
        @unless($catalogReady)
            <div class="rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-900 dark:text-amber-200 p-5 mb-6 flex items-start gap-3" role="status">
                <svg class="w-6 h-6 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4v5h5M20 20v-5h-5M5.5 15a7 7 0 0012.2 2.5M18.5 9A7 7 0 006.3 6.5"/>
                </svg>
                <div class="min-w-0">
                    <p class="font-semibold"><x-bi th="โหลดตัวเลือกเซิร์ฟเวอร์ไม่สำเร็จ" en="Couldn't load the server options" /></p>
                    <p class="mt-1 text-sm text-amber-800 dark:text-amber-300/90">
                        <x-bi th="รายการระบบปฏิบัติการหรือศูนย์ข้อมูลยังไม่พร้อมในขณะนี้ และยังไม่มีการตัดเงินใด ๆ — ลองรีโหลดหน้านี้อีกครั้งในอีกสักครู่"
                              en="The OS or location list isn't available right now, and nothing has been charged. Try reloading in a moment." />
                    </p>
                    <a href="{{ request()->fullUrl() }}" class="mt-3 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold transition">
                        <x-bi th="รีโหลด" en="Reload" />
                    </a>
                </div>
            </div>
        @endunless

        <form method="POST" action="{{ route('vps.order.store', $plan->slug) }}" @submit="submit($event)">
            @csrf
            <input type="hidden" name="order_token" value="{{ $formToken }}">
            <input type="hidden" name="expected_amount" value="{{ $firstByPeriod[$defaultPeriod] ?? '' }}" x-bind:value="expectedAmount">

            {{-- ══════════ รอบบิล ══════════ --}}
            <div class="{{ $cardClass }}">
                <h2 id="vps-period-heading" class="text-lg font-bold text-slate-900 dark:text-white mb-1">
                    <x-bi th="รอบบิล" en="Billing period" />
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    <x-bi th="ทุกตัวเลือกบอกทั้งราคารอบแรกและราคารอบถัดไป" en="Each option shows the first term and what every renewal costs." />
                </p>

                <div class="flex flex-col sm:flex-row gap-3" role="radiogroup" aria-labelledby="vps-period-heading">
                    @foreach($periods as $p)
                        @php
                            $copy = $periodCopy[$p['key']] ?? $fallbackCopy;
                            $dearer = (float) $p['renew'] > (float) $p['first'];
                        @endphp
                        <label class="flex-1 flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition"
                               :class="period === @js($p['key']) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'">
                            <input type="radio" name="period" value="{{ $p['key'] }}" x-model="period" @checked($p['key'] === $defaultPeriod)
                                   class="mt-1 w-4 h-4 shrink-0 accent-indigo-600">
                            <span class="min-w-0">
                                <span class="block font-semibold text-slate-900 dark:text-white">
                                    <x-bi :th="$p['label_th']" :en="$p['label_en']" />
                                </span>
                                <span class="block mt-1.5 text-[11px] font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                    <x-bi :th="$copy['first_th']" :en="$copy['first_en']" />
                                </span>
                                <span class="block text-xl font-bold text-slate-900 dark:text-white">{{ $p['first_display'] }}</span>
                                @if((int) $p['months'] > 1)
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        <x-bi th="เฉลี่ย" en="avg." /> <span class="whitespace-nowrap">{{ \App\Support\VpsPricing::format(ceil((float) $p['first'] / (int) $p['months'])) }}</span>/<x-bi th="เดือน" en="mo" />
                                    </span>
                                @endif
                                <span class="block mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    <x-bi th="ต่อไป" en="then" />
                                    <span class="font-semibold whitespace-nowrap {{ $dearer ? 'text-amber-600 dark:text-amber-400' : 'text-slate-700 dark:text-slate-200' }}">{{ $p['renew_display'] }}</span>/<x-bi :th="$p['unit_th']" :en="\App\Support\VpsPricing::unitLabel($p['key'], 'en')" />
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>

                {{-- เงินไม่พอ: บอกตั้งแต่ตอนเลือกรอบบิล ไม่ใช่หลังกรอกฟอร์มยาวจนจบ --}}
                <div x-show="short" @unless($shortDefault) x-cloak @endunless
                     class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-4 py-3 text-sm text-amber-900 dark:text-amber-200">
                    <p class="flex-1 min-w-0">
                        <x-bi th="ยอดในกระเป๋า" en="Your wallet has" />
                        <span class="font-semibold whitespace-nowrap">{{ $balanceText }}</span>
                        <x-bi th="ยังไม่พอสำหรับรอบบิลนี้" en="— not enough for this period" />
                    </p>
                    <a href="{{ route('user.wallet.index') }}"
                       class="shrink-0 inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-xs font-semibold transition">
                        <x-bi th="เติมเงิน" en="Top up" />
                    </a>
                </div>
                @error('period') <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- ══════════ ระบบปฏิบัติการ ══════════ --}}
            <div class="{{ $cardClass }}">
                <label for="template_id" class="block text-lg font-bold text-slate-900 dark:text-white mb-1">
                    <x-bi th="ระบบปฏิบัติการ" en="Operating system" />
                </label>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    <x-bi th="เลือก OS เปล่า หรือแบบที่ติดตั้งแอป/แผงควบคุมมาให้พร้อม ติดตั้งใหม่ภายหลังได้จากหน้าเซิร์ฟเวอร์"
                          en="A plain OS, or one with an app or control panel preinstalled. You can reinstall later." />
                </p>

                @if($templateMap === [])
                    <p class="rounded-xl border border-dashed border-slate-300 dark:border-slate-600 px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                        <x-bi th="ยังโหลดรายการระบบปฏิบัติการไม่ได้ — ลองรีโหลดหน้านี้" en="The OS list couldn't be loaded — try reloading the page." />
                    </p>
                @else
                    <select id="template_id" name="template_id" x-model.number="template" required
                            class="{{ $inputClass }} dark:[color-scheme:dark]">
                        @foreach($templates as $group => $list)
                            <optgroup label="{{ $groups[$group]['th'] ?? $group }} / {{ $groups[$group]['en'] ?? $group }}">
                                @foreach($list as $t)
                                    <option value="{{ $t['id'] }}" @selected($selectedTemplate === (int) $t['id'])>{{ $t['name'] }}{{ ! empty($t['licensed']) ? ' (ต้องซื้อไลเซนส์แยก)' : '' }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>

                    <p class="mt-2 min-h-[1.25rem] text-sm text-slate-500 dark:text-slate-400 break-words"
                       x-text="templateInfo ? templateInfo.description : ''">{{ $templateMap[$selectedTemplate]['description'] ?? '' }}</p>

                    <div x-show="templateInfo && templateInfo.licensed" @unless(! empty($templateMap[$selectedTemplate]['licensed'])) x-cloak @endunless
                         class="mt-3 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-900 dark:text-amber-200 px-4 py-3 text-sm">
                        <x-bi th="แผงควบคุมนี้ต้องซื้อไลเซนส์แยกจากผู้พัฒนา ค่าเช่าเครื่องไม่รวมค่าไลเซนส์"
                              en="This panel needs a licence bought separately from its developer — it is not included in the rent." />
                    </div>
                @endif
                @error('template_id') <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- ══════════ ศูนย์ข้อมูล ══════════ --}}
            <div class="{{ $cardClass }}">
                <h2 id="vps-dc-heading" class="text-lg font-bold text-slate-900 dark:text-white mb-1">
                    <x-bi th="ศูนย์ข้อมูล" en="Server location" />
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                    <x-bi th="เลือกที่ใกล้ผู้ใช้ของคุณที่สุด ตัวที่มีป้ายแนะนำอยู่ใกล้ไทยที่สุด"
                          en="Pick the one closest to your users — the recommended ones are nearest Thailand." />
                </p>

                @if($dcMap === [])
                    <p class="rounded-xl border border-dashed border-slate-300 dark:border-slate-600 px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                        <x-bi th="ยังโหลดรายการศูนย์ข้อมูลไม่ได้ — ลองรีโหลดหน้านี้" en="The location list couldn't be loaded — try reloading the page." />
                    </p>
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5" role="radiogroup" aria-labelledby="vps-dc-heading">
                    @foreach($dataCenters as $dc)
                        <label class="flex items-start gap-3 p-3.5 rounded-xl border-2 cursor-pointer transition"
                               :class="dataCenter === @js((int) $dc['id']) ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'">
                            <input type="radio" name="data_center_id" value="{{ $dc['id'] }}" x-model.number="dataCenter" required
                                   @checked($selectedDc === (int) $dc['id'])
                                   class="mt-1 w-4 h-4 shrink-0 accent-indigo-600">
                            @if($dc['flag'] !== '')
                                <span class="text-2xl leading-none shrink-0" aria-hidden="true">{{ $dc['flag'] }}</span>
                            @endif
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold text-slate-900 dark:text-white break-words">{{ $dc['city'] !== '' ? $dc['city'] : $dc['country_th'] }}</span>
                                <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $dc['country_th'] }}</span>
                                @if(! empty($dc['recommended']))
                                    <span class="inline-block mt-1.5 px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 text-[11px] font-semibold">
                                        <x-bi th="แนะนำสำหรับผู้ใช้ในไทย" en="Best for Thailand" />
                                    </span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('data_center_id') <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            {{-- ══════════ ตั้งค่าเซิร์ฟเวอร์ ══════════ --}}
            <div class="{{ $cardClass }} space-y-6">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                    <x-bi th="ตั้งค่าเซิร์ฟเวอร์" en="Server settings" />
                </h2>

                {{-- ชื่อโฮสต์ --}}
                <div>
                    <label for="hostname" class="{{ $labelClass }}">
                        <x-bi th="ชื่อโฮสต์" en="Hostname" /> <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="hostname" name="hostname" x-model="hostname" value="{{ $hostnameValue }}"
                           required maxlength="253" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" inputmode="url"
                           placeholder="server.example.com"
                           class="{{ $inputClass }} font-mono">
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                        <x-bi th="เช่น server.example.com — ใช้ a-z 0-9 ขีดกลาง และจุด เป็นแค่ชื่อเครื่อง ไม่ต้องมีโดเมนจริงก็ได้ และเปลี่ยนทีหลังได้"
                              en="e.g. server.example.com — a-z, 0-9, hyphens and dots. It only names the machine, needn't resolve, and can be changed later." />
                    </p>
                    <p x-show="hostname.trim() !== '' && !hostnameOk" x-cloak class="mt-1 text-xs text-red-600 dark:text-red-400">
                        รูปแบบไม่ถูกต้อง ต้องมีจุดอย่างน้อยหนึ่งจุด และขึ้นต้น/ลงท้ายแต่ละส่วนด้วยตัวอักษรหรือตัวเลข / Not a valid hostname
                    </p>
                    @error('hostname') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- รหัสผ่าน root — ไม่เติมกลับจาก old() เด็ดขาด --}}
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                        <label for="root_password" class="text-sm font-medium text-slate-700 dark:text-slate-300">
                            <x-bi th="รหัสผ่าน root" en="Root password" /> <span class="text-red-500">*</span>
                        </label>
                        <button type="button" @click="generatePassword()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30 hover:bg-indigo-100 dark:hover:bg-indigo-500/20 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.5 15a7 7 0 0012.2 2.5M18.5 9A7 7 0 006.3 6.5"/></svg>
                            <x-bi th="สุ่มรหัสผ่าน" en="Generate" />
                        </button>
                    </div>
                    <div class="relative">
                        <input id="root_password" name="root_password" type="password" :type="showPassword ? 'text' : 'password'"
                               x-ref="password" x-model="password"
                               required minlength="12" maxlength="128"
                               autocomplete="new-password" autocapitalize="off" autocorrect="off" spellcheck="false"
                               aria-describedby="vps-password-rules vps-password-note"
                               class="{{ $inputClass }} pr-20 font-mono">
                        <div class="absolute inset-y-0 right-0 flex items-center gap-0.5 pr-1.5">
                            <button type="button" @click="copyPassword()" x-show="password !== ''" x-cloak
                                    :aria-label="copied ? 'คัดลอกแล้ว / Copied' : 'คัดลอกรหัสผ่าน / Copy password'"
                                    :title="copied ? 'คัดลอกแล้ว / Copied' : 'คัดลอกรหัสผ่าน / Copy password'"
                                    class="p-2 rounded-md text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                                <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 15V5a2 2 0 012-2h10"/></svg>
                                <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            <button type="button" @click="showPassword = !showPassword"
                                    aria-label="แสดงรหัสผ่าน / Show password"
                                    :aria-label="showPassword ? 'ซ่อนรหัสผ่าน / Hide password' : 'แสดงรหัสผ่าน / Show password'"
                                    class="p-2 rounded-md text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12S6 5 12 5s9.5 7 9.5 7-3.5 7-9.5 7S2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3" stroke-width="1.8"/></svg>
                                <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8M9.9 5.2A9.9 9.9 0 0112 5c6 0 9.5 7 9.5 7a17.4 17.4 0 01-3.2 4.2M6.6 6.6C4 8.3 2.5 12 2.5 12S6 19 12 19a9.6 9.6 0 004.2-.9"/></svg>
                            </button>
                        </div>
                    </div>

                    <ul id="vps-password-rules" class="mt-2.5 grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                        <li class="flex items-center gap-1.5 transition" :class="pwLength ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'">
                            <svg x-show="pwLength" x-cloak class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <svg x-show="!pwLength" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="7" stroke-width="2"/></svg>
                            <x-bi th="อย่างน้อย 12 ตัวอักษร" en="12+ characters" />
                        </li>
                        <li class="flex items-center gap-1.5 transition" :class="pwUpper ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'">
                            <svg x-show="pwUpper" x-cloak class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <svg x-show="!pwUpper" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="7" stroke-width="2"/></svg>
                            <x-bi th="ตัวพิมพ์ใหญ่" en="Uppercase" />
                        </li>
                        <li class="flex items-center gap-1.5 transition" :class="pwLower ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'">
                            <svg x-show="pwLower" x-cloak class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <svg x-show="!pwLower" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="7" stroke-width="2"/></svg>
                            <x-bi th="ตัวพิมพ์เล็ก" en="Lowercase" />
                        </li>
                        <li class="flex items-center gap-1.5 transition" :class="pwDigit ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400'">
                            <svg x-show="pwDigit" x-cloak class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <svg x-show="!pwDigit" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="7" stroke-width="2"/></svg>
                            <x-bi th="ตัวเลข" en="A number" />
                        </li>
                    </ul>

                    <p id="vps-password-note" class="mt-3 flex items-start gap-2 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-3 py-2 text-xs text-amber-900 dark:text-amber-200">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M8 11V7a4 4 0 118 0v4"/></svg>
                        <x-bi th="เราไม่เก็บรหัสผ่านนี้ และไม่ส่งทางอีเมล — จดไว้ให้ดี" en="We don't keep this password or email it to you — write it down." />
                    </p>
                    @if($retrying)
                        <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">
                            <x-bi th="เพื่อความปลอดภัย รหัสผ่านที่กรอกไว้ครั้งก่อนถูกล้างแล้ว กรุณาตั้งใหม่อีกครั้ง" en="For your safety the password you typed was cleared — please set it again." />
                        </p>
                    @endif
                    <p x-show="generateError" x-cloak class="mt-2 text-xs text-red-600 dark:text-red-400">
                        เบราว์เซอร์นี้สุ่มรหัสผ่านให้ไม่ได้ กรุณาตั้งเอง / This browser can't generate one — please type your own.
                    </p>
                    @error('root_password') <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- SSH key (ไม่บังคับ) --}}
                <div>
                    <button type="button" @click="showKey = !showKey" :aria-expanded="showKey" aria-controls="vps-public-key-panel"
                            class="flex items-start gap-2 text-left text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                        <svg class="w-4 h-4 mt-0.5 shrink-0 transition-transform duration-200" :class="showKey ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span class="min-w-0">
                            <x-bi th="ใช้ SSH key (ไม่บังคับ)" en="Use an SSH key (optional)" />
                            <span x-show="!showKey && publicKey.trim() !== ''" x-cloak class="ml-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">✓ ใส่แล้ว / added</span>
                        </span>
                    </button>
                    <div id="vps-public-key-panel" x-show="showKey" @unless($formConfig['showKey']) x-cloak @endunless class="mt-3">
                        <label for="public_key" class="sr-only">SSH public key</label>
                        <textarea id="public_key" name="public_key" x-model="publicKey" rows="3" maxlength="4096"
                                  autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false"
                                  placeholder="ssh-ed25519 AAAA… you@laptop"
                                  class="{{ $inputClass }} font-mono">{{ $publicKeyValue }}</textarea>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                            <x-bi th="วาง public key (ไฟล์ .pub) หนึ่งบรรทัด ใช้เข้าเครื่องได้โดยไม่ต้องพิมพ์รหัสผ่าน — ยังต้องตั้งรหัสผ่าน root ด้านบนด้วย"
                                  en="Paste one public key (the .pub file) to log in without a password — the root password above is still required." />
                        </p>
                        <p x-show="!keyOk" x-cloak class="mt-1 text-xs text-red-600 dark:text-red-400">
                            รูปแบบ key ไม่ถูกต้อง ต้องขึ้นต้นด้วย ssh-ed25519, ssh-rsa หรือ ecdsa-… / Not a valid public key
                        </p>
                        @error('public_key') <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- ══════════ ตัวเลือก ══════════ --}}
            <div class="{{ $cardClass }}">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white mb-4">
                    <x-bi th="ตัวเลือก" en="Options" />
                </h2>
                <input type="hidden" name="auto_renew" value="0">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="auto_renew" value="1" @checked($autoRenewOn)
                           class="mt-1 w-4 h-4 shrink-0 accent-indigo-600">
                    <span>
                        <span class="block font-medium text-slate-900 dark:text-white">
                            <x-bi th="ต่ออายุอัตโนมัติ" en="Renew automatically" />
                        </span>
                        <span class="block text-sm text-slate-500 dark:text-slate-400">
                            <x-bi th="ตัดจากกระเป๋าเงินก่อนหมดอายุ {{ \App\Support\VpsSettings::chargeDays() }} วัน แจ้งล่วงหน้าทุกครั้ง ปิดได้ตลอด"
                                  en="Charged from your wallet {{ \App\Support\VpsSettings::chargeDays() }} days before expiry. We always tell you first, and you can switch it off any time." />
                        </span>
                    </span>
                </label>
            </div>

            {{-- ══════════ สรุปและยืนยัน ══════════ --}}
            <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5 sm:p-6">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                    <x-bi th="สรุปและยืนยัน" en="Review and confirm" />
                </h2>

                <dl class="mt-4 divide-y divide-slate-100 dark:divide-slate-700/60 text-sm">
                    <div class="flex items-start justify-between gap-4 py-2.5">
                        <dt class="shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="แพ็กเกจ" en="Plan" /></dt>
                        <dd class="min-w-0 text-right">
                            <span class="block font-semibold text-slate-900 dark:text-white break-words">{{ $plan->name }}</span>
                            <span class="block text-xs text-slate-500 dark:text-slate-400">{{ implode(' · ', $specChips) }}</span>
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-2.5">
                        <dt class="shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="รอบบิล" en="Billing" /></dt>
                        <dd class="min-w-0 text-right font-medium text-slate-900 dark:text-white">
                            @foreach($periods as $p)
                                <span x-show="period === @js($p['key'])" @if($p['key'] !== $defaultPeriod) x-cloak @endif>
                                    <x-bi :th="$p['label_th']" :en="$p['label_en']" />
                                </span>
                            @endforeach
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-2.5">
                        <dt class="shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="ระบบปฏิบัติการ" en="OS" /></dt>
                        <dd class="min-w-0 text-right font-medium text-slate-900 dark:text-white break-words"
                            x-text="templateInfo ? templateInfo.name : '—'">{{ $templateMap[$selectedTemplate]['name'] ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-2.5">
                        <dt class="shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="ศูนย์ข้อมูล" en="Location" /></dt>
                        <dd class="min-w-0 text-right font-medium text-slate-900 dark:text-white break-words"
                            x-text="dataCenterInfo ? dataCenterInfo.label : '—'">{{ $dcMap[$selectedDc]['label'] ?? '—' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 py-2.5">
                        <dt class="shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="ชื่อโฮสต์" en="Hostname" /></dt>
                        <dd class="min-w-0 text-right font-mono text-slate-900 dark:text-white break-all"
                            x-text="hostname.trim() || '—'">{{ $hostnameValue !== '' ? $hostnameValue : '—' }}</dd>
                    </div>
                </dl>

                {{-- เงื่อนไข --}}
                <div class="mt-5 pt-5 border-t border-slate-200 dark:border-slate-700">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="accept_terms" value="1" required x-model="accepted" @checked($acceptedOld)
                               class="mt-1 w-4 h-4 shrink-0 accent-indigo-600">
                        <span class="text-sm font-medium text-slate-900 dark:text-white">
                            <x-bi th="ข้าพเจ้ายอมรับเงื่อนไขการเช่าต่อไปนี้" en="I accept these rental terms" />
                        </span>
                    </label>
                    <ul class="mt-2.5 ml-7 space-y-2 text-sm text-slate-600 dark:text-slate-400">
                        @foreach($terms as $term)
                            <li class="flex gap-2">
                                <span class="mt-2 w-1.5 h-1.5 rounded-full bg-slate-400 dark:bg-slate-500 shrink-0" aria-hidden="true"></span>
                                <span class="min-w-0">
                                    <span class="block">{{ $term['th'] }}</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $term['en'] }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    @error('accept_terms') <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                {{-- ยอดเงินไม่พอสำหรับรอบบิลที่เลือก --}}
                <div x-show="short" @unless($shortDefault) x-cloak @endunless
                     class="mt-5 rounded-2xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 p-5">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-amber-900 dark:text-amber-200 mb-1">
                                <x-bi th="ยอดเงินในกระเป๋าไม่พอ" en="Not enough in your wallet" />
                            </p>
                            @foreach($periods as $p)
                                <p x-show="period === @js($p['key'])" @if($p['key'] !== $defaultPeriod) x-cloak @endif
                                   class="text-sm text-amber-800 dark:text-amber-300/90 mb-3">
                                    <x-bi th="ต้องใช้" en="Needs" /> <span class="font-semibold whitespace-nowrap">{{ $p['first_display'] }}</span> ·
                                    <x-bi th="มีอยู่" en="you have" /> <span class="font-semibold whitespace-nowrap">{{ $balanceText }}</span> ·
                                    <x-bi th="ขาดอีก" en="short by" /> <span class="font-semibold whitespace-nowrap">{{ $shortfalls[$p['key']] ?? '' }}</span>
                                </p>
                            @endforeach
                            <a href="{{ route('user.wallet.index') }}"
                               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold shadow-sm transition">
                                <x-bi th="เติมเงิน" en="Top up" />
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-5 border-t border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">
                    <div class="min-w-0">
                        @foreach($periods as $p)
                            @php
                                $copy = $periodCopy[$p['key']] ?? $fallbackCopy;
                                $dearer = (float) $p['renew'] > (float) $p['first'];
                            @endphp
                            <div x-show="period === @js($p['key'])" @if($p['key'] !== $defaultPeriod) x-cloak @endif>
                                <p class="text-sm text-slate-500 dark:text-slate-400">
                                    <x-bi :th="'ยอดชำระวันนี้ (' . $copy['first_th'] . ')'" :en="'Due today (' . \Illuminate\Support\Str::lcfirst($copy['first_en']) . ')'" />
                                </p>
                                <p class="text-3xl font-bold text-slate-900 dark:text-white">{{ $p['first_display'] }}</p>
                                <p class="text-xs {{ $dearer ? 'text-amber-700 dark:text-amber-300' : 'text-slate-500 dark:text-slate-400' }}">
                                    <x-bi th="รอบถัดไป" en="Next term" />
                                    <span class="whitespace-nowrap">{{ $p['renew_display'] }}</span>/<x-bi :th="$p['unit_th']" :en="\App\Support\VpsPricing::unitLabel($p['key'], 'en')" />
                                </p>
                            </div>
                        @endforeach
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            <x-bi th="หักจากกระเป๋าเงิน คงเหลือ" en="From your wallet — balance" /> <span class="whitespace-nowrap">{{ $balanceText }}</span>
                        </p>
                    </div>

                    <div class="w-full sm:w-auto sm:max-w-xs">
                        {{-- กดซ้ำไม่ได้: ปุ่มถูกปิดทันทีที่ส่ง และฝั่งเซิร์ฟเวอร์ยังกันซ้ำด้วย order_token อีกชั้น --}}
                        <button type="submit"
                                @disabled($serverBlocked)
                                x-bind:disabled="submitting || !canSubmit"
                                class="w-full px-8 py-4 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:scale-[1.02] active:scale-[0.99] transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                            <span x-show="!submitting">
                                <x-bi th="ยืนยันและสั่งเช่า" en="Confirm and rent" />
                            </span>
                            <span x-show="submitting" x-cloak class="flex items-center justify-center gap-2">
                                <svg class="animate-spin w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <x-bi th="กำลังสั่งเช่า อย่าปิดหน้านี้" en="Ordering — don't close this page" />
                            </span>
                        </button>

                        {{-- บอกว่าทำไมปุ่มยังกดไม่ได้ ปุ่มที่ปิดเฉย ๆ โดยไม่มีเหตุผลคือทางตัน --}}
                        <div x-show="!submitting && blockers.length > 0" x-cloak class="mt-3 text-xs text-amber-700 dark:text-amber-300">
                            <p class="font-semibold mb-1">ยังต้องทำก่อนสั่งเช่า / Still to do</p>
                            <ul class="space-y-1">
                                <template x-for="item in blockers" :key="item">
                                    <li class="flex items-start gap-1.5"><span aria-hidden="true">•</span><span x-text="item"></span></li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function vpsOrderForm(config) {
    // รูปแบบเดียวกับที่ VpsController::store ตรวจ — ตรวจซ้ำที่นี่เพราะทุกครั้ง
    // ที่เซิร์ฟเวอร์ตีกลับ ช่องรหัสผ่าน root จะว่างและลูกค้าต้องตั้งใหม่
    const HOSTNAME = /^(?=.{1,253}$)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)+$/i;
    const SSH_KEY = /^(ssh-(rsa|ed25519|dss)|ecdsa-sha2-nistp(256|384|521)|sk-ssh-ed25519@openssh\.com|sk-ecdsa-sha2-nistp256@openssh\.com) [A-Za-z0-9+\/=]+( [^\r\n]{0,200})?$/;

    // ไม่มีตัวที่หน้าตาคล้ายกัน (0/O, 1/l/I) — รหัสนี้ถูกอ่านจากจอแล้วพิมพ์ลง terminal
    const UPPER = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const LOWER = 'abcdefghijkmnpqrstuvwxyz';
    const DIGITS = '23456789';
    const LENGTH = 16;

    return {
        period: config.period,
        firsts: config.firsts || {},
        balance: Number(config.balance) || 0,
        paused: !!config.paused,
        catalogReady: !!config.catalogReady,
        template: config.template,
        templates: config.templates || {},
        dataCenter: config.dataCenter,
        dataCenters: config.dataCenters || {},
        hostname: config.hostname || '',
        publicKey: config.publicKey || '',
        showKey: !!config.showKey,
        accepted: !!config.accepted,

        password: '',
        showPassword: false,
        copied: false,
        copyTimer: null,
        generateError: false,
        submitting: false,

        // ราคารอบแรกของรอบบิลที่เลือก — ตัวเลขเดียวกับที่ลูกค้าเห็นบนหน้า
        get expectedAmount() {
            const amount = this.firsts[this.period];
            return amount === undefined ? '' : amount;
        },
        // เทียบแบบเดียวกับ Wallet::hasSufficientBalance (balance >= amount)
        get short() {
            const amount = this.firsts[this.period];
            return amount === undefined || this.balance < amount;
        },
        get templateInfo() {
            return this.templates[this.template] || null;
        },
        get dataCenterInfo() {
            return this.dataCenters[this.dataCenter] || null;
        },

        // กฎเดียวกับ Password::min(12)->mixedCase()->numbers() — นับเป็นตัวอักษร ไม่ใช่ byte
        get pwLength() { return Array.from(this.password).length >= 12; },
        get pwUpper() { return /\p{Lu}/u.test(this.password); },
        get pwLower() { return /\p{Ll}/u.test(this.password); },
        get pwDigit() { return /\p{N}/u.test(this.password); },
        get pwOk() {
            return this.pwLength && this.pwUpper && this.pwLower && this.pwDigit
                && Array.from(this.password).length <= 128;
        },
        get hostnameOk() { return HOSTNAME.test(this.hostname.trim()); },
        get keyOk() {
            const key = this.publicKey.trim();
            return key === '' || SSH_KEY.test(key);
        },

        get blockers() {
            const list = [];
            if (this.paused) list.push('ระบบสั่งซื้อปิดชั่วคราว / Ordering is paused');
            if (!this.catalogReady) list.push('ตัวเลือกเซิร์ฟเวอร์ยังโหลดไม่ได้ — รีโหลดหน้านี้ / Reload the page');
            if (this.short) list.push('ยอดเงินในกระเป๋าไม่พอสำหรับรอบบิลนี้ / Not enough in your wallet');
            if (this.catalogReady && !this.templateInfo) list.push('เลือกระบบปฏิบัติการ / Choose an OS');
            if (this.catalogReady && !this.dataCenterInfo) list.push('เลือกศูนย์ข้อมูล / Choose a location');
            if (!this.hostnameOk) list.push('ตั้งชื่อโฮสต์ให้ถูกรูปแบบ / Fix the hostname');
            if (!this.pwOk) list.push('ตั้งรหัสผ่าน root ให้ครบทุกข้อ / Set a root password that meets every rule');
            if (!this.keyOk) list.push('แก้หรือลบ SSH key ที่ไม่ถูกรูปแบบ / Fix or clear the SSH key');
            if (!this.accepted) list.push('ยอมรับเงื่อนไขการเช่า / Accept the rental terms');
            return list;
        },
        get canSubmit() {
            return this.blockers.length === 0;
        },

        // สุ่มแบบไม่เอนเอียง: ทิ้งค่าที่เกินช่วงที่หารลงตัว แทนการใช้ % ตรง ๆ
        randomInt(n) {
            const buf = new Uint32Array(1);
            const limit = Math.floor(0x100000000 / n) * n;
            do {
                window.crypto.getRandomValues(buf);
            } while (buf[0] >= limit);
            return buf[0] % n;
        },

        generatePassword() {
            if (!window.crypto || typeof window.crypto.getRandomValues !== 'function') {
                this.generateError = true;
                return;
            }
            this.generateError = false;

            const all = UPPER + LOWER + DIGITS;
            const pick = (set) => set[this.randomInt(set.length)];

            // รับประกันว่ามีครบทั้งตัวใหญ่ ตัวเล็ก และตัวเลข แล้วสลับตำแหน่ง
            const chars = [pick(UPPER), pick(LOWER), pick(DIGITS)];
            while (chars.length < LENGTH) chars.push(pick(all));
            for (let i = chars.length - 1; i > 0; i--) {
                const j = this.randomInt(i + 1);
                [chars[i], chars[j]] = [chars[j], chars[i]];
            }

            this.password = chars.join('');
            this.showPassword = true;
            this.copied = false;
        },

        async copyPassword() {
            if (this.password === '') return;
            try {
                await navigator.clipboard.writeText(this.password);
                this.copied = true;
                clearTimeout(this.copyTimer);
                this.copyTimer = setTimeout(() => { this.copied = false; }, 2000);
            } catch (e) {
                // คลิปบอร์ดถูกบล็อก: เปิดให้เห็นแล้วเลือกข้อความไว้ ให้กด Ctrl+C เองได้
                this.showPassword = true;
                this.$nextTick(() => this.$refs.password && this.$refs.password.select());
            }
        },

        submit(event) {
            if (this.submitting || !this.canSubmit) {
                event.preventDefault();
                return;
            }
            this.submitting = true;
        },
    };
}
</script>
@endpush
