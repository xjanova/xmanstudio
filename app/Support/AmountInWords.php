<?php

namespace App\Support;

/**
 * A baht amount written out in English, for the English half of a bilingual document
 * ("One Thousand Seventy Baht Only"). The Thai half is VatMode::bahtText().
 *
 * Written by hand rather than with NumberFormatter's spell-out: that needs the intl extension,
 * which a host is free not to have, and a receipt must print everywhere.
 */
class AmountInWords
{
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen',
    ];

    private const TENS = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    private const SCALES = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

    public static function baht(float $amount): string
    {
        $amount = round($amount, 2);
        $negative = $amount < 0;
        $amount = abs($amount);

        $baht = (int) floor($amount);
        $satang = (int) round(($amount - $baht) * 100);

        // 99.995 rounds to 100 satang, which is one more baht, not "100 Satang".
        if ($satang === 100) {
            $baht++;
            $satang = 0;
        }

        $text = self::integer($baht) . ' Baht';
        $text .= $satang > 0 ? ' and ' . self::integer($satang) . ' Satang' : ' Only';

        return ($negative ? 'Minus ' : '') . $text;
    }

    public static function integer(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $parts = [];
        $scale = 0;

        while ($number > 0 && $scale < count(self::SCALES)) {
            $chunk = $number % 1000;
            if ($chunk > 0) {
                array_unshift($parts, trim(self::belowThousand($chunk) . ' ' . self::SCALES[$scale]));
            }
            $number = intdiv($number, 1000);
            $scale++;
        }

        return implode(' ', $parts);
    }

    private static function belowThousand(int $number): string
    {
        $words = [];

        if ($number >= 100) {
            $words[] = self::ONES[intdiv($number, 100)] . ' Hundred';
            $number %= 100;
        }

        if ($number >= 20) {
            $words[] = self::TENS[intdiv($number, 10)] . ($number % 10 ? '-' . self::ONES[$number % 10] : '');
        } elseif ($number > 0) {
            $words[] = self::ONES[$number];
        }

        return implode(' ', $words);
    }
}
