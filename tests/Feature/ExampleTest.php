<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * When no admin exists, homepage should redirect to setup.
     */
    public function test_homepage_redirects_to_setup_when_no_admin(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/setup');
    }

    /**
     * When admin exists, homepage should return successful response.
     */
    public function test_homepage_returns_successful_response_when_admin_exists(): void
    {
        // Create an admin user
        User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
