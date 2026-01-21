@extends('layouts.customer')

@section('title', 'Downloads')
@section('page-title', 'Download Center')

@section('content')
<div class="mb-6">
    <p class="text-gray-600">Download software and resources for your active licenses and subscriptions.</p>
</div>

<!-- Licensed Products -->
@if($licensedProducts->count() > 0)
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Licensed Software</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($licensedProducts as $product)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            @if($product->image)
            <img src="{{ $product->image }}" alt="{{ $product->name }}" class="w-full h-40 object-cover">
            @else
            <div class="w-full h-40 bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                <svg class="w-16 h-16 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
            </div>
            @endif
            <div class="p-4">
                <h3 class="font-semibold text-gray-900">{{ $product->name }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ Str::limit($product->description, 80) }}</p>

                <div class="mt-4 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Version:</span>
                        <span class="font-medium">{{ $product->version ?? '1.0.0' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Platform:</span>
                        <span class="font-medium">{{ $product->platform ?? 'Windows' }}</span>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-100 space-y-2">
                    @if($product->slug === 'autotradex')
                    <a href="https://github.com/xjanova/autotradex/releases/latest" target="_blank"
                       class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download Latest
                    </a>
                    <a href="{{ route('products.show', 'autotradex') }}"
                       class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Documentation
                    </a>
                    @else
                    <button class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

<!-- Subscription Products -->
@if($rentalProducts->count() > 0)
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Subscription Resources</h2>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="divide-y divide-gray-200">
            @foreach($rentalProducts as $rental)
            <div class="p-4 hover:bg-gray-50">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-medium text-gray-900">{{ $rental->rentalPackage->display_name }}</h3>
                        <p class="text-sm text-gray-500">
                            Expires: {{ $rental->expires_at->format('d M Y') }}
                            <span class="text-gray-400">({{ $rental->expires_at->diffForHumans() }})</span>
                        </p>
                    </div>
                    <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">Active</span>
                </div>

                <div class="mt-3 flex gap-2">
                    <button class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-sm flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Documentation
                    </button>
                    <button class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-sm flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        API Keys
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<!-- No Downloads Available -->
@if($licensedProducts->count() === 0 && $rentalProducts->count() === 0)
<div class="bg-white rounded-xl shadow-sm p-12 text-center">
    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
    </svg>
    <h3 class="text-lg font-medium text-gray-900">No downloads available</h3>
    <p class="text-gray-500 mt-1">Purchase a product or subscribe to a package to access downloads</p>
    <div class="mt-6 flex justify-center gap-4">
        <a href="{{ route('products.index') }}" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            Browse Products
        </a>
        <a href="{{ route('rental.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            View Subscriptions
        </a>
    </div>
</div>
@endif

<!-- Download Guidelines -->
<div class="mt-8 bg-blue-50 rounded-xl p-6">
    <h3 class="font-semibold text-blue-900 mb-2">Download Guidelines</h3>
    <ul class="text-sm text-blue-800 space-y-1">
        <li>• Downloads are available only for active licenses and subscriptions</li>
        <li>• Make sure to download the correct version for your operating system</li>
        <li>• Keep your license key ready for software activation</li>
        <li>• Contact support if you encounter any download issues</li>
    </ul>
</div>
@endsection
