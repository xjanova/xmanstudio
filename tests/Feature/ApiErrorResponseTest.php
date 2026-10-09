<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the API answers when the caller got something wrong — as production
 * answers it, with debug off.
 *
 * The catch-all that hides internal errors from API callers also caught the
 * ordinary answers: "who are you?" (401), "these fields are wrong" (422) and a
 * named rate limiter's own "slow down" (429). On production all came back as a
 * bare 500, so an app could not tell a wrong password from a crashed server.
 * The local suite never saw it: APP_DEBUG is on here, and the catch-all only
 * runs with it off.
 */
class ApiErrorResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);
    }

    public function test_a_missing_token_is_a_401(): void
    {
        $this->getJson('/api/v1/auth/user')->assertStatus(401);
    }

    public function test_a_missing_token_is_json_even_without_an_accept_header(): void
    {
        $response = $this->get('/api/v1/auth/user');

        $response->assertStatus(401);
        $this->assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
    }

    public function test_bad_input_is_a_422_that_names_the_fields(): void
    {
        $this->postJson('/api/v1/auth/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_a_caller_over_a_named_rate_limit_gets_its_429_not_a_500(): void
    {
        // The api group's own limiter (60 a minute per address) answers with its own JSON,
        // which travels as an HttpResponseException — the catch-all used to turn it into a 500.
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/health')->assertOk();
        }

        $this->getJson('/api/health')
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'RATE_LIMIT_EXCEEDED');
    }

    public function test_a_wrong_password_is_a_422_not_a_crash(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
            'device_name' => 'phone',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }
}
