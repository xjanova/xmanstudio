<?php

namespace Tests\Feature;

use App\Support\Quotation\VatMode;
use Tests\TestCase;

/**
 * The totals block is what an accountant reads, so the arithmetic is pinned
 * here rather than trusted to the view.
 */
class QuotationVatTest extends TestCase
{
    public function test_exclusive_adds_the_tax_on_top(): void
    {
        $r = VatMode::split(190000, VatMode::EXCLUSIVE);

        $this->assertSame(190000.00, $r['base']);
        $this->assertSame(13300.00, $r['vat']);
        $this->assertSame(203300.00, $r['total']);
    }

    public function test_inclusive_extracts_the_tax_out_of_the_price(): void
    {
        $r = VatMode::split(190000, VatMode::INCLUSIVE);

        $this->assertSame(177570.09, $r['base']);
        $this->assertSame(12429.91, $r['vat']);
        $this->assertSame(190000.00, $r['total'], 'the customer still pays the quoted figure');
    }

    public function test_none_shows_no_tax_at_all(): void
    {
        $r = VatMode::split(190000, VatMode::NONE);

        $this->assertSame(0.0, $r['vat']);
        $this->assertSame(0.0, $r['rate']);
        $this->assertSame(190000.00, $r['total']);
    }

    /**
     * The invariant the whole class exists for.
     *
     * Rounding base and tax independently leaves them a satang short of the
     * total on plenty of ordinary figures, and a ledger built on that will not
     * balance. Taking the tax as the remainder makes it true by construction.
     */
    public function test_base_plus_vat_always_equals_the_total(): void
    {
        foreach ([1, 99.99, 1234.56, 190000, 203300, 7, 33.33, 1000000, 0.07, 45999.95] as $amount) {
            foreach (VatMode::all() as $mode) {
                $r = VatMode::split((float) $amount, $mode);
                $this->assertSame(
                    round($r['total'], 2),
                    round($r['base'] + $r['vat'], 2),
                    "base + vat != total for {$amount} in {$mode}"
                );
            }
        }
    }

    public function test_an_unknown_mode_falls_back_to_adding_the_tax(): void
    {
        // Never silently drop the tax because a bad value reached the column.
        $this->assertSame(VatMode::EXCLUSIVE, VatMode::normalise('nonsense'));
        $this->assertSame(VatMode::EXCLUSIVE, VatMode::normalise(null));
        $this->assertSame(13300.00, VatMode::split(190000, 'nonsense')['vat']);
    }

    public function test_withholding_is_taken_on_the_base_not_the_gross(): void
    {
        // 3% of 190,000 = 5,700 — not 3% of the 203,300 gross.
        $this->assertSame(5700.00, VatMode::withholding(190000, 3));
        $this->assertSame(0.0, VatMode::withholding(190000, 0));
    }

    /**
     * @dataProvider bahtTextCases
     */
    public function test_it_writes_baht_in_words(float $amount, string $expected): void
    {
        $this->assertSame($expected, VatMode::bahtText($amount));
    }

    public static function bahtTextCases(): array
    {
        return [
            'zero' => [0, 'ศูนย์บาทถ้วน'],
            'one' => [1, 'หนึ่งบาทถ้วน'],
            'ten reads สิบ not หนึ่งสิบ' => [10, 'สิบบาทถ้วน'],
            'eleven ends in เอ็ด' => [11, 'สิบเอ็ดบาทถ้วน'],
            'twenty reads ยี่สิบ' => [20, 'ยี่สิบบาทถ้วน'],
            'twenty one' => [21, 'ยี่สิบเอ็ดบาทถ้วน'],
            'hundred' => [100, 'หนึ่งร้อยบาทถ้วน'],
            'hundred and one' => [101, 'หนึ่งร้อยเอ็ดบาทถ้วน'],
            'the quotation total' => [203300, 'สองแสนสามพันสามร้อยบาทถ้วน'],
            'with satang' => [1234.50, 'หนึ่งพันสองร้อยสามสิบสี่บาทห้าสิบสตางค์'],
            'one satang' => [0.01, 'ศูนย์บาทหนึ่งสตางค์'],
            'million' => [1000000, 'หนึ่งล้านบาทถ้วน'],
            'million and change' => [1234567, 'หนึ่งล้านสองแสนสามหมื่นสี่พันห้าร้อยหกสิบเจ็ดบาทถ้วน'],
            'inclusive base' => [177570.09, 'หนึ่งแสนเจ็ดหมื่นเจ็ดพันห้าร้อยเจ็ดสิบบาทเก้าสตางค์'],
        ];
    }

    public function test_satang_rounding_up_rolls_into_the_baht(): void
    {
        // 0.999 rounds to 1.00, and must not read "ศูนย์บาทหนึ่งร้อยสตางค์".
        $this->assertSame('หนึ่งบาทถ้วน', VatMode::bahtText(0.999));
    }
}
