{{-- ══════════ ภาพรวม: เข้าใช้งาน · สถานะเครื่อง · การใช้งาน · ล่าสุด ══════════ --}}
<div class="grid lg:grid-cols-5 gap-6">
    <div class="lg:col-span-3 {{ $card }} p-6">
        <h2 class="{{ $h2 }}"><x-bi th="เข้าใช้งาน" en="Connect" /></h2>
        <p class="{{ $lead }}">
            <x-bi th="เปิด Terminal (Mac/Linux) หรือ PowerShell (Windows) แล้วพิมพ์คำสั่งนี้"
                  en="Open Terminal (Mac/Linux) or PowerShell (Windows) and run:" />
        </p>

        @if($sshCommand)
            {{-- จอแคบ: ปุ่มคัดลอกตกลงบรรทัดใหม่ ให้คำสั่งได้เต็มบรรทัดไม่ขาดกลาง IP --}}
            <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-2 rounded-xl bg-slate-900 ring-1 ring-white/5 px-4 py-3" x-data="vpsCopy(@js($sshCommand))">
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
                    <a href="{{ $tabUrl('system') }}#root-password" class="text-indigo-600 dark:text-indigo-400 hover:underline"><x-bi th="ตั้งใหม่" en="Reset" /></a>
                </dd>
            </div>
            @if($network['ipv6']['address'])
                <div class="min-w-0 sm:col-span-2">
                    <dt class="text-xs text-slate-500 dark:text-slate-400">IPv6</dt>
                    <dd class="font-mono text-slate-900 dark:text-white break-all select-all">{{ $network['ipv6']['address'] }}</dd>
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

        @if($panel)
            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-indigo-200 dark:border-indigo-500/30 bg-indigo-50 dark:bg-indigo-500/10 px-4 py-3">
                <div class="min-w-0 text-sm">
                    <p class="font-semibold text-indigo-900 dark:text-indigo-200">
                        <x-bi :th="'แผงควบคุม ' . $panel['name']" :en="$panel['name'] . ' control panel'" />
                    </p>
                    <p class="text-xs text-indigo-800/80 dark:text-indigo-300/80">
                        <x-bi th="ลืมรหัสแผงควบคุม? ตั้งใหม่ได้ที่แท็บระบบ" en="Forgot the panel password? Reset it on the System tab." />
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($panel['url'])
                        <a href="{{ $panel['url'] }}" target="_blank" rel="noopener noreferrer" class="{{ $btnPrimary }}">
                            <x-bi th="เปิดแผงควบคุม" en="Open panel" />
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5h5v5M19 5l-8 8M10 5H5v14h14v-5"/></svg>
                        </a>
                    @endif
                    <a href="{{ $tabUrl('system') }}#panel-password" class="{{ $btnGhost }}"><x-bi th="ตั้งรหัสแผงควบคุม" en="Panel password" /></a>
                </div>
            </div>
        @endif
    </div>

    <div class="lg:col-span-2 {{ $card }} p-6 flex flex-col">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="{{ $h2 }}"><x-bi th="สถานะเครื่อง" en="Server status" /></h2>
                <p class="mt-2 inline-flex items-center gap-2 text-base font-semibold text-slate-900 dark:text-white">
                    <span class="relative flex h-3 w-3" aria-hidden="true">
                        @if($running)
                            <span class="absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-60 animate-ping"></span>
                        @endif
                        <span class="relative inline-flex h-3 w-3 rounded-full {{ $power['dot'] }}"></span>
                    </span>
                    <x-bi :th="$power['th']" :en="$power['en']" />
                </p>
            </div>
            @if($uptime)
                <div class="text-right">
                    <p class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="เปิดต่อเนื่อง" en="Uptime" /></p>
                    <p class="text-sm font-bold text-slate-900 dark:text-white tabular-nums"><x-bi :th="$uptime['th']" :en="$uptime['en']" /></p>
                </div>
            @endif
        </div>

        <div class="mt-5 grid grid-cols-3 gap-2">
            <form method="POST" action="{{ route('customer.vps.power', $server->id) }}" data-once>
                @csrf
                <input type="hidden" name="action" value="start">
                <button type="submit" class="w-full flex flex-col items-center gap-1.5 rounded-xl px-2 py-3 text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition disabled:opacity-40 disabled:cursor-not-allowed" @disabled($busy || $running)>
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5v13a1 1 0 001.5.87l10.5-6.5a1 1 0 000-1.74L9.5 4.63A1 1 0 008 5.5z"/></svg>
                    <x-bi th="เปิดเครื่อง" en="Start" />
                </button>
            </form>
            <form method="POST" action="{{ route('customer.vps.power', $server->id) }}" data-once
                  onsubmit="return confirm(@js($confirm['restart']))">
                @csrf
                <input type="hidden" name="action" value="restart">
                <button type="submit" class="w-full flex flex-col items-center gap-1.5 rounded-xl px-2 py-3 text-xs font-semibold border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition disabled:opacity-40 disabled:cursor-not-allowed" @disabled($busy || $stopped)>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <x-bi th="รีสตาร์ท" en="Restart" />
                </button>
            </form>
            <form method="POST" action="{{ route('customer.vps.power', $server->id) }}" data-once
                  onsubmit="return confirm(@js($confirm['stop']))">
                @csrf
                <input type="hidden" name="action" value="stop">
                <button type="submit" class="w-full flex flex-col items-center gap-1.5 rounded-xl px-2 py-3 text-xs font-semibold border border-red-300 dark:border-red-500/40 text-red-700 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-500/10 transition disabled:opacity-40 disabled:cursor-not-allowed" @disabled($busy || $stopped)>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-width="2" d="M12 3v8"/><path stroke-linecap="round" stroke-width="2" d="M6.3 6.3a8 8 0 1011.4 0"/></svg>
                    <x-bi th="ปิดเครื่อง" en="Stop" />
                </button>
            </form>
        </div>


        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            <x-bi th="ปิดเครื่องไม่หยุดรอบบิล — เครื่องยังเป็นของคุณ และคิดค่าบริการตามปกติจนหมดอายุ"
                  en="Stopping doesn't pause billing — the server stays yours, and billed, until it expires." />
        </p>

        {{-- ทางลัดไปงานที่ทำบ่อย — ลิงก์ไปแท็บ ไม่ใช่ปุ่มสั่งเครื่อง --}}
        <div class="pt-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-2"><x-bi th="ทางลัด" en="Shortcuts" /></p>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <a href="{{ $tabUrl('network') }}#firewall" class="flex items-center gap-2 rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-slate-700 dark:text-slate-200 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-300 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3l7 3v6c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9V6l7-3z"/></svg>
                    <x-bi th="ไฟร์วอลล์" en="Firewall" />
                </a>
                <a href="{{ $tabUrl('backups') }}" class="flex items-center gap-2 rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-slate-700 dark:text-slate-200 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-300 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h3l2-3h6l2 3h3v12H4V7z"/><circle cx="12" cy="13" r="3.5" stroke-width="2"/></svg>
                    <x-bi th="สแนปช็อต" en="Snapshot" />
                </a>
                <a href="{{ $tabUrl('network') }}#ssh-keys" class="flex items-center gap-2 rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-slate-700 dark:text-slate-200 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-300 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="15" r="4" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 12l9-9m-4 4l3 3"/></svg>
                    SSH key
                </a>
                <a href="{{ $tabUrl('system') }}#recovery" class="flex items-center gap-2 rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-2 text-slate-700 dark:text-slate-200 hover:border-indigo-400 hover:text-indigo-600 dark:hover:text-indigo-300 transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2M3.5 12a8.5 8.5 0 102.5-6L3 9m0-5v5h5"/></svg>
                    <x-bi th="กู้ระบบ" en="Recovery" />
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ══════════ การใช้งาน ══════════ --}}
<div class="{{ $card }} p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="{{ $h2 }}"><x-bi th="การใช้งาน" en="Usage" /></h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><x-bi th="ข้อมูลอัปเดตทุก 5 นาที" en="Updated every 5 minutes" /></p>
        </div>
        <div class="inline-flex rounded-lg border border-slate-200 dark:border-slate-700 p-0.5 text-xs font-semibold" role="group" aria-label="ช่วงเวลา / Range">
            <a href="{{ $tabUrl('overview') }}" class="px-3 py-1.5 rounded-md transition {{ $range === '24h' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}" @if($range === '24h') aria-current="true" @endif>
                <x-bi th="24 ชม." en="24h" />
            </a>
            <a href="{{ $tabUrl('overview', ['range' => '7d']) }}" class="px-3 py-1.5 rounded-md transition {{ $range === '7d' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700' }}" @if($range === '7d') aria-current="true" @endif>
                <x-bi th="7 วัน" en="7 days" />
            </a>
        </div>
    </div>

    @if($metrics)
        @php
            $axisFrom = $metrics['from'] ? $metrics['from']->format($range === '7d' ? 'j M' : 'H:i') : '';
            $axisTo = $metrics['to'] ? $metrics['to']->format($range === '7d' ? 'j M' : 'H:i') : '';
            $charts = [
                ['key' => 'cpu', 'title' => 'CPU', 'value' => $pct($metrics['cpu'] ?? null), 'sub' => null, 'chart' => $metrics['cpu_chart'] ?? null,
                    'stroke' => 'text-indigo-500 dark:text-indigo-400', 'stop' => 'stop-color: rgb(99 102 241)'],
                ['key' => 'ram', 'title' => 'RAM', 'value' => $pct($metrics['ram_percent'] ?? null),
                    'sub' => $gb($metrics['ram_used'] ?? null) . ($server->spec('memory_mb') ? ' / ' . \App\Models\VpsPlan::sizeLabel($server->spec('memory_mb')) : ''),
                    'chart' => $metrics['ram_chart'] ?? null, 'stroke' => 'text-cyan-500 dark:text-cyan-400', 'stop' => 'stop-color: rgb(6 182 212)'],
            ];
        @endphp
        <div class="mt-5 grid lg:grid-cols-2 gap-4">
            @foreach($charts as $c)
                <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4 min-w-0">
                    <div class="flex items-baseline justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $c['title'] }}</p>
                        @if($c['chart'])
                            <p class="text-xs text-slate-500 dark:text-slate-400 tabular-nums">
                                <x-bi th="เฉลี่ย" en="avg" /> {{ number_format($c['chart']['avg'], 1) }}% ·
                                <x-bi th="สูงสุด" en="peak" /> {{ number_format($c['chart']['peak'], 1) }}%
                            </p>
                        @endif
                    </div>
                    <p class="mt-1 text-3xl font-bold text-slate-900 dark:text-white tabular-nums">
                        {{ $c['value'] }}
                        @if($c['sub'])
                            <span class="text-sm font-normal text-slate-500 dark:text-slate-400">{{ $c['sub'] }}</span>
                        @endif
                    </p>
                    @if($c['chart'])
                        <div class="relative mt-3">
                            <svg viewBox="0 0 600 160" preserveAspectRatio="none" class="w-full h-32 {{ $c['stroke'] }}" role="img"
                                 aria-label="{{ $c['title'] }} {{ $range === '7d' ? '7 วัน' : '24 ชม.' }}">
                                <defs>
                                    <linearGradient id="vps-fill-{{ $c['key'] }}" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" style="{{ $c['stop'] }}; stop-opacity: .35"/>
                                        <stop offset="100%" style="{{ $c['stop'] }}; stop-opacity: 0"/>
                                    </linearGradient>
                                </defs>
                                @foreach([40, 80, 120] as $gy)
                                    <line x1="0" y1="{{ $gy }}" x2="600" y2="{{ $gy }}" stroke="currentColor" stroke-opacity=".12" stroke-dasharray="4 6" vector-effect="non-scaling-stroke"/>
                                @endforeach
                                <polygon points="{{ $c['chart']['area'] }}" fill="url(#vps-fill-{{ $c['key'] }})"/>
                                <polyline points="{{ $c['chart']['line'] }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/>
                            </svg>
                            <div class="mt-1 flex justify-between text-[11px] text-slate-400 dark:text-slate-500 tabular-nums">
                                <span>{{ $axisFrom }}</span>
                                <span><x-bi th="ตอนนี้" en="now" /></span>
                            </div>
                        </div>
                    @else
                        <p class="mt-3 text-xs text-slate-500 dark:text-slate-400"><x-bi th="ยังมีข้อมูลไม่พอวาดกราฟ" en="Not enough data for a chart yet" /></p>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-4 grid sm:grid-cols-3 gap-4">
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
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                    <x-bi :th="'ทราฟฟิก ' . ($range === '7d' ? '7 วัน' : '24 ชม.')" :en="'Traffic, ' . ($range === '7d' ? '7 days' : '24h')" />
                </p>
                <p class="mt-1 text-sm text-slate-900 dark:text-white tabular-nums">
                    <span class="text-emerald-500" aria-hidden="true">↑</span> {{ $gb($metrics['traffic_out_gb'] ?? null, 2) }}
                    <span class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="ขาออก" en="out" /></span>
                </p>
                <p class="mt-1 text-sm text-slate-900 dark:text-white tabular-nums">
                    <span class="text-sky-500" aria-hidden="true">↓</span> {{ $gb($metrics['traffic_in_gb'] ?? null, 2) }}
                    <span class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="ขาเข้า" en="in" /></span>
                </p>
            </div>
            <div class="{{ $tile }}">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400"><x-bi th="เปิดต่อเนื่องมาแล้ว" en="Up for" /></p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-white tabular-nums">
                    @if($uptime)
                        <x-bi :th="$uptime['th']" :en="$uptime['en']" />
                    @else
                        —
                    @endif
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400"><x-bi th="นับจากเปิดเครื่องครั้งล่าสุด" en="Since the last boot" /></p>
            </div>
        </div>
    @else
        <p class="{{ $lead }} mt-4">
            <x-bi th="ยังไม่มีข้อมูลการใช้งาน — กราฟจะเริ่มแสดงหลังเครื่องทำงานไปสักพัก"
                  en="No usage data yet — the graphs appear once the server has been running for a while." />
        </p>
    @endif
</div>

{{-- ══════════ ล่าสุด ══════════ --}}
@if(! empty($actions))
    <div class="{{ $card }} p-6">
        <div class="flex items-center justify-between gap-3">
            <h2 class="{{ $h2 }}"><x-bi th="ทำอะไรกับเครื่องล่าสุด" en="Recent activity" /></h2>
            <a href="{{ $tabUrl('activity') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"><x-bi th="ดูทั้งหมด" en="See all" /></a>
        </div>
        @include('customer.vps.tabs.partials.action-list', ['items' => $actions])
    </div>
@endif
