<?php

namespace Tests\Feature;

use App\Http\Controllers\FaviconController;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * The site icon: browsers and Google showed a WordPress icon because /favicon.ico was an empty
 * placeholder and the uploaded icon (419×426) was not square. Every served size must be square,
 * padded rather than stretched, and the .ico must be a real ICO.
 *
 * No database — the upload is seeded through Setting's own cache — and no files outside fakes.
 */
class FaviconTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    /** Put a solid-colour upload on the public disk and point site_favicon at it. */
    private function upload(int $w, int $h, string $path = 'branding/icon.png'): void
    {
        $img = imagecreatetruecolor($w, $h);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 30, 90));
        ob_start();
        imagepng($img);
        Storage::disk('public')->put($path, (string) ob_get_clean());
        $this->setFavicon($path);
    }

    private function setFavicon(string $value): void
    {
        Cache::put('setting.site_favicon', new Setting(['key' => 'site_favicon', 'value' => $value]), 3600);
    }

    private static function body(BinaryFileResponse $response): string
    {
        return (string) file_get_contents($response->getFile()->getPathname());
    }

    public function test_every_png_size_is_square_and_padded_not_stretched(): void
    {
        $this->upload(419, 426);

        foreach ([16, 32, 192, 512] as $size) {
            $png = self::body(app(FaviconController::class)->png((string) $size));
            $img = imagecreatefromstring($png);

            $this->assertSame([$size, $size], [imagesx($img), imagesy($img)]);
            $this->assertSame(0, (imagecolorat($img, intdiv($size, 2), intdiv($size, 2)) >> 24) & 0x7F, "{$size}px: centre should be the icon");
            if ($size >= 192) {
                // 419 wide in a square: a transparent bar each side (too thin to exist below ~100px).
                $this->assertSame(127, (imagecolorat($img, 0, intdiv($size, 2)) >> 24) & 0x7F, "{$size}px: left edge should be padding");
            }
        }
    }

    public function test_favicon_ico_is_a_real_ico_holding_three_sizes(): void
    {
        $this->upload(419, 426);

        $response = app(FaviconController::class)->ico();
        $ico = self::body($response);

        $this->assertSame('image/x-icon', $response->headers->get('Content-Type'));
        $this->assertSame(['reserved' => 0, 'type' => 1, 'count' => 3], unpack('vreserved/vtype/vcount', $ico));
        foreach ([16, 32, 48] as $i => $size) {
            $entry = unpack('Cw/Ch/Ccolors/Creserved/vplanes/vbits/Vbytes/Voffset', substr($ico, 6 + 16 * $i, 16));
            $this->assertSame([$size, $size], [$entry['w'], $entry['h']]);
            $this->assertSame("\x89PNG", substr($ico, $entry['offset'], 4), "the {$size}px image is PNG data at its offset");
        }
    }

    public function test_the_layouts_link_versioned_icons_that_change_with_the_upload(): void
    {
        $this->upload(64, 64, 'branding/one.png');
        $first = view('partials.favicon')->render();
        $this->assertStringContainsString('/favicon.ico?v=' . FaviconController::version(), $first);
        $this->assertStringContainsString('rel="apple-touch-icon"', $first);

        $this->upload(64, 64, 'branding/two.png');
        $this->assertStringNotContainsString(FaviconController::version(), $first, 'a new upload must change the version tag');
    }

    public function test_without_an_upload_there_is_no_icon_only_the_layouts_own_fallback(): void
    {
        $this->setFavicon('');

        $this->assertSame('', FaviconController::version());
        $this->assertStringNotContainsString('favicon.ico', view('partials.favicon')->render());
        $this->assertStringContainsString('/images/xdreamer/logo.png', view('partials.favicon', ['fallback' => '/images/xdreamer/logo.png'])->render());

        try {
            app(FaviconController::class)->ico();
            $this->fail('no upload should be a 404');
        } catch (HttpException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }

    public function test_the_routes_exist(): void
    {
        $this->assertStringEndsWith('/favicon.ico', route('favicon.ico'));
        $this->assertStringEndsWith('/favicon-192.png', route('favicon.png', 192));
        $this->assertStringEndsWith('/apple-touch-icon.png', route('favicon.apple'));
    }
}
