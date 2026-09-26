<?php

namespace Tests\Feature;

use App\Support\HomeContent;
use Tests\TestCase;

/**
 * Lists the two home pages share (App\Support\HomeContent) that carry logic of their own.
 */
class HomeContentTest extends TestCase
{
    public function test_a_product_card_uses_the_key_art_drawn_for_it_when_there_is_some(): void
    {
        $dir = public_path('artwork/universe/products');
        $file = $dir . '/zz-test-product.webp';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file, 'webp');

        try {
            $own = 'https://cdn.example/own.png';

            $this->assertSame(
                asset('artwork/universe/products/zz-test-product.webp'),
                HomeContent::productArt((object) ['slug' => 'zz-test-product', 'artwork_url' => $own]),
            );
            $this->assertSame($own, HomeContent::productArt((object) ['slug' => 'zz-no-art', 'artwork_url' => $own]));
            $this->assertNull(HomeContent::productArt((object) ['slug' => 'zz-no-art', 'artwork_url' => null]));
            // A slug is never a path.
            $this->assertSame($own, HomeContent::productArt((object) ['slug' => '../products/zz-test-product', 'artwork_url' => $own]));
        } finally {
            @unlink($file);
        }
    }

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
