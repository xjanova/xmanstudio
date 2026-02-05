@extends('layouts.admin')

@section('title', 'จัดการสิทธิ์')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('admin.roles.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">จัดการบทบาท</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span>จัดการสิทธิ์</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการสิทธิ์</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">จัดการสิทธิ์การเข้าถึงในระบบ</p>
        </div>
        <div>
            <button type="button" onclick="document.getElementById('addPermissionModal').classList.remove('hidden')"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                เพิ่มสิทธิ์
            </button>
        </div>
    </div>

    <!-- Permissions by Group -->
    @foreach($permissionGroups as $groupKey => $groupName)
        @if(isset($permissions[$groupKey]) && $permissions[$groupKey]->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $groupName }}</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $permissions[$groupKey]->count() }} สิทธิ์</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ชื่อ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ชื่อแสดง</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">คำอธิบาย</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">บทบาทที่มีสิทธิ์นี้</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($permissions[$groupKey] as $permission)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $permission->name }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900 dark:text-white">{{ $permission->display_name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $permission->description ?: '-' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($permission->roles as $role)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background-color: {{ $role->color }}20; color: {{ $role->color }}">
                                        {{ $role->display_name }}
                                    </span>
                                    @endforeach
                                    @if($permission->roles->isEmpty())
                                    <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <form action="{{ route('admin.roles.permissions.destroy', $permission) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบสิทธิ์นี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300" title="ลบ">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endforeach

    @if($permissions->flatten()->isEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">ยังไม่มีสิทธิ์</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">เริ่มต้นสร้างสิทธิ์ใหม่</p>
        <div class="mt-6">
            <button type="button" onclick="document.getElementById('addPermissionModal').classList.remove('hidden')"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                เพิ่มสิทธิ์
            </button>
        </div>
    </div>
    @endif

    <!-- Permission Matrix -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Permission Matrix</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ภาพรวมสิทธิ์ของแต่ละบทบาท</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-700/50">สิทธิ์</th>
                        @foreach($roles as $role)
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background-color: {{ $role->color }}20; color: {{ $role->color }}">
                                {{ $role->display_name }}
                            </span>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($permissions->flatten() as $permission)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white sticky left-0 bg-white dark:bg-gray-800">
                            {{ $permission->display_name }}
                        </td>
                        @foreach($roles as $role)
                        <td class="px-4 py-3 text-center">
                            @if($role->name === 'super_admin' || $role->permissions->contains('id', $permission->id))
                            <svg class="w-5 h-5 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            @else
                            <svg class="w-5 h-5 text-gray-300 dark:text-gray-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Permission Modal -->
<div id="addPermissionModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="document.getElementById('addPermissionModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เพิ่มสิทธิ์ใหม่</h3>
            <form action="{{ route('admin.roles.permissions.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ชื่อ (slug) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            pattern="[a-z_.]+" placeholder="เช่น users.create">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">ใช้ตัวอักษรพิมพ์เล็ก, underscore และ จุด</p>
                    </div>

                    <div>
                        <label for="display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ชื่อแสดง <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="display_name" id="display_name" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="เช่น สร้างสมาชิกใหม่">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            คำอธิบาย
                        </label>
                        <input type="text" name="description" id="description"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="อธิบายสั้นๆ เกี่ยวกับสิทธิ์นี้">
                    </div>

                    <div>
                        <label for="group" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            กลุ่ม <span class="text-red-500">*</span>
                        </label>
                        <select name="group" id="group" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            @foreach($permissionGroups as $key => $name)
                            <option value="{{ $key }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="document.getElementById('addPermissionModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition">
                        ยกเลิก
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
