<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageService
{
    protected ImageManager $manager;

    protected int $quality;

    public function __construct(int $quality = 80)
    {
        $this->manager = new ImageManager(new Driver);
        $this->quality = $quality;
    }

    /**
     * Store an uploaded image as WebP.
     *
     * @param  UploadedFile  $file  The uploaded image file
     * @param  string  $directory  Storage directory (e.g. 'avatars', 'banners')
     * @param  string  $disk  Storage disk (default: 'public')
     * @param  string|null  $filename  Custom filename without extension (auto-generated if null)
     * @param  int|null  $maxWidth  Max width to resize (null = no resize)
     * @param  int|null  $quality  WebP quality 1-100 (null = use default)
     * @return string|null  The stored path, or null on failure
     */
    public function storeAsWebp(
        UploadedFile $file,
        string $directory,
        string $disk = 'public',
        ?string $filename = null,
        ?int $maxWidth = null,
        ?int $quality = null,
    ): ?string {
        try {
            $image = $this->manager->read($file->getPathname());

            // Resize if maxWidth specified (maintain aspect ratio)
            if ($maxWidth !== null && $image->width() > $maxWidth) {
                $image->scaleDown(width: $maxWidth);
            }

            $webpData = $image->toWebp($quality ?? $this->quality)->toString();

            $name = $filename ?? Str::random(40);
            $path = rtrim($directory, '/') . '/' . $name . '.webp';

            Storage::disk($disk)->put($path, $webpData);

            return $path;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Replace an existing image with a new WebP version.
     * Deletes the old file if it exists.
     */
    public function replaceWithWebp(
        UploadedFile $file,
        ?string $oldPath,
        string $directory,
        string $disk = 'public',
        ?string $filename = null,
        ?int $maxWidth = null,
        ?int $quality = null,
    ): ?string {
        if ($oldPath) {
            Storage::disk($disk)->delete($oldPath);
        }

        return $this->storeAsWebp($file, $directory, $disk, $filename, $maxWidth, $quality);
    }
}
