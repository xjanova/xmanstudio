@extends($customerLayout ?? 'layouts.customer')

@section('title', 'รับไลเซ่นฟรี MacDock')
@section('page-title')<x-bi th="รับไลเซ่นฟรี MacDock" en="Get your free MacDock licence" />@endsection
@section('page-description')<x-bi th="ไลเซ่นฟรีผูกกับบัญชีและเครื่องนี้ เปิดการอัปเดตอัตโนมัติ" en="A free licence for this PC on your account — it turns on automatic updates" />@endsection

@section('content')
{{-- ข้อความสำเร็จ/ผิดพลาดจาก session layout ของหน้าบัญชีแสดงให้เองแล้ว — หน้านี้แสดงแค่ error ของฟอร์ม --}}
<div class="max-w-3xl space-y-6" data-state="{{ $state }}">

    {{-- ══════════ เครื่องนี้ ══════════ --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-5 sm:px-8 py-5 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <x-bi th="เครื่องนี้" en="This PC" />
            </h2>
            @if($device)
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $device->machine_name ?: 'Windows PC' }}</p>
            @endif
        </div>

        <div class="p-5 sm:p-8 space-y-4">
            @switch($state)
                @case('no_machine')
                    <p class="text-gray-700 dark:text-gray-300">
                        ไม่พบรหัสเครื่อง — เปิดโปรแกรม MacDock แล้วกด <strong>รับไลเซ่นฟรี</strong> จากในโปรแกรม หน้านี้จะเปิดพร้อมรหัสเครื่องให้เอง
                    </p>
                    @break

                @case('unknown_device')
                    <p class="text-gray-700 dark:text-gray-300">
                        ยังไม่พบเครื่องนี้ในระบบ — เปิดโปรแกรม MacDock ให้เชื่อมต่ออินเทอร์เน็ตสักครู่ แล้วโหลดหน้านี้ใหม่
                    </p>
                    @break

                @case('ready')
                    <p class="text-gray-700 dark:text-gray-300">
                        กดยืนยันเพื่อรับไลเซ่นฟรีให้เครื่องนี้ ไลเซ่นจะผูกกับบัญชี <strong>{{ auth()->user()->email }}</strong>
                        แล้วโปรแกรมจะอัปเดตตัวเองได้อัตโนมัติ
                    </p>

                    <form method="POST" action="{{ route('macdock.free-license.store') }}"
                          x-data="{ sending: false }" @submit="sending = true">
                        @csrf
                        <input type="hidden" name="machine_id" value="{{ $machineId }}">
                        @error('machine_id')<p class="mb-3 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror

                        <button type="submit" x-bind:disabled="sending"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 px-7 py-3 text-white font-semibold shadow-lg shadow-sky-500/30 hover:from-sky-700 hover:to-indigo-700 transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                            <x-bi th="ยืนยัน รับไลเซ่นฟรี" en="Confirm — get the free licence" />
                        </button>
                    </form>
                    @break

                @case('mine')
                    <p class="text-emerald-700 dark:text-emerald-400 font-medium">
                        เครื่องนี้มีไลเซ่นฟรีในบัญชีของคุณแล้ว — กลับไปที่โปรแกรม MacDock ไลเซ่นจะขึ้นเองภายในไม่กี่วินาที
                    </p>
                    @break

                @case('taken')
                    <p class="text-gray-700 dark:text-gray-300">
                        เครื่องนี้รับไลเซ่นไว้กับบัญชีอื่นแล้ว หากเป็นเครื่องของคุณ กรุณาติดต่อฝ่ายบริการลูกค้า
                    </p>
                    @break
            @endswitch
        </div>
    </div>

    {{-- ══════════ ไลเซ่น MacDock ในบัญชีนี้ ══════════ --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-5 sm:px-8 py-5 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <x-bi th="ไลเซ่น MacDock ในบัญชีนี้" en="MacDock licences on this account" />
            </h2>
        </div>

        @if($myLicenses->isEmpty())
            <p class="px-5 sm:px-8 py-6 text-sm text-gray-500 dark:text-gray-400">
                <x-bi th="ยังไม่มีไลเซ่น" en="No licence yet" />
            </p>
        @else
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($myLicenses as $license)
                    <li class="px-5 sm:px-8 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                        <span class="text-gray-900 dark:text-white">
                            {{ $deviceNames[$license->machine_id] ?? 'Windows PC' }}
                            {{-- ไม่แสดงคีย์เต็ม --}}
                            <span class="ml-2 font-mono text-sm text-gray-500 dark:text-gray-400">{{ substr($license->license_key, 0, 9) }}••••{{ substr($license->license_key, -4) }}</span>
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            รับเมื่อ {{ $license->created_at->timezone('Asia/Bangkok')->format('d/m/Y') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <a href="{{ route('products.show', $product->slug) }}"
       class="inline-flex items-center text-sm font-semibold text-sky-600 dark:text-sky-400 hover:text-sky-700 dark:hover:text-sky-300">
        <x-bi th="ไปหน้า MacDock — ดาวน์โหลดโปรแกรม" en="Go to MacDock — download" /> →
    </a>
</div>
@endsection
