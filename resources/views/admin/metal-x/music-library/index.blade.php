@extends($adminLayout ?? 'layouts.admin')

@section('title', 'คลังเพลง Music Library - Metal-X')
@section('page-title', 'คลังเพลง Music Library')

@push('styles')
<style>
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
</style>
@endpush

@section('content')
<!-- Premium Header Banner -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-pink-600 via-rose-700 to-rose-800 p-8 shadow-2xl mb-8">
    <div class="absolute top-0 left-0 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 right-0 w-72 h-72 bg-rose-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-fuchsia-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>
    <div class="relative">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="text-3xl font-bold text-white mb-2">คลังเพลง Music Library</h2>
                <p class="text-pink-200">จัดการเพลงประกอบสำหรับวิดีโอ Metal-X</p>
            </div>
            <a href="{{ route('admin.metal-x.pipeline.index') }}"
               class="px-6 py-3 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all flex items-center font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับ Pipeline
            </a>
        </div>
    </div>
</div>

<!-- Success Message -->
@if(session('success'))
<div class="mb-6 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-xl p-4">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-green-700 dark:text-green-300">{{ session('success') }}</span>
    </div>
</div>
@endif

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 text-white shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">เพลงทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-xl bg-gradient-to-br from-green-500 to-emerald-500 text-white shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">เปิดใช้งาน</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['active']) }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-shadow">
        <div class="flex items-center">
            <div class="p-3 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">ระยะเวลารวม</p>
                <p class="text-2xl font-bold text-violet-600 dark:text-violet-400">
                    @php
                        $hours = floor($stats['total_duration'] / 3600);
                        $minutes = floor(($stats['total_duration'] % 3600) / 60);
                        $seconds = $stats['total_duration'] % 60;
                    @endphp
                    @if($hours > 0)
                        {{ $hours }}:{{ str_pad($minutes, 2, '0', STR_PAD_LEFT) }}:{{ str_pad($seconds, 2, '0', STR_PAD_LEFT) }}
                    @else
                        {{ $minutes }}:{{ str_pad($seconds, 2, '0', STR_PAD_LEFT) }}
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Upload Form -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
        </svg>
        อัปโหลดเพลงใหม่
    </h3>
    <form action="{{ route('admin.metal-x.music-library.upload') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลือกไฟล์เพลง</label>
                <input type="file" name="files[]" multiple accept="audio/mpeg,audio/wav,audio/ogg,audio/mp4"
                       class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100 dark:file:bg-pink-900/30 dark:file:text-pink-300" required>
                <p class="mt-1 text-xs text-gray-400">MP3, WAV, OGG, M4A (สูงสุด 50MB/ไฟล์)</p>
                @error('files') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                @error('files.*') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สไตล์เพลง</label>
                <select name="style" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-pink-500 focus:border-pink-500" required>
                    @foreach($styles as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('style') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แหล่งที่มา</label>
                <select name="source" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-pink-500 focus:border-pink-500" required>
                    <option value="suno">Suno AI</option>
                    <option value="custom">สร้างเอง</option>
                </select>
                @error('source') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แท็ก (คั่นด้วย ,)</label>
                <input type="text" name="tags" placeholder="cyberpunk, heavy, fast tempo"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-pink-500 focus:border-pink-500">
                @error('tags') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white rounded-lg hover:from-pink-700 hover:to-rose-700 transition-all font-medium text-sm shadow-lg">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    อัปโหลด
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Filter -->
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-4 mb-6">
    <form method="GET" action="{{ route('admin.metal-x.music-library.index') }}" class="flex flex-wrap items-center gap-3">
        <div>
            <select name="style" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-pink-500 focus:border-pink-500">
                <option value="">สไตล์ทั้งหมด</option>
                @foreach($styles as $value => $label)
                    <option value="{{ $value }}" {{ request('style') == $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 text-sm font-medium transition-colors">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            กรอง
        </button>
        @if(request()->hasAny(['style']))
            <a href="{{ route('admin.metal-x.music-library.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 text-sm font-medium transition-colors">
                ล้างตัวกรอง
            </a>
        @endif
    </form>
</div>

<!-- Tracks Table -->
@if($tracks->count() > 0)
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">เพลง</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">สไตล์</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ระยะเวลา</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">แหล่งที่มา</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ใช้แล้ว</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">แท็ก</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ฟังเพลง</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($tracks as $track)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors {{ !$track->is_active ? 'opacity-60' : '' }}">
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <div class="p-2 rounded-lg bg-gradient-to-br from-pink-500 to-rose-600 text-white mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $track->title }}</p>
                                <p class="text-xs text-gray-400">{{ $track->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $styleColors = [
                                'metal' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
                                'rock' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300',
                                'electronic' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                                'ambient' => 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300',
                                'synthwave' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
                                'cinematic' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
                                'lofi' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
                                'other' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            ];
                            $colorClass = $styleColors[$track->style] ?? $styleColors['other'];
                        @endphp
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $colorClass }}">
                            {{ $styles[$track->style] ?? $track->style }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm text-gray-700 dark:text-gray-300 font-mono">{{ $track->duration_human }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($track->source === 'suno')
                            <span class="px-2 py-1 text-xs font-medium bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-300 rounded-full">Suno AI</span>
                        @else
                            <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-full">Custom</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $track->usage_count ?? 0 }} ครั้ง</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($track->tags && count($track->tags) > 0)
                            <div class="flex flex-wrap gap-1">
                                @foreach(array_slice($track->tags, 0, 3) as $tag)
                                    <span class="px-1.5 py-0.5 text-xs bg-pink-50 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300 rounded">{{ $tag }}</span>
                                @endforeach
                                @if(count($track->tags) > 3)
                                    <span class="text-xs text-gray-400">+{{ count($track->tags) - 3 }}</span>
                                @endif
                            </div>
                        @else
                            <span class="text-xs text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <audio controls preload="none" class="h-8 w-48">
                            <source src="{{ Storage::url($track->file_path) }}">
                        </audio>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <form action="{{ route('admin.metal-x.music-library.destroy', $track) }}" method="POST" class="inline"
                              onsubmit="return confirm('ยืนยันลบเพลง &quot;{{ $track->title }}&quot;?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20" title="ลบ">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-12 text-center">
    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
    </svg>
    <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มีเพลงในคลัง</p>
    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">อัปโหลดเพลงเพื่อใช้เป็นเพลงประกอบวิดีโอ</p>
</div>
@endif

<!-- Pagination -->
<div class="mt-6">
    {{ $tracks->withQueryString()->links() }}
</div>
@endsection
