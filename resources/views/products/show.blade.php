@extends('layouts.app')

@section('title', $product->name . ' - XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-16 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%239C92AC\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-primary-400 hover:text-primary-300 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับไปรายการผลิตภัณฑ์
                </a>
            </nav>

            @if(session('success'))
                <div class="mb-6 bg-green-500/20 border border-green-500/50 text-green-300 px-4 py-3 rounded-xl backdrop-blur-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid lg:grid-cols-2 gap-12 items-start">
                <!-- Left: Product Images -->
                <div class="space-y-4">
                    <div class="bg-gradient-to-br from-primary-500/20 to-purple-500/20 rounded-2xl p-4 backdrop-blur-sm border border-primary-500/30">
                        @if($product->image)
                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                 class="w-full rounded-xl shadow-2xl" id="main-image">
                        @else
                            <div class="w-full aspect-video bg-gray-800 rounded-xl flex items-center justify-center">
                                <div class="text-center">
                                    <svg class="w-24 h-24 mx-auto text-primary-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    <p class="text-gray-400">{{ $product->name }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Screenshots Gallery --}}
                    @if($product->images && count($product->images) > 0)
                        <div class="grid grid-cols-4 gap-3">
                            @if($product->image)
                                <div class="bg-gray-800/50 rounded-lg p-1 border-2 border-primary-500 cursor-pointer"
                                     onclick="document.getElementById('main-image').src='{{ Storage::url($product->image) }}'">
                                    <img src="{{ Storage::url($product->image) }}" alt="Main"
                                         class="w-full rounded-lg aspect-video object-cover">
                                </div>
                            @endif
                            @foreach($product->images as $screenshot)
                                <div class="bg-gray-800/50 rounded-lg p-1 border-2 border-transparent hover:border-primary-500/50 cursor-pointer transition-all"
                                     onclick="document.getElementById('main-image').src='{{ Storage::url($screenshot) }}'">
                                    <img src="{{ Storage::url($screenshot) }}" alt="Screenshot"
                                         class="w-full rounded-lg aspect-video object-cover">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Right: Product Info -->
                <div>
                    @if($product->category)
                        <div class="inline-flex items-center px-4 py-2 bg-primary-500/20 rounded-full text-primary-300 text-sm mb-6 backdrop-blur-sm border border-primary-500/30">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            {{ $product->category->name }}
                        </div>
                    @endif

                    <h1 class="text-4xl md:text-5xl font-black text-white mb-4">{{ $product->name }}</h1>

                    @if($product->short_description)
                        <p class="text-xl text-gray-300 mb-6">{{ $product->short_description }}</p>
                    @endif

                    <!-- Tags -->
                    <div class="flex flex-wrap gap-3 mb-6">
                        @if($product->requires_license)
                            <span class="px-3 py-1 bg-purple-500/20 text-purple-300 rounded-full text-sm border border-purple-500/30">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                License Key
                            </span>
                        @endif
                        @if($product->is_custom)
                            <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-sm border border-yellow-500/30">
                                Custom Order
                            </span>
                        @endif
                        @if($product->stock !== null && $product->stock > 0)
                            <span class="px-3 py-1 bg-green-500/20 text-green-300 rounded-full text-sm border border-green-500/30">
                                In Stock
                            </span>
                        @endif
                    </div>

                    <!-- Price Card -->
                    <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 mb-6">
                        @if($product->is_custom)
                            <p class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-purple-400">สอบถามราคา</p>
                            <p class="text-gray-400 mt-1">ติดต่อเราเพื่อรับใบเสนอราคา</p>
                        @else
                            <div class="flex items-baseline">
                                <span class="text-4xl font-black text-white">฿{{ number_format($product->price, 0) }}</span>
                                @if($product->original_price && $product->original_price > $product->price)
                                    <span class="ml-3 text-xl text-gray-500 line-through">฿{{ number_format($product->original_price, 0) }}</span>
                                    <span class="ml-2 px-2 py-1 bg-red-500/20 text-red-400 text-sm rounded-full">
                                        -{{ round((1 - $product->price / $product->original_price) * 100) }}%
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Add to Cart -->
                    @if(!$product->is_custom)
                        <form action="{{ route('cart.add', $product) }}" method="POST" class="flex items-center gap-4 mb-8">
                            @csrf
                            <div class="flex items-center bg-gray-800 border border-gray-700 rounded-xl overflow-hidden">
                                <button type="button" onclick="decrementQty()" class="px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-700 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                    </svg>
                                </button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" max="99"
                                       class="w-16 text-center bg-transparent text-white border-x border-gray-700 py-3 focus:outline-none">
                                <button type="button" onclick="incrementQty()" class="px-4 py-3 text-gray-400 hover:text-white hover:bg-gray-700 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                            </div>
                            <button type="submit"
                                    class="flex-1 px-8 py-3 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-[1.02] shadow-lg shadow-primary-500/25">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                เพิ่มลงตะกร้า
                            </button>
                        </form>
                    @else
                        <a href="{{ route('support.index') }}"
                           class="block w-full text-center px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-[1.02] shadow-lg shadow-primary-500/25 mb-8">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            ติดต่อสอบถาม
                        </a>
                    @endif

                    <!-- Quick Info -->
                    <div class="grid grid-cols-2 gap-4">
                        @if($product->sku)
                            <div class="bg-gray-800/30 rounded-lg p-4 border border-gray-700/50">
                                <p class="text-gray-400 text-sm">SKU</p>
                                <p class="text-white font-medium">{{ $product->sku }}</p>
                            </div>
                        @endif
                        @if($product->stock !== null)
                            <div class="bg-gray-800/30 rounded-lg p-4 border border-gray-700/50">
                                <p class="text-gray-400 text-sm">Stock</p>
                                <p class="text-white font-medium">{{ $product->stock > 0 ? $product->stock . ' units' : 'Out of stock' }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Description Section -->
    @if($product->description)
        <section class="py-12 bg-gray-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    รายละเอียดผลิตภัณฑ์
                </h2>
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <div class="prose prose-lg prose-invert max-w-none
                                prose-headings:text-white prose-headings:font-bold
                                prose-p:text-gray-300
                                prose-a:text-primary-400 prose-a:no-underline hover:prose-a:underline
                                prose-strong:text-white
                                prose-ul:text-gray-300 prose-ol:text-gray-300
                                prose-li:marker:text-primary-400
                                prose-table:border-gray-700
                                prose-th:bg-gray-700/50 prose-th:text-white prose-th:p-3
                                prose-td:border-gray-700 prose-td:p-3">
                        {!! $product->description !!}
                    </div>
                </div>
            </div>
        </section>
    @endif

    <!-- Features Section -->
    @if($product->features && count($product->features) > 0)
        <section class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-white mb-8 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    คุณสมบัติ
                </h2>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @php
                        $features = is_array($product->features) ? $product->features : explode("\n", $product->features);
                    @endphp
                    @foreach($features as $index => $feature)
                        @if(is_array($feature))
                            <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-primary-500/50 transition-all">
                                <div class="w-10 h-10 bg-primary-500/20 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="w-5 h-5 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-white mb-2">{{ $feature['title'] ?? '' }}</h3>
                                @if(isset($feature['description']))
                                    <p class="text-gray-400">{{ $feature['description'] }}</p>
                                @endif
                            </div>
                        @elseif(trim($feature))
                            <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-primary-500/50 transition-all">
                                <div class="flex items-start">
                                    <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                        <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <span class="text-gray-300">{{ trim($feature) }}</span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Related Products -->
    @if($relatedProducts && $relatedProducts->count() > 0)
        <section class="py-12 bg-gray-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-white mb-8 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    ผลิตภัณฑ์ที่เกี่ยวข้อง
                </h2>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $related)
                        <a href="{{ route('products.show', $related->slug) }}"
                           class="group bg-gray-800/50 rounded-xl overflow-hidden border border-gray-700 hover:border-primary-500/50 transition-all hover:transform hover:scale-[1.02]">
                            <div class="aspect-video bg-gray-700 overflow-hidden">
                                @if($related->image)
                                    <img src="{{ Storage::url($related->image) }}" alt="{{ $related->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-12 h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-white group-hover:text-primary-400 transition-colors mb-2">{{ $related->name }}</h3>
                                @if(!$related->is_custom)
                                    <p class="text-primary-400 font-bold">฿{{ number_format($related->price, 0) }}</p>
                                @else
                                    <p class="text-gray-400">สอบถามราคา</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- CTA Section -->
    <section class="py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl font-bold text-white mb-4">มีคำถามเกี่ยวกับผลิตภัณฑ์นี้?</h2>
            <p class="text-gray-400 mb-6">ติดต่อทีมงานของเราเพื่อรับคำปรึกษาและข้อมูลเพิ่มเติม</p>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('support.index') }}"
                   class="px-6 py-3 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25">
                    ติดต่อเรา
                </a>
                <a href="{{ route('products.index') }}"
                   class="px-6 py-3 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all">
                    ดูผลิตภัณฑ์อื่นๆ
                </a>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
function incrementQty() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.max);
    const current = parseInt(input.value);
    if (current < max) {
        input.value = current + 1;
    }
}

function decrementQty() {
    const input = document.getElementById('quantity');
    const min = parseInt(input.min);
    const current = parseInt(input.value);
    if (current > min) {
        input.value = current - 1;
    }
}
</script>
@endpush
@endsection
