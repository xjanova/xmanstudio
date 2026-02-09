<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
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
                'key' => 'line_channel_access_token',
                'value' => '',
                'type' => 'string',
                'description' => 'Line Messaging API Channel Access Token',
                'is_public' => false,
            ],
            [
                'group' => 'notification',
                'key' => 'line_admin_user_id',
                'value' => '',
                'type' => 'string',
                'description' => 'Line User ID for receiving notifications',
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
                'value' => 'xjanovax@gmail.com',
                'type' => 'string',
                'description' => 'Company email',
                'is_public' => true,
            ],
            [
                'group' => 'company',
                'key' => 'company_phone',
                'value' => '080-6038278',
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
        $this->command->info('To configure Line Messaging API:');
        $this->command->info('  1. Go to https://developers.line.biz/console/');
        $this->command->info('  2. Create a Messaging API channel');
        $this->command->info('  3. Get Channel Access Token (long-lived)');
        $this->command->info('  4. Add to your .env file:');
        $this->command->info('     LINE_CHANNEL_ACCESS_TOKEN=your_token_here');
        $this->command->info('     LINE_ADMIN_USER_ID=your_user_id_here');
        $this->command->newLine();
        $this->command->info('To get your User ID:');
        $this->command->info('  - Add your bot as friend');
        $this->command->info('  - Use webhook to capture your user ID from events');
    }
}
