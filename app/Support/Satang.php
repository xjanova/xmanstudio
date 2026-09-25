<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;

/**
 * เงินเป็นสตางค์จำนวนเต็ม ↔ บาททศนิยมสองตำแหน่ง — ไม่ผ่าน float ทุกทาง
 *
 * คอลัมน์เงินของกระเป๋าเป็น DECIMAL(12,2) ส่วนการคิดทุกอย่างทำบนสตางค์ (int)
 * แบบเดียวกับ GpuxMineEarningSettlementService::baht()/satang()
 */
final class Satang
{
    /** ยอดสูงสุดที่ DECIMAL(12,2) เก็บได้ เป็นสตางค์ */
    public const MAX = 999_999_999_999;

    /** สตางค์ → "1234.05" */
    public static function toBaht(int $satang): string
    {
        return (string) BigDecimal::ofUnscaledValue($satang, 2);
    }

    /** บาทจากคอลัมน์ decimal → สตางค์ ปัดครึ่งขึ้นที่ทศนิยมตำแหน่งที่สอง */
    public static function fromBaht(?string $baht): int
    {
        if ($baht === null || $baht === '') {
            return 0;
        }

        return BigDecimal::of($baht)->toScale(2, RoundingMode::HALF_UP)->getUnscaledValue()->toInt();
    }

    /**
     * ยอดที่ผู้ใช้พิมพ์มา → สตางค์ หรือ null ถ้าไม่ใช่จำนวนเงินที่ถูกต้อง
     *
     * รับ "1500", "1,500", "1500.5", "1500.50" — ไม่รับติดลบ ทศนิยมเกินสองตำแหน่ง
     * เลขวิทยาศาสตร์ ("1e3") หรือยอดที่คอลัมน์เก็บไม่ได้ ไม่ปัดเงียบ ๆ
     */
    public static function parseInput(mixed $input): ?int
    {
        if (! is_string($input) && ! is_int($input)) {
            return null;
        }

        $clean = str_replace([',', ' ', "\u{00A0}"], '', trim((string) $input));
        if (! preg_match('/^\d{1,10}(\.\d{1,2})?$/', $clean)) {
            return null;
        }

        try {
            $satang = BigDecimal::of($clean)->toScale(2)->getUnscaledValue()->toInt();
        } catch (MathException) {
            return null;
        }

        return $satang <= self::MAX ? $satang : null;
    }

    /** สำหรับแสดงผล: "฿1,234.05" */
    public static function format(int $satang): string
    {
        $baht = BigDecimal::ofUnscaledValue(abs($satang), 2);
        $whole = number_format((int) (string) $baht->getIntegralPart());

        return ($satang < 0 ? '−' : '') . '฿' . $whole . '.' . str_pad((string) $baht->getFractionalPart(), 2, '0', STR_PAD_LEFT);
    }
}
