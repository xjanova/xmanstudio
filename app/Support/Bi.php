<?php

namespace App\Support;

/**
 * Bilingual (TH + EN shown together) helper.
 *
 * The site is intentionally NOT a language switcher — every UI string is
 * displayed in Thai AND English at the same time. Most strings live inline in
 * the Blade markup via <x-bi th="..." en="..." />. Strings that repeat across
 * many pages (buttons, common labels) are centralised in lang/bi/*.php and
 * referenced by key, e.g. <x-bi k="common.save" /> or bi('common.save').
 *
 * Key format: "<file>.<dot.path>"  →  lang/bi/<file>.php returns a nested array
 * whose leaves are ['th' => '...', 'en' => '...'].
 */
class Bi
{
    /** @var array<string, array> in-request cache of loaded lang/bi files */
    protected static array $cache = [];

    /**
     * Resolve a key to its ['th' => ..., 'en' => ...] pair, or null if missing.
     */
    public static function get(string $key): ?array
    {
        [$file, $rest] = array_pad(explode('.', $key, 2), 2, null);

        if ($file === null || $rest === null || $file === '') {
            return null;
        }

        $pair = data_get(static::load($file), $rest);

        return is_array($pair) && array_key_exists('th', $pair) ? $pair : null;
    }

    /**
     * Load and cache a lang/bi/<file>.php array.
     */
    protected static function load(string $file): array
    {
        // Guard against path traversal — keys are simple identifiers only.
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $file)) {
            return [];
        }

        if (! array_key_exists($file, static::$cache)) {
            $path = base_path("lang/bi/{$file}.php");
            static::$cache[$file] = is_file($path) ? (array) require $path : [];
        }

        return static::$cache[$file];
    }

    /**
     * Thai text for a key (falls back to the key itself if unknown).
     */
    public static function th(string $key, ?string $fallback = null): string
    {
        return static::get($key)['th'] ?? $fallback ?? $key;
    }

    /**
     * English text for a key (falls back to Thai, then to the key).
     */
    public static function en(string $key, ?string $fallback = null): string
    {
        $pair = static::get($key);

        return $pair['en'] ?? $fallback ?? $pair['th'] ?? $key;
    }

    /**
     * Plain "ไทย / English" string for use in attributes, <title>, <option>,
     * alt/placeholder, JS, etc. — places that cannot contain markup.
     */
    public static function inline(string $key, string $sep = ' / '): string
    {
        $pair = static::get($key);

        if (! $pair) {
            return $key;
        }

        $th = trim($pair['th'] ?? '');
        $en = trim($pair['en'] ?? '');

        return ($th !== '' && $en !== '') ? $th . $sep . $en : ($th ?: $en);
    }
}
