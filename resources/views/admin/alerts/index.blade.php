@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แจ้งเตือน Telegram')
@section('page-title', 'แจ้งเตือน Telegram')

@php
    $card = 'bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700';
    $input = 'w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-sky-500 focus:border-transparent font-mono text-sm';
    $btnGhost = 'px-4 py-2.5 rounded-xl text-sm font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 transition disabled:opacity-40 disabled:cursor-not-allowed';
    $btnDanger = 'px-4 py-2.5 rounded-xl text-sm font-semibold bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 text-red-700 dark:text-red-300 transition';
    $btnPrimary = 'px-6 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 shadow-lg transition';
    $chatTypes = ['private' => 'แชทส่วนตัว', 'group' => 'กลุ่ม', 'supergroup' => 'กลุ่ม', 'channel' => 'ช่อง'];
@endphp

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-sky-500 via-blue-600 to-violet-600 p-8 shadow-2xl">
        <div class="relative z-10 flex items-center justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">แจ้งเตือน Telegram</h1>
                <p class="text-sky-100 text-lg">ใบสั่งซื้อ · สลิป · เงินเข้า · ลูกค้าติดต่อ · แชท AI · มีคนบุกรุก — ส่งเป็นการ์ดกราฟิกพร้อมปุ่มอนุมัติ ฟรีไม่จำกัด</p>
            </div>
            <div class="hidden md:flex w-16 h-16 shrink-0 rounded-xl bg-white/20 backdrop-blur-sm items-center justify-center">
                <svg viewBox="0 0 24 24" class="w-8 h-8 fill-white" aria-hidden="true"><path d="M21.4 4.1 2.9 11.2c-1.3.5-1.3 1.2-.2 1.5l4.7 1.5 1.8 5.6c.2.6.1.9.8.9.5 0 .7-.2 1-.5l2.3-2.2 4.8 3.5c.9.5 1.5.2 1.7-.8l3.2-15c.3-1.3-.5-1.9-1.6-1.4ZM8.9 13.9l9.6-6.1c.5-.3.9-.1.5.2l-8.2 7.4-.3 3.4-1.6-4.9Z"/></svg>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl shadow">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl shadow">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- ── 1. Bot + chat ─────────────────────────────────────────────────── --}}
        <div class="{{ $card }}">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">1. เชื่อมบอทและแชทปลายทาง</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">แจ้งเตือนจะเด้งเข้าแชทนี้ (ส่วนตัวหรือกลุ่มทีมงานก็ได้)</p>
                </div>
                @if ($tg['ready'])
                    <span class="rounded-full bg-green-100 dark:bg-green-900/40 px-3 py-1 text-xs font-semibold text-green-700 dark:text-green-300">● กำลังส่งแจ้งเตือน</span>
                @elseif ($tg['hasToken'] && $tg['chat'] !== '')
                    <span class="rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs text-gray-600 dark:text-gray-300">ตั้งค่าครบ แต่ปิดอยู่</span>
                @else
                    <span class="rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs text-gray-600 dark:text-gray-300">ยังไม่ได้ตั้งค่า</span>
                @endif
            </div>

            @unless ($tg['ready'])
                <ol class="mb-5 space-y-1.5 rounded-xl border border-sky-100 dark:border-sky-900 bg-sky-50 dark:bg-sky-900/20 p-4 text-sm leading-relaxed text-sky-900 dark:text-sky-200 list-decimal list-inside">
                    <li>ใน Telegram ค้นหา <b>@BotFather</b> → พิมพ์ <code class="rounded bg-white/70 dark:bg-black/30 px-1">/newbot</code> → ตั้งชื่อ → คัดลอก <b>Token</b> มาวางด้านล่างแล้วกดบันทึก</li>
                    <li>เปิดแชทกับบอทแล้วกด <b>Start</b> (อยากให้ทีมเห็นด้วย ให้เพิ่มบอทเข้ากลุ่มแทน)</li>
                    <li>กด <b>ค้นหา Chat ID อัตโนมัติ</b> → เลือกแชท → กด <b>ทดสอบส่ง</b></li>
                </ol>
            @endunless

            <form method="POST" action="{{ route('admin.alerts.telegram.update') }}" class="space-y-4">
                @csrf @method('PUT')
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="telegram_alerts_enabled" value="1" @checked(old('telegram_alerts_enabled', $tg['enabled'])) class="h-5 w-5 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                    <span class="font-semibold text-gray-900 dark:text-white">เปิดการแจ้งเตือนเข้า Telegram</span>
                </label>

                <div>
                    <label class="mb-1 flex flex-wrap items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Bot Token
                        @if ($tg['hasToken'])
                            <span class="rounded-full bg-green-100 dark:bg-green-900/40 px-2 py-0.5 text-[11px] font-medium text-green-700 dark:text-green-300">ตั้งค่าไว้แล้ว</span>
                        @endif
                        @if ($tg['bot'] !== '')
                            <a href="https://t.me/{{ $tg['bot'] }}" target="_blank" rel="noopener noreferrer" class="rounded-full bg-sky-100 dark:bg-sky-900/40 px-2 py-0.5 text-[11px] font-medium text-sky-700 dark:text-sky-300 hover:underline">{{ '@'.$tg['bot'] }} · เปิดแชท ↗</a>
                        @endif
                    </label>
                    <input type="password" name="telegram_bot_token" value="" autocomplete="new-password" spellcheck="false"
                           placeholder="{{ $tg['hasToken'] ? 'เว้นว่างไว้ = ใช้ค่าเดิม' : '123456789:AAH…' }}" class="{{ $input }}">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">เก็บแบบเข้ารหัส และไม่แสดงค่ากลับมาอีก — ตอนบันทึกระบบจะเช็คกับ Telegram ให้ว่าใช้ได้จริง</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700 dark:text-gray-300">ส่งเข้าแชทไหน (Chat ID)</label>
                    <input type="text" name="telegram_chat_id" value="{{ old('telegram_chat_id', $tg['chat']) }}" placeholder="เช่น 123456789 หรือ -1001234567890" class="{{ $input }}">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ไม่ต้องหาเอง — กด "ค้นหา Chat ID อัตโนมัติ" หลังจากกด Start กับบอทแล้ว</p>
                </div>

                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="telegram_protect_content" value="1" @checked(old('telegram_protect_content', $tg['protect'])) class="mt-0.5 h-5 w-5 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300"><b>กันการส่งต่อข้อมูลลูกค้า</b> — การ์ดใบสั่งซื้อ/ลูกค้าติดต่อ/สมาชิก จะ forward หรือบันทึกออกจาก Telegram ไม่ได้</span>
                </label>

                <button class="{{ $btnPrimary }}">บันทึก</button>
            </form>

            <div class="mt-5 flex flex-wrap gap-2 border-t border-gray-100 dark:border-gray-700 pt-5">
                <form method="POST" action="{{ route('admin.alerts.telegram.detect') }}">@csrf
                    <button class="{{ $btnGhost }}" @disabled(! $tg['hasToken'])>ค้นหา Chat ID อัตโนมัติ</button>
                </form>
                <form method="POST" action="{{ route('admin.alerts.telegram.test') }}">@csrf
                    <button class="{{ $btnGhost }}" @disabled(! $tg['hasToken'])>ทดสอบส่ง</button>
                </form>
                @if ($tg['hasToken'])
                    <form method="POST" action="{{ route('admin.alerts.telegram.forget') }}" onsubmit="return confirm('ลบ Bot Token, ปิดการแจ้งเตือน และปิดรับคำสั่งบอท?')">@csrf @method('DELETE')
                        <button class="{{ $btnDanger }}">ลบ Token</button>
                    </form>
                @endif
            </div>

            @if (! empty($chats))
                <div class="mt-5 overflow-hidden rounded-xl border border-sky-200 dark:border-sky-800">
                    <div class="bg-sky-50 dark:bg-sky-900/30 px-4 py-2.5 text-sm font-semibold text-sky-800 dark:text-sky-200">แชทที่คุยกับบอทล่าสุด — เลือกแชทที่จะรับแจ้งเตือน</div>
                    @foreach ($chats as $c)
                        <div class="flex items-center justify-between gap-3 border-t border-gray-100 dark:border-gray-700 px-4 py-3">
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-gray-900 dark:text-white">{{ $c['title'] }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $chatTypes[$c['type']] ?? $c['type'] }}{{ ! empty($c['is_forum']) ? ' · มีห้องย่อย' : '' }} · <span class="font-mono">{{ $c['id'] }}</span></div>
                            </div>
                            <form method="POST" action="{{ route('admin.alerts.telegram.chat') }}">@csrf
                                <input type="hidden" name="chat_id" value="{{ $c['id'] }}">
                                <button class="shrink-0 rounded-lg bg-sky-600 hover:bg-sky-700 px-3.5 py-2 text-sm font-semibold text-white">ใช้แชทนี้</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── 2. Commands + buttons ─────────────────────────────────────────── --}}
        <div class="{{ $card }}">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">2. ปุ่มอนุมัติ & คำสั่งบอท</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">กดยืนยันสลิป/เติมเงิน สั่งดูรายงาน /today /week /pending ได้จากในแชท</p>
                </div>
                @if ($webhook['on'])
                    <span class="rounded-full bg-green-100 dark:bg-green-900/40 px-3 py-1 text-xs font-semibold text-green-700 dark:text-green-300">● รับคำสั่งอยู่</span>
                @else
                    <span class="rounded-full bg-gray-100 dark:bg-gray-700 px-3 py-1 text-xs text-gray-600 dark:text-gray-300">ปิดอยู่</span>
                @endif
            </div>

            @if ($webhook['on'] && $webhook['info'] && $webhook['info']['last_error'])
                <div class="mb-4 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                    Telegram ส่งคำสั่งเข้ามาไม่สำเร็จล่าสุด: {{ $webhook['info']['last_error'] }}
                    @if ($webhook['info']['last_error_at']) ({{ \Illuminate\Support\Carbon::createFromTimestamp($webhook['info']['last_error_at'])->timezone('Asia/Bangkok')->format('d/m H:i') }} น.) @endif
                </div>
            @endif
            @unless ($webhook['https'])
                <div class="mb-4 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-200">
                    เว็บนี้ไม่ได้เป็น HTTPS ({{ config('app.url') }}) — Telegram ส่งคำสั่งมาได้เฉพาะเว็บจริงที่เป็น HTTPS เปิดใช้ส่วนนี้บนเซิร์ฟเวอร์จริง
                </div>
            @endunless

            <div class="flex flex-wrap gap-2">
                @if ($webhook['on'])
                    <form method="POST" action="{{ route('admin.alerts.webhook.disable') }}" onsubmit="return confirm('ปิดรับคำสั่งบอท? ปุ่มอนุมัติในการ์ดจะกดไม่ได้ (การแจ้งเตือนยังส่งปกติ)')">@csrf @method('DELETE')
                        <button class="{{ $btnDanger }}">ปิดรับคำสั่ง</button>
                    </form>
                    <form method="POST" action="{{ route('admin.alerts.webhook.enable') }}">@csrf
                        <button class="{{ $btnGhost }}">ตั้งค่าใหม่ (หมุน secret)</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.alerts.webhook.enable') }}">@csrf
                        <button class="{{ $btnPrimary }}" @disabled(! $tg['hasToken'] || ! $webhook['https'])>เปิดรับคำสั่งบอท</button>
                    </form>
                @endif
            </div>

            <div class="mt-6 border-t border-gray-100 dark:border-gray-700 pt-5">
                <div class="mb-1 font-semibold text-gray-900 dark:text-white">บัญชีแอดมินที่สั่งบอทได้</div>
                <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">อยู่ในกลุ่มอย่างเดียวกดปุ่มไม่ได้ ต้องผูกบัญชีก่อน — และถ้าถอดสิทธิ์แอดมินบนเว็บ ปุ่มใน Telegram ของคนนั้นจะใช้ไม่ได้ทันที</p>

                @forelse ($admins as $telegramId => $a)
                    <div class="flex items-center justify-between gap-3 rounded-xl border border-gray-100 dark:border-gray-700 px-4 py-3 mb-2">
                        <div class="min-w-0">
                            <div class="truncate font-semibold text-gray-900 dark:text-white">{{ $a['name'] }} <span class="font-normal text-gray-500 dark:text-gray-400">· Telegram: {{ $a['tg'] }}</span></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">ID <span class="font-mono">{{ $telegramId }}</span> · ผูกเมื่อ {{ \Illuminate\Support\Carbon::parse($a['linked_at'])->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.alerts.unlink', $telegramId) }}" onsubmit="return confirm('ถอดบัญชี Telegram นี้? จะกดปุ่มหรือสั่งบอทไม่ได้อีก')">@csrf @method('DELETE')
                            <button class="{{ $btnDanger }} !px-3 !py-1.5 !text-xs">ถอด</button>
                        </form>
                    </div>
                @empty
                    <div class="mb-3 rounded-xl bg-gray-50 dark:bg-gray-700/40 px-4 py-3 text-sm text-gray-500 dark:text-gray-400">ยังไม่มีบัญชีที่ผูก</div>
                @endforelse

                <form method="POST" action="{{ route('admin.alerts.link') }}" class="mt-3">@csrf
                    <button class="{{ $btnGhost }}" @disabled(! $webhook['on'])>🔗 ผูกบัญชี Telegram ของฉัน</button>
                </form>

                @if ($link)
                    <div class="mt-4 flex flex-col sm:flex-row items-center gap-4 rounded-xl border border-sky-200 dark:border-sky-800 bg-sky-50 dark:bg-sky-900/20 p-4">
                        @if ($qr)
                            <div class="shrink-0 rounded-lg bg-white p-2">{!! $qr !!}</div>
                        @endif
                        <div class="text-sm text-sky-900 dark:text-sky-200">
                            <div class="font-semibold mb-1">สแกนด้วยมือถือ หรือกดลิงก์ในเครื่องที่มี Telegram</div>
                            <a href="{{ $link }}" target="_blank" rel="noopener noreferrer" class="inline-block rounded-lg bg-sky-600 hover:bg-sky-700 px-4 py-2 font-semibold text-white">เปิดใน Telegram ↗</a>
                            <p class="mt-2 text-xs opacity-80">ใช้ได้ครั้งเดียวภายใน 10 นาที · กด Start ในบอทแล้วเสร็จทันที · อย่าส่งลิงก์นี้ให้คนอื่น</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-6 border-t border-gray-100 dark:border-gray-700 pt-5 text-sm text-gray-600 dark:text-gray-300">
                <div class="font-semibold text-gray-900 dark:text-white mb-2">คำสั่งในแชท</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1">
                    @foreach (\App\Support\Telegram\BotCommands::MENU as $command => $description)
                        <div><code class="text-sky-700 dark:text-sky-300">/{{ $command }}</code> — {{ $description }}</div>
                    @endforeach
                </div>
                @if ($quietUntil)
                    <p class="mt-3 text-violet-700 dark:text-violet-300">🌙 ตอนนี้อยู่ในโหมดเงียบถึง {{ \Illuminate\Support\Carbon::createFromTimestamp($quietUntil)->timezone('Asia/Bangkok')->format('H:i') }} น.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Categories + topics ──────────────────────────────────────────────── --}}
    <div class="{{ $card }}">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">แจ้งเรื่องอะไรบ้าง</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">ทุกหมวดมีตัวกันแจ้งถี่ในตัว — เรื่องเดิมจะเงียบไประยะหนึ่ง และถ้าเกิดถี่ผิดปกติ (เช่นมีคนยิงฟอร์มสแปม) จะรวบเป็นข้อความเดียวต่อชั่วโมง</p>
        <form method="POST" action="{{ route('admin.alerts.categories') }}">
            @csrf @method('PUT')
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($categories as $key => $cat)
                    <label class="flex items-start gap-3 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/40 p-4 cursor-pointer">
                        <input type="checkbox" name="categories[]" value="{{ $key }}" @checked(in_array($key, $enabledCategories, true)) class="mt-0.5 h-5 w-5 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                        <span>
                            <span class="block font-semibold text-gray-900 dark:text-white">{{ $cat[0] }}
                                @if (! empty($topics[$key]))<span class="ml-1 rounded-full bg-violet-100 dark:bg-violet-900/40 px-2 py-0.5 text-[11px] font-medium text-violet-700 dark:text-violet-300">มีห้องย่อย</span>@endif
                            </span>
                            <span class="block text-sm text-gray-500 dark:text-gray-400">{{ $cat[1] }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <button class="{{ $btnPrimary }} mt-5">บันทึกหมวด</button>
        </form>

        <div class="mt-6 border-t border-gray-100 dark:border-gray-700 pt-5">
            <div class="font-semibold text-gray-900 dark:text-white">ห้องย่อยในกลุ่ม (Forum Topics)</div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">กลุ่มเดียวแต่แยกห้องตามหมวด 🛒ใบสั่งซื้อ / 💬ลูกค้าติดต่อ / 🛡ความปลอดภัย … — เปิดโหมด Topics ในตั้งค่ากลุ่ม และให้บอทเป็นแอดมินที่ "จัดการหัวข้อ" ได้ก่อน</p>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.alerts.topics.create') }}">@csrf
                    <button class="{{ $btnGhost }}" @disabled(! $tg['hasToken'] || ! str_starts_with($tg['chat'], '-100'))>สร้างห้องย่อยอัตโนมัติ</button>
                </form>
                @if (! empty($topics))
                    <form method="POST" action="{{ route('admin.alerts.topics.clear') }}" onsubmit="return confirm('เลิกใช้ห้องย่อย? แจ้งเตือนจะกลับไปเข้าแชทหลัก (ห้องใน Telegram ไม่ถูกลบ)')">@csrf @method('DELETE')
                        <button class="{{ $btnDanger }}">เลิกใช้ห้องย่อย</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Card previews ────────────────────────────────────────────────────── --}}
    <div class="{{ $card }}">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">หน้าตาการ์ดที่เด้งเข้า Telegram</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">ตัวอย่างจากข้อมูลสมมติ — ของจริงวาดจากเหตุการณ์จริงทุกครั้ง พร้อมปุ่มอนุมัติและปุ่มเปิดหน้าแอดมิน</p>
        @if ($canDraw)
            <div class="mt-4 flex gap-4 overflow-x-auto pb-2">
                @foreach (['order' => 'ลูกค้าแนบสลิป (มีปุ่มยืนยัน/ปฏิเสธ)', 'contact' => 'ลูกค้าในแชท AI ทิ้งเบอร์ไว้', 'security' => 'มีคนเดารหัสแอดมิน', 'daily' => 'รายงาน 09:00 น. พร้อมกราฟ'] as $kind => $caption)
                    <figure class="w-[280px] shrink-0">
                        <a href="{{ route('admin.alerts.preview', $kind) }}" target="_blank">
                            <img src="{{ route('admin.alerts.preview', $kind) }}" alt="{{ $caption }}" loading="lazy" class="w-full rounded-xl border border-gray-200 dark:border-gray-700">
                        </a>
                        <figcaption class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $caption }}</figcaption>
                    </figure>
                @endforeach
            </div>
        @else
            <div class="mt-3 rounded-xl bg-gray-50 dark:bg-gray-700/40 px-4 py-3 text-sm text-gray-500 dark:text-gray-400">เซิร์ฟเวอร์นี้วาดการ์ดไม่ได้ (ไม่มี GD/FreeType หรือไม่พบฟอนต์ใน resources/fonts) — Telegram จะส่งเป็นข้อความตัวอักษรแทน</div>
        @endif
    </div>

    {{-- ── History ───────────────────────────────────────────────────────────── --}}
    <div class="{{ $card }} !p-0 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">ข้อความที่ส่งไปแล้ว</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">ย้อนดูได้ว่าที่เด้งเข้ามือถือคืออะไร และอันไหนส่งไม่ผ่าน</p>
        </div>
        @php
            $catNames = collect($categories)->map(fn ($c) => $c[0]);
        @endphp
        @forelse ($recent as $a)
            <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-700/60">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="rounded px-2 py-0.5 {{ $a->ok ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">{{ $a->ok ? 'ส่งสำเร็จ' : 'ส่งไม่สำเร็จ' }}</span>
                    <span class="rounded bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-gray-600 dark:text-gray-300">{{ $catNames[$a->category] ?? $a->category }}</span>
                    <span class="text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Carbon::parse($a->created_at)->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</span>
                </div>
                <div class="mt-1.5 whitespace-pre-wrap text-sm leading-relaxed text-gray-700 dark:text-gray-200">{{ \Illuminate\Support\Str::limit($a->body, 400) }}</div>
                @if ($a->error)
                    <div class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $a->error }}</div>
                @endif
            </div>
        @empty
            <div class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">ยังไม่มีข้อความที่ส่งไป</div>
        @endforelse
    </div>
</div>
@endsection
