<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\BrandLogo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The 3D home shows the uploaded logo on near-black space, where its black wordmark
 * disappeared. BrandLogo keeps a copy with the dark, colourless "ink" turned white and
 * everything else — colours, transparency, anti-aliased edges — as it was.
 *
 * No database: the setting is seeded through Setting's own cache, files live on a fake disk.
 */
class BrandLogoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function setLogo(?string $path): void
    {
        Cache::put('setting.site_logo', new Setting(['key' => 'site_logo', 'value' => $path]), 3600);
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
}
