<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'color',
        'is_system',
        'level',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'level' => 'integer',
    ];

    /**
     * Get the permissions for this role.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Get the users that have this role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin role has all permissions
        if ($this->name === 'super_admin') {
            return true;
        }

        return $this->permissions()->where('name', $permission)->exists();
    }

    /**
     * Check if role has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->name === 'super_admin') {
            return true;
        }

        return $this->permissions()->whereIn('name', $permissions)->exists();
    }

    /**
     * Check if role has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if ($this->name === 'super_admin') {
            return true;
        }

        return $this->permissions()->whereIn('name', $permissions)->count() === count($permissions);
    }

    /**
     * Sync permissions to the role.
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->sync($permissionIds);
    }

    /**
     * Give permissions to the role.
     */
    public function givePermissionTo(string|array $permissions): void
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->syncWithoutDetaching($permissionIds);
    }

    /**
     * Revoke permissions from the role.
     */
    public function revokePermissionTo(string|array $permissions): void
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
        $this->permissions()->detach($permissionIds);
    }

    /**
     * Get color class for Tailwind CSS.
     */
    public function getColorClassAttribute(): string
    {
        $colors = [
            '#ef4444' => 'bg-red-500',
            '#f97316' => 'bg-orange-500',
            '#eab308' => 'bg-yellow-500',
            '#22c55e' => 'bg-green-500',
            '#14b8a6' => 'bg-teal-500',
            '#3b82f6' => 'bg-blue-500',
            '#6366f1' => 'bg-indigo-500',
            '#8b5cf6' => 'bg-violet-500',
            '#ec4899' => 'bg-pink-500',
            '#6b7280' => 'bg-gray-500',
        ];

        return $colors[$this->color] ?? 'bg-gray-500';
    }

    /**
     * Get default system roles.
     */
    public static function getDefaultRoles(): array
    {
        return [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Admin',
                'description' => 'มีสิทธิ์เข้าถึงทุกอย่างในระบบ',
                'color' => '#ef4444',
                'is_system' => true,
                'level' => 100,
            ],
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'description' => 'ผู้ดูแลระบบทั่วไป',
                'color' => '#3b82f6',
                'is_system' => true,
                'level' => 50,
            ],
            [
                'name' => 'moderator',
                'display_name' => 'Moderator',
                'description' => 'ผู้ดูแลเนื้อหาและสมาชิก',
                'color' => '#22c55e',
                'is_system' => false,
                'level' => 30,
            ],
            [
                'name' => 'support',
                'display_name' => 'Support',
                'description' => 'ฝ่ายสนับสนุนลูกค้า',
                'color' => '#8b5cf6',
                'is_system' => false,
                'level' => 20,
            ],
            [
                'name' => 'user',
                'display_name' => 'User',
                'description' => 'สมาชิกทั่วไป',
                'color' => '#6b7280',
                'is_system' => true,
                'level' => 0,
            ],
        ];
    }
}
