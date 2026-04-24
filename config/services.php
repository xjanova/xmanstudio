<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Line Messaging API (replaces deprecated Line Notify)
    'line' => [
        'channel_access_token' => env('LINE_CHANNEL_ACCESS_TOKEN'),
        'admin_user_id' => env('LINE_ADMIN_USER_ID'),
    ],

    // YouTube OAuth API
    'youtube' => [
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        // Redirect URI will be: {APP_URL}/auth/youtube/callback
        // Example: https://yourdomain.com/auth/youtube/callback
        // Add this exact URL to Google Cloud Console > APIs & Services > Credentials > OAuth 2.0 Client IDs > Authorized redirect URIs
    ],

    // Firebase Cloud Messaging (FCM) for push notifications
    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/service-account.json')),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

    // GitHub API for issue tracking and bug reports
    'github' => [
        'token' => env('GITHUB_API_TOKEN'),
        'owner' => env('GITHUB_OWNER', 'xjanova'),
        'repo' => env('GITHUB_REPO', 'xmanstudio'),
    ],

    // Puzzle ML inference service (Python microservice)
    'puzzle_ml' => [
        'url' => env('PUZZLE_ML_URL', 'http://127.0.0.1:5050/predict'),
    ],

    // Aipray ML inference service (Python microservice for chant AI)
    'aipray_ml' => [
        'url' => env('AIPRAY_ML_SERVICE_URL', 'http://localhost:8100'),
        'secret' => env('AIPRAY_ML_SERVICE_SECRET', 'ml-service-secret-key'),
        'timeout' => env('AIPRAY_ML_SERVICE_TIMEOUT', 300),
    ],

    // AIXMAN — AI generation platform (ai.xman4289.com, Next.js, shared DB)
    // See CLAUDE.md "Cross-Project Relationship with AIXMAN" section.
    'aixman' => [
        'api_base'       => env('AIXMAN_API_BASE', 'https://ai.xman4289.com'),
        'webhook_url'    => env('AIXMAN_WEBHOOK_URL', 'https://ai.xman4289.com/api/webhooks/xman-credit'),
        'webhook_secret' => env('AIXMAN_WEBHOOK_SECRET'),
        'timeout'        => env('AIXMAN_TIMEOUT', 10),
        'cache_ttl'      => env('AIXMAN_CACHE_TTL', 600), // 10 min
    ],

];
