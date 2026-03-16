<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetalXMediaLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MetalXMediaLibraryController extends Controller
{
    public function index(Request $request)
    {
        $query = MetalXMediaLibrary::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }

        $media = $query->latest()->paginate(24);

        $stats = [
            'total' => MetalXMediaLibrary::count(),
            'images' => MetalXMediaLibrary::images()->count(),
            'video_clips' => MetalXMediaLibrary::videoClips()->count(),
            'active' => MetalXMediaLibrary::active()->count(),
        ];

        $allTags = MetalXMediaLibrary::whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return view('admin.metal-x.media-library.index', compact('media', 'stats', 'allTags'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,webp,mp4,webm,mov|max:102400',
            'tags' => 'nullable|string|max:500',
            'source' => 'required|string|in:freepik,custom,ai_generated',
        ]);

        $tags = null;
        if ($request->filled('tags')) {
            $tags = array_map('trim', explode(',', $request->tags));
            $tags = array_values(array_filter($tags));
        }

        $uploaded = 0;
        foreach ($request->file('files') as $file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $isVideo = in_array($extension, ['mp4', 'webm', 'mov']);
            $type = $isVideo ? 'video_clip' : 'image';

            $path = $file->store('metal-x/media-library', 'local');

            $duration = null;
            if ($isVideo) {
                // Try to get duration using ffprobe if available
                $duration = $this->getVideoDuration(Storage::disk('local')->path($path));
            }

            MetalXMediaLibrary::create([
                'type' => $type,
                'file_path' => $path,
                'filename' => $file->getClientOriginalName(),
                'tags' => $tags,
                'source' => $request->source,
                'file_size' => $file->getSize(),
                'duration_seconds' => $duration,
                'is_active' => true,
            ]);

            $uploaded++;
        }

        return back()->with('success', "อัปโหลด {$uploaded} ไฟล์สำเร็จ");
    }

    public function update(Request $request, MetalXMediaLibrary $media)
    {
        $validated = $request->validate([
            'tags' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $updateData = ['is_active' => $request->boolean('is_active', $media->is_active)];

        if ($request->has('tags')) {
            if ($request->filled('tags')) {
                $tags = array_map('trim', explode(',', $validated['tags']));
                $updateData['tags'] = array_values(array_filter($tags));
            } else {
                $updateData['tags'] = [];
            }
        }

        $media->update($updateData);

        return back()->with('success', 'อัปเดตสำเร็จ');
    }

    public function destroy(MetalXMediaLibrary $media)
    {
        if (Storage::disk('local')->exists($media->file_path)) {
            Storage::disk('local')->delete($media->file_path);
        }

        $media->delete();

        return back()->with('success', 'ลบสำเร็จ');
    }

    public function bulkTag(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:metal_x_media_library,id',
            'tags' => 'required|string|max:500',
        ]);

        $tags = array_map('trim', explode(',', $request->tags));
        $tags = array_values(array_filter($tags));

        MetalXMediaLibrary::whereIn('id', $request->ids)->each(function ($item) use ($tags) {
            $existing = $item->tags ?? [];
            $merged = array_values(array_unique(array_merge($existing, $tags)));
            $item->update(['tags' => $merged]);
        });

        return back()->with('success', 'เพิ่มแท็กสำเร็จ');
    }

    protected function getVideoDuration(string $path): ?int
    {
        try {
            $ffprobe = \App\Models\Setting::getValue('ffprobe_binary', 'ffprobe');
            $result = shell_exec("{$ffprobe} -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($path) . ' 2>/dev/null');
            if ($result && is_numeric(trim($result))) {
                return (int) round((float) trim($result));
            }
        } catch (\Exception $e) {
            // Ignore - duration is optional
        }

        return null;
    }
}
