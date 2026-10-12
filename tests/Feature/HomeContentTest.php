<?php

namespace Tests\Feature;

use App\Support\HomeContent;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Lists the two home pages share (App\Support\HomeContent) that carry logic of their own.
 */
class HomeContentTest extends TestCase
{
    public function test_a_product_card_shows_the_real_picture_cut_for_it_when_there_is_one(): void
    {
        $dir = public_path('images/products/cards');
        $file = $dir . '/zz-test-product.webp';
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($file, 'webp');

        try {
            $uploaded = 'products/zz-upload.webp';

            $this->assertSame(
                asset('images/products/cards/zz-test-product.webp'),
                HomeContent::productArt((object) ['slug' => 'zz-test-product', 'image' => $uploaded]),
            );
            // No cut of its own: the image the admin uploaded, stored or linked.
            $this->assertSame(Storage::url($uploaded), HomeContent::productArt((object) ['slug' => 'zz-no-art', 'image' => $uploaded]));
            $this->assertSame('https://cdn.example/own.png', HomeContent::productArt((object) ['slug' => 'zz-no-art', 'image' => 'https://cdn.example/own.png']));
            // A slug is never a path.
            $this->assertSame(Storage::url($uploaded), HomeContent::productArt((object) ['slug' => '../cards/zz-test-product', 'image' => $uploaded]));
        } finally {
            @unlink($file);
        }
    }

    public function test_a_product_card_never_falls_back_to_drawn_key_art(): void
    {
        // artwork_url ends at artwork/product/{slug}.webp, which pictures the idea of the
        // product, not the program. The owner wants the software as it really is, or nothing.
        $this->assertNull(HomeContent::productArt((object) [
            'slug' => 'zz-no-art',
            'image' => null,
            'artwork_url' => asset('artwork/product/zz-no-art.webp'),
        ]));
    }

    public function test_every_product_on_sale_has_a_real_card_picture(): void
    {
        // The products on sale on 2026-10-12. A product that goes on sale later needs its
        // own cut in images/products/cards/ (960x540 webp), or the card shows initials.
        foreach (['anti-x', 'giggok', 'gpuxmine', 'cluadex-ai-coding-assistant', 'localvpn', 'aipray', 'tping', 'winx-tools', 'autotradex'] as $slug) {
            $path = public_path("images/products/cards/{$slug}.webp");
            $this->assertFileExists($path);
            $this->assertSame([960, 540], array_slice(getimagesize($path), 0, 2), $slug);
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
