<?php

namespace Tests\Feature;

use App\Mail\AdminAlertMail;
use App\Models\DomainContact;
use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Setting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\DomainPurchaseException;
use App\Services\DomainRegistrarService;
use App\Services\DomainSearchService;
use App\Support\WhoisContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * The registrant reaches the registrar in ITS keys and within ITS rules.
 *
 * Until 2026-10-09 the WHOIS profile went up as `address1` / `zip` / `state` /
 * `phone` / `country`; the registrar wants `address`, `zip_th`, `state_th`,
 * `phone_cc` + `phone_number` and `country_code`, and refused every one —
 * after the customer had paid. These tests pin the keys and the rules read
 * back from its 422s (config/domain_whois.php).
 */
class DomainRegistrantRulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('hostinger_api_token', 'test-token');
        Cache::flush();
        RateLimiter::clear('hostinger-api');

        DomainTld::updateOrCreate(['tld' => 'online'], [
            'item_id_register' => 'test-domain-online-thb-1y',
            'item_id_renew' => 'test-domain-online-thb-1y',
            'cost_usd_cents' => 4000,
            'renew_cost_usd_cents' => 120000,
            'cost_currency' => 'THB',
            'margin_percent' => null,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['name' => 'Somchai Jaidee']);
        Wallet::getOrCreateForUser($this->user->id)->deposit(1000, 'test funding');
    }

    /** @return array<string,string> */
    protected function thaiForm(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'นาย บุญณราช',
            'last_name' => 'อุปเสน',
            'email' => 'owner@example.com',
            'phone_country_code' => '+66',
            'phone' => '081-234-5678',
            'address1' => '99/12 ม.5 ถ.สุขุมวิท',
            'address2' => 'บางจาก',
            'city' => 'พระโขนง',
            'state' => 'กรุงเทพมหานคร',
            'zip' => '10260',
            'country' => 'TH',
            'save_contact' => '1',
            'accept_terms' => '1',
        ], $overrides);
    }

    protected function fakeRegistrar(): void
    {
        Http::fake([
            '*availability*' => Http::response(['data' => [['domain' => 'mygame.online', 'is_available' => true]]]),
            '*whois*' => Http::response(['id' => 900001]),
            '*portfolio/mygame.online*' => Http::response(['status' => 'active', 'expires_at' => now()->addYear()->toIso8601String()]),
            '*portfolio*' => Http::response(['id' => 1, 'subscription_id' => 'sub_1', 'status' => 'completed']),
            '*' => Http::response([], 200),
        ]);
    }

    public function test_the_whois_profile_goes_up_in_the_registrars_keys(): void
    {
        $this->fakeRegistrar();

        $this->actingAs($this->user)
            ->post(route('domains.register.store', 'mygame.online'), $this->thaiForm())
            ->assertSessionHasNoErrors();

        $whois = null;
        Http::recorded(function (Request $request) use (&$whois) {
            if ($request->method() === 'POST' && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/whois')) {
                $whois = $request->data();
            }
        });

        $this->assertNotNull($whois, 'a WHOIS profile should have been created');
        $this->assertSame('TH', $whois['country']);
        $this->assertSame([
            'first_name' => 'บุญณราช',          // the title typed in front is gone
            'last_name' => 'อุปเสน',
            'email' => 'owner@example.com',
            'address' => '99-12 ม.5 ถ.สุขุมวิท บางจาก', // "/" refused upstream → "-"; both lines joined
            'city' => 'พระโขนง',
            'country_code' => 'TH',
            'phone_cc' => '66',                 // digits only, no "+"
            'phone_number' => '812345678',      // no dashes, no trunk 0
            'state_th' => 'Bangkok',            // the registrar's spelling
            'zip_th' => '10260',
        ], $whois['whois_details']);

        $registration = DomainRegistration::where('domain', 'mygame.online')->first();
        $this->assertSame(DomainRegistration::STATUS_ACTIVE, $registration->status);
    }

    public function test_the_stored_contact_is_the_cleaned_record(): void
    {
        $this->fakeRegistrar();

        $this->actingAs($this->user)->post(route('domains.register.store', 'mygame.online'), $this->thaiForm());

        $contact = DomainContact::where('user_id', $this->user->id)->firstOrFail();

        $this->assertSame('บุญณราช', $contact->first_name);
        $this->assertSame('Bangkok', $contact->state);
        $this->assertSame('812345678', $contact->phone);
        $this->assertSame('+66', $contact->phone_country_code);
    }

    public function test_an_address_the_registrar_would_refuse_is_stopped_before_any_money_moves(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $this->actingAs($this->user)
            ->post(route('domains.register.store', 'mygame.online'), $this->thaiForm([
                'address1' => '999/99 หมู่ที่ 12 หมู่บ้านสวนสวยริมน้ำ ซอยสุขุมวิท 101/1 ถนนสุขุมวิท',
            ]))
            ->assertSessionHasErrors('address1');

        $this->assertSame('1000.00', Wallet::getOrCreateForUser($this->user->id)->fresh()->balance);
        $this->assertSame(0, DomainRegistration::count());
        Http::assertNothingSent();
    }

    public function test_a_province_not_on_the_registrars_list_is_a_form_error(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $this->actingAs($this->user)
            ->post(route('domains.register.store', 'mygame.online'), $this->thaiForm(['state' => 'แอตแลนติส']))
            ->assertSessionHasErrors('state');

        Http::assertNothingSent();
    }

    public function test_a_saved_contact_missing_its_province_is_refused_before_the_debit(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $old = DomainContact::create([
            'user_id' => $this->user->id,
            'first_name' => 'Somchai', 'last_name' => 'Jaidee', 'email' => 'somchai@example.com',
            'phone_country_code' => '+66', 'phone' => '812345678',
            'address1' => '1 Sukhumvit', 'city' => 'Bangkok', 'zip' => '10110', 'country' => 'TH',
        ]);

        try {
            app(DomainRegistrarService::class)->register($this->user->id, 'mygame.online', $old);
            $this->fail('a registrant without a province should be refused');
        } catch (DomainPurchaseException $e) {
            $this->assertStringContainsString('จังหวัด', $e->getMessage());
        }

        $this->assertSame('1000.00', Wallet::getOrCreateForUser($this->user->id)->fresh()->balance);
        Http::assertNothingSent();
    }

    public function test_the_refused_fields_are_kept_for_the_admin_without_the_values(): void
    {
        Http::fake([
            '*availability*' => Http::response(['data' => [['domain' => 'mygame.online', 'is_available' => true]]]),
            '*whois*' => Http::response(['message' => 'invalid', 'errors' => ['whois_details' => [
                '"zip_th" must be enter a ZIP/Postal code in the format NNNNN.',
            ]]], 422),
            '*' => Http::response([], 200),
        ]);

        $this->actingAs($this->user)->post(route('domains.register.store', 'mygame.online'), $this->thaiForm());

        $registration = DomainRegistration::where('domain', 'mygame.online')->firstOrFail();

        $this->assertSame(DomainRegistration::STATUS_REFUNDED, $registration->status);
        $this->assertStringContainsString('refused: zip_th', $registration->last_error);
        $this->assertStringNotContainsString('10260', $registration->last_error);
        $this->assertSame('1000.00', Wallet::getOrCreateForUser($this->user->id)->fresh()->balance);
    }

    public function test_regions_resolve_from_thai_english_and_autofill_spellings(): void
    {
        $this->assertSame('Bangkok', WhoisContact::region('TH', 'กรุงเทพมหานคร'));
        $this->assertSame('Bangkok', WhoisContact::region('TH', 'กทม'));
        $this->assertSame('Bangkok', WhoisContact::region('TH', 'bangkok'));
        $this->assertSame('Chiang Mai', WhoisContact::region('TH', 'จ.เชียงใหม่'));
        $this->assertSame('Chonburi', WhoisContact::region('TH', 'Chon Buri'));
        $this->assertSame('Sukhothai Thani', WhoisContact::region('TH', 'สุโขทัย'));
        $this->assertSame('Phra Nakhon Si Ayutthaya', WhoisContact::region('TH', 'อยุธยา'));
        $this->assertSame('CA', WhoisContact::region('US', 'California'));
        $this->assertNull(WhoisContact::region('TH', 'California'));
        $this->assertNull(WhoisContact::region('XX', 'Bangkok'));
    }

    public function test_thai_postcodes_name_their_province(): void
    {
        $this->assertSame('Bangkok', WhoisContact::thaiProvinceForPostcode('10260'));
        $this->assertSame('Samut Prakan', WhoisContact::thaiProvinceForPostcode('10270'));
        $this->assertSame('Chiang Mai', WhoisContact::thaiProvinceForPostcode('50200'));
        $this->assertSame('Bueng Kan', WhoisContact::thaiProvinceForPostcode('๓๘๐๐๐'));
        $this->assertNull(WhoisContact::thaiProvinceForPostcode('1026'));
    }

    public function test_every_country_offered_has_a_working_postcode_rule(): void
    {
        foreach (WhoisContact::countries() as $code => $spec) {
            $this->assertNotEmpty($spec['regions'], "$code needs regions");
            $this->assertMatchesRegularExpression($spec['zip_pattern'], $spec['zip_example'], "$code example postcode");
            $this->assertContains($spec['zip_key'], ['zip_' . strtolower($code), 'zip_general'], "$code postcode key");
            $this->assertSame('state_' . strtolower($code), $spec['state_key']);
        }

        // Hong Kong alone takes the general postcode key.
        $this->assertSame('zip_general', WhoisContact::country('HK')['zip_key']);
    }

    public function test_the_rules_match_what_the_registrar_refused(): void
    {
        $ok = [
            'first_name' => 'Jean-Luc', 'last_name' => 'อุปเสน', 'email' => 'a@example.com',
            'phone_country_code' => '+66', 'phone' => '0812345678',
            'address1' => '99 ม.5 ซ.สุขุมวิท 101 ถ.สุขุมวิท, ต.บางจาก', 'city' => 'อ.เมือง',
            'state' => 'Bangkok', 'zip' => '10260', 'country' => 'TH',
        ];

        $this->assertSame([], WhoisContact::problems($ok));
        $this->assertArrayHasKey('last_name', WhoisContact::problems(['last_name' => "O'Neil"] + $ok));
        $this->assertArrayHasKey('first_name', WhoisContact::problems(['first_name' => 'A'] + $ok));
        $this->assertArrayHasKey('first_name', WhoisContact::problems(['first_name' => 'Som2'] + $ok));
        $this->assertArrayHasKey('address1', WhoisContact::problems(['address1' => 'ถนนสุขุมวิท'] + $ok));
        $this->assertArrayHasKey('zip', WhoisContact::problems(['zip' => '1026'] + $ok));
        $this->assertArrayHasKey('phone', WhoisContact::problems(['phone' => '12'] + $ok));
        $this->assertArrayHasKey('country', WhoisContact::problems(['country' => 'ZZ'] + $ok));

        // "อ.เมือง" goes up without the refused "." and a slash becomes a dash.
        $this->assertSame('อำเภอเมือง', WhoisContact::city('อ.เมือง'));
        $this->assertSame('99-12 Moo 5', WhoisContact::addressLine('99/12 Moo 5'));
        // "นายิกา" is a name, not นาย + ิกา.
        $this->assertSame('นายิกา', WhoisContact::name('นายิกา'));
        $this->assertSame('SW1A 1AA', WhoisContact::zip('sw1a1aa', 'GB'));
        $this->assertSame('100-0001', WhoisContact::zip('1000001', 'JP'));
    }

    /** No Telegram bot: a refused order still reaches the owner, by e-mail — not only card trouble. */
    public function test_a_refused_order_is_mailed_to_the_admin_when_telegram_is_off(): void
    {
        Mail::fake();
        Setting::setValue('contact_email', 'owner@example.com');
        Http::fake([
            '*availability*' => Http::response(['data' => [['domain' => 'mygame.online', 'is_available' => true]]]),
            '*whois*' => Http::response(['id' => 900001]),
            '*portfolio*' => Http::response(['message' => 'Domain cannot be registered'], 422),
            '*' => Http::response([], 200),
        ]);

        $this->actingAs($this->user)->post(route('domains.register.store', 'mygame.online'), $this->thaiForm());

        $this->assertSame(DomainRegistration::STATUS_REFUNDED, DomainRegistration::firstOrFail()->status);
        Mail::assertSent(AdminAlertMail::class, fn (AdminAlertMail $m) => $m->hasTo('owner@example.com'));
    }

    /** A refused order lands on an orange "did not go through" card, not a green success banner. */
    public function test_a_refused_order_page_is_orange_with_a_way_forward(): void
    {
        Http::fake([
            '*availability*' => Http::response(['data' => [['domain' => 'mygame.online', 'is_available' => true]]]),
            '*whois*' => Http::response(['id' => 900001]),
            '*portfolio*' => Http::response(['message' => '[Billing:422] Payment failed'], 422),
            '*' => Http::response([], 200),
        ]);

        $response = $this->actingAs($this->user)
            ->followingRedirects()
            ->post(route('domains.register.store', 'mygame.online'), $this->thaiForm());

        $response->assertOk()
            ->assertSee('จดโดเมนไม่สำเร็จ')
            ->assertSee('ลองจดอีกครั้ง')
            ->assertSee('border-orange-300', false)
            ->assertDontSee('ปิดอยู่ — กดเพื่อเปิด')
            // the old green flash's wording
            ->assertDontSee('ทีมงานได้รับแจ้งแล้วและกำลังตรวจสอบ');
    }

    /**
     * Suggestions: the registrar wants `limit` (every call without it was a 422)
     * and answers with bare names, which are then checked for availability.
     */
    public function test_name_suggestions_send_a_limit_and_check_what_comes_back(): void
    {
        DomainTld::updateOrCreate(['tld' => 'com'], [
            'item_id_register' => 'test-domain-com-1y', 'cost_usd_cents' => 1099, 'renew_cost_usd_cents' => 1599, 'is_active' => true,
        ]);
        Cache::flush();

        Http::fake(function (Request $request) {
            $path = parse_url($request->url(), PHP_URL_PATH);

            if (str_ends_with($path, '/alternatives-from-description')) {
                return Http::response(['playxman.com', 'xmanarcade.online', 'nothere.zzz']);
            }

            if (str_ends_with($path, '/availability')) {
                $label = $request->data()['domain'];

                return Http::response(array_map(fn ($tld) => [
                    'domain' => $label . '.' . $tld, 'is_available' => $label === 'playxman', 'is_alternative' => false,
                ], $request->data()['tlds']));
            }

            return Http::response([], 200);
        });

        $result = app(DomainSearchService::class)->search('thai game hub');

        Http::assertSent(fn (Request $r) => str_ends_with(parse_url($r->url(), PHP_URL_PATH), '/alternatives-from-description')
            && ($r->data()['limit'] ?? null) > 0);
        $this->assertSame(['playxman.com'], array_column($result['results'], 'domain'));
        $this->assertSame(['xmanarcade.online'], array_column($result['unavailable'], 'domain'));
        // A TLD we do not sell is never even checked.
        Http::assertNotSent(fn (Request $r) => ($r->data()['domain'] ?? null) === 'nothere');
    }

    public function test_the_admin_sees_why_an_order_failed(): void
    {
        Http::fake(['*' => Http::response([], 200)]);
        $admin = User::factory()->create(['role' => 'super_admin']);

        DomainRegistration::create([
            'user_id' => $this->user->id, 'domain' => 'refused.online', 'tld' => 'online',
            'status' => DomainRegistration::STATUS_REFUNDED,
            'price_thb' => 60, 'cost_usd_cents' => 4000, 'fx_rate' => 1, 'idempotency_key' => 'refused-1',
            'last_error' => 'whois profile could not be created upstream (refused: zip_th)',
        ]);

        $this->actingAs($admin)->get(route('admin.domains.index'))
            ->assertOk()
            ->assertSee('คำสั่งซื้อที่ไม่สำเร็จล่าสุด')
            ->assertSee('refused.online')
            ->assertSee('ทะเบียนไม่รับข้อมูลผู้ถือครอง (ช่อง: zip_th)')
            ->assertSee('ยังไม่ได้ตั้งค่าบอท Telegram');
    }

    /** The registrant form asks for a fresh token before sending, so a form left open is not a 419. */
    public function test_a_long_open_form_can_fetch_a_fresh_token(): void
    {
        $token = $this->actingAs($this->user)->getJson(route('csrf.refresh'))
            ->assertOk()
            ->json('token');

        $this->assertIsString($token);
        $this->assertGreaterThan(20, strlen($token));
    }

    public function test_the_form_offers_autofill_hints_and_the_registrars_provinces(): void
    {
        $this->actingAs($this->user)
            ->get(route('domains.register', 'mygame.online'))
            ->assertOk()
            ->assertSee('autocomplete="given-name"', false)
            ->assertSee('autocomplete="family-name"', false)
            ->assertSee('autocomplete="address-line1"', false)
            ->assertSee('autocomplete="address-level1"', false)
            ->assertSee('autocomplete="postal-code"', false)
            ->assertSee('autocomplete="tel-national"', false)
            ->assertSee('Phra Nakhon Si Ayutthaya', false);
    }
}
