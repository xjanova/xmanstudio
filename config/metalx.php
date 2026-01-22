<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Metal-X YouTube Channel Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Metal-X YouTube channel integration, AI services,
    | and content management.
    |
    */

    /**
     * YouTube API Configuration
     */
    'youtube' => [
        'api_key' => env('METALX_YOUTUBE_API_KEY', ''),
        'channel_id' => env('METALX_YOUTUBE_CHANNEL_ID', ''),
        'channel_name' => env('METALX_CHANNEL_NAME', 'Metal-X'),
        'channel_description' => env('METALX_CHANNEL_DESCRIPTION', 'A metal fabrication and engineering company'),

        // API request timeouts (seconds)
        'timeout' => env('METALX_YOUTUBE_TIMEOUT', 30),

        // Maximum results per request
        'max_results' => env('METALX_YOUTUBE_MAX_RESULTS', 50),

        // Enable quota tracking
        'track_quota' => env('METALX_YOUTUBE_TRACK_QUOTA', true),

        // Daily quota limit (YouTube default: 10,000)
        'daily_quota_limit' => env('METALX_YOUTUBE_DAILY_QUOTA', 10000),
    ],

    /**
     * AI Service Configuration
     */
    'ai' => [
        // Default AI provider (openai, claude, ollama)
        'provider' => env('METALX_AI_PROVIDER', 'openai'),

        // AI generation settings
        'temperature' => env('METALX_AI_TEMPERATURE', 0.7),
        'max_tokens' => env('METALX_AI_MAX_TOKENS', 2000),

        // OpenAI configuration
        'openai' => [
            'api_key' => env('METALX_OPENAI_API_KEY', ''),
            'model' => env('METALX_OPENAI_MODEL', 'gpt-4o-mini'),
            'timeout' => env('METALX_OPENAI_TIMEOUT', 60),
        ],

        // Claude configuration
        'claude' => [
            'api_key' => env('METALX_CLAUDE_API_KEY', ''),
            'model' => env('METALX_CLAUDE_MODEL', 'claude-3-haiku-20240307'),
            'timeout' => env('METALX_CLAUDE_TIMEOUT', 60),
        ],

        // Ollama configuration (local AI)
        'ollama' => [
            'base_url' => env('METALX_OLLAMA_URL', 'http://localhost:11434'),
            'model' => env('METALX_OLLAMA_MODEL', 'llama2'),
            'timeout' => env('METALX_OLLAMA_TIMEOUT', 120),
        ],

        // Auto-approval settings
        'auto_approve' => [
            'enabled' => env('METALX_AI_AUTO_APPROVE', false),
            'min_confidence' => env('METALX_AI_MIN_CONFIDENCE', 80.0),
        ],

        // Content generation settings
        'content_generation' => [
            'enabled' => env('METALX_AI_CONTENT_GENERATION', true),
            'generate_on_import' => env('METALX_AI_GENERATE_ON_IMPORT', true),
        ],
    ],

    /**
     * Comment Engagement Configuration
     */
    'engagement' => [
        // Auto-engagement settings
        'auto_reply' => [
            'enabled' => env('METALX_AUTO_REPLY', false),
            'min_confidence' => env('METALX_AUTO_REPLY_MIN_CONFIDENCE', 85.0),
        ],

        'auto_like' => [
            'enabled' => env('METALX_AUTO_LIKE', false),
            'positive_only' => env('METALX_AUTO_LIKE_POSITIVE_ONLY', true),
        ],

        // Comment sync settings
        'sync' => [
            'max_comments_per_video' => env('METALX_SYNC_MAX_COMMENTS', 100),
            'sync_replies' => env('METALX_SYNC_REPLIES', true),
            'process_engagement' => env('METALX_SYNC_PROCESS_ENGAGEMENT', true),
        ],

        // Sentiment analysis
        'sentiment' => [
            'enabled' => env('METALX_SENTIMENT_ANALYSIS', true),
            'track_score' => env('METALX_SENTIMENT_TRACK_SCORE', true),
        ],
    ],

    /**
     * Content Moderation Configuration
     */
    'moderation' => [
        // Auto-moderation settings
        'auto_moderate' => [
            'enabled' => env('METALX_AUTO_MODERATE', true),
            'auto_delete_violations' => env('METALX_AUTO_DELETE_VIOLATIONS', true),
            'auto_block_repeat_offenders' => env('METALX_AUTO_BLOCK_REPEAT_OFFENDERS', true),
        ],

        // Violation detection
        'detection' => [
            'use_pattern_matching' => env('METALX_USE_PATTERN_MATCHING', true),
            'use_ai_detection' => env('METALX_USE_AI_DETECTION', true),
            'min_confidence' => env('METALX_VIOLATION_MIN_CONFIDENCE', 75.0),
        ],

        // Blacklist settings
        'blacklist' => [
            'enabled' => env('METALX_BLACKLIST_ENABLED', true),
            'delete_all_comments' => env('METALX_BLACKLIST_DELETE_ALL', true),
            'track_violations' => env('METALX_BLACKLIST_TRACK_VIOLATIONS', true),
        ],

        // Violation thresholds
        'thresholds' => [
            'auto_block_after_violations' => env('METALX_AUTO_BLOCK_THRESHOLD', 3),
            'critical_severity_auto_block' => env('METALX_CRITICAL_AUTO_BLOCK', true),
        ],
    ],

    /**
     * Rate Limiting Configuration
     */
    'rate_limiting' => [
        'ai_operations' => [
            'max_per_minute' => env('METALX_RATE_LIMIT_AI', 10),
        ],
        'youtube_operations' => [
            'max_per_minute' => env('METALX_RATE_LIMIT_YOUTUBE', 20),
        ],
        'comment_moderation' => [
            'max_per_minute' => env('METALX_RATE_LIMIT_MODERATION', 30),
        ],
    ],

    /**
     * Caching Configuration
     */
    'cache' => [
        // Cache settings (seconds)
        'ttl' => [
            'settings' => env('METALX_CACHE_SETTINGS_TTL', 3600), // 1 hour
            'ai_responses' => env('METALX_CACHE_AI_TTL', 86400), // 24 hours
            'youtube_data' => env('METALX_CACHE_YOUTUBE_TTL', 1800), // 30 minutes
        ],

        // Cache keys prefix
        'prefix' => env('METALX_CACHE_PREFIX', 'metalx:'),
    ],

    /**
     * Logging Configuration
     */
    'logging' => [
        // Enable detailed logging
        'enabled' => env('METALX_LOGGING_ENABLED', true),

        // Log channels
        'channel' => env('METALX_LOG_CHANNEL', 'stack'),

        // Log levels for different operations
        'levels' => [
            'ai_operations' => env('METALX_LOG_LEVEL_AI', 'info'),
            'youtube_api' => env('METALX_LOG_LEVEL_YOUTUBE', 'warning'),
            'moderation' => env('METALX_LOG_LEVEL_MODERATION', 'info'),
        ],
    ],

    /**
     * Security Configuration
     */
    'security' => [
        // Input sanitization
        'sanitize_prompts' => env('METALX_SANITIZE_PROMPTS', true),
        'max_prompt_length' => env('METALX_MAX_PROMPT_LENGTH', 5000),

        // Encryption
        'encrypt_api_keys' => env('METALX_ENCRYPT_API_KEYS', true),

        // XSS Protection
        'sanitize_html' => env('METALX_SANITIZE_HTML', true),
    ],

    /**
     * Queue Configuration
     */
    'queue' => [
        // Queue names for different job types
        'queues' => [
            'ai_generation' => env('METALX_QUEUE_AI', 'metalx-ai'),
            'youtube_sync' => env('METALX_QUEUE_YOUTUBE', 'metalx-youtube'),
            'moderation' => env('METALX_QUEUE_MODERATION', 'metalx-moderation'),
        ],

        // Job retries
        'retries' => [
            'ai_jobs' => env('METALX_RETRIES_AI', 3),
            'youtube_jobs' => env('METALX_RETRIES_YOUTUBE', 5),
            'moderation_jobs' => env('METALX_RETRIES_MODERATION', 2),
        ],

        // Job timeouts (seconds)
        'timeouts' => [
            'ai_jobs' => env('METALX_TIMEOUT_AI_JOBS', 180),
            'youtube_jobs' => env('METALX_TIMEOUT_YOUTUBE_JOBS', 120),
            'moderation_jobs' => env('METALX_TIMEOUT_MODERATION_JOBS', 60),
        ],
    ],

    /**
     * Feature Flags
     */
    'features' => [
        'ai_metadata_generation' => env('METALX_FEATURE_AI_METADATA', true),
        'comment_engagement' => env('METALX_FEATURE_ENGAGEMENT', true),
        'auto_moderation' => env('METALX_FEATURE_AUTO_MODERATION', true),
        'sentiment_analysis' => env('METALX_FEATURE_SENTIMENT', true),
        'blacklist_management' => env('METALX_FEATURE_BLACKLIST', true),
        'analytics_tracking' => env('METALX_FEATURE_ANALYTICS', true),
    ],

    /**
     * Circuit Breaker Configuration
     * Prevents cascading failures from external APIs
     */
    'circuit_breaker' => [
        'enabled' => env('METALX_CIRCUIT_BREAKER_ENABLED', true),

        // Failure threshold before opening circuit
        'failure_threshold' => env('METALX_CIRCUIT_BREAKER_THRESHOLD', 5),

        // Time window for counting failures (seconds)
        'failure_window' => env('METALX_CIRCUIT_BREAKER_WINDOW', 60),

        // How long to wait before trying again (seconds)
        'recovery_time' => env('METALX_CIRCUIT_BREAKER_RECOVERY', 300),
    ],

    /**
     * Notification Configuration
     */
    'notifications' => [
        // Line Notify integration
        'line_notify' => [
            'enabled' => env('METALX_LINE_NOTIFY_ENABLED', false),
            'token' => env('METALX_LINE_NOTIFY_TOKEN', ''),
        ],

        // Notification events
        'events' => [
            'quota_exceeded' => env('METALX_NOTIFY_QUOTA_EXCEEDED', true),
            'moderation_alerts' => env('METALX_NOTIFY_MODERATION', true),
            'ai_failures' => env('METALX_NOTIFY_AI_FAILURES', true),
        ],
    ],
];
