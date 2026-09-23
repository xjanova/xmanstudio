<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Which other devices count as "the same physical device" when a trial is asked for.
 *
 * Check 2 of ProductDevice::checkTrialAbuse() links devices by drm_id / android_id. A Windows app
 * sends neither — and with nothing to match on, the query used to match EVERY device of the
 * product. So the second PC ever to ask for a trial was flagged suspicious, handed the first PC's
 * trial clock (TRIAL_ACTIVE with someone else's expiry), and once that trial ran out, blocked.
 */
class TrialDeviceMatchingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_second_pc_without_device_ids_gets_its_own_trial(): void
    {
        $this->licensedProduct('some-desktop-app');

        $first = $this->demo('some-desktop-app', 'a', '203.0.113.10')->assertOk();

        $this->travel(1)->hours();

        $second = $this->demo('some-desktop-app', 'b', '198.51.100.20')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotSame($first->json('data.expires_at'), $second->json('data.expires_at'),
            'the second PC must get its own trial, not a copy of the first one');

        $device = ProductDevice::where('machine_id', str_repeat('b', 32))->sole();
        $this->assertFalse($device->is_suspicious);
        $this->assertNull($device->abuse_reason);
    }

    public function test_devices_that_share_a_drm_id_are_still_the_same_device(): void
    {
        $this->licensedProduct('some-android-app');

        $this->demo('some-android-app', 'a', '203.0.113.10', 'DRM-SAME-PHONE')->assertOk();

        // app data cleared → new machine id, same phone: still refused, and still flagged
        $this->demo('some-android-app', 'b', '198.51.100.20', 'DRM-SAME-PHONE')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'TRIAL_ACTIVE');

        $this->assertTrue(ProductDevice::where('machine_id', str_repeat('b', 32))->sole()->is_suspicious);
    }

    private function demo(string $slug, string $machine, string $ip, ?string $drmId = null): TestResponse
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson("/api/v1/product/{$slug}/demo", array_filter([
                'machine_id' => str_repeat($machine, 32),
                'drm_id' => $drmId,
            ]));
    }

    private function licensedProduct(string $slug): Product
    {
        return Product::create([
            'category_id' => Category::firstOrCreate(['slug' => 'test-apps'], ['name' => 'Apps', 'description' => 'x'])->id,
            'name' => $slug,
            'slug' => $slug,
            'description' => 'x',
            'price' => 990,
            'stock' => 0,
            'requires_license' => true,
            'is_active' => true,
        ]);
    }
}
