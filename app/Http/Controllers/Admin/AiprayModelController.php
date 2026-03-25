<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiprayAiModel;
use App\Services\AiprayMlServiceClient;
use Illuminate\Http\Request;

class AiprayModelController extends Controller
{
    public function index()
    {
        try {
            $models = AiprayAiModel::with('trainingJob')->latest()->paginate(20);
        } catch (\Exception $e) {
            $models = collect();
        }

        return view('admin.aipray.models.index', compact('models'));
    }

    public function show(AiprayAiModel $model)
    {
        $model->load(['trainingJob', 'evaluations']);

        return view('admin.aipray.models.show', compact('model'));
    }

    public function update(Request $request, AiprayAiModel $model)
    {
        $model->update($request->validate([
            'name' => 'required|string|max:255',
            'version' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,archived,deploying,deployed',
        ]));

        return back()->with('success', 'อัพเดทโมเดลแล้ว');
    }

    public function deploy(AiprayAiModel $model)
    {
        // Undeploy all other models first
        AiprayAiModel::where('status', 'deployed')->update(['status' => 'active']);

        $ml = new AiprayMlServiceClient;
        $result = $ml->loadModel($model->file_path, "aipray-{$model->id}");

        if ($result['success'] ?? false) {
            $model->update(['status' => 'deployed']);

            return back()->with('success', 'Deploy โมเดลแล้ว');
        }

        return back()->with('error', 'Deploy ล้มเหลว: ' . ($result['error'] ?? ''));
    }

    public function exportOnnx(AiprayAiModel $model)
    {
        $ml = new AiprayMlServiceClient;
        $result = $ml->exportOnnx($model->file_path);

        if ($result['success'] ?? false) {
            $model->update(['onnx_file_path' => $result['data']['path'] ?? null]);

            return back()->with('success', 'Export ONNX สำเร็จ');
        }

        return back()->with('error', 'Export ONNX ล้มเหลว: ' . ($result['error'] ?? ''));
    }

    public function destroy(AiprayAiModel $model)
    {
        $model->delete();

        return redirect()->route('admin.aipray.models.index')->with('success', 'ลบโมเดลแล้ว');
    }
}
