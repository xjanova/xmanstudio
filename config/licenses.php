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
];
