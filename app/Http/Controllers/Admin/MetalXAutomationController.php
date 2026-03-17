<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAndPostPromoCommentJob;
use App\Jobs\ProcessCommentEngagementJob;
use App\Jobs\RunAutomationScheduleJob;
use App\Models\MetalXAutomationLog;
use App\Models\MetalXAutomationSchedule;
use App\Models\MetalXComment;
use App\Models\MetalXPromoComment;
use App\Models\MetalXVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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
            ->orderBy('title')
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
            'action_type' => 'required|in:auto_reply,auto_like,auto_moderate,promo_comment,sync_comments,auto_generate',
            'frequency_minutes' => 'required|integer|min:15|max:1440',
            'max_actions_per_run' => 'required|integer|min:1|max:100',
            'is_enabled' => 'boolean',
        ]);

        $validated['is_enabled'] = $request->boolean('is_enabled', true);
        $validated['frequency_minutes'] = (int) $validated['frequency_minutes'];
        $validated['max_actions_per_run'] = (int) $validated['max_actions_per_run'];
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
            'action_type' => 'required|in:auto_reply,auto_like,auto_moderate,promo_comment,sync_comments,auto_generate',
            'frequency_minutes' => 'required|integer|min:15|max:1440',
            'max_actions_per_run' => 'required|integer|min:1|max:100',
            'is_enabled' => 'boolean',
        ]);

        $validated['is_enabled'] = $request->boolean('is_enabled');
        $validated['frequency_minutes'] = (int) $validated['frequency_minutes'];
        $validated['max_actions_per_run'] = (int) $validated['max_actions_per_run'];

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
     * Manually trigger a schedule run with progress tracking for auto_reply.
     */
    public function runNow(MetalXAutomationSchedule $schedule)
    {
        // For auto_reply, process ALL unreplied comments with progress tracking
        if ($schedule->action_type === 'auto_reply') {
            return $this->runAutoReplyWithProgress($schedule);
        }

        // For other types, dispatch normally
        $schedule->update(['next_run_at' => now()]);
        RunAutomationScheduleJob::dispatch();

        return response()->json([
            'success' => true,
            'message' => "เริ่มรัน {$schedule->action_label} แล้ว",
        ]);
    }

    /**
     * Run auto-reply for ALL unreplied comments with progress tracking.
     */
    protected function runAutoReplyWithProgress(MetalXAutomationSchedule $schedule)
    {
        $progressKey = 'auto_reply_' . Str::random(16);

        // Get target videos
        $videos = $schedule->video_id
            ? collect([$schedule->video])->filter()
            : MetalXVideo::where('is_active', true)->get();

        // Count total unreplied comments across all target videos
        $unrepliedQuery = MetalXComment::topLevel()
            ->where('ai_replied', false)
            ->where('is_spam', false)
            ->where('is_hidden', false)
            ->where(function ($q) {
                $q->where('can_reply', true)->orWhereNull('can_reply');
            });

        if ($schedule->video_id) {
            $unrepliedQuery->where('video_id', $schedule->video_id);
        } else {
            $unrepliedQuery->whereIn('video_id', $videos->pluck('id'));
        }

        $totalUnreplied = $unrepliedQuery->count();

        // Initialize progress
        Cache::put($progressKey, [
            'status' => 'running',
            'total' => $totalUnreplied,
            'processed' => 0,
            'replied' => 0,
            'failed' => 0,
            'current_comment' => '',
        ], 600);

        // Dispatch each comment reply job (they run async via queue)
        $comments = $unrepliedQuery->orderByDesc('published_at')->get();
        $dispatched = 0;

        foreach ($comments as $comment) {
            ProcessCommentEngagementJob::dispatch($comment, true, false);
            $dispatched++;
        }

        // Update progress with dispatch count
        Cache::put($progressKey, [
            'status' => $dispatched > 0 ? 'dispatched' : 'completed',
            'total' => $totalUnreplied,
            'dispatched' => $dispatched,
            'processed' => 0,
            'replied' => 0,
            'failed' => 0,
            'message' => $dispatched > 0
                ? "กำลังตอบคอมเม้นต์ {$dispatched} รายการ..."
                : 'ไม่มีคอมเม้นต์ที่ต้องตอบ',
        ], 600);

        $schedule->markRun();

        MetalXAutomationLog::log('auto_reply', 'success', [
            'video_id' => $schedule->video_id,
            'details' => ['dispatched' => $dispatched, 'total_unreplied' => $totalUnreplied],
        ]);

        return response()->json([
            'success' => true,
            'progress_key' => $progressKey,
            'total_unreplied' => $totalUnreplied,
            'dispatched' => $dispatched,
            'message' => $dispatched > 0
                ? "กำลังตอบคอมเม้นต์ {$dispatched} จาก {$totalUnreplied} รายการ"
                : 'ไม่มีคอมเม้นต์ที่ต้องตอบ',
        ]);
    }

    /**
     * Get automation run progress (AJAX polling endpoint).
     */
    public function runProgress(Request $request)
    {
        $key = $request->get('key');

        if (! $key) {
            return response()->json(['error' => 'No progress key'], 400);
        }

        $progress = Cache::get($key);

        if (! $progress) {
            // Key expired or doesn't exist — check unreplied count as live status
            $unreplied = MetalXComment::topLevel()
                ->where('ai_replied', false)
                ->where('is_spam', false)
                ->where('is_hidden', false)
                ->where(function ($q) {
                    $q->where('can_reply', true)->orWhereNull('can_reply');
                })
                ->count();

            return response()->json([
                'status' => 'live',
                'unreplied_remaining' => $unreplied,
            ]);
        }

        return response()->json($progress);
    }

    /**
     * View automation logs.
     */
    public function logs(Request $request)
    {
        $request->validate([
            'action_type' => 'nullable|in:auto_reply,auto_like,auto_moderate,promo_comment,sync_comments,auto_generate',
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
            ->orderBy('title')
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
            'message' => "กำลังสร้างคอมเม้นต์โปรโมทสำหรับ: {$video->title}",
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
     * Quick Setup: create standard automation schedules for all action types.
     * Designed for aggressive comment monitoring - check every channel, every video.
     */
    public function quickSetup()
    {
        $created = 0;
        // Aggressive settings: check every 5 minutes (fastest scheduler interval)
        // for ALL channels and ALL videos — respond to EVERY comment ASAP
        $defaultSchedules = [
            [
                'action_type' => 'sync_comments',
                'frequency_minutes' => 15, // Min allowed by model validation
                'max_actions_per_run' => 100, // Sync up to 100 comments per video
                'settings' => ['max_comments' => 200],
            ],
            [
                'action_type' => 'auto_moderate',
                'frequency_minutes' => 15, // Check spam/gambling/bad comments ASAP
                'max_actions_per_run' => 50,
                'settings' => null,
            ],
            [
                'action_type' => 'auto_reply',
                'frequency_minutes' => 15, // Reply to EVERY comment possible
                'max_actions_per_run' => 50,
                'settings' => ['min_confidence' => 60], // Lower threshold = reply more
            ],
            [
                'action_type' => 'auto_like',
                'frequency_minutes' => 15, // Heart every good comment
                'max_actions_per_run' => 100,
                'settings' => null,
            ],
            [
                'action_type' => 'promo_comment',
                'frequency_minutes' => 360, // Promo every 6 hours
                'max_actions_per_run' => 5,
                'settings' => ['require_approval' => false],
            ],
        ];

        foreach ($defaultSchedules as $config) {
            // Skip if same action_type already exists globally (video_id is null)
            $exists = MetalXAutomationSchedule::whereNull('video_id')
                ->where('action_type', $config['action_type'])
                ->exists();

            if (! $exists) {
                MetalXAutomationSchedule::create([
                    'video_id' => null, // Global = all videos on all channels
                    'action_type' => $config['action_type'],
                    'is_enabled' => true,
                    'frequency_minutes' => $config['frequency_minutes'],
                    'max_actions_per_run' => $config['max_actions_per_run'],
                    'next_run_at' => now(),
                    'settings' => $config['settings'],
                ]);
                $created++;
            }
        }

        if ($created > 0) {
            return redirect()->route('admin.metal-x.automation.index')
                ->with('success', "ตั้งค่าด่วนสำเร็จ! สร้าง {$created} ตารางอัตโนมัติ — ซิงค์ทุก 15 นาที, ตอบ+ไลค์+ตรวจสอบทุกคอมเม้นต์");
        }

        return redirect()->route('admin.metal-x.automation.index')
            ->with('info', 'มีตารางอัตโนมัติครบทุกประเภทแล้ว');
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
