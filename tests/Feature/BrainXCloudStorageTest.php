<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LicenseActivity;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\User;
use App\Support\CloudStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BrainX Cloud space: every brainx key starts with the plan's 4 GB and the admin can add
 * more to one key. The cloud server reads the total from the status API as
 * cloud_storage_bytes — an integer byte count — and enforces it.
 */
class BrainXCloudStorageTest extends TestCase
{
    use RefreshDatabase;

    private const GB = 1024 * 1024 * 1024;

    private Product $brainx;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        // Created by the 2026_09_24_100001 migration, exactly as production gets it on deploy
        $this->brainx = Product::where('slug', 'brainx')->firstOrFail();
    }

    private function brainxKey(array $attributes = []): LicenseKey
    {
        return LicenseKey::create($attributes + [
            'product_id' => $this->brainx->id,
            'license_key' => LicenseKey::generateKey(),
            'license_type' => LicenseKey::TYPE_MONTHLY,
            'status' => LicenseKey::STATUS_ACTIVE,
            'expires_at' => now()->addDays(30),
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ── what the cloud server is told ─────────────────────────────────

    public function test_a_new_brainx_key_carries_the_4_gb_starter_space(): void
    {
        $key = $this->brainxKey();

        $this->assertSame(0, $key->fresh()->extra_storage_gb);
        $this->getJson("/api/v1/product/brainx/status/{$key->license_key}")
            ->assertOk()
            ->assertJsonPath('data.cloud_storage_gb', 4)
            ->assertJsonPath('data.cloud_storage_bytes', 4 * self::GB);
    }

    public function test_space_the_admin_added_is_in_the_total(): void
    {
        $key = $this->brainxKey(['extra_storage_gb' => 12]);

        $this->getJson("/api/v1/product/brainx/status/{$key->license_key}")
            ->assertOk()
            ->assertJsonPath('data.cloud_storage_gb', 16)
            ->assertJsonPath('data.cloud_storage_bytes', 16 * self::GB);
    }

    public function test_the_byte_count_is_an_integer_in_the_raw_json(): void
    {
        $key = $this->brainxKey(['extra_storage_gb' => 1020]);

        $raw = $this->getJson("/api/v1/product/brainx/status/{$key->license_key}")->assertOk()->getContent();

        // 1 TB in bytes — must not come out as 1.099511627776E+12
        $this->assertStringContainsString('"cloud_storage_bytes":' . (1024 * self::GB), $raw);
    }

    public function test_a_lapsed_key_still_reports_its_space(): void
    {
        $key = $this->brainxKey(['expires_at' => now()->subDay(), 'extra_storage_gb' => 4]);

        $this->getJson("/api/v1/product/brainx/status/{$key->license_key}")
            ->assertOk()
            ->assertJsonPath('data.is_valid', false)
            ->assertJsonPath('data.cloud_storage_gb', 8);
    }

    public function test_a_product_without_cloud_space_answers_exactly_as_before(): void
    {
        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $other = Product::create([
            'category_id' => $category->id, 'name' => 'Other app', 'slug' => 'other-app',
            'description' => 'x', 'price' => 100, 'requires_license' => true, 'is_active' => true,
        ]);
        $key = LicenseKey::create([
            'product_id' => $other->id, 'license_key' => LicenseKey::generateKey(), 'license_type' => 'yearly',
            'status' => 'active', 'expires_at' => now()->addYear(), 'extra_storage_gb' => 50,
        ]);

        $data = $this->getJson("/api/v1/product/other-app/status/{$key->license_key}")->assertOk()->json('data');

        $this->assertArrayNotHasKey('cloud_storage_bytes', $data);
        $this->assertArrayNotHasKey('cloud_storage_gb', $data);
        $this->assertNull(CloudStorage::totalGb($key));
    }

    // ── admin ▸ License ▸ พื้นที่คลาวด์ ──────────────────────────────────

    public function test_the_admin_license_page_shows_the_space_card_for_brainx(): void
    {
        $key = $this->brainxKey(['extra_storage_gb' => 4]);

        $this->actingAs($this->admin())
            ->get(route('admin.licenses.show', $key))
            ->assertOk()
            ->assertSee('พื้นที่คลาวด์')
            ->assertSee(route('admin.licenses.storage', $key), false)
            ->assertSee('8 GB');
    }

    public function test_the_admin_adds_space_to_one_key_and_it_is_logged(): void
    {
        $admin = $this->admin();
        $key = $this->brainxKey();
        $other = $this->brainxKey();

        $this->actingAs($admin)
            ->post(route('admin.licenses.storage', $key), ['extra_storage_gb' => 28])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(28, $key->fresh()->extra_storage_gb);
        $this->assertSame(0, $other->fresh()->extra_storage_gb);

        $log = LicenseActivity::where('license_id', $key->id)->where('action', LicenseActivity::ACTION_STORAGE_CHANGED)->sole();
        $this->assertSame($admin->id, $log->user_id);
        // assertEquals, not assertSame: MySQL's JSON column hands the keys back in its own order
        $this->assertEquals(['base_gb' => 4, 'previous_extra_gb' => 0, 'extra_gb' => 28, 'total_gb' => 32], $log->metadata);
        $this->assertSame('ปรับพื้นที่คลาวด์', $log->action_label);

        $this->getJson("/api/v1/product/brainx/status/{$key->license_key}")
            ->assertJsonPath('data.cloud_storage_bytes', 32 * self::GB);
    }

    public function test_saving_the_same_space_twice_logs_once(): void
    {
        $admin = $this->admin();
        $key = $this->brainxKey();

        $this->actingAs($admin)->post(route('admin.licenses.storage', $key), ['extra_storage_gb' => 4]);
        $this->actingAs($admin)->post(route('admin.licenses.storage', $key), ['extra_storage_gb' => 4]);

        $this->assertSame(1, LicenseActivity::where('license_id', $key->id)->where('action', LicenseActivity::ACTION_STORAGE_CHANGED)->count());
    }

    public function test_the_admin_can_take_space_back_to_the_plan_base(): void
    {
        $key = $this->brainxKey(['extra_storage_gb' => 12]);

        $this->actingAs($this->admin())
            ->post(route('admin.licenses.storage', $key), ['extra_storage_gb' => 0])
            ->assertRedirect();

        $this->assertSame(0, $key->fresh()->extra_storage_gb);
        $this->getJson("/api/v1/product/brainx/status/{$key->license_key}")
            ->assertJsonPath('data.cloud_storage_gb', 4);
    }

    public function test_out_of_range_or_junk_space_is_refused_and_nothing_changes(): void
    {
        $admin = $this->admin();
        $key = $this->brainxKey(['extra_storage_gb' => 4]);

        foreach ([-1, 1025, 2.5, 'lots', '', null] as $bad) {
            $this->actingAs($admin)
                ->from(route('admin.licenses.show', $key))
                ->post(route('admin.licenses.storage', $key), ['extra_storage_gb' => $bad])
                ->assertSessionHasErrors('extra_storage_gb');
        }

        $this->assertSame(4, $key->fresh()->extra_storage_gb);
        $this->assertSame(0, LicenseActivity::where('action', LicenseActivity::ACTION_STORAGE_CHANGED)->count());
    }

    public function test_a_product_without_cloud_space_has_no_storage_action(): void
    {
        $category = Category::firstOrCreate(['slug' => 'software'], ['name' => 'Software', 'description' => 'x']);
        $other = Product::create([
            'category_id' => $category->id, 'name' => 'Other app', 'slug' => 'other-app',
            'description' => 'x', 'price' => 100, 'requires_license' => true, 'is_active' => true,
        ]);
        $key = LicenseKey::create([
            'product_id' => $other->id, 'license_key' => LicenseKey::generateKey(), 'license_type' => 'yearly',
            'status' => 'active', 'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.licenses.storage', $key), ['extra_storage_gb' => 10])
            ->assertNotFound();
        $this->assertSame(0, $key->fresh()->extra_storage_gb);
    }

    public function test_a_customer_cannot_change_cloud_space(): void
    {
        $customer = User::factory()->create();
        $key = $this->brainxKey(['user_id' => $customer->id]);

        $this->actingAs($customer)
            ->post(route('admin.licenses.storage', $key), ['extra_storage_gb' => 100]);

        $this->assertSame(0, $key->fresh()->extra_storage_gb);
    }

    public function test_the_customer_sees_their_cloud_space_on_their_license_page(): void
    {
        $customer = User::factory()->create();
        $key = $this->brainxKey(['user_id' => $customer->id, 'extra_storage_gb' => 12]);

        $this->actingAs($customer)
            ->get(route('customer.licenses.show', $key))
            ->assertOk()
            ->assertSee('16 GB');
    }
}
