<?php

namespace App\Support;

use App\Models\Order;
use App\Models\RentalPayment;
use App\Models\User;
use App\Services\ImageService;
use App\Support\Auth\TwoFactor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Customer payment slips. A transfer slip shows a name, a bank account and an amount, so it never
 * sits under the web root.
 *
 * Slips used to go to the "public" disk, which the web server hands to anyone who asks for
 * /storage/payment-slips/… — no sign-in, and that address travels in logs, e-mails, browser
 * history and Referer headers. They now go to the private "local" disk (storage/app/private) and
 * are opened only through PaymentSlipController, by the customer who sent the slip or by an admin.
 *
 * A row keeps the path relative to the disk ("payment-slips/…"), which is the same on either disk,
 * so moving a file (`payment-slips:privatize`) leaves its row right. Until a slip has been moved it
 * is still found on the public disk, read-only.
 */
final class PaymentSlips
{
    /** Where slips are kept: storage/app/private, outside the web root. */
    public const DISK = 'local';

    /** Where slips used to be kept, served at /storage/… — read as a fallback, never written. */
    public const LEGACY_DISK = 'public';

    public const DIRECTORY = 'payment-slips';

    /** Shown in the browser. Anything else (an SVG from before uploads were raster-only) is only offered as a download. */
    private const INLINE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    /** The largest slip attached to an admin's Telegram card. */
    private const MAX_TELEGRAM_BYTES = 10 * 1024 * 1024;

    /** Keep an uploaded slip as it came (cart orders, rentals). Null when the disk refused it. */
    public static function store(UploadedFile $file): ?string
    {
        $path = $file->store(self::DIRECTORY, self::DISK);

        return is_string($path) && $path !== '' ? $path : null;
    }

    /** Keep an uploaded slip re-encoded as WebP under payment-slips/{product} (the product checkouts). */
    public static function storeAsWebp(UploadedFile $file, string $product): ?string
    {
        return app(ImageService::class)->storeAsWebp($file, self::DIRECTORY . '/' . $product, self::DISK);
    }

    /** The slip on an order: on the row (cart checkout) or in its metadata (product checkouts). */
    public static function forOrder(Order $order): ?string
    {
        // Several product checkouts save json_encode()d text into the array-cast column, so the
        // cast hands back a JSON string rather than an array.
        $meta = $order->metadata;
        if (is_string($meta)) {
            $meta = json_decode($meta, true);
        }
        $slip = $order->payment_slip ?: (is_array($meta) ? ($meta['payment_slip'] ?? null) : null);

        return is_string($slip) && $slip !== '' ? $slip : null;
    }

    /**
     * May $user open the slip on $owner? The customer it belongs to may, and so may an admin, but
     * only in a session that has passed the admin panel's second sign-in step, as /admin requires.
     */
    public static function canView(User $user, Order|RentalPayment $owner): bool
    {
        if ($owner->user_id !== null && (int) $owner->user_id === (int) $user->id) {
            return true;
        }

        return $user->isAdmin() && TwoFactor::passed(request(), $user);
    }

    /**
     * A stored value as a path inside payment-slips/, or null. Rows hold "payment-slips/…"; a value
     * saved with the public address in front ("/storage/…", "https://host/storage/…") reads as the
     * same path. Anything else is refused: another folder, "..", a hidden file, odd characters. The
     * value comes from a row, and the private disk also holds files that no customer may read.
     */
    public static function normalize(?string $stored): ?string
    {
        if ($stored === null) {
            return null;
        }
        $path = (string) preg_replace('~^https?://[^/]+~i', '', trim($stored));
        $path = (string) preg_replace('~^/*(?:storage/)?~', '', $path);
        $segment = '[A-Za-z0-9_-][A-Za-z0-9._-]*';

        return preg_match('~^' . self::DIRECTORY . '(?:/' . $segment . ')+$~', $path) === 1 ? $path : null;
    }

    /**
     * Where a slip's file is, as [disk, path]: the private disk first, then the public one for a slip
     * that has not been moved yet. Null when there is no such file, or when the name resolves (through
     * a link) to somewhere outside the slips folder.
     *
     * @return array{0: string, 1: string}|null
     */
    public static function locate(?string $stored): ?array
    {
        $path = self::normalize($stored);
        if ($path === null) {
            return null;
        }

        foreach ([self::DISK, self::LEGACY_DISK] as $disk) {
            try {
                $root = realpath(Storage::disk($disk)->path(self::DIRECTORY));
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
     * The slip as a response to its customer or an admin: never cached, never sniffed into another
     * type, shown inline only when it is a raster image. Null when there is no file.
     */
    public static function response(?string $stored, string $name): ?StreamedResponse
    {
        $file = self::locate($stored);
        if ($file === null) {
            return null;
        }
        [$disk, $path] = $file;
        $storage = Storage::disk($disk);

        $type = $storage->mimeType($path) ?: 'application/octet-stream';
        $inline = in_array($type, self::INLINE_TYPES, true);
        $name = trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $name), '-') ?: 'payment-slip';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $storage->response($path, $name . ($extension !== '' ? '.' . $extension : ''), [
            'Content-Type' => $inline ? $type : 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            // Opened on its own in a tab, nothing in the file may run as this site.
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
        ], $inline ? 'inline' : 'attachment');
    }

    /**
     * The slip as a file on this server, to attach to the admin's Telegram card: only a JPEG, PNG or
     * WebP of at most 10 MB. Null when there is none.
     */
    public static function localPath(?string $stored): ?string
    {
        $file = self::locate($stored);
        if ($file === null) {
            return null;
        }
        [$disk, $path] = $file;
        $real = realpath(Storage::disk($disk)->path($path));

        return $real !== false && filesize($real) <= self::MAX_TELEGRAM_BYTES && preg_match('/\.(jpe?g|png|webp)$/i', $real) ? $real : null;
    }
}
