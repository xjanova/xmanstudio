<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Checker Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the SMS Payment Verification system that integrates
    | with the SmsChecker Android app for automatic bank transfer verification.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Timestamp Tolerance
    |--------------------------------------------------------------------------
    |
    | How many seconds of difference between client and server timestamps
    | is acceptable. Helps prevent replay attacks while allowing for
    | reasonable clock drift.
    |
    */
    'timestamp_tolerance' => env('SMSCHECKER_TIMESTAMP_TOLERANCE', 300),

    /*
    |--------------------------------------------------------------------------
    | Unique Amount Expiry
    |--------------------------------------------------------------------------
    |
    | How many minutes a unique payment amount reservation is valid for.
    | After this time, the amount will be marked as expired and can be reused.
    |
    */
    'amount_expiry' => env('SMSCHECKER_AMOUNT_EXPIRY', 30),

    /*
    |--------------------------------------------------------------------------
    | Maximum Pending Per Amount
    |--------------------------------------------------------------------------
    |
    | Maximum number of unique decimal suffixes (01-99) that can be
    | simultaneously reserved for a single base amount. If all suffixes
    | are in use, amount generation will fail.
    |
    */
    'max_pending_per_amount' => env('SMSCHECKER_MAX_PENDING', 99),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Maximum number of SMS notifications per device per minute.
    | Helps prevent abuse and DoS attacks.
    |
    */
    'rate_limit_per_minute' => env('SMSCHECKER_RATE_LIMIT', 30),

    /*
    |--------------------------------------------------------------------------
    | Nonce Expiry
    |--------------------------------------------------------------------------
    |
    | How many hours old nonces should be kept before cleanup.
    | Older nonces are deleted to prevent database bloat.
    |
    */
    'nonce_expiry_hours' => env('SMSCHECKER_NONCE_EXPIRY', 24),

    /*
    |--------------------------------------------------------------------------
    | Default Approval Mode
    |--------------------------------------------------------------------------
    |
    | Default approval mode for new devices. Can be overridden per-device
    | via the Android app settings.
    |
    | Options:
    | - 'auto': Automatically confirm matched payments (100% amount match)
    | - 'manual': All payments require manual admin approval
    | - 'smart': Auto for exact match, manual for partial/suspicious matches
    |
    */
    'default_approval_mode' => env('SMSCHECKER_DEFAULT_APPROVAL_MODE', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Logging Level
    |--------------------------------------------------------------------------
    |
    | Minimum logging level for SMS Checker events.
    | Options: 'debug', 'info', 'warning', 'error'
    |
    */
    'log_level' => env('SMSCHECKER_LOG_LEVEL', 'info'),

    /*
    |--------------------------------------------------------------------------
    | Supported Banks
    |--------------------------------------------------------------------------
    |
    | List of supported Thai banks with their display names.
    | These are parsed by the Android app's BankSmsParser.
    |
    */
    'banks' => [
        'KBANK' => 'ธนาคารกสิกรไทย',
        'SCB' => 'ธนาคารไทยพาณิชย์',
        'KTB' => 'ธนาคารกรุงไทย',
        'BBL' => 'ธนาคารกรุงเทพ',
        'GSB' => 'ธนาคารออมสิน',
        'BAY' => 'ธนาคารกรุงศรี',
        'TTB' => 'ธนาคารทหารไทยธนชาต',
        'PROMPTPAY' => 'พร้อมเพย์',
        'CIMB' => 'ธนาคารซีไอเอ็มบี',
        'KKP' => 'ธนาคารเกียรตินาคินภัทร',
        'LH' => 'ธนาคารแลนด์แอนด์เฮ้าส์',
        'TISCO' => 'ธนาคารทิสโก้',
        'UOB' => 'ธนาคารยูโอบี',
        'ICBC' => 'ธนาคารไอซีบีซี',
        'BAAC' => 'ธ.ก.ส.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Settings for notifications when payments are matched/confirmed.
    |
    */
    'notifications' => [
        // Send LINE notification on payment match
        'line_on_match' => env('SMSCHECKER_LINE_ON_MATCH', true),

        // Send email notification on payment match
        'email_on_match' => env('SMSCHECKER_EMAIL_ON_MATCH', false),

        // Send FCM push notification on payment match
        'fcm_on_match' => env('SMSCHECKER_FCM_ON_MATCH', true),

        // Send FCM push notification on new order
        'fcm_on_new_order' => env('SMSCHECKER_FCM_ON_NEW_ORDER', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings (Polling-based)
    |--------------------------------------------------------------------------
    |
    | Settings for polling-based sync between Android app and Server.
    | Uses polling instead of push notifications for privacy.
    |
    */
    'sync' => [
        // Sync interval in seconds - Android app will poll every X seconds
        'interval' => env('SMSCHECKER_SYNC_INTERVAL', 5),

        // Timeout for sync requests in seconds
        'timeout' => env('SMSCHECKER_SYNC_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket Settings
    |--------------------------------------------------------------------------
    |
    | Settings for WebSocket/Pusher broadcasting.
    |
    */
    'websocket' => [
        // Enable real-time broadcasting
        'enabled' => env('SMSCHECKER_WEBSOCKET_ENABLED', true),

        // WebSocket channel prefix
        'channel_prefix' => env('SMSCHECKER_CHANNEL_PREFIX', 'sms-checker'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Orphan Transaction Settings
    |--------------------------------------------------------------------------
    |
    | Settings for handling orphan transactions (payments that don't match
    | any order at the time of receipt).
    |
    */
    'orphan' => [
        // How many days to keep orphan transactions before expiring
        'retention_days' => env('SMSCHECKER_ORPHAN_RETENTION_DAYS', 7),

        // Time window in minutes to look for matching orders
        'match_window_minutes' => env('SMSCHECKER_ORPHAN_MATCH_WINDOW', 60),

        // Maximum decimal tolerance for amount matching
        'amount_tolerance' => env('SMSCHECKER_ORPHAN_AMOUNT_TOLERANCE', 0.01),
    ],
];
