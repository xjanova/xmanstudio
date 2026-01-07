@extends('layouts.app')

@section('title', 'Metal-X Project - Music Channel')

@section('content')
<!-- Hero Section with Channel Banner -->
<div class="relative bg-gradient-to-br from-gray-900 via-purple-900 to-black py-20">
    @if($channelSettings['channel_banner'])
        <div class="absolute inset-0 opacity-30">
            <img src="{{ $channelSettings['channel_banner'] }}" alt="Channel Banner" class="w-full h-full object-cover">
        </div>
    @endif
    <div class="absolute inset-0 bg-gradient-to-b from-transparent via-black/50 to-black"></div>

    <div class="relative container mx-auto px-4 py-16">
        <div class="text-center max-w-4xl mx-auto">
            <!-- Channel Logo -->
            @if($channelSettings['channel_logo'])
                <div class="mb-8 flex justify-center">
                    <div class="w-32 h-32 rounded-full border-4 border-white/20 overflow-hidden shadow-2xl">
                        <img src="{{ $channelSettings['channel_logo'] }}" alt="{{ $channelSettings['channel_name'] }}" class="w-full h-full object-cover">
                    </div>
                </div>
            @endif

            <!-- Channel Name -->
            <h1 class="text-5xl md:text-7xl font-black text-white mb-6 tracking-tight">
                <span class="bg-gradient-to-r from-red-500 via-purple-500 to-pink-500 bg-clip-text text-transparent">
                    {{ $channelSettings['channel_name'] }}
                </span>
            </h1>

            <!-- Channel Description -->
            @if($channelSettings['channel_description'])
                <p class="text-xl md:text-2xl text-gray-300 mb-8 leading-relaxed">
                    {{ $channelSettings['channel_description'] }}
                </p>
            @endif

            <!-- Channel Actions -->
            <div class="flex flex-wrap justify-center gap-4">
                @if($channelSettings['channel_url'])
                    <a href="{{ $channelSettings['channel_url'] }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center px-8 py-4 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-red-500/50">
                        <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        Subscribe on YouTube
                    </a>
                @endif
                <a href="#team" class="inline-flex items-center px-8 py-4 border-2 border-white/30 text-white font-semibold rounded-xl backdrop-blur-sm transition-all duration-300 hover:bg-white/10 hover:border-white/60 hover:scale-105">
                    Meet the Team
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Latest Videos Section -->
<section class="py-20 bg-gray-50 dark:bg-gray-900" id="videos">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-black text-gray-900 dark:text-white mb-4">
                Latest <span class="bg-gradient-to-r from-red-500 to-pink-500 bg-clip-text text-transparent">Music Videos</span>
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400">
                Check out our latest releases on YouTube
            </p>
        </div>

        <!-- YouTube Videos Grid -->
        <div id="youtube-videos" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Videos will be loaded via JavaScript if API key is configured -->
            @if($channelSettings['youtube_api_key'])
                <div class="col-span-full text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-red-600"></div>
                    <p class="mt-4 text-gray-600 dark:text-gray-400">Loading videos...</p>
                </div>
            @else
                <div class="col-span-full text-center py-12">
                    <div class="max-w-md mx-auto">
                        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">YouTube API Not Configured</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Please configure YouTube API key in admin settings to display videos automatically.
                        </p>
                        <a href="{{ $channelSettings['channel_url'] }}" target="_blank" class="inline-flex items-center text-red-600 hover:text-red-700 font-semibold">
                            Visit Channel
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="py-20 bg-white dark:bg-gray-800" id="team">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-black text-gray-900 dark:text-white mb-4">
                Meet Our <span class="bg-gradient-to-r from-purple-500 to-pink-500 bg-clip-text text-transparent">Talented Team</span>
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-400">
                The creative minds behind Metal-X Project
            </p>
        </div>

        @if($teamMembers->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                @foreach($teamMembers as $member)
                    <div class="group relative bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
                        <!-- Member Image -->
                        <div class="aspect-square overflow-hidden bg-gradient-to-br from-purple-500 to-pink-500">
                            @if($member->image)
                                <img src="{{ $member->image }}"
                                     alt="{{ $member->name }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-24 h-24 text-white/50" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <!-- Member Info -->
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                                {{ app()->getLocale() === 'th' && $member->name_th ? $member->name_th : $member->name }}
                            </h3>
                            <p class="text-purple-600 dark:text-purple-400 font-semibold mb-3">
                                {{ app()->getLocale() === 'th' && $member->role_th ? $member->role_th : $member->role }}
                            </p>

                            @if($member->bio || $member->bio_th)
                                <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                                    {{ app()->getLocale() === 'th' && $member->bio_th ? $member->bio_th : $member->bio }}
                                </p>
                            @endif

                            <!-- Social Links -->
                            <div class="flex gap-3">
                                @if($member->youtube_url)
                                    <a href="{{ $member->youtube_url }}" target="_blank" rel="noopener noreferrer"
                                       class="w-10 h-10 flex items-center justify-center bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                        </svg>
                                    </a>
                                @endif
                                @if($member->facebook_url)
                                    <a href="{{ $member->facebook_url }}" target="_blank" rel="noopener noreferrer"
                                       class="w-10 h-10 flex items-center justify-center bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                        </svg>
                                    </a>
                                @endif
                                @if($member->instagram_url)
                                    <a href="{{ $member->instagram_url }}" target="_blank" rel="noopener noreferrer"
                                       class="w-10 h-10 flex items-center justify-center bg-gradient-to-br from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition-colors">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                                        </svg>
                                    </a>
                                @endif
                                @if($member->twitter_url)
                                    <a href="{{ $member->twitter_url }}" target="_blank" rel="noopener noreferrer"
                                       class="w-10 h-10 flex items-center justify-center bg-gray-900 text-white rounded-lg hover:bg-black transition-colors">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                        </svg>
                                    </a>
                                @endif
                                @if($member->tiktok_url)
                                    <a href="{{ $member->tiktok_url }}" target="_blank" rel="noopener noreferrer"
                                       class="w-10 h-10 flex items-center justify-center bg-black text-white rounded-lg hover:bg-gray-800 transition-colors">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="max-w-md mx-auto">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No Team Members Yet</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Team members will be displayed here once added by the administrator.
                    </p>
                </div>
            </div>
        @endif
    </div>
</section>

<!-- Call to Action -->
<section class="py-20 bg-gradient-to-br from-purple-900 via-pink-900 to-red-900 relative overflow-hidden">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48cGF0aCBkPSJNMzYgMzRjMC0yLjIxIDEuNzktNCA0LTRzNCAxLjc5IDQgNC0xLjc5IDQtNCA0LTQtMS43OS00LTR6bS0yMCAwYzAtMi4yMSAxLjc5LTQgNC00czQgMS43OSA0IDQtMS43OSA0LTQgNC00LTEuNzktNC00eiIvPjwvZz48L2c+PC9zdmc+')]"></div>
    </div>
    <div class="relative container mx-auto px-4 text-center">
        <h2 class="text-4xl md:text-5xl font-black text-white mb-6">
            Ready to Experience Our Music?
        </h2>
        <p class="text-xl text-gray-200 mb-8 max-w-2xl mx-auto">
            Subscribe to our YouTube channel and never miss a new release
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ $channelSettings['channel_url'] }}" target="_blank"
               class="inline-flex items-center px-8 py-4 bg-white text-gray-900 font-bold rounded-xl hover:bg-gray-100 transition-all duration-300 hover:scale-105 shadow-lg">
                <svg class="w-6 h-6 mr-2 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
                Visit Our Channel
            </a>
            <a href="/" class="inline-flex items-center px-8 py-4 border-2 border-white text-white font-semibold rounded-xl backdrop-blur-sm transition-all duration-300 hover:bg-white/10 hover:scale-105">
                Back to Main Site
            </a>
        </div>
    </div>
