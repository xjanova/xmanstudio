<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiprayAiModel;
use App\Models\AiprayEvaluation;
use App\Services\AiprayMlServiceClient;
use Illuminate\Http\Request;

class AiprayEvaluationController extends Controller
{
    public function index()
    {
        $evaluations = AiprayEvaluation::with('aiModel')->latest()->paginate(30);
        $models = AiprayAiModel::whereIn('status', ['active', 'deployed'])->get();

        return view('admin.aipray.evaluate.index', compact('evaluations', 'models'));
    }

    public function evaluate(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:wav,mp3,ogg,m4a|max:10240',
            'reference_text' => 'nullable|string',
            'model_id' => 'nullable|integer',
        ]);

        $ml = new AiprayMlServiceClient;
        $modelId = $request->model_id ? "aipray-{$request->model_id}" : 'default';

        if ($request->filled('reference_text')) {
            $result = $ml->evaluate($request->file('audio'), $request->reference_text, $modelId);
        } else {
            $result = $ml->transcribeUpload($request->file('audio'), $modelId);
        }

        if (! ($result['success'] ?? false)) {
            return back()->with('error', 'ประเมินล้มเหลว: ' . ($result['error'] ?? ''));
        }

        $data = $result['data'];

        if ($request->model_id) {
            AiprayEvaluation::create([
                'ai_model_id' => $request->model_id,
                'eval_type' => 'live',
                'recognized_text' => $data['recognized_text'] ?? $data['text'] ?? '',
                'reference_text' => $request->reference_text,
                'accuracy' => $data['accuracy'] ?? null,
                'wer' => $data['wer'] ?? null,
                'cer' => $data['cer'] ?? null,
                'latency_ms' => $data['latency_ms'] ?? null,
            ]);
        }

        return back()->with('success', 'ผลลัพธ์: ' . ($data['text'] ?? $data['recognized_text'] ?? 'N/A'));
    }

    public function liveTranscribe(Request $request)
    {
        $request->validate([
            'audio' => 'required|string',
            'model_id' => 'nullable|string',
        ]);

        $audioBytes = base64_decode($request->audio, true);
        if (! $audioBytes) {
            return response()->json(['error' => 'Invalid audio'], 422);
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'aipray_') . '.wav';
        file_put_contents($tmpFile, $audioBytes);

        $ml = new AiprayMlServiceClient;
        $result = $ml->transcribeFile($tmpFile, $request->input('model_id', 'default'));

        @unlink($tmpFile);

        return response()->json($result);
    }
}
