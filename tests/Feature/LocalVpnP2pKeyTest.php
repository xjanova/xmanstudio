<?php

namespace Tests\Feature;

use App\Models\VpnNetworkMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * แอป LocalVPN เข้ารหัสข้อมูลระหว่างสมาชิกในวงเอง (X25519 + ChaCha20-Poly1305) เซิร์ฟเวอร์มีหน้าที่แค่ส่งต่อ public key
 *
 *   — แอปส่ง p2p_key ตอนสร้างวง / เข้าวง / heartbeat และได้ p2p_key ของเพื่อนทุกคนคืนใน heartbeat
 *   — สมาชิกที่ไม่มี key ได้ p2p_key = null (ฟิลด์มีเสมอ แอปจึงแยกออกว่าใครเข้ารหัสไม่ได้)
 *   — key ที่รูปแบบผิดถูกปฏิเสธ เซิร์ฟเวอร์ไม่ตีความ key เกินกว่านั้น
 */
class LocalVpnP2pKeyTest extends TestCase
{
    use RefreshDatabase;

    private const KEY_A = 'v2:AAECAwQFBgcICQoLDA0ODxAREhMUFRYXGBkaGxwdHh8=';

    private const KEY_B = 'v2:ICEiIyQlJicoKSorLC0uLzAxMjM0NTY3ODk6Ozw9Pj8=';

    public function test_keys_are_stored_and_relayed_to_every_member(): void
    {
        $slug = $this->createNetwork('owner', self::KEY_A);
        $this->join($slug, 'friend', self::KEY_B)->assertCreated();

        $peers = collect($this->heartbeat($slug, 'friend', self::KEY_B)->assertOk()->json('peers'))
            ->keyBy('display_name');

        $this->assertSame(self::KEY_A, $peers['owner']['p2p_key']);
        $this->assertSame(self::KEY_B, $peers['friend']['p2p_key']);
    }

    public function test_members_without_a_key_are_reported_as_null(): void
    {
        $slug = $this->createNetwork('owner', null);
        $this->join($slug, 'friend', null)->assertCreated();

        $peers = $this->heartbeat($slug, 'friend', null)->assertOk()->json('peers');

        $this->assertArrayHasKey('p2p_key', $peers[0]);
        $this->assertNull($peers[0]['p2p_key']);
    }

    public function test_the_heartbeat_keeps_the_key_current(): void
    {
        $slug = $this->createNetwork('owner', self::KEY_A);

        $this->heartbeat($slug, 'owner', self::KEY_B)->assertOk();
        $this->assertSame(self::KEY_B, VpnNetworkMember::where('machine_id', 'machine-owner')->value('p2p_key'));

        $this->join($slug, 'owner', self::KEY_A)->assertOk();
        $this->assertSame(self::KEY_A, VpnNetworkMember::where('machine_id', 'machine-owner')->value('p2p_key'));
    }

    public function test_malformed_keys_are_rejected(): void
    {
        $slug = $this->createNetwork('owner', null);

        foreach (['not-a-key', 'v2:short=', 'v2:' . str_repeat('A', 43) . '=;drop', str_repeat('A', 44)] as $bad) {
            $this->join($slug, 'friend', $bad)->assertStatus(422);
            $this->heartbeat($slug, 'owner', $bad)->assertStatus(422);
        }
    }

    private function createNetwork(string $who, ?string $key): string
    {
        return $this->postJson('/api/v1/localvpn/networks', array_filter([
            'name' => 'LAN party ' . $who,
            'machine_id' => 'machine-' . $who,
            'display_name' => $who,
            'p2p_key' => $key,
        ]))->assertCreated()->json('network.slug');
    }

    private function join(string $slug, string $who, ?string $key)
    {
        return $this->postJson('/api/v1/localvpn/networks/join', array_filter([
            'slug' => $slug,
            'machine_id' => 'machine-' . $who,
            'display_name' => $who,
            'p2p_key' => $key,
        ]));
    }

    private function heartbeat(string $slug, string $who, ?string $key)
    {
        return $this->postJson('/api/v1/localvpn/heartbeat', array_filter([
            'slug' => $slug,
            'machine_id' => 'machine-' . $who,
            'p2p_key' => $key,
        ]));
    }
}
