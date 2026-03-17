@extends($publicLayout ?? 'layouts.app')

@section('title', $channelSettings['channel_name'] . ' - Music Channel')

@push('styles')
<style>
    [x-cloak] { display: none !important; }

    /* Premium animations */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(60px); }
        to { opacity: 1; transform: translateX(0); }
    }
    @keyframes pulse-glow {
        0%, 100% { box-shadow: 0 0 20px rgba(168, 85, 247, 0.4); }
        50% { box-shadow: 0 0 40px rgba(168, 85, 247, 0.8), 0 0 60px rgba(236, 72, 153, 0.3); }
    }
    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }
    .animate-fade-in-up { animation: fadeInUp 0.8s ease-out forwards; }
    .animate-fade-in { animation: fadeIn 1s ease-out forwards; }
    .animate-slide-in { animation: slideIn 0.6s ease-out forwards; }
    .animate-pulse-glow { animation: pulse-glow 3s ease-in-out infinite; }
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }
    .delay-500 { animation-delay: 0.5s; }

    /* Glass morphism */
    .glass {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .glass-dark {
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(30px);
        -webkit-backdrop-filter: blur(30px);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Video player modal */
    .video-modal-backdrop {
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(10px);
    }

    /* Gradient text */
    .text-gradient-gold {
        background: linear-gradient(135deg, #f6d365 0%, #fda085 50%, #f6d365 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .text-gradient-premium {
        background: linear-gradient(135deg, #a855f7 0%, #ec4899 50%, #f43f5e 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Noise overlay texture */
    .noise-overlay::before {
        content: '';
        position: absolute;
        inset: 0;
        opacity: 0.03;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        pointer-events: none;
    }

    /* Team card premium hover */
    .team-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        padding: 1px;
        background: linear-gradient(135deg, rgba(168,85,247,0), rgba(168,85,247,0.5), rgba(236,72,153,0.5), rgba(168,85,247,0));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        opacity: 0;
        transition: opacity 0.5s;
    }
    .team-card:hover::before { opacity: 1; }

    /* =============================== */
    /* SLOT-MACHINE CAROUSEL           */
    /* =============================== */
    .carousel-viewport {
        position: relative;
        height: 420px;
        perspective: 1200px;
        overflow: hidden;
        cursor: grab;
        touch-action: pan-y;
        -webkit-user-select: none;
        user-select: none;
    }
    .carousel-viewport:active { cursor: grabbing; }

    .carousel-card {
        position: absolute;
        left: 50%;
        top: 50%;
        width: 340px;
        max-width: 85vw;
        transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        will-change: transform, opacity;
    }

    .carousel-card .card-inner {
        border-radius: 1.25rem;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.08);
        transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        box-shadow: 0 10px 40px rgba(0,0,0,0.5);
    }

    .carousel-card[data-pos="0"] .card-inner {
        border-color: rgba(168, 85, 247, 0.5);
        box-shadow: 0 0 40px rgba(168, 85, 247, 0.3), 0 20px 60px rgba(0,0,0,0.6);
    }

    /* Dot indicators */
    .carousel-dots {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
    }
    .carousel-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: rgba(255,255,255,0.2);
        transition: all 0.4s;
        cursor: pointer;
    }
    .carousel-dot.active {
        background: #a855f7;
        box-shadow: 0 0 10px rgba(168, 85, 247, 0.6);
        width: 24px;
        border-radius: 4px;
    }

    /* Partner channel card */
    .partner-card {
        position: relative;
        border-radius: 1.25rem;
        overflow: hidden;
        transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
    }
    .partner-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: inherit;
        padding: 1px;
        background: linear-gradient(135deg, rgba(59,130,246,0), rgba(59,130,246,0.5), rgba(168,85,247,0.5), rgba(59,130,246,0));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        opacity: 0;
        transition: opacity 0.5s;
        z-index: 1;
    }
    .partner-card:hover::before { opacity: 1; }
    .partner-card:hover { transform: translateY(-8px); }

    /* Partner mini video slider */
    .partner-videos-track {
        display: flex;
        gap: 0.75rem;
        overflow-x: auto;
        scrollbar-width: none;
        scroll-behavior: smooth;
        padding: 0.5rem 0;
    }
    .partner-videos-track::-webkit-scrollbar { display: none; }
</style>
@endpush

@section('content')
<div class="bg-black min-h-screen" x-data="metalXPage()">

    {{-- ============================================ --}}
    {{-- HERO SECTION - Video Background + Channel --}}
    {{-- ============================================ --}}
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden">
        {{-- Video Background (muted, autoplay, loop) --}}
        @if($heroVideo)
            <div class="absolute inset-0 z-0">
                <iframe
                    src="https://www.youtube.com/embed/{{ $heroVideo->youtube_id }}?autoplay=1&mute=1&loop=1&playlist={{ $heroVideo->youtube_id }}&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1&enablejsapi=1&origin={{ url('/') }}"
                    class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-none"
                    style="width: 177.78vh; height: 100vh; min-width: 100vw; min-height: 56.25vw;"
                    frameborder="0"
                    allow="autoplay; encrypted-media"
                    allowfullscreen></iframe>
            </div>
        @endif

        {{-- Gradient overlays --}}
        <div class="absolute inset-0 z-[1] bg-gradient-to-b from-black/70 via-black/50 to-black"></div>
        <div class="absolute inset-0 z-[1] bg-gradient-to-r from-purple-900/30 via-transparent to-pink-900/30"></div>
        <div class="absolute bottom-0 left-0 right-0 h-40 z-[1] bg-gradient-to-t from-black to-transparent"></div>

        {{-- Hero Content --}}
        <div class="relative z-10 container mx-auto px-4 text-center py-20">
            {{-- Channel Logo --}}
            @if($channelSettings['channel_logo'])
                <div class="mb-10 flex justify-center opacity-0 animate-fade-in">
                    <div class="w-36 h-36 md:w-44 md:h-44 rounded-full overflow-hidden animate-pulse-glow p-1 bg-gradient-to-br from-purple-500 via-pink-500 to-red-500">
                        <div class="w-full h-full rounded-full overflow-hidden bg-black">
                            <img src="{{ $channelSettings['channel_logo'] }}" alt="{{ $channelSettings['channel_name'] }}" class="w-full h-full object-cover">
                        </div>
                    </div>
                </div>
            @endif

            {{-- Channel Name --}}
            <h1 class="text-5xl md:text-7xl lg:text-8xl font-black mb-6 tracking-tighter opacity-0 animate-fade-in-up">
                <span class="text-gradient-premium">{{ $channelSettings['channel_name'] }}</span>
            </h1>

            {{-- Description --}}
            @if($channelSettings['channel_description'])
                <p class="text-lg md:text-xl text-gray-300/80 mb-10 max-w-2xl mx-auto leading-relaxed opacity-0 animate-fade-in-up delay-200">
                    {{ $channelSettings['channel_description'] }}
                </p>
            @endif

            {{-- CTAs --}}
            <div class="flex flex-wrap justify-center gap-4 opacity-0 animate-fade-in-up delay-300">
                @if($channelSettings['channel_url'])
                    <a href="{{ $channelSettings['channel_url'] }}" target="_blank" rel="noopener noreferrer"
                       class="group inline-flex items-center px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-2xl transition-all duration-300 hover:scale-105 shadow-lg shadow-red-600/30 hover:shadow-red-600/50">
                        <svg class="w-6 h-6 mr-2 transition-transform group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        Subscribe
                    </a>
                @endif
                <a href="#showcase" class="inline-flex items-center px-8 py-4 glass text-white font-semibold rounded-2xl transition-all duration-300 hover:bg-white/10 hover:scale-105">
                    Explore Music
                    <svg class="w-5 h-5 ml-2 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                </a>
            </div>

            {{-- Now Playing indicator --}}
            @if($heroVideo)
                <div class="mt-12 opacity-0 animate-fade-in delay-500">
                    <button @click="openPlayer('{{ $heroVideo->youtube_id }}', {{ Js::from($heroVideo->title) }})"
                            class="inline-flex items-center gap-3 glass rounded-full px-6 py-3 text-sm text-gray-400 hover:text-white transition-colors cursor-pointer">
                        <span class="flex items-center gap-1">
                            <span class="w-1 h-3 bg-purple-500 rounded-full animate-pulse"></span>
                            <span class="w-1 h-4 bg-pink-500 rounded-full animate-pulse" style="animation-delay: 0.2s"></span>
                            <span class="w-1 h-2 bg-red-500 rounded-full animate-pulse" style="animation-delay: 0.4s"></span>
                        </span>
                        <span>Now Playing: <span class="text-white/80">{{ Str::limit($heroVideo->title, 40) }}</span></span>
                    </button>
                </div>
            @endif
        </div>
    </section>

    {{-- ============================================ --}}
    {{-- FEATURED SHOWCASE - Slot-Machine Carousel   --}}
    {{-- ============================================ --}}
    @if($featuredVideos->count() > 0)
    <section id="showcase" class="relative py-24 noise-overlay">
        <div class="absolute inset-0 bg-gradient-to-b from-black via-gray-950 to-black"></div>

        <div class="relative z-10 container mx-auto px-4">
            {{-- Section Header --}}
            <div class="text-center mb-12">
                <span class="inline-block text-xs font-bold tracking-[0.3em] uppercase text-purple-400 mb-4">Curated Selection</span>
                <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-4">
                    <span class="text-gradient-gold">Featured</span> Videos
                </h2>
                <div class="w-20 h-1 bg-gradient-to-r from-purple-500 to-pink-500 mx-auto rounded-full"></div>
            </div>

            {{-- Slot-Machine Carousel --}}
            <div x-data="featuredCarousel({{ $featuredVideos->count() }})" x-init="startAutoPlay()" class="relative">

                {{-- Navigation Arrows --}}
                <button @click="prev()" class="absolute left-2 md:left-8 top-1/2 -translate-y-1/2 z-30 w-12 h-12 md:w-14 md:h-14 glass rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-300 hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <button @click="next()" class="absolute right-2 md:right-8 top-1/2 -translate-y-1/2 z-30 w-12 h-12 md:w-14 md:h-14 glass rounded-full flex items-center justify-center text-white hover:bg-white/20 transition-all duration-300 hover:scale-110">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>

                {{-- Carousel viewport --}}
                <div class="carousel-viewport"
                     @mousedown="onDragStart($event)"
                     @mousemove="onDragMove($event)"
                     @mouseup="onDragEnd()"
                     @mouseleave="onDragEnd()"
                     @touchstart.passive="onTouchStart($event)"
                     @touchmove.passive="onTouchMove($event)"
                     @touchend="onDragEnd()"
                     @mouseenter="pauseAutoPlay()"
                     @mouseleave="resumeAutoPlay()">

                    @foreach($featuredVideos as $i => $video)
                        <div class="carousel-card"
                             :data-pos="getRelativePos({{ $i }})"
                             :style="getCardStyle({{ $i }})"
                             @click="handleCardClick({{ $i }}, '{{ $video->youtube_id }}', {{ Js::from($video->title) }})">
                            <div class="card-inner">
                                {{-- Thumbnail --}}
                                <div class="relative aspect-video overflow-hidden">
                                    <img src="{{ $video->best_thumbnail }}"
                                         alt="{{ $video->title }}"
                                         class="w-full h-full object-cover"
                                         loading="lazy">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

                                    {{-- Play button (center card only) --}}
                                    <div class="absolute inset-0 flex items-center justify-center" x-show="activeIndex === {{ $i }}" x-cloak>
                                        <div class="w-16 h-16 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center border border-white/30 transition-transform hover:scale-110">
                                            <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </div>
                                    </div>

                                    {{-- Duration --}}
                                    <div class="absolute bottom-3 right-3 bg-black/80 backdrop-blur-sm text-white text-xs px-2.5 py-1 rounded-lg font-mono tracking-wide">
                                        {{ $video->formatted_duration }}
                                    </div>
                                    {{-- View count --}}
                                    <div class="absolute top-3 left-3 bg-black/60 backdrop-blur-sm text-white text-xs px-3 py-1.5 rounded-full flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        {{ $video->formatted_view_count }}
                                    </div>
                                </div>
                                {{-- Info --}}
                                <div class="p-5 bg-gradient-to-b from-gray-900/90 to-black">
                                    <h3 class="text-white font-bold text-sm line-clamp-2 mb-1">
                                        {{ $video->title }}
                                    </h3>
                                    @if($video->title_th)
                                        <p class="text-gray-500 text-xs line-clamp-1">{{ $video->title_th }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Dot indicators --}}
                <div class="carousel-dots">
                    @foreach($featuredVideos as $i => $video)
                        <button class="carousel-dot" :class="{ 'active': activeIndex === {{ $i }} }" @click="goTo({{ $i }})"></button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- ============================================ --}}
    {{-- POPULAR VIDEOS GRID --}}
    {{-- ============================================ --}}
    <section class="relative py-24 noise-overlay" id="videos">
        <div class="absolute inset-0 bg-gradient-to-b from-black via-gray-950 to-black"></div>

        <div class="relative z-10 container mx-auto px-4">
            {{-- Section Header --}}
            <div class="text-center mb-16">
                <span class="inline-block text-xs font-bold tracking-[0.3em] uppercase text-pink-400 mb-4">Most Viewed</span>
                <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-4">
                    Popular <span class="text-gradient-premium">Music Videos</span>
                </h2>
                <p class="text-gray-500 text-lg">50,000+ views</p>
                <div class="w-20 h-1 bg-gradient-to-r from-pink-500 to-red-500 mx-auto rounded-full mt-4"></div>
            </div>

            {{-- Video Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                @forelse($popularVideos as $video)
                    <div class="group relative rounded-2xl overflow-hidden glass-dark transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl hover:shadow-purple-500/10 cursor-pointer"
                         @click="openPlayer('{{ $video->youtube_id }}', {{ Js::from($video->title) }})">
                        {{-- Thumbnail --}}
                        <div class="relative aspect-video overflow-hidden">
                            <img src="{{ $video->best_thumbnail }}"
                                 alt="{{ $video->title }}"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                 loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent"></div>

                            {{-- Play overlay --}}
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-16 h-16 rounded-full bg-white/10 backdrop-blur-md flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 scale-50 group-hover:scale-100 border border-white/20">
                                    <svg class="w-7 h-7 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </div>

                            {{-- Duration --}}
                            <div class="absolute bottom-3 right-3 bg-black/70 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-lg font-mono">
                                {{ $video->formatted_duration }}
                            </div>
                        </div>

                        {{-- Info --}}
                        <div class="p-5">
                            <h3 class="text-white font-bold mb-1 line-clamp-2 group-hover:text-purple-300 transition-colors">
                                {{ $video->title }}
                            </h3>
                            @if($video->title_th)
                                <p class="text-gray-500 text-sm line-clamp-1 mb-3">{{ $video->title_th }}</p>
                            @endif
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    {{ $video->formatted_view_count }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                    {{ $video->formatted_like_count }}
                                </span>
                                <span>{{ $video->published_at?->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-20">
                        <div class="max-w-md mx-auto">
                            <div class="w-20 h-20 mx-auto mb-6 rounded-full glass flex items-center justify-center">
                                <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-2">No Popular Videos Yet</h3>
                            <p class="text-gray-500 mb-6">Sync videos from YouTube to display popular content here.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ============================================ --}}
    {{-- PARTNER CHANNELS - Allied Channels           --}}
    {{-- ============================================ --}}
    @if($partnerChannels->count() > 0)
    <section class="relative py-24 noise-overlay" id="partners">
        <div class="absolute inset-0 bg-gradient-to-b from-black via-gray-950 to-black"></div>
        {{-- Decorative orbs --}}
        <div class="absolute top-20 right-10 w-80 h-80 bg-blue-600/10 rounded-full blur-[130px]"></div>
        <div class="absolute bottom-20 left-10 w-72 h-72 bg-purple-600/10 rounded-full blur-[120px]"></div>

        <div class="relative z-10 container mx-auto px-4">
            {{-- Section Header --}}
            <div class="text-center mb-16">
                <span class="inline-block text-xs font-bold tracking-[0.3em] uppercase text-blue-400 mb-4">Network</span>
                <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-4">
                    Partner <span class="text-gradient-gold">Channels</span>
                </h2>
                <p class="text-gray-500 text-lg">ช่องพันธมิตรของเรา</p>
                <div class="w-20 h-1 bg-gradient-to-r from-blue-500 to-purple-500 mx-auto rounded-full mt-4"></div>
            </div>

            {{-- Partner Channel Cards --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                @foreach($partnerChannels as $channel)
                    <div class="partner-card glass-dark">
                        {{-- Channel Header --}}
                        <div class="p-6 pb-4">
                            <div class="flex items-center gap-4 mb-4">
                                {{-- Channel Avatar --}}
                                <div class="w-16 h-16 rounded-full overflow-hidden flex-shrink-0 ring-2 ring-purple-500/30 p-0.5 bg-gradient-to-br from-blue-500 to-purple-500">
                                    @if($channel->channel_thumbnail_url)
                                        <img src="{{ $channel->channel_thumbnail_url }}" alt="{{ $channel->name }}" class="w-full h-full rounded-full object-cover">
                                    @else
                                        <div class="w-full h-full rounded-full bg-gray-800 flex items-center justify-center">
                                            <svg class="w-8 h-8 text-gray-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>

                                {{-- Channel Info --}}
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-xl font-bold text-white truncate">{{ $channel->name }}</h3>
                                    <div class="flex items-center gap-4 text-sm text-gray-400 mt-1">
                                        @if($channel->subscriber_count)
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                                {{ number_format($channel->subscriber_count) }}
                                            </span>
                                        @endif
                                        @if($channel->video_count)
                                            <span class="flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                                {{ number_format($channel->video_count) }} videos
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Subscribe button --}}
                                <a href="{{ $channel->youtube_url }}" target="_blank" rel="noopener noreferrer"
                                   class="flex-shrink-0 inline-flex items-center px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl transition-all duration-300 hover:scale-105 shadow-lg shadow-red-600/20">
                                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                    Subscribe
                                </a>
                            </div>
                        </div>

                        {{-- Channel Videos --}}
                        @if($channel->videos->count() > 0)
                            <div class="px-6 pb-6">
                                <div class="partner-videos-track">
                                    @foreach($channel->videos as $video)
                                        <div class="flex-shrink-0 w-48 cursor-pointer group/pv"
                                             @click="openPlayer('{{ $video->youtube_id }}', {{ Js::from($video->title) }})">
                                            <div class="relative aspect-video rounded-xl overflow-hidden mb-2">
                                                <img src="{{ $video->best_thumbnail }}" alt="{{ $video->title }}"
                                                     class="w-full h-full object-cover transition-transform duration-500 group-hover/pv:scale-110" loading="lazy">
                                                <div class="absolute inset-0 bg-black/30 group-hover/pv:bg-black/50 transition-colors"></div>
                                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover/pv:opacity-100 transition-opacity">
                                                    <div class="w-10 h-10 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                    </div>
                                                </div>
                                                <div class="absolute bottom-1.5 right-1.5 bg-black/80 text-white text-[10px] px-1.5 py-0.5 rounded font-mono">
                                                    {{ $video->formatted_duration }}
                                                </div>
                                            </div>
                                            <h4 class="text-white text-xs font-medium line-clamp-2 group-hover/pv:text-purple-300 transition-colors">{{ $video->title }}</h4>
                                            <p class="text-gray-500 text-[10px] mt-0.5">{{ $video->formatted_view_count }} views</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ============================================ --}}
    {{-- TEAM SECTION - Premium --}}
    {{-- ============================================ --}}
    <section class="relative py-24 noise-overlay" id="team">
        <div class="absolute inset-0 bg-gradient-to-b from-black via-gray-950 to-black"></div>
        {{-- Decorative orbs --}}
        <div class="absolute top-20 left-10 w-72 h-72 bg-purple-600/10 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-pink-600/10 rounded-full blur-[150px]"></div>

        <div class="relative z-10 container mx-auto px-4">
            {{-- Section Header --}}
            <div class="text-center mb-16">
                <span class="inline-block text-xs font-bold tracking-[0.3em] uppercase text-purple-400 mb-4">The Creators</span>
                <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-4">
                    Meet Our <span class="text-gradient-gold">Talented Team</span>
                </h2>
                <p class="text-gray-500 text-lg">The creative minds behind {{ $channelSettings['channel_name'] }}</p>
                <div class="w-20 h-1 bg-gradient-to-r from-purple-500 via-pink-500 to-red-500 mx-auto rounded-full mt-4"></div>
            </div>

            @if($teamMembers->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    @foreach($teamMembers as $member)
                        <div class="team-card relative group rounded-2xl overflow-hidden glass-dark transition-all duration-500 hover:-translate-y-3 hover:shadow-2xl hover:shadow-purple-500/20">
                            {{-- Member Image --}}
                            <div class="relative aspect-square overflow-hidden">
                                @if($member->image)
                                    <img src="{{ $member->image }}"
                                         alt="{{ $member->name }}"
                                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                         loading="lazy">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-purple-900 to-pink-900 flex items-center justify-center">
                                        <svg class="w-24 h-24 text-white/20" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                        </svg>
                                    </div>
                                @endif
                                {{-- Gradient overlay --}}
                                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-transparent opacity-70 group-hover:opacity-90 transition-opacity"></div>

                                {{-- Role badge --}}
                                <div class="absolute bottom-4 left-4 right-4">
                                    <p class="text-purple-300 text-sm font-semibold tracking-wide">
                                        {{ app()->getLocale() === 'th' && $member->role_th ? $member->role_th : $member->role }}
                                    </p>
                                </div>
                            </div>

                            {{-- Member Info --}}
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-white mb-2 group-hover:text-gradient-premium transition-colors">
                                    {{ app()->getLocale() === 'th' && $member->name_th ? $member->name_th : $member->name }}
                                </h3>

                                @if($member->bio || $member->bio_th)
                                    <p class="text-gray-400 text-sm mb-5 line-clamp-3 leading-relaxed">
                                        {{ app()->getLocale() === 'th' && $member->bio_th ? $member->bio_th : $member->bio }}
                                    </p>
                                @endif

                                {{-- Social Links --}}
                                <div class="flex gap-2">
                                    @if($member->youtube_url)
                                        <a href="{{ $member->youtube_url }}" target="_blank" rel="noopener noreferrer"
                                           class="w-9 h-9 flex items-center justify-center rounded-lg glass text-gray-400 hover:text-red-400 hover:bg-red-500/10 transition-all">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    @if($member->facebook_url)
                                        <a href="{{ $member->facebook_url }}" target="_blank" rel="noopener noreferrer"
                                           class="w-9 h-9 flex items-center justify-center rounded-lg glass text-gray-400 hover:text-blue-400 hover:bg-blue-500/10 transition-all">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    @if($member->instagram_url)
                                        <a href="{{ $member->instagram_url }}" target="_blank" rel="noopener noreferrer"
                                           class="w-9 h-9 flex items-center justify-center rounded-lg glass text-gray-400 hover:text-pink-400 hover:bg-pink-500/10 transition-all">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    @if($member->twitter_url)
                                        <a href="{{ $member->twitter_url }}" target="_blank" rel="noopener noreferrer"
                                           class="w-9 h-9 flex items-center justify-center rounded-lg glass text-gray-400 hover:text-white hover:bg-white/10 transition-all">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                            </svg>
                                        </a>
                                    @endif
                                    @if($member->tiktok_url)
                                        <a href="{{ $member->tiktok_url }}" target="_blank" rel="noopener noreferrer"
                                           class="w-9 h-9 flex items-center justify-center rounded-lg glass text-gray-400 hover:text-white hover:bg-white/10 transition-all">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
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
                <div class="text-center py-20">
                    <div class="w-20 h-20 mx-auto mb-6 rounded-full glass flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">No Team Members Yet</h3>
                    <p class="text-gray-500">Team members will be displayed here once added by the administrator.</p>
                </div>
            @endif
        </div>
    </section>

    {{-- ============================================ --}}
    {{-- CTA SECTION --}}
    {{-- ============================================ --}}
    <section class="relative py-24 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-purple-950 via-black to-pink-950"></div>
        <div class="absolute inset-0 noise-overlay"></div>
        {{-- Decorative --}}
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-purple-600/20 rounded-full blur-[150px]"></div>
        <div class="absolute bottom-0 right-1/4 w-80 h-80 bg-pink-600/20 rounded-full blur-[120px]"></div>

        <div class="relative z-10 container mx-auto px-4 text-center">
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6">
                Ready to <span class="text-gradient-gold">Experience</span> Our Music?
            </h2>
            <p class="text-xl text-gray-400 mb-10 max-w-2xl mx-auto">
                Subscribe to our YouTube channel and never miss a new release
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                @if($channelSettings['channel_url'])
                    <a href="{{ $channelSettings['channel_url'] }}" target="_blank"
                       class="group inline-flex items-center px-10 py-5 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold text-lg rounded-2xl transition-all duration-300 hover:scale-105 shadow-2xl shadow-red-600/30 hover:shadow-red-600/50">
                        <svg class="w-7 h-7 mr-3 transition-transform group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        Visit Our Channel
                    </a>
                @endif
                <a href="/" class="inline-flex items-center px-10 py-5 glass text-white font-semibold text-lg rounded-2xl transition-all duration-300 hover:bg-white/10 hover:scale-105">
                    Back to Main Site
                </a>
            </div>
        </div>
    </section>

    {{-- ============================================ --}}
    {{-- FLOATING VIDEO PLAYER MODAL --}}
    {{-- ============================================ --}}
    <div x-show="playerOpen" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 video-modal-backdrop flex items-center justify-center p-4 md:p-8"
         @click.self="closePlayer()"
         @keydown.escape.window="closePlayer()">

        <div x-show="playerOpen"
             x-transition:enter="transition ease-out duration-300 delay-100"
             x-transition:enter-start="opacity-0 scale-90 translate-y-8"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-5xl">

            {{-- Close button --}}
            <button @click="closePlayer()"
                    class="absolute -top-12 right-0 w-10 h-10 rounded-full glass flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition-all z-10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Video container --}}
            <div class="rounded-2xl overflow-hidden shadow-2xl shadow-purple-500/20 border border-white/10">
                <div class="aspect-video bg-black">
                    <iframe x-ref="playerIframe"
                            :src="playerOpen ? 'https://www.youtube.com/embed/' + currentVideoId + '?autoplay=1&rel=0&modestbranding=1' : ''"
                            class="w-full h-full"
                            frameborder="0"
                            allow="autoplay; encrypted-media; fullscreen"
                            allowfullscreen></iframe>
                </div>
                {{-- Video title bar --}}
                <div class="glass-dark px-6 py-4 flex items-center justify-between">
                    <h3 class="text-white font-semibold text-sm md:text-base line-clamp-1" x-text="currentVideoTitle"></h3>
                    <a :href="'https://www.youtube.com/watch?v=' + currentVideoId" target="_blank"
                       class="flex-shrink-0 ml-4 text-xs text-gray-400 hover:text-red-400 transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                        YouTube
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function metalXPage() {
    return {
        playerOpen: false,
        currentVideoId: '',
        currentVideoTitle: '',

        openPlayer(videoId, title) {
            this.currentVideoId = videoId;
            this.currentVideoTitle = title;
            this.playerOpen = true;
            document.body.style.overflow = 'hidden';
        },

        closePlayer() {
            this.playerOpen = false;
            this.currentVideoId = '';
            this.currentVideoTitle = '';
            document.body.style.overflow = '';
        }
    }
}

function featuredCarousel(totalSlides) {
    return {
        activeIndex: 0,
        total: totalSlides,
        autoPlayTimer: null,
        isDragging: false,
        dragStartX: 0,
        dragDelta: 0,

        // Get relative position from active (-2, -1, 0, 1, 2, hidden)
        getRelativePos(index) {
            let diff = index - this.activeIndex;
            // Wrap around for circular carousel
            if (diff > Math.floor(this.total / 2)) diff -= this.total;
            if (diff < -Math.floor(this.total / 2)) diff += this.total;
            return diff;
        },

        // Calculate 3D card style based on relative position
        getCardStyle(index) {
            const pos = this.getRelativePos(index);
            const absPos = Math.abs(pos);

            // Hide cards too far away
            if (absPos > 2) {
                return 'transform: translate(-50%, -50%) scale(0.6); opacity: 0; z-index: 0; pointer-events: none;';
            }

            // Each position: translateX, scale, opacity, z-index, rotateY
            const configs = {
                0:  { x: 0,    scale: 1,    opacity: 1,   z: 20, rotateY: 0,   blur: 0 },
                1:  { x: 110,  scale: 0.82, opacity: 0.6, z: 10, rotateY: -8,  blur: 1 },
                '-1': { x: -110, scale: 0.82, opacity: 0.6, z: 10, rotateY: 8, blur: 1 },
                2:  { x: 195,  scale: 0.68, opacity: 0.3, z: 5,  rotateY: -15, blur: 2 },
                '-2': { x: -195, scale: 0.68, opacity: 0.3, z: 5, rotateY: 15, blur: 2 },
            };

            const key = pos === 0 ? 0 : (absPos <= 2 ? pos : (pos > 0 ? 2 : -2));
            const cfg = configs[key] || configs[absPos > 0 ? 2 : 0];

            return `transform: translate(calc(-50% + ${cfg.x}%), -50%) scale(${cfg.scale}) rotateY(${cfg.rotateY}deg); ` +
                   `opacity: ${cfg.opacity}; z-index: ${cfg.z}; ` +
                   `filter: blur(${cfg.blur}px);`;
        },

        next() {
            this.activeIndex = (this.activeIndex + 1) % this.total;
        },

        prev() {
            this.activeIndex = (this.activeIndex - 1 + this.total) % this.total;
        },

        goTo(index) {
            this.activeIndex = index;
        },

        handleCardClick(index, videoId, title) {
            if (index === this.activeIndex) {
                // Center card -> play video
                const pageRoot = document.querySelector('[x-data="metalXPage()"]');
                if (pageRoot && pageRoot._x_dataStack) {
                    pageRoot._x_dataStack[0].openPlayer(videoId, title);
                }
            } else {
                // Non-center card -> navigate to it
                this.goTo(index);
            }
        },

        // Auto-play (slow continuous rotation)
        startAutoPlay() {
            this.autoPlayTimer = setInterval(() => {
                this.next();
            }, 4000);
        },

        pauseAutoPlay() {
            if (this.autoPlayTimer) {
                clearInterval(this.autoPlayTimer);
                this.autoPlayTimer = null;
            }
        },

        resumeAutoPlay() {
            if (!this.autoPlayTimer) {
                this.startAutoPlay();
            }
        },

        // Touch / Mouse drag support
        onDragStart(e) {
            this.isDragging = true;
            this.dragStartX = e.clientX || e.pageX;
            this.dragDelta = 0;
            this.pauseAutoPlay();
        },

        onTouchStart(e) {
            this.isDragging = true;
            this.dragStartX = e.touches[0].clientX;
            this.dragDelta = 0;
            this.pauseAutoPlay();
        },

        onDragMove(e) {
            if (!this.isDragging) return;
            const x = e.clientX || e.pageX;
            this.dragDelta = x - this.dragStartX;
        },

        onTouchMove(e) {
            if (!this.isDragging) return;
            this.dragDelta = e.touches[0].clientX - this.dragStartX;
        },

        onDragEnd() {
            if (!this.isDragging) return;
            this.isDragging = false;

            // Threshold: 50px swipe
            if (this.dragDelta > 50) {
                this.prev();
            } else if (this.dragDelta < -50) {
                this.next();
            }

            this.dragDelta = 0;
            this.resumeAutoPlay();
        }
    }
}
</script>
@endpush
