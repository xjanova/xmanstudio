@extends($publicLayout ?? 'layouts.app')

@section('title', $product->name . ' - XMAN Studio')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-emerald-950 to-gray-900">
    <!-- Animated Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%2310B981&quot; fill-opacity=&quot;0.03&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')]"></div>
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-teal-500/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 right-1/3 w-64 h-64 bg-green-500/5 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <!-- Hero Section -->
    <section class="relative py-16 lg:py-24 overflow-hidden">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <a href="{{ route('products.index') }}" class="text-emerald-400 hover:text-emerald-300 flex items-center group">
                    <svg class="w-4 h-4 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ __('Back to Products') }}
                </a>
            </nav>

            @if(session('success'))
                <div class="mb-6 bg-green-500/20 border border-green-500/50 text-green-300 px-4 py-3 rounded-xl backdrop-blur-sm">
                    {{ session('success') }}
                </div>
            @endif

            {{-- License Status Banner --}}
            @if(isset($userLicense) && $userLicense)
                @if($userLicense->isValid())
                    @php $daysLeft = $userLicense->daysRemaining(); @endphp
                    @if($daysLeft <= 7 && $userLicense->license_type !== 'lifetime')
                        <div class="mb-6 bg-amber-500/20 border border-amber-500/50 text-amber-300 px-6 py-4 rounded-xl backdrop-blur-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <div>
                                        <p class="font-semibold">License expires soon!</p>
                                        <p class="text-sm text-amber-200">{{ $daysLeft }} days remaining ({{ $userLicense->expires_at->format('d/m/Y') }})</p>
                                    </div>
                                </div>
                                <a href="{{ route('customer.licenses.show', $userLicense) }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    Renew Now
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="mb-6 bg-green-500/20 border border-green-500/50 text-green-300 px-6 py-4 rounded-xl backdrop-blur-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <div>
                                        <p class="font-semibold">You have a valid license!</p>
                                        <p class="text-sm text-green-200">
                                            {{ $userLicense->license_type === 'lifetime' ? 'Lifetime License' : 'Expires: ' . $userLicense->expires_at->format('d/m/Y') }}
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ route('customer.licenses.show', $userLicense) }}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    View My License
                                </a>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="mb-6 bg-red-500/20 border border-red-500/50 text-red-300 px-6 py-4 rounded-xl backdrop-blur-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div class="flex items-center">
                                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-semibold">Your license has expired!</p>
                                    <p class="text-sm text-red-200">Expired on {{ $userLicense->expires_at->format('d/m/Y') }}</p>
                                </div>
                            </div>
                            <a href="{{ route('customer.licenses.show', $userLicense) }}" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                Renew Now
                            </a>
                        </div>
                    </div>
                @endif
            @endif

            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Left: Product Info -->
                <div>
                    <div class="inline-flex items-center px-4 py-2 bg-emerald-500/20 rounded-full text-emerald-300 text-sm mb-6 border border-emerald-500/30">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        WordPress Plugin
                    </div>

                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 leading-tight">
                        SMS Payment
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-400">Checker</span>
                    </h1>

                    <p class="text-xl text-gray-300 mb-8 leading-relaxed">
                        WordPress & WooCommerce Plugin for automatic bank transfer verification via SMS.
                        Supports 15+ Thai banks with real-time payment matching.
                    </p>

                    <!-- Key Stats -->
                    <div class="grid grid-cols-3 gap-4 mb-8">
                        <div class="bg-gray-800/50 rounded-xl p-4 text-center border border-gray-700">
                            <p class="text-3xl font-black text-emerald-400">15+</p>
                            <p class="text-sm text-gray-400 mt-1">Thai Banks</p>
                        </div>
                        <div class="bg-gray-800/50 rounded-xl p-4 text-center border border-gray-700">
                            <p class="text-3xl font-black text-emerald-400">AES-256</p>
                            <p class="text-sm text-gray-400 mt-1">Encryption</p>
                        </div>
                        <div class="bg-gray-800/50 rounded-xl p-4 text-center border border-gray-700">
                            <p class="text-3xl font-black text-emerald-400">Auto</p>
                            <p class="text-sm text-gray-400 mt-1">Matching</p>
                        </div>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap gap-4">
                        @if(!$product->isComingSoon())
                            <form action="{{ route('cart.add', $product) }}" method="POST">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-emerald-500/25">
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    Buy License - {{ number_format($product->price, 0) }} THB
                                </button>
                            </form>
                        @endif
                        <a href="#features" class="px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                            View Features
                        </a>
                    </div>
                </div>

                <!-- Right: Product Image -->
                <div class="relative">
                    <div class="bg-gradient-to-br from-emerald-500/20 to-teal-500/20 rounded-2xl p-4 backdrop-blur-sm border border-emerald-500/30">
                        @if($product->image)
                            <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="w-full rounded-xl shadow-2xl">
                        @else
                            <div class="w-full aspect-video bg-gray-800 rounded-xl flex items-center justify-center">
                                <div class="text-center p-8">
                                    <svg class="w-24 h-24 mx-auto text-emerald-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <p class="text-emerald-300 text-xl font-bold">SMS Payment Checker</p>
                                    <p class="text-gray-400 mt-2">WordPress & WooCommerce Plugin</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Floating Badge -->
                    <div class="absolute -top-4 -right-4 bg-gradient-to-r from-emerald-500 to-teal-500 text-white px-4 py-2 rounded-full text-sm font-bold shadow-lg">
                        WordPress + WooCommerce
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="relative py-16" id="features">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold text-white text-center mb-4">Key Features</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">Everything you need for automatic payment verification on your WordPress/WooCommerce store</p>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-emerald-500/50 transition-all group">
                    <div class="w-12 h-12 bg-emerald-500/20 rounded-lg flex items-center justify-center mb-4 group-hover:bg-emerald-500/30 transition-colors">
                        <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">15+ Thai Banks</h3>
                    <p class="text-gray-400">KBANK, SCB, KTB, BBL, GSB, BAY, TTB, PromptPay, CIMB, KKP, LH, TISCO, UOB, ICBC, BAAC</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-emerald-500/50 transition-all group">
                    <div class="w-12 h-12 bg-teal-500/20 rounded-lg flex items-center justify-center mb-4 group-hover:bg-teal-500/30 transition-colors">
                        <svg class="w-6 h-6 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Auto Payment Matching</h3>
                    <p class="text-gray-400">Unique decimal amounts (0.01-0.99 suffix) for unambiguous transaction matching with WooCommerce orders</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-emerald-500/50 transition-all group">
                    <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mb-4 group-hover:bg-green-500/30 transition-colors">
                        <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">AES-256-GCM Encryption</h3>
                    <p class="text-gray-400">End-to-end encryption with PBKDF2 key derivation, HMAC signing, and replay attack prevention</p>
                </div>

                <!-- Feature 4 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-emerald-500/50 transition-all group">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-4 group-hover:bg-blue-500/30 transition-colors">
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">WooCommerce Integration</h3>
                    <p class="text-gray-400">Auto-complete WooCommerce orders when bank transfer payment is verified via SMS</p>
                </div>

                <!-- Feature 5 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-emerald-500/50 transition-all group">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center mb-4 group-hover:bg-purple-500/30 transition-colors">
                        <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Android App Integration</h3>
                    <p class="text-gray-400">Works with the free SMS Payment Checker Android app for real-time SMS interception and notification</p>
                </div>

                <!-- Feature 6 -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700 hover:border-emerald-500/50 transition-all group">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center mb-4 group-hover:bg-yellow-500/30 transition-colors">
                        <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Admin Dashboard</h3>
                    <p class="text-gray-400">Device management, order approval workflows, transaction statistics, and payment verification logs</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="relative py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">How It Works</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">Simple setup process to start verifying payments automatically</p>

            <div class="grid md:grid-cols-4 gap-8">
                <div class="text-center relative">
                    <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-emerald-500">
                        <span class="text-2xl font-bold text-emerald-400">1</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Install Plugin</h3>
                    <p class="text-gray-400 text-sm">Upload and activate the plugin on your WordPress site</p>
                    <div class="hidden md:block absolute top-8 left-full w-full h-0.5 bg-gradient-to-r from-emerald-500/50 to-transparent"></div>
                </div>

                <div class="text-center relative">
                    <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-emerald-500">
                        <span class="text-2xl font-bold text-emerald-400">2</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Activate License</h3>
                    <p class="text-gray-400 text-sm">Enter your License Key in the plugin settings to unlock full features</p>
                    <div class="hidden md:block absolute top-8 left-full w-full h-0.5 bg-gradient-to-r from-emerald-500/50 to-transparent"></div>
                </div>

                <div class="text-center relative">
                    <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-emerald-500">
                        <span class="text-2xl font-bold text-emerald-400">3</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Install Android App</h3>
                    <p class="text-gray-400 text-sm">Download the free SMS Checker app and scan the QR code to connect</p>
                    <div class="hidden md:block absolute top-8 left-full w-full h-0.5 bg-gradient-to-r from-emerald-500/50 to-transparent"></div>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-emerald-500">
                        <span class="text-2xl font-bold text-emerald-400">4</span>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Auto Verify</h3>
                    <p class="text-gray-400 text-sm">Bank transfer payments are automatically verified and orders updated</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Supported Banks Section -->
    <section class="relative py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">Supported Banks</h2>
            <p class="text-gray-400 text-center mb-12">Support for all major Thai banking institutions</p>

            <div class="grid grid-cols-3 sm:grid-cols-5 lg:grid-cols-8 gap-4">
                @foreach(['KBANK', 'SCB', 'KTB', 'BBL', 'GSB', 'BAY', 'TTB', 'PromptPay', 'CIMB', 'KKP', 'LH Bank', 'TISCO', 'UOB', 'ICBC', 'BAAC'] as $bank)
                    <div class="bg-gray-800/50 rounded-xl p-4 text-center border border-gray-700 hover:border-emerald-500/30 transition-all">
                        <p class="text-white font-semibold text-sm">{{ $bank }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="relative py-16 bg-gray-900/50" id="pricing">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">Pricing</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">Choose the plan that fits your business</p>

            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Monthly -->
                <div class="bg-gray-800/50 rounded-2xl p-8 border border-gray-700 hover:border-emerald-500/50 transition-all">
                    <h3 class="text-xl font-bold text-white mb-2">Monthly</h3>
                    <p class="text-gray-400 mb-6">For testing and small shops</p>
                    <div class="mb-6">
                        <span class="text-4xl font-black text-white">990</span>
                        <span class="text-gray-400 ml-2">THB/month</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-300">
                            <svg class="w-5 h-5 text-emerald-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            All Thai banks support
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-5 h-5 text-emerald-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            WooCommerce integration
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-5 h-5 text-emerald-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            1 site license
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-5 h-5 text-emerald-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Email support
                        </li>
                    </ul>
                    <form action="{{ route('cart.add', $product) }}" method="POST">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="license_type" value="monthly">
                        <input type="hidden" name="price" value="990">
                        <button type="submit" class="w-full py-3 bg-gray-700 hover:bg-gray-600 text-white font-bold rounded-xl transition-all">
                            Get Monthly
                        </button>
                    </form>
                </div>

                <!-- Yearly (Popular) -->
                <div class="bg-gray-800/50 rounded-2xl p-8 border-2 border-emerald-500 relative transform md:scale-105">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-gradient-to-r from-emerald-500 to-teal-500 text-white px-6 py-1 rounded-full text-sm font-bold">
                        POPULAR
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Yearly</h3>
                    <p class="text-gray-400 mb-6">Best value for growing businesses</p>
                    <div class="mb-2">
                        <span class="text-4xl font-black text-white">9,900</span>
                        <span class="text-gray-400 ml-2">THB/year</span>
                    </div>
                    <p class="text-emerald-400 text-sm mb-6">Save 17% vs monthly</p>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-300">
                            <svg class="w-5 h-5 text-emerald-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            All Monthly features
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-5 h-5 text-emerald-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Priority support
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-5 h-5 text-emerald-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Free updates for 1 year
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-5 h-5 text-emerald-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Multi-device support
                        </li>
                    </ul>
                    <form action="{{ route('cart.add', $product) }}" method="POST">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="license_type" value="yearly">
                        <input type="hidden" name="price" value="9900">
                        <button type="submit" class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-emerald-500/25">
                            Get Yearly
                        </button>
                    </form>
                </div>

                <!-- Lifetime -->
                <div class="bg-gray-800/50 rounded-2xl p-8 border border-gray-700 hover:border-emerald-500/50 transition-all">
                    <h3 class="text-xl font-bold text-white mb-2">Lifetime</h3>
                    <p class="text-gray-400 mb-6">One-time payment, forever access</p>
                    <div class="mb-6">
                        <span class="text-4xl font-black text-white">29,900</span>
                        <span class="text-gray-400 ml-2">THB</span>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center text-gray-300">
                            <svg class="w-5 h-5 text-emerald-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            All Yearly features
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-5 h-5 text-emerald-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Lifetime updates
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-5 h-5 text-emerald-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Premium support
                        </li>
                        <li class="flex items-center text-gray-300">
                            <svg class="w-5 h-5 text-emerald-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            No recurring fees
                        </li>
                    </ul>
                    <form action="{{ route('cart.add', $product) }}" method="POST">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="license_type" value="lifetime">
                        <input type="hidden" name="price" value="29900">
                        <button type="submit" class="w-full py-3 bg-gray-700 hover:bg-gray-600 text-white font-bold rounded-xl transition-all">
                            Get Lifetime
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- License Activation Section -->
    <section class="relative py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-4">License Activation</h2>
            <p class="text-gray-400 text-center mb-12 max-w-2xl mx-auto">After purchase, activate your license in the WordPress plugin settings</p>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <!-- Steps -->
                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center text-white font-bold">1</div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-white">Purchase License</h3>
                            <p class="text-gray-400">Choose your plan and complete payment. License key will be generated automatically.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center text-white font-bold">2</div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-white">Receive License Key</h3>
                            <p class="text-gray-400">License key will be sent to your registered email and available in your account dashboard.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center text-white font-bold">3</div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-white">Enter in WordPress</h3>
                            <p class="text-gray-400">Go to WordPress Admin > SMS Payment Checker > Settings > License and enter your key.</p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 bg-emerald-500 rounded-full flex items-center justify-center text-white font-bold">4</div>
                        <div class="ml-4">
                            <h3 class="text-lg font-bold text-white">Start Using</h3>
                            <p class="text-gray-400">All premium features are unlocked. Connect your Android device and start verifying payments.</p>
                        </div>
                    </div>
                </div>

                <!-- License Info Card -->
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-xl font-bold text-white mb-4">License Information</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-emerald-400 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            License is bound to your WordPress site URL
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-emerald-400 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            1 license per WordPress site
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-emerald-400 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Can transfer to another site (contact support)
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-emerald-400 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Free updates during license period
                        </li>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-emerald-400 mr-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Android app is always free
                        </li>
                    </ul>

                    <div class="mt-6 pt-6 border-t border-gray-700">
                        <form action="{{ route('cart.add', $product) }}" method="POST">
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="block w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-center font-bold rounded-xl transition-all">
                                Buy License - {{ number_format($product->price, 0) }} THB
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Requirements Section -->
    <section class="relative py-16 bg-gray-900/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-white text-center mb-12">System Requirements</h2>

            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="bg-gray-800/50 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        WordPress
                    </h3>
                    <ul class="space-y-2 text-gray-300">
                        <li class="flex items-center"><svg class="w-4 h-4 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>WordPress 5.8+</li>
                        <li class="flex items-center"><svg class="w-4 h-4 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>PHP 7.4+ (8.1+ recommended)</li>
                        <li class="flex items-center"><svg class="w-4 h-4 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>WooCommerce 5.0+ (optional)</li>
                        <li class="flex items-center"><svg class="w-4 h-4 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>SSL Certificate (HTTPS)</li>
                    </ul>
                </div>

                <div class="bg-gray-800/50 rounded-xl p-6 border border-emerald-500/50">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                        <svg class="w-6 h-6 text-emerald-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Android App (Free)
                    </h3>
                    <ul class="space-y-2 text-gray-300">
                        <li class="flex items-center"><svg class="w-4 h-4 text-emerald-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Android 8.0+ (API 26)</li>
                        <li class="flex items-center"><svg class="w-4 h-4 text-emerald-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>SMS permission required</li>
                        <li class="flex items-center"><svg class="w-4 h-4 text-emerald-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Internet connection</li>
                        <li class="flex items-center"><svg class="w-4 h-4 text-emerald-400 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Always free to use</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="relative py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Ready to automate your payment verification?</h2>
            <p class="text-gray-400 mb-8 max-w-2xl mx-auto">
                Stop manually checking bank transfers. Let SMS Payment Checker do it automatically for your WooCommerce store.
            </p>

            <div class="flex flex-wrap justify-center gap-4">
                <form action="{{ route('cart.add', $product) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-emerald-500/25">
                        Buy License - {{ number_format($product->price, 0) }} THB
                    </button>
                </form>
                <a href="{{ route('support.index') }}" class="px-8 py-4 bg-gray-700/50 hover:bg-gray-600/50 text-white font-semibold rounded-xl border border-gray-600 transition-all backdrop-blur-sm">
                    Contact Us
                </a>
            </div>

            <p class="text-gray-500 text-sm mt-6">
                Need a custom solution? <a href="{{ route('services.index') }}" class="text-emerald-400 hover:text-emerald-300">Check our web development services</a> for non-WordPress websites.
            </p>
        </div>
    </section>

    <!-- Related Products -->
    @if($relatedProducts && $relatedProducts->count() > 0)
        <section class="relative py-16 bg-gray-900/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-white text-center mb-12">Related Products</h2>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $related)
                        <a href="{{ route('products.show', $related->slug) }}"
                           class="group bg-gray-800/50 rounded-xl overflow-hidden border border-gray-700 hover:border-emerald-500/50 transition-all hover:transform hover:scale-[1.02]">
                            <div class="aspect-video bg-gray-700 overflow-hidden">
                                @if($related->image)
                                    <img src="{{ Storage::url($related->image) }}" alt="{{ $related->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-emerald-500/20 to-teal-500/20">
                                        <svg class="w-12 h-12 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-white group-hover:text-emerald-400 transition-colors mb-2">{{ $related->name }}</h3>
                                @if(!$related->is_custom)
                                    <p class="text-emerald-400 font-bold text-lg">{{ number_format($related->price, 0) }} THB</p>
                                @else
                                    <p class="text-gray-400">Contact for pricing</p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
