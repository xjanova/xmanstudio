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
