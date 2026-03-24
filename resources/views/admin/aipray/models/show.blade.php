@extends('layouts.admin')

@section('title', 'Model: ' . $model->name)
@section('page-title', 'Aipray - Model Detail')

@section('content')
{{-- Navigation Tabs --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-6 overflow-x-auto">
        <a href="{{ route('admin.aipray.dashboard') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dashboard</a>
        <a href="{{ route('admin.aipray.dataset.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dataset</a>
        <a href="{{ route('admin.aipray.record.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Record</a>
        <a href="{{ route('admin.aipray.training.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Training</a>
        <a href="{{ route('admin.aipray.models.index') }}" class="whitespace-nowrap border-b-2 border-yellow-500 pb-3 px-1 text-sm font-medium text-yellow-600">Models</a>
        <a href="{{ route('admin.aipray.evaluate.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Evaluate</a>
        <a href="{{ route('admin.aipray.chants.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Chants</a>
        <a href="{{ route('admin.aipray.donations.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Donations</a>
    </nav>
</div>

{{-- Breadcrumb --}}
<div class="mb-6 flex items-center text-sm text-gray-500 dark:text-gray-400">
    <a href="{{ route('admin.aipray.models.index') }}" class="hover:text-yellow-600">Models</a>
    <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-gray-900 dark:text-white font-medium">{{ $model->name }}</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- Left: Model Info --}}
    <div class="lg:col-span-1 space-y-6">
        {{-- Overview Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Model Info</h3>
                @if($model->status === 'deployed')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Deployed</span>
                @elseif($model->status === 'ready')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Ready</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ ucfirst($model->status) }}</span>
                @endif
            </div>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Name</dt><dd class="text-gray-900 dark:text-white font-medium">{{ $model->name }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Version</dt><dd class="text-gray-900 dark:text-white font-mono">v{{ $model->version ?? '1.0' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Base Model</dt><dd class="text-gray-900 dark:text-white font-mono text-xs">{{ $model->base_model ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">File Size</dt><dd class="text-gray-900 dark:text-white">{{ $model->file_size ? number_format($model->file_size / 1048576, 1) . ' MB' : '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Created</dt><dd class="text-gray-900 dark:text-white">{{ $model->created_at->format('Y-m-d H:i') }}</dd></div>
            </dl>
        </div>

        {{-- Metrics Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performance Metrics</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-500 dark:text-gray-400">Accuracy</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $model->accuracy ? number_format($model->accuracy * 100, 1) . '%' : '-' }}</span>
                    </div>
                    @if($model->accuracy)
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ $model->accuracy * 100 }}%"></div>
                        </div>
                    @endif
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-500 dark:text-gray-400">WER (Word Error Rate)</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $model->wer !== null ? number_format($model->wer * 100, 1) . '%' : '-' }}</span>
                    </div>
                    @if($model->wer !== null)
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-yellow-500 h-2 rounded-full" style="width: {{ min($model->wer * 100, 100) }}%"></div>
                        </div>
                    @endif
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-500 dark:text-gray-400">CER (Char Error Rate)</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $model->cer !== null ? number_format($model->cer * 100, 1) . '%' : '-' }}</span>
                    </div>
                    @if($model->cer !== null)
                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-orange-500 h-2 rounded-full" style="width: {{ min($model->cer * 100, 100) }}%"></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6 space-y-3">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Actions</h3>
            @if($model->status === 'ready')
                <form action="{{ route('admin.aipray.models.deploy', $model) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Deploy Model
                    </button>
                </form>
            @endif
            @if(in_array($model->status, ['ready', 'deployed']))
                <form action="{{ route('admin.aipray.models.export', $model) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export as ONNX
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Right: Training Job & Evaluations --}}
    <div class="lg:col-span-2 space-y-8">
        {{-- Training Job Info --}}
        @if($model->trainingJob)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Training Job</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Job Name</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $model->trainingJob->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Epochs</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $model->trainingJob->epochs }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Batch Size</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $model->trainingJob->batch_size }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Learning Rate</p>
                        <p class="font-medium text-gray-900 dark:text-white font-mono">{{ $model->trainingJob->learning_rate }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Duration</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $model->trainingJob->duration ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Final Loss</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $model->trainingJob->final_loss !== null ? number_format($model->trainingJob->final_loss, 4) : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Status</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ ucfirst($model->trainingJob->status) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-gray-400">Completed</p>
                        <p class="font-medium text-gray-900 dark:text-white">{{ $model->trainingJob->completed_at ? $model->trainingJob->completed_at->format('Y-m-d H:i') : '-' }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Evaluations --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Evaluations</h3>
                <a href="{{ route('admin.aipray.evaluate.index') }}" class="text-sm text-yellow-600 hover:text-yellow-700 font-medium">Run Evaluation</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">WER</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">CER</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hypothesis</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($model->evaluations ?? [] as $eval)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $eval->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ number_format($eval->wer * 100, 1) }}%</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ number_format($eval->cer * 100, 1) }}%</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ Str::limit($eval->reference_text, 50) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ Str::limit($eval->hypothesis_text, 50) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No evaluations yet for this model.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
