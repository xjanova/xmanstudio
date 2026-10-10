<?php

namespace Tests\Feature;

use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Setting;
use App\Models\User;
use App\Services\DomainRegistrarService;
use App\Support\DnsProbe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * "Point to Cloudflare" in one click, and the way back.
 *
 * Cloudflare gives every account its own nameserver pair, so there is no
 * standard pair to switch to. The pair the customer pastes is asked of
 * Cloudflare before anything changes: a site still pending there is served
 * only on its assigned pair, and a domain pointed at a pair that does not
 * serve it loses its website and mail.
 */
class DomainCloudflareTest extends TestCase
{
    use RefreshDatabase;

    private const OURS = ['byte.dns-parking.com', 'pixel.dns-parking.com'];

    private const CLOUDFLARE = ['leah.ns.cloudflare.com', 'stan.ns.cloudflare.com'];

    protected User $owner;

    protected DomainRegistration $domain;

    /** @var array<string,array{status:string,nameservers:array<int,string>}> server => answer */
    protected array $answers = [];

    /** @var array<int,string> servers the probe was asked */
    protected array $asked = [];

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('hostinger_api_token', 'test-token');
        Cache::flush();

        Http::fake(['*' => Http::response(['message' => 'ok'], 200)]);

        DomainTld::updateOrCreate(['tld' => 'online'], [
            'item_id_register' => 'test-domain-online-thb-1y',
            'item_id_renew' => 'test-domain-online-thb-1y',
            'cost_usd_cents' => 3900,
            'renew_cost_usd_cents' => 123900,
            'cost_currency' => 'THB',
            'is_active' => true,
        ]);

        $test = $this;
        $this->app->instance(DnsProbe::class, new class($test) extends DnsProbe
        {
            public function __construct(private $test) {}

            public function nameservers(string $domain, string $server): array
            {
                $this->test->recordAsked($server);

                return $this->test->answerFor($server);
            }
        });

        $this->owner = User::create([
            'name' => 'Member',
            'email' => 'owner@example.com',
            'password' => bcrypt('secret-password'),
            'password_set_at' => now(),
            'is_active' => true,
            'role' => 'user',
        ]);

