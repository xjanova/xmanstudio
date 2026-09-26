<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * The site logo, made readable on the 3D home's near-black space.
 *
 * The logo uploaded on the Branding page (Setting `site_logo`, on the public disk) is drawn for
 * light pages: its wordmark is black ink, which vanishes on the universe. This makes a copy with
 * every dark, colourless pixel turned white — the coloured parts (the circuit X, the tagline)
 * are left as they are — and keeps it beside the original, named after the original's path and
 * modified time, so a new upload simply gets a new copy.
 *
 * Anything that cannot be recoloured (an SVG, a JPEG with no transparency, a missing file, no
 * GD) falls back to the original URL.
 */
class BrandLogo
{
    /** A pixel this dark and this grey is "ink": it becomes white. */
    private const INK_MAX_LEVEL = 110;

    private const INK_MAX_CHROMA = 48;

    public static function url(): ?string
    {
        $path = Setting::getValue('site_logo');

        return $path ? asset('storage/' . $path) : null;
    }

    public static function darkUrl(): ?string
    {
        $path = Setting::getValue('site_logo');
        if (! $path) {
            return null;
        }

        $disk = Storage::disk('public');
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($ext, ['png', 'webp'], true) || ! function_exists('imagecreatefrompng') || ! $disk->exists($path)) {
            return self::url();
        }

        $copy = 'branding/on-dark-' . substr(md5($path . '|' . $disk->lastModified($path)), 0, 16) . '.png';
        if (! $disk->exists($copy)) {
            // A copy that could not be made is not tried again on every page view:
            // walking every pixel is the slow part.
            $failed = 'brand-logo:no-dark-copy:' . $copy;
            if (Cache::has($failed)) {
                return self::url();
            }
            $png = self::recolour($disk->path($path), $ext);
            if ($png === null || ! $disk->put($copy, $png)) {
                Cache::put($failed, true, now()->addHour());
                Log::warning('BrandLogo: no dark copy of the site logo', ['logo' => $path, 'readable' => $png !== null]);

                return self::url();
            }
        }

        return asset('storage/' . $copy);
    }

    /**
     * @return string|null PNG bytes, or null when the image cannot be read
     */
    public static function recolour(string $file, string $ext): ?string
    {
        try {
            $im = $ext === 'webp' ? @imagecreatefromwebp($file) : @imagecreatefrompng($file);
        } catch (\Throwable $e) {
            $im = false;
        }
        if (! $im) {
            return null;
        }

        if (! imageistruecolor($im)) {
            imagepalettetotruecolor($im);
        }
        imagealphablending($im, false);
        imagesavealpha($im, true);

        $w = imagesx($im);
        $h = imagesy($im);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorat($im, $x, $y);
                $a = ($c >> 24) & 0x7F;
                if ($a === 127) {
                    continue;
                }
                $r = ($c >> 16) & 0xFF;
                $g = ($c >> 8) & 0xFF;
                $b = $c & 0xFF;
                $max = max($r, $g, $b);
                if ($max <= self::INK_MAX_LEVEL && $max - min($r, $g, $b) <= self::INK_MAX_CHROMA) {
                    // Keep the pixel's own coverage, so anti-aliased edges stay smooth.
                    imagesetpixel($im, $x, $y, imagecolorallocatealpha($im, 244, 246, 255, $a));
                }
            }
        }

        ob_start();
        imagepng($im, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        return $png !== '' ? $png : null;
    }
}
