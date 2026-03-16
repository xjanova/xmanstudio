<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetalXChannel;
use App\Models\MetalXContentPlan;
use App\Models\MetalXMediaLibrary;
use App\Models\MetalXMusicLibrary;
use App\Models\MetalXVideoProject;
use App\Services\ContentPlanService;
use Illuminate\Http\Request;

class MetalXContentPlanController extends Controller
{
    public function index()
    {
        $plans = MetalXContentPlan::with('channel')
            ->withCount(['videoProjects', 'videoProjects as active_projects_count' => function ($q) {
                $q->whereNotIn('status', ['published', 'failed']);
            }])
            ->latest()
            ->paginate(20);

        $stats = [
            'total_plans' => MetalXContentPlan::count(),
            'active_plans' => MetalXContentPlan::enabled()->count(),
            'total_generated' => MetalXContentPlan::sum('total_generated'),
            'media_count' => MetalXMediaLibrary::active()->count(),
            'music_count' => MetalXMusicLibrary::active()->count(),
            'projects_in_pipeline' => MetalXVideoProject::where('auto_generated', true)
                ->whereNotIn('status', ['published', 'failed'])->count(),
        ];

        return view('admin.metal-x.content-plans.index', compact('plans', 'stats'));
    }

    public function create()
    {
        $channels = MetalXChannel::where('is_active', true)->get();
        $templates = MetalXVideoProject::TEMPLATES;
        $mediaModes = MetalXVideoProject::MEDIA_MODES;
        $eqStyles = MetalXVideoProject::EQ_STYLES;
        $musicStyles = MetalXMusicLibrary::STYLES;
        $frequencyPresets = MetalXContentPlan::FREQUENCY_PRESETS;
        $daysOfWeek = MetalXContentPlan::DAYS_OF_WEEK;
        $availableTags = MetalXMediaLibrary::active()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return view('admin.metal-x.content-plans.create', compact(
            'channels', 'templates', 'mediaModes', 'eqStyles', 'musicStyles',
            'frequencyPresets', 'daysOfWeek', 'availableTags'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'channel_id' => 'required|exists:metal_x_channels,id',
            'name' => 'required|string|max:255',
            'topic_prompt' => 'required|string|max:2000',
            'music_prompt' => 'nullable|string|max:1000',
            'music_style' => 'required|string|max:50',
            'music_duration' => 'required|integer|min:30|max:300',
            'template' => 'required|string|in:' . implode(',', array_keys(MetalXVideoProject::TEMPLATES)),
            'media_mode' => 'required|string|in:images,video_clips,mixed',
            'privacy_status' => 'required|string|in:private,public,unlisted',
            'media_count' => 'required|integer|min:3|max:30',
            'schedule_frequency_hours' => 'required|integer|min:6|max:720',
            'preferred_publish_hour' => 'required|integer|min:0|max:23',
            'preferred_publish_days' => 'nullable|array',
            'preferred_publish_days.*' => 'integer|min:0|max:6',
            'max_queue_size' => 'required|integer|min:1|max:10',
            'media_pool_tags' => 'nullable|string',
            'eq_enabled' => 'nullable|boolean',
            'eq_style' => 'nullable|string',
            'eq_position' => 'nullable|string',
            'eq_height_percent' => 'nullable|integer|min:10|max:50',
            'eq_opacity' => 'nullable|numeric|min:0.1|max:1.0',
            'eq_color' => 'nullable|string',
            'slide_duration' => 'nullable|integer|min:2|max:15',
            'transition' => 'nullable|string',
            'transition_duration' => 'nullable|numeric|min:0.3|max:3',
            'effect' => 'nullable|string',
            'background_color' => 'nullable|string',
        ]);

        // Build template_settings
        $templateSettings = [
            'slide_duration' => $validated['slide_duration'] ?? 5,
            'transition' => $validated['transition'] ?? 'crossfade',
            'transition_duration' => $validated['transition_duration'] ?? 1,
            'effect' => $validated['effect'] ?? 'ken_burns',
            'background_color' => $validated['background_color'] ?? '#000000',
        ];

        // Build eq_settings
        $eqSettings = null;
        if ($request->boolean('eq_enabled')) {
            $eqSettings = [
                'enabled' => true,
                'style' => $validated['eq_style'] ?? 'showcqt',
                'position' => $validated['eq_position'] ?? 'bottom',
                'height_percent' => $validated['eq_height_percent'] ?? 20,
                'opacity' => $validated['eq_opacity'] ?? 0.7,
                'color' => $validated['eq_color'] ?? '#00ff88',
            ];
        }

        // Parse media pool tags
        $mediaPoolTags = null;
        if (! empty($validated['media_pool_tags'])) {
            $mediaPoolTags = array_map('trim', explode(',', $validated['media_pool_tags']));
            $mediaPoolTags = array_values(array_filter($mediaPoolTags));
        }

        $plan = MetalXContentPlan::create([
            'channel_id' => $validated['channel_id'],
            'name' => $validated['name'],
            'topic_prompt' => $validated['topic_prompt'],
            'music_prompt' => $validated['music_prompt'],
            'music_style' => $validated['music_style'],
            'music_duration' => $validated['music_duration'],
            'template' => $validated['template'],
            'template_settings' => $templateSettings,
            'eq_settings' => $eqSettings,
            'media_mode' => $validated['media_mode'],
            'privacy_status' => $validated['privacy_status'],
            'media_pool_tags' => $mediaPoolTags,
            'media_count' => $validated['media_count'],
            'schedule_frequency_hours' => $validated['schedule_frequency_hours'],
            'preferred_publish_hour' => $validated['preferred_publish_hour'],
            'preferred_publish_days' => $validated['preferred_publish_days'] ?? null,
            'max_queue_size' => $validated['max_queue_size'],
            'next_generation_at' => now(),
        ]);

        return redirect()->route('admin.metal-x.content-plans.index')
            ->with('success', "สร้างแผนเนื้อหา \"{$plan->name}\" สำเร็จ");
    }

