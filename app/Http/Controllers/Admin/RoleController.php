<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * Display a listing of roles
     */
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->orderBy('level', 'desc')->get();

        $stats = [
            'total_roles' => Role::count(),
            'system_roles' => Role::where('is_system', true)->count(),
            'custom_roles' => Role::where('is_system', false)->count(),
            'total_permissions' => Permission::count(),
        ];

        return view('admin.roles.index', compact('roles', 'stats'));
    }

    /**
     * Show the form for creating a new role
     */
    public function create()
    {
        $permissions = Permission::getGrouped();
        $permissionGroups = Permission::getGroups();

        return view('admin.roles.create', compact('permissions', 'permissionGroups'));
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles', 'regex:/^[a-z_]+$/'],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'level' => ['required', 'integer', 'min:0', 'max:99'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'],
            'level' => $validated['level'],
            'is_system' => false,
        ]);

        if (! empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', "สร้างบทบาท {$role->display_name} สำเร็จ");
    }

    /**
     * Display the specified role
     */
    public function show(Role $role)
    {
        $role->load(['permissions', 'users' => fn ($q) => $q->latest()->take(10)]);
        $permissionGroups = Permission::getGroups();
        $groupedPermissions = $role->permissions->groupBy('group');

        return view('admin.roles.show', compact('role', 'permissionGroups', 'groupedPermissions'));
    }

    /**
     * Show the form for editing the specified role
     */
    public function edit(Role $role)
    {
        $permissions = Permission::getGrouped();
        $permissionGroups = Permission::getGroups();
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'permissionGroups', 'rolePermissions'));
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles')->ignore($role->id), 'regex:/^[a-z_]+$/'],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'level' => ['required', 'integer', 'min:0', 'max:99'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        // Prevent modifying system role names
        if ($role->is_system && $role->name !== $validated['name']) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถเปลี่ยนชื่อ System Role ได้');
        }

        // Prevent setting level higher than current user's role
        $currentUserMaxLevel = auth()->user()->roles()->max('level') ?? 0;
        if (! auth()->user()->isSuperAdmin() && $validated['level'] > $currentUserMaxLevel) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถตั้ง Level สูงกว่าระดับของคุณได้');
        }

        $role->update([
            'name' => $role->is_system ? $role->name : $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'],
            'level' => $role->is_system && $role->name === 'super_admin' ? 100 : $validated['level'],
        ]);

        // Super admin always has all permissions
        if ($role->name !== 'super_admin') {
            $role->permissions()->sync($validated['permissions'] ?? []);
        }

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('success', 'อัพเดทบทบาทสำเร็จ');
    }

    /**
     * Remove the specified role
     */
    public function destroy(Role $role)
    {
        // Prevent deleting system roles
        if ($role->is_system) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถลบ System Role ได้');
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return redirect()
                ->back()
                ->with('error', 'ไม่สามารถลบบทบาทที่มีสมาชิกอยู่ได้ กรุณาย้ายสมาชิกไปบทบาทอื่นก่อน');
        }

        $roleName = $role->display_name;
        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', "ลบบทบาท {$roleName} สำเร็จ");
    }

    /**
     * Show users with this role
     */
    public function users(Role $role)
    {
        $users = $role->users()->latest()->paginate(20);

        return view('admin.roles.users', compact('role', 'users'));
    }

    /**
     * Add user to role
     */
    public function addUser(Request $request, Role $role)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Check if user already has this role
        if ($user->hasRole($role->name)) {
            return redirect()
                ->back()
                ->with('error', 'สมาชิกมีบทบาทนี้อยู่แล้ว');
        }

        $user->assignRole($role);

        return redirect()
            ->back()
            ->with('success', "เพิ่มบทบาท {$role->display_name} ให้ {$user->name} สำเร็จ");
    }

    /**
     * Remove user from role
     */
    public function removeUser(Request $request, Role $role)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Prevent removing last super_admin
        if ($role->name === 'super_admin') {
            $superAdminCount = $role->users()->count();
            if ($superAdminCount <= 1) {
                return redirect()
                    ->back()
                    ->with('error', 'ไม่สามารถลบ Super Admin คนสุดท้ายได้');
            }
        }

        $user->removeRole($role);

        return redirect()
            ->back()
            ->with('success', "ลบบทบาท {$role->display_name} จาก {$user->name} สำเร็จ");
    }

    /**
     * Duplicate a role
     */
    public function duplicate(Role $role)
    {
        $newRole = $role->replicate();
        $newRole->name = $role->name . '_copy_' . time();
        $newRole->display_name = $role->display_name . ' (สำเนา)';
        $newRole->is_system = false;
        $newRole->save();

        // Copy permissions
        $newRole->permissions()->sync($role->permissions->pluck('id'));

        return redirect()
            ->route('admin.roles.edit', $newRole)
            ->with('success', 'คัดลอกบทบาทสำเร็จ กรุณาแก้ไขรายละเอียด');
    }

    /**
     * Manage permissions page
     */
    public function permissions()
    {
        $permissions = Permission::getGrouped();
        $permissionGroups = Permission::getGroups();
        $roles = Role::orderBy('level', 'desc')->get();

        return view('admin.roles.permissions', compact('permissions', 'permissionGroups', 'roles'));
    }

    /**
     * Store a new permission
     */
    public function storePermission(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:permissions', 'regex:/^[a-z_.]+$/'],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'group' => ['required', 'string', 'max:50'],
        ]);

        Permission::create($validated);

        return redirect()
            ->back()
            ->with('success', 'เพิ่มสิทธิ์สำเร็จ');
    }

    /**
     * Delete a permission
     */
    public function destroyPermission(Permission $permission)
    {
        $permission->delete();

        return redirect()
            ->back()
            ->with('success', 'ลบสิทธิ์สำเร็จ');
    }
}
