{{--
    A report from a desktop app's "แจ้งปัญหา" window (channel 'app'): who sent it, what they agreed to attach,
    the screenshot, the diagnostics the app collected and the recent debug log. Everything here was scrubbed of
    keys and tokens twice (by the app, then by SecretScrubber) before it was stored.
--}}
@php
    $meta = $report->metadata ?? [];
    $diag = is_array($report->additional_info) ? $report->additional_info : [];
    $screenshots = $report->attachments->filter(fn ($a) => $a->isImage());
    $sectionNames = [
        'app' => 'แอป', 'system' => 'เครื่อง', 'settings' => 'การตั้งค่า (สรุป)', 'providers' => 'ผู้ให้บริการ AI',
        'recent_errors' => 'ข้อผิดพลาดล่าสุด', 'network' => 'เครือข่าย', 'mcp' => 'MCP / ส่วนเสริม',
        'performance' => 'ประสิทธิภาพ', 'session' => 'เซสชันปัจจุบัน', 'chat' => 'บทสนทนา (ผู้ใช้อนุญาต)',
    ];
    $scalar = fn ($v) => is_bool($v) ? ($v ? 'ใช่' : 'ไม่') : (is_scalar($v) || $v === null ? (string) ($v ?? '—') : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
@endphp

<div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-indigo-200 dark:border-indigo-900/50 p-6 space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-lg font-bold text-indigo-700 dark:text-indigo-300">รายงานจากแอป</h3>
        @if(!empty($meta['included']))
            <div class="flex flex-wrap gap-1.5">
                @foreach($meta['included'] as $part)
                    <span class="px-2 py-0.5 text-[11px] font-medium rounded-full bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">{{ $sectionNames[$part] ?? $part }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <div>
            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400">บัญชี</dt>
            <dd class="text-gray-900 dark:text-white break-words">
                @if($report->user)
                    {{ $report->user->name }} <span class="text-gray-500 dark:text-gray-400">({{ $report->user->email }})</span>
                @else
                    <span class="text-gray-500 dark:text-gray-400">ไม่ได้เชื่อมบัญชี</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400">ไลเซ่น</dt>
            <dd class="text-gray-900 dark:text-white font-mono text-xs">
                @if($report->licenseKey)
                    {{ $meta['license_masked'] ?? '••••' }}
                    <span class="font-sans text-gray-500 dark:text-gray-400">· {{ $report->licenseKey->license_type }} · {{ $report->licenseKey->status }}</span>
                @else
                    <span class="font-sans text-gray-500 dark:text-gray-400">—</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400">อีเมลติดต่อกลับ</dt>
            <dd class="text-gray-900 dark:text-white break-all">{{ $report->user_email ?: '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold text-gray-500 dark:text-gray-400">ประเภท</dt>
            <dd class="text-gray-900 dark:text-white">{{ $report->report_type }}</dd>
        </div>
    </dl>

    @foreach($screenshots as $shot)
        <figure>
            <figcaption class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">ภาพหน้าจอ ({{ $shot->formatted_size }})</figcaption>
            <a href="{{ route('admin.bug-reports.attachment', [$report, $shot]) }}" target="_blank" rel="noopener">
                <img src="{{ route('admin.bug-reports.attachment', [$report, $shot]) }}" alt="ภาพหน้าจอจากแอป" loading="lazy"
                     class="w-full max-h-[480px] object-contain rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            </a>
        </figure>
    @endforeach

    @foreach($diag as $section => $value)
        <details class="group rounded-lg border border-gray-200 dark:border-gray-700" @if(in_array($section, ['recent_errors', 'app', 'system'], true)) open @endif>
            <summary class="cursor-pointer select-none px-4 py-2.5 text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center justify-between">
                <span>{{ $sectionNames[$section] ?? $section }}</span>
                @if(is_array($value) && array_is_list($value))
                    <span class="text-xs font-normal text-gray-500 dark:text-gray-400">{{ count($value) }} รายการ</span>
                @endif
            </summary>
            <div class="px-4 pb-3 overflow-x-auto">
                @if(is_array($value) && ! array_is_list($value))
                    <table class="min-w-full text-xs">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($value as $k => $v)
                                <tr>
                                    <th scope="row" class="py-1.5 pr-4 text-left font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap align-top">{{ $k }}</th>
                                    <td class="py-1.5 text-gray-800 dark:text-gray-200 font-mono break-all">{{ $scalar($v) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @elseif(is_array($value))
                    <ul class="space-y-1.5">
                        @foreach($value as $item)
                            <li class="text-xs font-mono text-gray-800 dark:text-gray-200 break-all rounded bg-gray-50 dark:bg-gray-900/60 px-2 py-1.5">
                                @if(is_array($item) && ! array_is_list($item))
                                    @foreach($item as $k => $v)
                                        <span class="text-gray-500 dark:text-gray-400">{{ $k }}:</span> {{ $scalar($v) }}@if(! $loop->last) · @endif
                                    @endforeach
                                @else
                                    {{ $scalar($item) }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-xs font-mono text-gray-800 dark:text-gray-200 break-all">{{ $scalar($value) }}</p>
                @endif
            </div>
        </details>
    @endforeach

    @if($report->log_excerpt)
        <div x-data="{ copied: false }">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400">ล็อกดีบั๊กล่าสุด ({{ number_format(substr_count($report->log_excerpt, "\n") + 1) }} บรรทัด)</p>
                <button type="button" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                        @click="navigator.clipboard.writeText($refs.log.innerText); copied = true; setTimeout(() => copied = false, 1500)">
                    <span x-show="!copied">คัดลอก</span><span x-show="copied" x-cloak>คัดลอกแล้ว</span>
                </button>
            </div>
            <pre x-ref="log" class="bg-gray-900 text-gray-100 p-4 rounded-lg text-[11px] leading-relaxed overflow-auto whitespace-pre max-h-[32rem] font-mono">{{ $report->log_excerpt }}</pre>
        </div>
    @endif
</div>
