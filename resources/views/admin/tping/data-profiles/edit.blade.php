@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แก้ไข ' . $profile->name . ' - Admin')
@section('page-title', 'แก้ไข Data Profile')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.tping.data-profiles.show', $profile) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-600">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        กลับไปรายละเอียด
    </a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl text-sm">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-xl text-sm">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('admin.tping.data-profiles.update', $profile) }}">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-gray-100">
        <h2 class="text-lg font-bold text-gray-900 mb-4">ข้อมูล Data Profile</h2>
        <p class="text-xs text-gray-400 mb-4">
            เจ้าของ: {{ $profile->user->name ?? 'Unknown' }} ({{ $profile->user->email ?? '-' }}) &middot;
            ID: #{{ $profile->id }}
        </p>

        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ Profile</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $profile->name) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" required>
                </div>
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
                    <input type="text" name="category" id="category" value="{{ old('category', $profile->category) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                </div>
            </div>

            <div>
                <label for="fields_json" class="block text-sm font-medium text-gray-700 mb-1">Fields JSON</label>
                <textarea name="fields_json" id="fields_json" rows="15"
                    class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 font-mono text-xs">{{ old('fields_json', json_encode(json_decode($profile->fields_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="px-6 py-2.5 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 transition-all">
            บันทึก
        </button>
        <a href="{{ route('admin.tping.data-profiles.show', $profile) }}" class="px-6 py-2.5 bg-gray-100 text-gray-600 rounded-xl font-medium hover:bg-gray-200 transition-all">
            ยกเลิก
        </a>
    </div>
</form>
@endsection
