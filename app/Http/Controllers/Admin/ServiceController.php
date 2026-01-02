<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    /**
     * Display a listing of services
     */
    public function index()
    {
        $services = Service::ordered()->paginate(20);

        return view('admin.services.index', compact('services'));
    }

    /**
     * Show the form for creating a new service
     */
    public function create()
    {
        return view('admin.services.create');
    }

    /**
     * Store a newly created service
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:services,slug',
            'description' => 'required|string',
            'description_th' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:255',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'features_th' => 'nullable|array',
            'features_th.*' => 'string',
            'starting_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Filter empty features
        if (isset($validated['features'])) {
            $validated['features'] = array_values(array_filter($validated['features']));
        }
        if (isset($validated['features_th'])) {
            $validated['features_th'] = array_values(array_filter($validated['features_th']));
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_featured'] = $request->boolean('is_featured', false);

        $service = Service::create($validated);

        return redirect()
            ->route('admin.services.index')
            ->with('success', "บริการ '{$service->name}' ถูกสร้างเรียบร้อยแล้ว");
    }

    /**
     * Show the form for editing a service
     */
    public function edit(Service $service)
    {
        return view('admin.services.edit', compact('service'));
    }

    /**
     * Update the specified service
     */
    public function update(Request $request, Service $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_th' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:services,slug,'.$service->id,
            'description' => 'required|string',
            'description_th' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'image' => 'nullable|string|max:255',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'features_th' => 'nullable|array',
            'features_th.*' => 'string',
            'starting_price' => 'nullable|numeric|min:0',
            'price_unit' => 'nullable|string|max:50',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Filter empty features
        if (isset($validated['features'])) {
            $validated['features'] = array_values(array_filter($validated['features']));
        }
        if (isset($validated['features_th'])) {
            $validated['features_th'] = array_values(array_filter($validated['features_th']));
        }

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_featured'] = $request->boolean('is_featured', false);

        $service->update($validated);

        return redirect()
            ->route('admin.services.index')
            ->with('success', "บริการ '{$service->name}' ถูกอัพเดทเรียบร้อยแล้ว");
    }

    /**
     * Toggle service status
     */
    public function toggle(Service $service)
    {
        $service->update(['is_active' => ! $service->is_active]);

        $status = $service->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return redirect()
            ->back()
            ->with('success', "บริการ '{$service->name}' ถูก{$status}แล้ว");
    }

    /**
     * Remove the specified service
     */
    public function destroy(Service $service)
    {
        $name = $service->name;
        $service->delete();

        return redirect()
            ->route('admin.services.index')
            ->with('success', "บริการ '{$name}' ถูกลบเรียบร้อยแล้ว");
    }

    /**
     * Update service order
     */
    public function updateOrder(Request $request)
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:services,id',
            'orders.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->orders as $item) {
            Service::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json(['success' => true]);
    }
}
