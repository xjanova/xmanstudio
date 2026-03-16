@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แก้ไขช่อง - ' . $channel->name)
@section('page-title', 'แก้ไขช่อง YouTube')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center gap-4 mb-6">
            @if($channel->channel_thumbnail_url)
                <img src="{{ $channel->channel_thumbnail_url }}" alt="{{ $channel->name }}" class="w-16 h-16 rounded-full">
            @endif
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $channel->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $channel->youtube_channel_id }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.metal-x.channels.update', $channel) }}">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อช่อง</label>
                <input type="text" name="name" value="{{ old('name', $channel->name) }}" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">บันทึก</button>
                <a href="{{ route('admin.metal-x.channels.index') }}" class="px-6 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>
@endsection
