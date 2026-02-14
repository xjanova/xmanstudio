<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Services\GitHubIssueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BugReportController extends Controller
{
    public function __construct(
        private GitHubIssueService $githubService
    ) {}

    /**
     * Submit a new bug report
     *
     * POST /api/v1/bug-reports
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_name' => 'required|string|max:50',
            'product_version' => 'nullable|string|max:20',
            'report_type' => 'required|string|in:bug,misclassification,feature_request,crash,performance',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'metadata' => 'nullable|array',
            'device_id' => 'nullable|string|max:255',
            'user_email' => 'nullable|email|max:255',
            'priority' => 'nullable|in:low,medium,high,critical',
            'severity' => 'nullable|in:minor,moderate,major,critical',
            'os_version' => 'nullable|string|max:100',
            'app_version' => 'nullable|string|max:20',
            'stack_trace' => 'nullable|string',
            'additional_info' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $report = BugReport::create($validator->validated());

            Log::info('Bug report created', [
                'report_id' => $report->id,
                'product' => $report->product_name,
                'type' => $report->report_type,
            ]);

            // Optionally post to GitHub immediately (can also be done via queue)
            if (config('services.github.auto_post', false)) {
                $this->githubService->createIssue($report);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $report->id,
                    'status' => $report->status,
                    'github_issue_url' => $report->github_issue_url,
                ],
                'message' => 'Bug report submitted successfully',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create bug report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit bug report',
            ], 500);
        }
    }

    /**
     * Submit multiple bug reports in batch
     *
     * POST /api/v1/bug-reports/batch
     */
    public function storeBatch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reports' => 'required|array|min:1|max:50',
            'reports.*.product_name' => 'required|string|max:50',
            'reports.*.product_version' => 'nullable|string|max:20',
            'reports.*.report_type' => 'required|string|in:bug,misclassification,feature_request,crash,performance',
            'reports.*.title' => 'required|string|max:255',
            'reports.*.description' => 'required|string',
            'reports.*.metadata' => 'nullable|array',
            'reports.*.device_id' => 'nullable|string|max:255',
            'reports.*.user_email' => 'nullable|email|max:255',
            'reports.*.os_version' => 'nullable|string|max:100',
            'reports.*.app_version' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $created = [];
            $failed = [];

            foreach ($request->reports as $reportData) {
                try {
                    $report = BugReport::create($reportData);
                    $created[] = $report->id;
                } catch (\Exception $e) {
                    $failed[] = [
                        'title' => $reportData['title'] ?? 'Unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'created_count' => count($created),
                    'failed_count' => count($failed),
                    'created_ids' => $created,
                    'failed_reports' => $failed,
                ],
                'message' => sprintf('%d reports created, %d failed', count($created), count($failed)),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create batch bug reports', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit batch bug reports',
            ], 500);
        }
    }

    /**
     * Get bug report by ID
     *
     * GET /api/v1/bug-reports/{id}
     */
    public function show(string $id): JsonResponse
    {
        $report = BugReport::with(['comments', 'attachments'])->find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Bug report not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Get all bug reports with filters
     *
     * GET /api/v1/bug-reports
     */
    public function index(Request $request): JsonResponse
    {
        $query = BugReport::query();

        // Filters
        if ($request->has('product_name')) {
            $query->forProduct($request->product_name);
        }

        if ($request->has('report_type')) {
            $query->ofType($request->report_type);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('is_analyzed')) {
            $query->where('is_analyzed', $request->boolean('is_analyzed'));
        }

        if ($request->has('is_fixed')) {
            $query->where('is_fixed', $request->boolean('is_fixed'));
        }

        if ($request->has('device_id')) {
            $query->where('device_id', $request->device_id);
        }

        // Pagination
        $perPage = min($request->get('per_page', 20), 100);
        $reports = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    /**
     * Post unposted reports to GitHub
     *
     * POST /api/v1/bug-reports/post-to-github
     */
    public function postToGitHub(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 10), 50);

        $reports = BugReport::notPostedToGitHub()
            ->where('status', 'new')
            ->latest()
            ->limit($limit)
            ->get();

        if ($reports->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No reports to post to GitHub',
                'data' => [
                    'posted_count' => 0,
                ],
            ]);
        }

        $results = $this->githubService->createBatchIssues($reports);

        return response()->json([
            'success' => true,
            'data' => [
                'posted_count' => count($results['success']),
                'failed_count' => count($results['failed']),
                'success' => $results['success'],
                'failed' => $results['failed'],
            ],
            'message' => sprintf('%d reports posted to GitHub', count($results['success'])),
        ]);
    }

    /**
     * Get statistics
     *
     * GET /api/v1/bug-reports/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $productName = $request->get('product_name');

        $query = BugReport::query();
        if ($productName) {
            $query->forProduct($productName);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'by_status' => (clone $query)->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_type' => (clone $query)->selectRaw('report_type, COUNT(*) as count')
                ->groupBy('report_type')
                ->pluck('count', 'report_type'),
            'by_priority' => (clone $query)->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority'),
            'unanalyzed' => (clone $query)->unanalyzed()->count(),
            'unfixed' => (clone $query)->unfixed()->count(),
            'posted_to_github' => (clone $query)->whereNotNull('github_issue_number')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
