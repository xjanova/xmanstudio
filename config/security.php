<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Every IP-based defence in this app — the login throttle, the per-IP
    | failure counter, the block list, the Turnstile remoteip — is only as
    | honest as request()->ip(). That value comes from X-Forwarded-For, which
    | is a header the client writes, so it can be trusted only when the
    | connection itself arrives from a proxy we know.
    |
    | This used to be '*', meaning any client could hand us any IP. Five wrong
    | passwords then cost nothing: change the header, get a fresh throttle
    | bucket, keep guessing. The list below is the fix — Cloudflare's own
    | ranges (the site sits behind it) plus loopback and private ranges for the
    | local web server that fronts PHP-FPM.
    |
    | Refresh the Cloudflare entries with: php artisan security:refresh-proxies
    |
    */

    'trusted_proxies' => [

        // Local reverse proxy (nginx/apache -> php-fpm) and private LAN
        '127.0.0.1',
        '::1',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        'fc00::/7',

        // Cloudflare IPv4 — https://www.cloudflare.com/ips-v4
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',

        // Cloudflare IPv6 — https://www.cloudflare.com/ips-v6
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ],

    /*
    |--------------------------------------------------------------------------
    | Login attempt log
    |--------------------------------------------------------------------------
    |
    | How long a row survives. Long enough to investigate a break-in attempt
    | that happened while nobody was looking, short enough that the table is
    | not a permanent list of who signs in from where.
    |
    */

    'login_log_days' => (int) env('LOGIN_LOG_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Automatic IP blocking
    |--------------------------------------------------------------------------
    |
    | The throttle in LoginRequest only slows one IP+email pair down for a
    | minute. These thresholds are what actually stops a run: too many failures
    | from one address inside the window and the address is refused outright
    | for 'block_minutes', with the block written down so an admin can see and
    | undo it.
    |
    | 'enabled' false leaves the counting and the log in place and only skips
    | the refusal, which is the safe setting while tuning the numbers.
    |
    */

    'auto_block' => [
        'enabled' => (bool) env('LOGIN_AUTO_BLOCK', true),

        // Failures from one IP across all accounts inside the window.
        'ip_failures' => (int) env('LOGIN_AUTO_BLOCK_FAILURES', 20),

        // Distinct accounts one IP touched inside the window. Credential
        // stuffing spreads thin — one try each across a list — so it can stay
        // under the failure count above while being far more obviously a bot
        // than a person who forgot which password they used.
        'ip_accounts' => (int) env('LOGIN_AUTO_BLOCK_ACCOUNTS', 8),

        'window_minutes' => (int) env('LOGIN_AUTO_BLOCK_WINDOW', 15),
        'block_minutes' => (int) env('LOGIN_AUTO_BLOCK_MINUTES', 60),

        // An address an admin signed in from successfully this recently is
        // never blocked automatically. Locking the owner out of their own
        // office network is worse than letting a run of guesses continue while
        // the alert is read.
        'admin_grace_days' => (int) env('LOGIN_AUTO_BLOCK_ADMIN_GRACE', 30),

        // Exact addresses the blocker must never touch, whatever the counters
        // say. Comma-separated in .env.
        'never_block' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('LOGIN_NEVER_BLOCK_IPS', ''))
        ))),
    ],
];
