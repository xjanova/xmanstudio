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

];
