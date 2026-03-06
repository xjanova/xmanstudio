<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductWorkflow;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(
        private WorkflowService $workflowService,
    ) {}

    /**
     * List user's workflows for a product.
     */
    public function index(Request $request, string $productSlug): JsonResponse
    {
        $product = $this->workflowService->getProductBySlug($productSlug);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'ไม่พบผลิตภัณฑ์'], 404);
        }

        $workflows = $this->workflowService->listWorkflows(
            $request->user(),
            $product->id,
            $request->only(['search', 'app', 'per_page'])
        );

        return response()->json(['success' => true, 'data' => $workflows]);
    }

    /**
     * Store a new workflow.
     */
    public function store(Request $request, string $productSlug): JsonResponse
    {
        $product = $this->workflowService->getProductBySlug($productSlug);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'ไม่พบผลิตภัณฑ์'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'steps_json' => 'required|string',
            'target_app_package' => 'nullable|string|max:255',
            'target_app_name' => 'nullable|string|max:255',
            'device_id' => 'nullable|string|max:64',
            'app_version' => 'nullable|string|max:50',
            'local_id' => 'nullable|integer',
        ]);

        $workflow = $this->workflowService->storeWorkflow(
            $request->user(),
            $product->id,
            $request->all()
        );

        return response()->json(['success' => true, 'data' => $workflow], 201);
    }

    /**
     * Show a single workflow.
     */
    public function show(Request $request, string $productSlug, int $id): JsonResponse
    {
        $workflow = ProductWorkflow::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $workflow) {
            return response()->json(['success' => false, 'message' => 'ไม่พบ workflow'], 404);
        }

        return response()->json(['success' => true, 'data' => $workflow]);
    }

    /**
     * Update a workflow.
     */
    public function update(Request $request, string $productSlug, int $id): JsonResponse
    {
        $workflow = ProductWorkflow::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $workflow) {
            return response()->json(['success' => false, 'message' => 'ไม่พบ workflow'], 404);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'steps_json' => 'nullable|string',
            'target_app_package' => 'nullable|string|max:255',
            'target_app_name' => 'nullable|string|max:255',
        ]);

        $workflow = $this->workflowService->updateWorkflow($workflow, $request->all());

        return response()->json(['success' => true, 'data' => $workflow]);
    }

    /**
     * Delete a workflow.
     */
    public function destroy(Request $request, string $productSlug, int $id): JsonResponse
    {
        $workflow = ProductWorkflow::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $workflow) {
            return response()->json(['success' => false, 'message' => 'ไม่พบ workflow'], 404);
        }

        $this->workflowService->deleteWorkflow($workflow);

        return response()->json(['success' => true, 'message' => 'ลบแล้ว']);
    }

    /**
     * Generate a share token for a workflow.
     */
    public function share(Request $request, string $productSlug, int $id): JsonResponse
    {
        $workflow = ProductWorkflow::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $workflow) {
            return response()->json(['success' => false, 'message' => 'ไม่พบ workflow'], 404);
        }

        $token = $workflow->generateShareToken();

        return response()->json([
            'success' => true,
            'share_token' => $token,
            'share_url' => url("/shared/workflow/{$token}"),
        ]);
    }

    /**
     * Bulk import workflows from device.
     */
    public function bulkImport(Request $request, string $productSlug): JsonResponse
    {
        $product = $this->workflowService->getProductBySlug($productSlug);
        if (! $product) {
            return response()->json(['success' => false, 'message' => 'ไม่พบผลิตภัณฑ์'], 404);
        }

        $request->validate([
            'workflows' => 'required|array',
            'workflows.*.name' => 'required|string|max:255',
            'workflows.*.steps_json' => 'required|string',
        ]);

        $result = $this->workflowService->bulkImportWorkflows(
            $request->user(),
            $product->id,
            $request->input('workflows')
        );

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Get a shared workflow (public, no auth required).
     */
    public function getShared(string $productSlug, string $token): JsonResponse
    {
        $workflow = ProductWorkflow::where('share_token', $token)
            ->where('is_public', true)
            ->first();

        if (! $workflow) {
            return response()->json(['success' => false, 'message' => 'ไม่พบ workflow ที่แชร์'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'target_app_package' => $workflow->target_app_package,
                'target_app_name' => $workflow->target_app_name,
                'steps_json' => $workflow->steps_json,
                'app_version' => $workflow->app_version,
                'shared_at' => $workflow->shared_at,
            ],
        ]);
    }
}
