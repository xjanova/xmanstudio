<?php

namespace Tests\Feature;

use App\Models\VpnGameProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Rate limits on the LocalVPN API and the public game-profile list.
 *
 * Players behind one café router or a Thai ISP's CGNAT share an address; the
 * old 120/min per IP (and throttle:api's 60/min per IP on top) answered their
 * heartbeats and signal polls with 429. The budget is now per device.
 */
class LocalVpnFleetApiTest extends TestCase
{
    use RefreshDatabase;

    private function middlewareFor(string $method, string $uri): array
    {
        return Route::getRoutes()->match(Request::create($uri, $method))->gatherMiddleware();
    }

    public function test_localvpn_routes_use_the_per_device_limiter_not_the_per_ip_ones(): void
    {
        foreach ([['POST', '/api/v1/localvpn/heartbeat'], ['POST', '/api/v1/localvpn/signal/poll'], ['GET', '/api/v1/localvpn/game-profiles']] as [$method, $uri]) {
            $middleware = $this->middlewareFor($method, $uri);
            $this->assertContains('throttle:localvpn', $middleware, $uri);
            $this->assertNotContains('throttle:api', $middleware, $uri);
            $this->assertNotContains('throttle:120,1,api-localvpn', $middleware, $uri);
        }

        $sync = $this->middlewareFor('POST', '/api/v1/localvpn/node/sync');
        $this->assertContains('throttle:localvpn-node', $sync);
        $this->assertNotContains('throttle:localvpn', $sync);
        $this->assertNotContains('throttle:api', $sync);
    }

    public function test_each_device_behind_one_address_keeps_its_own_budget(): void
    {
        // The retired HTTP relay answers 410 without touching the database: a cheap target.
        $hit = fn (array $body = [], array $headers = []) => $this->postJson('/api/v1/localvpn/relay', $body, $headers);

        for ($i = 0; $i < 240; $i++) {
            $hit(['machine_id' => 'noisy-pc'])->assertStatus(410);
        }
        $hit(['machine_id' => 'noisy-pc'])
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'RATE_LIMIT_EXCEEDED')
            ->assertHeader('Retry-After');

        // Others in the same café — by body, query-less header, or the old relay's field — still get through.
        $hit(['machine_id' => 'quiet-pc'])->assertStatus(410);
        $hit([], ['X-Device-Id' => 'phone-1'])->assertStatus(410);
        $hit(['source_machine_id' => 'old-app'])->assertStatus(410);
    }

    public function test_the_game_profile_list_is_public_cached_and_only_enabled(): void
    {
        $this->getJson('/api/v1/localvpn/game-profiles')->assertOk()->assertExactJson(['profiles' => []]);

        VpnGameProfile::create(['name' => 'RoV', 'slug' => 'rov', 'region' => 'SEA', 'cidrs' => "# Garena\n43.229.64.0/18\n103.6.0.0/16", 'sort' => 2]);
        VpnGameProfile::create(['name' => 'Valorant', 'slug' => 'valorant', 'region' => 'SG', 'cidrs' => '43.229.65.0/24', 'sort' => 1]);
        VpnGameProfile::create(['name' => 'Hidden', 'slug' => 'hidden', 'cidrs' => '8.8.8.0/24', 'enabled' => false]);

        $this->getJson('/api/v1/localvpn/game-profiles')
            ->assertOk()
            ->assertHeader('Cache-Control')
            ->assertExactJson(['profiles' => [
                ['slug' => 'valorant', 'name' => 'Valorant', 'region' => 'SG', 'cidrs' => ['43.229.65.0/24']],
                ['slug' => 'rov', 'name' => 'RoV', 'region' => 'SEA', 'cidrs' => ['43.229.64.0/18', '103.6.0.0/16']],
            ]]);
    }
}
