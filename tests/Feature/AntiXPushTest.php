<?php

namespace Tests\Feature;

use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\ProductDevice;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Anti X phone alerts relayed by this site (owner, 2026-10-21: "push ผ่าน xman4289.com")
 *
 *   — only a PC registered for Anti X can ask; a key, when sent, must be an Anti X key
 *   — the text is fixed here: level and a number, never an address or a server name
 *   — tokens Firebase no longer knows come back so the PC drops them
 *   — 30 an hour per machine; nothing goes out before the Firebase key is uploaded in admin
 */
class AntiXPushTest extends TestCase
{
    use RefreshDatabase;

    private const MACHINE = 'f0e1d2c3b4a5968778695a4b3c2d1e0ff0e1d2c3b4a5968778695a4b3c2d1e0f';

    private Product $antiX;

    protected function setUp(): void
    {
        parent::setUp();
        $this->antiX = Product::where('slug', 'anti-x')->firstOrFail();
        ProductDevice::create(['product_id' => $this->antiX->id, 'machine_id' => self::MACHINE, 'first_seen_at' => now(), 'last_seen_at' => now()]);
        RateLimiter::clear('anti-x-push:' . self::MACHINE);
    }

    private function configure(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        $path = storage_path('framework/testing/antix-sa-' . uniqid() . '.json');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, json_encode([
            'type' => 'service_account', 'project_id' => 'anti-x-mobile',
            'client_email' => 'firebase-adminsdk@anti-x-mobile.iam.gserviceaccount.com', 'private_key' => $pem,
        ]));
        Setting::setValue('antix_fcm_credentials_path', $path, 'string', 'antix_push');
    }

    private function fakeGoogle(array $deadTokens = []): void
    {
        Http::fake(function (ClientRequest $r) use ($deadTokens) {
            if (str_starts_with($r->url(), 'https://oauth2.googleapis.com/token')) {
                return Http::response(['access_token' => 'ya29.test', 'expires_in' => 3600]);
            }
            $token = $r['message']['token'] ?? '';

            return in_array($token, $deadTokens, true)
                ? Http::response(['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404)
                : Http::response(['name' => 'projects/anti-x-mobile/messages/1']);
        });
    }

    private function ask(array $over = [])
    {
        return $this->postJson('/api/v1/product/anti-x/push', array_merge([
            'machine_id' => self::MACHINE, 'tokens' => ['tok-a', 'tok-b'], 'level' => 'critical', 'count' => 12,
        ], $over));
    }

    public function test_a_registered_pc_wakes_its_phones_with_a_fixed_message(): void
    {
        $this->configure();
        $this->fakeGoogle(deadTokens: ['tok-b']);

        $this->ask()->assertOk()->assertExactJson(['success' => true, 'data' => ['sent' => 1, 'invalid' => ['tok-b']]]);

        Http::assertSent(function (ClientRequest $r) {
            if (! str_contains($r->url(), 'fcm.googleapis.com/v1/projects/anti-x-mobile/messages:send')) {
                return false;
            }
            $m = $r['message'];

            return $m['token'] === 'tok-a'
                && $m['notification'] === ['title' => 'Anti X', 'body' => 'พบเหตุการณ์ร้ายแรงบนเครื่องที่ดูแล (12 รายการ) — เปิดแอปเพื่อดู']
                && $m['data'] === ['type' => 'antix_alert', 'level' => 'critical']
                && $m['android']['notification']['channel_id'] === 'antix_alerts'
                && $r->hasHeader('Authorization', 'Bearer ya29.test');
        });
    }

    public function test_unknown_machines_wrong_keys_and_bad_tokens_are_refused(): void
    {
        $this->configure();
        $this->fakeGoogle();

        $this->ask(['machine_id' => str_repeat('a', 64)])->assertStatus(403)->assertJson(['error_code' => 'UNKNOWN_MACHINE']);

        // คีย์ฟรีของแอปมือถือ ไม่ใช่คีย์ Anti X
        $other = Product::where('slug', 'anti-x-mobile')->firstOrFail();
        LicenseKey::create(['product_id' => $other->id, 'license_key' => 'WINX-AAAA-BBBB-CCCC', 'license_type' => 'yearly', 'status' => 'active', 'max_activations' => 1]);
        $this->ask(['license_key' => 'winx-aaaa-bbbb-cccc'])->assertStatus(403)->assertJson(['error_code' => 'INVALID_LICENSE']);

        $this->ask(['tokens' => ['has space']])->assertStatus(422);
        $this->ask(['level' => 'everything'])->assertStatus(422);
        $this->ask(['tokens' => array_fill(0, 21, 'x')])->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_nothing_goes_out_before_the_key_is_uploaded_and_it_is_rate_limited(): void
    {
        $this->fakeGoogle();
        $this->ask()->assertStatus(503)->assertJson(['error_code' => 'PUSH_NOT_CONFIGURED']);
        Http::assertNothingSent();

        $this->configure();
        for ($i = 0; $i < 30; $i++) {
            $this->ask(['level' => 'test', 'tokens' => ['tok-a']])->assertOk();
        }
        $this->ask(['level' => 'test', 'tokens' => ['tok-a']])->assertStatus(429)->assertJson(['error_code' => 'RATE_LIMITED']);
    }

    public function test_admin_uploads_the_service_account_and_only_that_kind_of_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.anti-x-push.edit'))->assertOk()->assertSee('ยังส่งแจ้งเตือนไม่ได้');

        $bad = UploadedFile::fake()->createWithContent('x.json', json_encode(['hello' => 'world']));
        $this->actingAs($admin)->post(route('admin.anti-x-push.update'), ['project_id' => 'anti-x-mobile', 'credentials' => $bad])
            ->assertSessionHas('error');
        $otherProject = UploadedFile::fake()->createWithContent('y.json', json_encode([
            'type' => 'service_account', 'project_id' => 'netwix-online', 'client_email' => 'a@b', 'private_key' => 'k',
        ]));
        $this->actingAs($admin)->post(route('admin.anti-x-push.update'), ['project_id' => 'anti-x-mobile', 'credentials' => $otherProject])
            ->assertSessionHas('error');

        $good = UploadedFile::fake()->createWithContent('sa.json', json_encode([
            'type' => 'service_account', 'project_id' => 'anti-x-mobile',
            'client_email' => 'firebase-adminsdk@anti-x-mobile.iam.gserviceaccount.com', 'private_key' => "-----BEGIN PRIVATE KEY-----\nx\n-----END PRIVATE KEY-----\n",
        ]));
        $this->actingAs($admin)->post(route('admin.anti-x-push.update'), ['project_id' => 'anti-x-mobile', 'credentials' => $good])
            ->assertRedirect(route('admin.anti-x-push.edit'));
        $this->actingAs($admin)->get(route('admin.anti-x-push.edit'))
            ->assertSee('firebase-adminsdk@anti-x-mobile.iam.gserviceaccount.com')
            ->assertDontSee('BEGIN PRIVATE KEY');

        $this->get(route('admin.anti-x-push.edit'))->assertOk(); // still the admin session
        auth()->logout();
        $this->get(route('admin.anti-x-push.edit'))->assertRedirect();
        @unlink(storage_path('app/firebase/anti-x-mobile.json'));
    }
}
