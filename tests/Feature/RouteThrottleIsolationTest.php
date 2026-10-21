<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A bare `throttle:N,M` is keyed on the user (or the IP) alone — the route is
 * not part of the key — so every such route used to share ONE counter: a
 * customer who pressed a few domain buttons was refused an unrelated one with
 * a lower limit. Each route now carries its own prefix; these tests pin that
 * a route's hits never spend another route's budget, while each route's own
 * limit still holds.
 */
class RouteThrottleIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Without an admin the setup wizard takes over every page.
        User::factory()->create(['role' => 'super_admin']);
    }

    public function test_a_logged_in_customer_is_not_refused_by_another_routes_hits(): void
    {
        $customer = User::factory()->create();

        // Five hits on one throttled route (5 per 10 minutes). The domain does
        // not exist — the counter is spent before the controller answers.
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($customer)
                ->post(route('customer.domains.auth-code', 999999))
                ->assertNotFound();
        }

        // Its own limit still holds…
        $this->actingAs($customer)
            ->post(route('customer.domains.auth-code', 999999))
            ->assertStatus(429);

        // …and a different route with the same limit is untouched by it.
        $this->actingAs($customer)
            ->post(route('customer.domains.renew', 999999))
            ->assertNotFound();
    }

    public function test_one_ip_is_not_refused_on_one_api_route_by_another_groups_hits(): void
    {
        // Ten hits on the app-login group (10 per minute per IP)…
        for ($i = 0; $i < 10; $i++) {
            $this->assertNotSame(429, $this->postJson('/api/v1/auth/login', [])->getStatusCode());
        }

        $this->postJson('/api/v1/auth/login', [])->assertStatus(429);

        // …must not spend the claim endpoint's much smaller budget (5 per minute).
        $this->assertNotSame(429, $this->postJson('/api/v1/product/gpuxmine/claim', [])->getStatusCode());
    }
}
