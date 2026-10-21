@extends($adminLayout ?? 'layouts.admin')

@section('title', 'GPUxMINE · ' . $node->displayName())
@section('page-title', 'GPUxMINE · ' . $node->displayName())

@section('content')
@php
    $card = 'rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
    $input = 'w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent';
    $bkk = static fn ($date, string $format = 'd/m/Y H:i') => $date ? $date->copy()->timezone('Asia/Bangkok')->format($format) . ' น.' : '—';

    $name = $node->displayName();
    $live = ! $node->trashed();
    $owner = $node->user ? $node->user->name . ' (' . $node->user->email . ')' : 'บัญชีที่ถูกลบ';

    $suspendPrompt = "ระงับ {$name} ของ {$owner}?\n\n"
        . "aixman หยุดส่งงานทันที (งานที่กำลังทำถูกย้ายไปที่อื่น) relay ตัดสายของเครื่อง "
        . "และรายได้ที่ยังไม่เข้ากระเป๋าของเครื่องนี้ถูกพักไว้จนกว่าจะยกเลิกระงับ\n"
        . 'เจ้าของเครื่องจะเห็นเหตุผลที่กรอก';
    $resumePrompt = "ยกเลิกระงับ {$name}?\n\nเครื่องกลับเข้าคิวงาน และรายได้ที่พักไว้จะเดินต่อในรอบถัดไป"
        . ($live ? '' : ' (เครื่องนี้ถูกถอนไปแล้ว — เจ้าของจะจับคู่ใหม่ได้)');
    $banPrompt = "แบน {$name} ของ {$owner}?\n\n"
        . "• ถอน worker ออกจาก aixman และ relay (ย้อนกลับไม่ได้ — ถ้ายกเลิกแบน เจ้าของต้องจับคู่ใหม่)\n"
        . "• บัญชีเจ้าของขอรหัสจับคู่ใหม่ไม่ได้ และเครื่องที่รายงาน machine id นี้จับคู่ในบัญชีไหนก็ไม่ได้ จนกว่าจะยกเลิกแบน\n"
        . "  (machine id มาจากตัวเครื่องเอง เครื่องที่ถูกแก้ให้รายงานค่าอื่นหลบได้ — ดูรายการเครื่องที่ IP ตรงกันในหน้านี้)\n"
        . "• รายได้ที่ยังไม่เข้ากระเป๋าของเครื่องนี้ถูกพักไว้ ยกเลิกทีละรายการได้ด้านล่าง\n"
        . '• เครื่องอื่นของเจ้าของคนนี้ยังทำงานต่อ — ระงับหรือแบนแยกทีละเครื่อง';
    $unbanPrompt = "ยกเลิกแบน {$name}?\n\nเจ้าของจับคู่ใหม่ได้ และรายได้ที่พักไว้ของเครื่องนี้จะเดินต่อเข้ากระเป๋าในรอบถัดไป"
        . ' — ยกเลิกรายการที่ไม่ควรจ่ายก่อนกดยืนยัน';
@endphp

