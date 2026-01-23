@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Metal-X Analytics')
@section('page-title', 'Metal-X Analytics Dashboard')

@section('content')
@php
    $isPremium = ($adminLayout ?? '') === 'layouts.admin-premium';
@endphp

<!-- Channel Info -->
<div class="{{ $isPremium ? 'bg-gradient-to-r from-red-600/90 to-red-800/90 backdrop-blur-sm border border-red-500/30' : 'bg-gradient-to-r from-red-600 to-red-800' }} rounded-xl shadow-lg p-6 mb-6 text-white">
    <div class="flex flex-col md:flex-row items-center gap-6">
        @if($channelInfo['logo'])
            <img src="{{ Storage::url($channelInfo['logo']) }}" alt="{{ $channelInfo['name'] }}"
                 class="w-24 h-24 rounded-full border-4 border-white/50 shadow-lg">
        @else
            <div class="w-24 h-24 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm">
                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                </svg>
            </div>
        @endif
        <div class="text-center md:text-left">
            <h2 class="text-2xl font-bold">{{ $channelInfo['name'] }}</h2>
            @if($channelInfo['url'])
                <a href="{{ $channelInfo['url'] }}" target="_blank" class="text-red-200 hover:text-white text-sm transition-colors">
                    {{ $channelInfo['url'] }}
                </a>
            @endif
        </div>
        <div class="md:ml-auto flex gap-4">
            @if($isApiConfigured)
                <form action="{{ route('admin.metal-x.analytics.refresh') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg flex items-center transition-all duration-300 hover:shadow-lg">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        รีเฟรช
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.metal-x.analytics.export', ['format' => 'csv']) }}"
               class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg flex items-center transition-all duration-300 hover:shadow-lg">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </a>
        </div>
    </div>
</div>

@if(!$isApiConfigured)
    <div class="{{ $isPremium ? 'bg-amber-500/20 border border-amber-500/30 text-amber-200' : 'bg-yellow-50 border-l-4 border-yellow-400 text-yellow-700' }} p-4 mb-6 rounded-lg">
        <p class="text-sm">
            YouTube API ยังไม่ได้ตั้งค่า - <a href="{{ route('admin.metal-x.settings') }}" class="font-medium underline hover:no-underline">ตั้งค่า API Key</a> เพื่อเปิดใช้งานการ sync อัตโนมัติ
        </p>
    </div>
@endif

