@extends('layouts.admin')

@section('title', 'Aipray Dashboard')
@section('page-title', 'Aipray - AI Chanting Companion')

@section('content')
{{-- Navigation Tabs --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Aipray Admin Navigation">
        <a href="{{ route('admin.aipray.dashboard') }}" class="whitespace-nowrap border-b-2 border-yellow-500 pb-3 px-1 text-sm font-medium text-yellow-600">Dashboard</a>
        <a href="{{ route('admin.aipray.dataset.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dataset</a>
        <a href="{{ route('admin.aipray.record.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Record</a>
        <a href="{{ route('admin.aipray.training.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Training</a>
        <a href="{{ route('admin.aipray.models.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Models</a>
        <a href="{{ route('admin.aipray.evaluate.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Evaluate</a>
        <a href="{{ route('admin.aipray.chants.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Chants</a>
        <a href="{{ route('admin.aipray.donations.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Donations</a>
    </nav>
</div>

{{-- Header with Gold Gradient --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-yellow-700 via-yellow-600 to-amber-500 p-8 mb-8 shadow-xl">
    <div class="absolute inset-0 bg-grid-white/10"></div>
    <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 bg-yellow-400/20 rounded-full blur-3xl"></div>
    <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                    </svg>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-white">Aipray Dashboard</h1>
            </div>
            <p class="text-yellow-100 text-lg">AI-Powered Buddhist Chanting Companion</p>
        </div>
        <div class="flex flex-wrap gap-3">
            {{-- ML Health Indicator --}}
            <div class="inline-flex items-center px-4 py-2.5 bg-white/20 backdrop-blur-sm text-white font-medium rounded-xl border border-white/25">
                @if(($stats['ml_health'] ?? 'unknown') === 'healthy')
                    <span class="w-3 h-3 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                    <span>ML Service: Healthy</span>
                @elseif(($stats['ml_health'] ?? 'unknown') === 'degraded')
                    <span class="w-3 h-3 bg-yellow-400 rounded-full mr-2 animate-pulse"></span>
                    <span>ML Service: Degraded</span>
                @else
                    <span class="w-3 h-3 bg-red-400 rounded-full mr-2"></span>
                    <span>ML Service: Offline</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
    {{-- Total Samples --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-yellow-500 to-amber-600 shadow-lg shadow-yellow-500/30">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Samples</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_samples'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Verified --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 shadow-lg shadow-green-500/30">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Verified</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['verified'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Total Hours --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 shadow-lg shadow-blue-500/30">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Hours</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_hours'] ?? 0, 1) }}</p>
            </div>
        </div>
    </div>

    {{-- Sessions --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-purple-500 to-violet-600 shadow-lg shadow-purple-500/30">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Sessions</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['sessions'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Models --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-rose-600 shadow-lg shadow-pink-500/30">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Models</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['models'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    {{-- Donations --}}
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-red-600 shadow-lg shadow-orange-500/30">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Donations</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['donations'] ?? 0) }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    {{-- Recent Training Jobs --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Training Jobs</h3>
            <a href="{{ route('admin.aipray.training.index') }}" class="text-sm text-yellow-600 hover:text-yellow-700 font-medium">View All</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Progress</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Started</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentJobs as $job)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $job->name }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($job->status === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Completed</span>
                                @elseif($job->status === 'running')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Running</span>
                                @elseif($job->status === 'failed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Failed</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">{{ ucfirst($job->status) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ $job->progress ?? 0 }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $job->progress ?? 0 }}%</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $job->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No training jobs yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Sessions --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Recording Sessions</h3>
            <a href="{{ route('admin.aipray.record.index') }}" class="text-sm text-yellow-600 hover:text-yellow-700 font-medium">Record New</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Chant</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Samples</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentSessions as $session)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">#{{ $session->id }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $session->chant_title ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $session->samples_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $session->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No sessions recorded yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Quick Links --}}
<div class="mt-8 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
    <a href="{{ route('admin.aipray.dataset.index') }}" class="flex flex-col items-center p-4 bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-yellow-300 transition-all group">
        <svg class="w-8 h-8 text-yellow-600 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Dataset</span>
    </a>
    <a href="{{ route('admin.aipray.training.index') }}" class="flex flex-col items-center p-4 bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-yellow-300 transition-all group">
        <svg class="w-8 h-8 text-yellow-600 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Training</span>
    </a>
    <a href="{{ route('admin.aipray.models.index') }}" class="flex flex-col items-center p-4 bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-yellow-300 transition-all group">
        <svg class="w-8 h-8 text-yellow-600 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Models</span>
    </a>
    <a href="{{ route('admin.aipray.evaluate.index') }}" class="flex flex-col items-center p-4 bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-yellow-300 transition-all group">
        <svg class="w-8 h-8 text-yellow-600 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Evaluate</span>
    </a>
    <a href="{{ route('admin.aipray.chants.index') }}" class="flex flex-col items-center p-4 bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-yellow-300 transition-all group">
        <svg class="w-8 h-8 text-yellow-600 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Chants</span>
    </a>
    <a href="{{ route('admin.aipray.donations.index') }}" class="flex flex-col items-center p-4 bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:border-yellow-300 transition-all group">
        <svg class="w-8 h-8 text-yellow-600 mb-2 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Donations</span>
    </a>
</div>
@endsection
