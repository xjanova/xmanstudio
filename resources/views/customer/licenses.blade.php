@extends('layouts.customer')

@section('title', 'My Licenses')
@section('page-title', 'My Licenses')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Licenses</p>
                <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            </div>
            <div class="p-3 bg-gray-100 rounded-lg">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Active</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</p>
            </div>
            <div class="p-3 bg-green-100 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Expired</p>
                <p class="text-2xl font-bold text-red-600">{{ $stats['expired'] }}</p>
            </div>
            <div class="p-3 bg-red-100 rounded-lg">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="" method="GET" class="flex flex-wrap gap-4">
        <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            <option value="all">All Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
            <option value="revoked" {{ request('status') === 'revoked' ? 'selected' : '' }}>Revoked</option>
        </select>

        <select name="type" class="rounded-lg border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            <option value="all">All Types</option>
            <option value="monthly" {{ request('type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
            <option value="yearly" {{ request('type') === 'yearly' ? 'selected' : '' }}>Yearly</option>
            <option value="lifetime" {{ request('type') === 'lifetime' ? 'selected' : '' }}>Lifetime</option>
            <option value="demo" {{ request('type') === 'demo' ? 'selected' : '' }}>Demo</option>
        </select>

        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
            Filter
        </button>
    </form>
</div>

<!-- License List -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">License Key</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activations</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($licenses as $license)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $license->product?->name ?? 'Software License' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <code class="text-sm bg-gray-100 px-2 py-1 rounded font-mono">{{ Str::limit($license->license_key, 20) }}</code>
                            <button onclick="copyToClipboard('{{ $license->license_key }}')" class="ml-2 text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            {{ $license->license_type === 'lifetime' ? 'bg-purple-100 text-purple-700' : '' }}
                            {{ $license->license_type === 'yearly' ? 'bg-blue-100 text-blue-700' : '' }}
                            {{ $license->license_type === 'monthly' ? 'bg-gray-100 text-gray-700' : '' }}
                            {{ $license->license_type === 'demo' ? 'bg-yellow-100 text-yellow-700' : '' }}
                        ">
                            {{ ucfirst($license->license_type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            {{ $license->status === 'active' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $license->status === 'expired' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $license->status === 'revoked' ? 'bg-gray-100 text-gray-700' : '' }}
                        ">
                            {{ ucfirst($license->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($license->expires_at)
                            {{ $license->expires_at->format('d M Y') }}
                            @if($license->expires_at->isPast())
                                <span class="text-red-500">(Expired)</span>
                            @elseif($license->expires_at->diffInDays() < 30)
                                <span class="text-yellow-500">({{ $license->expires_at->diffForHumans() }})</span>
                            @endif
                        @else
                            <span class="text-green-600">Never</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $license->activation_count }} / {{ $license->max_activations ?? '∞' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('customer.licenses.show', $license) }}" class="text-primary-600 hover:underline">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <p class="text-lg font-medium">No licenses found</p>
                        <p class="text-sm mt-1">Purchase a product to get your first license</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($licenses->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $licenses->withQueryString()->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('License key copied to clipboard!');
    });
}
</script>
@endpush
@endsection
