@extends($customerLayout ?? 'layouts.customer')

@section('title', $server->hostname . ' · VPS ของฉัน')
@section('page-title'){{ $server->hostname }}@endsection
@section('page-description')<x-bi th="เปิด/ปิดเครื่อง ตั้งรหัสผ่าน สแนปช็อต และต่ออายุเซิร์ฟเวอร์นี้" en="Power, passwords, snapshots and renewal for this server" />@endsection

@section('content')
@php
    $badge = $server->statusBadge();
    $power = $server->stateLabel();
    $days = $server->daysUntilExpiry();
    $status = $server->status;
    $busy = $server->isBusy();
    $settingUp = in_array($status, [\App\Models\VpsInstance::STATUS_PENDING, \App\Models\VpsInstance::STATUS_PROVISIONING], true);
    $running = $server->state === 'running';
    $stopped = $server->state === 'stopped';
    $powerText = $power['th'] . ' / ' . $power['en'];
    $statusUrl = route('customer.vps.status', $server->id);
    $sshCommand = $server->ipv4 ? 'ssh root@' . $server->ipv4 : null;

    // กล่องพื้นอ่อนในพอร์ทัลต้องเขียนคู่ light/dark เสมอ — customer-premium
    // ทับพื้นด้วย !important แต่ไม่แตะสีตัวอักษร (docs/artwork.md)
    $card = 'rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700';
    $tile = 'rounded-xl border border-slate-200 dark:border-slate-700 p-4 min-w-0';
    $h2 = 'text-lg font-bold text-slate-900 dark:text-white';
    $lead = 'text-sm text-slate-600 dark:text-slate-400 mt-1';
    $label = 'block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5';
    // ไม่ได้เปิดปลั๊กอิน forms ของ Tailwind: ช่องกรอกต้องใส่ขอบและ padding เอง
    // ไม่งั้นบนธีมปกติจะดูเหมือนข้อความเปล่าที่ไม่รู้ว่ากดพิมพ์ได้
    $field = 'w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-900 dark:text-white text-sm px-3 py-2 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30';
    $btnPrimary = 'inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed';
    $btnDark = 'inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-lg bg-slate-800 dark:bg-slate-600 hover:bg-slate-700 text-white text-sm font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed';
    $btnGhost = 'inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition disabled:opacity-40 disabled:cursor-not-allowed';
    $btnGhostDanger = 'inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg border border-red-300 dark:border-red-500/40 text-red-700 dark:text-red-300 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-500/10 transition disabled:opacity-40 disabled:cursor-not-allowed';
    $btnDanger = 'inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-lg bg-red-600 hover:bg-red-500 text-white text-sm font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed';
    $btnRenew = 'inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed';

    $left = match (true) {
        $days === null => null,
        $days < 0 => ['th' => 'หมดอายุแล้ว', 'en' => 'expired'],
        $days === 0 => ['th' => 'หมดวันนี้', 'en' => 'ends today'],
        default => ['th' => 'เหลือ ' . $days . ' วัน', 'en' => $days . ($days === 1 ? ' day' : ' days') . ' left'],
    };

    $specs = array_values(array_filter([
        $server->spec('cpus') ? $server->spec('cpus') . ' vCPU' : null,
        $server->spec('memory_mb') ? \App\Models\VpsPlan::sizeLabel($server->spec('memory_mb')) . ' RAM' : null,
        $server->spec('disk_mb') ? \App\Models\VpsPlan::sizeLabel($server->spec('disk_mb')) . ' NVMe' : null,
        $server->spec('bandwidth_mb') ? \App\Models\VpsPlan::sizeLabel($server->spec('bandwidth_mb')) . ' bandwidth' : null,
    ]));

    // เงินคำนวณที่ controller แล้ว หน้านี้แค่จัดรูปแบบ
    $period = (string) $server->period;
    $unitTh = \App\Support\VpsPricing::unitLabel($period, 'th');
    $unitEn = \App\Support\VpsPricing::unitLabel($period, 'en');
    $spanTh = preg_match('/^\d/', $unitTh) ? $unitTh : '1 ' . $unitTh;
    $renewDisplay = \App\Support\VpsPricing::format((float) $renewPrice);
    $renewable = $canRenew && $renewPrice > 0;
    // ต่อก่อนหมดอายุ = นับต่อจากวันเดิม · หมดไปแล้ว = นับรอบใหม่จากวันนี้ (ตาม VpsProvisioningService)
    $renewFromOld = $server->expires_at && $server->expires_at->isFuture();
    $renewBasisTh = $renewFromOld ? 'วันหมดอายุใหม่นับต่อจากวันเดิม ไม่เสียวันที่เหลือ' : 'รอบใหม่เริ่มนับตั้งแต่วันที่ต่ออายุ';
    $renewBasisEn = $renewFromOld ? 'added on top of the current expiry, so no days are lost' : 'the new period starts the day you renew';
    $pendingRenew = $payments->contains(fn ($p) => $p->kind === \App\Models\VpsPayment::KIND_RENEW && $p->status === \App\Models\VpsPayment::STATUS_PENDING);

    // เวลาของสแนปช็อต/แบ็กอัปมาเป็นสตริง UTC จากฝั่งเครื่อง — แปลงเป็นเวลาไทย
    // และสตริงที่อ่านไม่ออกต้องไม่ทำให้ทั้งหน้าพัง
    $when = function ($value): ?string {
        if (! $value) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->timezone('Asia/Bangkok')->format('j M Y H:i') . ' น.';
        } catch (\Throwable) {
            return null;
        }
    };
    $snapWhen = $snapshot ? $when($snapshot['created_at'] ?? null) : null;

    // ตัวเลขการใช้งาน — ค่าที่ขาดแสดงเป็นขีด ไม่ใช่ 0 ที่ทำให้เข้าใจผิด
    $pct = fn ($v) => $v === null ? '—' : number_format((float) $v, 1) . '%';
    $gb = fn ($v, int $dp = 1) => $v === null ? '—' : number_format((float) $v, $dp) . ' GB';
    $barWidth = fn ($v) => $v === null ? 0 : max(0, min(100, (float) $v));
    $barTone = fn ($v) => match (true) {
        $v === null => 'bg-slate-400',
        (float) $v >= 90 => 'bg-red-500',
        (float) $v >= 75 => 'bg-amber-500',
        default => 'bg-emerald-500',
    };
    $uptime = null;
    if ($metrics && ($metrics['uptime_hours'] ?? null) !== null) {
        $hours = (float) $metrics['uptime_hours'];
        $wholeDays = intdiv((int) floor($hours), 24);
        $uptime = $wholeDays > 0
            ? ['th' => $wholeDays . ' วัน ' . ((int) floor($hours) % 24) . ' ชม.', 'en' => $wholeDays . 'd ' . ((int) floor($hours) % 24) . 'h']
            : ['th' => number_format($hours, 1) . ' ชม.', 'en' => number_format($hours, 1) . 'h'];
    }

    // ข้อความยืนยันประกอบไว้ตรงนี้ แล้วส่งเข้า confirm() ผ่านตัวแปลง js ของ Blade
    // เท่านั้น — ห้ามเอาค่าไปวางในเครื่องหมายคำพูดใน onsubmit ตรงๆ
    $confirm = [
        'renew' => 'ต่ออายุ ' . $server->hostname . ' อีก ' . $spanTh . ' เป็นเงิน ' . $renewDisplay
            . "\n\nตัดจากกระเป๋าเงินทันที · " . $renewBasisTh . "\n\nยืนยันไหม?",
        'restart' => 'รีสตาร์ท ' . $server->hostname . "?\n\nเว็บและบริการบนเครื่องจะหยุดประมาณ 1–2 นาที",
        'stop' => 'ปิดเครื่อง ' . $server->hostname . "?\n\nเว็บและบริการทั้งหมดบนเครื่องจะหยุดจนกว่าคุณจะเปิดใหม่ · การปิดเครื่องไม่หยุดรอบบิล",
        'snapshotCreate' => "สร้างสแนปช็อตใหม่?\n\nสแนปช็อตเดิม" . ($snapWhen ? ' ' . $snapWhen : '') . ' จะถูกแทนที่ และย้อนกลับไปใช้ไม่ได้อีก',
        'snapshotRestore' => 'กู้คืนเครื่องกลับไปเป็นสแนปช็อต' . ($snapWhen ? ' ' . $snapWhen : '')
            . "?\n\nข้อมูลทั้งหมดที่เปลี่ยนหลังจากนั้นจะหายไป และเครื่องจะรีสตาร์ท",
        'snapshotDelete' => 'ลบสแนปช็อต' . ($snapWhen ? ' ' . $snapWhen : '') . "?\n\nลบแล้วกู้คืนไม่ได้",
        'reinstall' => 'ยืนยันครั้งสุดท้าย: ลบทุกอย่างใน ' . $server->hostname
            . " แล้วติดตั้งระบบใหม่?\n\nข้อมูลทั้งหมดและสแนปช็อตจะหายไป กู้คืนไม่ได้",
    ];

    // ฟอร์มที่เพิ่งส่งไม่ผ่าน validation — ช่องซ่อน section ถูก flash กลับมาพร้อม
    // input ใช้เปิดส่วนติดตั้ง OS ใหม่ค้างไว้ให้แก้ต่อ แทนที่จะพับหายไป
    $failedSection = $errors->any() ? old('section') : null;
    $selectedTemplate = (int) old('template_id', $server->template_id);
