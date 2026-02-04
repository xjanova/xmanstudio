<?php

namespace App\Console\Commands;

use App\Models\SmsCheckerDevice;
use Illuminate\Console\Command;

class SmsCheckerCreateDeviceCommand extends Command
{
    protected $signature = 'smschecker:create-device
                            {name : Device display name}
                            {--user= : Optional user ID to associate with device}
                            {--mode=auto : Approval mode (auto, manual, smart)}';

    protected $description = 'Create a new SMS Checker device with API credentials';

    public function handle(): int
    {
        $name = $this->argument('name');
        $userId = $this->option('user');
        $mode = $this->option('mode');

        if (! in_array($mode, ['auto', 'manual', 'smart'])) {
            $this->error('Invalid approval mode. Use: auto, manual, or smart');
            return self::FAILURE;
        }

        // Generate unique device ID
        $deviceId = 'SMSCHK-'.strtoupper(bin2hex(random_bytes(4)));

        // Generate secure keys
        $apiKey = SmsCheckerDevice::generateApiKey();
        $secretKey = SmsCheckerDevice::generateSecretKey();

        $device = SmsCheckerDevice::create([
            'device_id' => $deviceId,
            'device_name' => $name,
            'api_key' => $apiKey,
            'secret_key' => $secretKey,
            'status' => 'active',
            'approval_mode' => $mode,
            'user_id' => $userId,
        ]);

        $this->info('');
        $this->info('✅ Device created successfully!');
        $this->info('');
        $this->table(['Property', 'Value'], [
            ['Device ID', $device->device_id],
            ['Device Name', $device->device_name],
            ['API Key', $apiKey],
            ['Secret Key', $secretKey],
            ['Status', $device->status],
            ['Approval Mode', $device->approval_mode],
        ]);
        $this->info('');
        $this->warn('⚠️  Save these keys securely! They cannot be retrieved later.');
        $this->info('');

        // Show QR config JSON
        $config = [
            'server_url' => config('app.url').'/api/v1/sms-payment',
            'api_key' => $apiKey,
            'secret_key' => $secretKey,
            'device_id' => $device->device_id,
        ];

        $this->info('📱 Configuration for Android app (scan as QR or copy):');
        $this->line(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
