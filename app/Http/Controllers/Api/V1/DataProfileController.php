<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductDataProfile;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataProfileController extends Controller
{
    public function __construct(
        private WorkflowService $workflowService,
    ) {}

    /**
     * List user's data profiles for a product.
     */
    public function index(Request $request, string $productSlug): JsonResponse
    {
        $product = $this->workflowService->getProductBySlug($productSlug);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'ไม่พบผลิตภัณฑ์'], 404);
        }

        $profiles = $this->workflowService->listProfiles($request->user(), $product->id);

        return response()->json(['success' => true, 'data' => $profiles]);
    }

    /**
     * Store a new data profile.
     */
    public function store(Request $request, string $productSlug): JsonResponse
    {
        $product = $this->workflowService->getProductBySlug($productSlug);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'ไม่พบผลิตภัณฑ์'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'fields_json' => 'required|string',
            'category' => 'nullable|string|max:255',
            'device_id' => 'nullable|string|max:64',
            'local_id' => 'nullable|integer',
        ]);

        $profile = $this->workflowService->storeProfile(
            $request->user(),
            $product->id,
            $request->all()
        );

        return response()->json(['success' => true, 'data' => $profile], 201);
    }

    /**
     * Update a data profile.
     */
    public function update(Request $request, string $productSlug, int $id): JsonResponse
    {
        $profile = ProductDataProfile::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $profile) {
            return response()->json(['success' => false, 'message' => 'ไม่พบ data profile'], 404);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'fields_json' => 'nullable|string',
            'category' => 'nullable|string|max:255',
        ]);

        $profile = $this->workflowService->updateProfile($profile, $request->all());

        return response()->json(['success' => true, 'data' => $profile]);
    }

    /**
     * Delete a data profile.
     */
    public function destroy(Request $request, string $productSlug, int $id): JsonResponse
    {
        $profile = ProductDataProfile::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $profile) {
            return response()->json(['success' => false, 'message' => 'ไม่พบ data profile'], 404);
        }

        $profile->delete();

        return response()->json(['success' => true, 'message' => 'ลบแล้ว']);
    }

    /**
     * Bulk import data profiles from device.
     */
    public function bulkImport(Request $request, string $productSlug): JsonResponse
    {
        $product = $this->workflowService->getProductBySlug($productSlug);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'ไม่พบผลิตภัณฑ์'], 404);
        }

        $request->validate([
            'profiles' => 'required|array',
            'profiles.*.name' => 'required|string|max:255',
            'profiles.*.fields_json' => 'required|string',
        ]);

        $result = $this->workflowService->bulkImportProfiles(
            $request->user(),
            $product->id,
            $request->input('profiles')
        );

        return response()->json(['success' => true, 'data' => $result]);
    }
}
