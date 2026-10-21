@extends($customerLayout ?? 'layouts.customer')

@section('title', 'ผูกเครื่อง GigGok กับบัญชี')
@section('page-title')<x-bi th="ผูกเครื่อง GigGok กับบัญชี" en="Link GigGok to your account" />@endsection
@section('page-description')<x-bi th="ให้ชุดตัวมายด์ที่ซื้อบนเว็บขึ้นในร้านชุดของแอป" en="So the packs you buy on the web show up in the app" />@endsection

@section('content')
{{-- ข้อความสำเร็จ/ผิดพลาดจาก session layout ของหน้าบัญชีแสดงให้เองแล้ว — หน้านี้แสดงแค่ error ของช่องกรอก --}}
<div class="max-w-3xl space-y-6">

    {{-- ══════════ FORM ══════════ --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-5 sm:px-8 py-5 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <x-bi th="ใส่ License Key ของเครื่อง" en="Enter your phone's licence key" />
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                เปิดแอป GigGok → ตั้งค่า → License Key แล้วคัดลอกมาวางที่นี่ ผูกแล้วชุดที่บัญชีนี้ซื้อจะขึ้นในเครื่องนั้นเมื่อเปิดร้านชุดครั้งถัดไป
                ผูกได้หลายเครื่องต่อหนึ่งบัญชี
            </p>
        </div>

        <form method="POST" action="{{ route('giggok.link.store') }}" class="p-5 sm:p-8 space-y-4"
              x-data="{ sending: false }" @submit="sending = true">
            @csrf

            <div>
                <label for="license_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    License Key
                </label>
                <input type="text" id="license_key" name="license_key" value="{{ old('license_key') }}" required maxlength="100"
                       autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="FREE-XXXXXXXXXXXXXXXXXXXX"
                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white font-mono tracking-wide focus:border-fuchsia-500 focus:ring-fuchsia-500 @error('license_key') border-red-400 @enderror">
                @error('license_key')<p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <button type="submit" x-bind:disabled="sending"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-fuchsia-600 to-pink-600 px-7 py-3 text-white font-semibold shadow-lg shadow-fuchsia-500/30 hover:from-fuchsia-700 hover:to-pink-700 transition-all disabled:opacity-60 disabled:cursor-not-allowed">
                <x-bi th="ผูกเครื่องกับบัญชี" en="Link this phone" />
            </button>
        </form>
    </div>

    {{-- ══════════ เครื่องที่ผูกไว้แล้ว ══════════ --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-5 sm:px-8 py-5 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <x-bi th="เครื่องที่ผูกกับบัญชีนี้" en="Phones linked to this account" />
            </h2>
        </div>

        @if($linked->isEmpty())
            <p class="px-5 sm:px-8 py-6 text-sm text-gray-500 dark:text-gray-400">
                <x-bi th="ยังไม่มีเครื่องที่ผูกไว้" en="No phone linked yet" />
            </p>
        @else
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($linked as $license)
                    <li class="px-5 sm:px-8 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                        {{-- ไม่แสดงคีย์เต็ม — คีย์คือสิ่งที่แอปใช้ยืนยันตัวกับร้านชุด --}}
                        <span class="font-mono text-gray-900 dark:text-white">{{ substr($license->license_key, 0, 9) }}••••{{ substr($license->license_key, -4) }}</span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            @if($license->last_validated_at)
                                ใช้ล่าสุด {{ $license->last_validated_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}
                            @else
                                ลงทะเบียนเมื่อ {{ $license->created_at->timezone('Asia/Bangkok')->format('d/m/Y') }}
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <a href="{{ route('products.show', $product->slug) }}"
       class="inline-flex items-center text-sm font-semibold text-fuchsia-600 dark:text-fuchsia-400 hover:text-fuchsia-700 dark:hover:text-fuchsia-300">
        <x-bi th="ไปหน้า GigGok — ดาวน์โหลดแอป" en="Go to GigGok — download the app" /> →
    </a>
</div>
@endsection
