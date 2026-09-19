<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use App\Models\Setting;
use App\Support\Turnstile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The contact form sat unprotected on production for months while the admin
 * page said Turnstile was on.
 *
 * Every section was written to `settings` on 2026-03-08. The contact toggle
 * shipped after that, so its row never existed, and both readers treated a
 * missing row as "off" — the middleware waved POSTs through and the widget was
 * never drawn. Nothing anywhere said so.
 *
 * These tests pin the two properties that make that impossible to repeat: a
 * missing row means ON, and the middleware and the widget always agree.
 */
class TurnstileGateTest extends TestCase
{
    use RefreshDatabase;

    protected function configureTurnstile(): void
    {
        Setting::setValue('turnstile_enabled', '1', 'boolean');
        Setting::setValue('turnstile_site_key', '0xTEST_SITE_KEY', 'string');
        Setting::setValue('turnstile_secret_key', '0xTEST_SECRET_KEY', 'string');
    }

    /**
     * @return array<string, string>
     */
    protected function contactPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'สมชาย ใจดี',
            'email' => 'somchai@example.com',
            'subject' => 'สอบถามการทำเว็บไซต์',
            'message' => 'อยากได้เว็บไซต์บริษัท ต้องการใบเสนอราคาครับ',
        ], $overrides);
    }

    public function test_a_section_with_no_stored_row_is_protected(): void
    {
        $this->configureTurnstile();

        // Nobody has ever saved turnstile_contact. That is the exact production
        // state that let the spam through.
        $this->assertNull(Setting::where('key', 'turnstile_contact')->first());
        $this->assertTrue(Turnstile::enabledFor('contact'));
    }

    public function test_a_section_turned_off_on_purpose_stays_off(): void
    {
        $this->configureTurnstile();
        Setting::setValue('turnstile_contact', '0', 'boolean');

        $this->assertFalse(Turnstile::enabledFor('contact'));
    }

    public function test_nothing_is_enforced_while_the_master_switch_is_off(): void
    {
        Setting::setValue('turnstile_enabled', '0', 'boolean');
        Setting::setValue('turnstile_site_key', '0xTEST_SITE_KEY', 'string');
        Setting::setValue('turnstile_secret_key', '0xTEST_SECRET_KEY', 'string');

        $this->assertFalse(Turnstile::enabledFor('contact'));
    }

    public function test_a_half_configured_install_does_not_lock_visitors_out(): void
    {
        // Switch on, site key filled in, secret still blank: verification could
        // never succeed, so demanding a token would close every form on the site.
        Setting::setValue('turnstile_enabled', '1', 'boolean');
        Setting::setValue('turnstile_site_key', '0xTEST_SITE_KEY', 'string');
        Setting::setValue('turnstile_secret_key', '', 'string');

        $this->assertFalse(Turnstile::enabledFor('contact'));
    }

    public function test_the_contact_form_rejects_a_post_with_no_token(): void
    {
        Mail::fake();
        $this->configureTurnstile();
        Setting::setValue('contact_email', 'team@xman4289.com');

        $this->post('/contact', $this->contactPayload())
            ->assertSessionHasErrors('cf-turnstile-response');

        Mail::assertNothingSent();
    }

    public function test_the_contact_form_accepts_a_post_cloudflare_verifies(): void
    {
        Mail::fake();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);
        $this->configureTurnstile();
        Setting::setValue('contact_email', 'team@xman4289.com');

        $this->post('/contact', $this->contactPayload(['cf-turnstile-response' => 'solved-token']))
            ->assertSessionHas('contact_success');

        Mail::assertSent(ContactMessageMail::class);
    }

    public function test_the_contact_form_rejects_a_token_cloudflare_refuses(): void
    {
        Mail::fake();
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);
        $this->configureTurnstile();
        Setting::setValue('contact_email', 'team@xman4289.com');

        $this->post('/contact', $this->contactPayload(['cf-turnstile-response' => 'replayed-token']))
            ->assertSessionHasErrors('cf-turnstile-response');

        Mail::assertNothingSent();
    }

    /**
     * The invariant that matters most.
     *
     * If the middleware demands a token the page never rendered, the form is
     * dead for everyone — a worse outcome than the spam this all started with.
     */
    public function test_the_widget_is_rendered_exactly_when_the_gate_is_armed(): void
    {
        $this->configureTurnstile();

        $this->assertTrue(Turnstile::enabledFor('contact'), 'precondition');
        $this->get('/contact')
            ->assertOk()
            ->assertSee('cf-turnstile')
            ->assertSee('0xTEST_SITE_KEY');

        Setting::setValue('turnstile_contact', '0', 'boolean');

        $this->assertFalse(Turnstile::enabledFor('contact'));
        $this->get('/contact')
            ->assertOk()
            ->assertDontSee('cf-turnstile');
    }

    public function test_every_section_the_middleware_knows_about_is_listed_for_the_admin_page(): void
    {
        // A section used in routes/web.php but missing from SECTIONS would have
        // no toggle on the admin page, so it could never be turned off again.
        $routed = [];
        foreach (app('router')->getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (is_string($middleware) && str_starts_with($middleware, 'turnstile:')) {
                    $routed[] = substr($middleware, strlen('turnstile:'));
                }
            }
        }

        $this->assertNotEmpty($routed, 'no route uses the turnstile middleware any more');

        foreach (array_unique($routed) as $section) {
            $this->assertContains($section, Turnstile::SECTIONS, "section '{$section}' has no admin toggle");
        }
    }
}
