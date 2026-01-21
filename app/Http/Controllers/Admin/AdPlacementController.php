<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdPlacement;
use Illuminate\Http\Request;

class AdPlacementController extends Controller
{
    /**
     * Display a listing of ad placements.
     */
    public function index()
    {
        $placements = AdPlacement::orderBy('priority', 'desc')->get();

        return view('admin.ads.index', compact('placements'));
    }

    /**
     * Show the form for creating a new ad placement.
     */
    public function create()
    {
        return view('admin.ads.create');
    }

    /**
     * Store a newly created ad placement.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ad_placements,slug',
            'code' => 'nullable|string',
            'position' => 'required|string',
            'pages' => 'required|array',
            'priority' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        AdPlacement::create([
            'name' => $request->input('name'),
            'slug' => $request->input('slug'),
            'code' => $request->input('code'),
            'enabled' => $request->has('enabled'),
            'position' => $request->input('position'),
            'pages' => $request->input('pages'),
            'priority' => $request->input('priority', 0),
            'description' => $request->input('description'),
        ]);

        return redirect()
            ->route('admin.ads.index')
            ->with('success', 'Ad placement created successfully!');
    }

    /**
     * Show the form for editing an ad placement.
     */
    public function edit(AdPlacement $ad)
    {
        return view('admin.ads.edit', compact('ad'));
    }

    /**
     * Update the specified ad placement.
     */
    public function update(Request $request, AdPlacement $ad)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:ad_placements,slug,'.$ad->id,
            'code' => 'nullable|string',
            'position' => 'required|string',
            'pages' => 'required|array',
            'priority' => 'nullable|integer',
            'description' => 'nullable|string',
        ]);

        $ad->update([
            'name' => $request->input('name'),
            'slug' => $request->input('slug'),
            'code' => $request->input('code'),
            'enabled' => $request->has('enabled'),
            'position' => $request->input('position'),
            'pages' => $request->input('pages'),
            'priority' => $request->input('priority', 0),
            'description' => $request->input('description'),
        ]);

        return redirect()
            ->route('admin.ads.index')
            ->with('success', 'Ad placement updated successfully!');
    }

    /**
     * Toggle ad placement status.
     */
    public function toggle(AdPlacement $ad)
    {
        $ad->update(['enabled' => ! $ad->enabled]);

        return redirect()
            ->route('admin.ads.index')
            ->with('success', 'Ad placement status updated!');
    }

    /**
     * Remove the specified ad placement.
     */
    public function destroy(AdPlacement $ad)
    {
        $ad->delete();

        return redirect()
            ->route('admin.ads.index')
            ->with('success', 'Ad placement deleted successfully!');
    }
}
