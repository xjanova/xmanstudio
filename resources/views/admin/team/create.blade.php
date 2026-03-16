@extends($adminLayout ?? 'layouts.admin')

@section('title', 'เพิ่มสมาชิกทีม')
@section('page-title', 'เพิ่มสมาชิกทีม')

@push('styles')
<style>
    .animate-blob { animation: blob 7s infinite; }
    .animation-delay-2000 { animation-delay: 2s; }
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
</style>
@endpush

@section('content')
<!-- Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 p-8 shadow-2xl mb-8">
    <div class="absolute top-0 left-0 w-72 h-72 bg-indigo-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="relative">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.team.index') }}" class="p-2 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition-all">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h2 class="text-3xl font-bold text-white mb-2">เพิ่มสมาชิกทีม</h2>
                <p class="text-indigo-100">เพิ่มผู้บริหารหรือสมาชิกทีมใหม่</p>
            </div>
        </div>
    </div>
</div>

<div class="max-w-4xl mx-auto">
    <form action="{{ route('admin.team.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @include('admin.team._form')

        <!-- Actions -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('admin.team.index') }}" class="px-6 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium">
                ยกเลิก
            </a>
            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all font-medium shadow-lg">
                บันทึก
            </button>
        </div>
    </form>
</div>
@endsection
