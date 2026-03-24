@extends('layouts.admin')

@section('title', 'Aipray Evaluate')
@section('page-title', 'Aipray - Model Evaluation')

@section('content')
{{-- Navigation Tabs --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-6 overflow-x-auto">
        <a href="{{ route('admin.aipray.dashboard') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dashboard</a>
        <a href="{{ route('admin.aipray.dataset.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dataset</a>
        <a href="{{ route('admin.aipray.record.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Record</a>
        <a href="{{ route('admin.aipray.training.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Training</a>
        <a href="{{ route('admin.aipray.models.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Models</a>
        <a href="{{ route('admin.aipray.evaluate.index') }}" class="whitespace-nowrap border-b-2 border-yellow-500 pb-3 px-1 text-sm font-medium text-yellow-600">Evaluate</a>
        <a href="{{ route('admin.aipray.chants.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Chants</a>
        <a href="{{ route('admin.aipray.donations.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Donations</a>
    </nav>
</div>

{{-- Header --}}
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-yellow-700 via-yellow-600 to-amber-500 p-8 mb-8 shadow-xl">
    <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
    <div class="relative">
        <h1 class="text-2xl md:text-3xl font-bold text-white">Model Evaluation</h1>
        <p class="text-yellow-100 text-lg">Test models against reference audio and text</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- Left: Evaluation Form --}}
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Run Evaluation</h3>

            <form action="{{ route('admin.aipray.evaluate.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div>
                    <label for="model_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model</label>
                    <select id="model_id" name="model_id" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm">
                        <option value="">-- Select Model --</option>
                        @foreach($models as $model)
                            <option value="{{ $model->id }}" {{ old('model_id') == $model->id ? 'selected' : '' }}>
                                {{ $model->name }} (v{{ $model->version ?? '1.0' }})
                            </option>
                        @endforeach
                    </select>
                    @error('model_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="audio_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Audio File</label>
                    <input type="file" id="audio_file" name="audio_file" accept="audio/*" required
                        class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-yellow-50 file:text-yellow-700 hover:file:bg-yellow-100 dark:file:bg-yellow-900/30 dark:file:text-yellow-400">
                    @error('audio_file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="reference_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reference Text</label>
                    <textarea id="reference_text" name="reference_text" rows="4" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm font-mono"
                        placeholder="Enter the correct transcription...">{{ old('reference_text') }}</textarea>
                    @error('reference_text') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Run Evaluation
                </button>
            </form>
        </div>
    </div>

    {{-- Right: Results Table --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Evaluation Results</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Model</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">WER</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">CER</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hypothesis</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($evaluations as $eval)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $eval->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $eval->model->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ ($eval->wer ?? 1) < 0.2 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : (($eval->wer ?? 1) < 0.5 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400') }}">
                                        {{ number_format(($eval->wer ?? 0) * 100, 1) }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ ($eval->cer ?? 1) < 0.1 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : (($eval->cer ?? 1) < 0.3 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400') }}">
                                        {{ number_format(($eval->cer ?? 0) * 100, 1) }}%
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs" title="{{ $eval->reference_text }}">{{ Str::limit($eval->reference_text, 40) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs" title="{{ $eval->hypothesis_text }}">{{ Str::limit($eval->hypothesis_text, 40) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                    <p class="font-medium">No evaluations yet</p>
                                    <p class="mt-1">Upload an audio file and reference text to evaluate a model.</p>
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
