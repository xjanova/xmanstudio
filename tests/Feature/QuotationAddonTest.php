<?php

namespace Tests\Feature;

use App\Http\Controllers\QuotationController;
use App\Models\QuotationCategory;
use App\Models\QuotationOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The add-on groups are seeded by migration, so these run against the real
 * seeded rows rather than fixtures — that is also what verifies the seed.
 */
class QuotationAddonTest extends TestCase
{
    use RefreshDatabase;

    protected function makeServiceCategory(string $key, array $options = []): QuotationCategory
    {
        $category = QuotationCategory::create([
            'key' => $key,
            'type' => QuotationCategory::TYPE_SERVICE,
            'name' => ucfirst($key),
            'name_th' => 'หมวด ' . $key,
            'icon' => '🧩',
            'order' => 1,
            'is_active' => true,
        ]);

        foreach ($options as $optionKey => $option) {
            QuotationOption::create([
                'quotation_category_id' => $category->id,
                'key' => $optionKey,
                'name' => $option['name'],
                'name_th' => $option['name_th'],
                'price' => $option['price'],
                'order' => 1,
                'is_active' => true,
            ]);
        }

        return $category;
    }

    protected function callProtected(string $method): mixed
    {
        $reflection = new ReflectionMethod(QuotationController::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke(app(QuotationController::class));
    }

    public function test_the_migration_seeds_all_five_addon_groups(): void
    {
        $this->assertEqualsCanonicalizing(
            ['support', 'delivery', 'hosting', 'design', 'seo_marketing'],
            QuotationCategory::addons()->pluck('key')->all(),
        );

        $this->assertSame(30, QuotationOption::whereHas('category', fn ($q) => $q->addons())->count());
    }

    public function test_addon_groups_come_from_the_database_with_their_icons(): void
    {
        $groups = $this->callProtected('addonGroups');

        $this->assertSame('Hosting และโดเมน', $groups['hosting']['name_th']);
        $this->assertSame('☁️', $groups['hosting']['icon']);
        $this->assertSame(
            ['name' => 'SSL Certificate/Year', 'name_th' => 'ใบรับรอง SSL/ปี', 'price' => 3000.0, 'icon' => '🔐'],
            $groups['hosting']['options']['ssl'],
        );
    }

    public function test_an_admin_price_edit_is_what_the_quotation_uses(): void
    {
        // The whole point of the move to the database: no deploy needed.
        QuotationCategory::addons()->where('key', 'hosting')->firstOrFail()
            ->options()->where('key', 'ssl')->update(['price' => 4500]);

        $this->assertSame(4500.0, $this->callProtected('addonGroups')['hosting']['options']['ssl']['price']);
    }

    public function test_a_deactivated_addon_disappears_from_the_form(): void
    {
        QuotationCategory::addons()->where('key', 'hosting')->firstOrFail()
            ->options()->where('key', 'ssl')->update(['is_active' => false]);

        $this->assertArrayNotHasKey('ssl', $this->callProtected('addonGroups')['hosting']['options']);
    }

    public function test_it_falls_back_to_the_hard_coded_groups_when_none_are_seeded(): void
    {
        QuotationCategory::addons()->delete();

        // The quotation form must keep working on a database that never ran the
        // seed migration, so the fallback still covers all five groups.
        $this->assertEqualsCanonicalizing(
            ['support', 'delivery', 'hosting', 'design', 'seo_marketing'],
            array_keys($this->callProtected('addonGroups')),
        );
    }

    public function test_an_addon_group_is_not_a_valid_service_type(): void
    {
        // Otherwise a visitor could post service_type=hosting and price a
        // quotation against an add-on group.
        $keys = $this->callProtected('getAllServiceTypeKeys');

        foreach (QuotationCategory::addons()->pluck('key') as $addonKey) {
            $this->assertNotContains($addonKey, $keys);
        }
    }

    public function test_the_public_services_json_separates_services_from_addons(): void
    {
        $this->makeServiceCategory('web_development', [
            'web_landing' => ['name' => 'Landing Page', 'name_th' => 'Landing Page', 'price' => 15000],
        ]);

        $response = $this->getJson('/quotation/services')->assertOk();

        $this->assertSame(['web_development'], array_keys($response->json('services')));
        $this->assertArrayNotHasKey('hosting', $response->json('services'));
        $this->assertArrayHasKey('hosting', $response->json('additional_options'));
    }
}
