@extends('layouts.admin')

@section('title', 'แก้ไขบทบาท - ' . $role->display_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <nav class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('admin.roles.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">จัดการบทบาท</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <a href="{{ route('admin.roles.show', $role) }}" class="hover:text-gray-700 dark:hover:text-gray-300">{{ $role->display_name }}</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span>แก้ไข</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">แก้ไขบทบาท</h1>
        </div>
    </div>

    <form action="{{ route('admin.roles.update', $role) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูลบทบาท</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    ชื่อ (slug) <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" id="name" value="{{ old('name', $role->name) }}"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white {{ $role->is_system ? 'bg-gray-100 dark:bg-gray-600' : '' }}"
                                    pattern="[a-z_]+" placeholder="เช่น content_manager" required {{ $role->is_system ? 'readonly' : '' }}>
                                @if($role->is_system)
                                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">ไม่สามารถเปลี่ยนชื่อ System Role ได้</p>
                                @else
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ใช้ตัวอักษรพิมพ์เล็กและ underscore เท่านั้น</p>
                                @endif
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    ชื่อแสดง <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="display_name" id="display_name" value="{{ old('display_name', $role->display_name) }}"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                    placeholder="เช่น ผู้จัดการเนื้อหา" required>
                                @error('display_name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                คำอธิบาย
                            </label>
                            <textarea name="description" id="description" rows="3"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                placeholder="อธิบายหน้าที่และความรับผิดชอบของบทบาทนี้">{{ old('description', $role->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    สี <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="color" name="color" id="color" value="{{ old('color', $role->color) }}"
                                        class="h-10 w-16 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer">
                                    <div class="flex gap-2">
                                        @foreach(['#ef4444', '#f97316', '#eab308', '#22c55e', '#14b8a6', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#6b7280'] as $color)
                                        <button type="button" onclick="document.getElementById('color').value = '{{ $color }}'"
                                            class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-600 shadow-sm hover:scale-110 transition"
                                            style="background-color: {{ $color }}"></button>
                                        @endforeach
                                    </div>
                                </div>
                                @error('color')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="level" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Level <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="level" id="level" value="{{ old('level', $role->level) }}"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white {{ $role->name === 'super_admin' ? 'bg-gray-100 dark:bg-gray-600' : '' }}"
                                    min="0" max="99" required {{ $role->name === 'super_admin' ? 'readonly' : '' }}>
                                @if($role->name === 'super_admin')
                                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Super Admin มี Level สูงสุดเสมอ</p>
                                @else
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ระดับลำดับชั้น (0-99) ยิ่งสูงยิ่งมีอำนาจมาก</p>
                                @endif
                                @error('level')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permissions -->
                @if($role->name !== 'super_admin')
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">สิทธิ์การเข้าถึง</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เลือกสิทธิ์ที่ต้องการให้กับบทบาทนี้</p>
                    </div>
                    <div class="p-6 space-y-6">
                        @foreach($permissionGroups as $groupKey => $groupName)
                            @if(isset($permissions[$groupKey]) && $permissions[$groupKey]->count() > 0)
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $groupName }}</h3>
                                    <button type="button" onclick="toggleGroup('{{ $groupKey }}')" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                        เลือกทั้งหมด
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach($permissions[$groupKey] as $permission)
                                    <label class="flex items-start p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}"
                                            class="permission-{{ $groupKey }} mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                            {{ in_array($permission->id, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                        <div class="ml-3">
                                            <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $permission->display_name }}</span>
                                            @if($permission->description)
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $permission->description }}</span>
                                            @endif
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        @endforeach

                        @if($permissions->isEmpty())
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">ยังไม่มีสิทธิ์ในระบบ</p>
                            <a href="{{ route('admin.roles.permissions') }}" class="mt-2 inline-block text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                เพิ่มสิทธิ์ใหม่
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
                @else
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 p-6">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-amber-800 dark:text-amber-300">Super Admin มีสิทธิ์ทุกอย่าง</h3>
                            <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">บทบาท Super Admin มีสิทธิ์เข้าถึงทุกส่วนของระบบโดยอัตโนมัติ ไม่จำเป็นต้องกำหนดสิทธิ์</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">การดำเนินการ</h3>
                    <div class="space-y-3">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                            บันทึกการเปลี่ยนแปลง
                        </button>
                        <a href="{{ route('admin.roles.show', $role) }}" class="block w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition text-center">
                            ยกเลิก
                        </a>
                    </div>
                </div>

                <!-- Role Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">ข้อมูลบทบาท</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">ประเภท</dt>
                            <dd>
                                @if($role->is_system)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                    System Role
                                </span>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                    Custom Role
                                </span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">สมาชิก</dt>
                            <dd class="text-gray-900 dark:text-white">{{ number_format($role->users()->count()) }} คน</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">สร้างเมื่อ</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $role->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">อัพเดทล่าสุด</dt>
                            <dd class="text-gray-900 dark:text-white">{{ $role->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                @if($role->is_system)
                <!-- Warning -->
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 p-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-amber-800 dark:text-amber-300">System Role</h3>
                            <p class="mt-1 text-xs text-amber-700 dark:text-amber-400">บทบาทนี้เป็น System Role บางส่วนไม่สามารถแก้ไขได้</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function toggleGroup(group) {
    const checkboxes = document.querySelectorAll('.permission-' + group);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
}
</script>
@endpush
@endsection
