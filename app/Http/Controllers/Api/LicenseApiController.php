<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * License API Controller สำหรับ Desktop App
 */
class LicenseApiController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    /**
     * Activate license
     * POST /api/license/activate
     */
    public function activate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
            'machine_id' => 'required|string|max:255',
            'machine_fingerprint' => 'required|string|max:1024',
            'app_version' => 'nullable|string|max:50',
        ]);

        $result = $this->licenseService->activate(
            $request->license_key,
            $request->machine_id,
            $request->machine_fingerprint,
            $request->app_version
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Validate license
     * POST /api/license/validate
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
            'machine_id' => 'required|string',
        ]);

        $result = $this->licenseService->validate(
            $request->license_key,
            $request->machine_id
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Start demo
     * POST /api/license/demo
     */
    public function startDemo(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string|max:255',
            'machine_fingerprint' => 'required|string|max:1024',
        ]);

        $result = $this->licenseService->startDemo(
            $request->machine_id,
            $request->machine_fingerprint
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Check demo status
     * POST /api/license/demo/check
     */
    public function checkDemo(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|string',
        ]);

        $result = $this->licenseService->checkDemo($request->machine_id);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Deactivate license
     * POST /api/license/deactivate
     */
    public function deactivate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
            'machine_id' => 'required|string',
        ]);

        $result = $this->licenseService->deactivate(
            $request->license_key,
            $request->machine_id
        );

        $statusCode = $result['success'] ? 200 : 400;

        return response()->json($result, $statusCode);
    }

    /**
     * Get license status
     * GET /api/license/status/{licenseKey}
     */
    public function status(string $licenseKey): JsonResponse
    {
        $result = $this->licenseService->getStatus($licenseKey);

        $statusCode = $result['success'] ? 200 : 404;

        return response()->json($result, $statusCode);
    }

    /**
     * Generate licenses (Admin only)
     * POST /api/license/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:monthly,yearly,lifetime',
            'quantity' => 'integer|min:1|max:100',
            'max_activations' => 'integer|min:1|max:10',
            'product_id' => 'nullable|exists:products,id',
        ]);

        $licenses = $this->licenseService->generateLicenses(
            $request->type,
            $request->input('quantity', 1),
            $request->input('max_activations', 1),
            $request->product_id
        );

        return response()->json([
            'success' => true,
            'data' => $licenses,
            'message' => sprintf('สร้าง %d license keys สำเร็จ', count($licenses)),
        ]);
    }
}
