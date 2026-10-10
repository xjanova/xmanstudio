<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\ProductDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * BrainX Pro — the desktop app's paid tier — is licensed through product `brainx`, the same product
 * as BrainX Cloud: one paid key unlocks both. The app talks to the generic licence API
 * (/api/v1/product/brainx/...): a 7-day Pro trial, once per PC (bound to the hardware hash, as
 * WinXTools is — a Windows reinstall makes a new machine id, not a new trial), and a paid key that
 * activates on one machine, validates, is found again by check-machine, and can be moved.
 */
class BrainXProLicenseTest extends TestCase
{
    use RefreshDatabase;

    private const HARDWARE = '3b5d5c3712955042212316173ccf37be800c6ff7b0b8f1a3e4a0a2d4b6c8d0e2';

    private Product $brainx;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeSecond();

        // Created by the 2026_09_24_100001 migration, exactly as production has it
        $this->brainx = Product::where('slug', 'brainx')->firstOrFail();
        $this->assertTrue($this->brainx->requires_license);
    }

    // ── the trial ─────────────────────────────────────────────────────

    public function test_a_new_pc_is_offered_the_trial(): void
    {
        $this->registerDevice('a', self::HARDWARE)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.can_start_trial', true);

        $this->demoCheck('a')
            ->assertOk()
            ->assertJsonPath('data.has_used_demo', false)
            ->assertJsonPath('data.can_start_demo', true);
    }

    public function test_the_brainx_pro_trial_lasts_seven_days(): void
    {
        $response = $this->demo('a', self::HARDWARE);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.days_remaining', 7)
            ->assertJsonPath('data.hours_remaining', 168)
            ->assertJsonPath('data.seconds_remaining', 7 * 24 * 3600)
            ->assertJsonPath('data.expires_at', now()->addDays(7)->toISOString());

        $license = LicenseKey::where('license_key', $response->json('data.license_key'))->sole();
        $this->assertSame($this->brainx->id, $license->product_id);
        $this->assertSame(LicenseKey::TYPE_DEMO, $license->license_type);
        $this->assertTrue($license->expires_at->equalTo(now()->addDays(7)));

        $this->assertTrue($this->device('a')->trial_expires_at->equalTo(now()->addDays(7)));

        // the app reads the running trial back from demo/check
        $this->demoCheck('a')
            ->assertOk()
            ->assertJsonPath('data.is_trial_active', true)
            ->assertJsonPath('data.can_start_demo', false)
            ->assertJsonPath('data.trial_info.days_remaining', 7);

        // and the trial key validates on its own machine for the week
        $this->validateKey('a', $license->license_key)
            ->assertOk()
            ->assertJsonPath('is_valid', true)
            ->assertJsonPath('data.license_type', 'demo');

        $this->travel(7)->days();
        $this->travel(1)->minutes();

        $this->validateKey('a', $license->license_key)->assertOk()->assertJsonPath('is_valid', false);
    }

    public function test_reinstalling_windows_does_not_give_a_second_trial(): void
    {
        $this->registerDevice('a', self::HARDWARE)->assertOk();
        $this->demo('a', self::HARDWARE)->assertOk();

        // new MachineGuid → new machine id, same board
        $this->registerDevice('b', self::HARDWARE)->assertOk();

        $this->demoCheck('b')
            ->assertOk()
            ->assertJsonPath('data.has_used_demo', true)
            ->assertJsonPath('data.can_start_demo', false);

        $this->demo('b', self::HARDWARE)
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'TRIAL_USED_ON_THIS_HARDWARE');

        // told no, not accused of anything, and no second key
        $device = $this->device('b');
        $this->assertFalse($device->is_suspicious);
        $this->assertNotSame(ProductDevice::STATUS_BLOCKED, $device->status);
        $this->assertSame(1, LicenseKey::where('product_id', $this->brainx->id)->where('license_type', 'demo')->count());
    }

    public function test_other_hardware_still_gets_its_own_trial(): void
    {
        $this->demo('a', self::HARDWARE)->assertOk();

        $this->demo('c', str_repeat('0c', 32))->assertOk()->assertJsonPath('data.days_remaining', 7);
    }

    // ── a paid key ────────────────────────────────────────────────────

    public function test_a_paid_monthly_key_activates_validates_is_found_again_and_moves(): void
    {
        $key = $this->paidKey();

        $this->activate('a', strtolower($key->license_key))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.license_key', $key->license_key)
            ->assertJsonPath('data.license_type', 'monthly')
            ->assertJsonPath('data.days_remaining', 30)
            ->assertJsonPath('data.features', ['all_features', 'standard_support', 'cloud_sync']);

        $key->refresh();
        $this->assertSame($this->machineId('a'), $key->machine_id);
        $this->assertTrue($key->expires_at->equalTo(now()->addDays(30)), 'activation keeps the paid expiry');
        $this->assertSame(ProductDevice::STATUS_LICENSED, $this->device('a')->status);

        $this->validateKey('a', $key->license_key)
            ->assertOk()
            ->assertJsonPath('is_valid', true)
            ->assertJsonPath('data.license_type', 'monthly')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.is_expired', false);

        // first launch after a reinstall of the app: found by the machine alone
        $this->checkMachine('a')
            ->assertOk()
            ->assertJsonPath('has_license', true)
            ->assertJsonPath('data.license_key', $key->license_key)
            ->assertJsonPath('data.license_type', 'monthly');

        // the key is bound: another PC cannot take it without asking to move it
        $this->activate('b', $key->license_key)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'ALREADY_ACTIVATED_OTHER_DEVICE');

        $this->validateKey('b', $key->license_key)
            ->assertNotFound()
            ->assertJsonPath('is_valid', false)
            ->assertJsonPath('error_code', 'INVALID_LICENSE');

        // released from the first PC…
        $this->deactivate('a', $key->license_key)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.can_reactivate', true);

        $this->assertNull($key->fresh()->machine_id);
        $this->assertSame(ProductDevice::STATUS_PENDING, $this->device('a')->status);
        $this->validateKey('a', $key->license_key)->assertNotFound()->assertJsonPath('error_code', 'INVALID_LICENSE');
        $this->checkMachine('a')->assertOk()->assertJsonPath('has_license', false);

        // …it activates on the next one, with the expiry it was bought with
        $this->activate('b', $key->license_key)->assertOk()->assertJsonPath('data.days_remaining', 30);
        $this->assertSame($this->machineId('b'), $key->fresh()->machine_id);
        $this->validateKey('b', $key->license_key)->assertOk()->assertJsonPath('is_valid', true);
    }

    public function test_a_lapsed_paid_key_does_not_activate(): void
    {
        $key = $this->paidKey(['expires_at' => now()->subDay()]);

        $this->activate('a', $key->license_key)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'LICENSE_EXPIRED');
    }

    public function test_a_key_of_another_product_is_not_a_brainx_key(): void
    {
        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $other = Product::create([
            'category_id' => $category->id, 'name' => 'Other app', 'slug' => 'other-app',
            'description' => 'x', 'price' => 100, 'requires_license' => true, 'is_active' => true,
        ]);
        $otherKey = LicenseKey::create([
            'product_id' => $other->id, 'license_key' => LicenseKey::generateKey(), 'license_type' => 'monthly',
            'status' => 'active', 'expires_at' => now()->addDays(30), 'max_activations' => 1, 'activations' => 0,
        ]);

        $this->activate('a', $otherKey->license_key)
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'INVALID_LICENSE');

        $this->assertNull($otherKey->fresh()->machine_id);

        // bound to this PC under its own product, it is still not a BrainX licence here
        $this->postJson('/api/v1/product/other-app/activate', [
            'license_key' => $otherKey->license_key,
            'machine_id' => $this->machineId('a'),
            'machine_fingerprint' => 'fp-a',
        ])->assertOk();

        $this->validateKey('a', $otherKey->license_key)->assertNotFound()->assertJsonPath('error_code', 'INVALID_LICENSE');
        $this->deactivate('a', $otherKey->license_key)->assertNotFound()->assertJsonPath('error_code', 'INVALID_LICENSE');
        $this->checkMachine('a')->assertOk()->assertJsonPath('has_license', false);
    }

    // ── helpers ───────────────────────────────────────────────────────

    private function paidKey(array $overrides = []): LicenseKey
    {
        return LicenseKey::create($overrides + [
            'product_id' => $this->brainx->id,
            'license_key' => LicenseKey::generateKey(),
            'license_type' => LicenseKey::TYPE_MONTHLY,
            'status' => LicenseKey::STATUS_ACTIVE,
            'expires_at' => now()->addDays(30),
            'max_activations' => 1,
            'activations' => 0,
        ]);
    }

    private function registerDevice(string $machine, ?string $hardwareHash): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/brainx/register-device', array_filter([
            'machine_id' => $this->machineId($machine),
            'machine_name' => 'PC-' . strtoupper($machine),
            'hardware_hash' => $hardwareHash,
        ]));
    }

    private function demo(string $machine, ?string $hardwareHash): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/brainx/demo', array_filter([
            'machine_id' => $this->machineId($machine),
            'hardware_hash' => $hardwareHash,
        ]));
    }

    private function demoCheck(string $machine): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/brainx/demo/check', [
            'machine_id' => $this->machineId($machine),
        ]);
    }

    private function activate(string $machine, string $key): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/brainx/activate', [
            'license_key' => $key,
            'machine_id' => $this->machineId($machine),
            'machine_fingerprint' => 'fp-' . $machine,
            'app_version' => '2.0.492',
        ]);
    }

    private function validateKey(string $machine, string $key): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/brainx/validate', [
            'license_key' => $key,
            'machine_id' => $this->machineId($machine),
        ]);
    }

    private function checkMachine(string $machine): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/brainx/check-machine', [
            'machine_id' => $this->machineId($machine),
        ]);
    }

    private function deactivate(string $machine, string $key): TestResponse
    {
        return $this->fromPc($machine)->postJson('/api/v1/product/brainx/deactivate', [
            'license_key' => $key,
            'machine_id' => $this->machineId($machine),
        ]);
    }

    /** Each PC on its own address, so the unrelated same-IP trial rule stays out of these tests. */
    private function fromPc(string $machine): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.' . (ord($machine) - 96)]);
    }

    private function device(string $machine): ProductDevice
    {
        return ProductDevice::where('product_id', $this->brainx->id)->where('machine_id', $this->machineId($machine))->sole();
    }

    private function machineId(string $machine): string
    {
        return str_repeat($machine, 32);
    }
}
