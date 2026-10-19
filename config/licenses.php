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
        'anti-x' => ['monthly' => 199, 'yearly' => 1990, 'lifetime' => 9900],
        'autotradex' => ['monthly' => 299, 'yearly' => 1990, 'lifetime' => 19900],
        'brainx' => ['monthly' => 399],
        'cluadex-ai-coding-assistant' => ['yearly' => 199, 'lifetime' => 1999],
        // Chanthra Studio — moved here from ChanthraStudioWebController::PRICING (2026-10-12) so the hub prints it too
        'chanthra-studio' => ['monthly' => 399, 'yearly' => 2500, 'lifetime' => 7500],
        // LocalVPN Pro — owner 2026-10-11 (was 399 / 2,500 / 5,000)
        'localvpn' => ['monthly' => 199, 'yearly' => 1990, 'lifetime' => 4990],
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
    | of the table above has a checkout of its own (/tping, /smschecker,
    | /autotradex) that ties the purchase to the app's machine id. LocalVPN
    | sells both ways: /products/localvpn through the cart (the key is typed
    | into the app), /localvpn/checkout for the app's own machine-bound flow.
    |
    */
    'cart_products' => ['anti-x', 'brainx', 'cluadex-ai-coding-assistant', 'localvpn', 'sms-payment-checker', 'winx-tools'],

    /*
    |--------------------------------------------------------------------------
    | Devices one purchased key runs on
    |--------------------------------------------------------------------------
    |
    | A paid key of a product listed here runs on that many devices at once
    | (LocalVPN: a PC and a phone). Every key the store issues for it gets this
    | max_activations, and the product license API binds the extra devices as
    | seats of the same key (App\Support\LicenseSeats). A product missing here
    | stays one key = one machine, exactly as before. Trial and free keys are
    | always one device.
    |
    */
    'devices_per_key' => [
        'localvpn' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cloud space a key carries
    |--------------------------------------------------------------------------
    |
    | Every key of a product listed here starts with base_gb of cloud space;
    | the admin can add up to max_extra_gb more to one key (admin ▸ License ▸
    | พื้นที่คลาวด์). The status/validate API reports the total as
    | cloud_storage_bytes and the product's server enforces it
    | (App\Support\CloudStorage). A product missing here has no cloud space.
    |
    | addons are the packs the customer buys for one of their own keys from
    | its license page — GB => price in THB, paid once. The space stays with
    | the key for as long as the key lives, renewals included (owner,
    | 2026-10-19). They are sold through the cart as product addon_product
    | (created by a migration, never listed in the store); what was bought and
    | what the admin added together stay within max_extra_gb.
    |
    */
    'cloud_storage' => [
        'brainx' => [
            'base_gb' => 4,
            'max_extra_gb' => 1024,
            'addon_product' => 'brainx-cloud-storage',
            // owner 2026-10-19
            'addons' => [4 => 99, 12 => 249, 28 => 499],
        ],
    ],
];
