@extends('layouts.admin')

@section('title', 'Preview: ' . $product->name)
@section('page-title', 'Preview: ' . $product->name)

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.products.index') }}" class="text-primary-600 hover:text-primary-700 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            กลับไปรายการผลิตภัณฑ์
        </a>
        <div class="flex gap-3">
            <a href="{{ route('admin.products.edit', $product) }}" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                แก้ไข
            </a>
            <a href="{{ route('products.show', $product->slug) }}" target="_blank" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                เปิดหน้าจริง ↗
            </a>
        </div>
    </div>
</div>

<!-- Preview Container -->
<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-6 py-3 flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <div class="flex space-x-1.5">
                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                <div class="w-3 h-3 rounded-full bg-green-500"></div>
            </div>
            <span class="ml-4 text-sm text-gray-400">Preview Mode</span>
        </div>
        <span class="text-xs bg-yellow-500/20 text-yellow-300 px-2 py-1 rounded">
            {{ $product->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
        </span>
    </div>

    <!-- Product Preview Content -->
    <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
        <!-- Hero Section -->
        <section class="relative py-12 overflow-hidden">
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-2 gap-12 items-center">
                    <!-- Left: Product Info -->
                    <div>
                        @if($product->category)
                        <div class="inline-flex items-center px-4 py-2 bg-primary-500/20 rounded-full text-primary-300 text-sm mb-6">
                            {{ $product->category->icon ?? '📦' }} {{ $product->category->name }}
                        </div>
                        @endif

                        <h1 class="text-4xl md:text-5xl font-black text-white mb-6">
                            {{ $product->name }}
                        </h1>

                        @if($product->short_description)
                        <p class="text-xl text-gray-300 mb-8">
                            {{ $product->short_description }}
                        </p>
                        @endif

                        <!-- Price & CTA -->
                        <div class="flex flex-wrap gap-4 items-center">
                            @if($product->is_custom)
                                <span class="text-2xl font-bold text-primary-400">สอบถามราคา</span>
                            @else
                                <span class="text-3xl font-bold text-white">฿{{ number_format($product->price, 0) }}</span>
                            @endif
                            <button disabled class="px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-bold rounded-xl opacity-75 cursor-not-allowed">
                                {{ $product->requires_license ? 'ซื้อ License' : 'สั่งซื้อตอนนี้' }}
                            </button>
                        </div>

                        <!-- Badges -->
                        <div class="flex flex-wrap gap-2 mt-6">
                            @if($product->requires_license)
                                <span class="px-3 py-1 bg-purple-500/20 text-purple-300 rounded-full text-sm">ต้องใช้ License</span>
                            @endif
                            @if($product->is_custom)
                                <span class="px-3 py-1 bg-yellow-500/20 text-yellow-300 rounded-full text-sm">Custom Product</span>
                            @endif
                        </div>
                    </div>

                    <!-- Right: Preview Image -->
                    <div class="relative">
                        <div class="bg-gradient-to-br from-primary-500/20 to-purple-500/20 rounded-2xl p-8 border border-primary-500/30">
                            <div class="aspect-video bg-gray-800 rounded-xl flex items-center justify-center overflow-hidden">
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="text-center">
                                        <svg class="w-24 h-24 mx-auto text-primary-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        <p class="text-gray-400">{{ $product->name }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        @if($product->features && count($product->features) > 0)
        <section class="py-12 bg-gray-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-white text-center mb-8">คุณสมบัติเด่น</h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($product->features as $index => $feature)
                        @if(trim($feature))
                        <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                            <div class="flex items-start">
                                <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
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

        <!-- Description Section -->
        @if($product->description)
        <section class="py-12">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-white text-center mb-8">รายละเอียด</h2>
                <div class="bg-gray-800/50 rounded-xl p-8 border border-gray-700">
                    <div class="prose prose-invert max-w-none text-gray-300">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                </div>
            </div>
        </section>
        @endif

        <!-- Gallery Section -->
        @if($product->images && count($product->images) > 0)
        <section class="py-12 bg-gray-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-white text-center mb-8">Gallery</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($product->images as $image)
                    <div class="aspect-video bg-gray-800 rounded-lg overflow-hidden">
                        <img src="{{ Storage::url($image) }}" alt="Gallery" class="w-full h-full object-cover">
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        <!-- Related Products -->
        @if($relatedProducts && $relatedProducts->count() > 0)
        <section class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-white text-center mb-8">ผลิตภัณฑ์ที่เกี่ยวข้อง</h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $related)
                    <div class="bg-gray-800/50 rounded-xl overflow-hidden border border-gray-700">
                        <div class="aspect-video bg-gray-700">
                            @if($related->image)
                                <img src="{{ Storage::url($related->image) }}" alt="{{ $related->name }}" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-white mb-2">{{ $related->name }}</h3>
                            @if(!$related->is_custom)
                                <p class="text-primary-400 font-bold">฿{{ number_format($related->price, 0) }}</p>
                            @else
                                <p class="text-gray-400">สอบถามราคา</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif
    </div>
</div>

<!-- Product Info Panel -->
<div class="mt-6 bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">ข้อมูลผลิตภัณฑ์</h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <p class="text-sm text-gray-500">SKU</p>
            <p class="font-medium">{{ $product->sku ?: '-' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">หมวดหมู่</p>
            <p class="font-medium">{{ $product->category->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Stock</p>
            <p class="font-medium">{{ $product->stock ?? 'ไม่จำกัด' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">สร้างเมื่อ</p>
            <p class="font-medium">{{ $product->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</div>
@endsection
