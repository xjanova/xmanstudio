<?php

namespace Tests\Feature;

use App\Models\LicenseKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductDevice;
use App\Models\User;
use App\Models\VpnNetwork;
use App\Models\Wallet;
use App\Models\WireguardServer;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * LocalVPN Pro — the owner's decisions of 2026-10-11, on the license API and the LocalVPN APIs
 *
 *   — a 30-day full-Pro trial, once per device and per hardware (a reinstall cannot restart it)
 *   — every device keeps a FREE key for the free tier; holding it does not block the trial, and
 *     when the trial (or a paid term) ends, check-machine and validate hand it back
 *   — the running trial is Pro everywhere LocalVPN asks: every VPN country, networks past 5
 *     members — for LocalVPN only
 *   — one purchased key runs on two devices (a PC + a phone)
 */
class LocalVpnProTrialTest extends TestCase
{
    use RefreshDatabase;

    private const PC_HASH = 'a1b2c3d4e5f60718293a4b5c6d7e8f90a1b2c3d4e5f60718293a4b5c6d7e8f90';

    private const PHONE_HASH = 'ffeeddccbbaa99887766554433221100ffeeddccbbaa99887766554433221100';

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        // WireGuard registration shells out to `sudo wg …` on the VPN host — never on the test machine
        Process::fake();
        Process::preventStrayProcesses();