<div class="space-y-6">
    <a href="{{ route('admin.gpuxmine.index') }}" class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        กลับไปรายการเครื่อง
    </a>

    @if ($errors->any())
        <div class="rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-900 dark:text-red-200 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- ══════════ หัว ══════════ --}}
    <div class="{{ $card }} p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white break-words">{{ $name }}</h1>
                <p class="font-mono text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $node->worker_id }} · แถว #{{ $node->id }}</p>
                <div class="mt-2">@include('admin.gpuxmine.partials.node-badges', ['node' => $node])</div>
            </div>
            <div class="text-sm text-right">
                <p class="text-gray-900 dark:text-white">{{ $node->user?->name ?? 'บัญชีถูกลบ' }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $node->user?->email }}</p>
                @if ($ownerBanned)
                    <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">บัญชีนี้ถูกห้ามจับคู่เครื่องใหม่</p>
                @endif
            </div>
        </div>

        @if ($node->isBanned())
            <div class="mt-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-4 py-3 text-sm text-red-900 dark:text-red-200">
                <p class="font-semibold">ถูกแบนเมื่อ {{ $bkk($node->banned_at) }} โดย {{ $node->bannedBy?->name ?? 'แอดมินที่ถูกลบ' }}</p>
                <p class="mt-0.5">เหตุผล: {{ $node->banned_reason }}</p>
            </div>
        @elseif ($node->isSuspended())
            <div class="mt-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 px-4 py-3 text-sm text-red-900 dark:text-red-200">
                <p class="font-semibold">ถูกระงับเมื่อ {{ $bkk($node->suspended_at) }} โดย {{ $node->suspendedBy?->name ?? 'แอดมินที่ถูกลบ' }}</p>
                <p class="mt-0.5">เหตุผล (เจ้าของเห็น): {{ $node->suspended_reason }}</p>
                <p class="mt-0.5 text-xs">รายได้ที่ยังไม่เข้ากระเป๋าของเครื่องนี้ถูกพักไว้จนกว่าจะยกเลิกระงับ</p>
            </div>
        @endif

        @unless ($live)
            <div class="mt-4 rounded-xl bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700 px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                ถอนออกจากระบบเมื่อ {{ $bkk($node->deleted_at) }} ·
                @if ($node->retire_status === \App\Models\GpuNode::RETIRE_PENDING)
                    <span class="font-semibold text-amber-700 dark:text-amber-300">aixman หรือ relay ยังไม่ยืนยันการถอน — ระบบลองซ้ำทุกนาที</span>
                @elseif ($node->retire_status === \App\Models\GpuNode::RETIRE_AWAITING_RELAY)
                    <span class="font-semibold text-amber-700 dark:text-amber-300">aixman ถอนแล้ว แต่ relay รุ่นนี้ยังลบ worker ไม่ได้ — เครื่องยังต่อ relay ได้ (ไม่มีงานเข้า) ระบบลบให้เองเมื่ออัปเกรด relay</span>
                @elseif ($node->retire_status === \App\Models\GpuNode::RETIRE_DONE)
                    ถอนครบทั้ง aixman และ relay แล้ว
                @else
                    สถานะการถอนไม่ได้บันทึก (ถอนก่อนมีระบบติดตาม)
                @endif
                @if ($node->dispatch_note && ! $node->paired_at)
                    · {{ $node->dispatch_note }}
                @endif
            </div>
        @endunless
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- ══════════ รายละเอียด ══════════ --}}
        <div class="xl:col-span-2 space-y-6">
            <div class="{{ $card }} p-5 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">เครื่อง</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">การ์ดจอ</dt><dd class="text-gray-900 dark:text-white">{{ $node->gpu_name ?: '—' }}{{ $node->vram_total_mb > 0 ? ' · ' . number_format($node->vram_total_mb / 1024, 1) . ' GB' : '' }}</dd></div>
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">ประเมิน</dt><dd class="text-gray-900 dark:text-white">{{ $node->assessed ? strtoupper((string) $node->tier) . ' · ' . number_format((int) $node->score) . ' คะแนน' : 'ยังไม่ประเมิน' }}</dd></div>
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">งานที่รับได้</dt><dd class="text-gray-900 dark:text-white">{{ implode(', ', array_filter((array) ($node->can_run ?? []), 'is_string')) ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">ความเร็ว (lanes)</dt><dd class="font-mono text-xs text-gray-900 dark:text-white">{{ $node->lanes ? json_encode($node->lanes, JSON_UNESCAPED_UNICODE) : '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">แชร์ฟรี</dt><dd class="text-gray-900 dark:text-white">{{ (int) $node->free_share_pct }}%</dd></div>
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">รับงาน / กำลังทำงาน</dt><dd class="text-gray-900 dark:text-white">{{ $node->accepting === null ? 'ไม่รู้' : ($node->accepting ? 'รับ' : 'ไม่รับ') }} / {{ $node->busy === null ? 'ไม่รู้' : ($node->busy ? 'ใช่' : 'ไม่') }}</dd></div>
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">จับคู่เมื่อ</dt><dd class="text-gray-900 dark:text-white">{{ $bkk($node->paired_at) }}</dd></div>
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">เห็นล่าสุด</dt><dd class="text-gray-900 dark:text-white">{{ $bkk($node->last_seen_at) }}</dd></div>
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">เวอร์ชันโปรแกรม</dt><dd class="text-gray-900 dark:text-white">{{ $node->agent_version ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">ผู้แนะนำ (จับตอนจับคู่)</dt><dd class="text-gray-900 dark:text-white">{{ $node->referrer ? $node->referrer->name . ' · ' . $node->referrer->email : '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-gray-500 dark:text-gray-400">machine id</dt><dd class="font-mono text-xs text-gray-900 dark:text-white break-all">{{ $node->machine_id ?: '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-gray-500 dark:text-gray-400">ปลายทางที่ aixman ยิงงาน</dt><dd class="font-mono text-xs text-gray-900 dark:text-white break-all">{{ $node->tunnel_endpoint ?: '—' }} · {{ $node->tunnel_token ? 'กุญแจอุโมงค์แยก (relay รุ่นใหม่)' : 'กุญแจใบเดียว (relay รุ่นเก่า)' }}</dd></div>
                </dl>
            </div>

            <div class="{{ $card }} p-5 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">ระบบส่งงาน (aixman) ตอบล่าสุด</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">ผลการขึ้นทะเบียน</dt><dd class="text-gray-900 dark:text-white">{{ $node->dispatchStatusLabel() ?? 'ยังไม่เคยส่ง' }} <span class="font-mono text-[11px] text-gray-400">{{ $node->dispatch_status }}</span></dd></div>
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">สถานะ worker</dt><dd class="text-gray-900 dark:text-white">{{ $node->dispatchWorkerStatusLabel() ?? 'aixman ยังไม่ได้บอก' }} <span class="font-mono text-[11px] text-gray-400">{{ $node->dispatch_worker_status }}</span></dd></div>
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">ส่งล่าสุด</dt><dd class="text-gray-900 dark:text-white">{{ $bkk($node->dispatch_synced_at) }}</dd></div>
                    <div><dt class="text-xs text-gray-500 dark:text-gray-400">aixman รับข้อมูลชุดล่าสุดแล้ว</dt><dd class="text-gray-900 dark:text-white">{{ $node->dispatch_fingerprint ? 'ใช่' : 'ยังไม่เคยตอบรับ' }}</dd></div>
                    @if ($node->dispatch_note)
                        <div class="sm:col-span-2"><dt class="text-xs text-gray-500 dark:text-gray-400">หมายเหตุ</dt><dd class="text-gray-900 dark:text-white">{{ $node->dispatch_note }}</dd></div>
                    @endif
                    @if ($node->dispatch_last_error)
                        <div class="sm:col-span-2"><dt class="text-xs text-gray-500 dark:text-gray-400">ข้อผิดพลาดล่าสุดของ worker</dt><dd class="text-red-700 dark:text-red-300 break-words">{{ $node->dispatch_last_error }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- ══════════ จัดการ ══════════ --}}
        <div class="space-y-4">
            <div class="{{ $card }} p-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">จัดการเครื่องนี้</h2>

                @if ($live || in_array($node->retire_status, \App\Models\GpuNode::RETIRE_UNFINISHED, true))
                    <form method="POST" action="{{ route('admin.gpuxmine.resync', $node->id) }}"
                          onsubmit="this.querySelector('button[type=submit]').disabled = true">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 rounded-lg bg-slate-900 hover:bg-slate-800 dark:bg-slate-700 dark:hover:bg-slate-600 text-white text-sm font-semibold transition disabled:opacity-50 disabled:cursor-wait">
                            {{ $live ? 'อ่าน relay แล้วส่งให้ aixman ตอนนี้' : 'ลองถอนจาก aixman/relay อีกครั้ง' }}
                        </button>
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            {{ $live ? 'ไม่ต้องรอตัวจับเวลา — ใช้หลังแก้ปัญหาฝั่ง aixman หรือเมื่อสงสัยว่าสถานะค้าง' : 'ตัวจับเวลาลองให้อยู่แล้วทุกนาที' }}
                        </p>
                    </form>
                @endif

                @if ($node->isBanned())
                    <form method="POST" action="{{ route('admin.gpuxmine.unban', $node->id) }}"
                          onsubmit="if (! window.confirm(@js($unbanPrompt))) return false; this.querySelector('button[type=submit]').disabled = true;">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-100 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition disabled:opacity-50 disabled:cursor-wait">
                            ยกเลิกแบน
                        </button>
                    </form>
                @else
                    @if ($node->isSuspended())
                        <form method="POST" action="{{ route('admin.gpuxmine.resume', $node->id) }}"
                              onsubmit="if (! window.confirm(@js($resumePrompt))) return false; this.querySelector('button[type=submit]').disabled = true;">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold transition disabled:opacity-50 disabled:cursor-wait">
                                ยกเลิกระงับ
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.gpuxmine.suspend', $node->id) }}"
                              onsubmit="if (! window.confirm(@js($suspendPrompt))) return false; this.querySelector('button[type=submit]').disabled = true;"
                              class="rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 p-3 space-y-2">
                            @csrf
                            <p class="text-xs font-semibold text-amber-900 dark:text-amber-200">ระงับชั่วคราว</p>
                            <input type="text" name="reason" required minlength="3" maxlength="255" autocomplete="off"
                                   placeholder="เหตุผล (เจ้าของเครื่องจะเห็น)" class="{{ $input }}">
                            <button type="submit" class="w-full px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-900 text-sm font-semibold transition disabled:opacity-50 disabled:cursor-wait">
                                ระงับเครื่องนี้
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.gpuxmine.ban', $node->id) }}"
                          onsubmit="if (! window.confirm(@js($banPrompt))) return false; this.querySelector('button[type=submit]').disabled = true;"
                          class="rounded-xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-500/10 p-3 space-y-2">
                        @csrf
                        <p class="text-xs font-semibold text-red-900 dark:text-red-200">แบน — ถอนออกและห้ามจับคู่ใหม่</p>
                        <input type="text" name="reason" required minlength="3" maxlength="255" autocomplete="off"
                               placeholder="เหตุผล (เจ้าของเครื่องจะเห็น)" class="{{ $input }}">
                        <button type="submit" class="w-full px-4 py-2 rounded-lg bg-red-600 hover:bg-red-500 text-white text-sm font-semibold transition disabled:opacity-50 disabled:cursor-wait">
                            แบนเครื่องนี้
                        </button>
                    </form>
                @endif
            </div>

            @if ($siblings->isNotEmpty())
                <div class="{{ $card }} p-5">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เครื่องอื่นของเจ้าของ / แถวอื่นของเครื่องนี้</h2>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach ($siblings as $other)
                            <li class="py-2">
                                <a href="{{ route('admin.gpuxmine.show', $other->id) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">{{ $other->displayName() }}</a>
                                <span class="block font-mono text-[11px] text-gray-500 dark:text-gray-400">{{ $other->worker_id }}
                                    @if ((int) $other->user_id !== (int) $node->user_id) · บัญชี {{ $other->user?->email ?? 'ที่ถูกลบ' }} @endif
                                    @if ($other->machine_id && $other->machine_id === $node->machine_id) · เครื่องเดียวกัน @endif
                                </span>
                                <div class="mt-1">@include('admin.gpuxmine.partials.node-badges', ['node' => $other])</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($lookalikes->isNotEmpty())
                {{-- การแบนตาม machine id หลบได้ด้วยการแก้เครื่องแล้วมาด้วยบัญชีใหม่ — ที่นี่ช่วยให้แอดมินเห็นเอง
                     IP ซ้ำกันได้ในคนละบ้าน (CGNAT) จึงเป็นแค่เบาะแส ไม่ใช่หลักฐาน --}}
                <div class="{{ $card }} p-5">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">เครื่องที่ IP ตอนจับคู่ตรงกัน</h2>
                    <p class="mt-0.5 mb-2 text-[11px] text-gray-500 dark:text-gray-400">อาจเป็นเครื่องเดียวกันที่รายงาน machine id ใหม่ หรือแค่บ้านที่ใช้เน็ตเจ้าเดียวกัน — ดูประกอบกันก่อนตัดสิน</p>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach ($lookalikes as $match)
                            <li class="py-2">
                                <a href="{{ route('admin.gpuxmine.show', $match['node']->id) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400">{{ $match['node']->displayName() }}</a>
                                <span class="block font-mono text-[11px] text-gray-500 dark:text-gray-400">{{ $match['node']->worker_id }} · บัญชี {{ $match['node']->user?->email ?? 'ที่ถูกลบ' }}
                                    @if ($match['sameHardware']) · ฮาร์ดแวร์ตรงกันด้วย @endif
                                </span>
                                <div class="mt-1">@include('admin.gpuxmine.partials.node-badges', ['node' => $match['node']])</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ รายได้ของเครื่องนี้ ══════════ --}}
    <div class="space-y-2">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">รายได้ของเครื่องนี้</h2>
        @include('admin.gpuxmine.partials.money-tiles', ['money' => $money])
    </div>

    <div class="{{ $card }} overflow-hidden">
        @if ($earnings->isEmpty())
            <p class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">เครื่องนี้ยังไม่มีงานที่บันทึกรายได้</p>
        @else
            @include('admin.gpuxmine.partials.earnings-table', ['earnings' => $earnings, 'holdHours' => $holdHours, 'showNode' => false])
            @if ($earnings->hasPages())
                <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">{{ $earnings->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
