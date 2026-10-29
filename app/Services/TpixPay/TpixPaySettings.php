<?php

namespace App\Services\TpixPay;

use App\Models\PaymentSetting;
use Throwable;

/**
 * What the admin set for TPIX payments (payment_settings, group `tpix`), read once per request —
 * the price tag renders many times on a page and must not ask the database each time.
 * Bound as a scoped singleton in AppServiceProvider.
 */
class TpixPaySettings
{
    public const ADDRESS_PATTERN = '/^0x[0-9a-fA-F]{40}$/';

    /** The most the AI-credit TPIX discount may be set to (percent). */
    public const MAX_AI_CREDIT_DISCOUNT = 50;

    /** @var array<string, mixed>|null */
    private ?array $values = null;

    public function enabled(): bool
    {
        return (bool) $this->values()['enabled'];
    }

    /** The shop's address, lower-cased (EIP-55 checksums are only a typing aid; the chain does not care). */
    public function receiveAddress(): ?string
    {
        $address = trim((string) $this->values()['address']);

        return preg_match(self::ADDRESS_PATTERN, $address) ? strtolower($address) : null;
    }

    public function confirmations(): int
    {
        return max(1, (int) $this->values()['confirmations']);
    }

    public function quoteTtlMinutes(): int
    {
        return max(5, (int) $this->values()['ttl']);
    }

    /** A quote may be worth at most this many times the pool's USDT side (see config tpix_pay.max_pool_ratio). */
    public function maxPoolRatio(): float
    {
        return max(0.1, (float) $this->values()['max_pool_ratio']);
    }

    /** Customers may be offered TPIX: switched on and somewhere to send it. (A price is checked separately.) */
    public function acceptsPayments(): bool
    {
        return $this->enabled() && $this->receiveAddress() !== null;
    }

    /**
     * Show TPIX prices next to THB prices on the product pages and in the apps (owner, 2026-10-13:
     * on every product page, even before payments are switched on). Its own switch, on unless the
     * admin turned it off — independent of taking payments and of the receive address.
     */
    public function showsPrices(): bool
    {
        return (bool) $this->values()['show_prices'];
    }

    /**
     * How many percent cheaper an AI credit package is when paid in TPIX (owner, 2026-10-29: the
     * TPIX campaign on ai.xman4289.com). A whole number 0–50; whatever is stored is clamped into it.
     */
    public function aiCreditDiscountPercent(): int
    {
        return max(0, min(self::MAX_AI_CREDIT_DISCOUNT, (int) $this->values()['ai_credit_discount_percent']));
    }

    public function forget(): void
    {
        $this->values = null;
    }

    /**
     * Save what the admin page sent (already validated). The AI-credit discount is only written when
     * it is given — a caller that does not know about it leaves the stored value alone.
     *
     * @param  array{enabled:bool, address:?string, confirmations:int, ttl:int, max_pool_ratio?:float, show_prices?:bool, ai_credit_discount_percent?:int|null}  $values
     */
    public function save(array $values): void
    {
        PaymentSetting::set('tpix_enabled', (bool) $values['enabled'], [
            'group' => 'tpix', 'type' => 'boolean', 'label' => 'เปิดรับชำระด้วยเหรียญ TPIX',
        ]);
        PaymentSetting::set('tpix_show_prices', (bool) ($values['show_prices'] ?? true), [
            'group' => 'tpix', 'type' => 'boolean', 'label' => 'แสดงราคาเป็น TPIX บนหน้าสินค้าทุกหน้า',
        ]);
        PaymentSetting::set('tpix_receive_address', trim((string) $values['address']), [
            'group' => 'tpix', 'type' => 'string', 'label' => 'ที่อยู่กระเป๋ารับเหรียญ TPIX',
            'description' => 'ที่อยู่บน TPIX Chain (0x… 40 ตัวอักษร) ที่ลูกค้าโอนเหรียญเข้า',
        ]);
        PaymentSetting::set('tpix_confirmations', (int) $values['confirmations'], [
            'group' => 'tpix', 'type' => 'integer', 'label' => 'จำนวนบล็อกยืนยันก่อนถือว่าชำระแล้ว',
        ]);
        PaymentSetting::set('tpix_quote_ttl_minutes', (int) $values['ttl'], [
            'group' => 'tpix', 'type' => 'integer', 'label' => 'อายุใบเสนอราคา TPIX (นาที)',
        ]);
        PaymentSetting::set('tpix_max_pool_ratio', (float) ($values['max_pool_ratio'] ?? config('tpix_pay.max_pool_ratio', 5)), [
            'group' => 'tpix', 'type' => 'float', 'label' => 'ยอดต่อรายการสูงสุด (เท่าของ USDT ในพูล)',
        ]);
        if (isset($values['ai_credit_discount_percent'])) {
            PaymentSetting::set('tpix_ai_credit_discount_percent', max(0, min(self::MAX_AI_CREDIT_DISCOUNT, (int) $values['ai_credit_discount_percent'])), [
                'group' => 'tpix', 'type' => 'integer', 'label' => 'ส่วนลดแพ็กเกจ AI Credits เมื่อชำระด้วย TPIX (%)',
            ]);
        }

        $this->forget();
    }

    /** @return array<string, mixed> */
    private function values(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $values = [
            'enabled' => false,
            // On when nobody has saved it yet: the owner wants the TPIX price on every page
            'show_prices' => true,
            'address' => null,
            'confirmations' => (int) config('tpix_pay.confirmations', 3),
            'ttl' => (int) config('tpix_pay.quote_ttl_minutes', 30),
            'max_pool_ratio' => (float) config('tpix_pay.max_pool_ratio', 5),
            'ai_credit_discount_percent' => (int) config('tpix_pay.ai_credit_discount_percent', 20),
        ];

        try {
            $rows = PaymentSetting::where('group', 'tpix')->get()->mapWithKeys(fn ($s) => [$s->key => $s->typed_value]);
            $values['enabled'] = (bool) ($rows['tpix_enabled'] ?? false);
            $values['show_prices'] = (bool) ($rows['tpix_show_prices'] ?? true);
            $values['address'] = $rows['tpix_receive_address'] ?? null;
            $values['confirmations'] = (int) ($rows['tpix_confirmations'] ?? $values['confirmations']);
            $values['ttl'] = (int) ($rows['tpix_quote_ttl_minutes'] ?? $values['ttl']);
            $values['max_pool_ratio'] = (float) ($rows['tpix_max_pool_ratio'] ?? $values['max_pool_ratio']);
            $values['ai_credit_discount_percent'] = (int) ($rows['tpix_ai_credit_discount_percent'] ?? $values['ai_credit_discount_percent']);
        } catch (Throwable) {
            // No table yet (a fresh install mid-migration): TPIX simply stays off
        }

        return $this->values = $values;
    }
}
