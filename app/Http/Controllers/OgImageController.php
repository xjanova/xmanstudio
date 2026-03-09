<?php

namespace App\Http\Controllers;

use App\Models\SeoSetting;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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
     * Serve the uploaded OG image from storage (no symlink needed).
     * Falls back to dynamic generation if no image uploaded.
     */
    public function siteImage()
    {
        $seo = SeoSetting::getInstance();

        if ($seo->og_image && Storage::disk('public')->exists($seo->og_image)) {
            $path = Storage::disk('public')->path($seo->og_image);
            $mimeType = Storage::disk('public')->mimeType($seo->og_image);

            return response()->file($path, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        // Fallback to dynamically generated image
        return $this->defaultImage();
    }

    /**
     * Serve the default OG image.
     */
    public function defaultImage()
    {
        $seo = SeoSetting::getInstance();

        $imageData = Cache::remember('og_image_default_v5', 3600, function () use ($seo) {
            return $this->createImage(
                $seo->site_name ?: 'XMAN Studio',
                'IT Solutions & Software Development'
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

        // Background gradient
        $this->drawGradient($img, $width, $height);

        // Decorative elements
        $this->drawDecorations($img, $width, $height);

        // Draw logo centered, then text below
        $this->drawLogoAndText($img, $width, $height, $title, $subtitle);

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
            $r = (int) (10 + $ratio * 16);
            $g = (int) (14 - $ratio * 9);
            $b = (int) (39 + $ratio * 12);
            $color = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $width, $y, $color);
        }
    }

    private function drawDecorations($img, int $width, int $height): void
    {
        // Glowing orb top-right
        $this->drawGlow($img, (int) ($width * 0.85), (int) ($height * 0.2), 180, 0, 150, 255, 0.08);

        // Glowing orb bottom-left
        $this->drawGlow($img, (int) ($width * 0.15), (int) ($height * 0.75), 200, 120, 50, 200, 0.06);

        // Subtle grid
        $gridColor = imagecolorallocatealpha($img, 255, 255, 255, 120);
        for ($x = 0; $x < $width; $x += 60) {
            imageline($img, $x, 0, $x, $height, $gridColor);
        }
        for ($y = 0; $y < $height; $y += 60) {
            imageline($img, 0, $y, $width, $y, $gridColor);
        }

        // Accent line at top
        for ($x = 0; $x < $width; $x++) {
            $ratio = $x / $width;
            $r = (int) (0 + $ratio * 160);
            $g = (int) (200 - $ratio * 120);
            $color = imagecolorallocate($img, $r, $g, 255);
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

    private function drawLogoAndText($img, int $width, int $height, string $title, string $subtitle): void
    {
        $fontBold = $this->resolveFont('Sarabun-Bold.ttf', 'DejaVuSans-Bold.ttf');
        $fontRegular = $this->resolveFont('Sarabun-Regular.ttf', 'DejaVuSans.ttf');

        $white = imagecolorallocate($img, 255, 255, 255);
        $lightGray = imagecolorallocate($img, 180, 190, 210);
        $cyan = imagecolorallocate($img, 0, 220, 255);

        // Try loading admin logo
        $logoPlaced = $this->drawAdminLogo($img, $width, $height);

        // Calculate text Y position based on whether logo was placed
        $textStartY = $logoPlaced ? 340 : 230;

        try {
            if (! $fontBold || ! $fontRegular) {
                throw new \RuntimeException('Font not found');
            }

            if (! $logoPlaced) {
                // Draw "X" text logo if no admin logo
                imagettftext($img, 60, 0, 80, 240, $cyan, $fontBold, 'X');
            }

            // Title - centered
            $titleSize = $logoPlaced ? 42 : 48;
            $bbox = imagettfbbox($titleSize, 0, $fontBold, $title);
            $titleWidth = abs($bbox[2] - $bbox[0]);
            $titleX = (int) (($width - $titleWidth) / 2);
            imagettftext($img, $titleSize, 0, $titleX, $textStartY, $white, $fontBold, $title);

            // Subtitle - centered
            $subtitleSize = 22;
            $bbox = imagettfbbox($subtitleSize, 0, $fontRegular, $subtitle);
            $subWidth = abs($bbox[2] - $bbox[0]);
            $subX = (int) (($width - $subWidth) / 2);
            imagettftext($img, $subtitleSize, 0, $subX, $textStartY + 50, $lightGray, $fontRegular, $subtitle);
        } catch (\Throwable $e) {
            // Fallback: GD built-in fonts (ASCII only)
            if (! $logoPlaced) {
                $this->drawLargeX($img, (int) ($width / 2 - 30), 130, 60, $cyan);
            }

            // Center text using built-in font width (font 5 = 9px wide, font 4 = 8px wide)
            $titleLen = strlen($title);
            $subLen = strlen($subtitle);
            imagestring($img, 5, (int) (($width - $titleLen * 9) / 2), $textStartY, $title, $white);
            imagestring($img, 4, (int) (($width - $subLen * 8) / 2), $textStartY + 30, $subtitle, $lightGray);
        }
    }

    private function drawAdminLogo($img, int $width, int $height): bool
    {
        $siteLogo = Setting::getValue('site_logo');
        if (! $siteLogo) {
            return false;
        }

        $logoPath = storage_path('app/public/' . $siteLogo);
        if (! is_file($logoPath) || ! is_readable($logoPath)) {
            return false;
        }

        $info = @getimagesize($logoPath);
        if (! $info) {
            return false;
        }

        $logo = match ($info[2]) {
            IMAGETYPE_PNG => @imagecreatefrompng($logoPath),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($logoPath),
            IMAGETYPE_GIF => @imagecreatefromgif($logoPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($logoPath) : false,
            default => false,
        };

        if (! $logo) {
            return false;
        }

        // Scale logo to fit nicely (max 200px tall, max 400px wide)
        $origW = imagesx($logo);
        $origH = imagesy($logo);
        $maxW = 400;
        $maxH = 200;
        $scale = min($maxW / $origW, $maxH / $origH, 1.0);
        $newW = (int) ($origW * $scale);
        $newH = (int) ($origH * $scale);

        // Center horizontally, place in upper-center area
        $destX = (int) (($width - $newW) / 2);
        $destY = (int) (($height - $newH) / 2) - 80;

        imagecopyresampled($img, $logo, $destX, $destY, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($logo);

        return true;
    }

    private function resolveFont(string $sarabunName, string $dejavuName): ?string
    {
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

        $barColor = imagecolorallocatealpha($img, 0, 0, 0, 60);
        imagefilledrectangle($img, 0, $height - 60, $width, $height, $barColor);

        for ($x = 0; $x < $width; $x++) {
            $ratio = $x / $width;
            $r = (int) (0 + $ratio * 160);
            $g = (int) (200 - $ratio * 120);
            $color = imagecolorallocate($img, $r, $g, 255);
            imagefilledrectangle($img, $x, $height - 60, $x, $height - 57, $color);
        }

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

    private function drawLargeX($img, int $x, int $y, int $size, int $color): void
    {
        $thickness = (int) ($size / 6);
        for ($i = 0; $i < $size; $i++) {
            for ($t = 0; $t < $thickness; $t++) {
                imagesetpixel($img, $x + $i + $t, $y + $i, $color);
                imagesetpixel($img, $x + $size - $i + $t, $y + $i, $color);
            }
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
