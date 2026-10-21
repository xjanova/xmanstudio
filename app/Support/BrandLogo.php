<?php

namespace App\Support;

use App\Http\Controllers\FaviconController;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * The site logo, in the variant each background needs. Every page, e-mail and document asks
 * here instead of reading the settings itself, so a new upload on the Branding page reaches
 * every spot at once.
 *
 *   url() / path()          for light grounds — Setting `site_logo` (dark lettering)
 *   darkUrl() / darkPath()  for dark grounds  — Setting `site_logo_dark` (light lettering)
 *   pngPath()               the same, as PNG, for e-mail clients and DomPDF (no WebP there)
 *   markUrl()               the square emblem, for schema.org and app icons
 *
 * Paths are relative to the public disk. Uploads are converted to WebP, which Outlook cannot show
 * and DomPDF loses the transparency of, hence the PNG copies.
 *
 * Without a dark upload, the light logo's black ink is turned white in a copy kept beside it,
 * named after the original's path and modified time, so a new upload simply gets a new copy;
 * the coloured parts are left as they are. Anything that cannot be recoloured (an SVG, a JPEG,
 * a missing file, no GD) falls back to the light logo.
 */
class BrandLogo
{
    /** A pixel this dark and this grey is "ink": it becomes white. */
    private const INK_MAX_LEVEL = 110;

    private const INK_MAX_CHROMA = 48;

    /** E-mail shows the logo 260 px wide and documents smaller: 640 px is sharp at 2x and light. */
    private const PNG_MAX_WIDTH = 640;

    public static function path(): ?string
    {
        return Setting::getValue('site_logo') ?: null;
    }

    public static function url(): ?string
    {
        return self::asUrl(self::path());
    }

    public static function darkPath(): ?string
    {
        $dark = Setting::getValue('site_logo_dark');
        if ($dark && Storage::disk('public')->exists($dark)) {
            return $dark;
        }

        return self::recolouredCopy();
    }

    public static function darkUrl(): ?string
    {
        return self::asUrl(self::darkPath());
    }

    /**
     * The light or dark logo as a PNG on the public disk: the file itself when it already is one,
     * otherwise a converted copy beside it. Falls back to the file as it is when GD cannot read it.
     */
    public static function pngPath(bool $dark = false): ?string
    {
        $path = $dark ? self::darkPath() : self::path();
        if (! $path) {
            return null;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $disk = Storage::disk('public');
        if ($ext === 'png' || ! in_array($ext, ['webp', 'jpg', 'jpeg'], true) || ! $disk->exists($path)) {
            return $path;
        }

        $copy = 'branding/png-' . substr(md5($path . '|' . $disk->lastModified($path) . '|' . self::PNG_MAX_WIDTH), 0, 16) . '.png';
        if ($disk->exists($copy)) {
            return $copy;
        }

        $png = self::reencode($disk->path($path), $ext);
        if ($png === null || ! $disk->put($copy, $png)) {
            return $path;
        }

        return $copy;
    }

    /** Absolute file path of pngPath(), for DomPDF and GD; null when the file is not there. */
    public static function pngFile(bool $dark = false): ?string
    {
        $path = self::pngPath($dark);
        if (! $path) {
            return null;
        }
        $file = Storage::disk('public')->path($path);

        return is_file($file) && is_readable($file) ? $file : null;
    }

    /**
     * The square emblem (the favicon upload, served at 512 px) for schema.org's Organization logo
     * and anything else that wants an icon rather than a wordmark. Falls back to the light logo.
     */
    public static function markUrl(): ?string
    {
        return FaviconController::version() !== ''
            ? route('favicon.png', 512) . '?v=' . FaviconController::version()
            : self::url();
    }

    private static function asUrl(?string $path): ?string
    {
        return $path ? asset('storage/' . $path) : null;
    }

    private static function recolouredCopy(): ?string
    {
        $path = self::path();
        if (! $path) {
            return null;
        }

        $disk = Storage::disk('public');
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($ext, ['png', 'webp'], true) || ! function_exists('imagecreatefrompng') || ! $disk->exists($path)) {
            return $path;
        }

        $copy = 'branding/on-dark-' . substr(md5($path . '|' . $disk->lastModified($path)), 0, 16) . '.png';
        if (! $disk->exists($copy)) {
            // A copy that could not be made is not tried again on every page view:
            // walking every pixel is the slow part.
            $failed = 'brand-logo:no-dark-copy:' . $copy;
            if (Cache::has($failed)) {
                return $path;
            }
            $png = self::recolour($disk->path($path), $ext);
            if ($png === null || ! $disk->put($copy, $png)) {
                Cache::put($failed, true, now()->addHour());
                Log::warning('BrandLogo: no dark copy of the site logo', ['logo' => $path, 'readable' => $png !== null]);

                return $path;
            }
        }

        return $copy;
    }

    /**
     * @return string|null PNG bytes, or null when the image cannot be read
     */
    public static function recolour(string $file, string $ext): ?string
    {
        $im = self::open($file, $ext);
        if (! $im) {
            return null;
        }

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

        return self::toPng($im);
    }

    /** @return string|null PNG bytes of a WebP/JPEG file, no wider than PNG_MAX_WIDTH, transparency kept */
    private static function reencode(string $file, string $ext): ?string
    {
        $im = self::open($file, $ext);
        if (! $im) {
            return null;
        }

        if (imagesx($im) > self::PNG_MAX_WIDTH) {
            $scaled = imagescale($im, self::PNG_MAX_WIDTH, -1, IMG_BICUBIC);
            imagedestroy($im);
            if (! $scaled) {
                return null;
            }
            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
            $im = $scaled;
        }

        return self::toPng($im);
    }

    private static function open(string $file, string $ext): ?\GdImage
    {
        try {
            $im = match ($ext) {
                'webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file) : false,
                'jpg', 'jpeg' => @imagecreatefromjpeg($file),
                default => @imagecreatefrompng($file),
            };
        } catch (\Throwable) {
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

        return $im;
    }

    private static function toPng(\GdImage $im): ?string
    {
        ob_start();
        imagepng($im, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        return $png !== '' ? $png : null;
    }
}
