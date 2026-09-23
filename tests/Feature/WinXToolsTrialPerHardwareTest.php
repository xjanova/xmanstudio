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
 * WinXTools gives one Pro trial per PC — per hardware, not per machine id.
 *
 * Its machine id comes from Windows' MachineGuid, so reinstalling Windows makes a new machine id,
 * a new device row, and used to make a second trial. The hardware hash the app also sends
 * (SMBIOS system UUID + board/system serials) survives the reinstall, so the trial is bound to it.
 *
 * Someone who reinstalled Windows is not an abuser: they are told the trial is used, nothing more
 * — no suspicious flag, no block. Other products keep their old rules (some Android apps send a
 * hash that is the same for every phone of a model).
 */
class WinXToolsTrialPerHardwareTest extends TestCase
{
    use RefreshDatabase;

    private const HARDWARE = '9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08';

    private const FIRST_INSTALL = 'a';

    private const AFTER_REINSTALL = 'b';

    public function test_reinstalling_windows_does_not_give_a_second_trial(): void
    {
        $this->licensedProduct('winx-tools');

        $this->registerDevice('winx-tools', self::FIRST_INSTALL, self::HARDWARE)->assertOk();
        $this->demo('winx-tools', self::FIRST_INSTALL, self::HARDWARE)->assertOk();

        // Windows reinstalled: new MachineGuid → new machine id, same hardware
        $this->registerDevice('winx-tools', self::AFTER_REINSTALL, self::HARDWARE)->assertOk();

        // the app asks first and learns the trial is used, without calling /demo at all
        $this->demoCheck('winx-tools', self::AFTER_REINSTALL)
            ->assertOk()
            ->assertJsonPath('data.has_used_demo', true)
            ->assertJsonPath('data.can_start_demo', false);

        $this->demo('winx-tools', self::AFTER_REINSTALL, self::HARDWARE)
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'TRIAL_USED_ON_THIS_HARDWARE')
            ->assertJsonPath('message', 'เครื่องนี้เคยใช้สิทธิ์ทดลองใช้ไปแล้ว')
            ->assertJsonStructure(['server_time']);

        // told no — not accused of anything
        $device = $this->device(self::AFTER_REINSTALL);
        $this->assertFalse($device->is_suspicious);
        $this->assertNull($device->abuse_reason);
        $this->assertNotSame(ProductDevice::STATUS_BLOCKED, $device->status);
        $this->assertSame(0, $device->trial_attempts);
        $this->assertSame(0, LicenseKey::where('machine_id', $this->machineId(self::AFTER_REINSTALL))->count());
    }

    public function test_demo_check_can_be_told_the_hardware_when_the_pc_never_registered(): void
    {
        $this->licensedProduct('winx-tools');
        $this->demo('winx-tools', self::FIRST_INSTALL, self::HARDWARE)->assertOk();

        $this->demoCheck('winx-tools', self::AFTER_REINSTALL, self::HARDWARE)
            ->assertOk()
            ->assertJsonPath('data.has_used_demo', true)
            ->assertJsonPath('data.can_start_demo', false);

        // and without anything to go on, nothing is assumed
        $this->demoCheck('winx-tools', 'c')
            ->assertOk()
            ->assertExactJson(['success' => true, 'data' => ['has_used_demo' => false, 'can_start_demo' => true]]);
    }

    public function test_asking_again_during_its_own_trial_still_answers_trial_active(): void
    {
        $this->licensedProduct('winx-tools');
        $this->demo('winx-tools', self::FIRST_INSTALL, self::HARDWARE)->assertOk();

        // the app re-asks when two starts race; it reads the running trial back from this reply
        $this->demo('winx-tools', self::FIRST_INSTALL, self::HARDWARE)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'TRIAL_ACTIVE')
            ->assertJsonPath('trial_info.hours_remaining', 47);
    }

    public function test_other_hardware_and_unknown_hardware_still_get_a_trial(): void
    {
        $this->licensedProduct('winx-tools');
        $this->demo('winx-tools', self::FIRST_INSTALL, self::HARDWARE)->assertOk();

        $this->demo('winx-tools', 'c', str_repeat('0c', 32))->assertOk();

        // junk firmware → the app sends no hash rather than one shared by many PCs
        $this->demo('winx-tools', 'd', null)->assertOk();
    }

    public function test_other_products_do_not_bind_the_trial_to_the_hardware(): void
    {
        $this->licensedProduct('some-android-app');

        $this->registerDevice('some-android-app', self::FIRST_INSTALL, self::HARDWARE)->assertOk();
        $this->demo('some-android-app', self::FIRST_INSTALL, self::HARDWARE)->assertOk();

        $this->registerDevice('some-android-app', self::AFTER_REINSTALL, self::HARDWARE)->assertOk();

        $this->demoCheck('some-android-app', self::AFTER_REINSTALL)
            ->assertOk()
            ->assertJsonPath('data.has_used_demo', false)
            ->assertJsonPath('data.can_start_demo', true);

        $this->demo('some-android-app', self::AFTER_REINSTALL, self::HARDWARE)->assertOk();
    }

    private function registerDevice(string $slug, string $machine, ?string $hardwareHash): TestResponse
    {
        return $this->fromPc($machine)->postJson("/api/v1/product/{$slug}/register-device", array_filter([
            'machine_id' => $this->machineId($machine),
            'hardware_hash' => $hardwareHash,
        ]));
    }

    private function demo(string $slug, string $machine, ?string $hardwareHash): TestResponse
    {
        return $this->fromPc($machine)->postJson("/api/v1/product/{$slug}/demo", array_filter([
            'machine_id' => $this->machineId($machine),
            'hardware_hash' => $hardwareHash,
        ]));
    }

    private function demoCheck(string $slug, string $machine, ?string $hardwareHash = null): TestResponse
    {
        return $this->fromPc($machine)->postJson("/api/v1/product/{$slug}/demo/check", array_filter([
            'machine_id' => $this->machineId($machine),
            'hardware_hash' => $hardwareHash,
        ]));
    }

    /**
     * Each PC on its own address, so the unrelated same-IP rule (two trials from one IP already)
     * stays out of these tests.
     */
    private function fromPc(string $machine): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.' . (ord($machine) - 96)]);
    }

    private function device(string $machine): ProductDevice
    {
        return ProductDevice::where('machine_id', $this->machineId($machine))->sole();
    }

    private function machineId(string $machine): string
    {
        return str_repeat($machine, 32);
    }

    private function licensedProduct(string $slug): Product
    {
        return Product::create([
            'category_id' => Category::firstOrCreate(['slug' => 'test-apps'], ['name' => 'Apps', 'description' => 'x'])->id,
            'name' => $slug,
            'slug' => $slug,
            'description' => 'x',
            'price' => 199,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);
    }
}
