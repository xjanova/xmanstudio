<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'group',
    ];

    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Get all permission groups.
     */
    public static function getGroups(): array
    {
        return [
            'dashboard' => 'แดชบอร์ด',
            'users' => 'จัดการสมาชิก',
            'roles' => 'จัดการบทบาท',
            'products' => 'จัดการสินค้า',
            'orders' => 'จัดการคำสั่งซื้อ',
            'wallets' => 'จัดการกระเป๋าเงิน',
            'rentals' => 'จัดการเช่าใช้',
            'licenses' => 'จัดการ License',
            'sms_payment' => 'ระบบตรวจสอบ SMS',
            'reports' => 'รายงาน',
            'settings' => 'ตั้งค่าระบบ',
        ];
    }

    /**
     * Get grouped permissions.
     */
    public static function getGrouped()
    {
        return static::all()->groupBy('group');
    }
}
