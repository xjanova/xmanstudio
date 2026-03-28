@extends($adminLayout ?? 'layouts.admin')

@section('title', 'BitTorrent - Trophies')
@section('page-title', 'LocalVPN - จัดการถ้วยรางวัล')

@section('content')
@include('admin.localvpn._tabs')

{{-- Create Trophy Form --}}
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">เพิ่มถ้วยรางวัลใหม่</h3>
    <form method="POST" action="{{ route('admin.localvpn.torrent.trophies.store') }}" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ไอคอน</label>
                <input type="text" name="icon" placeholder="e.g. star, trophy, medal" value="{{ old('icon') }}"
                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ</label>
                <input type="text" name="name" required placeholder="เช่น Seed Master" value="{{ old('name') }}"
                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ข้อความบนป้าย</label>
                <input type="text" name="badge_text" placeholder="เช่น Seeder" value="{{ old('badge_text') }}"
                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">คำอธิบาย</label>
                <input type="text" name="description" placeholder="คำอธิบายถ้วยรางวัล..." value="{{ old('description') }}"
                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ระดับความยาก</label>
                <select name="difficulty" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
                    <option value="easy" {{ old('difficulty') === 'easy' ? 'selected' : '' }}>Easy</option>
                    <option value="medium" {{ old('difficulty') === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="hard" {{ old('difficulty') === 'hard' ? 'selected' : '' }}>Hard</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">เงื่อนไข (requirement)</label>
                <input type="text" name="requirement" placeholder="เช่น seed 100 hours, share 50 files" value="{{ old('requirement') }}"
                       class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-violet-500 focus:border-violet-500">
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                    <span class="text-sm text-gray-700">ใช้งาน</span>
                </label>
            </div>
        </div>
        <button type="submit" class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition-colors">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            เพิ่มถ้วยรางวัล
        </button>
    </form>
    @if($errors->any())
        <div class="mt-3 text-sm text-red-600">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif
</div>

@php
    $grouped = collect($trophies ?? [])->groupBy('difficulty');
    $difficultyLabels = [
        'easy' => ['label' => 'Easy', 'color' => 'green', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        'medium' => ['label' => 'Medium', 'color' => 'amber', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
        'hard' => ['label' => 'Hard', 'color' => 'red', 'icon' => 'M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z'],
    ];
@endphp

@foreach($difficultyLabels as $difficulty => $meta)
    @php $items = $grouped[$difficulty] ?? collect(); @endphp
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-{{ $meta['color'] }}-100">
                <svg class="w-4 h-4 text-{{ $meta['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $meta['icon'] }}"/></svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">{{ $meta['label'] }}</h3>
            <span class="text-sm text-gray-500">({{ $items->count() }})</span>
        </div>

        @if($items->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($items as $trophy)
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-5 relative group">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white text-lg">
                            @if($trophy->icon)
                                <span>{{ $trophy->icon }}</span>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                            @endif
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900">{{ $trophy->name }}</h4>
                            @if($trophy->badge_text)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-violet-100 text-violet-800">
                                    {{ $trophy->badge_text }}
                                </span>
                            @endif
                        </div>
                    </div>
                    @if(!$trophy->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">ปิด</span>
                    @endif
                </div>

                @if($trophy->description)
                    <p class="text-sm text-gray-600 mb-2">{{ $trophy->description }}</p>
                @endif

                @if($trophy->requirement)
                    <p class="text-xs text-gray-500 mb-3">
                        <span class="font-medium">เงื่อนไข:</span> {{ $trophy->requirement }}
                    </p>
                @endif

                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <form method="POST" action="{{ route('admin.localvpn.torrent.trophies.toggle', $trophy) }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1 text-xs font-medium rounded-lg transition-colors {{ $trophy->is_active ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                            {{ $trophy->is_active ? 'ปิด' : 'เปิด' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.localvpn.torrent.trophies.destroy', $trophy) }}"
                          onsubmit="return confirm('ลบถ้วยรางวัล \'{{ $trophy->name }}\'?')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-1 text-xs font-medium rounded-lg bg-red-100 text-red-700 hover:bg-red-200 transition-colors">ลบ</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 text-center text-gray-500">
            ยังไม่มีถ้วยรางวัลระดับ {{ $meta['label'] }}
        </div>
        @endif
    </div>
@endforeach
@endsection
