<?php

namespace App\Support;

use App\Models\User;
use App\Support\Auth\TwoFactor;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Files customers hand us that must never sit under the web root: payment slips
 * (App\Support\PaymentSlips) and support-ticket and project attachments (App\Support\Attachments).
 *
 * Each kind has its own folder on the private "local" disk (storage/app/private). A row keeps the
 * path relative to the disk ("payment-slips/…"), which is the same on the public disk these files
 * used to go to, so moving a file leaves its row right; until a file has been moved it is still
 * found on the public disk, read-only. A file is handed out only by a controller that has checked
 * who is asking, in a response no cache keeps and no browser runs.
 */
final class PrivateFiles
{
    /** Where the files are kept: storage/app/private, outside the web root. */
    public const DISK = 'local';

    /** Where they used to be kept, served at /storage/… — read as a fallback, never written. */
    public const LEGACY_DISK = 'public';

    /** Shown in the browser. Anything else (a PDF, an SVG from before uploads were raster-only) is only offered as a download. */
    private const INLINE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /**
     * May $user open a customer's file as an admin? Only in a session that has passed the admin
     * panel's second sign-in step, as /admin requires.
     */
    public static function adminMayOpen(User $user): bool
    {
        return $user->isAdmin() && TwoFactor::passed(request(), $user);
    }

    /**
     * A stored value as a path inside $directory/, or null. Rows hold "{directory}/…"; a value saved
     * with the public address in front ("/storage/…", "https://host/storage/…") reads as the same
     * path. Anything else is refused: another folder, "..", a hidden file, odd characters. The value
     * comes from a row, and the private disk also holds files that no customer may read.
     */
    public static function normalize(?string $stored, string $directory): ?string
    {
        if ($stored === null) {
            return null;
        }
        $path = (string) preg_replace('~^https?://[^/]+~i', '', trim($stored));
        $path = (string) preg_replace('~^/*(?:storage/)?~', '', $path);
        $segment = '[A-Za-z0-9_-][A-Za-z0-9._-]*';

        return preg_match('~^' . preg_quote($directory, '~') . '(?:/' . $segment . ')+$~', $path) === 1 ? $path : null;
    }

    /**
     * Where a file is, as [disk, path]: the private disk first, then the public one for a file that
     * has not been moved yet. Null when there is no such file, or when the name resolves (through a
     * link) to somewhere outside $directory.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function locate(?string $stored, string $directory): ?array
    {
        $path = self::normalize($stored, $directory);
        if ($path === null) {
            return null;
        }

        foreach ([self::DISK, self::LEGACY_DISK] as $disk) {
            try {
                $root = realpath(Storage::disk($disk)->path($directory));
                $real = realpath(Storage::disk($disk)->path($path));
            } catch (Throwable) {
                continue;
            }
            if ($root !== false && $real !== false && str_starts_with($real, $root . DIRECTORY_SEPARATOR) && is_file($real)) {
                return [$disk, $path];
            }
        }

        return null;
    }

    /**
     * The file as a response to someone already allowed to see it: never cached, never sniffed into
     * another type, shown inline only when it is a raster image. It is saved as $name (Thai is kept;
     * anything that could break the header is dropped) with the stored file's extension. Null when
     * there is no file.
     */
    public static function response(?string $stored, string $directory, string $name): ?StreamedResponse
    {
        $file = self::locate($stored, $directory);
        if ($file === null) {
            return null;
        }
        [$disk, $path] = $file;
        $storage = Storage::disk($disk);

        $type = $storage->mimeType($path) ?: 'application/octet-stream';
        $inline = in_array($type, self::INLINE_TYPES, true);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $extension = $extension !== '' ? '.' . $extension : '';

        $name = mb_substr(trim((string) preg_replace('/[^\p{L}\p{M}\p{N}_-]+/u', '-', $name), '-'), 0, 100);
        $name = $name !== '' ? $name : 'file';
        $ascii = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', Str::ascii($name)), '-');
        $ascii = $ascii !== '' ? $ascii : 'file';

        return $storage->response($path, $name . $extension, [
            'Content-Type' => $inline ? $type : 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition($inline ? 'inline' : 'attachment', $name . $extension, $ascii . $extension),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            // Opened on its own in a tab, nothing in the file may run as this site.
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ]);
    }
}