</section>

@if($channelSettings['youtube_api_key'])
<!-- YouTube API Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiKey = '{{ $channelSettings['youtube_api_key'] }}';
    const channelUrl = '{{ $channelSettings['channel_url'] }}';

    // Extract channel ID or handle from URL
    const channelId = extractChannelInfo(channelUrl);

    if (channelId) {
        loadYouTubeVideos(apiKey, channelId);
    }
});

function extractChannelInfo(url) {
    // Extract @handle or channel ID from URL
    const match = url.match(/@([^\/]+)|channel\/([^\/]+)/);
    return match ? (match[1] || match[2]) : null;
}

function loadYouTubeVideos(apiKey, channelInfo) {
    // First, get the channel ID if we have a handle
    let endpoint;
    if (channelInfo.startsWith('@')) {
        // Use search to find channel by handle
        endpoint = `https://www.googleapis.com/youtube/v3/search?part=snippet&q=${channelInfo}&type=channel&key=${apiKey}`;
    } else {
        // Direct channel ID
        loadVideosFromChannel(apiKey, channelInfo);
        return;
    }

    fetch(endpoint)
        .then(response => response.json())
        .then(data => {
            if (data.items && data.items.length > 0) {
                const channelId = data.items[0].snippet.channelId;
                loadVideosFromChannel(apiKey, channelId);
            }
        })
        .catch(error => {
            console.error('Error loading channel:', error);
            showError();
        });
}

function loadVideosFromChannel(apiKey, channelId) {
    const endpoint = `https://www.googleapis.com/youtube/v3/search?key=${apiKey}&channelId=${channelId}&part=snippet,id&order=date&maxResults=6&type=video`;

    fetch(endpoint)
        .then(response => response.json())
        .then(data => {
            if (data.items && data.items.length > 0) {
                displayVideos(data.items);
            } else {
                showError('No videos found');
            }
        })
        .catch(error => {
            console.error('Error loading videos:', error);
            showError();
        });
}

function displayVideos(videos) {
    const container = document.getElementById('youtube-videos');
    container.innerHTML = videos.map(video => `
        <div class="group relative bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
            <a href="https://www.youtube.com/watch?v=${video.id.videoId}" target="_blank" rel="noopener noreferrer">
                <div class="relative aspect-video overflow-hidden">
                    <img src="${video.snippet.thumbnails.high.url}"
                         alt="${video.snippet.title}"
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                        <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                        ${video.snippet.title}
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2">
                        ${video.snippet.description || 'No description available'}
                    </p>
                    <p class="text-gray-500 dark:text-gray-500 text-xs mt-2">
                        ${new Date(video.snippet.publishedAt).toLocaleDateString()}
                    </p>
                </div>
            </a>
        </div>
    `).join('');
}

function showError(message = 'Unable to load videos') {
    const container = document.getElementById('youtube-videos');
    container.innerHTML = `
        <div class="col-span-full text-center py-12">
            <div class="max-w-md mx-auto">
                <svg class="w-16 h-16 mx-auto text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Error Loading Videos</h3>
                <p class="text-gray-600 dark:text-gray-400">${message}</p>
            </div>
        </div>
    `;
}
</script>
@endif
@endsection
