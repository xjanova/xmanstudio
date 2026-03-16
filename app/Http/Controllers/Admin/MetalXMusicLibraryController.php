<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MetalXMusicLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MetalXMusicLibraryController extends Controller
{
    public function index(Request $request)
    {
        $query = MetalXMusicLibrary::query();

        if ($request->filled('style')) {
            $query->where('style', $request->style);
        }

        $tracks = $query->latest()->paginate(20);

        $stats = [
            'total' => MetalXMusicLibrary::count(),
            'active' => MetalXMusicLibrary::active()->count(),
            'total_duration' => MetalXMusicLibrary::active()->sum('duration_seconds'),
        ];

        $styles = MetalXMusicLibrary::STYLES;

        return view('admin.metal-x.music-library.index', compact('tracks', 'stats', 'styles'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:mp3,wav,ogg,m4a|max:51200',
            'style' => 'required|string|max:50',
            'tags' => 'nullable|string|max:500',
            'source' => 'required|string|in:suno,custom',
        ]);

        $tags = null;
        if ($request->filled('tags')) {
            $tags = array_map('trim', explode(',', $request->tags));
            $tags = array_values(array_filter($tags));
        }

        $uploaded = 0;
        foreach ($request->file('files') as $file) {
            $path = $file->store('metal-x/music-library', 'local');
            $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            $duration = $this->getAudioDuration(Storage::disk('local')->path($path));

            MetalXMusicLibrary::create([
                'title' => $title,
                'file_path' => $path,
                'style' => $request->style,
                'tags' => $tags,
                'duration_seconds' => $duration ?? 0,
                'source' => $request->source,
                'is_active' => true,
            ]);

            $uploaded++;
        }

        return back()->with('success', "อัปโหลด {$uploaded} เพลงสำเร็จ");
    }

    public function destroy(MetalXMusicLibrary $track)
    {
        if (Storage::disk('local')->exists($track->file_path)) {
            Storage::disk('local')->delete($track->file_path);
        }

        $track->delete();

        return back()->with('success', "ลบเพลง \"{$track->title}\" สำเร็จ");
    }

    protected function getAudioDuration(string $path): ?int
    {
        try {
            $ffprobe = \App\Models\Setting::getValue('ffprobe_binary', 'ffprobe');
            $result = shell_exec("{$ffprobe} -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($path) . ' 2>/dev/null');
            if ($result && is_numeric(trim($result))) {
                return (int) round((float) trim($result));
            }
        } catch (\Exception $e) {
            // Ignore
        }

        return null;
    }
}
