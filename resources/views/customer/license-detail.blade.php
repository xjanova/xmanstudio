@extends('layouts.customer')

@section('title', 'License Details - ' . ($license->product?->name ?? 'License'))
@section('page-title', 'License Details')

@section('content')
<div class="mb-6">
    <a href="{{ route('customer.licenses') }}" class="text-primary-600 hover:text-primary-700 flex items-center">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Licenses
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main License Info -->
    <div class="lg:col-span-2 space-y-6">
        <!-- License Card -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 bg-gradient-to-r from-purple-600 to-pink-600">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-white">{{ $license->product?->name ?? 'Software License' }}</h2>
                        <p class="text-purple-200 mt-1">License Key</p>
                    </div>
                    <span class="px-3 py-1 text-sm font-medium rounded-full
                        {{ $license->status === 'active' ? 'bg-green-400/20 text-green-100' : '' }}
                        {{ $license->status === 'expired' ? 'bg-red-400/20 text-red-100' : '' }}
                        {{ $license->status === 'revoked' ? 'bg-gray-400/20 text-gray-100' : '' }}
                    ">
                        {{ ucfirst($license->status) }}
                    </span>
                </div>
            </div>

            <div class="p-6">
                <!-- License Key Display -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <label class="block text-sm font-medium text-gray-500 mb-2">License Key</label>
                    <div class="flex items-center">
                        <code class="flex-1 text-lg font-mono bg-white px-4 py-3 rounded-lg border border-gray-200 break-all">{{ $license->license_key }}</code>
                        <button onclick="copyLicenseKey()" class="ml-3 px-4 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- License Details Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">License Type</label>
                        <p class="mt-1 text-lg font-semibold text-gray-900">
                            <span class="px-2 py-1 text-sm rounded-full
                                {{ $license->license_type === 'lifetime' ? 'bg-purple-100 text-purple-700' : '' }}
                                {{ $license->license_type === 'yearly' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $license->license_type === 'monthly' ? 'bg-gray-100 text-gray-700' : '' }}
                                {{ $license->license_type === 'demo' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            ">
                                {{ ucfirst($license->license_type) }}
                            </span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Created Date</label>
                        <p class="mt-1 text-gray-900">{{ $license->created_at->format('d M Y H:i') }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Expires</label>
                        <p class="mt-1">
                            @if($license->expires_at)
                                <span class="{{ $license->expires_at->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                                    {{ $license->expires_at->format('d M Y') }}
                                </span>
                                @if(!$license->expires_at->isPast())
                                    <span class="text-sm text-gray-500">({{ $license->expires_at->diffForHumans() }})</span>
                                @else
                                    <span class="text-sm text-red-500">(Expired)</span>
                                @endif
                            @else
                                <span class="text-green-600 font-semibold">Never (Lifetime)</span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Activations</label>
                        <p class="mt-1 text-gray-900">
                            {{ $license->activation_count }} / {{ $license->max_activations ?? '∞' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Machine Activation -->
        @if($license->machine_id)
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Activated Machine</h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Machine ID</p>
                        <code class="text-sm font-mono text-gray-700">{{ $license->machine_id }}</code>
                    </div>
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">Active</span>
                </div>
                @if($license->activated_at)
                <p class="mt-2 text-sm text-gray-500">
                    Activated: {{ $license->activated_at->format('d M Y H:i') }}
                </p>
                @endif
            </div>

            <div class="mt-4 p-4 bg-yellow-50 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <strong>Need to change machine?</strong> Contact support to reset your license activation.
                </p>
            </div>
        </div>
        @endif

        <!-- Activation History -->
        @if($license->activations && $license->activations->count() > 0)
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Activation History</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Machine ID</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($license->activations as $activation)
                        <tr>
                            <td class="px-4 py-2 text-sm font-mono text-gray-600">{{ Str::limit($activation->machine_id, 20) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ $activation->ip_address ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ $activation->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 text-xs rounded-full {{ $activation->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
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
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-3">
                @if($license->product && $license->product->slug === 'autotradex')
                <a href="https://github.com/xjanova/autotradex/releases/latest" target="_blank"
                   class="w-full flex items-center justify-center px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Software
                </a>
                @endif

                <a href="{{ route('customer.downloads') }}"
                   class="w-full flex items-center justify-center px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Center
                </a>

                <a href="{{ route('customer.support.create') }}"
                   class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Contact Support
                </a>
            </div>
        </div>

        <!-- License Features -->
        @if($license->product && $license->product->slug === 'autotradex')
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">License Features</h3>
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
                <li class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ $feature }}
                </li>
                @endforeach
            </ul>

            @if($license->license_type !== 'lifetime' && $license->status === 'active')
            <div class="mt-4 pt-4 border-t border-gray-200">
                <a href="{{ route('autotradex.pricing') }}" class="text-sm text-purple-600 hover:underline">
                    Upgrade to Lifetime &rarr;
                </a>
            </div>
            @endif
        </div>
        @endif

        <!-- Help Card -->
        <div class="bg-blue-50 rounded-xl p-6">
            <h3 class="font-semibold text-blue-900 mb-2">Need Help?</h3>
            <p class="text-sm text-blue-800 mb-3">
                If you have any issues with your license, our support team is here to help.
            </p>
            <a href="{{ route('customer.support.create') }}" class="text-sm text-blue-600 hover:underline font-medium">
                Open a Support Ticket &rarr;
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
