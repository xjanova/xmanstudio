<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Daily calls per license
    |--------------------------------------------------------------------------
    |
    | Our OpenAI key does the talking, so a leaked license key spends our money.
    | This is the ceiling that stops one device from running up the bill.
    |
    | Counted from app_ai_usages rows since midnight, failures included: a
    | caller stuck in a retry loop still costs us upstream attempts, and not
    | counting failures turns a broken request into an unlimited one.
    |
    */
    'daily_limit' => (int) env('APP_AI_DAILY_LIMIT', 200),

    /*
    |--------------------------------------------------------------------------
    | Request size limits
    |--------------------------------------------------------------------------
    |
    | The app sends a persona system prompt plus a short history. These caps are
    | generous for that and still stop someone pasting a novel through our key.
    |
    */
    'max_messages' => (int) env('APP_AI_MAX_MESSAGES', 40),
    'max_chars' => (int) env('APP_AI_MAX_CHARS', 24000),

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | Turn the app proxy off without touching the website's own AI chat, which
    | runs through the same AiChatService but a different controller.
    |
    */
    'enabled' => filter_var(env('APP_AI_ENABLED', true), FILTER_VALIDATE_BOOL),
];
