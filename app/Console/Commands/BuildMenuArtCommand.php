<?php

namespace App\Console\Commands;

use App\Support\FontFile;
use App\Support\ThaiShaper;
use Illuminate\Console\Command;

/**
 * Bakes each menu tile's label into its artwork.
 *
 * The nav used to draw the label as HTML beside the picture. The owner wanted
 * the words inside the picture instead, English larger than Thai, so a tile
 * reads as one object rather than a thumbnail with a caption glued on.
 *
 * Why this is a build step and not an AI prompt: an image model cannot write
 * Thai. It floats the vowels, puts tone marks on the wrong consonant and
 * invents letters that do not exist. Composing the text here with GD and the
 * bundled Sarabun gives exactly the string that was asked for, at a size we
 * chose, and it can be re-run the moment a label changes.
 *
 * Why the PUA font and not plain Sarabun: GD draws with FreeType and applies
 * no shaping at all, so in a word like "เช่าใช้งาน" the tone mark lands on top
 * of the vowel and the two merge into a blob. ThaiShaper swaps in
 * pre-positioned glyphs from the Private Use Area — the same fix the OG images
 * and the Telegram alert cards already use.
 *
 *     php artisan menu:build-art            # every tile
 *     php artisan menu:build-art --only=home,wallet
 *     php artisan menu:build-art --force    # redo tiles already built
 */
class BuildMenuArtCommand extends Command
{
    protected $signature = 'menu:build-art
                            {--only= : Only these keys, comma separated}
                            {--force : Rebuild tiles that already have a label}
                            {--scale=2 : Output pixels per CSS pixel}';

    protected $description = 'Draw the Thai + English label into each menu artwork tile';

    /** The tile as CSS sees it. The file is rendered at this times --scale. */
    private const CSS_WIDTH = 208;

    private const CSS_HEIGHT = 116;

    /** Source tiles, and where the labelled copies go. */
    private const SOURCE_DIR = 'public_html/artwork/menu';

    private const OUTPUT_DIR = 'public_html/artwork/menu/labelled';

    /**
     * Every tile the navigation can show, and what it says.
     *
     * Kept here rather than read from the Blade files because the two navs
     * word some entries differently and the picture can only carry one. These
     * are the labels; the Blade files keep the same strings for screen
     * readers.
     *
     * @var array<string,array{th:string,en:string}>
     */
    private const LABELS = [
        'home' => ['th' => 'หน้าหลัก', 'en' => 'HOME'],
        'services' => ['th' => 'บริการ', 'en' => 'SERVICES'],
        'products' => ['th' => 'ผลิตภัณฑ์', 'en' => 'PRODUCTS'],
        'rental' => ['th' => 'เช่าใช้งาน', 'en' => 'RENTALS'],
        'domains' => ['th' => 'จดโดเมน', 'en' => 'DOMAINS'],
        'vps' => ['th' => 'เช่าเซิร์ฟเวอร์', 'en' => 'VPS'],
        'portfolio' => ['th' => 'ผลงาน', 'en' => 'PORTFOLIO'],
        'team' => ['th' => 'ทีมงาน', 'en' => 'TEAM'],
        'support' => ['th' => 'ติดต่อเรา', 'en' => 'CONTACT'],
        'contact' => ['th' => 'ติดต่อเรา', 'en' => 'CONTACT'],
        'tracking' => ['th' => 'ติดตามงาน', 'en' => 'TRACKING'],
        'donate' => ['th' => 'บริจาค', 'en' => 'DONATE'],
        'academy' => ['th' => 'เรียนเขียนโค้ด', 'en' => 'ACADEMY'],
        'cart' => ['th' => 'ตะกร้า', 'en' => 'CART'],
        'orders' => ['th' => 'คำสั่งซื้อ', 'en' => 'ORDERS'],
        'wallet' => ['th' => 'กระเป๋าเงิน', 'en' => 'WALLET'],
        'downloads' => ['th' => 'ดาวน์โหลด', 'en' => 'DOWNLOADS'],
        'metalx' => ['th' => 'เมทัล-เอ็กซ์', 'en' => 'METAL-X'],
        'xdreamer' => ['th' => 'เอ็กซ์ดรีมเมอร์', 'en' => 'X-DREAMER'],
    ];

