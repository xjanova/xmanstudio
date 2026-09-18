@extends($customerLayout ?? 'layouts.customer')

@section('title', 'เครื่องของฉัน · GPUxMINE')
@section('page-title', 'GPUxMINE — เครื่องของฉัน')
@section('page-description', 'แชร์การ์ดจอที่บ้านให้รับงาน AI แล้วได้ค่าตอบแทนเข้ากระเป๋า XMAN Studio')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    @if (session('success'))
        <div class="rounded-xl bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    {{-- รหัสจับคู่ที่เพิ่งออก — แสดงใหญ่ เพราะต้องอ่านจากจอนี้ไปพิมพ์อีกจอ --}}
    @if (session('pairing_code'))
        <div class="rounded-2xl border-2 border-blue-300 bg-blue-50 p-6 text-center">
            <p class="text-sm text-blue-900 mb-2">พิมพ์รหัสนี้ลงในโปรแกรม GPUxMINE ที่หน้า <strong>Settings</strong></p>
            <p class="font-mono text-4xl font-bold tracking-widest text-blue-900 select-all">{{ session('pairing_code') }}</p>
            <p class="text-xs text-blue-700 mt-3">
                ใช้ได้ {{ \App\Models\GpuNode::PAIRING_TTL_MINUTES }} นาที และใช้ได้ครั้งเดียว
                — หมดอายุแล้วกดขอใหม่ได้เลย
            </p>
        </div>
    @endif

    {{-- เริ่มต้นใช้งาน --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">เพิ่มเครื่องใหม่</h2>
                <ol class="mt-3 text-sm text-gray-600 space-y-1.5 list-decimal list-inside">
                    <li>ติดตั้งโปรแกรม GPUxMINE บนเครื่องที่มีการ์ดจอ</li>
                    <li>กดปุ่ม <strong>ขอรหัสจับคู่</strong> ด้านขวา</li>
                    <li>พิมพ์รหัสลงในโปรแกรมที่หน้า Settings แล้วกดลงทะเบียน</li>
                    <li>โปรแกรมจะวัดความเร็วการ์ดจอให้เอง แล้วเริ่มรับงานตามที่เครื่องไหว</li>
                </ol>
            </div>
            <div class="flex flex-col gap-2 shrink-0">
                <form method="POST" action="{{ route('gpuxmine.pair') }}">
                    @csrf
                    <button type="submit"
                            @disabled(! $relayReady)
                            class="w-full px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        ขอรหัสจับคู่
                    </button>
                </form>
                <a href="{{ $downloadUrl }}" target="_blank" rel="noopener"
                   class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-sm font-medium hover:bg-gray-50 text-center">
                    ดาวน์โหลดโปรแกรม
                </a>
            </div>
        </div>

        @unless ($relayReady)
            <p class="mt-4 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                ระบบรับเครื่องยังไม่พร้อมใช้งาน — ผู้ดูแลยังไม่ได้ตั้งค่า relay
            </p>
        @endunless
    </div>

    {{-- รายการเครื่อง --}}
    <div class="space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">เครื่องที่ลงทะเบียนไว้</h2>

        @forelse ($nodes->where('paired_at', '!=', null) as $node)
            <div class="rounded-2xl border border-gray-200 bg-white p-5">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-semibold text-gray-900 truncate">{{ $node->displayName() }}</h3>
                            <span class="text-xs px-2 py-0.5 rounded-full border {{ $node->statusTone() }}">
                                {{ $node->statusLabel() }}
                            </span>
                            @if ($node->assessed)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                                    {{ strtoupper($node->tier) }} · {{ number_format($node->score) }} คะแนน
                                </span>
                            @endif
                        </div>

                        <dl class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-2 text-sm">
                            <div>
                                <dt class="text-gray-500 text-xs">การ์ดจอ</dt>
                                <dd class="text-gray-900">{{ $node->gpu_name ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-xs">VRAM</dt>
                                <dd class="text-gray-900">
                                    {{ $node->vram_total_mb > 0 ? number_format($node->vram_total_mb / 1024, 1) . ' GB' : '—' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 text-xs">เห็นล่าสุด</dt>
                                <dd class="text-gray-900">{{ $node->last_seen_at?->diffForHumans() ?? '—' }}</dd>
                            </div>
                        </dl>

                        {{-- งานที่เครื่องรับได้ มาจากการวัดจริง ไม่ใช่การประกาศเอง --}}
                        @if ($node->assessed)
                            <div class="mt-3">
                                <p class="text-xs text-gray-500 mb-1.5">งานที่เครื่องนี้รับได้</p>
                                @php
                                    $names = ['image' => 'สร้างภาพ', 'video' => 'สร้างวิดีโอ', 'upscale' => 'ขยายภาพ', 'embed' => 'ประมวลผลข้อความ'];
                                @endphp
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($node->can_run ?? [] as $kind)
                                        <span class="text-xs px-2 py-1 rounded-lg bg-green-50 text-green-800 border border-green-200">
                                            {{ $names[$kind] ?? $kind }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-amber-700">ยังไม่มีงานประเภทใดที่เครื่องนี้รับไหว — ดูรายละเอียดในโปรแกรมหน้า Benchmark</span>
                                    @endforelse
                                </div>
                            </div>
                        @endif

                        @if ($node->dispatch_note && $node->dispatch_status !== 'eligible')
                            <p class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                {{ $node->dispatch_note }}
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2 shrink-0">
                        <form method="POST" action="{{ route('gpuxmine.rename', $node->id) }}" class="flex gap-2">
                            @csrf
                            <input type="text" name="label" value="{{ $node->label }}" maxlength="60"
                                   placeholder="ตั้งชื่อเครื่อง"
                                   class="w-40 px-3 py-1.5 text-sm rounded-lg border border-gray-300">
                            <button type="submit" class="px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-gray-50">
                                บันทึก
                            </button>
                        </form>
                        <form method="POST" action="{{ route('gpuxmine.forget', $node->id) }}"
                              onsubmit="return confirm('ถอนเครื่อง {{ $node->displayName() }} ออกจากระบบ? จะไม่มีงานส่งมาที่เครื่องนี้อีก (ยอดที่ค้างจ่ายยังอยู่)');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full px-3 py-1.5 text-sm rounded-lg border border-red-200 text-red-700 hover:bg-red-50">
                                ถอนเครื่องออก
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-sm text-gray-600">
                ยังไม่มีเครื่องที่ลงทะเบียน — กด "ขอรหัสจับคู่" ด้านบนเพื่อเริ่ม
            </div>
        @endforelse
    </div>


    {{-- ประวัติการรับเงิน --}}
    {{-- เจ้าของเครื่องเอาการ์ดจอของเขามาให้เราใช้ ตัวเลขว่าได้อะไรกลับไป
         ต้องอยู่ในที่ที่เขาเปิดดูได้ตลอด ไม่ใช่อยู่แค่ในโปรแกรมแล้วหายไป
         เมื่อปิดเครื่อง --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">ประวัติการรับเงิน</h2>
            <a href="{{ route('user.wallet.index') }}" class="text-sm text-primary-600 hover:underline">ไปที่กระเป๋าเงิน</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 p-4">
                <p class="text-xs text-emerald-700 mb-1">เข้ากระเป๋าแล้ว</p>
                <p class="text-2xl font-semibold text-emerald-900">฿{{ number_format($paidSatang / 100, 2) }}</p>
            </div>
            <div class="rounded-lg bg-amber-50 border border-amber-100 p-4">
                <p class="text-xs text-amber-700 mb-1">รอเข้ากระเป๋า</p>
                <p class="text-2xl font-semibold text-amber-900">฿{{ number_format($pendingSatang / 100, 2) }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 border border-gray-200 p-4">
                <p class="text-xs text-gray-600 mb-1">งานที่ทำไปแล้ว</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($jobsTotal) }}<span class="text-sm font-normal text-gray-500"> งาน</span></p>
            </div>
        </div>

        @if ($earnings->isEmpty())
            {{-- บอกตามจริงว่าทำไมยังว่าง ดีกว่าปล่อยให้เดาว่าระบบพัง --}}
            <div class="rounded-lg bg-gray-50 border border-dashed border-gray-300 p-6 text-center">
                <p class="text-sm text-gray-600">ยังไม่มีงานที่จ่ายเงิน</p>
                <p class="text-xs text-gray-500 mt-1">
                    เครื่องจะเริ่มได้รับงานเมื่อผ่านการประเมิน และมีโมเดลในระบบที่การ์ดของคุณรับไหว
                    — รายการงานแต่ละชิ้นพร้อมยอดเงินจะขึ้นที่นี่ทันทีที่ทำเสร็จ
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 border-b border-gray-200">
                            <th class="py-2 pr-3 font-medium">เมื่อไร</th>
                            <th class="py-2 pr-3 font-medium">งาน</th>
                            <th class="py-2 pr-3 font-medium">เครื่อง</th>
                            <th class="py-2 pr-3 font-medium text-right">ใช้เวลา</th>
                            <th class="py-2 pr-3 font-medium text-right">ได้รับ</th>
                            <th class="py-2 font-medium">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($earnings as $row)
                            <tr>
                                <td class="py-2 pr-3 text-gray-600 whitespace-nowrap">
                                    {{ optional($row->completed_at)->format('d/m/y H:i') ?? '—' }}
                                </td>
                                <td class="py-2 pr-3">
                                    {{ $row->kindLabel() }}
                                    @if ($row->lane === 'slow')
                                        <span class="ml-1 text-xs text-amber-600">· ไม่เร่ง</span>
                                    @endif
                                </td>
                                <td class="py-2 pr-3 text-gray-600">
                                    {{ optional($row->node)->displayName() ?? $row->worker_id }}
                                </td>
                                <td class="py-2 pr-3 text-right text-gray-600 whitespace-nowrap">
                                    {{ $row->seconds > 0 ? $row->seconds . ' วิ' : '—' }}
                                </td>
                                <td class="py-2 pr-3 text-right font-medium whitespace-nowrap">
                                    ฿{{ number_format($row->amountBaht(), 2) }}
                                </td>
                                <td class="py-2">
                                    <span class="text-xs px-2 py-0.5 rounded
                                        {{ $row->status === 'paid' ? 'bg-emerald-50 text-emerald-700' : ($row->status === 'void' ? 'bg-gray-100 text-gray-500' : 'bg-amber-50 text-amber-700') }}">
                                        {{ $row->statusLabel() }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-400 mt-3">แสดง 50 รายการล่าสุด</p>
        @endif
    </div>

    <p class="text-xs text-gray-500">
        เครื่องจะรับงานได้ก็ต่อเมื่อผ่านการประเมินความเร็วแล้วเท่านั้น
        โปรแกรมวัดให้เองตอนเปิดครั้งแรก และวัดใหม่เมื่อเปลี่ยนการ์ดจอหรืออัปเดตเวอร์ชัน
    </p>
</div>

@endsection
