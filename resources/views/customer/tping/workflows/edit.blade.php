@extends($customerLayout ?? 'layouts.customer')

@section('title', 'แก้ไข ' . $workflow->name)
@section('page-title', 'แก้ไข Workflow')

@section('content')
<!-- Back Link -->
<div class="mb-6">
    <a href="{{ route('customer.tping.workflows.show', $workflow) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-cyan-600 transition-colors">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        กลับไปรายละเอียด
    </a>
</div>

<div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 max-w-2xl">
    <h2 class="text-xl font-bold text-gray-900 mb-6">แก้ไข Workflow</h2>

    <form method="POST" action="{{ route('customer.tping.workflows.update', $workflow) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ Workflow</label>
            <input type="text" name="name" id="name" value="{{ old('name', $workflow->name) }}" required
                   class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
            @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="target_app_name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อแอพเป้าหมาย</label>
            <input type="text" name="target_app_name" id="target_app_name" value="{{ old('target_app_name', $workflow->target_app_name) }}"
                   class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500">
        </div>

        <div>
            <label for="target_app_package" class="block text-sm font-medium text-gray-700 mb-1">Package Name</label>
            <input type="text" name="target_app_package" id="target_app_package" value="{{ old('target_app_package', $workflow->target_app_package) }}"
                   class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500"
                   placeholder="com.example.app">
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-xl text-sm font-medium hover:shadow-lg transition-all">
                บันทึกการเปลี่ยนแปลง
            </button>
            <a href="{{ route('customer.tping.workflows.show', $workflow) }}" class="px-6 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200 transition-all">
                ยกเลิก
            </a>
        </div>
    </form>
</div>
@endsection