    public function handle(): int
    {
        if (! function_exists('imagecreatefromwebp')) {
            $this->error('GD has no WebP support in this PHP build.');

            return self::FAILURE;
        }

        $fontBold = $this->font('Sarabun-PUA-Bold.ttf');
        $fontRegular = $this->font('Sarabun-PUA-Regular.ttf');

        if (! $fontBold || ! $fontRegular) {
            // Falling back to a Latin font would silently draw Thai as boxes,
            // which is worse than not building at all.
            $this->error('storage/fonts/Sarabun-PUA-*.ttf missing or not a real font.');

            return self::FAILURE;
        }

        $scale = max(1, min(4, (int) $this->option('scale')));
        $only = $this->option('only')
            ? array_filter(array_map('trim', explode(',', (string) $this->option('only'))))
            : null;

        $outDir = base_path(self::OUTPUT_DIR);

        if (! is_dir($outDir) && ! mkdir($outDir, 0o755, true) && ! is_dir($outDir)) {
            $this->error("Cannot create {$outDir}");

            return self::FAILURE;
        }

        $built = $skipped = $missing = 0;

        foreach (self::LABELS as $key => $label) {
            if ($only && ! in_array($key, $only, true)) {
                continue;
            }

            $source = base_path(self::SOURCE_DIR . "/{$key}.webp");
            $target = $outDir . "/{$key}.webp";

            if (! is_file($source)) {
                $this->warn("  ไม่มีภาพต้นฉบับ: {$key}.webp");
                $missing++;

                continue;
            }

            if (is_file($target) && ! $this->option('force')) {
                $skipped++;

                continue;
            }

            if ($this->compose($source, $target, $label, $fontBold, $fontRegular, $scale)) {
                $this->line(sprintf('  %-12s %s / %s', $key, $label['en'], $label['th']));
                $built++;
            } else {
                $this->warn("  วาดไม่สำเร็จ: {$key}");
                $missing++;
            }
        }

        $this->newLine();
        $this->info("สร้าง {$built} · ข้าม {$skipped} · มีปัญหา {$missing} → " . self::OUTPUT_DIR);

        if ($skipped > 0) {
            $this->comment('ใช้ --force เพื่อสร้างทับของเดิม');
        }

        return self::SUCCESS;
    }

    /**
     * Draw one tile.
     *
     * The order matters and each layer earns its place:
     *   1. the artwork, scaled up
     *   2. a scrim, darkest in the middle — the artwork is bright neon and
     *      white text on it is unreadable without one
     *   3. the glow: the text drawn several times in a translucent accent
     *      colour at small offsets, which is how you fake a blur in GD
     *   4. the text itself
     */
    private function compose(string $source, string $target, array $label, string $fontBold, string $fontRegular, int $scale): bool
    {
        $src = @imagecreatefromwebp($source);

        if (! $src) {
            return false;
        }

        $width = self::CSS_WIDTH * $scale;
        $height = self::CSS_HEIGHT * $scale;

        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        imagecopyresampled(
            $img, $src,
            0, 0, 0, 0,
            $width, $height,
            imagesx($src), imagesy($src)
        );

        imagedestroy($src);

        $this->drawScrim($img, $width, $height);

        // English larger, as asked — it is the line the eye lands on.
        $enSize = 15.0 * $scale;
        $thSize = 9.5 * $scale;

        $en = $label['en'];
        // Shaped, because GD cannot stack Thai marks on its own.
        $th = ThaiShaper::shape($label['th']);

        // Fit long words (PORTFOLIO, DOWNLOADS, X-DREAMER) by shrinking rather
        // than letting them run off the tile.
        $enSize = $this->fit($en, $fontBold, $enSize, $width - (22 * $scale));
        $thSize = $this->fit($th, $fontRegular, $thSize, $width - (22 * $scale));

        $gap = (int) round(7 * $scale);
        $enBox = imagettfbbox($enSize, 0, $fontBold, $en);
        $thBox = imagettfbbox($thSize, 0, $fontRegular, $th);

        $enHeight = abs($enBox[5] - $enBox[1]);
        $thHeight = abs($thBox[5] - $thBox[1]);

        // Centre the pair as a block, then place each line from its baseline.
        $blockTop = (int) round(($height - ($enHeight + $gap + $thHeight)) / 2);
        $enBaseline = $blockTop + $enHeight;
        $thBaseline = $enBaseline + $gap + $thHeight;

        $enX = (int) round(($width - ($enBox[2] - $enBox[0])) / 2);
        $thX = (int) round(($width - ($thBox[2] - $thBox[0])) / 2);

        $white = imagecolorallocate($img, 255, 255, 255);
        $soft = imagecolorallocate($img, 214, 226, 255);

        $this->drawGlow($img, $en, $fontBold, $enSize, $enX, $enBaseline, $scale, [125, 211, 252]);
        $this->drawGlow($img, $th, $fontRegular, $thSize, $thX, $thBaseline, $scale, [167, 139, 250]);

        imagettftext($img, $enSize, 0, $enX, $enBaseline, $white, $fontBold, $en);
        imagettftext($img, $thSize, 0, $thX, $thBaseline, $soft, $fontRegular, $th);

        $ok = imagewebp($img, $target, 88);

        imagedestroy($img);

        return $ok;
    }

