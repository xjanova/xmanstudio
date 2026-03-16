@extends($adminLayout ?? 'layouts.admin')

@section('title', 'YouTube Channels')
@section('page-title', 'จัดการช่อง YouTube')

@section('content')
<!-- Header -->
<div class="bg-gradient-to-r from-red-600 to-red-800 rounded-lg shadow-lg p-6 mb-6 text-white">
    <div class="flex flex-col md:flex-row items-center gap-6">
        <div class="w-20 h-20 rounded-full bg-white/20 flex items-center justify-center">
            <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
            </svg>
        </div>
        <div class="text-center md:text-left flex-1">
            <h2 class="text-2xl font-bold">YouTube Channel Manager</h2>
            <p class="text-red-200 text-sm">เชื่อมต่อและจัดการช่อง YouTube หลายช่องได้ในที่เดียว</p>
        </div>
        <a href="{{ route('youtube.redirect') }}" class="px-6 py-3 bg-white text-red-700 font-bold rounded-lg hover:bg-red-50 flex items-center text-sm">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            เชื่อมต่อช่องใหม่
        </a>
    </div>
</div>

<!-- Channels Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($channels as $channel)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <!-- Channel Header -->
            <div class="p-5">
                <div class="flex items-center gap-4 mb-4">
                    @if($channel->channel_thumbnail_url)
                        <img src="{{ $channel->channel_thumbnail_url }}" alt="{{ $channel->name }}" class="w-14 h-14 rounded-full">
                    @else
                        <div class="w-14 h-14 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
                            <svg class="w-7 h-7 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                            </svg>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate">{{ $channel->name }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $channel->youtube_channel_id }}</p>
                    </div>
                    @if($channel->is_default)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">ช่องหลัก</span>
                    @endif
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($channel->subscriber_count) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">ผู้ติดตาม</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 text-center">
                        <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($channel->video_count) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">วิดีโอ</p>
                    </div>
                </div>

                <!-- Status -->
                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-4">
                    <span class="w-2 h-2 rounded-full {{ $channel->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                    {{ $channel->is_active ? 'เชื่อมต่อแล้ว' : 'ไม่ได้เชื่อมต่อ' }}
                    @if($channel->last_synced_at)
                        <span class="ml-auto">ซิงค์ล่าสุด: {{ $channel->last_synced_at->diffForHumans() }}</span>
                    @endif
                </div>

                @if($channel->hasUploadScope())
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 mb-4">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        อัปโหลดได้
                    </span>
                @endif
            </div>

            <!-- Actions -->
            <div class="border-t border-gray-200 dark:border-gray-700 px-5 py-3 flex items-center gap-2">
                <form method="POST" action="{{ route('admin.metal-x.channels.sync', $channel) }}">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-800 text-xs font-medium">ซิงค์</button>
                </form>
                @if(!$channel->is_default)
                    <form method="POST" action="{{ route('admin.metal-x.channels.set-default', $channel) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-800 text-xs font-medium">ตั้งเป็นหลัก</button>
                    </form>
                @endif
                <a href="{{ route('admin.metal-x.channels.edit', $channel) }}" class="px-3 py-1.5 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 text-xs font-medium">แก้ไข</a>
                <form method="POST" action="{{ route('admin.metal-x.channels.destroy', $channel) }}" onsubmit="return confirm('ยืนยันการยกเลิกเชื่อมต่อช่องนี้?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-1.5 bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200 rounded-lg hover:bg-red-200 dark:hover:bg-red-800 text-xs font-medium">ยกเลิก</button>
                </form>
            </div>
        </div>
    @empty
        <div class="col-span-full bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
            </svg>
            <p class="text-lg font-medium text-gray-500 dark:text-gray-400">ยังไม่มีช่อง YouTube ที่เชื่อมต่อ</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">กดปุ่ม "เชื่อมต่อช่องใหม่" เพื่อเริ่มต้น</p>
        </div>
    @endforelse
</div>
@endsection
