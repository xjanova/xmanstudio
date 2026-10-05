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
    | Models the app is allowed to ask for
    |--------------------------------------------------------------------------
    |
    | The app has a model picker, but the bill for whatever it picks is ours,
    | and models differ in price by more than an order of magnitude. So a
    | request may only choose from this list.
    |
    | EMPTY (the default) means the app's choice is ignored entirely and the
    | model configured at /admin/ai-settings is used. That is the safe default:
    | opening this up is a deliberate act, not something that happens because
    | nobody set it.
    |
    | Comma separated, e.g. APP_AI_ALLOWED_MODELS="gpt-4o-mini,gpt-4.1-mini"
    |
    */
    'allowed_models' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('APP_AI_ALLOWED_MODELS', ''))
    ))),

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

    /*
    |--------------------------------------------------------------------------
    | Free keys need an account
    |--------------------------------------------------------------------------
    |
    | check-machine hands a FREE key to any device that asks, so a free key on
    | its own proves nothing about who is spending our AI budget - anyone can
    | mint as many as they like, each with its own daily quota. A free key only
    | reaches the proxy once a signed-in account has claimed it at /giggok/link,
    | which ties the spending to a person we can switch off.
    |
    | Paid keys are unaffected. Set APP_AI_FREE_NEEDS_ACCOUNT=false to let
    | unlinked free devices in (e.g. for a promotion).
    |
    */
    'free_needs_account' => filter_var(env('APP_AI_FREE_NEEDS_ACCOUNT', true), FILTER_VALIDATE_BOOL),
];