@endphp

<div class="space-y-6">

    <a href="{{ route('customer.vps.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <x-bi th="เซิร์ฟเวอร์ทั้งหมด" en="All servers" />
    </a>

    @if (session('success'))
        <div class="rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 text-green-900 dark:text-green-200 px-4 py-3 text-sm animate-fade-in">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-4 py-3 text-sm animate-fade-in">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-4 py-3 text-sm animate-fade-in">
            <p class="font-semibold mb-1"><x-bi th="ยังบันทึกไม่ได้ กรุณาแก้ไขก่อน" en="Not saved yet — please fix the following" /></p>
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ══════════ สรุปเซิร์ฟเวอร์ ══════════ --}}
    <div class="relative overflow-hidden rounded-2xl shadow-xl bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900">
        <x-page-art art="hero-vps" :opacity="35" :scrim="false" fade="bottom" />
        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/90 via-slate-900/40 to-transparent pointer-events-none" aria-hidden="true"></div>
        <div class="relative px-6 sm:px-8 py-7">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $badge['classes'] }}">
                            <x-bi :th="$badge['label_th']" :en="$badge['label_en']" />
                        </span>
                        @if($server->state && ! $settingUp)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/10 text-slate-100">
                                <span class="w-2 h-2 rounded-full shrink-0 {{ $power['dot'] }}" aria-hidden="true"></span>
                                <x-bi :th="$power['th']" :en="$power['en']" />
                            </span>
                        @endif
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-white mt-2 break-all">{{ $server->hostname }}</h1>
                    <p class="text-sm text-slate-300 mt-1">
                        {{ $server->plan_name }}
                        @if($server->period)
                            · <x-bi :th="$server->periodLabel()" :en="$server->periodLabel('en')" />
                        @endif
                    </p>
                    @if($specs)
                        <p class="text-xs text-slate-400 mt-1">{{ implode(' · ', $specs) }}</p>
                    @endif
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 mt-3 text-sm text-slate-300">
                        @if($server->ipv4)
                            <span class="inline-flex flex-wrap items-center gap-2" x-data="vpsCopy(@js($server->ipv4))">
                                <span class="text-slate-400">IPv4</span>
                                <span class="font-mono font-semibold text-white select-all break-all">{{ $server->ipv4 }}</span>
                                <button type="button" @click="copy()"
                                        class="px-2 py-0.5 rounded-md bg-white/10 hover:bg-white/20 text-xs text-white transition">
                                    <span x-show="!copied"><x-bi th="คัดลอก" en="Copy" /></span>
                                    <span x-show="copied" x-cloak><x-bi th="คัดลอกแล้ว" en="Copied" /></span>
                                </button>
                            </span>
                        @endif
                        @if($server->expires_at)
                            <span class="{{ $days !== null && $days <= 7 ? 'text-amber-300 font-semibold' : '' }}">
                                <x-bi th="หมดอายุ" en="Expires" /> {{ $server->expires_at->format('j M Y') }}
                                @if($left)
                                    {{-- วงเล็บอยู่ในข้อความเอง: คอมโพเนนต์จบด้วยขึ้นบรรทัด จะกลายเป็นช่องว่างก่อน ")" --}}
                                    <x-bi :th="'(' . $left['th']" :en="$left['en'] . ')'" />
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
                @if($renewPrice > 0 && $status !== \App\Models\VpsInstance::STATUS_REFUNDED)
                    <div class="text-left lg:text-right shrink-0">
                        <p class="text-xs text-slate-400">
                            <x-bi th="ค่าต่ออายุ" en="Renewal" />
                            @if($server->period)
                                · <x-bi :th="$server->periodLabel()" :en="$server->periodLabel('en')" />
                            @endif
                        </p>
                        <p class="text-xl font-bold text-white">{{ $renewDisplay }}<span class="text-sm font-normal text-slate-400">/<x-bi :th="$unitTh" :en="$unitEn" /></span></p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════ สถานะที่ต้องบอกก่อนปุ่มใดๆ ══════════ --}}
    @if($settingUp)
        {{-- ถามสถานะทุก 10 วินาที แล้วรีโหลดเองเมื่อเครื่องพร้อม (หรือคืนเงิน) --}}
        <div x-data="vpsPoller(@js($statusUrl), 'settle', @js($server->state ? $powerText : 'กำลังเตรียมเครื่อง / Preparing'))"
             class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-5 py-4 text-sm text-amber-900 dark:text-amber-200">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 shrink-0 mt-0.5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" class="opacity-25"/>
                    <path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
                <div class="min-w-0">
                    <p class="font-semibold mb-1"><x-bi th="กำลังติดตั้งเซิร์ฟเวอร์" en="Setting up your server" /></p>
                    <p>
                        <x-bi th="ปกติใช้เวลา 5–15 นาที หน้านี้จะอัปเดตเองเมื่อเสร็จ และเราจะส่งอีเมลแจ้ง — ไม่ต้องสั่งซ้ำ"
                              en="Usually 5–15 minutes. This page updates itself when it's done and we'll email you — no need to order again." />
                    </p>
                    <p class="mt-2 text-xs text-amber-800 dark:text-amber-300/90">
                        <x-bi th="สถานะล่าสุด" en="Latest status" />:
                        <span class="font-semibold" x-text="label">{{ $server->state ? $powerText : 'กำลังเตรียมเครื่อง / Preparing' }}</span>
                    </p>
                    @if($server->created_at && $server->created_at->lt(now()->subMinutes(30)))
                        <p class="mt-2 text-xs text-amber-800 dark:text-amber-300/90">
                            <x-bi th="ใช้เวลานานกว่าปกติ — ไม่ต้องสั่งซ้ำ หากเกิน 1 ชั่วโมงกรุณาแจ้งทีมงาน"
                                  en="Taking longer than usual — please don't order again. If it passes an hour, let our team know." />
                            <a href="{{ route('customer.support.create') }}" class="underline font-semibold hover:no-underline"><x-bi th="แจ้งทีมงาน" en="Contact support" /></a>
                        </p>
                    @endif
                    <p x-show="halted" x-cloak class="mt-2 text-xs">
                        <x-bi th="หยุดตรวจสถานะอัตโนมัติแล้ว กรุณารีเฟรชหน้าเพื่อดูสถานะล่าสุด" en="Stopped checking automatically — refresh the page for the latest status." />
                    </p>
                </div>
            </div>
        </div>
    @elseif($status === \App\Models\VpsInstance::STATUS_REFUNDED)
        <div class="rounded-xl bg-slate-100 dark:bg-slate-500/10 border border-slate-200 dark:border-slate-500/30 px-5 py-4 text-sm text-slate-800 dark:text-slate-200">
            <p class="font-semibold mb-1"><x-bi th="คำสั่งเช่าไม่สำเร็จ คืนเงินแล้ว" en="The order didn't go through — refunded" /></p>
            <p><x-bi th="เงินคืนเข้ากระเป๋าเงินของคุณเรียบร้อยแล้ว สั่งเช่าใหม่ได้ทุกเมื่อ" en="The money is back in your wallet. You can order again any time." /></p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('vps.index') }}" class="{{ $btnDark }}"><x-bi th="เลือกแพ็กเกจ" en="Choose a plan" /></a>
                <a href="{{ route('user.wallet.index') }}" class="{{ $btnGhost }}"><x-bi th="ดูกระเป๋าเงิน" en="View wallet" /></a>
            </div>
        </div>
    @elseif($status === \App\Models\VpsInstance::STATUS_FAILED)
        <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-5 py-4 text-sm text-red-900 dark:text-red-200">
            <p class="font-semibold mb-1"><x-bi th="ทีมงานกำลังตรวจสอบการติดตั้ง ไม่ต้องสั่งซ้ำ" en="Our team is checking the setup — no need to order again" /></p>
            <p><x-bi th="การติดตั้งเครื่องนี้ยังไม่เสร็จ เราจะแจ้งทางอีเมลเมื่อมีความคืบหน้า มีคำถามแจ้งทีมงานได้ตลอด"
                     en="This server didn't finish setting up. We'll email you as soon as there's news — ask our team any time." /></p>
            <a href="{{ route('customer.support.create') }}" class="inline-block mt-2 underline font-semibold hover:no-underline"><x-bi th="แจ้งทีมงาน" en="Contact support" /></a>
        </div>
    @elseif($status === \App\Models\VpsInstance::STATUS_EXPIRED)
        <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-5 py-4 text-sm text-red-900 dark:text-red-200">
            <p class="font-semibold mb-1"><x-bi th="เซิร์ฟเวอร์หมดอายุและถูกระงับแล้ว" en="This server has expired and is suspended" /></p>
            <p>
                <x-bi th="เว็บและบริการบนเครื่องหยุดทำงาน ข้อมูลในเครื่องยังอยู่ชั่วคราว — ต่ออายุตอนนี้เพื่อเปิดใช้งานต่อ ก่อนที่ข้อมูลจะถูกลบถาวร"
                      en="Everything on it has stopped. The data is still there for now — renew to bring it back before it is deleted for good." />
            </p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                @if($renewable)
                    <form method="POST" action="{{ route('customer.vps.renew', $server->id) }}" data-once
                          onsubmit="return confirm(@js($confirm['renew']))">
                        @csrf
                        <button type="submit" class="{{ $btnRenew }}">
                            <x-bi th="ต่ออายุตอนนี้" en="Renew now" /> · {{ $renewDisplay }}
                        </button>
                    </form>
                    <a href="{{ route('user.wallet.topup') }}" class="{{ $btnGhost }}"><x-bi th="เติมเงิน" en="Top up" /></a>
                @elseif($pendingRenew)
                    <p class="font-semibold"><x-bi th="กำลังยืนยันการต่ออายุ ไม่ต้องกดซ้ำ" en="Your renewal is being confirmed — no need to press again" /></p>
                @else
                    <a href="{{ route('customer.support.create') }}" class="underline font-semibold hover:no-underline"><x-bi th="ติดต่อทีมงานเพื่อต่ออายุ" en="Contact support to renew" /></a>
                @endif
            </div>
        </div>
    @elseif(! $manageable)
        <div class="rounded-xl bg-slate-100 dark:bg-slate-500/10 border border-slate-200 dark:border-slate-500/30 px-5 py-4 text-sm text-slate-800 dark:text-slate-200">
            <x-bi th="กำลังเชื่อมต่อกับเครื่อง ปุ่มจัดการจะปรากฏเมื่อพร้อม — ลองรีเฟรชอีกครั้งในไม่กี่นาที"
                  en="Connecting to the server — the controls appear once it's ready. Try refreshing in a few minutes." />
        </div>
    @endif

    @if($manageable && $server->needs_password_reset)
        <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-5 py-4 text-sm text-red-900 dark:text-red-200 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="font-semibold mb-1"><x-bi th="ตั้งรหัสผ่าน root ก่อนเข้าใช้งาน" en="Set a root password before you log in" /></p>
                <p><x-bi th="เครื่องนี้ถูกตั้งรหัสผ่าน root แบบสุ่มที่ไม่มีใครรู้ — ตั้งรหัสของคุณเองตอนนี้ แล้วเข้าใช้งานด้วย SSH ได้ทันที"
                         en="This server has a random root password nobody knows. Set your own now and SSH straight in." /></p>
            </div>
            <a href="#root-password" class="{{ $btnDanger }} shrink-0"><x-bi th="ตั้งรหัสผ่านตอนนี้" en="Set it now" /></a>
        </div>
    @endif

    @if($manageable)
        {{-- ══════════ เข้าใช้งาน + เปิด/ปิดเครื่อง ══════════ --}}
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="{{ $card }} p-6">
                <h2 class="{{ $h2 }}"><x-bi th="เข้าใช้งาน" en="Connect" /></h2>
                <p class="{{ $lead }}">
                    <x-bi th="เปิด Terminal (Mac/Linux) หรือ PowerShell (Windows) แล้วพิมพ์คำสั่งนี้"
                          en="Open Terminal (Mac/Linux) or PowerShell (Windows) and run:" />
                </p>

                @if($sshCommand)
                    {{-- จอแคบ: ปุ่มคัดลอกตกลงบรรทัดใหม่ ให้คำสั่งได้เต็มบรรทัดไม่ขาดกลาง IP --}}
                    <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl bg-slate-900 px-4 py-3" x-data="vpsCopy(@js($sshCommand))">
                        <p class="flex-1 min-w-[13rem] font-mono text-sm break-all">
                            <span class="text-slate-500 select-none" aria-hidden="true">$ </span><span class="text-emerald-300 select-all">{{ $sshCommand }}</span>
                        </p>
                        <button type="button" @click="copy()"
                                class="shrink-0 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-white text-xs font-medium transition">
                            <span x-show="!copied"><x-bi th="คัดลอก" en="Copy" /></span>
                            <span x-show="copied" x-cloak><x-bi th="คัดลอกแล้ว" en="Copied" /></span>
                        </button>
                    </div>
                @else
                    <p class="mt-4 rounded-xl bg-slate-100 dark:bg-slate-900/60 px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                        <x-bi th="เครื่องยังไม่ได้รับ IP — รีเฟรชอีกครั้งในไม่กี่นาที" en="No IP address yet — refresh again in a few minutes." />
                    </p>
                @endif

                <dl class="mt-5 grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div class="min-w-0">
                        <dt class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="ผู้ใช้" en="User" /></dt>
                        <dd class="font-mono text-slate-900 dark:text-white">root</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="รหัสผ่าน" en="Password" /></dt>
                        <dd class="text-slate-700 dark:text-slate-300">
                            <x-bi th="รหัสที่คุณตั้งไว้ — เราไม่เก็บและแสดงให้ดูไม่ได้" en="The one you set — we never keep or show it" />
                            <a href="#root-password" class="text-indigo-600 dark:text-indigo-400 hover:underline"><x-bi th="ตั้งใหม่" en="Reset" /></a>
                        </dd>
                    </div>
                    @if($server->ipv6)
                        <div class="min-w-0 sm:col-span-2">
                            <dt class="text-xs text-slate-500 dark:text-slate-400">IPv6</dt>
                            <dd class="font-mono text-slate-900 dark:text-white break-all select-all">{{ $server->ipv6 }}</dd>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <dt class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="ระบบปฏิบัติการ" en="Operating system" /></dt>
                        <dd class="text-slate-900 dark:text-white break-words">{{ $server->template_name ?: '—' }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="ศูนย์ข้อมูล" en="Data center" /></dt>
                        <dd class="text-slate-900 dark:text-white break-words">{{ $server->data_center_name ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="{{ $card }} p-6">
                <h2 class="{{ $h2 }}"><x-bi th="เปิด/ปิดเครื่อง" en="Power" /></h2>
                <p class="mt-1 inline-flex items-center gap-1.5 text-sm text-slate-700 dark:text-slate-300">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $power['dot'] }}" aria-hidden="true"></span>
                    <x-bi :th="$power['th']" :en="$power['en']" />
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('customer.vps.power', $server->id) }}" data-once>
                        @csrf
                        <input type="hidden" name="action" value="start">
                        <button type="submit" class="{{ $btnPrimary }}" @disabled($busy || $running)>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5v13a1 1 0 001.5.87l10.5-6.5a1 1 0 000-1.74L9.5 4.63A1 1 0 008 5.5z"/></svg>
                            <x-bi th="เปิดเครื่อง" en="Start" />
                        </button>
                    </form>
                    <form method="POST" action="{{ route('customer.vps.power', $server->id) }}" data-once
                          onsubmit="return confirm(@js($confirm['restart']))">
                        @csrf
                        <input type="hidden" name="action" value="restart">
                        <button type="submit" class="{{ $btnGhost }}" @disabled($busy || $stopped)>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <x-bi th="รีสตาร์ท" en="Restart" />
                        </button>
                    </form>
                    <form method="POST" action="{{ route('customer.vps.power', $server->id) }}" data-once
                          onsubmit="return confirm(@js($confirm['stop']))">
                        @csrf
                        <input type="hidden" name="action" value="stop">
                        <button type="submit" class="{{ $btnGhostDanger }}" @disabled($busy || $stopped)>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 3v8"/><path stroke-linecap="round" stroke-width="2" d="M6.3 6.3a8 8 0 1011.4 0"/></svg>
                            <x-bi th="ปิดเครื่อง" en="Stop" />
                        </button>
                    </form>
                </div>

                @if($busy)
                    {{-- ระหว่างรีสตาร์ท/ติดตั้งใหม่/กู้คืน ปุ่มถูกปิดไว้ — ถามสถานะเป็นระยะ
                         แล้วรีโหลดเมื่อเสร็จ ยกเว้นลูกค้ากำลังพิมพ์อะไรค้างในฟอร์มอยู่ --}}
                    <div x-data="vpsPoller(@js($statusUrl), 'idle', @js($powerText))"
                         class="mt-4 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-4 py-3 text-sm text-amber-900 dark:text-amber-200">
                        <p x-show="!ready">
                            <span class="font-semibold"><x-bi th="เครื่องกำลังทำงานอื่นอยู่" en="The server is busy with another task" /></span>
                            (<span x-text="label">{{ $powerText }}</span>)
                            — <x-bi th="ปุ่มจะกดได้อีกครั้งเมื่อเสร็จ หน้านี้จะอัปเดตเอง" en="the buttons come back when it's done; this page updates itself" />
                        </p>
                        <p x-show="halted" x-cloak class="mt-1 text-xs">
                            <x-bi th="หยุดตรวจสถานะอัตโนมัติแล้ว กรุณารีเฟรชหน้าเพื่อดูสถานะล่าสุด" en="Stopped checking automatically — refresh the page for the latest status." />
                        </p>
                        <p x-show="ready" x-cloak class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold"><x-bi th="เครื่องทำงานเสร็จแล้ว" en="All done" /></span>
                            <button type="button" onclick="window.location.reload()" class="underline font-semibold hover:no-underline">
                                <x-bi th="รีเฟรชหน้า" en="Refresh" />
                            </button>
                        </p>
                    </div>
                @endif

                <p class="mt-4 text-xs text-slate-500 dark:text-slate-400">
                    <x-bi th="ปิดเครื่องไม่หยุดรอบบิล — เครื่องยังเป็นของคุณ และคิดค่าบริการตามปกติจนหมดอายุ"
                          en="Stopping doesn't pause billing — the server stays yours, and billed, until it expires." />
                </p>
            </div>
        </div>

        {{-- ══════════ การใช้งาน ══════════ --}}
        @if($metrics)
            <div class="{{ $card }} p-6">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <h2 class="{{ $h2 }}"><x-bi th="การใช้งาน 24 ชั่วโมงล่าสุด" en="Last 24 hours" /></h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="ข้อมูลอัปเดตทุก 5 นาที" en="Updated every 5 minutes" /></p>
                </div>
                <div class="mt-4 grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="{{ $tile }}">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">CPU</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white tabular-nums">{{ $pct($metrics['cpu'] ?? null) }}</p>
                        @if(! empty($metrics['cpu_line']))
                            <svg viewBox="0 0 120 32" preserveAspectRatio="none" class="mt-2 w-full h-8 text-indigo-500 dark:text-indigo-400" aria-hidden="true">
                                <polyline points="{{ $metrics['cpu_line'] }}" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
                            </svg>
                        @endif
                    </div>
                    <div class="{{ $tile }}">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">RAM</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white tabular-nums">{{ $pct($metrics['ram_percent'] ?? null) }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 tabular-nums">
                            {{ $gb($metrics['ram_used'] ?? null) }}
                            @if($server->spec('memory_mb'))
                                <x-bi th="จาก" en="of" /> {{ \App\Models\VpsPlan::sizeLabel($server->spec('memory_mb')) }}
                            @endif
                        </p>
                        @if(! empty($metrics['ram_line']))
                            <svg viewBox="0 0 120 32" preserveAspectRatio="none" class="mt-2 w-full h-8 text-cyan-500 dark:text-cyan-400" aria-hidden="true">
                                <polyline points="{{ $metrics['ram_line'] }}" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
                            </svg>
                        @endif
                    </div>
                    <div class="{{ $tile }}">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400"><x-bi th="ดิสก์" en="Disk" /></p>
                        <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white tabular-nums">{{ $gb($metrics['disk_used'] ?? null) }}</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 tabular-nums">
                            {{ $pct($metrics['disk_percent'] ?? null) }}
                            @if($server->spec('disk_mb'))
                                <x-bi th="จาก" en="of" /> {{ \App\Models\VpsPlan::sizeLabel($server->spec('disk_mb')) }}
                            @endif
                        </p>
                        <div class="mt-3 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                            <div class="h-full rounded-full {{ $barTone($metrics['disk_percent'] ?? null) }}" style="width: {{ $barWidth($metrics['disk_percent'] ?? null) }}%"></div>
                        </div>
                    </div>
                    <div class="{{ $tile }}">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400"><x-bi th="ทราฟฟิก 24 ชม." en="Traffic, 24h" /></p>
                        <p class="mt-1 text-sm text-slate-900 dark:text-white tabular-nums">
                            <span class="text-slate-400" aria-hidden="true">↑</span> {{ $gb($metrics['traffic_out_gb'] ?? null, 2) }}
                            <span class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="ขาออก" en="out" /></span>
                        </p>
                        <p class="mt-1 text-sm text-slate-900 dark:text-white tabular-nums">
                            <span class="text-slate-400" aria-hidden="true">↓</span> {{ $gb($metrics['traffic_in_gb'] ?? null, 2) }}
                            <span class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="ขาเข้า" en="in" /></span>
                        </p>
                    </div>
                </div>
                @if($uptime)
                    <p class="mt-4 text-sm text-slate-600 dark:text-slate-400">
                        <x-bi th="เปิดต่อเนื่องมาแล้ว" en="Up for" />
                        <span class="font-semibold text-slate-900 dark:text-white"><x-bi :th="$uptime['th']" :en="$uptime['en']" /></span>
                    </p>
                @endif
            </div>
        @else
            <div class="{{ $card }} p-6">
                <h2 class="{{ $h2 }}"><x-bi th="การใช้งาน" en="Usage" /></h2>
                <p class="{{ $lead }}">
                    <x-bi th="ยังไม่มีข้อมูลการใช้งาน — กราฟจะเริ่มแสดงหลังเครื่องทำงานไปสักพัก"
                          en="No usage data yet — the graphs appear once the server has been running for a while." />
                </p>
            </div>
        @endif

        {{-- ══════════ รหัสผ่าน root + ชื่อโฮสต์ + ชี้โดเมน ══════════ --}}
        <div class="grid lg:grid-cols-2 gap-6">
            {{-- ไม่เติมค่ารหัสผ่านเดิมกลับเข้าช่องเด็ดขาด แม้ validation ไม่ผ่าน --}}
            <div id="root-password" class="{{ $card }} p-6 scroll-mt-24" x-data="vpsPassword()">
                <h2 class="{{ $h2 }}"><x-bi th="ตั้งรหัสผ่าน root" en="Root password" /></h2>
                <p class="{{ $lead }}">
                    <x-bi th="ลืมรหัสหรืออยากเปลี่ยน ตั้งใหม่ได้ทันทีโดยไม่ต้องติดตั้งเครื่องใหม่ — เราไม่เก็บรหัสผ่านของคุณ จึงแสดงให้ดูซ้ำไม่ได้"
                          en="Forgot it or want a new one? Set it here without reinstalling. We never keep your password, so we can't show it again." />
                </p>

                <form method="POST" action="{{ route('customer.vps.password', $server->id) }}" class="mt-4 space-y-3" data-once>
                    @csrf
                    <input type="hidden" name="section" value="password">
                    <div>
                        <label for="root_password" class="{{ $label }}"><x-bi th="รหัสผ่านใหม่" en="New password" /></label>
                        <div class="flex gap-2">
                            <input type="password" :type="show ? 'text' : 'password'" id="root_password" name="root_password" x-ref="pw"
                                   required minlength="12" maxlength="128" autocomplete="new-password" autocapitalize="off" spellcheck="false"
                                   class="{{ $field }} font-mono">
                            <button type="button" @click="show = !show" class="{{ $btnGhost }} shrink-0">
                                <span x-show="!show"><x-bi th="แสดง" en="Show" /></span>
                                <span x-show="show" x-cloak><x-bi th="ซ่อน" en="Hide" /></span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="root_password_confirmation" class="{{ $label }}"><x-bi th="ยืนยันรหัสผ่าน" en="Confirm password" /></label>
                        <input type="password" :type="show ? 'text' : 'password'" id="root_password_confirmation" name="root_password_confirmation" x-ref="confirm"
                               required minlength="12" maxlength="128" autocomplete="new-password" autocapitalize="off" spellcheck="false"
                               class="{{ $field }} font-mono">
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        <x-bi th="อย่างน้อย 12 ตัว มีทั้งตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข · ห้ามใช้รหัสที่เคยหลุดสู่สาธารณะ"
                              en="At least 12 characters with upper case, lower case and a number — and not one that has leaked before." />
                    </p>
                    <p x-show="generated" x-cloak class="rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-3 py-2 text-xs text-amber-900 dark:text-amber-200">
                        <x-bi th="คัดลอกหรือจดรหัสนี้ไว้ก่อนกดบันทึก — หลังจากนี้เราแสดงให้ดูอีกไม่ได้"
                              en="Copy or write it down before you save — we can't show it to you again afterwards." />
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" x-show="canGenerate" @click="generate()" class="{{ $btnGhost }}">
                            <x-bi th="สุ่มรหัสที่ปลอดภัย" en="Generate one" />
                        </button>
                        <button type="button" x-show="generated" x-cloak @click="copy()" class="{{ $btnGhost }}">
                            <span x-show="!copied"><x-bi th="คัดลอกรหัส" en="Copy" /></span>
                            <span x-show="copied" x-cloak><x-bi th="คัดลอกแล้ว" en="Copied" /></span>
                        </button>
                        <button type="submit" class="{{ $btnPrimary }}"><x-bi th="บันทึกรหัสผ่าน" en="Save password" /></button>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <div class="{{ $card }} p-6">
                    <h2 class="{{ $h2 }}"><x-bi th="ชื่อโฮสต์" en="Hostname" /></h2>
                    <p class="{{ $lead }}">
                        <x-bi th="ชื่อของเครื่อง เช่น server.example.com — เป็นแค่ป้ายชื่อ ไม่ได้ทำให้โดเมนชี้มาที่เครื่องนี้"
                              en="The machine's name, e.g. server.example.com. It's only a label — it doesn't point a domain here." />
                    </p>
                    <form method="POST" action="{{ route('customer.vps.hostname', $server->id) }}" class="mt-4 flex flex-col sm:flex-row gap-2" data-once>
                        @csrf
                        <input type="hidden" name="section" value="hostname">
                        <label for="hostname" class="sr-only">ชื่อโฮสต์ / Hostname</label>
                        <input type="text" id="hostname" name="hostname" value="{{ old('hostname', $server->hostname) }}"
                               required maxlength="253" autocomplete="off" autocapitalize="off" spellcheck="false" inputmode="url"
                               class="{{ $field }} font-mono">
                        <button type="submit" class="{{ $btnDark }} shrink-0"><x-bi th="บันทึก" en="Save" /></button>
                    </form>
                </div>

                <div class="{{ $card }} p-6">
                    <h2 class="{{ $h2 }}"><x-bi th="ชี้โดเมนมาที่เครื่องนี้" en="Point a domain here" /></h2>
                    <p class="{{ $lead }}">
                        <x-bi th="ตั้ง A record ของตัวโดเมน (@) ไปที่ IP ของเครื่องนี้ และให้ www ชี้ไปที่โดเมนเดียวกัน — ระเบียนอีเมลและระเบียนอื่นไม่ถูกแตะ · มีผลใน 5–30 นาที"
                              en="Sets the domain's A record (@) to this server's IP and points www at the domain. Mail and every other record are left alone. Takes 5–30 minutes." />
                    </p>
                    @if(! $server->ipv4)
                        <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
                            <x-bi th="เครื่องยังไม่ได้รับ IP — รอสักครู่แล้วรีเฟรช" en="The server has no IP yet — wait a moment and refresh." />
                        </p>
                    @elseif($domains->isNotEmpty())
                        <form method="POST" action="{{ route('customer.vps.point-domain', $server->id) }}" class="mt-4 flex flex-col sm:flex-row gap-2" data-once
                              onsubmit="return vpsConfirmPoint(this, @js($server->ipv4))">
                            @csrf
                            <label for="domain_id" class="sr-only">โดเมน / Domain</label>
                            {{-- ตัวเลือกเรนเดอร์จาก Blade ไม่ใช่ x-for (x-model อ่านค่าก่อนตัวเลือกจะมีจริง) --}}
                            <select id="domain_id" name="domain_id" required class="{{ $field }}">
                                @foreach($domains as $d)
                                    <option value="{{ $d->id }}" @selected((int) old('domain_id') === (int) $d->id)>{{ $d->domain }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="{{ $btnDark }} shrink-0"><x-bi th="ชี้มาที่นี่" en="Point it here" /></button>
                        </form>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            <x-bi :th="'ถ้าโดเมนใช้ nameserver ที่อื่น (เช่น Cloudflare) ให้ตั้ง A record ไปที่ ' . $server->ipv4 . ' ที่ผู้ให้บริการ DNS นั้นแทน'"
                                  :en="'If the domain uses someone else\'s nameservers (e.g. Cloudflare), add an A record for ' . $server->ipv4 . ' there instead.'" />
                        </p>
                    @else
                        <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3">
                            <p class="text-sm text-slate-500 dark:text-slate-400 flex-1"><x-bi th="ยังไม่มีโดเมนในบัญชีนี้" en="No domains on this account yet" /></p>
                            <a href="{{ route('domains.index') }}" class="{{ $btnGhost }} shrink-0"><x-bi th="จดโดเมนใหม่" en="Register a domain" /></a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══════════ สแนปช็อต + แบ็กอัป ══════════ --}}
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="{{ $card }} p-6">
                <h2 class="{{ $h2 }}"><x-bi th="สแนปช็อต" en="Snapshot" /></h2>
                <p class="{{ $lead }}">
                    <x-bi th="ภาพทั้งเครื่อง ณ เวลาหนึ่ง เก็บได้ครั้งละ 1 ชุด — ทำไว้ก่อนอัปเดตระบบหรือแก้ไขครั้งใหญ่ ถ้าพังก็ย้อนกลับได้"
                          en="A picture of the whole server at one moment, one at a time. Take one before a big update — if it breaks, roll back." />
                </p>

                @if($snapshot)
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg border border-slate-200 dark:border-slate-700 px-4 py-3 min-w-0">
                            <dt class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="สร้างเมื่อ" en="Taken" /></dt>
                            <dd class="font-semibold text-slate-900 dark:text-white">{{ $snapWhen ?? '—' }}</dd>
                        </div>
                        <div class="rounded-lg border border-slate-200 dark:border-slate-700 px-4 py-3 min-w-0">
                            <dt class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="หมดอายุ" en="Expires" /></dt>
                            <dd class="font-semibold text-slate-900 dark:text-white">{{ $when($snapshot['expires_at'] ?? null) ?? '—' }}</dd>
                        </div>
                    </dl>
                    @if(($snapshot['restore_minutes'] ?? 0) > 0)
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                            <x-bi :th="'กู้คืนใช้เวลาประมาณ ' . $snapshot['restore_minutes'] . ' นาที ระหว่างนั้นเครื่องใช้งานไม่ได้'"
                                  :en="'A restore takes about ' . $snapshot['restore_minutes'] . ' minutes, during which the server is offline.'" />
                        </p>
                    @endif
                @else
                    <p class="mt-4 text-sm text-slate-500 dark:text-slate-400"><x-bi th="ยังไม่มีสแนปช็อต" en="No snapshot yet" /></p>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('customer.vps.snapshot', $server->id) }}" data-once
                          @if($snapshot) onsubmit="return confirm(@js($confirm['snapshotCreate']))" @endif>
                        @csrf
                        <input type="hidden" name="action" value="create">
                        <button type="submit" class="{{ $btnDark }}" @disabled($busy)>
                            @if($snapshot)
                                <x-bi th="สร้างใหม่ (แทนที่อันเดิม)" en="Take a new one (replaces it)" />
                            @else
                                <x-bi th="สร้างสแนปช็อต" en="Take a snapshot" />
                            @endif
                        </button>
                    </form>
                    @if($snapshot)
                        <form method="POST" action="{{ route('customer.vps.snapshot', $server->id) }}" data-once
                              onsubmit="return confirm(@js($confirm['snapshotRestore']))">
                            @csrf
                            <input type="hidden" name="action" value="restore">
                            <button type="submit" class="{{ $btnGhost }}" @disabled($busy)><x-bi th="กู้คืนจากสแนปช็อต" en="Restore" /></button>
                        </form>
                        <form method="POST" action="{{ route('customer.vps.snapshot', $server->id) }}" data-once
                              onsubmit="return confirm(@js($confirm['snapshotDelete']))">
                            @csrf
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="{{ $btnGhostDanger }}" @disabled($busy)><x-bi th="ลบ" en="Delete" /></button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="{{ $card }} p-6">
                <h2 class="{{ $h2 }}"><x-bi th="แบ็กอัปรายสัปดาห์" en="Weekly backups" /></h2>
                <p class="{{ $lead }}">
                    <x-bi th="ระบบสำรองทั้งเครื่องให้อัตโนมัติทุกสัปดาห์ กู้คืนเองได้จากที่นี่"
                          en="The whole server is backed up automatically every week. Restore one yourself from here." />
                </p>

                @if(! empty($backups))
                    <ul class="mt-4 space-y-2">
                        @foreach($backups as $b)
                            @php
                                $backupLabel = $when($b['created_at'] ?? null) ?? ('#' . $b['id']);
                                $backupConfirm = 'กู้คืนเครื่องจากแบ็กอัป ' . $backupLabel
                                    . "?\n\nข้อมูลทั้งหมดในเครื่องจะถูกแทนที่ด้วยข้อมูลในแบ็กอัป — ไฟล์ที่สร้างหรือแก้ไขหลังจากนั้นจะหายไป และย้อนกลับไม่ได้";
                            @endphp
                            <li class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 dark:border-slate-700 px-4 py-2.5">
                                <div class="min-w-0 text-sm">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ $backupLabel }}</p>
                                    @if(($b['size_gb'] ?? 0) > 0)
                                        <p class="text-xs text-slate-500 dark:text-slate-400 tabular-nums">{{ number_format((float) $b['size_gb'], 1) }} GB</p>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('customer.vps.backup-restore', [$server->id, $b['id']]) }}" data-once
                                      onsubmit="return confirm(@js($backupConfirm))">
                                    @csrf
                                    <button type="submit" class="{{ $btnGhost }}" @disabled($busy)><x-bi th="กู้คืน" en="Restore" /></button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 rounded-lg bg-slate-100 dark:bg-slate-900/60 px-4 py-3 text-sm text-slate-600 dark:text-slate-400">
                        <x-bi th="ระบบสำรองข้อมูลรายสัปดาห์จะเริ่มสร้างแบ็กอัปแรกภายใน 7 วัน"
                              en="The weekly backup makes its first copy within 7 days." />
                    </p>
                @endif
            </div>
        </div>

        {{-- ══════════ DANGER ZONE — ติดตั้ง OS ใหม่ ══════════
             ปุ่มที่ทำลายที่สุดในหน้า: พับไว้ก่อน และต้องพิมพ์ชื่อโฮสต์ยืนยัน
             เพราะ confirm() เฉยๆ คนกดผ่านโดยไม่อ่าน (controller ตรวจซ้ำอีกชั้น)
             ห้ามใช้ bg-white ที่กล่องนี้: customer-premium เขียน .bg-white ทับ
             border เป็นเส้นม่วงบาง 1px (สไตล์นอก layer ชนะ utility เสมอ) ขอบแดงจะหายไป --}}
        <div class="rounded-2xl border-2 border-red-300 dark:border-red-500/40 bg-red-50/40 dark:bg-slate-800 p-6"
             x-data="vpsReinstall(@js(['hostname' => $server->hostname, 'open' => $failedSection === 'reinstall', 'busy' => $busy, 'confirmText' => $confirm['reinstall']]))">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-wider text-red-600 dark:text-red-400">Danger zone</p>
                    <h2 class="{{ $h2 }} mt-1"><x-bi th="ติดตั้งระบบปฏิบัติการใหม่" en="Reinstall the operating system" /></h2>
                    <p class="{{ $lead }}">
                        <x-bi th="ล้างดิสก์ทั้งหมดแล้วติดตั้งใหม่ — ใช้เมื่อเครื่องพังจนแก้ไม่ได้ หรืออยากเปลี่ยนระบบปฏิบัติการ"
                              en="Wipe the disk and start again — for a server broken beyond repair, or to switch operating system." />
                    </p>
                </div>
                <button type="button" @click="open = !open" class="{{ $btnGhostDanger }} shrink-0" aria-controls="reinstall-panel" :aria-expanded="open ? 'true' : 'false'">
                    <span x-show="!open"><x-bi th="เปิด" en="Show" /></span>
                    <span x-show="open" x-cloak><x-bi th="ปิด" en="Hide" /></span>
                </button>
            </div>

            <div id="reinstall-panel" x-show="open" x-cloak class="mt-5 pt-5 border-t border-red-200 dark:border-red-500/30">
                <div class="rounded-lg bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-4 py-3 mb-4 text-sm text-red-900 dark:text-red-200">
                    <p class="font-semibold"><x-bi th="ลบข้อมูลทั้งหมดและสแนปช็อต กู้คืนไม่ได้" en="Deletes all data and the snapshot — this cannot be undone" /></p>
                    <p class="mt-1"><x-bi th="ไฟล์ ฐานข้อมูล และการตั้งค่าทุกอย่างในเครื่องจะหายไป — สำรองสิ่งที่ต้องการออกไปก่อน"
                                          en="Every file, database and setting on the server goes. Copy off anything you need first." /></p>
                </div>

                @if(empty($templates))
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <x-bi th="โหลดรายการระบบปฏิบัติการไม่ได้ในขณะนี้ กรุณารีเฟรชหน้าอีกครั้ง" en="Couldn't load the list of operating systems — please refresh the page." />
                    </p>
                @else
                    <form method="POST" action="{{ route('customer.vps.reinstall', $server->id) }}" class="space-y-4" data-once
                          @submit="confirmSubmit($event)">
                        @csrf
                        <input type="hidden" name="section" value="reinstall">

                        <div>
                            <label for="template_id" class="{{ $label }}"><x-bi th="ระบบปฏิบัติการ" en="Operating system" /></label>
                            {{-- ตัวเลือกเรนเดอร์จาก Blade ไม่ใช่ x-for — x-model อ่านค่าตั้งแต่ก่อน
                                 ตัวเลือกจะมีจริง แล้ว select ตกไปที่ตัวแรก --}}
                            <select id="template_id" name="template_id" required class="{{ $field }}">
                                @foreach($templates as $group => $list)
                                    <optgroup label="{{ (\App\Support\VpsCatalog::GROUPS[$group]['th'] ?? $group) . ' / ' . (\App\Support\VpsCatalog::GROUPS[$group]['en'] ?? $group) }}">
                                        @foreach($list as $t)
                                            <option value="{{ $t['id'] }}" @selected($selectedTemplate === (int) $t['id'])>{{ $t['name'] }}{{ ! empty($t['licensed']) ? ' (ต้องซื้อไลเซนส์แยก)' : '' }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div x-data="vpsPassword()">
                            <label for="reinstall_root_password" class="{{ $label }}"><x-bi th="รหัสผ่าน root ของระบบใหม่" en="Root password for the new system" /></label>
                            <div class="flex gap-2">
                                <input type="password" :type="show ? 'text' : 'password'" id="reinstall_root_password" name="root_password" x-ref="pw"
                                       required minlength="12" maxlength="128" autocomplete="new-password" autocapitalize="off" spellcheck="false"
                                       class="{{ $field }} font-mono">
                                <button type="button" @click="show = !show" class="{{ $btnGhost }} shrink-0">
                                    <span x-show="!show"><x-bi th="แสดง" en="Show" /></span>
                                    <span x-show="show" x-cloak><x-bi th="ซ่อน" en="Hide" /></span>
                                </button>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                                <x-bi th="อย่างน้อย 12 ตัว มีทั้งตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข"
                                      en="At least 12 characters with upper case, lower case and a number." />
                            </p>
                            <p x-show="generated" x-cloak class="mt-2 rounded-lg bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-3 py-2 text-xs text-amber-900 dark:text-amber-200">
                                <x-bi th="คัดลอกหรือจดรหัสนี้ไว้ก่อนกดติดตั้ง — หลังจากนี้เราแสดงให้ดูอีกไม่ได้"
                                      en="Copy or write it down before you reinstall — we can't show it to you again afterwards." />
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button type="button" x-show="canGenerate" @click="generate()" class="{{ $btnGhost }}">
                                    <x-bi th="สุ่มรหัสที่ปลอดภัย" en="Generate one" />
                                </button>
                                <button type="button" x-show="generated" x-cloak @click="copy()" class="{{ $btnGhost }}">
                                    <span x-show="!copied"><x-bi th="คัดลอกรหัส" en="Copy" /></span>
                                    <span x-show="copied" x-cloak><x-bi th="คัดลอกแล้ว" en="Copied" /></span>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="confirm_hostname" class="{{ $label }}">
                                <x-bi th="พิมพ์ชื่อโฮสต์เพื่อยืนยัน" en="Type the hostname to confirm" />:
                                <span class="font-mono break-all">{{ $server->hostname }}</span>
                            </label>
                            <input type="text" id="confirm_hostname" name="confirm_hostname" x-model="typed"
                                   required autocomplete="off" autocapitalize="off" spellcheck="false"
                                   class="{{ $field }} font-mono">
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="{{ $btnDanger }}" @disabled($busy) :disabled="busy || !matches">
                                <x-bi th="ลบทุกอย่างและติดตั้งใหม่" en="Wipe and reinstall" />
                            </button>
                            @if($busy)
                                <p class="text-xs text-amber-700 dark:text-amber-300"><x-bi th="เครื่องกำลังทำงานอื่นอยู่ — รอให้เสร็จก่อน" en="The server is busy — wait for it to finish first" /></p>
                            @else
                                <p x-show="typed.length > 0 && !matches" x-cloak class="text-xs text-red-600 dark:text-red-400"><x-bi th="ชื่อโฮสต์ยังไม่ตรง" en="The hostname doesn't match yet" /></p>
                            @endif
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    {{-- ══════════ ต่ออายุและการชำระเงิน ══════════ --}}
    <div class="{{ $card }} p-6">
        <h2 class="{{ $h2 }}"><x-bi th="ต่ออายุและการชำระเงิน" en="Renewal & billing" /></h2>

        <div class="mt-4 grid sm:grid-cols-2 gap-4">
            @if($status !== \App\Models\VpsInstance::STATUS_REFUNDED)
                <div class="{{ $tile }}">
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-1"><x-bi th="ต่ออายุอัตโนมัติ" en="Auto-renew" /></h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                        <x-bi :th="'ตัดจากกระเป๋าเงินก่อนหมดอายุ ' . $chargeDays . ' วัน แจ้งล่วงหน้าทุกครั้ง'"
                              :en="'Charged from your wallet ' . $chargeDays . ' days before expiry — we always tell you first.'" />
                    </p>
                    <form method="POST" action="{{ route('customer.vps.auto-renew', $server->id) }}" data-once>
                        @csrf
                        <input type="hidden" name="auto_renew" value="{{ $server->auto_renew ? 0 : 1 }}">
                        <button type="submit"
                                class="px-5 py-2.5 rounded-lg text-sm font-semibold transition {{ $server->auto_renew
                                    ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 hover:bg-emerald-200'
                                    : 'bg-slate-800 dark:bg-slate-600 text-white hover:bg-slate-700' }}">
                            @if($server->auto_renew)
                                <x-bi th="เปิดอยู่ — กดเพื่อปิด" en="On — tap to turn off" />
                            @else
                                <x-bi th="ปิดอยู่ — กดเพื่อเปิด" en="Off — tap to turn on" />
                            @endif
                        </button>
                    </form>
                </div>
            @endif

            <div class="{{ $tile }}">
                <h3 class="font-semibold text-slate-900 dark:text-white mb-1"><x-bi th="ต่ออายุตอนนี้" en="Renew now" /></h3>
                @if($renewable)
                    {{-- ต่ออายุเองได้ตลอด ไม่ต้องรอรอบอัตโนมัติ — คนที่ปิดสวิตช์ไว้ก็ต้องมีทางจ่าย --}}
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                        <x-bi :th="'อีก ' . $spanTh . ' เป็นเงิน ' . $renewDisplay . ' — ' . $renewBasisTh"
                              :en="'One more period for ' . $renewDisplay . ' — ' . $renewBasisEn . '.'" />
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <form method="POST" action="{{ route('customer.vps.renew', $server->id) }}" data-once
                              onsubmit="return confirm(@js($confirm['renew']))">
                            @csrf
                            <button type="submit" class="{{ $btnRenew }}">
                                <x-bi th="ต่ออายุตอนนี้" en="Renew now" /> · {{ $renewDisplay }}
                            </button>
                        </form>
                        <a href="{{ route('user.wallet.topup') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"><x-bi th="เติมเงินเข้ากระเป๋า" en="Top up wallet" /></a>
                    </div>
                @elseif($pendingRenew)
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        <x-bi th="กำลังยืนยันการต่ออายุกับระบบ ไม่ต้องกดซ้ำ — วันหมดอายุจะอัปเดตภายในไม่กี่นาที"
                              en="Your renewal is being confirmed — no need to press again. The expiry date updates within minutes." />
                    </p>
                @elseif(in_array($status, [\App\Models\VpsInstance::STATUS_ACTIVE, \App\Models\VpsInstance::STATUS_EXPIRED], true))
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <x-bi th="ตอนนี้ยังต่ออายุออนไลน์ไม่ได้ กรุณาติดต่อทีมงาน" en="Online renewal isn't available right now — please contact our team." />
                        <a href="{{ route('customer.support.create') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline"><x-bi th="แจ้งทีมงาน" en="Contact support" /></a>
                    </p>
                @else
                    <p class="text-sm text-slate-600 dark:text-slate-400">
                        <x-bi th="ต่ออายุได้เมื่อเซิร์ฟเวอร์พร้อมใช้งาน" en="Renewal opens once the server is up and running." />
                    </p>
                @endif
            </div>
        </div>

        @if($payments->isNotEmpty())
            <h3 class="font-semibold text-slate-900 dark:text-white mt-6 mb-3"><x-bi th="ประวัติการชำระเงิน" en="Payment history" /></h3>
            <ul class="space-y-2">
                @foreach($payments as $p)
                    @php
                        // สตริงคลาสเต็ม ไม่ต่อชื่อสีเอง — Tailwind เห็นเฉพาะคลาสทั้งคำ
                        $pill = match ($p->status) {
                            \App\Models\VpsPayment::STATUS_PAID => ['th' => 'ชำระแล้ว', 'en' => 'Paid', 'classes' => 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300'],
                            \App\Models\VpsPayment::STATUS_REFUNDED => ['th' => 'คืนเงินแล้ว', 'en' => 'Refunded', 'classes' => 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'],
                            default => ['th' => 'กำลังดำเนินการ', 'en' => 'Processing', 'classes' => 'bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300'],
                        };
                        $refunded = $p->status === \App\Models\VpsPayment::STATUS_REFUNDED;
                    @endphp
                    <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                @if($p->kind === \App\Models\VpsPayment::KIND_RENEW)
                                    <x-bi th="ต่ออายุ" en="Renewal" />
                                @else
                                    <x-bi th="เช่าครั้งแรก" en="First rental" />
                                @endif
                                @if($p->months)
                                    <span class="font-normal text-slate-500 dark:text-slate-400">· {{ $p->months }} <x-bi th="เดือน" en="mo" /></span>
                                @endif
                            </p>
                            @if($p->created_at)
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $p->created_at->copy()->timezone('Asia/Bangkok')->format('j M Y H:i') }} น.</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $pill['classes'] }}">
                                <x-bi :th="$pill['th']" :en="$pill['en']" />
                            </span>
                            <span class="text-sm font-bold tabular-nums {{ $refunded ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-900 dark:text-white' }}">
                                {{ \App\Support\VpsPricing::format((float) $p->amount_thb) }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
// ── คัดลอกข้อความ (IP, คำสั่ง ssh, รหัสที่สุ่ม) ────────────────────────────
// clipboard API ใช้ได้เฉพาะหน้า https — มีทางสำรองผ่าน textarea ชั่วคราว
function vpsCopyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(text).then(() => true, () => vpsCopyFallback(text));
    }

    return Promise.resolve(vpsCopyFallback(text));
}

