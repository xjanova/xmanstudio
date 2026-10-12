<?php

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Real pictures for the products on sale, set as each product's image in the admin
 * (owner, 2026-10-12: "เปลี่ยนหลังบ้าน ทุกจุดต้องเปลี่ยนเหมือนกัน" — the admin image is the one
 * picture every page and product.xman4289.com shows; see Product::getArtworkUrlAttribute()).
 *
 * resources/product-images/<slug>.webp are 960x540 cuts of real screenshots. The product image
 * lives on the public disk, so the files alone would not reach production: this copies them there
 * and points the rows at them. A picture the admin already chose is left alone — except the
 * CluadeX logo, which reads "ClaudeX", and SpiderX's link to a logo on GitHub that is gone (404).
 */
return new class extends Migration
{
    /** Products with a real screenshot. GigGok, GPUxMINE, Tping and AutoTradeX have none yet. */
    private const REAL = ['anti-x', 'cluadex-ai-coding-assistant', 'localvpn', 'winx-tools', 'aipray'];

    /** Uploads that are wrong and are replaced (or cleared, with no real picture to replace them). */
    private const WRONG = [
        'cluadex-ai-coding-assistant' => 'products/lLtdJBSO4HAVoCLikuRv70LIQbUnqcSXtEV6zjKh.webp',
        'spiderx' => 'https://raw.githubusercontent.com/xjanova/SpiderX/main/docs/logo.png',
    ];

    public function up(): void
    {
        // The test suite calls seed() itself, on a fake disk.
        if (app()->runningUnitTests()) {
            return;
        }

        $this->seed(Storage::disk('public'));
    }

    /** @return array<string, string|null> slug => the image it had before */
    public function seed(Filesystem $disk): array
    {
        $previous = [];

        foreach (DB::table('products')->whereIn('slug', [...self::REAL, ...array_keys(self::WRONG)])->get(['id', 'slug', 'image', 'images']) as $row) {
            $wrong = self::WRONG[$row->slug] ?? null;
            $real = in_array($row->slug, self::REAL, true);
            $gallery = json_decode((string) $row->images, true);
            // SpiderX's gallery carried the same dead link.
            $galleryIsWrong = $wrong !== null && is_array($gallery) && in_array($wrong, $gallery, true);
            $imageIsMissingOrWrong = ($real && ! $row->image) || ($wrong !== null && $row->image === $wrong);

            if (! $imageIsMissingOrWrong && ! $galleryIsWrong) {
                continue;
            }

            $update = ['updated_at' => now()];

            if ($imageIsMissingOrWrong) {
                $update['image'] = null;
                if ($real) {
                    $update['image'] = 'products/real/' . $row->slug . '.webp';
                    $disk->put($update['image'], file_get_contents(resource_path('product-images/' . $row->slug . '.webp')));
                }
            }

            if ($galleryIsWrong) {
                $update['images'] = json_encode(array_values(array_diff($gallery, [$wrong])));
            }

            DB::table('products')->where('id', $row->id)->update($update);
            $previous[$row->slug] = $row->image;
        }

        Log::info('Real product images set in the admin', ['previous' => $previous]);

        return $previous;
    }

    public function down(): void
    {
        // Nothing to undo automatically: the previous images are logged by seed(), and an old
        // upload is still on the public disk.
    }
};
