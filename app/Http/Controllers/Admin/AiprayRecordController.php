<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiprayAudioSample;
use App\Models\AiprayChant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiprayRecordController extends Controller
{
    public function index()
    {
        $chants = AiprayChant::active()->orderBy('sort_order')->get();

        return view('admin.aipray.record.index', compact('chants'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'audio' => 'required|string',
            'chant_id' => 'required|string',
            'line_index' => 'required|integer',
            'transcript' => 'nullable|string',
            'duration' => 'required|numeric',
        ]);

        $audioBytes = base64_decode($request->audio, true);
        if (! $audioBytes) {
            return back()->with('error', 'ข้อมูลเสียงไม่ถูกต้อง');
        }

        $filename = sprintf('%s_line%d_%s.wav', $request->chant_id, $request->line_index, Str::random(8));
        $path = "aipray_audio/{$filename}";

        \Storage::disk('public')->put($path, $audioBytes);

        AiprayAudioSample::create([
            'filename' => $filename,
            'original_name' => $filename,
            'file_path' => $path,
            'chant_id' => $request->chant_id,
            'line_index' => $request->line_index,
            'transcript' => $request->transcript,
            'duration' => $request->duration,
            'sample_rate' => 16000,
            'format' => 'wav',
            'file_size' => strlen($audioBytes),
            'status' => $request->transcript ? 'labeled' : 'unlabeled',
            'device_info' => 'web_admin',
        ]);

        return back()->with('success', 'บันทึกเสียงเรียบร้อย');
    }
}
