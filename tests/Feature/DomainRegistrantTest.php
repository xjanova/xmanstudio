<?php

namespace Tests\Feature;

use App\Models\DomainContact;
use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Changing the registrant of a domain the customer already holds.
 *
 * The registry keeps the owner on a WHOIS profile; a change is a new profile
 * that all four roles are repointed to. Changing WHO owns it (name,
 * organisation, e-mail) makes the registry ask for e-mail confirmation and
 * hold transfers for 60 days — the customer is told before and after.
 */
class DomainRegistrantTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected DomainContact $contact;

    protected DomainRegistration $domain;

    /** @var array<string,mixed> upstream answers by "METHOD path" */
    protected array $upstream = [];

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('hostinger_api_token', 'test-token');
        Cache::flush();

        DomainTld::updateOrCreate(['tld' => 'online'], [
            'item_id_register' => 'test-domain-online-thb-1y',
            'item_id_renew' => 'test-domain-online-thb-1y',
            'cost_usd_cents' => 3900,
            'renew_cost_usd_cents' => 123900,
            'cost_currency' => 'THB',
            'is_active' => true,
        ]);

        $this->owner = User::create([
            'name' => 'Somchai Jaidee',
            'email' => 'owner@example.com',
            'password' => bcrypt('secret-password'),
            'password_set_at' => now(),
            'is_active' => true,
            'role' => 'user',
        ]);

        $this->contact = DomainContact::create([
            'user_id' => $this->owner->id,
            'remote_whois_id' => '16165816',
            'synced_at' => now()->addMinute(),
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'email' => 'owner@example.com',
            'phone_country_code' => '+66',
            'phone' => '812345678',
            'address1' => '99 Moo 1',
            'city' => 'Mueang',
            'state' => 'Bangkok',
            'zip' => '10200',
            'country' => 'TH',
        ]);

        $this->domain = DomainRegistration::create([
            'user_id' => $this->owner->id,
            'domain' => 'shop-example.online',
            'idempotency_key' => 'test-' . uniqid(),
            'tld' => 'online',
            'status' => DomainRegistration::STATUS_ACTIVE,
            'kind' => DomainRegistration::KIND_REGISTER,
            'domain_contact_id' => $this->contact->id,
            'auto_renew' => false,
            'price_thb' => 60,
            'cost_usd_cents' => 3900,
            'cost_currency' => 'THB',
            'years' => 1,
            'registered_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->upstream = [
            'POST /api/domains/v1/whois' => [['id' => 777001], 200],
            'PUT /api/domains/v1/whois/change' => [[], 200],
        ];

        Http::fake(function (HttpRequest $r) {
            $key = $r->method() . ' ' . parse_url($r->url(), PHP_URL_PATH);

            [$body, $status] = $this->upstream[$key] ?? [[], 200];

            return Http::response($body, $status);
        });
    }

    private function form(array $overrides = []): array
    {
        return $overrides + [
            'first_name' => 'Somchai',
            'last_name' => 'Jaidee',
            'organization' => '',
            'email' => 'owner@example.com',
            'phone_country_code' => '+66',
            'phone' => '081 234 5678',
            'address1' => '99 Moo 1',
            'address2' => '',
            'city' => 'Mueang',
            'state' => 'Bangkok',
            'zip' => '10200',
            'country' => 'TH',
            'accept_terms' => '1',
        ];
    }

    private function submit(array $form)
    {
        return $this->actingAs($this->owner)
            ->from("/my-account/domains/{$this->domain->id}/registrant")
            ->post("/my-account/domains/{$this->domain->id}/registrant", $form);
    }

    public function test_the_edit_page_starts_from_the_current_registrant_and_warns_first(): void
    {
        $this->actingAs($this->owner)->get("/my-account/domains/{$this->domain->id}/registrant")
            ->assertSuccessful()
            ->assertSee('99 Moo 1')
            ->assertSee('ย้ายไปผู้ให้บริการอื่นไม่ได้ 60 วัน')
            ->assertSee('action="' . route('customer.domains.registrant.update', $this->domain->id) . '"', false);
    }

    public function test_an_address_fix_moves_all_roles_to_a_new_profile_without_the_owner_warning(): void
    {
        $this->submit($this->form(['address1' => '100 Moo 2']))
            ->assertRedirect(route('customer.domains.show', $this->domain->id))
            ->assertSessionHas('success', fn ($m) => ! str_contains($m, 'อีเมลยืนยัน'));

        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'PUT'
            && str_ends_with($r->url(), '/api/domains/v1/whois/change')
            && $r['new_whois_id'] === 777001
            && $r['domain'] === 'shop-example.online'
            && $r['change_for'] === ['owner', 'admin', 'billing', 'tech']);

        $fresh = $this->domain->fresh();
        $this->assertNotSame($this->contact->id, $fresh->domain_contact_id);
        $this->assertSame('100 Moo 2', $fresh->contact->address1);
        $this->assertSame('777001', (string) $fresh->contact->remote_whois_id);
        $this->assertSame('99 Moo 1', $this->contact->fresh()->address1, 'the old record is kept as it was');
    }

    public function test_changing_the_owners_email_says_the_registry_will_ask_for_confirmation(): void
    {
        $this->submit($this->form(['email' => 'new-owner@example.com']))
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'อีเมลยืนยัน') && str_contains($m, 'new-owner@example.com') && str_contains($m, '60 วัน'));
    }

    public function test_the_same_details_change_nothing_upstream(): void
    {
        $this->submit($this->form())->assertSessionHas('success', fn ($m) => str_contains($m, 'เหมือนเดิม'));

        Http::assertNothingSent();
        $this->assertSame($this->contact->id, $this->domain->fresh()->domain_contact_id);
    }

    public function test_details_the_registry_refuses_change_nothing(): void
    {
        $this->upstream['POST /api/domains/v1/whois'] = [['errors' => ['whois_details.zip' => ['Invalid zip']]], 422];

        $this->submit($this->form(['address1' => '100 Moo 2']))->assertSessionHas('error');

        Http::assertNotSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/whois/change'));
        $this->assertSame($this->contact->id, $this->domain->fresh()->domain_contact_id);
        $this->assertSame(1, DomainContact::count(), 'the refused draft is not left behind');
    }

    public function test_a_refused_change_keeps_the_current_registrant(): void
    {
        $this->upstream['PUT /api/domains/v1/whois/change'] = [['message' => 'nope'], 422];

        $this->submit($this->form(['address1' => '100 Moo 2']))->assertSessionHas('error');

        $this->assertSame($this->contact->id, $this->domain->fresh()->domain_contact_id);
    }

    public function test_the_confirmation_box_is_required(): void
    {
        $this->submit($this->form(['address1' => '100 Moo 2', 'accept_terms' => null]))
            ->assertSessionHasErrors('accept_terms');

        Http::assertNothingSent();
    }

    public function test_registry_rules_are_checked_before_anything_is_sent(): void
    {
        $this->submit($this->form(['zip' => '1']))->assertSessionHasErrors('zip');

        Http::assertNothingSent();
    }

    public function test_another_customer_cannot_see_or_change_it(): void
    {
        $stranger = User::create([
            'name' => 'Stranger',
            'email' => 'stranger@example.com',
            'password' => bcrypt('secret-password'),
            'password_set_at' => now(),
            'is_active' => true,
            'role' => 'user',
        ]);

        $this->actingAs($stranger)->get("/my-account/domains/{$this->domain->id}/registrant")->assertNotFound();
        $this->actingAs($stranger)->post("/my-account/domains/{$this->domain->id}/registrant", $this->form())->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_the_domain_page_shows_the_registrant_and_a_change_in_progress(): void
    {
        $this->upstream['GET /api/domains/v1/portfolio/shop-example.online'] = [[
            'domain' => 'shop-example.online',
            'is_locked' => true,
            'domain_contacts' => ['owner_id' => 999, 'admin_id' => 999, 'billing_id' => 999, 'tech_id' => 999],
        ], 200];

        $this->actingAs($this->owner)->get("/my-account/domains/{$this->domain->id}")
            ->assertSuccessful()
            ->assertSee('Somchai Jaidee')
            ->assertSee('href="' . route('customer.domains.registrant', $this->domain->id) . '"', false)
            ->assertSee('กำลังเปลี่ยนที่ทะเบียน');
    }
}
