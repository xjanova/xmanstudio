@extends('layouts.admin')

@section('title', 'สมาชิก - ' . $role->display_name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('admin.roles.index') }}" class="hover:text-gray-700 dark:hover:text-gray-300">จัดการบทบาท</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <a href="{{ route('admin.roles.show', $role) }}" class="hover:text-gray-700 dark:hover:text-gray-300">{{ $role->display_name }}</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span>สมาชิก</span>
            </nav>
            <div class="flex items-center gap-3">
                <span class="w-4 h-4 rounded-full" style="background-color: {{ $role->color }}"></span>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">สมาชิกที่มีบทบาท {{ $role->display_name }}</h1>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="document.getElementById('addUserModal').classList.remove('hidden')"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                เพิ่มสมาชิก
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สมาชิก</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">อีเมล</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">บทบาทอื่น</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                                    @if($user->avatar_url)
                                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-full object-cover">
                                    @else
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ substr($user->name, 0, 1) }}</span>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <a href="{{ route('admin.users.show', $user) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $user->name }}
                                    </a>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->email }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->roles->where('id', '!=', $role->id) as $otherRole)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" style="background-color: {{ $otherRole->color }}20; color: {{ $otherRole->color }}">
                                    {{ $otherRole->display_name }}
                                </span>
                                @endforeach
                                @if($user->roles->where('id', '!=', $role->id)->isEmpty())
                                <span class="text-xs text-gray-400">-</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                                ใช้งาน
                            </span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                                ปิดใช้งาน
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white" title="ดูรายละเอียด">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('admin.roles.remove-user', $role) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบบทบาทนี้จากสมาชิก?')">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300" title="ลบบทบาท">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">ไม่มีสมาชิก</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">ยังไม่มีสมาชิกที่มีบทบาทนี้</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50 transition-opacity" onclick="document.getElementById('addUserModal').classList.add('hidden')"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เพิ่มสมาชิกในบทบาท {{ $role->display_name }}</h3>
            <form action="{{ route('admin.roles.add-user', $role) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เลือกสมาชิก</label>
                    <select name="user_id" id="user_id" required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">-- เลือกสมาชิก --</option>
                        @php
                            $availableUsers = \App\Models\User::whereDoesntHave('roles', fn($q) => $q->where('roles.id', $role->id))
                                ->orderBy('name')
                                ->get();
                        @endphp
                        @foreach($availableUsers as $availableUser)
                        <option value="{{ $availableUser->id }}">{{ $availableUser->name }} ({{ $availableUser->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-900 dark:text-white rounded-lg transition">
                        ยกเลิก
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        เพิ่มสมาชิก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
