@extends('layouts.app')

@section('title', 'ผลิตภัณฑ์ - XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%239C92AC\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center px-4 py-2 bg-primary-500/20 rounded-full text-primary-300 text-sm mb-6 backdrop-blur-sm border border-primary-500/30">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                ซอฟต์แวร์และเครื่องมือคุณภาพสูง
            </div>

            <h1 class="text-4xl md:text-6xl font-black text-white mb-6">
                ผลิตภัณฑ์<span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-purple-400">ของเรา</span>
            </h1>

            <p class="text-xl text-gray-300 max-w-2xl mx-auto mb-8">
                ค้นพบซอฟต์แวร์และเครื่องมือพัฒนาที่จะช่วยยกระดับการทำงานของคุณ
            </p>

            <!-- Stats -->
            <div class="flex flex-wrap justify-center gap-8 mt-12">
                <div class="text-center">
                    <div class="text-3xl font-bold text-white">{{ $products->total() }}+</div>
                    <div class="text-gray-400 text-sm">ผลิตภัณฑ์</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-white">500+</div>
                    <div class="text-gray-400 text-sm">ลูกค้าที่ไว้วางใจ</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-white">24/7</div>
                    <div class="text-gray-400 text-sm">ซัพพอร์ต</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Filter (Optional) -->
    @if(isset($categories) && $categories->count() > 0)
    <section class="py-6 border-b border-gray-700/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-center gap-3">
                <a href="{{ route('products.index') }}"
                   class="px-4 py-2 rounded-full text-sm font-medium transition-all {{ !request('category') ? 'bg-primary-600 text-white' : 'bg-gray-800/50 text-gray-300 hover:bg-gray-700/50' }}">
                    ทั้งหมด
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                       class="px-4 py-2 rounded-full text-sm font-medium transition-all {{ request('category') == $category->slug ? 'bg-primary-600 text-white' : 'bg-gray-800/50 text-gray-300 hover:bg-gray-700/50' }}">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Products Grid -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-8 bg-green-500/20 border border-green-500/50 text-green-300 px-6 py-4 rounded-xl backdrop-blur-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if($products->isEmpty())
                <div class="text-center py-20">
                    <div class="w-24 h-24 bg-gray-800/50 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">ยังไม่มีผลิตภัณฑ์</h2>
                    <p class="text-gray-400">กรุณากลับมาใหม่ภายหลัง</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($products as $product)
                        <div class="group bg-gray-800/50 rounded-2xl overflow-hidden border border-gray-700 hover:border-primary-500/50 transition-all hover:transform hover:scale-[1.02] backdrop-blur-sm">
                            <!-- Product Image -->
                            <div class="relative aspect-video overflow-hidden">
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-primary-500/20 to-purple-500/20 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                @endif

                                <!-- Badges -->
                                <div class="absolute top-4 left-4 flex gap-2">
                                    @if($product->requires_license)
                                        <span class="px-2 py-1 bg-purple-500/80 text-white text-xs rounded-full backdrop-blur-sm">
                                            License
                                        </span>
                                    @endif
                                    @if($product->is_custom)
                                        <span class="px-2 py-1 bg-yellow-500/80 text-white text-xs rounded-full backdrop-blur-sm">
                                            Custom
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Product Info -->
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-white group-hover:text-primary-400 transition-colors mb-2">
                                    {{ $product->name }}
                                </h3>

                                @if($product->short_description)
                                    <p class="text-gray-400 mb-4 line-clamp-2">{{ $product->short_description }}</p>
                                @elseif($product->description)
                                    <p class="text-gray-400 mb-4 line-clamp-2">{{ Str::limit(strip_tags($product->description), 100) }}</p>
                                @endif

                                <!-- Features Preview -->
                                @if($product->features && is_array($product->features))
                                    <div class="flex flex-wrap gap-2 mb-4">
                                        @foreach(array_slice($product->features, 0, 3) as $feature)
                                            @if(is_string($feature))
                                                <span class="text-xs text-gray-400 bg-gray-700/50 px-2 py-1 rounded">
                                                    {{ Str::limit($feature, 20) }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Price & Actions -->
                                <div class="flex items-center justify-between pt-4 border-t border-gray-700">
                                    @if($product->is_custom)
                                        <div>
                                            <p class="text-sm text-gray-400">ราคา</p>
                                            <p class="text-lg font-bold text-primary-400">สอบถามราคา</p>
                                        </div>
                                    @else
                                        <div>
                                            <p class="text-sm text-gray-400">ราคา</p>
                                            <p class="text-2xl font-bold text-white">
                                                ฿{{ number_format($product->price, 0) }}
                                                @if($product->original_price && $product->original_price > $product->price)
                                                    <span class="text-sm text-gray-500 line-through ml-1">฿{{ number_format($product->original_price, 0) }}</span>
                                                @endif
                                            </p>
                                        </div>
                                    @endif

                                    <div class="flex gap-2">
                                        <a href="{{ route('products.show', $product->slug) }}"
                                           class="px-4 py-2 bg-gray-700/50 hover:bg-gray-600/50 text-white text-sm font-medium rounded-lg transition-all border border-gray-600">
                                            รายละเอียด
                                        </a>
                                        @if(!$product->is_custom)
                                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                        class="px-4 py-2 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white text-sm font-medium rounded-lg transition-all">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($products->hasPages())
                    <div class="mt-12 flex justify-center">
                        <div class="bg-gray-800/50 rounded-xl p-2 border border-gray-700">
                            {{ $products->links() }}
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">ไม่พบสิ่งที่คุณต้องการ?</h2>
            <p class="text-gray-400 mb-8">เราให้บริการพัฒนาซอฟต์แวร์ตามความต้องการ ติดต่อเราเพื่อรับใบเสนอราคา</p>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('support.index') }}"
                   class="px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25">
                    ติดต่อเรา
                </a>
                <a href="{{ route('home') }}"
                   class="px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                    กลับหน้าแรก
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
