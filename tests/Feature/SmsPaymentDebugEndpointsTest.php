<?php

namespace Tests\Feature;

use App\Models\SmsCheckerDevice;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTopup;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * The SMS-payment debug endpoints that could move money.
 *
 * `POST /api/v1/sms-payment/debug-topup-approve` needed only a device API key — no signature, no
 * nonce — and reset ANY top-up back to pending before approving it, so every call deposited the
 * top-up's amount into the wallet again. Its GET sibling handed any key holder other customers'
 * top-ups, wallet balances and log lines. Neither was ever called by the SmsChecker app; both are
 * gone. The admin-side debug page approved on a GET (`?do_approve=1`) — a CSRF away from free
 * money — and is read-only now.
 */
class SmsPaymentDebugEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'test-device-api-key-0000000000000000000000000000000000000000000';

    private function device(): SmsCheckerDevice
    {
        return SmsCheckerDevice::create([
            'device_id' => 'phone-1',
            'name' => 'Owner phone',
            'api_key' => self::API_KEY,
            'secret_key' => str_repeat('s', 64),
            'status' => 'active',
        ]);
    }

    /** @return array{0:Wallet,1:WalletTopup} */
    private function topup(string $status = WalletTopup::STATUS_PENDING): array
    {
        $customer = User::factory()->create();
        $wallet = Wallet::create(['user_id' => $customer->id, 'balance' => 0]);
        $topup = WalletTopup::create([
            'wallet_id' => $wallet->id,
            'user_id' => $customer->id,
            'topup_id' => WalletTopup::generateTopupId(),
            'amount' => 500,
            'bonus_amount' => 0,
            'total_amount' => 500,
            'payment_method' => WalletTopup::METHOD_PROMPTPAY,
            'status' => $status,
        ]);

        return [$wallet, $topup];
    }

    public function test_the_debug_approve_endpoint_can_no_longer_credit_a_wallet_again(): void
    {
        $this->device();
        [$wallet, $topup] = $this->topup();
        $this->assertTrue($topup->approve(0));
        $this->assertEquals(500.0, (float) $wallet->fresh()->balance);

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/sms-payment/debug-topup-approve?topup_id=' . $topup->id, [], ['X-Api-Key' => self::API_KEY])
                ->assertNotFound();
        }

        $this->assertSame(WalletTopup::STATUS_APPROVED, $topup->fresh()->status);
        $this->assertEquals(500.0, (float) $wallet->fresh()->balance, 'credited once, not once per call');
        $this->assertSame(1, WalletTransaction::where('wallet_id', $wallet->id)->where('type', 'deposit')->count());
    }

    public function test_the_debug_topup_diagnostics_endpoint_is_gone(): void
    {
        $this->device();
        $this->topup();

        $this->getJson('/api/v1/sms-payment/debug-topup', ['X-Api-Key' => self::API_KEY])->assertNotFound();
    }

    public function test_a_top_up_is_never_approved_twice(): void
    {
        [$wallet, $topup] = $this->topup();

        $this->assertTrue($topup->approve(0));
        $this->assertFalse(WalletTopup::find($topup->id)->approve(0), 'a second approval, on a fresh copy, is refused');
        $this->assertFalse($topup->approve(0), 'and on the same copy');

        $this->assertEquals(500.0, (float) $wallet->fresh()->balance);
    }

    public function test_the_debug_report_still_works_but_never_logs_the_api_key(): void
    {
        $this->device();
        Log::spy();

        $this->postJson('/api/v1/sms-payment/debug-report', ['app_version' => '9.9', 'fcm_token_length' => 0], [
            'X-Api-Key' => self::API_KEY,
            'X-Signature' => 'sig-should-not-be-logged',
        ])->assertOk()->assertJson(['success' => true]);

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context = []) {
            if (! str_contains((string) $message, 'APP DEBUG REPORT')) {
                return false;
            }
            $logged = json_encode($context);

            return ! str_contains($logged, self::API_KEY) && ! str_contains($logged, 'sig-should-not-be-logged')
                && ($context['all_data']['app_version'] ?? null) === '9.9';
        })->once();
    }

    public function test_the_admin_debug_page_never_approves_on_a_get(): void
    {
        [$wallet, $topup] = $this->topup();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.wallets.topups.debug', $topup) . '?do_approve=1')
            ->assertOk()
            ->assertJsonPath('approve_simulation.status_check', 'PASS — status is pending');

        $this->assertSame(WalletTopup::STATUS_PENDING, $topup->fresh()->status);
        $this->assertEquals(0.0, (float) $wallet->fresh()->balance);
    }
}
