<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenseKey;
use App\Models\Product;
use App\Models\User;
use App\Services\AppAuth\AppAccountLinker;
use App\Services\AppAuth\DeviceAuthorizations;
use App\Support\Alerts\SecurityAlerts;
use App\Support\Auth\LoginLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Account sign-in from the desktop apps — /api/v1/product/{productSlug}/app-auth/*
 * (installer contract section 1). Same envelope as the product licence API:
 * {success, message, error_code, data}. The apps branch on error_code only; messages are Thai
 * for the person reading them.
 *
 * Nothing here issues a token or a session: a sign-in only answers which licence this account
 * holds for the app on this machine (AppAccountLinker), and activation stays with /activate.
 */
class AppAuthController extends Controller
{
    /**
     * A bcrypt hash of a random string nobody knows. Checked when the e-mail has no account so a
     * wrong address takes as long as a wrong password — the timing must not say which one it was.
     */
    private const DUMMY_HASH = '$2y$12$RKpbRy8BbN7vbbvVPeaxhelGXvt8Nw1O90Qwx.FJXFVMu1DGdkguO';

    public function __construct(
        private readonly AppAccountLinker $linker,
        private readonly DeviceAuthorizations $devices,
    ) {}

    /**
     * GET app-auth/health — the app's first call: is account sign-in here, and what time is it.
     */
    public function health(string $productSlug): JsonResponse
    {
        $product = $this->product($productSlug);
        if (! $product) {
            return $this->productNotFound();
        }

        return $this->ok([
            'server_time' => AppAccountLinker::iso(now()),
            'product' => [
                'slug' => $product->licenseSlug(),
                'name' => $product->name,
            ],
            'capabilities' => [
                'password_login' => true,
                'device_login' => true,
                'free_license' => AppAccountLinker::freeLicenseAllowed($product),
            ],
        ]);
    }

