<?php

return [
    'public_key' => env('STRIPE_PUBLIC_KEY', ''),
    'secret_key' => env('STRIPE_SECRET_KEY', ''),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET', ''),
    'currency' => env('STRIPE_CURRENCY', 'thb'),
    // Stripe amounts are in satang for THB (smallest currency unit)
    'currency_multiplier' => 100,
];
