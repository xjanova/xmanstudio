<?php

namespace App\Http\Controllers;

use App\Models\ProductWorkflow;

class SharedWorkflowController extends Controller
{
    public function show(string $token)
    {
        $workflow = ProductWorkflow::where('share_token', $token)
            ->where('is_public', true)
            ->with('user:id,name')
            ->firstOrFail();

        $steps = json_decode($workflow->steps_json, true) ?? [];

        return view('shared.workflow', compact('workflow', 'steps'));
    }
}
