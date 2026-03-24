@extends('layouts.admin')

@section('title', 'Sample Detail')
@section('page-title', 'Aipray - Sample Detail')

@section('content')
{{-- Navigation Tabs --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-6 overflow-x-auto">
        <a href="{{ route('admin.aipray.dashboard') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dashboard</a>
        <a href="{{ route('admin.aipray.dataset.index') }}" class="whitespace-nowrap border-b-2 border-yellow-500 pb-3 px-1 text-sm font-medium text-yellow-600">Dataset</a>
        <a href="{{ route('admin.aipray.record.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Record</a>
        <a href="{{ route('admin.aipray.training.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Training</a>
        <a href="{{ route('admin.aipray.models.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Models</a>
        <a href="{{ route('admin.aipray.evaluate.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Evaluate</a>
        <a href="{{ route('admin.aipray.chants.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Chants</a>
        <a href="{{ route('admin.aipray.donations.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Donations</a>
    </nav>
</div>

{{-- Breadcrumb --}}
<div class="mb-6 flex items-center text-sm text-gray-500 dark:text-gray-400">
    <a href="{{ route('admin.aipray.dataset.index') }}" class="hover:text-yellow-600">Dataset</a>
    <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-gray-900 dark:text-white font-medium">Sample #{{ $sample->id }}</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    {{-- Left Column: Audio Player & Info --}}
    <div class="lg:col-span-1 space-y-6">
        {{-- Audio Player --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Audio Player</h3>
            <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-4">
                <audio controls class="w-full" preload="metadata">
                    <source src="{{ asset('storage/' . $sample->file_path) }}" type="audio/wav">
                    Your browser does not support the audio element.
                </audio>
            </div>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Filename</span>
                    <span class="text-gray-900 dark:text-white font-mono text-xs truncate ml-2 max-w-[200px]" title="{{ $sample->filename }}">{{ $sample->filename }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Duration</span>
                    <span class="text-gray-900 dark:text-white">{{ number_format($sample->duration_seconds ?? 0, 1) }}s</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Sample Rate</span>
                    <span class="text-gray-900 dark:text-white">{{ $sample->sample_rate ?? '16000' }} Hz</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Device</span>
                    <span class="text-gray-900 dark:text-white">{{ $sample->device ?? 'Unknown' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Chant ID</span>
                    <span class="text-gray-900 dark:text-white">{{ $sample->chant_id ?? '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Created</span>
                    <span class="text-gray-900 dark:text-white">{{ $sample->created_at->format('Y-m-d H:i') }}</span>
                </div>
            </div>
        </div>

        {{-- Current Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Current Status</h3>
            <div class="text-center py-4">
                @if($sample->status === 'verified')
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Verified
                    </span>
                @elseif($sample->status === 'pending')
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Pending Review
                    </span>
                @elseif($sample->status === 'rejected')
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Rejected
                    </span>
                @else
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                        {{ ucfirst($sample->status) }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Right Column: Edit Form --}}
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Edit Sample</h3>

            <form action="{{ route('admin.aipray.dataset.update', $sample) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-6">
                    {{-- Transcript --}}
                    <div>
                        <label for="transcript" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Transcript</label>
                        <textarea id="transcript" name="transcript" rows="6"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm font-mono"
                            placeholder="Enter the transcript for this audio sample...">{{ old('transcript', $sample->transcript) }}</textarea>
                        @error('transcript')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select id="status" name="status"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm">
                            <option value="pending" {{ old('status', $sample->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="verified" {{ old('status', $sample->status) === 'verified' ? 'selected' : '' }}>Verified</option>
                            <option value="rejected" {{ old('status', $sample->status) === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Category --}}
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                        <select id="category" name="category"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm">
                            <option value="">-- Select Category --</option>
                            <option value="morning" {{ old('category', $sample->category) === 'morning' ? 'selected' : '' }}>Morning</option>
                            <option value="evening" {{ old('category', $sample->category) === 'evening' ? 'selected' : '' }}>Evening</option>
                            <option value="special" {{ old('category', $sample->category) === 'special' ? 'selected' : '' }}>Special</option>
                            <option value="daily" {{ old('category', $sample->category) === 'daily' ? 'selected' : '' }}>Daily</option>
                        </select>
                        @error('category')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea id="notes" name="notes" rows="3"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm"
                            placeholder="Any additional notes...">{{ old('notes', $sample->notes) }}</textarea>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <a href="{{ route('admin.aipray.dataset.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Back to Dataset</a>
                        <div class="flex gap-3">
                            <form action="{{ route('admin.aipray.dataset.destroy', $sample) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this sample?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Delete
                                </button>
                            </form>
                            <button type="submit" class="inline-flex items-center px-6 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
