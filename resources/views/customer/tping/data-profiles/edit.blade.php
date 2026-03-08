@extends($customerLayout ?? 'layouts.customer')

@section('title', 'แก้ไข ' . $profile->name)
@section('page-title', 'แก้ไข Data Profile')

@section('content')
<!-- Back Link -->
<div class="mb-6">
    <a href="{{ route('customer.tping.data-profiles.show', $profile) }}" class="inline-flex items-center text-sm text-gray-500 hover:text-emerald-600 transition-colors">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        กลับไปรายละเอียด
    </a>
</div>

<div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 max-w-3xl">
    <h2 class="text-xl font-bold text-gray-900 mb-6">แก้ไข Data Profile</h2>

    <form method="POST" action="{{ route('customer.tping.data-profiles.update', $profile) }}" class="space-y-5" id="profileForm">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ Profile</label>
            <input type="text" name="name" id="name" value="{{ old('name', $profile->name) }}" required
                   class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
            @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
            <input type="text" name="category" id="category" value="{{ old('category', $profile->category) }}"
                   class="w-full rounded-xl border-gray-200 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                   placeholder="เช่น ข้อมูลส่วนตัว, ที่อยู่">
        </div>

        <!-- Dynamic Fields -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-medium text-gray-700">ข้อมูล (Fields)</label>
                <button type="button" onclick="addField()" class="px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg text-xs font-medium hover:bg-emerald-100 transition-all">
                    + เพิ่ม Field
                </button>
            </div>
            <div id="fieldsContainer" class="space-y-2">
                @forelse($fields as $key => $value)
                    <div class="flex items-center gap-2 field-row">
                        <input type="text" name="field_keys[]" value="{{ $key }}" placeholder="Key"
                               class="w-1/3 rounded-lg border-gray-200 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm font-mono">
                        <input type="text" name="field_values[]" value="{{ $value }}" placeholder="Value"
                               class="flex-1 rounded-lg border-gray-200 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                        <button type="button" onclick="removeField(this)" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-all flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @empty
                    <p class="text-gray-400 text-sm text-center py-4" id="emptyMessage">ไม่มีข้อมูล กดเพิ่ม Field เพื่อเริ่ม</p>
                @endforelse
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl text-sm font-medium hover:shadow-lg transition-all">
                บันทึกการเปลี่ยนแปลง
            </button>
            <a href="{{ route('customer.tping.data-profiles.show', $profile) }}" class="px-6 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-200 transition-all">
                ยกเลิก
            </a>
        </div>
    </form>
</div>

<script>
function addField() {
    const empty = document.getElementById('emptyMessage');
    if (empty) empty.remove();

    const container = document.getElementById('fieldsContainer');
    const row = document.createElement('div');
    row.className = 'flex items-center gap-2 field-row';
    row.innerHTML = `
        <input type="text" name="field_keys[]" placeholder="Key"
               class="w-1/3 rounded-lg border-gray-200 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm font-mono">
        <input type="text" name="field_values[]" placeholder="Value"
               class="flex-1 rounded-lg border-gray-200 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
        <button type="button" onclick="removeField(this)" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition-all flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    `;
    container.appendChild(row);
    row.querySelector('input').focus();
}

function removeField(btn) {
    btn.closest('.field-row').remove();
}
</script>
@endsection
