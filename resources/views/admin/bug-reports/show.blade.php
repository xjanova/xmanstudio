@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Bug Report #' . $report->id)
@section('page-title', 'Bug Report #' . $report->id)

@section('content')
<div class="space-y-6">
    <!-- Back + Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.bug-reports.index') }}" class="inline-flex items-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            กลับไปรายการ
        </a>
        <div class="flex items-center space-x-2">
            @if($report->github_issue_url)
                <a href="{{ $report->github_issue_url }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-xs font-medium bg-gray-900 text-white rounded-lg hover:bg-gray-700 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                    GitHub #{{ $report->github_issue_number }}
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Report Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                <div class="flex items-start justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $report->title }}</h2>
                    @switch($report->status)
                        @case('new')
                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">New</span>
                            @break
                        @case('in_progress')
                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">In Progress</span>
                            @break
                        @case('fixed')
                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">Fixed</span>
                            @break
                        @default
                            <span class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300">{{ $report->status }}</span>
                    @endswitch
                </div>
                <div class="prose dark:prose-invert max-w-none text-sm text-gray-700 dark:text-gray-300">
                    {!! nl2br(e($report->description)) !!}
                </div>
            </div>

            <!-- SMS Misclassification Details -->
            @if($report->report_type === 'misclassification' && $report->metadata)
                @php $meta = $report->metadata; @endphp
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-orange-200 dark:border-orange-900/50 p-6">
                    <h3 class="text-lg font-bold text-orange-700 dark:text-orange-400 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                        SMS Misclassification Details
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Bank</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $meta['bank'] ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Amount</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $meta['amount'] ?? 'N/A' }} THB</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Issue Type</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $meta['issue_type'] ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Detected Type</p>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">{{ $meta['detected_type'] ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Correct Type</p>
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">{{ $meta['correct_type'] ?? 'N/A' }}</span>
                        </div>
                        @if(!empty($meta['sender']))
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Sender</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white font-mono">{{ $meta['sender'] }}</p>
                            </div>
                        @endif
                    </div>

                    @if(!empty($meta['original_message']))
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Original SMS</p>
                            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm overflow-x-auto whitespace-pre-wrap font-mono">{{ $meta['original_message'] }}</pre>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Stack Trace -->
            @if($report->stack_trace)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Stack Trace</h3>
                    <pre class="bg-gray-900 text-red-400 p-4 rounded-lg text-xs overflow-x-auto whitespace-pre-wrap font-mono max-h-96">{{ $report->stack_trace }}</pre>
                </div>
            @endif

            <!-- Raw Metadata -->
            @if($report->metadata || $report->additional_info)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Raw Data</h3>
                    @if($report->metadata)
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Metadata</p>
                        <pre class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg text-xs overflow-x-auto font-mono text-gray-700 dark:text-gray-300 mb-4">{{ json_encode($report->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif
                    @if($report->additional_info)
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-1">Additional Info</p>
                        <pre class="bg-gray-50 dark:bg-gray-900 p-3 rounded-lg text-xs overflow-x-auto font-mono text-gray-700 dark:text-gray-300">{{ json_encode($report->additional_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Product</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $report->product_name }} {{ $report->product_version ? 'v'.$report->product_version : '' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">App Version</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $report->app_version ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">OS</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $report->os_version ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Device ID</p>
                    <p class="text-xs font-mono text-gray-700 dark:text-gray-300 break-all">{{ $report->device_id ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Priority</p>
                    @switch($report->priority)
                        @case('critical')
                            <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300">Critical</span>
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
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Severity</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($report->severity) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Created</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $report->created_at->format('d/m/Y H:i:s') }}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase">Actions</h3>

                @if(!$report->is_analyzed)
                    <form method="POST" action="{{ route('admin.bug-reports.analyze', $report) }}">
                        @csrf
                        <textarea name="analysis_notes" rows="2" placeholder="Analysis notes (optional)..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm mb-2"></textarea>
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                            Mark as Analyzed
                        </button>
                    </form>
                @else
                    <div class="flex items-center text-green-600 dark:text-green-400 text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Analyzed {{ $report->analyzed_at?->format('d/m/Y') }}
                    </div>
                    @if($report->analysis_notes)
                        <p class="text-xs text-gray-500 dark:text-gray-400 italic">{{ $report->analysis_notes }}</p>
                    @endif
                @endif

                <hr class="border-gray-200 dark:border-gray-700">

                @if(!$report->is_fixed)
                    <form method="POST" action="{{ route('admin.bug-reports.fix', $report) }}">
                        @csrf
                        <input type="text" name="fixed_in_version" placeholder="Fixed in version (e.g. 1.2.3)" required
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm mb-2">
                        <textarea name="fix_notes" rows="2" placeholder="Fix notes (optional)..."
                                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm mb-2"></textarea>
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition">
                            Mark as Fixed
                        </button>
                    </form>
                @else
                    <div class="flex items-center text-green-600 dark:text-green-400 text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Fixed in v{{ $report->fixed_in_version }} ({{ $report->fixed_at?->format('d/m/Y') }})
                    </div>
                    @if($report->fix_notes)
                        <p class="text-xs text-gray-500 dark:text-gray-400 italic">{{ $report->fix_notes }}</p>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<script>
    // Simple toast notification
    document.addEventListener('DOMContentLoaded', function() {
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in-up';
        toast.textContent = @json(session('success'));
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    });
</script>
@endif
@endsection