    /**
     * POST app-auth/login — e-mail + password typed into the app.
     */
    public function login(Request $request, string $productSlug): JsonResponse
    {
        $product = $this->product($productSlug);
        if (! $product) {
            return $this->productNotFound();
        }

        $validator = Validator::make($request->all(), [
            'login' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            ...self::machineRules(),
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator->errors()->toArray());
        }

        $login = trim((string) $request->input('login'));
        $user = User::where('email', $login)->first();
        $passwordOk = Hash::check((string) $request->input('password'), $user?->password ?: self::DUMMY_HASH);

        if (! $user || ! $passwordOk) {
            // The same books as the sign-in form and /api/v1/auth/login: failure counter, IP auto-block,
            // the admin chat card. One answer for a wrong address and a wrong password.
            SecurityAlerts::failedLogin($login, $request->ip(), $user);
            LoginLog::failed($login, $user, 'app');

            return $this->fail('INVALID_CREDENTIALS', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง', 401);
        }

        if (! $user->is_active) {
            LoginLog::failed($login, $user, 'app');

            return $this->fail('ACCOUNT_DISABLED', 'บัญชีนี้ถูกระงับ ติดต่อทีมงาน', 403);
        }

        LoginLog::success($user, 'app');
        SecurityAlerts::adminLogin($user, $request->ip(), $request->userAgent());

        return $this->ok(
            $this->linker->link($user, $product, (string) $request->input('machine_id')),
            'เชื่อมบัญชีแล้ว'
        );
    }

    /**
     * POST app-auth/device/start — a code pair for the QR / "sign in on your phone" path.
     */
    public function deviceStart(Request $request, string $productSlug): JsonResponse
    {
        $product = $this->product($productSlug);
        if (! $product) {
            return $this->productNotFound();
        }

        $validator = Validator::make($request->all(), self::machineRules());
        if ($validator->fails()) {
            return $this->validationFailed($validator->errors()->toArray());
        }

        $codes = $this->devices->start(
            $product,
            (string) $request->input('machine_id'),
            self::optionalString($request->input('machine_name')),
            self::optionalString($request->input('app_version')),
        );

        $verificationUri = route('app-device.show');

        return $this->ok([
            'device_code' => $codes['device_code'],
            'user_code' => $codes['user_code'],
            'verification_uri' => $verificationUri,
            'verification_uri_complete' => $verificationUri . '?code=' . $codes['user_code'],
            'expires_in' => DeviceAuthorizations::TTL_SECONDS,
            'interval' => DeviceAuthorizations::INTERVAL_SECONDS,
        ], 'เปิดลิงก์หรือสแกน QR แล้วยืนยันในบัญชีของคุณ');
    }

    /**
     * POST app-auth/device/poll — has the person approved yet?
     */
    public function devicePoll(Request $request, string $productSlug): JsonResponse
    {
        $product = $this->product($productSlug);
        if (! $product) {
            return $this->productNotFound();
        }

        $validator = Validator::make($request->all(), [
            'device_code' => 'required|string|max:128',
            'machine_id' => 'required|string|min:32|max:64',
        ]);
        if ($validator->fails()) {
            return $this->validationFailed($validator->errors()->toArray());
        }

        $deviceCode = (string) $request->input('device_code');
        $machineId = (string) $request->input('machine_id');
        $record = $this->devices->findByDeviceCode($deviceCode);

        // Unknown, expired, another product's code, or another machine's: all the same answer
        if (! $record
            || (int) $record['product_id'] !== (int) $product->id
            || ! hash_equals((string) $record['machine_id'], $machineId)) {
            return $this->pollAnswer('EXPIRED_TOKEN', 'โค้ดหมดอายุ ขอโค้ดใหม่');
        }

        if ($this->devices->pollTooSoon($deviceCode)) {
            return $this->pollAnswer('SLOW_DOWN', 'ถามถี่เกินไป เว้นระยะอีกสักครู่');
        }

        if ($record['status'] === DeviceAuthorizations::STATUS_PENDING) {
            return $this->pollAnswer('AUTHORIZATION_PENDING', 'รอยืนยันจากหน้าเว็บ');
        }

        if ($record['status'] === DeviceAuthorizations::STATUS_DENIED) {
            return $this->pollAnswer('ACCESS_DENIED', 'ยกเลิกการเชื่อมจากมือถือแล้ว');
        }

        // Approved — handed out once
        if (! $this->devices->consume($deviceCode)) {
            return $this->pollAnswer('EXPIRED_TOKEN', 'โค้ดหมดอายุ ขอโค้ดใหม่');
        }

        $user = User::find($record['user_id']);
        if (! $user || ! $user->is_active) {
            return $this->pollAnswer('ACCESS_DENIED', 'ยกเลิกการเชื่อมจากมือถือแล้ว');
        }

        return $this->ok($this->linker->link($user, $product, $machineId), 'เชื่อมบัญชีแล้ว');
    }

    /**
     * POST app-auth/free-license — the "install fast" path: this machine's FREE key, no account.
     */
    public function freeLicense(Request $request, string $productSlug): JsonResponse
    {
        $product = $this->product($productSlug);
        if (! $product) {
            return $this->productNotFound();
        }

        if (! AppAccountLinker::freeLicenseAllowed($product)) {
            return $this->fail('FREE_LICENSE_NOT_AVAILABLE', 'ไลเซ่นฟรียังไม่เปิดสำหรับโปรแกรมนี้', 403);
        }

        $validator = Validator::make($request->all(), self::machineRules());
        if ($validator->fails()) {
            return $this->validationFailed($validator->errors()->toArray());
        }

        $license = LicenseKey::freeFor($product, (string) $request->input('machine_id'));

        return $this->ok([
            'license' => AppAccountLinker::describe($license, 'free'),
            'account_linked' => false,
        ], 'ได้รับไลเซ่นฟรีแล้ว');
    }

    // ──────────────────────────────────────────────────────────── helpers

    private static function machineRules(): array
    {
        return [
            // the same bounds as /activate and /check-machine
            'machine_id' => 'required|string|min:32|max:64',
            'machine_name' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
        ];
    }

    private static function optionalString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function product(string $productSlug): ?Product
    {
        return Product::bySlugOrAlias($productSlug)
            ->where('requires_license', true)
            ->first();
    }

    private function ok(array $data, string $message = 'สำเร็จ'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'error_code' => null,
            'data' => $data,
        ]);
    }

    private function fail(string $code, string $message, int $status, ?array $data = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $code,
            'data' => $data,
        ], $status);
    }

    /** Poll states are answers, not errors: HTTP 200 so the app's HTTP layer never treats them as a failure. */
    private function pollAnswer(string $code, string $message): JsonResponse
    {
        return $this->fail($code, $message, 200);
    }

    private function productNotFound(): JsonResponse
    {
        return $this->fail('PRODUCT_NOT_FOUND', 'เซิร์ฟเวอร์ไม่รู้จักโปรแกรมนี้', 404);
    }

    private function validationFailed(array $errors): JsonResponse
    {
        return $this->fail('VALIDATION_FAILED', 'ข้อมูลที่ส่งมาไม่ครบหรือไม่ถูกต้อง', 422, ['errors' => $errors]);
    }
}
