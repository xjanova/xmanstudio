@extends($customerLayout ?? 'layouts.customer')

@section('title', 'เชื่อมโปรแกรมกับบัญชี')
@section('page-title')<x-bi th="เชื่อมโปรแกรมกับบัญชี" en="Link an app to your account" />@endsection
@section('page-description')<x-bi th="ยืนยันโค้ดที่แสดงในโปรแกรม CluadeX หรือ BrainX บนคอมพิวเตอร์ของคุณ" en="Confirm the code shown in CluadeX or BrainX on your computer" />@endsection

@section('content')
<div class="max-w-xl space-y-6" data-state="{{ $state }}">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-5 sm:p-8 space-y-5">
            @switch($state)
                @case('approved')
                    <div class="text-center space-y-3">
                        <div class="mx-auto w-14 h-14 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-3xl text-emerald-600 dark:text-emerald-400">✓</div>
                        <p class="text-lg font-semibold text-emerald-700 dark:text-emerald-400">เชื่อมแล้ว กลับไปที่โปรแกรมได้เลย</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">โปรแกรมจะขึ้นชื่อบัญชีของคุณเองภายในไม่กี่วินาที ปิดหน้านี้ได้</p>
                    </div>
                    @break

                @case('denied')
                    <div class="text-center space-y-3">
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">ยกเลิกแล้ว</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">โปรแกรมบนเครื่องนั้นจะไม่ได้เชื่อมกับบัญชีของคุณ</p>
                    </div>
                    @break

                @case('confirm')
                    <div class="space-y-4"
                         x-data="{ left: {{ (int) $pending['seconds_left'] }} }"
                         x-init="setInterval(() => { if (left > 0) left--; }, 1000)">
                        <div class="text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">โค้ดในโปรแกรม</p>
                            <p class="mt-1 font-mono text-3xl sm:text-4xl font-bold tracking-widest text-gray-900 dark:text-white">{{ $pending['user_code'] }}</p>
                        </div>

                        <dl class="rounded-xl bg-gray-50 dark:bg-gray-900/40 divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            <div class="flex justify-between gap-4 px-4 py-3">
                                <dt class="text-gray-500 dark:text-gray-400">โปรแกรม</dt>
                                <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $pending['product'] }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 px-4 py-3">
                                <dt class="text-gray-500 dark:text-gray-400">เครื่อง</dt>
                                <dd class="text-right font-medium text-gray-900 dark:text-white break-all">{{ $pending['machine_name'] }}</dd>
                            </div>
                            @if($pending['app_version'])
                                <div class="flex justify-between gap-4 px-4 py-3">
                                    <dt class="text-gray-500 dark:text-gray-400">รุ่นโปรแกรม</dt>
                                    <dd class="text-right font-medium text-gray-900 dark:text-white">{{ $pending['app_version'] }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between gap-4 px-4 py-3">
                                <dt class="text-gray-500 dark:text-gray-400">เวลาที่เหลือ</dt>
                                <dd class="text-right font-medium text-gray-900 dark:text-white">
                                    <span x-text="left > 0 ? Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0') + ' นาที' : 'หมดเวลาแล้ว'">{{ intdiv($pending['seconds_left'], 60) }}:{{ str_pad($pending['seconds_left'] % 60, 2, '0', STR_PAD_LEFT) }} นาที</span>
                                </dd>
                            </div>
                        </dl>

                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            เครื่องนี้จะเชื่อมกับบัญชี <strong class="break-all">{{ auth()->user()->email }}</strong>
                            และใช้ไลเซ่นในบัญชีนี้ได้ กดยืนยันเฉพาะเมื่อโค้ดตรงกับที่เห็นในโปรแกรมของคุณเอง
                        </p>

                        {{-- สองฟอร์มแยก: action อยู่ใน hidden input — ปุ่มที่ถูก disable ตอนส่งจะไม่หาย value ไป --}}
                        <div class="space-y-3" x-data="{ sending: false }">
                            <form method="POST" action="{{ route('app-device.confirm') }}" x-on:submit="sending = true">
                                @csrf
                                <input type="hidden" name="user_code" value="{{ $pending['user_code'] }}">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" x-bind:disabled="sending || left <= 0"
                                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 px-6 py-3 text-white font-semibold shadow-lg shadow-sky-500/30 hover:from-sky-700 hover:to-indigo-700 transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                                    ยืนยัน เชื่อมเครื่องนี้กับบัญชีของฉัน
                                </button>
                            </form>
                            <form method="POST" action="{{ route('app-device.confirm') }}" x-on:submit="sending = true">
                                @csrf
                                <input type="hidden" name="user_code" value="{{ $pending['user_code'] }}">
                                <input type="hidden" name="action" value="deny">
                                <button type="submit" x-bind:disabled="sending"
                                        class="w-full inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-gray-600 px-6 py-3 font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                                    ไม่ใช่ฉัน ยกเลิก
                                </button>
                            </form>
                        </div>
                    </div>
                    @break

                @default
                    {{-- enter / invalid: ช่องกรอกโค้ด --}}
                    @if($state === 'invalid')
                        <p class="rounded-xl bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm font-medium text-red-700 dark:text-red-300">
                            โค้ดหมดอายุหรือไม่ถูกต้อง
                        </p>
                    @endif

                    <form method="GET" action="{{ route('app-device.show') }}" class="space-y-4">
                        <label for="device-code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            กรอกโค้ด 8 ตัวที่แสดงในโปรแกรม
                        </label>
                        <input id="device-code" name="code" type="text" value="{{ $code }}" required
                               maxlength="32" autocomplete="off" autocapitalize="characters" spellcheck="false"
                               placeholder="XXXX-XXXX"
                               class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-3 text-center font-mono text-2xl tracking-widest uppercase text-gray-900 dark:text-white focus:border-sky-500 focus:ring-sky-500">
                        @error('user_code')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 px-6 py-3 text-white font-semibold shadow-lg shadow-sky-500/30 hover:from-sky-700 hover:to-indigo-700 transition-all">
                            ถัดไป
                        </button>
                    </form>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        โค้ดใช้ได้ 10 นาที ถ้าหมดเวลา ให้กด "ขอโค้ดใหม่" ในโปรแกรม
                    </p>
            @endswitch
        </div>
    </div>
</div>
@endsection
