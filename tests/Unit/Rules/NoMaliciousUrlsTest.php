<?php

namespace Tests\Unit\Rules;

use App\Rules\NoMaliciousUrls;
use Tests\TestCase;

class NoMaliciousUrlsTest extends TestCase
{
    protected $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new NoMaliciousUrls();
    }

    /** @test */
    public function it_passes_for_valid_http_urls()
    {
        $validUrls = [
            'https://www.example.com',
            'http://example.com/path/to/page',
            'https://subdomain.example.com:8080/path?query=value',
            'https://example.com/path#anchor',
            'http://example.co.uk',
        ];

        foreach ($validUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, "Valid URL should pass: {$url}");
        }
    }

    /** @test */
    public function it_passes_for_non_url_strings()
    {
        $nonUrls = [
            'This is just text',
            'not-a-url',
            'random string',
        ];

        foreach ($nonUrls as $value) {
            $failed = false;
            $this->rule->validate('field', $value, function() use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, "Non-URL string should pass: {$value}");
        }
    }

    /** @test */
    public function it_blocks_javascript_protocol()
    {
        $maliciousUrls = [
            'javascript:alert(1)',
            'JAVASCRIPT:void(0)',
            'JavaScript:malicious()',
        ];

        foreach ($maliciousUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block javascript protocol: {$url}");
        }
    }

    /** @test */
    public function it_blocks_data_protocol()
    {
        $maliciousUrls = [
            'data:text/html,<script>alert(1)</script>',
            'DATA:text/plain;base64,SGVsbG8=',
            'data:image/svg+xml,<svg onload=alert(1)>',
        ];

        foreach ($maliciousUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block data protocol: {$url}");
        }
    }

    /** @test */
    public function it_blocks_vbscript_protocol()
    {
        $maliciousUrls = [
            'vbscript:msgbox(1)',
            'VBSCRIPT:Execute("code")',
        ];

        foreach ($maliciousUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block vbscript protocol: {$url}");
        }
    }

    /** @test */
    public function it_blocks_file_protocol()
    {
        $maliciousUrls = [
            'file:///etc/passwd',
            'FILE:///C:/Windows/System32',
            'file://localhost/path/to/file',
        ];

        foreach ($maliciousUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block file protocol: {$url}");
        }
    }

    /** @test */
    public function it_blocks_about_protocol()
    {
        $maliciousUrls = [
            'about:blank',
            'ABOUT:config',
        ];

        foreach ($maliciousUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block about protocol: {$url}");
        }
    }

    /** @test */
    public function it_blocks_localhost_by_default()
    {
        $localhostUrls = [
            'http://localhost',
            'https://localhost:8080/path',
            'http://127.0.0.1',
            'http://127.0.0.1:3000',
            'http://[::1]',
            'http://0.0.0.0',
        ];

        foreach ($localhostUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block localhost: {$url}");
        }
    }

    /** @test */
    public function it_allows_localhost_when_configured()
    {
        $rule = new NoMaliciousUrls(true); // Allow localhost

        $localhostUrls = [
            'http://localhost',
            'http://127.0.0.1',
            'http://[::1]',
        ];

        foreach ($localhostUrls as $url) {
            $failed = false;
            $rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, "Should allow localhost when configured: {$url}");
        }
    }

    /** @test */
    public function it_blocks_private_ip_ranges()
    {
        $privateIps = [
            'http://10.0.0.1',
            'http://192.168.1.1',
            'http://172.16.0.1',
            'http://192.168.0.100:8080/path',
        ];

        foreach ($privateIps as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block private IP: {$url}");
        }
    }

    /** @test */
    public function it_blocks_link_local_addresses()
    {
        $linkLocalUrls = [
            'http://169.254.1.1',
            'http://169.254.169.254', // AWS metadata service
            'http://[fe80::1]',
        ];

        foreach ($linkLocalUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block link-local address: {$url}");
        }
    }

    /** @test */
    public function it_blocks_url_shorteners()
    {
        $shortenerUrls = [
            'http://bit.ly/abc123',
            'https://tinyurl.com/xyz',
            'http://goo.gl/short',
            'https://t.co/link',
            'http://ow.ly/example',
        ];

        foreach ($shortenerUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block URL shortener: {$url}");
        }
    }

    /** @test */
    public function it_blocks_typosquatting_patterns()
    {
        $typosquattingUrls = [
            'http://paypa1.com', // PayPal with 1 instead of l
            'http://g00gle.com', // Google with zeros
            'http://amaz0n.com', // Amazon with zero
        ];

        foreach ($typosquattingUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block typosquatting: {$url}");
        }
    }

    /** @test */
    public function it_blocks_multiple_at_symbols()
    {
        $maliciousUrls = [
            'http://user@legit.com@evil.com',
            'https://fake@real@malicious.com/path',
        ];

        foreach ($maliciousUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block multiple @ symbols: {$url}");
        }
    }

    /** @test */
    public function it_blocks_excessive_url_encoding()
    {
        $maliciousUrls = [
            'http://example.com/%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd',
            'http://test.com/' . str_repeat('%20', 10),
        ];

        foreach ($maliciousUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block excessive URL encoding: {$url}");
        }
    }

    /** @test */
    public function it_blocks_html_entities_in_url()
    {
        $maliciousUrls = [
            'http://example.com/&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;',
            'http://test.com/path&#63;query=value',
        ];

        foreach ($maliciousUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block HTML entities in URL: {$url}");
        }
    }

    /** @test */
    public function it_requires_http_or_https_scheme_for_full_urls()
    {
        $invalidSchemes = [
            'ftp://example.com',
            'ssh://example.com',
            'telnet://example.com',
        ];

        foreach ($invalidSchemes as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block non-HTTP(S) scheme: {$url}");
        }
    }

    /** @test */
    public function it_handles_null_and_empty_values()
    {
        $values = [null, '', '   '];

        foreach ($values as $value) {
            $failed = false;
            $this->rule->validate('url_field', $value, function() use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, "Empty/null values should pass");
        }
    }

    /** @test */
    public function it_handles_non_string_values()
    {
        $values = [123, 45.67, true, false];

        foreach ($values as $value) {
            $failed = false;
            $this->rule->validate('url_field', $value, function() use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, "Non-string values should pass validation");
        }
    }

    /** @test */
    public function it_allows_legitimate_urls_with_credentials()
    {
        $validUrls = [
            'https://user:pass@example.com',
            'http://username@example.com',
        ];

        foreach ($validUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            // Single @ is OK, but we block multiple @
            $this->assertFalse($failed, "Valid URL with single @ should pass: {$url}");
        }
    }

    /** @test */
    public function it_allows_public_ips()
    {
        $publicIps = [
            'http://8.8.8.8',
            'http://1.1.1.1',
            'http://208.67.222.222',
        ];

        foreach ($publicIps as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, "Public IP should pass: {$url}");
        }
    }

    /** @test */
    public function it_allows_international_domain_names()
    {
        $idn = 'https://例え.jp'; // Japanese IDN

        $failed = false;
        $this->rule->validate('url_field', $idn, function() use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed, "International domain names should pass");
    }

    /** @test */
    public function it_returns_proper_error_message()
    {
        $message = $this->rule->message();

        $this->assertIsString($message);
        $this->assertStringContainsString('url', strtolower($message));
        $this->assertStringContainsString(':attribute', $message);
    }

    /** @test */
    public function it_provides_contextual_error_in_fail_callback()
    {
        $capturedMessage = null;

        $this->rule->validate('callback_url', 'javascript:alert(1)', function($message) use (&$capturedMessage) {
            $capturedMessage = $message;
        });

        $this->assertNotNull($capturedMessage);
        $this->assertStringContainsString('callback_url', $capturedMessage);
    }

    /** @test */
    public function it_protects_against_ssrf_via_aws_metadata_service()
    {
        $metadataUrls = [
            'http://169.254.169.254/latest/meta-data/',
            'http://[fe80::]/api',
        ];

        foreach ($metadataUrls as $url) {
            $failed = false;
            $this->rule->validate('url_field', $url, function() use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block SSRF attempt: {$url}");
        }
    }
}