        $this->domain = DomainRegistration::create([
            'user_id' => $this->owner->id,
            'domain' => 'shop-example.online',
            'idempotency_key' => 'test-' . uniqid(),
            'tld' => 'online',
            'status' => DomainRegistration::STATUS_ACTIVE,
            'kind' => DomainRegistration::KIND_REGISTER,
            'auto_renew' => false,
            'price_thb' => 60,
            'cost_usd_cents' => 3900,
            'cost_currency' => 'THB',
            'years' => 1,
            'nameservers' => self::OURS,
            'own_nameservers' => self::OURS,
            'registered_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
    }

    public function recordAsked(string $server): void
    {
        $this->asked[] = $server;
    }

    public function answerFor(string $server): array
    {
        return $this->answers[$server] ?? ['status' => DnsProbe::NOT_SERVED, 'nameservers' => []];
    }

    private function pointAtCloudflare(string $pasted)
    {
        return $this->actingAs($this->owner)
            ->from("/my-account/domains/{$this->domain->id}")
            ->post("/my-account/domains/{$this->domain->id}/cloudflare", ['cloudflare_ns' => $pasted]);
    }

    private function assertNameserversNotSent(): void
    {
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), '/nameservers'));
    }

    // ───────────────────────────────────────────── the one click

    public function test_one_pasted_name_switches_to_the_pair_cloudflare_assigned(): void
    {
        $this->answers['leah.ns.cloudflare.com'] = ['status' => DnsProbe::OK, 'nameservers' => self::CLOUDFLARE];

        // What a customer copies from Cloudflare's "Replace your nameservers" step.
        $this->pointAtCloudflare("B. Add each of your assigned Cloudflare nameservers:\n  Leah.NS.Cloudflare.com  ⧉")
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'PUT'
            && str_ends_with($r->url(), '/portfolio/shop-example.online/nameservers')
            && $r['ns1'] === 'leah.ns.cloudflare.com'
            && $r['ns2'] === 'stan.ns.cloudflare.com');

        $this->domain->refresh();
        $this->assertSame(self::CLOUDFLARE, $this->domain->nameservers);
        $this->assertSame(self::OURS, $this->domain->own_nameservers, 'the way back must survive the switch');
        $this->assertTrue($this->domain->usesCloudflare());
        $this->assertFalse($this->domain->usesOwnDns());
    }

    public function test_a_site_not_yet_added_on_cloudflare_changes_nothing(): void
    {
        $this->pointAtCloudflare("leah.ns.cloudflare.com\nstan.ns.cloudflare.com")
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Cloudflare ยังไม่รู้จัก shop-example.online'));

        $this->assertNameserversNotSent();
        $this->assertSame(self::OURS, $this->domain->fresh()->nameservers);
    }

    public function test_the_other_pasted_name_is_tried_when_the_first_refuses(): void
    {
        $this->answers['stan.ns.cloudflare.com'] = ['status' => DnsProbe::OK, 'nameservers' => self::CLOUDFLARE];

        $this->pointAtCloudflare('leah.ns.cloudflare.com stan.ns.cloudflare.com')->assertSessionHas('success');

        $this->assertSame(['leah.ns.cloudflare.com', 'stan.ns.cloudflare.com'], $this->asked);
        $this->assertSame(self::CLOUDFLARE, $this->domain->fresh()->nameservers);
    }

    public function test_a_pair_that_is_not_this_domains_is_refused_and_the_right_one_named(): void
    {
        // An active zone answers on any Cloudflare server, with its real pair.
        $this->answers['ada.ns.cloudflare.com'] = ['status' => DnsProbe::OK, 'nameservers' => self::CLOUDFLARE];

        $this->pointAtCloudflare("ada.ns.cloudflare.com\nbob.ns.cloudflare.com")
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'leah.ns.cloudflare.com, stan.ns.cloudflare.com'));

        $this->assertNameserversNotSent();
    }

    public function test_when_cloudflare_cannot_be_asked_nothing_changes(): void
    {
        $this->answers['leah.ns.cloudflare.com'] = ['status' => DnsProbe::FAILED, 'nameservers' => []];

        $this->pointAtCloudflare('leah.ns.cloudflare.com')
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'ตรวจสอบกับ Cloudflare ไม่สำเร็จ'));

        $this->assertNameserversNotSent();
    }

    public function test_text_without_a_cloudflare_name_is_refused_before_asking_anyone(): void
    {
        $this->pointAtCloudflare('ns1.example.com ns2.example.com')
            ->assertSessionHas('error', fn ($m) => str_contains($m, '.ns.cloudflare.com'));

        $this->assertSame([], $this->asked);
        $this->assertNameserversNotSent();
    }

    public function test_another_customer_cannot_point_it_anywhere(): void
    {
        $stranger = User::create([
            'name' => 'Stranger',
            'email' => 'stranger@example.com',
            'password' => bcrypt('secret-password'),
            'password_set_at' => now(),
            'is_active' => true,
            'role' => 'user',
        ]);

        $this->actingAs($stranger)
            ->post("/my-account/domains/{$this->domain->id}/cloudflare", ['cloudflare_ns' => 'leah.ns.cloudflare.com'])
            ->assertNotFound();

        $this->actingAs($stranger)
            ->post("/my-account/domains/{$this->domain->id}/own-dns")
            ->assertNotFound();

        $this->assertNameserversNotSent();
    }

    // ───────────────────────────────────────────── the way back

    public function test_back_to_our_dns_restores_the_nameservers_it_was_registered_with(): void
    {
        $this->domain->update(['nameservers' => self::CLOUDFLARE]);

        $this->actingAs($this->owner)
            ->from("/my-account/domains/{$this->domain->id}")
            ->post("/my-account/domains/{$this->domain->id}/own-dns")
            ->assertSessionHas('success');

        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'PUT'
            && str_ends_with($r->url(), '/nameservers')
            && $r['ns1'] === 'byte.dns-parking.com'
            && $r['ns2'] === 'pixel.dns-parking.com');

        $this->assertTrue($this->domain->fresh()->usesOwnDns());
    }

    public function test_without_a_record_of_our_nameservers_there_is_no_guessing(): void
    {
        $this->domain->update(['nameservers' => self::CLOUDFLARE, 'own_nameservers' => null]);

        $this->actingAs($this->owner)
            ->from("/my-account/domains/{$this->domain->id}")
            ->post("/my-account/domains/{$this->domain->id}/own-dns")
            ->assertSessionHas('error');

        $this->assertNameserversNotSent();
    }

    // ───────────────────────────────────────────── the page

    public function test_on_our_dns_the_page_offers_the_cloudflare_button_and_the_editor(): void
    {
        $this->actingAs($this->owner)->get("/my-account/domains/{$this->domain->id}")
            ->assertSuccessful()
            ->assertSee('action="' . route('customer.domains.cloudflare', $this->domain->id) . '"', false)
            ->assertSee('action="' . route('customer.domains.dns', $this->domain->id) . '"', false)
            ->assertDontSee('action="' . route('customer.domains.own-dns', $this->domain->id) . '"', false);
    }

    public function test_on_cloudflare_the_editor_gives_way_and_the_way_back_is_offered(): void
    {
        $this->domain->update(['nameservers' => self::CLOUDFLARE]);

        $this->actingAs($this->owner)->get("/my-account/domains/{$this->domain->id}")
            ->assertSuccessful()
            ->assertSee('ตอนนี้ DNS ของโดเมนนี้อยู่ที่ Cloudflare')
            ->assertSee('action="' . route('customer.domains.own-dns', $this->domain->id) . '"', false)
            ->assertDontSee('action="' . route('customer.domains.dns', $this->domain->id) . '"', false)
            ->assertDontSee('action="' . route('customer.domains.cloudflare', $this->domain->id) . '"', false);

        // Our zone answers nobody now, so it is not even read.
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), '/api/dns/v1/zones/'));
    }

    // ───────────────────────────────────────────── where "our" nameservers come from

    public function test_registration_keeps_its_first_nameservers_and_a_renewal_never_rewrites_them(): void
    {
        $this->domain->update(['own_nameservers' => null]);

        Http::swap(new Factory);
        Http::fake(['*' => Http::sequence()
            ->push(['domain' => 'shop-example.online', 'name_servers' => ['ns1' => self::OURS[0], 'ns2' => self::OURS[1]]])
            ->push(['domain' => 'shop-example.online', 'name_servers' => ['ns1' => self::CLOUDFLARE[0], 'ns2' => self::CLOUDFLARE[1]]]),
        ]);

        $registrar = app(DomainRegistrarService::class);

        $registrar->refreshFromUpstream($this->domain, justRegistered: true);
        $this->assertSame(self::OURS, $this->domain->fresh()->own_nameservers);

        $registrar->refreshFromUpstream($this->domain->fresh());
        $this->assertSame(self::CLOUDFLARE, $this->domain->fresh()->nameservers);
        $this->assertSame(self::OURS, $this->domain->fresh()->own_nameservers);
    }

    // ───────────────────────────────────────────── reading Cloudflare's answer

    public function test_a_real_cloudflare_answer_is_read_through_its_compression_pointers(): void
    {
        // leah.ns.cloudflare.com answering NS for xmangameshub.online, 2026-10-10.
        $packet = base64_decode('QkKEAAABAAIAAAAADHhtYW5nYW1lc2h1YgZvbmxpbmUAAAIAAcAMAAIAAQABUYAAGARsZWFoAm5zCmNsb3VkZmxhcmUDY29tAMAMAAIAAQABUYAABwRzdGFuwDY=');

        $this->assertSame(
            ['status' => DnsProbe::OK, 'nameservers' => self::CLOUDFLARE],
            DnsProbe::parse($packet, 0x4242),
        );

        // Someone else's answer is not ours to believe.
        $this->assertSame(DnsProbe::FAILED, DnsProbe::parse($packet, 0x4243)['status']);

        // REFUSED: the server does not serve this domain.
        $refused = pack('nnnnnn', 0x4242, 0x8005, 0, 0, 0, 0);
        $this->assertSame(DnsProbe::NOT_SERVED, DnsProbe::parse($refused, 0x4242)['status']);

        // A pointer loop must not hang the request.
        $loop = pack('nnnnnn', 0x4242, 0x8400, 1, 0, 0, 0) . "\xC0\x0C";
        $this->assertSame(DnsProbe::FAILED, DnsProbe::parse($loop, 0x4242)['status']);
    }
}
