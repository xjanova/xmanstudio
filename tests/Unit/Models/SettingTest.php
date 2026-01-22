<?php

namespace Tests\Unit\Models;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    /** @test */
    public function it_encrypts_sensitive_api_keys_when_storing()
    {
        $apiKey = 'sk-1234567890abcdef';

        Setting::setValue('ai_openai_key', $apiKey);

        $setting = Setting::where('key', 'ai_openai_key')->first();

        // The stored value should be encrypted (not plaintext)
        $this->assertNotEquals($apiKey, $setting->value);
        $this->assertStringContainsString('eyJpdiI6', $setting->value); // Laravel encrypted format starts with JSON
    }

    /** @test */
    public function it_decrypts_sensitive_keys_when_retrieving()
    {
        $apiKey = 'sk-test-api-key-12345';

        Setting::setValue('ai_openai_key', $apiKey);
        $retrieved = Setting::getValue('ai_openai_key');

        $this->assertEquals($apiKey, $retrieved);
    }

    /** @test */
    public function it_encrypts_all_sensitive_keys()
    {
        $sensitiveKeys = [
            'ai_openai_key' => 'sk-openai-123',
            'ai_claude_key' => 'claude-key-456',
            'youtube_api_key' => 'AIzaSy-youtube-789',
            'youtube_client_secret' => 'secret-abc',
            'youtube_refresh_token' => 'refresh-token-def',
            'line_notify_token' => 'line-token-ghi',
            'smtp_password' => 'smtp-pass-jkl',
        ];

        foreach ($sensitiveKeys as $key => $value) {
            Setting::setValue($key, $value);

            $stored = Setting::where('key', $key)->first();
            $retrieved = Setting::getValue($key);

            // Stored value should be encrypted
            $this->assertNotEquals($value, $stored->value);

            // Retrieved value should be decrypted
            $this->assertEquals($value, $retrieved);
        }
    }

    /** @test */
    public function it_does_not_encrypt_non_sensitive_keys()
    {
        $normalValue = 'just a normal setting value';

        Setting::setValue('app_name', $normalValue);

        $setting = Setting::where('key', 'app_name')->first();

        // Non-sensitive keys should be stored as plaintext
        $this->assertEquals($normalValue, $setting->value);
    }

    /** @test */
    public function it_handles_empty_sensitive_values()
    {
        Setting::setValue('ai_openai_key', '');

        $setting = Setting::where('key', 'ai_openai_key')->first();
        $retrieved = Setting::getValue('ai_openai_key');

        // Empty strings should not be encrypted
        $this->assertEquals('', $setting->value);
        $this->assertEquals('', $retrieved);
    }

    /** @test */
    public function it_returns_default_value_when_setting_not_found()
    {
        $default = 'default-value';

        $value = Setting::getValue('non_existent_key', $default);

        $this->assertEquals($default, $value);
    }

    /** @test */
    public function it_casts_boolean_values_correctly()
    {
        Setting::setValue('feature_enabled', 'true', 'boolean');
        $this->assertTrue(Setting::getValue('feature_enabled'));

        Setting::setValue('feature_disabled', 'false', 'boolean');
        $this->assertFalse(Setting::getValue('feature_disabled'));

        Setting::setValue('feature_one', '1', 'boolean');
        $this->assertTrue(Setting::getValue('feature_one'));

        Setting::setValue('feature_zero', '0', 'boolean');
        $this->assertFalse(Setting::getValue('feature_zero'));
    }

    /** @test */
    public function it_casts_integer_values_correctly()
    {
        Setting::setValue('max_items', '100', 'integer');

        $value = Setting::getValue('max_items');

        $this->assertIsInt($value);
        $this->assertEquals(100, $value);
    }

    /** @test */
    public function it_casts_json_values_correctly()
    {
        $jsonData = ['key1' => 'value1', 'key2' => 'value2', 'nested' => ['key3' => 'value3']];

        Setting::setValue('json_config', $jsonData, 'json');
        $retrieved = Setting::getValue('json_config');

        $this->assertIsArray($retrieved);
        $this->assertEquals($jsonData, $retrieved);
    }

    /** @test */
    public function it_updates_existing_settings()
    {
        Setting::setValue('app_version', '1.0.0');
        $this->assertEquals('1.0.0', Setting::getValue('app_version'));

        Setting::setValue('app_version', '2.0.0');
        $this->assertEquals('2.0.0', Setting::getValue('app_version'));

        // Should only have one record
        $count = Setting::where('key', 'app_version')->count();
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_caches_setting_values()
    {
        Setting::setValue('cached_value', 'test-value');

        // First call - hits database
        $value1 = Setting::getValue('cached_value');

        // Delete from database
        Setting::where('key', 'cached_value')->delete();

        // Second call - should return cached value
        $value2 = Setting::getValue('cached_value');

        $this->assertEquals($value1, $value2);
        $this->assertEquals('test-value', $value2);
    }

    /** @test */
    public function it_clears_cache_when_updating_value()
    {
        Setting::setValue('dynamic_value', 'original');
        $this->assertEquals('original', Setting::getValue('dynamic_value'));

        Setting::setValue('dynamic_value', 'updated');
        $this->assertEquals('updated', Setting::getValue('dynamic_value'));
    }

    /** @test */
    public function it_supports_backward_compatible_get_method()
    {
        Setting::setValue('test_key', 'test_value');

        $value = Setting::get('test_key');

        $this->assertEquals('test_value', $value);
    }

    /** @test */
    public function it_supports_backward_compatible_set_method()
    {
        Setting::set('legacy_key', 'legacy_value');

        $value = Setting::getValue('legacy_key');

        $this->assertEquals('legacy_value', $value);
    }

    /** @test */
    public function it_retrieves_settings_by_group()
    {
        Setting::setValue('ai_provider', 'openai', 'string', 'ai');
        Setting::setValue('ai_model', 'gpt-4', 'string', 'ai');
        Setting::setValue('youtube_enabled', 'true', 'string', 'youtube');

        $aiSettings = Setting::getByGroup('ai');

        $this->assertIsArray($aiSettings);
        $this->assertCount(2, $aiSettings);
        $this->assertArrayHasKey('ai_provider', $aiSettings);
        $this->assertArrayHasKey('ai_model', $aiSettings);
        $this->assertEquals('openai', $aiSettings['ai_provider']);
    }

    /** @test */
    public function it_stores_setting_metadata()
    {
        Setting::setValue(
            'api_key',
            'test-key',
            'string',
            'api',
            'API authentication key',
            false
        );

        $setting = Setting::where('key', 'api_key')->first();

        $this->assertEquals('api', $setting->group);
        $this->assertEquals('string', $setting->type);
        $this->assertEquals('API authentication key', $setting->description);
        $this->assertFalse($setting->is_public);
    }

    /** @test */
    public function it_marks_public_settings()
    {
        Setting::setValue('site_title', 'My Site', 'string', 'general', null, true);

        $setting = Setting::where('key', 'site_title')->first();

        $this->assertTrue($setting->is_public);
    }

    /** @test */
    public function it_has_line_notify_token_helpers()
    {
        $token = 'line-notify-token-12345';

        Setting::setLineNotifyToken($token);
        $retrieved = Setting::getLineNotifyToken();

        $this->assertEquals($token, $retrieved);

        // Verify it's encrypted in database
        $stored = Setting::where('key', 'line_notify_token')->first();
        $this->assertNotEquals($token, $stored->value);
    }

    /** @test */
    public function it_checks_notification_status()
    {
        // Default should be true
        $this->assertTrue(Setting::isNotificationEnabled());

        Setting::setValue('notification_enabled', 'false', 'boolean');
        $this->assertFalse(Setting::isNotificationEnabled());

        Setting::setValue('notification_enabled', 'true', 'boolean');
        $this->assertTrue(Setting::isNotificationEnabled());
    }

    /** @test */
    public function it_handles_decryption_failures_gracefully()
    {
        // Manually insert a "corrupted" encrypted value
        Setting::create([
            'key' => 'corrupted_key',
            'value' => 'not-a-valid-encrypted-string',
            'type' => 'string',
        ]);

        // Clear cache to force database read
        Cache::flush();

        // Should not throw exception, returns value as-is for backward compatibility
        $value = Setting::getValue('corrupted_key');

        $this->assertEquals('not-a-valid-encrypted-string', $value);
    }

    /** @test */
    public function it_encrypts_and_decrypts_special_characters()
    {
        $specialKey = 'sk-!@#$%^&*()_+-=[]{}|;:,.<>?/~`';

        Setting::setValue('ai_openai_key', $specialKey);
        $retrieved = Setting::getValue('ai_openai_key');

        $this->assertEquals($specialKey, $retrieved);
    }

    /** @test */
    public function it_encrypts_and_decrypts_unicode_characters()
    {
        $unicodeKey = 'key-with-unicode-สวัสดี-你好-مرحبا';

        Setting::setValue('ai_openai_key', $unicodeKey);
        $retrieved = Setting::getValue('ai_openai_key');

        $this->assertEquals($unicodeKey, $retrieved);
    }

    /** @test */
    public function it_handles_very_long_encrypted_values()
    {
        $longKey = str_repeat('a', 1000);

        Setting::setValue('ai_openai_key', $longKey);
        $retrieved = Setting::getValue('ai_openai_key');

        $this->assertEquals($longKey, $retrieved);
    }

    /** @test */
    public function it_does_not_double_encrypt_on_multiple_saves()
    {
        $apiKey = 'sk-original-key';

        Setting::setValue('ai_openai_key', $apiKey);
        $firstSave = Setting::where('key', 'ai_openai_key')->first()->value;

        Setting::setValue('ai_openai_key', $apiKey);
        $secondSave = Setting::where('key', 'ai_openai_key')->first()->value;

        // Both should decrypt to the same value
        $this->assertEquals($apiKey, Setting::getValue('ai_openai_key'));

        // The encrypted values should be different (due to different IVs)
        // but both should decrypt correctly
        $this->assertEquals(
            Crypt::decryptString($firstSave),
            Crypt::decryptString($secondSave)
        );
    }

    /** @test */
    public function it_encrypts_json_values_for_sensitive_keys()
    {
        // Note: In current implementation, json values are encoded first, then encrypted
        $jsonData = ['secret' => 'value', 'key' => 'data'];

        Setting::setValue('ai_openai_key', json_encode($jsonData));
        $retrieved = Setting::getValue('ai_openai_key');

        $this->assertEquals(json_encode($jsonData), $retrieved);
    }

    /** @test */
    public function it_preserves_null_values_for_non_existent_settings()
    {
        $value = Setting::getValue('does_not_exist');

        $this->assertNull($value);
    }

    /** @test */
    public function it_allows_different_types_for_same_key_on_update()
    {
        Setting::setValue('dynamic_setting', '100', 'integer');
        $this->assertEquals(100, Setting::getValue('dynamic_setting'));

        Setting::setValue('dynamic_setting', 'text', 'string');
        $this->assertEquals('text', Setting::getValue('dynamic_setting'));
    }

    /** @test */
    public function encrypted_keys_list_includes_all_sensitive_data()
    {
        $reflection = new \ReflectionClass(Setting::class);
        $property = $reflection->getProperty('encryptedKeys');
        $property->setAccessible(true);
        $encryptedKeys = $property->getValue();

        // Verify critical keys are included
        $criticalKeys = [
            'ai_openai_key',
            'ai_claude_key',
            'youtube_api_key',
            'line_notify_token',
        ];

        foreach ($criticalKeys as $key) {
            $this->assertContains($key, $encryptedKeys, "Critical key '{$key}' should be in encrypted keys list");
        }
    }

    /** @test */
    public function it_handles_whitespace_in_sensitive_values()
    {
        $keyWithSpaces = '  sk-test-key-with-spaces  ';

        Setting::setValue('ai_openai_key', $keyWithSpaces);
        $retrieved = Setting::getValue('ai_openai_key');

        // Should preserve whitespace
        $this->assertEquals($keyWithSpaces, $retrieved);
    }
}
