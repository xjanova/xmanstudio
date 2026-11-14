<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * "Sign in with XMAN ID" for more than one app.
 *
 * aixman (X-DREAMER + ai.xman4289.com) is the original client and must keep working exactly as
 * it did — no client_id, no X-Sso-Client header. mlm.xman4289.com is the second client, with its
 * own secret and its own redirect allowlist, and a code minted for one never redeems for the other.
 */
class XmanIdSsoClientsTest extends TestCase
{
    use RefreshDatabase;

    private const AIXMAN_SECRET = 'aixman-secret-0123456789abcdef';

    private const AIXMAN_URI = 'https://ai.xman4289.com/api/auth/xman/callback';

    private const MLM_SECRET = 'mlm-secret-0123456789abcdef0123';

    private const MLM_URI = 'https://mlm.xman4289.com/auth/xman/callback';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.aixman.sso_secret' => self::AIXMAN_SECRET,
            'services.aixman.sso_redirect_uris' => ['xdreamer://auth/callback', self::AIXMAN_URI],
            'services.xman_id.clients.mlm' => ['secret' => self::MLM_SECRET, 'redirect_uris' => [self::MLM_URI]],
        ]);
    }

    /** @return array{0:string,1:string} [verifier, S256 challenge] */
    private function pkce(): array
    {
        $verifier = Str::random(64);

        return [$verifier, rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=')];
    }

    /** @return array{0:string,1:string,2:TestResponse} [code, verifier, response] */
    private function authorize(User $user, array $query, string $path = '/auth/sso/authorize'): array
    {
        [$verifier, $challenge] = $this->pkce();
        $response = $this->actingAs($user)->get($path . '?' . http_build_query($query + [
            'state' => 'st-123',
            'code_challenge' => $challenge,
        ]));

        $location = (string) $response->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $params);

        return [(string) ($params['code'] ?? ''), $verifier, $response];
    }

    private function exchange(string $code, string $verifier, array $headers): TestResponse
    {
        // withHeaders() accumulates across calls in one test: start clean every time.
        return $this->flushHeaders()->withHeaders($headers)->postJson('/api/v1/auth/sso/exchange', [
            'code' => $code,
            'code_verifier' => $verifier,
        ]);
    }

    public function test_aixman_logs_in_exactly_as_before_without_naming_a_client(): void
    {
        $user = User::factory()->create(['name' => 'คุณเอ']);

        // The original URL, no client_id — what X-DREAMER and ai.xman4289.com send today.
        [$code, $verifier, $response] = $this->authorize($user, ['redirect_uri' => self::AIXMAN_URI], '/auth/xdreamer/authorize');
        $response->assertRedirect();
        $this->assertStringStartsWith(self::AIXMAN_URI . '?', $response->headers->get('Location'));
        $this->assertStringContainsString('state=st-123', $response->headers->get('Location'));

        // No X-Sso-Client header either.
        $this->exchange($code, $verifier, ['X-Sso-Secret' => self::AIXMAN_SECRET])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonStructure(['success', 'user' => ['id', 'name', 'email', 'avatar', 'role']]);
    }

    public function test_aixman_can_also_name_itself_on_the_new_url(): void
    {
        $user = User::factory()->create();

        [$code, $verifier] = $this->authorize($user, ['client_id' => 'aixman', 'redirect_uri' => 'xdreamer://auth/callback']);

        $this->exchange($code, $verifier, ['X-Sso-Client' => 'aixman', 'X-Sso-Secret' => self::AIXMAN_SECRET])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_mlm_logs_in_with_its_own_client_and_secret(): void
    {
        $user = User::factory()->create();

        [$code, $verifier, $response] = $this->authorize($user, ['client_id' => 'mlm', 'redirect_uri' => self::MLM_URI]);
        $this->assertStringStartsWith(self::MLM_URI . '?', $response->headers->get('Location'));

        $this->exchange($code, $verifier, ['X-Sso-Client' => 'mlm', 'X-Sso-Secret' => self::MLM_SECRET])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', $user->name);
    }

    public function test_a_code_minted_for_mlm_does_not_redeem_for_aixman(): void
    {
        $user = User::factory()->create();
        [$code, $verifier] = $this->authorize($user, ['client_id' => 'mlm', 'redirect_uri' => self::MLM_URI]);

        $this->exchange($code, $verifier, ['X-Sso-Secret' => self::AIXMAN_SECRET])
            ->assertStatus(400)
            ->assertJsonPath('success', false);

        // And it is spent: the right client cannot use it afterwards either.
        $this->exchange($code, $verifier, ['X-Sso-Client' => 'mlm', 'X-Sso-Secret' => self::MLM_SECRET])
            ->assertStatus(400);
    }

    public function test_a_code_minted_for_aixman_does_not_redeem_for_mlm(): void
    {
        $user = User::factory()->create();
        [$code, $verifier] = $this->authorize($user, ['redirect_uri' => self::AIXMAN_URI]);

        $this->exchange($code, $verifier, ['X-Sso-Client' => 'mlm', 'X-Sso-Secret' => self::MLM_SECRET])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Code was not issued to this client');
    }

    public function test_a_redirect_uri_not_registered_for_the_client_is_refused(): void
    {
        $user = User::factory()->create();

        // Not on anyone's list.
        [, , $response] = $this->authorize($user, ['client_id' => 'mlm', 'redirect_uri' => 'https://evil.example/cb']);
        $response->assertStatus(400);

        // On aixman's list, but not mlm's: no borrowing another client's callback.
        [, , $response] = $this->authorize($user, ['client_id' => 'mlm', 'redirect_uri' => self::AIXMAN_URI]);
        $response->assertStatus(400);

        // And a trailing slash is a different URI.
        [, , $response] = $this->authorize($user, ['client_id' => 'mlm', 'redirect_uri' => self::MLM_URI . '/']);
        $response->assertStatus(400);
    }

    public function test_an_unknown_client_is_refused_on_both_ends(): void
    {
        $user = User::factory()->create();

        [, , $response] = $this->authorize($user, ['client_id' => 'nobody', 'redirect_uri' => self::MLM_URI]);
        $response->assertStatus(400);

        [, , $response] = $this->authorize($user, ['client_id' => 'services.aixman', 'redirect_uri' => self::AIXMAN_URI]);
        $response->assertStatus(400);

        [$code, $verifier] = $this->authorize($user, ['client_id' => 'mlm', 'redirect_uri' => self::MLM_URI]);
        $this->exchange($code, $verifier, ['X-Sso-Client' => 'nobody', 'X-Sso-Secret' => self::MLM_SECRET])
            ->assertStatus(401);
    }

    public function test_a_wrong_secret_is_401(): void
    {
        $user = User::factory()->create();
        [$code, $verifier] = $this->authorize($user, ['client_id' => 'mlm', 'redirect_uri' => self::MLM_URI]);

        // Another client's (valid) secret is still the wrong secret for mlm.
        $this->exchange($code, $verifier, ['X-Sso-Client' => 'mlm', 'X-Sso-Secret' => self::AIXMAN_SECRET])
            ->assertStatus(401);
        $this->exchange($code, $verifier, ['X-Sso-Client' => 'mlm'])
            ->assertStatus(401);
        $this->exchange($code, $verifier, ['X-Sso-Secret' => 'wrong'])
            ->assertStatus(401);
    }

    public function test_a_client_without_a_secret_fails_closed_without_touching_aixman(): void
    {
        config(['services.xman_id.clients.mlm.secret' => '']);
        $user = User::factory()->create();

        [$code, $verifier] = $this->authorize($user, ['client_id' => 'mlm', 'redirect_uri' => self::MLM_URI]);
        $this->exchange($code, $verifier, ['X-Sso-Client' => 'mlm', 'X-Sso-Secret' => ''])
            ->assertStatus(503);

        [$code, $verifier] = $this->authorize($user, ['redirect_uri' => self::AIXMAN_URI]);
        $this->exchange($code, $verifier, ['X-Sso-Secret' => self::AIXMAN_SECRET])->assertOk();
    }

    public function test_guests_are_sent_to_login_first(): void
    {
        [, $challenge] = $this->pkce();

        $this->get('/auth/sso/authorize?' . http_build_query([
            'client_id' => 'mlm', 'redirect_uri' => self::MLM_URI, 'state' => 's', 'code_challenge' => $challenge,
        ]))->assertRedirect(route('login'));
    }
}
