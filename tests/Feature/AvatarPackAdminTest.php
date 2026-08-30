<?php

namespace Tests\Feature;

use App\Models\AvatarPack;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * The admin screens for avatar packs.
 *
 * These also stand in for a Blade compile check: a view that will not
 * compile, or a route() name that does not exist, fails here rather than
 * the first time an admin opens the page.
 */
class AvatarPackAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['packs.disk' => 'local']);

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /**
     * A zip shaped the way the app expects one.
     */
    protected function zipWithPackId(?string $id, bool $nested = false): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'pack') . '.zip';

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($id !== null) {
            $name = $nested ? 'wrapper/pack.json' : 'pack.json';
            $zip->addFromString($name, json_encode(['id' => $id, 'model' => 'x.vrm']));
        }

        $zip->addFromString($nested ? 'wrapper/x.vrm' : 'x.vrm', 'vrm-bytes');
        $zip->close();

        return new UploadedFile($path, 'pack.zip', 'application/zip', null, true);
    }

    protected function createPack(array $overrides = []): AvatarPack
    {
        $this->actingAs($this->admin)->post(route('admin.packs.store'), array_merge([
            'pack_id' => 'nana-office',
            'name_th' => 'นานา ชุดออฟฟิศ',
            'name_en' => 'Nana office',
            'kind' => AvatarPack::KIND_CHARACTER,
            'price' => 149,
            'is_active' => '1',
        ], $overrides));

        return AvatarPack::firstWhere('pack_id', $overrides['pack_id'] ?? 'nana-office');
    }

    public function test_the_pack_screens_are_admin_only(): void
    {
        $this->get(route('admin.packs.index'))->assertRedirect();

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('admin.packs.index'))
            ->assertForbidden();
    }

    public function test_the_list_and_form_render(): void
    {
        $pack = $this->createPack();

        $this->actingAs($this->admin)->get(route('admin.packs.index'))
            ->assertOk()
            ->assertSee('nana-office');

        $this->actingAs($this->admin)->get(route('admin.packs.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.packs.edit', $pack))->assertOk();
    }

    /**
     * One form creates all three rows an admin should not have to know about.
     */
    public function test_creating_a_pack_creates_the_product_behind_it(): void
    {
        $pack = $this->createPack();

        $this->assertNotNull($pack);
        $this->assertSame('นานา ชุดออฟฟิศ', $pack->product->name);
        $this->assertSame('149.00', $pack->product->price);
        $this->assertTrue($pack->product->is_active);
        $this->assertSame('giggok-packs', $pack->product->category->slug);
    }

    public function test_a_pack_id_the_app_cannot_use_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.packs.store'), [
                'pack_id' => 'Nana Office/../etc',
                'name_th' => 'x',
                'kind' => AvatarPack::KIND_CHARACTER,
                'price' => 10,
            ])
            ->assertSessionHasErrors('pack_id');

        $this->assertDatabaseCount('avatar_packs', 0);
    }

    public function test_two_packs_cannot_share_an_id(): void
    {
        $this->createPack();

        $this->actingAs($this->admin)
            ->post(route('admin.packs.store'), [
                'pack_id' => 'nana-office',
                'name_th' => 'ซ้ำ',
                'kind' => AvatarPack::KIND_OUTFIT,
                'price' => 10,
            ])
            ->assertSessionHasErrors('pack_id');
    }

    public function test_an_outfit_cannot_require_itself(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.packs.store'), [
                'pack_id' => 'loop',
                'name_th' => 'วน',
                'kind' => AvatarPack::KIND_OUTFIT,
                'requires' => 'loop',
                'price' => 10,
            ])
            ->assertSessionHasErrors('requires');
    }

    /**
     * A prerequisite only means something for an outfit; carrying one on a
     * standalone character would have the app hunt for a pack nobody sells.
     */
    public function test_a_prerequisite_is_dropped_for_a_character(): void
    {
        $pack = $this->createPack(['kind' => AvatarPack::KIND_CHARACTER, 'requires' => 'something']);

        $this->assertNull($pack->requires);
    }

    public function test_uploading_a_zip_records_its_size_and_hash(): void
    {
        $pack = $this->createPack();

        $this->actingAs($this->admin)
            ->post(route('admin.packs.file', $pack), ['file' => $this->zipWithPackId('nana-office')])
            ->assertSessionHasNoErrors();

        $version = $pack->product->fresh()->latestVersion();

        $this->assertNotNull($version->storage_path);
        $this->assertSame(64, strlen($version->sha256));
        $this->assertGreaterThan(0, $version->file_size);
        Storage::disk('local')->assertExists($version->storage_path);
    }

    /**
     * The id inside the zip and the id being sold are separate things that
     * must agree. If they drift, the app installs a pack it then cannot
     * match to the purchase - and nothing anywhere reports a problem.
     */
    public function test_a_zip_whose_id_does_not_match_is_refused(): void
    {
        $pack = $this->createPack();

        $this->actingAs($this->admin)
            ->post(route('admin.packs.file', $pack), ['file' => $this->zipWithPackId('someone-else')])
            ->assertSessionHasErrors('file');

        $this->assertNull($pack->product->fresh()->latestVersion());
    }

    public function test_a_zip_with_no_pack_json_is_refused(): void
    {
        $pack = $this->createPack();

        $this->actingAs($this->admin)
            ->post(route('admin.packs.file', $pack), ['file' => $this->zipWithPackId(null)])
            ->assertSessionHasErrors('file');
    }

    /**
     * Right-clicking a folder on Windows wraps everything one level deep.
     * The app unwraps that, so refusing it here would reject a pack that
     * works perfectly well.
     */
    public function test_a_zip_wrapped_in_one_folder_is_accepted(): void
    {
        $pack = $this->createPack();

        $this->actingAs($this->admin)
            ->post(route('admin.packs.file', $pack), [
                'file' => $this->zipWithPackId('nana-office', nested: true),
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($pack->product->fresh()->latestVersion());
    }

    /**
     * The app has no version picker, so only the newest upload may be live.
     */
    public function test_uploading_again_retires_the_previous_version(): void
    {
        $pack = $this->createPack();

        $this->actingAs($this->admin)->post(route('admin.packs.file', $pack), [
            'file' => $this->zipWithPackId('nana-office'), 'version' => '1.0.0',
        ]);
        $this->actingAs($this->admin)->post(route('admin.packs.file', $pack), [
            'file' => $this->zipWithPackId('nana-office'), 'version' => '2.0.0',
        ]);

        $active = $pack->product->versions()->where('is_active', true)->get();

        $this->assertCount(1, $active);
        $this->assertSame('2.0.0', $active->first()->version);
    }

    public function test_toggling_takes_a_pack_off_sale(): void
    {
        $pack = $this->createPack();

        $this->actingAs($this->admin)->post(route('admin.packs.toggle', $pack));

        $this->assertFalse($pack->fresh()->is_active);
    }

    /**
     * Deleting a sold pack would leave paying customers with a license
     * pointing at a product that no longer exists.
     */
    public function test_a_pack_someone_bought_cannot_be_deleted(): void
    {
        $pack = $this->createPack();
        LicenseKey::create([
            'product_id' => $pack->product_id,
            'user_id' => User::factory()->create()->id,
            'license_key' => 'SOLD-1',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)->delete(route('admin.packs.destroy', $pack));

        $this->assertDatabaseHas('avatar_packs', ['id' => $pack->id]);
    }

    public function test_an_unsold_pack_is_deleted_with_its_product(): void
    {
        $pack = $this->createPack();
        $productId = $pack->product_id;

        $this->actingAs($this->admin)->delete(route('admin.packs.destroy', $pack));

        $this->assertDatabaseMissing('avatar_packs', ['id' => $pack->id]);
        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }

    public function test_editing_updates_both_the_pack_and_its_product(): void
    {
        $pack = $this->createPack();

        $this->actingAs($this->admin)->put(route('admin.packs.update', $pack), [
            'pack_id' => 'nana-office',
            'name_th' => 'นานา ชุดใหม่',
            'name_en' => 'Nana new',
            'kind' => AvatarPack::KIND_OUTFIT,
            'requires' => 'mind-default',
            'price' => 99,
            'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $pack->refresh();

        $this->assertSame('นานา ชุดใหม่', $pack->product->name);
        $this->assertSame('99.00', $pack->product->price);
        $this->assertSame(AvatarPack::KIND_OUTFIT, $pack->kind);
        $this->assertSame('mind-default', $pack->requires);
    }

    /**
     * Pack ids allow characters a product slug does not, so two different
     * ids can slug to the same string - "nana-office" and "nana_office"
     * both become "pack-nana-office". The second must not collide with the
     * first, or creating it dies on a unique constraint.
     */
    public function test_two_ids_that_slug_the_same_still_get_separate_products(): void
    {
        $first = $this->createPack(['pack_id' => 'nana-office']);
        $second = $this->createPack(['pack_id' => 'nana_office']);

        $this->assertNotNull($second, 'the second pack was not created at all');
        $this->assertNotSame($first->product->slug, $second->product->slug);
        $this->assertSame(2, Product::where('slug', 'like', 'pack-nana-office%')->count());
    }
}
