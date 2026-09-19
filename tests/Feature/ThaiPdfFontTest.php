<?php

namespace Tests\Feature;

use App\Support\FontFile;
use App\Support\ThaiShaper;
use FontLib\Font;
use Tests\TestCase;

/**
 * Thai marks in the printed documents.
 *
 * DomPDF places every glyph at its default position and ignores GSUB/GPOS, so a tone mark over an
 * upper vowel was drawn at the same height as the vowel: the invoice title ใบแจ้งหนี้ came out as a
 * smudge and งวดที่ lost its tone completely. The fix has two halves that only work together —
 * ThaiShaper swaps in Private Use Area code points, and storage/fonts/Sarabun-PUA-*.ttf carry the
 * pre-positioned glyphs behind them.
 *
 * The important test here is the one that walks every cluster the shaper can produce and demands
 * the font actually has that code point. Change the layout in the shaper or in
 * resources/fonts/build_thai_pua_font.py alone and this goes red instead of printing tofu.
 */
class ThaiPdfFontTest extends TestCase
{
    private const REGULAR = 'fonts/Sarabun-PUA-Regular.ttf';

    private const BOLD = 'fonts/Sarabun-PUA-Bold.ttf';

    /** Upper vowels, in the order the PUA layout indexes them. */
    private const UPPER = ["\u{0E31}", "\u{0E34}", "\u{0E35}", "\u{0E36}", "\u{0E37}", "\u{0E47}", "\u{0E4D}"];

    private const TONES = ["\u{0E48}", "\u{0E49}", "\u{0E4A}", "\u{0E4B}", "\u{0E4C}"];

    public function test_a_tone_over_a_vowel_becomes_one_ready_positioned_glyph(): void
    {
        // หนี้ = ห น ี ้ — the ี and the ้ used to be drawn at the same height.
        $shaped = ThaiShaper::shape('ใบแจ้งหนี้');

        $this->assertStringNotContainsString("\u{0E35}\u{0E49}", $shaped, 'the vowel + tone pair must not survive as two marks');
        $this->assertMatchesRegularExpression('/[\x{F700}-\x{F771}]/u', $shaped);

        // What is left of the word is untouched: only the colliding pair is swapped.
        $this->assertStringStartsWith('ใบแจ้งหน', mb_substr($shaped, 0, 8));
    }

    public function test_a_mark_on_a_tall_consonant_is_pulled_off_its_ascender(): void
    {
        foreach (['ป่า', 'ฟ้า', 'ฝั่ง', 'ปิด'] as $word) {
            $this->assertMatchesRegularExpression(
                '/[\x{F700}-\x{F771}]/u',
                ThaiShaper::shape($word),
                "{$word} has a mark sitting on a tall consonant"
            );
        }
    }

    public function test_sara_am_after_a_tone_is_split_so_the_tone_lands_on_the_circle(): void
    {
        // น้ำ is น + ้ + ำ, and ำ is really ํ + า: the tone belongs on the ํ, not beside it.
        $shaped = ThaiShaper::shape('น้ำ');

        $this->assertStringNotContainsString("\u{0E33}", $shaped, 'ำ must be split into ํ + า');
        $this->assertStringEndsWith("\u{0E32}", $shaped);
    }

    public function test_a_descender_is_dropped_when_a_vowel_hangs_under_it(): void
    {
        $this->assertMatchesRegularExpression('/^[\x{F770}\x{F771}]/u', ThaiShaper::shape('ญุ'));
        $this->assertMatchesRegularExpression('/^[\x{F770}\x{F771}]/u', ThaiShaper::shape('ฐุ'));

        // No lower vowel, no reason to change the letter.
        $this->assertSame('ญ', ThaiShaper::shape('ญ'));
    }

    public function test_text_with_nothing_to_fix_comes_back_untouched(): void
    {
        foreach (['INV-20260919-AB12', 'Quotation 385,200.00', 'ผู้มีอำนาจลงนาม', 'กรุงเทพมหานคร'] as $text) {
            $this->assertSame($text, ThaiShaper::shape($text), $text . ' has no colliding marks');
        }
    }

