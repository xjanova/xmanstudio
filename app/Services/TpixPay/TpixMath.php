<?php

namespace App\Services\TpixPay;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;

/**
 * Exact decimal math for TPIX amounts — no floats anywhere money is computed or compared.
 *
 * brick/math (a dependency of laravel/framework itself) rather than calling bcmath directly: it uses
 * bcmath or GMP when the server has them and its own exact code when it has neither, so a missing
 * PHP extension on the server cannot take payments down or round an amount.
 */
final class TpixMath
{
    public const DECIMALS = 18;

    /** A decimal string (or int/float from a form or a DB driver) as an exact decimal. */
    public static function dec(string|int|float|BigDecimal $value): BigDecimal
    {
        if ($value instanceof BigDecimal) {
            return $value;
        }

        // A float only ever arrives here from display paths (a price column read by SQLite);
        // fixing it to a plain string first keeps "1.0E-5" style notation out.
        if (is_float($value)) {
            $value = number_format($value, 8, '.', '');
        }

        return BigDecimal::of(trim((string) $value) === '' ? '0' : trim((string) $value));
    }

    /** THB as the 2-decimal string the database stores (half-up, like the order totals). */
    public static function thb(string|int|float $value): string
    {
        return (string) self::dec($value)->toScale(2, RoundingMode::HalfUp);
    }

    /** TPIX (decimal) → wei (integer string). Anything below 1 wei is cut, never rounded up into a different amount. */
    public static function toWei(string|BigDecimal $tpix): string
    {
        return (string) self::dec($tpix)->withPointMovedRight(self::DECIMALS)->toScale(0, RoundingMode::Down)->toBigInteger();
    }

    /** wei (integer string) → TPIX with all 18 decimals. */
    public static function fromWei(string $wei): string
    {
        return (string) BigDecimal::ofUnscaledValue(BigInteger::of($wei), self::DECIMALS);
    }

    /** A 0x hex quantity (a transaction's value) as an integer string, however large. */
    public static function hexToDecimal(string $hex): string
    {
        $digits = ltrim(substr($hex, 2), '0');

        return $digits === '' ? '0' : (string) BigInteger::fromBase($digits, 16);
    }

    /** wei as the 0x hex quantity eth_sendTransaction expects. */
    public static function weiToHex(string $wei): string
    {
        return '0x' . BigInteger::of($wei)->toBase(16);
    }

    public static function equal(string $a, string $b): bool
    {
        return BigInteger::of($a)->isEqualTo(BigInteger::of($b));
    }

    /**
     * A decimal for people: thousands separators, $scale decimals (half-up), never through float.
     */
    public static function format(string|BigDecimal $value, int $scale = 2): string
    {
        $fixed = (string) self::dec($value)->toScale($scale, RoundingMode::HalfUp);
        $negative = str_starts_with($fixed, '-');
        $fixed = ltrim($fixed, '-');
        [$int, $frac] = array_pad(explode('.', $fixed, 2), 2, '');
        $int = strrev(implode(',', str_split(strrev($int), 3)));

        return ($negative ? '-' : '') . $int . ($frac !== '' ? '.' . $frac : '');
    }

    /** A TPIX amount for a price tag: 2 decimals from 1 TPIX up, more below so it never reads 0.00. */
    public static function formatTpix(string|BigDecimal $value): string
    {
        $dec = self::dec($value);

        return self::format($dec, $dec->compareTo(1) >= 0 ? 2 : 6);
    }
}
