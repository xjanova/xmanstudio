@extends($customerLayout ?? 'layouts.customer')

@section('title', 'License Details - ' . ($license->product?->name ?? 'License'))
@section('page-title', 'License Details')

@push('styles')
<style>
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animation-delay-4000 {
        animation-delay: 4s;
    }
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
</style>
@endpush

@section('content')
<div class="mb-6">
    <a href="{{ route('customer.licenses') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 flex items-center font-medium transition-colors">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Licenses
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main License Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- License Card with Premium Header -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-100 dark:border-gray-700">
            <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-violet-600 to-pink-600 p-6">
                <div class="absolute top-0 left-0 w-72 h-72 bg-pink-400 rounded-full mix-blend-multiply filter blur-xl opacity-50 animate-blob"></div>
                <div class="absolute top-0 right-0 w-72 h-72 bg-purple-400 rounded-full mix-blend-multiply filter blur-xl opacity-50 animate-blob animation-delay-2000"></div>
                <div class="absolute -bottom-8 left-20 w-72 h-72 bg-violet-400 rounded-full mix-blend-multiply filter blur-xl opacity-50 animate-blob animation-delay-4000"></div>

                <div class="relative flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white">{{ $license->product?->name ?? 'Software License' }}</h2>
                        <p class="text-purple-200 mt-1">License Key</p>
                    </div>
                    @php
                        $statusGradients = [
                            'active' => 'bg-gradient-to-r from-green-400 to-emerald-500 text-white',
                            'expired' => 'bg-gradient-to-r from-red-400 to-rose-500 text-white',
                            'revoked' => 'bg-white/20 text-white',
                        ];
                    @endphp
                    <span class="px-3 py-1 text-sm font-semibold rounded-full shadow {{ $statusGradients[$license->status] ?? 'bg-white/20 text-white' }}">
                        {{ ucfirst($license->status) }}
                    </span>
                </div>
            </div>

            <div class="p-6">
                <!-- License Key Display -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50 rounded-xl p-4 mb-6 border border-gray-200 dark:border-gray-600">
                    <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-2">License Key</label>
                    <div class="flex items-center">
                        <code class="flex-1 text-lg font-mono bg-white dark:bg-gray-800 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 break-all text-gray-900 dark:text-white">{{ $license->license_key }}</code>
                        <button onclick="copyLicenseKey()" class="ml-3 px-4 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-xl hover:from-purple-600 hover:to-pink-600 transition-all shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- License Details Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">License Type</label>
                        @php
                            $typeGradients = [
                                'lifetime' => 'bg-gradient-to-r from-purple-400 to-violet-500 text-white',
                                'yearly' => 'bg-gradient-to-r from-blue-400 to-indigo-500 text-white',
                                'monthly' => 'bg-gradient-to-r from-gray-400 to-gray-500 text-white',
                                'demo' => 'bg-gradient-to-r from-yellow-400 to-amber-500 text-white',
                            ];
                        @endphp
                        <span class="px-3 py-1 text-sm font-semibold rounded-full shadow {{ $typeGradients[$license->license_type] ?? 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300' }}">
                            {{ ucfirst($license->license_type) }}
                        </span>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">Created Date</label>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $license->created_at->format('d M Y H:i') }}</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">Expires</label>
                        @if($license->expires_at)
                            <span class="{{ $license->expires_at->isPast() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }} font-medium">
                                {{ $license->expires_at->format('d M Y') }}
                            </span>
                            @if(!$license->expires_at->isPast())
                                <span class="text-sm text-gray-500 dark:text-gray-400 block">({{ $license->expires_at->diffForHumans() }})</span>
                            @else
                                <span class="text-sm text-red-500 dark:text-red-400 block">(Expired)</span>
                            @endif
                        @else
                            <span class="text-green-600 dark:text-green-400 font-semibold">Never (Lifetime)</span>
                        @endif
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                        <label class="block text-sm font-semibold text-gray-500 dark:text-gray-400 mb-1">Activations</label>
                        <p class="text-gray-900 dark:text-white font-medium">
                            {{ $license->activation_count }} / {{ $license->max_activations ?? '∞' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Machine Activation -->
        @if($license->machine_id)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                </div>
                Activated Machine
            </h3>
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Machine ID</p>
                        <code class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ $license->machine_id }}</code>
                    </div>
                    <span class="px-3 py-1 bg-gradient-to-r from-green-400 to-emerald-500 text-white text-xs font-semibold rounded-full shadow">Active</span>
                </div>
                @if($license->activated_at)
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Activated: {{ $license->activated_at->format('d M Y H:i') }}
                </p>
                @endif
            </div>

            <div class="mt-4 p-4 bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-xl border border-yellow-200 dark:border-yellow-700">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">
                    <strong>Need to change machine?</strong> Contact support to reset your license activation.
                </p>
            </div>
        </div>
        @endif

        <!-- Activation History -->
        @if($license->activations && $license->activations->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                Activation History
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Machine ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP Address</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($license->activations as $activation)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-sm font-mono text-gray-600 dark:text-gray-400">{{ Str::limit($activation->machine_id, 20) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $activation->ip_address ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $activation->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $activation->is_active ? 'bg-gradient-to-r from-green-400 to-emerald-500 text-white shadow' : 'bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300' }}">
                                    {{ $activation->is_active ? 'Active' : 'Deactivated' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                Quick Actions
            </h3>
            <div class="space-y-3">
                @if($license->product && $license->product->slug === 'autotradex')
                <a href="https://github.com/xjanova/autotradex/releases/latest" target="_blank"
                   class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-purple-500 via-violet-500 to-pink-500 text-white rounded-xl hover:from-purple-600 hover:via-violet-600 hover:to-pink-600 font-semibold shadow-lg transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Software
                </a>
                @endif

                <a href="{{ route('customer.downloads') }}"
                   class="w-full flex items-center justify-center px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 font-medium transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Center
                </a>

                <a href="{{ route('customer.support.create') }}"
                   class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 font-medium transition-all">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Contact Support
                </a>
            </div>
        </div>

        <!-- License Features -->
        @if($license->product && $license->product->slug === 'autotradex')
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
                License Features
            </h3>
            <ul class="space-y-3">
                @php
                    $features = [
                        'demo' => ['Simulation Mode', '1 Exchange', 'Basic Alerts'],
                        'monthly' => ['Live Trading', '3 Exchanges', 'P&L Tracking', 'Basic Arbitrage'],
                        'yearly' => ['Live Trading', '5 Exchanges', 'Advanced Arbitrage', 'Auto Rebalance', 'Priority Support'],
                        'lifetime' => ['All Features', '6 Exchanges', 'Triangular Arbitrage', 'API Access', 'Lifetime Updates'],
                    ];
                    $licenseFeatures = $features[$license->license_type] ?? $features['monthly'];
                @endphp
                @foreach($licenseFeatures as $feature)
                <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <div class="w-6 h-6 rounded-full bg-gradient-to-r from-green-400 to-emerald-500 flex items-center justify-center mr-3 shadow">
                        <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>

            @if($license->license_type !== 'lifetime' && $license->status === 'active')
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('autotradex.pricing') }}" class="text-sm text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-medium flex items-center transition-colors">
                    Upgrade to Lifetime
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            @endif
        </div>
        @endif

        <!-- Help Card -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-700">
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-indigo-500 flex items-center justify-center mb-4 shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="font-semibold text-blue-900 dark:text-blue-300 mb-2">Need Help?</h3>
            <p class="text-sm text-blue-800 dark:text-blue-200 mb-3">
                If you have any issues with your license, our support team is here to help.
            </p>
            <a href="{{ route('customer.support.create') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-semibold flex items-center transition-colors">
                Open a Support Ticket
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyLicenseKey() {
    const licenseKey = '{{ $license->license_key }}';
    navigator.clipboard.writeText(licenseKey).then(function() {
        alert('License key copied to clipboard!');
    }).catch(function(err) {
        console.error('Failed to copy:', err);
    });
}
</script>
@endpush
@endsection
