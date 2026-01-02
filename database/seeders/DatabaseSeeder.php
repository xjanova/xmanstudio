<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user if not exists
        if (! User::where('email', 'admin@xmanstudio.com')->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => 'admin@xmanstudio.com',
                'password' => Hash::make(env('ADMIN_PASSWORD', 'Admin@123!')),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
        }

        // Create super admin if not exists
        if (! User::where('email', env('SUPER_ADMIN_EMAIL', 'superadmin@xmanstudio.com'))->exists()) {
            User::create([
                'name' => 'Super Admin',
                'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@xmanstudio.com'),
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'SuperAdmin@123!')),
                'role' => 'super_admin',
                'email_verified_at' => now(),
            ]);
        }

        // Seed services and payment settings
        $this->call([
            ServiceSeeder::class,
            PaymentSeeder::class,
        ]);
    }
}
