<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductDataProfile;
use App\Models\ProductWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TpingWorkflowController extends Controller
{
    private function getProduct(): Product
    {
        return Product::where('slug', 'tping')->firstOrFail();
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $product = $this->getProduct();

        $query = ProductWorkflow::byUser($user->id)
            ->where('product_id', $product->id)
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('app')) {
            $query->where('target_app_package', $request->app);
        }

        $workflows = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => ProductWorkflow::byUser($user->id)->where('product_id', $product->id)->count(),
            'shared' => ProductWorkflow::byUser($user->id)->where('product_id', $product->id)->whereNotNull('share_token')->count(),
            'profiles' => ProductDataProfile::byUser($user->id)->where('product_id', $product->id)->count(),
        ];

        $apps = ProductWorkflow::byUser($user->id)
            ->where('product_id', $product->id)
            ->where('target_app_package', '!=', '')
            ->distinct()
            ->pluck('target_app_package', 'target_app_name')
            ->toArray();

        return view('customer.tping.workflows.index', compact('workflows', 'stats', 'apps'));
    }

    public function show(ProductWorkflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            abort(403);
        }

        $steps = json_decode($workflow->steps_json, true) ?? [];

        return view('customer.tping.workflows.show', compact('workflow', 'steps'));
    }

    public function edit(ProductWorkflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            abort(403);
        }

        return view('customer.tping.workflows.edit', compact('workflow'));
    }

    public function update(Request $request, ProductWorkflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_app_name' => 'nullable|string|max:255',
            'target_app_package' => 'nullable|string|max:255',
        ]);

        $workflow->update($validated);

        return redirect()->route('customer.tping.workflows.show', $workflow)
            ->with('success', 'อัปเดต Workflow สำเร็จ');
    }

    public function destroy(ProductWorkflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            abort(403);
        }

        $workflow->delete();

        return redirect()->route('customer.tping.workflows.index')
            ->with('success', 'ลบ Workflow สำเร็จ');
    }

    public function share(ProductWorkflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $workflow->share_token) {
            $workflow->update([
                'share_token' => Str::random(64),
                'is_public' => true,
                'shared_at' => now(),
            ]);
        }

        return back()->with('success', 'สร้างลิงก์แชร์สำเร็จ');
    }

    public function unshare(ProductWorkflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            abort(403);
        }

        $workflow->update([
            'share_token' => null,
            'is_public' => false,
            'shared_at' => null,
        ]);

        return back()->with('success', 'ยกเลิกการแชร์สำเร็จ');
    }
}