        // created by 2026_03_26_000001_insert_localvpn_product, as on production
        $this->product = Product::where('slug', 'localvpn')->firstOrFail();
    }

    // ── the trial ───────────────────────────────────────────────────────

    public function test_a_new_device_gets_its_free_key_and_is_offered_the_trial(): void
    {
        $this->checkMachine('a')->assertOk()
            ->assertJsonPath('has_license', true)
            ->assertJsonPath('data.license_type', 'free');

        $this->registerDevice('a', self::PC_HASH)->assertOk()->assertJsonPath('data.can_start_trial', true);

        $this->demoCheck('a')->assertOk()
            ->assertJsonPath('data.has_used_demo', false)
            ->assertJsonPath('data.can_start_demo', true);
    }

    public function test_the_trial_is_thirty_days_of_full_pro(): void
    {
        $this->freezeSecond();
        $free = $this->checkMachine('a')->json('data.license_key');

        $response = $this->demo('a', self::PC_HASH)->assertOk()
            ->assertJsonPath('message', 'เริ่มใช้งาน Trial 30 วันสำเร็จ')
            ->assertJsonPath('data.license_type', 'demo')
            ->assertJsonPath('data.days_remaining', 30)
            ->assertJsonPath('data.seconds_remaining', 30 * 24 * 3600)
            ->assertJsonPath('data.expires_at', now()->addDays(30)->toISOString())
            ->assertJsonPath('data.features', ['all_features', 'trial_mode'])
            // no checkout of LocalVPN gives an early-bird discount — the answer must not promise one
            ->assertJsonPath('data.early_bird', ['eligible' => false]);

        $demo = $response->json('data.license_key');
        $this->assertStringStartsWith('DEMO-', $demo);

        // the FREE key is still there, but the machine now runs the trial
        $this->checkMachine('a')->assertOk()
            ->assertJsonPath('data.license_key', $demo)
            ->assertJsonPath('data.license_type', 'demo');

        $this->validateKey('a', $demo)->assertOk()
            ->assertJsonPath('is_valid', true)
            ->assertJsonMissingPath('license_changed');

        // an app still holding the FREE key is told to switch to the trial
        $this->validateKey('a', $free)->assertOk()
            ->assertJsonPath('license_changed', true)
            ->assertJsonPath('data.license_key', $demo);

        $this->demoCheck('a')->assertOk()
            ->assertJsonPath('data.is_trial_active', true)
            ->assertJsonPath('data.can_start_demo', false);
    }

    public function test_an_existing_free_device_can_start_the_trial_exactly_once(): void
    {
        // a device from before the trial existed: FREE key from check-machine, and the app once typed
        // that key into "activate", which marks the device row as licensed
        $free = $this->checkMachine('a')->json('data.license_key');
        $this->activate('a', $free)->assertOk();
        $this->assertSame(ProductDevice::STATUS_LICENSED, $this->device('a')->status);

        $this->registerDevice('a', self::PC_HASH)->assertOk()->assertJsonPath('data.can_start_trial', true);
        $this->demo('a', self::PC_HASH)->assertOk()->assertJsonPath('data.days_remaining', 30);

        // a second tap while it runs
        $this->demo('a', self::PC_HASH)->assertForbidden()->assertJsonPath('error_code', 'TRIAL_ACTIVE');

        // and never again once it has ended
        $this->travel(31)->days();
        $this->demo('a', self::PC_HASH)->assertForbidden()->assertJsonPath('error_code', 'TRIAL_ALREADY_USED');
        $this->demoCheck('a')->assertOk()
            ->assertJsonPath('data.has_used_demo', true)
            ->assertJsonPath('data.can_start_demo', false);

        // told no, not accused of anything
        $device = $this->device('a');
        $this->assertFalse($device->is_suspicious);
        $this->assertNotSame(ProductDevice::STATUS_BLOCKED, $device->status);
        $this->assertSame(1, LicenseKey::where('product_id', $this->product->id)->where('license_type', 'demo')->count());
    }

    public function test_a_device_that_had_the_old_one_day_demo_does_not_get_another_trial(): void
    {
        LicenseKey::create([
            'product_id' => $this->product->id,
            'license_key' => LicenseKey::generateDemoKey(),
            'license_type' => 'demo',
            'status' => 'active',
            'machine_id' => $this->machineId('a'),
            'expires_at' => now()->subMonths(3),
            'max_activations' => 1,
            'activations' => 1,
        ]);

        $this->demo('a', self::PC_HASH)->assertForbidden()->assertJsonPath('error_code', 'TRIAL_ALREADY_USED');
    }

    public function test_the_device_record_alone_remembers_a_used_trial(): void
    {
        // the DEMO key was deleted in the admin; the device row still counts its attempt
        $demo = $this->demo('a', null)->json('data.license_key');
        LicenseKey::where('license_key', $demo)->delete();
        $this->travel(31)->days();

        $this->demo('a', null)->assertForbidden()->assertJsonPath('error_code', 'TRIAL_ALREADY_USED');
    }

    public function test_reinstalling_does_not_restart_the_trial_on_the_same_hardware(): void
    {
        $this->registerDevice('a', self::PC_HASH)->assertOk();
        $this->demo('a', self::PC_HASH)->assertOk();

        // Windows reinstalled / app data wiped: a new machine id, the same SMBIOS-based hash
        $this->registerDevice('b', self::PC_HASH)->assertOk()->assertJsonPath('data.can_start_trial', false);
        $this->demoCheck('b')->assertOk()->assertJsonPath('data.can_start_demo', false);
        $this->demo('b', self::PC_HASH)->assertForbidden()->assertJsonPath('error_code', 'TRIAL_USED_ON_THIS_HARDWARE');
        $this->assertFalse($this->device('b')->is_suspicious);

        // other hardware keeps its own trial
        $this->demo('c', self::PHONE_HASH)->assertOk()->assertJsonPath('data.days_remaining', 30);
    }

    public function test_an_android_phone_is_recognised_by_its_drm_and_android_ids_too(): void
    {
        $this->demo('a', self::PHONE_HASH, ['drm_id' => 'drm-widevine-0001', 'android_id' => 'android0001'])->assertOk();

        // same phone, the hash recipe changed in an update: MediaDrm id still matches
        $this->demo('b', str_repeat('1', 64), ['drm_id' => 'drm-widevine-0001'])
            ->assertForbidden()->assertJsonPath('error_code', 'TRIAL_USED_ON_THIS_HARDWARE');

        // ANDROID_ID alone matches as well
        $this->demo('c', str_repeat('2', 64), ['android_id' => 'android0001'])
            ->assertForbidden()->assertJsonPath('error_code', 'TRIAL_USED_ON_THIS_HARDWARE');
    }

    public function test_friends_behind_one_router_each_get_their_trial(): void
    {
        // the old shared-IP rule turned the third device on one address away; hardware ids decide now
        foreach (['a', 'b', 'c', 'd'] as $i => $machine) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
                ->postJson('/api/v1/product/localvpn/demo', [
                    'machine_id' => $this->machineId($machine),
                    'hardware_hash' => str_repeat(dechex($i + 3), 64),
                ])
                ->assertOk();
        }
    }

    // ── after the trial ─────────────────────────────────────────────────

    public function test_when_the_trial_ends_the_device_is_handed_its_free_key_again(): void
    {
        // a device that started its trial before it ever asked check-machine — it has no FREE key yet
        $demo = $this->demo('a', self::PC_HASH)->json('data.license_key');
        $this->assertSame(0, LicenseKey::where('license_type', 'free')->count());

        $this->travel(30)->days();
        $this->travel(1)->minutes();

        $response = $this->validateKey('a', $demo)->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_valid', true)
            ->assertJsonPath('license_changed', true)
            ->assertJsonPath('previous_license.license_key', $demo)
            ->assertJsonPath('previous_license.license_type', 'demo')
            ->assertJsonPath('previous_license.is_expired', true)
            ->assertJsonPath('data.license_type', 'free')
            ->assertJsonPath('data.is_expired', false);

        $free = $response->json('data.license_key');
        $this->assertMatchesRegularExpression('/^FREE-[A-Z0-9]{20}$/', $free);

        // check-machine agrees, and nothing was duplicated
        $this->checkMachine('a')->assertOk()
            ->assertJsonPath('data.license_key', $free)
            ->assertJsonPath('data.license_type', 'free');
        $this->assertSame(1, LicenseKey::where('license_type', 'free')->count());

        // the free tier keeps working on the LocalVPN APIs with either key
        $this->postJson('/api/v1/localvpn/torrent/profile', ['machine_id' => $this->machineId('a'), 'license_key' => $demo])->assertOk();
        $this->getJson('/api/v1/localvpn/wireguard/servers?' . http_build_query(['machine_id' => $this->machineId('a'), 'license_key' => $demo]))
            ->assertOk()->assertJsonPath('is_premium', false);
    }

    public function test_the_free_key_it_already_had_comes_back_rather_than_a_new_one(): void
    {
        $free = $this->checkMachine('a')->json('data.license_key');
        $demo = $this->demo('a', self::PC_HASH)->json('data.license_key');

        $this->travel(31)->days();

        $this->validateKey('a', $demo)->assertOk()->assertJsonPath('data.license_key', $free);
        $this->checkMachine('a')->assertOk()->assertJsonPath('data.license_key', $free);
        $this->assertSame(1, LicenseKey::where('license_type', 'free')->count());
    }

    public function test_other_products_keep_answering_is_valid_false_for_an_ended_trial(): void
    {
        $antiX = Product::where('slug', 'anti-x')->firstOrFail();
        $machine = str_repeat('e', 40);

        $demo = $this->postJson('/api/v1/product/anti-x/demo', ['machine_id' => $machine, 'hardware_hash' => self::PC_HASH])
            ->assertOk()->assertJsonPath('data.days_remaining', 14)->json('data.license_key');

        $this->travel(15)->days();

        $this->postJson('/api/v1/product/anti-x/validate', ['machine_id' => $machine, 'license_key' => $demo])
            ->assertOk()
            ->assertJsonPath('is_valid', false)
            ->assertJsonMissingPath('license_changed');
        $this->assertSame(0, LicenseKey::where('product_id', $antiX->id)->where('license_type', 'free')->count());
    }

    // ── the trial is Pro on the LocalVPN APIs ───────────────────────────

    public function test_the_trial_unlocks_every_wireguard_country(): void
    {
        $this->server('SG');
        $this->server('FR');

        $free = $this->checkMachine('a')->json('data.license_key');
        $this->wireguardServers('a', $free)->assertOk()
            ->assertJsonPath('is_premium', false)
            ->assertJsonCount(1, 'servers')
            ->assertJsonCount(1, 'locked_servers');

        $demo = $this->demo('a', self::PC_HASH)->json('data.license_key');
        $this->wireguardServers('a', $demo)->assertOk()
            ->assertJsonPath('is_premium', true)
            ->assertJsonCount(2, 'servers')
            ->assertJsonCount(0, 'locked_servers');

        // a Pro-only country is no longer refused — even when the app still sends its FREE key
        $this->postJson('/api/v1/localvpn/wireguard/register', [
            'machine_id' => $this->machineId('a'),
            'license_key' => $free,
            'public_key' => base64_encode(random_bytes(32)),
            'country_code' => 'FR',
        ])->assertOk()->assertJsonPath('success', true)->assertJsonPath('server.country_code', 'FR');

        $this->travel(31)->days();
        $this->wireguardServers('a', $demo)->assertOk()->assertJsonPath('is_premium', false);
    }

    public function test_the_trial_unlocks_every_proxy_country(): void
    {
        Cache::put('vpngate_servers_healthy', [
            ['country_code' => 'JP', 'country_name' => 'Japan', 'score' => 10, 'speed' => 5, 'hostname' => 'jp1'],
            ['country_code' => 'FR', 'country_name' => 'France', 'score' => 10, 'speed' => 5, 'hostname' => 'fr1'],
        ], 600);

        $this->getJson('/api/v1/localvpn/proxy-servers?machine_id=' . $this->machineId('a'))
            ->assertOk()->assertJsonPath('is_premium', false)->assertJsonCount(1, 'countries');

        $this->demo('a', self::PC_HASH)->assertOk();

        $this->getJson('/api/v1/localvpn/proxy-servers?machine_id=' . $this->machineId('a'))
            ->assertOk()->assertJsonPath('is_premium', true)->assertJsonCount(2, 'countries');
    }

    public function test_a_network_owned_by_a_trial_device_takes_more_than_five_members(): void
    {
        $demo = $this->demo('o', self::PC_HASH)->json('data.license_key');
        $slug = $this->createNetwork('o', $demo);

        // the owner is member 1; members 2..7 join — past the free tier's 5
        foreach (['b', 'c', 'd', 'e', 'f', 'g'] as $machine) {
            $this->join($slug, $machine)->assertSuccessful()->assertJsonPath('success', true);
        }

        // once the trial is over the network is a free one again: no 8th member
        $this->travel(31)->days();
        $this->join($slug, 'h')->assertForbidden()
            ->assertJsonPath('error', 'Network is limited to 5 members. Owner must upgrade to premium.');
    }

    public function test_a_free_owners_network_stops_at_five_members(): void
    {
        $free = $this->checkMachine('o')->json('data.license_key');
        $slug = $this->createNetwork('o', $free);

        foreach (['b', 'c', 'd', 'e'] as $machine) {
            $this->join($slug, $machine)->assertSuccessful();
        }

        $this->join($slug, 'f')->assertForbidden();
    }

    public function test_a_paid_key_of_another_product_does_not_unlock_a_localvpn_network(): void
    {
        $owner = User::factory()->create();
        $paid = $this->paidKey(['user_id' => $owner->id]);
        $this->activate('o', $paid->license_key)->assertOk();
        $slug = $this->createNetwork('o', $paid->license_key);
        $this->assertSame($owner->id, VpnNetwork::where('slug', $slug)->value('owner_user_id'));

        // the LocalVPN key goes; the owner still has a paid Anti X key
        $paid->update(['status' => 'revoked']);
        LicenseKey::create([
            'product_id' => Product::where('slug', 'anti-x')->value('id'),
            'license_key' => LicenseKey::generateKey(),
            'license_type' => 'lifetime',
            'status' => 'active',
            'user_id' => $owner->id,
            'max_activations' => 1,
            'activations' => 0,
        ]);

        foreach (['b', 'c', 'd', 'e'] as $machine) {
            $this->join($slug, $machine)->assertSuccessful();
        }
        $this->join($slug, 'f')->assertForbidden();
    }

    // ── one key, two devices ────────────────────────────────────────────

    public function test_one_paid_key_runs_on_a_pc_and_a_phone(): void
    {
        $key = $this->paidKey();

        $this->activate('p', $key->license_key)->assertOk()
            ->assertJsonPath('data.devices_used', 1)
            ->assertJsonPath('data.max_devices', 2);
        $this->activate('m', $key->license_key)->assertOk()
            ->assertJsonPath('data.devices_used', 2);

        // both machines run it — the key's own machine and the phone's seat
        foreach (['p', 'm'] as $machine) {
            $this->validateKey($machine, $key->license_key)->assertOk()
                ->assertJsonPath('is_valid', true)
                ->assertJsonMissingPath('license_changed');
            $this->checkMachine($machine)->assertOk()->assertJsonPath('data.license_key', $key->license_key);
        }
        $this->assertSame($this->machineId('p'), $key->fresh()->machine_id);
        $this->assertSame(2, $key->fresh()->activations);

        // the phone is Pro on the LocalVPN APIs too
        $this->server('FR');
        $this->wireguardServers('m', $key->license_key)->assertOk()->assertJsonPath('is_premium', true);

        // a third device is refused …
        $this->activate('x', $key->license_key)->assertForbidden()
            ->assertJsonPath('error_code', 'ALREADY_ACTIVATED_OTHER_DEVICE')
            ->assertJsonPath('max_devices', 2);

        // … until the phone lets go of its place; the PC never notices
        $this->deactivate('m', $key->license_key)->assertOk()->assertJsonPath('data.can_reactivate', true);
        $this->validateKey('m', $key->license_key)->assertNotFound();
        $this->activate('x', $key->license_key)->assertOk();
        $this->validateKey('p', $key->license_key)->assertOk()->assertJsonPath('is_valid', true);
        $this->validateKey('x', $key->license_key)->assertOk()->assertJsonPath('is_valid', true);
    }

    public function test_moving_a_full_key_frees_the_device_seen_least_recently(): void
    {
        $key = $this->paidKey();
        $this->activate('p', $key->license_key)->assertOk();
        $this->activate('m', $key->license_key)->assertOk();

        // the PC keeps being used; the old phone is not seen again
        $this->travel(3)->days();
        $this->validateKey('p', $key->license_key)->assertOk();

        $this->activate('n', $key->license_key, ['force_rebind' => 'true'])->assertOk();

        $this->validateKey('p', $key->license_key)->assertOk()->assertJsonPath('is_valid', true);
        $this->validateKey('n', $key->license_key)->assertOk()->assertJsonPath('is_valid', true);
        $this->validateKey('m', $key->license_key)->assertNotFound();
        // the old phone falls back to the free tier
        $this->checkMachine('m')->assertOk()->assertJsonPath('data.license_type', 'free');
    }

    public function test_every_purchased_localvpn_key_gets_two_devices(): void
    {
        // through the site cart
        app(LicenseService::class)->generateLicensesForOrder($this->paidOrder($this->product, 'yearly', 1990));
        $this->assertSame(2, LicenseKey::where('product_id', $this->product->id)->sole()->max_activations);

        // other products stay one key, one machine
        $antiX = Product::where('slug', 'anti-x')->firstOrFail();
        app(LicenseService::class)->generateLicensesForOrder($this->paidOrder($antiX, 'monthly', 199));
        $this->assertSame(1, LicenseKey::where('product_id', $antiX->id)->sole()->max_activations);
    }

    public function test_the_apps_own_checkout_issues_a_two_device_key_too(): void
    {
        $user = User::factory()->create();
        Wallet::getOrCreateForUser($user->id)->update(['balance' => 10000]);

        $this->actingAs($user)->post(route('localvpn.process', 'lifetime'), [
            'customer_name' => 'Tee',
            'customer_email' => 'tee@example.com',
            'customer_phone' => '0800000000',
            'payment_method' => 'wallet',
            'machine_id' => $this->machineId('p'),
        ])->assertRedirect();

        $key = LicenseKey::where('product_id', $this->product->id)->where('license_type', 'lifetime')->sole();
        $this->assertSame(2, $key->max_activations);
        $this->assertSame($this->machineId('p'), $key->machine_id);

        // the phone takes the second place
        $this->activate('m', $key->license_key)->assertOk()->assertJsonPath('data.devices_used', 2);
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function paidKey(array $attributes = []): LicenseKey
    {
        return LicenseKey::create($attributes + [
            'product_id' => $this->product->id,
            'license_key' => LicenseKey::generateKey(),
            'license_type' => 'yearly',
            'status' => 'active',
            'expires_at' => now()->addYear(),
            'max_activations' => 2,
            'activations' => 0,
        ]);
    }

    private function paidOrder(Product $product, string $term, int $price): Order
    {
        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '0800000000',
            'subtotal' => $price,
            'total' => $price,
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => $price,
            'quantity' => 1,
            'subtotal' => $price,
            'custom_requirements' => json_encode(['license_type' => $term]),
        ]);

        return $order;
    }

    private function server(string $country): WireguardServer
    {
        return WireguardServer::create([
            'name' => "wg-{$country}",
            'country_code' => $country,
            'country_name' => $country,
            'endpoint' => strtolower($country) . '.example.net:51820',
            'public_key' => 'pub-' . $country,
            'private_key' => 'priv-' . $country,
            'address' => '10.20.0.1/24',
        ]);
    }

    private function createNetwork(string $machine, string $key): string
    {
        return $this->fromDevice($machine)->postJson('/api/v1/localvpn/networks', [
            'name' => 'Friday LAN ' . $machine,
            'machine_id' => $this->machineId($machine),
            'license_key' => $key,
            'max_members' => 50,
        ])->assertCreated()->json('network.slug');
    }

    private function join(string $slug, string $machine): TestResponse
    {
        return $this->fromDevice($machine)->postJson('/api/v1/localvpn/networks/join', [
            'slug' => $slug,
            'machine_id' => $this->machineId($machine),
            'display_name' => 'Player ' . $machine,
        ]);
    }

    private function wireguardServers(string $machine, string $key): TestResponse
    {
        return $this->getJson('/api/v1/localvpn/wireguard/servers?' . http_build_query([
            'machine_id' => $this->machineId($machine),
            'license_key' => $key,
        ]));
    }

    private function device(string $machine): ProductDevice
    {
        return ProductDevice::where('product_id', $this->product->id)->where('machine_id', $this->machineId($machine))->sole();
    }

    private function registerDevice(string $machine, ?string $hash): TestResponse
    {
        return $this->fromDevice($machine)->postJson('/api/v1/product/localvpn/register-device', array_filter([
            'machine_id' => $this->machineId($machine),
            'machine_name' => 'PC-' . strtoupper($machine),
            'os_version' => 'Windows 11',
            'app_version' => '1.1.0',
            'hardware_hash' => $hash,
        ]));
    }

    private function demo(string $machine, ?string $hash, array $extra = []): TestResponse
    {
        return $this->fromDevice($machine)->postJson('/api/v1/product/localvpn/demo', array_filter([
            'machine_id' => $this->machineId($machine),
            'hardware_hash' => $hash,
        ]) + $extra);
    }

    private function demoCheck(string $machine): TestResponse
    {
        return $this->fromDevice($machine)->postJson('/api/v1/product/localvpn/demo/check', ['machine_id' => $this->machineId($machine)]);
    }

    private function checkMachine(string $machine): TestResponse
    {
        return $this->fromDevice($machine)->postJson('/api/v1/product/localvpn/check-machine', ['machine_id' => $this->machineId($machine)]);
    }

    private function activate(string $machine, string $key, array $extra = []): TestResponse
    {
        return $this->fromDevice($machine)->postJson('/api/v1/product/localvpn/activate', [
            'license_key' => $key,
            'machine_id' => $this->machineId($machine),
            'machine_fingerprint' => 'fp-' . $machine,
            'app_version' => '1.1.0',
        ] + $extra);
    }

    private function validateKey(string $machine, string $key): TestResponse
    {
        return $this->fromDevice($machine)->postJson('/api/v1/product/localvpn/validate', [
            'license_key' => $key,
            'machine_id' => $this->machineId($machine),
        ]);
    }

    private function deactivate(string $machine, string $key): TestResponse
    {
        return $this->fromDevice($machine)->postJson('/api/v1/product/localvpn/deactivate', [
            'license_key' => $key,
            'machine_id' => $this->machineId($machine),
        ]);
    }

    /** each device on its own address unless a test says otherwise */
    private function fromDevice(string $machine): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.' . (ord($machine) - 90)]);
    }

    private function machineId(string $machine): string
    {
        return str_repeat($machine, 40);
    }
}