function vpsCopyFallback(text) {
    const area = document.createElement('textarea');
    area.value = text;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.opacity = '0';
    document.body.appendChild(area);
    area.select();
    let ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    area.remove();

    return ok;
}

function vpsCopy(text) {
    return {
        copied: false,
        copy() {
            vpsCopyText(text).then((ok) => {
                if (!ok) return;
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            });
        },
    };
}

// ── สุ่มรหัสผ่าน ─────────────────────────────────────────────────────────
// สุ่มในเบราว์เซอร์เท่านั้น ไม่ส่งไปไหนจนกว่าลูกค้าจะกดบันทึกเอง
// rejection sampling: ใช้ % ตรงๆ ตัวอักษรต้นชุดจะออกบ่อยกว่าตัวอื่น
function vpsRandomIndex(n) {
    const limit = Math.floor(0x100000000 / n) * n;
    const buf = new Uint32Array(1);
    do { crypto.getRandomValues(buf); } while (buf[0] >= limit);

    return buf[0] % n;
}

// 16 ตัว มีตัวใหญ่ ตัวเล็ก ตัวเลขอย่างน้อยอย่างละตัว ตัด 0/O/1/l/I ที่อ่านสับสนออก
// ไม่ใส่สัญลักษณ์: พิมพ์ยากบนมือถือและใน console ของบางระบบ
function vpsGeneratePassword(length) {
    const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const lower = 'abcdefghijkmnpqrstuvwxyz';
    const digits = '23456789';
    const all = upper + lower + digits;
    const chars = [upper, lower, digits].map((set) => set[vpsRandomIndex(set.length)]);
    while (chars.length < length) chars.push(all[vpsRandomIndex(all.length)]);
    for (let i = chars.length - 1; i > 0; i--) {
        const j = vpsRandomIndex(i + 1);
        [chars[i], chars[j]] = [chars[j], chars[i]];
    }

    return chars.join('');
}

