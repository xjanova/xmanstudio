<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMusicJob;
use App\Jobs\GenerateVideoMetadataJob;
use App\Jobs\RenderVideoJob;
use App\Jobs\UploadVideoJob;
use App\Models\MetalXChannel;
use App\Models\MetalXMusicGeneration;
use App\Models\MetalXVideoProject;
use App\Models\Setting;
use App\Services\VideoRenderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MetalXVideoProjectController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|string|in:' . implode(',', array_keys(MetalXVideoProject::STATUSES)),
            'channel_id' => 'nullable|integer|exists:metal_x_channels,id',
        ]);

        $projects = MetalXVideoProject::with(['channel', 'musicGeneration', 'video'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->channel_id, fn ($q, $c) => $q->where('channel_id', $c))
            ->orderByDesc('created_at')
            ->paginate(20);

        $channels = MetalXChannel::active()->orderBy('name')->get();
        $stats = [
            'total' => MetalXVideoProject::count(),
            'drafts' => MetalXVideoProject::where('status', 'draft')->count(),
            'rendering' => MetalXVideoProject::whereIn('status', ['generating_music', 'rendering'])->count(),
            'uploaded' => MetalXVideoProject::whereIn('status', ['uploaded', 'published'])->count(),
            'failed' => MetalXVideoProject::where('status', 'failed')->count(),
        ];

        return view('admin.metal-x.projects.index', compact('projects', 'channels', 'stats'));
    }

    public function create()
    {
        $channels = MetalXChannel::active()->orderBy('name')->get();
        $templates = VideoRenderService::getTemplates();
        $sunoMode = Setting::getValue('suno_mode', 'api');
        $sunoCreateUrl = Setting::getValue('suno_create_url', 'https://suno.com/create');
        $freepikCreateUrl = Setting::getValue('freepik_create_url', 'https://www.freepik.com/pikaso/ai-video-generator');

        return view('admin.metal-x.projects.create', compact('channels', 'templates', 'sunoMode', 'sunoCreateUrl', 'freepikCreateUrl'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'channel_id' => 'required|exists:metal_x_channels,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'privacy_status' => 'required|in:public,private,unlisted',
            'template' => 'required|string|in:' . implode(',', array_keys(MetalXVideoProject::TEMPLATES)),
            'music_prompt' => 'required|string|max:1000',
            'music_style' => 'nullable|string|max:200',
            'music_model' => 'nullable|string|in:V4,V4_5,V4_5PLUS,V4_5ALL,V5',
            'slide_duration' => 'nullable|numeric|min:2|max:30',
            'transition' => 'nullable|string|in:crossfade,fade,concat',
            'effect' => 'nullable|string|in:ken_burns,slide_left,zoom,none',
            'background_color' => 'nullable|string|max:7',
            'scheduled_at' => 'nullable|date|after:now',
            'media_mode' => 'nullable|string|in:images,video_clips,mixed',
            'images' => 'nullable|array|max:50',
            'images.*' => 'image|max:10240',
            'video_clips' => 'nullable|array|max:20',
            'video_clips.*' => 'file|mimes:mp4,webm,mov|max:102400', // 100MB
            'eq_enabled' => 'nullable|boolean',
            'eq_style' => 'nullable|string|in:showcqt,showwaves,showfreqs,bars',
            'eq_position' => 'nullable|string|in:top,center,bottom',
            'eq_height_percent' => 'nullable|integer|min:10|max:50',
            'eq_opacity' => 'nullable|numeric|min:0.1|max:1.0',
            'eq_color' => 'nullable|string|max:7',
        ]);

        // At least images or video clips must be provided
        if (! $request->hasFile('images') && ! $request->hasFile('video_clips')) {
            return back()->withErrors(['images' => 'กรุณาอัปโหลดรูปภาพหรือคลิปวิดีโออย่างน้อย 1 รายการ'])->withInput();
        }

        // Upload images
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('metal-x/project-images', 'local');
            }
        }

        // Upload video clips
        $clipPaths = [];
        if ($request->hasFile('video_clips')) {
            foreach ($request->file('video_clips') as $clip) {
                $clipPaths[] = $clip->store('metal-x/project-clips', 'local');
            }
        }

        $project = MetalXVideoProject::create([
            'channel_id' => $validated['channel_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'privacy_status' => $validated['privacy_status'],
            'template' => $validated['template'],
            'template_settings' => [
                'music_prompt' => $validated['music_prompt'],
                'music_style' => $validated['music_style'] ?? '',
                'music_model' => $validated['music_model'] ?? 'V4',
                'music_duration' => 60,
                'slide_duration' => $validated['slide_duration'] ?? 5,
                'transition' => $validated['transition'] ?? 'crossfade',
                'transition_duration' => 1,
                'effect' => $validated['effect'] ?? 'ken_burns',
                'background_color' => $validated['background_color'] ?? '#000000',
            ],
            'images' => $imagePaths,
            'media_mode' => $validated['media_mode'] ?? 'images',
            'video_clips' => $clipPaths ?: null,
            'eq_settings' => $request->input('eq_enabled') ? [
                'enabled' => true,
                'style' => $validated['eq_style'] ?? 'showcqt',
                'position' => $validated['eq_position'] ?? 'bottom',
                'height_percent' => (int) ($validated['eq_height_percent'] ?? 20),
                'opacity' => (float) ($validated['eq_opacity'] ?? 0.6),
                'color' => $validated['eq_color'] ?? '#ff00ff',
            ] : null,
            'status' => 'draft',
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.metal-x.projects.show', $project)
            ->with('success', 'สร้างโปรเจกต์สำเร็จ');
    }

    public function show(MetalXVideoProject $project)
    {
        $project->load(['channel', 'musicGeneration', 'video', 'createdBy']);
        $sunoMode = Setting::getValue('suno_mode', 'api');
        $sunoCreateUrl = Setting::getValue('suno_create_url', 'https://suno.com/create');
        $freepikCreateUrl = Setting::getValue('freepik_create_url', 'https://www.freepik.com/pikaso/ai-video-generator');

        return view('admin.metal-x.projects.show', compact('project', 'sunoMode', 'sunoCreateUrl', 'freepikCreateUrl'));
    }

    public function edit(MetalXVideoProject $project)
    {
        if (! in_array($project->status, ['draft', 'failed'])) {
            return back()->with('error', 'ไม่สามารถแก้ไขโปรเจกต์ที่กำลังดำเนินการ');
        }

        $channels = MetalXChannel::active()->orderBy('name')->get();
        $templates = VideoRenderService::getTemplates();
        $sunoMode = Setting::getValue('suno_mode', 'api');
        $sunoCreateUrl = Setting::getValue('suno_create_url', 'https://suno.com/create');

        return view('admin.metal-x.projects.edit', compact('project', 'channels', 'templates', 'sunoMode', 'sunoCreateUrl'));
    }

    public function update(Request $request, MetalXVideoProject $project)
    {
        if (! in_array($project->status, ['draft', 'failed'])) {
            return back()->with('error', 'ไม่สามารถแก้ไขโปรเจกต์ที่กำลังดำเนินการ');
        }

        $validated = $request->validate([
            'channel_id' => 'required|exists:metal_x_channels,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'privacy_status' => 'required|in:public,private,unlisted',
            'template' => 'required|string|in:' . implode(',', array_keys(MetalXVideoProject::TEMPLATES)),
            'music_prompt' => 'required|string|max:1000',
            'music_style' => 'nullable|string|max:200',
            'music_model' => 'nullable|string|in:V4,V4_5,V4_5PLUS,V4_5ALL,V5',
            'slide_duration' => 'nullable|numeric|min:2|max:30',
            'transition' => 'nullable|string|in:crossfade,fade,concat',
            'effect' => 'nullable|string|in:ken_burns,slide_left,zoom,none',
            'background_color' => 'nullable|string|max:7',
            'scheduled_at' => 'nullable|date|after:now',
            'new_images' => 'nullable|array|max:50',
            'new_images.*' => 'image|max:10240',
            'media_mode' => 'nullable|string|in:images,video_clips,mixed',
            'video_clips' => 'nullable|array|max:20',
            'video_clips.*' => 'file|mimes:mp4,webm,mov|max:102400', // 100MB
            'eq_enabled' => 'nullable|boolean',
            'eq_style' => 'nullable|string|in:showcqt,showwaves,showfreqs,bars',
            'eq_position' => 'nullable|string|in:top,center,bottom',
            'eq_height_percent' => 'nullable|integer|min:10|max:50',
            'eq_opacity' => 'nullable|numeric|min:0.1|max:1.0',
            'eq_color' => 'nullable|string|max:7',
        ]);

        // Handle new images
        $imagePaths = $project->images ?? [];
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $image) {
                $imagePaths[] = $image->store('metal-x/project-images', 'local');
            }
        }

        // Handle video clips
        $clipPaths = $project->video_clips ?? [];
        if ($request->hasFile('video_clips')) {
            foreach ($request->file('video_clips') as $clip) {
                $clipPaths[] = $clip->store('metal-x/project-clips', 'local');
            }
        }

        $project->update([
            'channel_id' => $validated['channel_id'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'privacy_status' => $validated['privacy_status'],
            'template' => $validated['template'],
            'template_settings' => [
                'music_prompt' => $validated['music_prompt'],
                'music_style' => $validated['music_style'] ?? '',
                'music_model' => $validated['music_model'] ?? 'V4',
                'music_duration' => 60,
                'slide_duration' => $validated['slide_duration'] ?? 5,
                'transition' => $validated['transition'] ?? 'crossfade',
                'transition_duration' => 1,
                'effect' => $validated['effect'] ?? 'ken_burns',
                'background_color' => $validated['background_color'] ?? '#000000',
            ],
            'images' => $imagePaths,
            'media_mode' => $validated['media_mode'] ?? 'images',
            'video_clips' => $clipPaths ?: null,
            'eq_settings' => $request->input('eq_enabled') ? [
                'enabled' => true,
                'style' => $validated['eq_style'] ?? 'showcqt',
                'position' => $validated['eq_position'] ?? 'bottom',
                'height_percent' => (int) ($validated['eq_height_percent'] ?? 20),
                'opacity' => (float) ($validated['eq_opacity'] ?? 0.6),
                'color' => $validated['eq_color'] ?? '#ff00ff',
            ] : null,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'status' => 'draft',
            'error_message' => null,
        ]);

        return redirect()->route('admin.metal-x.projects.show', $project)
            ->with('success', 'อัปเดตโปรเจกต์สำเร็จ');
    }

    public function destroy(MetalXVideoProject $project)
    {
        // Cleanup files
        if ($project->images) {
            foreach ($project->images as $image) {
                Storage::disk('local')->delete($image);
            }
        }

        if ($project->video_clips) {
            foreach ($project->video_clips as $clip) {
                Storage::disk('local')->delete($clip);
            }
        }

        if ($project->video_file_path) {
            Storage::disk('local')->delete($project->video_file_path);
        }

        $project->delete();

        return redirect()->route('admin.metal-x.projects.index')
            ->with('success', 'ลบโปรเจกต์สำเร็จ');
    }

    public function generateMusic(MetalXVideoProject $project)
    {
        if (! in_array($project->status, ['draft', 'failed', 'music_ready'])) {
            return response()->json(['success' => false, 'message' => 'สถานะไม่ถูกต้อง'], 422);
        }

        GenerateMusicJob::dispatch($project);

        return response()->json(['success' => true, 'message' => 'เริ่มสร้างเพลงแล้ว']);
    }

    public function generateMetadata(MetalXVideoProject $project)
    {
        GenerateVideoMetadataJob::dispatch($project);

        return response()->json(['success' => true, 'message' => 'กำลังสร้างข้อมูลด้วย AI']);
    }

    public function renderVideo(MetalXVideoProject $project)
    {
        if ($project->status !== 'music_ready') {
            return response()->json(['success' => false, 'message' => 'เพลงยังไม่พร้อม'], 422);
        }

        RenderVideoJob::dispatch($project);

        return response()->json(['success' => true, 'message' => 'เริ่มเรนเดอร์วิดีโอแล้ว']);
    }

    public function uploadVideo(MetalXVideoProject $project)
    {
        if ($project->status !== 'rendered') {
            return response()->json(['success' => false, 'message' => 'วิดีโอยังไม่เรนเดอร์เสร็จ'], 422);
        }

        if (! $project->channel) {
            return response()->json(['success' => false, 'message' => 'ยังไม่ได้เลือกช่อง'], 422);
        }

        UploadVideoJob::dispatch($project);

        return response()->json(['success' => true, 'message' => 'เริ่มอัปโหลดไป YouTube แล้ว']);
    }

    public function publish(MetalXVideoProject $project)
    {
        if (! in_array($project->status, ['draft', 'failed'])) {
            return response()->json(['success' => false, 'message' => 'สถานะไม่ถูกต้อง'], 422);
        }

        $sunoMode = Setting::getValue('suno_mode', 'api');

        // Onsite mode — user must upload audio first
        if ($sunoMode === 'onsite') {
            return response()->json([
                'success' => false,
                'message' => 'โหมด Onsite — กรุณาสร้างเพลงที่ suno.com แล้วอัปโหลดไฟล์ MP3 ก่อน',
            ], 422);
        }

        // Auto-generate AI metadata if no title set
        if (empty($project->title) && ! $project->ai_metadata_generated) {
            GenerateVideoMetadataJob::dispatch($project);
        }

        // Start the full pipeline: generate music → render → upload (auto)
        GenerateMusicJob::dispatch($project, autoUpload: true);

        return response()->json(['success' => true, 'message' => 'เริ่มกระบวนการสร้างและเผยแพร่แล้ว']);
    }

    public function updateTemplate(Request $request, MetalXVideoProject $project)
    {
        $validated = $request->validate([
            'template_settings' => 'required|array',
        ]);

        $project->update(['template_settings' => $validated['template_settings']]);

        return response()->json(['success' => true]);
    }

    /**
     * Upload audio file manually (Onsite mode).
     */
    public function uploadAudio(Request $request, MetalXVideoProject $project)
    {
        $request->validate([
            'audio_file' => 'required|file|mimes:mp3,wav,ogg,m4a,aac|max:51200', // max 50MB
            'audio_url' => 'nullable|url|max:1000',
        ]);

        if (! in_array($project->status, ['draft', 'failed', 'generating_music'])) {
            return response()->json(['success' => false, 'message' => 'สถานะโปรเจกต์ไม่อนุญาตให้อัปโหลดเพลง'], 422);
        }

        $audioFile = $request->file('audio_file');
        $path = $audioFile->store('metal-x/music', 'local');

        // Create or update music generation record
        $generation = $project->musicGeneration;
        if (! $generation) {
            $generation = MetalXMusicGeneration::create([
                'prompt' => $project->getTemplateSetting('music_prompt', 'manual upload'),
                'style' => $project->getTemplateSetting('music_style', ''),
                'duration_seconds' => 0,
                'status' => 'completed',
                'audio_path' => $path,
                'audio_url' => $request->input('audio_url'),
                'metadata' => ['source' => 'onsite_upload', 'original_name' => $audioFile->getClientOriginalName()],
            ]);

            $project->update(['music_generation_id' => $generation->id]);
        } else {
            // Delete old audio file if exists
            if ($generation->audio_path) {
                Storage::disk('local')->delete($generation->audio_path);
            }

            $generation->update([
                'status' => 'completed',
                'audio_path' => $path,
                'audio_url' => $request->input('audio_url'),
                'metadata' => ['source' => 'onsite_upload', 'original_name' => $audioFile->getClientOriginalName()],
            ]);
        }

        $project->update(['status' => 'music_ready']);

        return response()->json([
            'success' => true,
            'message' => 'อัปโหลดเพลงสำเร็จ! พร้อมเรนเดอร์วิดีโอ',
            'audio_path' => $path,
        ]);
    }

    /**
     * Upload video clips (Freepik AI clips).
     */
    public function uploadVideoClips(Request $request, MetalXVideoProject $project)
    {
        $request->validate([
            'video_clips' => 'required|array|min:1|max:20',
            'video_clips.*' => 'file|mimes:mp4,webm,mov|max:102400',
        ]);

        if (! in_array($project->status, ['draft', 'failed', 'music_ready'])) {
            return response()->json(['success' => false, 'message' => 'สถานะไม่อนุญาตให้อัปโหลด'], 422);
        }

        $clipPaths = $project->video_clips ?? [];
        foreach ($request->file('video_clips') as $clip) {
            $clipPaths[] = $clip->store('metal-x/project-clips', 'local');
        }

        $project->update([
            'video_clips' => $clipPaths,
            'media_mode' => count($project->images ?? []) > 0 ? 'mixed' : 'video_clips',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'อัปโหลดคลิปวิดีโอสำเร็จ! (' . count($clipPaths) . ' คลิป)',
        ]);
    }
}
