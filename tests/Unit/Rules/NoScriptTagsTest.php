<?php

namespace Tests\Unit\Rules;

use App\Rules\NoScriptTags;
use Tests\TestCase;

class NoScriptTagsTest extends TestCase
{
    protected $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new NoScriptTags;
    }

    /** @test */
    public function it_passes_for_clean_text()
    {
        $cleanInputs = [
            'This is a normal text without any scripts',
            'Hello World!',
            'Some text with números 123 and special chars: !@#$%',
            'Thai text: สวัสดีครับ',
            'Text with line\nbreaks and\ttabs',
        ];

        foreach ($cleanInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, "Clean input should pass: {$input}");
        }
    }

    /** @test */
    public function it_blocks_script_tags()
    {
        $maliciousInputs = [
            '<script>alert("XSS")</script>',
            '<script type="text/javascript">alert(1)</script>',
            '<SCRIPT>alert("XSS")</SCRIPT>', // Case insensitive
            '<script src="evil.js"></script>',
            'Text before <script>alert("XSS")</script> text after',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block script tag: {$input}");
        }
    }

    /** @test */
    public function it_blocks_inline_event_handlers()
    {
        $maliciousInputs = [
            '<div onclick="alert(1)">Click me</div>',
            '<img src="x" onerror="alert(1)">',
            '<body onload="malicious()">',
            '<input onfocus="steal()">',
            '<a onmouseover="evil()">Link</a>',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block event handler: {$input}");
        }
    }

    /** @test */
    public function it_blocks_javascript_protocol()
    {
        $maliciousInputs = [
            '<a href="javascript:alert(1)">Click</a>',
            'javascript:void(0)',
            'JAVASCRIPT:alert("XSS")', // Case insensitive
            '<iframe src="javascript:alert(1)"></iframe>',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block javascript protocol: {$input}");
        }
    }

    /** @test */
    public function it_blocks_data_protocol()
    {
        $maliciousInputs = [
            '<img src="data:text/html,<script>alert(1)</script>">',
            'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
            '<a href="data:text/html,<h1>XSS</h1>">Click</a>',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block data protocol: {$input}");
        }
    }

    /** @test */
    public function it_blocks_vbscript_protocol()
    {
        $maliciousInputs = [
            '<a href="vbscript:msgbox(1)">Click</a>',
            'vbscript:Execute("malicious code")',
            '<iframe src="vbscript:alert(1)"></iframe>',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block vbscript protocol: {$input}");
        }
    }

    /** @test */
    public function it_blocks_iframe_tags()
    {
        $maliciousInputs = [
            '<iframe src="evil.com"></iframe>',
            '<IFRAME src="malicious.js"></IFRAME>',
            'Text <iframe></iframe> more text',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block iframe: {$input}");
        }
    }

    /** @test */
    public function it_blocks_object_and_embed_tags()
    {
        $maliciousInputs = [
            '<object data="evil.swf"></object>',
            '<embed src="malicious.swf">',
            '<OBJECT></OBJECT>',
            '<EMBED type="application/x-shockwave-flash">',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block object/embed: {$input}");
        }
    }

    /** @test */
    public function it_blocks_meta_refresh_redirects()
    {
        $maliciousInputs = [
            '<meta http-equiv="refresh" content="0;url=evil.com">',
            '<META HTTP-EQUIV="refresh" CONTENT="1; URL=malicious.com">',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block meta refresh: {$input}");
        }
    }

    /** @test */
    public function it_blocks_base_tag()
    {
        $maliciousInputs = [
            '<base href="https://evil.com/">',
            '<BASE HREF="malicious.com">',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block base tag: {$input}");
        }
    }

    /** @test */
    public function it_blocks_form_tags()
    {
        $maliciousInputs = [
            '<form action="evil.com" method="post">',
            '<FORM></FORM>',
            'Text <form><input></form> more',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block form tag: {$input}");
        }
    }

    /** @test */
    public function it_blocks_link_tags_with_stylesheet()
    {
        $maliciousInputs = [
            '<link rel="stylesheet" href="evil.css">',
            '<LINK REL="stylesheet" HREF="malicious.css">',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block link stylesheet: {$input}");
        }
    }

    /** @test */
    public function it_blocks_style_tags()
    {
        $maliciousInputs = [
            '<style>body{background:url("javascript:alert(1)")}</style>',
            '<STYLE>@import "evil.css";</STYLE>',
            'Text <style></style> more',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block style tag: {$input}");
        }
    }

    /** @test */
    public function it_blocks_svg_with_script()
    {
        $maliciousInputs = [
            '<svg><script>alert(1)</script></svg>',
            '<svg onload="alert(1)">',
            '<svg><foreignObject><script>alert(1)</script></foreignObject></svg>',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block malicious SVG: {$input}");
        }
    }

    /** @test */
    public function it_blocks_expression_in_css()
    {
        $maliciousInputs = [
            '<div style="width: expression(alert(1))">',
            'width: expression(alert("XSS"))',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block CSS expression: {$input}");
        }
    }

    /** @test */
    public function it_allows_safe_html_tags()
    {
        $safeInputs = [
            '<p>This is a paragraph</p>',
            '<div>Safe content</div>',
            '<span>Text</span>',
            '<a href="https://example.com">Link</a>',
            '<img src="image.jpg" alt="Image">',
            '<strong>Bold</strong> and <em>italic</em>',
            '<ul><li>Item 1</li><li>Item 2</li></ul>',
            '<table><tr><td>Cell</td></tr></table>',
        ];

        foreach ($safeInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, "Safe HTML should pass: {$input}");
        }
    }

    /** @test */
    public function it_handles_null_and_empty_values()
    {
        $values = [null, '', '   '];

        foreach ($values as $value) {
            $failed = false;
            $this->rule->validate('test_field', $value, function () use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, 'Empty/null values should pass');
        }
    }

    /** @test */
    public function it_handles_non_string_values()
    {
        $values = [123, 45.67, true, false];

        foreach ($values as $value) {
            $failed = false;
            $this->rule->validate('test_field', $value, function () use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, 'Non-string values should pass validation');
        }
    }

    /** @test */
    public function it_blocks_obfuscated_script_tags()
    {
        $maliciousInputs = [
            '<scr<script>ipt>alert(1)</scr</script>ipt>',
            '<<SCRIPT>script>alert(1)<</SCRIPT>/script>',
            '<scr\x00ipt>alert(1)</script>', // Null byte injection
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block obfuscated script: {$input}");
        }
    }

    /** @test */
    public function it_blocks_html_entities_in_script_context()
    {
        $maliciousInputs = [
            '<img src=x onerror=&#97;&#108;&#101;&#114;&#116;&#40;&#49;&#41;>',
            '&#60;script&#62;alert(1)&#60;/script&#62;',
        ];

        foreach ($maliciousInputs as $input) {
            $failed = false;
            $this->rule->validate('test_field', $input, function () use (&$failed) {
                $failed = true;
            });

            $this->assertTrue($failed, "Should block HTML entity obfuscation: {$input}");
        }
    }

    /** @test */
    public function it_returns_proper_error_message()
    {
        $message = $this->rule->message();

        $this->assertIsString($message);
        $this->assertStringContainsString('script', strtolower($message));
        $this->assertStringContainsString(':attribute', $message);
    }

    /** @test */
    public function it_provides_contextual_error_in_fail_callback()
    {
        $capturedMessage = null;

        $this->rule->validate('comment_text', '<script>alert(1)</script>', function ($message) use (&$capturedMessage) {
            $capturedMessage = $message;
        });

        $this->assertNotNull($capturedMessage);
        $this->assertStringContainsString('comment_text', $capturedMessage);
    }
}
