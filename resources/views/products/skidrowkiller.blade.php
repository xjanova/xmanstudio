@extends('layouts.app')

@section('title', $product->name . ' - XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-red-950 to-gray-900">
    <!-- Animated Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23DC2626\" fill-opacity=\"0.03\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>
        <!-- Floating Orbs -->
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-red-500/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-orange-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 right-1/3 w-64 h-64 bg-yellow-500/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <!-- Hero Section -->
    <section class="relative py-16 lg:py-24 overflow-hidden">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8 animate-fade-in">
                <a href="{{ route('products.index') }}" class="text-red-400 hover:text-red-300 flex items-center group">
                    <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    กลับไปรายการผลิตภัณฑ์
                </a>
            </nav>

            @if(session('success'))
                <div class="mb-6 bg-green-500/20 border border-green-500/50 text-green-300 px-4 py-3 rounded-xl backdrop-blur-sm animate-fade-in-up">
                    {{ session('success') }}
                </div>
            @endif

            {{-- License Status Banner --}}
            @if(isset($userLicense) && $userLicense)
                @if($userLicense->isValid())
                    @php $daysLeft = $userLicense->daysRemaining(); @endphp
                    @if($daysLeft <= 7 && $userLicense->license_type !== 'lifetime')
                        <div class="mb-6 bg-amber-500/20 border border-amber-500/50 text-amber-300 px-6 py-4 rounded-xl backdrop-blur-sm animate-fade-in-up">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <div>
                                        <p class="font-semibold">License ใกล้หมดอายุ!</p>
                                        <p class="text-sm text-amber-200">เหลืออีก {{ $daysLeft }} วัน ({{ $userLicense->expires_at->format('d/m/Y') }})</p>
                                    </div>
                                </div>
                                <a href="{{ route('customer.licenses.show', $userLicense) }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    ต่ออายุตอนนี้
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="mb-6 bg-green-500/20 border border-green-500/50 text-green-300 px-6 py-4 rounded-xl backdrop-blur-sm animate-fade-in-up">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <div>
                                        <p class="font-semibold">คุณมี License สำหรับผลิตภัณฑ์นี้แล้ว!</p>
                                        <p class="text-sm text-green-200">
                                            {{ $userLicense->license_type === 'lifetime' ? 'ใบอนุญาตตลอดชีพ' : 'หมดอายุ: ' . $userLicense->expires_at->format('d/m/Y') }}
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ route('customer.licenses.show', $userLicense) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    ดู License ของฉัน
                                </a>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="mb-6 bg-red-500/20 border border-red-500/50 text-red-300 px-6 py-4 rounded-xl backdrop-blur-sm animate-fade-in-up">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-semibold">License ของคุณหมดอายุแล้ว!</p>
                                    <p class="text-sm text-red-200">หมดอายุเมื่อ {{ $userLicense->expires_at->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            <a href="{{ route('customer.licenses.show', $userLicense) }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                ต่ออายุตอนนี้
                            </a>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Coming Soon Banner --}}
            @if($product->isComingSoon())
                <div class="mb-6 bg-orange-500/20 border border-orange-500/50 text-orange-300 px-6 py-4 rounded-xl backdrop-blur-sm animate-fade-in-up">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="font-semibold">Coming Soon!</p>
                                <p class="text-sm text-orange-200">
                                    @if($product->coming_soon_until)
                                        เปิดขายวันที่ {{ $product->coming_soon_until->format('d/m/Y H:i') }}
                                    @else
                                        ผลิตภัณฑ์นี้จะเปิดขายเร็วๆ นี้
                                    @endif
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('support.index') }}" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors">
                            แจ้งเตือนเมื่อเปิดขาย
                        </a>
                    </div>
                </div>
            @endif

            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <!-- Left: Product Info -->
                <div class="space-y-8 animate-fade-in-up">
                    @if($product->category)
                        <div class="inline-flex items-center px-4 py-2 bg-red-500/20 rounded-full text-red-300 text-sm backdrop-blur-sm border border-red-500/30">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                            {{ $product->category->name }}
                        </div>
                    @endif

                    <!-- Product Name with Gradient -->
                    <h1 class="text-4xl md:text-6xl font-black leading-tight">
                        <span class="text-white">Skidrow</span>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 via-orange-500 to-yellow-500"> Killer</span>
                    </h1>

                    @if($product->short_description)
                        <p class="text-xl text-gray-300 leading-relaxed">{{ $product->short_description }}</p>
                    @endif

                    <!-- Feature Tags -->
                    <div class="flex flex-wrap gap-3">
                        @if($product->requires_license)
                            <span class="px-4 py-2 bg-purple-500/20 text-purple-300 rounded-full text-sm border border-purple-500/30 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                                License Protected
                            </span>
                        @endif
                        <span class="px-4 py-2 bg-red-500/20 text-red-300 rounded-full text-sm border border-red-500/30 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Anti-Piracy Tool
                        </span>
                        <span class="px-4 py-2 bg-orange-500/20 text-orange-300 rounded-full text-sm border border-orange-500/30 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            High Performance
                        </span>
                    </div>

                    <!-- Price Card -->
                    <div class="bg-gray-800/60 rounded-2xl p-6 border border-gray-700/50 backdrop-blur-sm">
                        @if($product->is_custom)
                            <p class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-orange-400">สอบถามราคา</p>
                            <p class="text-gray-400 mt-2">ติดต่อเราเพื่อรับใบเสนอราคา</p>
                        @else
                            <div class="flex items-baseline gap-4">
                                <span class="text-4xl font-black text-white">฿{{ number_format($product->price, 0) }}</span>
                                @if($product->original_price && $product->original_price > $product->price)
                                    <span class="text-xl text-gray-500 line-through">฿{{ number_format($product->original_price, 0) }}</span>
                                    <span class="px-3 py-1 bg-red-500/20 text-red-400 text-sm rounded-full font-semibold">
                                        ลด {{ round((1 - $product->price / $product->original_price) * 100) }}%
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- CTA Buttons -->
                    @if($product->isComingSoon())
                        <div class="flex-1 px-8 py-4 bg-orange-600/50 text-orange-200 font-bold rounded-xl text-center cursor-not-allowed flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Coming Soon
                            @if($product->coming_soon_until)
                                - {{ $product->coming_soon_until->format('d/m/Y') }}
                            @endif
                        </div>
                    @elseif(!$product->is_custom)
                        <form action="{{ route('cart.add', $product) }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
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
                                    class="flex-1 px-8 py-4 bg-gradient-to-r from-red-600 via-orange-600 to-red-600 hover:from-red-700 hover:via-orange-700 hover:to-red-700 text-white font-bold rounded-xl transition-all transform hover:scale-[1.02] shadow-lg shadow-red-500/25 flex items-center justify-center gap-2 group">
                                <svg class="w-5 h-5 transform group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                เพิ่มลงตะกร้า
                            </button>
                        </form>
                    @else
                        <a href="{{ route('support.index') }}"
                           class="block w-full text-center px-8 py-4 bg-gradient-to-r from-red-600 via-orange-600 to-red-600 hover:from-red-700 hover:via-orange-700 hover:to-red-700 text-white font-bold rounded-xl transition-all transform hover:scale-[1.02] shadow-lg shadow-red-500/25">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                            ติดต่อสอบถาม
                        </a>
                    @endif
                </div>

                <!-- Right: Product Image -->
                <div class="relative animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="relative">
                        <!-- Glow Effect -->
                        <div class="absolute -inset-4 bg-gradient-to-r from-red-500/20 via-orange-500/20 to-yellow-500/20 rounded-3xl blur-2xl"></div>

                        <!-- Main Image Container -->
                        <div class="relative bg-gradient-to-br from-red-500/10 to-orange-500/10 rounded-2xl p-6 backdrop-blur-sm border border-red-500/20 overflow-hidden">
                            <!-- Corner Decorations -->
                            <div class="absolute top-0 left-0 w-20 h-20 border-t-2 border-l-2 border-red-500/50 rounded-tl-2xl"></div>
                            <div class="absolute bottom-0 right-0 w-20 h-20 border-b-2 border-r-2 border-orange-500/50 rounded-br-2xl"></div>

                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                     class="w-full rounded-xl shadow-2xl" id="main-image">
                            @else
                                <div class="w-full aspect-video bg-gray-800/50 rounded-xl flex items-center justify-center">
                                    <div class="text-center">
                                        <svg class="w-24 h-24 mx-auto text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                        <p class="text-gray-400 font-medium">{{ $product->name }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Screenshots Gallery --}}
                    @if($product->images && count($product->images) > 0)
                        <div class="grid grid-cols-4 gap-3 mt-4">
                            @if($product->image)
                                <div class="bg-gray-800/50 rounded-lg p-1 border-2 border-red-500 cursor-pointer transform hover:scale-105 transition-all"
                                     onclick="document.getElementById('main-image').src='{{ Storage::url($product->image) }}'">
                                    <img src="{{ Storage::url($product->image) }}" alt="Main"
                                         class="w-full rounded-lg aspect-video object-cover">
                                </div>
                            @endif
                            @foreach($product->images as $screenshot)
                                <div class="bg-gray-800/50 rounded-lg p-1 border-2 border-transparent hover:border-red-500/50 cursor-pointer transition-all transform hover:scale-105"
                                     onclick="document.getElementById('main-image').src='{{ Storage::url($screenshot) }}'">
                                    <img src="{{ Storage::url($screenshot) }}" alt="Screenshot"
                                         class="w-full rounded-lg aspect-video object-cover">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Description Section -->
    @if($product->description)
        <section class="relative py-16 bg-gray-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold text-white">รายละเอียดผลิตภัณฑ์</h2>
                </div>
                <div class="bg-gray-800/50 rounded-2xl p-8 border border-gray-700/50 backdrop-blur-sm">
                    <div class="prose prose-lg prose-invert max-w-none
                                prose-headings:text-white prose-headings:font-bold
                                prose-p:text-gray-300
                                prose-a:text-red-400 prose-a:no-underline hover:prose-a:underline
                                prose-strong:text-white
                                prose-ul:text-gray-300 prose-ol:text-gray-300
                                prose-li:marker:text-red-400
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
        <section class="relative py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl font-bold text-white mb-4">คุณสมบัติเด่น</h2>
                    <p class="text-gray-400 max-w-2xl mx-auto">เครื่องมือป้องกันการละเมิดลิขสิทธิ์ที่ทรงพลังที่สุด</p>
                </div>

                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @php
                        $features = is_array($product->features) ? $product->features : explode("\n", $product->features);
                        $featureColors = [
                            ['bg' => 'red', 'border' => 'red'],
                            ['bg' => 'orange', 'border' => 'orange'],
                            ['bg' => 'yellow', 'border' => 'yellow'],
                            ['bg' => 'green', 'border' => 'green'],
                            ['bg' => 'blue', 'border' => 'blue'],
                            ['bg' => 'purple', 'border' => 'purple'],
                        ];
                    @endphp
                    @foreach($features as $index => $feature)
                        @php $color = $featureColors[$index % count($featureColors)]; @endphp
                        @if(is_array($feature))
                            <div class="group bg-gray-800/50 rounded-2xl p-6 border border-gray-700/50 hover:border-{{ $color['border'] }}-500/50 transition-all duration-300 hover:transform hover:-translate-y-1 backdrop-blur-sm">
                                <div class="w-14 h-14 bg-{{ $color['bg'] }}-500/20 rounded-xl flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                                    <svg class="w-7 h-7 text-{{ $color['bg'] }}-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-white mb-3">{{ $feature['title'] ?? '' }}</h3>
                                @if(isset($feature['description']))
                                    <p class="text-gray-400 leading-relaxed">{{ $feature['description'] }}</p>
                                @endif
                            </div>
                        @elseif(trim($feature))
                            <div class="group bg-gray-800/50 rounded-2xl p-6 border border-gray-700/50 hover:border-{{ $color['border'] }}-500/50 transition-all duration-300 hover:transform hover:-translate-y-1 backdrop-blur-sm">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-{{ $color['bg'] }}-500/20 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                        <svg class="w-6 h-6 text-{{ $color['bg'] }}-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <span class="text-gray-300 text-lg leading-relaxed pt-2">{{ trim($feature) }}</span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- How It Works Section -->
    <section class="relative py-20 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">วิธีการใช้งาน</h2>
                <p class="text-gray-400 max-w-2xl mx-auto">เริ่มต้นใช้งาน {{ $product->name }} ได้ง่ายๆ เพียง 3 ขั้นตอน</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                <!-- Step 1 -->
                <div class="relative text-center group">
                    <div class="w-20 h-20 bg-gradient-to-br from-red-500 to-orange-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-red-500/25 group-hover:scale-110 transition-transform">
                        <span class="text-3xl font-black text-white">1</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">ดาวน์โหลดและติดตั้ง</h3>
                    <p class="text-gray-400">ดาวน์โหลดโปรแกรมจากเว็บไซต์และติดตั้งบนเครื่องของคุณ</p>
                    <!-- Connector Line -->
                    <div class="hidden md:block absolute top-10 left-[calc(100%+0.5rem)] w-[calc(100%-1rem)] h-0.5 bg-gradient-to-r from-red-500/50 to-orange-500/50"></div>
                </div>

                <!-- Step 2 -->
                <div class="relative text-center group">
                    <div class="w-20 h-20 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-orange-500/25 group-hover:scale-110 transition-transform">
                        <span class="text-3xl font-black text-white">2</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">เปิดใช้งาน License</h3>
                    <p class="text-gray-400">ใส่ License Key ที่ได้รับเพื่อเปิดใช้งานฟีเจอร์เต็มรูปแบบ</p>
                    <!-- Connector Line -->
                    <div class="hidden md:block absolute top-10 left-[calc(100%+0.5rem)] w-[calc(100%-1rem)] h-0.5 bg-gradient-to-r from-orange-500/50 to-yellow-500/50"></div>
                </div>

                <!-- Step 3 -->
                <div class="text-center group">
                    <div class="w-20 h-20 bg-gradient-to-br from-yellow-500 to-green-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-yellow-500/25 group-hover:scale-110 transition-transform">
                        <span class="text-3xl font-black text-white">3</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">เริ่มใช้งาน</h3>
                    <p class="text-gray-400">ตั้งค่าตามคู่มือและเริ่มต้นปกป้องซอฟต์แวร์ของคุณได้ทันที</p>
                </div>
            </div>
        </div>
    </section>

    <!-- System Requirements Section -->
    <section class="relative py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">ความต้องการระบบ</h2>
                <p class="text-gray-400">สเปคเครื่องที่แนะนำสำหรับการใช้งาน</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Minimum Requirements -->
                <div class="bg-gray-800/50 rounded-2xl p-8 border border-gray-700/50 backdrop-blur-sm">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">ขั้นต่ำ</h3>
                    </div>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3 text-gray-400">
                            <svg class="w-5 h-5 text-gray-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Windows 10 (64-bit)
                        </li>
                        <li class="flex items-center gap-3 text-gray-400">
                            <svg class="w-5 h-5 text-gray-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            RAM 4GB
                        </li>
                        <li class="flex items-center gap-3 text-gray-400">
                            <svg class="w-5 h-5 text-gray-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            พื้นที่ว่าง 500MB
                        </li>
                        <li class="flex items-center gap-3 text-gray-400">
                            <svg class="w-5 h-5 text-gray-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                            </svg>
                            การเชื่อมต่ออินเทอร์เน็ต
                        </li>
                    </ul>
                </div>

                <!-- Recommended Requirements -->
                <div class="bg-gradient-to-br from-red-500/10 to-orange-500/10 rounded-2xl p-8 border border-red-500/30 backdrop-blur-sm">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-white">แนะนำ</h3>
                    </div>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3 text-gray-300">
                            <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            Windows 11 (64-bit)
                        </li>
                        <li class="flex items-center gap-3 text-gray-300">
                            <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            RAM 8GB ขึ้นไป
                        </li>
                        <li class="flex items-center gap-3 text-gray-300">
                            <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            SSD Storage
                        </li>
                        <li class="flex items-center gap-3 text-gray-300">
                            <svg class="w-5 h-5 text-red-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
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
    <section class="relative py-20 bg-gray-900/50" id="download">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-white mb-4">ดาวน์โหลด {{ $product->name }}</h2>
            <p class="text-gray-400 mb-12 max-w-2xl mx-auto">ดาวน์โหลดโปรแกรมเวอร์ชันล่าสุดและเริ่มต้นปกป้องซอฟต์แวร์ของคุณ</p>

            <div class="bg-gradient-to-br from-gray-800/80 to-gray-900/80 rounded-3xl p-10 border border-gray-700/50 max-w-2xl mx-auto backdrop-blur-sm">
                <div class="flex items-center justify-between mb-8">
                    <div class="text-left">
                        <p class="text-gray-400 text-sm mb-1">เวอร์ชันล่าสุด</p>
                        <p class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-orange-400">v{{ $latestVersion->version }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-400 text-sm mb-1">ขนาดไฟล์</p>
                        <p class="text-xl font-semibold text-white">{{ $latestVersion->file_size_formatted }}</p>
                    </div>
                </div>

                @if($latestVersion->changelog)
                    <div class="text-left mb-8 bg-gray-700/30 rounded-xl p-5">
                        <p class="text-gray-400 text-sm mb-2 font-medium">Changelog</p>
                        <p class="text-gray-300 text-sm whitespace-pre-wrap">{{ Str::limit($latestVersion->changelog, 200) }}</p>
                    </div>
                @endif

                <a href="{{ route('download.page', ['slug' => $product->slug, 'version' => $latestVersion->version]) }}"
                   class="inline-flex items-center px-10 py-5 bg-gradient-to-r from-red-600 via-orange-600 to-red-600 hover:from-red-700 hover:via-orange-700 hover:to-red-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-red-500/25 group">
                    <svg class="w-6 h-6 mr-3 transform group-hover:translate-y-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    ดาวน์โหลดตอนนี้
                </a>
            </div>

            <p class="text-gray-500 text-sm mt-6">
                อัปเดตเมื่อ: {{ $latestVersion->synced_at?->format('d/m/Y') ?? 'N/A' }} |
                รองรับ Windows 10/11 (64-bit)
            </p>
        </div>
    </section>
    @endif

    <!-- License Info Section -->
    @if($product->requires_license)
    <section class="relative py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-white mb-4">การลงทะเบียน License</h2>
                <p class="text-gray-400 max-w-2xl mx-auto">ขั้นตอนการเปิดใช้งาน {{ $product->name }} อย่างง่าย</p>
            </div>

            <div class="grid md:grid-cols-2 gap-10 max-w-5xl mx-auto">
                <!-- Steps -->
                <div class="space-y-6">
                    <div class="flex items-start gap-4 group">
                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-red-500 to-orange-500 rounded-xl flex items-center justify-center text-white font-bold shadow-lg shadow-red-500/25 group-hover:scale-110 transition-transform">1</div>
                        <div class="pt-1">
                            <h3 class="text-lg font-bold text-white mb-1">ซื้อ License</h3>
                            <p class="text-gray-400">เลือกแพ็กเกจที่เหมาะสมและทำการชำระเงิน</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 group">
                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-orange-500 to-yellow-500 rounded-xl flex items-center justify-center text-white font-bold shadow-lg shadow-orange-500/25 group-hover:scale-110 transition-transform">2</div>
                        <div class="pt-1">
                            <h3 class="text-lg font-bold text-white mb-1">รับ License Key</h3>
                            <p class="text-gray-400">License Key จะถูกส่งไปยังอีเมลที่ลงทะเบียน</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 group">
                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-yellow-500 to-green-500 rounded-xl flex items-center justify-center text-white font-bold shadow-lg shadow-yellow-500/25 group-hover:scale-110 transition-transform">3</div>
                        <div class="pt-1">
                            <h3 class="text-lg font-bold text-white mb-1">เปิดโปรแกรม</h3>
                            <p class="text-gray-400">ดาวน์โหลดและติดตั้งโปรแกรมบนเครื่องของคุณ</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 group">
                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-green-500 to-blue-500 rounded-xl flex items-center justify-center text-white font-bold shadow-lg shadow-green-500/25 group-hover:scale-110 transition-transform">4</div>
                        <div class="pt-1">
                            <h3 class="text-lg font-bold text-white mb-1">Activate License</h3>
                            <p class="text-gray-400">ใส่ License Key ในโปรแกรมเพื่อเปิดใช้งานเต็มรูปแบบ</p>
                        </div>
                    </div>
                </div>

                <!-- License Info Card -->
                <div class="bg-gradient-to-br from-red-500/10 to-orange-500/10 rounded-2xl p-8 border border-red-500/30 backdrop-blur-sm">
                    <h3 class="text-2xl font-bold text-white mb-6">ข้อมูล License</h3>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-gray-300">License ผูกกับเครื่อง (Machine ID)</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-gray-300">ใช้งานได้ 1 เครื่องต่อ 1 License</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-gray-300">สามารถย้ายเครื่องได้ (ติดต่อ Support)</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <span class="text-gray-300">อัปเดตฟรีตลอดอายุ License</span>
                        </li>
                    </ul>

                    <div class="pt-6 border-t border-gray-700/50">
                        @if(!$product->is_custom)
                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit"
                                        class="w-full py-4 bg-gradient-to-r from-red-600 via-orange-600 to-red-600 hover:from-red-700 hover:via-orange-700 hover:to-red-700 text-white text-center font-bold rounded-xl transition-all transform hover:scale-[1.02] shadow-lg shadow-red-500/25">
                                    ซื้อ License - ฿{{ number_format($product->price, 0) }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('support.index') }}"
                               class="block w-full py-4 bg-gradient-to-r from-red-600 via-orange-600 to-red-600 hover:from-red-700 hover:via-orange-700 hover:to-red-700 text-white text-center font-bold rounded-xl transition-all">
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
        <section class="relative py-20 bg-gray-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-bold text-white text-center mb-12">ผลิตภัณฑ์ที่เกี่ยวข้อง</h2>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $related)
                        <a href="{{ route('products.show', $related->slug) }}"
                           class="group bg-gray-800/50 rounded-2xl overflow-hidden border border-gray-700/50 hover:border-red-500/50 transition-all hover:transform hover:-translate-y-2 backdrop-blur-sm">
                            <div class="aspect-video bg-gray-700 overflow-hidden">
                                @if($related->image)
                                    <img src="{{ Storage::url($related->image) }}" alt="{{ $related->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-red-500/20 to-orange-500/20">
                                        <svg class="w-12 h-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-5">
                                <h3 class="font-bold text-white group-hover:text-red-400 transition-colors mb-2">{{ $related->name }}</h3>
                                @if($related->short_description)
                                    <p class="text-gray-400 text-sm mb-3 line-clamp-2">{{ $related->short_description }}</p>
                                @endif
                                @if(!$related->is_custom)
                                    <p class="text-transparent bg-clip-text bg-gradient-to-r from-red-400 to-orange-400 font-bold text-lg">฿{{ number_format($related->price, 0) }}</p>
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
    <section class="relative py-24 overflow-hidden">
        <!-- Animated Background -->
        <div class="absolute inset-0 bg-gradient-to-r from-red-900/50 via-orange-900/50 to-red-900/50"></div>
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.03\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl md:text-5xl font-black text-white mb-6">พร้อมปกป้องซอฟต์แวร์ของคุณ?</h2>
            <p class="text-xl text-gray-300 mb-10 max-w-2xl mx-auto">
                @if($product->requires_license)
                    เลือกแพ็กเกจ License ที่เหมาะกับความต้องการของคุณ หรือติดต่อเราสำหรับข้อมูลเพิ่มเติม
                @else
                    ดาวน์โหลดและเริ่มต้นใช้งาน {{ $product->name }} ได้ทันที
                @endif
            </p>

            <div class="flex flex-wrap justify-center gap-4">
                @if(!$product->is_custom)
                    <form action="{{ route('cart.add', $product) }}" method="POST">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit"
                                class="px-10 py-5 bg-gradient-to-r from-red-600 via-orange-600 to-red-600 hover:from-red-700 hover:via-orange-700 hover:to-red-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-2xl shadow-red-500/30">
                            @if($product->requires_license)
                                ซื้อ License - ฿{{ number_format($product->price, 0) }}
                            @else
                                สั่งซื้อตอนนี้ - ฿{{ number_format($product->price, 0) }}
                            @endif
                        </button>
                    </form>
                @endif
                <a href="{{ route('support.index') }}"
                   class="px-10 py-5 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl border border-white/20 transition-all backdrop-blur-sm">
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
