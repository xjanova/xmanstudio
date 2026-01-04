@extends('layouts.customer')

@section('title', 'My Subscriptions')
@section('page-title', 'My Subscriptions')

@section('content')
<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
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
                <p class="text-2xl font-bold text-gray-600">{{ $stats['expired'] }}</p>
            </div>
            <div class="p-3 bg-gray-100 rounded-lg">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Spent</p>
                <p class="text-2xl font-bold text-primary-600">฿{{ number_format($stats['total_spent']) }}</p>
            </div>
            <div class="p-3 bg-primary-100 rounded-lg">
                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form action="" method="GET" class="flex flex-wrap gap-4">
        <select name="status" class="rounded-lg border-gray-300 text-sm focus:ring-primary-500 focus:border-primary-500">
            <option value="all">All Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>

        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
            Filter
        </button>

        <a href="{{ route('rental.index') }}" class="ml-auto px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm">
            + New Subscription
        </a>
    </form>
</div>

<!-- Subscription List -->
<div class="space-y-4">
    @forelse($subscriptions as $subscription)
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div class="flex-1">
                    <div class="flex items-center">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $subscription->rentalPackage->display_name }}</h3>
                        <span class="ml-3 px-3 py-1 text-xs font-medium rounded-full
                            {{ $subscription->status === 'active' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $subscription->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            {{ $subscription->status === 'expired' ? 'bg-red-100 text-red-700' : '' }}
                            {{ $subscription->status === 'cancelled' ? 'bg-gray-100 text-gray-700' : '' }}
                            {{ $subscription->status === 'suspended' ? 'bg-orange-100 text-orange-700' : '' }}
                        ">
                            {{ ucfirst($subscription->status) }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ $subscription->rentalPackage->display_description }}</p>
                </div>

                <div class="mt-4 md:mt-0 md:ml-6 text-right">
                    <p class="text-2xl font-bold text-gray-900">฿{{ number_format($subscription->rentalPackage->price) }}</p>
                    <p class="text-sm text-gray-500">{{ $subscription->rentalPackage->duration_text }}</p>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap gap-6 text-sm">
                <div>
                    <span class="text-gray-500">Started:</span>
                    <span class="font-medium text-gray-900">{{ $subscription->starts_at?->format('d M Y') ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Expires:</span>
                    <span class="font-medium {{ $subscription->expires_at?->isPast() ? 'text-red-600' : 'text-gray-900' }}">
                        {{ $subscription->expires_at?->format('d M Y') ?? '-' }}
                        @if($subscription->expires_at && !$subscription->expires_at->isPast())
                            <span class="text-gray-400">({{ $subscription->expires_at->diffForHumans() }})</span>
                        @endif
                    </span>
                </div>
                @if($subscription->auto_renew)
                <div>
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Auto-renew</span>
                </div>
                @endif
            </div>

            <div class="mt-4 flex gap-3">
                <a href="{{ route('customer.subscriptions.show', $subscription) }}"
                   class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                    View Details
                </a>
                @if($subscription->status === 'active' && $subscription->expires_at?->diffInDays() < 30)
                <a href="{{ route('rental.checkout', $subscription->rentalPackage) }}"
                   class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm">
                    Renew
                </a>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl shadow-sm p-12 text-center">
        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <h3 class="text-lg font-medium text-gray-900">No subscriptions yet</h3>
        <p class="text-gray-500 mt-1">Start your first subscription to access our services</p>
        <a href="{{ route('rental.index') }}" class="mt-4 inline-block px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
            Browse Packages
        </a>
    </div>
    @endforelse
</div>

@if($subscriptions->hasPages())
<div class="mt-6">
    {{ $subscriptions->withQueryString()->links() }}
</div>
@endif
@endsection
