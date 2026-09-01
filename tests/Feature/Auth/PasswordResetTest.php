<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) {
            $response = $this->get('/reset-password/' . $notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    /**
     * The form must not tell an anonymous visitor whether an address is
     * registered — otherwise it is a membership oracle for the customer list.
     */
    public function test_unknown_email_gets_the_same_answer_as_a_known_one(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $known = $this->post('/forgot-password', ['email' => $user->email]);
        $unknown = $this->post('/forgot-password', ['email' => 'nobody-here@example.invalid']);

        $known->assertSessionHasNoErrors()->assertSessionHas('status');
        $unknown->assertSessionHasNoErrors()->assertSessionHas('status');

        $this->assertSame(
            session()->get('status'),
            $known->getSession()->get('status'),
            'A missing account must produce the identical status message.'
        );

        // ...and no mail is actually sent for the address that does not exist.
        Notification::assertSentTimes(ResetPasswordNotification::class, 1);
    }

    public function test_reset_link_requests_are_rate_limited(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        // The route allows 5 per minute per IP; the 6th must be turned away.
        for ($i = 0; $i < 5; $i++) {
            $this->post('/forgot-password', ['email' => $user->email])->assertStatus(302);
        }

        $this->post('/forgot-password', ['email' => $user->email])->assertStatus(429);
    }
}
