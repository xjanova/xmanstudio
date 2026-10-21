<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Support\Facades\View;

/**
 * Every PDF this site prints, with its Thai marks in the right place.
 *
 * DomPDF has no shaping engine, so a tone mark over an upper vowel is drawn at the same height as
 * the vowel: ใบแจ้งหนี้ came out as a smudge and งวดที่ lost its tone entirely. The fix is a font
 * carrying ready-positioned marks plus ThaiShaper to swap them in — and it only works if EVERY
 * document goes through it, which is why the controllers call this instead of Pdf::loadView().
 *
 * Shaping happens on the finished HTML, as late as possible: the swapped-in code points are for
 * drawing, and nothing should store or compare them.
 */
class ThaiPdf
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function view(
        string $view,
        array $data = [],
        string $paper = 'a4',
        string $orientation = 'portrait',
    ): DomPdfWrapper {
        $html = ThaiShaper::shapeHtml(View::make($view, $data)->render());

        return Pdf::loadHTML($html)->setPaper($paper, $orientation);
    }
}
