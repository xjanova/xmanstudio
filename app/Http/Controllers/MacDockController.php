<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesReleaseDownloads;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\ProductDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * MacDock (Windows) — the public zip download and the page where a signed-in customer takes the free licence.
 *
 * The app is free. Its licence only unlocks automatic updates: the app opens
 * /products/macdock/free-license?machine_id=…, the customer signs in (or signs up) and presses confirm,
 * and a FREE key is created for that PC on their account. The app polls check-machine and picks it up.
 * check-machine never hands out a key by itself (MacDock is not in FREEMIUM_PRODUCTS — owner, 2026-10-14).
 *
 * ⚠️ The download is tied to macdock on purpose, never /{slug}/download — that would become a back
 *    door to download products that must be bought first.
 * ⚠️ Releases live in a private repository: the zip is streamed from xman4289.com with the product's
 *    token, never a redirect to GitHub (owner's rule 2026-09-24).
 */
class MacDockController extends Controller
{
    use ServesReleaseDownloads;

    private const PRODUCT_SLUG = 'macdock';

    /** what the app sends: sha256 hex of its machine identity (the API itself accepts 32-64 characters) */
    private const MACHINE_ID_RULE = 'regex:/^[A-Za-z0-9]{32,64}$/';

    /**
     * GET /macdock/download/{version?} — anyone, no session.
     *
     * {version} = exactly the build update/check announced with its sha256 · none = the latest.
     */
    public function download(Request $request, ?string $version = null)
    {
        $product = Product::where('slug', self::PRODUCT_SLUG)
            ->where('is_active', true)
            ->first();

        if (! $product) {
            abort_if($this->isBrowser($request), 404);

            return response()->json(['success' => false, 'error' => 'Product not found'], 404);
        }

        $release = $this->releaseFor($product, $version);

        return $this->serveRelease(
            $request,
            $product,
            $release,
            route('products.show', self::PRODUCT_SLUG),
            // a version made by hand in the admin may not know its file name — same name as the release asset
            $release ? "MacDock-{$release->version}-win-x64.zip" : null,
        );
    }

    /**
     * GET /products/macdock/free-license?machine_id=… — what this PC holds, and the confirm button.
     */
    public function showFreeLicense(Request $request): View
    {
        $product = $this->appProduct();
        $userId = (int) $request->user()->id;

        $machineId = (string) $request->query('machine_id', '');
        $validMachine = preg_match('/^[A-Za-z0-9]{32,64}$/', $machineId) === 1;

        $device = $validMachine ? $this->device($product, $machineId) : null;
        $license = $device ? $this->machineKey($product, $machineId) : null;

        $state = match (true) {
            ! $validMachine => 'no_machine',
            $device === null => 'unknown_device',
            $license === null, $license->user_id === null => 'ready',
            (int) $license->user_id === $userId => 'mine',
            default => 'taken',
        };

        $myLicenses = $this->activeKeys($product)
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return view('macdock.free-license', [
            'product' => $product,
            'state' => $state,
            'machineId' => $validMachine ? $machineId : null,
            'device' => $device,
            'myLicenses' => $myLicenses,
            'deviceNames' => ProductDevice::where('product_id', $product->id)
                ->whereIn('machine_id', $myLicenses->pluck('machine_id')->filter())
                ->pluck('machine_name', 'machine_id'),
        ]);
    }

    /**
     * POST /products/macdock/free-license — the FREE key for this PC, on the signed-in account.
     *
     * Only a PC the app has registered (register-device) can be claimed. A PC whose key already sits on
     * another account stays theirs. Of two accounts confirming the same PC at once, exactly one wins.
     */
    public function claimFreeLicense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'machine_id' => ['required', 'string', self::MACHINE_ID_RULE],
        ], [
            'machine_id.required' => 'ไม่พบรหัสเครื่อง กรุณากดรับไลเซ่นฟรีจากในโปรแกรม MacDock',
            'machine_id.regex' => 'รหัสเครื่องไม่ถูกต้อง กรุณากดรับไลเซ่นฟรีจากในโปรแกรม MacDock',
        ]);

        $product = $this->appProduct();
        $userId = (int) $request->user()->id;
        $machineId = $validated['machine_id'];
        $back = route('macdock.free-license', ['machine_id' => $machineId]);

        $device = $this->device($product, $machineId);
        if (! $device) {
            return redirect($back)->with('error', 'ยังไม่พบเครื่องนี้ในระบบ — เปิดโปรแกรม MacDock ให้เชื่อมต่ออินเทอร์เน็ตสักครู่ แล้วกดรับไลเซ่นฟรีอีกครั้ง');
        }

        $license = $this->machineKey($product, $machineId)
            ?? LicenseKey::freeFor($product, $machineId);

        if ($license->user_id === null) {
            // Conditional update: of two accounts confirming the same PC at once, exactly one wins
            $claimed = LicenseKey::where('id', $license->id)
                ->whereNull('user_id')
                ->update(['user_id' => $userId]);

            if ($claimed === 1) {
                $device->update(['license_id' => $license->id, 'status' => ProductDevice::STATUS_LICENSED]);

                Log::info('macdock: free license claimed', [
                    'license_id' => $license->id,
                    'user_id' => $userId,
                ]);

                return redirect($back)->with('success', 'รับไลเซ่นฟรีเรียบร้อยแล้ว — กลับไปที่โปรแกรม MacDock ไลเซ่นจะขึ้นเองภายในไม่กี่วินาที');
            }

            $license->refresh();
        }

        if ((int) $license->user_id === $userId) {
            return redirect($back)->with('success', 'เครื่องนี้มีไลเซ่นฟรีในบัญชีของคุณอยู่แล้ว');
        }

        Log::warning('macdock: claim refused, the PC belongs to another account', [
            'license_id' => $license->id,
            'user_id' => $userId,
        ]);

        return redirect($back)->with('error', 'เครื่องนี้รับไลเซ่นไว้กับบัญชีอื่นแล้ว หากเป็นเครื่องของคุณ กรุณาติดต่อฝ่ายบริการลูกค้า');
    }

    private function appProduct(): Product
    {
        $product = Product::where('slug', self::PRODUCT_SLUG)->where('is_active', true)->first();

        abort_unless($product, 404);

        return $product;
    }

    private function device(Product $product, string $machineId): ?ProductDevice
    {
        return ProductDevice::where('product_id', $product->id)
            ->where('machine_id', $machineId)
            ->first();
    }

    /**
     * the PC's lasting key — never a DEMO one: a trial started through the API must not be what lands
     * on the account (it lapses within a day), the claim creates the FREE key instead
     */
    private function machineKey(Product $product, string $machineId): ?LicenseKey
    {
        return $this->activeKeys($product)
            ->where('machine_id', $machineId)
            ->where('license_type', '!=', LicenseKey::TYPE_DEMO)
            ->orderBy('id')
            ->first();
    }

    private function activeKeys(Product $product)
    {
        return LicenseKey::where('product_id', $product->id)
            ->where('status', LicenseKey::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
