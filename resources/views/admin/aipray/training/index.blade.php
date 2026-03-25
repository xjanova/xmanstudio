@extends( ?? 'layouts.admin')

@section('title', 'Aipray Training')
@section('page-title', 'Aipray - Model Training')

@section('content')
{{-- Navigation Tabs --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-6 overflow-x-auto">
        <a href="{{ route('admin.aipray.dashboard') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dashboard</a>
        <a href="{{ route('admin.aipray.dataset.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dataset</a>
        <a href="{{ route('admin.aipray.record.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Record</a>
        <a href="{{ route('admin.aipray.training.index') }}" class="whitespace-nowrap border-b-2 border-yellow-500 pb-3 px-1 text-sm font-medium text-yellow-600">Training</a>
        <a href="{{ route('admin.aipray.models.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Models</a>
        <a href="{{ route('admin.aipray.evaluate.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Evaluate</a>
        <a href="{{ route('admin.aipray.chants.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Chants</a>
        <a href="{{ route('admin.aipray.donations.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Donations</a>
    </nav>
</div>

{{-- Header --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-yellow-700 via-yellow-600 to-amber-500 p-8 mb-8 shadow-xl">
    <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">Model Training</h1>
            <p class="text-yellow-100 text-lg">Train and fine-tune speech recognition models</p>
        </div>
        <div class="flex items-center gap-3">
            {{-- ML Health --}}
            <div class="inline-flex items-center px-4 py-2.5 bg-white/20 backdrop-blur-sm text-white font-medium rounded-xl border border-white/25">
                @if(($mlHealth ?? 'unknown') === 'healthy')
                    <span class="w-3 h-3 bg-green-400 rounded-full mr-2 animate-pulse"></span> ML: Healthy
                @elseif(($mlHealth ?? 'unknown') === 'degraded')
                    <span class="w-3 h-3 bg-yellow-400 rounded-full mr-2 animate-pulse"></span> ML: Degraded
                @else
                    <span class="w-3 h-3 bg-red-400 rounded-full mr-2"></span> ML: Offline
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Verified Samples</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['verified_samples'] ?? 0) }}</p>
    </div>
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Jobs</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_jobs'] ?? 0) }}</p>
    </div>
    <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-lg border border-gray-100 dark:border-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Active Jobs</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['active_jobs'] ?? 0) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- New Training Job Form --}}
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Start New Training Job</h3>

            <form action="{{ route('admin.aipray.training.store') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Job Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm"
                        placeholder="e.g. thai-chant-v2">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="base_model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Base Model</label>
                    <select id="base_model" name="base_model" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm">
                        <option value="openai/whisper-small">Whisper Small</option>
                        <option value="openai/whisper-medium">Whisper Medium</option>
                        <option value="openai/whisper-large-v3">Whisper Large v3</option>
                        <option value="wav2vec2-base">Wav2Vec2 Base</option>
                    </select>
                    @error('base_model') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="epochs" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Epochs</label>
                    <input type="number" id="epochs" name="epochs" value="{{ old('epochs', 10) }}" min="1" max="100" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm">
                    @error('epochs') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="batch_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Batch Size</label>
                    <input type="number" id="batch_size" name="batch_size" value="{{ old('batch_size', 8) }}" min="1" max="64" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm">
                    @error('batch_size') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="learning_rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Learning Rate</label>
                    <input type="number" id="learning_rate" name="learning_rate" value="{{ old('learning_rate', 0.0001) }}" step="0.00001" min="0.000001" max="1" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm">
                    @error('learning_rate') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors mt-2"
                    {{ ($mlHealth ?? 'unknown') !== 'healthy' ? 'disabled' : '' }}>
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Start Training
                </button>

                @if(($mlHealth ?? 'unknown') !== 'healthy')
                    <p class="text-xs text-red-500 text-center mt-2">ML service must be healthy to start training.</p>
                @endif
            </form>
        </div>
    </div>

    {{-- Jobs List --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Training Jobs</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Base Model</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Progress</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Epoch</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Started</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700" id="jobs-tbody">
                        @forelse($jobs as $job)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50" data-job-id="{{ $job->id }}">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $job->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $job->base_model }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($job->status === 'completed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Completed</span>
                                    @elseif($job->status === 'running')
                                        <span class="job-status inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Running</span>
                                    @elseif($job->status === 'failed')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Failed</span>
                                    @elseif($job->status === 'queued')
                                        <span class="job-status inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">Queued</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ ucfirst($job->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 w-24">
                                            <div class="job-progress bg-yellow-500 h-2 rounded-full transition-all duration-500" style="width: {{ $job->progress ?? 0 }}%"></div>
                                        </div>
                                        <span class="job-progress-text text-xs text-gray-500 dark:text-gray-400 w-10 text-right">{{ $job->progress ?? 0 }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="job-epoch">{{ $job->current_epoch ?? 0 }}</span>/{{ $job->epochs }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $job->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No training jobs yet. Configure and start a new job.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Poll running jobs for progress
    function pollJobs() {
        const rows = document.querySelectorAll('#jobs-tbody tr[data-job-id]');
        rows.forEach(function (row) {
            const statusEl = row.querySelector('.job-status');
            if (!statusEl) return; // only poll running/queued jobs

            const jobId = row.dataset.jobId;
            fetch("{{ url('/admin/aipray/training') }}/" + jobId + "/progress", {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(r => r.json())
            .then(data => {
                const progressBar = row.querySelector('.job-progress');
                const progressText = row.querySelector('.job-progress-text');
                const epochEl = row.querySelector('.job-epoch');

                if (progressBar) progressBar.style.width = (data.progress || 0) + '%';
                if (progressText) progressText.textContent = (data.progress || 0) + '%';
                if (epochEl) epochEl.textContent = data.current_epoch || 0;

                if (data.status === 'completed' || data.status === 'failed') {
                    location.reload();
                }
            })
            .catch(() => {});
        });
    }

    // Poll every 5 seconds if there are active jobs
    const hasActiveJobs = document.querySelectorAll('#jobs-tbody .job-status').length > 0;
    if (hasActiveJobs) {
        setInterval(pollJobs, 5000);
    }
});
</script>
@endpush
