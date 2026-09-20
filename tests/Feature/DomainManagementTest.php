<?php

namespace Tests\Feature;

use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The pages a customer uses after the domain is theirs.
 *
 * Nobody has bought one on production yet, so none of this had ever been
 * exercised against a real record — only the purchase and renewal maths had
 * tests. These cover the management screens themselves: that they render,
 * that one customer cannot reach another's domain, and that the promise
 * printed on them tracks the setting instead of a number typed into the view.
 */
class DomainManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected DomainRegistration $domain;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('hostinger_api_token', 'test-token');
        Setting::setValue('domain_margin_percent', 45);
        Setting::setValue('domain_price_rounding', 10);
        Cache::flush();

        Http::fake(['*' => Http::response([], 200)]);

        DomainTld::updateOrCreate(['tld' => 'com'], [
            'item_id_register' => 'test-domain-com-thb-1y',
            'item_id_renew' => 'test-domain-com-thb-1y',
            'cost_usd_cents' => 31900,
            'renew_cost_usd_cents' => 51900,
            'cost_currency' => 'THB',
            'is_active' => true,
        ]);

        $this->owner = $this->member('owner@example.com');
        $this->domain = $this->domainFor($this->owner, 'owned-example.com');
    }

    // ───────────────────────────────────────────── the pages render

    public function test_the_list_shows_the_customers_domains(): void
    {
        $response = $this->actingAs($this->owner)->get('/my-account/domains');

        $response->assertSuccessful();
        $response->assertSee('owned-example.com');
    }

    public function test_the_detail_page_renders(): void
    {
        $response = $this->actingAs($this->owner)->get("/my-account/domains/{$this->domain->id}");

        $response->assertSuccessful();
        $response->assertSee('owned-example.com');
    }

    // ───────────────────────────────────────────── somebody else's domain

    public function test_another_customer_cannot_open_it(): void
    {
        $stranger = $this->member('stranger@example.com');

        $this->actingAs($stranger)
            ->get("/my-account/domains/{$this->domain->id}")
            ->assertNotFound();
    }

    public function test_another_customer_cannot_change_its_nameservers(): void
    {
        $stranger = $this->member('stranger@example.com');

        $this->actingAs($stranger)
            ->post("/my-account/domains/{$this->domain->id}/nameservers", [
                'nameservers' => ['ns1.attacker.test', 'ns2.attacker.test'],
            ])
            ->assertNotFound();

        $this->assertNotSame(
            ['ns1.attacker.test', 'ns2.attacker.test'],
            $this->domain->fresh()->nameservers,
            'pointing somebody else’s domain at your own servers is a hijack'
        );
    }

    public function test_a_signed_out_visitor_is_sent_to_the_login_page(): void
    {
        $this->get("/my-account/domains/{$this->domain->id}")->assertRedirect('/login');
    }

    // ───────────────────────────────────────────── auto-renew

    public function test_the_owner_can_switch_auto_renew_on_and_off(): void
    {
        $this->assertFalse($this->domain->auto_renew);

        $this->actingAs($this->owner)
            ->post("/my-account/domains/{$this->domain->id}/auto-renew", ['auto_renew' => '1'])
            ->assertRedirect();

        $this->assertTrue($this->domain->fresh()->auto_renew);

        $this->actingAs($this->owner)
            ->post("/my-account/domains/{$this->domain->id}/auto-renew", ['auto_renew' => '0'])
            ->assertRedirect();

        $this->assertFalse($this->domain->fresh()->auto_renew);
    }

    /**
     * The confirmation used to name 30 days in the string. The charge day is
     * an operator setting now, so the sentence has to read it back or it
     * becomes a promise the scheduler will not keep.
     */
    public function test_the_confirmation_quotes_the_configured_charge_day(): void
    {
        Setting::setValue('domain_charge_days', 15);
        Setting::setValue('domain_notice_days', 25);
        Cache::flush();

        $this->actingAs($this->owner)
            ->post("/my-account/domains/{$this->domain->id}/auto-renew", ['auto_renew' => '1'])
            ->assertSessionHas('success', fn (string $m) => str_contains($m, '15 วัน') && ! str_contains($m, '30 วัน'));
    }

    /**
     * Same promise, printed on the page rather than flashed. This is the one
     * that would silently break: it lives inside an <x-bi> attribute, where a
     * mis-written expression renders as literal text rather than failing.
     */
    public function test_the_page_quotes_the_configured_charge_day(): void
    {
        Setting::setValue('domain_charge_days', 15);
        Setting::setValue('domain_notice_days', 25);
        Cache::flush();

        $response = $this->actingAs($this->owner)->get("/my-account/domains/{$this->domain->id}");

        $response->assertSuccessful();
        $response->assertSee('ก่อนหมดอายุ 15 วัน', escape: false);
        $response->assertDontSee('ก่อนหมดอายุ 30 วัน', escape: false);
        $response->assertDontSee('DomainReminders::chargeDays', escape: false);
    }

    public function test_the_order_form_quotes_it_too(): void
    {
        Setting::setValue('domain_charge_days', 15);
        Setting::setValue('domain_notice_days', 25);
        Cache::flush();

        $response = $this->actingAs($this->owner)->get('/domains/register/brand-new-example.com');

        $response->assertSuccessful();
        $response->assertDontSee('DomainReminders::chargeDays', escape: false);
        $response->assertSee('ก่อนหมดอายุ 15 วัน', escape: false);
    }

    private function member(string $email): User
    {
        return User::create([
            'name' => 'Member',
            'email' => $email,
            'password' => bcrypt('secret-password'),
            'password_set_at' => now(),
            'is_active' => true,
            'role' => 'user',
        ]);
    }

    private function domainFor(User $user, string $name): DomainRegistration
    {
        return DomainRegistration::create([
            'user_id' => $user->id,
            'domain' => $name,
            'idempotency_key' => 'test-' . uniqid(),
            'tld' => 'com',
            'status' => DomainRegistration::STATUS_ACTIVE,
            'kind' => DomainRegistration::KIND_REGISTER,
            'auto_renew' => false,
            'price_thb' => 470,
            'cost_usd_cents' => 31900,
            'cost_currency' => 'THB',
            'years' => 1,
            'registered_at' => now()->subMonths(2),
            'expires_at' => now()->addMonths(10),
            'remote_subscription_id' => 'sub-test-1',
        ]);
    }
}
