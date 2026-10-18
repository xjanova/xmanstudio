<?php

namespace Tests\Feature;

use App\Models\BlockedIp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Honeypot decoy logins: fake WordPress / phpMyAdmin pages that log and ban scanners,
 * never authenticate anything, and can never catch our own people.
 */
class DecoyLoginTest extends TestCase
{
    use RefreshDatabase;

    private string $log;

    protected function setUp(): void
    {
        parent::setUp();
        $this->log = tempnam(sys_get_temp_dir(), 'decoy') ?: storage_path('logs/decoy-test.log');
        @unlink($this->log);
        config(['decoy.enabled' => true, 'decoy.block' => true, 'decoy.log' => $this->log, 'decoy.safe_ips' => []]);
    }

    protected function tearDown(): void
    {
        @unlink($this->log);
        parent::tearDown();
    }

    private function asIp(string $ip)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }

    public function test_a_bot_posting_to_wp_login_is_logged_and_banned_for_good(): void
    {
        $res = $this->asIp('45.137.21.9')->post('/wp-login.php', ['log' => 'admin', 'pwd' => 'admin123']);

        $res->assertStatus(401);
        $block = BlockedIp::where('ip', '45.137.21.9')->first();
        $this->assertNotNull($block);
        $this->assertNull($block->expires_at, 'honeypot ban is permanent');

        $this->assertFileExists($this->log);
        $line = file_get_contents($this->log);
        $this->assertStringContainsString('ANTIX-DECOY ip=45.137.21.9 port=', $line);
        $this->assertStringContainsString('user=admin', $line);
        $this->assertStringContainsString('pass=admin123', $line);
    }

    public function test_the_credentials_are_escaped_onto_one_line(): void
    {
        $this->asIp('45.137.21.10')->post('/wp-login.php', ['log' => "a\r\nb", 'pwd' => "x\ny"]);
        $line = file_get_contents($this->log);
        $this->assertSame(1, substr_count(rtrim($line, "\n"), "\n") + 1, 'one log line per hit');
        $this->assertStringContainsString('user=a\\r\\nb', $line);
    }

    public function test_getting_the_page_shows_a_login_form_and_does_not_ban(): void
    {
        $res = $this->asIp('45.137.21.11')->get('/wp-login.php');
        $res->assertStatus(200)->assertSee('Site Administration');
        $this->assertNull(BlockedIp::where('ip', '45.137.21.11')->first());
        $this->assertFileDoesNotExist($this->log);
    }

    public function test_when_disabled_the_paths_are_a_normal_404(): void
    {
        config(['decoy.enabled' => false]);
        $this->asIp('45.137.21.12')->get('/wp-login.php')->assertStatus(404);
        $this->asIp('45.137.21.12')->post('/phpmyadmin', ['pwd' => 'x'])->assertStatus(404);
        $this->assertNull(BlockedIp::where('ip', '45.137.21.12')->first());
    }

    public function test_our_own_and_allow_listed_addresses_are_never_banned(): void
    {
        // ภายใน
        $this->asIp('127.0.0.1')->post('/wp-login.php', ['log' => 'x', 'pwd' => 'y'])->assertStatus(401);
        $this->assertNull(BlockedIp::where('ip', '127.0.0.1')->first());

        // allow-list
        config(['decoy.safe_ips' => ['203.0.113.7']]);
        $this->asIp('203.0.113.7')->post('/phpmyadmin', ['pwd' => 'y'])->assertStatus(401);
        $this->assertNull(BlockedIp::where('ip', '203.0.113.7')->first());
        $this->assertFileDoesNotExist($this->log);
    }

    public function test_a_signed_in_user_is_never_banned_by_a_decoy_path(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->asIp('45.137.21.13')->post('/administrator', ['log' => 'x', 'pwd' => 'y'])->assertStatus(401);
        $this->assertNull(BlockedIp::where('ip', '45.137.21.13')->first());
    }

    public function test_the_real_login_route_is_untouched(): void
    {
        $this->get('/login')->assertStatus(200);
    }
}
