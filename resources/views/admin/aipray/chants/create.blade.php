@extends($adminLayout ?? 'layouts.admin')

@section('title', 'Create Chant')
@section('page-title', 'Aipray - Create Chant')

@section('content')
{{-- Navigation Tabs --}}
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex space-x-6 overflow-x-auto">
        <a href="{{ route('admin.aipray.dashboard') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dashboard</a>
        <a href="{{ route('admin.aipray.dataset.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Dataset</a>
        <a href="{{ route('admin.aipray.record.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Record</a>
        <a href="{{ route('admin.aipray.training.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Training</a>
        <a href="{{ route('admin.aipray.models.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Models</a>
        <a href="{{ route('admin.aipray.evaluate.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Evaluate</a>
        <a href="{{ route('admin.aipray.chants.index') }}" class="whitespace-nowrap border-b-2 border-yellow-500 pb-3 px-1 text-sm font-medium text-yellow-600">Chants</a>
        <a href="{{ route('admin.aipray.donations.index') }}" class="whitespace-nowrap border-b-2 border-transparent pb-3 px-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">Donations</a>
    </nav>
</div>

{{-- Breadcrumb --}}
<div class="mb-6 flex items-center text-sm text-gray-500 dark:text-gray-400">
    <a href="{{ route('admin.aipray.chants.index') }}" class="hover:text-yellow-600">Chants</a>
    <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-gray-900 dark:text-white font-medium">Create New Chant</span>
</div>

<div class="max-w-3xl">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Create New Chant</h3>

        <form action="{{ route('admin.aipray.chants.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Chant ID --}}
                <div>
                    <label for="chant_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chant ID</label>
                    <input type="text" id="chant_id" name="chant_id" value="{{ old('chant_id') }}" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm"
                        placeholder="e.g. morning_chant_01">
                    @error('chant_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Category --}}
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                    <select id="category" name="category" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm">
                        <option value="">-- Select Category --</option>
                        <option value="morning" {{ old('category') === 'morning' ? 'selected' : '' }}>Morning</option>
                        <option value="evening" {{ old('category') === 'evening' ? 'selected' : '' }}>Evening</option>
                        <option value="daily" {{ old('category') === 'daily' ? 'selected' : '' }}>Daily</option>
                        <option value="special" {{ old('category') === 'special' ? 'selected' : '' }}>Special</option>
                        <option value="meditation" {{ old('category') === 'meditation' ? 'selected' : '' }}>Meditation</option>
                    </select>
                    @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Title TH --}}
            <div>
                <label for="title_th" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title (Thai)</label>
                <input type="text" id="title_th" name="title_th" value="{{ old('title_th') }}" required
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm"
                    placeholder="Enter Thai title...">
                @error('title_th') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Title EN --}}
            <div>
                <label for="title_en" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title (English)</label>
                <input type="text" id="title_en" name="title_en" value="{{ old('title_en') }}"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm"
                    placeholder="Enter English title (optional)...">
                @error('title_en') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Lines (JSON) --}}
            <div>
                <label for="lines" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Lines (JSON)
                    <span class="text-xs text-gray-400 font-normal ml-1">Array of strings or objects with "text" field</span>
                </label>
                <textarea id="lines" name="lines" rows="8" required
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm font-mono"
                    placeholder='["Line 1 text", "Line 2 text", "Line 3 text"]'>{{ old('lines') }}</textarea>
                @error('lines') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-400">Enter a valid JSON array. Each element represents one line of the chant.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Author --}}
                <div>
                    <label for="author" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Author</label>
                    <input type="text" id="author" name="author" value="{{ old('author') }}"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm"
                        placeholder="Author name (optional)">
                    @error('author') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Sort Order --}}
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-yellow-500 focus:border-yellow-500 text-sm">
                    @error('sort_order') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Checkboxes --}}
            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_community" value="1" {{ old('is_community') ? 'checked' : '' }}
                        class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500 dark:border-gray-600 dark:bg-gray-700">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Community Chant</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500 dark:border-gray-600 dark:bg-gray-700">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                </label>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.aipray.chants.index') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Cancel</a>
                <button type="submit" class="inline-flex items-center px-6 py-2.5 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Create Chant
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
