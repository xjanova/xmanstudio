<?php

namespace App\Support\Quotation;

/**
 * How a quotation's totals are meant to be read.
 *
 * The builder used to do one thing: `$vat = $total * 0.07`, always, with no way
 * to say that a price already contained the tax or that we were not charging
 * it. Three modes, one calculator, and the mode stored on the document so an
 * old quotation still reads correctly after the rate or the policy changes.
 */
class VatMode
{
    /** Prices are before tax; the tax is added on top. */
    public const EXCLUSIVE = 'exclusive';

    /** The price IS the amount payable; the tax is extracted out of it. */
    public const INCLUSIVE = 'inclusive';

    /** Not registered, or an exempt service: no tax line at all. */
    public const NONE = 'none';

    public const DEFAULT_RATE = 7.00;

    /** @return array<int, string> */
    public static function all(): array
    {
        return [self::EXCLUSIVE, self::INCLUSIVE, self::NONE];
    }

    public static function normalise(?string $mode): string
    {
        return in_array($mode, self::all(), true) ? $mode : self::EXCLUSIVE;
    }

    /**
     * @return array{th: string, en: string, hint_th: string}
     */
    public static function label(string $mode): array
    {
        return match (self::normalise($mode)) {
            self::INCLUSIVE => [
                'th' => 'ราคารวม VAT แล้ว',
                'en' => 'VAT included',
                'hint_th' => 'ยอดที่เห็นคือยอดที่จ่ายจริง ระบบจะถอดภาษีออกมาแสดงให้',
            ],
            self::NONE => [
                'th' => 'ไม่คิด VAT',
                'en' => 'No VAT',
                'hint_th' => 'ไม่มีบรรทัดภาษีบนเอกสาร ใช้เมื่อยังไม่ได้จดทะเบียนหรือได้รับยกเว้น',
            ],
            default => [
                'th' => 'บวก VAT 7%',
                'en' => 'Add 7% VAT',
                'hint_th' => 'ราคาที่เลือกเป็นราคาก่อนภาษี แล้วบวกภาษีเพิ่มท้ายใบ',
            ],
        };
    }

    /**
     * Split an amount into base, tax and total.
     *
     * `$amount` is the figure after discounts and any rush fee — i.e. what the
     * old code called `$total` right before it multiplied by 0.07.
     *
     * The rounding matters more than it looks. Under INCLUSIVE the obvious
     * implementation rounds the base AND the tax separately, and they stop
     * summing to the amount the customer was quoted — one satang out, every
     * time, which is exactly the kind of drift that makes a ledger refuse to
     * balance. So the base is rounded once and the tax is taken as the
     * remainder, which makes `base + vat === total` true by construction in
     * every mode.
     *
     * @return array{base: float, vat: float, total: float, rate: float, mode: string}
     */
    public static function split(float $amount, string $mode, float $rate = self::DEFAULT_RATE): array
    {
        $mode = self::normalise($mode);
        $amount = round($amount, 2);

        if ($mode === self::NONE || $rate <= 0) {
            return ['base' => $amount, 'vat' => 0.0, 'total' => $amount, 'rate' => 0.0, 'mode' => $mode];
        }

        if ($mode === self::INCLUSIVE) {
            $total = $amount;
            $base = round($total / (1 + $rate / 100), 2);

            return ['base' => $base, 'vat' => round($total - $base, 2), 'total' => $total, 'rate' => $rate, 'mode' => $mode];
        }

        $base = $amount;
        $total = round($base * (1 + $rate / 100), 2);

        return ['base' => $base, 'vat' => round($total - $base, 2), 'total' => $total, 'rate' => $rate, 'mode' => $mode];
    }

    /**
     * Withholding tax the customer deducts when they pay us.
     *
     * It is computed on the pre-VAT base, never on the gross, and it does not
     * change what we invoice — only what arrives in the bank. Showing it on the
     * quotation stops the "why is the transfer short?" conversation later.
     */
    public static function withholding(float $base, float $pct): float
    {
        return $pct > 0 ? round($base * $pct / 100, 2) : 0.0;
    }

    /**
     * Thai baht in words, the line every Thai quotation carries under the total.
     *
     * Written out rather than pulled from a package: the only rule with any
     * subtlety is that a tens digit of 2 reads "ยี่" and a units digit of 1 in
     * any group beyond the first reads "เอ็ด".
     */
    public static function bahtText(float $amount): string
    {
        $amount = round($amount, 2);
        $negative = $amount < 0;
        $amount = abs($amount);

        $baht = (int) floor($amount);
        $satang = (int) round(($amount - $baht) * 100);

        // Rounding up to 100 satang has to roll into the baht, or the words
        // say "ศูนย์บาทหนึ่งร้อยสตางค์".
        if ($satang === 100) {
            $baht++;
            $satang = 0;
        }

        $text = ($baht === 0 && $satang === 0)
            ? 'ศูนย์บาทถ้วน'
            : self::readInteger($baht) . 'บาท' . ($satang > 0 ? self::readInteger($satang) . 'สตางค์' : 'ถ้วน');

        return ($negative ? 'ลบ' : '') . $text;
    }

    /**
     * Read a non-negative integer in Thai.
     */
    protected static function readInteger(int $number): string
    {
        if ($number === 0) {
            return 'ศูนย์';
        }

        $digits = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
        $places = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน'];

        // Thai counts in millions: anything above ล้าน is the same six-digit
        // pattern again with "ล้าน" appended.
        if ($number >= 1000000) {
            return self::readInteger((int) floor($number / 1000000)) . 'ล้าน'
                . (($rest = $number % 1000000) > 0 ? self::readInteger($rest) : '');
        }

        $out = '';
        $str = (string) $number;
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            $digit = (int) $str[$i];
            $place = $len - $i - 1;

            if ($digit === 0) {
                continue;
            }

            if ($place === 1) {
                // สิบ / ยี่สิบ / สามสิบ — never "หนึ่งสิบ" or "สองสิบ"
                $out .= match ($digit) {
                    1 => 'สิบ',
                    2 => 'ยี่สิบ',
                    default => $digits[$digit] . 'สิบ',
                };

                continue;
            }

            // A trailing 1 after any other digit is "เอ็ด": ยี่สิบเอ็ด, หนึ่งร้อยเอ็ด
            if ($place === 0 && $digit === 1 && $len > 1) {
                $out .= 'เอ็ด';

                continue;
            }

            $out .= $digits[$digit] . $places[$place];
        }

        return $out;
    }
}
