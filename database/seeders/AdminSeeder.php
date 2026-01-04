<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@xmanstudio.com'],
            [
                'name' => 'Admin XMAN Studio',
                'password' => Hash::make('XmanAdmin@2024'),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user created/updated:');
        $this->command->info('  Email: admin@xmanstudio.com');
        $this->command->info('  Password: XmanAdmin@2024');
        $this->command->warn('  Please change the password after first login!');

        // Create default settings
        $settings = [
            [
                'group' => 'notification',
                'key' => 'line_notify_token',
                'value' => '',
                'type' => 'string',
                'description' => 'Line Notify Access Token',
                'is_public' => false,
            ],
            [
                'group' => 'notification',
                'key' => 'notification_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable/disable notifications',
                'is_public' => false,
            ],
            [
                'group' => 'company',
                'key' => 'company_name',
                'value' => 'XMAN STUDIO',
                'type' => 'string',
                'description' => 'Company name',
                'is_public' => true,
            ],
            [
                'group' => 'company',
                'key' => 'company_email',
                'value' => 'info@xmanstudio.com',
                'type' => 'string',
                'description' => 'Company email',
                'is_public' => true,
            ],
            [
                'group' => 'company',
                'key' => 'company_phone',
                'value' => '+66 XX XXX XXXX',
                'type' => 'string',
                'description' => 'Company phone',
                'is_public' => true,
            ],
            [
                'group' => 'company',
                'key' => 'company_line',
                'value' => '@xmanstudio',
                'type' => 'string',
                'description' => 'Line OA ID',
                'is_public' => true,
            ],
            [
                'group' => 'quotation',
                'key' => 'quotation_validity_days',
                'value' => '30',
                'type' => 'integer',
                'description' => 'Quotation validity in days',
                'is_public' => false,
            ],
            [
                'group' => 'quotation',
                'key' => 'vat_rate',
                'value' => '7',
                'type' => 'integer',
                'description' => 'VAT rate percentage',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Default settings created.');
        $this->command->newLine();
        $this->command->info('To configure Line Notify:');
        $this->command->info('  1. Go to https://notify-bot.line.me/');
        $this->command->info('  2. Login and create a new token');
        $this->command->info('  3. Add the token to your .env file:');
        $this->command->info('     LINE_NOTIFY_TOKEN=your_token_here');
        $this->command->info('  4. Or update the database setting directly');
    }
}
