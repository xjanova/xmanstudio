@extends('layouts.app')

@section('title', 'บริการของเรา - XMAN Studio')

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-r from-primary-600 to-primary-800 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">บริการของเรา</h1>
        <p class="text-xl text-primary-100 max-w-2xl mx-auto">
            IT Solutions ครบวงจร ตอบโจทย์ทุกความต้องการทางธุรกิจ
        </p>
    </div>
</section>

@php
    // Category images mapping - beautiful tech-themed images
    $categoryImages = [
        'blockchain' => 'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=800&auto=format&fit=crop',
        'web' => 'https://images.unsplash.com/photo-1547658719-da2b51169166?w=800&auto=format&fit=crop',
        'mobile' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=800&auto=format&fit=crop',
        'ai' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&auto=format&fit=crop',
        'iot' => 'https://images.unsplash.com/photo-1558346490-a72e53ae2d4f?w=800&auto=format&fit=crop',
        'security' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=800&auto=format&fit=crop',
        'software' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&auto=format&fit=crop',
        'flutter' => 'https://images.unsplash.com/photo-1551650975-87deedd944c3?w=800&auto=format&fit=crop',
    ];

    // Gradient colors for categories
    $categoryGradients = [
        'blockchain' => 'from-purple-600 to-indigo-600',
        'web' => 'from-blue-600 to-cyan-600',
        'mobile' => 'from-green-600 to-teal-600',
        'ai' => 'from-indigo-600 to-purple-600',
        'iot' => 'from-orange-600 to-red-600',
        'security' => 'from-red-600 to-pink-600',
        'software' => 'from-teal-600 to-green-600',
        'flutter' => 'from-cyan-600 to-blue-600',
    ];
@endphp

<!-- Services Categories Grid -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($categories->isEmpty())
            <div class="text-center py-16">
                <svg class="w-24 h-24 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-700">ยังไม่มีบริการ</h2>
                <p class="text-gray-500 mt-2">กรุณากลับมาใหม่ภายหลัง</p>
            </div>
        @else
            @foreach($categories as $category)
                @php
                    $categoryKey = $category->key;
                    $categoryImage = $category->image ?? $categoryImages[$categoryKey] ?? 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&auto=format&fit=crop';
                    $categoryGradient = $categoryGradients[$categoryKey] ?? 'from-primary-600 to-primary-800';
                @endphp

                <!-- Category Section -->
                <div class="mb-16 last:mb-0">
                    <!-- Category Header -->
                    <div class="relative rounded-2xl overflow-hidden shadow-xl mb-8 group">
                        <!-- Background Image -->
                        <div class="absolute inset-0">
                            <img src="{{ $categoryImage }}"
                                 alt="{{ $category->display_name }}"
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-r {{ $categoryGradient }} opacity-80"></div>
                        </div>

                        <!-- Category Info -->
                        <div class="relative z-10 px-8 py-12 text-white">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="text-6xl">{{ $category->icon }}</div>
                                <div>
                                    <h2 class="text-3xl md:text-4xl font-bold">{{ $category->display_name }}</h2>
                                    @if($category->display_description)
                                        <p class="text-lg text-white/90 mt-2">{{ $category->display_description }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Category Options Grid -->
                    @if($category->activeOptions->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            @foreach($category->activeOptions as $option)
                                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 group border border-gray-100">
                                    <!-- Option Card -->
                                    <div class="p-6">
                                        <!-- Icon/Image -->
                                        @if($option->image)
                                            <div class="w-full h-40 mb-4 rounded-lg overflow-hidden">
                                                <img src="{{ asset('storage/' . $option->image) }}"
                                                     alt="{{ $option->display_name }}"
                                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                            </div>
                                        @else
                                            <div class="w-16 h-16 mb-4 bg-gradient-to-br {{ $categoryGradient }} rounded-xl flex items-center justify-center text-3xl shadow-lg">
                                                {{ $category->icon }}
                                            </div>
                                        @endif

                                        <!-- Title -->
                                        <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-primary-600 transition-colors">
                                            {{ $option->display_name }}
                                        </h3>

                                        <!-- Description -->
                                        @if($option->display_description)
                                            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                                {{ $option->display_description }}
                                            </p>
                                        @endif

                                        <!-- Features Preview -->
                                        @if($option->features_th || $option->features)
                                            @php
                                                $features = $option->features_th ?? $option->features;
                                                $featuresList = is_array($features) ? $features : [];
                                            @endphp
                                            @if(count($featuresList) > 0)
                                                <ul class="text-xs text-gray-500 mb-4 space-y-1">
                                                    @foreach(array_slice($featuresList, 0, 2) as $feature)
                                                        <li class="flex items-center">
                                                            <svg class="w-3 h-3 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                            <span class="line-clamp-1">{{ $feature }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        @endif

                                        <!-- Price and Action -->
                                        <div class="flex items-center justify-between mt-auto pt-4 border-t border-gray-100">
                                            <div>
                                                <span class="text-2xl font-bold text-primary-600">
                                                    {{ number_format($option->price, 0) }}
                                                </span>
                                                <span class="text-sm text-gray-500 ml-1">฿</span>
                                            </div>

                                            <a href="{{ route('service.detail', [$category->key, $option->key]) }}"
                                               class="px-4 py-2 bg-gradient-to-r {{ $categoryGradient }} text-white rounded-lg hover:shadow-lg transition-all text-sm font-semibold">
                                                ดูรายละเอียด
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            ไม่มีตัวเลือกบริการในหมวดนี้ในขณะนี้
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-gradient-to-r from-primary-600 to-primary-800 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-4">พร้อมเริ่มโปรเจคของคุณแล้วหรือยัง?</h2>
        <p class="text-lg text-primary-100 mb-8">
            ทีมผู้เชี่ยวชาญของเราพร้อมให้คำปรึกษาและวางแผนโซลูชันที่เหมาะสมกับธุรกิจของคุณ
        </p>
        <div class="flex flex-wrap gap-4 justify-center">
            <a href="{{ route('support.index') }}"
               class="inline-block px-8 py-4 bg-white text-primary-600 rounded-lg hover:bg-gray-100 font-semibold text-lg transition-all shadow-lg">
                สั่งซื้อบริการ
            </a>
            <a href="{{ route('support.index') }}#contact"
               class="inline-block px-8 py-4 border-2 border-white text-white rounded-lg hover:bg-white/10 font-semibold text-lg transition-all">
                ติดต่อเรา
            </a>
        </div>
    </div>
</section>

@push('styles')
<style>
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush
@endsection
