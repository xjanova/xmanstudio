<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\Satang;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * บวก/หักกระเป๋าหนึ่งรายการ แบบที่เงินจริงต้องเป็น — ไม่ผ่าน Wallet::deposit()/pay()/addBonus()
 *
 * สามตัวนั้นอ่าน balance_before โดยไม่ล็อก และ deposit/addBonus บวก total_deposited ด้วย
 * (รายได้ ค่าแนะนำ และการคืนยอดที่พักไว้ ไม่ใช่เงินที่เจ้าของเติม) ตรงนี้:
 *
 *   - เปลี่ยนยอดด้วย UPDATE แบบ atomic (balance = balance ± x) — การหักมีเงื่อนไข balance >= x
 *     ในคำสั่งเดียวกัน ยอดจึงไม่มีวันติดลบแม้สองคำขอมาพร้อมกัน (แบบเดียวกับที่ aixman ตัดเงิน)
 *   - อ่านยอดหลังเปลี่ยนในทรานแซกชันเดียวกัน (แถวถูกล็อกด้วย UPDATE แล้ว) แล้วคิด balance_before ย้อน
 *   - เขียน wallet_transactions ด้วยเลขรายการที่ผู้เรียกกำหนด — เลขที่คำนวณซ้ำได้และเป็น unique
 *     คือสิ่งที่กันไม่ให้เหตุการณ์เดียวกันเข้ากระเป๋าสองครั้ง
 *
 * ผู้เรียกต้องอยู่ในทรานแซกชันเสมอ (DB::transaction) — ไม่งั้นยอดกับรายการอาจไม่ตรงกันเมื่อพังกลางทาง
 */
class WalletLedger
{
    /**
     * บวกเงินเข้ากระเป๋า
     *
     * @param  bool  $onlyActive  true = กระเป๋าที่ถูกปิดไม่รับเงิน (รายได้/ค่าแนะนำรอจนกว่าจะเปิด)
     *                            false = คืนเงินที่เป็นของเขาอยู่แล้ว (ยอดที่พักไว้กับคำขอถอน) แม้กระเป๋าถูกปิด
     * @param  array<string, mixed>  $attributes  คอลัมน์ของ wallet_transactions (ต้องมี transaction_id และ type)
     */
    public function credit(Wallet $wallet, int $satang, array $attributes, bool $onlyActive = true): WalletTransaction
    {
        $this->assertInTransaction();

        if ($satang <= 0) {
            throw new RuntimeException("WalletLedger: credit of {$satang} satang to wallet {$wallet->id}");
        }

        $updated = Wallet::whereKey($wallet->id)
            ->when($onlyActive, fn ($q) => $q->where('is_active', true))
            ->increment('balance', Satang::toBaht($satang));

        if ($updated !== 1) {
            throw new RuntimeException("WalletLedger: wallet {$wallet->id} refused the credit");
        }

        return $this->record($wallet, $satang, $attributes);
    }

    /**
     * หักเงินจากกระเป๋า — null เมื่อยอดไม่พอ (ไม่หักอะไรเลย)
     *
     * @param  array<string, mixed>  $attributes
     */
    public function debit(Wallet $wallet, int $satang, array $attributes): ?WalletTransaction
    {
        $this->assertInTransaction();

        if ($satang <= 0) {
            throw new RuntimeException("WalletLedger: debit of {$satang} satang from wallet {$wallet->id}");
        }

        $amount = Satang::toBaht($satang);

        $updated = Wallet::whereKey($wallet->id)
            ->where('balance', '>=', $amount)
            ->decrement('balance', $amount);

        if ($updated !== 1) {
            return null;
        }

        return $this->record($wallet, -$satang, $attributes);
    }

    /** ยอดคงเหลือตอนนี้เป็นสตางค์ (อ่านในทรานแซกชันของผู้เรียก) */
    public function balanceSatang(int $walletId): int
    {
        $balance = Wallet::whereKey($walletId)->value('balance');

        return Satang::fromBaht($balance === null ? null : (string) $balance);
    }

    /** @param  array<string, mixed>  $attributes */
    private function record(Wallet $wallet, int $signedSatang, array $attributes): WalletTransaction
    {
        $after = $this->balanceSatang($wallet->id);

        return WalletTransaction::create(array_merge($attributes, [
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'amount' => Satang::toBaht($signedSatang),
            'balance_before' => Satang::toBaht($after - $signedSatang),
            'balance_after' => Satang::toBaht($after),
        ]));
    }

    private function assertInTransaction(): void
    {
        if (DB::transactionLevel() < 1) {
            throw new RuntimeException('WalletLedger must run inside a database transaction');
        }
    }
}
