@extends($adminLayout ?? 'layouts.admin')

@section('title', 'AI Crawl Control')
@section('page-title', 'AI Crawl Control - Analytics Dashboard')

@push('styles')
<style>
    .animate-blob { animation: blob 7s infinite; }
    .animation-delay-2000 { animation-delay: 2s; }
    .animation-delay-4000 { animation-delay: 4s; }
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <!-- Premium Header Banner -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-700 p-8 shadow-2xl">
        <div class="absolute top-0 left-0 w-72 h-72 bg-cyan-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
        <div class="absolute top-0 right-0 w-72 h-72 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-20 w-72 h-72 bg-indigo-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

        <div class="relative">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h1 class="text-3xl font-bold text-white">AI Crawl Control</h1>
                    </div>
                    <p class="text-cyan-100 text-lg">ติดตามและควบคุม AI Bots ที่เข้ามา crawl เว็บไซต์ของคุณ</p>
                </div>
                <div class="hidden md:flex items-center space-x-3">
                    <a href="{{ route('admin.ai-crawl.settings') }}" class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        Settings
                    </a>
                    <a href="{{ route('admin.ai-crawl.export', ['period' => $period]) }}" class="inline-flex items-center px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Status & Period Filter -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-2">
            @if($setting->enabled)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span> Active
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span> Disabled
                </span>
            @endif
            @if($setting->logging_enabled)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                    Logging ON
                </span>
            @endif
        </div>
        <div class="flex items-center space-x-2">
            @foreach(['24h' => '24 ชม.', '7d' => '7 วัน', '30d' => '30 วัน', '90d' => '90 วัน'] as $key => $label)
                <a href="{{ route('admin.ai-crawl.index', ['period' => $key]) }}"
                   class="px-4 py-2 rounded-xl text-sm font-medium transition-all {{ $period === $key ? 'bg-blue-600 text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-600' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Requests -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($totalRequests) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total AI Requests</div>
        </div>

        <!-- Blocked -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($blockedRequests) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Blocked Requests</div>
            @if($totalRequests > 0)
                <div class="text-xs text-red-500 mt-1">{{ round(($blockedRequests / $totalRequests) * 100, 1) }}% blocked</div>
            @endif
        </div>

        <!-- Unique Bots -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $uniqueBots }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Unique AI Bots</div>
        </div>

        <!-- Today -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($todayRequests) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Today's Requests</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Daily Trend Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Daily Trend</h3>
            <div x-data="dailyChart()" x-init="init()" class="relative">
                <canvas id="dailyTrendChart" height="200"></canvas>
            </div>
        </div>

        <!-- Bot Category Breakdown -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Bot Categories</h3>
            @if($categoryStats->isEmpty())
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <p>No AI bot activity yet</p>
                </div>
            @else
                <div class="space-y-4">
                    @php
                        $categoryColors = [
                            'training' => ['bg' => 'bg-red-500', 'text' => 'text-red-600 dark:text-red-400', 'label' => 'Training'],
                            'assistant' => ['bg' => 'bg-green-500', 'text' => 'text-green-600 dark:text-green-400', 'label' => 'Assistant'],
                            'search' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-600 dark:text-blue-400', 'label' => 'Search'],
                            'unknown' => ['bg' => 'bg-gray-500', 'text' => 'text-gray-600 dark:text-gray-400', 'label' => 'Unknown'],
                        ];
                    @endphp
                    @foreach($categoryStats as $cat)
                        @php $color = $categoryColors[$cat->bot_category] ?? $categoryColors['unknown']; @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium {{ $color['text'] }}">{{ $color['label'] }}</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($cat->total) }} ({{ $cat->blocked }} blocked)</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="{{ $color['bg'] }} h-3 rounded-full transition-all" style="width: {{ $totalRequests > 0 ? round(($cat->total / $totalRequests) * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Bot Stats Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Bot Activity</h3>
        @if($botStats->isEmpty())
            <div class="text-center py-8 text-gray-400">No activity data</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-4 font-semibold text-gray-600 dark:text-gray-300">Bot</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-600 dark:text-gray-300">Category</th>
                            <th class="text-right py-3 px-4 font-semibold text-gray-600 dark:text-gray-300">Total</th>
                            <th class="text-right py-3 px-4 font-semibold text-gray-600 dark:text-gray-300">Blocked</th>
                            <th class="text-right py-3 px-4 font-semibold text-gray-600 dark:text-gray-300">Allowed</th>
                            <th class="text-center py-3 px-4 font-semibold text-gray-600 dark:text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($botStats as $bot)
                            <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="py-3 px-4">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $bot->bot_name }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $catBadge = match($bot->bot_category) {
                                            'training' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                            'assistant' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                            'search' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
                                        };
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $catBadge }}">{{ ucfirst($bot->bot_category) }}</span>
                                </td>
                                <td class="py-3 px-4 text-right font-mono text-gray-900 dark:text-white">{{ number_format($bot->total) }}</td>
                                <td class="py-3 px-4 text-right font-mono text-red-600 dark:text-red-400">{{ number_format($bot->blocked) }}</td>
                                <td class="py-3 px-4 text-right font-mono text-green-600 dark:text-green-400">{{ number_format($bot->total - $bot->blocked) }}</td>
                                <td class="py-3 px-4 text-center">
                                    @if($bot->blocked > 0 && $bot->blocked == $bot->total)
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">Fully Blocked</span>
                                    @elseif($bot->blocked > 0)
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">Partially</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Allowed</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Top URLs and Recent Logs side by side -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Crawled URLs -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Top Crawled URLs</h3>
            @if($topUrls->isEmpty())
                <div class="text-center py-8 text-gray-400">No data</div>
            @else
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($topUrls as $urlItem)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate flex-1 mr-3 font-mono">{{ Str::limit(parse_url($urlItem->url, PHP_URL_PATH) ?: '/', 60) }}</span>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400 whitespace-nowrap">{{ number_format($urlItem->hits) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Recent Activity Log -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Recent Activity</h3>
                <form action="{{ route('admin.ai-crawl.clear-logs') }}" method="POST" onsubmit="return confirm('Clear logs older than 30 days?')">
                    @csrf
                    <input type="hidden" name="days" value="30">
                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 transition-colors">Clear old logs</button>
                </form>
            </div>
            @if($recentLogs->isEmpty())
                <div class="text-center py-8 text-gray-400">No recent activity</div>
            @else
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    @foreach($recentLogs as $log)
                        <div class="flex items-center space-x-3 p-2 rounded-lg {{ $log->was_blocked ? 'bg-red-50 dark:bg-red-900/10' : 'bg-gray-50 dark:bg-gray-700/30' }}">
                            <div class="w-2 h-2 rounded-full {{ $log->was_blocked ? 'bg-red-500' : 'bg-green-500' }} flex-shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-semibold {{ $log->was_blocked ? 'text-red-700 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $log->bot_name }}</span>
                                    <span class="text-xs text-gray-400">{{ $log->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate font-mono">{{ Str::limit(parse_url($log->url, PHP_URL_PATH) ?: '/', 50) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
function dailyChart() {
    return {
        init() {
            const ctx = document.getElementById('dailyTrendChart');
            if (!ctx) return;

            const data = @json($dailyTrend);
            const labels = data.map(d => d.date);
            const totals = data.map(d => d.total);
            const blocked = data.map(d => d.blocked);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Total Requests',
                            data: totals,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                        },
                        {
                            label: 'Blocked',
                            data: blocked,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            fill: true,
                            tension: 0.4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280',
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280' },
                            grid: { color: document.documentElement.classList.contains('dark') ? '#374151' : '#f3f4f6' }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280' },
                            grid: { color: document.documentElement.classList.contains('dark') ? '#374151' : '#f3f4f6' }
                        }
                    }
                }
            });
        }
    }
}
</script>
@endpush
