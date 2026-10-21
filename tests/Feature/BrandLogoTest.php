<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\BrandLogo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Every page asks BrandLogo for the logo its background needs. The dark variant is the upload made
 * for dark grounds; without one, a copy of the light logo with its dark, colourless "ink" turned
 * white and everything else — colours, transparency, anti-aliased edges — as it was. E-mail and
 * PDF get PNG copies of the WebP uploads.
 *
 * No database: the setting is seeded through Setting's own cache, files live on a fake disk.
 */
class BrandLogoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->setSetting('site_logo_dark', null);
        $this->setSetting('site_favicon', null);
    }

    private function setSetting(string $key, ?string $value): void
    {
        Cache::put("setting.{$key}", new Setting(['key' => $key, 'value' => $value]), 3600);
    }

    private function setLogo(?string $path): void
    {
        $this->setSetting('site_logo', $path);
    }

    /** A 4×1 logo: black ink, half-covered black ink, a saturated blue, and nothing. */
    private function uploadLogo(string $path = 'branding/logo.png'): void
    {
        $img = imagecreatetruecolor(4, 1);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagesetpixel($img, 0, 0, imagecolorallocatealpha($img, 20, 20, 24, 0));
        imagesetpixel($img, 1, 0, imagecolorallocatealpha($img, 0, 0, 0, 64));
        imagesetpixel($img, 2, 0, imagecolorallocatealpha($img, 30, 90, 230, 0));
        imagesetpixel($img, 3, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        ob_start();
        imagepng($img);
        Storage::disk('public')->put($path, (string) ob_get_clean());
        $this->setLogo($path);
    }

    /** @return array{0: int, 1: int, 2: int, 3: int} r, g, b, GD alpha (127 = transparent) */
    private static function pixel(\GdImage $img, int $x): array
    {
        $c = imagecolorat($img, $x, 0);

        return [($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF, ($c >> 24) & 0x7F];
    }

    public function test_the_dark_copy_turns_the_black_ink_white_and_keeps_the_rest(): void
    {
        $this->uploadLogo();

        $url = BrandLogo::darkUrl();

        $this->assertMatchesRegularExpression('#/storage/branding/on-dark-[0-9a-f]{16}\.png$#', $url);
        $copy = 'branding/' . basename($url);
        Storage::disk('public')->assertExists($copy);
        $img = imagecreatefromstring(Storage::disk('public')->get($copy));

        $this->assertSame([244, 246, 255, 0], self::pixel($img, 0), 'ink becomes white');
        $this->assertSame([244, 246, 255, 64], self::pixel($img, 1), 'an anti-aliased edge keeps its coverage');
        $this->assertSame([30, 90, 230, 0], self::pixel($img, 2), 'colour is left alone');
        $this->assertSame(127, self::pixel($img, 3)[3], 'nothing stays nothing');

        $original = imagecreatefromstring(Storage::disk('public')->get('branding/logo.png'));
        $this->assertSame([20, 20, 24, 0], self::pixel($original, 0), 'the upload itself is untouched');
    }

    public function test_the_copy_is_made_once_and_a_new_upload_gets_its_own(): void
    {
        $this->uploadLogo();
        $first = BrandLogo::darkUrl();

        $this->assertSame($first, BrandLogo::darkUrl());

        $this->uploadLogo('branding/logo-2.png');
        $this->assertNotSame($first, BrandLogo::darkUrl());
    }

    public function test_what_cannot_be_recoloured_is_shown_as_uploaded(): void
    {
        $this->setLogo(null);
        $this->assertNull(BrandLogo::darkUrl());

        Storage::disk('public')->put('branding/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        $this->setLogo('branding/logo.svg');
        $this->assertSame(asset('storage/branding/logo.svg'), BrandLogo::darkUrl());

        $this->setLogo('branding/missing.png');
        $this->assertSame(asset('storage/branding/missing.png'), BrandLogo::darkUrl());

        Storage::disk('public')->put('branding/broken.png', 'not a png');
        $this->setLogo('branding/broken.png');
        $this->assertSame(asset('storage/branding/broken.png'), BrandLogo::darkUrl());
    }

    public function test_an_uploaded_dark_logo_wins_over_the_recoloured_copy(): void
    {
        $this->uploadLogo();
        Storage::disk('public')->put('branding/logo-dark.webp', 'webp bytes');
        $this->setSetting('site_logo_dark', 'branding/logo-dark.webp');

        $this->assertSame(asset('storage/branding/logo-dark.webp'), BrandLogo::darkUrl());
        $this->assertSame(asset('storage/branding/logo.png'), BrandLogo::url(), 'light grounds keep the light logo');

        // Its file gone (a restore without the disk), the recoloured copy steps in again.
        Storage::disk('public')->delete('branding/logo-dark.webp');
        $this->assertMatchesRegularExpression('#/storage/branding/on-dark-[0-9a-f]{16}\.png$#', BrandLogo::darkUrl());
    }

    public function test_mail_and_pdf_get_a_png_copy_of_a_webp_logo_with_its_transparency(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('GD without WebP');
        }
        $img = imagecreatetruecolor(2, 1);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagesetpixel($img, 0, 0, imagecolorallocatealpha($img, 255, 255, 255, 0));
        imagesetpixel($img, 1, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
        ob_start();
        imagewebp($img, null, IMG_WEBP_LOSSLESS);
        Storage::disk('public')->put('branding/logo-dark.webp', (string) ob_get_clean());
        $this->uploadLogo();
        $this->setSetting('site_logo_dark', 'branding/logo-dark.webp');

        $png = BrandLogo::pngPath(dark: true);

        $this->assertMatchesRegularExpression('#^branding/png-[0-9a-f]{16}\.png$#', $png);
        $copy = imagecreatefromstring(Storage::disk('public')->get($png));
        $this->assertSame([255, 255, 255, 0], self::pixel($copy, 0));
        $this->assertSame(127, self::pixel($copy, 1)[3], 'the transparent background stays transparent');
        $this->assertSame($png, BrandLogo::pngPath(dark: true), 'made once');
        $this->assertSame(Storage::disk('public')->path($png), BrandLogo::pngFile(dark: true));

        $this->assertSame('branding/logo.png', BrandLogo::pngPath(), 'a PNG is used as it is');
    }

    public function test_the_square_mark_is_the_favicon_and_falls_back_to_the_logo(): void
    {
        $this->uploadLogo();
        $this->assertSame(BrandLogo::url(), BrandLogo::markUrl());

        $this->setSetting('site_favicon', 'branding/mark.png');
        $this->assertStringContainsString('/favicon-512.png?v=', BrandLogo::markUrl());
    }
}
