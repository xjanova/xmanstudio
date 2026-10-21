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
        'recovery' => 'บูต ' . $server->hostname . " เข้าโหมดกู้ระบบ?\n\nเว็บและบริการทั้งหมดบนเครื่องจะหยุดจนกว่าจะออกจากโหมดนี้",
        'reinstall' => 'ยืนยันครั้งสุดท้าย: ลบทุกอย่างใน ' . $server->hostname
            . " แล้วติดตั้งระบบใหม่?\n\nข้อมูลทั้งหมดและสแนปช็อตจะหายไป กู้คืนไม่ได้",
    ];

    // ฟอร์มที่เพิ่งส่งไม่ผ่าน validation — ช่องซ่อน section ถูก flash กลับมาพร้อม
    // input ใช้เปิดส่วนติดตั้ง OS ใหม่ค้างไว้ให้แก้ต่อ แทนที่จะพับหายไป
    $failedSection = $errors->any() ? old('section') : null;

    // แท็บของหน้า — ลิงก์ธรรมดา (?tab=) ไม่ใช่สลับด้วย JS: แต่ละแท็บดึงข้อมูลจากเครื่อง
    // เฉพาะที่ตัวเองแสดง และฟอร์มบันทึกเสร็จกลับมาแท็บเดิมได้โดยไม่ต้องใช้ JS
    $tabUrl = fn (string $key, array $extra = []) => route('customer.vps.show', array_merge(['id' => $server->id, 'tab' => $key], $extra));
    $tabs = [
        'overview' => ['th' => 'ภาพรวม', 'en' => 'Overview', 'icon' => 'M4 13h6V4H4v9zm0 7h6v-5H4v5zm10 0h6v-9h-6v9zm0-16v5h6V4h-6z'],
        'network' => ['th' => 'ความปลอดภัย', 'en' => 'Security & network', 'icon' => 'M12 3l7 3v6c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9V6l7-3z'],
        'backups' => ['th' => 'สำรองข้อมูล', 'en' => 'Backups', 'icon' => 'M4 7h3l2-3h6l2 3h3v12H4V7zm8 9.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7z'],
        'system' => ['th' => 'ระบบ', 'en' => 'System', 'icon' => 'M12 15.5a3.5 3.5 0 100-7 3.5 3.5 0 000 7zm7.4-2.5l1.6 1.2-2 3.4-1.9-.7a7 7 0 01-1.7 1l-.3 2.1h-4l-.3-2.1a7 7 0 01-1.7-1l-1.9.7-2-3.4L4.6 13a7 7 0 010-2L3 9.8l2-3.4 1.9.7a7 7 0 011.7-1L8.9 4h4l.3 2.1a7 7 0 011.7 1l1.9-.7 2 3.4-1.6 1.2a7 7 0 010 2z'],
        'activity' => ['th' => 'ประวัติ', 'en' => 'Activity', 'icon' => 'M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'billing' => ['th' => 'การเงิน', 'en' => 'Billing', 'icon' => 'M3 10h18M7 15h2m4 0h4M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z'],
    ];
    $inRecovery = in_array($server->state, ['recovery', 'stopping_recovery'], true);
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
            <a href="{{ $tabUrl('system') }}#root-password" class="{{ $btnDanger }} shrink-0"><x-bi th="ตั้งรหัสผ่านตอนนี้" en="Set it now" /></a>
        </div>
    @endif

    @if($manageable && $inRecovery && $tab !== 'system')
        <div class="rounded-xl bg-violet-50 dark:bg-violet-500/10 border border-violet-200 dark:border-violet-500/30 px-5 py-4 text-sm text-violet-900 dark:text-violet-200 flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="font-semibold mb-1"><x-bi th="เครื่องอยู่ในโหมดกู้ระบบ" en="The server is in recovery mode" /></p>
                <p><x-bi th="เว็บและบริการปกติหยุดอยู่ — ดิสก์เดิมอยู่ที่ /mnt ออกจากโหมดนี้ได้ที่แท็บระบบ"
                         en="Normal services are down — your disk is at /mnt. Leave recovery mode on the System tab." /></p>
            </div>
            <a href="{{ $tabUrl('system') }}#recovery" class="{{ $btnPrimary }} shrink-0"><x-bi th="ไปที่โหมดกู้ระบบ" en="Go to recovery" /></a>
        </div>
    @endif

    @if($manageable)
        {{-- เครื่องกำลังรีสตาร์ท/ติดตั้งใหม่/กู้คืน: ปุ่มทุกแท็บถูกปิดไว้ — ถามสถานะเป็นระยะ
             แล้วรีโหลดเมื่อเสร็จ อยู่เหนือแถบแท็บเพื่อให้ทำงานไม่ว่าจะอยู่แท็บไหน --}}
        @if($busy)
            <div x-data="vpsPoller(@js($statusUrl), 'idle', @js($powerText))"
                 class="rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/30 px-5 py-4 text-sm text-amber-900 dark:text-amber-200">
                <p x-show="!ready" class="flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" class="opacity-25"/>
                        <path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                    <span>
                        <span class="font-semibold"><x-bi th="เครื่องกำลังทำงานอื่นอยู่" en="The server is busy with another task" /></span>
                        (<span x-text="label">{{ $powerText }}</span>)
                        — <x-bi th="ปุ่มจะกดได้อีกครั้งเมื่อเสร็จ หน้านี้จะอัปเดตเอง" en="the buttons come back when it's done; this page updates itself" />
                    </span>
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

        {{-- ══════════ แถบแท็บ ══════════
             เลื่อนแนวนอนได้บนจอแคบ — ชื่อแท็บไทยยาว ห้ามตัดคำกลางแท็บ --}}
        <nav class="-mx-1 overflow-x-auto" aria-label="ส่วนของหน้า / Sections">
            <ul class="flex w-full min-w-max gap-1 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 p-1 mx-1">
                @foreach($tabs as $key => $t)
                    <li class="flex-1">
                        <a href="{{ $tabUrl($key) }}"
                           @if($tab === $key) aria-current="page" @endif
                           class="flex items-center justify-center gap-2 whitespace-nowrap rounded-xl px-3 py-2 transition {{ $tab === $key
                               ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-900/30'
                               : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-white/60 dark:hover:bg-slate-700/60' }}">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $t['icon'] }}"/></svg>
                            <span class="flex flex-col items-start leading-tight">
                                <span class="text-sm font-semibold">{{ $t['th'] }}</span>
                                <span class="text-[10px] font-normal opacity-70">{{ $t['en'] }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @include('customer.vps.tabs.' . $tab)
    @else
        @include('customer.vps.tabs.billing')
    @endif
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
// ค่าตั้งต้นของทุกช่องหลัง Alpine เติมค่าเสร็จ — ช่องที่ x-model หรือ x-for สร้าง
// ไม่มี defaultValue ให้เทียบ จะถูกนับว่า "แก้ค้าง" ตลอดถ้าเทียบกับค่านั้น
const vpsInitialValues = new WeakMap();
const vpsFieldSelector = 'form input[type=text], form input[type=password], form textarea';
document.addEventListener('alpine:initialized', () => {
    document.querySelectorAll(vpsFieldSelector).forEach((el) => vpsInitialValues.set(el, el.value));
});

function vpsFormsDirty() {
    return Array.from(document.querySelectorAll(vpsFieldSelector))
        .some((el) => el.value !== (vpsInitialValues.has(el) ? vpsInitialValues.get(el) : el.defaultValue));
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

// ── ตัวแก้กฎไฟร์วอลล์ ─────────────────────────────────────────────────────
// ชนิดที่มีพอร์ตในตัว (SSH, HTTP …) ล็อกช่องพอร์ตไว้ · เตือนเมื่อไม่มีกฎที่เปิด SSH
// เพราะไฟร์วอลล์ปิดทุกอย่างที่ไม่มีกฎ — ไม่มี SSH = เจ้าของเข้าเครื่องตัวเองไม่ได้
function vpsFirewallRules(config) {
    let serial = 0;
    const protocols = config.protocols || {};
    const toRow = (r) => ({
        _k: ++serial,
        protocol: r && protocols[r.protocol] ? r.protocol : 'TCP',
        port: r && r.port != null ? String(r.port) : '',
        source_detail: r && r.source_detail && String(r.source_detail).toLowerCase() !== 'any' ? String(r.source_detail) : '',
    });

    const snapshot = (rows) => JSON.stringify(rows.map((r) => [r.protocol, String(r.port || '').trim(), String(r.source_detail || '').trim()]));
    const rows = (config.rows || []).map(toRow);

    return {
        protocols,
        rows,
        confirmNoSsh: !!config.confirmNoSsh,
        // เทียบกับกฎที่บันทึกไว้จริง — แถวที่กลับมาจาก validation ไม่ผ่านยังไม่ได้บันทึก
        initial: snapshot((config.saved || config.rows || []).map(toRow)),

        // แก้กฎค้างอยู่ยังไม่บันทึก — ปุ่ม "เปิดไฟร์วอลล์" ใช้กฎที่บันทึกไว้
        // ไม่ใช่ที่เห็นบนจอ จึงต้องให้บันทึกก่อน
        get dirty() {
            return snapshot(this.rows) !== this.initial;
        },

        portFixed(row) {
            const p = protocols[row.protocol];
            return !!(p && p.port !== null);
        },

        fixPort(row) {
            const p = protocols[row.protocol];
            if (p && p.port !== null) {
                row.port = p.port;
            } else if (Object.values(protocols).some((x) => x.port === row.port)) {
                row.port = '';
            }
        },

        add() {
            this.rows.push(toRow({ protocol: 'TCP', port: '', source_detail: '' }));
        },

        remove(i) {
            this.rows.splice(i, 1);
        },

        covers(port, wanted) {
            const text = String(port || '').trim().toLowerCase();
            if (text === '' || text === 'any') return true;
            const range = text.match(/^(\d+):(\d+)$/);
            if (range) return Number(range[1]) <= wanted && wanted <= Number(range[2]);
            return Number(text) === wanted;
        },

        get allowsSsh() {
            return this.rows.some((r) => r.protocol === 'SSH' || (r.protocol === 'TCP' && this.covers(r.port, 22)));
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
