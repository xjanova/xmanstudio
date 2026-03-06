<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDataProfile;
use App\Models\ProductWorkflow;
use Illuminate\Http\Request;

class TpingWorkflowController extends Controller
{
    private function getProduct(): Product
    {
        return Product::where('slug', 'tping')->firstOrFail();
    }

    public function index(Request $request)
    {
        $product = $this->getProduct();

        $query = ProductWorkflow::where('product_id', $product->id)
            ->with('user')
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $workflows = $query->paginate(30)->withQueryString();

        $stats = [
            'total' => ProductWorkflow::where('product_id', $product->id)->count(),
            'shared' => ProductWorkflow::where('product_id', $product->id)->whereNotNull('share_token')->count(),
            'users' => ProductWorkflow::where('product_id', $product->id)->distinct('user_id')->count('user_id'),
            'profiles' => ProductDataProfile::where('product_id', $product->id)->count(),
        ];

        return view('admin.tping.workflows.index', compact('workflows', 'stats'));
    }

    public function show(ProductWorkflow $workflow)
    {
        $workflow->load('user');
        $steps = json_decode($workflow->steps_json, true) ?? [];

        return view('admin.tping.workflows.show', compact('workflow', 'steps'));
    }

    public function destroy(ProductWorkflow $workflow)
    {
        $workflow->delete();

        return redirect()->route('admin.tping.workflows.index')
            ->with('success', 'ลบ Workflow สำเร็จ');
    }
}
