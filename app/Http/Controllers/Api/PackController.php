<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AvatarPack;
use App\Models\DownloadLog;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\ProductVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * The GigGok app's pack store.
 *
 * Contract lives in the app repo at docs/pack-store.md. Three endpoints:
 * a public catalogue, the caller's owned list, and a download link.
 *
 * The catalogue and the owned list are deliberately separate calls. The
 * catalogue barely changes and can be cached hard; ownership changes the
 * second a payment clears. Serving them together would make the whole
 * response uncacheable to keep the half that moves correct.
 */
class PackController extends Controller
{
    /**
     * GET /api/packs - everything on sale, no authentication.
     *
     * Someone who has not bought anything still needs to see what exists.
     */
    public function index(): JsonResponse
    {
        $packs = AvatarPack::active()
            ->with(['product.versions'])
            ->get()
            ->filter(fn (AvatarPack $p) => $p->product?->is_active)
            ->map(fn (AvatarPack $p) => $p->toCatalogueArray())
            ->values();

        return response()->json(['packs' => $packs]);
    }

    /**
     * GET /api/packs/mine - which packs this license's owner has bought.
     *
     * Returns ids only. The app already has the catalogue and matches them
     * up itself, so sending the full records again would be the same bytes
     * twice for nothing.
     */
    public function mine(Request $request): JsonResponse
    {
        $license = $this->resolveLicense($request);

        if (! $license) {
            return response()->json(['error' => 'Invalid or expired license'], 401);
        }

        if (! $license->user_id) {
            // A device-only or demo license proves a machine, not a person,
            // and packs are bought by people. Not an error - an empty library.
            return response()->json(['owned' => []]);
        }

        return response()->json(['owned' => $this->ownedPackIds($license->user_id)]);
    }

    /**
     * POST /api/packs/{pack}/download - a link, only for what was paid for.
     *
     * 403 and 404 are kept distinct on purpose: "this pack does not exist"
     * and "it exists but you have not bought it" need different words in
     * front of the user, and the app cannot tell them apart from one code.
     */
    public function download(Request $request, string $pack): JsonResponse
    {
        $license = $this->resolveLicense($request);

        if (! $license) {
            return response()->json(['error' => 'Invalid or expired license'], 401);
        }

        $avatarPack = AvatarPack::active()->where('pack_id', $pack)->first();

        if (! $avatarPack || ! $avatarPack->product?->is_active) {
            return response()->json(['error' => 'Pack not found'], 404);
        }

        $free = (float) $avatarPack->product->price <= 0;
        $owned = $license->user_id
            && in_array($pack, $this->ownedPackIds($license->user_id), true);

        if (! $free && ! $owned) {
            return response()->json(['error' => 'Pack not purchased'], 403);
        }

        $version = $avatarPack->product->latestVersion();

        if (! $version || ! $version->storage_path) {
            return response()->json(['error' => 'No file for this pack yet'], 404);
        }

        $minutes = (int) config('packs.download_link_minutes', 10);

        DownloadLog::create([
            'user_id' => $license->user_id,
            'license_key_id' => $license->id,
            'product_version_id' => $version->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'downloaded_at' => now(),
        ]);

        return response()->json([
            'url' => URL::temporarySignedRoute(
                'packs.file',
                now()->addMinutes($minutes),
                ['version' => $version->id]
            ),
            'sha256' => $version->sha256,
            'expiresIn' => $minutes * 60,
        ]);
    }

    /**
     * Stream a pack file. Reached only through a signed, expiring URL.
     *
     * No license check here and that is correct: the signature IS the proof,
     * issued moments ago to a caller whose license was checked then. Checking
     * again would not make it safer, and would break a download that outlives
     * an expiring license by a few minutes.
     */
    public function file(ProductVersion $version)
    {
        $disk = Storage::disk(config('packs.disk', 'local'));

        if (! $version->storage_path || ! $disk->exists($version->storage_path)) {
            abort(404);
        }

        return $disk->download(
            $version->storage_path,
            $version->download_filename ?: basename($version->storage_path)
        );
    }

    /**
     * Turn the bearer token into the license row it names.
     *
     * The app sends its own license key, not a session token - it has no
     * login screen and we do not want one. The key identifies the buyer;
     * what they own is looked up from there.
     */
    protected function resolveLicense(Request $request): ?LicenseKey
    {
        $key = trim((string) $request->bearerToken());

        if ($key === '') {
            return null;
        }

        $appProduct = Product::where('slug', config('packs.app_product_slug'))->first();

        if (! $appProduct) {
            return null;
        }

        return LicenseKey::where('license_key', $key)
            ->where('product_id', $appProduct->id)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Pack ids this user holds an active license for.
     *
     * @return array<int, string>
     */
    protected function ownedPackIds(int $userId): array
    {
        return AvatarPack::active()
            ->whereIn('product_id', function ($q) use ($userId) {
                $q->select('product_id')
                    ->from('license_keys')
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->where(function ($q2) {
                        $q2->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            })
            ->pluck('pack_id')
            ->all();
    }
}
