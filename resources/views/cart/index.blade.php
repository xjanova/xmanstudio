@extends($publicLayout ?? 'layouts.app')

@section('title', 'ตะกร้าสินค้า - XMAN Studio')

@section('content')
<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">ตะกร้าสินค้า</h1>

    @if(session('success'))
        <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if($cart->items->isEmpty())
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="w-24 h-24 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <h2 class="text-xl font-semibold text-gray-700 mb-4">ตะกร้าว่างเปล่า</h2>
            <p class="text-gray-500 mb-6">ยังไม่มีสินค้าในตะกร้า เลือกซื้อสินค้าได้เลย</p>
            <a href="{{ route('products.index') }}"
               class="inline-block px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                ดูสินค้าทั้งหมด
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สินค้า</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ราคา</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวน</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รวม</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($cart->items as $item)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            @if($item->product->image)
                                                <img src="{{ Storage::url($item->product->image) }}" alt="{{ $item->product->name }}"
                                                     class="w-16 h-16 rounded-lg object-cover mr-4">
                                            @else
                                                <div class="w-16 h-16 rounded-lg bg-gray-200 flex items-center justify-center mr-4">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $item->product->name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                        {{ number_format($item->price, 2) }} บาท
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form action="{{ route('cart.update', $item) }}" method="POST" class="flex items-center">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="99"
                                                   class="w-20 px-2 py-1 border border-gray-300 rounded text-center"
                                                   onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">
                                        {{ number_format($item->price * $item->quantity, 2) }} บาท
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <form action="{{ route('cart.remove', $item) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-between">
                    <a href="{{ route('products.index') }}" class="text-primary-600 hover:underline">
                        &larr; เลือกสินค้าเพิ่ม
                    </a>
                    <form action="{{ route('cart.index') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">
                            ล้างตะกร้า
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">สรุปคำสั่งซื้อ</h2>

                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-gray-600">
                            <span>รวมสินค้า ({{ $cart->items->sum('quantity') }} ชิ้น)</span>
                            <span>{{ number_format($cart->total, 2) }} บาท</span>
                        </div>
                        <div class="border-t pt-3 flex justify-between text-lg font-bold text-gray-900">
                            <span>ยอดรวมทั้งหมด</span>
                            <span>{{ number_format($cart->total, 2) }} บาท</span>
                        </div>
                    </div>

                    <a href="{{ route('checkout') }}"
                       class="block w-full text-center px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-semibold">
                        ดำเนินการสั่งซื้อ
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
