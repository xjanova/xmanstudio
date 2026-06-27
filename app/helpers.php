<?php

use App\Support\Bi;

/*
|--------------------------------------------------------------------------
| Bilingual (TH + EN) global helpers
|--------------------------------------------------------------------------
| Loaded via AppServiceProvider::register(). Convenience wrappers around
| App\Support\Bi for use inside Blade attributes / plain-string contexts.
*/

if (! function_exists('bi')) {
    /**
     * Plain "ไทย / English" string for attributes, <title>, <option>, JS, etc.
     * For visible HTML content prefer the <x-bi> component instead.
     */
    function bi(string $key, string $sep = ' / '): string
    {
        return Bi::inline($key, $sep);
    }
}

if (! function_exists('bi_th')) {
    function bi_th(string $key, ?string $fallback = null): string
    {
        return Bi::th($key, $fallback);
    }
}

if (! function_exists('bi_en')) {
    function bi_en(string $key, ?string $fallback = null): string
    {
        return Bi::en($key, $fallback);
    }
}
