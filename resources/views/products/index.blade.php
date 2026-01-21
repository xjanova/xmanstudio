@extends('layouts.app')

@section('title', 'ผลิตภัณฑ์ - XMAN Studio')

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-r from-primary-600 to-primary-800 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">ผลิตภัณฑ์ของเรา</h1>
        <p class="text-xl text-primary-100 max-w-2xl mx-auto">
            ซอฟต์แวร์และเครื่องมือพัฒนาคุณภาพสูงจาก XMAN Studio
        </p>
    </div>
</section>

<!-- Products Grid -->
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if($products->isEmpty())
            <div class="text-center py-16">
                <svg class="w-24 h-24 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-700">ยังไม่มีผลิตภัณฑ์</h2>
                <p class="text-gray-500 mt-2">กรุณากลับมาใหม่ภายหลัง</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($products as $product)
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                        @if($product->image)
                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                                 class="w-full h-48 object-cover">
                        @else
                            <div class="w-full h-48 bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center">
                                <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                        @endif

                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $product->name }}</h3>
                            <p class="text-gray-600 mb-4 line-clamp-2">{{ $product->description }}</p>

                            @if($product->features && is_array($product->features))
                                <ul class="text-sm text-gray-500 mb-4 space-y-1">
                                    @foreach(array_slice($product->features, 0, 3) as $feature)
                                        <li class="flex items-center">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ trim($feature) }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            <div class="flex items-center justify-between">
                                @if($product->is_custom)
                                    <span class="text-lg font-bold text-primary-600">สอบถามราคา</span>
                                @else
                                    <span class="text-2xl font-bold text-gray-900">{{ number_format($product->price, 0) }} <span class="text-sm">บาท</span></span>
                                @endif

                                <div class="flex space-x-2">
                                    <a href="{{ route('products.show', $product->slug) }}"
                                       class="px-4 py-2 border border-primary-600 text-primary-600 rounded-lg hover:bg-primary-50 transition">
                                        ดูรายละเอียด
                                    </a>
                                    @if(!$product->is_custom)
                                        <form action="{{ route('cart.add', $product) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
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
                <div class="mt-12">
                    {{ $products->links() }}
                </div>
            @endif
        @endif
    </div>
</section>
@endsection
