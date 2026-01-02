<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\PaymentSetting;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Payment Settings
        $settings = [
            // PromptPay
            [
                'key' => 'promptpay_number',
                'group' => 'promptpay',
                'value' => env('PROMPTPAY_NUMBER', ''),
                'type' => 'string',
                'label' => 'เบอร์พร้อมเพย์',
                'description' => 'เบอร์โทรศัพท์หรือเลขบัตรประชาชนสำหรับพร้อมเพย์',
            ],
            [
                'key' => 'promptpay_enabled',
                'group' => 'promptpay',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'เปิดใช้งานพร้อมเพย์',
            ],
            // Bank Transfer
            [
                'key' => 'bank_transfer_enabled',
                'group' => 'bank_transfer',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'เปิดใช้งานโอนเงินธนาคาร',
            ],
            // Card Payment
            [
                'key' => 'card_payment_enabled',
                'group' => 'card',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'เปิดใช้งานบัตรเครดิต/เดบิต',
            ],
            // General
            [
                'key' => 'payment_timeout_hours',
                'group' => 'general',
                'value' => '24',
                'type' => 'integer',
                'label' => 'ระยะเวลารอชำระเงิน (ชั่วโมง)',
            ],
            [
                'key' => 'auto_cancel_pending_after_hours',
                'group' => 'general',
                'value' => '48',
                'type' => 'integer',
                'label' => 'ยกเลิกอัตโนมัติหลัง (ชั่วโมง)',
            ],
        ];

        foreach ($settings as $setting) {
            PaymentSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        // Default Bank Accounts - Only create if none exist
        if (BankAccount::count() === 0) {
            $bankAccounts = [
                [
                    'bank_name' => 'ธนาคารกสิกรไทย',
                    'bank_code' => 'KBANK',
                    'account_number' => env('KBANK_ACCOUNT_NUMBER', ''),
                    'account_name' => env('BANK_ACCOUNT_NAME', 'XMAN Studio Co., Ltd.'),
                    'branch' => env('KBANK_BRANCH', ''),
                    'order' => 1,
                    'is_active' => true,
                ],
                [
                    'bank_name' => 'ธนาคารไทยพาณิชย์',
                    'bank_code' => 'SCB',
                    'account_number' => env('SCB_ACCOUNT_NUMBER', ''),
                    'account_name' => env('BANK_ACCOUNT_NAME', 'XMAN Studio Co., Ltd.'),
                    'branch' => env('SCB_BRANCH', ''),
                    'order' => 2,
                    'is_active' => true,
                ],
            ];

            foreach ($bankAccounts as $account) {
                // Only create if account number is provided
                if (!empty($account['account_number'])) {
                    BankAccount::create($account);
                }
            }
        }
    }
}
