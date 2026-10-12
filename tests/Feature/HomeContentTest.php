<?php

namespace Tests\Feature;

use App\Support\HomeContent;
use Tests\TestCase;

/**
 * Lists the two home pages share (App\Support\HomeContent) that carry logic of their own.
 */
class HomeContentTest extends TestCase
{
    public function test_every_stack_and_ai_logo_is_a_label_and_a_url(): void
    {
        foreach ([...HomeContent::tech(), ...HomeContent::aiTech()] as [$label, $url, $invert]) {
            $this->assertNotSame('', $label);
            $this->assertMatchesRegularExpression('#^https?://#', $url);
            $this->assertIsBool($invert);
        }

        // The AI logos are kept in the repository, not fetched from a CDN.
        foreach (HomeContent::aiTech() as [$label, $url]) {
            $path = public_path(ltrim((string) parse_url($url, PHP_URL_PATH), '/'));
            $this->assertFileExists($path, "{$label}: {$path}");
        }
    }
}
