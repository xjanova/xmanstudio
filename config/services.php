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
    'aipray' => [
        // The app's consent notice promises this (lib/config/privacy.dart,
        // PrivacyConfig.retention). Change both together.
        'audio_retention_days' => (int) env('AIPRAY_AUDIO_RETENTION_DAYS', 1095),
        // /apps/aipray/privacy shows a "draft" banner until the owner has
        // reviewed the text (and the app's PrivacyConfig placeholders).
        'privacy_policy_reviewed' => (bool) env('AIPRAY_PRIVACY_REVIEWED', false),
        'dpo_email' => env('AIPRAY_DPO_EMAIL', 'privacy@xman4289.com'),
    ],

    'simplepod' => [
        // Renting costs money: nothing is rented unless this is explicitly on.
        'enabled' => (bool) env('SIMPLEPOD_ENABLED', false),
        // Prefer a SimplePod account separate from aixman's: the orphan sweep
        // only ever touches instances named `aipray-train-*`, but a shared
        // balance means one project can starve the other.
        'api_key' => env('SIMPLEPOD_API_KEY'),
        'base_url' => env('SIMPLEPOD_BASE_URL', 'https://api.simplepod.ai'),
    ],

    // Whisper fine-tuning on a rented GPU (App\Services\AiprayGpuTrainingRunner).
    'aipray_gpu' => [
        'max_price_per_hour' => (float) env('AIPRAY_GPU_MAX_PRICE', 1.0),
        // Used for estimates when the market cannot be read.
        'expected_price_per_hour' => (float) env('AIPRAY_GPU_EXPECTED_PRICE', 0.5),
        'gpu_models' => ['A100', 'H100', 'L40', 'RTX 4090', 'RTX 5090', 'RTX PRO 6000'],
        'min_vram_mb' => ['whisper-medium' => 40960, 'default' => 24576],
        // The image ships torch 2.5.1, the version ml-service pins; hosts must
        // run a driver for CUDA 12.4 or newer (checked client-side, see
        // SimplePodClient::offers()).
        'image' => 'pytorch/pytorch',
        'image_tag' => '2.5.1-cuda12.4-cudnn9-runtime',
        'min_cuda' => '12.4',
        'disk_gb' => 60,
        'port' => 8100,
        // The trainer (training/ml-service) is fetched from GitHub at boot.
        // No default, and nothing is rented until it is set: point it at the
        // branch or commit that carries remote.py. Not Aipray's main, where
        // a push releases the APK. A commit SHA gives reproducible runs.
        'trainer_repo' => 'xjanova/Aipray',
        'trainer_ref' => env('AIPRAY_TRAINER_REF'),
        // "Enough voice" before an admin may rent a GPU for a real run. The
        // chants are fixed text, so what matters is every line heard in many
        // voices, not raw hours. Starting points; tune after the first runs.
        'readiness' => [
            'min_samples' => (int) env('AIPRAY_TRAIN_MIN_SAMPLES', 3000),
            'min_contributors' => (int) env('AIPRAY_TRAIN_MIN_CONTRIBUTORS', 20),
            'min_samples_per_line' => 5,
            'min_line_coverage' => 0.8,
        ],
        // Floor even for a trial run (a small run that checks the pipeline).
        'min_samples' => 50,
        'queue_timeout_minutes' => 60,
        'boot_timeout_minutes' => 45,
        'unreachable_minutes' => 15,
        'max_lifetime_minutes' => 480,
        // Estimates only. Whisper pads every clip to 30 s, so cost follows the
        // number of samples, not their length. Samples per second of training
        // on an A100-class GPU in fp32, batch 16; replace with measured values
        // after the first real run.
        'throughput' => [
            'whisper-tiny' => 120,
            'whisper-base' => 70,
            'whisper-small' => 30,
            'whisper-medium' => 10,
        ],
        // Boot, apt/pip install, model download and packaging.
        'overhead_minutes' => 20,
    ],

    'aipray_ml' => [
        'url' => env('AIPRAY_ML_SERVICE_URL', 'http://localhost:8100'),
        // No default. The old one was published with the code, which made it a
        // password everyone had; with this unset the training callback answers 503.
        'secret' => env('AIPRAY_ML_SERVICE_SECRET'),
        'timeout' => env('AIPRAY_ML_SERVICE_TIMEOUT', 300),
    ],

    // AIXMAN — AI generation platform (ai.xman4289.com, Next.js, shared DB)
    // See CLAUDE.md "Cross-Project Relationship with AIXMAN" section.
    // GPUxMINE relay — ตัวรับสายจากเครื่องที่บ้านคน
    //
    // admin key นี้ออก worker ใหม่ได้ทั้งเครือข่าย XMAN Studio เป็นที่เดียว
    // ที่ถือมัน เจ้าของเครื่องไม่เคยเห็น และ aixman ก็ไม่ต้องรู้จัก relay เลย
    'gpuxmine' => [
        'relay_url' => env('GPUXMINE_RELAY_URL'),
        'admin_key' => env('GPUXMINE_RELAY_ADMIN_KEY'),

        // เครื่องต่อบัญชี (D9) — บัญชีเดียวลงทะเบียนเครื่องได้ไม่จำกัดคือช่องให้
        // สร้าง worker ปลอมเป็นกอง ทั้งถ่วง relay และแย่งคิวงาน 0 = ไม่จำกัด
        'max_nodes_per_user' => (int) env('GPUXMINE_MAX_NODES_PER_USER', 10),

        // ส่งสถานะเครื่องให้ aixman ซ้ำทุกกี่นาทีแม้ไม่มีอะไรเปลี่ยน — เผื่อ
        // aixman ปิด worker ไปเองระหว่างนั้น (ดู GpuxMineNodeStateService)
        'resync_minutes' => (int) env('GPUXMINE_RESYNC_MINUTES', 10),

        // เงินจากงานแต่ละชิ้นพักไว้กี่ชั่วโมงก่อนเข้ากระเป๋า (D2) — ช่วงที่แอดมิน
        // ยังระงับเครื่องที่ส่งผลงานปลอมทันก่อนเงินออก ดู gpuxmine:settle-earnings
        'earning_hold_hours' => (int) env('GPUXMINE_EARNING_HOLD_HOURS', 24),

        // ส่วนแบ่งผู้แนะนำที่ถึงวันโอนแล้วผู้แนะนำไม่ active (ถูกระงับ/ถูกลบ) ไปที่ไหน:
        // owner = คืนเข้ากระเป๋าเจ้าของเครื่อง (ค่าเริ่มต้น — หักจากเขาตั้งแต่แรก)
        // platform = แพลตฟอร์มเก็บ ทั้งสองแบบบันทึกลงแถวและเห็นในหน้าแอดมิน GPUxMINE
        'unpaid_referral' => env('GPUXMINE_UNPAID_REFERRAL', 'owner'),
    ],

    'aixman' => [
        'api_base' => env('AIXMAN_API_BASE', 'https://ai.xman4289.com'),

        // Public site URL for X-DREAMER. Every outbound link from xmanstudio
        // (nav, ecosystem card, footer) must point here, not at the local
        // /xdreamer/* mirror pages.
        'site_url' => env('AIXMAN_SITE_URL', 'https://ai.xman4289.com'),
        'webhook_url' => env('AIXMAN_WEBHOOK_URL', 'https://ai.xman4289.com/api/webhooks/xman-credit'),
        'webhook_secret' => env('AIXMAN_WEBHOOK_SECRET'),
        'timeout' => env('AIXMAN_TIMEOUT', 10),
        'cache_ttl' => env('AIXMAN_CACHE_TTL', 600), // 10 min

        // "Sign in with XMAN ID" — used by the X-DREAMER mobile app and by
        // ai.xman4289.com's web login.
        //
        // aixman holds this secret (as XMAN_SSO_SECRET in its own .env — same
        // value, different variable name) and is the only caller allowed to
        // redeem an authorization code. Without it the exchange endpoint answers
        // 503, so a half-configured deploy fails closed rather than open.
        'sso_secret' => env('XDREAMER_SSO_SECRET'),

        // Exact-match allowlist. Anything not listed here cannot be handed a
        // code, which is what stops this being an open redirector.
        //
        // Two entries by default: the mobile app's custom URL scheme, and the
        // web callback on aixman. The web one must match
        // `ssoRedirectUri()` in aixman's src/lib/xman-sso.ts character for
        // character — a trailing slash on either side breaks sign-in with a
        // bare 400.
        'sso_redirect_uris' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'XDREAMER_SSO_REDIRECT_URIS',
                'xdreamer://auth/callback,https://ai.xman4289.com/api/auth/xman/callback'
            ))
        ))),
    ],

];
