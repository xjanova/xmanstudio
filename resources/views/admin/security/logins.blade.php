@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตรวจสอบการเข้าสู่ระบบ')
@section('page-title', 'ตรวจสอบการเข้าสู่ระบบ')

@php
    $outcomeStyles = [
        'success'   => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        'failed'    => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        'lockout'   => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
        'blocked'   => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        'turnstile' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
    ];
    $peak = max(1, collect($hourly)->max('count'));
@endphp

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-800 via-indigo-800 to-violet-800 p-8 shadow-2xl">
        <div class="relative z-10 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">ตรวจสอบการเข้าสู่ระบบ</h1>
                <p class="text-indigo-200 text-lg">ใครพยายามเข้าระบบ จากที่ไหน และ IP ไหนถูกบล็อกอยู่</p>
            </div>
            <div class="hidden md:flex w-16 h-16 rounded-xl bg-white/20 backdrop-blur-sm items-center justify-center">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl font-medium">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl font-medium">
            {{ session('error') }}
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Numbers --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach([
            ['เข้าสำเร็จ (24 ชม.)', $stats['success_24h'], 'emerald', 'M5 13l4 4L19 7'],
            ['รหัสผ่านผิด (24 ชม.)', $stats['failed_24h'], 'amber', 'M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0l-7.1 12.25A2 2 0 004.99 19z'],
            ['สกัดไว้ได้ (24 ชม.)', $stats['deflected_24h'], 'purple', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['เล็งบัญชีแอดมิน', $stats['admin_failed_24h'], 'rose', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z'],
            ['IP ที่ถูกบล็อกอยู่', $stats['blocked_now'], 'red', 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
        ] as [$label, $value, $tone, $icon])
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $label }}</span>
                    <svg class="w-5 h-5 text-{{ $tone }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
                    </svg>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($value) }}</div>
            </div>
        @endforeach
    </div>

    {{-- Failures per hour --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">การเข้าสู่ระบบที่ไม่สำเร็จ · 24 ชั่วโมงล่าสุด</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">รวมรหัสผ่านผิด ถูกล็อกชั่วคราว ไม่ผ่าน Turnstile และถูกบล็อก</p>
        <div class="flex items-end gap-1 h-32">
            @foreach($hourly as $bucket)
                <div class="flex-1 flex flex-col items-center justify-end h-full group relative">
                    <div class="w-full rounded-t {{ $bucket['count'] > 0 ? 'bg-gradient-to-t from-indigo-500 to-violet-400' : 'bg-gray-200 dark:bg-gray-700' }}"
                         style="height: {{ $bucket['count'] > 0 ? max(4, round($bucket['count'] / $peak * 100)) : 2 }}%"></div>
                    <span class="absolute -top-7 hidden group-hover:block px-2 py-1 rounded bg-gray-900 text-white text-xs whitespace-nowrap z-10">
                        {{ $bucket['label'] }} · {{ $bucket['count'] }}
                    </span>
                </div>
            @endforeach
        </div>
        <div class="flex justify-between mt-2 text-xs text-gray-400">
            <span>{{ $hourly[0]['label'] ?? '' }}</span>
            <span>{{ $hourly[count($hourly) - 1]['label'] ?? '' }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- Worst offenders --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">IP ที่ล้มเหลวมากที่สุด · 7 วัน</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                ระบบบล็อกเองเมื่อผิด {{ $autoBlock['ip_failures'] }} ครั้ง หรือแตะ {{ $autoBlock['ip_accounts'] }} บัญชี ภายใน {{ $autoBlock['window_minutes'] }} นาที
                @unless($autoBlock['enabled'])
                    <span class="text-amber-600 dark:text-amber-400 font-semibold">(ปิดการบล็อกอัตโนมัติอยู่)</span>
                @endunless
            </p>

            @forelse($topIps as $row)
                <div class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                    <div class="min-w-0">
                        <div class="font-mono text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $row['ip'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            ผิด {{ number_format($row['failures']) }} ครั้ง · {{ $row['accounts'] }} บัญชี ·
                            ล่าสุด {{ $row['last_seen']->diffForHumans() }}
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0 ml-3">
                        @if($row['blocked'])
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">บล็อกอยู่</span>
                            <form method="POST" action="{{ route('admin.security.logins.unblock') }}">
                                @csrf
                                <input type="hidden" name="ip" value="{{ $row['ip'] }}">
                                <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                                    ปลดบล็อก
                                </button>
                            </form>
                        @else
                            @if($row['trusted'])
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300"
                                      title="มีแอดมินเคยล็อกอินสำเร็จจาก IP นี้ ระบบจึงไม่บล็อกอัตโนมัติ">ยกเว้นไว้</span>
                            @endif
                            <form method="POST" action="{{ route('admin.security.logins.block') }}"
                                  onsubmit="return confirm('บล็อก ' + @js($row['ip']) + ' ถาวรใช่ไหม? ผู้ใช้ที่ใช้ IP นี้จะเข้าเว็บไม่ได้ทั้งเว็บ')">
                                @csrf
                                <input type="hidden" name="ip" value="{{ $row['ip'] }}">
                                <input type="hidden" name="reason" value="บล็อกจากหน้าสรุป IP ที่ล้มเหลวมากที่สุด">
                                <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-500 hover:bg-red-600 text-white">
                                    บล็อก
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-6 text-center">ยังไม่มีการเข้าสู่ระบบที่ล้มเหลวในช่วง 7 วัน</p>
            @endforelse
        </div>

        {{-- Block list --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">รายการ IP ที่ถูกบล็อก</h3>

            <form method="POST" action="{{ route('admin.security.logins.block') }}"
                  class="flex flex-wrap gap-2 mb-5 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600">
                @csrf
                <input type="text" name="ip" required placeholder="เช่น 203.0.113.9"
                       class="flex-1 min-w-[150px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-mono text-gray-900 dark:text-white">
                <input type="text" name="reason" maxlength="255" placeholder="เหตุผล (ไม่บังคับ)"
                       class="flex-1 min-w-[150px] px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
                <select name="hours" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
                    <option value="">ถาวร</option>
                    <option value="1">1 ชั่วโมง</option>
                    <option value="24">24 ชั่วโมง</option>
                    <option value="168">7 วัน</option>
                </select>
                <button class="px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-semibold">เพิ่ม</button>
            </form>

            <div class="space-y-2 max-h-96 overflow-y-auto">
                @forelse($blocks as $block)
                    <div class="flex items-center justify-between gap-3 py-2.5 border-b border-gray-100 dark:border-gray-700 last:border-0 {{ $block->isActive() ? '' : 'opacity-50' }}">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-sm font-semibold text-gray-900 dark:text-white">{{ $block->ip }}</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide
                                    {{ $block->source === 'manual'
                                        ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $block->source === 'manual' ? 'แอดมิน' : 'อัตโนมัติ' }}
                                </span>
                                @unless($block->isActive())
                                    <span class="text-[10px] text-gray-400 font-semibold uppercase">หมดอายุแล้ว</span>
                                @endunless
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ $block->reason ?: '—' }}
                                @if($block->hits > 0) · พยายามเข้าอีก {{ number_format($block->hits) }} ครั้ง @endif
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ $block->isPermanent() ? 'ถาวร' : 'ถึง ' . $block->expires_at->format('d/m/Y H:i') }}
                                @if($block->blocker) · โดย {{ $block->blocker->name }} @endif
                            </div>
                        </div>
                        <form method="POST" action="{{ route('admin.security.logins.unblock') }}" class="shrink-0">
                            @csrf
                            <input type="hidden" name="ip" value="{{ $block->ip }}">
                            <button class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                                ปลดบล็อก
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 py-6 text-center">ยังไม่มี IP ที่ถูกบล็อก</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- The log --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ประวัติการเข้าสู่ระบบ</h3>

            <form method="GET" class="flex flex-wrap gap-2">
                <select name="range" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
                    @foreach(['24h' => '24 ชั่วโมง', '7d' => '7 วัน', '30d' => '30 วัน', 'all' => 'ทั้งหมด'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['range'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="outcome" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
                    <option value="">ผลลัพธ์ทั้งหมด</option>
                    @foreach(\App\Models\LoginAttempt::OUTCOMES as $value => $label)
                        <option value="{{ $value }}" @selected($filters['outcome'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="method" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white">
                    <option value="">ทุกช่องทาง</option>
                    @foreach(\App\Models\LoginAttempt::METHODS as $value => $label)
                        <option value="{{ $value }}" @selected($filters['method'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <input type="text" name="ip" value="{{ $filters['ip'] }}" placeholder="IP"
                       class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-mono w-36 text-gray-900 dark:text-white">
                <input type="text" name="email" value="{{ $filters['email'] }}" placeholder="อีเมล"
                       class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm w-44 text-gray-900 dark:text-white">

                <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200 cursor-pointer">
                    <input type="checkbox" name="admin_only" value="1" @checked($filters['admin_only'])
                           class="rounded border-gray-300 text-indigo-600">
                    เฉพาะบัญชีแอดมิน
                </label>

                <button class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">ค้นหา</button>
                <a href="{{ route('admin.security.logins.index') }}"
                   class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-sm font-semibold">ล้าง</a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">เวลา</th>
                        <th class="px-4 py-3 text-left font-semibold">ผลลัพธ์</th>
                        <th class="px-4 py-3 text-left font-semibold">บัญชี</th>
                        <th class="px-4 py-3 text-left font-semibold">ช่องทาง</th>
                        <th class="px-4 py-3 text-left font-semibold">IP</th>
                        <th class="px-4 py-3 text-left font-semibold">อุปกรณ์</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($attempts as $attempt)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 {{ $attempt->is_admin_target ? 'bg-rose-50/50 dark:bg-rose-900/10' : '' }}">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                <div>{{ $attempt->created_at?->format('d/m/Y H:i:s') }}</div>
                                <div class="text-xs text-gray-400">{{ $attempt->created_at?->diffForHumans() }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $outcomeStyles[$attempt->outcome] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $attempt->outcomeLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-gray-900 dark:text-white">{{ $attempt->maskedEmail() }}</div>
                                @if($attempt->is_admin_target)
                                    <span class="text-xs font-bold text-rose-600 dark:text-rose-400">บัญชีแอดมิน</span>
                                @elseif($attempt->user)
                                    <span class="text-xs text-gray-400">{{ $attempt->user->name }}</span>
                                @else
                                    <span class="text-xs text-gray-400">ไม่มีบัญชีนี้</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $attempt->methodLabel() }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('admin.security.logins.index', ['ip' => $attempt->ip, 'range' => 'all']) }}"
                                   class="font-mono text-xs text-indigo-600 dark:text-indigo-400 hover:underline">{{ $attempt->ip ?? '—' }}</a>
                                @if($attempt->country)
                                    <span class="ml-1 text-xs text-gray-400">{{ $attempt->country }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate" title="{{ $attempt->user_agent }}">
                                {{ \Illuminate\Support\Str::limit($attempt->user_agent, 48) ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($attempt->ip && $attempt->outcome !== 'success')
                                    <form method="POST" action="{{ route('admin.security.logins.block') }}"
                                          onsubmit="return confirm('บล็อก ' + @js($attempt->ip) + ' ถาวรใช่ไหม?')">
                                        @csrf
                                        <input type="hidden" name="ip" value="{{ $attempt->ip }}">
                                        <input type="hidden" name="reason" value="บล็อกจากประวัติการเข้าสู่ระบบ">
                                        <button class="text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400">บล็อก IP</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400">ไม่พบข้อมูลตามเงื่อนไขที่เลือก</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attempts->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                {{ $attempts->links() }}
            </div>
        @endif
    </div>

    <p class="text-xs text-gray-400 text-center">
        เก็บประวัติไว้ {{ config('security.login_log_days') }} วัน แล้วลบอัตโนมัติ ·
        อีเมลถูกปิดบางส่วนเพื่อความเป็นส่วนตัวของลูกค้า
    </p>
</div>
@endsection