    /**
     * A vignette that is darkest where the text sits.
     *
     * Drawn as horizontal bands rather than a real radial gradient: GD has no
     * gradient primitive, and at 232 rows the banding is invisible while a
     * per-pixel loop would be 96,000 calls per tile.
     */
    private function drawScrim($img, int $width, int $height): void
    {
        $centre = $height / 2;

        for ($y = 0; $y < $height; $y++) {
            // 0 at the edges, 1 in the middle.
            $t = 1 - (abs($y - $centre) / $centre);
            $alpha = (int) round(127 - (0.62 * 127 * $t * $t));

            $band = imagecolorallocatealpha($img, 4, 6, 18, max(28, min(127, $alpha)));
            imagefilledrectangle($img, 0, $y, $width, $y, $band);
        }
    }

    /**
     * Fake a glow by stamping the text repeatedly, faintly, around itself.
     *
     * GD's imagefilter(IMG_FILTER_GAUSSIAN_BLUR) would blur the artwork too, and
     * blurring a separate layer means allocating a second truecolour image per
     * line. Eight offsets at two radii is cheaper and, at this size, reads the
     * same — and it is what makes white text survive a bright neon background.
     *
     * @param  array{int,int,int}  $rgb
     */
    private function drawGlow($img, string $text, string $font, float $size, int $x, int $y, int $scale, array $rgb): void
    {
        foreach ([[2, 92], [1, 66]] as [$radius, $alpha]) {
            $r = max(1, (int) round($radius * $scale * 0.75));
            $colour = imagecolorallocatealpha($img, $rgb[0], $rgb[1], $rgb[2], $alpha);

            foreach ([[-1, -1], [1, -1], [-1, 1], [1, 1], [0, -1], [0, 1], [-1, 0], [1, 0]] as [$dx, $dy]) {
                imagettftext($img, $size, 0, $x + ($dx * $r), $y + ($dy * $r), $colour, $font, $text);
            }
        }
    }

    /** Shrink until the line fits, down to a floor where it is still legible. */
    private function fit(string $text, string $font, float $size, float $maxWidth): float
    {
        $floor = $size * 0.6;

        while ($size > $floor) {
            $box = imagettfbbox($size, 0, $font, $text);

            if (($box[2] - $box[0]) <= $maxWidth) {
                break;
            }

            $size -= 0.5;
        }

        return $size;
    }

    /** A real font file, or null — never a saved web page with a .ttf name. */
    private function font(string $name): ?string
    {
        $path = storage_path('fonts/' . $name);

        return FontFile::isReal($path) ? $path : null;
    }
}
