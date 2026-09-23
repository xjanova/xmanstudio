<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\RedisSettingsController;
use App\Models\Setting;
use App\Models\User;
use Dotenv\Parser\Parser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Secrets the admin panel keeps: stored encrypted, never printed back into the
 * page, and never able to become a second line in .env.
 */
class SecretSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_migration_encrypts_plaintext_secrets_in_place(): void
    {
        DB::table('settings')->insert([
            ['key' => 'line_channel_secret', 'value' => 'plain-secret', 'type' => 'string', 'group' => 'line'],
            ['key' => 'freepik_api_key', 'value' => Crypt::encryptString('already-sealed'), 'type' => 'string', 'group' => 'metalx'],
        ]);

        $migration = require database_path('migrations/2026_09_23_100000_encrypt_remaining_secret_settings.php');
        $migration->up();

        $stored = DB::table('settings')->where('key', 'line_channel_secret')->value('value');
        $this->assertNotSame('plain-secret', $stored);
        $this->assertSame('plain-secret', Crypt::decryptString($stored));
        $this->assertSame('plain-secret', Setting::getValue('line_channel_secret'));

        // Encrypted once, not twice.
        $this->assertSame('already-sealed', Setting::getValue('freepik_api_key'));
    }

    public function test_the_line_settings_page_does_not_print_stored_secrets(): void
    {
        Setting::setValue('line_channel_access_token', 'tok-SECRET-123', 'line');
        Setting::setValue('line_channel_secret', 'sec-SECRET-456', 'line');

        $admin = User::create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'password' => bcrypt('secret-password'),
            'is_active' => true,
            'role' => 'super_admin',
        ]);

        $html = $this->actingAs($admin)->get(route('admin.line-settings.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('tok-SECRET-123', $html);
        $this->assertStringNotContainsString('sec-SECRET-456', $html);
        $this->assertStringContainsString('name="line_channel_secret" value=""', $html);
    }

    public function test_an_env_value_is_always_one_line_and_reads_back_exactly(): void
    {
        $envValue = new ReflectionMethod(RedisSettingsController::class, 'envValue');
        $parser = new Parser;

        foreach (['plain', 'with space', 'a#b', 'a$b', 'a"b', 'back\\slash', '${APP_KEY}', "two\nlines"] as $value) {
            $line = 'REDIS_PASSWORD=' . $envValue->invoke(null, $value);
            $entries = $parser->parse($line);

            $this->assertStringNotContainsString("\n", $line);
            $this->assertCount(1, $entries);
            $this->assertSame(
                str_replace("\n", '', $value),
                $entries[0]->getValue()->get()->getChars(),
                $line
            );
        }
    }
}
