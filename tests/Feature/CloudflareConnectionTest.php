<?php

namespace Tests\Feature;

use App\Models\CloudflareConnection;
use App\Models\DomainRegistration;
use App\Models\DomainTld;
use App\Models\Setting;
use App\Models\User;
use App\Models\VpsInstance;
use App\Services\CloudflareApiService;
use App\Support\CloudflareTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The customer's own Cloudflare, connected by their API token.
 *
 * Cloudflare is faked by a small in-memory account: zones and records the
 * calls create are kept, so each test can assert on what the customer's
 * account ends up holding — and on what was never touched.
 */
class CloudflareConnectionTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'cfTOKENabcdefghijklmnopqrstuvwxyz0123456789';

    private const CLOUDFLARE = ['leah.ns.cloudflare.com', 'stan.ns.cloudflare.com'];

    protected User $owner;

    protected DomainRegistration $domain;

    /** @var array<string,array<string,mixed>> zone id => zone */
    protected array $zones = [];

    /** @var array<string,array<int,array<string,mixed>>> zone id => records */
    protected array $records = [];

    /** Our (registrar) zone for the domain, in the registrar's shape. */
    protected array $ourZone = [];

    protected bool $tokenActive = true;

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

        $this->owner = $this->member('owner@example.com');
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
            'nameservers' => ['byte.dns-parking.com', 'pixel.dns-parking.com'],
            'own_nameservers' => ['byte.dns-parking.com', 'pixel.dns-parking.com'],
            'registered_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        Http::fake(fn (HttpRequest $r) => $this->route($r));
    }

    // ───────────────────────────────────────────── connecting

    public function test_connecting_stores_the_token_encrypted_and_never_shows_it_again(): void
    {
        $this->actingAs($this->owner)->from($this->page())
            ->post('/my-account/domains/cloudflare/connect', ['cloudflare_token' => self::TOKEN])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Owner Account'));

        $connection = CloudflareConnection::where('user_id', $this->owner->id)->firstOrFail();
        $this->assertSame(self::TOKEN, $connection->api_token);
        $this->assertSame('acc-1', $connection->account_id);
        $this->assertNotSame(self::TOKEN, DB::table('cloudflare_connections')->value('api_token'), 'stored encrypted');
        $this->assertArrayNotHasKey('api_token', $connection->toArray());

        $this->actingAs($this->owner)->get($this->page())
            ->assertSuccessful()
            ->assertDontSee(self::TOKEN)
            ->assertSee('••••' . substr(self::TOKEN, -4), false);
    }

    public function test_a_token_cloudflare_rejects_is_not_kept(): void
    {
        $this->tokenActive = false;

        $this->actingAs($this->owner)->from($this->page())
            ->post('/my-account/domains/cloudflare/connect', ['cloudflare_token' => self::TOKEN])
            ->assertSessionHas('error');

        $this->assertSame(0, CloudflareConnection::count());
    }

    public function test_disconnecting_forgets_the_token(): void
    {
        $this->connect();

        $this->actingAs($this->owner)->from($this->page())
            ->post('/my-account/domains/cloudflare/disconnect')
            ->assertSessionHas('success');

        $this->assertSame(0, CloudflareConnection::count());
    }

    public function test_the_token_link_ticks_the_permissions_we_need(): void
    {
        parse_str((string) parse_url(CloudflareApiService::tokenTemplateUrl(), PHP_URL_QUERY), $query);
        $keys = collect(json_decode($query['permissionGroupKeys'], true))->pluck('type', 'key')->all();

        $this->assertSame('edit', $keys['zone']);
        $this->assertSame('edit', $keys['dns']);
        $this->assertSame('edit', $keys['zone_settings']);
        $this->assertSame('all', $query['zoneId']);
    }

    // ───────────────────────────────────────────── moving the domain

    public function test_moving_adds_the_site_copies_our_records_and_points_the_nameservers(): void
    {
        $this->connect();
        $this->ourZone = [
            ['name' => '@', 'type' => 'A', 'ttl' => 50, 'records' => [['content' => '2.57.91.91', 'is_disabled' => false]]],
            ['name' => 'www', 'type' => 'CNAME', 'ttl' => 300, 'records' => [['content' => 'shop-example.online.', 'is_disabled' => false]]],
            ['name' => '@', 'type' => 'MX', 'ttl' => 3600, 'records' => [['content' => '10 mx1.mail-example.net.', 'is_disabled' => false]]],
            ['name' => '@', 'type' => 'TXT', 'ttl' => 3600, 'records' => [['content' => '"v=spf1 -all"', 'is_disabled' => false]]],
            ['name' => '@', 'type' => 'CAA', 'ttl' => 3600, 'records' => [['content' => '0 issue "letsencrypt.org"', 'is_disabled' => false]]],
            ['name' => '@', 'type' => 'NS', 'ttl' => 3600, 'records' => [['content' => 'byte.dns-parking.com.', 'is_disabled' => false]]],
        ];

        $this->actingAs($this->owner)->from($this->page())
            ->post("/my-account/domains/{$this->domain->id}/cloudflare/move")
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'CAA shop-example.online') && str_contains($m, '4 รายการ'));

        $zone = collect($this->zones)->firstWhere('name', 'shop-example.online');
        $this->assertNotNull($zone, 'the site was added to the customer\'s account');
        $this->assertSame('acc-1', $zone['account']['id']);

        $copied = collect($this->records[$zone['id']])->keyBy('type');
        $this->assertSame(['A', 'CNAME', 'MX', 'TXT'], $copied->keys()->sort()->values()->all());
        $this->assertSame('2.57.91.91', $copied['A']['content']);
        $this->assertSame(1, $copied['A']['ttl'], 'a TTL under 60 becomes automatic');
        $this->assertFalse($copied['A']['proxied'], 'copied exactly as it worked: DNS only');
        $this->assertSame('shop-example.online', $copied['CNAME']['content']);
        $this->assertSame('www.shop-example.online', $copied['CNAME']['name']);
        $this->assertSame(['mx1.mail-example.net', 10], [$copied['MX']['content'], $copied['MX']['priority']]);
        $this->assertSame('v=spf1 -all', $copied['TXT']['content']);

        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'PUT'
            && str_ends_with($r->url(), '/portfolio/shop-example.online/nameservers')
            && $r['ns1'] === 'leah.ns.cloudflare.com' && $r['ns2'] === 'stan.ns.cloudflare.com');
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/activation_check'));

        $this->assertTrue($this->domain->fresh()->usesCloudflare());
    }

    public function test_moving_into_a_zone_the_customer_already_set_up_copies_nothing(): void
    {
        $this->connect();
        $zoneId = $this->addZone('shop-example.online');
        $this->records[$zoneId][] = ['id' => 'mine', 'type' => 'A', 'name' => 'shop-example.online', 'content' => '198.51.100.7'];

        $this->actingAs($this->owner)->from($this->page())
            ->post("/my-account/domains/{$this->domain->id}/cloudflare/move")
            ->assertSessionHas('success');

        Http::assertNotSent(fn (HttpRequest $r) => $r->method() === 'POST' && str_ends_with(parse_url($r->url(), PHP_URL_PATH), '/zones'));
        $this->assertCount(1, $this->records[$zoneId]);
        $this->assertTrue($this->domain->fresh()->usesCloudflare());
    }

    // ───────────────────────────────────────────── quick setup

    public function test_the_vps_template_replaces_only_the_host_records_and_turns_on_https(): void
    {
        $this->connect();
        $zoneId = $this->addZone('shop-example.online');
        $this->records[$zoneId] = [
            ['id' => 'old-a', 'type' => 'A', 'name' => 'shop-example.online', 'content' => '2.57.91.91'],
            ['id' => 'old-www', 'type' => 'CNAME', 'name' => 'www.shop-example.online', 'content' => 'shop-example.online'],
            ['id' => 'mail', 'type' => 'MX', 'name' => 'shop-example.online', 'content' => 'mx.mail-example.net', 'priority' => 10],
            ['id' => 'verify', 'type' => 'TXT', 'name' => 'shop-example.online', 'content' => 'google-site-verification=abc'],
        ];
        $vps = $this->vpsFor($this->owner, '203.0.113.5');

        $this->applyTemplate(['template' => 'vps', 'vps_id' => $vps->id, 'ssl' => 'full'])
            ->assertSessionHas('success');

        $left = collect($this->records[$zoneId]);
        $this->assertSame(['mail', 'verify'], $left->whereIn('id', ['mail', 'verify', 'old-a', 'old-www'])->pluck('id')->sort()->values()->all());
        $this->assertTrue($left->contains(fn ($r) => $r['type'] === 'A' && $r['content'] === '203.0.113.5' && $r['proxied'] === true));
        $this->assertTrue($left->contains(fn ($r) => $r['type'] === 'CNAME' && $r['name'] === 'www.shop-example.online' && $r['content'] === 'shop-example.online'));

        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'PATCH' && str_ends_with($r->url(), '/settings/ssl') && $r['value'] === 'full');
        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'PATCH' && str_ends_with($r->url(), '/settings/always_use_https') && $r['value'] === 'on');
    }

    public function test_someone_elses_vps_cannot_be_named(): void
    {
        $this->connect();
        $zoneId = $this->addZone('shop-example.online');
        $stranger = $this->member('stranger@example.com');
        $theirs = $this->vpsFor($stranger, '198.51.100.99');

        $this->applyTemplate(['template' => 'vps', 'vps_id' => $theirs->id])->assertSessionHas('error');

        $this->assertSame([], $this->records[$zoneId]);
        $this->assertCloudflareWrites(0);
    }

    public function test_google_workspace_replaces_mail_and_spf_but_keeps_other_txt(): void
    {
        $this->connect();
        $zoneId = $this->addZone('shop-example.online');
        $this->records[$zoneId] = [
            ['id' => 'old-mx', 'type' => 'MX', 'name' => 'shop-example.online', 'content' => 'mx.old-host.net', 'priority' => 5],
            ['id' => 'old-spf', 'type' => 'TXT', 'name' => 'shop-example.online', 'content' => '"v=spf1 include:old-host.net -all"'],
            ['id' => 'keep', 'type' => 'TXT', 'name' => 'shop-example.online', 'content' => 'facebook-domain-verification=xyz'],
            ['id' => 'site', 'type' => 'A', 'name' => 'shop-example.online', 'content' => '203.0.113.5'],
        ];

        $this->applyTemplate(['template' => 'google_workspace', 'verification' => 'abc123'])
            ->assertSessionHas('success');

        $left = collect($this->records[$zoneId]);
        $this->assertFalse($left->contains('id', 'old-mx'));
        $this->assertFalse($left->contains('id', 'old-spf'));
        $this->assertTrue($left->contains('id', 'keep'));
        $this->assertTrue($left->contains('id', 'site'), 'mail setup never touches the website');
        $this->assertTrue($left->contains(fn ($r) => $r['type'] === 'MX' && $r['content'] === 'smtp.google.com' && $r['priority'] === 1));
        $this->assertTrue($left->contains(fn ($r) => $r['content'] === 'v=spf1 include:_spf.google.com ~all'));
        $this->assertTrue($left->contains(fn ($r) => $r['name'] === '_dmarc.shop-example.online' && str_starts_with($r['content'], 'v=DMARC1')));
        $this->assertTrue($left->contains(fn ($r) => $r['content'] === 'google-site-verification=abc123'));
    }

    public function test_a_bad_field_changes_nothing(): void
    {
        $this->connect();
        $this->addZone('shop-example.online');

        $this->applyTemplate(['template' => 'netlify', 'site' => 'not a site!'])->assertSessionHas('error');
        $this->applyTemplate(['template' => 'server_ip', 'ipv4' => '192.168.1.10'])->assertSessionHas('error');

        $this->assertCloudflareWrites(0);
    }

    public function test_a_template_for_a_domain_not_in_cloudflare_changes_nothing(): void
    {
        $this->connect();

        $this->applyTemplate(['template' => 'security'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'ยังไม่มี shop-example.online ใน Cloudflare'));

        $this->assertCloudflareWrites(0);
    }

    public function test_another_customers_domain_is_not_found(): void
    {
        $stranger = $this->member('stranger@example.com');
        CloudflareConnection::create(['user_id' => $stranger->id, 'api_token' => self::TOKEN, 'account_id' => 'acc-1']);

        $this->actingAs($stranger)->post("/my-account/domains/{$this->domain->id}/cloudflare/move")->assertNotFound();
        $this->actingAs($stranger)->post("/my-account/domains/{$this->domain->id}/cloudflare/template", ['template' => 'security'])->assertNotFound();

        $this->assertCloudflareWrites(0);
    }

    public function test_the_page_offers_the_templates_once_the_site_is_in_cloudflare(): void
    {
        $this->connect();

        $this->actingAs($this->owner)->get($this->page())
            ->assertSee('action="' . route('customer.domains.cloudflare-move', $this->domain->id) . '"', false)
            ->assertSee('ยังไม่มีเว็บนี้ใน Cloudflare ของคุณ')
            ->assertDontSee('action="' . route('customer.domains.cloudflare-template', $this->domain->id) . '"', false);

        $this->addZone('shop-example.online');
        Cache::flush();

        $this->actingAs($this->owner)->get($this->page())
            ->assertSee('action="' . route('customer.domains.cloudflare-template', $this->domain->id) . '"', false)
            ->assertSee('อีเมล Google Workspace');
    }

    public function test_without_a_connection_the_page_offers_the_token_link(): void
    {
        $this->actingAs($this->owner)->get($this->page())
            ->assertSee('action="' . route('customer.domains.cloudflare-connect') . '"', false)
            ->assertSee('permissionGroupKeys', false)
            ->assertDontSee('ตั้งค่าด่วน (Cloudflare)');
    }

    public function test_plan_keeps_identical_extras_and_clears_clashes(): void
    {
        $built = CloudflareTemplates::build('github_pages', ['user' => 'octocat'], 'shop-example.online');
        $existing = [
            ['id' => 'old', 'type' => 'A', 'name' => 'shop-example.online', 'content' => '2.57.91.91'],
            ['id' => 'txt', 'type' => 'TXT', 'name' => 'shop-example.online', 'content' => 'hello'],
        ];

        $plan = CloudflareTemplates::plan($built['records'], $existing);

        $this->assertSame(['old'], array_column($plan['delete'], 'id'));
        $this->assertCount(5, $plan['create'], 'four A records and the www CNAME');
        $this->assertSame('octocat.github.io', collect($plan['create'])->firstWhere('type', 'CNAME')['content']);
    }

    // ───────────────────────────────────────────── helpers

    private function page(): string
    {
        return "/my-account/domains/{$this->domain->id}";
    }

    private function connect(): void
    {
        CloudflareConnection::create([
            'user_id' => $this->owner->id,
            'api_token' => self::TOKEN,
            'token_hint' => substr(self::TOKEN, -4),
            'account_id' => 'acc-1',
            'account_name' => 'Owner Account',
            'verified_at' => now(),
        ]);
    }

    private function applyTemplate(array $input)
    {
        return $this->actingAs($this->owner)->from($this->page())
            ->post("/my-account/domains/{$this->domain->id}/cloudflare/template", $input);
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

    private function vpsFor(User $user, string $ip): VpsInstance
    {
        return VpsInstance::forceCreate([
            'user_id' => $user->id,
            'plan_name' => 'VPS Business',
            'status' => VpsInstance::STATUS_ACTIVE,
            'hostname' => 'srv.example.net',
            'ipv4' => $ip,
            'period' => '1m',
            'months' => 1,
        ]);
    }

    private function addZone(string $name): string
    {
        $id = md5($name);
        $this->zones[$id] = ['id' => $id, 'name' => $name, 'status' => 'pending', 'name_servers' => self::CLOUDFLARE, 'account' => ['id' => 'acc-1']];
        $this->records[$id] ??= [];

        return $id;
    }

    private function assertCloudflareWrites(int $count): void
    {
        $writes = Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), 'api.cloudflare.com') && $r->method() !== 'GET');
        $this->assertCount($count, $writes);
    }

    private function route(HttpRequest $r)
    {
        $url = $r->url();

        if (! str_contains($url, 'api.cloudflare.com')) {
            // The registrar.
            if (str_contains($url, '/api/dns/v1/zones/')) {
                return Http::response($this->ourZone, 200);
            }

            return Http::response(['message' => 'ok'], 200);
        }

        $this->assertSame('Bearer ' . self::TOKEN, $r->header('Authorization')[0] ?? null);

        $path = substr((string) parse_url($url, PHP_URL_PATH), strlen('/client/v4'));
        $ok = fn ($result) => Http::response(['success' => true, 'errors' => [], 'result' => $result], 200);
        $method = $r->method();

        if ($path === '/user/tokens/verify') {
            return $this->tokenActive
                ? $ok(['id' => 't1', 'status' => 'active'])
                : Http::response(['success' => false, 'errors' => [['code' => 1000, 'message' => 'Invalid API Token']]], 401);
        }

        if ($path === '/accounts') {
            return $ok([['id' => 'acc-1', 'name' => 'Owner Account']]);
        }

        if ($path === '/zones' && $method === 'GET') {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $q);

            return $ok(array_values(array_filter($this->zones, fn ($z) => $z['name'] === ($q['name'] ?? null))));
        }

        if ($path === '/zones' && $method === 'POST') {
            $id = $this->addZone($r['name']);
            $this->zones[$id]['account'] = $r['account'];

            return $ok($this->zones[$id]);
        }

        if (preg_match('#^/zones/([^/]+)/dns_records$#', $path, $m)) {
            if ($method === 'GET') {
                return $ok($this->records[$m[1]] ?? []);
            }

            $record = $r->data() + ['id' => 'new-' . count($this->records[$m[1]] ?? [])];
            $this->records[$m[1]][] = $record;

            return $ok($record);
        }

        if (preg_match('#^/zones/([^/]+)/dns_records/([^/]+)$#', $path, $m) && $method === 'DELETE') {
            $this->records[$m[1]] = array_values(array_filter($this->records[$m[1]], fn ($rec) => $rec['id'] !== $m[2]));

            return $ok(['id' => $m[2]]);
        }

        if (preg_match('#^/zones/[^/]+/(settings/[a-z_0-9]+|activation_check)$#', $path)) {
            return $ok(['ok' => true]);
        }

        return Http::response(['success' => false, 'errors' => [['code' => 7003, 'message' => 'Could not route']]], 404);
    }
}