<!-- Stats Overview -->
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-6">
    <!-- Total Videos -->
    <div class="{{ $isPremium ? 'bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 hover:border-indigo-500/50' : 'bg-white shadow' }} rounded-xl p-4 transition-all duration-300 hover:shadow-lg">
        <p class="text-sm {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">วิดีโอทั้งหมด</p>
        <p class="text-2xl font-bold {{ $isPremium ? 'text-white' : 'text-gray-900' }}">{{ number_format($videoStats['total']) }}</p>
    </div>
    <!-- Featured Videos -->
    <div class="{{ $isPremium ? 'bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 hover:border-yellow-500/50' : 'bg-white shadow' }} rounded-xl p-4 transition-all duration-300 hover:shadow-lg">
        <p class="text-sm {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">วิดีโอแนะนำ</p>
        <p class="text-2xl font-bold {{ $isPremium ? 'text-yellow-400' : 'text-yellow-600' }}">{{ number_format($videoStats['featured']) }}</p>
    </div>
    <!-- Total Views -->
    <div class="{{ $isPremium ? 'bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 hover:border-blue-500/50' : 'bg-white shadow' }} rounded-xl p-4 transition-all duration-300 hover:shadow-lg">
        <p class="text-sm {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">ยอดวิวรวม</p>
        <p class="text-2xl font-bold {{ $isPremium ? 'text-blue-400' : 'text-blue-600' }}">{{ number_format($videoStats['total_views']) }}</p>
    </div>
    <!-- Total Likes -->
    <div class="{{ $isPremium ? 'bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 hover:border-red-500/50' : 'bg-white shadow' }} rounded-xl p-4 transition-all duration-300 hover:shadow-lg">
        <p class="text-sm {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">ยอดไลค์รวม</p>
        <p class="text-2xl font-bold {{ $isPremium ? 'text-red-400' : 'text-red-600' }}">{{ number_format($videoStats['total_likes']) }}</p>
    </div>
    <!-- Total Comments -->
    <div class="{{ $isPremium ? 'bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 hover:border-green-500/50' : 'bg-white shadow' }} rounded-xl p-4 transition-all duration-300 hover:shadow-lg">
        <p class="text-sm {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">คอมเมนต์รวม</p>
        <p class="text-2xl font-bold {{ $isPremium ? 'text-green-400' : 'text-green-600' }}">{{ number_format($videoStats['total_comments']) }}</p>
    </div>
    <!-- Playlists -->
    <div class="{{ $isPremium ? 'bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 hover:border-purple-500/50' : 'bg-white shadow' }} rounded-xl p-4 transition-all duration-300 hover:shadow-lg">
        <p class="text-sm {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">เพลย์ลิสต์</p>
        <p class="text-2xl font-bold {{ $isPremium ? 'text-purple-400' : 'text-purple-600' }}">{{ number_format($playlistStats['total']) }}</p>
    </div>
    <!-- Team Members -->
    <div class="{{ $isPremium ? 'bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 hover:border-orange-500/50' : 'bg-white shadow' }} rounded-xl p-4 transition-all duration-300 hover:shadow-lg">
        <p class="text-sm {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">สมาชิกทีม</p>
        <p class="text-2xl font-bold {{ $isPremium ? 'text-orange-400' : 'text-orange-600' }}">{{ number_format($teamStats['active']) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Top Videos by Views -->
    <div class="{{ $isPremium ? 'bg-slate-800/50 backdrop-blur-sm border border-slate-700/50' : 'bg-white shadow' }} rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b {{ $isPremium ? 'border-slate-700/50' : 'border-gray-200' }}">
            <h3 class="text-lg font-semibold {{ $isPremium ? 'text-white' : 'text-gray-900' }}">Top 10 วิดีโอยอดนิยม (ยอดวิว)</h3>
        </div>
        <div class="divide-y {{ $isPremium ? 'divide-slate-700/50' : 'divide-gray-100' }}">
            @forelse($topVideosByViews as $index => $video)
                <div class="flex items-center px-6 py-3 {{ $isPremium ? 'hover:bg-slate-700/30' : 'hover:bg-gray-50' }} transition-colors">
                    <span class="text-lg font-bold {{ $isPremium ? 'text-slate-500' : 'text-gray-400' }} w-8">{{ $index + 1 }}</span>
                    <img src="{{ $video->thumbnail_url }}" alt="" class="w-16 h-10 object-cover rounded mx-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium {{ $isPremium ? 'text-white' : 'text-gray-900' }} truncate">{{ $video->title }}</p>
                        <p class="text-xs {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">{{ $video->published_at?->format('d M Y') }}</p>
                    </div>
                    <span class="text-sm font-semibold {{ $isPremium ? 'text-blue-400' : 'text-blue-600' }}">{{ $video->formatted_view_count }}</span>
                </div>
            @empty
                <div class="px-6 py-8 text-center {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">ไม่มีข้อมูล</div>
            @endforelse
        </div>
    </div>

    <!-- Top Videos by Likes -->
    <div class="{{ $isPremium ? 'bg-slate-800/50 backdrop-blur-sm border border-slate-700/50' : 'bg-white shadow' }} rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b {{ $isPremium ? 'border-slate-700/50' : 'border-gray-200' }}">
            <h3 class="text-lg font-semibold {{ $isPremium ? 'text-white' : 'text-gray-900' }}">Top 10 วิดีโอยอดนิยม (ยอดไลค์)</h3>
        </div>
        <div class="divide-y {{ $isPremium ? 'divide-slate-700/50' : 'divide-gray-100' }}">
            @forelse($topVideosByLikes as $index => $video)
                <div class="flex items-center px-6 py-3 {{ $isPremium ? 'hover:bg-slate-700/30' : 'hover:bg-gray-50' }} transition-colors">
                    <span class="text-lg font-bold {{ $isPremium ? 'text-slate-500' : 'text-gray-400' }} w-8">{{ $index + 1 }}</span>
                    <img src="{{ $video->thumbnail_url }}" alt="" class="w-16 h-10 object-cover rounded mx-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium {{ $isPremium ? 'text-white' : 'text-gray-900' }} truncate">{{ $video->title }}</p>
                        <p class="text-xs {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">{{ $video->published_at?->format('d M Y') }}</p>
                    </div>
                    <span class="text-sm font-semibold {{ $isPremium ? 'text-red-400' : 'text-red-600' }}">{{ $video->formatted_like_count }}</span>
                </div>
            @empty
                <div class="px-6 py-8 text-center {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">ไม่มีข้อมูล</div>
            @endforelse
        </div>
    </div>
</div>

<!-- Recent Videos -->
<div class="{{ $isPremium ? 'bg-slate-800/50 backdrop-blur-sm border border-slate-700/50' : 'bg-white shadow' }} rounded-xl mb-6 overflow-hidden">
    <div class="px-6 py-4 border-b {{ $isPremium ? 'border-slate-700/50' : 'border-gray-200' }} flex justify-between items-center">
        <h3 class="text-lg font-semibold {{ $isPremium ? 'text-white' : 'text-gray-900' }}">วิดีโอล่าสุด</h3>
        <a href="{{ route('admin.metal-x.videos.index') }}" class="text-sm {{ $isPremium ? 'text-indigo-400 hover:text-indigo-300' : 'text-primary-600 hover:underline' }} transition-colors">ดูทั้งหมด</a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 p-6">
        @forelse($recentVideos as $video)
            <div class="group">
                <div class="relative aspect-video {{ $isPremium ? 'bg-slate-700/50' : 'bg-gray-100' }} rounded-lg overflow-hidden mb-2">
                    <img src="{{ $video->best_thumbnail }}" alt="{{ $video->title }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    <div class="absolute bottom-1 right-1 bg-black/75 text-white text-xs px-1.5 py-0.5 rounded">
                        {{ $video->formatted_duration }}
                    </div>
                </div>
                <p class="text-sm font-medium {{ $isPremium ? 'text-white' : 'text-gray-900' }} line-clamp-2">{{ $video->title }}</p>
                <p class="text-xs {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">{{ $video->formatted_view_count }} views</p>
            </div>
        @empty
            <div class="col-span-5 text-center py-8 {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }}">ไม่มีวิดีโอ</div>
        @endforelse
    </div>
</div>

<!-- Videos by Month Chart -->
@if($videosByMonth->count() > 0)
    <div class="{{ $isPremium ? 'bg-slate-800/50 backdrop-blur-sm border border-slate-700/50' : 'bg-white shadow' }} rounded-xl p-6 overflow-hidden">
        <h3 class="text-lg font-semibold {{ $isPremium ? 'text-white' : 'text-gray-900' }} mb-4">วิดีโอและยอดวิวตามเดือน (12 เดือนล่าสุด)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="text-left text-xs {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }} uppercase">
                        <th class="pb-3">เดือน</th>
                        <th class="pb-3">จำนวนวิดีโอ</th>
                        <th class="pb-3">ยอดวิวรวม</th>
                    </tr>
                </thead>
                <tbody class="divide-y {{ $isPremium ? 'divide-slate-700/50' : 'divide-gray-100' }}">
                    @foreach($videosByMonth as $data)
                        <tr class="{{ $isPremium ? 'hover:bg-slate-700/30' : 'hover:bg-gray-50' }} transition-colors">
                            <td class="py-3 font-medium {{ $isPremium ? 'text-white' : 'text-gray-900' }}">{{ $data->month }}</td>
                            <td class="py-3 {{ $isPremium ? 'text-slate-300' : 'text-gray-700' }}">{{ number_format($data->count) }}</td>
                            <td class="py-3 {{ $isPremium ? 'text-slate-300' : 'text-gray-700' }}">{{ number_format($data->views) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<!-- Last Sync Info -->
<div class="mt-6 text-sm {{ $isPremium ? 'text-slate-400' : 'text-gray-500' }} text-center">
    @if($lastSync)
        Sync ล่าสุด: {{ $lastSync->format('d M Y H:i') }}
    @else
        ยังไม่เคย sync ข้อมูล
    @endif
</div>
@endsection
