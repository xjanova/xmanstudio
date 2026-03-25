<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiprayAudioSample;
use Illuminate\Http\Request;

class AiprayDatasetController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = AiprayAudioSample::latest();

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }
            if ($request->filled('search')) {
                $query->where('transcript', 'like', '%' . $request->search . '%');
            }

            $samples = $query->paginate(30);

            $stats = [
                'total' => AiprayAudioSample::count(),
                'unlabeled' => AiprayAudioSample::where('status', 'unlabeled')->count(),
                'labeled' => AiprayAudioSample::where('status', 'labeled')->count(),
                'verified' => AiprayAudioSample::where('status', 'verified')->count(),
            ];
        } catch (\Exception $e) {
            $samples = collect();
            $stats = ['total' => 0, 'unlabeled' => 0, 'labeled' => 0, 'verified' => 0];
        }

        return view('admin.aipray.dataset.index', compact('samples', 'stats'));
    }

    public function show(AiprayAudioSample $sample)
    {
        return view('admin.aipray.dataset.show', compact('sample'));
    }

    public function update(Request $request, AiprayAudioSample $sample)
    {
        $validated = $request->validate([
            'transcript' => 'nullable|string',
            'status' => 'required|in:unlabeled,labeled,verified,rejected',
            'category' => 'nullable|string',
        ]);

        $sample->update($validated);

        return redirect()->route('admin.aipray.dataset.show', $sample)
            ->with('success', 'อัพเดทตัวอย่างเสียงแล้ว');
    }

    public function destroy(AiprayAudioSample $sample)
    {
        \Storage::disk('public')->delete($sample->file_path);
        $sample->delete();

        return redirect()->route('admin.aipray.dataset.index')
            ->with('success', 'ลบตัวอย่างเสียงแล้ว');
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:verify,reject,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $samples = AiprayAudioSample::whereIn('id', $request->ids);

        match ($request->action) {
            'verify' => $samples->update(['status' => 'verified']),
            'reject' => $samples->update(['status' => 'rejected']),
            'delete' => $samples->get()->each(function ($s) {
                \Storage::disk('public')->delete($s->file_path);
                $s->delete();
            }),
        };

        return back()->with('success', 'ดำเนินการเสร็จสิ้น');
    }
}
