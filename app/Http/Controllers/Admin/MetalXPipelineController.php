<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMusicJob;
use App\Jobs\RenderVideoJob;
use App\Jobs\UploadVideoJob;
use App\Models\MetalXChannel;
use App\Models\MetalXVideoProject;
use App\Services\SunoMusicService;
use Illuminate\Support\Facades\Storage;

class MetalXPipelineController extends Controller
{
    /**
     * Pipeline Dashboard - แสดง visual pipeline ของทุกโปรเจกต์
     */
    public function index()
    {
        $projects = MetalXVideoProject::with(['channel', 'musicGeneration', 'video'])
            ->orderByRaw("CASE
                WHEN status IN ('generating_music','rendering','uploading') THEN 0
                WHEN status = 'failed' THEN 1
                WHEN status IN ('draft','music_ready','rendered') THEN 2
                ELSE 3
            END")
            ->orderByDesc('updated_at')
            ->paginate(20);

        $channels = MetalXChannel::active()->orderBy('name')->get();

        $stats = [
            'total' => MetalXVideoProject::count(),
            'active' => MetalXVideoProject::whereIn('status', ['generating_music', 'rendering', 'uploading'])->count(),
            'waiting' => MetalXVideoProject::whereIn('status', ['draft', 'music_ready', 'rendered'])->count(),
            'completed' => MetalXVideoProject::whereIn('status', ['uploaded', 'published'])->count(),
            'failed' => MetalXVideoProject::where('status', 'failed')->count(),
        ];

        $recentActivity = MetalXVideoProject::with('channel')
            ->whereIn('status', ['uploaded', 'published', 'failed'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('admin.metal-x.pipeline.index', compact('projects', 'channels', 'stats', 'recentActivity'));
    }

    /**
     * API: สถานะ real-time ของทุกโปรเจกต์ที่กำลังทำงาน
     */
    public function status()
    {
        $projects = MetalXVideoProject::with(['musicGeneration'])
            ->whereIn('status', ['generating_music', 'rendering', 'uploading', 'music_ready', 'rendered', 'failed'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title ?: "โปรเจกต์ #{$p->id}",
                'status' => $p->status,
                'status_label' => $p->status_label,
                'status_color' => $p->status_color,
                'error_message' => $p->error_message,
                'music_status' => $p->musicGeneration?->status,
                'music_audio_url' => $p->musicGeneration?->audio_url,
                'video_file' => $p->video_file_path ? true : false,
                'video_id' => $p->video_id,
                'updated_at' => $p->updated_at->diffForHumans(),
            ]);

        return response()->json(['projects' => $projects]);
    }

    /**
     * เริ่มกระบวนการสร้างเพลง
     */
    public function startMusic(MetalXVideoProject $project)
    {
        if (! in_array($project->status, ['draft', 'failed'])) {
            return response()->json(['success' => false, 'message' => 'สถานะไม่ถูกต้องสำหรับการสร้างเพลง'], 422);
        }

        GenerateMusicJob::dispatch($project);

        return response()->json(['success' => true, 'message' => 'เริ่มสร้างเพลงแล้ว']);
    }

    /**
     * เริ่มเรนเดอร์วิดีโอ
     */
    public function startRender(MetalXVideoProject $project)
    {
        if ($project->status !== 'music_ready') {
            return response()->json(['success' => false, 'message' => 'เพลงยังไม่พร้อม'], 422);
        }

        RenderVideoJob::dispatch($project);

        return response()->json(['success' => true, 'message' => 'เริ่มเรนเดอร์วิดีโอแล้ว']);
    }

    /**
     * เริ่มอัปโหลดไป YouTube
     */
    public function startUpload(MetalXVideoProject $project)
    {
        if ($project->status !== 'rendered') {
            return response()->json(['success' => false, 'message' => 'วิดีโอยังไม่เรนเดอร์เสร็จ'], 422);
        }

        if (! $project->channel) {
            return response()->json(['success' => false, 'message' => 'ยังไม่ได้เลือกช่อง YouTube'], 422);
        }

        UploadVideoJob::dispatch($project);

        return response()->json(['success' => true, 'message' => 'เริ่มอัปโหลดแล้ว']);
    }

    /**
     * เริ่ม Pipeline ทั้งหมด (music → render → upload)
     */
    public function startFull(MetalXVideoProject $project)
    {
        if (! in_array($project->status, ['draft', 'failed'])) {
            return response()->json(['success' => false, 'message' => 'สถานะไม่ถูกต้อง'], 422);
        }

        GenerateMusicJob::dispatch($project, autoUpload: true);

        return response()->json(['success' => true, 'message' => 'เริ่มกระบวนการทั้งหมดแล้ว (สร้างเพลง → เรนเดอร์ → อัปโหลด)']);
    }

    /**
     * Retry โปรเจกต์ที่ล้มเหลว
     */
    public function retry(MetalXVideoProject $project)
    {
        if ($project->status !== 'failed') {
            return response()->json(['success' => false, 'message' => 'โปรเจกต์ไม่ได้อยู่ในสถานะล้มเหลว'], 422);
        }

        $project->update(['error_message' => null]);

        // ตรวจสอบว่าควรเริ่มจากจุดไหน
        if ($project->musicGeneration && $project->musicGeneration->status === 'completed' && $project->video_file_path) {
            // เพลงพร้อม + วิดีโอเรนเดอร์แล้ว → อัปโหลด
            $project->update(['status' => 'rendered']);
            UploadVideoJob::dispatch($project);

            return response()->json(['success' => true, 'message' => 'เริ่มอัปโหลดใหม่']);
        }

        if ($project->musicGeneration && $project->musicGeneration->status === 'completed') {
            // เพลงพร้อม → เรนเดอร์
            $project->update(['status' => 'music_ready']);
            RenderVideoJob::dispatch($project);

            return response()->json(['success' => true, 'message' => 'เริ่มเรนเดอร์ใหม่']);
        }

        // เริ่มใหม่ทั้งหมด
        $project->update(['status' => 'draft']);
        GenerateMusicJob::dispatch($project, autoUpload: true);

        return response()->json(['success' => true, 'message' => 'เริ่มกระบวนการใหม่ทั้งหมด']);
    }

    /**
     * หยุด/ยกเลิกโปรเจกต์
     */
    public function cancel(MetalXVideoProject $project)
    {
        if (in_array($project->status, ['uploaded', 'published'])) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถยกเลิกโปรเจกต์ที่อัปโหลดแล้ว'], 422);
        }

        $project->update([
            'status' => 'draft',
            'error_message' => 'ยกเลิกโดยผู้ดูแลระบบ',
        ]);

        return response()->json(['success' => true, 'message' => 'ยกเลิกโปรเจกต์แล้ว']);
    }

    /**
     * เช็คสถานะเพลง Suno แบบ manual
     */
    public function checkMusic(MetalXVideoProject $project)
    {
        if (! $project->musicGeneration) {
            return response()->json(['success' => false, 'message' => 'ยังไม่ได้สร้างเพลง'], 422);
        }

        $suno = app(SunoMusicService::class);
        $suno->checkStatus($project->musicGeneration);

        $gen = $project->musicGeneration->fresh();

        return response()->json([
            'success' => true,
            'status' => $gen->status,
            'audio_url' => $gen->audio_url,
            'message' => "สถานะเพลง: {$gen->status}",
        ]);
    }

    /**
     * ลบโปรเจกต์
     */
    public function destroy(MetalXVideoProject $project)
    {
        if (in_array($project->status, ['generating_music', 'rendering', 'uploading'])) {
            return response()->json(['success' => false, 'message' => 'ไม่สามารถลบโปรเจกต์ที่กำลังทำงาน'], 422);
        }

        if ($project->images) {
            foreach ($project->images as $image) {
                Storage::disk('local')->delete($image);
            }
        }

        if ($project->video_file_path) {
            Storage::disk('local')->delete($project->video_file_path);
        }

        $title = $project->title ?: "โปรเจกต์ #{$project->id}";
        $project->delete();

        return response()->json(['success' => true, 'message' => "ลบ \"{$title}\" แล้ว"]);
    }
}
