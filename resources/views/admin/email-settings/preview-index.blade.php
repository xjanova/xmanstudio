@extends($adminLayout ?? 'layouts.admin')

@section('title', 'ตัวอย่างเทมเพลตอีเมล')
@section('page-title', 'ตัวอย่างเทมเพลตอีเมล')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 p-8 shadow-2xl">
        <div class="relative z-10">
            <h1 class="text-2xl font-bold text-white">ตัวอย่างเทมเพลตอีเมล</h1>
            <p class="text-indigo-100 mt-1">เลือกเทมเพลตเพื่อดูตัวอย่างหน้าตาอีเมลที่ระบบจะส่ง</p>
        </div>
    </div>

    <!-- Template Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($templates as $tpl)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-shadow">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ $tpl['name'] }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ $tpl['description'] }}</p>
                <div class="flex gap-2">
                    <a href="{{ route('admin.email-settings.preview', $tpl['slug']) }}"
                       target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        ดูตัวอย่าง
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Back -->
    <div>
        <a href="{{ route('admin.email-settings.index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับไปตั้งค่าอีเมล
        </a>
    </div>
</div>
@endsection