function vpsPassword() {
    return {
        show: false,
        generated: false,
        copied: false,
        canGenerate: !!(window.crypto && window.crypto.getRandomValues),

        generate() {
            if (!this.canGenerate) return;
            const value = vpsGeneratePassword(16);
            this.$refs.pw.value = value;
            if (this.$refs.confirm) this.$refs.confirm.value = value;
            this.show = true;
            this.generated = true;
            this.copied = false;
        },

        copy() {
            vpsCopyText(this.$refs.pw.value).then((ok) => {
                if (!ok) return;
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            });
        },
    };
}

// ── ติดตั้ง OS ใหม่: ต้องพิมพ์ชื่อโฮสต์ตรง แล้วยืนยันอีกครั้ง ──────────────
function vpsReinstall(config) {
    return {
        open: !!config.open,
        busy: !!config.busy,
        typed: '',

        get matches() {
            return this.typed.trim().toLowerCase() === String(config.hostname).toLowerCase();
        },

        confirmSubmit(event) {
            if (this.busy || !this.matches || !window.confirm(config.confirmText)) {
                event.preventDefault();
            }
        },
    };
}

// ── ชี้โดเมน: ชื่อโดเมนอยู่ใน select จึงประกอบข้อความยืนยันตอนกด ──────────
function vpsConfirmPoint(form, ip) {
    const select = form.elements['domain_id'];
    const option = select ? select.options[select.selectedIndex] : null;
    const domain = option ? option.text.trim() : '';

    return window.confirm(
        'ชี้ ' + domain + ' และ www.' + domain + ' มาที่ ' + ip + ' ?\n\n' +
        'ถ้าโดเมนนี้เปิดเว็บอยู่ที่อื่น เว็บเดิมจะเข้าไม่ได้หลังจากนี้ · ระเบียนอีเมลไม่ถูกแตะ'
    );
}

