<?php

return [
    /*
    |--------------------------------------------------------------------------
    | App product slug
    |--------------------------------------------------------------------------
    |
    | The product whose license keys identify a GigGok user. The app sends its
    | own license key as the bearer token; we resolve it to a user and then
    | report which packs that user owns.
    |
    | If no product with this slug exists nobody authenticates. That is the
    | intended failure: the alternative - accepting a license for any product -
    | would let a license bought for something else read this user's library.
    |
    */
    'app_product_slug' => env('PACKS_APP_PRODUCT_SLUG', 'giggok'),

    /*
    |--------------------------------------------------------------------------
    | Download link lifetime
    |--------------------------------------------------------------------------
    |
    | How long a signed download URL stays valid, in minutes. Long enough to
    | start a download on a slow connection, short enough that a link pasted
    | somewhere public is worthless by the time anyone reads it.
    |
    */
    'download_link_minutes' => (int) env('PACKS_DOWNLOAD_LINK_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Storage disk for pack files
    |--------------------------------------------------------------------------
    |
    | Pack zips must NOT be web-readable. They are streamed by a signed route
    | after the license check, never served straight off the filesystem.
    |
    */
    'disk' => env('PACKS_DISK', 'local'),
];
