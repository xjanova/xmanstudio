<?php

namespace App\Http\Controllers;

use App\Models\SeoSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OgImageController extends Controller
{
    /**
     * Generate a dynamic Open Graph image (1200x630).
     */
    public function generate(Request $request)
    {
        $title = $request->query('title', 'XMAN Studio');
        $subtitle = $request->query('subtitle', 'IT Solutions & Software Development');

        $cacheKey = 'og_image_' . md5($title . $subtitle);

        $imageData = Cache::remember($cacheKey, 3600, function () use ($title, $subtitle) {
            return $this->createImage($title, $subtitle);
        });

        return response($imageData, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Serve the default OG image.
     */
    public function defaultImage()
    {
        $seo = SeoSetting::getInstance();

        $imageData = Cache::remember('og_image_default_v2', 3600, function () use ($seo) {
            return $this->createImage(
                $seo->site_name ?: 'XMAN Studio',
                $seo->site_description ?: 'IT Solutions & Software Development ครบวงจร'
            );
        });

        return response($imageData, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function createImage(string $title, string $subtitle): string
    {
        $width = 1200;
        $height = 630;

        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        // Background gradient - dark blue to purple
        $this->drawGradient($img, $width, $height);

        // Decorative elements
        $this->drawDecorations($img, $width, $height);

        // Draw text
        $this->drawText($img, $width, $height, $title, $subtitle);

        // Draw bottom bar
        $this->drawBottomBar($img, $width, $height);

        ob_start();
        imagepng($img, null, 9);
        $data = ob_get_clean();
        imagedestroy($img);

        return $data;
    }

    private function drawGradient($img, int $width, int $height): void
    {
        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            // From deep navy (#0a0e27) to deep purple (#1a0533)
            $r = (int) (10 + $ratio * 16);
            $g = (int) (14 - $ratio * 9);
            $b = (int) (39 + $ratio * 12);
            $color = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $width, $y, $color);
        }
    }

    private function drawDecorations($img, int $width, int $height): void
    {
        // Glowing orb top-right (cyan/blue)
        $this->drawGlow($img, (int) ($width * 0.85), (int) ($height * 0.2), 180, 0, 150, 255, 0.08);

        // Glowing orb bottom-left (purple/pink)
        $this->drawGlow($img, (int) ($width * 0.15), (int) ($height * 0.75), 200, 120, 50, 200, 0.06);

        // Subtle grid pattern
        $gridColor = imagecolorallocatealpha($img, 255, 255, 255, 120);
        for ($x = 0; $x < $width; $x += 60) {
            imageline($img, $x, 0, $x, $height, $gridColor);
        }
        for ($y = 0; $y < $height; $y += 60) {
            imageline($img, 0, $y, $width, $y, $gridColor);
        }

        // Accent line at top
        $accentStart = imagecolorallocate($img, 0, 200, 255);
        $accentEnd = imagecolorallocate($img, 160, 80, 255);
        for ($x = 0; $x < $width; $x++) {
            $ratio = $x / $width;
            $r = (int) (0 + $ratio * 160);
            $g = (int) (200 - $ratio * 120);
            $b = 255;
            $color = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, $x, 0, $x, 4, $color);
        }
    }

    private function drawGlow($img, int $cx, int $cy, int $radius, int $r, int $g, int $b, float $maxAlpha): void
    {
        for ($i = $radius; $i > 0; $i -= 2) {
            $alpha = (int) (127 - (127 * $maxAlpha * ($i / $radius)));
            $color = imagecolorallocatealpha($img, $r, $g, $b, max(0, min(127, $alpha)));
            imagefilledellipse($img, $cx, $cy, $i * 2, $i * 2, $color);
        }
    }

    private function drawText($img, int $width, int $height, string $title, string $subtitle): void
    {
        $fontBold = $this->resolveFont('Sarabun-Bold.ttf', 'DejaVuSans-Bold.ttf');
        $fontRegular = $this->resolveFont('Sarabun-Regular.ttf', 'DejaVuSans.ttf');

        $white = imagecolorallocate($img, 255, 255, 255);
        $lightGray = imagecolorallocate($img, 180, 190, 210);
        $cyan = imagecolorallocate($img, 0, 220, 255);

        try {
            if (! $fontBold || ! $fontRegular) {
                throw new \RuntimeException('Font not found');
            }

            // "X" logo mark
            imagettftext($img, 60, 0, 80, 240, $cyan, $fontBold, 'X');

            // Title
            $titleSize = 48;
            $titleLines = $this->wrapText($title, $fontBold, $titleSize, $width - 220);
            $yPos = 230;
            foreach ($titleLines as $line) {
                imagettftext($img, $titleSize, 0, 160, $yPos, $white, $fontBold, $line);
                $yPos += 65;
            }

            // Subtitle
            $subtitleSize = 22;
            $subtitleLines = $this->wrapText($subtitle, $fontRegular, $subtitleSize, $width - 220);
            $yPos += 15;
            foreach ($subtitleLines as $line) {
                imagettftext($img, $subtitleSize, 0, 160, $yPos, $lightGray, $fontRegular, $line);
                $yPos += 35;
            }
        } catch (\Throwable $e) {
            // Fallback: use GD built-in fonts (no TTF needed)
            imagestring($img, 5, 80, 200, 'X', $cyan);
            imagestring($img, 5, 160, 250, $title, $white);
            imagestring($img, 3, 160, 280, $subtitle, $lightGray);
        }
    }

    private function resolveFont(string $sarabunName, string $dejavuName): ?string
    {
        // Try copying font to temp directory (workaround for hosting path restrictions)
        $storagePath = storage_path('fonts/' . $sarabunName);
        $tempPath = sys_get_temp_dir() . '/' . $sarabunName;

        if (is_file($storagePath) && (! is_file($tempPath) || filemtime($storagePath) > filemtime($tempPath))) {
            @copy($storagePath, $tempPath);
        }

        $candidates = [
            $tempPath,
            realpath($storagePath) ?: null,
            $storagePath,
            base_path('storage/fonts/' . $sarabunName),
            '/usr/share/fonts/truetype/dejavu/' . $dejavuName,
            '/usr/share/fonts/dejavu/' . $dejavuName,
        ];

        foreach ($candidates as $path) {
            if ($path && is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    private function drawBottomBar($img, int $width, int $height): void
    {
        $fontRegular = $this->resolveFont('Sarabun-Regular.ttf', 'DejaVuSans.ttf');

        // Bottom dark bar
        $barColor = imagecolorallocatealpha($img, 0, 0, 0, 60);
        imagefilledrectangle($img, 0, $height - 60, $width, $height, $barColor);

        // Bottom accent line
        for ($x = 0; $x < $width; $x++) {
            $ratio = $x / $width;
            $r = (int) (0 + $ratio * 160);
            $g = (int) (200 - $ratio * 120);
            $b = 255;
            $color = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, $x, $height - 60, $x, $height - 57, $color);
        }

        // URL text
        $urlColor = imagecolorallocate($img, 140, 150, 170);
        $tagColor = imagecolorallocate($img, 0, 200, 240);

        try {
            if (! $fontRegular) {
                throw new \RuntimeException('Font not found');
            }
            imagettftext($img, 16, 0, 80, $height - 22, $urlColor, $fontRegular, 'xmanstudio.com');
            imagettftext($img, 14, 0, $width - 320, $height - 22, $tagColor, $fontRegular, 'IT Solutions & Development');
        } catch (\Throwable $e) {
            imagestring($img, 3, 80, $height - 35, 'xmanstudio.com', $urlColor);
            imagestring($img, 2, $width - 280, $height - 35, 'IT Solutions & Development', $tagColor);
        }
    }

    private function wrapText(string $text, string $font, int $size, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            $bbox = imagettfbbox($size, 0, $font, $testLine);
            $lineWidth = abs($bbox[2] - $bbox[0]);

            if ($lineWidth > $maxWidth && $currentLine !== '') {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $currentLine = $testLine;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines ?: [$text];
    }
}