// ── ถามสถานะเครื่องเป็นระยะ ─────────────────────────────────────────────
//   settle — ระหว่างติดตั้ง: รอจน settled แล้วรีโหลด
//   idle   — ระหว่างเครื่องทำงานอื่น: รอจน busy = false แล้วรีโหลด
// หยุดถามตอนแท็บถูกซ่อน (โควตาเรียกเครื่องทั้งเว็บมีจำกัด) แล้วถามต่อเมื่อกลับมา
// ถ้าลูกค้าพิมพ์อะไรค้างในฟอร์มอยู่ จะไม่รีโหลดทับ แต่ขึ้นปุ่มให้รีเฟรชเอง
function vpsFormsDirty() {
    return Array.from(document.querySelectorAll('form input[type=text], form input[type=password]'))
        .some((el) => el.value !== el.defaultValue);
}

function vpsPoller(url, mode, initialLabel) {
    const every = mode === 'settle' ? 10000 : 15000;

    return {
        label: initialLabel,
        ready: false,      // เสร็จแล้ว แต่ไม่รีโหลดเพราะมีฟอร์มพิมพ์ค้าง
        halted: false,     // เลิกถามเพราะหลุดล็อกอิน/ไม่พบเครื่อง
        stopped: false,
        timer: null,
        inflight: false,
        failures: 0,

        init() {
            this.schedule(every);
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && !this.timer && !this.inflight && !this.stopped) this.tick();
            });
        },

        schedule(ms) {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => { this.timer = null; this.tick(); }, ms);
        },

        async tick() {
            if (this.stopped || this.inflight || document.hidden) return;
            this.inflight = true;
            try {
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                // หลุดล็อกอิน / ไม่ใช่เครื่องของเรา — ถามต่อก็ไม่มีประโยชน์
                if ([401, 403, 404, 419].includes(res.status)) {
                    this.stopped = true;
                    this.halted = true;
                    return;
                }
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const body = await res.json();
                this.failures = 0;
                if (body.state_label) this.label = body.state_label;
                const finished = mode === 'settle' ? body.settled === true : body.busy === false;
                if (finished) {
                    this.stopped = true;
                    if (vpsFormsDirty()) {
                        this.ready = true;
                    } else {
                        window.location.reload();
                    }
                    return;
                }
            } catch (e) {
                // 429 / เน็ตสะดุด: ถามห่างขึ้นเรื่อยๆ สูงสุดนาทีละครั้ง
                this.failures++;
            } finally {
                this.inflight = false;
            }
            if (!this.stopped) this.schedule(Math.min(60000, every * (1 + this.failures)));
        },
    };
}

// ── กันกดซ้ำ ─────────────────────────────────────────────────────────────
// ปุ่มที่สั่งเครื่องจริงหรือใช้เงิน กดสองครั้งไม่ควรกลายเป็นสองคำสั่ง
// (ฟอร์มที่ confirm() ถูกยกเลิก หรือถูก preventDefault จะไม่โดนล็อก)
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-once') || event.defaultPrevented) return;
    setTimeout(() => {
        form.querySelectorAll('button[type=submit]').forEach((button) => {
            if (button.disabled) return;
            button.disabled = true;
            button.dataset.onceLocked = '1';
            button.classList.add('cursor-wait');
        });
    }, 0);
});

// กลับมาหน้านี้ด้วยปุ่ม Back (bfcache) — ปลดเฉพาะปุ่มที่เราล็อกเอง
// ปุ่มที่ปิดไว้จากฝั่งเซิร์ฟเวอร์ (เครื่องกำลังทำงานอื่น) ยังปิดอยู่ตามเดิม
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    document.querySelectorAll('button[data-once-locked]').forEach((button) => {
        button.disabled = false;
        button.removeAttribute('data-once-locked');
        button.classList.remove('cursor-wait');
    });
});
</script>
@endpush
