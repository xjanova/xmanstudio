<?php

namespace App\Support;

/**
 * Makes Thai text safe to hand to DomPDF, which places every glyph by its default position and
 * ignores the font's GSUB/GPOS tables entirely — it has no shaping engine.
 *
 * Without shaping every Thai mark sits at its one default spot, so a tone mark over an upper vowel
 * (ใบแจ้งหนี้ งวดที่ ชื่อ น้ำ) is drawn at the same height as the vowel and the two collide into one
 * blob, and marks on a tall consonant (ป่า ฟ้า ฝั่ง) land on its ascender. A browser gets both right;
 * a PDF from DomPDF does not, which is why the invoice title read as a smudge.
 *
 * storage/fonts/Sarabun-PUA-*.ttf carry ready-positioned copies of those marks in the Private Use
 * Area (built by resources/fonts/build_thai_pua_font.py); this swaps them in by context — the same
 * trick Thai fonts used for renderers that could not shape.
 *
 * THE PUA LAYOUT HERE AND IN build_thai_pua_font.py MUST MATCH — change both or neither.
 *
 * The output is for drawing only. Copy-and-paste out of the finished PDF gives the PUA code points
 * for the clusters that were swapped, so shape as late as possible: on the rendered HTML, never on
 * anything that is stored, searched or compared.
 *
 * Adapted from NetWix's App\Support\Alerts\ThaiShaper, which does the same for GD.
 */
final class ThaiShaper
{
    /** Order matters: it is the index used in the PUA layout. */
    private const UPPER = ["\u{0E31}", "\u{0E34}", "\u{0E35}", "\u{0E36}", "\u{0E37}", "\u{0E47}", "\u{0E4D}"];

    private const TONES = ["\u{0E48}", "\u{0E49}", "\u{0E4A}", "\u{0E4B}", "\u{0E4C}"];

    private const LOWER = ["\u{0E38}", "\u{0E39}", "\u{0E3A}"];

    /** ป ฝ ฟ ฬ — the ascender sits where an upper mark would go. */
    private const TALL = ["\u{0E1B}", "\u{0E1D}", "\u{0E1F}", "\u{0E2C}"];

    private const SARA_AM = "\u{0E33}";

    private const NIKHAHIT = "\u{0E4D}";

    private const SARA_AA = "\u{0E32}";

    private const NARROW_BASE = 0xF700;

    private const STACK_BASE = 0xF710;

    private const STACK_TALL_BASE = 0xF740;

    /** ญ ฐ lose their descender when a lower vowel hangs under them. */
    private const DESCLESS = ["\u{0E0D}" => 0xF770, "\u{0E10}" => 0xF771];

    public static function shape(string $text): string
    {
        if (! preg_match('/[\x{0E00}-\x{0E7F}]/u', $text)) {
            return $text;
        }

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = '';
        $tall = false;      // is the current cluster's base a tall consonant?
        $upper = null;      // index of the upper vowel already placed in this cluster

        for ($i = 0, $n = count($chars); $i < $n; $i++) {
            $c = $chars[$i];

            $u = array_search($c, self::UPPER, true);
            if ($u !== false) {
                $upper = $u;
                $out .= $tall ? self::chr(self::NARROW_BASE + $u) : $c;

                continue;
            }

            $t = array_search($c, self::TONES, true);
            if ($t !== false) {
                // Tone + ำ: ำ is really ํ + า, and the tone belongs ON the ํ (น้ำ, ค่ำ).
                if (($chars[$i + 1] ?? null) === self::SARA_AM) {
                    $nik = 6;   // index of ํ in UPPER
                    $out .= ($tall ? self::chr(self::NARROW_BASE + $nik) : self::NIKHAHIT)
                        . self::stacked($t, $nik, $tall)
                        . self::SARA_AA;
                    $i++;
                    $upper = null;

                    continue;
                }
                $out .= $upper !== null
                    ? self::stacked($t, $upper, $tall)
                    : ($tall ? self::chr(self::NARROW_BASE + count(self::UPPER) + $t) : $c);

                continue;
            }

            if (in_array($c, self::LOWER, true)) {
                $out .= $c;     // below the base: no effect on what goes above it

                continue;
            }

            // Anything else starts a new cluster.
            $upper = null;
            $tall = in_array($c, self::TALL, true);
            $next = $chars[$i + 1] ?? null;
            $out .= isset(self::DESCLESS[$c]) && in_array($next, self::LOWER, true)
                ? self::chr(self::DESCLESS[$c])
                : $c;
        }

        return $out;
    }

    /**
     * Shape the Thai inside an HTML document, leaving the markup alone.
     *
     * Tags, attributes and CSS are ASCII, and shape() only ever rewrites characters in the Thai
     * block, so running it over the whole document cannot break the markup. Text inside <style> and
     * <script> is left as it is: a Thai string there would be a selector or a value, not something
     * anybody reads.
     */
    public static function shapeHtml(string $html): string
    {
        if (! preg_match('/[\x{0E00}-\x{0E7F}]/u', $html)) {
            return $html;
        }

        $parts = preg_split(
            '#(<(?:style|script)\b[^>]*>.*?</(?:style|script)>)#is',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        ) ?: [$html];

        $out = '';
        foreach ($parts as $part) {
            $out .= preg_match('#^<(?:style|script)\b#i', $part) === 1 ? $part : self::shape($part);
        }

        return $out;
    }

    private static function stacked(int $tone, int $upper, bool $tall): string
    {
        return self::chr(($tall ? self::STACK_TALL_BASE : self::STACK_BASE) + $tone * 8 + $upper);
    }

    private static function chr(int $code): string
    {
        return mb_chr($code, 'UTF-8');
    }
}
