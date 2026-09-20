<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Auth\SocialAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * "Sign in with Google / LINE / Telegram" — the shared half.
 *
 * Every provider hands SocialAuth a profile and lets it decide who that is.
 * Three of these tests exist because the LINE flow, which used to own this
 * logic alone, got the answer wrong:
 *
 *   — it matched an existing account on an email it had not verified, so
 *     putting a victim's address in a provider profile took over their orders,
 *     wallet and licences;
 *   — it let a disabled account sign in, so banning someone only closed the
 *     door they were least likely to use;
 *   — it never regenerated the session id.
 *
 * The fourth rule is the one that bites afterwards: never leave an account
 * with no way back in.
 */
class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    // ───────────────────────────────────────────── the takeover

    public function test_an_unverified_email_never_attaches_to_an_existing_account(): void
    {
        $victim = $this->victim();

        SocialAuth::login('google', 'attacker-google-id', [
            'name' => 'Not The Victim',
            'email' => $victim->email,
            'email_verified' => false,
        ]);

        $this->assertNull($victim->fresh()->google_id,
            'an unverified address must not be enough to claim somebody else’s account');

        $this->assertNotSame($victim->id, Auth::id(),
            'the attacker must end up in their own new account, not the victim’s');
    }

    public function test_a_verified_email_does_attach(): void
    {
        $victim = $this->victim();

        SocialAuth::login('google', 'real-google-id', [
            'name' => $victim->name,
            'email' => $victim->email,
            'email_verified' => true,
        ]);

        $this->assertSame('real-google-id', $victim->fresh()->google_id);
        $this->assertSame($victim->id, Auth::id());
    }

    /** Telegram sends no address at all, so it can never match by one. */
    public function test_telegram_gets_a_placeholder_address_and_matches_nobody(): void
    {
        $victim = $this->victim();

        SocialAuth::login('telegram', 'tg-1', [
            'name' => 'Nok',
            'email' => null,
            'email_verified' => false,
            'extra' => ['username' => 'nok'],
        ]);

        $this->assertNull($victim->fresh()->telegram_id);

        $created = User::where('telegram_id', 'tg-1')->firstOrFail();

        $this->assertNotSame($victim->email, $created->email);
        $this->assertNull($created->email_verified_at,
            'a made-up address must never be marked verified');
        $this->assertStringContainsString('@no-reply.', $created->email,
            'the placeholder has to be undeliverable and un-registerable elsewhere');
    }

    // ───────────────────────────────────────────── the disabled account

    public function test_a_disabled_account_cannot_sign_in_through_a_provider(): void
    {
        $banned = $this->victim(['is_active' => false, 'google_id' => 'banned-google-id']);

        SocialAuth::login('google', 'banned-google-id', [
            'email' => $banned->email,
            'email_verified' => true,
        ]);

        // Being banned has to mean banned everywhere, not just on the password form.
        $this->assertGuest();
    }

    public function test_a_disabled_account_cannot_be_reached_by_email_either(): void
    {
        $banned = $this->victim(['is_active' => false]);

        SocialAuth::login('google', 'some-google-id', [
            'email' => $banned->email,
            'email_verified' => true,
        ]);

        $this->assertGuest();
        $this->assertNull($banned->fresh()->google_id);
    }

    // ───────────────────────────────────────────── the session

    public function test_signing_in_regenerates_the_session_id(): void
    {
        // The store has to be on the request SocialAuth reads, not just on the
        // test's own — request()->hasSession() is what it checks.
        $this->startSession();
        request()->setLaravelSession(app('session.store'));

        $before = session()->getId();

        SocialAuth::login('google', 'fresh-google-id', [
            'name' => 'New Person',
            'email' => 'new.person@example.com',
            'email_verified' => true,
        ]);

        $this->assertNotSame($before, session()->getId(),
            'a sign-in must not keep the session id the visitor arrived holding');
    }

    /**
     * The guard around that regenerate: without a session bound, signing in
     * used to throw — after Auth::login had already succeeded, so the visitor
     * was in and looking at a 500.
     */
    public function test_signing_in_without_a_session_does_not_throw(): void
    {
        $response = SocialAuth::login('google', 'no-session-id', [
            'name' => 'No Session',
            'email' => 'no.session@example.com',
            'email_verified' => true,
        ]);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertAuthenticated();
    }

    // ───────────────────────────────────────────── the way back in

    public function test_a_social_only_account_cannot_unlink_its_last_provider(): void
    {
        $user = $this->victim(['password_set_at' => null, 'google_id' => 'only-way-in']);

        $this->assertFalse(SocialAuth::canUnlink($user, 'google'));

        SocialAuth::unlink($user, 'google');

        $this->assertSame('only-way-in', $user->fresh()->google_id,
            'removing the only way in has to be refused, not merely discouraged');
    }

    public function test_a_second_provider_is_a_way_back_in(): void
    {
        $user = $this->victim([
            'password_set_at' => null,
            'google_id' => 'g-1',
            'telegram_id' => 'tg-1',
        ]);

        $this->assertTrue(SocialAuth::canUnlink($user, 'google'));

        SocialAuth::unlink($user, 'google');

        $this->assertNull($user->fresh()->google_id);
        $this->assertSame('tg-1', $user->fresh()->telegram_id);
    }

    public function test_a_password_is_a_way_back_in(): void
    {
        $user = $this->victim(['password_set_at' => now(), 'google_id' => 'g-1']);

        $this->assertTrue(SocialAuth::canUnlink($user, 'google'));

        SocialAuth::unlink($user, 'google');

        $this->assertNull($user->fresh()->google_id);
    }

    /**
     * password is never empty — social signups get a random one — so the
     * column cannot answer "does a human know it". password_set_at can.
     */
    public function test_a_random_password_does_not_count_as_a_way_back_in(): void
    {
        $user = $this->victim(['password_set_at' => null, 'google_id' => 'g-1']);

        $this->assertNotEmpty($user->password);
        $this->assertFalse(SocialAuth::canUnlink($user, 'google'));
    }

    // ───────────────────────────────────────────── linking

    public function test_a_provider_already_on_another_account_cannot_be_linked(): void
    {
        $owner = $this->victim(['google_id' => 'contested-id']);
        $other = $this->victim(['email' => 'other@example.com']);

        SocialAuth::link($other, 'google', 'contested-id', ['email_verified' => false]);

        $this->assertNull($other->fresh()->google_id);
        $this->assertSame('contested-id', $owner->fresh()->google_id);
    }

    public function test_linking_the_same_provider_twice_is_harmless(): void
    {
        $user = $this->victim(['google_id' => 'g-1']);

        SocialAuth::link($user, 'google', 'g-1', ['avatar' => 'https://example.test/a.png']);

        $this->assertSame('g-1', $user->fresh()->google_id);
        $this->assertSame('https://example.test/a.png', $user->fresh()->google_avatar);
    }

    // ───────────────────────────────────────────── returning

    public function test_a_returning_user_is_matched_on_the_provider_id_not_the_email(): void
    {
        $user = $this->victim(['google_id' => 'stable-id', 'email' => 'old@example.com']);

        // They changed their address at Google since last time.
        SocialAuth::login('google', 'stable-id', [
            'email' => 'brand.new@example.com',
            'email_verified' => true,
        ]);

        $this->assertSame($user->id, Auth::id());
        $this->assertSame('old@example.com', $user->fresh()->email,
            'the provider does not get to rewrite the address we invoice');
    }

    private function victim(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Victim',
            'email' => 'victim' . User::count() . '@example.com',
            'password' => bcrypt('secret-password'),
            'password_set_at' => now(),
            'is_active' => true,
            'role' => 'user',
        ], $overrides));
    }
}
