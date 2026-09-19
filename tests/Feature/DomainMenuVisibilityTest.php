<?php

namespace Tests\Feature;

use App\Models\DomainTld;
use App\Models\User;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The domain shop has to be reachable from the front page.
 *
 * It was built, deployed and priced while being linked from nowhere a visitor
 * would look: the orbital menu on the home page listed eight destinations and
 * domains was not one of them, and neither the footer nor the platforms section
 * mentioned it. A shop nobody can find sells nothing.
 */
class DomainMenuVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The nav caches its lists under fixed keys, and the cache store is not
        // rolled back between tests the way the database is.
        Cache::flush();

        // Pin the theme: it decides which LAYOUT renders, and each layout
        // carries a different menu. Production runs nova, whose home page is
        // home-nova.blade.php — assert against any other theme and this file
        // would be green while the live front page linked nowhere.
        ThemeService::setTheme('nova');
        Cache::forget('site_theme');

        // Without an admin the home page redirects to the setup wizard, and
        // every assertion below would pass on a 302 that rendered no menu.
        User::factory()->create(['role' => 'admin']);

        // The nav is rendered regardless, but the shop pages need something
        // sellable to list.
        DomainTld::updateOrCreate(['tld' => 'com'], [
            'item_id_register' => 'test-domain-com-thb-1y',
            'cost_usd_cents' => 31900,
            'cost_currency' => 'THB',
            'is_active' => true,
            'search_by_default' => true,
        ]);
    }

    public function test_the_home_page_links_to_the_domain_shop(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('domains.index'), false);
        $response->assertSee('จดโดเมน', false);
    }

    public function test_the_home_page_links_to_domain_pricing(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('domains.pricing'), false);
    }

    public function test_a_signed_in_customer_can_reach_their_own_domains_from_the_footer(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee(route('customer.domains.index'), false);
    }

    public function test_the_shop_pages_answer(): void
    {
        $this->get(route('domains.index'))->assertOk();
        $this->get(route('domains.pricing'))->assertOk();
    }
}
