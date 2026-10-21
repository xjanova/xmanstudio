<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Models\LicenseKey;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * GigGok (Android) — the public APK download and the page that ties a device to an account.
 *
 * The app is free and has no login screen: every device gets a FREE license from check-machine,
 * and that key is how the app identifies itself to the pack store (/api/packs). Packs, though,
 * are bought on the website by a signed-in person, and PackController finds what a device owns
 * through license_keys.user_id. A device key is born without one — so a pack bought on the web
 * shows up in the app only after its owner attaches the device's key to their account here.
 *
 * ⚠️ The download is tied to giggok on purpose, never /{slug}/download — that would become a back
 *    door to download products that must be bought first.
 * ⚠️ The APK is streamed from xman4289.com, never a redirect to GitHub (owner's rule 2026-09-24).
 */
class GigGokController extends Controller
{
    use ServesReleaseDownloads;

    private const PRODUCT_SLUG = 'giggok';

    /**
     * GET /giggok/download/{version?} — anyone, no session.
     *
     * {version} = exactly the build update/check announced with its sha256 · none = the latest.
     */
    public function download(Request $request, ?string $version = null)
    {
        $product = Product::where('slug', self::PRODUCT_SLUG)
            ->where('is_active', true)
            ->first();

        if (! $product) {
            // The product page is a 404 as well — there is nowhere to send a browser back to
            abort_if($this->isBrowser($request), 404);

            return response()->json(['success' => false, 'error' => 'Product not found'], 404);
        }

        return $this->serveApk(
            $request,
            $product,
            $this->releaseFor($product, $version),
            route('products.show', self::PRODUCT_SLUG),
            'giggok',
        );
    }

    /**
     * GET /giggok/link — the form, plus the devices already on this account.
     */
    public function showLink(Request $request): View
    {
        $product = $this->appProduct();

        $linked = LicenseKey::where('product_id', $product->id)
            ->where('user_id', $request->user()->id)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->get();

        return view('giggok.link', compact('product', 'linked'));
    }

    /**
     * POST /giggok/link — attach the device key typed in to the signed-in account.
     *
     * Only an active, unexpired key of the app itself counts — a pack's key, or one revoked by an
     * admin, is "not found". A key someone else already attached stays theirs: taking it over
     * would move their purchases off their own phone.
     */
    public function link(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'license_key' => ['required', 'string', 'max:100'],
        ], [
            'license_key.required' => 'กรุณากรอก License Key จากแอป GigGok',
            'license_key.max' => 'License Key ยาวเกินไป กรุณาตรวจสอบอีกครั้ง',
        ]);

        $product = $this->appProduct();
        $userId = (int) $request->user()->id;
        $typed = trim($validated['license_key']);

        // Keys are issued in upper case (FREE-…); someone copying one by hand may not keep that
        $license = LicenseKey::where('product_id', $product->id)
            ->whereIn('license_key', array_values(array_unique([$typed, strtoupper($typed)])))
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if (! $license) {
            return redirect()->route('giggok.link')->withInput()->withErrors([
                'license_key' => 'ไม่พบ License Key นี้ หรือ License หมดอายุ/ถูกระงับแล้ว — คัดลอกจากหน้าตั้งค่าในแอป GigGok แล้วลองอีกครั้ง',
            ]);
        }

        if ($license->user_id === null) {
            // Conditional update: of two accounts submitting the same key at once, exactly one wins
            $claimed = LicenseKey::where('id', $license->id)
                ->whereNull('user_id')
                ->update(['user_id' => $userId]);

            if ($claimed === 1) {
                Log::info('giggok: device license linked to account', [
                    'license_id' => $license->id,
                    'user_id' => $userId,
                ]);

                return redirect()->route('giggok.link')
                    ->with('success', 'ผูกเครื่องกับบัญชีเรียบร้อยแล้ว — ชุดที่ซื้อบนเว็บจะขึ้นในร้านชุดของแอปเมื่อเปิดร้านครั้งถัดไป');
            }

            $license->refresh();
        }

        if ((int) $license->user_id === $userId) {
            return redirect()->route('giggok.link')
                ->with('success', 'เครื่องนี้ผูกกับบัญชีของคุณอยู่แล้ว');
        }

        Log::warning('giggok: link refused, license belongs to another account', [
            'license_id' => $license->id,
            'user_id' => $userId,
        ]);

        return redirect()->route('giggok.link')->withInput()->withErrors([
            'license_key' => 'License Key นี้ผูกกับบัญชีอื่นอยู่แล้ว หากเป็นเครื่องของคุณ กรุณาติดต่อฝ่ายบริการลูกค้า',
        ]);
    }

    /**
     * The product whose keys identify a GigGok device — the same one the pack store resolves
     * bearer tokens against (config/packs.php), so a key linked here is a key /api/packs/mine reads.
     */
    private function appProduct(): Product
    {
        $product = Product::where('slug', config('packs.app_product_slug', self::PRODUCT_SLUG))->first();

        abort_unless($product, 404);

        return $product;
    }
}
