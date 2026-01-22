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
                <h2 class="text-3xl font-bold text-white text-center mb-12">คุณสมบัติเด่น</h2>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @php
                        $features = is_array($product->features) ? $product->features : explode("\n", $product->features);
                        $featureIcons = ['purple', 'green', 'blue', 'yellow', 'pink', 'cyan', 'red', 'orange'];
                    @endphp
                    @foreach($features as $index => $feature)
                        @php $colorClass = $featureIcons[$index % count($featureIcons)]; @endphp
                        @if(is_array($feature))
                            <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-primary-500/50 transition-all">
                                <div class="w-12 h-12 bg-{{ $colorClass }}-500/20 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-{{ $colorClass }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <div class="w-10 h-10 bg-{{ $colorClass }}-500/20 rounded-lg flex items-center justify-center mr-4 flex-shrink-0">
                                        <svg class="w-5 h-5 text-{{ $colorClass }}-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <span class="text-gray-300 text-lg">{{ trim($feature) }}</span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- How It Works Section -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">หลักการทำงาน</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">ขั้นตอนการใช้งาน {{ $product->name }} อย่างง่าย</p>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-primary-500">
                        <span class="text-2xl font-bold text-primary-400">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">ดาวน์โหลดและติดตั้ง</h3>
                    <p class="text-gray-400">ดาวน์โหลดโปรแกรมจากเว็บไซต์และติดตั้งบนเครื่องของคุณ</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-primary-500">
                        <span class="text-2xl font-bold text-primary-400">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">เปิดใช้งาน License</h3>
                    <p class="text-gray-400">ใส่ License Key ที่ได้รับเพื่อเปิดใช้งานฟีเจอร์เต็มรูปแบบ</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-primary-500">
                        <span class="text-2xl font-bold text-primary-400">3</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">เริ่มใช้งาน</h3>
                    <p class="text-gray-400">ตั้งค่าตามคู่มือและเริ่มต้นใช้งานได้ทันที</p>
                </div>
            </div>
        </div>
    </section>

    <!-- System Requirements Section -->
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">ความต้องการระบบ</h2>

            <div class="grid md:grid-cols-2 gap-8 max-w-3xl mx-auto">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        ขั้นต่ำ
                    </h3>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Windows 10 (64-bit)
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            RAM 4GB
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            พื้นที่ว่าง 500MB
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            การเชื่อมต่ออินเทอร์เน็ต
                        </li>
                    </ul>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-primary-500/50">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-primary-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        แนะนำ
                    </h3>
                    <ul class="space-y-2 text-gray-300">
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-primary-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Windows 11 (64-bit)
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-primary-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            RAM 8GB ขึ้นไป
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-primary-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            SSD Storage
                        </li>
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-primary-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            อินเทอร์เน็ตความเร็วสูง
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Download Section -->
    @php
        $latestVersion = $product->latestVersion();
    @endphp
    @if($latestVersion)
    <section class="py-16" id="download">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">ดาวน์โหลด {{ $product->name }}</h2>
            <p class="text-gray-400 mb-8 max-w-2xl mx-auto">ดาวน์โหลดโปรแกรมเวอร์ชันล่าสุดและเริ่มต้นใช้งานได้ทันที</p>

            <div class="bg-gray-800/50 rounded-2xl p-8 border border-gray-700 max-w-xl mx-auto mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div class="text-left">
                        <p class="text-gray-400 text-sm">เวอร์ชันล่าสุด</p>
                        <p class="text-2xl font-bold text-white">v{{ $latestVersion->version }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-400 text-sm">ขนาดไฟล์</p>
                        <p class="text-lg font-semibold text-white">{{ $latestVersion->file_size_formatted }}</p>
                    </div>
                </div>

                @if($latestVersion->changelog)
                    <div class="text-left mb-6 bg-gray-700/30 rounded-lg p-4">
                        <p class="text-gray-400 text-sm mb-2">Changelog</p>
                        <p class="text-gray-300 text-sm whitespace-pre-wrap">{{ Str::limit($latestVersion->changelog, 200) }}</p>
                    </div>
                @endif

                <a href="{{ route('download.page', ['slug' => $product->slug, 'version' => $latestVersion->version]) }}"
                   class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    ดาวน์โหลดตอนนี้
                </a>
            </div>

            <p class="text-gray-500 text-sm">
                อัปเดตเมื่อ: {{ $latestVersion->synced_at?->format('d/m/Y') ?? 'N/A' }} |
                รองรับ Windows 10/11 (64-bit)
            </p>
        </div>
    </section>
    @endif

    <!-- License Info Section -->
    @if($product->requires_license)
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">การลงทะเบียน License</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">ขั้นตอนการเปิดใช้งาน {{ $product->name }}</p>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Steps -->
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center text-white font-bold">1</div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-white">ซื้อ License</h3>
                            <p class="text-gray-400">เลือกแพ็กเกจที่เหมาะสมและทำการชำระเงิน</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center text-white font-bold">2</div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-white">รับ License Key</h3>
                            <p class="text-gray-400">License Key จะถูกส่งไปยังอีเมลที่ลงทะเบียน</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center text-white font-bold">3</div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-white">เปิดโปรแกรม</h3>
                            <p class="text-gray-400">ดาวน์โหลดและติดตั้งโปรแกรมบนเครื่องของคุณ</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-primary-500 rounded-full flex items-center justify-center text-white font-bold">4</div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-white">Activate License</h3>
                            <p class="text-gray-400">ใส่ License Key ในโปรแกรมเพื่อเปิดใช้งานเต็มรูปแบบ</p>
                        </div>
                    </div>
                </div>

                <!-- License Info Card -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-xl font-bold text-white mb-4">ข้อมูล License</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            License ผูกกับเครื่อง (Machine ID)
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            ใช้งานได้ 1 เครื่องต่อ 1 License
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            สามารถย้ายเครื่องได้ (ติดต่อ Support)
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-400 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            อัปเดตฟรีตลอดอายุ License
                        </li>
                    </ul>

                    <div class="mt-6 pt-6 border-t border-gray-700">
                        @if(!$product->is_custom)
                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit"
                                        class="block w-full py-3 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white text-center font-bold rounded-xl transition-all">
                                    ซื้อ License - ฿{{ number_format($product->price, 0) }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('support.index') }}"
                               class="block w-full py-3 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white text-center font-bold rounded-xl transition-all">
                                ติดต่อสอบถามราคา
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Related Products -->
    @if($relatedProducts && $relatedProducts->count() > 0)
        <section class="py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-white text-center mb-12">ผลิตภัณฑ์ที่เกี่ยวข้อง</h2>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $related)
                        <a href="{{ route('products.show', $related->slug) }}"
                           class="group bg-gray-800/50 rounded-xl overflow-hidden border border-gray-700 hover:border-primary-500/50 transition-all hover:transform hover:scale-[1.02]">
                            <div class="aspect-video bg-gray-700 overflow-hidden">
                                @if($related->image)
                                    <img src="{{ Storage::url($related->image) }}" alt="{{ $related->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-500/20 to-purple-500/20">
                                        <svg class="w-12 h-12 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-white group-hover:text-primary-400 transition-colors mb-2">{{ $related->name }}</h3>
                                @if($related->short_description)
                                    <p class="text-gray-400 text-sm mb-2 line-clamp-2">{{ $related->short_description }}</p>
                                @endif
                                @if(!$related->is_custom)
                                    <p class="text-primary-400 font-bold text-lg">฿{{ number_format($related->price, 0) }}</p>
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
    <section class="py-16 bg-gray-900/50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">พร้อมเริ่มต้นใช้งาน?</h2>
            <p class="text-gray-400 mb-8 max-w-2xl mx-auto">
                @if($product->requires_license)
                    เลือกแพ็กเกจ License ที่เหมาะกับความต้องการของคุณ หรือติดต่อเราสำหรับข้อมูลเพิ่มเติม
                @else
                    ดาวน์โหลดและเริ่มต้นใช้งาน {{ $product->name }} ได้ทันที
                @endif
            </p>

            <div class="flex flex-wrap justify-center gap-4">
                @if(!$product->is_custom)
                    <form action="{{ route('cart.add', $product) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit"
                                class="px-8 py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25">
                            @if($product->requires_license)
                                ซื้อ License - ฿{{ number_format($product->price, 0) }}
                            @else
                                สั่งซื้อตอนนี้ - ฿{{ number_format($product->price, 0) }}
                            @endif
                        </button>
                    </form>
                @endif
                <a href="{{ route('support.index') }}"
                   class="px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                    ติดต่อสอบถาม
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