    public function test_shaping_a_document_leaves_the_markup_alone(): void
    {
        $html = '<div class="doc-title" data-x="ที่">ใบแจ้งหนี้</div><style>.a{content:"ที่";}</style>';
        $shaped = ThaiShaper::shapeHtml($html);

        $this->assertStringContainsString('<div class="doc-title"', $shaped);
        $this->assertStringContainsString('</div>', $shaped);
        // A stylesheet is not prose — leave every byte of it as it was.
        $this->assertStringContainsString('<style>.a{content:"ที่";}</style>', $shaped);
        $this->assertStringNotContainsString('>ใบแจ้งหนี้<', $shaped, 'the visible text should have been shaped');
    }

    public function test_the_fonts_the_documents_point_at_are_real_fonts(): void
    {
        foreach ([self::REGULAR, self::BOLD] as $font) {
            $path = storage_path($font);
            $this->assertFileExists($path);
            $this->assertTrue(FontFile::isReal($path), $font . ' must be a font, not a saved web page');
        }
    }

    /**
     * The one that matters: every code point the shaper can emit has to exist in both weights, or
     * the document prints an empty box where a tone mark belongs.
     */
    public function test_every_cluster_the_shaper_produces_exists_in_both_fonts(): void
    {
        $needed = [];

        foreach (["\u{0E19}", "\u{0E1B}"] as $base) {          // น (normal) and ป (tall)
            foreach (self::UPPER as $vowel) {
                $needed[] = ThaiShaper::shape($base . $vowel);
                foreach (self::TONES as $tone) {
                    $needed[] = ThaiShaper::shape($base . $vowel . $tone);
                }
            }
            foreach (self::TONES as $tone) {
                $needed[] = ThaiShaper::shape($base . $tone);
                $needed[] = ThaiShaper::shape($base . $tone . "\u{0E33}");   // tone + ำ
            }
        }
        $needed[] = ThaiShaper::shape("\u{0E0D}\u{0E38}");     // ญุ
        $needed[] = ThaiShaper::shape("\u{0E10}\u{0E38}");     // ฐุ

        $points = [];
        foreach ($needed as $text) {
            foreach (preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) as $char) {
                $code = mb_ord($char, 'UTF-8');
                if ($code >= 0xF700 && $code <= 0xF7FF) {
                    $points[$code] = true;
                }
            }
        }

        $this->assertGreaterThanOrEqual(80, count($points), 'the shaper should be producing the whole PUA layout');

        foreach ([self::REGULAR, self::BOLD] as $file) {
            $font = Font::load(storage_path($file));
            $font->parse();
            $cmap = $font->getUnicodeCharMap();

            $missing = array_values(array_filter(
                array_keys($points),
                fn (int $code) => ! isset($cmap[$code])
            ));

            $this->assertSame([], array_map(fn ($c) => sprintf('U+%04X', $c), $missing),
                $file . ' is missing glyphs the shaper emits — rebuild it with resources/fonts/build_thai_pua_font.py');
        }
    }

    public function test_both_document_templates_use_the_shaped_font(): void
    {
        foreach (['quotation/pdf', 'invoice/pdf'] as $view) {
            $source = file_get_contents(resource_path('views/' . $view . '.blade.php'));

            $this->assertStringContainsString('Sarabun-PUA-Regular.ttf', $source, $view . ' must embed the PUA font');
            $this->assertStringContainsString('Sarabun-PUA-Bold.ttf', $source);
        }
    }

    public function test_no_controller_renders_a_pdf_around_the_shaper(): void
    {
        // Pdf::loadView() skips ThaiShaper, and the mistake is invisible until a customer opens the
        // file, so the whole app has exactly one way to print.
        foreach (glob(app_path('Http/Controllers/*.php')) + glob(app_path('Http/Controllers/*/*.php')) as $file) {
            $this->assertStringNotContainsString(
                'Pdf::loadView',
                file_get_contents($file),
                basename($file) . ' must print through App\Support\ThaiPdf'
            );
        }
    }
}
