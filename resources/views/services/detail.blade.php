@extends('layouts.app')

@section('title', $option->name . ' - XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-gray-900 via-gray-800 to-black text-white">
    <!-- Hero Section -->
    <div class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-r from-primary-600/20 to-purple-600/20"></div>
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%239C92AC&quot; fill-opacity=&quot;0.05&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

        <div class="container mx-auto px-4 relative z-10">
            <!-- Breadcrumb -->
            <nav class="text-sm mb-8">
                <ol class="flex items-center space-x-2 text-gray-400">
                    <li><a href="{{ route('home') }}" class="hover:text-white transition">หน้าแรก</a></li>
                    <li>/</li>
                    <li><a href="{{ route('support.index') }}" class="hover:text-white transition">บริการ</a></li>
                    <li>/</li>
                    <li class="text-white">{{ $category->name_th ?? $category->name }}</li>
                    <li>/</li>
                    <li class="text-primary-400">{{ $option->name_th ?? $option->name }}</li>
                </ol>
            </nav>

            <div class="grid lg:grid-cols-2 gap-12 items-start">
                <!-- Left Column - Image & Basic Info -->
                <div>
                    <!-- Service Image -->
                    @if($option->image)
                        <div class="relative rounded-2xl overflow-hidden shadow-2xl mb-8">
                            <img src="{{ asset('storage/' . $option->image) }}"
                                 alt="{{ $option->name }}"
                                 class="w-full h-96 object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        </div>
                    @else
                        <div class="relative rounded-2xl overflow-hidden shadow-2xl mb-8 bg-gradient-to-br from-primary-600 to-purple-600 h-96 flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-8xl mb-4">{{ $category->icon }}</div>
                                <h3 class="text-2xl font-bold">{{ $option->name }}</h3>
                            </div>
                        </div>
                    @endif

                    <!-- Category Badge -->
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600/20 border border-primary-500/30 rounded-full text-primary-400 mb-6">
                        <span class="text-2xl">{{ $category->icon }}</span>
                        <span class="font-medium">{{ $category->name_th ?? $category->name }}</span>
                    </div>
                </div>

                <!-- Right Column - Details -->
                <div>
                    <h1 class="text-4xl lg:text-5xl font-bold mb-4 bg-gradient-to-r from-white to-gray-400 bg-clip-text text-transparent">
                        {{ $option->name_th ?? $option->name }}
                    </h1>

                    @if($option->name_th && $option->name !== $option->name_th)
                        <p class="text-xl text-gray-400 mb-6">{{ $option->name }}</p>
                    @endif

                    <!-- Price -->
                    <div class="mb-8">
                        <div class="inline-flex items-baseline gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-purple-600 rounded-xl">
                            <span class="text-4xl font-bold">{{ number_format($option->price, 0) }}</span>
                            <span class="text-xl">฿</span>
                        </div>
                    </div>

                    <!-- Short Description -->
                    @if($option->description_th || $option->description)
                        <p class="text-lg text-gray-300 mb-8 leading-relaxed">
                            {{ $option->description_th ?? $option->description }}
                        </p>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex flex-wrap gap-4 mb-8">
                        <a href="{{ route('support.index') }}#quotation-form"
                           class="px-8 py-4 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 rounded-xl font-semibold text-lg transition-all transform hover:scale-105 shadow-lg">
                            สั่งซื้อบริการนี้
                        </a>
                        <a href="{{ route('support.index') }}"
                           class="px-8 py-4 border-2 border-gray-600 hover:border-primary-500 rounded-xl font-semibold text-lg transition-all">
                            เลือกบริการอื่น
                        </a>
                    </div>

                    <!-- Trust Badges -->
                    <div class="grid grid-cols-3 gap-4 p-6 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10">
                        <div class="text-center">
                            <div class="text-2xl mb-1">✓</div>
                            <div class="text-sm text-gray-400">มืออาชีพ</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl mb-1">⚡</div>
                            <div class="text-sm text-gray-400">ส่งมอบรวดเร็ว</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl mb-1">💎</div>
                            <div class="text-sm text-gray-400">คุณภาพสูง</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Long Description Section -->
    @if($option->long_description_th || $option->long_description)
        <div class="py-16 bg-gray-900/50">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold mb-8">รายละเอียดบริการ</h2>
                <div class="prose prose-invert prose-lg max-w-none">
                    <p class="text-gray-300 leading-relaxed whitespace-pre-line">
                        {{ $option->long_description_th ?? $option->long_description }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Features Section -->
    @if($option->features_th || $option->features)
        <div class="py-16">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold mb-12 text-center">คุณสมบัติเด่น</h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach(($option->features_th ?? $option->features ?? []) as $feature)
                        <div class="flex items-start gap-4 p-6 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 hover:border-primary-500/50 transition-all group">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-primary-600 to-purple-600 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-300">{{ $feature }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Steps/Process Section -->
    @if($option->steps_th || $option->steps)
        <div class="py-16 bg-gray-900/50">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold mb-12 text-center">ขั้นตอนการทำงาน</h2>
                <div class="max-w-4xl mx-auto">
                    @foreach(($option->steps_th ?? $option->steps ?? []) as $index => $step)
                        <div class="flex gap-6 mb-8 last:mb-0">
                            <!-- Step Number -->
                            <div class="flex-shrink-0">
                                <div class="w-16 h-16 bg-gradient-to-br from-primary-600 to-purple-600 rounded-full flex items-center justify-center text-2xl font-bold shadow-lg">
                                    {{ $index + 1 }}
                                </div>
                            </div>
                            <!-- Step Content -->
                            <div class="flex-1 pt-3">
                                <div class="p-6 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10">
                                    <p class="text-lg text-gray-300">{{ $step }}</p>
                                </div>
                            </div>
                        </div>
                        @if(!$loop->last)
                            <div class="flex gap-6 mb-4">
                                <div class="flex-shrink-0 w-16 flex justify-center">
                                    <div class="w-0.5 h-8 bg-gradient-to-b from-primary-600 to-purple-600"></div>
                                </div>
                                <div class="flex-1"></div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Related Services -->
    @if($relatedServices->count() > 0)
        <div class="py-16">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold mb-12 text-center">บริการที่เกี่ยวข้อง</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    @foreach($relatedServices as $related)
                        <a href="{{ route('service.detail', [$category->key, $related->key]) }}"
                           class="block p-6 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 hover:border-primary-500/50 transition-all group">
                            <h3 class="text-xl font-bold mb-2 group-hover:text-primary-400 transition">
                                {{ $related->name_th ?? $related->name }}
                            </h3>
                            @if($related->description_th || $related->description)
                                <p class="text-gray-400 text-sm mb-4 line-clamp-2">
                                    {{ $related->description_th ?? $related->description }}
                                </p>
                            @endif
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-primary-400">
                                    {{ number_format($related->price, 0) }} ฿
                                </span>
                                <span class="text-primary-400 group-hover:translate-x-2 transition-transform">→</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- CTA Section -->
    <div class="py-20 bg-gradient-to-r from-primary-600 to-purple-600">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-6">พร้อมที่จะเริ่มต้นแล้วหรือยัง?</h2>
            <p class="text-xl mb-8 text-white/90">ติดต่อเราวันนี้เพื่อรับคำปรึกษาฟรี</p>
            <div class="flex flex-wrap gap-4 justify-center">
                <a href="{{ route('support.index') }}#quotation-form"
                   class="px-8 py-4 bg-white text-primary-600 hover:bg-gray-100 rounded-xl font-semibold text-lg transition-all transform hover:scale-105 shadow-lg">
                    สั่งซื้อเลย
                </a>
                <a href="{{ route('support.index') }}"
                   class="px-8 py-4 border-2 border-white hover:bg-white/10 rounded-xl font-semibold text-lg transition-all">
                    ดูบริการทั้งหมด
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Smooth scroll to quotation form
    document.querySelectorAll('a[href*="#quotation-form"]').forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href.includes('#')) {
                e.preventDefault();
                window.location.href = href;
            }
        });
    });
</script>
@endpush
@endsection
