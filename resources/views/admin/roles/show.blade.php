@extends('layouts.admin')

@section('title', 'บทบาท - ' . $role->display_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('admin.roles.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">จัดการบทบาท</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span>{{ $role->display_name }}</span>
            </nav>
            <div class="flex items-center gap-3">
                <span class="w-4 h-4 rounded-full" style="background-color: {{ $role->color }}"></span>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $role->display_name }}</h1>
                @if($role->is_system)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                    System
                </span>
                @endif
            </div>
            @if($role->description)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $role->description }}</p>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.roles.edit', $role) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                แก้ไข
            </a>
            <form action="{{ route('admin.roles.duplicate', $role) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    คัดลอก
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Role Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">ข้อมูลบทบาท</h2>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ชื่อ (slug)</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $role->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ชื่อแสดง</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->display_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Level</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                    {{ $role->level }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ประเภท</dt>
                            <dd class="mt-1">
                                @if($role->is_system)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                    System Role
                                </span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                    Custom Role
                                </span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">สร้างเมื่อ</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">อัพเดทล่าสุด</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->updated_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Permissions -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">สิทธิ์การเข้าถึง</h2>
                        @if($role->name === 'super_admin')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                            มีสิทธิ์ทั้งหมด
                        </span>
                        @else
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $role->permissions->count() }} สิทธิ์</span>
                        @endif
                    </div>
                </div>
                <div class="p-6">
                    @if($role->name === 'super_admin')
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Super Admin มีสิทธิ์เข้าถึงทุกส่วนของระบบโดยอัตโนมัติ</p>
                    </div>
                    @elseif($role->permissions->isEmpty())
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">ยังไม่มีสิทธิ์ที่กำหนดให้บทบาทนี้</p>
                        <a href="{{ route('admin.roles.edit', $role) }}" class="mt-2 inline-block text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            เพิ่มสิทธิ์
                        </a>
                    </div>
                    @else
                    <div class="space-y-4">
                        @foreach($permissionGroups as $groupKey => $groupName)
                            @if(isset($groupedPermissions[$groupKey]) && $groupedPermissions[$groupKey]->count() > 0)
                            <div>
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">{{ $groupName }}</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($groupedPermissions[$groupKey] as $permission)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                                        {{ $permission->display_name }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Stats -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-4">สถิติ</h3>
                <dl class="space-y-4">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">สมาชิกทั้งหมด</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format($role->users->count()) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">สิทธิ์ทั้งหมด</dt>
                        <dd class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $role->name === 'super_admin' ? 'ทั้งหมด' : $role->permissions->count() }}
                        </dd>
                    </div>
                </dl>
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.roles.users', $role) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        ดูสมาชิกทั้งหมด &rarr;
                    </a>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">สมาชิกล่าสุด</h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($role->users as $user)
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                @if($user->avatar_url)
                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full">
                                @else
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ substr($user->name, 0, 1) }}</span>
                                @endif
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.users.show', $user) }}" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </a>
                    </div>
                    @empty
                    <div class="p-6 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีสมาชิกที่มีบทบาทนี้</p>
                    </div>
                    @endforelse
                </div>
                @if($role->users->count() > 0)
                <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.roles.users', $role) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                        ดูทั้งหมด &rarr;
                    </a>
                </div>
                @endif
            </div>

            <!-- Danger Zone -->
            @if(!$role->is_system)
            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-6">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-300 mb-2">Danger Zone</h3>
                <p class="text-sm text-red-700 dark:text-red-400 mb-4">การลบบทบาทจะไม่สามารถกู้คืนได้ สมาชิกที่มีบทบาทนี้จะถูกนำออกโดยอัตโนมัติ</p>
                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบบทบาทนี้? การดำเนินการนี้ไม่สามารถยกเลิกได้')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition text-sm">
                        ลบบทบาทนี้
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
