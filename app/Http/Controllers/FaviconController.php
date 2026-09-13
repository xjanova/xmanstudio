<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The site icon, cut from whatever the admin uploaded on the Branding page.
 *
 * Browsers and Google kept showing a WordPress icon for this domain. /favicon.ico was Laravel's
 * empty placeholder (0 bytes), and the uploaded icon was linked as-is: 419×426, so not square —
 * Google only uses square favicons — and labelled image/x-icon while being a PNG. With nothing
 * valid to replace it, everyone kept the icon cached from the WordPress site that used to live here.
 *
 * Every size is cut SQUARE from the upload (padded, never stretched) and cached on disk per upload.
 * The layouts link them with a version tag, so a new upload replaces every cached copy — including
 * Cloudflare's, which caches /favicon.ico for hours.
 */
class FaviconController extends Controller
{
    /** Sizes served as /favicon-{size}.png. 180 is the apple-touch-icon. */
    public const PNG_SIZES = [16, 32, 48, 180, 192, 512];

    /** Sizes packed into /favicon.ico. */
    private const ICO_SIZES = [16, 32, 48];

    /** Bump when the rendering changes, so cached icons everywhere are replaced. */
    private const RENDER = 'r1';

    public function png(string $size): Response
    {
        $size = (int) $size;
        abort_unless(in_array($size, self::PNG_SIZES, true), 404);

        return $this->serve(fn (string $source) => $this->square($source, $size), 'image/png', "png-{$size}");
    }

    public function appleTouch(): Response
    {
        return $this->png('180');
    }

    public function ico(): Response
    {
        return $this->serve(function (string $source) {
            $images = [];
            foreach (self::ICO_SIZES as $size) {
                $png = $this->square($source, $size);
                if ($png === null) {
                    return null;
                }
                $images[$size] = $png;
            }

            return self::packIco($images);
        }, 'image/x-icon', 'ico');
    }

    /** Short tag that changes whenever the admin uploads a new icon; '' when there is none. */
    public static function version(): string
    {
        $upload = (string) Setting::getValue('site_favicon', '');

        return $upload === '' ? '' : substr(md5($upload . '|' . self::RENDER), 0, 8);
    }

    /**
     * @param  callable(string):(?string)  $make  bytes for this variant, from the uploaded file's path
     * @param  string  $variant  names the output in the disk cache ('ico', 'png-192', …)
     */
    private function serve(callable $make, string $type, string $variant): Response
    {
        $upload = (string) Setting::getValue('site_favicon', '');
        $disk = Storage::disk('public');
        abort_if($upload === '' || ! $disk->exists($upload), 404);
        $source = $disk->path($upload);

        $cache = Storage::disk('local');
        $key = 'favicons/' . md5($upload . '|' . filemtime($source) . '|' . self::RENDER) . '-' . $variant;
        if (! $cache->exists($key)) {
            $bytes = $make($source);
            if ($bytes === null) {
                // A format GD cannot read (SVG, ICO): hand over the upload itself rather than nothing.
                return response()->file($source, ['Cache-Control' => 'public, max-age=86400']);
            }
            $cache->put($key, $bytes);
        }

        return response()->file($cache->exists($key) ? $cache->path($key) : $source, [
            'Content-Type' => $type,
            // Versioned links make a long cache safe; the bare /favicon.ico has no version, so a day.
            'Cache-Control' => request()->query('v') ? 'public, max-age=2592000, immutable' : 'public, max-age=86400',
        ]);
    }

    /** The upload fitted into a transparent $size×$size square, as PNG bytes; null if GD can't read it. */
    private function square(string $source, int $size): ?string
    {
        try {
            $info = @getimagesize($source);
            $src = match ($info[2] ?? null) {
                IMAGETYPE_PNG => @imagecreatefrompng($source),
                IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
                IMAGETYPE_GIF => @imagecreatefromgif($source),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
                default => false,
            };
            if (! $src) {
                return null;
            }

            $w = imagesx($src);
            $h = imagesy($src);
            $scale = min($size / $w, $size / $h);
            $dw = max(1, (int) round($w * $scale));
            $dh = max(1, (int) round($h * $scale));

            $canvas = imagecreatetruecolor($size, $size);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            imagecopyresampled($canvas, $src, intdiv($size - $dw, 2), intdiv($size - $dh, 2), 0, 0, $dw, $dh, $w, $h);

            ob_start();
            imagepng($canvas, null, 9);

            return (string) ob_get_clean();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * A real .ico holding PNG-compressed images (the Vista+ ICO format every current browser and
     * Google read). GD cannot write ICO, but the container is a 6-byte header, one 16-byte entry
     * per image, then the images.
     *
     * @param  array<int,string>  $images  size => PNG bytes
     */
    public static function packIco(array $images): string
    {
        $header = pack('vvv', 0, 1, count($images));
        $entries = '';
        $data = '';
        $offset = 6 + 16 * count($images);
        foreach ($images as $size => $png) {
            $dim = $size >= 256 ? 0 : $size;    // 0 means 256 in the ICO format
            $entries .= pack('CCCCvvVV', $dim, $dim, 0, 0, 1, 32, strlen($png), $offset);
            $data .= $png;
            $offset += strlen($png);
        }

        return $header . $entries . $data;
    }
}
