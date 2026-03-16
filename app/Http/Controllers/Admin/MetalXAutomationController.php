<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAndPostPromoCommentJob;
use App\Jobs\RunAutomationScheduleJob;
use App\Models\MetalXAutomationLog;
use App\Models\MetalXAutomationSchedule;
use App\Models\MetalXPromoComment;
use App\Models\MetalXVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MetalXAutomationController extends Controller
{
    /**
     * Automation dashboard.
     */
    public function index()
    {
        $schedules = MetalXAutomationSchedule::with('video')
            ->orderByDesc('is_enabled')
            ->orderBy('action_type')
            ->get();

        $videos = MetalXVideo::where('is_active', true)
            ->orderBy('title_en')
            ->get();

        $stats = [
            'active_schedules' => MetalXAutomationSchedule::enabled()->count(),
            'total_schedules' => MetalXAutomationSchedule::count(),
            'actions_today' => MetalXAutomationLog::recent(24)->where('status', 'success')->count(),
            'failures_today' => MetalXAutomationLog::recent(24)->where('status', 'failed')->count(),
            'promo_posted_today' => MetalXPromoComment::where('status', 'posted')
                ->where('posted_at', '>=', now()->startOfDay())->count(),
            'promo_drafts' => MetalXPromoComment::draft()->count(),
        ];

        $recentLogs = MetalXAutomationLog::with('video')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.metal-x.automation.index', compact('schedules', 'videos', 'stats', 'recentLogs'));
    }

    /**
     * Create a new schedule.
     */
    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'video_id' => 'nullable|exists:metal_x_videos,id',
            'action_type' => 'required|in:auto_reply,auto_like,auto_moderate,promo_comment,sync_comments',
            'frequency_minutes' => 'required|integer|min:15|max:1440',
            'max_actions_per_run' => 'required|integer|min:1|max:100',
            'is_enabled' => 'boolean',
        ]);

        $validated['is_enabled'] = $request->boolean('is_enabled', true);
        $validated['next_run_at'] = now()->addMinutes($validated['frequency_minutes']);

        MetalXAutomationSchedule::create($validated);

        return redirect()->route('admin.metal-x.automation.index')
            ->with('success', 'สร้างตารางอัตโนมัติเรียบร้อยแล้ว!');
    }

    /**
     * Update an existing schedule.
     */
    public function updateSchedule(Request $request, MetalXAutomationSchedule $schedule)
    {
        $validated = $request->validate([
            'video_id' => 'nullable|exists:metal_x_videos,id',
            'action_type' => 'required|in:auto_reply,auto_like,auto_moderate,promo_comment,sync_comments',
            'frequency_minutes' => 'required|integer|min:15|max:1440',
            'max_actions_per_run' => 'required|integer|min:1|max:100',
            'is_enabled' => 'boolean',
        ]);

        $validated['is_enabled'] = $request->boolean('is_enabled');

        // Recalculate next_run_at if frequency changed
        if ($validated['frequency_minutes'] !== $schedule->frequency_minutes) {
            $validated['next_run_at'] = now()->addMinutes($validated['frequency_minutes']);
        }

        $schedule->update($validated);

        return redirect()->route('admin.metal-x.automation.index')
            ->with('success', 'อัปเดตตารางอัตโนมัติเรียบร้อยแล้ว!');
    }

    /**
     * Delete a schedule.
     */
    public function destroySchedule(MetalXAutomationSchedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('admin.metal-x.automation.index')
            ->with('success', 'ลบตารางอัตโนมัติเรียบร้อยแล้ว!');
    }

    /**
     * Toggle schedule enabled/disabled.
     */
    public function toggleSchedule(MetalXAutomationSchedule $schedule)
    {
        $schedule->update([
            'is_enabled' => ! $schedule->is_enabled,
            'next_run_at' => ! $schedule->is_enabled ? now()->addMinutes($schedule->frequency_minutes) : $schedule->next_run_at,
        ]);

        return response()->json([
            'success' => true,
            'is_enabled' => $schedule->is_enabled,
            'message' => $schedule->is_enabled ? 'เปิดใช้งานแล้ว' : 'ปิดใช้งานแล้ว',
        ]);
    }

    /**
     * Manually trigger a schedule run.
     */
    public function runNow(MetalXAutomationSchedule $schedule)
    {
        // Set next_run_at to now so RunAutomationScheduleJob picks it up
        $schedule->update(['next_run_at' => now()]);

        RunAutomationScheduleJob::dispatch();

        return response()->json([
            'success' => true,
            'message' => "เริ่มรัน {$schedule->action_label} แล้ว",
        ]);
    }

    /**
     * View automation logs.
     */
    public function logs(Request $request)
    {
        $request->validate([
            'action_type' => 'nullable|in:auto_reply,auto_like,auto_moderate,promo_comment,sync_comments',
            'status' => 'nullable|in:success,failed,skipped',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = MetalXAutomationLog::with('video')
            ->orderByDesc('created_at');

        if ($request->filled('action_type')) {
            $query->forAction($request->action_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $logs = $query->paginate(50);

        $stats = [
            'total_24h' => MetalXAutomationLog::recent(24)->count(),
            'success_24h' => MetalXAutomationLog::recent(24)->where('status', 'success')->count(),
            'failed_24h' => MetalXAutomationLog::recent(24)->where('status', 'failed')->count(),
            'skipped_24h' => MetalXAutomationLog::recent(24)->where('status', 'skipped')->count(),
        ];

        return view('admin.metal-x.automation.logs', compact('logs', 'stats'));
    }

    /**
     * View promo comments.
     */
    public function promoComments(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:draft,scheduled,posted,failed',
            'video_id' => 'nullable|integer|exists:metal_x_videos,id',
        ]);

        $query = MetalXPromoComment::with('video')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('video_id')) {
            $query->where('video_id', $request->video_id);
        }

        $promos = $query->paginate(30);

        $videos = MetalXVideo::where('is_active', true)
            ->orderBy('title_en')
            ->get();

        $stats = [
            'total' => MetalXPromoComment::count(),
            'drafts' => MetalXPromoComment::draft()->count(),
            'scheduled' => MetalXPromoComment::scheduled()->count(),
            'posted' => MetalXPromoComment::posted()->count(),
            'posted_today' => MetalXPromoComment::posted()
                ->where('posted_at', '>=', now()->startOfDay())->count(),
        ];

        return view('admin.metal-x.automation.promo', compact('promos', 'videos', 'stats'));
    }

    /**
     * Generate a promo comment for a video.
     */
    public function generatePromo(MetalXVideo $video)
    {
        GenerateAndPostPromoCommentJob::dispatch($video, true);

        return response()->json([
            'success' => true,
            'message' => "กำลังสร้างคอมเม้นต์โปรโมทสำหรับ: {$video->title_en}",
        ]);
    }

    /**
     * Approve a draft promo comment for posting.
     */
    public function approvePromo(MetalXPromoComment $promo)
    {
        if ($promo->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'สามารถอนุมัติได้เฉพาะแบบร่างเท่านั้น',
            ], 422);
        }

        $promo->update([
            'status' => 'scheduled',
            'scheduled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อนุมัติแล้ว — จะโพสในรอบถัดไป',
        ]);
    }

    /**
     * Delete a promo comment.
     */
    public function deletePromo(MetalXPromoComment $promo)
    {
        if ($promo->status === 'posted') {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถลบคอมเม้นต์ที่โพสแล้วได้',
            ], 422);
        }

        $promo->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบคอมเม้นต์โปรโมทเรียบร้อยแล้ว',
        ]);
    }
}
