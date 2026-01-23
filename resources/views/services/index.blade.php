@extends($publicLayout ?? 'layouts.app')

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
    // Category specific styling - unique colors and images
    $categoryStyles = [
        'blockchain' => [
            'gradient' => 'from-purple-600 via-violet-600 to-fuchsia-600',
            'bg' => 'bg-purple-50',
            'border' => 'border-purple-200',
            'text' => 'text-purple-600',
            'hover' => 'hover:border-purple-400',
            'badge_bg' => 'bg-purple-100',
            'badge_text' => 'text-purple-700',
            'button' => 'bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-700 hover:to-violet-700',
            'image' => 'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=800&auto=format&fit=crop',
        ],
        'web' => [
            'gradient' => 'from-blue-600 via-sky-600 to-cyan-600',
            'bg' => 'bg-blue-50',
            'border' => 'border-blue-200',
            'text' => 'text-blue-600',
            'hover' => 'hover:border-blue-400',
            'badge_bg' => 'bg-blue-100',
            'badge_text' => 'text-blue-700',
            'button' => 'bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700',
            'image' => 'https://images.unsplash.com/photo-1547658719-da2b51169166?w=800&auto=format&fit=crop',
        ],
        'mobile' => [
            'gradient' => 'from-green-600 via-emerald-600 to-teal-600',
            'bg' => 'bg-green-50',
            'border' => 'border-green-200',
            'text' => 'text-green-600',
            'hover' => 'hover:border-green-400',
            'badge_bg' => 'bg-green-100',
            'badge_text' => 'text-green-700',
            'button' => 'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700',
            'image' => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=800&auto=format&fit=crop',
        ],
        'ai' => [
            'gradient' => 'from-indigo-600 via-purple-600 to-pink-600',
            'bg' => 'bg-indigo-50',
            'border' => 'border-indigo-200',
            'text' => 'text-indigo-600',
            'hover' => 'hover:border-indigo-400',
            'badge_bg' => 'bg-indigo-100',
            'badge_text' => 'text-indigo-700',
            'button' => 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700',
            'image' => 'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=800&auto=format&fit=crop',
        ],
        'iot' => [
            'gradient' => 'from-orange-600 via-amber-600 to-yellow-600',
            'bg' => 'bg-orange-50',
            'border' => 'border-orange-200',
            'text' => 'text-orange-600',
            'hover' => 'hover:border-orange-400',
            'badge_bg' => 'bg-orange-100',
            'badge_text' => 'text-orange-700',
            'button' => 'bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700',
            'image' => 'https://images.unsplash.com/photo-1558346490-a72e53ae2d4f?w=800&auto=format&fit=crop',
        ],
        'security' => [
            'gradient' => 'from-red-600 via-rose-600 to-pink-600',
            'bg' => 'bg-red-50',
            'border' => 'border-red-200',
            'text' => 'text-red-600',
            'hover' => 'hover:border-red-400',
            'badge_bg' => 'bg-red-100',
            'badge_text' => 'text-red-700',
            'button' => 'bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700',
            'image' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=800&auto=format&fit=crop',
        ],
        'software' => [
            'gradient' => 'from-teal-600 via-cyan-600 to-sky-600',
            'bg' => 'bg-teal-50',
            'border' => 'border-teal-200',
            'text' => 'text-teal-600',
            'hover' => 'hover:border-teal-400',
            'badge_bg' => 'bg-teal-100',
            'badge_text' => 'text-teal-700',
            'button' => 'bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700',
            'image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=800&auto=format&fit=crop',
        ],
        'flutter' => [
            'gradient' => 'from-cyan-600 via-blue-600 to-indigo-600',
            'bg' => 'bg-cyan-50',
            'border' => 'border-cyan-200',
            'text' => 'text-cyan-600',
            'hover' => 'hover:border-cyan-400',
            'badge_bg' => 'bg-cyan-100',
            'badge_text' => 'text-cyan-700',
            'button' => 'bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700',
            'image' => 'https://images.unsplash.com/photo-1551650975-87deedd944c3?w=800&auto=format&fit=crop',
        ],
    ];

    // Default style for unknown categories
    $defaultStyle = [
        'gradient' => 'from-gray-600 via-slate-600 to-zinc-600',
        'bg' => 'bg-gray-50',
        'border' => 'border-gray-200',
        'text' => 'text-gray-600',
        'hover' => 'hover:border-gray-400',
        'badge_bg' => 'bg-gray-100',
        'badge_text' => 'text-gray-700',
        'button' => 'bg-gradient-to-r from-gray-600 to-slate-600 hover:from-gray-700 hover:to-slate-700',
        'image' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&auto=format&fit=crop',
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
            <!-- Categories Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
                @foreach($categories as $category)
                    @php
                        $style = $categoryStyles[$category->key] ?? $defaultStyle;
                        $categoryImage = $category->image ?? $style['image'];
                    @endphp

                    <!-- Category Card -->
                    <div class="category-card group cursor-pointer" data-category="{{ $category->key }}">
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-2xl border-2 {{ $style['border'] }} {{ $style['hover'] }}">
                            <!-- Category Image -->
                            <div class="relative h-48 overflow-hidden">
                                <img src="{{ $categoryImage }}"
                                     alt="{{ $category->display_name }}"
                                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                <div class="absolute inset-0 bg-gradient-to-br {{ $style['gradient'] }} opacity-70 group-hover:opacity-60 transition-opacity"></div>

                                <!-- Icon Badge -->
                                <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm rounded-xl p-3 shadow-lg">
                                    <span class="text-4xl">{{ $category->icon }}</span>
                                </div>

                                <!-- Options Count Badge -->
                                <div class="absolute bottom-4 left-4 {{ $style['badge_bg'] }} {{ $style['badge_text'] }} px-3 py-1 rounded-full text-sm font-semibold">
                                    {{ $category->activeOptions->count() }} บริการ
                                </div>
                            </div>

                            <!-- Category Info -->
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-2 group-hover:{{ $style['text'] }} transition-colors">
                                    {{ $category->display_name }}
                                </h3>
                                @if($category->display_description)
                                    <p class="text-sm text-gray-600 mb-4 line-clamp-2">
                                        {{ $category->display_description }}
                                    </p>
                                @endif

                                <div class="flex items-center {{ $style['text'] }} font-semibold text-sm group-hover:gap-2 transition-all">
                                    <span>ดูบริการทั้งหมด</span>
                                    <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Expanded Category Options -->
            @foreach($categories as $category)
                @php
                    $style = $categoryStyles[$category->key] ?? $defaultStyle;
                @endphp

                <div id="category-{{ $category->key }}" class="category-options hidden mb-12">
                    <!-- Category Header -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 border-2 {{ $style['border'] }}">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div class="flex items-center gap-4">
                                <div class="text-6xl">{{ $category->icon }}</div>
                                <div>
                                    <h2 class="text-3xl font-bold {{ $style['text'] }}">{{ $category->display_name }}</h2>
                                    @if($category->display_description)
                                        <p class="text-gray-600 mt-1">{{ $category->display_description }}</p>
                                    @endif
                                </div>
                            </div>
                            <button class="close-category px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-semibold transition-colors">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    ปิด
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Options Grid -->
                    @if($category->activeOptions->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            @foreach($category->activeOptions as $option)
                                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 group border-2 {{ $style['border'] }} {{ $style['hover'] }}">
                                    <div class="p-6">
                                        <!-- Option Image -->
                                        @if($option->image)
                                            <div class="w-full h-40 mb-4 rounded-lg overflow-hidden {{ $style['bg'] }}">
                                                <img src="{{ asset('storage/' . $option->image) }}"
                                                     alt="{{ $option->display_name }}"
                                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                            </div>
                                        @else
                                            <div class="w-16 h-16 mb-4 bg-gradient-to-br {{ $style['gradient'] }} rounded-xl flex items-center justify-center text-3xl shadow-lg">
                                                {{ $category->icon }}
                                            </div>
                                        @endif

                                        <!-- Title -->
                                        <h3 class="text-lg font-bold text-gray-900 mb-2 group-hover:{{ $style['text'] }} transition-colors">
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
                                                            <svg class="w-3 h-3 {{ $style['text'] }} mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                            </svg>
                                                            <span class="line-clamp-1">{{ $feature }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        @endif

                                        <!-- Price and Action -->
                                        <div class="flex items-center justify-between mt-auto pt-4 border-t {{ $style['border'] }}">
                                            <div>
                                                <span class="text-2xl font-bold {{ $style['text'] }}">
                                                    {{ number_format($option->price, 0) }}
                                                </span>
                                                <span class="text-sm text-gray-500 ml-1">฿</span>
                                            </div>

                                            <a href="{{ route('service.detail', [$category->key, $option->key]) }}"
                                               class="px-4 py-2 {{ $style['button'] }} text-white rounded-lg transition-all text-sm font-semibold shadow-md hover:shadow-lg">
                                                รายละเอียด
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 bg-white rounded-xl">
                            <p class="text-gray-500">ไม่มีตัวเลือกบริการในหมวดนี้ในขณะนี้</p>
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

    .category-options {
        animation: slideDown 0.4s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categoryCards = document.querySelectorAll('.category-card');
        const categoryOptions = document.querySelectorAll('.category-options');
        const closeButtons = document.querySelectorAll('.close-category');

        // Function to close all categories
        function closeAllCategories() {
            categoryOptions.forEach(option => {
                option.classList.add('hidden');
            });
            // Scroll back to top of categories
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Category card click handler
        categoryCards.forEach(card => {
            card.addEventListener('click', function() {
                const categoryKey = this.dataset.category;
                const targetOptions = document.getElementById(`category-${categoryKey}`);

                // Close all other categories first
                categoryOptions.forEach(option => {
                    if (option.id !== `category-${categoryKey}`) {
                        option.classList.add('hidden');
                    }
                });

                // Toggle the clicked category
                if (targetOptions.classList.contains('hidden')) {
                    targetOptions.classList.remove('hidden');

                    // Smooth scroll to the expanded section
                    setTimeout(() => {
                        targetOptions.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 100);
                } else {
                    targetOptions.classList.add('hidden');
                }
            });
        });

        // Close button handler
        closeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
                closeAllCategories();
            });
        });

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.category-card') && !e.target.closest('.category-options')) {
                closeAllCategories();
            }
        });
    });
</script>
@endpush
@endsection
