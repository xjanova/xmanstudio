<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The JSON-LD that <x-seo-meta> puts on every public page. Laravel 12 added an @context Blade
 * directive, so the "@context" key has to be written @@context: left as it was, the component
 * compiled to broken PHP and every page that uses the layout answered 500.
 */
class SeoStructuredDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_organization_json_ld_keeps_its_context_key(): void
    {
        $html = (string) $this->blade('<x-seo-meta />');

        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $match));

        $data = json_decode($match[1], true);
        $this->assertSame('https://schema.org', $data['@context'] ?? null);
        $this->assertSame('Organization', $data['@type'] ?? null);
    }
}
