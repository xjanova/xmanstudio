<?php

namespace Tests\Feature;

use App\Models\AutoTradeXDevice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * register-device has to answer a machine that sends no hardware hash.
 *
 * findRelatedByHardware() declares an Eloquent collection but handed back a
 * Support one for such a machine; the TypeError reached the app as a 500 and
 * the app gave up on registering.
 */
class AutoTradeXDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_machine_without_a_hardware_hash_can_register(): void
    {
        $machine = str_repeat('b2', 16);

        $this->postJson('/api/v1/autotradex/register-device', ['machine_id' => $machine])
            ->assertOk()
            ->assertJsonPath('success', true);

        $related = AutoTradeXDevice::where('machine_id', $machine)->sole()->findRelatedByHardware();
        $this->assertInstanceOf(Collection::class, $related);
        $this->assertCount(0, $related);
    }

    public function test_machines_sharing_a_hardware_hash_still_find_each_other(): void
    {
        foreach (['c3', 'd4'] as $pair) {
            $this->postJson('/api/v1/autotradex/register-device', [
                'machine_id' => str_repeat($pair, 16),
                'hardware_hash' => 'same-board',
            ])->assertOk();
        }

        $related = AutoTradeXDevice::where('machine_id', str_repeat('c3', 16))->sole()->findRelatedByHardware();
        $this->assertCount(1, $related);
        $this->assertSame(str_repeat('d4', 16), $related->first()->machine_id);
    }
}
