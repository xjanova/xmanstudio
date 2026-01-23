@extends($publicLayout ?? 'layouts.app')

@section('title', 'ผลิตภัณฑ์ - XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <!-- Hero Section -->
    <section class="relative py-12 sm:py-16 lg:py-20 overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%239C92AC\" fill-opacity=\"0.05\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="inline-flex items-center px-3 py-1.5 sm:px-4 sm:py-2 bg-primary-500/20 rounded-full text-primary-300 text-xs sm:text-sm mb-4 sm:mb-6 backdrop-blur-sm border border-primary-500/30">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-1.5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                ซอฟต์แวร์และเครื่องมือคุณภาพสูง
            </div>

            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-black text-white mb-4 sm:mb-6">
                ผลิตภัณฑ์<span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-purple-400">ของเรา</span>
            </h1>

            <p class="text-base sm:text-lg lg:text-xl text-gray-300 max-w-2xl mx-auto mb-6 sm:mb-8 px-4">
                ค้นพบซอฟต์แวร์และเครื่องมือพัฒนาที่จะช่วยยกระดับการทำงานของคุณ
            </p>

            <!-- Stats -->
            <div class="flex flex-wrap justify-center gap-6 sm:gap-8 mt-8 sm:mt-12">
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-bold text-white">{{ $products->total() }}+</div>
                    <div class="text-gray-400 text-xs sm:text-sm">ผลิตภัณฑ์</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-bold text-white">500+</div>
                    <div class="text-gray-400 text-xs sm:text-sm">ลูกค้าที่ไว้วางใจ</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl sm:text-3xl font-bold text-white">24/7</div>
                    <div class="text-gray-400 text-xs sm:text-sm">ซัพพอร์ต</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Filter (Optional) -->
    @if(isset($categories) && $categories->count() > 0)
    <section class="py-4 sm:py-6 border-b border-gray-700/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-center gap-2 sm:gap-3">
                <a href="{{ route('products.index') }}"
                   class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full text-xs sm:text-sm font-medium transition-all {{ !request('category') ? 'bg-primary-600 text-white' : 'bg-gray-800/50 text-gray-300 hover:bg-gray-700/50' }}">
                    ทั้งหมด
                </a>
                @foreach($categories as $category)
                    <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                       class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full text-xs sm:text-sm font-medium transition-all {{ request('category') == $category->slug ? 'bg-primary-600 text-white' : 'bg-gray-800/50 text-gray-300 hover:bg-gray-700/50' }}">
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Products Grid -->
    <section class="py-8 sm:py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 sm:mb-8 bg-green-500/20 border border-green-500/50 text-green-300 px-4 sm:px-6 py-3 sm:py-4 rounded-xl backdrop-blur-sm text-sm sm:text-base">
                    {{ session('success') }}
                </div>
            @endif

            @if($products->isEmpty())
                <div class="text-center py-16 sm:py-20">
                    <div class="w-20 h-20 sm:w-24 sm:h-24 bg-gray-800/50 rounded-full flex items-center justify-center mx-auto mb-4 sm:mb-6">
                        <svg class="w-10 h-10 sm:w-12 sm:h-12 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-white mb-2">ยังไม่มีผลิตภัณฑ์</h2>
                    <p class="text-gray-400 text-sm sm:text-base">กรุณากลับมาใหม่ภายหลัง</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
                    @foreach($products as $product)
                        <a href="{{ route('products.show', $product->slug) }}"
                           class="group relative bg-gray-800/50 rounded-2xl overflow-hidden border border-gray-700 hover:border-primary-500/50 transition-all duration-300 hover:transform hover:scale-[1.02] hover:shadow-xl hover:shadow-primary-500/10 backdrop-blur-sm block">
                            <!-- Product Image -->
                            <div class="relative aspect-video overflow-hidden">
                                @if($product->image)
                                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-primary-500/20 to-purple-500/20 flex items-center justify-center">
                                        <svg class="w-12 h-12 sm:w-16 sm:h-16 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                @endif

                                <!-- Badges -->
                                <div class="absolute top-3 left-3 sm:top-4 sm:left-4 flex flex-wrap gap-1.5 sm:gap-2">
                                    @php
                                        $productLicense = isset($userLicenses[$product->id]) ? $userLicenses[$product->id] : null;
                                    @endphp
                                    @if($product->requires_license && $productLicense)
                                        @if($productLicense->isValid())
                                            @php $remainingDays = $productLicense->daysRemaining(); @endphp
                                            @if($remainingDays <= 7 && $productLicense->license_type !== 'lifetime')
                                                <span class="px-2 py-0.5 sm:py-1 bg-amber-500/90 text-white text-[10px] sm:text-xs rounded-full backdrop-blur-sm flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                                    เหลือ {{ $remainingDays }} วัน
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 sm:py-1 bg-green-500/90 text-white text-[10px] sm:text-xs rounded-full backdrop-blur-sm flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                    {{ $productLicense->license_type === 'lifetime' ? 'ตลอดชีพ' : 'มี License' }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="px-2 py-0.5 sm:py-1 bg-red-500/90 text-white text-[10px] sm:text-xs rounded-full backdrop-blur-sm flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                                หมดอายุ
                                            </span>
                                        @endif
                                    @elseif($product->requires_license)
                                        <span class="px-2 py-0.5 sm:py-1 bg-purple-500/80 text-white text-[10px] sm:text-xs rounded-full backdrop-blur-sm">
                                            License
                                        </span>
                                    @endif
                                    @if($product->is_custom)
                                        <span class="px-2 py-0.5 sm:py-1 bg-yellow-500/80 text-white text-[10px] sm:text-xs rounded-full backdrop-blur-sm">
                                            Custom
                                        </span>
                                    @endif
                                    @if($product->isComingSoon())
                                        <span class="px-2 py-0.5 sm:py-1 bg-orange-500/90 text-white text-[10px] sm:text-xs rounded-full backdrop-blur-sm flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Coming Soon
                                        </span>
                                    @endif
                                </div>

                                <!-- Hover Overlay -->
                                <div class="absolute inset-0 bg-gradient-to-t from-gray-900/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-4">
                                    @php
                                        $userLicense = isset($userLicenses[$product->id]) ? $userLicenses[$product->id] : null;
                                        $hoverText = 'ดูรายละเอียด';
                                        $hoverClass = 'bg-primary-600';

                                        if ($product->requires_license && $userLicense) {
                                            if ($userLicense->isValid()) {
                                                $daysLeft = $userLicense->daysRemaining();
                                                if ($daysLeft <= 7 && $userLicense->license_type !== 'lifetime') {
                                                    $hoverText = 'ต่ออายุ License';
                                                    $hoverClass = 'bg-gradient-to-r from-amber-500 to-orange-500';
                                                } else {
                                                    $hoverText = 'ดู License ของคุณ';
                                                    $hoverClass = 'bg-gradient-to-r from-green-500 to-emerald-500';
                                                }
                                            } else {
                                                $hoverText = 'ต่ออายุ License';
                                                $hoverClass = 'bg-gradient-to-r from-red-500 to-pink-500';
                                            }
                                        } elseif ($product->requires_license) {
                                            $hoverText = 'ดูแพ็กเกจราคา';
                                            $hoverClass = 'bg-gradient-to-r from-purple-600 to-pink-600';
                                        }
                                    @endphp
                                    <span class="px-4 py-2 {{ $hoverClass }} text-white text-sm font-medium rounded-lg transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                                        {{ $hoverText }}
                                    </span>
                                </div>
                            </div>

                            <!-- Product Info -->
                            <div class="p-4 sm:p-6">
                                <h3 class="text-lg sm:text-xl font-bold text-white group-hover:text-primary-400 transition-colors mb-1.5 sm:mb-2 line-clamp-1">
                                    {{ $product->name }}
                                </h3>

                                @if($product->short_description)
                                    <p class="text-gray-400 text-sm mb-3 sm:mb-4 line-clamp-2">{{ $product->short_description }}</p>
                                @elseif($product->description)
                                    <p class="text-gray-400 text-sm mb-3 sm:mb-4 line-clamp-2">{{ Str::limit(strip_tags($product->description), 80) }}</p>
                                @endif

                                <!-- Features Preview -->
                                @if($product->features && is_array($product->features))
                                    <div class="flex flex-wrap gap-1.5 sm:gap-2 mb-3 sm:mb-4">
                                        @foreach(array_slice($product->features, 0, 2) as $feature)
                                            @if(is_string($feature))
                                                <span class="text-[10px] sm:text-xs text-gray-400 bg-gray-700/50 px-2 py-0.5 sm:py-1 rounded">
                                                    {{ Str::limit($feature, 15) }}
                                                </span>
                                            @endif
                                        @endforeach
                                        @if(count($product->features) > 2)
                                            <span class="text-[10px] sm:text-xs text-gray-500">+{{ count($product->features) - 2 }}</span>
                                        @endif
                                    </div>
                                @endif

                                <!-- Price & Actions -->
                                <div class="flex items-center justify-between pt-3 sm:pt-4 border-t border-gray-700">
                                    @if($product->is_custom)
                                        <div>
                                            <p class="text-xs text-gray-500">ราคา</p>
                                            <p class="text-base sm:text-lg font-bold text-primary-400">สอบถามราคา</p>
                                        </div>
                                    @else
                                        <div>
                                            <p class="text-xs text-gray-500">ราคา</p>
                                            <p class="text-xl sm:text-2xl font-bold text-white">
                                                ฿{{ number_format($product->price, 0) }}
                                                @if($product->original_price && $product->original_price > $product->price)
                                                    <span class="text-xs sm:text-sm text-gray-500 line-through ml-1">฿{{ number_format($product->original_price, 0) }}</span>
                                                @endif
                                            </p>
                                        </div>
                                    @endif

                                    @if($product->requires_license)
                                        @php
                                            $license = isset($userLicenses[$product->id]) ? $userLicenses[$product->id] : null;
                                        @endphp

                                        @if($license && $license->isValid())
                                            @php $daysLeft = $license->daysRemaining(); @endphp
                                            @if($daysLeft <= 7 && $license->license_type !== 'lifetime')
                                                {{-- License expiring soon: show renew icon --}}
                                                <div class="p-2.5 sm:p-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl shadow-lg shadow-amber-500/25">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </div>
                                            @else
                                                {{-- Valid license: show view/check icon --}}
                                                <div class="p-2.5 sm:p-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl shadow-lg shadow-green-500/25">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                        @elseif($license && $license->isExpired())
                                            {{-- Expired license: show renew icon --}}
                                            <div class="p-2.5 sm:p-3 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-xl shadow-lg shadow-red-500/25">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </div>
                                        @else
                                            {{-- No license: show key icon to view packages --}}
                                            <div class="p-2.5 sm:p-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl shadow-lg shadow-purple-500/25">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                                </svg>
                                            </div>
                                        @endif
                                    @elseif(!$product->is_custom)
                                        {{-- Regular products: add to cart --}}
                                        <div class="relative z-10" onclick="event.stopPropagation();">
                                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                                @csrf
                                                <button type="submit"
                                                        class="p-2.5 sm:p-3 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white rounded-xl transition-all hover:scale-110 shadow-lg shadow-primary-500/25">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        {{-- Custom products: contact icon --}}
                                        <div class="p-2.5 sm:p-3 bg-gray-700/50 text-gray-400 rounded-xl">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                @if($products->hasPages())
                    <div class="mt-8 sm:mt-12 flex justify-center">
                        <div class="bg-gray-800/50 rounded-xl p-2 border border-gray-700">
                            {{ $products->links() }}
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-12 sm:py-16 bg-gray-900/50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl sm:text-3xl font-bold text-white mb-3 sm:mb-4">ไม่พบสิ่งที่คุณต้องการ?</h2>
            <p class="text-gray-400 mb-6 sm:mb-8 text-sm sm:text-base">เราให้บริการพัฒนาซอฟต์แวร์ตามความต้องการ ติดต่อเราเพื่อรับใบเสนอราคา</p>

            <div class="flex flex-col sm:flex-row flex-wrap justify-center gap-3 sm:gap-4">
                <a href="{{ route('support.index') }}"
                   class="px-6 sm:px-8 py-3 sm:py-4 bg-gradient-to-r from-primary-600 to-purple-600 hover:from-primary-700 hover:to-purple-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-primary-500/25 text-sm sm:text-base">
                    ติดต่อเรา
                </a>
                <a href="{{ route('home') }}"
                   class="px-6 sm:px-8 py-3 sm:py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm text-sm sm:text-base">
                    กลับหน้าแรก
                </a>
            </div>
        </div>
    </section>
</div>
@endsection
