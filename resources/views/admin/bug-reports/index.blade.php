@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Bug Reports')
@section('page-title', 'Bug Reports')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-red-600 via-orange-600 to-amber-500 p-8 shadow-2xl">
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-bold text-white">Bug Reports</h1>
                </div>
                <p class="text-orange-100 text-lg">รายงาน Bug & SMS Misclassification จาก SmsChecker App</p>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('admin.bug-reports.index') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ !request('status') && !request('report_type') ? 'ring-2 ring-blue-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $counts['all'] }}</p>
        </a>
        <a href="{{ route('admin.bug-reports.index', ['status' => 'new']) }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ request('status') === 'new' ? 'ring-2 ring-yellow-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">New</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $counts['new'] }}</p>
        </a>
        <a href="{{ route('admin.bug-reports.index', ['report_type' => 'misclassification']) }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ request('report_type') === 'misclassification' ? 'ring-2 ring-orange-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">SMS Misclassification</p>
            <p class="text-2xl font-bold text-orange-600">{{ $counts['misclassification'] }}</p>
        </a>
        <a href="{{ route('admin.bug-reports.index', ['status' => 'fixed']) }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition {{ request('status') === 'fixed' ? 'ring-2 ring-green-500' : '' }}">
            <p class="text-sm text-gray-500 dark:text-gray-400">Fixed</p>
            <p class="text-2xl font-bold text-green-600">{{ $counts['fixed'] }}</p>
        </a>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 border border-gray-100 dark:border-gray-700">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Title, description, device ID..."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">ประเภท</label>
                <select name="report_type" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="misclassification" {{ request('report_type') === 'misclassification' ? 'selected' : '' }}>SMS Misclassification</option>
                    <option value="bug" {{ request('report_type') === 'bug' ? 'selected' : '' }}>Bug</option>
                    <option value="crash" {{ request('report_type') === 'crash' ? 'selected' : '' }}>Crash</option>
                    <option value="feature_request" {{ request('report_type') === 'feature_request' ? 'selected' : '' }}>Feature Request</option>
                    <option value="performance" {{ request('report_type') === 'performance' ? 'selected' : '' }}>Performance</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">สถานะ</label>
                <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="new" {{ request('status') === 'new' ? 'selected' : '' }}>New</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="fixed" {{ request('status') === 'fixed' ? 'selected' : '' }}>Fixed</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                    <option value="wont_fix" {{ request('status') === 'wont_fix' ? 'selected' : '' }}>Won't Fix</option>
                </select>
            </div>
            @if($products->isNotEmpty())
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Product</label>
                <select name="product_name" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm">
                    <option value="">ทั้งหมด</option>
                    @foreach($products as $product)
                        <option value="{{ $product }}" {{ request('product_name') === $product ? 'selected' : '' }}>{{ $product }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                ค้นหา
            </button>
            @if(request()->hasAny(['search', 'report_type', 'status', 'product_name']))
                <a href="{{ route('admin.bug-reports.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium hover:bg-gray-300 transition">
                    ล้าง
                </a>
            @endif
        </form>
    </div>

    <!-- Reports Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Priority</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">Analyzed</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase">วันที่</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($reports as $report)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">
                            #{{ $report->id }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.bug-reports.show', $report) }}" class="text-sm font-semibold text-blue-600 dark:text-blue-400 hover:underline">
                                {{ Str::limit($report->title, 50) }}
                            </a>
                            @if($report->report_type === 'misclassification' && $report->metadata)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $report->metadata['bank'] ?? '' }} &middot; {{ $report->metadata['amount'] ?? '' }} THB
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ $report->product_name }}</span>
                            @if($report->app_version)
                                <div class="text-xs text-gray-400">v{{ $report->app_version }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @switch($report->report_type)
                                @case('misclassification')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300">SMS Misclass</span>
                                    @break
                                @case('bug')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">Bug</span>
                                    @break
                                @case('crash')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">Crash</span>
                                    @break
                                @case('feature_request')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">Feature</span>
                                    @break
                                @default
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">{{ $report->report_type }}</span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3">
                            @switch($report->priority)
                                @case('critical')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 animate-pulse">Critical</span>
                                    @break
                                @case('high')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300">High</span>
                                    @break
                                @case('medium')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">Medium</span>
                                    @break
                                @default
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">Low</span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3">
                            @switch($report->status)
                                @case('new')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">New</span>
                                    @break
                                @case('in_progress')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">In Progress</span>
                                    @break
                                @case('fixed')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">Fixed</span>
                                    @break
                                @case('closed')
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">Closed</span>
                                    @break
                                @default
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">{{ $report->status }}</span>
                            @endswitch
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($report->is_analyzed)
                                <span class="text-green-500" title="Analyzed">&#10003;</span>
                            @else
                                <span class="text-gray-300 dark:text-gray-600">&mdash;</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $report->created_at->format('d/m/Y') }}<br>
                            {{ $report->created_at->format('H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.bug-reports.show', $report) }}" class="px-3 py-1.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 transition">
                                ดูรายละเอียด
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 font-medium">ไม่พบ bug reports</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reports->hasPages())
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            {{ $reports->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
