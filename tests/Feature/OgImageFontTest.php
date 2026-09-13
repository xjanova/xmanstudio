<?php

namespace Tests\Feature;

use App\Http\Controllers\OgImageController;
use App\Support\FontFile;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The bundled fonts must be fonts.
 *
 * storage/fonts/Sarabun-*.ttf were saved GitHub "Page not found" pages for months and nothing
 * noticed: every check asked only whether the file existed, GD and dompdf fell back quietly, and
 * Thai never rendered — OG images drew their title in a 9px bitmap font (Thai as mojibake) and
 * quotation PDFs fell back to DejaVu Sans, which has no Thai glyphs.
 *
 * No database: this must run anywhere, including against a developer's local sqlite file.
 */
class OgImageFontTest extends TestCase
{
    public function test_every_bundled_font_file_is_a_real_font(): void
    {
        $fonts = array_merge(glob(storage_path('fonts/*.ttf')) ?: [], glob(storage_path('fonts/*.otf')) ?: []);
        $this->assertNotEmpty($fonts, 'storage/fonts should ship the Sarabun fonts');

        foreach ($fonts as $path) {
            $this->assertTrue(
                FontFile::isReal($path),
                basename($path) . ' is not a font (starts with 0x' . bin2hex((string) file_get_contents($path, false, null, 0, 8)) . ')',
            );
        }
    }

    public function test_the_og_image_fonts_resolve_to_sarabun_and_measure_thai(): void
    {
        if (! function_exists('imagettfbbox')) {
            $this->markTestSkipped('GD is built without FreeType here.');
        }
        $resolve = new ReflectionMethod(OgImageController::class, 'resolveFont');

        foreach (['Sarabun-Bold.ttf' => 'DejaVuSans-Bold.ttf', 'Sarabun-Regular.ttf' => 'DejaVuSans.ttf'] as $name => $fallback) {
            $font = $resolve->invoke(new OgImageController, $name, $fallback);

            $this->assertNotNull($font, "no usable font found for {$name}");
            $this->assertStringEndsWith($name, str_replace('\\', '/', $font), 'the bundled Sarabun must win over the Latin-only DejaVu fallback');
            $box = imagettfbbox(24, 0, $font, 'ใบเสนอราคา XMAN Studio');
            $this->assertIsArray($box, "GD could not read {$font}");
            $this->assertGreaterThan(200, $box[2] - $box[0], 'Thai text must be drawn at its real width');
        }
    }

    public function test_a_saved_web_page_is_not_mistaken_for_a_font(): void
    {
        $fake = sys_get_temp_dir() . '/not-a-font-' . bin2hex(random_bytes(4)) . '.ttf';
        file_put_contents($fake, "\n\n\n\n<!DOCTYPE html>\n<title>Page not found · GitHub · GitHub</title>");

        try {
            $this->assertFalse(FontFile::isReal($fake));
            $this->assertNull(FontFile::firstReal([$fake, null, '/no/such/font.ttf']));
        } finally {
            @unlink($fake);
        }
    }
}
