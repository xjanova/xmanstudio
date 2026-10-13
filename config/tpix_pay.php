<?php

/*
|--------------------------------------------------------------------------
| Pay with TPIX coin (owner, 2026-10-13: "ชำระเหรียญได้จริง ตามราคาปัจจุบันจริงๆ")
|--------------------------------------------------------------------------
|
| TPIX Chain is an EVM chain (id 4289, native coin TPIX, 18 decimals, gas price 0, ~2 s blocks).
| The price is read live from the chain's own DEX pool (WTPIX/USDT), never typed in: there is no
| THB market for TPIX, so the THB price is pool price × the USD→THB rate.
|
| The switch, the receive address and the confirmation count live in payment_settings (group
| `tpix`, set from /admin/tpix-payments); the values here are the defaults and the chain facts.
*/

return [
    'chain_id' => 4289,
    'chain_name' => 'TPIX Chain',
    'symbol' => 'TPIX',
    'decimals' => 18,

    // Cloudflare in front of both refuses a request without a User-Agent (see user_agent below)
    'rpc_url' => env('TPIX_PAY_RPC_URL', 'https://rpc.tpix.online'),
    'rpc_backup_url' => env('TPIX_PAY_RPC_BACKUP_URL', 'https://rpc1.tpix.online'),
    'explorer_url' => env('TPIX_PAY_EXPLORER_URL', 'https://explorer.tpix.online'),
    'user_agent' => 'Mozilla/5.0 (compatible; XmanStudio-TpixPay/1.0; +https://xman4289.com)',
    'rpc_timeout_seconds' => 8,

    // The TPIX DEX pool the price comes from. token0 is checked on every read: if the pair ever
    // lists USDT first, the reserves are swapped rather than read upside down.
    'pair_address' => '0x3684E0eBD1bd7eF7B7783e695639BADCD04BC4f0',
    'wtpix_address' => '0x0A3dB168aF6b85e61D81CA78C608BDadAC832DFF',
    'usdt_address' => '0x0A4Ee87551eF041f3EecFBBfdcfFC1B46a4878f1',
    'usdt_decimals' => 6,

    // A pool with less than this much USDT in it is too thin to price anything: no quotes, no
    // TPIX prices on the pages.
    'min_pool_usd' => 5,

    // Defaults for what the admin page can override
    'confirmations' => 3,
    'quote_ttl_minutes' => 30,

    // A transfer that lands after its quote expired is still found for this long (and parked as
    // `late` for the admin); the quote's exact amount stays reserved until then.
    'late_grace_minutes' => 120,

    // Pages read the price at most this often; the chain is asked once per period, not per page.
    'display_cache_seconds' => 60,

    // Quotes use the lower of the live price and the average of this window, so pumping the thin
    // pool for a minute does not buy a cheap quote.
    'average_window_minutes' => 30,
    'average_min_snapshots' => 3,

    // USD→THB. Payments never fall back to a typed-in rate: with no live rate and none fetched in
    // the last fx_max_age_hours, nothing is quoted and the pages hide their TPIX prices.
    'fx_urls' => [
        'https://open.er-api.com/v6/latest/USD',
        'https://api.exchangerate-api.com/v4/latest/USD',
    ],
    'fx_refresh_minutes' => 60,
    'fx_max_age_hours' => 24,
];
