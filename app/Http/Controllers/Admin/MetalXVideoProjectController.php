<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMusicJob;
use App\Jobs\GenerateVideoMetadataJob;
use App\Jobs\RenderVideoJob;
use App\Jobs\UploadVideoJob;
use App\Models\MetalXChannel;
use App\Models\MetalXVideoProject;
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

        return view('admin.metal-x.projects.create', compact('channels', 'templates'));
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
            'slide_duration' => 'nullable|numeric|min:2|max:30',
            'transition' => 'nullable|string|in:crossfade,fade,concat',
            'effect' => 'nullable|string|in:ken_burns,slide_left,zoom,none',
            'background_color' => 'nullable|string|max:7',
            'scheduled_at' => 'nullable|date|after:now',
            'images' => 'required|array|min:1|max:50',
            'images.*' => 'image|max:10240',
        ]);

        // Upload images
        $imagePaths = [];
        foreach ($request->file('images') as $image) {
            $imagePaths[] = $image->store('metal-x/project-images', 'local');
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
                'music_duration' => 60,
                'slide_duration' => $validated['slide_duration'] ?? 5,
                'transition' => $validated['transition'] ?? 'crossfade',
                'transition_duration' => 1,
                'effect' => $validated['effect'] ?? 'ken_burns',
                'background_color' => $validated['background_color'] ?? '#000000',
            ],
            'images' => $imagePaths,
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

        return view('admin.metal-x.projects.show', compact('project'));
    }

    public function edit(MetalXVideoProject $project)
    {
        if (! in_array($project->status, ['draft', 'failed'])) {
            return back()->with('error', 'ไม่สามารถแก้ไขโปรเจกต์ที่กำลังดำเนินการ');
        }

        $channels = MetalXChannel::active()->orderBy('name')->get();
        $templates = VideoRenderService::getTemplates();

        return view('admin.metal-x.projects.edit', compact('project', 'channels', 'templates'));
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
            'slide_duration' => 'nullable|numeric|min:2|max:30',
            'transition' => 'nullable|string|in:crossfade,fade,concat',
            'effect' => 'nullable|string|in:ken_burns,slide_left,zoom,none',
            'background_color' => 'nullable|string|max:7',
            'scheduled_at' => 'nullable|date|after:now',
            'new_images' => 'nullable|array|max:50',
            'new_images.*' => 'image|max:10240',
        ]);

        // Handle new images
        $imagePaths = $project->images ?? [];
        if ($request->hasFile('new_images')) {
            foreach ($request->file('new_images') as $image) {
                $imagePaths[] = $image->store('metal-x/project-images', 'local');
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
                'music_duration' => 60,
                'slide_duration' => $validated['slide_duration'] ?? 5,
                'transition' => $validated['transition'] ?? 'crossfade',
                'transition_duration' => 1,
                'effect' => $validated['effect'] ?? 'ken_burns',
                'background_color' => $validated['background_color'] ?? '#000000',
            ],
            'images' => $imagePaths,
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

        // Start the full pipeline: generate music → render → upload
        GenerateMusicJob::dispatch($project);

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
}
