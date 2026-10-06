<?php

/*
|--------------------------------------------------------------------------
| GigGok AI proxy
|--------------------------------------------------------------------------
|
| Every message is paid from the user's wallet (THB) - there is no free quota
| any more. WHICH models the app may use and what each costs per message, plus
| the per-user daily spending cap, are set by the admin at /admin/ai-settings
| (Settings keys appai_models / appai_daily_cap / appai_enabled), not here:
| prices change far more often than deploys happen. See App\Services\AppAiBilling.
|
| What stays here are the guards that are not business decisions.
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Request size limits
    |--------------------------------------------------------------------------
    |
    | The app sends a persona system prompt plus a short history. These caps are
    | generous for that and still stop someone pasting a novel through our key
    | for the price of one message.
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
    | runs through the same AiChatService but a different controller. The admin
    | page has its own switch too; the proxy is open only when both are on.
    |
    */
    'enabled' => filter_var(env('APP_AI_ENABLED', true), FILTER_VALIDATE_BOOL),
];
