@extends($adminLayout ?? 'layouts.admin')

@section('title', 'แก้ไข ' . $workflow->name . ' - Admin')
@section('page-title', 'แก้ไข Workflow')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.tping.workflows.show', $workflow) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-600">
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

<form method="POST" action="{{ route('admin.tping.workflows.update', $workflow) }}">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6 border border-gray-100">
        <h2 class="text-lg font-bold text-gray-900 mb-4">ข้อมูล Workflow</h2>
        <p class="text-xs text-gray-400 mb-4">
            เจ้าของ: {{ $workflow->user->name ?? 'Unknown' }} ({{ $workflow->user->email ?? '-' }}) &middot;
            ID: #{{ $workflow->id }}
        </p>

        <div class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ Workflow</label>
                <input type="text" name="name" id="name" value="{{ old('name', $workflow->name) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500" required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="target_app_package" class="block text-sm font-medium text-gray-700 mb-1">App Package</label>
                    <input type="text" name="target_app_package" id="target_app_package" value="{{ old('target_app_package', $workflow->target_app_package) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                </div>
                <div>
                    <label for="target_app_name" class="block text-sm font-medium text-gray-700 mb-1">App Name</label>
                    <input type="text" name="target_app_name" id="target_app_name" value="{{ old('target_app_name', $workflow->target_app_name) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
                </div>
            </div>

            <div>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_public" value="1" {{ $workflow->is_public ? 'checked' : '' }}
                        class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500">
                    <span class="text-sm text-gray-700">เปิดสาธารณะ (Public)</span>
                </label>
            </div>

            <div>
                <label for="steps_json" class="block text-sm font-medium text-gray-700 mb-1">Steps JSON</label>
                <textarea name="steps_json" id="steps_json" rows="15"
                    class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 font-mono text-xs">{{ old('steps_json', json_encode(json_decode($workflow->steps_json), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="px-6 py-2.5 bg-cyan-600 text-white rounded-xl font-medium hover:bg-cyan-700 transition-all">
            บันทึก
        </button>
        <a href="{{ route('admin.tping.workflows.show', $workflow) }}" class="px-6 py-2.5 bg-gray-100 text-gray-600 rounded-xl font-medium hover:bg-gray-200 transition-all">
            ยกเลิก
        </a>
    </div>
</form>
@endsection
