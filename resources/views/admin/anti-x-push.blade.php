@extends($adminLayout ?? 'layouts.admin')

@section('page-title', 'แจ้งเตือนแอป Anti X มือถือ')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แจ้งเตือนแอป Anti X มือถือ</h1>
    <p class="text-gray-500 dark:text-gray-400 mt-1">เครื่อง Anti X ส่งคำขอแจ้งเตือนผ่านเว็บนี้ — ข้อความบนมือถือเป็นข้อความกลางๆ ไม่มี IP หรือชื่อเครื่อง</p>
</div>

@if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400">{{ session('error') }}</div>
@endif

<div class="rounded-2xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden max-w-3xl">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-cyan-600 to-blue-700">
        <h2 class="text-lg font-semibold text-white">Firebase Cloud Messaging</h2>
    </div>
    <div class="p-6">
        <div class="mb-6 p-4 rounded-xl {{ $serviceAccount ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800' }}">
            @if($serviceAccount)
                <p class="font-semibold text-emerald-700 dark:text-emerald-400">พร้อมส่งแจ้งเตือน</p>
                <p class="text-sm text-emerald-600 dark:text-emerald-500 font-mono">{{ $serviceAccount }}</p>
            @else
                <p class="font-semibold text-amber-700 dark:text-amber-400">ยังส่งแจ้งเตือนไม่ได้</p>
                <p class="text-sm text-amber-600 dark:text-amber-500">อัปโหลด Service Account JSON ของโปรเจกต์ Firebase "anti-x-mobile"</p>
            @endif
        </div>

        <form action="{{ route('admin.anti-x-push.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Firebase Project ID</label>
                <input type="text" name="project_id" value="{{ old('project_id', $projectId) }}"
                    class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-mono text-gray-900 dark:text-white">
            </div>
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Service Account JSON</label>
                <input type="file" name="credentials" accept=".json,application/json"
                    class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-900 dark:text-white">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Firebase Console → anti-x-mobile → Project settings → Service accounts → Generate new private key · เก็บไว้ในเซิร์ฟเวอร์ ไม่แสดงอีก</p>
            </div>
            <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl">บันทึก</button>
        </form>
    </div>
</div>
@endsection