    public function edit(MetalXContentPlan $plan)
    {
        $channels = MetalXChannel::where('is_active', true)->get();
        $templates = MetalXVideoProject::TEMPLATES;
        $mediaModes = MetalXVideoProject::MEDIA_MODES;
        $eqStyles = MetalXVideoProject::EQ_STYLES;
        $musicStyles = MetalXMusicLibrary::STYLES;
        $frequencyPresets = MetalXContentPlan::FREQUENCY_PRESETS;
        $daysOfWeek = MetalXContentPlan::DAYS_OF_WEEK;
        $availableTags = MetalXMediaLibrary::active()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return view('admin.metal-x.content-plans.edit', compact(
            'plan', 'channels', 'templates', 'mediaModes', 'eqStyles', 'musicStyles',
            'frequencyPresets', 'daysOfWeek', 'availableTags'
        ));
    }

    public function update(Request $request, MetalXContentPlan $plan)
    {
        $validated = $request->validate([
            'channel_id' => 'required|exists:metal_x_channels,id',
            'name' => 'required|string|max:255',
            'topic_prompt' => 'required|string|max:2000',
            'music_prompt' => 'nullable|string|max:1000',
            'music_style' => 'required|string|max:50',
            'music_duration' => 'required|integer|min:30|max:300',
            'template' => 'required|string|in:' . implode(',', array_keys(MetalXVideoProject::TEMPLATES)),
            'media_mode' => 'required|string|in:images,video_clips,mixed',
            'privacy_status' => 'required|string|in:private,public,unlisted',
            'media_count' => 'required|integer|min:3|max:30',
            'schedule_frequency_hours' => 'required|integer|min:6|max:720',
            'preferred_publish_hour' => 'required|integer|min:0|max:23',
            'preferred_publish_days' => 'nullable|array',
            'preferred_publish_days.*' => 'integer|min:0|max:6',
            'max_queue_size' => 'required|integer|min:1|max:10',
            'media_pool_tags' => 'nullable|string',
            'eq_enabled' => 'nullable|boolean',
            'eq_style' => 'nullable|string',
            'eq_position' => 'nullable|string',
            'eq_height_percent' => 'nullable|integer|min:10|max:50',
            'eq_opacity' => 'nullable|numeric|min:0.1|max:1.0',
            'eq_color' => 'nullable|string',
            'slide_duration' => 'nullable|integer|min:2|max:15',
            'transition' => 'nullable|string',
            'transition_duration' => 'nullable|numeric|min:0.3|max:3',
            'effect' => 'nullable|string',
            'background_color' => 'nullable|string',
        ]);

        $templateSettings = [
            'slide_duration' => $validated['slide_duration'] ?? 5,
            'transition' => $validated['transition'] ?? 'crossfade',
            'transition_duration' => $validated['transition_duration'] ?? 1,
            'effect' => $validated['effect'] ?? 'ken_burns',
            'background_color' => $validated['background_color'] ?? '#000000',
        ];

        $eqSettings = null;
        if ($request->boolean('eq_enabled')) {
            $eqSettings = [
                'enabled' => true,
                'style' => $validated['eq_style'] ?? 'showcqt',
                'position' => $validated['eq_position'] ?? 'bottom',
                'height_percent' => $validated['eq_height_percent'] ?? 20,
                'opacity' => $validated['eq_opacity'] ?? 0.7,
                'color' => $validated['eq_color'] ?? '#00ff88',
            ];
        }

        $mediaPoolTags = null;
        if (! empty($validated['media_pool_tags'])) {
            $mediaPoolTags = array_map('trim', explode(',', $validated['media_pool_tags']));
            $mediaPoolTags = array_values(array_filter($mediaPoolTags));
        }

        $plan->update([
            'channel_id' => $validated['channel_id'],
            'name' => $validated['name'],
            'topic_prompt' => $validated['topic_prompt'],
            'music_prompt' => $validated['music_prompt'],
            'music_style' => $validated['music_style'],
            'music_duration' => $validated['music_duration'],
            'template' => $validated['template'],
            'template_settings' => $templateSettings,
            'eq_settings' => $eqSettings,
            'media_mode' => $validated['media_mode'],
            'privacy_status' => $validated['privacy_status'],
            'media_pool_tags' => $mediaPoolTags,
            'media_count' => $validated['media_count'],
            'schedule_frequency_hours' => $validated['schedule_frequency_hours'],
            'preferred_publish_hour' => $validated['preferred_publish_hour'],
            'preferred_publish_days' => $validated['preferred_publish_days'] ?? null,
            'max_queue_size' => $validated['max_queue_size'],
        ]);

        return redirect()->route('admin.metal-x.content-plans.index')
            ->with('success', "อัปเดตแผนเนื้อหา \"{$plan->name}\" สำเร็จ");
    }

    public function destroy(MetalXContentPlan $plan)
    {
        $name = $plan->name;
        $plan->delete();

        return redirect()->route('admin.metal-x.content-plans.index')
            ->with('success', "ลบแผนเนื้อหา \"{$name}\" สำเร็จ");
    }

    public function toggle(MetalXContentPlan $plan)
    {
        $plan->update(['is_enabled' => ! $plan->is_enabled]);

        $status = $plan->is_enabled ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return back()->with('success', "{$status}แผน \"{$plan->name}\" สำเร็จ");
    }

    public function generateNow(MetalXContentPlan $plan)
    {
        if (! $plan->is_enabled) {
            return back()->with('error', 'แผนนี้ปิดใช้งานอยู่');
        }

        try {
            $service = app(ContentPlanService::class);
            $project = $service->generateProject($plan);

            if ($project) {
                return back()->with('success', "สร้างโปรเจกต์ #{$project->id} จากแผน \"{$plan->name}\" สำเร็จ");
            }

            return back()->with('error', 'ไม่สามารถสร้างโปรเจกต์ได้ -- ตรวจสอบสื่อและเพลงในคลัง');
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
