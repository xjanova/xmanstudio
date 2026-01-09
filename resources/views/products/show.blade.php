@extends('layouts.app')

@section('title', $product->name . ' - XMAN Studio')

@section('content')
<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <nav class="mb-8">
        <a href="{{ route('products.index') }}" class="text-primary-600 hover:underline">&larr; กลับไปรายการผลิตภัณฑ์</a>
    </nav>

    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Product Images -->
        <div>
            @if($product->image)
                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                     class="w-full rounded-xl shadow-lg mb-4" id="main-image">
            @else
                <div class="w-full aspect-square bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-32 h-32 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            @endif

            {{-- Screenshots Gallery --}}
            @if($product->images && count($product->images) > 0)
                <div class="grid grid-cols-3 gap-3">
                    @foreach($product->images as $screenshot)
                        <img src="{{ Storage::url($screenshot) }}" alt="Screenshot"
                             class="w-full rounded-lg cursor-pointer hover:opacity-80 transition screenshot-thumb"
                             onclick="document.getElementById('main-image').src='{{ Storage::url($screenshot) }}'">
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Product Info -->
        <div>
            @if($product->category)
                <span class="inline-block px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm mb-4">
                    {{ $product->category->name }}
                </span>
            @endif

            <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ $product->name }}</h1>

            @if($product->short_description)
                <p class="text-xl text-gray-600 mb-4">{{ $product->short_description }}</p>
            @endif

            @if($product->requires_license)
                <span class="inline-flex items-center px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm mb-4">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    ต้องใช้ License Key
                </span>
            @endif

            <div class="text-lg text-gray-600 mb-6 prose prose-lg max-w-none">{!! $product->description !!}</div>

            <!-- Price -->
            <div class="mb-8">
                @if($product->is_custom)
                    <p class="text-3xl font-bold text-primary-600">สอบถามราคา</p>
                    <p class="text-gray-500 mt-1">ติดต่อเราเพื่อรับใบเสนอราคา</p>
                @else
                    <p class="text-4xl font-bold text-gray-900">{{ number_format($product->price, 0) }} <span class="text-lg">บาท</span></p>
                @endif
            </div>

            <!-- Add to Cart -->
            @if(!$product->is_custom)
                <form action="{{ route('cart.add', $product) }}" method="POST" class="flex items-center space-x-4 mb-8">
                    @csrf
                    <div class="flex items-center border border-gray-300 rounded-lg">
                        <button type="button" onclick="decrementQty()" class="px-4 py-2 text-gray-600 hover:text-gray-900">-</button>
                        <input type="number" name="quantity" id="quantity" value="1" min="1" max="99"
                               class="w-16 text-center border-x border-gray-300 py-2">
                        <button type="button" onclick="incrementQty()" class="px-4 py-2 text-gray-600 hover:text-gray-900">+</button>
                    </div>
                    <button type="submit"
                            class="flex-1 px-8 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold text-lg">
                        เพิ่มลงตะกร้า
                    </button>
                </form>
            @else
                <a href="{{ route('support.index') }}"
                   class="inline-block w-full text-center px-8 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold text-lg mb-8">
                    ติดต่อสอบถาม
                </a>
            @endif

            <!-- Features -->
            @if($product->features)
                <div class="border-t pt-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">คุณสมบัติ</h2>
                    <ul class="space-y-3">
                        @php
                            $features = is_array($product->features) ? $product->features : explode("\n", $product->features);
                        @endphp
                        @foreach($features as $feature)
                            @if(is_array($feature))
                                {{-- Feature is an object with icon, title, description --}}
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <div>
                                        <span class="font-medium text-gray-900">{{ $feature['title'] ?? '' }}</span>
                                        @if(isset($feature['description']))
                                            <p class="text-gray-600 text-sm">{{ $feature['description'] }}</p>
                                        @endif
                                    </div>
                                </li>
                            @elseif(trim($feature))
                                {{-- Feature is a simple string --}}
                                <li class="flex items-start">
                                    <svg class="w-5 h-5 text-green-500 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-600">{{ trim($feature) }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
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
