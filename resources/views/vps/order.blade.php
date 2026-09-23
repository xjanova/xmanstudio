@extends($publicLayout ?? 'layouts.app')

@section('title', 'เช่า ' . $plan->name . ' | XMAN Studio')
@section('meta_description', 'สั่งเช่า ' . $plan->name . ' — เลือกรอบบิล ระบบปฏิบัติการ และศูนย์ข้อมูล ชำระจากกระเป๋าเงิน เห็นราคารอบถัดไปก่อนยืนยันทุกครั้ง')

@section('content')
{{--
    ฟอร์มสั่งเช่า VPS — ขั้นตอนอยู่ซ้าย สรุปรายการติดจออยู่ขวา (จอ lg ขึ้นไป)
    จอเล็กเรียงเป็นคอลัมน์เดียว สรุปอยู่ท้ายขั้นตอน ทั้งหมดอยู่ใน form เดียวกัน

    - expected_amount คือราคารอบแรกที่ลูกค้าเห็นบนหน้านี้ ฝั่งเซิร์ฟเวอร์เทียบกับ
      ราคาจริงอีกครั้ง ถ้าราคาขยับระหว่างเปิดหน้าค้างไว้ ระบบจะถามใหม่ ไม่ตัดเงิน
      ตัวเลขที่ลูกค้าไม่เคยเห็น
    - ราคาวันนี้และราคารอบถัดไป (ต่ออายุ) ต้องเห็นคู่กันเสมอ ทั้งบนการ์ดรอบบิล
      และในสรุปรายการ — กฎของเจ้าของ ห้ามตัดราคาต่ออายุออก
    - Alpine แค่สลับว่าโชว์รอบบิลไหน ตัวเลขเงินทุกตัว (รวมยอดคงเหลือหลังชำระ)
      จัดรูปแบบมาจาก PHP ไม่คิดเงินใน JavaScript
    - รหัสผ่าน root ไม่ถูกเติมกลับเด็ดขาด (ไม่อ่าน old) ทุกครั้งที่เซิร์ฟเวอร์ตีกลับ
      ลูกค้าต้องตั้งใหม่ จึงตรวจกฎเดียวกับฝั่งเซิร์ฟเวอร์ไว้ที่นี่ก่อนส่ง
    - ตัวเลือกระบบปฏิบัติการเป็นการ์ด radio จริง (name="template_id") เรนเดอร์ด้วย
      Blade พร้อม checked จากฝั่งเซิร์ฟเวอร์ ไม่ใช่ x-for — ค่าที่เลือกจึงถูกต้องตั้งแต่
      ก่อน Alpine เริ่ม และ radio ที่ถูกซ่อน (แท็บอื่น / ถูกกรองออก) ยังถูกส่งไปตามปกติ
    - ธงประเทศเป็น SVG (vps.partials.flag) ไม่ใช่อีโมจิ: Windows ไม่วาดอีโมจิธง
    - auto_renew มี hidden "0" นำหน้า: checkbox ที่ไม่ติ๊กจะไม่ถูกส่งมาเลย และ
      ฝั่งเซิร์ฟเวอร์อ่านค่าที่หายไปว่า "เปิด" — ลูกค้าจะปิดไม่ได้
    - ส่งซ้ำหลังถูกตีกลับใช้ order_token เดิม ถ้าครั้งก่อนตัดเงินไปแล้วแต่เกิด
      ข้อผิดพลาดที่ไม่คาดคิด ครั้งนี้จะพาไปที่เครื่องเดิม ไม่ซื้อเครื่องที่สอง
    - ข้อความ flash จาก session แสดงที่ layout อยู่แล้ว หน้านี้จึงไม่แสดงซ้ำ
    - ห้ามเอ่ยชื่อผู้ให้บริการต้นทางที่ใดในหน้านี้
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
    $afterPay = $periods->mapWithKeys(fn ($p) => [$p['key'] => $baht(max(0.0, (float) $balance - (float) $p['first']))])->all();
    $shortDefault = (float) $balance < ($firstByPeriod[$defaultPeriod] ?? INF);

    // ระบบปฏิบัติการ: แผนที่ id → ข้อมูล ให้ Alpine ใช้กรอง แสดงคำอธิบาย และสรุปรายการ
    // คีย์ค้นหาคือชื่อที่ตัดช่องว่าง จุด ขีด ออก (JS ทำคำค้นด้วยกฎเดียวกัน)
    $groups = \App\Support\VpsCatalog::GROUPS;
    $normalize = fn (string $text) => (string) preg_replace('/[\s._\-\/]+/u', '', mb_strtolower($text));
    $templateMap = [];
    $templateCards = [];
    foreach ($templates as $group => $list) {
        foreach ($list as $t) {
            $tId = (int) $t['id'];
            $tName = (string) $t['name'];
            $withAt = stripos($tName, ' with ');

            $templateMap[$tId] = [
                'name' => $tName,
                'description' => (string) ($t['description'] ?? ''),
                'licensed' => ! empty($t['licensed']),
                'group' => (string) $group,
                'search' => $normalize($tName),
            ];
            // "Ubuntu 24.04 with Docker" → หัวข้อ "Docker" บรรทัดรอง "Ubuntu 24.04"
            // สิบกว่าการ์ดที่ขึ้นต้นด้วย "Ubuntu 24.04 with" เหมือนกันหมดอ่านไม่ออก
            $templateCards[$group][] = [
                'id' => $tId,
                'name' => $tName,
                'title' => $withAt !== false ? trim(substr($tName, $withAt + 6)) : $tName,
                'base' => $withAt !== false ? trim(substr($tName, 0, $withAt)) : '',
                'licensed' => ! empty($t['licensed']),
                'search' => $templateMap[$tId]['search'],
            ];
        }
    }
    $selectedTemplate = (int) old('template_id', $defaultTemplate);
    if (! isset($templateMap[$selectedTemplate])) {
        $selectedTemplate = (int) (array_key_first($templateMap) ?? 0);
    }
    $groupKeys = array_keys($templateCards);
    $selectedGroup = $templateMap[$selectedTemplate]['group'] ?? ($groupKeys[0] ?? 'os');
    // สี่แท็บเรียงแถวเดียวเมื่อคอลัมน์ซ้ายกว้างพอ จอ lg ที่มีสรุปด้านขวาเหลือที่แคบ ใช้ 2×2
    $tabCols = match (count($groupKeys)) {
        1 => 'grid-cols-1',
        2 => 'grid-cols-2',
        3 => 'grid-cols-2 sm:grid-cols-3',
        default => 'grid-cols-2 md:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4',
    };

    $dcMap = [];
    $dcRows = [];
    foreach ($dataCenters as $dc) {
        $dcMap[(int) $dc['id']] = ['label' => \App\Support\VpsCatalog::describeDataCenter($dc)];
        $dcRows[(int) $dc['id']] = $dc;
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
    $specList = array_values(array_filter([
        ['cpu', $plan->cpus . ' vCPU'],
        $known($memory) ? ['ram', $memory . ' RAM'] : null,
        $known($disk) ? ['disk', $disk . ' NVMe SSD'] : null,
        $known($bandwidth) ? ['bandwidth', $bandwidth . ' แบนด์วิดท์'] : null,
        $plan->network_mbps ? ['network', $plan->network_mbps . ' Mbps'] : null,
    ]));
    $specChips = array_column($specList, 1);

    $serverBlocked = $isPaused || ! $catalogReady || $shortDefault;
    $chargeDays = \App\Support\VpsSettings::chargeDays();

    // สถานะเริ่มต้นของเลขขั้น (เลข → เครื่องหมายถูก) ให้ตรงกับที่ Alpine จะคำนวณ
    // ตอนเริ่ม หน้าจึงไม่กะพริบ ขั้นที่ 4 ไม่เคยครบตอนเปิดหน้า: รหัสผ่านไม่ถูกเติมกลับ
    $stepDoneInitially = [
        1 => isset($firstByPeriod[$defaultPeriod]),
        2 => isset($templateMap[$selectedTemplate]),
        3 => isset($dcMap[$selectedDc]),
        4 => false,
        5 => true,
    ];

    $inputClass = 'w-full min-w-0 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900/80 text-slate-900 dark:text-white px-3.5 py-2.5 text-base sm:text-sm shadow-sm placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30 transition';
    $labelClass = 'block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5';
    $cardClass = 'scroll-mt-24 rounded-2xl bg-white dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/70 shadow-sm dark:shadow-none p-5 sm:p-6';
    $stepTitleClass = 'text-lg font-bold text-slate-900 dark:text-white leading-tight';
    $stepHintClass = 'mt-1 text-sm text-slate-600 dark:text-slate-400';

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
        'groups' => $groupKeys,
        'osTab' => $selectedGroup,
        'dataCenter' => $selectedDc,
        'dataCenters' => (object) $dcMap,
        'hostname' => $hostnameValue,
        'publicKey' => $publicKeyValue,
        'showKey' => $publicKeyValue !== '' || $errors->has('public_key'),
        'autoRenew' => $autoRenewOn,
        'accepted' => $acceptedOld,
    ];
@endphp

<div class="bg-gray-50 dark:bg-slate-900 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10"
         x-data="vpsOrderForm(@js($formConfig))"
         @pageshow.window="if ($event.persisted) submitting = false">

        <a href="{{ route('vps.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 mb-5 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <x-bi th="ดูแพ็กเกจทั้งหมด" en="All plans" />
        </a>

        @if ($errors->any())
            <div class="rounded-2xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-5 py-4 mb-6 text-sm" role="alert">
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

        <form method="POST" action="{{ route('vps.order.store', $plan->slug) }}" @submit="submit($event)"
              class="lg:grid lg:grid-cols-[minmax(0,1fr)_22rem] xl:grid-cols-[minmax(0,1fr)_24rem] lg:items-start gap-6 xl:gap-8">
            @csrf
            <input type="hidden" name="order_token" value="{{ $formToken }}">
            <input type="hidden" name="expected_amount" value="{{ $firstByPeriod[$defaultPeriod] ?? '' }}" x-bind:value="expectedAmount">

            <div class="min-w-0 space-y-5">

                {{-- ══════════ แพ็กเกจที่กำลังเช่า ══════════ --}}
                <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-6 sm:p-7 shadow-xl ring-1 ring-white/10">
                    <x-page-art art="hero-vps" :opacity="30" :scrim="false" />
                    <div class="absolute -top-24 -right-16 w-72 h-72 rounded-full bg-indigo-500/25 blur-3xl pointer-events-none" aria-hidden="true"></div>
                    <div class="relative flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">
                        <div class="min-w-0">
                            <p class="inline-flex items-center gap-2 text-indigo-200/90 text-xs font-semibold [&_.bi-en]:uppercase [&_.bi-en]:tracking-[0.2em]">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shadow-[0_0_10px_2px_rgba(52,211,153,0.6)]" aria-hidden="true"></span>
                                <x-bi th="กำลังเช่า" en="Renting" />
                            </p>
                            <h1 class="mt-2 text-2xl sm:text-3xl font-bold tracking-tight break-words">{{ $plan->name }}</h1>
                            <ul class="mt-4 flex flex-wrap gap-2 text-xs">
                                @foreach($specList as [$specIcon, $specText])
                                    <li class="inline-flex items-center gap-1.5 pl-2 pr-2.5 py-1 rounded-lg bg-white/10 border border-white/10 backdrop-blur-sm whitespace-nowrap">
                                        <svg class="w-3.5 h-3.5 text-indigo-200" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
                                            @switch($specIcon)
                                                @case('cpu')
                                                    <rect x="6.5" y="6.5" width="11" height="11" rx="2"/><path d="M9.5 3.5v3M14.5 3.5v3M9.5 17.5v3M14.5 17.5v3M3.5 9.5h3M3.5 14.5h3M17.5 9.5h3M17.5 14.5h3"/>
                                                    @break
                                                @case('ram')
                                                    <rect x="3" y="7" width="18" height="9" rx="1.5"/><path d="M7 16v3M11 16v3M15 16v3M7 10.5h2M11 10.5h2M15 10.5h2"/>
                                                    @break
                                                @case('disk')
                                                    <ellipse cx="12" cy="6.5" rx="7.5" ry="2.8"/><path d="M4.5 6.5v11c0 1.5 3.4 2.8 7.5 2.8s7.5-1.3 7.5-2.8v-11M4.5 12c0 1.5 3.4 2.8 7.5 2.8s7.5-1.3 7.5-2.8"/>
                                                    @break
                                                @case('bandwidth')
                                                    <path d="M7 4v16M7 4 3.5 7.5M7 4l3.5 3.5M17 20V4M17 20l-3.5-3.5M17 20l3.5-3.5"/>
                                                    @break
                                                @default
                                                    <path d="M13 3 5 13.5h6l-1 7.5 8-10.5h-6z"/>
                                            @endswitch
                                        </svg>
                                        {{ $specText }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        {{-- จอเล็กเห็นราคาตั้งแต่บนสุด (สรุปรายการอยู่ท้ายหน้า) จอใหญ่ดูที่สรุปด้านขวา --}}
                        <div class="lg:hidden text-left sm:text-right shrink-0">
                            @foreach($periods as $p)
                                @php
                                    $copy = $periodCopy[$p['key']] ?? $fallbackCopy;
                                    $dearer = (float) $p['renew'] > (float) $p['first'];
                                @endphp
                                <div x-show="period === @js($p['key'])" @if($p['key'] !== $defaultPeriod) x-cloak @endif>
                                    <p class="text-xs font-semibold [&_.bi-en]:uppercase [&_.bi-en]:tracking-wider text-emerald-300">
                                        <x-bi :th="$copy['first_th']" :en="$copy['first_en']" />
                                    </p>
                                    <p class="text-3xl font-bold">{{ $p['first_display'] }}</p>
                                    <p class="mt-0.5 text-sm {{ $dearer ? 'text-amber-200' : 'text-indigo-200/80' }}">
                                        <x-bi th="ต่อไป" en="then" />
                                        <span class="font-bold text-white whitespace-nowrap">{{ $p['renew_display'] }}</span>/<x-bi :th="$p['unit_th']" :en="\App\Support\VpsPricing::unitLabel($p['key'], 'en')" />
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- ══════════ 1 · รอบบิล ══════════ --}}
                <section id="vps-step-1" class="{{ $cardClass }}" aria-labelledby="vps-step-1-title">
                    <header class="flex items-start gap-3.5 mb-5">
                        <span class="group/step relative w-9 h-9 shrink-0 rounded-xl flex items-center justify-center text-sm font-bold text-white shadow-lg transition bg-gradient-to-br from-indigo-500 to-violet-600 shadow-indigo-500/30 data-[done=true]:from-emerald-500 data-[done=true]:to-teal-500 data-[done=true]:shadow-emerald-500/25"
                              @if($stepDoneInitially[1]) data-done="true" @endif :data-done="stepDone(1)" aria-hidden="true">
                            <span class="group-data-[done=true]/step:hidden">1</span>
                            <svg class="hidden group-data-[done=true]/step:block w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 id="vps-step-1-title" class="{{ $stepTitleClass }}"><x-bi th="รอบบิล" en="Billing period" /></h2>
                            <p class="{{ $stepHintClass }}"><x-bi th="ทุกตัวเลือกบอกทั้งราคารอบแรกและราคารอบถัดไป" en="Each option shows the first term and what every renewal costs." /></p>
                        </div>
                    </header>

                    <div class="flex flex-col sm:flex-row gap-3" role="radiogroup" aria-labelledby="vps-step-1-title">
                        @foreach($periods as $p)
                            @php
                                $copy = $periodCopy[$p['key']] ?? $fallbackCopy;
                                $dearer = (float) $p['renew'] > (float) $p['first'];
                            @endphp
                            <label class="group relative flex-1 flex flex-col rounded-xl border-2 p-4 cursor-pointer transition border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 has-checked:border-indigo-500 dark:has-checked:border-indigo-400 has-checked:bg-indigo-50 dark:has-checked:bg-indigo-500/10 has-focus-visible:ring-2 has-focus-visible:ring-indigo-500/50">
                                <input type="radio" name="period" value="{{ $p['key'] }}" x-model="period" @checked($p['key'] === $defaultPeriod) class="sr-only">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="font-semibold text-slate-900 dark:text-white">
                                        <x-bi :th="$p['label_th']" :en="$p['label_en']" />
                                    </span>
                                    <span class="w-5 h-5 shrink-0 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center transition group-has-checked:border-indigo-500 group-has-checked:bg-indigo-500" aria-hidden="true">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white opacity-0 group-has-checked:opacity-100"></span>
                                    </span>
                                </span>
                                <span class="block mt-3 text-[11px] font-semibold [&_.bi-en]:uppercase [&_.bi-en]:tracking-wider text-emerald-600 dark:text-emerald-400">
                                    <x-bi :th="$copy['first_th']" :en="$copy['first_en']" />
                                </span>
                                <span class="block text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $p['first_display'] }}</span>
                                @if((int) $p['months'] > 1)
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">
                                        <x-bi th="เฉลี่ย" en="avg." /> <span class="whitespace-nowrap">{{ \App\Support\VpsPricing::format(ceil((float) $p['first'] / (int) $p['months'])) }}</span>/<x-bi th="เดือน" en="mo" />
                                    </span>
                                @endif
                                <span class="mt-auto pt-3">
                                    <span class="block pt-2.5 border-t border-slate-200 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400">
                                        <x-bi th="ต่อไป" en="then" />
                                        <span class="font-semibold whitespace-nowrap {{ $dearer ? 'text-amber-600 dark:text-amber-400' : 'text-slate-700 dark:text-slate-200' }}">{{ $p['renew_display'] }}</span>/<x-bi :th="$p['unit_th']" :en="\App\Support\VpsPricing::unitLabel($p['key'], 'en')" />
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    {{-- รอบถัดไปแพงกว่ารอบแรก: บอกตรงนี้ ตอนลูกค้ากำลังเลือกรอบบิล --}}
                    @foreach($periods as $p)
                        @if((float) $p['renew'] > (float) $p['first'])
                            <p x-show="period === @js($p['key'])" @if($p['key'] !== $defaultPeriod) x-cloak @endif
                               class="mt-3 flex items-start gap-2 text-xs text-amber-700 dark:text-amber-300/90">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.6h.01" stroke-width="2.2"/></svg>
                                <x-bi th="ราคารอบแรกเป็นราคาโปรโมชัน — เราแจ้งเตือนล่วงหน้าก่อนถึงกำหนดต่ออายุทุกครั้ง"
                                      en="The first term is promotional — we always remind you before a renewal is due." />
                            </p>
                        @endif
                    @endforeach

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
                </section>

                {{-- ══════════ 2 · ระบบปฏิบัติการ ══════════ --}}
                <section id="vps-step-2" class="{{ $cardClass }}" aria-labelledby="vps-step-2-title">
                    <header class="flex items-start gap-3.5 mb-5">
                        <span class="group/step relative w-9 h-9 shrink-0 rounded-xl flex items-center justify-center text-sm font-bold text-white shadow-lg transition bg-gradient-to-br from-indigo-500 to-violet-600 shadow-indigo-500/30 data-[done=true]:from-emerald-500 data-[done=true]:to-teal-500 data-[done=true]:shadow-emerald-500/25"
                              @if($stepDoneInitially[2]) data-done="true" @endif :data-done="stepDone(2)" aria-hidden="true">
                            <span class="group-data-[done=true]/step:hidden">2</span>
                            <svg class="hidden group-data-[done=true]/step:block w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 id="vps-step-2-title" class="{{ $stepTitleClass }}"><x-bi th="ระบบปฏิบัติการ" en="Operating system" /></h2>
                            <p class="{{ $stepHintClass }}">
                                <x-bi th="เลือก OS เปล่า หรือแบบที่ติดตั้งแอป/แผงควบคุมมาให้พร้อม ติดตั้งใหม่ภายหลังได้จากหน้าเซิร์ฟเวอร์"
                                      en="A plain OS, or one with an app or control panel preinstalled. You can reinstall later." />
                            </p>
                        </div>
                    </header>

                    @if($templateMap === [])
                        <p class="rounded-xl border border-dashed border-slate-300 dark:border-slate-600 px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                            <x-bi th="ยังโหลดรายการระบบปฏิบัติการไม่ได้ — ลองรีโหลดหน้านี้" en="The OS list couldn't be loaded — try reloading the page." />
                        </p>
                    @else
                        {{-- แท็บตามกลุ่ม: ลูกศรซ้าย/ขวาเลื่อนแท็บ ตอนค้นหาแสดงผลจากทุกกลุ่ม --}}
                        <div role="tablist" aria-label="ประเภทเทมเพลต / Template type"
                             class="grid {{ $tabCols }} gap-2"
                             @keydown.arrow-right.prevent="moveTab(1)" @keydown.arrow-left.prevent="moveTab(-1)">
                            @foreach($templateCards as $group => $cards)
                                @php $isFirstTab = $group === $selectedGroup; @endphp
                                <button type="button" role="tab" id="vps-os-tab-{{ $group }}" aria-controls="vps-os-panel-{{ $group }}"
                                        aria-selected="{{ $isFirstTab ? 'true' : 'false' }}" :aria-selected="tabActive(@js($group)) ? 'true' : 'false'"
                                        tabindex="{{ $isFirstTab ? '0' : '-1' }}" :tabindex="osTab === @js($group) ? 0 : -1"
                                        @click="pickTab(@js($group))"
                                        class="group/tab flex flex-col justify-between rounded-xl border px-3.5 py-2.5 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/60 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-800/70 aria-selected:border-indigo-500 aria-selected:bg-indigo-50 dark:aria-selected:border-indigo-400/70 dark:aria-selected:bg-indigo-500/15">
                                    <span class="block text-sm font-semibold leading-snug text-slate-800 dark:text-slate-100">{{ $groups[$group]['th'] ?? $group }}</span>
                                    <span class="mt-0.5 flex items-end justify-between gap-2">
                                        <span class="min-w-0 text-[11px] leading-tight text-slate-500 dark:text-slate-400" lang="en">{{ $groups[$group]['en'] ?? $group }}</span>
                                        <span class="shrink-0 min-w-[1.6rem] px-1.5 py-0.5 rounded-md text-center text-[11px] font-bold tabular-nums transition bg-slate-100 dark:bg-slate-700/80 text-slate-600 dark:text-slate-300 group-aria-selected/tab:bg-indigo-500 dark:group-aria-selected/tab:bg-indigo-500 group-aria-selected/tab:text-white dark:group-aria-selected/tab:text-white">{{ count($cards) }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        <div class="relative mt-3">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4.2-4.2"/></svg>
                            <input type="search" x-model="osQuery" autocomplete="off" autocapitalize="off" spellcheck="false"
                                   @keydown.enter.prevent @keydown.escape.prevent="osQuery = ''"
                                   placeholder="ค้นหา เช่น Ubuntu, Docker, n8n, cPanel / Search"
                                   aria-label="ค้นหาระบบปฏิบัติการหรือแอป / Search operating systems and apps"
                                   class="{{ $inputClass }} pl-10">
                        </div>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400" x-show="searching" x-cloak aria-live="polite">
                            <x-bi th="พบ" en="Found" /> <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="matchCount"></span>
                            <x-bi th="รายการจากทุกกลุ่ม" en="across every group" />
                        </p>

                        <div class="mt-4 space-y-4">
                            @foreach($templateCards as $group => $cards)
                                <div id="vps-os-panel-{{ $group }}" role="tabpanel" aria-labelledby="vps-os-tab-{{ $group }}"
                                     x-show="groupVisible(@js($group))" @if($group !== $selectedGroup) x-cloak @endif>
                                    <p x-show="searching" x-cloak class="mb-2 text-xs font-semibold [&_.bi-en]:uppercase [&_.bi-en]:tracking-wider text-slate-500 dark:text-slate-400">
                                        <x-bi :th="$groups[$group]['th'] ?? $group" :en="$groups[$group]['en'] ?? $group" />
                                    </p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2.5">
                                        @foreach($cards as $card)
                                            <label class="group relative flex items-center gap-3 rounded-xl border p-3 cursor-pointer transition border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-900/40 hover:border-indigo-300 dark:hover:border-indigo-500/50 hover:bg-slate-50 dark:hover:bg-slate-800/80 has-checked:border-indigo-500 dark:has-checked:border-indigo-400 has-checked:bg-indigo-50 dark:has-checked:bg-indigo-500/10 has-checked:ring-1 has-checked:ring-indigo-500 dark:has-checked:ring-indigo-400 has-focus-visible:ring-2 has-focus-visible:ring-indigo-500/60"
                                                   x-show="osMatch(@js($card['search']))" title="{{ $card['name'] }}">
                                                <input type="radio" name="template_id" value="{{ $card['id'] }}" x-model.number="template" required @checked($selectedTemplate === $card['id']) class="sr-only">
                                                <span class="w-10 h-10 shrink-0" id="vps-os-icon-{{ $card['id'] }}">@include('vps.partials.os-icon', ['name' => $card['name'], 'class' => 'w-full h-full'])</span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-sm font-semibold leading-snug text-slate-900 dark:text-white break-words">{{ $card['title'] }}</span>
                                                    @if($card['base'] !== '')<span class="block text-xs text-slate-500 dark:text-slate-400 break-words">{{ $card['base'] }}</span>@endif
                                                    @if($card['licensed'])
                                                        <span class="mt-1 inline-flex items-center gap-1 rounded-md bg-amber-100 dark:bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-semibold leading-tight text-amber-800 dark:text-amber-300">
                                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="M11 12l8-8M16 7l2.5 2.5M14 9l2 2"/></svg>
                                                            <x-bi th="ต้องซื้อไลเซนส์แยก" en="licence sold separately" />
                                                        </span>
                                                    @endif
                                                </span>
                                                <span class="w-5 h-5 shrink-0 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center transition group-has-checked:border-indigo-500 group-has-checked:bg-indigo-500" aria-hidden="true">
                                                    <svg class="w-3 h-3 text-white opacity-0 group-has-checked:opacity-100" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            <p x-show="searching && matchCount === 0" x-cloak class="rounded-xl border border-dashed border-slate-300 dark:border-slate-600 px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                                <x-bi th="ไม่พบเทมเพลตที่ตรงกับคำค้นหา" en="No template matches that search" />
                                <button type="button" @click="osQuery = ''" class="ml-1 font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    <x-bi th="ล้างคำค้นหา" en="Clear" />
                                </button>
                            </p>
                        </div>

                        {{-- ที่เลือกอยู่ — เห็นเสมอ แม้การ์ดจะอยู่แท็บอื่นหรือถูกกรองออก
                             ไอคอนในช่อง data-os-icon-slot คัดลอกจากการ์ดที่เลือก (syncArt) --}}
                        <div class="mt-4 flex items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 p-3.5">
                            <span class="w-9 h-9 shrink-0" data-os-icon-slot>@if(isset($templateMap[$selectedTemplate]))@include('vps.partials.os-icon', ['name' => $templateMap[$selectedTemplate]['name'], 'class' => 'w-full h-full'])@endif</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-semibold [&_.bi-en]:uppercase [&_.bi-en]:tracking-wider text-slate-500 dark:text-slate-400"><x-bi th="ที่เลือก" en="Selected" /></p>
                                <p class="font-semibold text-slate-900 dark:text-white break-words"
                                   x-text="templateInfo ? templateInfo.name : '—'">{{ $templateMap[$selectedTemplate]['name'] ?? '—' }}</p>
                                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400 break-words"
                                   x-show="templateInfo && templateInfo.description !== ''" @if(($templateMap[$selectedTemplate]['description'] ?? '') === '') x-cloak @endif
                                   x-text="templateInfo ? templateInfo.description : ''">{{ $templateMap[$selectedTemplate]['description'] ?? '' }}</p>
                            </div>
                        </div>

                        <div x-show="templateInfo && templateInfo.licensed" @unless(! empty($templateMap[$selectedTemplate]['licensed'])) x-cloak @endunless
                             class="mt-3 flex items-start gap-2.5 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 text-amber-900 dark:text-amber-200 px-4 py-3 text-sm">
                            <svg class="w-5 h-5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="15" r="4"/><path d="M11 12l8-8M16 7l2.5 2.5M14 9l2 2"/></svg>
                            <x-bi th="แผงควบคุมนี้ต้องซื้อไลเซนส์แยกจากผู้พัฒนา ค่าเช่าเครื่องไม่รวมค่าไลเซนส์"
                                  en="This panel needs a licence bought separately from its developer — it is not included in the rent." />
                        </div>
                    @endif
                    @error('template_id') <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </section>

                {{-- ══════════ 3 · ศูนย์ข้อมูล ══════════ --}}
                <section id="vps-step-3" class="{{ $cardClass }}" aria-labelledby="vps-step-3-title">
                    <header class="flex items-start gap-3.5 mb-5">
                        <span class="group/step relative w-9 h-9 shrink-0 rounded-xl flex items-center justify-center text-sm font-bold text-white shadow-lg transition bg-gradient-to-br from-indigo-500 to-violet-600 shadow-indigo-500/30 data-[done=true]:from-emerald-500 data-[done=true]:to-teal-500 data-[done=true]:shadow-emerald-500/25"
                              @if($stepDoneInitially[3]) data-done="true" @endif :data-done="stepDone(3)" aria-hidden="true">
                            <span class="group-data-[done=true]/step:hidden">3</span>
                            <svg class="hidden group-data-[done=true]/step:block w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 id="vps-step-3-title" class="{{ $stepTitleClass }}"><x-bi th="ศูนย์ข้อมูล" en="Server location" /></h2>
                            <p class="{{ $stepHintClass }}">
                                <x-bi th="เลือกที่ใกล้ผู้ใช้ของคุณที่สุด ตัวที่มีป้ายแนะนำอยู่ใกล้ไทยที่สุด"
                                      en="Pick the one closest to your users — the recommended ones are nearest Thailand." />
                            </p>
                        </div>
                    </header>

                    @if($dcMap === [])
                        <p class="rounded-xl border border-dashed border-slate-300 dark:border-slate-600 px-4 py-3 text-sm text-slate-500 dark:text-slate-400">
                            <x-bi th="ยังโหลดรายการศูนย์ข้อมูลไม่ได้ — ลองรีโหลดหน้านี้" en="The location list couldn't be loaded — try reloading the page." />
                        </p>
                    @endif
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-2.5" role="radiogroup" aria-labelledby="vps-step-3-title">
                        @foreach($dataCenters as $dc)
                            <label class="group relative flex items-center gap-3 rounded-xl border p-3.5 cursor-pointer transition border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-900/40 hover:border-indigo-300 dark:hover:border-indigo-500/50 hover:bg-slate-50 dark:hover:bg-slate-800/80 has-checked:border-indigo-500 dark:has-checked:border-indigo-400 has-checked:bg-indigo-50 dark:has-checked:bg-indigo-500/10 has-checked:ring-1 has-checked:ring-indigo-500 dark:has-checked:ring-indigo-400 has-focus-visible:ring-2 has-focus-visible:ring-indigo-500/60">
                                <input type="radio" name="data_center_id" value="{{ $dc['id'] }}" x-model.number="dataCenter" required @checked($selectedDc === (int) $dc['id']) class="sr-only">
                                <span class="block w-9 h-6 shrink-0" id="vps-dc-flag-{{ $dc['id'] }}">@include('vps.partials.flag', ['country' => $dc['country'], 'class' => 'w-full h-full'])</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-slate-900 dark:text-white break-words">{{ $dc['city'] !== '' ? $dc['city'] : $dc['country_th'] }}</span>
                                    <span class="block text-xs text-slate-500 dark:text-slate-400">{{ $dc['country_th'] }}</span>
                                    @if(! empty($dc['recommended']))
                                        <span class="inline-flex items-center gap-1 mt-1.5 px-1.5 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 text-[10px] font-semibold leading-tight">
                                            <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/></svg>
                                            <x-bi th="แนะนำ" en="Recommended" />
                                        </span>
                                    @endif
                                </span>
                                <span class="w-5 h-5 shrink-0 rounded-full border-2 border-slate-300 dark:border-slate-600 flex items-center justify-center transition group-has-checked:border-indigo-500 group-has-checked:bg-indigo-500" aria-hidden="true">
                                    <svg class="w-3 h-3 text-white opacity-0 group-has-checked:opacity-100" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('data_center_id') <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </section>

                {{-- ══════════ 4 · ตั้งค่าเซิร์ฟเวอร์ ══════════ --}}
                <section id="vps-step-4" class="{{ $cardClass }}" aria-labelledby="vps-step-4-title">
                    <header class="flex items-start gap-3.5 mb-5">
                        <span class="group/step relative w-9 h-9 shrink-0 rounded-xl flex items-center justify-center text-sm font-bold text-white shadow-lg transition bg-gradient-to-br from-indigo-500 to-violet-600 shadow-indigo-500/30 data-[done=true]:from-emerald-500 data-[done=true]:to-teal-500 data-[done=true]:shadow-emerald-500/25"
                              @if($stepDoneInitially[4]) data-done="true" @endif :data-done="stepDone(4)" aria-hidden="true">
                            <span class="group-data-[done=true]/step:hidden">4</span>
                            <svg class="hidden group-data-[done=true]/step:block w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 id="vps-step-4-title" class="{{ $stepTitleClass }}"><x-bi th="ตั้งค่าเซิร์ฟเวอร์" en="Server settings" /></h2>
                            <p class="{{ $stepHintClass }}"><x-bi th="ชื่อเครื่องและรหัสผ่านสำหรับเข้าใช้งานผ่าน SSH" en="The machine's name and the password you'll log in with over SSH." /></p>
                        </div>
                    </header>

                    <div class="space-y-6">
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

                            <ul id="vps-password-rules" class="mt-2.5 grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-1.5 text-xs">
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
                </section>

                {{-- ══════════ 5 · ตัวเลือก ══════════ --}}
                <section id="vps-step-5" class="{{ $cardClass }}" aria-labelledby="vps-step-5-title">
                    <header class="flex items-start gap-3.5 mb-5">
                        <span class="group/step relative w-9 h-9 shrink-0 rounded-xl flex items-center justify-center text-sm font-bold text-white shadow-lg transition bg-gradient-to-br from-indigo-500 to-violet-600 shadow-indigo-500/30 data-[done=true]:from-emerald-500 data-[done=true]:to-teal-500 data-[done=true]:shadow-emerald-500/25"
                              @if($stepDoneInitially[5]) data-done="true" @endif :data-done="stepDone(5)" aria-hidden="true">
                            <span class="group-data-[done=true]/step:hidden">5</span>
                            <svg class="hidden group-data-[done=true]/step:block w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h2 id="vps-step-5-title" class="{{ $stepTitleClass }}"><x-bi th="ตัวเลือก" en="Options" /></h2>
                        </div>
                    </header>
                    <input type="hidden" name="auto_renew" value="0">
                    <label class="flex items-start justify-between gap-4 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-900/40 p-4 cursor-pointer">
                        <span class="min-w-0">
                            <span class="block font-medium text-slate-900 dark:text-white">
                                <x-bi th="ต่ออายุอัตโนมัติ" en="Renew automatically" />
                            </span>
                            <span class="block mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                                <x-bi th="ตัดจากกระเป๋าเงินก่อนหมดอายุ {{ $chargeDays }} วัน แจ้งล่วงหน้าทุกครั้ง ปิดได้ตลอด"
                                      en="Charged from your wallet {{ $chargeDays }} days before expiry. We always tell you first, and you can switch it off any time." />
                            </span>
                        </span>
                        <span class="relative shrink-0 mt-0.5">
                            <input type="checkbox" name="auto_renew" value="1" x-model="autoRenew" @checked($autoRenewOn) class="peer sr-only">
                            <span class="block w-11 h-6 rounded-full bg-slate-300 dark:bg-slate-600 transition peer-checked:bg-indigo-500 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500/60 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-white dark:peer-focus-visible:ring-offset-slate-900" aria-hidden="true"></span>
                            <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5" aria-hidden="true"></span>
                        </span>
                    </label>
                </section>
            </div>

            {{-- ══════════ สรุปและยืนยัน (ติดจอด้านขวาบน lg) ══════════
                 การ์ดสูงไม่เกินจอ: ส่วนรายละเอียดด้านบนหดและเลื่อนในตัวได้ ส่วนราคา
                 เงื่อนไข และปุ่มยืนยันด้านล่างไม่หดเลย ปุ่มจึงไม่หลุดจอบนโน้ตบุ๊กจอเตี้ย --}}
            <aside class="mt-5 lg:mt-0 lg:sticky lg:top-24" aria-labelledby="vps-summary-title">
                <div class="flex flex-col overflow-hidden rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 shadow-xl shadow-slate-900/5 dark:shadow-black/30 lg:max-h-[calc(100vh-7rem)] lg:overflow-y-auto [scrollbar-width:thin]">
                    <div class="relative shrink-0 overflow-hidden px-5 pt-3.5 pb-3 bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 text-white">
                        <div class="absolute -top-10 -right-10 w-32 h-32 rounded-full bg-white/10 blur-2xl pointer-events-none" aria-hidden="true"></div>
                        <h2 id="vps-summary-title" class="relative text-xs font-semibold [&_.bi-en]:uppercase [&_.bi-en]:tracking-[0.16em] text-indigo-100/90">
                            <x-bi th="สรุปคำสั่งเช่า" en="Order summary" />
                        </h2>
                        <p class="relative mt-1 text-lg font-bold leading-tight break-words">{{ $plan->name }}</p>
                        <p class="relative mt-0.5 text-[11px] leading-snug text-indigo-100/80">{{ implode(' · ', $specChips) }}</p>
                    </div>

                    <dl class="lg:min-h-0 lg:overflow-y-auto lg:overscroll-contain lg:[mask-image:linear-gradient(to_bottom,#000_calc(100%-0.75rem),transparent)] px-5 py-3 space-y-1.5 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="รอบบิล" en="Billing" /></dt>
                            <dd class="min-w-0 text-right font-medium text-slate-900 dark:text-white">
                                @foreach($periods as $p)
                                    <span x-show="period === @js($p['key'])" @if($p['key'] !== $defaultPeriod) x-cloak @endif>
                                        <x-bi :th="$p['label_th']" :en="$p['label_en']" />
                                    </span>
                                @endforeach
                            </dd>
                        </div>
                        {{-- ไอคอน OS และธงในสรุปคัดลอกจากการ์ดที่เลือก (syncArt) ไม่เรนเดอร์ซ้ำทุกแบบ --}}
                        <div class="flex items-start justify-between gap-4">
                            <dt class="shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="ระบบ" en="OS" /></dt>
                            <dd class="min-w-0 flex items-start justify-end gap-2 text-right font-medium text-slate-900 dark:text-white">
                                <span class="min-w-0 break-words" x-text="templateInfo ? templateInfo.name : '—'">{{ $templateMap[$selectedTemplate]['name'] ?? '—' }}</span>
                                <span class="w-5 h-5 shrink-0" data-os-icon-slot>@if(isset($templateMap[$selectedTemplate]))@include('vps.partials.os-icon', ['name' => $templateMap[$selectedTemplate]['name'], 'class' => 'w-full h-full'])@endif</span>
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="ที่ตั้ง" en="Location" /></dt>
                            <dd class="min-w-0 flex items-start justify-end gap-2 text-right font-medium text-slate-900 dark:text-white">
                                <span class="min-w-0 break-words" x-text="dataCenterInfo ? dataCenterInfo.label : '—'">{{ $dcMap[$selectedDc]['label'] ?? '—' }}</span>
                                <span class="block w-6 h-4 mt-0.5 shrink-0" data-dc-flag-slot>@if(isset($dcRows[$selectedDc]))@include('vps.partials.flag', ['country' => $dcRows[$selectedDc]['country'], 'class' => 'w-full h-full'])@endif</span>
                            </dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="shrink-0 text-slate-500 dark:text-slate-400"><x-bi th="ชื่อโฮสต์" en="Hostname" /></dt>
                            <dd class="min-w-0 text-right font-mono text-[13px] text-slate-900 dark:text-white break-all"
                                x-text="hostname.trim() || '—'">{{ $hostnameValue !== '' ? $hostnameValue : '—' }}</dd>
                        </div>
                    </dl>

                    <div class="shrink-0 px-5 pb-5">
                        {{-- ราคาวันนี้ + ราคาต่ออายุ: เห็นคู่กันเสมอ --}}
                        <div class="pt-3.5 border-t border-slate-200 dark:border-slate-700">
                            @foreach($periods as $p)
                                @php
                                    $copy = $periodCopy[$p['key']] ?? $fallbackCopy;
                                    $dearer = (float) $p['renew'] > (float) $p['first'];
                                @endphp
                                <div x-show="period === @js($p['key'])" @if($p['key'] !== $defaultPeriod) x-cloak @endif>
                                    <div class="flex items-end justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200"><x-bi th="ยอดชำระวันนี้" en="Due today" /></p>
                                            <p class="text-[11px] font-semibold [&_.bi-en]:uppercase [&_.bi-en]:tracking-wider text-emerald-600 dark:text-emerald-400"><x-bi :th="$copy['first_th']" :en="$copy['first_en']" /></p>
                                        </div>
                                        <p class="shrink-0 text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">{{ $p['first_display'] }}</p>
                                    </div>
                                    <div class="mt-2.5 flex items-center justify-between gap-3 rounded-xl px-3 py-2 {{ $dearer ? 'bg-amber-50 dark:bg-amber-500/10 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/25 text-amber-900 dark:text-amber-200' : 'bg-slate-50 dark:bg-slate-900/50 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 text-slate-600 dark:text-slate-300' }}">
                                        <span class="min-w-0 text-xs leading-snug"><x-bi :th="$copy['next_th']" :en="$copy['next_en']" /></span>
                                        <span class="shrink-0 text-right">
                                            <span class="font-bold whitespace-nowrap {{ $dearer ? 'text-amber-700 dark:text-amber-300' : 'text-slate-900 dark:text-white' }}">{{ $p['renew_display'] }}</span><span class="text-xs">/<x-bi :th="$p['unit_th']" :en="\App\Support\VpsPricing::unitLabel($p['key'], 'en')" /></span>
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                            <p class="mt-1.5 flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4v5h5M20 20v-5h-5M5.5 15a7 7 0 0012.2 2.5M18.5 9A7 7 0 006.3 6.5"/></svg>
                                <span x-show="autoRenew" @unless($autoRenewOn) x-cloak @endunless><x-bi th="ต่ออายุอัตโนมัติ: เปิด" en="Auto-renew on" /></span>
                                <span x-show="!autoRenew" @if($autoRenewOn) x-cloak @endif><x-bi th="ต่ออายุอัตโนมัติ: ปิด" en="Auto-renew off" /></span>
                            </p>
                        </div>

                        {{-- กระเป๋าเงิน: คงเหลือหลังชำระ หรือขาดเท่าไหร่ + ปุ่มเติมเงิน --}}
                        <div class="mt-3">
                            <div x-show="!short" @if($shortDefault) x-cloak @endif
                                 class="rounded-xl bg-emerald-50/70 dark:bg-emerald-500/10 ring-1 ring-inset ring-emerald-200/80 dark:ring-emerald-500/20 px-3 py-2 text-xs space-y-0.5">
                                <p class="flex items-center justify-between gap-3 text-slate-600 dark:text-slate-300">
                                    <span><x-bi th="ยอดในกระเป๋า" en="Wallet balance" /></span>
                                    <span class="font-medium whitespace-nowrap text-slate-900 dark:text-white">{{ $balanceText }}</span>
                                </p>
                                @foreach($periods as $p)
                                    <p class="flex items-center justify-between gap-3 text-slate-600 dark:text-slate-300" x-show="period === @js($p['key'])" @if($p['key'] !== $defaultPeriod) x-cloak @endif>
                                        <span><x-bi th="คงเหลือหลังชำระ" en="Left after paying" /></span>
                                        <span class="font-semibold whitespace-nowrap text-emerald-700 dark:text-emerald-300">{{ $afterPay[$p['key']] ?? '' }}</span>
                                    </p>
                                @endforeach
                            </div>
                            <div x-show="short" @unless($shortDefault) x-cloak @endunless
                                 class="rounded-xl bg-amber-50 dark:bg-amber-500/10 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/30 p-3">
                                <p class="flex items-center gap-2 font-semibold text-sm text-amber-900 dark:text-amber-200">
                                    <svg class="w-4 h-4 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                    <x-bi th="ยอดเงินในกระเป๋าไม่พอ" en="Not enough in your wallet" />
                                </p>
                                @foreach($periods as $p)
                                    <p x-show="period === @js($p['key'])" @if($p['key'] !== $defaultPeriod) x-cloak @endif
                                       class="mt-1 text-xs text-amber-800 dark:text-amber-300/90">
                                        <x-bi th="ต้องใช้" en="Needs" /> <span class="font-semibold whitespace-nowrap">{{ $p['first_display'] }}</span> ·
                                        <x-bi th="มีอยู่" en="you have" /> <span class="font-semibold whitespace-nowrap">{{ $balanceText }}</span> ·
                                        <x-bi th="ขาดอีก" en="short by" /> <span class="font-semibold whitespace-nowrap">{{ $shortfalls[$p['key']] ?? '' }}</span>
                                    </p>
                                @endforeach
                                <a href="{{ route('user.wallet.index') }}"
                                   class="mt-2.5 inline-flex w-full items-center justify-center gap-2 px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold shadow-sm transition">
                                    <x-bi th="เติมเงิน" en="Top up" />
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </a>
                            </div>
                        </div>

                        {{-- เงื่อนไข --}}
                        <div class="mt-3.5 pt-3.5 border-t border-slate-200 dark:border-slate-700">
                            <label class="flex items-start gap-2.5 cursor-pointer">
                                <input type="checkbox" id="vps-accept-terms" name="accept_terms" value="1" required x-model="accepted" @checked($acceptedOld)
                                       class="mt-0.5 w-4 h-4 shrink-0 accent-indigo-600">
                                <span class="text-sm font-medium leading-snug text-slate-900 dark:text-white">
                                    <x-bi th="ข้าพเจ้ายอมรับเงื่อนไขการเช่าต่อไปนี้" en="I accept these rental terms" />
                                </span>
                            </label>
                            <ul class="mt-2 ml-[1.625rem] space-y-1 text-[11px] leading-snug text-slate-600 dark:text-slate-400">
                                @foreach($terms as $term)
                                    <li class="flex gap-1.5">
                                        <span class="mt-[0.45rem] w-1 h-1 rounded-full bg-slate-400 dark:bg-slate-500 shrink-0" aria-hidden="true"></span>
                                        <span class="min-w-0">{{ $term['th'] }} <span class="text-slate-500 dark:text-slate-500">/ {{ $term['en'] }}</span></span>
                                    </li>
                                @endforeach
                            </ul>
                            @error('accept_terms') <p class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        {{-- กดซ้ำไม่ได้: ปุ่มถูกปิดทันทีที่ส่ง และฝั่งเซิร์ฟเวอร์ยังกันซ้ำด้วย order_token อีกชั้น --}}
                        <button type="submit"
                                @disabled($serverBlocked)
                                x-bind:disabled="submitting || !canSubmit"
                                class="mt-4 w-full px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-white font-bold shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 hover:brightness-110 active:scale-[0.99] transition disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none disabled:hover:brightness-100">
                            <span x-show="!submitting" class="inline-flex items-center justify-center gap-2">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="9.5" rx="2"/><path d="M8 11V7.5a4 4 0 0 1 8 0V11"/></svg>
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

                        {{-- บอกว่าทำไมปุ่มยังกดไม่ได้ ปุ่มที่ปิดเฉย ๆ โดยไม่มีเหตุผลคือทางตัน
                             รายการที่มีช่องให้แก้ กดแล้วพาไปที่ช่องนั้น (สรุปอยู่ไกลจากช่องบนจอใหญ่) --}}
                        <div x-show="!submitting && blockers.length > 0" x-cloak class="mt-3 text-xs text-amber-800 dark:text-amber-300">
                            <p class="font-semibold mb-1">ยังต้องทำก่อนสั่งเช่า / Still to do</p>
                            <ul class="flex flex-wrap gap-1">
                                <template x-for="item in blockers" :key="item.text">
                                    <li class="max-w-full">
                                        <button type="button" x-show="item.target" @click="jumpTo(item.target)"
                                                class="inline-flex max-w-full items-center gap-0.5 rounded-md bg-amber-50 dark:bg-amber-500/10 ring-1 ring-inset ring-amber-300/70 dark:ring-amber-500/30 px-1.5 py-0.5 text-[11px] text-left leading-snug hover:bg-amber-100 dark:hover:bg-amber-500/20 transition">
                                            <span x-text="item.text"></span>
                                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                                        </button>
                                        <span x-show="!item.target" class="inline-flex max-w-full rounded-md bg-amber-50 dark:bg-amber-500/10 ring-1 ring-inset ring-amber-300/70 dark:ring-amber-500/30 px-1.5 py-0.5 text-[11px] leading-snug" x-text="item.text"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>
            </aside>
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

    // เทียบคำค้นแบบไม่สนช่องว่าง จุด และขีด: "ubuntu24" เจอ "Ubuntu 24.04"
    // (ฝั่ง PHP ทำคีย์ค้นหาของแต่ละเทมเพลตด้วยกฎเดียวกัน)
    const normalize = (text) => String(text).toLowerCase().replace(/[\s._\-\/]+/gu, '');

    return {
        period: config.period,
        firsts: config.firsts || {},
        balance: Number(config.balance) || 0,
        paused: !!config.paused,
        catalogReady: !!config.catalogReady,
        template: config.template,
        templates: config.templates || {},
        groups: Array.isArray(config.groups) ? config.groups : [],
        osTab: config.osTab,
        osQuery: '',
        dataCenter: config.dataCenter,
        dataCenters: config.dataCenters || {},
        hostname: config.hostname || '',
        publicKey: config.publicKey || '',
        showKey: !!config.showKey,
        autoRenew: !!config.autoRenew,
        accepted: !!config.accepted,

        password: '',
        showPassword: false,
        copied: false,
        copyTimer: null,
        generateError: false,
        submitting: false,

        init() {
            this.$watch('template', (id) => {
                // เลือกจากผลค้นหาข้ามกลุ่ม แล้วล้างคำค้น — กลับมาที่แท็บของตัวที่เลือกไว้
                const info = this.templates[id];
                if (info && this.searching) this.osTab = info.group;
                this.syncArt();
            });
            this.$watch('dataCenter', () => this.syncArt());
        },

        // ไอคอน OS และธงในสรุปรายการ / กล่อง "ที่เลือก" คัดลอกจากการ์ดที่ถูกเลือก
        // แทนการเรนเดอร์ไอคอนทุกแบบซ้ำไว้ให้ x-show สลับ — เกือบร้อยเทมเพลตคือหลายสิบ KB
        syncArt() {
            this.copyArt('[data-os-icon-slot]', 'vps-os-icon-' + this.template);
            this.copyArt('[data-dc-flag-slot]', 'vps-dc-flag-' + this.dataCenter);
        },
        copyArt(slotSelector, sourceId) {
            const source = document.getElementById(sourceId);
            const art = source ? source.firstElementChild : null;
            this.$root.querySelectorAll(slotSelector).forEach((slot) => {
                slot.replaceChildren(...(art ? [art.cloneNode(true)] : []));
            });
        },

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

        // ── ตัวเลือกระบบปฏิบัติการ: แท็บ + ค้นหา ──
        get needle() { return normalize(this.osQuery.trim()); },
        get searching() { return this.needle !== ''; },
        osMatch(key) {
            return this.needle === '' || String(key).indexOf(this.needle) !== -1;
        },
        groupMatches(group) {
            let count = 0;
            for (const id in this.templates) {
                const t = this.templates[id];
                if (t.group === group && this.osMatch(t.search)) count++;
            }
            return count;
        },
        get matchCount() {
            return this.groups.reduce((sum, group) => sum + this.groupMatches(group), 0);
        },
        groupVisible(group) {
            return this.searching ? this.groupMatches(group) > 0 : this.osTab === group;
        },
        tabActive(group) {
            return !this.searching && this.osTab === group;
        },
        pickTab(group) {
            this.osQuery = '';
            this.osTab = group;
        },
        moveTab(step) {
            if (this.groups.length === 0) return;
            const at = Math.max(0, this.groups.indexOf(this.osTab));
            this.pickTab(this.groups[(at + step + this.groups.length) % this.groups.length]);
            this.$nextTick(() => {
                const tab = document.getElementById('vps-os-tab-' + this.osTab);
                if (tab) tab.focus();
            });
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

        // ขั้นที่ครบแล้วเปลี่ยนเลขเป็นเครื่องหมายถูก — ลูกค้าเห็นว่าเหลือแค่ขั้นไหน
        stepDone(n) {
            switch (n) {
                case 1: return this.firsts[this.period] !== undefined;
                case 2: return !!this.templateInfo;
                case 3: return !!this.dataCenterInfo;
                case 4: return this.hostnameOk && this.pwOk && this.keyOk;
                default: return true;
            }
        },

        get blockers() {
            const list = [];
            if (this.paused) list.push({ text: 'ระบบปิดรับชั่วคราว / Ordering paused', target: null });
            if (!this.catalogReady) list.push({ text: 'รีโหลดหน้านี้ / Reload the page', target: null });
            if (this.short) list.push({ text: 'เติมเงิน / Top up', target: null });
            if (this.catalogReady && !this.templateInfo) list.push({ text: 'เลือก OS / Choose an OS', target: 'vps-step-2' });
            if (this.catalogReady && !this.dataCenterInfo) list.push({ text: 'เลือกที่ตั้ง / Choose a location', target: 'vps-step-3' });
            if (!this.hostnameOk) list.push({ text: 'ชื่อโฮสต์ / Hostname', target: 'hostname' });
            if (!this.pwOk) list.push({ text: 'รหัสผ่าน root / Root password', target: 'root_password' });
            if (!this.keyOk) list.push({ text: 'SSH key', target: 'public_key' });
            if (!this.accepted) list.push({ text: 'ยอมรับเงื่อนไข / Accept terms', target: 'vps-accept-terms' });
            return list;
        },
        get canSubmit() {
            return this.blockers.length === 0;
        },

        // พาไปที่ช่องที่ยังต้องแก้ — เลื่อนให้อยู่กลางจอแล้วโฟกัส
        jumpTo(id) {
            if (id === 'public_key') this.showKey = true;
            this.$nextTick(() => {
                const el = document.getElementById(id);
                if (!el) return;
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (typeof el.focus === 'function' && el.matches('input, textarea, select, button')) {
                    el.focus({ preventScroll: true });
                }
            });
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
