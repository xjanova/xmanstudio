<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default permissions
        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view', 'display_name' => 'ดู Dashboard', 'description' => 'สามารถดู Dashboard ได้', 'group' => 'dashboard'],
            ['name' => 'dashboard.analytics', 'display_name' => 'ดู Analytics', 'description' => 'สามารถดูข้อมูล Analytics', 'group' => 'dashboard'],

            // Users
            ['name' => 'users.view', 'display_name' => 'ดูรายชื่อสมาชิก', 'description' => 'สามารถดูรายชื่อสมาชิกได้', 'group' => 'users'],
            ['name' => 'users.create', 'display_name' => 'สร้างสมาชิก', 'description' => 'สามารถสร้างสมาชิกใหม่ได้', 'group' => 'users'],
            ['name' => 'users.edit', 'display_name' => 'แก้ไขสมาชิก', 'description' => 'สามารถแก้ไขข้อมูลสมาชิกได้', 'group' => 'users'],
            ['name' => 'users.delete', 'display_name' => 'ลบสมาชิก', 'description' => 'สามารถลบสมาชิกได้', 'group' => 'users'],
            ['name' => 'users.toggle', 'display_name' => 'เปิด/ปิดการใช้งานสมาชิก', 'description' => 'สามารถเปิด/ปิดการใช้งานบัญชีได้', 'group' => 'users'],

            // Roles
            ['name' => 'roles.view', 'display_name' => 'ดูบทบาท', 'description' => 'สามารถดูรายการบทบาทได้', 'group' => 'roles'],
            ['name' => 'roles.create', 'display_name' => 'สร้างบทบาท', 'description' => 'สามารถสร้างบทบาทใหม่ได้', 'group' => 'roles'],
            ['name' => 'roles.edit', 'display_name' => 'แก้ไขบทบาท', 'description' => 'สามารถแก้ไขบทบาทได้', 'group' => 'roles'],
            ['name' => 'roles.delete', 'display_name' => 'ลบบทบาท', 'description' => 'สามารถลบบทบาทได้', 'group' => 'roles'],
            ['name' => 'roles.assign', 'display_name' => 'กำหนดบทบาท', 'description' => 'สามารถกำหนดบทบาทให้สมาชิกได้', 'group' => 'roles'],

            // Products
            ['name' => 'products.view', 'display_name' => 'ดูสินค้า', 'description' => 'สามารถดูรายการสินค้าได้', 'group' => 'products'],
            ['name' => 'products.create', 'display_name' => 'สร้างสินค้า', 'description' => 'สามารถสร้างสินค้าใหม่ได้', 'group' => 'products'],
            ['name' => 'products.edit', 'display_name' => 'แก้ไขสินค้า', 'description' => 'สามารถแก้ไขสินค้าได้', 'group' => 'products'],
            ['name' => 'products.delete', 'display_name' => 'ลบสินค้า', 'description' => 'สามารถลบสินค้าได้', 'group' => 'products'],

            // Orders
            ['name' => 'orders.view', 'display_name' => 'ดูคำสั่งซื้อ', 'description' => 'สามารถดูรายการคำสั่งซื้อได้', 'group' => 'orders'],
            ['name' => 'orders.edit', 'display_name' => 'แก้ไขคำสั่งซื้อ', 'description' => 'สามารถแก้ไขคำสั่งซื้อได้', 'group' => 'orders'],
            ['name' => 'orders.approve', 'display_name' => 'อนุมัติคำสั่งซื้อ', 'description' => 'สามารถอนุมัติการชำระเงินได้', 'group' => 'orders'],
            ['name' => 'orders.reject', 'display_name' => 'ปฏิเสธคำสั่งซื้อ', 'description' => 'สามารถปฏิเสธการชำระเงินได้', 'group' => 'orders'],

            // Wallets
            ['name' => 'wallets.view', 'display_name' => 'ดูกระเป๋าเงิน', 'description' => 'สามารถดูข้อมูลกระเป๋าเงินได้', 'group' => 'wallets'],
            ['name' => 'wallets.adjust', 'display_name' => 'ปรับยอดเงิน', 'description' => 'สามารถปรับยอดเงินในกระเป๋าได้', 'group' => 'wallets'],
            ['name' => 'wallets.approve_topup', 'display_name' => 'อนุมัติการเติมเงิน', 'description' => 'สามารถอนุมัติการเติมเงินได้', 'group' => 'wallets'],
            ['name' => 'wallets.settings', 'display_name' => 'ตั้งค่า Wallet', 'description' => 'สามารถตั้งค่าระบบ Wallet ได้', 'group' => 'wallets'],

            // Rentals
            ['name' => 'rentals.view', 'display_name' => 'ดูการเช่า', 'description' => 'สามารถดูรายการเช่าได้', 'group' => 'rentals'],
            ['name' => 'rentals.manage', 'display_name' => 'จัดการการเช่า', 'description' => 'สามารถจัดการการเช่าได้', 'group' => 'rentals'],
            ['name' => 'rentals.packages', 'display_name' => 'จัดการแพ็กเกจ', 'description' => 'สามารถจัดการแพ็กเกจได้', 'group' => 'rentals'],

            // Licenses
            ['name' => 'licenses.view', 'display_name' => 'ดู License', 'description' => 'สามารถดูรายการ License ได้', 'group' => 'licenses'],
            ['name' => 'licenses.create', 'display_name' => 'สร้าง License', 'description' => 'สามารถสร้าง License ได้', 'group' => 'licenses'],
            ['name' => 'licenses.manage', 'display_name' => 'จัดการ License', 'description' => 'สามารถจัดการ License ได้', 'group' => 'licenses'],

            // SMS Payment
            ['name' => 'sms_payment.view', 'display_name' => 'ดู SMS Payment', 'description' => 'สามารถดูการตรวจสอบ SMS ได้', 'group' => 'sms_payment'],
            ['name' => 'sms_payment.manage', 'display_name' => 'จัดการ SMS Payment', 'description' => 'สามารถจัดการ SMS Payment ได้', 'group' => 'sms_payment'],
            ['name' => 'sms_payment.devices', 'display_name' => 'จัดการ Devices', 'description' => 'สามารถจัดการ SMS Devices ได้', 'group' => 'sms_payment'],

            // Reports
            ['name' => 'reports.view', 'display_name' => 'ดูรายงาน', 'description' => 'สามารถดูรายงานได้', 'group' => 'reports'],
            ['name' => 'reports.export', 'display_name' => 'ส่งออกรายงาน', 'description' => 'สามารถส่งออกรายงานได้', 'group' => 'reports'],

            // Settings
            ['name' => 'settings.general', 'display_name' => 'ตั้งค่าทั่วไป', 'description' => 'สามารถตั้งค่าทั่วไปได้', 'group' => 'settings'],
            ['name' => 'settings.payment', 'display_name' => 'ตั้งค่าการชำระเงิน', 'description' => 'สามารถตั้งค่าการชำระเงินได้', 'group' => 'settings'],
            ['name' => 'settings.branding', 'display_name' => 'ตั้งค่า Branding', 'description' => 'สามารถตั้งค่า Logo และ Branding ได้', 'group' => 'settings'],
            ['name' => 'settings.theme', 'display_name' => 'ตั้งค่าธีม', 'description' => 'สามารถตั้งค่าธีมได้', 'group' => 'settings'],
            ['name' => 'settings.line', 'display_name' => 'ตั้งค่า Line', 'description' => 'สามารถตั้งค่า Line OA ได้', 'group' => 'settings'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        // Create default roles
        $roles = Role::getDefaultRoles();

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }

        // Assign permissions to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminPermissions = Permission::whereIn('group', [
                'dashboard', 'users', 'products', 'orders', 'wallets',
                'rentals', 'licenses', 'sms_payment', 'reports',
            ])->pluck('id');
            $adminRole->permissions()->sync($adminPermissions);
        }

        // Assign permissions to moderator role
        $moderatorRole = Role::where('name', 'moderator')->first();
        if ($moderatorRole) {
            $moderatorPermissions = Permission::whereIn('name', [
                'dashboard.view',
                'users.view', 'users.edit',
                'products.view', 'products.edit',
                'orders.view', 'orders.edit', 'orders.approve',
                'rentals.view', 'rentals.manage',
                'licenses.view',
                'reports.view',
            ])->pluck('id');
            $moderatorRole->permissions()->sync($moderatorPermissions);
        }

        // Assign permissions to support role
        $supportRole = Role::where('name', 'support')->first();
        if ($supportRole) {
            $supportPermissions = Permission::whereIn('name', [
                'dashboard.view',
                'users.view',
                'orders.view',
                'wallets.view', 'wallets.approve_topup',
                'rentals.view',
                'licenses.view',
            ])->pluck('id');
            $supportRole->permissions()->sync($supportPermissions);
        }
    }
}
