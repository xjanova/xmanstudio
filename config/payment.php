<?php

return [
    /*
    |--------------------------------------------------------------------------
    | PromptPay Configuration
    |--------------------------------------------------------------------------
    */
    'promptpay' => [
        'number' => env('PROMPTPAY_NUMBER', '0812345678'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bank Accounts
    |--------------------------------------------------------------------------
    */
    'bank_accounts' => [
        [
            'bank' => 'ธนาคารกสิกรไทย',
            'bank_code' => 'KBANK',
            'account_number' => env('KBANK_ACCOUNT_NUMBER', 'XXX-X-XXXXX-X'),
            'account_name' => env('BANK_ACCOUNT_NAME', 'XMAN Studio Co., Ltd.'),
            'branch' => 'สาขาสยามพารากอน',
        ],
        [
            'bank' => 'ธนาคารไทยพาณิชย์',
            'bank_code' => 'SCB',
            'account_number' => env('SCB_ACCOUNT_NUMBER', 'XXX-X-XXXXX-X'),
            'account_name' => env('BANK_ACCOUNT_NAME', 'XMAN Studio Co., Ltd.'),
            'branch' => 'สาขาเซ็นทรัลเวิลด์',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Credit Card Configuration
    |--------------------------------------------------------------------------
    */
    'card' => [
        'enabled' => env('CARD_PAYMENT_ENABLED', false),
        'gateway' => env('CARD_GATEWAY', 'omise'), // omise, 2c2p, stripe
        'public_key' => env('CARD_PUBLIC_KEY', ''),
        'secret_key' => env('CARD_SECRET_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */
    'currency' => 'THB',
    'payment_timeout_hours' => 24,
    'auto_cancel_pending_after_hours' => 48,
];
