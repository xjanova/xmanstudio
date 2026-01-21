<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    /**
     * Display a listing of banners.
     */
    public function index()
    {
        $banners = Banner::orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.banners.index', compact('banners'));
    }

    /**
     * Show the form for creating a new banner.
     */
    public function create()
    {
        return view('admin.banners.create');
    }

    /**
     * Store a newly created banner.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'crop_data' => 'nullable|json',
            'display_width' => 'nullable|integer|min:100|max:5000',
            'display_height' => 'nullable|integer|min:100|max:5000',
            'link_url' => 'nullable|url|max:500',
            'position' => 'required|string',
            'pages' => 'required|array',
            'priority' => 'nullable|integer|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
        ]);

        // Upload image
        $imagePath = $request->file('image')->store('banners', 'public');

        Banner::create([
            'title' => $request->input('title'),
            'image' => $imagePath,
            'crop_data' => $request->input('crop_data') ? json_decode($request->input('crop_data'), true) : null,
            'display_width' => $request->input('display_width'),
            'display_height' => $request->input('display_height'),
            'link_url' => $request->input('link_url'),
            'target_blank' => $request->has('target_blank'),
            'enabled' => $request->has('enabled'),
            'position' => $request->input('position'),
            'pages' => $request->input('pages'),
            'priority' => $request->input('priority', 0),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'description' => $request->input('description'),
        ]);

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner created successfully!');
    }

    /**
     * Show the form for editing a banner.
     */
    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    /**
     * Update the specified banner.
     */
    public function update(Request $request, Banner $banner)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'crop_data' => 'nullable|json',
            'display_width' => 'nullable|integer|min:100|max:5000',
            'display_height' => 'nullable|integer|min:100|max:5000',
            'link_url' => 'nullable|url|max:500',
            'position' => 'required|string',
            'pages' => 'required|array',
            'priority' => 'nullable|integer|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
        ]);

        $data = [
            'title' => $request->input('title'),
            'link_url' => $request->input('link_url'),
            'target_blank' => $request->has('target_blank'),
            'enabled' => $request->has('enabled'),
            'position' => $request->input('position'),
            'pages' => $request->input('pages'),
            'priority' => $request->input('priority', 0),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'description' => $request->input('description'),
        ];

        // Update crop data if provided
        if ($request->has('crop_data')) {
            $data['crop_data'] = $request->input('crop_data') ? json_decode($request->input('crop_data'), true) : null;
            $data['display_width'] = $request->input('display_width');
            $data['display_height'] = $request->input('display_height');
        }

        // Upload new image if provided
        if ($request->hasFile('image')) {
            // Delete old image
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }

            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner updated successfully!');
    }

    /**
     * Toggle banner status.
     */
    public function toggle(Banner $banner)
    {
        $banner->update(['enabled' => ! $banner->enabled]);

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner status updated!');
    }

    /**
     * Remove the specified banner.
     */
    public function destroy(Banner $banner)
    {
        // Delete image file
        if ($banner->image) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner deleted successfully!');
    }

    /**
     * Track banner view.
     */
    public function trackView(Banner $banner)
    {
        $banner->incrementViews();

        return response()->json(['success' => true]);
    }

    /**
     * Track banner click.
     */
    public function trackClick(Banner $banner)
    {
        $banner->incrementClicks();

        return response()->json(['success' => true]);
    }
}
