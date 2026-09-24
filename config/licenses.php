<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Products sold as a subscription on ONE key
    |--------------------------------------------------------------------------
    |
    | Buying one of these again extends the key the customer already holds
    | instead of issuing a second one: expires_at moves to
    | max(now, expires_at) + one term per unit bought, and the key keeps its
    | value. BrainX Cloud needs this — the cloud account IS the key (its id is
    | a hash of the key), so a new key would be a new, empty account.
    |
    | A guest order, a customer whose every key of the product was revoked, or
    | a first purchase still gets a new key — one key carrying the whole term.
    | See LicenseService::generateLicensesForOrder().
    |
    */
    'renewable_products' => ['brainx'],

    /*
    |--------------------------------------------------------------------------
    | What each licensed product sells, and for how much (THB)
    |--------------------------------------------------------------------------
    |
    | The one price table. The pricing API (/api/v1/product/{slug}/pricing),
    | the cart, the product pages and each product's own checkout (Tping,
    | LocalVPN, SmsChecker, AutoTradeX, CluadeX) all read their numbers here
    | through App\Support\LicensePlans — a price is changed in this file and
    | nowhere else.
    |
    | Terms are monthly, yearly and lifetime, offered in the order written. A
    | product missing here sells no term: the pricing API answers with no plans
    | instead of making a price up.
    |
    */
    'plans' => [
        'autotradex' => ['monthly' => 299, 'yearly' => 1990, 'lifetime' => 19900],
        'brainx' => ['monthly' => 399],
        'cluadex-ai-coding-assistant' => ['yearly' => 199, 'lifetime' => 1999],
        'localvpn' => ['monthly' => 399, 'yearly' => 2500, 'lifetime' => 5000],
        'sms-payment-checker' => ['monthly' => 990, 'yearly' => 9900, 'lifetime' => 29900],
        'smschecker' => ['monthly' => 499, 'yearly' => 4990, 'lifetime' => 29000],
        'tping' => ['monthly' => 399, 'yearly' => 2500, 'lifetime' => 5000],
        'winx-tools' => ['yearly' => 199],
    ],

    /*
    |--------------------------------------------------------------------------
    | Products bought through the site cart
    |--------------------------------------------------------------------------
    |
    | POST /cart/add with a license_type is accepted only for these. The rest
    | of the table above has a checkout of its own (/tping, /localvpn,
    | /smschecker, /autotradex) that ties the purchase to the app's machine id.
    |
    */
    'cart_products' => ['brainx', 'cluadex-ai-coding-assistant', 'sms-payment-checker', 'winx-tools'],
];
